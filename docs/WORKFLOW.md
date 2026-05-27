# Development & deployment workflow

## Branches

| Branch | Where | Purpose |
|--------|-------|---------|
| `develop` | Your PC | Daily work + local Docker (`docker compose up`) |
| `main` | GitHub + VPS | Production — auto-deploys on push |

## Local development

```powershell
git checkout develop
docker compose up -d
```

Uses `.env.docker` (not committed). Site: http://localhost:8080

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
