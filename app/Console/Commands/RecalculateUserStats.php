<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserStatistic;

class RecalculateUserStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:recalculate {--user-id= : Recalculate for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate user tournament statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($userId = $this->option('user-id')) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            $this->info("Recalculating statistics for user: {$user->name}");
            UserStatistic::recalculateForUser($userId);
            $this->info("✅ Statistics updated for {$user->name}");
            
        } else {
            $users = User::all();
            $this->info("Recalculating statistics for {$users->count()} users...");
            
            $progressBar = $this->output->createProgressBar($users->count());
            
            foreach ($users as $user) {
                UserStatistic::recalculateForUser($user->id);
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            $this->info("✅ All user statistics have been recalculated!");
        }
        
        return 0;
    }
}
