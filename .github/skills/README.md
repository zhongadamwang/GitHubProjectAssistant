# EDPS Skills Documentation

A comprehensive guide to using the Evolutionary Development Process System (EDPS) skills with GitHub Copilot.

## Quick Start 🚀

**Essential Prompt Pattern:**
```
@workspace Use the [skill-name] skill to [specific action] on [input description].

[Your content here]

Project ID: [YOUR-PROJECT-ID]
```

## Skills Overview

### S1 — Project Initialization
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `project-document-management` | Initialize project folder structure | Project details | Project folder tree | → `requirements-ingest` |
| `project-planning-tracking` | Create project plans and track milestones | Project scope | project-plan.md, task-tracking.md | → Execution |
| `project-status-reporting` | Generate status dashboards and reports | Project artifacts | Status reports | → Stakeholder review |

### S2 — Requirements Processing
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `requirements-ingest` | Normalize any format to structured requirements | Raw docs (PDF/Word/MD) | requirements.json/md | → `goals-extract` |
| `requirements-merge` | Combine multiple requirement sources with conflict resolution | Multiple requirement docs | unified-requirements.json/md | → `goals-extract` |
| `goals-extract` | Extract business goals and success criteria | requirements.json | goals.json/md | → `process-w5h` |
| `process-w5h` | Who/What/When/Where/Why/How analysis | requirements.json | w5h-analysis.json/md | → `domain-extractconcepts` |
| `process-scopemin` | Define MVP and minimal viable scope | requirements.json, goals.json | scope-analysis.json/md | → Planning |

### S3 — Domain Analysis
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `domain-extractconcepts` | Identify domain entities and concepts | requirements.json | domain-concepts.json/md | → `domain-alignentities` |
| `domain-alignentities` | Align concepts with existing org domain models | domain-concepts.json | domain-alignment.json/md | → `domain-proposenewconcepts` |
| `domain-proposenewconcepts` | Propose new concepts to fill domain gaps | domain-alignment.json | domain-newconcepts.json/md | → `diagram-generatecollaboration` |

### S4 — Planning
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `plan-derivetasks` | Convert requirements into actionable tasks | requirements.json, goals.json | task-breakdown.json/md | → `plan-estimateeffort` |
| `plan-estimateeffort` | Generate effort estimates with confidence levels | task-breakdown.json | effort-estimates.json/md | → `plan-buildschedule` |
| `plan-buildschedule` | Generate project schedules with critical path | task-breakdown.json, effort-estimates.json | project-schedule.json/md | → Execution |

### S5 — Design & Visualization
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `diagram-generatecollaboration` | Generate EDPS collaboration diagrams with stereotype classification and boundary validation (VR-1–VR-4 authoritative source) | domain artifacts | collaboration-diagrams.json/md, boundary_validation_report.json | → `hierarchy-management` |
| `hierarchy-management` | Decompose control-type participants into sub-processes; manage sub-folders, cross-references, hierarchy index, and scale metrics | collaboration.md | Level N+1 sub-folder, hierarchy-metadata.json, hierarchy-index.md | → `documentation-automation` |
| `documentation-automation` | Auto-generate level-calibrated main.md, process.md, collaboration.md, domain-model.md stubs for each hierarchy level | hierarchy-metadata.json, parent collaboration.md | Four doc files per level | → `orgmodel-update` |
| `migration-tools` | Non-destructively migrate flat Project 1 diagrams to hierarchical boundary format | Existing collaboration.md files | Enhanced diagrams, migration-log.md | → `hierarchy-management` |

### S6 — Process & Change Management
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `process-merge` | Merge process models with existing organizational models | Multiple process docs | process-merge.json/md | → `model-integration` |
| `process-findtopandupdate` | Identify and update top-level requirements in hierarchies | Analysis files, hierarchy | top-requirements-update.json/md | → `orgmodel-update` |
| `change-management` | Track, document, and manage requirement changes | Change requests (conversation) | Change documentation | → Update artifacts |
| `change-impact-analysis` | Trace change propagation across hierarchy levels with risk classification | hierarchy-metadata.json, change inputs | change-impact-report.json/md | → `orgmodel-update` |

### S7 — Model Integration
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `model-integration` | Integrate new domain models into existing org structures | domain-alignment.json, domain-newconcepts.json | model-integration.json/md | → `orgmodel-update` |
| `orgmodel-update` | Update organizational model documents (with EDPS-Hierarchy Guard preventing overwrite of hierarchy-aware files) | Model artifacts | orgModel document updates | → `integration-testing` |

### S8 — Compliance & Validation
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `hierarchy-validation` | Validate hierarchy structural integrity — 14 rules across cross-level consistency, cross-reference integrity, naming/structure (authoritative structural source) | hierarchy-metadata.json + orgModel folders | hierarchy-validation-report.json/md | → `edps-compliance` |
| `edps-compliance` | Score EDPS methodology compliance — 11 rules (VR-1–4 delegated to `diagram-generatecollaboration`; HR-2/6 native; EP-1–4 native); gates on `hierarchy-validation` PASS | boundary_validation_report.json, hierarchy-validation-report.json | edps-compliance-report.json/md (0–100 score) | → `integration-testing` |
| `integration-testing` | Validate end-to-end workflows across all skills | All skill artifacts | test-reports.json/md | → Production |

### S9 — External Integration
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `github-issue-create-update` | Create and update GitHub Issues from local task markdown files | Local task files | GitHub Issues (created/updated) | ⇄ `github-issue-sync-status` |
| `github-issue-sync-status` | Sync local task status from GitHub Issue state changes | GitHub Issues | Updated local task files | ⇄ `github-issue-create-update` |

### S10 — Orchestration & Meta
| Skill | Purpose | Input | Output | Next Step |
|-------|---------|-------|---------|-----------|
| `edps-skill-navigator` | Intelligently orchestrate all EDPS skills from natural language requests (v1.2.0) | Natural language intent | Skill execution plan, workflow guidance | → Execute recommended skills |
| `skill-creator` | Scaffold new EDPS-compliant skills following SKILL.md standard | Skill requirements | New SKILL.md + reference docs | → Implement skill |

## Common Workflows

### 🏁 New Project Setup (5 minutes)
```markdown
@workspace Use project-document-management skill to initialize a new project structure:

Project Name: Customer Portal Redesign
Project ID: CPR-2024
Description: Modernize customer portal with improved UX and mobile support
Stakeholders: Product, Engineering, Design, Customer Success
```

### 📑 Requirements Analysis Pipeline (15 minutes)
```markdown
@workspace Execute this requirements analysis workflow:

1. Use requirements-ingest skill on attached requirements document
2. Use goals-extract skill on the resulting requirements
3. Use process-w5h skill for comprehensive analysis
4. Use domain-extractconcepts skill to identify key entities

Project ID: CPR-2024

[Attach your requirements document here]
```

### 🔄 Iterative Planning (10 minutes)
```markdown
@workspace Help me scope this project for MVP delivery:

1. Use process-scopemin skill to identify core vs optional features
2. Use project-planning-tracking skill to create a delivery timeline
3. Generate initial task breakdown

Project: Customer Portal Redesign (CPR-2024)
Timeline: 3 months
Team Size: 5 developers, 1 designer
```

### 📊 Complete Planning Pipeline (20 minutes)
```markdown
@workspace Execute full planning workflow from requirements to schedule:

1. Use plan-derivetasks skill to break down requirements into actionable tasks
2. Use plan-estimateeffort skill to estimate effort for all tasks
3. Use plan-buildschedule skill to generate project schedule with critical path

Project: Customer Portal Redesign (CPR-2024)
Team: 5 developers (3 senior, 2 junior), 1 designer
Deadline: 12 weeks from start
```

## Advanced Usage Patterns

### Skill Chaining
```markdown
@workspace Execute this complete requirements-to-design workflow:

1. Use requirements-ingest → goals-extract → process-w5h → domain-extractconcepts → domain-alignentities → diagram-generatecollaboration

Input: [Your requirements document]
Project: [PROJECT-ID]

Please chain these skills automatically and provide final collaboration diagrams.
```

### Change Management
```markdown
@workspace Handle this requirement change:

1. Use change-management skill to document this change request
2. Update affected artifacts using process-merge skill
3. Generate impact analysis with project-status-reporting skill

Change: Add real-time notifications to the customer portal
Project: CPR-2024
```

## File Structure Convention

All skills output to standardized locations:
```
projects/[PROJECT-ID]/
├── artifacts/
│   ├── Analysis/          # Core analysis outputs
│   │   ├── requirements.json/md
│   │   ├── goals.json/md  
│   │   ├── w5h-analysis.json/md
│   │   ├── domain-concepts.json/md
│   │   ├── domain-alignment.json/md
│   │   ├── scope-analysis.json/md
│   │   ├── collaboration-diagrams.json/md
│   │   ├── task-breakdown.json/md      # Planning outputs
│   │   ├── effort-estimates.json/md
│   │   ├── project-schedule.json/md
│   │   └── critical-path-analysis.md
│   ├── Changes/           # Change management
│   │   └── [DATE]-[TYPE]-CHG-[NUM]-[description].md
│   ├── Requirements/      # Input documents
│   └── UI Mockups/        # Design artifacts
└── tasks/                 # Project planning outputs
    ├── project-plan.md
    ├── task-tracking.md
    └── T[NN]-[task-name].md
```

## Tips for Success 💡

### Effective Prompting
- **Be specific**: Include project IDs and clear input descriptions
- **Chain related skills**: Use workflows instead of individual skills when possible
- **Validate outputs**: Check generated artifacts before proceeding to next steps
- **Iterate**: Refine inputs based on skill outputs for better results

### Project Organization
- **Start with structure**: Always use `project-document-management` first
- **Follow the pipeline**: Requirements → Goals → Analysis → Domain → Planning
- **Version control**: Skills create timestamped outputs for change tracking
- **Review outputs**: Each skill generates both JSON (machine) and MD (human) formats

### Common Patterns
```markdown
# Quick Analysis
@workspace Quick requirements analysis using requirements-ingest and goals-extract skills:
[Content]

# Full Domain Analysis  
@workspace Complete domain analysis pipeline (requirements-ingest → goals-extract → process-w5h → domain-extractconcepts):
[Content]

# Change Request
@workspace Process this change using change-management skill and update project status:
[Change description]
```

## Next Steps

1. **Browse individual skill guides**: Each skill has detailed documentation in its folder
2. **Try the examples**: Start with simple single-skill invocations
3. **Build workflows**: Chain skills together for comprehensive analysis
4. **Customize**: Adapt prompt patterns to your specific domain and needs

See `/examples/` folder for complete project walkthroughs and `/workflows/` for advanced skill combination patterns.