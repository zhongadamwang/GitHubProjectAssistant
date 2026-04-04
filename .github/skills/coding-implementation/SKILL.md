---
name: coding-implementation
description: Implement development tasks from task files in the Scrum Master Assistant project (PRJ-01). Reads the task file for acceptance criteria and subtasks, reads technical-architecture.md for design decisions, creates all required source files, and updates the task status to Completed. Use when a user says "implement task T###" or "build task T###".
---

# Coding Implementation

## Intent

Implement a development task end-to-end: read the task spec, create all required files following the technical architecture, and update task tracking.

## Inputs

- **Task ID** (e.g., `T001`) — resolves to a file in `OrgDocument/projects/01 - Scrum master assistant/tasks/`
- **Task file** must declare a **Target Solution** (e.g., `ScrumMasterTool`) — maps to `OrgDocument/Solutions/<SolutionName>/`
- **Solution architecture**: `OrgDocument/Solutions/<SolutionName>/technical-architecture.md`
- **Org architecture**: `OrgDocument/orgModel/technical-architecture.md`
- **Task tracking**: `OrgDocument/projects/01 - Scrum master assistant/tasks/task-tracking.md`

## Outputs

- Source files created under `OrgDocument/Solutions/<SolutionName>/` per the task spec
- Task file updated: checklist items checked, status set to `Completed`, progress update added
- `task-tracking.md` table row updated: status → `Completed`, completed date added to the Completed table

## Workflow

### Step 1 — Read task spec
Read the task file. Extract:
- Acceptance criteria checklist
- Subtasks list
- Dependencies
- Tech notes / source requirements

### Step 2 — Read architecture (if needed)
For backed tasks, read the **solution-level** `technical-architecture.md` at `OrgDocument/Solutions/<SolutionName>/technical-architecture.md` to confirm naming, method signatures, and endpoint paths. Read the **org-level** `OrgDocument/orgModel/technical-architecture.md` for cross-cutting security and deployment standards.

### Step 3 — Create files
Create all files under `OrgDocument/Solutions/<SolutionName>/` per the task's subtask list. Follow these project conventions:

**PHP Backend** (`OrgDocument/Solutions/<SolutionName>/`):
- PSR-4 namespace: `App\` mapped to `src/`
- Controllers extend no base class — inject dependencies via constructor
- Services and Repositories are plain PHP classes with typed properties
- Always use `declare(strict_types=1);`
- Passwords: `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])` / `password_verify()`
- Sessions: `$_SESSION` guarded by `AuthMiddleware`
- PDO: prepared statements only — never string interpolation in queries
- `.env` loaded via `vlucas/phpdotenv`

**Directory layout** (ScrumMasterTool — from ADR-1):
```
OrgDocument/Solutions/ScrumMasterTool/
├── composer.json
├── .env.example
├── .gitignore
├── technical-architecture.md
├── public/
│   └── index.php          ← Slim app entry point
├── config/
│   └── app.php            ← DI container / middleware registration
├── src/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── GraphQL/
│   └── Middleware/
├── database/
│   ├── migrations/
│   └── seeds/
└── data/
    └── snapshots/
```

### Step 4 — Update task file
- Check all completed checklist items with `[x]`
- Set `**Status**: Completed`
- Set `**Last Updated**` to today's date
- Add a progress update entry under `### Progress Updates`

### Step 5 — Update task-tracking.md
- Move the task row from the Active Tasks table to the Completed Tasks table
- Add today's date in the Completed Date column

## Security Rules
- No SQL string interpolation — PDO prepared statements only
- Passwords always bcrypt (cost ≥ 12)
- Session cookies: `httpOnly` + `secure` flags
- `.env` never committed — covered by `.gitignore`
- GitHub PAT stored only in `.env`, never hardcoded
