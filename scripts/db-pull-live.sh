#!/usr/bin/env bash
# Pull the Hetzner live Postgres DB and replace the local Docker Postgres DB.
#
# Setup (once, local only — not committed):
#   cp .env.pull.example .env.pull
#   # edit .env.pull and set VPS_HOST
#
# Usage:
#   make db-pull-live
#   CONFIRM=yes make db-pull-live          # skip prompt
#
# Required: VPS_HOST (via .env.pull or environment)
# Optional:
#   VPS_SSH_USER   default: root
#   VPS_SSH_KEY    default: ~/.ssh/id_ed25519_hetzner (if that file exists)
#   VPS_APP_DIR    default: /var/www/premier-league-tournament
#   DUMP_FILE      default: storage/app/live-pull.sql

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Load local overrides (gitignored)
if [[ -f "$ROOT_DIR/.env.pull" ]]; then
  set -a
  # shellcheck disable=SC1091
  source "$ROOT_DIR/.env.pull"
  set +a
fi

COMPOSE_DEV="${COMPOSE_DEV:-docker compose}"
VPS_SSH_USER="${VPS_SSH_USER:-root}"
VPS_SSH_KEY="${VPS_SSH_KEY:-$HOME/.ssh/id_ed25519_hetzner}"
VPS_APP_DIR="${VPS_APP_DIR:-/var/www/premier-league-tournament}"
DUMP_FILE="${DUMP_FILE:-storage/app/live-pull.sql}"

if [[ -z "${VPS_HOST:-}" ]]; then
  echo "VPS_HOST is not set." >&2
  echo "Copy .env.pull.example to .env.pull and set VPS_HOST (gitignored)." >&2
  exit 1
fi

# accept-new: trust the host on first connect; still fail if the key changes later.
# BatchMode: don't hang waiting for a password (key/agent auth only).
SSH_OPTS=(
  -o BatchMode=yes
  -o ConnectTimeout=15
  -o StrictHostKeyChecking=accept-new
)
if [[ -f "$VPS_SSH_KEY" ]]; then
  SSH_OPTS+=(-i "$VPS_SSH_KEY")
fi

SSH_TARGET="${VPS_SSH_USER}@${VPS_HOST}"

echo "==> This will REPLACE your local Docker database with a copy of live (${SSH_TARGET})."
if [[ "${CONFIRM:-}" != "yes" ]]; then
  read -r -p "Continue? [y/N] " reply
  case "$reply" in
    y|Y|yes|YES) ;;
    *)
      echo "Aborted."
      exit 1
      ;;
  esac
fi

echo "==> Ensuring local postgres is up..."
$COMPOSE_DEV up -d postgres

echo "==> Waiting for local postgres..."
for _ in $(seq 1 30); do
  if $COMPOSE_DEV exec -T postgres sh -lc 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"' >/dev/null 2>&1; then
    break
  fi
  sleep 1
done
if ! $COMPOSE_DEV exec -T postgres sh -lc 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"' >/dev/null 2>&1; then
  echo "Local postgres is not ready. Run: make start-dev" >&2
  exit 1
fi

mkdir -p "$(dirname "$DUMP_FILE")"

echo "==> Dumping live database over SSH (this may take a minute)..."
ssh "${SSH_OPTS[@]}" "$SSH_TARGET" \
  "cd $(printf '%q' "$VPS_APP_DIR") && docker compose -f docker-compose.live.yml exec -T postgres sh -lc 'pg_dump -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\" --clean --if-exists --no-owner --no-acl'" \
  > "$DUMP_FILE"

if [[ ! -s "$DUMP_FILE" ]]; then
  echo "Dump file is empty (${DUMP_FILE}). Check SSH access and live postgres." >&2
  exit 1
fi

echo "==> Restoring into local postgres (replacing existing data)..."
$COMPOSE_DEV exec -T postgres sh -lc 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -v ON_ERROR_STOP=1' < "$DUMP_FILE"

echo "==> Done."
echo "    Local DB now matches live snapshot."
echo "    Dump kept at: ${DUMP_FILE}"
echo "    Refresh DataGrip if you have it open."
