# T054 — Credential Security Review and Hardening

**Task ID**: T054  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md)  

### Description
Dedicated review of everything that touches GitHub tokens and admin configuration, against the OWASP checklist already in `security-checklist.md`.

### Acceptance Criteria
- [x] No endpoint, log line, exception message, snapshot, audit entry or frontend state exposes a token or ciphertext (verified by an automated test that scans API responses and log output after exercising every account endpoint)
- [x] All new endpoints enforce auth + admin (403 matrix test); inputs validated (owner login, label length, https-only endpoint — SSRF guard against internal addresses/redirects in the configurable GraphQL URL)
- [x] Test-connection and discovery are rate-limited; failures do not reveal whether an account exists for other users
- [x] `Authorization` header redacted in any debug/trace output of the GitHub client; cURL verbose/logging disabled in production
- [x] CSRF posture reviewed for the new state-changing endpoints (SameSite=Strict session cookie + JSON-only content type, or add a token) and documented
- [x] Audit log verified (actor, action, entity, no secrets) and visible to admins (list endpoint)
- [x] Key handling documented: `APP_KEY` generation, backup, rotation, loss recovery; `.env` permissions (600) in the deployment guide
- [x] `security-checklist.md` extended with the new items (all PASS or tracked findings fixed)

### Tasks/Subtasks
- [x] Review + automated leakage test; fix findings
- [x] SSRF/URL validation, rate limiting, redaction
- [x] Documentation updates

### Definition of Done
- [x] Checklist items PASS; no open High/Critical findings (P8-10 fixed; owner rotated the token and DB password 2026-10-25)  

### Dependencies
- T048, T049, T050, T051  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Progress Updates
- **2026-10-25**: Review done; automated leak/CSRF/SSRF/authorization tests added; fixes: token redaction, endpoint re-validation at use time, no redirects, JSON-only admin requests, no server paths in API output, Slim 4.15.3 security update. **Open finding P8-10 (owner action):** `.env.production` is tracked in git and contains a real-looking token — rotate it.

---
**Status**: Completed  
**Last Updated**: 2026-10-25
