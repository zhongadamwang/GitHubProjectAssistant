# T009 — Implement GraphQL ResponseParser

**Task ID**: T009  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Build the `ResponseParser` class that transforms raw GitHub GraphQL JSON payloads into structured local domain models (`Project` and `Issue`). This class isolates all knowledge of the GitHub API response shape from the rest of the codebase.

### Acceptance Criteria
- [ ] `ResponseParser::parseProject(array $rawProjectData): Project` maps GraphQL project fields to a `Project` model instance
- [ ] `ResponseParser::parseIssueNode(array $rawNode): Issue` maps a single project item node (with inline-fragment field values) to an `Issue` model instance
- [ ] `ResponseParser::parseIssueNodes(array $rawNodes): array` returns an array of `Issue` instances from a flat list of nodes
- [ ] Custom field values extracted correctly: text, number, date, and single-select types all handled
- [ ] Fields absent from the response (null / missing key) are mapped to `null` without throwing exceptions
- [ ] `Issue` model carries: `githubId`, `title`, `body`, `state`, `assignees[]`, `labels[]`, `milestone`, `createdAt`, `updatedAt`, `closedAt`, `customFields[]` (key-value map)
- [ ] `Project` model carries: `githubId`, `title`, `number`, `owner`, `description`, `fields[]` (field definitions)

### Tasks/Subtasks
- [ ] Create `src/Models/Project.php` — typed properties, `fromArray()` named constructor, `toArray()` serializer
- [ ] Create `src/Models/Issue.php` — typed properties, `fromArray()` named constructor, `toArray()` serializer; include `estimatedHours`, `remainingHours`, `actualHours` as nullable fields (populated by local data, not GitHub)
- [ ] Create `src/GraphQL/ResponseParser.php` with `parseProject()`, `parseIssueNode()`, `parseIssueNodes()` static methods
- [ ] Handle inline fragment dispatch: check for `__typename` or presence of value keys (`text`, `number`, `date`, `name`) to determine field value type
- [ ] Map `ProjectV2ItemFieldTextValue` → string, `ProjectV2ItemFieldNumberValue` → float, `ProjectV2ItemFieldDateValue` → string (ISO 8601), `ProjectV2ItemFieldSingleSelectValue` → string
- [ ] Write unit tests with fixture JSON payloads for all field value types (text, number, date, single-select, null/missing)

### Definition of Done
- [ ] All acceptance criteria met
- [ ] No silently dropped data — unrecognised field types are logged as warnings (not thrown)
- [ ] `Project` and `Issue` models implement `toArray()` for serialization to the snapshot JSON and DB insert
- [ ] Unit tests pass for all field type combinations including missing/null

### Dependencies
- T007 — Response shape determined by query structure in `queries.php`

### Effort Estimate
**Time Estimate**: 0.5 day

### Priority
High — Blocks T010 (Sync Logic)

### Labels/Tags
- Category: development
- Component: backend, github-api, graphql, data-mapping
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- GitHub Projects v2 items are typed as `ProjectV2Item`; the inner `fieldValues` connection contains a union of concrete types
- `content` node on each item holds the underlying Issue/PR; check `__typename` for `Issue` vs `PullRequest`
- Only map `Issue` content nodes; skip `DraftIssue` and `PullRequest` unless required
- `assignees` and `labels` are nested connections — extract `nodes[].login` / `nodes[].name`
- Source Requirements: R-001, R-002, R-004 — ADR-4

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-03
