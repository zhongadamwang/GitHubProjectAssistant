# T003 — Create Database Seed Script

**Task ID**: T003  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #3  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/3  

### Description
Create seed script that initializes the first admin user with bcrypt-hashed password. Ensure idempotent execution (skip if admin exists).

### Acceptance Criteria
- [x] `seed.php` creates admin user with `password_hash()` (bcrypt, cost 12)
- [x] Seed is idempotent — does not duplicate if run multiple times
- [x] Admin email and password configurable via `.env` or CLI arguments
- [x] Outputs confirmation message on success

### Tasks/Subtasks
- [x] Create `database/seed.php` entry point
- [x] Load `.env` config for default admin credentials
- [x] Connect to MySQL using PDO from container config
- [x] Check if admin user already exists (by email)
- [x] Insert admin user with hashed password if not exists
- [x] Output success/skip message to console
- [x] Support CLI argument override: `php seed.php --email=admin@example.com --password=secret`

### Definition of Done
- [x] All acceptance criteria met
- [x] Seed script runs idempotently (safe to re-run)
- [x] Admin user can be verified in database
- [x] Password is properly bcrypt-hashed (never stored in plaintext)

### Dependencies
- T002 — Database schema must exist (`users` table)

### Effort Estimate
**Time Estimate**: 0.5 days  

### Priority
High — Required before authentication can be tested

### Labels/Tags
- Category: development
- Component: database
- Sprint: Phase 1 — Foundation

### Notes
- Default admin credentials should be in `.env` (not hardcoded)
- bcrypt cost factor: 12 (per ADR-7)
- Source Requirements: ADR-7

### Progress Updates
- **2026-04-03**: Implemented `database/seed.php`. Idempotent admin seeder with bcrypt cost 12, `.env` + CLI arg support, email validation, and skip-on-duplicate logic.

---
**Status**: Completed  
**Last Updated**: 2026-04-03
