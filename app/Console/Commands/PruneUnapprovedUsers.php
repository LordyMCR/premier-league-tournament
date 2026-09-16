<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PruneUnapprovedUsers extends Command
{
    protected $signature = 'users:prune-unapproved
                            {--days=7 : Only delete accounts older than this many days (0 = all ages)}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Permanently delete unapproved users who never joined a tournament';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $count = User::query()->unapprovedJunk($days > 0 ? $days : null)->count();

        if ($count === 0) {
            $this->info('No unapproved users to prune.');

            return self::SUCCESS;
        }

        $age = $days > 0 ? " older than {$days} day(s)" : ' of any age';

        if ($this->option('dry-run')) {
            $this->info("Would permanently delete {$count} unapproved user(s){$age}.");

            return self::SUCCESS;
        }

        $deleted = User::pruneUnapprovedJunk($days > 0 ? $days : null);
        $this->info("Permanently deleted {$deleted} unapproved user(s).");

        return self::SUCCESS;
    }
}
