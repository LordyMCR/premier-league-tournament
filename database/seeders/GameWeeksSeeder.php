<?php

namespace Database\Seeders;

use App\Models\GameWeek;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GameWeeksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Start from this week
        $startDate = Carbon::now()->startOfWeek();
        
        for ($week = 1; $week <= 20; $week++) {
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
