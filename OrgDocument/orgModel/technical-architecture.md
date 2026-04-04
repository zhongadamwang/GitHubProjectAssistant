# Technical Architecture — Organization Level

**Organization**: GitHub Project Assistant  
**Version**: 1.0.0  
**Created**: 2026-04-03  
**Status**: Draft  

> This document captures organization-wide architectural standards, hosting platform decisions, shared infrastructure, and cross-cutting concerns. Solution-specific architecture is documented in each solution's `technical-architecture.md`.

---

## Hosting Platform

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| Runtime | cPanel Shared Hosting | Low cost, org-standard for all web applications |
| Language | PHP 8.2+ | Universal cPanel support |
| Database | MySQL 5.7+ / 8.0 via PDO | Included with cPanel; sufficient for internal tooling |
| Static Assets | Compiled and served from cPanel document root | No CDN required at current scale |
| Deployment | GitHub Actions → SFTP to cPanel | Automated CI/CD, no server-side agents needed |
| Cron Jobs | cPanel Scheduled Tasks | Available on all shared hosting plans |

---

## Source Code Structure

All application source code lives under `OrgDocument/Solutions/`. Each solution has its own isolated folder.

```
OrgDocument/
├── orgModel/
│   ├── solutions.md                      ← Solutions registry (this level)
│   ├── technical-architecture.md         ← Org-level architecture (this file)
│   └── 01 - Scrum Master Support Process/
├── Solutions/
│   └── <SolutionName>/                   ← One folder per application
│       ├── technical-architecture.md     ← Solution-level architecture
│       ├── composer.json / package.json  ← Dependencies
│       ├── src/                          ← PHP backend source
│       ├── public/                       ← Web root
│       └── ...
└── projects/
    └── <project>/                        ← Planning, analysis, tasks
```

---

## Organization-Wide Standards

### Security
- All passwords hashed with bcrypt (cost ≥ 12) via `password_hash()` / `password_verify()`
- No secrets committed to version control; always use `.env` files with `.env.example` templates
- PDO prepared statements mandatory — no SQL string interpolation
- Session cookies must have `httpOnly` + `secure` + `SameSite=Strict` flags in production
- GitHub Personal Access Tokens stored in `.env` and rotated annually

### API Design
- REST endpoints prefixed with `/api/`
- Authentication enforced via middleware on all non-public routes
- Responses use consistent JSON envelope: `{ "data": ..., "error": null }`

### Dependency Management
- PHP projects: Composer with `composer.lock` committed
- Frontend projects: npm/pnpm with lock file committed
- `vendor/` and `node_modules/` excluded from version control

### Deployment
- All solutions deployed via GitHub Actions workflow in `.github/workflows/`
- Pre-deploy: run migrations
- Post-deploy: smoke test the health endpoint

---

## Solutions Inventory

See [solutions.md](solutions.md) for the live registry of all organization solutions.
