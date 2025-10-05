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
        // If this is the user's first tournament, set it as favorite
        $userTournamentCount = TournamentParticipant::where('user_id', $tournamentParticipant->user_id)->count();
        if ($userTournamentCount === 1) {
            $tournamentParticipant->update(['is_favorite' => true]);
        }

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
