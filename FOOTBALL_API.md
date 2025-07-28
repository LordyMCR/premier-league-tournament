# Football Data API Setup

This application now uses the Football-Data.org API to automatically fetch real Premier League team and fixture data.

## Setup Instructions

### 1. Get a Free API Key

1. Visit [https://www.football-data.org/](https://www.football-data.org/)
2. Click "Register" and create a free account
3. After registration, you'll get an API key (free tier allows 10 requests/minute, 100/day)

### 2. Configure Your Environment

Add your API key to your `.env` file:

```bash
FOOTBALL_DATA_API_KEY=your_api_key_here
```

### 3. Seed Your Database

Run the seeders to populate your database with real Premier League data:

```bash
# Seed teams and gameweeks
php artisan db:seed --class=PremierLeagueTeamsSeeder
php artisan db:seed --class=GameWeeksSeeder

# Or run all seeders
php artisan db:seed
```

### 4. Manual Updates

You can manually update the data anytime using the Artisan command:

```bash
# Update both teams and gameweeks
php artisan football:update

# Update only teams
php artisan football:update --teams

# Update only gameweeks
php artisan football:update --gameweeks

# Force update even if data exists
php artisan football:update --force
```

### 5. Automatic Daily Updates

The system is configured to automatically update the data daily at 3:00 AM. To enable this, make sure your Laravel scheduler is running:

```bash
# Add this to your crontab (Linux/Mac) or Task Scheduler (Windows)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Fallback System

If the API is unavailable or not configured, the system will automatically fall back to hardcoded Premier League team data. This ensures your application continues to work even without an API key.

## API Features

- **Real Team Data**: Fetches current Premier League teams with official names, logos, colors, and stadium information
- **Live Fixtures**: Gets actual match schedules and gameweek dates
- **Automatic Updates**: Daily sync to keep data current
- **Completion Status**: Tracks which gameweeks have finished
- **Error Handling**: Robust fallback system and logging

## API Endpoints Used

- **Teams**: `GET /v4/competitions/2021/teams` - Premier League teams
- **Fixtures**: `GET /v4/competitions/2021/matches` - Premier League matches

## Rate Limits

Free tier limitations:
- 10 requests per minute
- 100 requests per day
- No commercial use

For higher limits, consider upgrading to a paid plan on football-data.org.
