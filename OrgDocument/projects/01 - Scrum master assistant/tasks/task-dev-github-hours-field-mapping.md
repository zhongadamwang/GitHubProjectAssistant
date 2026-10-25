# T037 — Map GitHub Number Fields to Time Hours (Parser + Config)

**Task ID**: T037  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Read estimated / remaining / actual hours from configurable GitHub Project number fields and expose them on the parsed `Issue` model. Today `ResponseParser::parseIssueNode()` hard-codes the three values to `null` even though number-field values are already parsed into `Issue::$customFields`.

### Acceptance Criteria
- [ ] New settings `REPO_FIELD_ESTIMATED` (default `Estimate Hours`), `REPO_FIELD_REMAINING` (default `Remains Hours`), `REPO_FIELD_ACTUAL` (default `Actual Hours`), `REPO_FIELD_ITERATION` (default `Iteration`) in `config/settings.php`; matched case-insensitively against GitHub field names (parser keys are lower-cased, and the names contain spaces)
- [ ] `ResponseParser::parseIssueNode()` populates `estimatedHours/remainingHours/actualHours` from the mapped number fields (float, or `null` when the field is absent/empty)
- [ ] Non-numeric or negative values are treated as `null` and logged, never throw
- [ ] Iteration is read from the configured field name (no longer the literal key `iteration`)
- [ ] Unit tests cover: field present, missing, zero, decimal, wrong type, different casing

### Tasks/Subtasks
- [ ] Verify the three fields are NUMBER type on the real project (first real sync); log a warning if a mapped field is missing or not numeric
- [ ] Add settings + inject into `ResponseParser` (static helper → accept a field map)
- [ ] Update fixture `tests/fixtures/github-project-response.json` with number fields
- [ ] Parser unit tests (`tests/Integration/Phase3` or new `Phase7` suite)

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit passes  
- [ ] `.env.example` documents new variables  

### Dependencies
- None (field names confirmed by the owner 2026-10-24)  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High — blocks accurate burndown  

### Labels/Tags
- Category: backend, integration  
- Component: GraphQL parser, config  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
