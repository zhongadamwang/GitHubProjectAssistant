---
name: domain-alignentities
description: Align extracted domain concepts with existing organizational domain models and standards. Maps new concepts to existing entities, identifies conflicts and inconsistencies in terminology, suggests alignments and standardizations, and produces mapping documentation for consistent domain modeling across projects.
license: MIT
---

# Domain Align Entities

Align extracted domain concepts with existing organizational domain models and standards to ensure consistency and reuse across projects.

## Intent
Systematically compare new domain concepts extracted from requirements against existing organizational domain models, vocabularies, and standards. Identifies mappings, conflicts, and alignment opportunities to maintain consistency across projects while supporting domain model evolution.

## Inputs
- **Primary**: `projects/[project-name]/artifacts/Analysis/domain-concepts.json` (from domain-extractconcepts skill)
- **References**: Organizational domain models (`orgModel/**/*domain-model.md`)
- **References**: Organizational vocabularies (`orgModel/**/*vocabulary.md`)
- **References**: Organizational class diagrams (`orgModel/**/*domain-model.md` containing Mermaid class diagrams)
- **Format**: Structured domain concepts with entities, terminology, relationships, operations, and metadata

## Outputs
**Files Generated:**
- `projects/[project-name]/artifacts/Analysis/domain-alignment.json` - Structured alignment data for programmatic use
- `projects/[project-name]/artifacts/Analysis/domain-alignment.md` - Human-readable alignment report and recommendations

### JSON Structure (`domain-alignment.json`)
```json
{
  "project_id": "string",
  "alignment_metadata": {
    "generated_at": "ISO8601",
    "source_concepts_file": "domain-concepts.json",
    "reference_models": ["orgModel/01-skill-dev/domain-model.md"],
    "reference_vocabularies": ["orgModel/01-skill-dev/vocabulary.md"],
    "total_alignments": "number",
    "conflict_count": "number",
    "alignment_confidence": "0.0-1.0"
  },
  "entity_alignments": [
    {
      "extracted_entity": {
        "id": "ENT-001",
        "name": "User",
        "attributes": ["user_id", "email", "role"],
        "operations": ["authenticate", "updateProfile"],
        "source": "domain-concepts.json"
      },
      "alignment_result": {
        "type": "direct_match|partial_match|new_entity|conflict",
        "target_entity": {
          "name": "Team Member",
          "source": "orgModel/01-skill-dev/domain-model.md",
          "section": "Actors.Primary Actors"
        },
        "confidence": "0.0-1.0",
        "mapping_rationale": "Both represent individuals using system functionality",
        "recommended_action": "map_to_existing|rename_extracted|create_new|resolve_conflict",
        "notes": "Consider if User is more specific than Team Member"
      }
    }
  ],
  "terminology_alignments": [
    {
      "extracted_term": {
        "term": "Authentication",
        "definition": "Process of verifying user identity",
        "source": "domain-concepts.json"
      },
      "alignment_result": {
        "type": "vocabulary_match|vocabulary_conflict|new_term|synonym_detected",
        "target_term": {
          "term": "Skills Assessment",
          "definition": "Systematic evaluation of current skill levels",
          "source": "orgModel/01-skill-dev/vocabulary.md"
        },
        "confidence": "0.0-1.0",
        "relationship": "unrelated|synonym|broader|narrower|similar",
        "recommended_action": "adopt_standard|define_new|clarify_distinction",
        "notes": "No direct relationship - different domains"
      }
    }
  ],
  "relationship_alignments": [
    {
      "extracted_relationship": {
        "id": "REL-001",
        "source_entity": "User",
        "target_entity": "Role",
        "type": "association"
      },
      "alignment_result": {
        "alignment_pattern": "existing_pattern|new_pattern",
        "similar_relationships": [
          {
            "source": "Team Member",
            "target": "Skill Profile",
            "type": "has",
            "model": "orgModel/01-skill-dev/domain-model.md"
          }
        ],
        "recommended_action": "use_existing_pattern|define_new_pattern",
        "notes": "Similar ownership pattern exists in organizational model"
      }
    }
  ],
  "conflicts_detected": [
    {
      "conflict_id": "CONF-001",
      "type": "naming_conflict|definition_conflict|structure_conflict",
      "description": "Term 'User' conflicts with established 'Team Member' terminology",
      "affected_items": ["ENT-001", "termUser"],
      "severity": "high|medium|low",
      "resolution_options": [
        {
          "option": "rename_extracted",
          "description": "Rename 'User' to 'Team Member' in extracted concepts",
          "impact": "Updates project terminology to match organizational standard"
        },
        {
          "option": "create_distinction",
          "description": "Define 'User' as a specialization of 'Team Member'",
          "impact": "Extends organizational model with new concept"
        }
      ]
    }
  ],
  "operation_alignments": [
    {
      "extracted_operation": {
        "entity": "User",
        "operation": "authenticate",
        "parameters": ["username", "password"],
        "return_type": "boolean",
        "source": "domain-concepts.json"
      },
      "alignment_result": {
        "type": "method_match|method_conflict|new_method",
        "target_operation": {
          "entity": "Team Member",
          "operation": "validateCredentials", 
          "parameters": ["credentials"],
          "source": "orgModel/01-skill-dev/domain-model.md"
        },
        "confidence": "0.0-1.0",
        "similarity": "identical|similar|different",
        "recommended_action": "use_existing|rename_operation|define_new",
        "notes": "Similar functionality, consider parameter alignment"
      }
    }
  ],
  "recommendations": [
    {
      "id": "REC-001",
      "type": "standardization|extension|clarification",
      "priority": "high|medium|low",
      "description": "Adopt organizational 'Team Member' terminology instead of 'User'",
      "impact": "Improves consistency across projects",
      "implementation": "Update domain-concepts.json and project documentation"
    }
  ]
}
```

### Markdown Structure (`domain-alignment.md`)
```markdown
# Domain Alignment Report

**Project**: [project_id]  
**Generated**: [timestamp]  
**Source Concepts**: domain-concepts.json ([count] entities, [count] concepts)  
**Reference Models**: [list of organizational models]  
**Alignment Confidence**: [score]/1.0

## Executive Summary

**Total Alignments**: [count]  
**Direct Matches**: [count] | **Partial Matches**: [count] | **New Entities**: [count]  
**Conflicts Detected**: [count] | **Recommendations**: [count]

## Entity Alignments

### ✅ Direct Matches
Extracted entities that directly align with existing organizational entities.

#### User → Team Member *(ENT-001)*
**Confidence**: 0.95  
**Source Model**: orgModel/01-skill-dev/domain-model.md  
**Rationale**: Both represent individuals using system functionality  
**Action**: ✅ Map to existing organizational entity

### ⚠️ Partial Matches  
Extracted entities with similar but not identical organizational counterparts.

#### ProjectOwner → Team Lead *(ENT-002)*
**Confidence**: 0.75  
**Source Model**: orgModel/01-skill-dev/domain-model.md  
**Differences**: ProjectOwner broader scope, Team Lead specific to team management  
**Action**: 🔄 Consider creating specialization relationship

### 🆕 New Entities
Extracted entities with no organizational counterparts - potential model extensions.

#### SkillFramework *(ENT-003)*
**Domain Area**: Learning Management  
**Rationale**: Project-specific concept not in organizational model  
**Action**: ➕ Propose addition to organizational model

## Operation Alignments

### ✅ Method Matches
Extracted operations that align with existing organizational entity methods.

#### User.authenticate() → Team Member.validateCredentials() *(OP-001)*
**Confidence**: 0.85  
**Source Entity**: Team Member (orgModel/01-skill-dev/domain-model.md)  
**Parameter Mapping**: [username, password] → [credentials]  
**Action**: ✅ Align with organizational operation pattern

### 🆕 New Operations
Extracted operations with no organizational counterparts.

#### User.updateProfile() *(OP-002)*
**Entity**: User  
**Parameters**: [profile_data]  
**Return Type**: void  
**Action**: 🆕 Propose as new capability

## Terminology Alignments

### ✅ Vocabulary Matches
| Extracted Term | Organization Term | Confidence | Action |
|----------------|-------------------|------------|---------|
| Assessment | Skills Assessment | 0.90 | Adopt standard term |

### ⚠️ Conflicting Definitions
| Term | Extracted Definition | Org Definition | Resolution |
|------|---------------------|----------------|------------|
| Validation | Process verification | Skill demonstration requirement | Clarify context distinction |

### 🆕 New Terminology
Terms not in organizational vocabulary that could be valuable additions.

## Relationship Patterns

### Existing Patterns Applied
- **Ownership Pattern**: User ↔ Role similar to Team Member ↔ Skill Profile
- **Validation Pattern**: Assessment validates Skills (organizational model)

### New Patterns Identified
- **Framework Hierarchy**: SkillFramework contains multiple Skills
- **Progress Tracking**: Assessment tracks learning progression

## Conflicts & Resolutions

### 🔴 High Priority Conflicts

#### CONF-001: Naming Conflict - User vs Team Member
**Issue**: Project uses "User" while organization uses "Team Member"  
**Impact**: Terminology inconsistency across projects  
**Resolution Options**:
1. ✅ **Recommended**: Rename to "Team Member" for consistency
2. Define "User" as specialization of "Team Member"

### 🟡 Medium Priority Issues

#### CONF-002: Definition Scope - Assessment scope differences
**Issue**: Project Assessment broader than organizational Skills Assessment  
**Impact**: Potential confusion in assessment processes  
**Resolution**: Define Assessment as supertype of Skills Assessment

## Recommendations

### 🎯 High Priority Actions

1. **Standardize Entity Names** (Impact: High)
   - Rename "User" → "Team Member"
   - Update all references and relationships

2. **Adopt Organizational Vocabulary** (Impact: Medium)
   - Use "Skills Assessment" instead of generic "Assessment"
   - Maintain traceability in concept definitions

### 🔄 Domain Model Evolution

1. **Proposed Extensions**
   - Add "SkillFramework" entity to organizational model
   - Define relationship patterns for framework hierarchies

2. **Vocabulary Additions**
   - Add project-specific terms to organizational vocabulary
   - Document context-specific usage patterns

---
**Traceability**: Aligned concepts from domain-concepts.json against [org models]  
**Next Steps**: Review recommendations with domain stakeholders  
**Generated**: [timestamp]
```

## Core Alignment Process

### 1. Entity Mapping
**Pattern Matching**:
- **Direct name matches**: Identical or near-identical entity names
- **Semantic similarity**: Entities with similar descriptions and attributes
- **Functional equivalence**: Entities serving similar roles in domain
- **Structural patterns**: Entities with comparable attribute sets

**Conflict Detection**:
- **Naming conflicts**: Same name, different meanings
- **Definition conflicts**: Similar entities, incompatible definitions  
- **Attribute mismatches**: Same entity, different attribute sets
- **Relationship inconsistencies**: Conflicting relationship patterns

### 2. Terminology Standardization
**Vocabulary Alignment**:
- **Exact matches**: Terms with identical definitions
- **Synonym detection**: Different terms, same meaning
- **Broader/narrower relationships**: Hierarchical term relationships
- **Context conflicts**: Same term, different contexts

**Standardization Rules**:
- **Prefer organizational terms** over project-specific variants
- **Maintain precision** - don't force inappropriate generalizations
- **Document distinctions** when terms serve different purposes
- **Suggest vocabulary extensions** for valuable new concepts

### 3. Relationship Pattern Analysis
**Pattern Recognition**:
- **Ownership patterns**: Entity A "has" Entity B
- **Dependency patterns**: Entity A "requires" Entity B
- **Hierarchy patterns**: Entity A "contains" Entity B
- **Process patterns**: Entity A "triggers" Entity B

**Consistency Checking**:
- **Verify similar relationships** use consistent naming
- **Check cardinality consistency** across similar patterns
- **Validate relationship semantics** against organizational models

### 4. Incremental Model Evolution
**Model Extension Strategy**:
- **Assess new concept value** for organizational reuse
- **Propose model additions** for broadly applicable concepts
- **Document specializations** for project-specific needs
- **Maintain backward compatibility** with existing models

**Change Management**:
- **Track proposed changes** to organizational models
- **Document impact assessment** for model modifications
- **Coordinate with domain stakeholders** for approval process
- **Version control integration** for model evolution tracking

## Quality Assurance

### Alignment Validation
- **Semantic consistency**: Aligned entities maintain coherent meaning
- **Relationship integrity**: Mapped relationships preserve logical structure
- **Terminology accuracy**: Aligned terms maintain definitional precision
- **Completeness checking**: All extracted concepts processed for alignment

### Confidence Scoring
- **High (0.8-1.0)**: Clear semantic match with strong evidence
- **Medium (0.5-0.79)**: Reasonable match with some uncertainty
- **Low (0.2-0.49)**: Weak match requiring human review

For detailed alignment patterns and conflict resolution strategies, see [alignment-patterns.md](references/alignment-patterns.md).

## Usage Pattern
```
1. Call after domain-extractconcepts skill completion
2. Load domain-concepts.json from project artifacts
3. Analyze against organizational domain models and vocabularies
4. Generate alignment analysis and recommendations
5. Create domain-alignment.json and domain-alignment.md
6. Update domain-model.md class diagrams with aligned concepts (optional)
7. Feed results to domain-proposenewconcepts and model-integration skills
```

## Integration with Diagram Updates
When alignment results indicate changes to domain models, this skill can trigger class diagram updates:

### Diagram Update Integration
```json
{
  "update_class_diagrams": true,
  "target_domain_models": ["orgModel/01-skill-dev/domain-model.md"],
  "alignment_mode": "rename|merge|extend|create_new",
  "preserve_styling": true
}
```

### Class Diagram Alignment Process
1. **Identify diagram impacts** from entity and operation alignments
2. **Generate updated class diagrams** reflecting organizational standards
3. **Update domain-model.md** with aligned entity names and relationships
4. **Maintain diagram consistency** across organizational models
5. **Preserve existing styling** and layout where possible

## Cross-Skill Integration

### Input Dependencies:
- domain-extractconcepts skill → domain-concepts.json
- Organizational domain models (orgModel/**/*domain-model.md)
- Organizational vocabularies (orgModel/**/*vocabulary.md)

### Output Consumers:
- domain-proposenewconcepts skill ← domain-alignment.json
- model-integration skill ← domain-alignment.json
- diagram-generatecollaboration skill ← alignment results (for diagram updates)
- orgmodel-update skill ← alignment recommendations

### Diagram Generation Integration:
When updating domain models based on alignment results:
- Entity alignments trigger class diagram updates with proper styling categories
- Operation alignments modify method signatures in diagrams
- Terminology standardization updates entity and attribute names
- Relationship alignments adjust diagram associations
- Styling consistency maintained with organizational standards:
  - `classDef actor fill:#e1f5fe` - for actors and user roles
  - `classDef entity fill:#f3e5f5` - for business entities and data structures  
  - `classDef enum fill:#fff3e0` - for enumeration and value types
  - `classDef ai fill:#e8f5e8` - for AI and automation components