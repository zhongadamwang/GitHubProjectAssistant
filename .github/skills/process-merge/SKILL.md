---
name: process-merge
description: Merges new process models with existing organizational models by identifying minimum sub-process overlap and implementing systematic integration following EDP methodology principles for minimal disruption while maintaining model coherence.
license: MIT
---

# Process Merge

Systematically merges new process models derived from requirements with existing organizational model structures through intelligent overlap analysis, minimal disruption integration, and comprehensive stakeholder validation workflows.

## Intent
Implement true process model integration that analyzes new process elements against existing orgModel structure, identifies minimum sub-process overlap using domain entity matching, and executes systematic integration following EDP methodology principles. Maintains process coherence while preserving existing organizational content and ensuring stakeholder validation at critical integration points.

## Inputs
- **Primary**: `projects/[project-name]/artifacts/Analysis/domain-concepts.json` (new process entities from domain-extractconcepts skill)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/domain-alignment.json` (entity mappings from domain-alignentities skill)
- **Context**: `projects/[project-name]/artifacts/Analysis/requirements.json` (source requirements for traceability)
- **Context**: `projects/[project-name]/artifacts/Analysis/w5h-analysis.json` (process context understanding)
- **Existing**: `orgModel/*/process.md` (existing organizational process flows)
- **Existing**: `orgModel/*/domain-model.md` (existing domain models for entity mapping)
- **Existing**: `orgModel/*/vocabulary.md` (organizational terminology consistency)

## Outputs
**Files Generated:**
- `projects/[project-name]/artifacts/Analysis/process-merge.json` - Structured merge plan and overlap analysis
- `projects/[project-name]/artifacts/Analysis/process-merge.md` - Human-readable integration report
- `projects/[project-name]/artifacts/Analysis/process-integration-plan.md` - Stakeholder review and approval workflow
- `projects/[project-name]/artifacts/Analysis/process-rollback-data.json` - Rollback capabilities and validation checkpoints

### JSON Structure (`process-merge.json`)
```json
{
  "project_id": "string",
  "merge_metadata": {
    "generated_at": "ISO8601",
    "source_domain_concepts": "domain-concepts.json",
    "source_alignment": "domain-alignment.json",
    "target_orgmodels": ["orgModel/01-skill-dev/process.md"],
    "overlap_analysis_method": "entity_matching",
    "integration_strategy": "minimal_disruption",
    "edp_compliance": "boolean",
    "total_processes_analyzed": "number",
    "minimum_overlap_identified": "number",
    "integration_complexity": "low|medium|high",
    "stakeholder_validation_required": "boolean",
    "rollback_capability": "boolean"
  },
  "overlap_analysis": {
    "process_mappings": [
      {
        "mapping_id": "PM-001",
        "new_process": {
          "name": "User Authentication Process",
          "source_requirements": ["REQ-001", "REQ-003"],
          "entities": ["User", "Authentication Service", "Security Token"],
          "activities": ["Login Request", "Credential Validation", "Token Generation"],
          "decision_points": ["Credential Valid?", "Multi-factor Required?"],
          "estimated_integration_impact": "low|medium|high"
        },
        "existing_processes": [
          {
            "orgmodel": "orgModel/02-security-management/process.md",
            "process_name": "Access Control Process",
            "overlap_percentage": 85,
            "shared_entities": ["User", "Security Token"],
            "shared_activities": ["Credential Validation"],
            "integration_points": ["post_authentication", "token_management"],
            "disruption_assessment": "minimal"
          }
        ],
        "overlap_type": "significant|partial|minimal|none",
        "integration_strategy": {
          "approach": "extend_existing|merge_processes|create_specialized|create_new",
          "minimal_disruption_plan": {
            "preserve_existing": ["authentication_flow", "token_structure"],
            "extend_with": ["multi_factor_support", "enhanced_logging"],
            "stakeholder_checkpoints": ["after_entity_mapping", "before_deployment"]
          }
        }
      }
    ],
    "overlap_summary": {
      "total_new_processes": "number",
      "processes_with_significant_overlap": "number",
      "processes_requiring_new_creation": "number",
      "processes_for_extension": "number",
      "minimum_integration_scope": {
        "entities_to_integrate": "number",
        "activities_to_merge": "number",
        "decision_points_to_align": "number"  
      }
    }
  },
  "integration_plan": {
    "phases": [
      {
        "phase_id": "P1",
        "phase_name": "Entity Alignment",
        "sequence": 1,
        "strategy": "minimal_disruption",
        "activities": [
          "Map new entities to existing entity hierarchy",
          "Identify terminology conflicts and resolutions",
          "Validate entity relationship consistency"
        ],
        "deliverables": ["entity_mapping_validation.md"],
        "stakeholder_checkpoints": ["entity_review_meeting"],
        "rollback_points": ["before_entity_integration"],
        "estimated_effort": "0.5 days",
        "risk_assessment": "low"
      },
      {
        "phase_id": "P2",
        "phase_name": "Process Flow Integration",
        "sequence": 2,
        "strategy": "extend_existing_flows",
        "activities": [
          "Integrate new activities into existing process flows",
          "Align decision points and branching logic",
          "Update process documentation and diagrams"
        ],
        "deliverables": ["updated_process_flows.md", "integration_validation.json"],
        "stakeholder_checkpoints": ["process_flow_review"],
        "rollback_points": ["before_process_update", "after_validation"],
        "estimated_effort": "1.5 days",
        "risk_assessment": "medium"
      }
    ],
    "stakeholder_validation": {
      "required_approvals": [
        {
          "approval_id": "SV-001",
          "stakeholder_role": "Process Owner",
          "scope": "process_flow_changes",
          "validation_criteria": ["business_logic_preservation", "performance_maintenance"],
          "approval_deadline": "ISO8601",
          "escalation_path": "department_head > executive_sponsor"
        }
      ],
      "validation_checkpoints": [
        {
          "checkpoint_id": "CP-001",
          "name": "Entity Integration Validation",
          "criteria": ["terminology_consistency", "relationship_integrity"],
          "validation_method": "stakeholder_review",
          "required_participants": ["domain_expert", "process_owner"],
          "success_criteria": "unanimous_approval"
        }
      ]
    },
    "rollback_strategy": {
      "rollback_points": [
        {
          "point_id": "RP-001",
          "name": "Pre-Integration State",
          "captured_state": "complete_orgmodel_snapshot",
          "rollback_triggers": ["validation_failure", "stakeholder_rejection"],
          "recovery_time": "< 1 hour",
          "data_integrity": "guaranteed"
        }
      ],
      "rollback_procedures": [
        "Restore orgModel files from snapshot",
        "Reset domain model alignments",
        "Notify stakeholders of rollback completion"
      ]
    }
  },
  "integration_results": {
    "successful_integrations": [
      {
        "integration_id": "SI-001",
        "process_name": "Enhanced Authentication Process",
        "target_orgmodel": "orgModel/02-security-management/",
        "integration_type": "extension",
        "changes_applied": [
          "Added multi-factor authentication activities",
          "Extended user entity with authentication attributes",  
          "Updated decision points for security levels"
        ],
        "validation_status": "passed",
        "stakeholder_approval": "approved",
        "rollback_status": "not_required"
      }
    ],
    "failed_integrations": [
      {
        "integration_id": "FI-001", 
        "process_name": "Complex Workflow Process",
        "failure_reason": "irreconcilable_entity_conflicts",
        "rollback_executed": "true",
        "alternative_approach": "create_separate_process_model",
        "stakeholder_notification": "completed"
      }
    ],
    "integration_metrics": {
      "total_processes_integrated": "number",
      "average_overlap_percentage": "number",
      "minimal_disruption_achieved": "boolean",
      "stakeholder_satisfaction": "high|medium|low",
      "rollback_incidents": "number",
      "integration_completion_time": "hours"
    }
  }
}
```

## Core Functions

### 1. Process Overlap Analysis

**Identifies minimum sub-process overlap through sophisticated entity matching:**

- **Entity-Based Mapping**: Matches new process entities with existing domain models using fuzzy matching and semantic analysis
- **Activity Flow Analysis**: Compares process activities and workflows to identify shared patterns and integration opportunities
- **Decision Point Alignment**: Maps decision criteria and branching logic between new and existing processes
- **Impact Assessment**: Evaluates integration complexity and potential disruption to existing organizational flows

**Algorithm**: Domain entity mapping with coverage optimization to identify minimal viable integration points that preserve maximum existing process integrity.

### 2. EDP Methodology Integration

**Implements Evolutionary Development Process principles for minimal disruption:**

- **Incremental Integration**: Phases integration into digestible stages with validation checkpoints
- **Stakeholder-Driven Validation**: Requires explicit approval at critical integration points
- **Progressive Enhancement**: Extensions over replacements wherever possible
- **Rollback Capability**: Maintains ability to revert changes at multiple integration stages

**Philosophy**: "Extend rather than replace, validate before committing, preserve organizational investment."

### 3. Systematic Integration Planning

**Orchestrates integration with comprehensive planning and risk management:**

```markdown
## Integration Strategy Selection

### Minimal Disruption Hierarchy (Preferred Order):
1. **Extension**: Add capabilities to existing processes (lowest risk)
2. **Specialization**: Create specialized variants of existing processes  
3. **Merger**: Combine compatible processes with careful conflict resolution
4. **New Creation**: Establish separate processes only when integration impossible

### Risk Assessment Matrix:
- **Low Risk**: Entity extensions, terminology alignment, documentation updates
- **Medium Risk**: Activity integration, decision point modifications, workflow changes
- **High Risk**: Core process replacement, entity restructuring, fundamental logic changes
```

### 4. OrgModel Structure Preservation

**Maintains existing organizational model integrity during integration:**

- **Content Preservation**: Existing process documentation remains intact unless explicitly changed
- **Cross-Reference Maintenance**: Updates all internal references and links affected by changes
- **Vocabulary Consistency**: Ensures terminology alignment across all affected organizational models
- **Test Case Continuity**: Updates test cases to reflect process changes while preserving coverage

### 5. Stakeholder Validation Workflow

**Implements comprehensive validation and approval process:**

```markdown
## Validation Checkpoints

### Phase 1: Pre-Integration Validation
- Entity mapping review with domain experts
- Process flow compatibility assessment
- Risk assessment validation with process owners
- **Go/No-Go Decision Point**

### Phase 2: Integration Validation  
- Integration results review with stakeholders
- Performance impact assessment
- Business continuity validation
- **Deployment Approval Checkpoint**

### Phase 3: Post-Integration Validation
- Operational validation with end users
- Integration success metrics review
- Rollback assessment (if required)
- **Final Acceptance Checkpoint**
```

## Usage Examples

### GitHub Copilot Integration

**Direct Invocation:**
```markdown
Use process-merge skill to analyze and integrate the new authentication processes from requirements analysis with existing security management processes in orgModel. Apply minimal disruption strategy with stakeholder validation checkpoints.

Expected Input Files:
- projects/AUTH-2026/artifacts/Analysis/domain-concepts.json
- projects/AUTH-2026/artifacts/Analysis/domain-alignment.json  
- orgModel/02-security-management/process.md

Generate integration plan with rollback capability and stakeholder approval workflow.
```

**Workflow Orchestration:**
```markdown
1. Extract domain concepts from requirements (domain-extractconcepts skill)
2. Align entities with existing models (domain-alignentities skill)  
3. **Merge processes with existing orgModel (process-merge skill)**
4. Update organizational models (orgmodel-update skill)
5. Generate integration documentation (change-management skill)
```

### Command Line Usage

```bash
# Analyze process overlap and generate integration plan
python -m skills.process_merge PROJECT-AUTH-2026 \
  --source-concepts "projects/AUTH-2026/artifacts/Analysis/domain-concepts.json" \
  --domain-alignment "projects/AUTH-2026/artifacts/Analysis/domain-alignment.json" \
  --target-orgmodel "orgModel/02-security-management/" \
  --integration-strategy "minimal_disruption" \
  --validation-mode "stakeholder_required"

# Output Files:
#   projects/AUTH-2026/artifacts/Analysis/process-merge.json
#   projects/AUTH-2026/artifacts/Analysis/process-merge.md  
#   projects/AUTH-2026/artifacts/Analysis/process-integration-plan.md
#   projects/AUTH-2026/artifacts/Analysis/process-rollback-data.json
```

### Integration with Model-Integration Skill

**Collaborative Workflow:**
```markdown
process-merge skill focuses on process flow integration and overlap analysis
model-integration skill handles entity relationship conflicts and cross-process consistency
orgmodel-update skill applies validated changes to organizational model structure

Recommended sequence:
1. process-merge → generates integration plan
2. model-integration → resolves entity conflicts  
3. orgmodel-update → applies changes systematically
```

## Implementation Notes

### Dependencies
- **Hard Requirements**: orgmodel-update skill (T17), model-integration skill (T18), domain-alignentities skill (T6)
- **Soft Dependencies**: change-management skill (T16), edps-skill-navigator skill (T19)

### EDP Methodology Compliance
- **Minimal Disruption**: Prioritizes extensions over replacements
- **Stakeholder Validation**: Requires explicit approval for significant changes
- **Progressive Integration**: Implements changes in phases with rollback points
- **Documentation Continuity**: Maintains organizational knowledge preservation

### Performance Considerations
- **Large OrgModel Handling**: Optimized for organizations with 10+ process models
- **Overlap Analysis Efficiency**: Entity matching algorithms scale to 100+ entities per process
- **Validation Workflow**: Supports concurrent stakeholder review processes
- **Rollback Performance**: < 1 hour recovery time for typical integration scenarios

### Error Handling
- **Integration Conflicts**: Automatically escalates irreconcilable conflicts to stakeholder review
- **Validation Failures**: Triggers rollback to last stable checkpoint
- **Entity Mapping Errors**: Provides detailed conflict analysis and resolution options
- **Stakeholder Approval Timeout**: Implements escalation paths and deadline management

---

*This skill implements the true process model integration capability identified in task T10-NEW, providing systematic merge functionality that respects existing organizational investments while enabling evolutionary development through minimal disruption integration strategies.*
````