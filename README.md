# ClearPhish – Internal Phishing Simulation SaaS

> Authorized internal phishing awareness simulation platform.

## ⚠️ Authorized Use Only

This tool is designed exclusively for **authorized internal security awareness campaigns**.  
It must only be used by organizations with proper consent, legal authorization, and employee notification policies.  
**Never use against third parties or without explicit organizational authorization.**

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.4) |
| Frontend | Vue 3 + Inertia.js |
| Database | PostgreSQL 16 |
| Queue | Redis + Laravel Queue Workers |
| Email (dev) | Mailpit (local SMTP trap) |
| Assets | Vite |
| Deploy | Docker Compose |

---

## Quick Start

```bash
# 1. Configure environment
cp .env.example .env

# 2. Start services
docker compose up -d

# 3. First-time setup
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm run build

# 4. Access
#   App:     http://localhost:8000
#   Mailpit: http://localhost:8025
```

### Default credentials (seeded demo)

```
Email:    admin@demo.local
Password: password
```

---

## Local Development (no Docker)

Requirements: PHP 8.4, Composer, Node.js 22, PostgreSQL, Redis.

```bash
cp .env.example .env
# Set DB_HOST=127.0.0.1, REDIS_HOST=127.0.0.1

composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run dev

# In separate terminals:
php artisan serve
php artisan queue:work redis --queue=campaigns,tracking,default
php artisan schedule:work
```

---

## Architecture

```
┌─────────────────────────────────────────────────┐
│  Laravel 11 + Inertia.js + Vue 3                │
│  Single process serves both backend + SPA       │
├──────────┬──────────────────────────────────────┤
│ App      │  HTTP routes, auth, admin panel       │
│ Worker   │  Queue: campaign emails + tracking    │
│ Scheduler│  Auto-launch/finish, data retention   │
└──────────┴──────────────────────────────────────┘
         ↕                       ↕
    PostgreSQL 16             Redis 7
    (data + events)     (queues + sessions + cache)
```

---

## Phishing Simulation Flow

1. Admin creates **Organization** → verifies domain via DNS TXT record
2. Admin adds **Sending Profile** (authorized SMTP) → DNS checks run automatically
3. Admin imports **Target Users** (CSV/manual) – only verified domains accepted
4. Admin creates **Email Template** with variables (`{{first_name}}`, `{{landing_url}}`, etc.)
5. Admin creates **Landing Page** – form values are **never stored**, only metadata
6. Admin creates **Campaign** → selects groups, time window, rate limit
7. Scheduler dispatches emails randomly within configured window
8. Events recorded: `email_opened`, `link_clicked`, `landing_loaded`, `form_submitted`, `email_reported`
9. Dashboard shows metrics, department risk tables, timeline, CSV export

---

## Security Controls

| Control | Implementation |
|---|---|
| Domain verification | DNS TXT record required before any campaign |
| No credential storage | Form values discarded or SHA-256 hashed; only field type metadata stored |
| IP truncation | Last octet removed (`192.168.1.0` instead of full IP) |
| Sender restriction | Only verified domains usable in sending profiles |
| Recipient restriction | Only authorized-domain emails can receive campaigns |
| Audit log | All admin actions logged with user, IP, timestamp |
| Emergency stop | Pause/cancel at any time |
| Exclusion list | Per-user campaign exclusion |
| Rate limiting | Configurable emails/minute per campaign |
| Dry run | Test full flow without sending real emails |
| CSRF/XSS protection | Laravel built-in + Inertia CSRF |

---

## CSV Import Format

```csv
first_name,last_name,email,department,job_title,office,language
Ana,García,ana.garcia@company.com,Engineering,Developer,Madrid,es
```

---

## Roles

| Role | Access |
|---|---|
| `superadmin` | Full access, all orgs |
| `org_admin` | Full access, own org |
| `campaign_manager` | Create/manage campaigns |
| `analyst` | View reports only |
| `viewer` | Dashboard only |

---

## Docs

- [`SECURITY.md`](SECURITY.md) – Security controls and responsible use policy
- [`GOOGLE_WORKSPACE_SETUP.md`](GOOGLE_WORKSPACE_SETUP.md) – Authorized Google Workspace email delivery setup

---

## License

For internal organizational use only. Not licensed for commercial redistribution.
