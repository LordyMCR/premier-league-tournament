# Production deployment (Hetzner VPS + Docker)

- **Local dev:** `docker-compose.yml` + `.env.docker`
- **Production:** `docker-compose.live.yml` + `.env` on the VPS

## Architecture

```
Internet → Caddy (SSL) → web (nginx + PHP) → PostgreSQL
                      ↘ scheduler (cron every minute)
                      ↘ queue (background jobs / emails)
```

External services: **AWS S3**, **Brevo**, **IONOS DNS**.

## Initial VPS setup

1. Ubuntu 24.04 server with Docker installed (`curl -fsSL https://get.docker.com | sh`)
2. Clone repo to `/var/www/premier-league-tournament`
3. `cp .env.live.example .env` and fill in production values
4. Point IONOS A records for `@` and `www` at the server IP
5. Ensure `docker/caddy/Caddyfile` lists your domains (space after commas)
6. Start stack:

```bash
cd /var/www/premier-league-tournament
docker compose -f docker-compose.live.yml up -d --build
```

## Database restore (from backup)

If importing an existing PostgreSQL custom-format dump:

```bash
docker compose -f docker-compose.live.yml up -d postgres
# wait ~15s
docker compose -f docker-compose.live.yml exec -T postgres \
  pg_restore -U pl_tournament -d pl_tournament --clean --if-exists --no-owner --no-acl \
  < backup.dump
```

Production uses **PostgreSQL 17** (`postgres:17-alpine`).

## Deploying updates

Pushes to `main` auto-deploy via GitHub Actions (see [WORKFLOW.md](WORKFLOW.md)).

Manual deploy:

```bash
cd /var/www/premier-league-tournament
bash scripts/deploy-live.sh
```

## Troubleshooting

```bash
docker compose -f docker-compose.live.yml ps
docker compose -f docker-compose.live.yml logs web caddy scheduler
```

### SSL / Caddy

Domains are configured in `docker/caddy/Caddyfile`, not via `SITE_DOMAINS` in `.env`.

### Database

Ensure `.env` `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` match `docker-compose.live.yml`.

## Environment files

| File | Used by |
|------|---------|
| `.env` | Local non-Docker dev |
| `.env.docker` | Local Docker dev |
| `.env` on VPS | Production |
