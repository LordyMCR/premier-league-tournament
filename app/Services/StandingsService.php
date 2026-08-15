<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;

class StandingsService
{
    /**
     * Build Premier League standings for a season from finished games.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forSeason(?Season $season = null): array
    {
        $season = $season ?? Season::current();

        if (!$season) {
            return [];
        }

        $teams = $season->teams()->orderBy('name')->get();
        if ($teams->isEmpty()) {
            // Fallback before season_team is populated
            $teams = Team::orderBy('name')->get();
        }

        $seasonGameWeekIds = $season->gameWeeks()->pluck('id');

        $standings = [];

        foreach ($teams as $team) {
            $homeGames = Game::where('home_team_id', $team->id)
                ->where('status', 'FINISHED')
                ->whereIn('game_week_id', $seasonGameWeekIds)
                ->get();
            $awayGames = Game::where('away_team_id', $team->id)
                ->where('status', 'FINISHED')
                ->whereIn('game_week_id', $seasonGameWeekIds)
                ->get();

            $played = $homeGames->count() + $awayGames->count();
            $wins = 0;
            $draws = 0;
            $losses = 0;
            $goalsFor = 0;
            $goalsAgainst = 0;

            foreach ($homeGames as $game) {
                $goalsFor += $game->home_score;
                $goalsAgainst += $game->away_score;

                if ($game->home_score > $game->away_score) {
                    $wins++;
                } elseif ($game->home_score == $game->away_score) {
                    $draws++;
                } else {
                    $losses++;
                }
            }

            foreach ($awayGames as $game) {
                $goalsFor += $game->away_score;
                $goalsAgainst += $game->home_score;

                if ($game->away_score > $game->home_score) {
                    $wins++;
                } elseif ($game->home_score == $game->away_score) {
                    $draws++;
                } else {
                    $losses++;
                }
            }

            $goalDifference = $goalsFor - $goalsAgainst;
            $points = ($wins * 3) + $draws;

            $allGames = $homeGames->concat($awayGames)->sortByDesc('kick_off_time')->take(5);
            $form = [];

            foreach ($allGames as $game) {
                if ($game->home_team_id == $team->id) {
                    if ($game->home_score > $game->away_score) {
                        $form[] = 'W';
                    } elseif ($game->home_score == $game->away_score) {
                        $form[] = 'D';
                    } else {
                        $form[] = 'L';
                    }
                } else {
                    if ($game->away_score > $game->home_score) {
                        $form[] = 'W';
                    } elseif ($game->home_score == $game->away_score) {
                        $form[] = 'D';
                    } else {
                        $form[] = 'L';
                    }
                }
            }

            while (count($form) < 5) {
                $form[] = null;
            }

            $standings[] = [
                'position' => 0,
                'team' => $team->name,
                'team_short' => $team->short_name ?? substr($team->name, 0, 3),
                'team_id' => $team->id,
                'team_logo' => $team->logo_url,
                'team_primary_color' => $team->primary_color,
                'played' => $played,
                'wins' => $wins,
                'draws' => $draws,
                'losses' => $losses,
                'goals_for' => $goalsFor,
                'goals_against' => $goalsAgainst,
                'goal_difference' => $goalDifference,
                'points' => $points,
                'form' => $form,
            ];
        }

        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            if ($a['goal_difference'] !== $b['goal_difference']) {
                return $b['goal_difference'] - $a['goal_difference'];
            }

            return $b['goals_for'] - $a['goals_for'];
        });

        foreach ($standings as $index => &$row) {
            $row['position'] = $index + 1;
        }

        return $standings;
    }
}
