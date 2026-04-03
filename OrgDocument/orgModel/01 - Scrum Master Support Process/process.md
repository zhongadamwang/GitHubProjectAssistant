# Process Flow — Scrum Master Support Process

**Process**: 01 - Scrum Master Support Process  
**Level**: 0  
**Status**: Active  
**Last Updated**: 2026-04-02  
**Source Requirements**: [R-001], [R-002], [R-004], [R-005], [R-006], [R-007], [R-008]

## Process Overview

This document defines the activity flows for the GitHub-integrated Scrum project management dashboard. The system operates through two primary process paths: automated data synchronization (cron-driven) and interactive dashboard usage (user-driven).

## Activity Diagram

```mermaid
flowchart TD
    START([Start]) --> SYNC_OR_USER{Trigger Type?}

    %% Automated Sync Path
    SYNC_OR_USER -->|Cron 15min| RATE_CHECK[Check GitHub API Rate Limit]
    RATE_CHECK --> RATE_OK{Points Available?}
    RATE_OK -->|No| SKIP_SYNC[Skip Sync — Log Warning]
    SKIP_SYNC --> END_SYNC([End Sync Cycle])

    RATE_OK -->|Yes| GQL_FETCH[Execute GraphQL v4 Query<br/>Cursor Pagination]
    GQL_FETCH --> PARSE[Parse GraphQL Response<br/>→ Project + Issue Arrays]
    PARSE --> DIFF[Compare Fetched vs Local<br/>by github_issue_id]
    DIFF --> UPSERT[Upsert Issues in Transaction<br/>Preserve Local Time Fields]
    UPSERT --> SNAPSHOT[Write JSON Snapshot<br/>data/snapshots/YYYY-MM-DD_HH-mm.json]
    SNAPSHOT --> LOG_SYNC[Log to sync_history<br/>issues_added, updated, points_used]
    LOG_SYNC --> BURNDOWN_SNAP[Capture Daily Burndown Snapshot<br/>UPSERT burndown_daily]
    BURNDOWN_SNAP --> END_SYNC

    %% User Dashboard Path
    SYNC_OR_USER -->|User Access| AUTH{Authenticated?}
    AUTH -->|No| LOGIN[Login Form<br/>Email + Password]
    LOGIN --> VERIFY[Verify Credentials<br/>bcrypt + Session]
    VERIFY --> AUTH_RESULT{Valid?}
    AUTH_RESULT -->|No| LOGIN
    AUTH_RESULT -->|Yes| DASHBOARD

    AUTH -->|Yes| DASHBOARD[Load Sprint Dashboard]
    DASHBOARD --> VIEW_SELECT{User Action?}

    VIEW_SELECT -->|View Burndown| BURNDOWN[Fetch Burndown Data<br/>GET /api/projects/id/burndown]
    BURNDOWN --> CALC_IDEAL[Calculate Ideal Curve<br/>total_estimated ÷ working_days]
    CALC_IDEAL --> CALC_HEALTH[Calculate Sprint Health<br/>On Track / At Risk / Behind]
    CALC_HEALTH --> DISPLAY_BURN[Display Chart.js Line Chart<br/>Ideal=dashed, Actual=solid]
    DISPLAY_BURN --> VIEW_SELECT

    VIEW_SELECT -->|Update Time| TIME_EDIT[Edit Issue Time Fields<br/>Estimated / Remaining / Actual]
    TIME_EDIT --> VALIDATE[Validate Input<br/>Non-negative, 0–9999.99]
    VALIDATE --> AUDIT[Write TimeLog Audit Record<br/>old_value → new_value]
    AUDIT --> UPDATE_ISSUE[Update Issue + Trigger Refresh]
    UPDATE_ISSUE --> VIEW_SELECT

    VIEW_SELECT -->|View Efficiency| EFFICIENCY[Fetch Member Efficiency<br/>GET /api/projects/id/members]
    EFFICIENCY --> CALC_RATIO[Calculate Accuracy Ratio<br/>actual ÷ estimated per member]
    CALC_RATIO --> DISPLAY_EFF[Display Grouped Bar Chart<br/>+ Accuracy Table]
    DISPLAY_EFF --> VIEW_SELECT

    VIEW_SELECT -->|Admin: Trigger Sync| MANUAL_SYNC[POST /api/sync/trigger]
    MANUAL_SYNC --> RATE_CHECK

    VIEW_SELECT -->|Logout| LOGOUT[Destroy Session<br/>Redirect to Login]
    LOGOUT --> LOGIN

    %% Styling
    classDef sync fill:#e8f5e8
    classDef user fill:#e1f5fe
    classDef auth fill:#fff3e0
    classDef analytics fill:#f3e5f5

    class RATE_CHECK,GQL_FETCH,PARSE,DIFF,UPSERT,SNAPSHOT,LOG_SYNC,BURNDOWN_SNAP sync
    class DASHBOARD,VIEW_SELECT,DISPLAY_BURN,DISPLAY_EFF user
    class LOGIN,VERIFY,AUTH,AUTH_RESULT,LOGOUT auth
    class BURNDOWN,CALC_IDEAL,CALC_HEALTH,TIME_EDIT,VALIDATE,AUDIT,UPDATE_ISSUE,EFFICIENCY,CALC_RATIO analytics
```

## Process Description

### Path A: Automated GitHub Synchronization (Cron-Driven)

#### 1. Rate Limit Check
The cron job (every 15 minutes) first checks the GitHub API rate limit. If insufficient points remain (of the 5000/hour budget), the sync cycle is skipped and a warning is logged.

#### 2. GraphQL Data Fetch
Executes a GraphQL v4 query against GitHub Projects v2 using cursor-based pagination. Fetches project metadata, all issues with title, status, assignee, labels, iteration, and timestamps.

#### 3. Response Parsing
The GraphQL response parser transforms the nested JSON response into flat Project and Issue model arrays suitable for database persistence.

#### 4. Diff and Upsert
Compares fetched issues against local database by `github_issue_id`. Inserts new issues and updates changed ones. **Critical invariant**: local time-tracking fields (estimated, remaining, actual) are never overwritten during sync. All DB operations are wrapped in a MySQL transaction.

#### 5. Snapshot and Audit
Writes a JSON snapshot to `data/snapshots/` for historical audit trail [R-003]. Logs the sync event to `sync_history` with counts and GraphQL points consumed.

#### 6. Daily Burndown Capture
After sync, aggregates remaining_time totals per iteration and upserts into the `burndown_daily` table for chart generation.

### Path B: Interactive Dashboard Usage (User-Driven)

#### 1. Authentication
Session-based authentication with bcrypt password verification. Session ID regenerated on login to prevent fixation attacks. Two roles: admin (full access) and member (read + time tracking).

#### 2. Sprint Dashboard
Main view displays burndown line chart (ideal vs actual curves). The ideal curve is calculated as linear decrease from total estimated hours. Sprint health indicator: On Track (actual ≤ ideal), At Risk (<20% over), Behind (>20% over). Auto-refreshes every 30 seconds.

#### 3. Time Tracking
Inline editing of estimated, remaining, and actual time per issue. Input validation ensures non-negative values in range 0–9999.99. Each change creates a TimeLog audit record (old_value, new_value, changed_by). Update wrapped in database transaction.

#### 4. Efficiency Analysis
Per-member aggregation of estimated vs actual time across completed issues. Accuracy ratio: actual ÷ estimated (1.0 = perfect). Displayed as grouped bar chart with color-coding (green = accurate, red = underestimated, blue = overestimated).

#### 5. Admin Functions
Admin users can trigger manual sync and manage dashboard users (create, list). Non-admin users receive 403 on admin endpoints.

## Boundary Rules Applied

- **VR-1** (Single External Interface): External actors (Scrum Master, Cron) each enter through a single boundary participant (Dashboard UI, GitHub API Client)
- **VR-2** (Boundary-First Reception): First message from each external actor targets a boundary-type participant

## Error Handling

- **GitHub API Failure (502/503)**: Retry with exponential backoff (max 3 attempts), then log failure to sync_history
- **Rate Limit Exceeded**: Skip sync cycle, log warning, retry on next cron cycle
- **Invalid Time Input**: Return 422 with validation errors
- **Auth Failure**: Return 401, no user enumeration in error messages
- **DB Transaction Failure**: Rollback and return 503

## Performance Requirements

- API response time < 200ms for all dashboard endpoints
- Sync cycle completes within 60 seconds
- Frontend bundle < 500KB gzipped
- Burndown queries use pre-calculated `burndown_daily` table (no heavy aggregation at request time)

---
<!-- Last Updated: 2026-04-02 -->