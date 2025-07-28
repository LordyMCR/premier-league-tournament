<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TestModeService;
use App\Services\FootballDataService;
use App\Models\GameWeek;
use App\Models\Game;
use Illuminate\Support\Facades\File;

class EnableTestMode extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tournament:enable-test-mode {--disable : Disable test mode instead}';

    /**
     * The console command description.
     */
    protected $description = 'Enable tournament test mode with compressed timeline for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disable = $this->option('disable');
        
        if ($disable) {
            $this->disableTestMode();
        } else {
            $this->enableTestMode();
        }
    }

    private function enableTestMode()
    {
        $this->info('🧪 Enabling Tournament Test Mode...');
        
        // Update .env file
        $this->updateEnvFile('TOURNAMENT_TEST_MODE', 'true');
        
        $this->info('✅ Test mode enabled in .env file');
        
        // Clear config cache to reload new values
        $this->call('config:clear');
        
        // Update gameweeks and games with test mode timing
        $this->info('📅 Updating gameweeks with compressed timeline...');
        $this->call('football:update', ['--gameweeks' => true, '--games' => true, '--force' => true]);
        
        $settings = TestModeService::getSettings();
        
        $this->line('');
        $this->info('🎯 Test Mode Configuration:');
        $this->line("   • Each gameweek lasts: {$settings['gameweek_duration_minutes']} minutes");
        $this->line("   • Selection window: {$settings['selection_window_minutes']} minutes");  
        $this->line("   • Games every: {$settings['games_spacing_minutes']} minutes");
        $this->line("   • Break between gameweeks: {$settings['break_between_gameweeks_minutes']} minutes");
        $this->line("   • Total tournament duration: " . TestModeService::getTotalTestDuration());
        
        $this->line('');
        $this->info('✅ Test mode is now active! You can test the complete tournament flow quickly.');
        $this->warn('💡 Remember to disable test mode when you want to return to real schedule.');
    }

    private function disableTestMode()
    {
        $this->info('🔄 Disabling Tournament Test Mode...');
        
        // Update .env file
        $this->updateEnvFile('TOURNAMENT_TEST_MODE', 'false');
        
        $this->info('✅ Test mode disabled in .env file');
        
        // Clear config cache
        $this->call('config:clear');
        
        // Update gameweeks and games with real timing
        $this->info('📅 Restoring real Premier League schedule...');
        $this->call('football:update', ['--gameweeks' => true, '--games' => true, '--force' => true]);
        
        $this->info('✅ Tournament restored to real Premier League schedule.');
    }

    private function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->error('.env file not found!');
            return;
        }
        
        $envContent = File::get($envPath);
        
        // Check if key already exists
        if (preg_match("/^{$key}=.*$/m", $envContent)) {
            // Replace existing value
            $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
        } else {
            // Add new key-value pair
            $envContent .= "\n{$key}={$value}";
        }
        
        File::put($envPath, $envContent);
    }
}
