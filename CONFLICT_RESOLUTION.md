# 🚨 CONFLICT RESOLVED - API Usage Fix

## The Problem We Found

When adding the live match tracking system, we discovered that **TWO commands were scheduled to run every 10 minutes:**

1. ❌ `football:smart-update` (OLD) - Existing command that calls `football:update --results`
2. ❌ `matches:update-live` (NEW) - New live tracking command

### Why This Was Bad

Both commands fetch match data from the same API, meaning:
- **12 API calls per hour** (6 from each command)
- **288 API calls per day** on match days
- **EXCEEDS the 100/day free tier limit!** ⚠️

---

## The Solution

### ✅ DISABLED: `football:smart-update`

**File:** `routes/console.php`

```php
// BEFORE (BAD - Duplicate calls!)
Schedule::command('football:smart-update')->everyTenMinutes();
Schedule::command('matches:update-live')->everyTenMinutes();

// AFTER (GOOD - Single source of truth!)
// Schedule::command('football:smart-update')->everyTenMinutes(); // COMMENTED OUT
Schedule::command('matches:update-live')->everyTenMinutes();
```

### Why `matches:update-live` is Better

| Feature | `football:smart-update` | `matches:update-live` | Winner |
|---------|-------------------------|------------------------|--------|
| **API Calls** | Varies (6-12/hour) | Fixed (6/hour) | ✅ NEW |
| **Match Window Detection** | Complex logic | Simple Sat/Sun/Tue/Wed/Thu | ✅ NEW |
| **Frontend Caching** | ❌ No | ✅ Yes (`live_match_events` table) | ✅ NEW |
| **Event Tracking** | ❌ No | ✅ Yes (goals, cards, subs) | ✅ NEW |
| **User Pick Status** | ❌ No | ✅ Yes (winning/drawing/losing) | ✅ NEW |
| **Projected Points** | ❌ No | ✅ Yes | ✅ NEW |
| **Auto Cleanup** | ❌ No | ✅ Yes (deletes old matches) | ✅ NEW |
| **Frontend Component** | ❌ No | ✅ Yes (LiveMatchTracker.vue) | ✅ NEW |

---

## Current Scheduler (VERIFIED ✅)

```
0    1 * * *  php artisan football:update           → 3 API calls (daily full sync)
*/30 * * * *  php artisan picks:auto-assign         → 0 API calls
*/10 * * * *  php artisan matches:update-live       → 6 API calls/hour (only during matches)
0    2 * * *  php artisan squad:update-stats        → 1 API call
0    3 * * *  php artisan squad:fetch               → 1 API call
0    4 * * *  php artisan tournament:recalculate-points → 0 API calls
```

**Total API Usage:**
- **Match Day (Saturday):** ~71 calls ✅
- **Midweek Match (Wednesday):** ~35 calls ✅
- **No Matches (Monday):** ~5 calls ✅
- **FREE TIER LIMIT:** 100 calls/day ✅

---

## How `matches:update-live` Replaced `football:smart-update`

### What They Both Do
1. ✅ Fetch current match scores from API
2. ✅ Update `games` table with latest status
3. ✅ Only run when matches are actually happening

### What ONLY `matches:update-live` Does
1. ✅ Stores data in `live_match_events` cache table
2. ✅ Extracts event details (goals, cards, subs) into JSON
3. ✅ Provides `/api/live-matches` endpoint for frontend
4. ✅ Calculates user pick status (winning/drawing/losing)
5. ✅ Computes projected points in real-time
6. ✅ Auto-deletes finished matches after 2 hours
7. ✅ Works with LiveMatchTracker.vue component

---

## Verification Steps

### 1. Check Scheduler
```bash
php artisan schedule:list
```

**Expected Output:**
```
0    1 * * *  php artisan football:update
*/30 * * * *  php artisan picks:auto-assign
*/10 * * * *  php artisan matches:update-live    ← Should see this
0    2 * * *  php artisan squad:update-stats
0    3 * * *  php artisan squad:fetch
0    4 * * *  php artisan tournament:recalculate-points
```

**Should NOT see:**
```
*/10 * * * *  php artisan football:smart-update   ← Should NOT see this!
```

### 2. Test Manual Execution
```bash
# This should work
php artisan matches:update-live --force

# This should still work (not deleted, just not scheduled)
php artisan football:smart-update --force
```

### 3. Check Database
```bash
php artisan tinker
```

```php
>>> LiveMatchEvent::count()
=> 5  // Number of matches cached

>>> LiveMatchEvent::with('game.homeTeam', 'game.awayTeam')->get()
// Should show cached matches with scores
```

---

## What Happens to `football:smart-update`?

### NOT DELETED ❌
The command still exists in `app/Console/Commands/SmartFootballUpdate.php` and can be run manually if needed.

### NOT SCHEDULED ✅
It's simply commented out in `routes/console.php` so it doesn't run automatically.

### Can Be Re-enabled ⚙️
If you ever need it back, just uncomment the line:
```php
Schedule::command('football:smart-update')->everyTenMinutes();
```

**BUT:** If you do this, make sure to disable `matches:update-live` to avoid duplicate calls!

---

## API Call Breakdown (With Fix)

### Saturday (Match Day)
```
01:00 - football:update           → 3 calls
02:00 - squad:update-stats        → 1 call
03:00 - squad:fetch               → 1 call
11:00-22:00 - matches:update-live → 6 calls/hour × 11 hours = 66 calls
                                    _________________
                                    TOTAL: 71 calls ✅
```

### Wednesday (Midweek Match)
```
01:00 - football:update           → 3 calls
02:00 - squad:update-stats        → 1 call
03:00 - squad:fetch               → 1 call
18:00-23:00 - matches:update-live → 6 calls/hour × 5 hours = 30 calls
                                    _________________
                                    TOTAL: 35 calls ✅
```

### Monday (No Matches)
```
01:00 - football:update           → 3 calls
02:00 - squad:update-stats        → 1 call
03:00 - squad:fetch               → 1 call
All day - matches:update-live     → 0 calls (not a match day)
                                    _________________
                                    TOTAL: 5 calls ✅
```

---

## If You Need to Adjust API Usage Further

### Option 1: Reduce Update Frequency (Safest)
Change from every 10 minutes to every 15 minutes:

```php
Schedule::command('matches:update-live')
    ->everyFifteenMinutes()  // Instead of everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

**Result:** 4 calls/hour × 11 hours = **44 calls/day** (even safer!)

### Option 2: Narrow Match Windows
Only run during peak times (3pm Saturday):

```php
// In UpdateLiveMatches.php
protected function isMatchWindow(): bool
{
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $hour = $now->hour;
    
    // Only Saturday afternoon
    if ($dayOfWeek === Carbon::SATURDAY) {
        return $hour >= 14 && $hour < 18; // 2pm - 6pm
    }
    
    return false;
}
```

**Result:** 4 hours × 6 calls/hour = **24 calls/day** (ultra-conservative!)

### Option 3: Use Both Commands with Offset
**Not recommended**, but possible:

```php
// Smart-update runs at :00, :20, :40
Schedule::command('football:smart-update')->cron('0,20,40 * * * *');

// Live tracking runs at :10, :30, :50
Schedule::command('matches:update-live')->cron('10,30,50 * * * *');
```

**Result:** Still **12 calls/hour** = **288 calls/day** ❌ (exceeds limit on busy days)

---

## Summary

### ✅ Problem Identified
Two commands making duplicate API calls → would exceed free tier limit

### ✅ Solution Implemented
Disabled `football:smart-update` in scheduler → now using only `matches:update-live`

### ✅ Benefits
1. **50% reduction** in API calls during match times
2. **Better frontend caching** for unlimited user polling
3. **Event tracking** (goals, cards, subs)
4. **User pick status** calculations
5. **Projected points** in real-time
6. **Auto-cleanup** of old data

### ✅ Verified Working
- Scheduler shows only `matches:update-live` running every 10 minutes
- Manual test successful: `php artisan matches:update-live --force`
- Database populated with 5 cached matches
- API usage: **71 calls/day max** (within 100/day free tier) ✅

---

**🎉 Conflict resolved! Your live match tracking is now optimized and won't exceed API limits!**
