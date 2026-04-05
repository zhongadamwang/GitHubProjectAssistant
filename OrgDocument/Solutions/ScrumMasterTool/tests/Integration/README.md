# Integration Tests — README

## Running Tests

### Fixture mode (CI-safe, no network required)

```bash
cd OrgDocument/Solutions/ScrumMasterTool
composer test
```

All Phase 1 and Phase 2 tests run against the local MySQL test database using
pre-recorded fixture data. No GitHub PAT is required.

### Running only Phase 2 sync tests

```bash
composer test -- --testsuite "Phase2 Integration"
```

---

## Live-mode test (manual, network required)

The live-mode test (`testLiveSyncSkippedByDefault`) is **skipped by default**.
To execute it against a real GitHub Projects v2 project:

### Prerequisites

1. A GitHub Personal Access Token (PAT) with scopes:
   - `read:project` — read Projects v2 data
   - `repo` — read issues from private repositories (optional for public)

2. A GitHub Projects v2 project owned by a user (not an organisation) with at
   least one issue added to the project board.

3. `.env.test` configured with:

```dotenv
# Existing DB settings
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=scrum_test
DB_USER=root
DB_PASS=

# Live test enablement flag
GITHUB_INTEGRATION_TEST=true

# GitHub credentials — replace with real values
GITHUB_PAT=ghp_your_real_token_here
GITHUB_ORG=your-github-login
GITHUB_PROJECT_NUMBER=1
```

### Running the live test

```bash
GITHUB_INTEGRATION_TEST=true composer test -- --testsuite "Phase2 Integration" --filter testLiveSyncSkippedByDefault
```

> **Security note**: never commit your real PAT. Use `.env.test` which is
> listed in `.gitignore`. Verify with `git status` before committing.

---

## Test database setup

The test bootstrap (`tests/bootstrap.php`) creates the test database and runs
all migrations automatically. Ensure your MySQL server is running and the
credentials in `.env.test` have `CREATE DATABASE` privileges.

```bash
# Verify DB connectivity before running tests
php -r "
  require 'vendor/autoload.php';
  \$d = Dotenv\Dotenv::createImmutable('.', '.env.test');
  \$d->load();
  \$pdo = new PDO('mysql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'], \$_ENV['DB_USER'], \$_ENV['DB_PASS']);
  echo 'DB connected OK' . PHP_EOL;
"
```
