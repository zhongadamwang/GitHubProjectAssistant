---
name: change-management
description: Identify, document, and track changes to requirements, processes, and specifications with automated referencing and impact analysis. Converts AI conversation text into structured change documents following established change tracking workflows.
license: MIT
---

# Change Management

Transforms AI conversation text and requirement discussions into structured change documents with automatic classification, impact analysis, and reference updates.

## Intent

Convert AI conversation text and requirement discussions into structured change documents, automatically classify change types, score impact using `change-impact-analysis` outputs, and produce reference-update instructions for all affected files.

## Inputs

- **Conversation text**: Raw AI conversation or discussion text that contains requirement changes
- **Project ID**: `project_id` string
- **Optional**: `change-impact-report.json` from `change-impact-analysis` — the `normalized_risk_level` field maps directly to `risk_level` in each `affected_documents` entry; entries with `critical_flag: true` represent CRITICAL severity capped at `High` and require elevated review (see `change-impact-analysis` `## Risk Level Mapping` for the full scale)

## Outputs

- `artifacts/Changes/[date]-[change_id]-[title].md` — Structured change document (Markdown)
- JSON response following the `## Output Schema`: `changes_identified`, `reference_updates`, `next_actions`

## Core Function

**Input**: Conversation text + project context + change scope  
**Output**: Structured change document + affected file list + reference updates

> **Compatible upstream source**: Use the `change-impact-analysis` skill to generate `change-impact-report.json`. Pass each entry's `normalized_risk_level` field directly as `risk_level` in `affected_documents`. Entries with `critical_flag: true` indicate CRITICAL impacts that have been capped at `High` and should be reviewed with elevated priority.

## Usage

**GitHub Copilot Integration (Recommended):**
```markdown
Use this skill directly in Copilot by providing conversation text that contains requirement changes.
Copilot will automatically identify changes, classify them, and generate proper documentation.

Example prompt:
"Use change-management skill to analyze this conversation for requirement changes and create proper change documentation with impact analysis and reference updates."
```

**Traditional Script Approach:**
```python
from change_management import ChangeProcessor
processor = ChangeProcessor()
result = processor.process_conversation(text=conversation_text, project_id="PRJ-001")
```

## Output Schema

ALWAYS return exactly this JSON structure:

```json
{
  "project_id": "string",
  "changes_identified": [
    {
      "change_id": "PROC-CHG-001", 
      "change_type": "REQ-CHG|REQ-ADD|REQ-REM|SCOPE-CHG|PROC-CHG",
      "title": "Brief description for filename",
      "summary": "One-line change summary",
      "priority": "Low|Medium|High|Critical",
      "status": "Proposed",
      "rationale": "Why this change is needed",
      "current_state": "Description of current requirement/process",
      "proposed_state": "Description after change",
      "impact_analysis": {
        "affected_documents": [
          {
            "file_path": "relative/path/to/file.md",
            "impact_description": "How this file is affected",
            "update_required": true
          }
        ],
        "affected_tasks": [
          {
            "task_id": "T2",
            "impact_description": "How this task is affected"
          }
        ],
        "risk_level": "Low|Medium|High",
        "estimated_effort": "X hours/days"
      }
    }
  ],
  "reference_updates": [
    {
      "file_path": "relative/path/to/file.md",
      "section": "Related Changes",
      "new_reference": "- [PROC-CHG-001](../artifacts/Changes/2026-02-08-PROC-CHG-001-title.md) - Description"
    }
  ],
  "next_actions": [
    "Action item 1",
    "Action item 2"
  ]
}
```

## GitHub Copilot Integration

### Direct Usage in Copilot Chat
Paste your conversation or discussion text and ask:

```
@workspace Use the change-management skill to process this conversation:

[PASTE CONVERSATION TEXT HERE]

Project ID: AI-SLOWCOOKER-001
Context: Building Skills project

Identify requirement changes and:
- Classify change types (REQ-CHG, PROC-CHG, etc.)
- Generate impact analysis
- Create proper change documentation
- Identify files needing reference updates
- Suggest next actions

Return structured JSON following the schema.
```

### Copilot Prompt Template
```
Analyze conversation using change-management methodology:

1. IDENTIFY: Scan for explicit/implicit requirement changes
2. CLASSIFY: Categorize as REQ-CHG|REQ-ADD|REQ-REM|SCOPE-CHG|PROC-CHG
3. ANALYZE: Assess impact on documents, tasks, orgModel files
4. DOCUMENT: Generate structured change document content
5. REFERENCE: Identify files needing reference updates

Output exact JSON schema with changes_identified, reference_updates, next_actions.
```

## Classification Rules

### Change Types
- **REQ-CHG**: Modifications to existing requirements
- **REQ-ADD**: New requirements added to project scope
- **REQ-REM**: Requirements removed or marked obsolete  
- **SCOPE-CHG**: Project scope adjustments (budget, timeline, deliverables)
- **PROC-CHG**: Development process or workflow modifications

### Priority Assessment
- **Critical**: Blocks progress, affects core functionality
- **High**: Significant impact on project deliverables
- **Medium**: Moderate impact, can be scheduled normally
- **Low**: Minor impact, can be deferred

### Project Phase Context
- **Planning Phase**: Changes have higher flexibility, lower implementation cost
- **Development Phase**: Changes require careful impact assessment, may affect timeline
- **Testing Phase**: Changes should be minimal, focus on critical fixes only
- **Deployment Phase**: Only critical changes allowed, require stakeholder approval

### Risk Levels
- **Low**: Minimal impact, easy implementation
- **Medium**: Some complexity, moderate impact
- **High**: Significant impact, complex implementation

## Processing Rules

1. **Change Detection**: Identify explicit statements ("we need to change") and implicit changes ("actually, it should...")
2. **Context Awareness**: Consider project phase, existing constraints, stakeholder roles
3. **Impact Analysis**: Evaluate effects on requirements, tasks, process models, timeline
4. **Traceability**: Maintain links between changes and affected components
5. **File Naming**: Generate proper filename using format `YYYY-MM-DD-{TYPE}-{ID}-{title}.md`

## Reference Path Patterns

- **From Tasks to Changes**: `../artifacts/Changes/`
- **From OrgModel to Changes**: `../../projects/{project-name}/artifacts/Changes/`
- **From Project Root to Changes**: `artifacts/Changes/`

## Change ID Management

### Sequential ID Generation
1. **Scan Existing Changes**: Check `artifacts/Changes/` directory for highest ID number per type
2. **Auto-Increment Logic**: Generate next available ID within change type
3. **Conflict Prevention**: Verify ID uniqueness before document creation
4. **Cross-Reference Check**: Ensure ID not used in any related project files

### ID Format Rules
- Pattern: `{TYPE}-CHG-{###}` where ### is zero-padded 3-digit number
- Examples: `REQ-CHG-001`, `SCOPE-CHG-015`, `PROC-CHG-003`
- Numbering: Sequential within each change type, starting from 001

### Implementation Algorithm
```python
def generate_change_id(change_type, changes_directory):
    # Scan existing change files for this type
    existing_ids = scan_change_files(changes_directory, change_type)
    # Find highest number
    max_id = max([extract_id_number(id) for id in existing_ids], default=0)
    # Generate next ID
    next_id = f"{change_type}-CHG-{str(max_id + 1).zfill(3)}"
    # Verify uniqueness across all files
    verify_id_uniqueness(next_id, project_directory)
    return next_id
```

## Quality Checks

1. **Change ID Uniqueness**: 
   - Scan all existing change documents for ID conflicts
   - Verify ID follows proper format pattern
   - Check cross-references in tasks, requirements, and orgModel files
   
2. **Impact Completeness**: 
   - Every affected document must have specific impact description
   - Risk level must align with scope of affected components
   - Effort estimation must consider cascading effects
   - Missing dependencies must be flagged as incomplete
   
3. **Reference Accuracy**: 
   - Validate all relative paths resolve correctly from target locations
   - Ensure markdown links use proper encoding for spaces/special chars
   - Verify referenced files actually exist in project structure
   
4. **Documentation Standards**: 
   - Title length must be under 80 characters for filename compatibility
   - Summary must be single line, under 120 characters
   - Rationale must explain business/technical justification
   
5. **Status Consistency**: 
   - New changes default to "Proposed" status
   - Status progression follows: Proposed → Approved → Implemented → Verified
   - Critical changes require immediate stakeholder notification

## Integration Points

- **Requirements Ingest**: Changes may trigger re-ingestion of modified requirements
- **Task Planning**: New changes may spawn additional tasks or modify existing ones
- **Status Reporting**: Changes feed into project status and progress tracking
- **Document Management**: Changes integrate with overall project documentation structure

## AI Conversation Patterns

### Detection Signals
- "We need to change..." / "Actually, we should..."
- "I think the requirement should be..." / "Let me clarify..."
- "Instead of X, we need Y..." / "This doesn't work because..."
- "Add to the scope..." / "Remove from the scope..."
- "The process should..." / "Our workflow needs..."

### Context Clues
- Reference to existing requirement documents
- Discussion of implementation challenges
- Stakeholder feedback incorporation  
- Technical constraint discoveries
- Business priority adjustments

## Error Handling & Validation

### Input Validation
1. **Ambiguous Changes**: When conversation contains unclear requirements
   - Flag as "Needs Clarification" status
   - Generate follow-up questions for stakeholders
   - Document assumptions made and validation needed
   
2. **Incomplete Context**: When project context is insufficient
   - Request additional project information
   - Use conservative impact assessment
   - Mark analysis as "Preliminary - Requires Project Context"
   
3. **Conflicting Information**: When conversation contains contradictions
   - Document all conflicting statements
   - Flag for stakeholder resolution
   - Do not auto-classify until clarified

### Validation Rules
1. **Minimum Required Information**:
   - Change description (explicit or derivable from context)
   - Affected component identification (documents/tasks/processes)
   - Business rationale (stated or reasonably inferred)
   
2. **Quality Thresholds**:
   - Impact analysis must identify at least 1 affected component
   - Risk assessment must align with scope (High risk = multiple components)
   - Effort estimation must be within reasonable bounds (1 hour - 2 weeks)
   
3. **Cross-Reference Validation**:
   - All mentioned files must exist in project structure
   - Task references must match existing task IDs
   - Path references must be valid from multiple locations

### Error Recovery
- **Missing Information**: Generate change document with placeholders and flag sections needing input
- **Invalid References**: Log broken references and suggest corrections
- **ID Conflicts**: Auto-increment to next available ID and document conflict resolution

## File Generation

The skill generates change documents following this template structure:

```markdown
# Change Title

**Change ID**: {TYPE}-{###}  
**Date Created**: {YYYY-MM-DD}  
**Status**: Proposed  
**Priority**: {Level}  
**Requested By**: [Extracted from context]

## Summary
{One-line description}

## Change Details
{Detailed description extracted from conversation}

### Current State
{Current situation description}

### Proposed State  
{Desired future state}

### Rationale
{Why change is needed}

## Impact Analysis
{Generated impact assessment}

## Implementation Plan
{Suggested implementation steps}

## Acceptance Criteria
{Generated success criteria}
```