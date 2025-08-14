<?php

namespace App\Http\Controllers;

use App\Models\GameWeek;
use App\Models\Game;
use App\Models\Team;
use App\Services\HistoricalDataService;
use App\Services\TeamNewsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ScheduleController extends Controller
{
    /**
     * Display the Premier League schedule
     */
    public function index(Request $request)
    {
        $currentDate = now();
        
        // Get all gameweeks with their games
        $gameweeks = GameWeek::with([
            'games' => function ($query) {
                $query->with(['homeTeam', 'awayTeam'])
                      ->orderBy('kick_off_time');
            }
        ])
        ->orderBy('week_number')
        ->get();

        // Get current and upcoming gameweeks
        $currentGameweek = GameWeek::getCurrentGameWeek();
        $nextGameweek = GameWeek::getNextGameWeek();
        
        // Get teams for filtering/reference
        $teams = Team::orderBy('name')->get();
        
        // Calculate some statistics
        $totalGames = Game::count();
        $completedGames = Game::where('status', 'FINISHED')->count();
        $upcomingGames = Game::where('status', 'SCHEDULED')->count();
        
        // Get recent and upcoming games for highlights
        $recentGames = Game::with(['homeTeam', 'awayTeam'])
            ->where('status', 'FINISHED')
            ->orderBy('kick_off_time', 'desc')
            ->limit(6)
            ->get();
            
        $upcomingHighlights = Game::with(['homeTeam', 'awayTeam'])
            ->where('status', 'SCHEDULED')
            ->where('kick_off_time', '>', $currentDate)
            ->orderBy('kick_off_time')
            ->limit(6)
            ->get();

        return Inertia::render('Schedule/Index', [
            'gameweeks' => $gameweeks,
            'currentGameweek' => $currentGameweek,
            'nextGameweek' => $nextGameweek,
            'teams' => $teams,
            'stats' => [
                'total_games' => $totalGames,
                'completed_games' => $completedGames,
                'upcoming_games' => $upcomingGames,
                'completion_percentage' => $totalGames > 0 ? round(($completedGames / $totalGames) * 100, 1) : 0
            ],
            'recentGames' => $recentGames,
            'upcomingHighlights' => $upcomingHighlights,
        ]);
    }

    /**
     * Display a specific gameweek
     */
    public function gameweek(GameWeek $gameweek)
    {
        $gameweek->load([
            'games' => function ($query) {
                $query->with(['homeTeam', 'awayTeam'])
                      ->orderBy('kick_off_time');
            }
        ]);

        // Get previous and next gameweeks for navigation
        $previousGameweek = GameWeek::where('week_number', '<', $gameweek->week_number)
            ->orderBy('week_number', 'desc')
            ->first();
            
        $nextGameweek = GameWeek::where('week_number', '>', $gameweek->week_number)
            ->orderBy('week_number')
            ->first();

        return Inertia::render('Schedule/Gameweek', [
            'gameweek' => $gameweek,
            'previousGameweek' => $previousGameweek,
            'nextGameweek' => $nextGameweek,
        ]);
    }

    /**
     * Display fixtures for a specific team
     */
    public function team(Request $request, Team $team)
    {
        $games = Game::with(['homeTeam', 'awayTeam', 'gameWeek'])
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->orderBy('kick_off_time')
            ->get();

        // Separate into completed and upcoming
        $completedGames = $games->where('status', 'FINISHED');
        $upcomingGames = $games->where('status', 'SCHEDULED');

        // Calculate team statistics
        $stats = [
            'games_played' => $completedGames->count(),
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
        ];

        foreach ($completedGames as $game) {
            if ($game->home_team_id == $team->id) {
                $stats['goals_for'] += $game->home_score ?? 0;
                $stats['goals_against'] += $game->away_score ?? 0;
                
                if (($game->home_score ?? 0) > ($game->away_score ?? 0)) {
                    $stats['wins']++;
                } elseif (($game->home_score ?? 0) < ($game->away_score ?? 0)) {
                    $stats['losses']++;
                } else {
                    $stats['draws']++;
                }
            } else {
                $stats['goals_for'] += $game->away_score ?? 0;
                $stats['goals_against'] += $game->home_score ?? 0;
                
                if (($game->away_score ?? 0) > ($game->home_score ?? 0)) {
                    $stats['wins']++;
                } elseif (($game->away_score ?? 0) < ($game->home_score ?? 0)) {
                    $stats['losses']++;
                } else {
                    $stats['draws']++;
                }
            }
        }

        $stats['points'] = ($stats['wins'] * 3) + $stats['draws'];
        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        // Enhanced analytics
        $analytics = $this->calculateTeamAnalytics($team, $completedGames, $upcomingGames);

        // Phase 2B: Team News Integration
        $teamNewsService = app(TeamNewsService::class);
        $teamNews = [
            'news' => $teamNewsService->getTeamNews($team->name, 5),
            'injuries' => $teamNewsService->getTeamInjuries($team->id),
        ];

        // Phase 2C: Squad Information
        $squadService = app(\App\Services\SquadService::class);
        $squadData = [
            'squad_by_position' => $squadService->getSquadByPosition($team),
        ];

        // Get referer to determine back button
        $referer = $request->header('referer');

        return Inertia::render('Schedule/Team', [
            'team' => $team,
            'completedGames' => $completedGames,
            'upcomingGames' => $upcomingGames,
            'stats' => $stats,
            'analytics' => $analytics,
            'teamNews' => $teamNews,
            'squadData' => $squadData,
            'referer' => $referer,
        ]);
    }

    /**
     * Calculate enhanced team analytics
     */
    private function calculateTeamAnalytics(Team $team, $completedGames, $upcomingGames = null)
    {
        $analytics = [
            'recent_form' => [],
            'home_stats' => ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0],
            'away_stats' => ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0],
            'monthly_performance' => [],
            'goal_trends' => [],
            'clean_sheets' => 0,
            'average_goals_per_game' => 0,
            'average_goals_conceded' => 0,
            'upcoming_fixtures_analysis' => [],
            'head_to_head_records' => [],
        ];

        // Recent form (last 6 games)
        $recentGames = collect($completedGames)->sortBy('kick_off_time')->take(-6);
        foreach ($recentGames as $game) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? ($game->home_score ?? 0) : ($game->away_score ?? 0);
            $opponentScore = $isHome ? ($game->away_score ?? 0) : ($game->home_score ?? 0);

            if ($teamScore > $opponentScore) {
                $analytics['recent_form'][] = ['result' => 'W', 'class' => 'bg-green-500'];
            } elseif ($teamScore < $opponentScore) {
                $analytics['recent_form'][] = ['result' => 'L', 'class' => 'bg-red-500'];
            } else {
                $analytics['recent_form'][] = ['result' => 'D', 'class' => 'bg-yellow-500'];
            }
        }

        // Home vs Away analysis & Goal trends
        foreach ($completedGames as $game) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? ($game->home_score ?? 0) : ($game->away_score ?? 0);
            $opponentScore = $isHome ? ($game->away_score ?? 0) : ($game->home_score ?? 0);
            
            $isHomeGame = $isHome;
            if ($isHomeGame) {
                $analytics['home_stats']['played']++;
                $analytics['home_stats']['goals_for'] += $teamScore;
                $analytics['home_stats']['goals_against'] += $opponentScore;

                if ($teamScore > $opponentScore) {
                    $analytics['home_stats']['wins']++;
                } elseif ($teamScore < $opponentScore) {
                    $analytics['home_stats']['losses']++;
                } else {
                    $analytics['home_stats']['draws']++;
                }
            } else {
                $analytics['away_stats']['played']++;
                $analytics['away_stats']['goals_for'] += $teamScore;
                $analytics['away_stats']['goals_against'] += $opponentScore;

                if ($teamScore > $opponentScore) {
                    $analytics['away_stats']['wins']++;
                } elseif ($teamScore < $opponentScore) {
                    $analytics['away_stats']['losses']++;
                } else {
                    $analytics['away_stats']['draws']++;
                }
            }

            // Clean sheets
            if ($opponentScore === 0) {
                $analytics['clean_sheets']++;
            }

            // Goal trends by gameweek
            $analytics['goal_trends'][] = [
                'gameweek' => $game->game_week->week_number,
                'goals_scored' => $teamScore,
                'goals_conceded' => $opponentScore,
                'date' => $game->kick_off_time
            ];
        }

        // Calculate averages
        $totalGames = count($completedGames);
        if ($totalGames > 0) {
            $totalGoalsFor = $analytics['home_stats']['goals_for'] + $analytics['away_stats']['goals_for'];
            $totalGoalsAgainst = $analytics['home_stats']['goals_against'] + $analytics['away_stats']['goals_against'];
            
            $analytics['average_goals_per_game'] = round($totalGoalsFor / $totalGames, 1);
            $analytics['average_goals_conceded'] = round($totalGoalsAgainst / $totalGames, 1);
        }

        // Calculate points for home/away
        $analytics['home_stats']['points'] = ($analytics['home_stats']['wins'] * 3) + $analytics['home_stats']['draws'];
        $analytics['away_stats']['points'] = ($analytics['away_stats']['wins'] * 3) + $analytics['away_stats']['draws'];

        // Monthly performance
        $monthlyData = [];
        foreach ($completedGames as $game) {
            $month = date('Y-m', strtotime($game->kick_off_time));
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0];
            }

            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? ($game->home_score ?? 0) : ($game->away_score ?? 0);
            $opponentScore = $isHome ? ($game->away_score ?? 0) : ($game->home_score ?? 0);

            $monthlyData[$month]['played']++;
            $monthlyData[$month]['goals_for'] += $teamScore;
            $monthlyData[$month]['goals_against'] += $opponentScore;

            if ($teamScore > $opponentScore) {
                $monthlyData[$month]['wins']++;
            } elseif ($teamScore < $opponentScore) {
                $monthlyData[$month]['losses']++;
            } else {
                $monthlyData[$month]['draws']++;
            }
        }

        foreach ($monthlyData as $month => $data) {
            $analytics['monthly_performance'][] = [
                'month' => $month,
                'month_name' => date('M Y', strtotime($month . '-01')),
                'played' => $data['played'],
                'wins' => $data['wins'],
                'draws' => $data['draws'],
                'losses' => $data['losses'],
                'goals_for' => $data['goals_for'],
                'goals_against' => $data['goals_against'],
                'points' => ($data['wins'] * 3) + $data['draws'],
                'win_percentage' => $data['played'] > 0 ? round(($data['wins'] / $data['played']) * 100, 1) : 0
            ];
        }

        // Upcoming fixtures analysis
        if ($upcomingGames) {
            $analytics['upcoming_fixtures_analysis'] = $this->analyzeUpcomingFixtures($team, $upcomingGames);
        }

        return $analytics;
    }

    /**
     * Analyze upcoming fixtures for difficulty and opponent form
     */
    private function analyzeUpcomingFixtures(Team $team, $upcomingGames)
    {
        $analysis = [];
        $historicalService = app(HistoricalDataService::class);
        
        foreach ($upcomingGames->take(5) as $game) {
            // Ensure teams are loaded
            if (!$game->relationLoaded('homeTeam')) {
                $game->load('homeTeam');
            }
            if (!$game->relationLoaded('awayTeam')) {
                $game->load('awayTeam');
            }
            
            $opponent = $game->home_team_id === $team->id ? $game->awayTeam : $game->homeTeam;
            $isHome = $game->home_team_id === $team->id;
            
            // Skip if opponent is null
            if (!$opponent) {
                continue;
            }
            
            // Get opponent's recent form (current season + historical)
            $opponentForm = $historicalService->getTeamRecentForm($opponent->name, 6);
            
            // Get current season opponent games for additional stats
            $opponentGames = Game::with(['homeTeam', 'awayTeam', 'gameWeek'])
                ->where(function ($query) use ($opponent) {
                    $query->where('home_team_id', $opponent->id)
                          ->orWhere('away_team_id', $opponent->id);
                })
                ->where('status', 'FINISHED')
                ->orderBy('kick_off_time', 'desc')
                ->limit(6)
                ->get();

            // Calculate opponent stats
            $opponentStats = [
                'wins' => 0, 'draws' => 0, 'losses' => 0, 
                'goals_for' => 0, 'goals_against' => 0,
                'recent_form' => $opponentForm,
                'has_recent_data' => !empty($opponentForm)
            ];

            // If we have current season data, use it for stats
            if (count($opponentGames) > 0) {
                foreach ($opponentGames as $opponentGame) {
                    $oppIsHome = $opponentGame->home_team_id === $opponent->id;
                    $oppScore = $oppIsHome ? ($opponentGame->home_score ?? 0) : ($opponentGame->away_score ?? 0);
                    $enemyScore = $oppIsHome ? ($opponentGame->away_score ?? 0) : ($opponentGame->home_score ?? 0);

                    $opponentStats['goals_for'] += $oppScore;
                    $opponentStats['goals_against'] += $enemyScore;

                    if ($oppScore > $enemyScore) {
                        $opponentStats['wins']++;
                    } elseif ($oppScore < $enemyScore) {
                        $opponentStats['losses']++;
                    } else {
                        $opponentStats['draws']++;
                    }
                }
            }

            $opponentStats['points'] = ($opponentStats['wins'] * 3) + $opponentStats['draws'];
            $gamesPlayed = count($opponentGames);
            $opponentStats['form_percentage'] = $gamesPlayed > 0 ? 
                round((($opponentStats['wins'] * 3 + $opponentStats['draws']) / ($gamesPlayed * 3)) * 100, 1) : 0;

            // Get difficulty rating using historical data
            $difficultyData = $historicalService->calculateDifficultyRating($opponent->name);
            $difficulty = $difficultyData['difficulty'];
            $difficultyClass = $difficultyData['class'];
            $finalRating = $difficultyData['rating'];

            // Enhanced difficulty calculation considering multiple factors
            $factors = [];
            $weights = [];

            // Historical strength (40% weight)
            $factors[] = $difficultyData['rating'];
            $weights[] = 0.4;

            // Current season form (35% weight if we have data)
            if ($opponentStats['has_recent_data'] && $gamesPlayed >= 3) {
                $factors[] = $opponentStats['form_percentage'];
                $weights[] = 0.35;
            } else {
                // If no current form, give more weight to historical data
                $weights[0] = 0.6;
            }

            // Head-to-head factor (25% weight)
            $h2hRecord = $this->getHeadToHeadRecordWithHistory($team, $opponent, $historicalService);
            $h2hRating = 50; // Default neutral rating
            
            if ($h2hRecord['total_games'] > 0) {
                $totalGames = $h2hRecord['total_games'];
                $teamPerformance = ($h2hRecord['wins'] * 3 + $h2hRecord['draws']) / ($totalGames * 3);
                // Invert the rating - if team performs well against opponent, opponent is "easier"
                $h2hRating = (1 - $teamPerformance) * 100;
                
                $factors[] = $h2hRating;
                $weights[] = 0.25;
            }

            // Calculate weighted average
            if (count($factors) > 1) {
                $totalWeight = array_sum($weights);
                $weightedSum = 0;
                
                for ($i = 0; $i < count($factors); $i++) {
                    $weightedSum += $factors[$i] * $weights[$i];
                }
                
                $finalRating = $weightedSum / $totalWeight;
            }

            // Additional adjustments: venue and sample-size-aware head-to-head dominance
            // Venue: away games are generally tougher, home slightly easier
            $venueAdjustment = $isHome ? -5 : 10; // negative lowers difficulty, positive raises
            $finalRating += $venueAdjustment;

            // Head-to-head dominance bonus/penalty with confidence by sample size
            $h2hAdjustment = 0; // negative reduces difficulty, positive increases
            if ($h2hRecord['total_games'] > 0) {
                $totalGames = $h2hRecord['total_games'];
                $confidence = min(1.0, $totalGames / 6.0); // more meetings → more confidence (cap at 6)
                $teamPerformance = ($h2hRecord['wins'] * 3 + $h2hRecord['draws']) / ($totalGames * 3); // 0..1
                $dominance = ($teamPerformance - 0.5) * 2; // -1..1 (positive means favorable H2H)

                if ($dominance > 0) {
                    // Reward for historical dominance; stronger effect with more meetings
                    $h2hAdjustment = -((5 + 10 * $confidence) * $dominance);
                } elseif ($dominance < 0) {
                    // Penalize if historically poor vs opponent
                    $h2hAdjustment = (6 * $confidence) * abs($dominance);
                }

                $finalRating += $h2hAdjustment;
            }

            // Clamp to sane bounds
            $finalRating = max(0, min(100, $finalRating));

            // Determine difficulty label and class based on final rating
            if ($finalRating >= 75) {
                $difficulty = 'Very Hard';
                $difficultyClass = 'bg-red-800 text-white font-bold shadow-lg';
            } elseif ($finalRating >= 60) {
                $difficulty = 'Hard';
                $difficultyClass = 'bg-red-600 text-white font-bold shadow-md';
            } elseif ($finalRating >= 45) {
                $difficulty = 'Medium';
                $difficultyClass = 'bg-yellow-500 text-black font-bold shadow-md';
            } elseif ($finalRating >= 30) {
                $difficulty = 'Easy';
                $difficultyClass = 'bg-green-500 text-white font-bold shadow-md';
            } else {
                $difficulty = 'Very Easy';
                $difficultyClass = 'bg-green-700 text-white font-bold shadow-lg';
            }

            // Get head-to-head record using historical data - already retrieved above
            

            $analysis[] = [
                'game' => $game,
                'opponent' => $opponent,
                'is_home' => $isHome,
                'venue' => $isHome ? 'Home' : 'Away',
                'difficulty' => $difficulty,
                'difficulty_class' => $difficultyClass,
                'difficulty_rating' => round($finalRating, 1),
                'opponent_stats' => $opponentStats,
                'head_to_head' => $h2hRecord,
                'analysis_factors' => [
                    'historical_strength' => $difficultyData['rating'],
                    'current_form' => $opponentStats['form_percentage'],
                    'h2h_factor' => isset($h2hRating) ? round($h2hRating, 1) : null,
                    'venue_adjustment' => $venueAdjustment,
                    'h2h_adjustment' => round($h2hAdjustment, 1),
                    'games_played_vs_opponent' => $h2hRecord['total_games'],
                ]
            ];
        }

        return $analysis;
    }

    /**
     * Get head-to-head record with historical data
     */
    private function getHeadToHeadRecordWithHistory(Team $team, Team $opponent, HistoricalDataService $historicalService)
    {
        // Get current season record
        $currentRecord = $this->getHeadToHeadRecord($team, $opponent);
        
        // Get historical record
        $historicalRecord = $historicalService->getHeadToHeadRecord($team->name, $opponent->name);
        
        // If we have historical data, use it; otherwise fall back to current season
        if ($historicalRecord['total_games'] > 0) {
            return [
                'wins' => $historicalRecord['team_wins'],
                'draws' => $historicalRecord['draws'],
                'losses' => $historicalRecord['team_losses'],
                'total_games' => $historicalRecord['total_games'],
                'recent_record' => $historicalRecord['recent_record'],
                'data_source' => 'historical'
            ];
        }
        
        // Fall back to current season data
        return [
            'wins' => $currentRecord['wins'],
            'draws' => $currentRecord['draws'],
            'losses' => $currentRecord['losses'],
            'total_games' => $currentRecord['total_games'],
            'recent_record' => $currentRecord['recent_record'],
            'data_source' => 'current_season'
        ];
    }

    /**
     * Get head-to-head record between two teams
     */
    private function getHeadToHeadRecord(Team $teamA, Team $teamB)
    {
        // First try to get data from our database (current season)
        $h2hGames = Game::where(function ($query) use ($teamA, $teamB) {
            $query->where('home_team_id', $teamA->id)->where('away_team_id', $teamB->id);
        })->orWhere(function ($query) use ($teamA, $teamB) {
            $query->where('home_team_id', $teamB->id)->where('away_team_id', $teamA->id);
        })
        ->where('status', 'FINISHED')
        ->orderBy('kick_off_time', 'desc')
        ->limit(5)
        ->get();

        $record = ['wins' => 0, 'draws' => 0, 'losses' => 0, 'recent_meetings' => [], 'recent_record' => [], 'has_historical_data' => false, 'total_games' => 0];

        // Process current season games
        foreach ($h2hGames as $game) {
            $teamAIsHome = $game->home_team_id === $teamA->id;
            $homeScore = $game->home_score ?? 0;
            $awayScore = $game->away_score ?? 0;

            // Always show the actual historical result: home team score vs away team score
            $displayScore = "{$homeScore}-{$awayScore}";

            $record['recent_meetings'][] = [
                'date' => $game->kick_off_time,
                'score' => $displayScore,
                'venue' => $teamAIsHome ? 'H' : 'A'
            ];

            // Also add to recent_record for consistency with historical data
            if ($teamAIsHome) {
                if ($homeScore > $awayScore) {
                    $result = 'W';
                } elseif ($homeScore < $awayScore) {
                    $result = 'L';
                } else {
                    $result = 'D';
                }
            } else {
                if ($awayScore > $homeScore) {
                    $result = 'W';
                } elseif ($awayScore < $homeScore) {
                    $result = 'L';
                } else {
                    $result = 'D';
                }
            }

            $record['recent_record'][] = [
                'result' => $result,
                'score' => $displayScore,
                'venue' => $teamAIsHome ? 'H' : 'A',
                'date' => $game->kick_off_time
            ];

            if ($teamAIsHome) {
                if ($homeScore > $awayScore) {
                    $record['wins']++;
                } elseif ($homeScore < $awayScore) {
                    $record['losses']++;
                } else {
                    $record['draws']++;
                }
            } else {
                if ($awayScore > $homeScore) {
                    $record['wins']++;
                } elseif ($awayScore < $homeScore) {
                    $record['losses']++;
                } else {
                    $record['draws']++;
                }
            }
        }

        // Set total games count
        $record['total_games'] = $record['wins'] + $record['draws'] + $record['losses'];

        // If no current season data, try to get historical data from API
        if (count($h2hGames) === 0 && $teamA->external_id && $teamB->external_id) {
            try {
                $footballService = app(\App\Services\FootballDataService::class);
                $apiH2H = $footballService->getHeadToHeadData($teamA->external_id, $teamB->external_id);
                
                if ($apiH2H['total_matches'] > 0) {
                    // The API gives us overall head-to-head, but we need to calculate from Team A's perspective
                    // For now, let's just show the total matches and indicate it's historical data
                    // We'll calculate the actual W-D-L when we can determine which team is which in the API data
                    $record['has_historical_data'] = true;
                    $record['total_matches'] = $apiH2H['total_matches'];
                    $record['historical_note'] = "Historical data available ({$apiH2H['total_matches']} matches)";
                }
            } catch (\Exception $e) {
                Log::error('Error fetching head-to-head data', [
                    'teamA' => $teamA->id,
                    'teamB' => $teamB->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $record;
    }

    /**
     * Display a specific match
     */
    public function match(Game $game)
    {
        $game->load(['homeTeam', 'awayTeam', 'gameWeek']);
        
        $historicalService = app(HistoricalDataService::class);
        
        // Get team stats for both teams (with historical data)
        $homeTeamStats = $this->getTeamMatchStatsWithHistory($game->homeTeam, $historicalService);
        $awayTeamStats = $this->getTeamMatchStatsWithHistory($game->awayTeam, $historicalService);
        
        // Get head-to-head record with historical data
        $headToHead = $this->getHeadToHeadRecordWithHistory($game->homeTeam, $game->awayTeam, $historicalService);
        
        // Get recent form for both teams (with historical data)
        $homeTeamForm = $this->getTeamRecentFormWithHistory($game->homeTeam, $historicalService);
        $awayTeamForm = $this->getTeamRecentFormWithHistory($game->awayTeam, $historicalService);

        return Inertia::render('Schedule/Match', [
            'game' => $game,
            'homeTeamStats' => $homeTeamStats,
            'awayTeamStats' => $awayTeamStats,
            'headToHead' => $headToHead,
            'homeTeamForm' => $homeTeamForm,
            'awayTeamForm' => $awayTeamForm,
        ]);
    }

    /**
     * Get team stats for match page
     */
    private function getTeamMatchStats(Team $team)
    {
        $games = Game::where(function ($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })
        ->where('status', 'FINISHED')
        ->get();

        $stats = [
            'played' => $games->count(),
            'wins' => 0, 'draws' => 0, 'losses' => 0,
            'goals_for' => 0, 'goals_against' => 0,
            'home_record' => ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            'away_record' => ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0]
        ];

        foreach ($games as $game) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? ($game->home_score ?? 0) : ($game->away_score ?? 0);
            $opponentScore = $isHome ? ($game->away_score ?? 0) : ($game->home_score ?? 0);

            $stats['goals_for'] += $teamScore;
            $stats['goals_against'] += $opponentScore;

            if ($isHome) {
                $stats['home_record']['played']++;
                if ($teamScore > $opponentScore) {
                    $stats['wins']++;
                    $stats['home_record']['wins']++;
                } elseif ($teamScore < $opponentScore) {
                    $stats['losses']++;
                    $stats['home_record']['losses']++;
                } else {
                    $stats['draws']++;
                    $stats['home_record']['draws']++;
                }
            } else {
                $stats['away_record']['played']++;
                if ($teamScore > $opponentScore) {
                    $stats['wins']++;
                    $stats['away_record']['wins']++;
                } elseif ($teamScore < $opponentScore) {
                    $stats['losses']++;
                    $stats['away_record']['losses']++;
                } else {
                    $stats['draws']++;
                    $stats['away_record']['draws']++;
                }
            }
        }

        $stats['points'] = ($stats['wins'] * 3) + $stats['draws'];
        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        return $stats;
    }

    /**
     * Get team recent form
     */
    private function getTeamRecentForm(Team $team)
    {
        $recentGames = Game::where(function ($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })
        ->where('status', 'FINISHED')
        ->orderBy('kick_off_time', 'desc')
        ->limit(5)
        ->get();

        $form = [];
        foreach ($recentGames as $game) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? ($game->home_score ?? 0) : ($game->away_score ?? 0);
            $opponentScore = $isHome ? ($game->away_score ?? 0) : ($game->home_score ?? 0);

            if ($teamScore > $opponentScore) {
                $form[] = ['result' => 'W', 'class' => 'bg-green-500'];
            } elseif ($teamScore < $opponentScore) {
                $form[] = ['result' => 'L', 'class' => 'bg-red-500'];
            } else {
                $form[] = ['result' => 'D', 'class' => 'bg-yellow-500'];
            }
        }

        return array_reverse($form);
    }

    /**
     * Get team stats with historical data
     */
    private function getTeamMatchStatsWithHistory(Team $team, HistoricalDataService $historicalService)
    {
        // Get current season stats
        $currentStats = $this->getTeamMatchStats($team);
        
        // Get historical stats for context
        $historicalStats = $historicalService->getTeamStats($team->name);
        
        // If we have significant current season data, use it; otherwise blend with historical
        if ($currentStats['played'] >= 5) {
            return array_merge($currentStats, [
                'historical_context' => $historicalStats,
                'data_source' => 'current_season'
            ]);
        } else {
            // Early season - use historical data for context
            return array_merge($currentStats, [
                'historical_avg_points_per_game' => $historicalStats['avg_points_per_game'] ?? 0,
                'historical_avg_goals_per_game' => $historicalStats['avg_goals_per_game'] ?? 0,
                'historical_strength_rating' => $historicalStats['strength_rating'] ?? 50,
                'data_source' => 'blended'
            ]);
        }
    }

    /**
     * Get team recent form with historical data
     */
    private function getTeamRecentFormWithHistory(Team $team, HistoricalDataService $historicalService)
    {
        // Get current season form
        $currentForm = $this->getTeamRecentForm($team);
        
        // If we have enough current form, use it
        if (count($currentForm) >= 3) {
            return $currentForm;
        }
        
        // Otherwise, get historical form to fill the gaps
        $historicalForm = $historicalService->getTeamRecentForm($team->name, 5);
        
        $combinedForm = [];
        
        // Add current form first
        foreach ($currentForm as $result) {
            $combinedForm[] = $result;
        }
        
        // Fill remaining with historical form
        $needed = 5 - count($currentForm);
        for ($i = 0; $i < min($needed, count($historicalForm)); $i++) {
            $result = $historicalForm[$i];
            // HistoricalDataService already returns properly formatted objects
            $combinedForm[] = $result;
        }
        
        return $combinedForm;
    }
}
