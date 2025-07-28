# Premier League Tournament - Football Data Integration

This system automatically fetches real Premier League data using the Football-Data.org API.

## Setup

### 1. Get a Free API Key

1. Visit [https://www.football-data.org/](https://www.football-data.org/)
2. Register for a free account
3. Get your API key from the dashboard
4. Add it to your `.env` file:

```bash
FOOTBALL_DATA_API_KEY=your_api_key_here
```

### 2. Seed Initial Data

```bash
# Seed both teams and gameweeks
php artisan db:seed --class=PremierLeagueTeamsSeeder
php artisan db:seed --class=GameWeeksSeeder

# Or use the manual update command
php artisan football:update
```

## Features

✅ **Real Team Data**: Fetches current Premier League teams with:
- Official team names
- Team logos
- Proper team colors
- Stadium information
- Founded year

✅ **Real Gameweek Schedule**: Fetches actual 2025/2026 season fixture dates:
- Real matchday dates
- Automatic completion status
- 38 gameweeks (full season)

✅ **Fallback System**: Works without API key using hardcoded data

✅ **Daily Updates**: Automatically scheduled to run daily at 3 AM

## Manual Commands

```bash
# Update everything
php artisan football:update

# Update only teams
php artisan football:update --teams

# Update only gameweeks  
php artisan football:update --gameweeks

# Force update (skip confirmations)
php artisan football:update --force
```

## API Limits

**Free Tier**: 100 requests per day, 10 per minute

The system is designed to be efficient:
- Teams: 1 request per update
- Gameweeks: 1 request per update  
- Daily updates: 2 requests per day
- Well within free limits!

## Automatic Scheduling

The system automatically updates data daily at 3 AM via Laravel's scheduler.

To enable this in production, add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Data Structure

### Teams Table
- `name`: Arsenal FC
- `short_name`: ARS
- `primary_color`: #EF0107 (team colors)
- `secondary_color`: #9C824A
- `logo_url`: Official team crest
- `external_id`: API reference ID
- `founded`: 1886
- `venue`: Emirates Stadium

### GameWeeks Table
- `week_number`: 1-38
- `name`: Gameweek 1
- `start_date`: 2025-08-15
- `end_date`: 2025-08-21
- `is_completed`: false (updated automatically)

## Benefits

🚀 **Always Current**: Real fixture dates, not hardcoded schedules
🎨 **Rich Data**: Team colors, logos, venues automatically populated  
⚡ **Reliable**: Fallback system ensures it always works
🔄 **Automated**: Set it and forget it - updates daily
📊 **Accurate**: Official Premier League data source
