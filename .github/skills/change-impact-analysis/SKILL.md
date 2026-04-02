---
name: change-impact-analysis
description: Trace how changes at one EDPS hierarchy level propagate to parent and child levels. Given a boundary restructuring, participant rename, or requirement modification, this skill identifies all affected artifacts across the full hierarchy tree, generates a prioritised impact report, and supports what-if mode so proposed changes can be evaluated without being applied. Use when a user wants to understand the blast radius of a hierarchy or requirement change, trace a requirement to all implementing artifacts, or run a pre-flight check before executing a hierarchy modification.
---

# Change Impact Analysis

Identify and report all artifacts affected when a change is made at any level of an EDPS process hierarchy.

## Intent

Trace and enumerate all artifacts impacted by a boundary rename, boundary restructure, participant change, or requirement modification at any EDPS hierarchy level. Supports `--mode what-if` (safe preview, no writes) and `--mode apply` (auto-fix navigational links). Emits `change-impact-report.json` with a `normalized_risk_level` field directly consumable by `change-management`.

## Inputs

- **Change type**: one of:
  - `boundary-rename` — a boundary folder or its corresponding participant label is renamed
  - `boundary-restructure` — a boundary is moved, split, merged, or deleted
  - `participant-change` — a participant is renamed, retyped, or removed within a `collaboration.md`
  - `requirement-change` — a requirement (e.g., R-302) is modified or removed
- **Target**: one of:
  - Folder path for boundary changes (e.g., `orgModel/01 - Skill Development Process/01-OrderBoundary/`)
  - Participant name for participant changes
  - Requirement ID for requirement changes (e.g., `R-302`)
- **Root folder**: root of the hierarchy to scan (e.g., `orgModel/01 - Skill Development Process/`)
- **Optional**: `--mode what-if` — enumerate all impacts without modifying any files (default: `what-if`)
- **Optional**: `--mode apply` — apply safe navigational fixes automatically (path updates, breadcrumb rebuilds) after reporting impact; structural edits always require human review
- **Optional**: `--depth [N]` — limit upward/downward traversal to N levels from the change point (default: unlimited)

## Outputs

- `[root-folder]/artifacts/Testing/change-impact-report.md` — human-readable impact report with prioritised artifact list
- `[root-folder]/artifacts/Testing/change-impact-report.json` — machine-readable results for downstream skill consumption
- Console summary: change point, total affected artifacts, blocking impacts, and next recommended action

---

## Risk Level Mapping

The `change-impact-analysis` skill uses a 5-level internal severity scale. When outputs are consumed by `change-management`, use the `normalized_risk_level` field (added to every artifact entry in `change-impact-report.json`) which maps to `change-management`'s 3-level `risk_level` scale.

| Internal `severity` | `normalized_risk_level` (change-management compatible) | `critical_flag` | Rationale |
|---|---|---|---|
| `NONE` | `Low` | `false` | No impact — maps to minimum risk |
| `LOW` | `Low` | `false` | Minor impact — within Low threshold |
| `MEDIUM` | `Medium` | `false` | Moderate impact — maps directly |
| `HIGH` | `High` | `false` | Significant impact — maps directly |
| `CRITICAL` | `High` | `true` | Exceeds change-management's scale — capped at High with `critical_flag: true` to preserve visibility |

**Example — CRITICAL entry with normalization:**

```json
{
  "rule_id": "CI-1",
  "rule_name": "Parent Reference Impact",
  "direction": "upward",
  "severity": "CRITICAL",
  "normalized_risk_level": "High",
  "critical_flag": true,
  "affected_file": "path/to/parent/main.md",
  "affected_level": 0,
  "impact_description": "Boundary restructure cascades to 12+ parent artifacts",
  "remediation": "Rebuild Sub-Processes table and all breadcrumbs in affected parents",
  "remediation_required": true,
  "auto_fixable": false
}
```

---

## Impact Rule Catalogue

### Group CI — Artifact-Level Impact Rules

| ID | Name | Direction | Severity | Description |
|----|------|-----------|----------|-------------|
| CI-1 | Parent Reference Impact | Upward (N→N-1) | HIGH | A boundary change at Level N invalidates the parent's `## Sub-Processes` table entry and any `%% Decomposition:` comments in the parent `collaboration.md`. |
| CI-2 | Child Navigation Impact | Downward (N→N+1…) | HIGH | A boundary change at Level N invalidates every child sub-process `**Parent Process**` link and every breadcrumb segment that passes through the changed folder. |
| CI-3 | Participant Propagation Impact | Downward (N→N+1) | MEDIUM | Renaming or retyping a `control`-type participant at Level N invalidates the matching external `actor` entry in the Level N+1 `collaboration.md` and the sub-folder name derived from it. |
| CI-4 | Hierarchy Index Impact | Global | MEDIUM | Any boundary change anywhere in the tree invalidates `hierarchy-index.md` at the root — specifically all rows referencing the changed folder path. |
| CI-5 | Side-Document Impact | Local | LOW | Changes to a `collaboration.md` may require corresponding updates to the sibling `process.md` and `domain-model.md` files if they reference the changed boundary or participant by name. |

### Group CR — Requirement Change Tracing Rules

| ID | Name | Scope | Severity | Description |
|----|------|-------|----------|-------------|
| CR-1 | Requirement-to-Artifact Mapping | Full tree | HIGH | Locate all `collaboration.md` and `main.md` files that contain the requirement ID as an inline reference (`[R-XXX]` annotation or `%% Requirement:` comment). Changes to the requirement potentially affect every such file. |
| CR-2 | Requirement Boundary Impact | Full tree | HIGH | For each artifact identified by CR-1, determine which `boundary`-type participants implement the requirement and report the constraint that will change. |
| CR-3 | Downstream Requirement Propagation | Downward | MEDIUM | When a requirement at Level N is modified, identify all descendant levels where derived sub-requirements or direct annotations reference the same root requirement ID, propagating to the full sub-tree. |

---

## Workflow

### Step 1 — Load Hierarchy Map

1. Read `[root-folder]/hierarchy-metadata.json` if present (accelerates traversal); otherwise walk the filesystem.
2. Build a **full parent-child map**: `{ folder → { level, parent, children: [...], files: [...] } }`.
3. Locate the **change point** within the map:
   - For boundary changes: find the folder entry whose path matches the target.
   - For participant changes: scan `collaboration.md` files for the participant name.
   - For requirement changes: scan all `collaboration.md` and `main.md` files for `[R-XXX]` annotations.
4. If the change point is not found, return:

```json
{
  "error": "change-point-not-found",
  "target": "[target]",
  "message": "No artifact in the hierarchy contains the specified change target.",
  "suggestion": "Verify the folder path, participant name, or requirement ID, and ensure the hierarchy root is correct."
}
```

### Step 2 — Apply Group CI Rules

#### CI-1 — Parent Reference Impact

For each folder at the change point or above (up to root or `--depth` limit):

1. Identify the **immediate parent folder** (one level up).
2. In the parent `main.md`, locate the `## Sub-Processes` table row whose link path contains the changed folder name.
3. **Record as HIGH impact** if:
   - The folder is renamed → the link path is now broken.
   - The folder is deleted → the row referencing it becomes a dangling link.
4. In the parent `collaboration.md`, scan for `%% [Dd]ecompos(ed|ition):\s*\S*[ChangedName]`.
5. **Record as HIGH impact** for each matching comment whose referenced path is now invalid.

#### CI-2 — Child Navigation Impact

For each folder **at or below** the change point (down to leaves or `--depth` limit):

1. Read `main.md` in each child folder.
2. Locate the `**Parent Process**` link — **record as HIGH impact** if the link path contains the changed folder name (boundary rename or move).
3. Parse the breadcrumb line: split on `›` and resolve each linked segment. **Record as HIGH impact** for each breadcrumb segment whose path traverses the changed folder.
4. Traverse recursively to all descendants — every level whose breadcrumb passes through the changed folder is affected.

#### CI-3 — Participant Propagation Impact

For participant-change or boundary-rename events:

1. Derive the expected child `collaboration.md` location: `[parent-folder]/[NN]-[ParticipantName]Boundary/collaboration.md`.
2. Read the child `collaboration.md`.
3. Find the external `actor`-type participant (outside `box … end`) whose label matches the old participant name.
4. **Record as MEDIUM impact** on the child `collaboration.md` (actor label must be updated).
5. If the participant is a `control` type being renamed, the **sub-folder name** itself is derived from the participant name — **record as HIGH impact** (folder rename required, triggering CI-1 and CI-2 cascades).

#### CI-4 — Hierarchy Index Impact

1. Check whether `[root-folder]/hierarchy-index.md` exists.
2. If it exists, search for all rows in the `## Full Hierarchy Tree` table that reference the changed folder path.
3. **Record as MEDIUM impact** for each affected row.
4. Note the full index regeneration as a single recommended remediation action.

#### CI-5 — Side-Document Impact

For each folder containing the change point:

1. Read `process.md` (if present) and search for the changed boundary folder name or participant label.
2. Read `domain-model.md` (if present) and search for the same strings.
3. **Record as LOW impact** for each file where the changed name appears.

### Step 3 — Apply Group CR Rules (requirement-change only)

#### CR-1 — Requirement-to-Artifact Mapping

1. Compile a regex from the requirement ID: `\[R-[0-9]+[a-z]?\]|\bR-[0-9]+[a-z]?\b`.
2. Walk every `collaboration.md` and `main.md` in the full hierarchy tree.
3. Collect all files where the regex matches.
4. **Record as HIGH impact** for each match, noting the line number and file path.

#### CR-2 — Requirement Boundary Impact

For each file identified in CR-1:

1. In `collaboration.md` files: identify all `boundary`-type participants whose `@{ "note": "..." }` or adjacent comments reference the requirement ID.
2. Determine which constraint the requirement imposes (e.g., "single-actor rule", "entry-point restriction").
3. **Record as HIGH impact** with the specific participant name and the affected constraint type.

#### CR-3 — Downstream Requirement Propagation

1. Starting from each folder identified in CR-1, walk all descendant sub-folders.
2. For each descendant, search `collaboration.md` and `main.md` for annotations that reference the same root requirement ID OR any derived sub-requirement ID that shares the same root (pattern: `R-[root-number][a-z]`).
3. **Record as MEDIUM impact** for each downstream artifact found.

### Step 4 — Score and Aggregate

```
total_affected = count of distinct files with at least one impact record

severity_counts:
  high_impacts    = count of FAIL on HIGH-severity rules across all records
  medium_impacts  = count of FAIL on MEDIUM-severity rules
  low_impacts     = count of FAIL on LOW-severity rules

overall_risk (first match wins):
  CRITICAL    → high_impacts ≥ 5  OR  change_type = boundary-restructure AND total_affected ≥ 10
  HIGH        → high_impacts ≥ 1
  MEDIUM      → medium_impacts ≥ 1 AND high_impacts = 0
  LOW         → only low_impacts present
  NONE        → total_affected = 0
```

### Step 5 — What-If vs Apply Mode

#### What-If Mode (default)

- Generate reports (Step 6) without modifying any files.
- Return a recommended action plan ordered by severity.
- Mark each impact record with `"remediation_required": true | false`.

#### Apply Mode (`--mode apply`)

Apply the following safe navigational fixes **only** after user confirmation. Log each fix.

| Rule | Auto-Fix |
|------|----------|
| CI-1 | Update the broken `## Sub-Processes` table link in the parent `main.md` to the new folder name |
| CI-2 | Update the `**Parent Process**` link and breadcrumb segments in every affected child `main.md` |
| CI-4 | Regenerate `hierarchy-index.md` at the root |

The following impacts are **never** auto-applied — they require human review:

| Rule | Reason |
|------|--------|
| CI-3 | Participant renames may affect diagram semantics and boundary definitions |
| CI-5 | `process.md` and `domain-model.md` edits require domain knowledge |
| CR-1 / CR-2 / CR-3 | Requirement change implications require stakeholder review |

### Step 6 — Generate Reports

#### Console Summary

```
Change Impact Analysis — [change type]: [target]
  Root:      [root folder path]
  Mode:      [what-if | apply]
  Depth:     [unlimited | N levels]

  Risk Level:         [NONE | LOW | MEDIUM | HIGH | CRITICAL]
  Total Affected:     [count] files
  High Impacts:       [count]
  Medium Impacts:     [count]
  Low Impacts:        [count]
  Auto-Fixed:         [count] (if --mode apply was used)

[If risk = CRITICAL or HIGH]
  ❌ High-impact changes detected — review required before proceeding.
[If risk = MEDIUM]
  ⚠  Medium-impact changes detected — review recommended.
[If risk = LOW]
  ℹ  Low-impact changes only — safe to proceed with caution.
[If risk = NONE]
  ✅ No affected artifacts found — change appears isolated.
```

#### JSON Report (`change-impact-report.json`)

```json
{
  "report_metadata": {
    "generated": "ISO8601 timestamp",
    "change_type": "boundary-rename | boundary-restructure | participant-change | requirement-change",
    "target": "path/to/folder | ParticipantName | R-XXX",
    "root_folder": "path/to/root",
    "mode": "what-if | apply",
    "depth_limit": null,
    "schema_version": "1.0"
  },
  "summary": {
    "overall_risk": "NONE | LOW | MEDIUM | HIGH | CRITICAL",
    "total_affected_files": 0,
    "high_impacts": 0,
    "medium_impacts": 0,
    "low_impacts": 0,
    "critical_count": 0,
    "auto_fixed": 0,
    "change_point_level": 0
  },
  "impact_records": [
    {
      "rule_id": "CI-1",
      "rule_name": "Parent Reference Impact",
      "direction": "upward",
      "severity": "HIGH",
      "normalized_risk_level": "High",
      "critical_flag": false,
      "affected_file": "path/to/parent/main.md",
      "affected_level": 0,
      "impact_description": "Sub-Processes table row for '01-OrderBoundary' contains a broken link after folder rename to '01-NewOrderBoundary'",
      "remediation": "Update the link in the ## Sub-Processes table from '../01-OrderBoundary/main.md' to '../01-NewOrderBoundary/main.md'",
      "remediation_required": true,
      "auto_fixable": true
    }
  ],
  "auto_fixes_applied": [],
  "recommended_action_plan": [
    {
      "priority": 1,
      "action": "Update parent Sub-Processes table link",
      "file": "path/to/parent/main.md",
      "rule": "CI-1"
    }
  ]
}
```

#### Markdown Report (`change-impact-report.md`)

```markdown
# Change Impact Report

**Generated**: [timestamp]
**Change Type**: [change type]
**Target**: [target path/name/ID]
**Root Folder**: [root folder]
**Mode**: [what-if | apply]
**Overall Risk**: [NONE ✅ | LOW ℹ️ | MEDIUM ⚠️ | HIGH ❌ | CRITICAL 🚨]

## Summary

| Metric | Value |
|--------|-------|
| Total Affected Files | [n] |
| High Impacts | [n] |
| Medium Impacts | [n] |
| Low Impacts | [n] |
| Change Point Level | [n] |
| Auto-Fixed | [n] |

## Critical Impacts 🚨

> This section is present only when one or more artifacts have `critical_flag: true` (i.e., `severity: CRITICAL`). CRITICAL impacts exceed `change-management`'s native risk scale and are capped at `normalized_risk_level: High` with `critical_flag: true` to prevent silent downgrading.

| Rule | Affected File | Level | Description |
|------|--------------|-------|-------------|
| [rule] | [path] | [n] | [description] |

---

## Impact Records

### ❌ HIGH Impacts — Immediate Action Required

| Rule | Affected File | Level | Description | Auto-Fixable |
|------|--------------|-------|-------------|--------------|
| CI-1 | [path/main.md](path/main.md) | N-1 | Sub-Processes link broken | Yes |
| CI-2 | [path/child/main.md](path/child/main.md) | N+1 | Parent link broken | Yes |

### ⚠️ MEDIUM Impacts — Review Recommended

| Rule | Affected File | Level | Description | Auto-Fixable |
|------|--------------|-------|-------------|--------------|
| CI-4 | [hierarchy-index.md](hierarchy-index.md) | Root | Index row outdated | Yes |

### ℹ️ LOW Impacts — Informational

| Rule | Affected File | Level | Description | Auto-Fixable |
|------|--------------|-------|-------------|--------------|
| CI-5 | [process.md](process.md) | N | References changed name | No |

## Detailed Remediations

### CI-1: Parent Reference Impact

**Affected File**: `path/to/parent/main.md`
**Level**: N-1
**Issue**: [specific description]
**Remediation**: [exact step-by-step fix]

---

## Recommended Action Plan

1. [Priority 1 action — rule, file, and exact change]
2. [Priority 2 action]
...

## Auto-Fixes Applied

[List of fixes applied in --mode apply, or "No auto-fixes applied (what-if mode)." if default mode]
```

---

## What-If Analysis

What-if mode (`--mode what-if`) is the default and is designed for pre-flight evaluation of proposed changes.

**Typical workflow**:
1. Invoke with the proposed target: `change-impact-analysis --mode what-if boundary-rename orgModel/01 - Skill Development Process/01-OrderBoundary/`
2. Review the generated `change-impact-report.md` to assess all impacts.
3. Decide whether to proceed, scope the change differently, or cancel.
4. If proceeding, run the hierarchy change, then execute `change-impact-analysis --mode apply` to repair navigational artifacts.
5. Run `hierarchy-validation` afterwards to confirm no residual issues.

**What-if guarantees**:
- No files are created, modified, or deleted.
- No `hierarchy-metadata.json` updates are written.
- Report files are the only output.

---

## Integration with Other Skills

- Run **before** `hierarchy-management` decompositions or rollbacks to understand downstream impact.
- Run **after** a hierarchy change in `--mode apply` to repair navigation links without running full `hierarchy-validation`.
- Feeds into `change-management` skill — the `affected_documents` entries in `change-impact-report.json` are compatible with `change-management` skill's `affected_documents` format using the `normalized_risk_level` field. Use `critical_flag: true` to identify CRITICAL impacts that exceed `change-management`'s native scale.
- Use `edps-compliance` for full EDPS rule validation after all impacts have been addressed.
- Use `hierarchy-validation` for a structural integrity check once navigational repairs are complete.
- Requirement change outputs (CR-1/CR-2/CR-3) can be forwarded to `process-findtopandupdate` to update top-level requirement documents.

---

## Remediation Reference

| Rule | Common Fix |
|------|-----------|
| CI-1 | Update the broken `## Sub-Processes` table link in the parent `main.md` to use the new folder name or remove the row if the boundary was deleted |
| CI-2 | Rebuild `**Parent Process**` links and breadcrumb trails in all child `main.md` files; use `hierarchy-management` link rebuild or `--mode apply` |
| CI-3 | Rename the external `actor` participant in the child `collaboration.md` to match the new parent participant label; update sub-folder name if derived from participant name |
| CI-4 | Regenerate `hierarchy-index.md` at the root using `hierarchy-management`, or use `--mode apply` to auto-update the index |
| CI-5 | Manually update `process.md` and `domain-model.md` to reflect the new boundary or participant name; review for semantic accuracy |
| CR-1 | Review all flagged `collaboration.md` and `main.md` files to determine whether the requirement change affects the modelled constraints |
| CR-2 | Update `boundary`-type participant annotations to reflect the changed requirement; validate boundary semantics remain correct |
| CR-3 | Trace all downstream requirement annotations; update or remove derived sub-requirement references as appropriate; coordinate with requirement owners |
