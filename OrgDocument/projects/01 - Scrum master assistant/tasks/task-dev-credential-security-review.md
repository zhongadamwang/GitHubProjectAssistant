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
- [ ] No endpoint, log line, exception message, snapshot, audit entry or frontend state exposes a token or ciphertext (verified by an automated test that scans API responses and log output after exercising every account endpoint)
- [ ] All new endpoints enforce auth + admin (403 matrix test); inputs validated (owner login, label length, https-only endpoint — SSRF guard against internal addresses/redirects in the configurable GraphQL URL)
- [ ] Test-connection and discovery are rate-limited; failures do not reveal whether an account exists for other users
- [ ] `Authorization` header redacted in any debug/trace output of the GitHub client; cURL verbose/logging disabled in production
- [ ] CSRF posture reviewed for the new state-changing endpoints (SameSite=Strict session cookie + JSON-only content type, or add a token) and documented
- [ ] Audit log verified (actor, action, entity, no secrets) and visible to admins (list endpoint)
- [ ] Key handling documented: `APP_KEY` generation, backup, rotation, loss recovery; `.env` permissions (600) in the deployment guide
- [ ] `security-checklist.md` extended with the new items (all PASS or tracked findings fixed)

### Tasks/Subtasks
- [ ] Review + automated leakage test; fix findings
- [ ] SSRF/URL validation, rate limiting, redaction
- [ ] Documentation updates

### Definition of Done
- [ ] Checklist items PASS; no open High/Critical findings  

### Dependencies
- T048, T049, T050, T051  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
