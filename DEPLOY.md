# Deploying PL Tournament (Heroku → Hetzner VPS + Docker)

This project uses:

- **Local dev:** `docker-compose.yml` + `.env.docker`
- **Production:** `docker-compose.live.yml` + `.env` on the VPS
- **Your existing `.env`:** still for non-Docker local dev only

Production on Heroku uses **PostgreSQL** (not MySQL). The Docker setup matches that.

---

## Architecture (production)

```
Internet → Caddy (SSL) → web (nginx + PHP) → PostgreSQL
                      ↘ scheduler (cron every minute)
                      ↘ queue (background jobs / emails)
```

External services stay the same: **AWS S3**, **Brevo**, **IONOS DNS**.

---

## Part 1 — Local Docker dev (optional, test before migrating)

### 1. Create dev env file

```bash
cp .env.docker.example .env.docker
```

Generate an app key:

```bash
docker compose run --rm app php artisan key:generate
```

Or paste a key manually into `.env.docker`.

### 2. Start dev stack

```bash
docker compose up -d --build
```

- Site: http://localhost:8080
- Vite HMR: http://localhost:5173

### 3. First-time setup

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed   # if you use seeders
```

### 4. Stop

```bash
docker compose down
```

---

## Part 2 — Export data from Heroku

Replace `YOUR_HEROKU_APP` with your app name.

### 1. Backup PostgreSQL

```bash
heroku pg:backups:capture --app YOUR_HEROKU_APP
heroku pg:backups:download --app YOUR_HEROKU_APP -o heroku-backup.dump
```

### 2. Export config vars (for reference)

```bash
heroku config --app YOUR_HEROKU_APP > heroku-config.txt
```

You'll copy these values into `.env` on the VPS. **Do not commit that file.**

Important: **Do not copy `DATABASE_URL`** to production `.env`. Docker Compose sets `DB_HOST=postgres` internally.

---

## Part 3 — Hetzner VPS setup

### 1. Create the server

1. Sign up at [hetzner.com](https://www.hetzner.com/cloud)
2. Create a project → **Add Server**
3. Location: closest to you (e.g. Falkenstein or Helsinki)
4. Image: **Ubuntu 24.04**
5. Type: **CX22** (2 vCPU, 4 GB RAM, ~€4.59/mo) — recommended minimum for Postgres + 4 containers
6. Add your SSH key
7. Create server → note the **IP address**

### 2. Point DNS (can do after testing)

In **IONOS Domain Console** for `pl-tournament.com`:

| Type | Name | Value |
|------|------|-------|
| A | `@` | Your Hetzner IP |
| A | `www` | Your Hetzner IP |

Lower TTL to 300 before switching if you want faster propagation.

### 3. Install Docker on the VPS

SSH in:

```bash
ssh root@YOUR_VPS_IP
```

Install Docker:

```bash
apt update && apt upgrade -y
apt install -y git curl
curl -fsSL https://get.docker.com | sh
```

### 4. Clone the repo

```bash
mkdir -p /var/www && cd /var/www
git clone https://github.com/LordyMCR/premier-league-tournament.git
cd premier-league-tournament
```

### 5. Create production `.env`

```bash
cp .env.live.example .env
nano .env
```

Copy every value from your Heroku config vars screenshot / `heroku-config.txt`:

| Heroku var | Notes |
|------------|-------|
| `APP_KEY` | **Must be identical** — or all encrypted data breaks |
| `APP_URL` | `https://www.pl-tournament.com` |
| `AWS_*` | Same S3 credentials |
| `BREVO_*` / `MAIL_*` | Same email setup |
| `FOOTBALL_*`, `NEWS_API_KEY`, etc. | All API keys |
| `DB_PASSWORD` | Pick a **new** strong password (not Heroku's) |

Set `SITE_DOMAINS=www.pl-tournament.com,pl-tournament.com` (no spaces).

**Remove or leave blank:** `DATABASE_URL` (Docker handles DB connection).

### 6. Import Heroku database

From your **local machine**, upload the backup:

```bash
scp heroku-backup.dump root@YOUR_VPS_IP:/var/www/premier-league-tournament/
```

On the **VPS**, start Postgres first:

```bash
cd /var/www/premier-league-tournament
docker compose -f docker-compose.live.yml up -d postgres
```

Wait ~10 seconds, then restore:

```bash
docker compose -f docker-compose.live.yml exec -T postgres \
  pg_restore -U pl_tournament -d pl_tournament --clean --if-exists --no-owner --no-acl \
  < heroku-backup.dump
```

If restore errors about existing objects, that's usually fine on first import. Verify with:

```bash
docker compose -f docker-compose.live.yml exec postgres \
  psql -U pl_tournament -d pl_tournament -c "SELECT COUNT(*) FROM users;"
```

### 7. Build and start everything

```bash
docker compose -f docker-compose.live.yml up -d --build
```

This starts: **postgres**, **web**, **scheduler**, **queue**, **caddy** (auto SSL).

Check logs:

```bash
docker compose -f docker-compose.live.yml logs -f web
docker compose -f docker-compose.live.yml ps
```

### 8. Verify

Before DNS switch, test via IP (Caddy may need Host header):

```bash
curl -I http://YOUR_VPS_IP
```

Once DNS propagates, visit https://www.pl-tournament.com and check:

- [ ] Login works
- [ ] Tournaments load
- [ ] Avatars display (S3)
- [ ] Send a test email (Brevo)
- [ ] `/up` health check returns 200

---

## Part 4 — Deploying updates

On the VPS:

```bash
cd /var/www/premier-league-tournament
bash scripts/deploy-live.sh
```

Or manually:

```bash
git pull origin main
docker compose -f docker-compose.live.yml up -d --build
```

---

## Part 5 — Shut down Heroku

Once everything works for a few days:

```bash
heroku apps:destroy YOUR_HEROKU_APP --confirm YOUR_HEROKU_APP
```

Cancel any Heroku Postgres / add-on billing.

---

## Environment variables cheat sheet

| File | Used by | Committed? |
|------|---------|------------|
| `.env` | Local non-Docker dev | No |
| `.env.docker` | `docker compose up` (dev) | No |
| `.env` on VPS | `docker compose -f docker-compose.live.yml` | No |
| `.env.docker.example` | Template for dev | Yes |
| `.env.live.example` | Template for production | Yes |

---

## Troubleshooting

### 502 / site not loading

```bash
docker compose -f docker-compose.live.yml logs web caddy
```

### Database connection refused

Ensure `.env` has `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` matching the `postgres` service in `docker-compose.live.yml`.

### SSL not working

- DNS must point to the VPS IP
- Ports 80 and 443 open in Hetzner firewall (default: open)
- `SITE_DOMAINS` must match your real domain

### Scheduler not running

```bash
docker compose -f docker-compose.live.yml logs scheduler
```

You should see `schedule:run` output every minute.

### Out of memory on small VPS

Upgrade to CX22 (4 GB) or stop the `queue` container if `QUEUE_CONNECTION=sync` in Heroku (check `heroku config:get QUEUE_CONNECTION`).

---

## Monthly cost estimate

| Service | Cost |
|---------|------|
| Hetzner CX22 | ~€4.59/mo |
| AWS S3 (2 users) | ~$0 |
| Brevo free tier | $0 |
| IONOS domain | existing |
| **Total** | **~€5/mo** |
