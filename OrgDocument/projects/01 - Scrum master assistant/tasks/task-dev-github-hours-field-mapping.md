# T037 — Map GitHub Number Fields to Time Hours (Parser + Config)

**Task ID**: T037  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Read estimated / remaining / actual hours from GitHub Project number fields and expose them on the parsed `Issue` model. Previously `ResponseParser::parseIssueNode()` hard-coded the three values to `null` even though number-field values were already parsed into `Issue::$customFields`.

### Acceptance Criteria
- [x] Settings `REPO_FIELD_ESTIMATED` (default `Estimate Hours`), `REPO_FIELD_REMAINING` (`Remains Hours`), `REPO_FIELD_ACTUAL` (`Actual Hours`), `REPO_FIELD_ITERATION` (`Iteration`) in `config/settings.php`; matched case-insensitively
- [x] `ResponseParser::parseIssueNode()` populates `estimatedHours/remainingHours/actualHours` from the mapped number fields (float, or `null` when absent/empty)
- [x] Non-numeric or negative values become `null`, never throw
- [x] The iteration is read from the configured field name and exposed as `customFields['iteration']`
- [x] Tests cover: field present, missing/cleared, custom field names (`SyncIntegrationTest`)

### Tasks/Subtasks
- [x] Field-name map with defaults (`ResponseParser::DEFAULT_FIELD_NAMES`) passed through `parseIssueNodes()` / `SyncService`
- [x] Settings + container wiring; `.env.example` documents the variables
- [x] Verified on the real GitHub project in production (hours synced; owner sign-off 2026-10-24)

### Definition of Done
- [x] PHPUnit passes (128 tests)  
- [x] `.env.example` documents new variables  

### Dependencies
- None  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High  

### Progress Updates
- **2026-10-24**: Implemented in `src/GraphQL/ResponseParser.php`, `src/Models/Issue.php`, `config/settings.php`, `config/container.php`.

---
**Status**: Completed (production verified, owner sign-off 2026-10-24)  
**Last Updated**: 2026-10-24
