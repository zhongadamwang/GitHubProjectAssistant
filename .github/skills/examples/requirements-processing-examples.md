# Requirements Processing Skills - Usage Examples

Complete examples and prompt patterns for the core requirements processing pipeline.

## 📋 requirements-ingest

Transform any format (PDF, Word, Markdown, emails) into structured, atomic requirements.

### Basic Usage

```markdown
@workspace Use the requirements-ingest skill to process this requirements document:

# Mobile Banking App Requirements

## User Registration
- New users must be able to register using email and phone number
- Registration process should not exceed 3 steps
- Email verification is required within 24 hours

## Account Access
- Users shall authenticate using biometric or PIN
- Login attempts are limited to 5 failures before lockout
- Session timeout occurs after 15 minutes of inactivity

Project ID: MBA-2024
```

### Advanced Usage with Multiple Documents

```markdown
@workspace Use requirements-ingest skill to merge and process these multiple requirement sources:

Document 1: [Business Requirements Document]
Document 2: [Technical Specifications] 
Document 3: [Stakeholder Email Thread]

Combine into unified requirements with conflict resolution and source traceability.

Project ID: MBA-2024
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/requirements.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/requirements.md`
- Each requirement gets unique ID (R-001, R-002, etc.)
- Source traceability to original documents
- Classifications: functional/non-functional/constraint/assumption/out-of-scope

---

## 🎯 goals-extract

Extract business goals, success criteria, KPIs, and constraints from processed requirements.

### Basic Goal Extraction

```markdown
@workspace Use the goals-extract skill to analyze the requirements from project MBA-2024:

Focus on:
- Primary business objectives
- Measurable success criteria  
- Key performance indicators
- Business constraints and assumptions
- Open questions that need stakeholder input

Input: projects/MBA-2024/artifacts/Analysis/requirements.json
```

### Context-Enhanced Extraction

```markdown
@workspace Use goals-extract skill with this business context:

Business Context:
- Company: Regional Bank serving 100K customers
- Goal: Increase mobile adoption from 35% to 60%
- Timeline: 18 months
- Budget: $2.5M
- Competition: 3 major fintech mobile apps in market

Analyze requirements.json and extract goals aligned with this business context.

Project ID: MBA-2024
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/goals.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/goals.md`
- Structured goal statements with measurable criteria
- Source traceability to specific requirements
- KPIs with baseline and target values

---

## 🔍 process-w5h

Comprehensive WHO/What/When/Where/Why/How analysis of requirements.

### Standard W5H Analysis

```markdown
@workspace Use the process-w5h skill to analyze requirements for comprehensive stakeholder and implementation understanding:

Input: projects/MBA-2024/artifacts/Analysis/requirements.json

Focus areas:
- WHO: All stakeholder types and user personas
- WHAT: Core functionalities and feature definitions
- WHEN: Timeline dependencies and sequencing
- WHERE: System boundaries and integration points  
- WHY: Business justification and value drivers
- HOW: Implementation approaches and technical strategies

Project ID: MBA-2024
```

### Stakeholder-Focused Analysis

```markdown
@workspace Use process-w5h skill with emphasis on stakeholder analysis:

Stakeholder Context:
- Primary Users: Bank customers (age 25-65)
- Secondary Users: Bank tellers, customer service
- Administrators: IT operations, compliance team
- External: Payment processors, credit bureaus

Perform W5H analysis identifying all touchpoints and interactions.

Input: requirements.json for MBA-2024
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/w5h-analysis.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/w5h-analysis.md`
- Stakeholder mapping with roles and responsibilities
- Implementation timeline and dependencies
- Context analysis and business justification

---

## 🔄 Workflow Examples

### Complete Requirements Pipeline (15-20 minutes)

```markdown
@workspace Execute the complete requirements processing pipeline:

Step 1: Use requirements-ingest skill to process attached requirements document
Step 2: Use goals-extract skill on resulting requirements.json
Step 3: Use process-w5h skill for comprehensive stakeholder analysis
Step 4: Generate glossary of domain terms

Project ID: MBA-2024

[Attach your requirements document]

Provide summary of key findings from each step.
```

### Incremental Analysis

```markdown
@workspace I need to iterate on requirements analysis:

Current State: Have requirements.json for MBA-2024 
Next Steps:
1. Re-run goals-extract with refined business context
2. Update w5h-analysis focusing on implementation timeline
3. Identify any gaps or inconsistencies

Business Context Update:
- Regulatory requirement: PCI DSS compliance mandatory
- New constraint: Must integrate with existing core banking system
- Updated timeline: Launch moved up by 3 months
```

### Quality Validation

```markdown
@workspace Validate the completeness of requirements processing:

Project: MBA-2024

Check:
1. Are all requirements atomic and testable?
2. Do extracted goals align with business objectives?
3. Are all stakeholders identified in W5H analysis?
4. Are there any orphaned requirements without clear business value?
5. Do timelines and dependencies make sense?

Suggest improvements and missing elements.
```

## Pro Tips 💡

### Effective Input Preparation
- **Structure your documents**: Use clear headings and sections
- **Include context**: Business background, stakeholders, constraints
- **Specify project ID**: Consistent naming helps with file organization
- **Attach source files**: Let Copilot process original documents directly

### Prompt Optimization
- **Be specific about focus areas**: Technical vs. business vs. user requirements
- **Request specific output formats**: JSON vs. Markdown vs. both
- **Chain skills explicitly**: "Use A then B then C with output from previous step"
- **Include validation requests**: "Check for completeness and consistency"

### Common Issues and Fixes
- **Vague requirements**: Ask for more atomic, testable statements
- **Missing business context**: Provide stakeholder and business goal information
- **Unclear scope**: Use process-scopemin skill to define MVP boundaries
- **Inconsistent terminology**: Request glossary generation and term standardization

## Next Steps

After completing requirements processing:

1. **Domain Analysis**: Use `domain-extractconcepts` to identify key entities
2. **Scope Definition**: Use `process-scopemin` for MVP planning
3. **Change Management**: Set up `change-management` for requirement evolution
4. **Project Planning**: Use `project-planning-tracking` for timeline creation

See `domain-modeling-examples.md` for the next phase of the EDPS workflow.