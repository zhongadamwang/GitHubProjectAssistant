---
name: model-integration
description: Systematically integrate new domain models and processes into existing organizational model structures using EDP methodology for minimal disruption integration, conflict resolution, and model coherence maintenance across multiple organizational processes.
license: MIT
---

# Model Integration

Systematically combine new domain models and processes into existing organizational model structures while maintaining coherence and minimizing disruption through sophisticated conflict resolution and progressive integration strategies.

## Intent
Implement comprehensive model integration that analyzes integration points, resolves entity mapping conflicts, maintains model coherence, and supports progressive deployment with rollback capabilities. Follows EDP methodology principles for minimal disruption while ensuring cross-process consistency and stakeholder impact analysis.

## Inputs
- **Primary**: `projects/[project-name]/artifacts/Analysis/domain-alignment.json` (from domain-alignentities skill)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/domain-newconcepts.json` (from domain-proposenewconcepts skill)
- **Context**: `projects/[project-name]/artifacts/Analysis/domain-concepts.json` (source concepts)
- **Context**: `projects/[project-name]/artifacts/Analysis/requirements.json` (for impact traceability)
- **Existing**: `orgModel/**/*domain-model.md` (target organizational models)
- **Existing**: `orgModel/**/*process.md` (existing process flows)
- **Existing**: `orgModel/**/*vocabulary.md` (organizational terminology)

## Outputs
**Files Generated:**
- `projects/[project-name]/artifacts/Analysis/model-integration.json` - Structured integration plan and results
- `projects/[project-name]/artifacts/Analysis/model-integration.md` - Human-readable integration report
- `projects/[project-name]/artifacts/Analysis/integration-impact-assessment.md` - Stakeholder impact analysis
- `projects/[project-name]/artifacts/Analysis/integration-validation.json` - Validation results and rollback data

### JSON Structure (`model-integration.json`)
```json
{
  "project_id": "string",
  "integration_metadata": {
    "generated_at": "ISO8601",
    "source_alignment": "domain-alignment.json",
    "source_newconcepts": "domain-newconcepts.json",
    "target_models": ["orgModel/01-skill-dev/domain-model.md"],
    "integration_strategy": "additive|replacement|merge|hybrid",
    "edp_compliance": "boolean",
    "total_integrations": "number",
    "conflict_resolution_count": "number",
    "rollback_capability": "boolean"
  },
  "integration_plan": {
    "phases": [
      {
        "phase_id": "P1",
        "phase_name": "Entity Integration",
        "sequence": 1,
        "strategy": "additive",
        "estimated_impact": "low|medium|high",
        "rollback_points": ["after_entity_mapping", "after_validation"],
        "dependencies": ["terminology_alignment"]
      }
    ],
    "integration_points": [
      {
        "integration_id": "IP-001",
        "type": "entity_integration|process_integration|terminology_integration|relationship_integration",
        "source": {
          "entity": "User Authentication",
          "source_file": "domain-concepts.json",
          "requirements_trace": ["REQ-001", "REQ-005"]
        },
        "target": {
          "model": "orgModel/01-skill-dev/domain-model.md",
          "section": "Security.Authentication",
          "existing_entities": ["Team Member", "Access Control"]
        },
        "integration_strategy": {
          "approach": "merge_with_existing",
          "conflict_resolution": "rename_and_specialize",
          "minimal_disruption": "extend_existing_hierarchy",
          "validation_checkpoints": ["entity_consistency", "relationship_integrity"]
        }
      }
    ],
    "conflict_resolutions": [
      {
        "conflict_id": "CR-001",
        "type": "entity_naming|relationship_mismatch|process_overlap|terminology_conflict",
        "description": "User vs Team Member entity naming conflict",
        "source_entity": "User",
        "target_entity": "Team Member",
        "resolution_strategy": {
          "approach": "create_specialization",
          "new_hierarchy": "Team Member > Authenticated User > External User",
          "impact": "extends_existing_model",
          "validation": "backward_compatibility_maintained"
        },
        "stakeholder_impact": {
          "affected_processes": ["authentication", "user_management"],
          "communication_required": ["developers", "business_analysts"],
          "training_impact": "minimal"
        }
      }
    ]
  },
  "integration_results": {
    "successful_integrations": [
      {
        "integration_id": "IP-001",
        "target_file": "orgModel/01-skill-dev/domain-model.md",
        "changes_applied": {
          "entities_added": ["Authenticated User"],
          "relationships_modified": ["Team Member -> has -> Authentication Profile"],
          "sections_updated": ["Security.Authentication"]
        },
        "validation_status": "passed",
        "rollback_data": "backup_reference_id"
      }
    ],
    "failed_integrations": [
      {
        "integration_id": "IP-005",
        "failure_reason": "circular_dependency_detected",
        "error_details": "Process A requires Process B which depends on Process A",
        "recommended_action": "redesign_process_flow",
        "rollback_performed": "true"
      }
    ],
    "model_coherence_check": {
      "consistency_score": "0.0-1.0",
      "validation_results": [
        {
          "check_type": "entity_consistency",
          "status": "passed|warning|failed",
          "issues": ["duplicate_entity_definitions"],
          "recommendations": ["consolidate_duplicate_entities"]
        }
      ]
    }
  }
}
```

### Integration Report Structure (`model-integration.md`)
```markdown
# Model Integration Report

**Project**: [Project Name]  
**Generated**: [ISO8601 timestamp]  
**Integration Strategy**: [additive|replacement|merge|hybrid]  
**EDP Compliance**: [Yes|No]  
**Overall Status**: [Success|Partial|Failed]

## Executive Summary
[High-level summary of integration approach, results, and impact]

## Integration Strategy
- **Approach**: [Strategy explanation]
- **Minimal Disruption Principles**: [How EDP methodology applied]
- **Rollback Capability**: [Available rollback points]
- **Progressive Integration**: [Phased deployment approach]

## Integration Results

### Successful Integrations
| Integration ID | Type | Target Model | Changes Applied | Impact |
|----------------|------|--------------|-----------------|--------|
| IP-001 | Entity | orgModel/01-skill-dev/domain-model.md | Added Authenticated User | Low |

### Conflict Resolutions
| Conflict ID | Type | Resolution | Stakeholder Impact |
|-------------|------|------------|-------------------|
| CR-001 | Entity Naming | Create Specialization | Minimal - extends existing |

### Model Coherence Validation
- **Consistency Score**: [0.0-1.0]
- **Critical Issues**: [Number]
- **Warnings**: [Number]
- **Recommendations**: [Number]

## Stakeholder Impact Analysis
[Summary of impacts by stakeholder group with communication requirements]

## Next Steps
- [ ] Review integration results with stakeholders
- [ ] Validate updated models in target environment
- [ ] Schedule training for affected processes
- [ ] Plan next integration phase
```

## Core Functions

### 1. Integration Analysis Framework

**Integration Point Detection**
- Analyze alignment results to identify integration opportunities
- Map source concepts to target model structures
- Detect potential conflicts and dependencies
- Assess integration complexity and risk levels

**Conflict Detection Algorithm**
```
for each aligned_entity in domain_alignment:
    if alignment_result.type == "conflict":
        analyze_conflict_type()
        determine_resolution_strategy()
        assess_stakeholder_impact()
        validate_resolution_feasibility()
```

### 2. Minimal Disruption Integration (EDP Methodology)

**Integration Strategies**
- **Additive**: Add new elements without modifying existing structure
- **Replacement**: Replace obsolete elements with minimal impact  
- **Merge**: Combine similar elements with conflict resolution
- **Hybrid**: Combination approach for complex scenarios

**EDP Compliance Checks**
- Preserve existing functionality during integration
- Maintain backward compatibility where possible
- Implement staged deployment with validation checkpoints
- Provide clear rollback paths at each stage

### 3. Progressive Integration Support

**Phased Integration Planning**
1. **Phase 1**: Terminology and vocabulary alignment
2. **Phase 2**: Entity integration with conflict resolution
3. **Phase 3**: Relationship and process integration
4. **Phase 4**: Validation and coherence checking

**Staged Deployment**
- Integration checkpoints with validation gates
- Rollback capability at each stage
- Stakeholder approval workflow
- Automated validation testing

### 4. Model Coherence Validation

**Consistency Checks**
- Entity name uniqueness across models
- Relationship integrity and circular dependency detection
- Terminology consistency validation
- Process flow completeness verification

**Cross-Model Validation**
```
validation_rules = {
    "entity_consistency": check_duplicate_entities(),
    "relationship_integrity": validate_all_relationships(),
    "terminology_alignment": verify_vocabulary_consistency(),
    "process_completeness": validate_process_flows()
}
```

### 5. Integration Impact Assessment

**Stakeholder Analysis**
- Identify affected processes and systems
- Assess training and communication requirements
- Evaluate business process impacts
- Determine approval workflow requirements

**Impact Categories**
- **Technical**: System changes, interface modifications
- **Process**: Business workflow adjustments
- **Training**: User education requirements
- **Communication**: Stakeholder notification needs

## Usage Workflow

### Input Validation
```
required_inputs = [
    "domain-alignment.json",
    "target orgModel structure"
]
validate_input_completeness()
load_existing_models()
analyze_integration_complexity()
```

### Integration Execution
```
1. Plan integration phases
2. Resolve conflicts systematically
3. Apply minimal disruption strategies
4. Validate model coherence
5. Generate integration documentation
6. Assess stakeholder impact
```

### Validation and Rollback
```
for each integration_point:
    apply_changes()
    validate_consistency()
    if validation_failed:
        execute_rollback()
        log_failure_reason()
    else:
        commit_changes()
        update_integration_log()
```

## Integration Patterns

### Entity Integration Pattern
```markdown
## Existing Entity: [Entity Name]
[Original definition and relationships]

### Integration Changes
- **New Attributes**: [Added attributes from source concepts]
- **Modified Relationships**: [Updated relationship definitions]  
- **Specializations**: [New specialized subtypes]

### Backward Compatibility
- [Existing functionality preserved]
- [Migration path for dependent systems]
```

### Process Integration Pattern
```markdown
## Process: [Process Name]
### Original Process Flow
[Existing process description]

### Integrated Enhancements
[New activities and decision points from requirements]

### Impact Assessment
- **Modified Activities**: [List of changed activities]
- **New Dependencies**: [Additional process dependencies]
- **Stakeholder Effects**: [Affected roles and responsibilities]
```

## Error Handling

### Integration Failures
- **Circular Dependencies**: Detect and report dependency cycles
- **Conflicting Requirements**: Document irreconcilable conflicts
- **Model Inconsistencies**: Validate structural integrity
- **Rollback Failures**: Maintain recovery mechanisms

### Validation Failures
- **Entity Conflicts**: Resolve naming and definitional conflicts
- **Relationship Violations**: Fix broken or invalid relationships
- **Process Inconsistencies**: Align process flow requirements
- **Terminology Mismatches**: Standardize vocabulary usage

## Dependencies
- **domain-alignentities**: Entity mapping and conflict identification
- **domain-proposenewconcepts**: Gap analysis and new concept definitions
- **orgmodel-update**: Target model update execution
- **change-management**: Integration tracking and approval workflow

## Success Criteria
- All integration points successfully processed
- Model coherence validation passes
- Stakeholder impact assessment complete
- Rollback capability tested and available
- EDP methodology compliance verified
- Progressive integration milestones achieved