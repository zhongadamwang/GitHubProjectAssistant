# T048 — GitHub Account API, Client Factory and Owner-Generic Queries

**Task ID**: T048  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md), [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
Admin endpoints to manage GitHub accounts, a factory that builds GitHub clients from stored accounts, project discovery, and queries that work for organizations and users.

### Acceptance Criteria
- [x] Admin-only endpoints: `GET/POST /api/admin/github-accounts`, `PUT/DELETE /api/admin/github-accounts/{id}`, `POST …/{id}/test`, `GET …/{id}/projects` (discover); non-admin → 403, unauthenticated → 401
- [x] Create/update validate input (label, owner login format, endpoint must be https); token required on create, optional on update (replace-only); responses contain `token_hint`, status and timestamps — **never** the token or ciphertext
- [x] Test connection: calls `FETCH_VIEWER`, records status (`ok | invalid | expired | insufficient_scope | unreachable`), message, `last_verified_at`, token expiry when GitHub reports it (`github-authentication-token-expiration` header); rate-limited (10/min/user)
- [x] Delete blocked with 409 while tracked projects reference the account
- [x] `GitHubClientFactory::forAccount(account)` decrypts via the vault and returns a `GitHubGraphQLService`; a decryption failure marks the account `needs_reentry`
- [x] `FETCH_PROJECT_FIELDS` / `FETCH_PROJECT_ITEMS` use `repositoryOwner(login:)` with `... on ProjectV2Owner` so organization and user projects both work (verify against the live API); new `LIST_OWNER_PROJECTS` query (paged) for discovery
- [x] Every create/update/replace/test/delete writes an audit entry (no secrets)
- [x] PHPUnit with a stubbed GitHub client for all endpoints and failure modes; container no longer throws when `.env` has no PAT

### Tasks/Subtasks
- [x] `GitHubAccountController`, routes, validation, rate limit
- [x] `GitHubClientFactory`, query changes, discovery parser
- [x] Tests and smoke script additions

### Definition of Done
- [x] All acceptance criteria met; PHPUnit and smoke pass; responses verified free of token material  

### Dependencies
- T046, T047  

### Effort Estimate
**Time Estimate**: 2 days  

### Priority
High  

### Progress Updates
- **2026-10-25**: Account API, client factory and owner-generic queries complete. Live check (read-only, `gh`): `repositoryOwner{__typename}` returns User/Organization; all four project queries pass GitHub schema validation for both `user` and `organization` roots (the `gh` token lacks `read:project`, so project data itself was not fetched — it stops at INSUFFICIENT_SCOPES, which the app maps to the *insufficient scope* status).

---
**Status**: Completed  
**Last Updated**: 2026-10-25
