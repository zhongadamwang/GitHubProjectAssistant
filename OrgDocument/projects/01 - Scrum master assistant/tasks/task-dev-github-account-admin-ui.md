# T049 — Admin UI: GitHub Accounts Settings

**Task ID**: T049  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md)  

### Description
A "Settings → GitHub accounts" area for admins to add, edit, test and delete accounts and replace tokens without ever seeing them again.

### Acceptance Criteria
- [x] New admin-only route/view (`/admin/github`) linked from the Admin area; non-admins are redirected as for `/admin`
- [x] Account list: label, owner, type, endpoint, status badge (ok / invalid / expired / insufficient scope / unreachable / needs re-entry), last verified, token hint (`••••abcd`), tracked-project count
- [x] Add/edit form with password-type token input (never pre-filled), inline validation, helper text listing the required token permissions (classic and fine-grained)
- [x] Buttons: **Test connection** (shows result message), **Replace token**, **Delete** (confirmation; shows the 409 reason when projects are still tracked)
- [x] "Import from .env" button appears only when `.env` has legacy `REPO_*` values and no account exists (calls T048/T053 endpoint)
- [x] The token is cleared from component state immediately after submit; no token in the URL, console or error banner
- [x] Browser-verified: add → test → replace → delete, error states, non-admin access

### Tasks/Subtasks
- [x] `GitHubAccountsView.vue`, form component, API service functions, store
- [x] Admin navigation entry; styles consistent with existing views
- [x] Manual checklist in `tests/e2e/FINDINGS.md`

### Definition of Done
- [x] All acceptance criteria met; `npm run build` clean  

### Dependencies
- T048  

### Effort Estimate
**Time Estimate**: 1.5 days  

### Priority
High  

### Progress Updates
- **2026-10-25**: Admin → GitHub accounts view complete; verified in the browser (token write-only, status badges, audit log, import from .env).

---
**Status**: Completed  
**Last Updated**: 2026-10-25
