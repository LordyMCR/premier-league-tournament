<?php

namespace App\Observers;

use App\Models\TournamentParticipant;
use App\Models\UserStatistic;

class TournamentParticipantObserver
{
    /**
     * Handle the TournamentParticipant "created" event.
     */
    public function created(TournamentParticipant $tournamentParticipant): void
    {
        $this->recalculateUserStats($tournamentParticipant);
    }

    /**
     * Handle the TournamentParticipant "updated" event.
     */
    public function updated(TournamentParticipant $tournamentParticipant): void
    {
        $this->recalculateUserStats($tournamentParticipant);
    }

    /**
     * Handle the TournamentParticipant "deleted" event.
     */
    public function deleted(TournamentParticipant $tournamentParticipant): void
    {
        $this->recalculateUserStats($tournamentParticipant);
    }

    /**
     * Handle the TournamentParticipant "restored" event.
     */
    public function restored(TournamentParticipant $tournamentParticipant): void
    {
        $this->recalculateUserStats($tournamentParticipant);
    }

    /**
     * Handle the TournamentParticipant "force deleted" event.
     */
    public function forceDeleted(TournamentParticipant $tournamentParticipant): void
    {
        $this->recalculateUserStats($tournamentParticipant);
    }

    /**
     * Recalculate user statistics when tournament participation changes
     */
    private function recalculateUserStats(TournamentParticipant $tournamentParticipant): void
    {
        UserStatistic::recalculateForUser($tournamentParticipant->user_id);
    }
}
