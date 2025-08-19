<?php

namespace App\Observers;

use App\Models\Pick;
use App\Models\UserStatistic;

class PickObserver
{
    /**
     * Handle the Pick "created" event.
     */
    public function created(Pick $pick): void
    {
        $this->recalculateUserStats($pick);
    }

    /**
     * Handle the Pick "updated" event.
     */
    public function updated(Pick $pick): void
    {
        $this->recalculateUserStats($pick);
    }

    /**
     * Handle the Pick "deleted" event.
     */
    public function deleted(Pick $pick): void
    {
        $this->recalculateUserStats($pick);
    }

    /**
     * Handle the Pick "restored" event.
     */
    public function restored(Pick $pick): void
    {
        $this->recalculateUserStats($pick);
    }

    /**
     * Handle the Pick "force deleted" event.
     */
    public function forceDeleted(Pick $pick): void
    {
        $this->recalculateUserStats($pick);
    }

    /**
     * Recalculate user statistics when picks change
     */
    private function recalculateUserStats(Pick $pick): void
    {
        UserStatistic::recalculateForUser($pick->user_id);
    }
}
