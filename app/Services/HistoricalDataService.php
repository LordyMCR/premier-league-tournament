<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\Team;
use App\Models\Game;

class HistoricalDataService
{
    private $historicalData = null;

    /**
     * Load all historical data
     */
    private function loadHistoricalData()
    {
        if ($this->historicalData !== null) {
            return $this->historicalData;
        }

        // Cache for 1 hour since this data doesn't change often
        $this->historicalData = Cache::remember('historical_premier_league_data', 3600, function () {
            $files = Storage::disk('local')->files('historical_data');
            $allData = [];
            
            // Add debug logging
            \Log::info('HistoricalDataService: Found files', ['files' => $files]);

            foreach ($files as $file) {
                if (str_ends_with($file, '.json')) {
                    try {
                        $content = Storage::disk('local')->get($file);
                        $seasonData = json_decode($content, true);
                        if ($seasonData) {
                            \Log::info('HistoricalDataService: Loaded season', [
                                'file' => $file,
                                'season' => $seasonData['season'] ?? 'Unknown',
                                'matches' => count($seasonData['matches'] ?? []),
                                'teams' => count($seasonData['teams'] ?? [])
                            ]);
                            $allData[] = $seasonData;
                        } else {
                            \Log::error('HistoricalDataService: Failed to decode JSON', ['file' => $file]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('HistoricalDataService: Error loading file', ['file' => $file, 'error' => $e->getMessage()]);
                    }
                }
            }

            \Log::info('HistoricalDataService: Total seasons loaded', ['count' => count($allData)]);

            // Sort by year (most recent first)
            usort($allData, function ($a, $b) {
                return $b['year'] <=> $a['year'];
            });

            return $allData;
        });

        return $this->historicalData;
    }

    /**
     * Get team strength rating based on historical performance
     */
    public function getTeamStrengthRating($teamName)
    {
        $historicalData = $this->loadHistoricalData();
        
        if (empty($historicalData)) {
            return 50; // Default neutral rating
        }

        $totalPoints = 0;
        $totalSeasons = 0;
        $recentWeight = 3; // Weight recent seasons more heavily

        foreach ($historicalData as $index => $seasonData) {
            // Find team in this season
            $teamStats = collect($seasonData['team_stats'])->firstWhere('team_name', $teamName);
            
            if ($teamStats) {
                $weight = $recentWeight - ($index * 0.3); // Recent seasons weighted more
                if ($weight < 0.5) $weight = 0.5; // Minimum weight
                
                // Convert points to a 0-100 scale (assuming 38 games, max 114 points)
                $seasonRating = ($teamStats['points'] / 114) * 100;
                
                $totalPoints += $seasonRating * $weight;
                $totalSeasons += $weight;
            }
        }

        if ($totalSeasons === 0) {
            return 50; // Default if no historical data
        }

        return round($totalPoints / $totalSeasons, 1);
    }

    /**
     * Get head-to-head record between two teams from historical data
     */
    public function getHistoricalHeadToHead($teamAName, $teamBName, $limit = 10)
    {
        $historicalData = $this->loadHistoricalData();
        $meetings = [];

        foreach ($historicalData as $seasonData) {
            foreach ($seasonData['matches'] as $match) {
                $homeTeam = $match['home_team_name'];
                $awayTeam = $match['away_team_name'];

                // Check if this match involves both teams
                if (($homeTeam === $teamAName && $awayTeam === $teamBName) ||
                    ($homeTeam === $teamBName && $awayTeam === $teamAName)) {
                    
                    $meetings[] = [
                        'date' => $match['kick_off_time'],
                        'season' => $seasonData['season'],
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'home_score' => $match['home_score'],
                        'away_score' => $match['away_score'],
                        'venue' => $homeTeam === $teamAName ? 'H' : 'A' // From Team A's perspective
                    ];
                }
            }
        }

        // Sort by date (most recent first) and limit
        usort($meetings, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        $meetings = array_slice($meetings, 0, $limit);

        // Calculate record from Team A's perspective
        $record = ['wins' => 0, 'draws' => 0, 'losses' => 0];
        
        foreach ($meetings as $meeting) {
            $teamAScore = $meeting['venue'] === 'H' ? $meeting['home_score'] : $meeting['away_score'];
            $teamBScore = $meeting['venue'] === 'H' ? $meeting['away_score'] : $meeting['home_score'];

            if ($teamAScore > $teamBScore) {
                $record['wins']++;
            } elseif ($teamAScore < $teamBScore) {
                $record['losses']++;
            } else {
                $record['draws']++;
            }
        }

        return [
            'record' => $record,
            'recent_meetings' => $meetings,
            'total_meetings' => count($meetings)
        ];
    }

    /**
     * Get team's recent form based on historical and current season data
     */
    public function getTeamRecentForm($teamName, $gamesCount = 6)
    {
        // First get current season completed games
        $currentSeasonGames = Game::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($teamName) {
                $query->whereHas('homeTeam', function ($q) use ($teamName) {
                    $q->where('name', $teamName);
                })->orWhereHas('awayTeam', function ($q) use ($teamName) {
                    $q->where('name', $teamName);
                });
            })
            ->where('status', 'FINISHED')
            ->orderBy('kick_off_time', 'desc')
            ->get();

        $form = [];
        
        // Process current season games first
        foreach ($currentSeasonGames as $game) {
            if (count($form) >= $gamesCount) break;

            $isHome = $game->homeTeam->name === $teamName;
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

        // If we need more games, get from historical data
        if (count($form) < $gamesCount) {
            $historicalData = $this->loadHistoricalData();
            $gamesNeeded = $gamesCount - count($form);

            // Get most recent historical games
            foreach ($historicalData as $seasonData) {
                if ($gamesNeeded <= 0) break;

                $seasonGames = [];
                foreach ($seasonData['matches'] as $match) {
                    if ($match['home_team_name'] === $teamName || $match['away_team_name'] === $teamName) {
                        $seasonGames[] = $match;
                    }
                }

                // Sort by date (most recent first)
                usort($seasonGames, function ($a, $b) {
                    return strtotime($b['kick_off_time']) <=> strtotime($a['kick_off_time']);
                });

                foreach ($seasonGames as $match) {
                    if ($gamesNeeded <= 0) break;

                    $isHome = $match['home_team_name'] === $teamName;
                    $teamScore = $isHome ? $match['home_score'] : $match['away_score'];
                    $opponentScore = $isHome ? $match['away_score'] : $match['home_score'];

                    if ($teamScore > $opponentScore) {
                        $form[] = ['result' => 'W', 'class' => 'bg-green-500'];
                    } elseif ($teamScore < $opponentScore) {
                        $form[] = ['result' => 'L', 'class' => 'bg-red-500'];
                    } else {
                        $form[] = ['result' => 'D', 'class' => 'bg-yellow-500'];
                    }

                    $gamesNeeded--;
                }
            }
        }

        return array_reverse($form); // Return in chronological order (oldest first)
    }

    /**
     * Calculate difficulty rating based on historical performance
     */
    public function calculateDifficultyRating($opponentName)
    {
        $strengthRating = $this->getTeamStrengthRating($opponentName);
        
        // Convert strength rating to difficulty categories
        if ($strengthRating >= 70) {
            return ['difficulty' => 'Hard', 'class' => 'bg-red-500', 'rating' => $strengthRating];
        } elseif ($strengthRating >= 45) {
            return ['difficulty' => 'Medium', 'class' => 'bg-yellow-500', 'rating' => $strengthRating];
        } else {
            return ['difficulty' => 'Easy', 'class' => 'bg-green-500', 'rating' => $strengthRating];
        }
    }

    /**
     * Get available historical seasons
     */
    public function getAvailableSeasons()
    {
        $historicalData = $this->loadHistoricalData();
        return array_map(function ($season) {
            return [
                'season' => $season['season'],
                'year' => $season['year'],
                'teams_count' => count($season['teams']),
                'matches_count' => count($season['matches']),
                'imported_at' => $season['imported_at'] ?? null
            ];
        }, $historicalData);
    }

    /**
     * Get team statistics from historical data
     */
    public function getTeamStats($teamName)
    {
        $historicalData = $this->loadHistoricalData();
        
        $stats = [
            'avg_points_per_game' => 0,
            'avg_goals_per_game' => 0,
            'avg_goals_conceded_per_game' => 0,
            'strength_rating' => 50,
            'seasons_played' => 0,
            'total_games' => 0,
            'total_wins' => 0,
            'total_draws' => 0,
            'total_losses' => 0
        ];

        $totalGames = 0;
        $totalPoints = 0;
        $totalGoalsFor = 0;
        $totalGoalsAgainst = 0;
        $seasonsFound = 0;

        foreach ($historicalData as $season) {
            $teamStats = null;
            
            // Find this team's stats in the season
            foreach ($season['team_stats'] ?? [] as $teamStat) {
                if ($teamStat['team_name'] === $teamName) {
                    $teamStats = $teamStat;
                    break;
                }
            }

            if ($teamStats) {
                $seasonsFound++;
                $gamesPlayed = $teamStats['played'] ?? 0;
                $totalGames += $gamesPlayed;
                $totalPoints += $teamStats['points'] ?? 0;
                $totalGoalsFor += $teamStats['goals_for'] ?? 0;
                $totalGoalsAgainst += $teamStats['goals_against'] ?? 0;
                $stats['total_wins'] += $teamStats['wins'] ?? 0;
                $stats['total_draws'] += $teamStats['draws'] ?? 0;
                $stats['total_losses'] += $teamStats['losses'] ?? 0;
            }
        }

        // Calculate averages
        if ($totalGames > 0) {
            $stats['avg_points_per_game'] = round($totalPoints / $totalGames, 2);
            $stats['avg_goals_per_game'] = round($totalGoalsFor / $totalGames, 2);
            $stats['avg_goals_conceded_per_game'] = round($totalGoalsAgainst / $totalGames, 2);
            
            // Calculate strength rating (0-100 scale based on points per game)
            $stats['strength_rating'] = min(100, max(0, round(($stats['avg_points_per_game'] / 3) * 100)));
        }

        $stats['seasons_played'] = $seasonsFound;
        $stats['total_games'] = $totalGames;

        return $stats;
    }

    /**
     * Get head-to-head record between two teams from historical data
     */
    public function getHeadToHeadRecord($teamAName, $teamBName)
    {
        $historicalData = $this->loadHistoricalData();
        
        $record = [
            'team_wins' => 0,
            'draws' => 0,
            'team_losses' => 0,
            'total_games' => 0,
            'recent_record' => []
        ];

        // Process all seasons
        foreach ($historicalData as $season) {
            $matches = $season['matches'] ?? [];
            
            foreach ($matches as $match) {
                $homeTeamName = $match['home_team_name'];
                $awayTeamName = $match['away_team_name'];
                
                // Check if this match involves both teams
                if (($homeTeamName === $teamAName && $awayTeamName === $teamBName) ||
                    ($homeTeamName === $teamBName && $awayTeamName === $teamAName)) {
                    
                    $homeScore = (int) ($match['home_score'] ?? 0);
                    $awayScore = (int) ($match['away_score'] ?? 0);
                    
                    $record['total_games']++;
                    
                    // Determine result from teamA's perspective
                    $teamAIsHome = ($homeTeamName === $teamAName);
                    $teamAScore = $teamAIsHome ? $homeScore : $awayScore;
                    $teamBScore = $teamAIsHome ? $awayScore : $homeScore;
                    
                    if ($teamAScore > $teamBScore) {
                        $record['team_wins']++;
                        $result = 'W';
                    } elseif ($teamAScore < $teamBScore) {
                        $record['team_losses']++;
                        $result = 'L';
                    } else {
                        $record['draws']++;
                        $result = 'D';
                    }
                    
                    // Add to recent record (limit to last 10 meetings)
                    if (count($record['recent_record']) < 10) {
                        $record['recent_record'][] = [
                            'result' => $result,
                            'score' => "{$teamAScore}-{$teamBScore}",
                            'venue' => $teamAIsHome ? 'H' : 'A',
                            'date' => $match['kick_off_time'],
                            'season' => $season['season']
                        ];
                    }
                }
            }
        }

        // Sort recent record by date (most recent first)
        usort($record['recent_record'], function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        return $record;
    }
}
