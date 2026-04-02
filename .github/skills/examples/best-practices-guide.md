# EDPS Skills - Best Practices & Troubleshooting Guide

Expert guidance for maximizing effectiveness and resolving common issues with EDPS skills.

## 🎯 Best Practices for Skill Usage

### 1. Project Organization Best Practices

#### Consistent Project ID Convention
```markdown
# Recommended formats
[DOMAIN]-[YEAR]           # Example: BANK-2024
[COMPANY]-[PROJECT]-[YEAR] # Example: ACME-PORTAL-2024  
[DEPT]-[INITIATIVE]-[YEAR] # Example: IT-MODERNIZATION-2024
```

#### Folder Structure Discipline
```
projects/[PROJECT-ID]/
├── artifacts/
│   ├── Analysis/          # Keep all skill outputs here
│   ├── Changes/           # Track all changes chronologically
│   ├── Requirements/      # Store input documents
│   └── UI Mockups/        # Visual artifacts
├── tasks/                 # Project planning outputs
├── main.md               # Project overview
└── README.md             # Quick project reference
```

#### File Naming Conventions
```markdown
# Skill outputs (auto-generated)
requirements.json/md       # requirements-ingest output
goals.json/md             # goals-extract output
w5h-analysis.json/md      # process-w5h output
domain-concepts.json/md   # domain-extractconcepts output

# Change documents (manual naming)
2024-02-17-SCOPE-CHG-001-add-mobile-features.md
2024-02-20-PROC-CHG-002-update-security-requirements.md
```

---

### 2. Effective Prompting Strategies

#### Context Layering Technique
```markdown
@workspace Use [skill-name] skill with layered context:

🏢 Business Context:
- Industry: [Specific industry]
- Company type: [Size and market position]
- Regulatory environment: [Compliance requirements]

🎯 Project Context:  
- Objective: [Clear business goal]
- Timeline: [Duration and key dates]
- Constraints: [Budget, resources, risks]

🔧 Technical Context:
- Architecture: [Current and target]
- Integration: [Existing systems]
- Performance: [Requirements and constraints]

[Input content]

Project ID: [PROJECT-ID]
```

#### Progressive Refinement Approach
```markdown
# Step 1: Broad analysis
@workspace Use [skill] for initial [broad analysis] of [input]

# Step 2: Focused refinement  
@workspace Refine [skill] output with focus on [specific area]

# Step 3: Validation and adjustment
@workspace Validate [skill] output against [business criteria]
```

#### Skill Chaining Strategy
```markdown
# Chain compatible skills in logical sequence
requirements-ingest → goals-extract → process-w5h
domain-extractconcepts → domain-alignentities → domain-proposenewconcepts  
process-scopemin → project-planning-tracking → project-status-reporting
```

---

### 3. Quality Assurance Practices

#### Output Validation Checklist
- ✅ **Completeness**: All expected sections present
- ✅ **Traceability**: Source references maintained  
- ✅ **Consistency**: Terminology and concepts align
- ✅ **Actionability**: Outputs ready for next step
- ✅ **Business Alignment**: Supports business objectives

#### Validation Prompt Pattern
```markdown
@workspace Validate skill output quality:

Output to validate: projects/[PROJECT-ID]/artifacts/Analysis/[output-file]

Validation criteria:
1. Completeness: [Specific completeness requirements]
2. Business alignment: [Business objective alignment]  
3. Technical feasibility: [Implementation considerations]
4. Traceability: [Source reference quality]

Identify gaps and recommend improvements.
```

#### Cross-Skill Consistency Check
```markdown
@workspace Check consistency across skill outputs:

Files to validate:
- projects/[PROJECT-ID]/artifacts/Analysis/requirements.json
- projects/[PROJECT-ID]/artifacts/Analysis/goals.json  
- projects/[PROJECT-ID]/artifacts/Analysis/domain-concepts.json

Check for:
- Terminology consistency
- Goal-requirement alignment
- Domain concept coverage
- Missing elements or conflicts
```

---

## 🔧 Common Issues and Solutions

### Issue 1: Incomplete Requirements Extraction

**Symptoms:**
- Missing functional requirements
- Vague or non-atomic requirements
- Poor classification accuracy
- Missing source traceability

**Solutions:**
```markdown
# Enhanced requirements processing
@workspace Use requirements-ingest skill with enhanced context:

Document structure guidance:
- Functional requirements: "The system shall..."
- Non-functional requirements: Performance, security, usability criteria
- Constraints: Technical, regulatory, business limitations
- Assumptions: Implicit beliefs and dependencies

Processing focus:
- Extract atomic, testable requirements
- Classify each requirement precisely
- Maintain source document traceability
- Generate comprehensive glossary

[Your requirements document]

Project ID: [PROJECT-ID]
```

**Prevention:**
- Structure input documents clearly with section headers
- Include business context and stakeholder information
- Specify functional vs non-functional requirements explicitly
- Provide examples of expected atomic requirement format

---

### Issue 2: Poor Domain Concept Extraction

**Symptoms:**
- Missing core business entities
- Vague entity definitions
- Poor relationship mapping
- Inconsistent terminology

**Solutions:**
```markdown
# Enhanced domain analysis
@workspace Use domain-extractconcepts skill with industry expertise:

Industry Context: [Specific industry domain]
Business Model: [How the business operates]
Existing Systems: [Current system entities to reference]

Focus Requirements:
- Core business entities with clear definitions
- Entity attributes with data types
- Relationships with cardinality
- Business rules and constraints
- Domain-specific terminology with definitions

Enhanced extraction from: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json
```

**Prevention:**
- Include industry context and business model information
- Provide examples of similar domain entities
- Specify relationship types needed (1:1, 1:many, many:many)
- Include business rule examples and constraint types

---

### Issue 3: Scope Creep and Poor MVP Definition  

**Symptoms:**
- MVP includes too many features
- Unclear feature prioritization
- Missing business value justification
- Unrealistic timeline estimates

**Solutions:**
```markdown
# Strict MVP scope definition
@workspace Use process-scopemin skill with aggressive constraints:

Hard Constraints (Non-negotiable):
- Timeline: [Hard deadline]
- Budget: [Firm budget limit]  
- Resources: [Exact team size and skills]
- Risk Tolerance: Low (conservative estimate)

Business Value Metrics:
- Primary: [Most important business outcome]
- Secondary: [Important but not critical]
- Nice-to-have: [Future enhancement candidates]

Feature Evaluation Criteria:
1. Essential for primary business value (must-have)
2. Supports user core workflow (important)
3. Competitive advantage (nice-to-have)

Apply strict 80/20 rule: 80% of business value from 20% of features.

Input: projects/[PROJECT-ID]/artifacts/Analysis/requirements.json
```

**Prevention:**
- Start with business constraints, not feature lists
- Use MoSCoW prioritization (Must/Should/Could/Won't)
- Validate MVP against business objectives
- Include buffer time for unknowns and risks

---

### Issue 4: Change Management Chaos

**Symptoms:**
- Uncontrolled requirement changes
- Poor change impact assessment
- Scope creep without budget/timeline adjustment
- Lack of audit trail

**Solutions:**
```markdown
# Structured change management
@workspace Use change-management skill with governance framework:

Change Request Details:
- Type: [SCOPE/PROC/TECH]-CHG
- Impact Level: [LOW/MEDIUM/HIGH/CRITICAL]
- Requestor: [Name and role]
- Business Justification: [Why this change is needed]
- Alternative Options: [Other ways to address need]

Current Project Health:
- Timeline: [Current status and remaining buffer]
- Budget: [Spent vs allocated with contingency]
- Resources: [Team capacity and availability]
- Quality: [Current quality metrics]

Assessment Requirements:
1. Technical impact and implementation effort
2. Timeline implications and critical path impact 
3. Budget impact and funding source
4. Risk assessment and mitigation strategies
5. Business value validation and ROI

Approval Workflow: [Define who approves what level of changes]

Change: [Detailed change description]
Project ID: [PROJECT-ID]
```

**Prevention:**
- Establish change control process at project start
- Define change approval authority levels  
- Require business justification for all changes
- Build change contingency into timeline and budget

---

### Issue 5: Poor Stakeholder Communication

**Symptoms:**
- Status reports don't match audience needs
- Stakeholders surprised by project issues
- Lack of actionable information in reports
- Communication frequency mismatched to needs

**Solutions:**
```markdown
# Audience-specific status reporting  
@workspace Use project-status-reporting skill with audience customization:

Report Configuration:
- Audience: [Specific stakeholder group]
- Frequency: [Based on audience decision-making needs]
- Detail Level: [Executive summary vs operational detail]
- Action Orientation: [What decisions need to be made]

Stakeholder Context:
- Primary Concerns: [What keeps this audience awake at night]
- Decision Authority: [What can this audience actually influence]
- Communication Preferences: [How they like to receive information]
- Success Metrics: [What they measure their success by]

Report Content Framework:
- Executive Summary: [Key achievements, risks, decisions needed]
- Progress Metrics: [Relevant KPIs and trend information]
- Issue Escalation: [Problems that need stakeholder attention]
- Next Steps: [Specific actions and expectations]

Project Context: [Current status for PROJECT-ID]
```

**Prevention:**
- Define stakeholder communication plan at project start
- Tailor reports to audience decision-making needs
- Include both progress and forward-looking information
- Establish escalation criteria for issues

---

## 🚀 Advanced Usage Optimization

### Skill Performance Optimization

#### Input Preparation Best Practices
```markdown
# Optimize input structure for better results
Document Structure:
📋 Executive Summary (business context)
🎯 Business Objectives (measurable goals)  
👥 Stakeholder Analysis (roles and responsibilities)
📝 Functional Requirements (what system does)
⚡ Non-Functional Requirements (how system performs)
🔒 Constraints (limitations and rules)
📊 Assumptions (implicit beliefs)
❌ Out of Scope (explicitly excluded)
```

#### Context Enrichment Strategies  
```markdown
# Layer context for better skill performance
@workspace Use [skill-name] with enriched context:

📈 Business Intelligence:
- Market analysis: [Competitive landscape]
- Customer insights: [User research findings]  
- Revenue model: [How business makes money]

💡 Domain Expertise:
- Industry standards: [Relevant standards and frameworks]
- Best practices: [Proven approaches in domain]
- Anti-patterns: [Common mistakes to avoid]

🔧 Technical Intelligence:
- Architecture constraints: [Technical limitations]
- Integration complexity: [System interdependencies]
- Performance requirements: [Non-functional demands]
```

### Workflow Automation Patterns

#### Repeatable Project Templates
```markdown
# Create reusable project workflow templates
@workspace Execute [PROJECT-TYPE] standard workflow:

Template: [Financial Services Mobile App / E-commerce Platform / Healthcare Portal]

Auto-execute skill sequence:
1. project-document-management → Initialize structure
2. requirements-ingest → Process requirements  
3. goals-extract → Business objectives
4. process-w5h → Stakeholder analysis
5. domain-extractconcepts → Domain modeling
6. process-scopemin → MVP definition
7. project-planning-tracking → Delivery planning

Context Template:
- Industry: [Auto-fill based on project type]
- Compliance: [Standard requirements for domain]
- Architecture: [Typical patterns for project type]

Project: [PROJECT-ID]
Input: [Requirements document]
```

#### Progressive Enhancement Workflows
```markdown
# Build from basic to comprehensive analysis
Phase 1: Foundation (15 minutes)
- requirements-ingest + goals-extract + process-scopemin

Phase 2: Analysis (20 minutes)  
- process-w5h + domain-extractconcepts + domain-alignentities

Phase 3: Planning (15 minutes)
- project-planning-tracking + change-management + project-status-reporting

Each phase validates previous work and builds incrementally.
```

---

## 📊 Success Metrics and KPIs

### Skill Usage Effectiveness

#### Requirements Processing Quality
- **Atomic Requirements**: >90% of requirements are testable and unambiguous
- **Classification Accuracy**: >95% functional/non-functional classification correct  
- **Traceability**: 100% requirements linked to source documents
- **Glossary Completeness**: All domain terms defined and consistent

#### Domain Modeling Quality  
- **Entity Coverage**: All business concepts represented in domain model
- **Relationship Accuracy**: Business relationships correctly modeled
- **Alignment Success**: >95% alignment with existing enterprise entities
- **Stakeholder Validation**: Domain experts confirm model accuracy

#### Project Management Effectiveness
- **Scope Stability**: <15% scope change after initial MVP definition
- **Timeline Accuracy**: <20% variance between planned and actual delivery
- **Budget Control**: <10% variance from planned budget
- **Stakeholder Satisfaction**: >85% satisfaction with project communication

### Continuous Improvement Metrics

#### Skill Output Quality Trends
```markdown
@workspace Track skill improvement over time:

Quality Metrics by Project:
- Requirements extraction completeness
- Domain modeling accuracy  
- Scope definition stability
- Change management effectiveness

Trend Analysis:
- Are skill outputs improving with practice?
- Which skills need additional guidance or examples?
- What context patterns produce best results?

Project Comparison: [List recent projects for analysis]
```

#### Process Efficiency Gains
- **Time to MVP Definition**: Baseline vs. current time
- **Change Request Processing Time**: Response time improvement
- **Stakeholder Alignment Speed**: Time to stakeholder consensus
- **Rework Reduction**: Decreased need to redo analysis

---

## 🎯 Expert-Level Tips

### Advanced Skill Integration
```markdown
# Cross-project pattern recognition
@workspace Identify patterns across projects:

Project Portfolio:
- [PROJECT-1]: [Industry/type]
- [PROJECT-2]: [Industry/type] 
- [PROJECT-3]: [Industry/type]

Analysis Request:
1. Common domain patterns across projects
2. Reusable requirement templates  
3. Standard stakeholder types and roles
4. Shared risk and mitigation strategies

Generate enterprise playbook for future projects.
```

### Skill Customization for Industry
```markdown
# Industry-specific skill optimization
@workspace Customize [skill-name] for [specific industry]:

Industry Characteristics:
- Regulatory environment: [Key regulations]
- Standard entities: [Common business objects]
- Typical workflows: [Standard processes]
- Compliance patterns: [Audit and control requirements]

Optimization Focus:
- Terminology alignment with industry standards
- Compliance requirement templates
- Standard integration patterns
- Risk assessment frameworks specific to industry

Create [INDUSTRY] version of skill guidance.
```

### Enterprise-Scale Implementation
```markdown
# Portfolio-level skill orchestration
@workspace Coordinate skills across enterprise portfolio:

Portfolio Context:
- Program: [Overall initiative]
- Projects: [List of related projects]
- Timeline: [Coordinated delivery schedule]
- Shared Services: [Common components]

Skill Coordination:
1. Shared domain modeling across projects
2. Unified change management process
3. Coordinated status reporting for portfolio
4. Cross-project dependency management

Generate enterprise skill orchestration plan.
```

---

## 📚 Learning and Development

### Skill Mastery Path
1. **Foundation (Week 1-2)**: Master individual skills with simple projects
2. **Integration (Week 3-4)**: Practice skill chaining and workflow creation  
3. **Optimization (Week 5-6)**: Focus on context enhancement and quality improvement
4. **Advanced (Week 7-8)**: Cross-project patterns and enterprise integration

### Practice Exercises
```markdown
# Progressive skill building exercises

Exercise 1: Basic Requirements Processing (30 minutes)
- Take simple requirement document
- Execute: requirements-ingest → goals-extract → process-w5h
- Focus on understanding skill output structure

Exercise 2: Complete Domain Analysis (45 minutes)  
- Use Exercise 1 outputs
- Execute: domain-extractconcepts → domain-alignentities → diagram-generatecollaboration
- Practice domain modeling concepts

Exercise 3: Project Planning Integration (60 minutes)
- Use previous exercises
- Execute: process-scopemin → project-planning-tracking 
- Practice connecting analysis to planning

Exercise 4: Full Project Simulation (90 minutes)
- Complete end-to-end workflow
- Include change management and status reporting
- Practice real-world scenario handling
```

### Common Pitfalls to Avoid
1. **Skipping context**: Providing insufficient business and technical context
2. **Output isolation**: Using skills individually instead of integrated workflows
3. **Poor validation**: Not checking skill outputs for quality and completeness  
4. **Scope creep tolerance**: Accepting vague or expanding requirements
5. **Communication gaps**: Not tailoring outputs to stakeholder needs

Remember: EDPS skills are most effective when used as integrated workflows with rich context and systematic validation!