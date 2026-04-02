---
name: process-w5h
description: Analyzes requirements using the Who, What, When, Where, Why, How framework for comprehensive requirement analysis from multiple perspectives.
license: MIT
---

# Process W5H Analysis

Transforms requirements into comprehensive W5H analysis (Who, What, When, Where, Why, How) providing stakeholder identification, functional analysis, timeline extraction, context understanding, purpose clarification, and implementation approach definition.

## Intent

Apply the W5H analytical framework to requirements to systematically extract: stakeholder roles and responsibilities (Who), functional scope (What), timeline and trigger conditions (When), operational context (Where), business rationale and success criteria (Why), and implementation approaches (How).

## Inputs

- **Requirements**: Raw requirements text, document, or `requirements.json`/`requirements.md` (from `requirements-ingest`)
- **Project ID**: `project_id`

## Outputs

- `outputs/projects/{project_id}/Analysis/w5h-analysis.md` — W5H analysis report (primary, for downstream skills)
- `outputs/projects/{project_id}/Analysis/w5h-analysis.json` — Structured W5H data for machine processing

## Core Function

**Input**: Requirements documents + project_id
**Primary Output**: Markdown W5H analysis report matching specification format
**Secondary Output**: JSON format with structured W5H elements for machine processing
**Output Destination**: 
- Markdown: `outputs/projects/{project_id}/Analysis/w5h-analysis.md` (primary for downstream)
- JSON: `outputs/projects/{project_id}/Analysis/w5h-analysis.json` (machine processing)
**Directory Structure**: Auto-created project folders with Analysis subfolder containing W5H analysis reports, stakeholder maps, and implementation guides

## Usage

**GitHub Copilot Integration (Recommended):**
```markdown
Use this skill directly in Copilot by providing requirements documents.
Copilot will automatically analyze requirements using the W5H framework.

Example prompt:
"Use process-w5h skill to analyze this requirements document and extract Who, What, When, Where, Why, and How elements with stakeholder identification and implementation approaches."
```

**Traditional Script Approach:**
```python
from process_w5h import W5HAnalyzer

analyzer = W5HAnalyzer()
result = analyzer.analyze_requirements(requirements_text, "MY-PROJECT")
# Creates: 
#   outputs/projects/MY-PROJECT/w5h-analysis.md (primary)
#   outputs/projects/MY-PROJECT/w5h-analysis.json (secondary)
```

**Command Line:**
```bash
python process_w5h.py PROJECT-001 requirements.md
# Saves to: outputs/projects/PROJECT-001/
#   📄 w5h-analysis.md (Markdown - for downstream skills)
#   📋 w5h-analysis.json (JSON - for machine processing)
```

## Output Schema

**Primary Format (Markdown)** - W5H Analysis Report:

```markdown
# W5H Requirements Analysis

**Project**: PROJECT-001
**Source**: requirements.md
**Generated**: 2026-02-08T14:30:00Z
**Analysis Framework**: Who, What, When, Where, Why, How

## WHO - Stakeholders and Roles

### Primary Stakeholders
- **End Users**: Software developers using evolutionary development practices
- **Project Team**: Internal development team members
- **Management**: Leadership overseeing development process improvement

### Secondary Stakeholders
- **Quality Assurance**: Testing teams validating new practices
- **Operations**: Teams responsible for deployment and maintenance

### Roles and Responsibilities
| Role | Responsibilities | Authority Level |
|------|-----------------|----------------|
| Development Team Lead | Oversee skill implementation | Decision maker |
| Team Members | Learn and apply new skills | Contributors |

## WHAT - Functional and Non-Functional Requirements

### Functional Requirements
- **F1**: Define core competencies for evolutionary development
- **F2**: Create learning pathways for skill development
- **F3**: Implement adaptive planning methodologies

### Non-Functional Requirements
- **NF1**: Process must integrate with existing tooling
- **NF2**: Training materials must be accessible to all team members
- **NF3**: Implementation timeline must be flexible

### System Boundaries
- **In Scope**: Internal team skill development, process adaptation
- **Out of Scope**: External vendor training, new tooling procurement

## WHEN - Timeline and Milestones

### Project Timeline
- **Phase 1**: Skill framework documentation (Weeks 1-2)
- **Phase 2**: Team capability assessment (Weeks 3-4)
- **Phase 3**: Learning plan creation (Weeks 5-6)
- **Phase 4**: Pilot implementation (Weeks 7-10)

### Critical Milestones
- **M1**: Framework completion - Week 2
- **M2**: Assessment completion - Week 4
- **M3**: Pilot launch - Week 7

### Dependencies and Constraints
- Must complete stakeholder interviews before assessment
- Pilot implementation depends on framework approval

## WHERE - Context and Environment

### Operational Environment
- **Development Environment**: VS Code with existing toolchain
- **Team Structure**: Cross-functional development teams
- **Geographic Distribution**: Primarily local team with some remote members

### System Integration Points
- **Version Control**: Integration with current Git workflows
- **CI/CD**: Compatibility with existing deployment pipelines
- **Documentation**: Alignment with current documentation standards

### Physical and Virtual Boundaries
- **Team Workspace**: Hybrid office/remote environment
- **Development Infrastructure**: Cloud-based development environment
- **Communication Channels**: Existing team communication tools

## WHY - Purpose and Business Rationale

### Business Drivers
- **Primary**: Improve development agility and responsiveness
- **Secondary**: Enhance team capabilities and job satisfaction
- **Strategic**: Position team for future evolutionary development challenges

### Success Metrics
- **Quantitative**: 25% reduction in feature delivery time
- **Qualitative**: Improved team confidence in handling changing requirements
- **Business**: Increased customer satisfaction with development responsiveness

### Value Proposition
- Enhanced ability to respond to changing market conditions
- Improved team resilience and adaptability
- Better alignment between development practices and business needs

## HOW - Implementation Approach and Methods

### Technical Approach
- **Methodology**: Incremental skill development with regular feedback
- **Framework**: Competency-based learning model
- **Assessment**: 360-degree skill evaluation process

### Implementation Strategy
1. **Discovery Phase**: Research best practices and assess current state
2. **Design Phase**: Create competency framework and learning paths
3. **Development Phase**: Build training materials and assessment tools
4. **Deployment Phase**: Roll out pilot program with selected team
5. **Evaluation Phase**: Measure results and refine approach

### Risk Mitigation
- **Risk**: Team resistance to change
  - **Mitigation**: Involve team in framework design, emphasize benefits
- **Risk**: Time constraints
  - **Mitigation**: Phase implementation, integrate with existing work

### Quality Assurance
- Regular checkpoint reviews with stakeholders
- Continuous feedback collection from participants
- Measurable success criteria at each phase

## Summary

This W5H analysis provides a comprehensive view of the evolutionary development skill building project, ensuring all critical dimensions are considered in planning and implementation.
```

**Secondary Format (JSON)** - For Machine Processing:

```json
{
  "project_id": "PROJECT-001",
  "generated_at": "2026-02-08T14:30:00Z",
  "analysis_framework": "W5H",
  "who": {
    "primary_stakeholders": [
      {
        "role": "End Users",
        "description": "Software developers using evolutionary development practices",
        "influence": "high",
        "interest": "high"
      }
    ],
    "secondary_stakeholders": [
      {
        "role": "Quality Assurance", 
        "description": "Testing teams validating new practices",
        "influence": "medium",
        "interest": "medium"
      }
    ],
    "roles_responsibilities": [
      {
        "role": "Development Team Lead",
        "responsibilities": ["Oversee skill implementation"],
        "authority_level": "Decision maker"
      }
    ]
  },
  "what": {
    "functional_requirements": [
      {
        "id": "F1",
        "description": "Define core competencies for evolutionary development",
        "priority": "high",
        "category": "core_functionality"
      }
    ],
    "non_functional_requirements": [
      {
        "id": "NF1", 
        "description": "Process must integrate with existing tooling",
        "type": "integration_constraint",
        "priority": "high"
      }
    ],
    "scope": {
      "in_scope": ["Internal team skill development", "process adaptation"],
      "out_of_scope": ["External vendor training", "new tooling procurement"]
    }
  },
  "when": {
    "timeline": {
      "total_duration": "10 weeks",
      "phases": [
        {
          "name": "Phase 1",
          "description": "Skill framework documentation",
          "duration": "Weeks 1-2",
          "deliverables": ["Framework document"]
        }
      ]
    },
    "milestones": [
      {
        "id": "M1",
        "name": "Framework completion",
        "target_date": "Week 2",
        "dependencies": []
      }
    ]
  },
  "where": {
    "environment": {
      "development": "VS Code with existing toolchain",
      "team_structure": "Cross-functional development teams",
      "geographic": "Primarily local team with some remote members"
    },
    "integration_points": {
      "version_control": "Integration with current Git workflows",
      "cicd": "Compatibility with existing deployment pipelines",
      "documentation": "Alignment with current documentation standards"
    }
  },
  "why": {
    "business_drivers": {
      "primary": "Improve development agility and responsiveness",
      "secondary": "Enhance team capabilities and job satisfaction",
      "strategic": "Position team for future evolutionary development challenges"
    },
    "success_metrics": {
      "quantitative": "25% reduction in feature delivery time",
      "qualitative": "Improved team confidence in handling changing requirements",
      "business": "Increased customer satisfaction with development responsiveness"
    }
  },
  "how": {
    "technical_approach": {
      "methodology": "Incremental skill development with regular feedback",
      "framework": "Competency-based learning model",
      "assessment": "360-degree skill evaluation process"
    },
    "implementation_phases": [
      {
        "phase": "Discovery",
        "description": "Research best practices and assess current state",
        "activities": ["Stakeholder interviews", "Best practice research"]
      }
    ],
    "risks": [
      {
        "risk": "Team resistance to change",
        "probability": "medium",
        "impact": "high",
        "mitigation": "Involve team in framework design, emphasize benefits"
      }
    ]
  }
}
```

## GitHub Copilot Integration

### Direct Usage in Copilot Chat
Simply paste your requirements document and ask:

```
@workspace Use the process-w5h skill to analyze this document:

[PASTE YOUR REQUIREMENTS DOCUMENT HERE]

Project ID: MY-PROJECT-001

Perform comprehensive W5H analysis extracting:
- WHO: Stakeholders, roles, responsibilities
- WHAT: Functional/non-functional requirements, scope
- WHEN: Timeline, milestones, dependencies
- WHERE: Context, environment, integration points
- WHY: Business drivers, success metrics, value proposition
- HOW: Implementation approach, methodology, risk mitigation

Return structured analysis with both strategic and tactical insights.
```

### Copilot Prompt Template
```
Analyze requirements document using W5H framework methodology:

1. WHO Analysis: Identify all stakeholders, map roles and responsibilities, determine authority levels
2. WHAT Analysis: Extract functional and non-functional requirements, define system boundaries
3. WHEN Analysis: Establish timeline, identify milestones, map dependencies
4. WHERE Analysis: Define operational context, identify integration points, map environments
5. WHY Analysis: Clarify business drivers, define success metrics, articulate value proposition
6. HOW Analysis: Define implementation approach, identify methodology, plan risk mitigation

Output comprehensive W5H analysis following JSON schema with strategic insights and tactical details.
```

**Advantages of Copilot Integration:**
- ✅ **Comprehensive Analysis**: Ensures all W5H dimensions are thoroughly examined
- ✅ **Stakeholder Clarity**: Clear identification of who is involved and their roles
- ✅ **Strategic Alignment**: Links requirements to business objectives and success metrics
- ✅ **Implementation Ready**: Provides actionable insights for project execution

## W5H Analysis Framework

### WHO Analysis Prompt
```
Identify stakeholders using this framework:

- **Primary Stakeholders**: Direct users, customers, decision makers
- **Secondary Stakeholders**: Influenced parties, support teams, vendors
- **Internal Roles**: Team members, management, operations
- **External Entities**: Customers, partners, regulators

Map responsibilities, authority levels, and influence/interest matrix.
```

### WHAT Analysis Prompt
```
Categorize requirements using:

- **Functional**: Core features, user actions, system behaviors
- **Non-Functional**: Performance, security, usability, scalability
- **Business Rules**: Logic, workflows, decision points
- **Constraints**: Budget, technology, regulatory limitations
- **Assumptions**: Dependencies, prerequisites, accepted facts

Define clear scope boundaries (in/out of scope).
```

### WHEN Analysis Prompt
```
Extract temporal elements:

- **Timeline**: Project phases, duration estimates
- **Milestones**: Critical checkpoints, deliverables
- **Dependencies**: Sequential requirements, blocking factors
- **Deadlines**: Hard constraints, market windows
- **Iterations**: Planned releases, feedback cycles

Create realistic schedule with buffer time.
```

### WHERE Analysis Prompt
```
Define operational context:

- **Physical Environment**: Hardware, infrastructure, locations
- **System Environment**: Software, platforms, integrations
- **Process Environment**: Workflows, methodologies, standards
- **Organizational Environment**: Team structure, culture, policies

Map integration points and environmental constraints.
```

### WHY Analysis Prompt
```
Clarify purpose and rationale:

- **Business Drivers**: Market needs, strategic goals, competitive advantage
- **Problem Statement**: Current pain points, inefficiencies, gaps
- **Success Metrics**: Quantifiable measures, KPIs, ROI targets
- **Value Proposition**: Benefits, outcomes, stakeholder value

Ensure alignment between requirements and business objectives.
```

### HOW Analysis Prompt
```
Define implementation approach:

- **Technical Approach**: Architecture, methodologies, frameworks
- **Process Approach**: Project management, quality assurance, validation
- **Resource Approach**: Team composition, skill requirements, budget
- **Risk Management**: Identified risks, mitigation strategies, contingencies

Provide actionable implementation roadmap.
```

## Processing Rules

1. **Comprehensive Coverage**: All six W5H dimensions must be addressed
2. **Stakeholder Focus**: Clear identification of who is involved and their interests
3. **Business Alignment**: Strong connection between requirements and business objectives
4. **Actionable Insights**: Analysis leads to concrete implementation guidance
5. **Risk Awareness**: Identification of potential challenges and mitigation strategies
6. **Dual Output**: Markdown (primary for downstream) + JSON (machine processing)
7. **Traceability**: Clear links back to source requirements
8. **Iterative Refinement**: Analysis can be updated as requirements evolve