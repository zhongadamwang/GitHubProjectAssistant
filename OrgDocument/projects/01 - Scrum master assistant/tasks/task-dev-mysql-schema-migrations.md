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
- [ ] Migration files create all 6 tables with correct column types (`DECIMAL` for time, `JSON` for labels, `ENUM` for roles/status)
- [ ] All foreign key constraints defined (`ON DELETE CASCADE` where appropriate)
- [ ] All indexes created (`project_id`, `assignee`, `iteration`, burndown composite, `time_logs`)
- [ ] `migrate.php` runner executes migrations idempotently (skips already-applied)
- [ ] Migrations run successfully on MySQL 5.7+ and 8.0

### Tasks/Subtasks
- [ ] Create `database/migrations/` directory
- [ ] Write migration `001_create_users_table.sql` — email unique, bcrypt hash, role enum, timestamps
- [ ] Write migration `002_create_projects_table.sql` — GitHub project ID unique, iteration, sync timestamp
- [ ] Write migration `003_create_issues_table.sql` — FK to projects, JSON labels, DECIMAL time fields, compound indexes
- [ ] Write migration `004_create_time_logs_table.sql` — append-only audit trail, FK to issues and users
- [ ] Write migration `005_create_sync_history_table.sql` — sync status, counts, error messages
- [ ] Write migration `006_create_burndown_daily_table.sql` — composite unique on project+iteration+date
- [ ] Create `database/migrations_log` table for tracking applied migrations
- [ ] Write `database/migrate.php` — reads and applies un-applied migrations in order
- [ ] Test migrations on MySQL 5.7+ and 8.0

### Definition of Done
- [ ] All acceptance criteria met
- [ ] All 6 tables created with correct constraints and indexes
- [ ] Migration runner is idempotent
- [ ] Schema validated against domain model entities

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
*No updates yet*

---
**Status**: Not Started  
**Last Updated**: 2026-04-02
