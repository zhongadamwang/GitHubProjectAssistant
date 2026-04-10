#!/usr/bin/env bash
# =============================================================================
# tests/perf/benchmark.sh
# Performance benchmark — measures TTFB for all 13 ScrumMasterTool API endpoints.
#
# Usage:
#   chmod +x tests/perf/benchmark.sh
#   BASE_URL=http://localhost:8080 \
#   ADMIN_EMAIL=admin@example.com \
#   ADMIN_PASSWORD=secret \
#   PROJECT_ID=1 \
#   ISSUE_ID=1 \
#   tests/perf/benchmark.sh
#
# Environment variables (all have defaults below):
#   BASE_URL        — API host (no trailing slash)
#   ADMIN_EMAIL     — Admin account email
#   ADMIN_PASSWORD  — Admin account password
#   PROJECT_ID      — A valid project ID in the database
#   ISSUE_ID        — A valid issue ID in the database
#   RUNS            — Number of timed requests per endpoint (default: 10)
#   RESULTS_FILE    — Output markdown path (default: tests/perf/results.md)
#   WARMUP          — Number of warmup runs before timing (default: 2)
#
# Output:
#   Prints per-endpoint summary to stdout.
#   Appends a marked section to RESULTS_FILE.
#
# Requirements: curl (tested with 7.74+), awk, date
# =============================================================================
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-changeme}"
PROJECT_ID="${PROJECT_ID:-1}"
ISSUE_ID="${ISSUE_ID:-1}"
RUNS="${RUNS:-10}"
WARMUP="${WARMUP:-2}"
RESULTS_FILE="${RESULTS_FILE:-$(dirname "$0")/results.md}"

COOKIE_JAR="$(mktemp /tmp/smt_bench_XXXXXX.txt)"
trap 'rm -f "$COOKIE_JAR"' EXIT

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
run_stamp() {
    date '+%Y-%m-%d %H:%M:%S %Z'
}

# bench_endpoint LABEL METHOD URL [CURL_EXTRA...]
# Prints TTFB readings and returns "LABEL | min | avg | max" line.
bench_endpoint() {
    local label="$1"
    local method="$2"
    local url="$3"
    shift 3
    local extra_args=("$@")

    # Warmup runs (not measured)
    for ((i = 0; i < WARMUP; i++)); do
        curl -s -o /dev/null -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
            -X "$method" "$url" "${extra_args[@]}" \
            -w '' 2>/dev/null || true
    done

    local times=()
    for ((i = 0; i < RUNS; i++)); do
        local t
        t=$(curl -s -o /dev/null -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
            -X "$method" "$url" "${extra_args[@]}" \
            -w "%{time_starttransfer}" 2>/dev/null || echo "0")
        times+=("$t")
    done

    # Compute min / avg / max with awk (values are seconds; convert to ms)
    local stats
    stats=$(printf '%s\n' "${times[@]}" | awk '
        BEGIN { min=9999; max=0; sum=0; n=0 }
        {
            v = $1 * 1000
            if (v < min) min = v
            if (v > max) max = v
            sum += v
            n++
        }
        END {
            avg = (n > 0) ? sum / n : 0
            printf "%.1f\t%.1f\t%.1f", min, avg, max
        }
    ')

    local min avg max
    IFS=$'\t' read -r min avg max <<< "$stats"

    printf "  %-55s  min=%6s ms  avg=%6s ms  max=%6s ms\n" \
        "$label" "$min" "$avg" "$max"

    # Return structured result for markdown table
    echo "$label|$method|$min|$avg|$max"
}

# ---------------------------------------------------------------------------
# Authenticate — POST /api/auth/login
# ---------------------------------------------------------------------------
echo "============================================================"
echo " ScrumMasterTool API Performance Benchmark"
echo " $(run_stamp)"
echo " Base URL : $BASE_URL"
echo " Runs/endpoint: $RUNS  (warmup: $WARMUP)"
echo "============================================================"
echo ""
echo "[1/2] Authenticating as $ADMIN_EMAIL ..."

LOGIN_HTTP=$(curl -s -o /dev/null -w "%{http_code}" \
    -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -X POST "$BASE_URL/api/auth/login" \
    -H 'Content-Type: application/json' \
    -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")

if [[ "$LOGIN_HTTP" != "200" ]]; then
    echo "ERROR: Login failed (HTTP $LOGIN_HTTP). Cannot benchmark authenticated endpoints."
    echo "       Set ADMIN_EMAIL and ADMIN_PASSWORD environment variables and retry."
    exit 1
fi
echo "  Login OK (HTTP 200)."
echo ""
echo "[2/2] Benchmarking endpoints ($RUNS runs each after $WARMUP warmup) ..."
echo ""

# Collect results for markdown table
RESULTS=()

# ---------------------------------------------------------------------------
# 1. POST /api/auth/login  (unauthenticated — use fresh cookie jar for this)
# ---------------------------------------------------------------------------
TMP_COOKIE="$(mktemp /tmp/smt_login_XXXXXX.txt)"
trap 'rm -f "$TMP_COOKIE"' EXIT
RESULTS+=( "$(bench_endpoint \
    "POST /api/auth/login" POST \
    "$BASE_URL/api/auth/login" \
    -H 'Content-Type: application/json' \
    -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}" \
    -b "$TMP_COOKIE" -c "$TMP_COOKIE"
)" )

# ---------------------------------------------------------------------------
# 2. GET /api/auth/me
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/auth/me" GET \
    "$BASE_URL/api/auth/me"
)" )

# ---------------------------------------------------------------------------
# 3. GET /api/projects
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/projects" GET \
    "$BASE_URL/api/projects"
)" )

# ---------------------------------------------------------------------------
# 4. GET /api/projects/{id}
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/projects/$PROJECT_ID" GET \
    "$BASE_URL/api/projects/$PROJECT_ID"
)" )

# ---------------------------------------------------------------------------
# 5. GET /api/projects/{id}/issues
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/projects/$PROJECT_ID/issues" GET \
    "$BASE_URL/api/projects/$PROJECT_ID/issues"
)" )

# ---------------------------------------------------------------------------
# 6. GET /api/projects/{id}/burndown
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/projects/$PROJECT_ID/burndown" GET \
    "$BASE_URL/api/projects/$PROJECT_ID/burndown"
)" )

# ---------------------------------------------------------------------------
# 7. GET /api/projects/{id}/members
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/projects/$PROJECT_ID/members" GET \
    "$BASE_URL/api/projects/$PROJECT_ID/members"
)" )

# ---------------------------------------------------------------------------
# 8. PUT /api/issues/{id}/time
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "PUT  /api/issues/$ISSUE_ID/time" PUT \
    "$BASE_URL/api/issues/$ISSUE_ID/time" \
    -H 'Content-Type: application/json' \
    -d '{"estimated_time":8,"remaining_time":4,"actual_time":2}'
)" )

# ---------------------------------------------------------------------------
# 9. GET /api/sync/history
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/sync/history" GET \
    "$BASE_URL/api/sync/history"
)" )

# ---------------------------------------------------------------------------
# 10. POST /api/sync/trigger  (admin)
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "POST /api/sync/trigger  (admin)" POST \
    "$BASE_URL/api/sync/trigger"
)" )

# ---------------------------------------------------------------------------
# 11. GET /api/admin/users  (admin)
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "GET  /api/admin/users  (admin)" GET \
    "$BASE_URL/api/admin/users"
)" )

# ---------------------------------------------------------------------------
# 12. POST /api/admin/users  (admin)
# ---------------------------------------------------------------------------
BENCH_USER_EMAIL="bench_$(date +%s)@example.com"
RESULTS+=( "$(bench_endpoint \
    "POST /api/admin/users  (admin)" POST \
    "$BASE_URL/api/admin/users" \
    -H 'Content-Type: application/json' \
    -d "{\"email\":\"$BENCH_USER_EMAIL\",\"display_name\":\"Bench User\",\"password\":\"Bench1234!\",\"role\":\"member\"}"
)" )

# ---------------------------------------------------------------------------
# 13. POST /api/auth/logout
# ---------------------------------------------------------------------------
RESULTS+=( "$(bench_endpoint \
    "POST /api/auth/logout" POST \
    "$BASE_URL/api/auth/logout"
)" )

# ---------------------------------------------------------------------------
# Build markdown table and append to results.md
# ---------------------------------------------------------------------------

TIMESTAMP="$(run_stamp)"

{
    echo ""
    echo "---"
    echo ""
    echo "## Benchmark Run — $TIMESTAMP"
    echo ""
    echo "**Environment**: \`$BASE_URL\` | **Runs**: $RUNS | **Warmup**: $WARMUP"
    echo ""
    echo "| Endpoint | Method | Min (ms) | Avg (ms) | Max (ms) | ≤ 200 ms? |"
    echo "|----------|--------|----------|----------|----------|-----------|"

    PASS=0
    FAIL=0
    for result_line in "${RESULTS[@]}"; do
        IFS='|' read -r ep_label ep_method ep_min ep_avg ep_max <<< "$result_line"
        # Check if avg ≤ 200
        ok=$(awk -v avg="$ep_avg" 'BEGIN { print (avg <= 200) ? "✅" : "❌" }')
        if [[ "$ok" == "✅" ]]; then
            ((PASS++)) || true
        else
            ((FAIL++)) || true
        fi
        echo "| \`$ep_label\` | $ep_method | $ep_min | $ep_avg | $ep_max | $ok |"
    done

    echo ""
    echo "**Summary**: $PASS / $((PASS + FAIL)) endpoints meet the < 200 ms target."
    echo ""
} >> "$RESULTS_FILE"

echo ""
echo "============================================================"
echo " Results appended to: $RESULTS_FILE"
echo " PASS: $PASS  FAIL: $FAIL"
echo "============================================================"
