# Backlog & ideas

Living list of future work. Update as priorities change.

**Environments**

| | Local (`docker-compose.yml`) | Production (`docker-compose.live.yml`) |
|--|------------------------------|----------------------------------------|
| **Purpose** | Develop & test on your PC | Hetzner VPS — real users & data |
| **Config** | `.env.docker` | `.env` on server only |
| **Deploy** | `docker compose up` | Push to `main` → GitHub Actions |
| **Docs** | [WORKFLOW.md](WORKFLOW.md) | [DEPLOY.md](DEPLOY.md) |

When adding a task below, tag it: **`[local]`**, **`[production]`**, **`[both]`**, or **`[app]`** (Laravel code, applies everywhere).

---

## Admin / ops visibility (future)

**Goal:** See production health and errors without SSHing for every issue — not a full Datadog replacement, something appropriate for a small hobby app.

### Likely split

| Layer | Where it lives | Notes |
|-------|----------------|-------|
| **In-app admin UI** | `[app]` | Laravel routes/controllers — works in both envs but **only show sensitive ops data in production** (or gate behind `is_admin`). Reuse existing admin middleware. |
| **Error tracking** | `[production]` + `[app]` | e.g. Sentry, Flare, or Laravel log channel → email/Slack. `.env` / `.env.live.example` only — no keys in `.env.docker` unless you want test projects. |
| **Container health** | `[production]` | Options: Uptime Kuma on VPS, Hetzner alerts, simple `/up` + external ping (UptimeRobot), or `docker compose ps` cron + notify. **Don’t** run heavy monitoring stack in local compose. |
| **Log aggregation** | `[production]` | `docker compose logs` today; future: ship logs to one place (Loki, Papertrail, or read-only admin page that tails last N lines via guarded artisan command). |
| **Scheduler / queue failures** | `[app]` + `[production]` | DB table or notifications when `schedule:run` / `queue:work` jobs fail; verify `scheduler` and `queue` containers in **live compose only**. |

### Open questions (decide before building)

- [ ] Admin panel **inside** PL Tournament vs separate tool (e.g. Uptime Kuma + Sentry only)?
- [ ] Real-time Docker stats on admin page vs email/Slack when something breaks?
- [ ] Should local Docker expose a **debug** health dashboard (`[local]` only, `APP_DEBUG=true`)?

### Suggested phases

1. **`[production]`** — External uptime check on `https://www.pl-tournament.com/up` (free tier).
2. **`[app]`** + **`[production]`** — Sentry (or similar) for Laravel exceptions; DSN in VPS `.env` only.
3. **`[app]`** — Admin page: recent failed jobs, last scheduler run, link to docs for `docker compose logs` commands.
4. **`[production]`** — Optional: lightweight on-VPS dashboard (Uptime Kuma) for container/process up/down.

---

## Infrastructure & DevOps

- [ ] **`[both]`** — Document one-liner “is production healthy?” in DEPLOY.md (ps, logs, disk).
- [ ] **`[production]`** — Hetzner snapshot schedule or monthly `pg_dump` cron to off-site storage.
- [ ] **`[production]`** — `develop` branch workflow: confirm team always merges via PR to `main`.
- [ ] **`[local]`** — Seed script / anonymised DB dump for realistic local data without production secrets.

---

## App features (unrelated to ops — add as you think of them)

- [ ] _…_

---

## Done

_Move completed items here with date._

- [x] 2026-05 — Migrate production from Heroku → Hetzner Docker + Caddy SSL
- [x] 2026-05 — GitHub Actions auto-deploy on `main`
- [x] 2026-05 — Remove Heroku from codebase and hosting
