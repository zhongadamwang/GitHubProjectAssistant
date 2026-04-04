# T007 — Write GraphQL Query Templates

**Task ID**: T007  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Define reusable GitHub GraphQL v4 query strings for fetching project metadata, project items (issues), and field definitions. Queries must support cursor-based pagination and include all fields required by the response parser and sync service.

### Acceptance Criteria
- [x] `src/GraphQL/queries.php` returns a named array of GraphQL query strings
- [x] `FETCH_PROJECT_FIELDS` query retrieves project title, number, creator, and custom field definitions (name, id, type)
- [x] `FETCH_PROJECT_ITEMS` query retrieves all project items with: title, body, state, assignees, labels, milestone, createdAt, updatedAt, closedAt, plus all custom field values
- [x] `FETCH_PROJECT_ITEMS` supports cursor pagination via `$after: String` variable
- [x] `FETCH_VIEWER` query retrieves authenticated user login as a connection health check
- [x] All queries are parameterised (use GraphQL variables — not string interpolation)
- [x] Queries request only the fields actually consumed; no over-fetching

### Tasks/Subtasks
- [x] Create `src/GraphQL/queries.php` with PHP constants / associative array for each named query
- [x] Write `FETCH_VIEWER` — minimal viewer check (`viewer { login }`)
- [x] Write `FETCH_PROJECT_FIELDS` — project by owner+number, field list with ID and data type
- [x] Write `FETCH_PROJECT_ITEMS` — paginated project items (first 100 per page), inline fragments for each field value type (`ProjectV2ItemFieldTextValue`, `ProjectV2ItemFieldNumberValue`, `ProjectV2ItemFieldDateValue`, `ProjectV2ItemFieldSingleSelectValue`)
- [x] Add `pageInfo { hasNextPage endCursor }` to `FETCH_PROJECT_ITEMS` for cursor navigation
- [x] Document each query constant with a JSDoc-style block comment: purpose, variables, return shape

### Definition of Done
- [x] All acceptance criteria met
- [x] Queries validated against GitHub GraphQL Explorer (schema-compatible)
- [x] No hardcoded owner/repo/project values — all injected via GraphQL variables
- [x] Each query constant has an explanatory comment

### Dependencies
- T001 — PHP project must exist; `src/GraphQL/` directory expected

### Effort Estimate
**Time Estimate**: 0.5 day

### Priority
High — All Phase 2 tasks (T008, T009) depend on these query definitions

### Labels/Tags
- Category: development
- Component: backend, github-api, graphql
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- GitHub GraphQL v4 endpoint: `https://api.github.com/graphql`
- Auth header: `Authorization: Bearer <PAT>` — PAT loaded from `.env` (`GITHUB_TOKEN`)
- Projects v2 API uses `projectV2` (not legacy `project`)
- Custom field values use a union type — must use inline fragments to extract the concrete value
- Max items per page: 100 (GitHub hard limit for ProjectV2Items connection)
- Source Requirements: R-001, R-002 — ADR-4

### Progress Updates
- **2026-04-03**: Created `src/GraphQL/Queries.php` as a final class with three public constants (`FETCH_VIEWER`, `FETCH_PROJECT_FIELDS`, `FETCH_PROJECT_ITEMS`) using PHP heredoc strings. `FETCH_VIEWER` returns `viewer { login }`. `FETCH_PROJECT_FIELDS` fetches project metadata plus field definitions with `ProjectV2Field`, `ProjectV2SingleSelectField`, and `ProjectV2IterationField` inline fragments. `FETCH_PROJECT_ITEMS` uses `$after: String` cursor pagination (100/page), fetches full issue content (assignees, labels, milestone, dates), and extracts all field value types via inline fragments (Text, Number, Date, SingleSelect, Iteration). Added `Queries::get()` and `Queries::variables()` static helpers for lookup and schema documentation.

---
**Status**: Completed  
**Last Updated**: 2026-04-03
