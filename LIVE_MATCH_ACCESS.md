# Live Match Tracker - Access Points

## Where Can Users See Live Matches?

The LiveMatchTracker component is now available on **2 main pages**:

### 1. 📊 Dashboard (Main Access Point)
**Route:** `/dashboard`  
**Navigation:** Click "My Tournament" in the header

**Location:** Appears between the hero section and performance stats

**Why here:**
- First place users land after login
- Natural starting point for the app
- Shows live matches alongside tournament info

---

### 2. 📅 Fixtures Page (Schedule)
**Route:** `/schedule`  
**Navigation:** Click "Fixtures" in the header

**Location:** Appears between the filters and gameweeks grid

**Why here:**
- Users checking the schedule likely want live updates
- Complements fixture browsing
- Natural place for match-day activity

---

## User Journey Examples

### Scenario 1: Saturday Afternoon Match Day
1. User opens app → Lands on **Dashboard**
2. Sees "Live Matches" section with animated red "LIVE" indicator
3. Views:
   - Current scores (Arsenal 2-1 Chelsea)
   - Match minute (67')
   - Their pick status (✓ Winning - 3 pts)
   - Recent events (⚽ Goals, 🟨 Cards)

### Scenario 2: Checking Full Fixtures
1. User clicks "Fixtures" in header → Opens **Schedule page**
2. Filters matches by their favorite team
3. Scrolls down to see "Live Matches" section
4. Sees their team playing live with current score

### Scenario 3: Mobile Quick Check
1. User gets notification on phone
2. Opens PWA → **Dashboard** loads
3. Immediately sees live scores at top of feed
4. Checks projected points without scrolling

---

## Component Features (Both Pages)

### What Users See

#### When Matches Are Live:
```
┌─────────────────────────────────────────┐
│ 🔴 LIVE     Live Matches                │
│         Updated 1 min ago               │
├─────────────────────────────────────────┤
│                                         │
│  [Arsenal Logo]  Arsenal  2-1  Chelsea  [Chelsea Logo]│
│                     67'                 │
│                                         │
│  Your pick: Arsenal                     │
│  ✓ Winning - 3 pts                      │
│                                         │
│  23' ⚽ Saka                             │
│  45' 🟨 Sterling                        │
└─────────────────────────────────────────┘
```

#### When No Matches Are Live:
```
┌─────────────────────────────────────────┐
│           Live Matches                  │
├─────────────────────────────────────────┤
│                                         │
│              ⚽                          │
│   No matches currently live             │
│   Check back during match days          │
│                                         │
└─────────────────────────────────────────┘
```

---

## Future Enhancement Ideas

### Potential Additional Access Points:

#### 3. Tournament Page (Future)
**Route:** `/tournaments/{tournament}`  
**Why:** Show live matches relevant to that specific tournament
**Implementation:** Filter live matches by tournament participants' picks

#### 4. Dedicated Live Matches Page (Future)
**Route:** `/live` or `/matches/live`  
**Why:** Full-screen experience for match day
**Features:**
- Larger match cards
- More detailed event timeline
- Multiple matches side-by-side
- Auto-scroll to live matches

#### 5. Header Indicator (Future)
**Location:** Main navigation bar  
**Why:** Always-visible live match indicator
**Implementation:**
```vue
<div v-if="hasLiveMatches" class="flex items-center gap-2">
  <span class="relative flex h-3 w-3">
    <span class="animate-ping absolute h-full w-full rounded-full bg-red-400 opacity-75"></span>
    <span class="relative rounded-full h-3 w-3 bg-red-500"></span>
  </span>
  <span class="text-sm font-medium">2 Live</span>
</div>
```

---

## Current Implementation

### Files Modified:

1. **Dashboard.vue**
   - Line ~98: `<LiveMatchTracker />` component added
   - Wrapped in white card with padding
   
2. **Schedule/Index.vue**  
   - Line ~4: Import added
   - Line ~234: `<LiveMatchTracker />` component added
   - Placed between filters and gameweeks grid

### Component Features:

✅ **Auto-refresh:** Polls database every 60 seconds  
✅ **Responsive:** Mobile-first design with short team names  
✅ **Pick status:** Shows if user's pick is winning/drawing/losing  
✅ **Projected points:** Real-time calculation (3/1/0 pts)  
✅ **Event timeline:** Recent goals, cards, substitutions  
✅ **Stats summary:** Total picks, winning picks, projected total  
✅ **Loading states:** Skeleton while fetching  
✅ **No matches state:** Clear message when nothing live  

---

## API Endpoints Used

The component polls these endpoints:

### GET `/api/live-matches`
Returns:
```json
{
  "live_matches": [
    {
      "id": 123,
      "home_team": {...},
      "away_team": {...},
      "live_event": {
        "home_score": 2,
        "away_score": 1,
        "minute": 67,
        "status": "IN_PLAY",
        "events": [...]
      },
      "user_pick": {
        "team_name": "Arsenal",
        "status": "winning",
        "projected_points": 3
      }
    }
  ],
  "stats": {
    "total_picks": 5,
    "winning_picks": 3,
    "projected_points": 11
  }
}
```

### GET `/api/live-matches/{game}`
Returns detailed match events (not currently used in UI)

---

## Performance

### Database Queries (Per Poll)
- 1 query to `live_match_events` table
- 1 query to `picks` table (user's picks)
- **Total:** ~2-3 queries every 60 seconds per user

### Zero External API Calls
- Frontend polls **local database only**
- Backend updates every 15 minutes during match windows
- Supports unlimited concurrent users

---

## Mobile Experience

### Optimizations:
- Short team names (Wolverhampton → Wolves)
- Compact layout for small screens
- Touch-friendly tap targets
- No horizontal scroll
- Animated indicators catch attention

### PWA Benefits:
- Push notifications (future)
- Offline capability
- Home screen shortcut
- Full-screen mode

---

## Summary

**Current Access Points:** 2 (Dashboard + Schedule)  
**User Experience:** Excellent (60s updates, clear status)  
**Performance:** Optimal (database caching)  
**Mobile:** Fully responsive  
**Future Potential:** High (notifications, dedicated page)  

The live match tracker is now accessible from the two most logical places in your app! 🎉
