<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class PremierLeagueTeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'primary_color' => '#EF0107',
                'secondary_color' => '#9C824A',
            ],
            [
                'name' => 'Aston Villa',
                'short_name' => 'AVL',
                'primary_color' => '#95BFE5',
                'secondary_color' => '#670E36',
            ],
            [
                'name' => 'Brighton & Hove Albion',
                'short_name' => 'BHA',
                'primary_color' => '#0057B8',
                'secondary_color' => '#FFCD00',
            ],
            [
                'name' => 'Burnley',
                'short_name' => 'BUR',
                'primary_color' => '#6C1D45',
                'secondary_color' => '#99D6EA',
            ],
            [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'primary_color' => '#034694',
                'secondary_color' => '#DBA111',
            ],
            [
                'name' => 'Crystal Palace',
                'short_name' => 'CRY',
                'primary_color' => '#1B458F',
                'secondary_color' => '#A7A5A6',
            ],
            [
                'name' => 'Everton',
                'short_name' => 'EVE',
                'primary_color' => '#003399',
                'secondary_color' => '#FFFFFF',
            ],
            [
                'name' => 'Fulham',
                'short_name' => 'FUL',
                'primary_color' => '#FFFFFF',
                'secondary_color' => '#000000',
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'primary_color' => '#C8102E',
                'secondary_color' => '#F6EB61',
            ],
            [
                'name' => 'Luton Town',
                'short_name' => 'LUT',
                'primary_color' => '#F78F1E',
                'secondary_color' => '#002D62',
            ],
            [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'primary_color' => '#6CABDD',
                'secondary_color' => '#1C2C5B',
            ],
            [
                'name' => 'Manchester United',
                'short_name' => 'MUN',
                'primary_color' => '#FFF200',
                'secondary_color' => '#DA020E',
            ],
            [
                'name' => 'Newcastle United',
                'short_name' => 'NEW',
                'primary_color' => '#241F20',
                'secondary_color' => '#FFFFFF',
            ],
            [
                'name' => 'Nottingham Forest',
                'short_name' => 'NFO',
                'primary_color' => '#DD0000',
                'secondary_color' => '#FFFFFF',
            ],
            [
                'name' => 'Sheffield United',
                'short_name' => 'SHU',
                'primary_color' => '#EE2737',
                'secondary_color' => '#000000',
            ],
            [
                'name' => 'Tottenham Hotspur',
                'short_name' => 'TOT',
                'primary_color' => '#132257',
                'secondary_color' => '#FFFFFF',
            ],
            [
                'name' => 'West Ham United',
                'short_name' => 'WHU',
                'primary_color' => '#7A263A',
                'secondary_color' => '#1BB1E7',
            ],
            [
                'name' => 'Wolverhampton Wanderers',
                'short_name' => 'WOL',
                'primary_color' => '#FDB462',
                'secondary_color' => '#231F20',
            ],
            [
                'name' => 'Brentford',
                'short_name' => 'BRE',
                'primary_color' => '#E30613',
                'secondary_color' => '#FEE133',
            ],
            [
                'name' => 'Bournemouth',
                'short_name' => 'BOU',
                'primary_color' => '#DA020E',
                'secondary_color' => '#000000',
            ],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['short_name' => $team['short_name']],
                $team
            );
        }
    }
}
