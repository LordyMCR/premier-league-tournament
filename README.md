# PL Picks Platform

A web application for running private Premier League prediction tournaments with friends and family. Create tournaments, make weekly picks, and track standings and statistics powered by real match data.

## Website
Visit [plpicks.com](https://www.plpicks.com).

## Core Features
- Tournament management: private tournaments, join codes, leaderboards
- Picks and scoring: gameweek picks, validation, automatic scoring, and history
- Real data integration: fixtures, squads, and results from Football-Data.org
- Profiles and privacy: avatars, optional bios, and visibility controls
- Notifications and administration: email notifications, user approval, and restriction modes
- Analytics: personal and tournament statistics, achievements, and head-to-head comparisons

## Technology
- Backend: Laravel 12 (PHP 8.2), PostgreSQL (production) / MySQL (local)
- Frontend: Vue 3, Inertia.js, Tailwind CSS, Vite
- Services: Brevo (email), AWS S3 (storage), Football-Data.org (data)

## Local Docker (quick commands)

```bash
make start-dev    # start
make stop-dev     # stop
make rebuild      # rebuild images
make help         # see all shortcuts
```

Requires Docker Desktop and `make` (Git Bash / WSL). See [docs/WORKFLOW.md](docs/WORKFLOW.md).

## Documentation
- [Development & deployment workflow](docs/WORKFLOW.md)
- [Production setup (Hetzner + Docker)](docs/DEPLOY.md)
- [Backlog & future ideas](docs/BACKLOG.md)

## Status
Actively used by a private group. The site is currently in private mode; new registrations are accepted but require approval.

## Contact
Questions or access requests: support@plpicks.com

## Author
Daniel Lord ([@LordyMCR](https://github.com/LordyMCR))
