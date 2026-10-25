# T047 — Credential Vault (Token Encryption at Rest)

**Task ID**: T047  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md)  

### Description
Provide an encryption service so GitHub tokens are stored only as authenticated ciphertext, with a key from `.env`, key rotation and safe failure modes.

### Acceptance Criteria
- [ ] `CredentialVault::encrypt(string): array{ciphertext, nonce}` / `decrypt(...)` using libsodium `secretbox` (XChaCha20-Poly1305) with a fresh random nonce per value; fallback to `openssl` AES-256-GCM when sodium is unavailable (NFR-A6)
- [ ] Key from `APP_KEY` (base64, 32 bytes) via `config/settings.php`; missing/short key → clear startup error for vault use only (other endpoints keep working)
- [ ] Wrong key or tampered ciphertext → typed `CredentialDecryptionException` (no partial plaintext, no stack trace leak); callers mark the account `needs_reentry`
- [ ] `php cron/generate-app-key.php` prints a new key; `php cron/rotate-app-key.php --old=… --new=…` re-encrypts all stored tokens in one transaction
- [ ] `token_hint()` helper returns the last 4 characters only; vault never logs plaintext; `__debugInfo`/`__toString` do not expose secrets
- [ ] PHPUnit: round-trip, nonce uniqueness, tamper detection, wrong key, rotation, openssl fallback

### Tasks/Subtasks
- [ ] Vault class + settings + exception
- [ ] Key generation and rotation scripts
- [ ] Tests; document key handling in `DEPLOYMENT.md` and `security-checklist.md`

### Definition of Done
- [ ] All acceptance criteria met; no plaintext token in any log/test output  

### Dependencies
- None (T046 supplies the columns it fills)  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High — security foundation  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
