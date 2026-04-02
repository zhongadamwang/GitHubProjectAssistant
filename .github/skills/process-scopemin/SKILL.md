---
name: process-scopemin
description: Identifies minimum viable scope for requirements, helping prioritize essential features and define MVP boundaries. Analyzes requirements to classify core vs. optional features and produces scoped requirement sets for iterative development.
license: MIT
---

# Process Scope Minimization

Transforms comprehensive requirements into minimum viable product (MVP) scope by analyzing business value, dependencies, and implementation complexity to identify core features and establish iterative development boundaries.

## Intent

Identify the minimum viable product (MVP) scope from a comprehensive requirements set by classifying features as `core`, `enhancing`, or `optional` based on business value, inter-feature dependencies, and implementation complexity. Produces scoped requirement sets for iterative delivery planning.

## Inputs

- **Requirements**: Raw requirements text, document, or `requirements.json`/`requirements.md` (from `requirements-ingest`)
- **Project ID**: `project_id`
- **Optional**: `goals.json` from `goals-extract` — used to weight business-value scoring

## Outputs

- `outputs/projects/{project_id}/Analysis/scope-analysis.md` — Scope analysis report with MVP recommendation (primary, for downstream skills)
- `outputs/projects/{project_id}/Analysis/scope-analysis.json` — Structured scope classifications (machine processing)

## Core Function

**Input**: Requirements documents + project_id + business context
**Primary Output**: Markdown scope analysis report with MVP recommendations
**Secondary Output**: JSON format with structured scope classifications for machine processing
**Output Destination**: 
- Markdown: `outputs/projects/{project_id}/Analysis/scope-analysis.md` (primary for downstream)
- JSON: `outputs/projects/{project_id}/Analysis/scope-analysis.json` (machine processing)
**Directory Structure**: Auto-created project folders with Analysis subfolder containing scope analysis, feature prioritization matrices, and MVP roadmaps

## Usage

**GitHub Copilot Integration (Recommended):**
```markdown
Use this skill directly in Copilot by providing requirements documents.
Copilot will automatically analyze requirements to identify minimum viable scope.

Example prompt:
"Use process-scopemin skill to analyze these requirements and identify the minimum viable product scope, classifying features as core, enhancing, or optional based on business value and dependencies."
```

**Traditional Script Approach:**
```python
from process_scopemin import ScopeMinimizer

minimizer = ScopeMinimizer()
result = minimizer.analyze_scope(requirements_text, "MY-PROJECT")
# Creates: 
#   outputs/projects/MY-PROJECT/scope-analysis.md (primary)
#   outputs/projects/MY-PROJECT/scope-analysis.json (secondary)
```

**Command Line:**
```bash
python process_scopemin.py PROJECT-001 requirements.md
# Saves to: outputs/projects/PROJECT-001/
#   📄 scope-analysis.md (Markdown - for downstream skills)
#   📋 scope-analysis.json (JSON - for machine processing)
```

## Output Schema

**Primary Format (Markdown)** - Scope Analysis Report:

```markdown
# Minimum Viable Scope Analysis

**Project**: PROJECT-001
**Source**: requirements.md
**Generated**: 2026-02-15T10:30:00Z
**Analysis Method**: Business Value + Dependency + Complexity Assessment

## Executive Summary

**MVP Definition**: [Brief description of the minimum viable product]
**Core Features Count**: X features identified as essential
**Enhancement Features**: Y features for post-MVP phases
**Optional Features**: Z features for future consideration
**Estimated MVP Effort**: [Time/resource estimate]

## Core Features (MVP Essential)

### Feature: [Feature Name]
- **Business Value**: [High/Medium/Low] - [Justification]
- **Technical Complexity**: [High/Medium/Low]
- **Dependencies**: [List of prerequisite features]
- **User Impact**: [Critical functionality description]
- **Risk of Exclusion**: [What happens if not included in MVP]

## Enhancement Features (Post-MVP Phase 1)

### Feature: [Feature Name]
- **Business Value**: [High/Medium/Low] - [Justification]
- **Technical Complexity**: [High/Medium/Low]
- **Dependencies**: [List of prerequisite features]
- **Enhancement Rationale**: [Why this builds on MVP]
- **Target Release**: [Suggested phase/timeline]

## Optional Features (Future Consideration)

### Feature: [Feature Name]
- **Business Value**: [High/Medium/Low] - [Justification]
- **Technical Complexity**: [High/Medium/Low]
- **Nice-to-Have Rationale**: [Why this is optional]
- **Potential ROI**: [Return on investment analysis]

## Feature Prioritization Matrix

| Feature | Business Value | Technical Complexity | Dependencies | MVP Category |
|---------|----------------|---------------------|--------------|--------------|
| [Name]  | [H/M/L]        | [H/M/L]            | [List/None]  | [Core/Enhance/Optional] |

## MVP Roadmap

### Phase 0 - Foundation
- [Core infrastructure features]
- [Essential service foundations]

### Phase 1 - MVP Launch
- [Minimum viable feature set]
- [Core user workflows]

### Phase 2 - Enhancement
- [First enhancement features]
- [User experience improvements]

### Phase 3 - Expansion
- [Advanced features consideration]
- [Platform scaling capabilities]

## Scope Boundaries

### In Scope (MVP)
- [Clearly defined included functionality]
- [Core user journeys supported]
- [Essential integrations]

### Out of Scope (MVP)
- [Explicitly excluded functionality]
- [Deferred advanced features]
- [Future integration considerations]

## Risk Assessment

### High Priority Risks
- [Risks that could impact MVP viability]
- [Mitigation strategies]

### Medium Priority Risks
- [Risks that could delay enhancement phases]
- [Monitoring approaches]

## Success Criteria

### MVP Launch Success
- [Measurable criteria for MVP success]
- [User adoption metrics]
- [Business value validation]

### Enhancement Phase Success
- [Criteria for proceeding to enhancement features]
- [Performance and usage thresholds]

## Recommendations

### Immediate Actions
1. [Priority development recommendations]
2. [Resource allocation suggestions]
3. [Risk mitigation priorities]

### Future Considerations
1. [Long-term strategic recommendations]
2. [Technology evolution planning]
3. [Market expansion opportunities]

---
**Traceability**: Requirements tracked to original specifications
**Review Date**: [Scheduled scope review date]
**Stakeholder Approval**: [Pending/Approved status]
```

**Secondary Format (JSON)** - Machine Processing:

```json
{
  "metadata": {
    "project_id": "PROJECT-001",
    "source_file": "requirements.md",
    "generated_timestamp": "2026-02-15T10:30:00Z",
    "analysis_method": "business_value_dependency_complexity",
    "skill_version": "1.0.0"
  },
  "mvp_summary": {
    "definition": "Brief MVP description",
    "core_features_count": 5,
    "enhancement_features_count": 8,
    "optional_features_count": 12,
    "estimated_effort": "3-4 months"
  },
  "features": [
    {
      "id": "FEAT-001",
      "name": "Feature Name",
      "category": "core|enhancement|optional",
      "business_value": {
        "score": "high|medium|low",
        "justification": "Value proposition explanation"
      },
      "technical_complexity": {
        "score": "high|medium|low",
        "implementation_notes": "Technical considerations"
      },
      "dependencies": [
        "FEAT-002", "FEAT-003"
      ],
      "user_impact": "Impact description",
      "priority_score": 85,
      "phase_assignment": "mvp|phase1|phase2|future"
    }
  ],
  "roadmap": {
    "phase0_foundation": ["FEAT-001", "FEAT-002"],
    "phase1_mvp": ["FEAT-003", "FEAT-004"],
    "phase2_enhancement": ["FEAT-005", "FEAT-006"],
    "phase3_expansion": ["FEAT-007", "FEAT-008"]
  },
  "scope_boundaries": {
    "in_scope": [
      "Core functionality description",
      "Essential user workflows"
    ],
    "out_of_scope": [
      "Advanced features deferred",
      "Future integrations"
    ]
  },
  "risks": [
    {
      "id": "RISK-001",
      "priority": "high|medium|low",
      "description": "Risk description",
      "impact": "Impact on scope",
      "mitigation": "Mitigation strategy"
    }
  ],
  "success_criteria": {
    "mvp_launch": [
      "User adoption target",
      "Business value metric"
    ],
    "enhancement_phase": [
      "Performance threshold",
      "Feature usage metric"
    ]
  },
  "traceability": [
    {
      "feature_id": "FEAT-001",
      "source_requirements": ["REQ-001", "REQ-003"],
      "justification": "Traceability explanation"
    }
  ]
}
```

## Analysis Methodology

### 1. Feature Classification Algorithm

**Input Analysis**:
- Parse requirements to identify distinct features and capabilities
- Extract user stories, functional requirements, and business objectives
- Identify stakeholder priorities and constraints

**Classification Criteria**:
- **Core Features**: Essential for basic product operation, high business value, fundamental user needs
- **Enhancement Features**: Improves user experience, medium-high business value, builds on core functionality
- **Optional Features**: Nice-to-have capabilities, lower immediate business value, represents future opportunities

### 2. Business Value Assessment

**Value Scoring Approach**:
- **High Value**: Direct revenue impact, critical user workflow, competitive differentiation
- **Medium Value**: Operational efficiency, user satisfaction improvement, market positioning
- **Low Value**: Convenience features, advanced use cases, future market preparation

**Assessment Factors**:
- Revenue impact potential
- User adoption likelihood
- Competitive advantage creation
- Operational cost reduction
- Strategic market positioning

### 3. Dependency Analysis

**Dependency Types**:
- **Technical Dependencies**: Infrastructure, APIs, data models, security frameworks
- **Business Dependencies**: Regulatory requirements, partner integrations, compliance needs
- **User Dependencies**: Workflow prerequisites, training requirements, adoption prerequisites

**Dependency Impact**:
- Features with many dependencies → Consider for later phases
- Features that are dependencies for others → Prioritize for MVP
- Circular dependencies → Resolve through architectural decisions

### 4. Complexity Evaluation

**Complexity Factors**:
- **Technical Implementation**: Code complexity, integration requirements, testing needs
- **Resource Requirements**: Development time, specialized skills, infrastructure needs
- **Risk Profile**: Technical unknowns, external dependencies, change likelihood

**Complexity Scoring**:
- **High Complexity**: New technology, complex integrations, significant unknowns
- **Medium Complexity**: Standard implementation, known patterns, manageable risks
- **Low Complexity**: Well-understood technology, minimal integration, low risk

### 5. MVP Boundary Definition

**Boundary Criteria**:
- Minimum set of features that provide standalone value
- Complete user workflows from start to finish
- Sufficient functionality to validate business hypothesis
- Foundation for iterative enhancement

**Boundary Validation**:
- Can users accomplish core objectives?
- Does the product solve a complete problem?
- Is there sufficient value for market acceptance?
- Can the product sustain itself while adding features?

## Implementation Strategy

### Phase-Based Development

**Phase 0 - Foundation** (Pre-MVP):
- Core infrastructure and architecture
- Essential security and compliance frameworks
- Basic data models and APIs
- Development and deployment pipelines

**Phase 1 - MVP Launch**:
- Core feature implementation
- Essential user workflows
- Basic user interface and experience
- Fundamental integrations

**Phase 2 - Enhancement**:
- User experience improvements
- Additional workflows and capabilities
- Performance optimizations
- Extended integrations

**Phase 3 - Expansion**:
- Advanced features and capabilities
- Platform scaling and optimization
- New market segments and use cases
- Innovation and differentiation features

### Iterative Refinement Process

**Continuous Scope Assessment**:
- Regular review of feature priorities
- Business value re-evaluation based on market feedback
- Technical complexity updates based on implementation learning
- User feedback integration for priority adjustment

**Scope Change Management**:
- Formal process for scope additions/modifications
- Impact assessment for scope changes
- Stakeholder approval workflow
- Documentation and traceability maintenance

## Integration Points

**Upstream Skills**:
- `requirements-ingest`: Source requirements for scope analysis
- `goals-extract`: Business objectives inform prioritization
- `process-w5h`: Stakeholder and context analysis influences scoping

**Downstream Skills**:
- `project-planning-tracking`: MVP roadmap feeds into project schedules
- `diagram-generatecollaboration`: Scoped features inform system design
- `change-management`: Scope changes trigger change management processes

**Collaboration Skills**:
- `domain-extractconcepts`: Domain model alignment with scope boundaries
- `domain-alignentities`: Ensure scoped features align with domain architecture

## Quality Assurance

### Validation Checklist

**Scope Completeness**:
- [ ] All requirements categorized (core/enhancement/optional)
- [ ] Business value justification provided for all features
- [ ] Dependencies identified and analyzed
- [ ] Technical complexity assessed

**MVP Viability**:
- [ ] Core features form coherent, complete product
- [ ] User workflows are end-to-end functional
- [ ] Business value is demonstrable and measurable
- [ ] Technical implementation is feasible with available resources

**Traceability Maintenance**:
- [ ] All scope decisions traceable to source requirements
- [ ] Feature classifications justified with business rationale
- [ ] Priority decisions documented with supporting analysis
- [ ] Scope boundaries clearly defined and communicated

### Success Metrics

**Process Effectiveness**:
- Scope analysis completion time
- Stakeholder approval rate for MVPs defined
- Feature priority accuracy (validated post-implementation)
- Sprint planning efficiency improvement

**Business Impact**:
- MVP time-to-market improvement
- Development resource optimization
- User satisfaction with phased delivery
- Business value realization rate

---

*This skill integrates with the broader requirements analysis and project planning ecosystem, providing focused scope minimization capabilities that enable efficient MVP development and iterative product enhancement.*