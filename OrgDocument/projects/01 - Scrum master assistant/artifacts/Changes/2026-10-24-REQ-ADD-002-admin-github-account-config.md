# Admin-Managed GitHub Account Configuration (Secure Token Storage)

## Change ID
**Change ID**: REQ-ADD-002  
**Date Created**: 2026-10-24  
**Status**: Implemented (2026-10-25; verified locally — production verification pending)  
**Priority**: High  
**Requested By**: Project owner (2026-10-24: "allow admin to configure the Git account instead of saving the PAT in the .env file")  
**Change Type**: REQ-ADD (new requirement; also changes deployment/configuration process)

## Summary
Admins configure GitHub account(s) and their access tokens in the application; tokens are stored encrypted in the database, not in `.env`.

## Change Details

### Current State
- `REPO_PAT`, `REPO_ORG`, `REPO_PROJECT_NUMBER` (and optional `REPO_GRAPHQL_URL`) live in the server `.env`. Changing the token or account requires file access (cPanel/SSH) and a redeploy-safe edit.
- `config/container.php` builds a single `GitHubGraphQLService` from `settings.github.pat`; if the PAT is empty the container throws, so even read-only endpoints that depend on `SyncController` fail (seen during T032 testing).
- The GitHub queries hard-code `organization(login: $owner)`, so personal (user) accounts cannot be used.
- No way to verify a token, see its status/expiry, or rotate it from the UI. Only user management exists in the Admin view.

### Proposed State (requirements)
**Functional**
- **FR-A1** An admin can add a *GitHub account* with: label, owner login (organization or user), owner type (auto-detected), access token (PAT), optional GraphQL endpoint (GitHub Enterprise; default `https://api.github.com/graphql`).
- **FR-A2** An admin can edit an account (label, endpoint), replace its token, and delete it. A token is never shown again after saving: the UI shows only a mask and the last 4 characters.
- **FR-A3** **Test connection**: validates the token against GitHub (viewer login, token type/scopes where available, expiry if reported) and records `last_verified_at`, status (`ok | invalid | expired | insufficient_scope | unreachable`) and the message.
- **FR-A4** On save and on every sync failure caused by authentication, the account status is updated; the Admin and Sync views surface "token invalid / expired" clearly.
- **FR-A5** Deleting an account that still has tracked projects is blocked (or requires the admin to untrack them first — see REQ-ADD-003); deleting removes the stored token.
- **FR-A6** Migration/backward compatibility: if no account exists yet but `REPO_PAT`/`REPO_ORG` are set in `.env`, an admin action "Import from .env" (and the migration helper in T053) creates the account and its project from those values. After import, `.env` values are ignored and may be removed.
- **FR-A7** All account changes (create, update, token replaced, test, delete) are recorded in an audit log (who, when, what — never the secret).

**Non-functional / security**
- **NFR-A1** Tokens are encrypted at rest with authenticated encryption (libsodium `secretbox` / XChaCha20-Poly1305) using a key from `.env` (`APP_KEY`, 32 random bytes, base64). Plaintext tokens are never written to the database, logs, snapshots or error messages.
- **NFR-A2** The API never returns the token (not even to admins). Responses contain `token_hint` (last 4) and status only.
- **NFR-A3** Account endpoints are admin-only (`AdminMiddleware`). Test-connection is rate-limited (e.g. 10/min/user) to avoid abuse of GitHub quotas.
- **NFR-A4** Key rotation: a CLI command re-encrypts all tokens with a new key; losing the key means admins must re-enter tokens (documented, no silent failure).
- **NFR-A5** Token handled only in memory when building the GitHub client; request/response logging must redact `Authorization`.
- **NFR-A6** Works on cPanel shared hosting (PHP 8.2 with sodium or openssl; fallback to `openssl` AES-256-GCM if sodium is missing).
- **NFR-A7** Deployment no longer needs the GitHub token as a secret/variable; `APP_KEY` becomes the only new required secret.

### Design analysis (options considered)
| Option | Verdict |
|---|---|
| Keep PAT in `.env` | Current behaviour; rejected by the owner (needs file access, no rotation/verification UI) |
| Plaintext in DB | Rejected — a DB leak/backup exposes the token |
| Hash the token | Impossible — the app must send the real token to GitHub |
| **Encrypt in DB with an app key from `.env`** | **Recommended** — DB leak alone is not enough; one small secret stays in `.env` |
| GitHub App / OAuth device flow | Better long term (short-lived tokens, no PAT) but needs app registration and callback handling on shared hosting; listed as a future enhancement |

Required PAT permissions (to document in the UI): classic token `read:project` (+ `read:org` for org projects, `repo` for private repository issues); fine-grained token with organization *Projects: Read* and repository *Issues: Read*.

### Data model (sketch)
`github_accounts(id, label, owner_login, owner_type ENUM('organization','user'), graphql_url, token_ciphertext, token_nonce, token_hint, status, status_message, last_verified_at, token_expires_at NULL, created_by, created_at, updated_at)` and `admin_audit_log(id, actor_user_id, action, entity, entity_id, detail JSON, created_at)`.

### API (sketch, all admin-only)
`GET /api/admin/github-accounts` · `POST /api/admin/github-accounts` · `PUT /api/admin/github-accounts/{id}` · `DELETE /api/admin/github-accounts/{id}` · `POST /api/admin/github-accounts/{id}/test` · `POST /api/admin/github-accounts/import-env`

### Rationale
Operational independence from server file access, safer secret handling, visible token health, and the prerequisite for multi-project/multi-account support (REQ-ADD-003).

## Impact Analysis

| File / component | Impact | Update required |
|---|---|---|
| `database/migrations/011_*`, repositories | `github_accounts`, `admin_audit_log` | Yes |
| New `CredentialVault` (encryption) service, `config/settings.php` (`APP_KEY`) | Encrypt/decrypt tokens | Yes |
| `src/Services/GitHubGraphQLService.php`, `config/container.php` | Build client per account from the vault; stop throwing when `.env` PAT is empty | Yes |
| `src/GraphQL/Queries.php` | `repositoryOwner(login)` (org or user) instead of `organization(login)` — verify in T048 | Yes |
| New admin controller/routes, `AdminView.vue` / new Settings view | Account management UI | Yes |
| `.env.example`, `.env.production`, `DEPLOYMENT.md`, GitHub Actions workflow | `APP_KEY` instead of `REPO_PAT` | Yes |
| `security-checklist.md`, tests | New credential-handling checks | Yes |

**Affected tasks**: T008 (GitHub client), T010 (sync), T017/T026 (admin).  
**Risk level**: High (credentials) — dedicated security review task T054.  
**Estimated effort**: ~5.5 days (T046–T049 + part of T053/T054).

## Owner Decisions (2026-10-25)
1. **Several GitHub accounts** are supported — approved.
2. **PAT now**; GitHub App / OAuth is a possible later phase — approved.
3. **`APP_KEY` in `.env`** is the one remaining secret — approved.
4. Admins never see a saved token (replace-only) — recommended default, not contested.
5. A token with insufficient scopes can be saved but is marked `insufficient_scope` with guidance — recommended default, not contested.

## Implementation Plan
T046 data model → T047 credential vault → T048 account API + GitHub client factory + query generalisation → T049 admin UI → T053 import/compat → T054 security review → T055 release.

## Acceptance Criteria
- An admin can add an account with a PAT in the UI, test it, and run a sync without `REPO_PAT` in `.env`.
- Database dumps and API responses contain no plaintext token; only the hint.
- Replacing/deleting a token works and is audited; a wrong key yields a clear "re-enter token" status, not a crash.
- Existing deployments keep working after upgrade via "Import from .env".
- Non-admins cannot reach any account endpoint (403).

## Approval Status
- [x] Owner confirms open questions (2026-10-25)
- [x] Approved for implementation

## Related
- Tasks: [T046](../../tasks/task-dev-account-data-model.md), [T047](../../tasks/task-dev-credential-vault.md), [T048](../../tasks/task-dev-github-account-api.md), [T049](../../tasks/task-dev-github-account-admin-ui.md), [T053](../../tasks/task-dev-env-import-compat.md), [T054](../../tasks/task-dev-credential-security-review.md)
- Related change: [REQ-ADD-003](2026-10-24-REQ-ADD-003-multi-project-support.md)
