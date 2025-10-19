<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FootballDataApiKeyManager
{
    protected $apiKeys = [];
    protected $currentKeyIndex = 0;

    public function __construct()
    {
        $mainKey = config('services.football_data.api_key');
        if ($mainKey) {
            $this->apiKeys[] = $mainKey;
        }

        $spareKeys = config('services.football_data.spare_keys', []);
        foreach ($spareKeys as $key) {
            if ($key) {
                $this->apiKeys[] = $key;
            }
        }
    }

    public function getTotalKeys(): int
    {
        return count($this->apiKeys);
    }

    public function getNextKey(): ?string
    {
        if (empty($this->apiKeys)) {
            return null;
        }

        $key = $this->apiKeys[$this->currentKeyIndex];
        $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys); // Cycle through keys
        return $key;
    }

    public function makeRequest(string $url, array $params = []): ?array
    {
        $initialKeyIndex = $this->currentKeyIndex;
        $bestResponse = null;
        $bestTimestamp = null;
        
        do {
            $apiKey = $this->getNextKey();
            if (!$apiKey) {
                Log::warning('FootballDataApiKeyManager: No API keys available for request.');
                return null;
            }

            try {
                $response = Http::withHeaders([
                    'X-Auth-Token' => $apiKey,
                ])
                ->timeout(10)
                ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if this response has fresh data
                    $responseTimestamp = $this->getResponseTimestamp($data);
                    
                    $minutesOld = $responseTimestamp ? $responseTimestamp->diffInMinutes(now()) : 'unknown';
                    Log::info("FootballDataApiKeyManager: Success with key ending in " . substr($apiKey, -5) . ". Timestamp: " . $responseTimestamp . " (Age: {$minutesOld} minutes)");
                    
                    // Keep the response with the most recent timestamp
                    if ($bestTimestamp === null || $responseTimestamp > $bestTimestamp) {
                        $bestResponse = $data;
                        $bestTimestamp = $responseTimestamp;
                    }
                    
                    // Reject data that's more than 30 minutes old (increased from 15 to handle delayed API updates)
                    if ($responseTimestamp && $responseTimestamp->diffInMinutes(now()) > 30) {
                        Log::warning("FootballDataApiKeyManager: Rejecting stale data from key ending in " . substr($apiKey, -5) . ". Age: {$minutesOld} minutes");
                        continue;
                    }
                    
                    // If we have very fresh data (within last 5 minutes), use it immediately
                    if ($responseTimestamp && $responseTimestamp->diffInMinutes(now()) <= 5) {
                        Log::info("FootballDataApiKeyManager: Using fresh data from key ending in " . substr($apiKey, -5));
                        return $data;
                    }
                } else {
                    Log::warning("FootballDataApiKeyManager: API request failed with key ending in " . substr($apiKey, -5) . ". Status: " . $response->status() . ". Response: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("FootballDataApiKeyManager: Exception with key ending in " . substr($apiKey, -5) . ". Error: " . $e->getMessage());
            }
        } while ($this->currentKeyIndex !== $initialKeyIndex); // Try all keys once

        if ($bestResponse) {
            Log::info("FootballDataApiKeyManager: Using best available response with timestamp: " . $bestTimestamp);
            return $bestResponse;
        }

        Log::error('FootballDataApiKeyManager: All API keys failed to make a successful request.');
        return null;
    }

    /**
     * Extract the most recent timestamp from API response
     */
    private function getResponseTimestamp(array $data): ?\Carbon\Carbon
    {
        $latestTimestamp = null;
        
        foreach ($data['matches'] ?? [] as $match) {
            if (isset($match['lastUpdated'])) {
                $timestamp = \Carbon\Carbon::parse($match['lastUpdated']);
                if ($latestTimestamp === null || $timestamp->gt($latestTimestamp)) {
                    $latestTimestamp = $timestamp;
                }
            }
        }
        
        return $latestTimestamp;
    }
}
