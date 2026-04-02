# Project Management & Process Skills - Usage Examples

Complete examples for project planning, tracking, scope management, and change control.

## 📁 project-document-management

Initialize and structure project documentation following hierarchical folder guidelines.

### New Project Initialization

```markdown
@workspace Use project-document-management skill to initialize a new project:

Project Details:
- Name: Customer Portal Redesign
- ID: CPR-2024
- Description: Modernize customer self-service portal with improved UX and mobile responsiveness
- Duration: 6 months
- Team Size: 5 developers, 2 designers, 1 PM, 1 QA

Stakeholders:
- Product Owner: Sarah Chen
- Engineering Lead: Mike Rodriguez  
- Design Lead: Jennifer Park
- Customer Success: Tom Wilson

Budget: $350K
Target Launch: Q3 2024

Create complete project structure with folders, templates, and initial documentation.
```

### Enterprise Project Setup

```markdown
@workspace Use project-document-management skill for enterprise project setup:

Enterprise Context:
- Program: Digital Transformation Initiative
- Portfolio: Customer Experience Projects
- Project: Customer Portal Redesign (CPR-2024)
- Sub-projects: Mobile App (MBA-2024), API Platform (API-2024)

Compliance Requirements:
- SOX documentation standards
- Change control procedures
- Audit trail requirements
- Security review checkpoints

Create enterprise-grade project structure with compliance templates.
```

### Multi-Phase Project Structure

```markdown
@workspace Use project-document-management skill for phased project setup:

Project: Customer Portal Redesign (CPR-2024)

Phase Structure:
- Phase 1: Discovery & Requirements (2 months)
- Phase 2: Design & Architecture (1 month)  
- Phase 3: Core Development (2 months)
- Phase 4: Testing & Launch (1 month)

Each phase needs:
- Separate requirement artifacts
- Phase-specific deliverables
- Gate review documentation
- Change management processes

Create phase-based project structure.
```

### Expected Output Structure
- Creates complete `/projects/CPR-2024/` hierarchy
- Generates folder structure: `artifacts/`, `tasks/`, `README.md`
- Creates template files: `main.md`, `project-plan.md`
- Initializes tracking documents and change logs

---

## 📋 project-planning-tracking

Create project plans, track milestones, and monitor progress with structured templates.

### Comprehensive Project Planning

```markdown
@workspace Use project-planning-tracking skill to create detailed project plan:

Project: Customer Portal Redesign (CPR-2024)
Current State: Project initialized, requirements gathering started

Planning Requirements:
- 6-month timeline with monthly milestones
- 8-person cross-functional team
- Dependencies: Core platform upgrade, API development  
- Constraints: Must launch before holiday shopping season
- Budget: $350K with monthly burn tracking

Deliverables:
1. Requirements analysis and stakeholder approval
2. UX/UI designs and prototypes
3. Technical architecture and API specifications
4. Development sprints (2-week cycles)
5. Testing phases (unit, integration, UAT)
6. Production deployment and monitoring

Create detailed project plan with task breakdown, resource allocation, and timeline.
```

### Agile/Sprint Planning Integration

```markdown
@workspace Use project-planning-tracking skill with Agile methodology:

Project: Customer Portal Redesign (CPR-2024)
Methodology: Scrum with 2-week sprints

Sprint Planning Context:
- Team velocity: 35 story points per sprint
- Sprint duration: 2 weeks
- Total project duration: 12 sprints (6 months)
- Release schedule: Monthly releases to staging, final production release

Epic Breakdown:
1. User Authentication & Profile Management (3 sprints)
2. Account Dashboard & Transactions (3 sprints)  
3. Bill Payment & Transfer Features (2 sprints)
4. Mobile Responsiveness & PWA (2 sprints)
5. Testing & Performance Optimization (1 sprint)
6. Production Launch & Post-Launch Support (1 sprint)

Generate sprint-based project plan with backlog prioritization.
```

### Resource and Dependency Planning

```markdown
@workspace Use project-planning-tracking skill with focus on resource management:

Project: Customer Portal Redesign (CPR-2024)

Resource Context:
- Frontend Developers (3): React/TypeScript expertise required
- Backend Developers (2): Node.js/Express, database design
- UX/UI Designer (2): Customer portal experience, mobile design
- Project Manager (1): Technical PM with financial services background
- QA Engineer (1): Automation testing, accessibility testing

Dependencies:
- External: Core banking system API updates (vendor dependency)
- Internal: Security review and penetration testing
- Infrastructure: AWS environment setup and configuration
- Compliance: PCI-DSS certification for payment features

Create resource allocation plan with dependency management and risk mitigation.
```

### Expected Output Structure
- Creates: `projects/CPR-2024/tasks/project-plan.md`
- Creates: `projects/CPR-2024/tasks/task-tracking.md`
- Generates individual task files: `T1-requirements-analysis.md`, etc.
- Creates milestone tracking and progress monitoring templates

---

## 🎯 process-scopemin

Identify minimal viable scope, prioritize features, and define MVP boundaries.

### MVP Scope Definition

```markdown
@workspace Use process-scopemin skill to define MVP scope:

Project: Customer Portal Redesign (CPR-2024)
Current State: Requirements processed, domain model complete

Business Constraints:
- Launch deadline: 6 months (hard constraint)
- Budget limit: $350K (firm)
- Team size: 8 people (cannot increase)
- Risk tolerance: Low (banking industry)

Must-Have Business Goals:
1. Reduce customer service calls by 40%
2. Increase customer satisfaction scores by 25%
3. Support 50% of current transactions online
4. Mobile responsiveness for 80% of features

Input: projects/CPR-2024/artifacts/Analysis/requirements.json

Analyze and prioritize features for MVP definition.
```

### Feature Prioritization Analysis

```markdown
@workspace Use process-scopemin skill for detailed feature prioritization:

Project: Customer Portal Redesign (CPR-2024)

Prioritization Criteria:
1. Business Impact (1-5): Revenue impact, cost reduction, customer satisfaction
2. Technical Complexity (1-5): Development effort, integration complexity, risk
3. User Value (1-5): Frequency of use, importance to user workflow
4. Competitive Advantage (1-5): Differentiation, market positioning

Features to Analyze:
- Account overview and balance display
- Transaction history and search
- Bill payment and scheduled payments
- Money transfers between accounts
- Profile management and settings
- Mobile app companion
- Real-time notifications
- Advanced reporting and analytics
- Third-party financial service integrations

Create prioritization matrix with MVP boundary recommendations.
```

### Risk-Based Scope Management

```markdown
@workspace Use process-scopemin skill with risk assessment:

Project: Customer Portal Redesign (CPR-2024)

Risk Factors:
- High: Security vulnerabilities, data breaches, regulatory compliance
- Medium: Performance issues, integration failures, user adoption
- Low: UI polish, advanced features, nice-to-have integrations

Risk Mitigation Strategy:
- Start with core security and compliance features
- Build minimal feature set first, then enhance
- Plan for extensive testing and security reviews
- Have rollback plan for each major feature

Scope recommendations based on risk tolerance and mitigation strategies.

Input: requirements.json and project constraints for CPR-2024
```

### Expected Output Structure
- Creates: `projects/CPR-2024/artifacts/Analysis/scope-analysis.json`
- Creates: `projects/CPR-2024/artifacts/Analysis/scope-analysis.md`
- Feature prioritization matrix with business value scoring
- MVP feature list with justification
- Risk assessment and mitigation recommendations

---

## 🔄 change-management

Track and document requirement changes with automated referencing and impact analysis.

### Change Request Processing

```markdown
@workspace Use change-management skill to process this change request:

Project: Customer Portal Redesign (CPR-2024)
Change Type: SCOPE-CHG

Change Request:
- Description: Add cryptocurrency wallet integration and Bitcoin purchase feature
- Requestor: Product Owner (Sarah Chen)
- Business Justification: Competitor analysis shows 30% of fintech apps offer crypto
- Customer Demand: 500+ customer requests in past 6 months

Current State:
- Project 60% complete (Month 4 of 6)
- Core features in testing phase
- Budget: $280K spent, $70K remaining
- Team: Fully allocated to current scope

Impact Assessment Needed:
1. Technical complexity and integration effort
2. Regulatory compliance requirements (crypto regulations)
3. Timeline impact and delivery risk
4. Budget implications and resource requirements
5. Security and risk assessment

Process change request with full impact analysis.
```

### Regulatory Change Management

```markdown
@workspace Use change-management skill for regulatory compliance change:

Project: Customer Portal Redesign (CPR-2024)
Change Type: PROC-CHG

Regulatory Change:
- New PCI-DSS 4.0 requirements effective immediately
- Enhanced authentication requirements for card data
- Additional encryption standards for data transmission
- New audit trail and logging requirements

Impact Analysis Required:
1. Current design compliance gaps
2. Code changes needed for compliance
3. Testing and certification timeline
4. Timeline and budget impact
5. Risk of non-compliance vs. delayed launch

Generate change document with regulatory compliance assessment.

Project Status:
- Currently in Month 4 of 6-month timeline
- Payment features partially implemented
- Security review not yet started
```

### Scope Reduction Change

```markdown
@workspace Use change-management skill for scope reduction:

Project: Customer Portal Redesign (CPR-2024)
Change Type: SCOPE-CHG

Situation:
- Timeline pressure: Must launch 2 months early for competitive reasons
- Budget constraints: 15% budget cut due to economic conditions
- Resource limitations: Lost 1 developer to another project

Proposed Scope Changes:
- Remove: Advanced reporting and analytics features
- Remove: Third-party financial service integrations  
- Reduce: Mobile app to responsive web only (no native apps)
- Defer: Real-time notifications to post-launch enhancement

Document scope reduction with impact on business goals and user experience.

Current Progress: 50% complete based on original scope
New Target: Launch in 4 months instead of 6
```

### Expected Output Structure
- Creates: `projects/CPR-2024/artifacts/Changes/[DATE]-[TYPE]-CHG-[NUM]-[description].md`
- Updates: Change tracking logs and impact assessments
- Generates: Stakeholder communication templates
- Maintains: Cross-reference links to affected artifacts

---

## 📊 project-status-reporting

Generate status reports, executive dashboards, and stakeholder communications.

### Executive Status Dashboard

```markdown
@workspace Use project-status-reporting skill to generate executive status report:

Project: Customer Portal Redesign (CPR-2024)
Report Type: Executive Monthly Dashboard
Audience: C-Suite, Board of Directors

Current Status (Month 4 of 6):
- Budget: $280K spent of $350K (80% used, 67% timeline complete)
- Timeline: On track for Q3 launch
- Team: 8 FTEs, no resource constraints
- Risks: Medium risk on crypto integration change request

Key Metrics:
- Requirements: 95% approved and locked
- Development: 60% complete
- Testing: 25% started (unit tests passing)
- User Acceptance: Scheduled for Month 5

Business Impact Projections:
- Customer satisfaction increase: On track for 25% improvement
- Call center volume reduction: Projected 35% decrease
- Online transaction capability: 45% of target transactions supported

Generate executive summary with key achievements, risks, and next steps.
```

### Stakeholder Communication Report

```markdown
@workspace Use project-status-reporting skill for stakeholder communication:

Project: Customer Portal Redesign (CPR-2024)
Report Type: Stakeholder Update
Audience: Product Owner, Engineering Leads, Customer Success Team

Communication Needs:
- Recent change requests and impact assessment
- Feature completion status and demo availability
- User testing feedback and priority adjustments
- Integration testing results and issues
- Launch readiness checklist and dependencies

Stakeholder-Specific Information:
- Product Owner: Feature completion status, change impact on business goals
- Engineering Leads: Technical risks, architecture decisions, performance metrics
- Customer Success: User experience improvements, training material needs

Generate detailed stakeholder communication with actionable next steps.
```

### Project Health Assessment

```markdown
@workspace Use project-status-reporting skill for comprehensive project health check:

Project: Customer Portal Redesign (CPR-2024)
Assessment Type: Project Health Review
Purpose: Mid-project checkpoint for steering committee

Health Metrics:
- Schedule Performance: Green (on track)
- Budget Performance: Yellow (80% spent, 67% complete)
- Quality Metrics: Green (95% test coverage, zero critical bugs)
- Team Performance: Green (velocity stable, no turnover)
- Stakeholder Satisfaction: Green (weekly feedback positive)

Risk Assessment:
- Technical Risks: Medium (crypto integration complexity)
- Business Risks: Low (requirements stable, stakeholder alignment)
- Resource Risks: Low (team stable, skills adequate)
- External Risks: Medium (regulatory changes possible)

Generate comprehensive health assessment with recommendations for steering committee.
```

### Expected Output Structure
- Creates: `projects/CPR-2024/reports/[DATE]-[TYPE]-report.md`
- Generates: Dashboard visualizations and charts
- Updates: Progress tracking and milestone status
- Provides: Stakeholder-specific communication materials

---

## 🔄 Complete Project Management Workflow

### Project Launch to Delivery (Full Lifecycle)

```markdown
@workspace Execute complete project management workflow:

Project: Customer Portal Redesign (CPR-2024)

Phase 1 - Project Initialization:
1. Use project-document-management skill to set up project structure
2. Use project-planning-tracking skill to create detailed project plan
3. Use process-scopemin skill to define MVP and feature prioritization

Phase 2 - Execution Management:
4. Set up change-management system for requirement changes
5. Use project-status-reporting skill for monthly status reports
6. Monitor scope, timeline, and budget throughout execution

Phase 3 - Delivery & Closure:
7. Final status reporting and lessons learned capture
8. Project closure documentation and artifact handover

Business Context:
- Timeline: 6 months
- Budget: $350K
- Team: 8 people cross-functional
- Stakeholders: Product, Engineering, Design, Customer Success

Execute phases with detailed output at each step.
```

### Change-Driven Project Adaptation

```markdown
@workspace Manage project adaptation to changing requirements:

Project: Customer Portal Redesign (CPR-2024)
Current State: Month 3, multiple change requests pending

Change Management Workflow:
1. Use change-management skill to assess pending changes:
   - Crypto wallet integration request
   - Early launch requirement (2 months acceleration)
   - Budget reduction (15% cut)

2. Use process-scopemin skill to re-evaluate MVP scope with new constraints

3. Use project-planning-tracking skill to revise timeline and resource allocation

4. Use project-status-reporting skill to communicate impacts to stakeholders

Generate revised project plan with change impact assessment and stakeholder communication.
```

## Pro Tips 💡

### Effective Project Management
- **Start with structure**: Always use project-document-management first
- **Plan iteratively**: Update plans as requirements and constraints evolve
- **Track consistently**: Regular status reporting prevents small issues from becoming big problems
- **Manage scope actively**: Use scope-min tools to make informed trade-off decisions

### Change Management Best Practices
- **Document everything**: Every change needs traceability
- **Assess impact immediately**: Quick impact assessment prevents scope creep
- **Communicate proactively**: Keep stakeholders informed of change implications
- **Maintain decision history**: Track why changes were accepted or rejected

### Status Reporting Success
- **Know your audience**: Executive reports vs. developer reports need different detail
- **Focus on trends**: Show progress over time, not just current state
- **Highlight risks early**: Escalate issues before they become blockers
- **Provide actionable information**: Every report should enable decision-making

## Next Steps

Project management skills integrate with all other EDPS skills:

1. **Requirements to Planning**: Use requirements processing output for project planning input
2. **Domain to Architecture**: Use domain modeling output for technical planning
3. **Changes to Communication**: Use change management output for stakeholder reporting
4. **Status to Steering**: Use status reports for project governance and decision-making

See `workflow-integration-examples.md` for advanced skill combination patterns.