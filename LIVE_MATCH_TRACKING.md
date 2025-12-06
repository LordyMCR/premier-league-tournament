# Live Match Tracking System

## Overview
FREE TIER optimized live match tracking system that allows unlimited users to check live match scores without exhausting API rate limits.

## Architecture

### 🎯 Key Strategy: Database Caching
Instead of having users poll external APIs directly (which would require 600+ API calls/hour for just 10 users), we use a smart 3-layer caching system:

**Layer 1 (Background Job)** → **Layer 2 (Database Cache)** → **Layer 3 (Frontend)**

## Components

### 1. Database Table: `live_match_events`
**File:** `database/migrations/2025_10_05_200950_create_live_match_events_table.php`

**Schema:**
- `game_id` (foreign key to games table)
- `home_score`, `away_score` (current scores)
- `status` (LIVE, PAUSED, FINISHED, SCHEDULED)
- `minute` (current match minute)
- `events` (JSON array of goals, cards, subs)
- `last_updated` (timestamp)
- Indexes on `(game_id, status)` and `last_updated`

**Purpose:** Cache all live match data so frontend never calls external API

---

### 2. Model: `LiveMatchEvent`
**File:** `app/Models/LiveMatchEvent.php`

**Key Methods:**
- `isStale()` - Returns true if data is >15 minutes old
- `scopeLive()` - Query scope for live/paused matches
- `scopeFinished()` - Query scope for finished matches
- `game()` - Relationship to Game model

**Casts:**
- `events` → array (JSON storage)
- `last_updated` → datetime

---

### 3. Background Command: `UpdateLiveMatches`
**File:** `app/Console/Commands/UpdateLiveMatches.php`
**Signature:** `php artisan matches:update-live {--force}`

**Smart Features:**

#### Match Window Detection
Only runs during actual match times to save API calls:
- **Weekend:** Saturday/Sunday 11am-10pm
- **Midweek:** Tuesday/Wednesday/Thursday 6pm-11pm
- `--force` flag bypasses window check for testing

#### Single API Call Strategy
Makes **1 API call per execution** to fetch ALL today's matches (not per-match)

#### API Usage Calculation
- Runs every 10 minutes during match windows
- Weekend: 11 hours × 6 calls/hour = 66 calls/day
- Midweek: 5 hours × 6 calls/hour = 30 calls/day
- **Total: ~96 calls/day** (well within 100/day free tier limit)

#### What It Does
1. Fetches all today's Premier League matches from Football-Data.org
2. Updates `live_match_events` table with current scores/status
3. Updates `games` table with final scores when finished
4. Extracts events (goals, cards, substitutions) into JSON
5. Auto-cleanup: Deletes finished matches >2 hours old

**Console Output Example:**
```
🔄 Checking for live Premier League matches...
🔴 AVL 2 - 1 BUR [LIVE 78']
✅ EVE 2 - 1 CRY [FINISHED]
📅 NEW vs NOT [SCHEDULED 15:00]
✅ Updated 3 matches (1 live)
```

---

### 4. Scheduler
**File:** `routes/console.php`

```php
Schedule::command('matches:update-live')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

Automatically runs the update command every 10 minutes.

**To test manually:**
```bash
php artisan matches:update-live --force
```

**To test scheduler:**
```bash
php artisan schedule:run
```

---

### 5. API Controller: `LiveMatchController`
**File:** `app/Http/Controllers/LiveMatchController.php`

**Endpoints:**

#### GET `/api/live-matches`
Returns all live matches + user's pick status

**Response:**
```json
{
  "live_matches": [
    {
      "id": 123,
      "home_team": { "name": "Arsenal", "logo_url": "..." },
      "away_team": { "name": "Chelsea", "logo_url": "..." },
      "home_score": 2,
      "away_score": 1,
      "status": "IN_PLAY",
      "live_event": {
        "minute": 67,
        "events": [
          { "type": "GOAL", "minute": 23, "player": "Saka", "team": "HOME" },
          { "type": "YELLOW_CARD", "minute": 45, "player": "Sterling", "team": "AWAY" }
        ]
      },
      "user_pick": {
        "team_name": "Arsenal",
        "status": "winning",
        "projected_points": 3
      }
    }
  ],
  "user_picks": [...],
  "stats": {
    "total_picks": 5,
    "winning_picks": 3,
    "drawing_picks": 1,
    "losing_picks": 1,
    "projected_points": 11
  }
}
```

**Key Features:**
- Serves data from database cache only (zero external API calls)
- Calculates if user's picks are winning/drawing/losing
- Computes projected points based on current match state
- Returns stats summary for dashboard display

#### GET `/api/live-matches/{game}`
Returns detailed events for a specific match

---

### 6. Frontend Component: `LiveMatchTracker`
**File:** `resources/js/Components/LiveMatchTracker.vue`

**Features:**
- Auto-refreshes every 60 seconds by polling `/api/live-matches`
- Animated "LIVE" indicator with pulsing red dot
- Mobile-responsive design with short team names
- Color-coded pick status:
  - 🟢 Green = Winning (3 pts)
  - 🟡 Yellow = Drawing (1 pt)
  - 🔴 Red = Losing (0 pts)
- Recent events timeline (goals ⚽, cards 🟨🟥, subs 🔄)
- Stats summary (total picks, winning/drawing, projected points)
- "No matches live" state for off-days
- Loading skeleton while fetching

**Short Team Names (Mobile):**
- Manchester United → Man Utd
- Wolverhampton Wanderers → Wolves
- Brighton & Hove Albion → Brighton
- (etc.)

**Integration:**
Currently added to `Dashboard.vue` - appears between hero section and stats

---

## API Efficiency Comparison

### ❌ Without Caching (Per-User Polling)
- 10 users × 1 poll/min × 60 min = **600 calls/hour**
- Exceeds free tier in 10 minutes ⚠️

### ✅ With Database Caching
- 1 background job × 6 calls/hour = **6 calls/hour**
- Supports **unlimited users** polling frontend
- **100× more efficient** 🎉

---

## Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Test the Command
```bash
# Force run outside match windows
php artisan matches:update-live --force
```

**Expected Output:**
```
🔄 Checking for live Premier League matches...
✅ AVL 2 - 1 BUR [FINISHED]
✅ EVE 2 - 1 CRY [FINISHED]
✅ Updated 5 matches (0 live)
```

### 3. Verify Scheduler
```bash
php artisan schedule:list
```

Should show:
```
0 */10 * * * * php artisan matches:update-live
```

### 4. Test Frontend
1. Visit `/dashboard`
2. Look for "Live Matches" section
3. Component polls `/api/live-matches` every 60 seconds
4. Check browser Network tab - should see requests to `/api/live-matches` (NOT external API)

### 5. Monitor API Usage (Production)
- Football-Data.org free tier: **10 requests/minute**
- API-Football free tier: **100 requests/day**
- This system uses: **~96 calls/day** ✅

---

## How It Works (User Journey)

### Match Day (Saturday 3pm)

**14:50** - Background job checks match window → Too early, skips

**15:00** - Matches kick off

**15:00** - Background job runs:
1. Calls Football-Data.org API once
2. Fetches all today's matches (1 API call)
3. Stores in `live_match_events` table
4. Sets status to `IN_PLAY`

**15:01** - User visits dashboard:
1. LiveMatchTracker component mounts
2. Calls `/api/live-matches` endpoint (local server, NOT external API)
3. Gets data from database cache
4. Displays: "🔴 LIVE - Arsenal 0-0 Chelsea (1')"
5. Shows user's pick status if they picked Arsenal/Chelsea

**15:02-15:09** - Users keep refreshing (60 second polling):
- All requests hit database cache
- Zero external API calls
- Data shows last update from 15:00

**15:10** - Background job runs again:
1. Fetches updated scores (1 API call)
2. Updates database: "Arsenal 1-0 Chelsea (10')"
3. Adds goal event to JSON

**15:11** - User sees update automatically:
- "🔴 LIVE - Arsenal 1-0 Chelsea (10')"
- Pick status: "✓ Winning - 3 pts"
- Recent events: "10' ⚽ Saka"

**17:00** - Match finishes:
- Background job updates status to `FINISHED`
- Updates `games` table with final score
- Frontend shows: "✅ Arsenal 2-1 Chelsea [FT]"

**19:00** - Cleanup:
- Background job deletes match from `live_match_events` (>2 hours old)
- Keeps database lean

---

## Configuration

### Adjust Polling Frequency

**Backend (Command):**
`routes/console.php`
```php
// Current: Every 10 minutes
Schedule::command('matches:update-live')->everyTenMinutes();

// Options:
->everyFiveMinutes()  // More frequent (12/hour = 288/day - exceeds free tier!)
->everyFifteenMinutes()  // Less frequent (4/hour = 96/day - saves API calls)
```

**Frontend (Component):**
`resources/js/Components/LiveMatchTracker.vue`
```javascript
// Current: Every 60 seconds
pollingInterval = setInterval(() => {
    fetchLiveMatches();
}, 60000);

// Options:
}, 30000);  // 30 seconds - more responsive
}, 120000);  // 2 minutes - less traffic
```

### Match Windows
Edit `app/Console/Commands/UpdateLiveMatches.php`:
```php
protected function isMatchWindow(): bool
{
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $hour = $now->hour;
    
    // Weekend matches (Saturday=6, Sunday=0)
    if (in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
        return $hour >= 11 && $hour < 22; // 11am - 10pm
    }
    
    // Midweek matches (Tuesday=2, Wednesday=3, Thursday=4)
    if (in_array($dayOfWeek, [Carbon::TUESDAY, Carbon::WEDNESDAY, Carbon::THURSDAY])) {
        return $hour >= 18 && $hour < 23; // 6pm - 11pm
    }
    
    return false;
}
```

---

## Troubleshooting

### No matches showing
1. Check if background job ran: `php artisan matches:update-live --force`
2. Check database: `SELECT * FROM live_match_events;`
3. Check API response in browser console Network tab

### API errors
1. Verify API key in `.env`: `FOOTBALL_DATA_API_KEY=...`
2. Check rate limits: Football-Data free tier = 10 req/min
3. View Laravel logs: `storage/logs/laravel.log`

### Stale data
- Command auto-deletes matches >2 hours after finishing
- Force cleanup: Edit `UpdateLiveMatches.php` cleanup time

### Scheduler not running
**Development:**
```bash
# Run manually
php artisan schedule:run
```

**Production (Heroku):**
Add to `Procfile`:
```
worker: php artisan schedule:work
```

Or use Heroku Scheduler add-on:
```bash
heroku addons:create scheduler:standard
heroku addons:open scheduler
# Add: php artisan matches:update-live (every 10 min)
```

---

## Future Enhancements

### Phase 2 (Optional)
- [ ] WebSocket integration for instant updates (eliminate 60s polling)
- [ ] Push notifications when user's pick score changes
- [ ] Audio alerts for goals in user's matches
- [ ] Match detail modal with full event timeline
- [ ] Historical match statistics integration
- [ ] Live commentary from API-Football (if available in free tier)

### Phase 3 (Advanced)
- [ ] Predictive score updates using ML
- [ ] Live odds integration
- [ ] Fan sentiment analysis from social media
- [ ] Video highlights embedding (YouTube API)

---

## Summary

✅ **Completed:**
- Database migration for live match cache
- LiveMatchEvent model with relationships
- UpdateLiveMatches command with smart scheduling
- LiveMatchController serving cached data
- LiveMatchTracker Vue component with polling
- Integration into Dashboard
- API routes configured
- Frontend built and deployed

✅ **API Efficiency:**
- Before: 600+ calls/hour per 10 users ❌
- After: 6 calls/hour for unlimited users ✅
- **100× improvement** 🎉

✅ **Free Tier Compliance:**
- Football-Data.org: 10 req/min limit ✅
- API-Football: 100 req/day limit ✅
- System usage: ~96 calls/day ✅

🎯 **Next Steps:**
1. Test during actual match day (Saturday/Sunday)
2. Monitor API usage in production
3. Gather user feedback on polling frequency
4. Consider WebSocket upgrade if real-time updates needed

---

**Built with ❤️ for Premier League Tournament PWA**
