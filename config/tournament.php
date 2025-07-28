<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Test Mode Configuration
    |--------------------------------------------------------------------------
    |
    | When test_mode is enabled, the tournament timeline is compressed to allow
    | rapid testing of the complete tournament flow without waiting weeks.
    |
    */

    'test_mode' => env('TOURNAMENT_TEST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Test Mode Timeline Compression
    |--------------------------------------------------------------------------
    |
    | These settings define how much to compress the timeline in test mode:
    | - gameweek_duration_minutes: How long each gameweek lasts in test mode
    | - selection_window_minutes: How long the selection window stays open
    | - games_spacing_minutes: Time between games in a gameweek
    |
    */

    'test_mode_settings' => [
        'gameweek_duration_minutes' => 15,    // Each gameweek lasts 15 minutes
        'selection_window_minutes' => 5,      // 5 minute selection window
        'games_spacing_minutes' => 2,         // Games every 2 minutes
        'break_between_gameweeks_minutes' => 2, // 2 minute break between gameweeks
    ],

];
