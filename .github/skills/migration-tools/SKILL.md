---
name: migration-tools
description: "Migrate Project 1 flat collaboration diagrams to the Project 3 hierarchical format with boundary groupings and participant-type annotations. Supports non-destructive batch or individual migration: reads collaboration-diagrams.md and collaboration-diagrams.json produced by diagram-generatecollaboration (Project 1 mode), applies boundary-detection heuristics and stereotype-classification rules from diagram-generatecollaboration, and writes an enhanced counterpart file (collaboration-diagrams-enhanced.md / collaboration-diagrams-enhanced.json) alongside the originals. All existing requirement-traceability links and message sequences are preserved. Use when a user wants to upgrade legacy flat diagrams, batch-migrate all Project 1 diagrams, preview migration changes before applying, or add participant annotations to existing diagrams."
license: MIT
---

# Migration Tools

Convert existing flat (Project 1 style) collaboration diagrams into hierarchical, boundary-annotated (Project 3 style) collaboration diagrams, preserving 100 % of requirement traceability and message-flow content.

## Intent

Non-destructively upgrade pre-Project 3 flat `sequenceDiagram` EDPS collaboration diagrams to hierarchical, boundary-annotated format. Applies stereotype-classification rules from `diagram-generatecollaboration`, adds box boundaries, and annotates participant types — without modifying the original files.

> **Deprecation notice (see C1)**: The `migrate` operation has been absorbed into `hierarchy-management --op migrate`. Prefer calling `hierarchy-management` directly. This skill is retained for backward compatibility during the transition period.

## Core Function

**Purpose**: Non-destructive upgrade of flat sequenceDiagrams to hierarchical boundary-box format  
**Input**: `collaboration-diagrams.md` and/or `collaboration-diagrams.json` produced by any pre-Project 3 invocation of `diagram-generatecollaboration`  
**Output**: `collaboration-diagrams-enhanced.md`, `collaboration-diagrams-enhanced.json`, and `migration-log.md`  
**Integration**: Downstream of `diagram-generatecollaboration`; upstream of `hierarchy-management` and `documentation-automation`

## Inputs

| Artifact | Required | Notes |
|----------|----------|-------|
| `[project-folder]/artifacts/Analysis/collaboration-diagrams.md` | Yes (batch) or one of the two | Human-readable collaboration diagram file from Project 1 |
| `[project-folder]/artifacts/Analysis/collaboration-diagrams.json` | Yes (batch) or one of the two | Machine-readable collaboration diagram file from Project 1 |
| `--mode` flag | No | `preview` (default) or `apply`; preview shows changes without writing files |
| `--scope` flag | No | `batch` (default, all diagrams) or `diagram=<diagram-id>` (single diagram) |

## Outputs

| Artifact | Description |
|----------|-------------|
| `[project-folder]/artifacts/Analysis/collaboration-diagrams-enhanced.md` | Enhanced markdown: original diagrams unchanged; new enhanced variants appended with `*(Enhanced — Diagram <id>-E)*` suffix |
| `[project-folder]/artifacts/Analysis/collaboration-diagrams-enhanced.json` | Enhanced JSON: each original diagram object duplicated as an `enhanced` sibling with added `boundaries`, `participant_types`, and `migration_metadata` fields |
| `[project-folder]/artifacts/Analysis/migration-log.md` | Migration record: per-diagram change summary, boundary suggestions, type inferences, and any warnings |

**Non-destructive guarantee**: Original `collaboration-diagrams.md` and `collaboration-diagrams.json` are **never modified**. All additions are written to the `*-enhanced.*` counterparts.

---

## Migration Workflow

### Step 1 — Load Source Diagrams

1a. If `collaboration-diagrams.json` is present, parse it as primary source (authoritative participant list, requirement refs).  
1b. If only `collaboration-diagrams.md` is present, parse the Mermaid `sequenceDiagram` blocks textually:
  - Extract `participant <Alias> as <Label>` declarations.
  - Extract `**Source Requirements**: [R-xxx], [R-yyy]` lines.
  - Extract `**Entities Involved**: ...` lines.
  - Extract all message lines (`->>`, `-->>`, `-x`, `--x`, `-)`, `--)`) preserving order and alt/loop blocks.

1c. Record per-diagram:
  ```json
  {
    "diagram_id": "D-001",
    "title": "AI Skills Invocation Flow",
    "source_requirements": ["R-019", "R-021", "R-023"],
    "entities_involved": ["DeveloperTeamMember", "VSCodeWorkspace", "AIAgentSkill"],
    "participants": [
      { "alias": "Dev", "label": "DeveloperTeamMember" },
      { "alias": "VSC", "label": "VSCodeWorkspace" }
    ],
    "messages": [ ... ]
  }
  ```

---

### Step 2 — Participant Stereotype Inference

Apply the same inference rules as `diagram-generatecollaboration` §Participant Stereotype Classification. Evaluate in order; use the **first matching rule**:

| Priority | Rule | Inferred type |
|----------|------|---------------|
| 1 | Label contains `User`, `Developer`, `Operator`, `Client`, `Owner`, `Human`, `Person`, or `Team` | `actor` |
| 2 | Label contains `API`, `Gateway`, `Interface`, `Service` (AND does not contain `DB`, `Store`, `Repository`, `Cache`) | `boundary` |
| 3 | Label contains `DB`, `Database`, `Store`, `Repository`, `Cache`, `Registry`, `Queue`, `Log`, `Report`, `Artifact`, `Artifacts`, `Output`, `Document`, `Storage`, `Journal` | `entity` |
| 4 | Label contains `Manager`, `Controller`, `Engine`, `Processor`, `Coordinator`, `Orchestrator`, `Validator`, `Analyzer` | `control` |
| 5 | Label contains `Framework`, `Workspace`, `Environment`, `Platform`, `System` (not classifiable above) | `control` |
| 6 | No rule matched | `control` (fallback) — flag as `INFERRED_FALLBACK` in migration log |

Record confidence level for each:
- `HIGH`: Rules 1–3 (distinct label keywords)
- `MEDIUM`: Rules 4–5 (structural keywords, some ambiguity)
- `LOW`: Rule 6 (fallback) — note in migration log; human review recommended

---

### Step 3 — Boundary Grouping Detection

Identify participant clusters that should share a `box` boundary in the enhanced diagram.

#### 3a. Actor Exclusion
Participants inferred as `actor` type are placed **outside all boundary boxes** (they interact with the system; they are never enclosed).

#### 3b. Automatic Boundary Clustering Rules

Apply in order:

1. **Service Boundary**: Group all `control` and `entity` participants whose labels share a common prefix word (e.g., `AuthService`, `AuthDB` → `Auth Boundary`).
2. **Colocation Heuristic**: If ≥ 2 non-actor participants send and receive messages **only among themselves** (a closed subgraph), group them into a single boundary.
3. **Single-Entity Boundary**: Any `entity` participant that exclusively receives messages from a single `control` participant is co-located in the same boundary as that `control`.
4. **Ungrouped Fallback**: Any non-actor participant not captured by rules 1–3 gets a singleton boundary named `"<Label> System"`.

#### 3c. Boundary Naming Convention
- Boundary name: shared prefix word + `" Boundary"` (e.g., `"Auth Boundary"`) or colocation grouping label.
- Maximum 8 participants per boundary. If a candidate group exceeds 8, split by rule 2 (closed subgraph) first, then alphabetically.

#### 3d. Box Color Assignment
Use the palette from `diagram-generatecollaboration` §Box Syntax Generation:

| Boundary index | Color |
|----------------|-------|
| 1 | `rgb(230,240,255)` — blue tint |
| 2 | `rgb(230,255,230)` — green tint |
| 3 | `rgb(255,240,220)` — orange tint |
| 4 | `rgb(245,230,255)` — purple tint |
| 5+ | `rgb(240,240,240)` — neutral grey |

---

### Step 4 — Build Enhanced Diagram Block

For each source diagram, produce an enhanced `sequenceDiagram` block:

```
### <Original Title> — Enhanced *(Diagram <id>-E)*
**Source Requirements**: <preserved from original>
**Entities Involved**: <preserved from original>
**Migration**: Enhanced from Diagram <id> on <ISO-8601 date>

```mermaid
sequenceDiagram
    %% BOUNDARY SUMMARY
    %% Boundary 1: <BoundaryName> — participants: <Alias1>, <Alias2>
    %% Boundary 2: <BoundaryName> — participants: ...

    box rgb(nnn,nnn,nnn) <BoundaryName>
        participant <Alias1> @{ "type": "<type>", "label": "<Label1>" }
        participant <Alias2> @{ "type": "<type>", "label": "<Label2>" }
    end
    participant <ActorAlias> @{ "type": "actor", "label": "<ActorLabel>" }

    %% --- messages preserved verbatim from original ---
    <all original message lines, alt/loop/note blocks unchanged>
```
```

**Preservation rules**:
- Every message line from the original must appear in the enhanced version unchanged.
- `Note over`, `Note left of`, `Note right of` blocks preserved verbatim.
- `alt/else/end`, `loop/end`, `opt/end`, `par/and/end`, `critical/option/end` blocks preserved verbatim.
- Only the `participant` declarations change (reordered by boundary grouping, annotations added).

---

### Step 5 — Preserve Requirement Traceability

For every `**Source Requirements**: [R-xxx]` line found in the source, copy it verbatim into:
- The enhanced markdown block header (Step 4).
- The `source_requirements` array in the enhanced JSON (Step 6).

No requirement reference may be omitted, reworded, or reformatted. If the source uses `[R-xxx]` notation, that exact notation is preserved.

---

### Step 6 — Generate Enhanced JSON

For each diagram, create an enhanced JSON object alongside the original:

```json
{
  "diagram_id": "D-001-E",
  "migrated_from": "D-001",
  "title": "AI Skills Invocation Flow — Enhanced",
  "source_requirements": ["R-019", "R-021", "R-023"],
  "entities_involved": ["DeveloperTeamMember", "VSCodeWorkspace", "AIAgentSkill"],
  "participants": [
    {
      "alias": "Dev",
      "label": "DeveloperTeamMember",
      "type": "actor",
      "boundary": null,
      "inference_confidence": "HIGH",
      "inference_rule": 1
    },
    {
      "alias": "VSC",
      "label": "VSCodeWorkspace",
      "type": "control",
      "boundary": "Workspace Boundary",
      "inference_confidence": "MEDIUM",
      "inference_rule": 5
    }
  ],
  "boundaries": [
    {
      "name": "Workspace Boundary",
      "color": "rgb(230,240,255)",
      "participants": ["VSC", "Skill"]
    }
  ],
  "migration_metadata": {
    "migrated_at": "<ISO-8601>",
    "migration_tool_version": "1.0.0",
    "mode": "apply",
    "fallback_participants": [],
    "boundary_count": 1,
    "actor_count": 1,
    "warnings": []
  },
  "messages": [ "...all original message objects preserved..." ]
}
```

---

### Step 7 — Generate Migration Log

Write `migration-log.md` with the following sections:

```markdown
# Migration Log

**Generated**: <ISO-8601>
**Source**: collaboration-diagrams.md / collaboration-diagrams.json
**Mode**: preview | apply
**Scope**: batch | diagram=<id>

## Summary

| Metric | Value |
|--------|-------|
| Diagrams processed | N |
| Participants annotated | N |
| Boundaries added | N |
| Fallback (LOW confidence) participants | N |
| Warnings | N |

## Per-Diagram Results

### D-001: AI Skills Invocation Flow
- **Participants** (5 total): Dev → actor (HIGH), VSC → control (MEDIUM), Skill → control (MEDIUM), Output → entity (HIGH), Report → entity (HIGH)
- **Boundaries** (1): Workspace Boundary [VSC, Skill, Output]
- **Requirement links preserved**: R-019, R-021, R-023
- **Warnings**: none

...
```

Append any `INFERRED_FALLBACK` (LOW confidence) participant notices at the end under a `## Human Review Required` section, with the participant alias, label, the diagram it appears in, and a suggested type.

---

## Preview Mode

When `--mode preview` (default):
- Run Steps 1–7 in full.
- Print the migration log to the chat window / stdout.
- Do **not** write `collaboration-diagrams-enhanced.md`, `collaboration-diagrams-enhanced.json`, or `migration-log.md` to disk.
- Show the user a summary: "N diagrams ready for migration. N participants require human review. Run with `--mode apply` to write files."

When `--mode apply`:
- Run Steps 1–7 in full.
- Write all three output files.
- Print a brief confirmation: "Migration complete. Files written: collaboration-diagrams-enhanced.md, collaboration-diagrams-enhanced.json, migration-log.md."

---

## Batch vs Individual Scope

**`--scope batch`** (default): Migrate all diagrams found in the source files in a single pass.

**`--scope diagram=<id>`**: Process only the diagram with the given `diagram_id` (e.g., `D-001`). Write or preview only that diagram's enhanced block. The `*-enhanced.*` files are created if they do not exist; if they already exist, only the targeted diagram's entry is updated (others preserved verbatim).

---

## Integration Points

| Skill | Relationship |
|-------|-------------|
| `diagram-generatecollaboration` | **Authoritative source** for stereotype inference rules and box syntax. Migration-tools delegates classification logic to this skill's §Participant Stereotype Classification and §Box Syntax Generation sections. |
| `hierarchy-management` | Run after migration to decompose any `control`-type participant in the enhanced diagram into a sub-process hierarchy. |
| `documentation-automation` | Run after `hierarchy-management` to generate `main.md`, `process.md`, and fully annotated `collaboration.md` for each decomposed level. |
| `edps-compliance` | Run after migration to validate the enhanced diagram against VR-1–VR-4 boundary rules. |
| `hierarchy-validation` | Run after `hierarchy-management` decomposition to validate structural integrity. |

---

## Error Handling

| Error | Code | Resolution |
|-------|------|------------|
| Source file not found | `ERR_NO_SOURCE` | Provide path to collaboration-diagrams.md or collaboration-diagrams.json |
| Diagram ID not found (individual scope) | `ERR_DIAGRAM_NOT_FOUND` | Check diagram ID against source; use `--scope batch` to migrate all |
| All participants of a diagram are `actor` type | `ERR_NO_BOUNDARY_CANDIDATES` | Review participation list; at least one non-actor participant required for boundary generation |
| Enhanced file already exists and `--scope diagram=<id>` would overwrite other diagrams | `WARN_PARTIAL_OVERWRITE` | Non-blocking; only the targeted diagram section is updated |
| > 50 % of participants in a diagram are LOW confidence | `WARN_LOW_CONFIDENCE` | Preview shows warning and lists participants needing review before `apply` |

---

## Validation Checklist (Post-Migration)

After `--mode apply` completes, verify:
1. `collaboration-diagrams.md` and `collaboration-diagrams.json` are **unchanged** (bit-for-bit).
2. Every `**Source Requirements**: [R-xxx]` reference from the original appears in the corresponding enhanced block.
3. No message lines are missing, reordered, or altered in content.
4. Every non-actor participant belongs to exactly one boundary box.
5. The `migration-log.md` lists every diagram processed and shows zero omitted requirement links.
6. If any LOW-confidence participants were noted, they are listed in the `## Human Review Required` section of the log.

---

## Usage Examples

### Batch Preview (default)
```
Use migration-tools to preview migration of all Project 1 diagrams.
Source: OrgDocument/projects/01 - Building Skills/artifacts/Analysis/
```

### Batch Apply
```
Use migration-tools --mode apply to migrate all Project 1 diagrams.
Source: OrgDocument/projects/01 - Building Skills/artifacts/Analysis/
```

### Individual Diagram Enhancement
```
Use migration-tools --mode apply --scope diagram=D-003 to enhance only diagram D-003.
Source: OrgDocument/projects/01 - Building Skills/artifacts/Analysis/
```

---

**Version**: 1.0.0  
**Last Updated**: 2026-03-15  
**Compatibility**: EDPS v1.x, Project 3 boundary-diagram format  
**Depends On**: diagram-generatecollaboration (stereotype + box syntax rules)  
**Unblocks**: hierarchy-management, documentation-automation, edps-compliance

---

> **Migration path**: This skill is superseded by `hierarchy-management --op migrate` (see [hierarchy-management SKILL.md](../hierarchy-management/SKILL.md#migrate-operation-mode--op-migrate)). This skill file is retained for backward compatibility. New projects should use `hierarchy-management --op migrate` directly.
