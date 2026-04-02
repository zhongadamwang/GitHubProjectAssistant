---
name: process-findtopandupdate
description: Identifies top-level requirements in hierarchies and updates them based on detailed analysis insights, domain modeling results, and process changes. Maintains consistency between high-level and detailed requirements while updating relevant orgModel documents to preserve organizational coherence.
license: MIT
---

# Process Find Top And Update

Systematically identifies the highest-level requirements in requirement hierarchies and updates them based on comprehensive analysis insights from domain modeling, process integration, and detailed requirement analysis. Ensures consistency across requirement levels while maintaining orgModel document coherence through coordinated updates and traceability preservation.

## Intent

Implement intelligent requirement hierarchy analysis that identifies top-level requirements, applies insights from detailed analysis processes, and updates both requirement documentation and corresponding orgModel elements. Maintains bidirectional consistency between high-level strategic requirements and detailed implementation requirements while preserving change traceability throughout the organizational model structure.

## Inputs

- **Primary**: `projects/[project-name]/artifacts/Analysis/requirements.json` (processed requirements with hierarchy metadata)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/domain-concepts.json` (domain entities from analysis)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/domain-alignment.json` (entity mappings and alignments)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/process-merge.json` (process integration results)
- **Context**: `projects/[project-name]/artifacts/Analysis/w5h-analysis.json` (comprehensive requirement context)
- **Context**: `projects/[project-name]/artifacts/Analysis/goals.json` (business goals and success criteria)
- **Existing**: `orgModel/*/main.md` (existing high-level organizational processes)
- **Existing**: `orgModel/*/process.md` (detailed process flows)
- **Existing**: `orgModel/*/domain-model.md` (organizational domain models)

## Outputs

**Files Generated:**
- `projects/[project-name]/artifacts/Analysis/top-requirements-update.json` - Structured hierarchy analysis and update plan
- `projects/[project-name]/artifacts/Analysis/top-requirements-update.md` - Human-readable requirement update report
- `projects/[project-name]/artifacts/Analysis/orgmodel-update-plan.md` - OrgModel update coordination plan
- `projects/[project-name]/artifacts/Analysis/requirement-consistency-report.md` - Consistency validation and traceability report

### JSON Structure (`top-requirements-update.json`)

```json
{
  "project_id": "string",
  "update_metadata": {
    "generated_at": "ISO8601",
    "source_requirements": "requirements.json",
    "source_analysis": ["domain-concepts.json", "process-merge.json", "w5h-analysis.json"],
    "hierarchy_analysis_method": "dependency_graph",
    "update_strategy": "insight_integration",
    "consistency_validation": "bidirectional",
    "total_requirements_analyzed": "number",
    "top_level_requirements_identified": "number",
    "requirements_updated": "number",
    "orgmodel_documents_affected": "number",
    "traceability_preservation": "complete",
    "validation_status": "passed|failed|partial"
  },
  "hierarchy_analysis": {
    "requirement_hierarchy": [
      {
        "hierarchy_level": 1,
        "requirement_id": "REQ-TOP-001",
        "requirement_type": "business_goal",
        "title": "Implement Evolutionary Development Capability",
        "current_statement": "Enable team to adapt development practices based on changing requirements",
        "dependency_type": "strategic",
        "child_requirements": ["REQ-002", "REQ-005", "REQ-012"],
        "affects_orgmodel": ["orgModel/01-skill-development/main.md"],
        "stakeholder_impact": "high",
        "update_priority": "critical"
      }
    ],
    "dependency_graph": {
      "nodes": [
        {
          "requirement_id": "REQ-TOP-001",
          "level": 1,
          "type": "strategic",
          "influences": ["REQ-002", "REQ-005"],
          "influenced_by": ["business_goals"]
        }
      ],
      "edges": [
        {
          "from": "REQ-TOP-001",
          "to": "REQ-002",
          "relationship": "decomposes_to",
          "weight": 0.8
        }
      ]
    }
  },
  "insight_integration": {
    "updates_applied": [
      {
        "update_id": "UPD-001",
        "target_requirement": "REQ-TOP-001",
        "update_type": "refinement",
        "source_insights": [
          {
            "source": "domain-concepts.json",
            "insight_type": "entity_identification",
            "specific_insight": "Skill entity identified as core domain concept",
            "integration_approach": "terminology_alignment"
          },
          {
            "source": "process-merge.json",
            "insight_type": "process_integration",
            "specific_insight": "Existing training process can be extended vs replaced",
            "integration_approach": "process_refinement"
          }
        ],
        "original_statement": "Enable team to adapt development practices based on changing requirements",
        "updated_statement": "Implement skill-based evolutionary development framework that extends existing training processes to enable adaptive development practices",
        "rationale": "Domain analysis revealed 'Skill' as central entity; process analysis showed extension opportunity rather than replacement",
        "impact_assessment": {
          "scope_change": "expanded",
          "complexity_change": "increased",
          "risk_change": "decreased",
          "stakeholder_alignment": "improved"
        }
      }
    ],
    "consistency_validations": [
      {
        "validation_id": "CV-001",
        "validation_type": "hierarchical_consistency",
        "parent_requirement": "REQ-TOP-001",
        "child_requirements": ["REQ-002", "REQ-005"],
        "consistency_check": "alignment_validation",
        "status": "consistent",
        "alignment_score": 0.92,
        "issues_identified": [],
        "resolution_actions": []
      }
    ]
  },
  "orgmodel_coordination": {
    "affected_documents": [
      {
        "document_path": "orgModel/01-skill-development/main.md",
        "update_type": "strategic_alignment",
        "update_priority": "high",
        "changes_required": [
          {
            "section": "Process Overview",
            "change_type": "enhancement",
            "current_content": "Team development through structured learning",
            "proposed_content": "Skill-based evolutionary development with adaptive learning pathways",
            "source_requirement": "REQ-TOP-001",
            "validation_required": true
          }
        ],
        "consistency_dependencies": ["orgModel/02-training/process.md"],
        "stakeholder_approval": "pending"
      }
    ],
    "cross_document_consistency": [
      {
        "consistency_id": "CDC-001",
        "documents": ["orgModel/01-skill-development/main.md", "orgModel/02-training/process.md"],
        "consistency_type": "terminology_alignment",
        "alignment_requirement": "Skill terminology must be consistent across documents",
        "current_status": "inconsistent",
        "resolution_plan": "Update training process terminology to align with skill framework",
        "estimated_effort": "0.5 days"
      }
    ]
  },
  "traceability_maintenance": {
    "requirement_lineage": [
      {
        "requirement_id": "REQ-TOP-001",
        "change_history": [
          {
            "change_id": "CHG-001",
            "timestamp": "ISO8601",
            "change_type": "insight_integration",
            "source_analysis": ["domain-concepts", "process-merge"],
            "previous_version": "Enable team to adapt development practices",
            "updated_version": "Implement skill-based evolutionary development framework",
            "change_rationale": "Domain analysis insights integrated",
            "approver": "system_analysis",
            "rollback_data": "stored"
          }
        ],
        "impact_trace": [
          {
            "affected_requirement": "REQ-002",
            "relationship": "child",
            "impact_type": "alignment_update",
            "status": "propagated"
          }
        ]
      }
    ],
    "validation_checkpoints": [
      {
        "checkpoint_id": "VC-001",
        "checkpoint_type": "consistency_validation",
        "scope": "hierarchy_alignment",
        "validation_criteria": "All child requirements align with updated parent",
        "status": "passed",
        "validation_timestamp": "ISO8601"
      }
    ]
  }
}
```

## Core Workflow

### 1. Requirement Hierarchy Analysis

**Identification Process:**
- Parse requirement dependency graphs from processed requirements
- Identify top-level requirements with minimal or no dependencies
- Classify requirements by hierarchy level and strategic importance
- Map requirement relationships and influence patterns

**Hierarchy Classification:**
- **Level 1**: Strategic business goals and high-level outcomes
- **Level 2**: Major functional areas and process definitions  
- **Level 3**: Detailed functional and non-functional requirements
- **Level 4**: Implementation-specific requirements and constraints

### 2. Insight Integration Analysis

**Source Integration:**
- Extract relevant insights from domain concept analysis
- Incorporate process integration findings and alignment results
- Apply W5H analysis context for stakeholder and implementation insights
- Align with extracted business goals and success criteria

**Update Strategy:**
- **Refinement**: Enhance requirement clarity and precision
- **Expansion**: Add scope based on discovered domain concepts
- **Alignment**: Adjust terminology and approach for consistency
- **Simplification**: Reduce complexity based on process integration findings

### 3. Consistency Validation

**Hierarchical Consistency:**
- Validate that updated top-level requirements align with child requirements
- Check for conflicting objectives or contradictory specifications
- Ensure requirement decomposition remains logical and complete
- Verify that changes preserve requirement completeness

**Cross-Reference Validation:**
- Validate consistency with domain models and entity definitions
- Check alignment with process integration results
- Verify stakeholder and goal alignment across requirement levels
- Ensure terminology consistency across all requirement levels

### 4. OrgModel Coordination

**Document Identification:**
- Identify orgModel documents affected by requirement updates
- Map requirement changes to corresponding organizational processes
- Determine cross-document consistency requirements
- Plan coordinated update sequences to maintain coherence

**Update Orchestration:**
- Coordinate with orgmodel-update skill for systematic document updates
- Maintain consistency across main.md, process.md, and domain-model.md files
- Ensure terminology alignment across organizational documentation
- Preserve existing organizational workflows and approval processes

## Usage Patterns

### GitHub Copilot Integration

Use this skill when requirements have been analyzed and you need to update high-level requirements based on detailed insights:

```markdown
"Use process-findtopandupdate skill to identify top-level requirements 
and update them based on domain analysis and process integration results. 
Ensure orgModel documents are updated for consistency."
```

### Integration Sequence

**Recommended Workflow:**
1. Complete requirements-ingest and basic analysis
2. Run domain-extractconcepts and domain-alignentities
3. Execute process-merge for process integration analysis
4. Apply process-findtopandupdate for hierarchy updates
5. Coordinate with orgmodel-update for organizational consistency

### Validation and Rollback

**Validation Process:**
- Automated consistency checking across requirement hierarchy
- Stakeholder validation for strategic requirement changes
- Cross-reference validation with orgModel documents
- Impact assessment for downstream requirement dependencies

**Rollback Capability:**
- Complete requirement version history maintenance
- Point-in-time restoration for failed validations
- Coordinated rollback with orgModel document changes
- Stakeholder notification for rollback scenarios

## Output Format

### Human-Readable Report (Markdown)

```markdown
# Top-Level Requirements Update Report

**Project**: PROJECT-001
**Generated**: 2026-02-19T10:30:00Z
**Analysis Sources**: domain-concepts, process-merge, w5h-analysis
**Requirements Updated**: 3 of 15 top-level requirements

## Executive Summary

Updated 3 strategic requirements based on domain analysis and process integration insights. Key changes include terminology alignment with identified domain entities and process refinement approach based on existing organizational capabilities.

## Updated Requirements

### REQ-TOP-001: Evolutionary Development Capability
**Previous**: Enable team to adapt development practices based on changing requirements
**Updated**: Implement skill-based evolutionary development framework that extends existing training processes to enable adaptive development practices

**Change Rationale**: Domain analysis identified 'Skill' as central entity; process integration revealed extension opportunity rather than replacement approach.

**Impact Assessment**:
- Scope: Expanded to include skill framework
- Complexity: Increased due to framework requirements
- Risk: Decreased through existing process extension
- Stakeholder Alignment: Improved through terminology consistency

## OrgModel Coordination

### Documents Requiring Updates
- `orgModel/01-skill-development/main.md`: Strategic alignment updates
- `orgModel/02-training/process.md`: Terminology consistency updates

### Cross-Document Consistency
- Skill terminology standardization across training and development processes
- Process extension approach alignment with existing organizational workflows

## Validation Results

### Hierarchical Consistency: ✅ PASSED
- All child requirements align with updated parent requirements
- No contradictory objectives identified
- Requirement decomposition remains complete

### Stakeholder Alignment: ✅ VALIDATED
- Updated requirements maintain stakeholder value propositions
- Strategic goals preserved with enhanced implementation approach
```

## Error Handling

**Common Issues:**
- **Circular Dependencies**: Detected through graph analysis with resolution suggestions
- **Inconsistent Updates**: Automatic rollback with stakeholder notification
- **OrgModel Conflicts**: Coordination failure handling with manual resolution paths
- **Missing Context**: Graceful degradation with partial analysis completion

**Recovery Procedures:**
- Automatic validation checkpoint restoration
- Stakeholder notification for manual review requirements
- Partial update completion with clear status reporting
- Integration retry mechanisms for temporary failures

## Dependencies

**Required Prior Skills:**
- requirements-ingest: For normalized requirement input
- domain-extractconcepts: For domain entity identification
- process-merge: For process integration insights

**Coordination Skills:**
- orgmodel-update: For organizational document consistency
- change-management: For tracking requirement evolution

**Supporting Skills:**
- domain-alignentities: For terminology consistency
- goals-extract: For business alignment validation