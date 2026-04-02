---
name: documentation-automation
description: Automate generation of process documentation at each hierarchy level following organizational standards. Auto-generates main.md (with hierarchy navigation and breadcrumb), process.md (level-appropriate activity diagram and description), collaboration.md (EDPS-compliant sequencing with box boundaries and participant stereotypes), and domain-model.md (entities scoped to the boundary). Use when a new process folder has been created (e.g., after hierarchy-management decomposition), when existing docs need to be regenerated from collaboration.md, or when a user asks to "document a process", "generate docs", "regenerate documentation", or "fill in the templates" for a hierarchy level.
---

# Documentation Automation

Generate rich, level-appropriate process documentation for every folder in an EDPS process hierarchy.

## Intent

Generate `main.md`, `process.md`, `collaboration.md`, and `domain-model.md` for every EDPS hierarchy node after decomposition events, or when regeneration from source `collaboration.md` is requested. Takes precedence over `orgmodel-update` for these four files in hierarchy-managed folders.

## Scope and Pipeline Position

**Trigger**: Hierarchy decomposition events — invoked after `hierarchy-management` creates a new process folder, or when existing docs need regeneration from `collaboration.md`.

**Primary input**: `[process-folder]/collaboration.md` (participant list, stereotypes, and message flows).

**Operates at**: Each individual EDPS hierarchy node folder (e.g., `orgModel/.../01-OrderServiceBoundary/`). Generates per-node process documentation.

### Relative to `orgmodel-update`

| Skill | Trigger | Primary Input | Owns |
|-------|---------|---------------|------|
| `documentation-automation` *(this skill)* | Hierarchy decomposition event | `[folder]/collaboration.md` | `main.md`, `process.md`, `collaboration.md`, `domain-model.md` at each EDPS hierarchy node |
| `orgmodel-update` | Project analysis outputs | `domain-alignment.json`, `domain-concepts.json` | `vocabulary.md`, `test-case-list.md` (always); shared files only in non-hierarchy-managed folders |

**Ordering rule**: Within a single process:
- `documentation-automation` always runs **after** `hierarchy-management` decomposition.
- `orgmodel-update` runs from project analysis outputs. If both skills target the same folder, **`documentation-automation` takes precedence** for the four shared files. `orgmodel-update` detects this via `hierarchy-metadata.json` and writes to `pending-orgmodel-updates.md` instead of overwriting.
- `documentation-automation` does **not** generate `vocabulary.md` or `test-case-list.md`; those are owned by `orgmodel-update`.

## Inputs

- **Primary**: `[process-folder]/collaboration.md` — participant list, stereotypes, and message flows
- **Hierarchy context**: `[process-folder]/hierarchy-metadata.json` (if present) or folder-path depth
- **Parent context**: `../collaboration.md` — message exchanges involving the decomposed participant
- **Optional**: `[process-folder]/domain-concepts.json` — for additional entity attributes

## Outputs

Four files written or overwritten in `[process-folder]/`:

| File | Purpose |
|------|---------|
| `main.md` | Business overview, navigation links, decomposition status |
| `process.md` | Level-appropriate activity diagram + step descriptions |
| `collaboration.md` | EDPS-compliant sequence diagram with box syntax and stereotypes |
| `domain-model.md` | Class diagram scoped to this boundary's participants |

## Workflow

### 1. Determine Hierarchy Level and Process Name

1. If `hierarchy-metadata.json` exists, read `level` and `process_name` from the current node entry.
2. Otherwise, count the number of `[NN]-[Name]Boundary/` ancestor folders from the root:
   - 0 ancestors = Level 0
   - 1 ancestor = Level 1, etc.
3. Derive the process name from the folder name: strip the ordinal prefix and `Boundary` suffix, then insert spaces at PascalCase boundaries.
   - `01-OrderServiceBoundary` → `Order Service`
   - `02-ValidationEngineBoundary` → `Validation Engine`
   - Acronym rule: treat a run of consecutive uppercase letters as a single word unit (insert space only before the run, not between letters). Example: `03-EDPSSkillNavigatorBoundary` → `EDPS Skill Navigator`.
4. Identify parent process name: the folder name one level up (applying the same stripping rule), or `[Root Process]` for Level 0.

### 2. Extract Participant Inventory from `collaboration.md`

Parse the `sequenceDiagram` block:

1. For each `participant [Alias]@{ "type": "...", "label": "..." }` declaration, record:
   - `alias`, `type` (actor | boundary | control | entity), `label`
   - `box_name`: the `box [Name]` block the participant belongs to (empty string if outside all boxes)
2. Build a message list: each `->>`, `-->>`, `-x` arrow becomes `{ from, to, label, async: bool }`.
3. For each participant, calculate `involvement_count` = number of messages where it is sender or receiver.

### 2b. Content Guard Pre-Check

Before writing any of the four files (`main.md`, `process.md`, `collaboration.md`, `domain-model.md`), evaluate each file's current state:

**Stub detection**: A file is classified as a **stub** if it contains the exact substring `[TO BE GENERATED - invoke documentation-automation]` anywhere in its content.

**Non-stub content threshold (FR-T20.5)**: A file is considered to contain non-stub content if it has more than **10 non-placeholder, non-header lines** of substantive text or diagram code. A line is excluded from this count if it:
- Is blank
- Begins with `#` (Markdown heading)
- Contains `[TO BE GENERATED`
- Is a front-matter key/value line (e.g., `**Level**: [N]`)
- Contains only Mermaid comment markers (`%%`) or opening/closing code-block fences

**Decision matrix for each file:**

| File state | Action |
|------------|--------|
| File does not exist | Proceed with generation (no guard) |
| File exists and is a stub (contains `[TO BE GENERATED - invoke documentation-automation]`) | Proceed with generation — replace stub |
| File exists and non-stub line count ≤ 10 | Proceed with generation |
| File exists and non-stub line count > 10 | Prompt user: **"[filename] already contains generated content. Overwrite? (y/N)"** |

If the user responds `y` → overwrite. If the user responds `N` or provides no input → skip that file and preserve its content.

**`--force` flag**: When `documentation-automation` is invoked with `--force`, skip all Content Guard prompts and overwrite all files unconditionally. Use this flag in CI/automated pipelines where interactive prompts are unavailable.

### 3. Generate `main.md`

Use the **Level Content Guide** (§Level Content Guide) to calibrate description depth.

```markdown
# [ProcessName] [Boundary|Process]

**Level**: [N]  
**Parent Process**: [[ParentProcessName]](../main.md)  
**Status**: [Active | Decomposed | Draft]  
**Decomposed From**: `[DecomposedParticipantLabel]` (type: control)  <!-- omit for Level 0 -->

## Navigation

**Breadcrumb**: [build from root per breadcrumb rules in hierarchy-management §4 Breadcrumb Construction Rules]

**Parent Process**: [[ParentProcessName]](../main.md)  <!-- omit for Level 0 -->

**Sub-Processes**: [table per hierarchy-management §5 if sub-folders exist; else placeholder text]

## Overview

[Level-appropriate description — see §Level Content Guide]

## Participant Summary

| Participant | Type | Role |
|-------------|------|------|
| [Label] | [type] | [one-phrase role inferred from message labels] |

## Boundary Rules Applied

| Rule | Status |
|------|--------|
| VR-1: Single External Interface | [✓ Compliant / ⚠ Review] |
| VR-2: Boundary-First Reception | [✓ Compliant / ⚠ Review] |
| VR-3: Control-Only Decomposition | [✓ Compliant / N/A] |

## Decomposition Status

| Participant | Type | Decomposition Status |
|-------------|------|---------------------|
| [ControlLabel] | control | [Available / Decomposed → link] |

## Key Documents

- [Collaboration Diagram](collaboration.md)
- [Process Flow](process.md)
- [Domain Model](domain-model.md)

## Related Changes
<!-- List changes that have impacted this level -->
```

**Boundary rule evaluation:**
- **VR-1 compliant**: only one boundary-type participant exists in the primary box, or there is exactly one actor interacting with the box from outside.
- **VR-2 compliant**: the first message arrow inside the box (from any external participant) targets the boundary-type participant.
- **VR-3 compliant**: only control-type participants are listed in `decomposition_candidates`.

### 4. Generate `process.md`

Use the message sequence in `collaboration.md` to infer activity steps. Map each send-to-boundary → process-call → store interaction triplet to an activity block.

```markdown
<!-- Identifier: P-[NN] -->

# [ProcessName] — Process Flow

**Parent Process**: [[ParentProcessName]](../process.md)  <!-- omit at Level 0 -->
**Hierarchy Level**: [N]

```mermaid
flowchart TD
    [STEPS inferred from collaboration.md — see §Flowchart Inference Rules]
```

## Process Description

### 1. [Step derived from first message group]
[Description]

### 2. [Step derived from second message group]
[Description]

...

## Boundary Rules Applied

- **VR-1** (Single External Interface): All external requests enter through `[BoundaryLabel]`
- **VR-2** (Boundary-First Reception): `[BoundaryLabel]` is the first recipient of every inbound message

## Error Handling

- **Invalid Request**: `[BoundaryLabel]` returns error to caller before engaging control participants
- **Processing Failure**: `[ControlLabel]` rolls back and signals `[BoundaryLabel]` to return failure response
```

#### Flowchart Inference Rules

1. Group consecutive messages by sender/receiver pattern into logical "steps".
2. For each `loop` block in `collaboration.md`, generate a `subgraph` or decision loop in the flowchart.
3. For each `alt` / `else` block, generate a diamond decision node with two branches.
4. The entry node is always the first external actor sending to the boundary.
5. The terminal node is always the boundary returning to the external actor.

### 5. Generate `collaboration.md`

If `collaboration.md` does not yet exist (new sub-folder without a diagram), synthesize one from parent context:

1. Identify the parent participant that was decomposed (the one whose `status` → `decomposed` in parent's `hierarchy-metadata.json`).
2. Apply the **Level N+1 collaboration diagram template** from `hierarchy-management` §3 as the starting point.
3. Apply EDPS validation rules (VR-1, VR-2, VR-3) before writing.

If `collaboration.md` already exists, validate and annotate:

1. Add missing `@{ "type": "..." }` annotations for any participant lacking a stereotype (infer from hierarchy-management §Participant Stereotype Inference).
2. Add `%% BOUNDARY SUMMARY` comment block at the top of each `sequenceDiagram` block if absent:
   ```
   %% [B-N] [BoxName]
   %%         Participants: [alias] ([type]), ...
   %%         Decomposable: [control aliases]
   %%         External actor: [actor alias]
   ```
3. Ensure every `box` block uses the `box rgb(...) [Name]` syntax with level-appropriate color:
   - Level 1: `rgb(235, 245, 255)` (light blue)
   - Level 2: `rgb(235, 255, 240)` (light green)
   - Level 3: `rgb(255, 248, 225)` (light amber)
   - Level 4+: `rgb(243, 229, 245)` (light lavender)
4. Write the annotated file back.

### 6. Generate `domain-model.md`

```markdown
<!-- Identifier: D-[NN] -->

# [ProcessName] — Domain Model

**Parent Process**: [[ParentProcessName]](../domain-model.md)  <!-- omit at Level 0 -->
**Hierarchy Level**: [N]

## Domain Class Diagram

```mermaid
classDiagram
    [CLASS BLOCKS — one per participant, stereotype as CSS class]
    [RELATIONSHIP lines inferred from message labels]
```

## Key Domain Concepts

| Term | Type | Description |
|------|------|-------------|
| [Label] | [actor|boundary|control|entity] | [inferred from role in messages] |

## Relationships to Parent Domain  <!-- omit at Level 0 -->

- Inherits context from [[ParentProcessName] Domain Model](../domain-model.md)
- `[DecomposedParticipantLabel]` maps to the decomposed participant in the parent diagram
```

**Class diagram inference rules:**
- Each participant record → one `class [Alias]:::[type]` block.
- Infer 2–3 attributes from message labels (e.g., a message "Submit Order" → `order_id: String`, `submit()`).
- Infer relationships from directional message arrows: `->>` becomes `-->` association; `-->>` becomes `..>` dependency.

---

## Level Content Guide

Calibrate description depth in `main.md`'s **Overview** section by hierarchy level:

| Level | Scope | Overview tone | Process detail |
|-------|-------|---------------|----------------|
| 0 | Whole-organisation | Strategic: business goals, stakeholder groups, value proposition | High-level phases, major decision gates |
| 1 | Functional boundary | Functional: sub-system responsibility, key interfaces | Numbered activity steps with decision points |
| 2 | Sub-system component | Technical: component contract, data flows, error paths | Low-level steps, exception paths, retry logic |
| 3+ | Implementation detail | Implementation: specific algorithms, SLA constraints | Pseudocode-level granularity for steps |

For Level 0, omit "Parent Process" navigation links and the "Decomposed From" header field.

---

## Template Customization

To override the default templates for an organization:

1. Create `[process-folder-root]/doc-templates/` with overrides for any of: `main.md.template`, `process.md.template`, `collaboration.md.template`, `domain-model.md.template`.
2. When documentation-automation runs, check for `doc-templates/[filename].template` walking up to root; if found, use it instead of the built-in template.
3. Placeholders in custom templates follow `{{variable_name}}` syntax (double braces). Supported placeholders mirror the section headings in this file (e.g., `{{ProcessName}}`, `{{Level}}`, `{{ParentProcessName}}`, `{{BreadcrumbTrail}}`, `{{ParticipantSummaryTable}}`).

---

## Integration with Related Skills

| Skill | Relationship |
|-------|-------------|
| `hierarchy-management` | Creates folder structure and hierarchy-metadata.json; documentation-automation populates file content |
| `diagram-generatecollaboration` | Validates EDPS boundary rules used in Step 5; apply its stereotype classification rules when adding annotations |
| `orgmodel-update` | **Downstream / lower precedence for shared files**. Consumes completed per-node docs to update root-level OrgModel analysis. Must be run after `documentation-automation` completes for hierarchy-managed folders. When `hierarchy-metadata.json` is present, `orgmodel-update` defers to this skill for `main.md`, `process.md`, `collaboration.md`, `domain-model.md` and writes proposed changes to `pending-orgmodel-updates.md` instead. See `orgmodel-update/SKILL.md §Scope and Pipeline Position` for details. |

---

## Validation Checklist

After generating all files, confirm:

- [ ] All four files exist in the target folder
- [ ] `main.md` breadcrumb depth matches actual folder nesting
- [ ] `process.md` flowchart entry/terminal nodes match collaboration.md first/last messages
- [ ] `collaboration.md` every participant has a `@{ "type": "..." }` annotation
- [ ] `domain-model.md` class count equals participant count in collaboration.md
- [ ] VR-1 and VR-2 status in `main.md` correctly reflects collaboration.md structure
