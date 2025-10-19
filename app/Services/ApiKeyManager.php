<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ApiKeyManager
{
    private $apiKeys = [];
    private $currentKeyIndex = 0;

    public function __construct()
    {
        // Get primary API key
        $primaryKey = config('services.football_api.api_key');
        if ($primaryKey) {
            $this->apiKeys[] = $primaryKey;
        }

        // Get spare API keys
        $spareKeys = config('services.football_api.spare_keys', []);
        foreach ($spareKeys as $spareKey) {
            if ($spareKey) {
                $this->apiKeys[] = $spareKey;
            }
        }

        // Remove duplicates
        $this->apiKeys = array_unique($this->apiKeys);
    }

    /**
     * Make an API request with automatic fallback to spare keys
     */
    public function makeRequest(string $url, array $params = [], int $timeout = 10): ?array
    {
        $attempt = 0;
        $maxAttempts = count($this->apiKeys);

        while ($attempt < $maxAttempts) {
            $apiKey = $this->apiKeys[$this->currentKeyIndex];
            
            try {
                $response = Http::withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => 'v3.football.api-sports.io'
                ])
                ->timeout($timeout)
                ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if we got valid data (not just empty results due to rate limiting)
                    if ($this->isValidResponse($data)) {
                        Log::info("API request successful with key index {$this->currentKeyIndex}");
                        return $data;
                    } else {
                        Log::warning("API returned invalid/empty data with key index {$this->currentKeyIndex}");
                    }
                } else {
                    Log::warning("API request failed with key index {$this->currentKeyIndex}: {$response->status()}");
                }

            } catch (\Exception $e) {
                Log::error("API request exception with key index {$this->currentKeyIndex}: {$e->getMessage()}");
            }

            // Move to next API key
            $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);
            $attempt++;
        }

        Log::error("All API keys exhausted, no valid response received");
        return null;
    }

    /**
     * Check if the API response contains valid data
     */
    private function isValidResponse(array $data): bool
    {
        // Check if response has the expected structure and contains data
        if (!isset($data['response'])) {
            return false;
        }

        // For live matches, we expect either live matches or an empty array
        // Empty array is valid (no live matches), but missing 'response' key indicates API issues
        return true;
    }

    /**
     * Get current API key being used
     */
    public function getCurrentKey(): ?string
    {
        return $this->apiKeys[$this->currentKeyIndex] ?? null;
    }

    /**
     * Get total number of available API keys
     */
    public function getTotalKeys(): int
    {
        return count($this->apiKeys);
    }

    /**
     * Reset to primary API key
     */
    public function resetToPrimary(): void
    {
        $this->currentKeyIndex = 0;
    }
}
