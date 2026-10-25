# T040 — Hours Sync: Integration Tests, Docs and Production Backfill

**Task ID**: T040  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
End-to-end verification of the hours sync, documentation updates and a safe rollout on production.

### Acceptance Criteria
- [x] Integration tests: stubbed GitHub response with number fields → stored hours, GitHub-wins overwrite, audit, iteration config (`SyncIntegrationTest`); issues/iterations endpoints (`EndToEndTest`)
- [x] `DEPLOYMENT.md`, `README.md`, `.env.example` document the `REPO_FIELD_*` variables (defaults already match the owner's field names, so the server `.env` needs no change)
- [x] `artifacts/Analysis/requirements.md` R-002/R-004 and both technical-architecture documents updated (hours sourced from GitHub, dashboard read-only, endpoint list)
- [ ] Production runbook executed: deploy → migrations 009/010 → real "Sync Now" → `php cron/rebuild-burndown.php` → verify Dashboard, Issues, Members against GitHub
- [x] `tests/e2e/FINDINGS.md` has the Phase 7 local verification (production entries open)

### Tasks/Subtasks
- [x] Extend fixtures/tests (built in-memory in `SyncIntegrationTest`)
- [x] Update docs and requirement text
- [ ] Production verification (needs the real PAT on the server — owner)

### Definition of Done
- [ ] Owner confirms hours and burndown on the live site match GitHub  

### Dependencies
- T038, T039  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High  

### Labels/Tags
- Category: testing, documentation  

---
**Status**: In Progress — code, tests and docs done; production backfill/verification pending  
**Last Updated**: 2026-10-24
