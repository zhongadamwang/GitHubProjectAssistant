---
name: project-document-management
description: Initialize and manage project documentation structures following hierarchical folder guidelines. Creates consistent project trees with requirements, artifacts, and organizational modeling documents. Supports flat (Project 1) and EDPS-hierarchical (Project 3) archetypes, integrates with documentation-automation for stub generation, and tracks project evolution metadata.
license: MIT
version: 2.0.0
last-updated: 2026-03-16
---

# Project Document Management

Creates and manages standardized EDPS project documentation structures. Supports three project archetypes, integrates with `documentation-automation` for auto-generated docs, and records project evolution metadata for compliance and change tracking.

## Intent

Bootstrap and maintain a standardised EDPS project documentation tree — creating folder hierarchies, stub template files, cross-reference structures, and `project-metadata.json` evolution records that all downstream skills depend on. Selects the correct folder layout and file templates based on the chosen **archetype**.

## Inputs

- **Project name**: Human-readable project name
- **Project number**: Sequential two-digit project number (e.g., `03`)
- **Archetype** *(optional, default: `standard`)*: One of `flat`, `standard`, or `hierarchical` — see [Project Archetypes](#project-archetypes) below
- **Initial requirements** *(optional)*: Text to pre-populate `artifacts/Requirements/`
- **orgModel process name** *(optional)*: If provided, also initializes matching `orgModel/[NN] - [Process Name]/` tree

## Outputs

- `OrgDocument/projects/[NN] - [Project Name]/` — Full project folder tree per archetype
- Stubbed `main.md`, `README.md`, `project-plan.md`, `tasks/README.md`, `tasks/task-tracking.md`, `tasks/task-template.md`
- `project-metadata.json` — Machine-readable project metadata and evolution history
- *(hierarchical archetype only)* `orgModel/[NN] - [Process Name]/` tree with `documentation-automation` stubs

## Project Archetypes

Archetypes control which folders and files are created at initialization. Specify the archetype when invoking this skill.

### `flat` — Project 1 Style
Minimal project structure: `artifacts/` with standard subfolders, `tasks/`, and core markdown files. No `orgModel` integration. Use for quick analysis projects or GitHub integration projects.

```
[NN] - [Project Name]/
├── artifacts/
│   ├── Analysis/
│   ├── Requirements/
│   ├── Changes/
│   ├── Sample Data/
│   └── Testing/
├── tasks/
│   ├── README.md
│   ├── task-tracking.md
│   └── task-template.md
├── main.md
├── project-plan.md
└── README.md
```

### `standard` — Default EDPS Style (Projects 2–3)
Full project artifacts + `orgModel` process folder with standard modeling files. Use for most EDPS skill development or requirements analysis projects.

```
[NN] - [Project Name]/
├── artifacts/
│   ├── Analysis/
│   ├── Requirements/
│   ├── Changes/
│   ├── Documentation/
│   ├── Sample Data/
│   └── Testing/
├── tasks/
│   ├── README.md
│   ├── task-tracking.md
│   └── task-template.md
├── main.md
├── project-plan.md
└── README.md

orgModel/[NN] - [Process Name]/
├── main.md
├── process.md
├── collaboration.md
├── domain-model.md
├── vocabulary.md
├── test-case-list.md
└── test-cases/
```

### `hierarchical` — EDPS Boundary-First Style (Project 3+)
All `standard` content plus `orgModel` folders using the `[NN]-[Name]Boundary/` hierarchy pattern. Each boundary sub-folder receives `documentation-automation` stubs. Use when designing multi-level EDPS process hierarchies.

```
orgModel/[NN] - [Process Name]/
├── main.md
├── process.md
├── collaboration.md               ← defines boundary sub-folders
├── domain-model.md
├── vocabulary.md
├── test-case-list.md
├── test-cases/
├── hierarchy-metadata.json        ← created by hierarchy-management
└── [NN]-[BoundaryName]Boundary/   ← one per control-type participant
    ├── main.md                    ← [TO BE GENERATED - invoke documentation-automation]
    ├── process.md                 ← [TO BE GENERATED - invoke documentation-automation]
    ├── collaboration.md           ← [TO BE GENERATED - invoke documentation-automation]
    └── domain-model.md            ← [TO BE GENERATED - invoke documentation-automation]
```

**Important**: Boundary sub-folder files are initialised as stubs. After creating each boundary folder, invoke `documentation-automation` to replace stubs with generated content based on `collaboration.md`.

## Usage Scenarios

1. **New Project Initialization**: Create complete folder structure for a greenfield project (specify archetype)
2. **orgModel Process Initialization**: Set up a new top-level or sub-process folder within an existing project
3. **Project Migration**: Restructure an existing flat project to standard or hierarchical layout
4. **Structure Validation**: Verify a project follows documentation guidelines and flag missing elements
5. **Iterative Update**: Add folders or update stubs in an existing project following EDPS evolutionary principles
6. **Template Refresh**: Regenerate template files to the current version without overwriting substantive content

## Project Initialization Workflow

### Step 1 — Select Archetype and Determine Parameters

1. Confirm `project_number` (next sequential NN in `projects.md`) and `project_name`.
2. If the user does not specify an archetype, apply `standard`.
3. Read `projects.md` to validate the number is not already in use.

### Step 2 — Create Project Folder Tree

Create the folder structure for the selected archetype (see [Project Archetypes](#project-archetypes)). Always create:

```
OrgDocument/projects/[NN] - [Project Name]/
├── artifacts/
│   ├── Analysis/
│   ├── Requirements/
│   ├── Changes/
│   ├── Documentation/       ← standard + hierarchical only
│   ├── Sample Data/
│   └── Testing/
├── tasks/
│   ├── README.md
│   ├── task-tracking.md
│   └── task-template.md
├── main.md
├── project-plan.md
├── README.md
└── project-metadata.json
```

### Step 3 — Write project-metadata.json

Create `project-metadata.json` in the project root:

```json
{
  "project_id": "PRJ-[NN]",
  "project_name": "[Project Name]",
  "project_number": "[NN]",
  "archetype": "[flat|standard|hierarchical]",
  "created": "[ISO-8601 date]",
  "last_updated": "[ISO-8601 date]",
  "status": "Initialized",
  "version": "1.0.0",
  "evolution_history": [
    {
      "version": "1.0.0",
      "date": "[ISO-8601 date]",
      "change": "Initial project structure created",
      "archetype": "[archetype]",
      "changed_by": "project-document-management"
    }
  ],
  "linked_orgmodel": "[OrgDocument/orgModel/NN - Process Name]  or null",
  "skills_used": []
}
```

Update `evolution_history` with a new entry every time the project structure is modified by this skill.

### Step 4 — Update Project Registry

Add row to `OrgDocument/projects/projects.md`:

```markdown
| [NN] | [Project Name] | [Brief description] | Initialized | [Date] |
```

### Step 5 — Initialize orgModel Process Tree (standard and hierarchical archetypes)

If `orgModel process name` was provided or archetype requires it:

```
OrgDocument/orgModel/[NN] - [Process Name]/
├── main.md
├── process.md
├── collaboration.md
├── domain-model.md
├── vocabulary.md
├── test-case-list.md
└── test-cases/
    └── tc-[identifier]-[3-digit-sequence].md
```

**File initialization rules**:
- `main.md`, `process.md`, `collaboration.md`, `domain-model.md` → write with the standard EDPS templates (see below)
- `vocabulary.md`, `test-case-list.md` → write empty stubs with heading only; `orgmodel-update` owns their content

### Step 6 — Initialize Boundary Sub-folders (hierarchical archetype only)

For each `control`-type participant identified in the top-level `collaboration.md`, create a boundary sub-folder:

```
orgModel/[NN] - [Process Name]/
└── [NN]-[BoundaryName]Boundary/
    ├── main.md          ← insert stub: [TO BE GENERATED - invoke documentation-automation]
    ├── process.md       ← insert stub: [TO BE GENERATED - invoke documentation-automation]
    ├── collaboration.md ← insert stub: [TO BE GENERATED - invoke documentation-automation]
    └── domain-model.md  ← insert stub: [TO BE GENERATED - invoke documentation-automation]
```

After creating each boundary folder, **invoke `documentation-automation`** to replace stubs with generated content. This skill does not generate the content itself — it only creates the stub structure and triggers the downstream generation.

**Invocation pattern**:
```
[After creating boundary folder] → invoke documentation-automation for [process-folder]/[NN]-[Name]Boundary/
```

## Iterative Update Workflow

Use this workflow when evolving an **existing** project structure to add EDPS methodology support or upgrade an archetype.

### Detect Current State
1. Read `project-metadata.json` to determine current `archetype` and `version`.
2. If `project-metadata.json` is missing → treat as legacy project, infer archetype from folder structure.

### Upgrade Archetype
| From → To | Actions |
|-----------|---------|
| `flat` → `standard` | Add `Documentation/` to artifacts; create `orgModel/[NN] - [Process Name]/` tree if process name provided |
| `flat` → `hierarchical` | All standard actions plus boundary sub-folder stubs |
| `standard` → `hierarchical` | Add boundary sub-folder stubs for `control`-type participants in existing `collaboration.md` |

### Add Missing Files
For each expected file that is absent, create the stub. Do **not** overwrite files that already contain substantive content (line count > 10 non-placeholder lines).

### Record Evolution
After every update, append a new entry to `evolution_history` in `project-metadata.json` and increment the `version` field (patch increment for file additions, minor for archetype upgrades).

## Standard File Templates

### Project main.md Template
```markdown
<!-- Identifier: PRJ-[NN] -->

# [NN] - [Project Name]

## Overview
[Brief project description and objectives]

**Archetype**: [flat|standard|hierarchical]  
**Status**: Initialized — Awaiting Requirements  
**Created**: [Date]  

## Structure
- `artifacts/` - Supporting materials and analysis outputs
  - `Analysis/` - Technical analysis documents
  - `Requirements/` - Requirements and specifications
  - `Changes/` - Change records and impact analyses
  - `Documentation/` - Generated and authored documentation
  - `Sample Data/` - Test data and examples
  - `Testing/` - Test cases and validation artifacts
- `tasks/` - Individual task files in GitHub issue format

## Key Documents
- [`project-plan.md`](project-plan.md) — Detailed project planning and timeline
- [`README.md`](README.md) — Project summary and quick reference
- [`tasks/README.md`](tasks/README.md) — Task overview and tracking
- [`artifacts/Requirements/`](artifacts/Requirements/) — Requirements and specifications
- [`../../orgModel/[NN] - [Process Name]/main.md`](../../orgModel/[NN]%20-%20[Process%20Name]/main.md) — Linked organizational process model

## Dependencies
- [List of project dependencies]

## Success Criteria
[To be defined based on requirements]

## Timeline
[To be defined in project-plan.md]

---
**Project Status**: Initialized — Awaiting Requirements  
**Last Updated**: [Date]  
**Next Steps**: Provide requirements input and conduct initial analysis
```

### Process main.md Template (standard / hierarchical archetypes)
```markdown
# [NN] - [Process Name]

**Level**: [0 | 1 | 2 | …]  
**Parent Process**: [Parent process name or Root]  
**Archetype**: [standard|hierarchical]  
**Last Updated**: [Date]  

## Business Model Overview
[Description of the business model at this process level]

## Requirements Source
[Overview of requirements driving this process level]

## Process Scope
[Specific scope and boundaries for this process granularity]

## Business Context
[Business context and rationale for this process level]

## Key Stakeholders
[Primary stakeholders involved at this process level]

## Process Flow
See [process.md](process.md) for detailed activity diagram.

## Collaborations
See [collaboration.md](collaboration.md) for entity interactions.

## Domain Model
See [domain-model.md](domain-model.md) for actors and entities.

## Sub-Processes
[List of boundary sub-folder links — e.g., [01-OrderServiceBoundary](01-OrderServiceBoundary/main.md)]

## Test Coverage
See [test-case-list.md](test-case-list.md) for verification test cases.
```

### Boundary Sub-folder Stub Template (hierarchical archetype)
The following placeholder is inserted into each of the four generated files in a new boundary sub-folder. `documentation-automation` replaces this stub with real content.

```markdown
[TO BE GENERATED - invoke documentation-automation]
```

### project-plan.md Template
```markdown
# Project Plan — [NN] - [Project Name]

**Project ID**: PRJ-[NN]  
**Archetype**: [flat|standard|hierarchical]  
**Start Date**: [Date]  
**Target Completion**: [Date]  
**Status**: Planning Phase — Awaiting Requirements  

## Executive Summary
[To be defined]

## Project Phases

### Phase 0: Requirements Analysis and Planning
**Duration**: [TBD]  
**Deliverables**: Requirements doc, Technical analysis, Task breakdown

### Phase 1: [To be defined]
**Deliverables**: [TBD]

### Phase 4: Integration and Testing
**Deliverables**: Integration testing, Documentation updates, Deployment

## Risk Management
[To be updated as risks are identified]
```

## Quick Commands

### Initialize New Project
**Parameters**: `project_number`, `project_name`, `description`, `archetype` (default: `standard`), `orgmodel_process_name` (optional)  
**Actions**:
1. Read `projects.md` — verify `project_number` not already in use
2. Create project folder with archetype-appropriate subfolder structure
3. Write `project-metadata.json` (version `1.0.0`)
4. Generate `main.md`, `README.md`, `project-plan.md` from templates
5. Create `tasks/` with `README.md`, `task-tracking.md`, `task-template.md`
6. Update `projects.md` registry
7. *(standard/hierarchical)* Create `orgModel/[NN] - [Process Name]/` tree
8. *(hierarchical)* Create boundary sub-folder stubs; invoke `documentation-automation` per boundary folder

### Initialize Process Model
**Parameters**: `process_number`, `process_name`, `scope_description`, `archetype` (default: `standard`)  
**Actions**:
1. Create `orgModel/[NN] - [Process Name]/` folder
2. Generate `main.md`, `process.md`, `collaboration.md`, `domain-model.md` from templates
3. Create `vocabulary.md`, `test-case-list.md` stubs (content owned by `orgmodel-update`)
4. Create `test-cases/` folder
5. *(hierarchical)* Create boundary sub-folder stubs and invoke `documentation-automation`
6. Update parent `main.md` sub-process list

### Upgrade Archetype
**Parameters**: `project_number`, `target_archetype`  
**Actions**:
1. Read `project-metadata.json` to determine current archetype
2. Apply incremental changes per upgrade path table
3. Increment version in `project-metadata.json` (minor bump for archetype upgrade)
4. Append entry to `evolution_history`

### Structure Validation
**Actions**:
1. Verify folder naming conventions (`[NN] - [Name]` for projects; `[NN]-[Name]Boundary` for hierarchy nodes)
2. Check required files in each folder (per archetype)
3. Validate `project-metadata.json` presence and schema
4. Validate cross-reference links in `main.md` resolve to existing files
5. Detect stub files not yet replaced by `documentation-automation`
6. Report: ✅ valid | ⚠️ missing files | ❌ broken links | 🔲 ungenerated stubs

## Naming Conventions

### Folders
- Projects: `[NN] - [Project Name]` (e.g., "01 - Customer Portal")
- Processes: `[NN] - [Process Name]` (e.g., "02 - User Authentication")
- Artifacts: Descriptive names without numbering (e.g., "Analysis", "UI Mockups")

### Files  
- Main documentation: `main.md`
- Process diagrams: `process.md`  
- Collaboration diagrams: `collaboration.md`
- Domain models: `domain-model.md`
- Vocabulary: `vocabulary.md`
- Test case lists: `test-case-list.md`
- Individual test cases: `tc-[identifier]-[3-digit-sequence].md`

## Naming Conventions

### Folders
- Projects: `[NN] - [Project Name]` (e.g., `03 - Building Skills Iteration 2`)
- Standard process nodes: `[NN] - [Process Name]` (e.g., `01 - Skill Development Process`)
- Hierarchical boundary nodes: `[NN]-[NamePascalCase]Boundary` (e.g., `01-OrderServiceBoundary`)
- Artifacts: Descriptive names without numbering (e.g., `Analysis`, `Testing`)

### Files
- Main documentation: `main.md`
- Process diagrams: `process.md`
- Collaboration diagrams: `collaboration.md`
- Domain models: `domain-model.md`
- Vocabulary: `vocabulary.md`
- Test case lists: `test-case-list.md`
- Individual test cases: `tc-[identifier]-[3-digit-sequence].md`
- Hierarchy metadata: `hierarchy-metadata.json` *(created by `hierarchy-management`)*
- Project metadata: `project-metadata.json` *(created by this skill)*

## Integration Points

### `documentation-automation` *(primary downstream)*
- **Trigger**: After this skill creates any boundary sub-folder (hierarchical archetype)
- **Input it provides**: Stub files containing `[TO BE GENERATED - invoke documentation-automation]`
- **Output it receives**: Fully generated `main.md`, `process.md`, `collaboration.md`, `domain-model.md`
- **Ordering rule**: This skill creates the folder structure → `documentation-automation` fills the content

### `hierarchy-management`
- **Coordination**: `hierarchy-management` creates `hierarchy-metadata.json` and boundary sub-folders when decomposing control-type participants. This skill creates the initial top-level process tree; `hierarchy-management` extends it downward.
- **Conflict avoidance**: Do not create `[NN]-[Name]Boundary/` folders if `hierarchy-management` will do so based on `collaboration.md` decomposition.

### `orgmodel-update`
- **Files owned by `orgmodel-update`**: `vocabulary.md`, `test-case-list.md` — initialize as empty stubs only; never overwrite content in these files.
- **Files owned by this skill**: `main.md`, `project-plan.md`, `README.md`, `project-metadata.json`

### `requirements-ingest`
- Pre-populate `artifacts/Requirements/` when initial requirements text is provided at initialization.

### `goals-extract`
- Output can seed the `## Success Criteria` section of `main.md` after project initialization.

### `edps-workflow-orchestrator` *(planned — T07)*
- `project-metadata.json` `skills_used` array is updated by the orchestrator to track which skills have been invoked against this project.

## Best Practices

1. **Select the right archetype first**: Wrong archetype choice requires a structure upgrade later — ask if unsure.
2. **Consistent numbering**: Use sequential NN for projects and process nodes; do not reuse numbers.
3. **project-metadata.json is the source of truth**: Always read it before modifying a project structure.
4. **Write stubs, not content**: This skill creates structure; `documentation-automation` and `orgmodel-update` fill content.
5. **Registry updates are mandatory**: Always update `projects.md` when creating a new project folder.
6. **Never skip documentation-automation**: After creating boundary sub-folders (hierarchical archetype), immediately invoke `documentation-automation` to convert stubs.
7. **Record every structural change**: Append to `evolution_history` in `project-metadata.json` for every modification — required for EDPS compliance.