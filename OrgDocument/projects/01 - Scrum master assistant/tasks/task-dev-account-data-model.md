# T046 — Data Model: GitHub Accounts, Tracked Projects, Admin Audit Log

**Task ID**: T046  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md), [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
Add the schema and repositories for admin-managed GitHub accounts, tracked projects and an audit log. Pure data layer — no HTTP or UI.

### Acceptance Criteria
- [x] Migration `011`: `github_accounts` (label, owner_login, owner_type, graphql_url, token_ciphertext, token_nonce, token_hint, status, status_message, last_verified_at, token_expires_at, created_by, timestamps; UNIQUE on owner_login + graphql_url) and `admin_audit_log` (actor_user_id, action, entity, entity_id, detail JSON, created_at)
- [x] Migration `012`: `projects` gains `account_id` (nullable FK, ON DELETE RESTRICT), `is_tracked` (default 1 for existing rows), `archived_at`, `field_mapping` JSON NULL, `last_sync_status`, `last_sync_at`
- [x] Migrations are idempotent / safe on a populated database and verified on MySQL 5.7/8.0 syntax
- [x] `GitHubAccountRepository` (CRUD, status update, `findAll`, `findById`) and `AuditLogRepository` (append, list) — token columns only handled as opaque ciphertext here
- [x] `ProjectRepository` extended: list tracked/archived, set tracking, set mapping, record last sync status
- [x] PHPUnit tests for repositories (including FK restrict and defaults for existing projects)

### Tasks/Subtasks
- [x] Migrations 011, 012 and `migrate.php` run on a copy of production-like data
- [x] Repositories + tests
- [x] Update `technical-architecture.md` data model section

### Definition of Done
- [x] All acceptance criteria met; PHPUnit passes; existing data unchanged after migration  

### Dependencies
- None  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Progress Updates
- **2026-10-25**: Migrations 011/012, repositories and tests complete; upgrade rehearsal on a Phase 7-shaped database passed (see T053/T055).

---
**Status**: Completed  
**Last Updated**: 2026-10-25
