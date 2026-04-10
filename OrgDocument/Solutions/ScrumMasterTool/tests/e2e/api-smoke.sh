#!/usr/bin/env bash
# =============================================================================
# api-smoke.sh — ScrumMasterTool API smoke test (T032)
#
# Exercises all 13 REST endpoints against a running server instance.
# Prints PASS / FAIL per endpoint and exits 1 if any assertion fails.
#
# Usage:
#   TEST_BASE_URL=http://localhost:8080 \
#   TEST_ADMIN_EMAIL=admin@example.com  \
#   TEST_ADMIN_PASSWORD=Admin1234!      \
#   bash tests/e2e/api-smoke.sh
#
# Optional env vars:
#   TEST_MEMBER_EMAIL    — email of a member-role user (default: uses admin)
#   TEST_MEMBER_PASSWORD — password of member-role user
#   TEST_PROJECT_ID      — project id to test data endpoints (default: 1)
#   TEST_ISSUE_ID        — issue id for time update test (default: 1)
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
BASE_URL="${TEST_BASE_URL:-http://localhost:8080}"
ADMIN_EMAIL="${TEST_ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${TEST_ADMIN_PASSWORD:-Admin1234!}"
MEMBER_EMAIL="${TEST_MEMBER_EMAIL:-}"
MEMBER_PASSWORD="${TEST_MEMBER_PASSWORD:-}"
PROJECT_ID="${TEST_PROJECT_ID:-1}"
ISSUE_ID="${TEST_ISSUE_ID:-1}"

# Temporary cookie jar for session persistence
ADMIN_COOKIE=$(mktemp)
MEMBER_COOKIE=$(mktemp)
CLEAN_COOKIE=$(mktemp)  # intentionally empty — unauthenticated requests

cleanup() {
    rm -f "$ADMIN_COOKIE" "$MEMBER_COOKIE" "$CLEAN_COOKIE"
}
trap cleanup EXIT

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

PASS=0
FAIL=0

pass() { echo "  [PASS] $1"; PASS=$((PASS + 1)); }
fail() { echo "  [FAIL] $1"; FAIL=$((FAIL + 1)); }

# Send a curl request and capture HTTP status code.
# Usage: http_status <cookie_jar> <method> <path> [curl_extra_args...]
http_status() {
    local cookie_jar="$1"
    local method="$2"
    local path="$3"
    shift 3
    curl -s -o /dev/null -w "%{http_code}" \
        -b "$cookie_jar" -c "$cookie_jar" \
        -X "$method" \
        "${BASE_URL}${path}" \
        "$@"
}

# POST JSON body and capture HTTP status.
post_json() {
    local cookie_jar="$1"
    local path="$2"
    local json="$3"
    curl -s -o /dev/null -w "%{http_code}" \
        -b "$cookie_jar" -c "$cookie_jar" \
        -X POST \
        -H "Content-Type: application/json" \
        -d "$json" \
        "${BASE_URL}${path}"
}

# PUT JSON body and capture HTTP status.
put_json() {
    local cookie_jar="$1"
    local path="$2"
    local json="$3"
    curl -s -o /dev/null -w "%{http_code}" \
        -b "$cookie_jar" -c "$cookie_jar" \
        -X PUT \
        -H "Content-Type: application/json" \
        -d "$json" \
        "${BASE_URL}${path}"
}

assert_status() {
    local label="$1"
    local expected="$2"
    local actual="$3"
    if [[ "$actual" == "$expected" ]]; then
        pass "$label → HTTP $actual"
    else
        fail "$label → expected HTTP $expected, got HTTP $actual"
    fi
}

# ---------------------------------------------------------------------------
# Health check — abort early if server is not reachable
# ---------------------------------------------------------------------------
echo ""
echo "=== ScrumMasterTool API Smoke Test ==="
echo "Base URL : $BASE_URL"
echo "Project  : $PROJECT_ID   Issue: $ISSUE_ID"
echo ""

HEALTH=$(http_status "$CLEAN_COOKIE" GET /api/health 2>/dev/null) || true
if [[ "$HEALTH" != "200" ]]; then
    echo "[ERROR] Server not reachable at ${BASE_URL}/api/health (HTTP ${HEALTH:-no-response})"
    echo "        Start the server with: php -S localhost:8080 -t public/"
    exit 2
fi
echo "[OK] Server reachable"
echo ""

# ---------------------------------------------------------------------------
# Establish admin session
# ---------------------------------------------------------------------------
echo "--- Auth ---"

STATUS=$(post_json "$ADMIN_COOKIE" /api/auth/login \
    "{\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${ADMIN_PASSWORD}\"}")
assert_status "POST /api/auth/login (admin)"  "200" "$STATUS"

if [[ "$STATUS" != "200" ]]; then
    echo "[FATAL] Admin login failed — cannot continue smoke test"
    exit 1
fi

STATUS=$(http_status "$ADMIN_COOKIE" GET /api/auth/me)
assert_status "GET /api/auth/me (authenticated)" "200" "$STATUS"

# Repeated login should still be 200
STATUS=$(post_json "$ADMIN_COOKIE" /api/auth/login \
    "{\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${ADMIN_PASSWORD}\"}")
assert_status "POST /api/auth/login (repeated)" "200" "$STATUS"

# Unauthenticated /me should be 401
STATUS=$(http_status "$CLEAN_COOKIE" GET /api/auth/me)
assert_status "GET /api/auth/me (unauthenticated)" "401" "$STATUS"

# ---------------------------------------------------------------------------
# Projects
# ---------------------------------------------------------------------------
echo ""
echo "--- Projects ---"

STATUS=$(http_status "$ADMIN_COOKIE" GET /api/projects)
assert_status "GET /api/projects" "200" "$STATUS"

STATUS=$(http_status "$ADMIN_COOKIE" GET "/api/projects/${PROJECT_ID}")
assert_status "GET /api/projects/{id}" "200" "$STATUS"

STATUS=$(http_status "$ADMIN_COOKIE" GET "/api/projects/${PROJECT_ID}/issues")
assert_status "GET /api/projects/{id}/issues" "200" "$STATUS"

STATUS=$(http_status "$ADMIN_COOKIE" GET "/api/projects/${PROJECT_ID}/burndown")
assert_status "GET /api/projects/{id}/burndown" "200" "$STATUS"

STATUS=$(http_status "$ADMIN_COOKIE" GET "/api/projects/${PROJECT_ID}/members")
assert_status "GET /api/projects/{id}/members" "200" "$STATUS"

# ---------------------------------------------------------------------------
# Issues — time update
# ---------------------------------------------------------------------------
echo ""
echo "--- Issues ---"

STATUS=$(put_json "$ADMIN_COOKIE" "/api/issues/${ISSUE_ID}/time" \
    '{"estimated_time":8.0,"remaining_time":5.0,"actual_time":3.0}')
assert_status "PUT /api/issues/{id}/time (valid payload)" "200" "$STATUS"

STATUS=$(put_json "$ADMIN_COOKIE" "/api/issues/${ISSUE_ID}/time" \
    '{"estimated_time":-1.0}')
assert_status "PUT /api/issues/{id}/time (negative value)" "400" "$STATUS"

STATUS=$(put_json "$ADMIN_COOKIE" "/api/issues/999999/time" \
    '{"estimated_time":5.0}')
assert_status "PUT /api/issues/{id}/time (nonexistent issue)" "404" "$STATUS"

# ---------------------------------------------------------------------------
# Sync
# ---------------------------------------------------------------------------
echo ""
echo "--- Sync ---"

STATUS=$(http_status "$ADMIN_COOKIE" GET /api/sync/history)
assert_status "GET /api/sync/history" "200" "$STATUS"

# Admin trigger — may return 200 (success) or 502 (no GitHub PAT in test)
STATUS=$(http_status "$ADMIN_COOKIE" POST /api/sync/trigger)
if [[ "$STATUS" == "200" || "$STATUS" == "502" ]]; then
    pass "POST /api/sync/trigger (admin) → HTTP $STATUS (200=success, 502=API-error expected in test)"
else
    fail "POST /api/sync/trigger (admin) → expected 200 or 502, got $STATUS"
fi

# ---------------------------------------------------------------------------
# Admin
# ---------------------------------------------------------------------------
echo ""
echo "--- Admin ---"

STATUS=$(http_status "$ADMIN_COOKIE" GET /api/admin/users)
assert_status "GET /api/admin/users" "200" "$STATUS"

UNIQUE_EMAIL="smoke_$(date +%s)@test.local"
STATUS=$(post_json "$ADMIN_COOKIE" /api/admin/users \
    "{\"email\":\"${UNIQUE_EMAIL}\",\"password\":\"Smoke5678!\",\"display_name\":\"Smoke User\",\"role\":\"member\"}")
assert_status "POST /api/admin/users (create)" "201" "$STATUS"

STATUS=$(post_json "$ADMIN_COOKIE" /api/admin/users \
    "{\"email\":\"${UNIQUE_EMAIL}\",\"password\":\"Smoke5678!\",\"display_name\":\"Smoke User\"}")
assert_status "POST /api/admin/users (duplicate email)" "409" "$STATUS"

STATUS=$(post_json "$ADMIN_COOKIE" /api/admin/users \
    '{"email":"not-an-email","password":"Short","display_name":"Bad"}')
assert_status "POST /api/admin/users (invalid data)" "422" "$STATUS"

# ---------------------------------------------------------------------------
# Member access control (only if TEST_MEMBER_EMAIL is set)
# ---------------------------------------------------------------------------
if [[ -n "$MEMBER_EMAIL" ]]; then
    echo ""
    echo "--- Member access control ---"

    STATUS=$(post_json "$MEMBER_COOKIE" /api/auth/login \
        "{\"email\":\"${MEMBER_EMAIL}\",\"password\":\"${MEMBER_PASSWORD}\"}")
    assert_status "POST /api/auth/login (member)" "200" "$STATUS"

    if [[ "$STATUS" == "200" ]]; then
        STATUS=$(http_status "$MEMBER_COOKIE" GET "/api/projects/${PROJECT_ID}/issues")
        assert_status "GET /api/projects/{id}/issues (member)" "200" "$STATUS"

        STATUS=$(http_status "$MEMBER_COOKIE" GET /api/admin/users)
        assert_status "GET /api/admin/users (member) → should be 403" "403" "$STATUS"

        STATUS=$(http_status "$MEMBER_COOKIE" POST /api/sync/trigger)
        assert_status "POST /api/sync/trigger (member) → should be 403" "403" "$STATUS"
    fi
fi

# ---------------------------------------------------------------------------
# Logout
# ---------------------------------------------------------------------------
echo ""
echo "--- Logout ---"

STATUS=$(http_status "$ADMIN_COOKIE" POST /api/auth/logout)
assert_status "POST /api/auth/logout" "200" "$STATUS"

# After logout, /me should be 401
STATUS=$(http_status "$ADMIN_COOKIE" GET /api/auth/me)
assert_status "GET /api/auth/me (after logout)" "401" "$STATUS"

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "======================================="
echo "Results: ${PASS} passed, ${FAIL} failed"
echo "======================================="

if [[ $FAIL -gt 0 ]]; then
    exit 1
fi

exit 0
