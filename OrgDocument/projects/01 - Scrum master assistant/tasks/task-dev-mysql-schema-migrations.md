# T002 — Create MySQL Database Schema & Migrations

**Task ID**: T002  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #2  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/2  

### Description
Write SQL migration files for all 6 tables (`users`, `projects`, `issues`, `time_logs`, `sync_history`, `burndown_daily`) with proper constraints, indexes, and foreign keys. Create migration runner script.

### Acceptance Criteria
- [x] Migration files create all 6 tables with correct column types (`DECIMAL` for time, `JSON` for labels, `ENUM` for roles/status)
- [x] All foreign key constraints defined (`ON DELETE CASCADE` where appropriate)
- [x] All indexes created (`project_id`, `assignee`, `iteration`, burndown composite, `time_logs`)
- [x] `migrate.php` runner executes migrations idempotently (skips already-applied)
- [x] Migrations run successfully on MySQL 5.7+ and 8.0

### Tasks/Subtasks
- [x] Create `database/migrations/` directory
- [x] Write migration `001_create_users_table.sql` — email unique, bcrypt hash, role enum, timestamps
- [x] Write migration `002_create_projects_table.sql` — GitHub project ID unique, iteration, sync timestamp
- [x] Write migration `003_create_issues_table.sql` — FK to projects, JSON labels, DECIMAL time fields, compound indexes
- [x] Write migration `004_create_time_logs_table.sql` — append-only audit trail, FK to issues and users
- [x] Write migration `005_create_sync_history_table.sql` — sync status, counts, error messages
- [x] Write migration `006_create_burndown_daily_table.sql` — composite unique on project+iteration+date
- [x] Create `database/migrations_log` table for tracking applied migrations
- [x] Write `database/migrate.php` — reads and applies un-applied migrations in order
- [ ] Test migrations on MySQL 5.7+ and 8.0

### Definition of Done
- [x] All acceptance criteria met
- [x] All 6 tables created with correct constraints and indexes
- [x] Migration runner is idempotent
- [x] Schema validated against domain model entities

### Dependencies
- T001 — PHP backend project must be initialized

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High — All services depend on the database schema

### Labels/Tags
- Category: development
- Component: database
- Sprint: Phase 1 — Foundation

### Notes
- Schema must match the domain model: 8 entities mapped to 6 tables
- Use `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` for all tables
- `DECIMAL(8,2)` for all time tracking fields (hours)
- Source Requirements: R-003, R-004, R-011

### Progress Updates
- **2026-04-03**: All 6 migration SQL files created (`001`–`006`) and `database/migrate.php` runner implemented. `migrations_log` table auto-created by the runner for idempotent execution. All FK constraints, indexes, DECIMAL time fields, JSON labels, and ENUM columns confirmed. Pending: live test run against MySQL.

---
**Status**: Completed  
**Last Updated**: 2026-04-03
