# EDPS Skill Ecosystem Reference

## Skill Dependency Matrix

This matrix shows which skills depend on outputs from other skills, enabling the Navigator to orchestrate proper execution sequences.

### Primary Dependencies

| Skill | Prerequisites | Outputs Used By | Notes |
|-------|---------------|-----------------|-------|
| project-document-management | None (initiation) | All skills | Creates folder structure |
| requirements-ingest | Project structure | goals-extract, process-w5h, domain-extractconcepts | Entry point for requirements |
| goals-extract | requirements-ingest | process-w5h, process-scopemin, project-planning-tracking | Clarifies objectives |
| process-w5h | requirements-ingest, goals-extract | domain-extractconcepts, process-scopemin, diagram-generatecollaboration | Comprehensive analysis |
| domain-extractconcepts | requirements-ingest, process-w5h | domain-alignentities, diagram-generatecollaboration | Domain understanding |
| domain-alignentities | domain-extractconcepts | domain-proposenewconcepts, diagram-generatecollaboration | Entity alignment |
| domain-proposenewconcepts | domain-alignentities | diagram-generatecollaboration, change-management | Domain evolution |
| diagram-generatecollaboration | domain-extractconcepts, domain-alignentities, process-w5h | project-status-reporting | Visual documentation |
| process-scopemin | goals-extract, process-w5h, domain-extractconcepts | project-planning-tracking | Scope definition |
| process-merge | Multiple requirement sources | change-management, project-status-reporting | Integration workflows |
| project-planning-tracking | goals-extract, process-scopemin | project-status-reporting, change-management | Project management |
| project-status-reporting | Most skills (aggregates outputs) | change-management | Status communication |
| change-management | Any skill creating/modifying artifacts | All skills (tracks changes) | Change tracking |

### Secondary Dependencies (Optional Enhancements)

| Skill A | Skill B | Relationship | Benefit |
|---------|---------|--------------|---------|
| goals-extract | domain-extractconcepts | Bidirectional enhancement | Goals inform domain focus; domain clarifies goal scope |
| process-w5h | diagram-generatecollaboration | Output enhancement | W5H analysis improves diagram accuracy |
| process-scopemin | domain-proposenewconcepts | Planning feedback | Scope constraints influence domain proposals |
| project-planning-tracking | process-merge | Integration planning | Merge activities need scheduling |

## Orchestration Patterns

### Pattern 1: Complete Project Initiation
```
Trigger: "Start new project with requirements"
Sequence:
1. project-document-management (Setup: Create project structure)
2. requirements-ingest (Ingest: Process requirements document)
3. goals-extract (Analyze: Extract business objectives)
4. process-w5h (Analyze: Comprehensive requirement analysis)
5. domain-extractconcepts (Model: Identify domain entities)
6. domain-alignentities (Model: Align with standards) [optional]
7. diagram-generatecollaboration (Document: Create visual models) [optional]

Duration: 30-60 minutes
Complexity: High
Success Rate: 90%
```

### Pattern 2: Requirements Analysis Focus
```
Trigger: "Analyze these requirements thoroughly"
Sequence:
1. requirements-ingest (if not done)
2. goals-extract 
3. process-w5h
4. process-scopemin
5. project-status-reporting (summary)

Duration: 15-30 minutes
Complexity: Medium
Success Rate: 95%
```

### Pattern 3: Domain Modeling Workflow
```
Trigger: "Model the business domain" or "understand domain concepts"
Prerequisite: requirements-ingest completed
Sequence:
1. domain-extractconcepts
2. domain-alignentities
3. domain-proposenewconcepts (if gaps identified)
4. diagram-generatecollaboration
5. change-management (document domain decisions)

Duration: 20-40 minutes
Complexity: Medium
Success Rate: 85%
```

### Pattern 4: Quick Start (MVP)
```
Trigger: "Get started quickly" or "minimum viable analysis"
Sequence:
1. requirements-ingest
2. goals-extract
3. process-scopemin
4. project-planning-tracking (basic plan)

Duration: 10-20 minutes
Complexity: Low
Success Rate: 98%
```

### Pattern 5: Change Integration
```
Trigger: "Process requirement changes" or "update existing project"
Sequence:
1. change-management (document incoming change)
2. process-merge (if multiple sources)
3. [Re-run affected analysis skills based on change scope]
4. project-status-reporting (impact assessment)
5. change-management (finalize change documentation)

Duration: Variable (5-45 minutes)
Complexity: Variable
Success Rate: 80%
```

## Natural Language Intent Mapping

### Project Initiation Intents
- "start new project" → Complete Project Initiation pattern
- "setup project structure" → project-document-management only
- "process requirements document" → requirements-ingest + basic analysis
- "initialize EDPS project" → Complete Project Initiation pattern

### Analysis Intents  
- "analyze requirements" → Requirements Analysis Focus pattern
- "understand domain" → Domain Modeling Workflow pattern
- "extract goals" → goals-extract (+ prerequisites if needed)
- "comprehensive analysis" → Requirements Analysis Focus + Domain Modeling
- "find minimum scope" → process-scopemin (+ prerequisites)

### Visualization Intents
- "create diagrams" → diagram-generatecollaboration (+ prerequisites)
- "visualize collaboration" → diagram-generatecollaboration
- "show system interactions" → diagram-generatecollaboration

### Management Intents
- "plan project" → project-planning-tracking (+ prerequisites)
- "track status" → project-status-reporting
- "manage changes" → change-management
- "merge requirements" → process-merge

### Support Intents
- "create new skill" → skill-creator
- "help me decide" → Interactive guidance mode
- "what should I do next" → Context analysis and recommendation

## Context Recognition Patterns

### Project Stage Detection
1. **Initialization Stage**: No project structure or minimal artifacts
   - Recommend: Complete Project Initiation pattern
   - Priority: Structure creation, requirement processing

2. **Analysis Stage**: Requirements processed, basic analysis started
   - Recommend: Complete analysis workflows (W5H, domain modeling)
   - Priority: Comprehensive understanding

3. **Design Stage**: Analysis complete, moving to solution design
   - Recommend: Collaboration diagrams, scope refinement
   - Priority: Visual modeling, planning

4. **Implementation Stage**: Design artifacts exist, tracking progress
   - Recommend: Change management, status reporting
   - Priority: Progress tracking, change control

5. **Evolution Stage**: Working system, handling changes/enhancements
   - Recommend: Change integration patterns, impact analysis
   - Priority: Change management, requirement merging

### Artifact Detection Cues
- `requirements.md` exists → Requirements processing completed
- `goals.md` exists → Goal extraction completed  
- `domain-concepts.md` exists → Domain analysis started
- `collaboration-diagrams.md` exists → Visual modeling completed
- `project-plan.md` exists → Planning completed
- `Changes/` folder exists → Change management active

### Quality Gates
Before recommending certain skills, ensure prerequisites are met:
- Domain skills require processed requirements
- Collaboration diagrams require domain analysis
- Scope minimization requires goals and W5H analysis
- Project planning requires scope definition
- Status reporting requires multiple completed artifacts

## Error Recovery Strategies

### Common Failure Scenarios

1. **Missing Prerequisite Data**
   - Problem: Skill requires input that doesn't exist
   - Recovery: Auto-invoke prerequisite skill, then retry
   - Example: domain-extractconcepts fails → invoke requirements-ingest

2. **Skill Execution Errors**
   - Problem: Skill encounters processing error
   - Recovery: Suggest alternative approaches or manual intervention
   - Example: PDF parsing fails → suggest manual text extraction

3. **Circular Dependencies**
   - Problem: Skills create circular dependency loops
   - Recovery: Break loop with manual checkpoint or alternative sequence
   - Example: Change management triggering re-analysis loops

4. **Incomplete Analysis**
   - Problem: Skill completes but with poor quality outputs
   - Recovery: Suggest additional analysis or expert review
   - Example: Domain extraction finds too few concepts

5. **Resource Constraints**
   - Problem: Insufficient context window or processing capacity
   - Recovery: Break workflow into smaller chunks, prioritize critical skills
   - Example: Large requirements document → chunk processing

### Recovery Patterns
```
Error Detection → Problem Classification → Recovery Strategy Selection → User Notification → Alternative Path Execution
```

## Performance Optimization

### Skill Execution Priorities
1. **Critical Path Skills**: Requirements ingest, project setup
2. **Foundation Skills**: Goals extract, W5H analysis
3. **Enhancement Skills**: Domain modeling, collaboration diagrams
4. **Optional Skills**: Advanced analysis, status reporting

### Parallel Execution Opportunities
- Domain analysis can run parallel to goal extraction (after requirements ingest)
- Multiple domain skills can run in sequence efficiently  
- Status reporting can aggregate from multiple completed skills
- Change management can run parallel to most other skills

### Resource Management
- Monitor context window usage during orchestration
- Prioritize essential skills when resources constrained
- Cache intermediate results to avoid re-computation
- Use progressive disclosure for complex workflows

---

**Document Version**: 1.0.0
**Last Updated**: 2026-02-17
**Review Cycle**: Monthly
**Dependencies**: EDPS Skill Ecosystem v1.x