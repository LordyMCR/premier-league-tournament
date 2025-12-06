# WORST-CASE API Usage Analysis - Premier League Season

## 🚨 Critical Question: Can We Exceed the Free Tier?

**SHORT ANSWER: YES - In extreme cases during Christmas/Boxing Day fixture congestion, we could exceed 100 calls/day!** ⚠️

Let me break down the realistic scenarios:

---

## Premier League Match Schedule Patterns

### Typical Weekend (90% of season)
- **Friday:** 1 match (8pm kickoff)
- **Saturday:** 5-7 matches (12:30pm, 3pm, 5:30pm kickoffs)
- **Sunday:** 2-3 matches (2pm, 4:30pm kickoffs)

### Midweek Rounds (Dec/Jan/Feb/Mar - ~10 weeks)
- **Tuesday:** 3-5 matches
- **Wednesday:** 3-5 matches
- **Thursday:** 0-1 matches

### Festive Period (Christmas/Boxing Day/New Year)
- **Boxing Day (Dec 26):** ALL 10 MATCHES IN ONE DAY! 🎄
- **Dec 27-29:** Another full round (10 matches over 2-3 days)
- **New Year's Day:** Another full round

---

## Current Match Windows in Code

```php
// Weekend: Saturday & Sunday (11am - 10pm) = 11 hours
if (in_array($dayOfWeek, [0, 6])) {
    return $hour >= 11 && $hour <= 22;
}

// Midweek: Tuesday/Wednesday/Thursday (6pm - 11pm) = 5 hours
if (in_array($dayOfWeek, [2, 3, 4])) {
    return $hour >= 18 && $hour <= 23;
}

// Monday evening (7pm - 11pm) = 4 hours
if ($dayOfWeek === 1) {
    return $hour >= 19 && $hour <= 23;
}

// Fallback: Check if games exist today
return Game::whereDate('kick_off_time', today())
    ->whereIn('status', ['SCHEDULED', 'LIVE', 'IN_PLAY', 'PAUSED'])
    ->exists();
```

---

## Scenario Analysis

### 1️⃣ TYPICAL SATURDAY (Most Common - 38 weeks/year)
**Matches:** 12:30pm, 3pm (x5), 5:30pm kickoffs

**API Calls:**
- Daily maintenance (01:00-04:00): 5 calls
- Live tracking (11:00-22:00): 6 calls/hour × 11 hours = **66 calls**
- **TOTAL: 71 calls/day** ✅

**Status:** Safe (29% under limit)

---

### 2️⃣ TYPICAL SUNDAY (Most Common - 38 weeks/year)
**Matches:** 2pm, 4:30pm kickoffs

**API Calls:**
- Daily maintenance: 5 calls
- Live tracking (11:00-22:00): 6 calls/hour × 11 hours = **66 calls**
- **TOTAL: 71 calls/day** ✅

**Status:** Safe (29% under limit)

---

### 3️⃣ FRIDAY NIGHT (Every week - 38 weeks/year)
**Matches:** 8pm kickoff (1 match)

**Current Behavior:** ❌ NO MATCH WINDOW DEFINED FOR FRIDAY!

**API Calls:**
- Daily maintenance: 5 calls
- Live tracking: **0 calls** (Friday not in match windows!)
- Fallback: Checks `Game::whereDate` → triggers if games exist
- **TOTAL: 5-35 calls/day** (depends on fallback logic)

**Status:** ⚠️ FRIDAY IS MISSING FROM SCHEDULE!

---

### 4️⃣ MIDWEEK MATCH (Tuesday/Wednesday - 10 weeks/year)
**Matches:** 7:45pm, 8pm kickoffs (3-5 matches per day)

**API Calls:**
- Daily maintenance: 5 calls
- Live tracking (18:00-23:00): 6 calls/hour × 5 hours = **30 calls**
- **TOTAL: 35 calls/day** ✅

**Status:** Safe (65% under limit)

---

### 5️⃣ BOXING DAY (Dec 26) 🎄 - WORST CASE!
**Matches:** ALL 10 PREMIER LEAGUE MATCHES (12:30pm, 3pm, 5:30pm, 8pm kickoffs)

**Current Code Behavior:**
- Runs 11:00-22:00 (11 hours) because it's Saturday/Sunday (Boxing Day can be any day!)
- 6 calls/hour × 11 hours = **66 calls**
- Daily maintenance: **5 calls**
- **TOTAL: 71 calls/day** ✅

**BUT WAIT!** If Boxing Day falls on a weekday (Mon-Fri), the match window is WRONG!

---

### 6️⃣ BOXING DAY ON MONDAY (Real Risk!)
**Matches:** 10 matches from 12:30pm - 8pm

**Current Code:**
```php
// Monday evening (7pm - 11pm) = 4 hours only!
if ($dayOfWeek === 1) {
    return $hour >= 19 && $hour <= 23;
}
```

**Problem:** Matches start at 12:30pm but window only starts at 7pm! ❌

**Actual API Calls:**
- Window: 19:00-23:00 = 4 hours × 6 = 24 calls
- **TOTAL: 29 calls/day** ✅ (but misses most matches!)

**Status:** ⚠️ FUNCTIONALLY BROKEN (misses 6 hours of matches)

---

### 7️⃣ DOUBLE GAMEWEEK (FA Cup weekends - 5-6 times/year)
**Scenario:** Saturday + Sunday both full of matches, PLUS Friday night match

**Friday:**
- **ISSUE:** No Friday window! Falls back to database check
- Estimate: 5-35 calls

**Saturday:**
- 71 calls ✅

**Sunday:**
- 71 calls ✅

**3-Day Total: ~150-180 calls** ❌ (50-80% over limit!)

**Status:** 🚨 EXCEEDS LIMIT!

---

### 8️⃣ CHRISTMAS PERIOD (Dec 26-Jan 2)
**7 days with:**
- Dec 26 (Boxing Day): 10 matches
- Dec 27-29: 10 matches spread over 2-3 days
- Jan 1 (New Year): 10 matches

**Daily breakdown:**
- **Dec 26:** 71 calls ✅
- **Dec 27:** 71 calls ✅
- **Dec 28:** 71 calls ✅
- **Dec 29:** 71 calls ✅
- **Dec 30:** 5 calls ✅
- **Dec 31:** 5 calls ✅
- **Jan 1:** 71 calls ✅

**Weekly Total: ~365 calls over 7 days** ✅
**Daily Average: ~52 calls/day** ✅

**Status:** Safe (individual days under limit)

---

## 🔥 THE REAL PROBLEMS

### Problem 1: Friday Not Defined ❌
```php
// Current code has NO Friday window!
// Friday matches run at 8pm every single week
```

### Problem 2: Weekday Match Windows Too Narrow ❌
```php
// Monday: 7pm-11pm (4 hours)
// But Boxing Day matches can start at 12:30pm!
```

### Problem 3: Fallback is Risky ⚠️
```php
// Fallback checks database for games
// But doesn't limit hours - could run all day!
return Game::whereDate('kick_off_time', today())
    ->whereIn('status', ['SCHEDULED', 'LIVE', 'IN_PLAY', 'PAUSED'])
    ->exists();
```

If this fallback runs all day (24 hours):
- 6 calls/hour × 24 hours = **144 calls** ❌ (exceeds limit!)

---

## 🛠️ SOLUTIONS

### Option 1: Add Friday + Expand Windows (RECOMMENDED)
```php
private function isMatchWindow(): bool
{
    $now = now();
    $dayOfWeek = $now->dayOfWeek;
    $hour = $now->hour;

    // Weekend: Friday/Saturday/Sunday (11am - 11pm)
    if (in_array($dayOfWeek, [5, 6, 0])) { // Fri=5, Sat=6, Sun=0
        return $hour >= 11 && $hour <= 23;
    }

    // Midweek + Bank Holidays: Mon-Thu (11am - 11pm for flexibility)
    if (in_array($dayOfWeek, [1, 2, 3, 4])) {
        return $hour >= 11 && $hour <= 23;
    }

    return false; // Remove risky fallback
}
```

**API Usage:**
- Weekend day: 6 calls/hour × 12 hours = **72 calls + 5 = 77 calls/day** ✅
- Midweek day: 6 calls/hour × 12 hours = **72 calls + 5 = 77 calls/day** ✅
- No matches: **5 calls/day** ✅

**Status:** Safe, but uses 77% of quota on match days

---

### Option 2: Reduce Frequency to 15 Minutes (SAFEST)
```php
// In routes/console.php
Schedule::command('matches:update-live')
    ->everyFifteenMinutes() // Instead of everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

**API Usage:**
- 4 calls/hour × 12 hours = **48 calls + 5 = 53 calls/day** ✅
- **Even on double gameweeks:** 53 calls × 3 days = 159 calls (53/day avg) ✅

**Trade-off:** Live updates every 15 minutes instead of 10 (still excellent!)

---

### Option 3: Smart Hybrid (OPTIMAL)
Combine both approaches:
- Add Friday + expand windows
- Reduce frequency to 15 minutes

**Result:**
- 4 calls/hour × 12 hours = **48 calls + 5 = 53 calls/day** ✅
- **53% quota usage** (47% headroom for errors)
- Updates every 15 minutes (perfectly acceptable for live tracking)

---

## 📊 Final Comparison

| Scenario | Current Setup | Option 1 (Add Fri) | Option 2 (15 min) | Option 3 (Both) |
|----------|---------------|-------------------|-------------------|-----------------|
| **Typical Saturday** | 71 calls ✅ | 77 calls ✅ | 53 calls ✅ | 53 calls ✅ |
| **Friday Match** | 5-35 calls ⚠️ | 77 calls ✅ | 53 calls ✅ | 53 calls ✅ |
| **Boxing Day** | 71 calls ✅ | 77 calls ✅ | 53 calls ✅ | 53 calls ✅ |
| **Double Gameweek** | ~150 calls ❌ | ~231 calls ❌ | ~159 calls ❌ | ~159 calls ❌ |
| **Safety Margin** | 29% | 23% | 47% | 47% |

---

## ⚠️ Double Gameweek Problem

**No solution keeps 3 consecutive match days under 100/day each:**
- Fri + Sat + Sun with full matches = 150-230 calls over 3 days
- This WILL happen 5-6 times per season

**BUT:** The API limit is **100 calls per day**, not per rolling 3 days!
- As long as EACH individual day stays under 100, you're fine ✅

---

## 🎯 RECOMMENDED IMPLEMENTATION

### Use Option 3 (Hybrid Approach):

1. **Expand match windows to include Friday**
2. **Reduce polling to every 15 minutes**
3. **Remove risky fallback**

**Benefits:**
- ✅ 53 calls/day max (47% under limit)
- ✅ Covers Friday night matches
- ✅ Covers Boxing Day (any day of week)
- ✅ Still excellent UX (15min updates is fine)
- ✅ Huge safety margin for API hiccups

**Trade-offs:**
- Users see updates every 15 min instead of 10 min (barely noticeable)

---

## 🚨 ANSWER TO YOUR QUESTION

> "Are we certain that we won't be using more than the allocated API calls?"

**HONEST ANSWER:** 

With the **CURRENT setup (Option 0):**
- ❌ **NO** - Friday matches are not properly covered
- ⚠️ **MAYBE** - Double gameweeks could push close to limit
- ✅ **YES** - Single match days are safe

With **RECOMMENDED setup (Option 3):**
- ✅ **YES** - 53 calls/day leaves 47% safety margin
- ✅ **YES** - All days covered properly (Fri-Sun)
- ✅ **YES** - Even double gameweeks stay under 60 calls/day

---

## 🛡️ INSURANCE POLICY

Add monitoring to auto-adjust if approaching limit:

```php
// In UpdateLiveMatches.php
private function checkApiQuota(): bool
{
    $today = today();
    $callsToday = Cache::get("api_calls_{$today}", 0);
    
    if ($callsToday >= 90) {
        $this->warn("⚠️  Approaching API limit ({$callsToday}/100) - skipping update");
        return false;
    }
    
    return true;
}

public function handle()
{
    if (!$this->checkApiQuota()) {
        return 0;
    }
    
    // ... make API call ...
    
    Cache::increment("api_calls_{$today}");
}
```

This creates a circuit breaker at 90 calls/day!

---

## 🎬 NEXT STEPS

Would you like me to:
1. ✅ Implement Option 3 (15-minute polling + Friday windows)?
2. 📊 Add API quota monitoring?
3. 🔔 Set up alerts when approaching 80% of limit?

Let me know and I'll implement it right away! 🚀
