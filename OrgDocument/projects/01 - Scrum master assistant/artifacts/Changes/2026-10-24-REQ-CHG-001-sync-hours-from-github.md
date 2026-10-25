# Sync Time-Tracking Hours from GitHub Project Fields

## Change ID
**Change ID**: REQ-CHG-001  
**Date Created**: 2026-10-24  
**Status**: Verified — owner sign-off 2026-10-24  
**Priority**: High  
**Requested By**: Project owner (live-site review of https://scrum.softercolor.com/, feedback #3)  
**Change Type**: REQ-CHG (modifies R-002, R-004, R-005, R-006)

## Summary
Estimated, remaining and actual hours must be synced from GitHub Project number fields; today they stay 0 and break the burndown chart.

## Change Details
During the live-site review the owner observed that issues are synced from GitHub, but **Estimate Hours, Remaining Hours and Actual Hours are not synced**, which makes the burndown chart (ideal and actual curves) meaningless.

### Current State
- Requirement R-004 / the original brief define the three time attributes as **local extensions**; R-002 defines GitHub sync as read-only.
- Implemented accordingly: `ResponseParser::parseIssueNode()` hard-codes `estimatedHours/remainingHours/actualHours = null`, and `IssueRepository::upsertFromGitHub()` deliberately excludes `estimated_time`, `remaining_time`, `actual_time` from the upsert. Values exist only if someone types them into the dashboard.
- GitHub number fields *are* fetched and parsed into `Issue::$customFields` (lower-cased field name) but never mapped to the time columns.
- `SyncService` re-imports an issue only when the **Issue** `updatedAt` is newer than the stored value. Editing a Projects-v2 *field value* does not bump the Issue's `updatedAt`, so hour changes in GitHub would not be detected even if mapped.
- `BurndownService::getBurndown()` uses the first snapshot's `total_estimated` as the ideal-line baseline; with hours at 0 the ideal line is flat zero. `captureDaily()` also sums `remaining_time` of closed issues.
- `time_logs.changed_by` is `NOT NULL` with an FK to `users`, so system (sync) changes cannot currently be audited.

### Proposed State
- The three hour values are read from GitHub Project **number** fields, configurable via env with the owner-confirmed names as defaults: `REPO_FIELD_ESTIMATED="Estimate Hours"`, `REPO_FIELD_REMAINING="Remains Hours"`, `REPO_FIELD_ACTUAL="Actual Hours"`; the iteration field is `REPO_FIELD_ITERATION="Iteration"`.
- Sync persists these values; change detection also considers the project item's own `updatedAt` / the field values so hour edits in GitHub are picked up.
- **Precedence (owner decision): GitHub always wins.** The sync overwrites the stored hours on every run; an empty/cleared GitHub field becomes `0`. **The dashboard is read-only for hours**: the inline time editor in the Issues view is removed (values shown as text), and the `PUT /api/issues/{id}/time` endpoint is retired (proposal: remove the route; keep `time_logs` for sync audit — confirm during T038).
- Sync-originated changes are audited (system actor) in `time_logs`.
- Burndown remains accurate: baseline = largest/first non-zero `total_estimated`; **closed issues contribute 0 remaining (owner decision)**; existing zero-valued snapshots can be rebuilt.

### Rationale
The burndown (R-005/R-006) and member-efficiency (R-008) views are the product's core value; without hours from the system of record they show nothing. Teams already maintain these numbers in GitHub Projects, so re-typing them locally is error-prone duplication.

## Impact Analysis

| File / component | Impact | Update required |
|---|---|---|
| `src/GraphQL/ResponseParser.php`, `src/Models/Issue.php` | Map number fields to hours | Yes |
| `src/Repositories/IssueRepository.php` | Persist hours with precedence rule | Yes |
| `src/Services/SyncService.php` | Change detection via item `updatedAt` / field values; audit | Yes |
| `src/Services/BurndownService.php` | Baseline + closed-issue handling | Yes |
| `config/settings.php`, `.env.example`, `.env.production`, `DEPLOYMENT.md`, `README.md` | New `REPO_FIELD_*` settings | Yes |
| `database/migrations/009_*.sql` | Allow system actor in `time_logs` (nullable `changed_by` or `source` column) | Yes |
| `tests/fixtures/github-project-response.json`, Sync/Burndown tests | Number-field fixtures and assertions | Yes |
| `artifacts/Analysis/requirements.md` (R-002, R-004) | Reword "local extension / read-only" | Yes |
| Existing production data | Zero-hour snapshots in `burndown_daily` | Backfill |

**Affected tasks**: T010 (sync logic), T014 (burndown snapshot), T016 (time tracking — editor/endpoint retired), T009 (parser), T023 (Issues view — editor becomes read-only).  
**Risk level**: High — changes a documented design rule ("local time fields are NEVER overwritten") and the burndown data basis.  
**Estimated effort**: ~3.5 days (T037–T040).

## Owner Decisions (2026-10-24)
1. **Field names**: `Estimate Hours`, `Remains Hours`, `Actual Hours`, iteration field `Iteration`. (Field data type assumed NUMBER — verify on first real sync.)
2. **Precedence**: GitHub wins; the dashboard is read-only for hours.
3. **Closed issues count as 0 remaining** in the burndown.
4. Iteration field name: `Iteration` (see 1).

## Implementation Plan
1. T037 — Field mapping + parser/config.
2. T038 — Persist hours, change detection, audit.
3. T039 — Burndown accuracy and snapshot rebuild.
4. T040 — Tests, docs, production backfill runbook.

## Acceptance Criteria
- After a sync, issues show the hours set in GitHub; changing an hour value in GitHub (including clearing it → 0) is reflected after the next sync.
- The Issues view shows hours read-only; no dashboard path can change them.
- Burndown chart shows a non-zero ideal curve and an actual curve consistent with remaining hours (closed issues = 0 remaining).
- Member efficiency chart reflects synced estimated/actual hours for closed issues.
- PHPUnit covers mapping, GitHub-wins overwrite, change detection, and burndown baseline.

## Approval Status
- [x] Owner confirms open questions 1–4 (2026-10-24)
- [x] Approved for implementation

## Related
- Tasks: [T037](../../tasks/task-dev-github-hours-field-mapping.md), [T038](../../tasks/task-dev-sync-hours-persistence.md), [T039](../../tasks/task-dev-burndown-accuracy.md), [T040](../../tasks/task-dev-hours-sync-tests-docs.md)
