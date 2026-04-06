# E2E Findings — ScrumMasterTool (T032)

**Run Date**: 2026-04-06  
**Tester**: TBD  
**Baseline**: Phase 1–5 complete (T001–T031)  

## Findings

| # | Severity | Endpoint / Area | Description | Status | Fixed In |
|---|----------|----------------|-------------|--------|----------|
| — | — | — | No findings yet — testing not started | — | — |

## Severity Definitions

| Severity | Definition |
|----------|------------|
| **Blocker** | Prevents T032 from being marked complete; must be fixed before Phase 6 can advance |
| **Major** | Significant functional defect; should be fixed in T035 (Error Handling) or T033/T034 |
| **Minor** | Cosmetic or edge-case issue; tracked for awareness but not blocking |

## Testing Checklist

- [ ] `tests/e2e/api-smoke.sh` executed against running instance — exits 0
- [ ] `composer test` executed — exits 0
- [ ] Vue frontend manually exercised:
  - [ ] Login page loads and accepts credentials
  - [ ] Dashboard renders burndown chart
  - [ ] Issues view: inline time edit works (PUT /api/issues/{id}/time)
  - [ ] Members view: efficiency bar chart renders
  - [ ] Sync status view: history table populates
  - [ ] Admin view: user list loads; create user form works
- [ ] 30-second auto-refresh confirmed (network tab at ~30s intervals)
- [ ] Session expiry test: cleared session → API returns 401 → frontend redirects to /login
- [ ] Member → POST /api/sync/trigger → returns 403
- [ ] Time update survives sync: PATCH issue time → run sync → re-check values unchanged
