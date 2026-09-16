# Season rollover checklist

Use this when archiving a finished Premier League season and opening the next one.

## Before deploy

1. Take a full database backup.
2. Deploy the season-aware code (migrations + `season:migrate-existing` command).
3. Do **not** run a naive `football:update` until after the data split — old gameweeks were keyed only by `week_number`.

## On production (order matters)

```bash
# 1. Run migrations
php artisan migrate --force

# 2. Preview the split (no writes)
php artisan season:migrate-existing --dry-run

# 3. Apply the split (creates 2025-26 + 2026-27, remaps games, completes tournaments)
php artisan season:migrate-existing

# 4. Sync the current season fixtures/teams (2026-27 when calendar is Aug+)
php artisan football:update --force

# 5. Clear caches
php artisan cache:clear
```

Optional cutoff override if fixtures straddle an unexpected date:

```bash
php artisan season:migrate-existing --cutoff=2026-08-10
```

## Verify

| Check | Expectation |
|-------|-------------|
| Schedule Gameweek 1 (current) | Only new-season fixtures (no last-season FT mixed in) |
| Schedule Gameweek 1 (`?season=2025-26`) | Archived results for 2025-26 |
| Standings (current) | 20 teams for 2026-27 |
| Standings (`?season=2025-26`) | Final 2025-26 table (20 teams) |
| Tournaments list | Old tournaments show **completed** + season badge; viewable but no new picks/joins |
| New tournament create | Bound to current season only |
| `football:update` | Upserts gameweeks by `(season_id, week_number)` |

## Ongoing

- Daily `football:update` targets the calendar current season (`Season::ensureForApiYear`).
- `picks:reset-and-score` defaults to the **current** season; use `--all` only when intentionally rescoring history.
- `picks:auto-assign` only processes active tournaments for the gameweek’s season.

## Next year

1. Mark previous season `is_current = false` (or rely on `football:update` when the new API year becomes current).
2. Run `football:update --season=YYYY` for the new API year.
3. Complete remaining active tournaments for the finished season if any slipped through.
