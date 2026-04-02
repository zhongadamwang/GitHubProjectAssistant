# EDPS Skills - Prompt Patterns Reference

Quick reference for effective prompt patterns with EDPS skills in GitHub Copilot.

## 🚀 Essential Prompt Patterns

### Basic Single Skill Invocation
```markdown
@workspace Use the [skill-name] skill to [specific action]:

[Your content/context here]

Project ID: [PROJECT-ID]
```

### Skill Chain/Pipeline
```markdown
@workspace Execute this skill pipeline:

1. Use [skill-1] skill to [action-1]
2. Use [skill-2] skill on output from step 1 to [action-2]
3. Use [skill-3] skill to [action-3]

[Input content]

Project ID: [PROJECT-ID]
```

### Context-Enhanced Invocation
```markdown
@workspace Use [skill-name] skill with this context:

Business Context:
- [Key business information]
- [Constraints and requirements]
- [Stakeholder information]

Technical Context:
- [Integration requirements]
- [Technology stack]
- [Performance requirements]

Use skill to [specific action].

Input: [source material]
Project ID: [PROJECT-ID]
```

---

## 📋 Requirements Processing Patterns

### Document Ingestion
```markdown
@workspace Use requirements-ingest skill to process this document:

[Document content or attachment]

Project ID: [PROJECT-ID]

Focus on:
- Atomic requirement extraction
- Functional vs non-functional classification
- Source traceability
- Glossary term identification
```

### Multi-Source Requirements
```markdown
@workspace Use requirements-ingest skill to merge these requirement sources:

Source 1: [Business Requirements Document]
Source 2: [Technical Specifications]
Source 3: [Stakeholder Interview Notes]

Resolve conflicts and maintain source traceability.
Project ID: [PROJECT-ID]
```

### Goals and Objectives Focus
```markdown
@workspace Use goals-extract skill with business objective alignment:

Business Objectives:
- [Primary business goal]
- [Success metrics]
- [Constraints and assumptions]

Extract goals from: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json

Focus on measurable outcomes and KPIs.
```

### Comprehensive Analysis
```markdown
@workspace Use process-w5h skill for comprehensive analysis:

Stakeholder Context:
- Primary Users: [user types]
- Secondary Users: [support users]
- Administrators: [admin types]
- External: [external parties]

Analyze: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json

Focus on stakeholder touchpoints and implementation approaches.
```

---

## 🎯 Domain Modeling Patterns

### Core Concept Extraction
```markdown
@workspace Use domain-extractconcepts skill to identify key business entities:

Industry Context: [industry/domain type]
Business Focus: [core business areas]

Input: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json

Extract:
- Core business entities and attributes
- Relationships between entities
- Business rules and constraints
- Domain-specific terminology
```

### Enterprise Alignment
```markdown
@workspace Use domain-alignentities skill with enterprise context:

Existing Systems:
- [System 1]: [entity types]
- [System 2]: [entity types]
- [System 3]: [entity types]

Standards:
- [Industry standard 1]
- [Compliance requirement 1]

Align concepts from: projects/[PROJECT-ID]/artifacts/Analysis/domain-concepts.json
```

### Innovation and Gap Analysis
```markdown
@workspace Use domain-proposenewconcepts skill for strategic planning:

Strategic Direction:
- [Future capability 1]
- [Innovation area 1]
- [Competitive advantage focus]

Current State: projects/[PROJECT-ID]/artifacts/Analysis/domain-alignment.json

Identify gaps and propose future-state concepts for 2-year roadmap.
```

### System Visualization
```markdown
@workspace Use diagram-generatecollaboration skill to create interaction models:

Focus Areas:
- User journey mapping
- System integration flows
- Data flow between entities
- Stakeholder collaboration patterns

Input Sources:
- projects/[PROJECT-ID]/artifacts/Analysis/domain-concepts.json
- projects/[PROJECT-ID]/artifacts/Analysis/w5h-analysis.json

Generate Mermaid diagrams for architecture and workflow visualization.
```

---

## 📊 Planning and Management Patterns

### Project Initialization
```markdown
@workspace Use project-document-management skill to initialize project:

Project Details:
- Name: [Project Name]
- ID: [PROJECT-ID]
- Description: [Brief description]
- Timeline: [Duration]
- Team Size: [Number and roles]
- Budget: [Budget information]

Stakeholders:
- [Role]: [Name]
- [Role]: [Name]

Create complete project structure with templates and initial documentation.
```

### Comprehensive Planning
```markdown
@workspace Use project-planning-tracking skill for detailed planning:

Project Context:
- Timeline: [Duration with milestones]
- Team: [Size and skill composition]
- Dependencies: [External and internal]
- Constraints: [Budget, timeline, resource]

Deliverables:
1. [Deliverable 1]
2. [Deliverable 2]
3. [Deliverable 3]

Create task breakdown with resource allocation and timeline.
Project ID: [PROJECT-ID]
```

### Scope Definition and MVP
```markdown
@workspace Use process-scopemin skill to define MVP scope:

Business Constraints:
- Timeline: [Hard deadlines]
- Budget: [Firm limits]
- Resources: [Team limitations]
- Risk Tolerance: [High/Medium/Low]

Business Goals (Priority Order):
1. [Primary goal with success metric]
2. [Secondary goal with success metric]
3. [Tertiary goal with success metric]

Input: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json

Define MVP boundary with feature prioritization matrix.
```

---

## 🔄 Change and Status Patterns

### Change Request Processing
```markdown
@workspace Use change-management skill to process change request:

Change Details:
- Type: [SCOPE-CHG/PROC-CHG/TECH-CHG]
- Description: [Change description]
- Requestor: [Person/Role]
- Justification: [Business reason]

Current Project Status:
- Timeline: [Current phase, % complete]
- Budget: [Spent vs. allocated]
- Resources: [Team status]
- Risks: [Known risks]

Assess impact on timeline, budget, scope, and risks.

Project ID: [PROJECT-ID]
```

### Executive Status Reporting
```markdown
@workspace Use project-status-reporting skill for executive dashboard:

Report Type: [Executive/Stakeholder/Team]
Audience: [Specific audience]
Frequency: [Weekly/Monthly/Quarterly]

Current Metrics:
- Timeline: [% complete, milestone status]
- Budget: [Spent, remaining, burn rate]
- Quality: [Metrics and indicators]
- Risks: [Current risk status]

Business Impact: [Progress toward business objectives]

Generate summary with key achievements, risks, and next steps.
Project ID: [PROJECT-ID]
```

---

## 🎯 Advanced Pattern Combinations

### Complete New Project Setup (15 minutes)
```markdown
@workspace Execute complete new project setup:

1. Use project-document-management skill to create structure
2. Use requirements-ingest skill to process attached requirements
3. Use goals-extract skill for business objective identification
4. Use project-planning-tracking skill for initial planning

Project: [Project Name] ([PROJECT-ID])
Context: [Business context and constraints]

[Attach requirements document]
```

### Requirements to Domain Pipeline (25 minutes)
```markdown
@workspace Execute requirements to domain analysis pipeline:

1. Use requirements-ingest → goals-extract → process-w5h 
2. Use domain-extractconcepts → domain-alignentities → domain-proposenewconcepts
3. Use diagram-generatecollaboration for visualization

Industry: [Industry context]
Integration: [Existing systems to align with]

[Input requirements document]
Project ID: [PROJECT-ID]
```

### MVP Definition Workflow (20 minutes)
```markdown
@workspace Define MVP with comprehensive analysis:

1. Use process-w5h for stakeholder mapping
2. Use process-scopemin for feature prioritization  
3. Use project-planning-tracking for MVP timeline
4. Use project-status-reporting for stakeholder communication plan

Business Context:
- Market pressure: [Competitive situation]
- Resources: [Team and budget constraints]
- Timeline: [Delivery requirements]

Input: Existing project analysis for [PROJECT-ID]
```

### Change Impact Assessment (15 minutes)
```markdown
@workspace Assess comprehensive change impact:

1. Use change-management skill to document change
2. Use process-scopemin to assess scope implications
3. Use project-planning-tracking to revise timeline
4. Use project-status-reporting to communicate impacts

Change: [Description of change request]

Current Status:
- Timeline: [Current progress]
- Budget: [Current spend]
- Team: [Resource status]

Project ID: [PROJECT-ID]
```

---

## 💡 Pattern Optimization Tips

### Effective Context Setting
```markdown
# Include relevant business context
Business Context:
- Industry: [Financial Services/Healthcare/Retail/etc.]
- Company Size: [Startup/SME/Enterprise]
- Market Position: [Established/Growing/Disrupting]
- Compliance: [Regulatory requirements]

# Specify technical context
Technical Context:
- Architecture: [Microservices/Monolith/Serverless]
- Technology Stack: [Languages/Frameworks]
- Integration Points: [External systems]
- Performance Requirements: [SLAs/metrics]

# Define project context
Project Context:
- Timeline: [Duration and milestones]
- Team: [Size, skills, location]
- Budget: [Total and phases]
- Risks: [Known constraints]
```

### Input Specification Best Practices
```markdown
# For file-based inputs
Input: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json

# For direct content
[Paste or attach content here]

# For multi-source inputs
Sources:
1. [Source 1 description]: [content or file reference]
2. [Source 2 description]: [content or file reference]
```

### Output Direction Patterns
```markdown
# Specific output focus
Focus on:
- [Specific area 1]
- [Specific area 2]
- [Specific output format]

# Quality criteria
Ensure:
- Completeness of [specific area]
- Alignment with [business objective]
- Integration with [existing system]

# Next steps indication
Prepare for:
- [Next skill in workflow]
- [Specific stakeholder review]
- [Implementation phase]
```

---

## 🔧 Debugging and Troubleshooting Patterns

### Skill Output Validation
```markdown
@workspace Validate skill output quality:

Skill Used: [skill-name]
Output Location: projects/[PROJECT-ID]/artifacts/[path]

Validation Criteria:
- Completeness: [What should be included]
- Consistency: [Alignment with inputs]
- Usability: [Ready for next step]

Identify gaps and suggest improvements.
```

### Workflow Recovery
```markdown
@workspace Recover from incomplete workflow:

Current State:
- Completed: [List of completed skills]
- Outputs: [Available artifacts]
- Issues: [Problems encountered]

Target State:
- Goal: [Workflow objective]
- Remaining Steps: [Skills still needed]

Recommend recovery approach and missing inputs.
Project ID: [PROJECT-ID]
```

### Quality Improvement
```markdown
@workspace Improve skill output quality:

Current Output: [Describe current state]
Quality Issues: [Specific problems]
Business Impact: [Why improvement needed]

Re-run [skill-name] with enhanced context:
[Provide additional context or constraints]

Project ID: [PROJECT-ID]
```

---

## 🎯 Quick Reference Commands

### Single Skill Patterns
```markdown
# Document processing
@workspace Use requirements-ingest skill: [content] Project: [ID]

# Goal extraction  
@workspace Use goals-extract skill for [PROJECT-ID] with focus on [business area]

# Domain analysis
@workspace Use domain-extractconcepts skill for [PROJECT-ID] in [industry] domain

# Scope definition
@workspace Use process-scopemin skill: MVP for [PROJECT-ID] with [constraints]
```

### Common Workflows
```markdown
# New project foundation
@workspace New project setup: [name/description] Project: [ID]  

# Requirements analysis
@workspace Requirements pipeline: ingest→goals→w5h for [PROJECT-ID]

# Domain modeling  
@workspace Domain pipeline: concepts→align→propose→diagram for [PROJECT-ID]

# Change processing
@workspace Change impact: [change description] Project: [PROJECT-ID]
```

Save this reference for quick access to proven prompt patterns that work effectively with EDPS skills!