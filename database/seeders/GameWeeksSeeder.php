<?php

namespace Database\Seeders;

use App\Models\GameWeek;
use App\Services\FootballDataService;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GameWeeksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $footballService = new FootballDataService();

        if (!$footballService->isConfigured()) {
            $this->command->warn('Football Data API key not configured. Using fallback data.');
            $this->seedFallbackGameweeks();
            return;
        }

        $this->command->info('Fetching Premier League fixtures from API...');
        $gameweeks = $footballService->getPremierLeagueFixtures();

        if (empty($gameweeks)) {
            $this->command->warn('Failed to fetch fixtures from API. Using fallback data.');
            $this->seedFallbackGameweeks();
            return;
        }

        $this->command->info('Seeding ' . count($gameweeks) . ' gameweeks...');

        foreach ($gameweeks as $gameweek) {
            GameWeek::updateOrCreate(
                ['week_number' => $gameweek['week_number']],
                $gameweek
            );
        }

        $this->command->info('Gameweeks seeded successfully!');
    }

    /**
     * Fallback method to seed gameweeks when API is unavailable
     */
    private function seedFallbackGameweeks(): void
    {
        // Start from this week
        $startDate = Carbon::now()->startOfWeek();
        
        for ($week = 1; $week <= 38; $week++) {
            // Each game week runs for 7 days (Saturday to Friday)
            $weekStart = $startDate->copy()->addWeeks($week - 1);
            $weekEnd = $weekStart->copy()->addDays(6);
            
            GameWeek::updateOrCreate(
                ['week_number' => $week],
                [
                    'name' => "Gameweek {$week}",
                    'start_date' => $weekStart->toDateString(),
                    'end_date' => $weekEnd->toDateString(),
                    'is_completed' => false,
                ]
            );
        }
    }
}
