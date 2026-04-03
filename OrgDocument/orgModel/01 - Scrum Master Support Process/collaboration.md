# Collaboration Diagram — Scrum Master Support Process

**Process**: 01 - Scrum Master Support Process  
**Level**: 0  
**Status**: Active  
**Last Updated**: 2026-04-02  
**Source Requirements**: [R-001], [R-002], [R-003], [R-004], [R-005], [R-006], [R-007], [R-008], [R-009], [R-010], [R-011], [R-012]

## Collaboration Overview

This document defines the participants, their stereotypes, boundary groupings, and interaction sequences for the Scrum Master Support Process. The process supports GitHub-integrated Scrum project management through data synchronization, time tracking, burndown analysis, and efficiency reporting.

## System Boundary View *(Diagram CB-001)*

**Source Requirements**: [R-001], [R-005], [R-006], [R-007], [R-008], [R-009]

```mermaid
sequenceDiagram
    participant SM@{ "type": "actor", "label": "Scrum Master" }

    box Integration Boundary
        participant GHClient@{ "type": "boundary", "label": "GitHub API Client" }
        participant GHSync@{ "type": "control", "label": "GitHub Sync Service" }
        participant SyncHist@{ "type": "entity", "label": "Sync History" }
    end

    box Analytics Boundary
        participant BurnSvc@{ "type": "control", "label": "Burndown Service" }
        participant EffSvc@{ "type": "control", "label": "Efficiency Service" }
        participant TimeSvc@{ "type": "control", "label": "Time Tracking Service" }
    end

    box Presentation Boundary
        participant DashUI@{ "type": "boundary", "label": "Dashboard UI" }
    end

    box Data Boundary
        participant DB@{ "type": "entity", "label": "MySQL Database" }
    end

    SM->>DashUI: Access sprint dashboard
    DashUI->>BurnSvc: Request burndown data
    BurnSvc->>DB: Query burndown_daily + issues
    DB-->>BurnSvc: Return aggregated data
    BurnSvc-->>DashUI: Return ideal vs actual curves
    DashUI-->>SM: Display burndown chart + sprint health

    SM->>DashUI: Update issue time tracking
    DashUI->>TimeSvc: PUT /api/issues/{id}/time
    TimeSvc->>DB: Read current values, write time_logs audit
    TimeSvc->>DB: Update issue time fields
    DB-->>TimeSvc: Confirm update
    TimeSvc-->>DashUI: Return updated issue
    DashUI-->>SM: Show updated progress

    SM->>DashUI: View member efficiency
    DashUI->>EffSvc: Request efficiency analysis
    EffSvc->>DB: Aggregate estimated vs actual per member
    DB-->>EffSvc: Return member time data
    EffSvc-->>DashUI: Return accuracy ratios + trends
    DashUI-->>SM: Display efficiency charts

    Note over GHSync,GHClient: Cron-triggered every 15 minutes [R-001, R-002]
    GHSync->>GHClient: Execute GraphQL v4 query
    GHClient-->>GHSync: Return project + issues data
    GHSync->>DB: Upsert issues (preserve local time fields)
    GHSync->>SyncHist: Log sync event + create JSON snapshot
    GHSync->>BurnSvc: Trigger daily burndown capture

    Note over DashUI: Auto-refresh every 30 seconds [R-006]
    Note over DB: MySQL 5.7+ on cPanel shared hosting [R-009, R-011]
```

## Domain Class Model *(Diagram CB-002)*

**Source Requirements**: [R-001], [R-004], [R-007], [R-008]  
**Domain Source**: artifacts/Analysis/domain-concepts.json

```mermaid
classDiagram
    class GitHubProject:::core {
        +project_id: String
        +name: String
        +sync_timestamp: DateTime
        +iteration: String
        +syncWithGitHub()
        +generateBurndown()
    }

    class EnhancedIssue:::entity {
        +github_issue_id: String
        +title: String
        +estimated_time: Number
        +remaining_time: Number
        +actual_time: Number
        +assignee: String
        +updateTimeTracking()
        +calculateProgress()
    }

    class BurndownChart:::analytics {
        +sprint_id: String
        +ideal_curve: Array
        +actual_curve: Array
        +generated_at: DateTime
        +generateIdealCurve()
        +updateActualCurve()
    }

    class TeamMember:::entity {
        +github_username: String
        +display_name: String
        +estimation_accuracy_score: Number
        +calculateEstimationAccuracy()
        +getEfficiencyReport()
    }

    class Dashboard:::ui {
        +refresh_rate: Number
        +active_sprint: String
        +refreshCharts()
        +exportReport()
    }

    class GitHubAPIClient:::integration {
        +api_token: String
        +rate_limit_remaining: Number
        +last_request_time: DateTime
        +fetchProjects()
        +fetchIssues()
        +checkRateLimit()
    }

    class SyncHistory:::audit {
        +sync_id: String
        +timestamp: DateTime
        +data_snapshot: JSON
        +changes_detected: Array
        +createSnapshot()
        +compareSnapshots()
    }

    GitHubProject "1" *-- "*" EnhancedIssue : contains
    EnhancedIssue "*" --> "1" TeamMember : assigned_to
    GitHubProject "1" *-- "*" BurndownChart : generates
    Dashboard "*" --> "*" BurndownChart : displays
    GitHubAPIClient "1" --> "*" SyncHistory : creates
    GitHubAPIClient "1" --> "1" GitHubProject : synchronizes

    %% Styling Definitions
    classDef core fill:#e1f5fe
    classDef entity fill:#f3e5f5
    classDef analytics fill:#fff3e0
    classDef ui fill:#e8f5e8
    classDef integration fill:#ffebee
    classDef audit fill:#fafafa
```

## GitHub Data Synchronization Flow *(Diagram CB-003)*

**Source Requirements**: [R-001], [R-002], [R-003]

```mermaid
sequenceDiagram
    participant Cron@{ "type": "actor", "label": "cPanel Cron (15min)" }

    box Integration Boundary
        participant GHClient@{ "type": "boundary", "label": "GitHub API Client" }
        participant GHSync@{ "type": "control", "label": "GitHub Sync Service" }
        participant Parser@{ "type": "control", "label": "GraphQL Response Parser" }
    end

    box Data Boundary
        participant DB@{ "type": "entity", "label": "MySQL Database" }
        participant Snap@{ "type": "entity", "label": "JSON Snapshots" }
        participant SyncLog@{ "type": "entity", "label": "Sync History" }
    end

    Cron->>GHClient: Trigger sync (cron/sync.php)
    GHClient->>GHClient: Check rate limit (5000 pts/hr)
    GHClient->>GHClient: Execute GraphQL v4 query with cursor pagination
    GHClient-->>Parser: Return raw GraphQL JSON response

    Parser->>Parser: Transform nested response to flat models
    Parser-->>GHSync: Return Project + Issue arrays

    GHSync->>DB: Compare fetched issues vs local (by github_issue_id)
    GHSync->>DB: INSERT new issues / UPDATE changed issues
    Note over GHSync,DB: Preserves local time fields (estimated, remaining, actual)
    GHSync->>DB: Wrap in MySQL transaction — rollback on failure

    GHSync->>Snap: Write data/snapshots/YYYY-MM-DD_HH-mm.json
    GHSync->>SyncLog: Log (issues_added, issues_updated, graphql_points_used, status)
    SyncLog-->>GHSync: Return sync operation ID

    Note over GHClient: Retries on 502/503 with exponential backoff (max 3)
    Note over Snap: Historical audit trail for data accountability [R-003]
```

## Sprint Dashboard Access Flow *(Diagram CB-004)*

**Source Requirements**: [R-005], [R-006], [R-007]

```mermaid
sequenceDiagram
    participant SM@{ "type": "actor", "label": "Scrum Master" }

    box Presentation Boundary
        participant DashUI@{ "type": "boundary", "label": "Dashboard UI (Vue 3)" }
    end

    box Analytics Boundary
        participant BurnSvc@{ "type": "control", "label": "Burndown Service" }
    end

    box Data Boundary
        participant DB@{ "type": "entity", "label": "MySQL Database" }
    end

    SM->>DashUI: Access sprint dashboard
    DashUI->>BurnSvc: GET /api/projects/{id}/burndown?iteration=X
    BurnSvc->>DB: Query burndown_daily table for iteration
    DB-->>BurnSvc: Return daily (date, remaining, estimated, actual) rows
    BurnSvc->>BurnSvc: Calculate ideal curve (total_estimated ÷ working_days)
    BurnSvc-->>DashUI: Return {date, ideal, actual}[] data points

    DashUI->>DashUI: Render Chart.js line chart (ideal=dashed blue, actual=solid red)
    DashUI->>DashUI: Calculate sprint health indicator
    DashUI-->>SM: Display burndown chart + health badge

    Note over DashUI: Auto-refresh every 30 seconds [R-006]
    Note over BurnSvc: Health: On Track (actual ≤ ideal), At Risk (<20% over), Behind (>20% over)
```

## Time Tracking with Audit Flow *(Diagram CB-005)*

**Source Requirements**: [R-004], [R-006]

```mermaid
sequenceDiagram
    participant TM@{ "type": "actor", "label": "Team Member" }

    box Presentation Boundary
        participant DashUI@{ "type": "boundary", "label": "Dashboard UI (Vue 3)" }
    end

    box Analytics Boundary
        participant TimeSvc@{ "type": "control", "label": "Time Tracking Service" }
    end

    box Data Boundary
        participant Issues@{ "type": "entity", "label": "Issues Table" }
        participant TimeLog@{ "type": "entity", "label": "Time Logs (Audit)" }
    end

    TM->>DashUI: Edit time fields (estimated, remaining, actual)
    DashUI->>TimeSvc: PUT /api/issues/{id}/time {estimated, remaining, actual}
    TimeSvc->>TimeSvc: Validate (non-negative, range 0–9999.99)
    TimeSvc->>Issues: Read current values (SELECT FOR UPDATE)
    TimeSvc->>TimeLog: INSERT audit record (old_value, new_value, changed_by, changed_at)
    TimeSvc->>Issues: UPDATE time fields + updated_at
    Note over TimeSvc,Issues: Wrapped in MySQL transaction
    TimeSvc-->>DashUI: Return updated issue data
    DashUI-->>TM: Show success feedback + updated progress
```

## Member Efficiency Analysis Flow *(Diagram CB-006)*

**Source Requirements**: [R-008]

```mermaid
sequenceDiagram
    participant SM@{ "type": "actor", "label": "Scrum Master" }

    box Presentation Boundary
        participant DashUI@{ "type": "boundary", "label": "Dashboard UI (Vue 3)" }
    end

    box Analytics Boundary
        participant EffSvc@{ "type": "control", "label": "Efficiency Service" }
    end

    box Data Boundary
        participant DB@{ "type": "entity", "label": "MySQL Database" }
    end

    SM->>DashUI: Navigate to Members view
    DashUI->>EffSvc: GET /api/projects/{id}/members?iteration=X
    EffSvc->>DB: Aggregate per assignee (SUM estimated, SUM actual, COUNT completed)
    DB-->>EffSvc: Return member aggregation rows
    EffSvc->>EffSvc: Calculate accuracy ratio (actual / estimated)
    EffSvc-->>DashUI: Return [{member, estimated, actual, ratio, issues_count}]

    DashUI->>DashUI: Render Chart.js grouped bar chart (blue=estimated, orange=actual)
    DashUI->>DashUI: Color-code ratios (green=accurate, blue=over, red=under)
    DashUI-->>SM: Display efficiency charts + accuracy table

    Note over EffSvc: Ratio 1.0 = perfect, >1.0 = underestimated, <1.0 = overestimated
```

## Data Flow Architecture *(Diagram CB-007)*

**Source Requirements**: [R-002], [R-003], [R-009], [R-011]

```mermaid
flowchart TD
    subgraph "External Boundary"
        GH[GitHub API<br/>GraphQL v4]
        SM[Scrum Master / Team<br/>Browser Client]
    end

    subgraph "Integration Boundary"
        API[GitHub API Client<br/>GraphQL + Rate Limiting]
        HIST[Sync History<br/>JSON Snapshots + DB Log]
    end

    subgraph "Processing Boundary"
        AUTH[Auth Service<br/>Session + bcrypt]
        ENH[Time Tracking Service<br/>Audit Trail]
        BURN[Burndown Service<br/>Ideal vs Actual Curves]
        EFF[Efficiency Service<br/>Estimation Accuracy]
    end

    subgraph "Storage Boundary"
        DB[(MySQL 5.7+<br/>6 Tables)]
    end

    subgraph "Presentation Boundary"
        DASH[Vue 3 Dashboard<br/>Chart.js Visualizations]
    end

    GH -->|Cron 15min| API
    API --> HIST
    API --> ENH

    SM -->|HTTPS| DASH
    DASH -->|Slim 4 API| AUTH
    AUTH --> ENH
    AUTH --> BURN
    AUTH --> EFF

    ENH --> DB
    HIST --> DB
    BURN --> DB
    EFF --> DB

    DB --> BURN
    DB --> EFF

    BURN --> DASH
    EFF --> DASH

    classDef external fill:#ffebee
    classDef integration fill:#e8f5e8
    classDef processing fill:#fff3e0
    classDef storage fill:#f3e5f5
    classDef presentation fill:#e1f5fe

    class GH,SM external
    class API,HIST integration
    class AUTH,ENH,BURN,EFF processing
    class DB storage
    class DASH presentation
```

## Deployment Architecture *(Diagram CB-008)*

**Source Requirements**: [R-009], [R-010], [R-011]

```mermaid
flowchart LR
    subgraph "Development Boundary"
        DEV[Developer<br/>Code Changes]
        REPO[GitHub Repository<br/>Source Code]
    end

    subgraph "CI/CD Boundary"
        GHA[GitHub Actions<br/>Build & Test]
        BUILD[Build Artifacts<br/>Vue Dist + PHP]
    end

    subgraph "Shared Hosting Boundary"
        HOST[cPanel Server<br/>PHP 8.2 + MySQL]
        APP[Slim 4 API<br/>+ Vue SPA]
        CRON[Cron Job<br/>Every 15 min]
        FS[(MySQL DB<br/>+ JSON Snapshots)]
    end

    subgraph "External Integration"
        GITHUB[GitHub GraphQL v4<br/>Data Source]
    end

    DEV --> REPO
    REPO -->|Push to main| GHA
    GHA --> BUILD
    BUILD -->|SFTP Deploy| HOST
    HOST --> APP
    APP --> FS
    CRON -->|sync.php| APP
    APP <-->|Bearer PAT| GITHUB

    classDef dev fill:#e8f5e8
    classDef cicd fill:#fff3e0
    classDef hosting fill:#f3e5f5
    classDef external fill:#ffebee

    class DEV,REPO dev
    class GHA,BUILD cicd
    class HOST,APP,CRON,FS hosting
    class GITHUB external
```

---

## Participant Registry

| Participant | Stereotype | Boundary | Decomposable | Involvement |
|------------|-----------|----------|-------------|-------------|
| Scrum Master | actor | External | No | Primary user |
| Team Member | actor | External | No | Time tracking |
| cPanel Cron | actor | External | No | Trigger sync |
| Dashboard UI | boundary | Presentation | No | Entry point |
| GitHub API Client | boundary | Integration | No | External API |
| GitHub Sync Service | control | Integration | Yes | Sync orchestration |
| GraphQL Response Parser | control | Integration | Yes | Data transformation |
| Burndown Service | control | Analytics | Yes | Chart calculation |
| Efficiency Service | control | Analytics | Yes | Member analysis |
| Time Tracking Service | control | Analytics | Yes | Audit + update |
| Auth Service | control | Processing | Yes | Session auth |
| MySQL Database | entity | Data | No | Persistence |
| Sync History | entity | Data | No | Audit trail |
| JSON Snapshots | entity | Data | No | Historical data |
| Issues Table | entity | Data | No | Issue storage |
| Time Logs | entity | Data | No | Audit records |

## Boundary Rules Compliance

| Rule | Status | Notes |
|------|--------|-------|
| VR-1: Single External Interface | ✓ Compliant | Each boundary has one external actor entry point |
| VR-2: Boundary-First Reception | ✓ Compliant | External actors always target boundary-type participants first (DashUI, GHClient) |
| VR-3: Control-Only Decomposition | ✓ Compliant | Only control-type participants listed as decomposable |
| VR-4: Cohesive Responsibility | ✓ Compliant | Each boundary groups functionally related participants |

---
<!-- Last Updated: 2026-04-02 -->