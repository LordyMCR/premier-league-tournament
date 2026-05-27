# Development & deployment workflow

## Branches

| Branch | Where | Purpose |
|--------|-------|---------|
| `develop` | Your PC | Daily work + local Docker (`docker compose up`) |
| `main` | GitHub + VPS | Production — auto-deploys on push |

## Local development

```powershell
git checkout develop
make start-dev
```

Uses `.env.docker` (not committed). Site: http://localhost:8080

### Make shortcuts

From the project root (requires [GNU Make](https://www.gnu.org/software/make/) — use **Git Bash**, **WSL**, or `choco install make` on Windows):

| Command | What it does |
|---------|----------------|
| `make` or `make help` | List commands |
| `make start-dev` | `docker compose up -d` |
| `make stop-dev` | `docker compose down` |
| `make restart-dev` | Restart dev containers |
| `make rebuild` | Rebuild images and start |
| `make rebuild-fresh` | Wipe dev DB volume + rebuild |
| `make logs` | Follow all logs |
| `make migrate` | Run migrations |
| `make shell` | Bash inside app container |
| `make artisan CMD="..."` | Run any artisan command |

### Production shortcuts (on the VPS via SSH)

After `cd /var/www/premier-league-tournament` (install `make` with `apt install make` if needed):

| Command | What it does |
|---------|----------------|
| `make ps-live` | Container status |
| `make logs-live` | All logs |
| `make logs-web` / `make logs-caddy` | App or SSL logs |
| `make restart-live` | Restart containers |
| `make rebuild-live` | Rebuild images (slow) |
| `make deploy-live` | Same as GitHub Actions deploy script |

Day-to-day deploys: push to `main` (auto-deploy). Use Make on the VPS for debugging or manual deploys.

Non-Docker local dev still uses `.env` + MySQL as before.

## Ship to production

```powershell
git checkout main
git pull origin main
git merge develop
git push origin main
```

GitHub Actions SSHs to the VPS and runs `scripts/deploy-live.sh` (build + restart).

No manual SSH required after one-time setup below.

## One-time: enable auto-deploy

1. GitHub repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret | Value |
|--------|--------|
| `VPS_HOST` | `62.238.43.223` |
| `VPS_SSH_USER` | `root` |
| `VPS_SSH_KEY` | Private key for deploy (see below) |
| `VPS_SSH_PASSPHRASE` | Only if that key has a passphrase — otherwise leave this secret unset |

### SSH key for GitHub Actions (pick one)

**Option A — Use your existing Hetzner key**

- `VPS_SSH_KEY` = contents of `id_ed25519_hetzner` (private key)
- `VPS_SSH_PASSPHRASE` = the passphrase you set when creating that key

**Option B — Dedicated deploy key (recommended, no passphrase)**

On your PC:

```powershell
ssh-keygen -t ed25519 -f $env:USERPROFILE\.ssh\id_ed25519_github_deploy -C "github-actions" -N '""'
```

Add the public key on the VPS (SSH in with your normal key first):

```bash
echo "PASTE_CONTENTS_OF_id_ed25519_github_deploy.pub" >> ~/.ssh/authorized_keys
```

GitHub secrets:

- `VPS_SSH_KEY` = contents of `id_ed25519_github_deploy` (private, no passphrase)
- Do **not** set `VPS_SSH_PASSPHRASE`

2. Commit and push `.github/workflows/deploy-production.yml` to `main`.

3. **Actions** tab → confirm "Deploy production" succeeds on the next push.

## Manual deploy (fallback)

```bash
ssh -i ~/.ssh/id_ed25519_hetzner root@62.238.43.223
cd /var/www/premier-league-tournament
bash scripts/deploy-live.sh
```

## Do not commit

- `.env`, `.env.docker`
- `*.dump`, `config-export.txt`
