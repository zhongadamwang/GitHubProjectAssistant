# EDPS Skills Documentation Index

Complete navigation guide for EDPS (Evolutionary Development Process System) skills documentation.

**Version**: 2.0.0 | **Last Updated**: 2026-03-16 | **Skill Count**: 31 (1 deprecated)

> **Consolidation Note (March 2026)**: Following the Project 1 + Project 3 integration review, all skills now include canonical `## Intent`, `## Inputs`, and `## Outputs` sections. `hierarchy-management` absorbs `migration-tools` via `--op migrate`. `change-impact-analysis → change-management` contract is formalised via `normalized_risk_level`. See [EDPS_Skills_Validation_Report.md](../../EDPS_Skills_Validation_Report.md) for full details.

## 📚 Documentation Structure

### 🚀 Getting Started
- **[Main Skills README](README.md)** - Overview and quick start guide
- **[Prompt Patterns Reference](examples/prompt-patterns-reference.md)** - Quick reference for common prompt patterns  
- **[Best Practices Guide](examples/best-practices-guide.md)** - Expert guidance and troubleshooting

### 📖 Detailed Usage Guides
- **[Requirements Processing Examples](examples/requirements-processing-examples.md)** - Document ingestion, analysis, and goal extraction
- **[Domain Modeling Examples](examples/domain-modeling-examples.md)** - Concept extraction, alignment, and visualization
- **[Planning & Management Examples](examples/planning-and-management-examples.md)** - Project setup, planning, and change control
- **[Workflow Integration Examples](examples/workflow-integration-examples.md)** - Advanced skill combinations and orchestration

---

## 🎯 Quick Navigation by Use Case

### 👋 New to EDPS Skills?
**Start here**: [Main README](README.md) → [Requirements Processing Examples](examples/requirements-processing-examples.md)

**Learning path**:
1. Read overview and essential prompt patterns
2. Try basic single-skill examples  
3. Practice simple skill chains
4. Advance to complex workflows

### 📋 Processing Requirements?
**Go to**: [Requirements Processing Examples](examples/requirements-processing-examples.md)

**Key patterns**:
- Document ingestion: `requirements-ingest`
- Goal extraction: `goals-extract`  
- Stakeholder analysis: `process-w5h`
- Pipeline: `requirements-ingest → goals-extract → process-w5h`

### 🎯 Domain Modeling?
**Go to**: [Domain Modeling Examples](examples/domain-modeling-examples.md)

**Key patterns**:
- Concept extraction: `domain-extractconcepts`
- System alignment: `domain-alignentities`
- Innovation planning: `domain-proposenewconcepts`
- Visualization: `diagram-generatecollaboration`

### 📊 Project Management?
**Go to**: [Planning & Management Examples](examples/planning-and-management-examples.md)

**Key patterns**:
- Project setup: `project-document-management`
- Planning: `project-planning-tracking`
- Scope management: `process-scopemin`
- Change control: `change-management`
- Status reporting: `project-status-reporting`

### 🔄 Advanced Workflows?
**Go to**: [Workflow Integration Examples](examples/workflow-integration-examples.md)

**Key patterns**:
- Full project lifecycle (90+ minutes)
- MVP definition workflows  
- Change impact assessment
- Enterprise portfolio integration
- Continuous improvement processes

### 🔧 Need Help?
**Go to**: [Best Practices Guide](examples/best-practices-guide.md)

**Troubleshooting**:
- Common issues and solutions
- Quality assurance practices
- Performance optimization
- Expert-level tips

---

## 🎨 Skills by Category

### 📋 Requirements & Analysis
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **requirements-ingest** | Transform any format to structured requirements | [Individual skill docs](.github/skills/requirements-ingest/SKILL.md) |
| **goals-extract** | Extract business goals and success criteria | [Individual skill docs](.github/skills/goals-extract/SKILL.md) |
| **process-w5h** | WHO/What/When/Where/Why/How analysis | [Individual skill docs](.github/skills/process-w5h/SKILL.md) |
| **process-merge** | Merge multiple requirement sources | [Individual skill docs](.github/skills/process-merge/SKILL.md) |
| **process-findtopandupdate** | Identify and update top-level requirements in hierarchies | [Individual skill docs](.github/skills/process-findtopandupdate/SKILL.md) |
| **requirements-merge** | Combine multiple requirement sources with conflict resolution | [Individual skill docs](.github/skills/requirements-merge/SKILL.md) |

### 🎯 Domain Modeling
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **domain-extractconcepts** | Identify domain entities and concepts | [Individual skill docs](.github/skills/domain-extractconcepts/SKILL.md) |
| **domain-alignentities** | Align concepts with existing systems | [Individual skill docs](.github/skills/domain-alignentities/SKILL.md) |
| **domain-proposenewconcepts** | Propose new concepts for gaps | [Individual skill docs](.github/skills/domain-proposenewconcepts/SKILL.md) |
| **diagram-generatecollaboration** | Generate system interaction diagrams | [Individual skill docs](.github/skills/diagram-generatecollaboration/SKILL.md) |
| **model-integration** | Integrate new domain models into existing organizational structures | [Individual skill docs](.github/skills/model-integration/SKILL.md) |
| **hierarchy-management** | Decompose control-type participants into sub-processes; manage folder structure, metadata, and cross-reference navigation; `--op migrate` absorbs `migration-tools` for non-destructive P1→P3 upgrades | [Individual skill docs](.github/skills/hierarchy-management/SKILL.md) |
| **documentation-automation** | Auto-generate `main.md`, `process.md`, `collaboration.md`, and `domain-model.md` for each process hierarchy level with level-appropriate content; takes precedence over `orgmodel-update` for the four shared files in hierarchy-managed folders | [Individual skill docs](.github/skills/documentation-automation/SKILL.md) |
| **migration-tools** | ~~DEPRECATED~~ — superseded by `hierarchy-management --op migrate`; retained for backward compatibility during transition | [Individual skill docs](.github/skills/migration-tools/SKILL.md) |

### ✅ Compliance & Validation
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **edps-compliance** | Validate EDPS methodology compliance (VR-1–4 delegated to `diagram-generatecollaboration`, structural checks delegated to `hierarchy-validation` via pre-condition gate); own rules: HR-2/HR-6 and EP-1–4; generates scored reports with remediation guidance | [Individual skill docs](.github/skills/edps-compliance/SKILL.md) |
| **hierarchy-validation** | **Authoritative structural integrity checker.** Validates cross-level type consistency (HV-1–5), cross-reference link integrity (HX-1–5), and naming/structure rules (HN-1–4). All other skills delegate file/link integrity checks here. Supports full-tree and incremental single-branch modes with `--fix` auto-repair | [Individual skill docs](.github/skills/hierarchy-validation/SKILL.md) |
| **change-impact-analysis** | Trace change propagation across hierarchy levels (CI-1–5, CR-1–3); emits `change-impact-report.json` with `normalized_risk_level` field directly consumable by `change-management`; supports `--mode what-if` (safe preview) and `--mode apply` (auto-fix navigational links) | [Individual skill docs](.github/skills/change-impact-analysis/SKILL.md) |

### 📊 Planning & Management  
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **project-document-management** | Initialize project structure | [Individual skill docs](.github/skills/project-document-management/SKILL.md) |
| **project-planning-tracking** | Create plans and track progress | [Individual skill docs](.github/skills/project-planning-tracking/SKILL.md) |
| **project-status-reporting** | Generate status reports | [Individual skill docs](.github/skills/project-status-reporting/SKILL.md) |
| **plan-derivetasks** | Convert requirements into actionable development tasks | [Individual skill docs](.github/skills/plan-derivetasks/SKILL.md) |
| **plan-estimateeffort** | Generate effort estimates using multiple methodologies | [Individual skill docs](.github/skills/plan-estimateeffort/SKILL.md) |
| **plan-buildschedule** | Generate project schedules with dependencies and critical path | [Individual skill docs](.github/skills/plan-buildschedule/SKILL.md) |
| **process-scopemin** | Define MVP and scope boundaries | [Individual skill docs](.github/skills/process-scopemin/SKILL.md) |
| **change-management** | Track and manage changes; accepts `change-impact-report.json` with `normalized_risk_level` from `change-impact-analysis` directly as `risk_level` in `affected_documents` | [Individual skill docs](.github/skills/change-management/SKILL.md) |

### 🔗 Integration & Automation
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **github-issue-create-update** | Create and update GitHub Issues from local task files with dual CLI/API support | [Individual skill docs](.github/skills/github-issue-create-update/SKILL.md) |
| **github-issue-sync-status** | Sync local task status from GitHub Issue state changes with format preservation | [Individual skill docs](.github/skills/github-issue-sync-status/SKILL.md) |

### 🧠 Meta Skills
| Skill | Purpose | Documentation |
|-------|---------|---------------|
| **edps-skill-navigator** | Navigate and orchestrate skills | [Individual skill docs](.github/skills/edps-skill-navigator/SKILL.md) |
| **skill-creator** | Create new EDPS skills | [Individual skill docs](.github/skills/skill-creator/SKILL.md) |
| **integration-testing** | Comprehensive testing and validation of all EDPS skills | [Individual skill docs](.github/skills/integration-testing/SKILL.md) |
| **orgmodel-update** | Update organizational model documents and folder structures | [Individual skill docs](.github/skills/orgmodel-update/SKILL.md) |

---

## 🚀 Common Workflows Quick Reference

### ⚡ 15-Minute Quick Start
```markdown
@workspace Execute quick project foundation:
1. Use project-document-management → project structure
2. Use requirements-ingest → process requirements  
3. Use goals-extract → business objectives

Project: [PROJECT-NAME] ([PROJECT-ID])
[Input requirements]
```

### 🎯 30-Minute MVP Definition
```markdown
@workspace Define MVP scope:
1. Use process-w5h → stakeholder analysis
2. Use process-scopemin → feature prioritization
3. Use project-planning-tracking → MVP timeline

Business constraints: [timeline/budget/resources]
Input: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json
```

### 🔄 45-Minute Domain Analysis
```markdown
@workspace Complete domain modeling:
1. Use domain-extractconcepts → identify entities
2. Use domain-alignentities → align with existing systems  
3. Use domain-proposenewconcepts → fill gaps
4. Use diagram-generatecollaboration → visualize

Industry: [INDUSTRY-TYPE]
Integration: [EXISTING-SYSTEMS]
Input: projects/[PROJECT-ID]/artifacts/Analysis/
```

### 📊 60-Minute Full Analysis Pipeline
```markdown
@workspace Execute complete analysis pipeline:
requirements-ingest → goals-extract → process-w5h → domain-extractconcepts → domain-alignentities → diagram-generatecollaboration → process-scopemin

Project: [PROJECT-NAME] ([PROJECT-ID])
[Input requirements document]
```

### 🚀 Full Analysis-to-Planning Pipeline (End-to-End)
```markdown
@workspace Execute full EDPS analysis-to-planning pipeline:

Phase 1 — Setup:    project-document-management
Phase 2 — Ingest:   requirements-ingest → goals-extract ‖ process-w5h → process-scopemin
Phase 3 — Domain:   domain-extractconcepts → domain-alignentities → domain-proposenewconcepts
Phase 4 — Design:   diagram-generatecollaboration → hierarchy-management → documentation-automation
Phase 5 — Process:  process-merge → process-findtopandupdate ‖ change-impact-analysis
Phase 6 — Model:    model-integration → orgmodel-update
Phase 7 — Plan:     plan-derivetasks → plan-estimateeffort → plan-buildschedule
Phase 8 — Validate: hierarchy-validation → edps-compliance ‖ integration-testing → project-status-reporting

(‖ = parallel steps; see workflow-templates.json for full parallel opportunity map)

Project: [PROJECT-NAME] ([PROJECT-ID])
[Input requirements document]
```

---

## 📖 Reading Guide by Experience Level

### 🆕 Beginner (New to EDPS)
**Recommended reading order**:
1. [Main README](README.md) - Understand skills overview and basic patterns
2. [Prompt Patterns Reference](examples/prompt-patterns-reference.md) - Learn essential prompt structures
3. [Requirements Processing Examples](examples/requirements-processing-examples.md) - Start with basic document processing
4. Practice with simple single-skill invocations

**First skills to master**: `requirements-ingest`, `goals-extract`, `project-document-management`

### 🚀 Intermediate (Familiar with Individual Skills)
**Focus areas**:
1. [Domain Modeling Examples](examples/domain-modeling-examples.md) - Learn concept extraction and alignment
2. [Planning & Management Examples](examples/planning-and-management-examples.md) - Master project management workflows
3. [Workflow Integration Examples](examples/workflow-integration-examples.md) - Practice skill chaining
4. [Best Practices Guide](examples/best-practices-guide.md) - Improve quality and efficiency

**Skills to develop**: Domain modeling skills, project management integration, workflow orchestration

### 💡 Advanced (Regular EDPS User)
**Advanced topics**:
1. [Workflow Integration Examples](examples/workflow-integration-examples.md) - Master complex orchestration
2. [Best Practices Guide](examples/best-practices-guide.md) - Expert-level optimization  
3. Enterprise-scale pattern development
4. Custom skill creation with [skill-creator](d:\GitHub_Zhongadamwang\AI_Slowcooker\.github\skills\skill-creator\SKILL.md)

**Focus**: Enterprise integration, portfolio management, continuous improvement

### 🏢 Enterprise (Organization-Wide Implementation)
**Strategic guidance**:
1. Portfolio-level skill orchestration
2. Enterprise domain alignment strategies
3. Governance and compliance integration  
4. Training and adoption programs
5. Custom skill development for organizational needs

---

## 🔗 Related Resources

### Individual Skill Documentation
Each skill has detailed documentation in its respective folder:
- **Skill Definition**: Core purpose and capabilities
- **Input/Output Specifications**: Detailed parameter and format information
- **Usage Examples**: Skill-specific examples and patterns
- **Integration Guidance**: How skill connects to workflows

### GitHub Integration
- **Repository Structure**: How skills integrate with project management
- **Change Management**: Version control and artifact tracking
- **Collaboration Patterns**: Team usage and review workflows

### Development Resources
- **Skill Creation**: Guidelines for building new EDPS skills
- **Testing Patterns**: Quality assurance and validation approaches
- **Integration APIs**: Technical integration with external systems

---

## 📞 Getting Help

### Documentation Issues
- Missing examples or unclear guidance
- Request for additional use cases
- Suggestions for improvement

### Skill Usage Questions
- How to combine skills effectively
- Troubleshooting poor output quality  
- Optimization for specific domains

### Advanced Implementation
- Enterprise-scale deployment
- Custom skill development  
- Integration with existing tools

**Remember**: EDPS skills work best when used as integrated workflows with rich context. Start simple, build competency, then advance to complex orchestration patterns.

---

**Quick Start**: [Main README](README.md) | **Examples**: [Prompt Patterns](examples/prompt-patterns-reference.md) | **Help**: [Best Practices](examples/best-practices-guide.md)