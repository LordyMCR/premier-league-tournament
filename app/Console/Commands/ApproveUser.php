<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ApproveUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:approve {action=list : list, approve, or disapprove} {email? : Email of user to approve/disapprove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user approval status when restrictions are enabled';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $email = $this->argument('email');

        switch ($action) {
            case 'list':
                $this->listPendingUsers();
                break;
            
            case 'approve':
                if (!$email) {
                    $this->error('Email is required for approve action');
                    return 1;
                }
                $this->approveUser($email);
                break;
            
            case 'disapprove':
                if (!$email) {
                    $this->error('Email is required for disapprove action');
                    return 1;
                }
                $this->disapproveUser($email);
                break;
            
            default:
                $this->error('Invalid action. Use: list, approve, or disapprove');
                return 1;
        }

        return 0;
    }

    private function listPendingUsers()
    {
        if (!config('app.restrictions_enabled')) {
            $this->info('Restrictions are not enabled.');
            return;
        }

        $pendingUsers = User::where('is_approved', false)->get();

        if ($pendingUsers->isEmpty()) {
            $this->info('No pending user approvals.');
            return;
        }

        $this->info('Pending User Approvals:');
        $this->table(
            ['Name', 'Email', 'Registered At'],
            $pendingUsers->map(function ($user) {
                return [
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d H:i:s'),
                ];
            })
        );
    }

    private function approveUser($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return;
        }

        if ($user->is_approved) {
            $this->info("User {$email} is already approved.");
            return;
        }

        $user->approve();
        $this->info("User {$email} has been approved successfully.");
        $this->comment("Approval email notification has been sent to the user.");
    }

    private function disapproveUser($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return;
        }

        if (!$user->is_approved) {
            $this->info("User {$email} is already not approved.");
            return;
        }

        $user->update(['is_approved' => false, 'approved_at' => null]);
        $this->info("User {$email} approval has been revoked.");
    }
}
