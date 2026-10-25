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
- [ ] Integration test: stubbed GitHub response with number fields → issues, member efficiency and burndown API return the expected hours
- [ ] `DEPLOYMENT.md`, `README.md`, `.env.example`, `.env.production` document `REPO_FIELD_*` variables; server `.env` updated with the real field names
- [ ] `artifacts/Analysis/requirements.md` R-002/R-004 reworded (hours sourced from GitHub, dashboard read-only); technical-architecture.md updated
- [ ] Production runbook executed: deploy → migration 009 → real "Sync Now" → rebuild burndown → verify Dashboard, Issues, Members
- [ ] `tests/e2e/FINDINGS.md` updated with the production verification

### Tasks/Subtasks
- [ ] Extend fixtures and `SyncIntegrationTest`/`EndToEndTest`
- [ ] Update docs and requirement text
- [ ] Production verification (needs real PAT on server)

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
**Status**: Not Started  
**Last Updated**: 2026-10-24
