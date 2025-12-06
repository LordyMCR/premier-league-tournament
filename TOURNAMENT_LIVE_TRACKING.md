# Tournament-Specific Live Match Tracking

## Overview
Added tournament-specific live match tracking to the Tournament Show page, allowing participants to see real-time updates for matches where tournament participants have made picks.

## Key Features
- **Zero Additional API Usage**: Reuses existing `/api/live-matches` cached data
- **Tournament-Filtered View**: Shows only matches relevant to this tournament
- **Participant Pick Display**: Shows each participant's pick with status indicators
- **Real-Time Status**: Color-coded badges (✓ winning, = drawing, ✗ losing)
- **Tournament Stats**: Summary of winning picks and projected points
- **Auto-Refresh**: Polls every 60 seconds during live matches

## Implementation

### Backend

#### Controller: `app/Http/Controllers/TournamentLiveController.php`
```php
public function getLivePicks(Tournament $tournament)
```
- Returns picks for current gameweek where user is tournament participant
- Database-only queries (no external API calls)
- Response includes: user_id, user_name, team_id, team_name, game_id

#### Route: `routes/web.php`
```php
Route::get('/api/tournaments/{tournament}/live-picks', [TournamentLiveController::class, 'getLivePicks'])
    ->name('api.tournaments.live-picks');
```

### Frontend

#### Component: `resources/js/Components/TournamentLiveMatches.vue`
- Props: `tournamentId` (required)
- Fetches data from:
  1. `/api/live-matches` (existing cached data)
  2. `/api/tournaments/{id}/live-picks` (new DB-only endpoint)
- Filters matches to show only those where participants have picks
- Displays participant names and their pick status
- Shows tournament-level statistics

#### Integration: `resources/js/Pages/Tournaments/Show.vue`
```vue
<TournamentLiveMatches 
    v-if="isParticipant && currentGameweek" 
    :tournament-id="tournament.id" 
/>
```
- Positioned after "Current Pick" section
- Only visible to tournament participants during active gameweek

## API Usage Impact

### Before Tournament Feature
- Background job: 15-minute polling during match windows
- Usage: 37-51 calls/day (depending on match schedule)
- Free tier limit: 100 calls/day
- Margin: 49-63% headroom

### After Tournament Feature
- **Same API usage: 37-51 calls/day**
- Tournament feature reuses existing cached data
- New endpoint queries database only (zero external API calls)
- Free tier margin unchanged: 49-63% headroom ✅

## Architecture

### Data Flow
```
1. Background Job (every 15 min during match windows)
   ↓
   Fetches ALL live matches from Football-Data.org API
   ↓
   Stores in live_match_events table (database cache)
   
2. Tournament Page Frontend (every 60 sec)
   ↓
   Fetches /api/live-matches (from DB cache)
   ↓
   Fetches /api/tournaments/{id}/live-picks (from DB)
   ↓
   Filters matches to show only relevant ones
   ↓
   Displays with participant pick status
```

### Smart Filtering
- Component receives ALL live matches (already cached)
- Receives tournament participants' picks (lightweight DB query)
- Frontend filters to show only matches with participant picks
- No additional external API calls required

## User Experience

### Visibility Rules
- **Participants Only**: Component only shows to tournament participants
- **Active Gameweek**: Only displays during an active gameweek
- **Match Windows**: Shows matches from current gameweek where participants have picks
- **Auto-Hide**: Disappears when no relevant matches are live

### Display Features
- **Compact Cards**: Each match shown in clean, readable card format
- **Team Logos**: Visual identification with team colors
- **Live Scores**: Real-time score updates every 60 seconds
- **Match Minute**: Shows current match time (e.g., "67'", "HT", "FT")
- **Participant List**: Shows all participants' picks for each match
- **Status Indicators**: 
  - ✓ Green badge: Team is winning
  - = Yellow badge: Match is drawn
  - ✗ Red badge: Team is losing
- **Tournament Stats**: Summary showing:
  - Number of participants currently winning
  - Number of participants with draws
  - Projected total points for tournament

### Example Display
```
🔴 LIVE MATCHES IN THIS TOURNAMENT

┌──────────────────────────────────────┐
│ Arsenal 2 - 1 Liverpool       ⚽ 67' │
│                                      │
│ Participants' Picks:                 │
│ • Alice (Arsenal) ✓                  │
│ • Bob (Liverpool) ✗                  │
│ • Charlie (Arsenal) ✓                │
└──────────────────────────────────────┘

Tournament Stats:
• 2 Winning Picks
• 0 Drawing Picks
• Projected Points: 6
```

## Testing

### To Test During Match Day
1. Ensure background job is running: `php artisan matches:update-live --force`
2. Check `live_match_events` table has data
3. Navigate to a tournament show page as participant
4. Verify component appears (only during live matches)
5. Confirm only relevant matches display
6. Check participant picks show with correct status
7. Verify stats summary calculates correctly
8. Watch for auto-refresh every 60 seconds

### Test API Endpoint Manually
```bash
# Get tournament live picks (as authenticated user)
curl -X GET http://pl-tournament.test/api/tournaments/1/live-picks \
  -H "Authorization: Bearer {token}"

# Expected response:
{
  "picks": [
    {
      "user_id": 1,
      "user_name": "Alice",
      "team_id": 57,
      "team_name": "Arsenal FC",
      "game_id": 123
    },
    ...
  ],
  "gameweek": {
    "id": 5,
    "week_number": 5,
    "name": "Gameweek 5"
  }
}
```

## Performance

### Database Queries
- **Live Matches Endpoint**: 1 query (cached data)
- **Tournament Picks Endpoint**: ~3-4 queries (with eager loading)
  - GameWeek query (1)
  - Pick query with joins (1)
  - User eager load (included in pick query)
  - Team eager load (included in pick query)
  - Game eager load (included in pick query)
- **Total per 60-sec refresh**: 4-5 queries (all to local database)

### Optimizations
- Eager loading prevents N+1 query issues
- Component-level filtering (client-side, no server load)
- Reuses existing cached match data
- Only queries tournament-specific picks (not all picks)

## Files Modified/Created

### Created
1. `app/Http/Controllers/TournamentLiveController.php` - Tournament picks endpoint
2. `resources/js/Components/TournamentLiveMatches.vue` - Tournament live component

### Modified
1. `routes/web.php` - Added tournament picks route
2. `resources/js/Pages/Tournaments/Show.vue` - Integrated component

## Deployment Notes
- ✅ Backend implemented
- ✅ Frontend component created
- ✅ Route registered
- ✅ Build completed (`npm run build`)
- ✅ Zero additional API usage
- 🔄 Awaiting match day for live testing

## Future Enhancements
- [ ] Push notifications when user's pick status changes
- [ ] Historical view of past gameweek performances
- [ ] Tournament-wide statistics page
- [ ] Export tournament results
- [ ] Add "View Full Live Matches" link to Dashboard tracker
