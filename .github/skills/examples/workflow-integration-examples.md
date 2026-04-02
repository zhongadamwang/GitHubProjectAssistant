# Workflow Integration Examples - Advanced Skill Combinations

Complete workflows showing how to orchestrate multiple EDPS skills for comprehensive project delivery.

## 🚀 Complete Project Lifecycle (90-120 minutes)

End-to-end project execution from initial requirements to delivery planning.

```markdown
@workspace Execute complete EDPS project lifecycle workflow:

Project Setup:
- Name: Digital Banking Mobile App
- ID: DBMA-2024
- Description: New mobile banking application for iOS and Android
- Timeline: 9 months
- Team: 12 people (6 developers, 3 designers, 2 PM, 1 QA)
- Budget: $1.2M

Business Context:
- Target: 100K active users within 12 months
- Goal: Replace legacy mobile app with modern React Native solution
- Compliance: PCI-DSS, FDIC regulations, accessibility standards
- Integration: Core banking system, payment processors, fraud detection

Workflow Execution:

🏗️ Phase 1: Project Foundation (15 minutes)
1. Use project-document-management skill to initialize project structure
2. Use project-planning-tracking skill for high-level project plan

📋 Phase 2: Requirements Analysis (30 minutes)  
3. Use requirements-ingest skill to process business requirements document
4. Use goals-extract skill to identify business objectives and KPIs
5. Use process-w5h skill for comprehensive stakeholder analysis

🎯 Phase 3: Domain Modeling (25 minutes)
6. Use domain-extractconcepts skill to identify core banking entities
7. Use domain-alignentities skill to align with existing banking systems 
8. Use domain-proposenewconcepts skill for mobile-specific concepts
9. Use diagram-generatecollaboration skill for system interaction models

📊 Phase 4: Scope & Planning (20 minutes)
10. Use process-scopemin skill to define MVP and feature prioritization
11. Update project-planning-tracking with detailed development roadmap
12. Use change-management skill to establish change control process

📈 Phase 5: Project Execution Setup (10 minutes)  
13. Use project-status-reporting skill to create stakeholder communication plan
14. Generate initial project status baseline

[Attach business requirements document]

Provide comprehensive project foundation ready for development execution.
```

---

## 🔄 Agile Development Integration (45-60 minutes)

Integrate EDPS skills with Agile/Scrum development methodology.

```markdown
@workspace Integrate EDPS with Agile development workflow:

Project: Digital Banking Mobile App (DBMA-2024)
Methodology: Scrum with 3-week sprints
Current State: Project kickoff, initial requirements gathering complete

Agile Integration Workflow:

🎯 Sprint 0: Foundation Sprint (20 minutes)
1. Use requirements-ingest skill to process user stories backlog
2. Use goals-extract skill to align user stories with business objectives  
3. Use process-w5h skill to identify all stakeholder touchpoints
4. Use process-scopemin skill to prioritize backlog for first 3 sprints

📋 Sprint Planning Enhancement (15 minutes)
5. Use domain-extractconcepts skill to identify entities for each epic
6. Use diagram-generatecollaboration skill to create user journey diagrams
7. Use project-planning-tracking skill to estimate and allocate sprint tasks

🔄 Sprint Execution Support (15 minutes)
8. Use change-management skill to handle mid-sprint requirement changes
9. Use project-status-reporting skill to generate sprint review reports
10. Update domain models and collaboration diagrams based on development learnings

Sprint Backlog Context:
Epic 1: User Authentication & Profile (Stories US-001 through US-015)
Epic 2: Account Management (Stories US-016 through US-030)  
Epic 3: Transaction Features (Stories US-031 through US-050)
Epic 4: Bill Payment Integration (Stories US-051 through US-065)

Integration Requirements:
- Core Banking API integration
- Identity Management System (OAuth2/OIDC)
- Payment Gateway APIs  
- Fraud Detection Service
- Customer Service Platform

Generate Agile-ready artifacts with EDPS analysis foundation.
```

---

## 🎯 MVP Definition and Validation (30-40 minutes)  

Focus on minimal viable product definition with market validation approach.

```markdown
@workspace Execute MVP definition and validation workflow:

Project: Digital Banking Mobile App (DBMA-2024)  
Objective: Define MVP for 6-month accelerated timeline
Market Context: Competing with 3 major fintech mobile apps

MVP Workflow:

📊 Market Analysis Integration (10 minutes)
1. Use process-w5h skill with competitive analysis context:
   - WHO: Target user segments vs. competitor user base
   - WHAT: Core features vs. competitor feature sets  
   - WHY: Unique value proposition and differentiation

🎯 Scope Optimization (15 minutes)
2. Use process-scopemin skill with aggressive constraints:
   - Timeline: 6 months instead of 9 (33% reduction)
   - Budget: $800K instead of $1.2M (33% reduction)  
   - Team: 8 people instead of 12 (33% reduction)

3. Use goals-extract skill to align reduced scope with core business objectives
4. Use domain-extractconcepts skill to identify minimal entity set for MVP

📋 Validation Planning (15 minutes)
5. Use project-planning-tracking skill for MVP delivery timeline
6. Use diagram-generatecollaboration skill for MVP user journey validation
7. Use change-management skill to establish scope protection processes

Competitive Context:
- App A: Strong in payments, weak in account management
- App B: Great UX, limited banking features  
- App C: Full-featured, poor mobile experience

Business Constraints:
- Must launch before App A's planned update (6-month window)
- Marketing budget committed to holiday 2024 campaign
- Core banking system upgrade creates 3-month integration window

Market Validation Approach:
- Beta testing with 1,000 existing customers
- A/B testing on key user flows
- Customer interview insights integration

Generate MVP definition optimized for market entry and validation.
```

---

## 🔄 Change Impact Assessment (20-30 minutes)

Handle significant requirement changes mid-project with full impact analysis.

```markdown
@workspace Execute comprehensive change impact assessment:

Project: Digital Banking Mobile App (DBMA-2024)
Current State: Month 5 of 9, 55% complete
Change Trigger: Regulatory compliance requirement (immediate effective date)

Major Change Request:
- New Regulation: Enhanced customer due diligence (CDD) requirements
- Impact: All account opening and high-value transactions require additional verification
- Compliance Deadline: 90 days (firm regulatory requirement)
- Non-compliance Risk: $10M+ fines, potential license issues

Change Impact Workflow:

🔍 Requirement Analysis (10 minutes)
1. Use requirements-ingest skill to process regulatory compliance requirements
2. Use process-w5h skill to analyze:
   - WHO: Affected user types, compliance officers, auditors
   - WHAT: New verification workflows and data requirements
   - WHEN: Implementation timeline and compliance deadlines
   - HOW: Technical integration with identity verification services

🎯 Domain Impact Assessment (10 minutes)  
3. Use domain-extractconcepts skill to identify new compliance entities
4. Use domain-alignentities skill to integrate with existing user/account model
5. Use domain-proposenewconcepts skill for verification workflow concepts

📊 Project Impact Analysis (10 minutes)
6. Use process-scopemin skill to assess MVP impact and scope changes
7. Use project-planning-tracking skill to revise timeline and resource allocation
8. Use change-management skill to document change impact and approvals
9. Use project-status-reporting skill to communicate impacts to stakeholders

Current Project Metrics:
- Development: 55% complete (authentication, accounts, basic transactions done)
- Testing: 30% complete (unit tests passing, integration testing started)  
- Budget: $720K spent of $1.2M (60% used)
- Timeline: Month 5 of 9 (56% elapsed)

Technical Impact Areas:
- Account opening workflow (major changes required)
- Transaction processing (additional verification steps)
- User interface (new verification forms and flows)
- API integration (identity verification service integration)  
- Database schema (new compliance tracking tables)

Generate comprehensive change impact assessment with revised project plan.
```

---

## 🏢 Enterprise Portfolio Integration (60-90 minutes)

Integrate multiple related projects across an enterprise portfolio.

```markdown
@workspace Execute enterprise portfolio integration workflow:

Portfolio Context:
- Program: Digital Banking Transformation
- Timeline: 18 months across 4 related projects
- Budget: $5.2M total portfolio budget
- Governance: Monthly steering committee, quarterly board reviews

Portfolio Projects:
1. Digital Banking Mobile App (DBMA-2024) - $1.2M, 9 months
2. Customer Web Portal Redesign (CWPR-2024) - $800K, 6 months  
3. Open Banking API Platform (OBAP-2024) - $1.5M, 12 months
4. Data Analytics & Insights Platform (DAIP-2024) - $1.7M, 15 months

Enterprise Integration Workflow:

🏗️ Portfolio Architecture Planning (25 minutes)
1. Use project-document-management skill to create portfolio structure
2. Use domain-extractconcepts skill across all 4 projects to identify shared entities
3. Use domain-alignentities skill for enterprise data model alignment
4. Use diagram-generatecollaboration skill for cross-project integration mapping

📋 Shared Requirements Analysis (20 minutes)
5. Use requirements-ingest skill to process shared functional requirements
6. Use goals-extract skill to identify portfolio-level business objectives
7. Use process-w5h skill for enterprise stakeholder analysis including:
   - Shared stakeholders across projects
   - Cross-project dependencies and touchpoints  
   - Enterprise governance and oversight requirements

🎯 Portfolio Scope Management (15 minutes)
8. Use process-scopemin skill to optimize portfolio scope and project interdependencies
9. Use project-planning-tracking skill for portfolio-level timeline and milestone coordination
10. Use change-management skill to establish enterprise change control process

📊 Portfolio Governance Setup (20 minutes)  
11. Use project-status-reporting skill to create portfolio dashboard and reporting structure
12. Create enterprise risk management and issue escalation procedures
13. Establish shared services and reusable component strategies

Shared Enterprise Entities:
- Customer (all projects)
- Account (DBMA, CWPR, OBAP)  
- Transaction (DBMA, CWPR, DAIP)
- Authentication (DBMA, CWPR, OBAP)
- API Gateway (DBMA, CWPR, OBAP, DAIP)
- Data Warehouse (DAIP integrates with all)

Cross-Project Dependencies:
- OBAP APIs required for DBMA and CWPR
- DAIP analytics feed all customer-facing platforms
- Shared authentication service across all platforms
- Common design system and UX patterns

Enterprise Governance Requirements:
- Security and compliance standards across all projects
- Shared technology stack and architectural principles  
- Common data governance and privacy policies
- Coordinated customer communication and change management

Generate enterprise portfolio integration plan with shared service identification.
```

---

## 🔄 Continuous Improvement Integration (35-45 minutes)

Establish continuous improvement processes using EDPS skills for ongoing optimization.

```markdown
@workspace Establish continuous improvement workflow with EDPS integration:

Project: Digital Banking Mobile App (DBMA-2024)
Context: Post-launch optimization and ongoing enhancement
Launch Status: Successfully launched, 45K active users in first 3 months

Continuous Improvement Framework:

📊 Performance Baseline Establishment (15 minutes)
1. Use project-status-reporting skill to establish post-launch baseline metrics:
   - User adoption and engagement metrics
   - Technical performance indicators  
   - Customer satisfaction scores
   - Business objective achievement status

2. Use goals-extract skill to validate achieved goals against original objectives
3. Use process-w5h skill to identify ongoing stakeholder feedback and requirements

🔄 Iterative Enhancement Process (15 minutes)
4. Use requirements-ingest skill to process user feedback and feature requests
5. Use process-scopemin skill for ongoing feature prioritization and roadmap planning
6. Use domain-proposenewconcepts skill to identify innovative enhancement opportunities

📈 Change Management Integration (15 minutes)
7. Use change-management skill to establish ongoing enhancement change control
8. Use project-planning-tracking skill for continuous delivery planning and sprint integration
9. Use diagram-generatecollaboration skill to update system models based on usage patterns

Post-Launch Metrics (current):
- User Adoption: 45K active users (target: 100K in 12 months)
- Customer Satisfaction: 4.2/5 (target: 4.5+)
- Transaction Volume: 2.3M monthly (growing 15% month-over-month)  
- Technical Performance: 99.2% uptime, 1.8s average response time
- Revenue Impact: $2.1M cost savings from reduced call center volume

User Feedback Themes:
- Request: Cryptocurrency portfolio tracking (250+ requests)
- Request: Advanced budgeting and financial planning tools (180+ requests)
- Issue: Slow performance during peak hours (125 reports)
- Request: Integration with popular financial apps (100+ requests)

Market Evolution:
- Competitor A launched similar mobile-first banking features
- New fintech startups entering market with innovative approaches
- Regulatory environment evolving toward Open Banking standards  
- Customer expectations increasing based on other industry apps

Enhancement Priorities:
1. Performance optimization for peak load handling
2. Advanced personal financial management features
3. Open Banking API integration for account aggregation
4. Enhanced security features (biometric authentication)
5. AI-powered financial insights and recommendations

Generate continuous improvement roadmap with data-driven prioritization.
```

---

## 🎯 Risk Mitigation Workflow (25-35 minutes)

Proactive risk identification and mitigation using integrated EDPS skills.

```markdown
@workspace Execute comprehensive risk mitigation workflow:

Project: Digital Banking Mobile App (DBMA-2024)
Risk Context: High-stakes financial services project with regulatory compliance requirements
Current Phase: Month 6 of 9, development 75% complete

Risk Assessment Integration:

🔍 Risk Identification (15 minutes)
1. Use process-w5h skill for comprehensive risk stakeholder analysis:
   - WHO: Risk owners, impact stakeholders, mitigation teams
   - WHAT: Technical, business, regulatory, market risks
   - WHEN: Risk timing, probability windows, impact timelines
   - WHERE: System boundaries, external dependencies, integration points
   - WHY: Business justification for risk tolerance levels
   - HOW: Risk mitigation strategies and contingency plans

2. Use requirements-ingest skill to process regulatory compliance checklist
3. Use domain-alignentities skill to identify integration risk points with existing systems

🎯 Risk Prioritization and Planning (10 minutes)  
4. Use process-scopemin skill to evaluate scope reduction options for high-risk features
5. Use project-planning-tracking skill to create risk mitigation timeline and resource allocation
6. Use change-management skill to establish risk-driven change approval processes

📊 Risk Monitoring and Communication (10 minutes)
7. Use project-status-reporting skill to create risk dashboard and stakeholder communication
8. Use diagram-generatecollaboration skill to model risk escalation and response workflows

Identified Risk Categories:

**Technical Risks (High Priority):**
- Integration failure with legacy core banking system (Probability: 30%, Impact: High)
- Mobile app performance under peak load conditions (Probability: 40%, Impact: Medium)  
- Security vulnerability in payment processing flow (Probability: 15%, Impact: Critical)
- Apple/Google app store approval delays (Probability: 25%, Impact: Medium)

**Business Risks (Medium Priority):**
- Customer adoption below 50K target in first 6 months (Probability: 35%, Impact: High)
- Competitive response from existing mobile banking providers (Probability: 60%, Impact: Medium)
- Regulatory compliance deadline shifts during development (Probability: 20%, Impact: High)

**Project Execution Risks (Medium Priority):**
- Key developer departure during critical development phase (Probability: 20%, Impact: High)
- Budget overrun due to scope creep or technical challenges (Probability: 30%, Impact: Medium)
- Timeline delay due to integration complexity (Probability: 25%, Impact: High)

**External Risks (Variable Priority):**
- Core banking system outage affecting integration testing (Probability: 15%, Impact: High)
- Third-party payment gateway service disruption (Probability: 10%, Impact: Medium)
- Economic downturn affecting customer early adoption (Probability: 25%, Impact: Medium)

Risk Mitigation Strategies:
- Technical: Comprehensive integration testing, performance testing, security audits
- Business: Phased launch approach, competitive monitoring, regulatory compliance tracking
- Project: Cross-training, budget contingency, alternative timeline scenarios  
- External: Vendor relationship management, service level agreements, market monitoring

Generate comprehensive risk mitigation plan with monitoring and response procedures.
```

## Pro Tips for Workflow Integration 💡

### Skill Orchestration Best Practices
- **Plan the sequence**: Requirements → Analysis → Domain → Planning → Execution
- **Validate at each step**: Use outputs of previous skills to validate next step inputs
- **Iterate when needed**: Don't hesitate to re-run skills with refined inputs
- **Document decisions**: Capture rationale for skill selection and parameter choices

### Effective Workflow Design  
- **Start broad, then focus**: Use comprehensive skills first, then specialized ones
- **Chain related outputs**: Use JSON outputs as inputs for subsequent skills
- **Validate consistency**: Check that skill outputs align with business objectives
- **Plan for evolution**: Workflows themselves evolve as projects progress

### Common Integration Patterns
```markdown
# Requirements to Planning Pipeline  
requirements-ingest → goals-extract → process-w5h → process-scopemin → project-planning-tracking

# Domain Modeling Pipeline
requirements-ingest → domain-extractconcepts → domain-alignentities → domain-proposenewconcepts → diagram-generatecollaboration

# Change Management Pipeline
change-management → process-scopemin → project-planning-tracking → project-status-reporting → stakeholder communication

# Continuous Improvement Pipeline  
project-status-reporting → requirements-ingest → goals-extract → process-scopemin → project-planning-tracking
```

### Workflow Quality Gates
- **After Requirements Processing**: Validate completeness and clarity before domain modeling
- **After Domain Modeling**: Ensure concepts support identified business goals
- **After Scope Definition**: Verify MVP aligns with business objectives and constraints  
- **After Planning**: Confirm resource allocation and timeline feasibility

## Next Steps

Master these workflow patterns to:

1. **Deliver projects faster**: Integrated workflows reduce manual coordination overhead
2. **Improve quality**: Systematic skill application ensures comprehensive analysis
3. **Reduce risks**: Proactive risk identification and mitigation through integrated analysis
4. **Scale expertise**: Reusable workflow patterns enable consistent project delivery  

See individual skill documentation for detailed parameter options and advanced usage patterns.