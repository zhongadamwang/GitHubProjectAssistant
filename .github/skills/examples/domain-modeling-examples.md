# Domain Modeling Skills - Usage Examples

Complete examples for extracting, aligning, and evolving domain concepts from requirements.

## 🎯 domain-extractconcepts

Identify business domain entities, concepts, and relationships from processed requirements.

### Basic Entity Extraction

```markdown
@workspace Use the domain-extractconcepts skill to identify key business entities:

Input: projects/MBA-2024/artifacts/Analysis/requirements.json

Focus on:
- Core business entities (User, Account, Transaction, etc.)
- Entity attributes and relationships
- Domain-specific terminology
- Business rules and constraints
- Data flow between entities

Project ID: MBA-2024
```

### Industry-Specific Domain Analysis

```markdown
@workspace Use domain-extractconcepts skill with banking domain context:

Industry Context: 
- Banking/Financial Services
- Regulatory compliance: FDIC, PCI-DSS, SOX
- Core banking concepts: Accounts, Transactions, Customers, Compliance
- Standard banking terminology and relationships

Extract domain concepts with banking industry alignment.

Input: projects/MBA-2024/artifacts/Analysis/requirements.json
```

### Hierarchical Concept Mapping

```markdown
@workspace Use domain-extractconcepts skill to create hierarchical domain model:

Requirements Context: Multi-level banking system
- Corporate level: Bank, Branch, RegionalOffice
- Customer level: Customer, Account, Product, Service  
- Transaction level: Transaction, Payment, Transfer, Fee
- Operational level: Employee, Role, Permission, Audit

Create structured concept hierarchy with relationships.

Input: requirements.json for MBA-2024
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-concepts.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-concepts.md`
- Entity definitions with attributes and types
- Relationship mapping between entities
- Business rules and constraints
- Confidence scores for each extracted concept

---

## 🔄 domain-alignentities

Align extracted concepts with existing organizational domain models.

### Alignment with Existing Systems

```markdown
@workspace Use domain-alignentities skill to align concepts with our existing banking system:

Existing Domain Model:
- Core Banking System entities: Customer, Account, Product, Transaction
- CRM System entities: Lead, Contact, Opportunity, Campaign
- Compliance System entities: RiskProfile, ComplianceCheck, AuditTrail

New Domain Concepts: projects/MBA-2024/artifacts/Analysis/domain-concepts.json

Identify:
- Direct matches with existing entities
- Partial overlaps requiring integration
- New concepts not in existing systems
- Conflicts requiring resolution

Project ID: MBA-2024
```

### Cross-System Integration Analysis

```markdown
@workspace Use domain-alignentities skill for enterprise architecture alignment:

Enterprise Systems Context:
- Core Banking Platform (IBM)
- Customer Service Portal (Salesforce)  
- Mobile Banking App (New Development)
- Compliance Management (GRC Solution)
- Analytics Platform (Tableau/PowerBI)

Analyze how MBA-2024 domain concepts integrate across these systems.

Input: domain-concepts.json for MBA-2024
```

### Standards Compliance Alignment

```markdown
@workspace Use domain-alignentities skill with industry standard alignment:

Industry Standards:
- ISO 20022 Payment Message Standards
- SWIFT Banking Message Types  
- FDIC Banking Entity Classifications
- PCI-DSS Data Classification Standards

Align MBA-2024 concepts with these standards for compliance and interoperability.

Input: projects/MBA-2024/artifacts/Analysis/domain-concepts.json
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-alignment.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-alignment.md`
- Mapping between new and existing concepts
- Conflict identification and resolution strategies
- Integration recommendations and impact analysis

---

## 🚀 domain-proposenewconcepts

Propose new domain concepts to fill gaps in coverage and functionality.

### Gap Analysis and Proposals

```markdown
@workspace Use domain-proposenewconcepts skill to identify and propose missing concepts:

Current State: domain-alignment.json for MBA-2024 shows some gaps

Gap Areas to Analyze:
1. Mobile-specific concepts not in legacy banking systems
2. Real-time notification and messaging entities
3. Biometric authentication and security concepts
4. Social banking and sharing features
5. AI/ML personalization entities

Propose new concepts to complete the domain model.

Input: projects/MBA-2024/artifacts/Analysis/domain-alignment.json
```

### Future-State Domain Planning

```markdown
@workspace Use domain-proposenewconcepts skill for strategic domain evolution:

Strategic Direction:
- Move to Open Banking APIs
- Implement AI-driven customer insights
- Add cryptocurrency support
- Enable social payment features
- Enhance fraud detection capabilities

Based on domain alignment, propose future concepts needed for 2-year roadmap.

Input: domain-alignment.json for MBA-2024
```

### Innovation-Driven Concept proposals

```markdown
@workspace Use domain-proposenewconcepts skill with innovation focus:

Innovation Areas:
- Blockchain/DLT integration concepts
- IoT payment scenarios (car payments, smart home)
- Voice banking and conversational interfaces
- AR/VR banking experiences  
- Gamification and rewards concepts

Propose forward-looking domain concepts for competitive advantage.

Input: projects/MBA-2024/artifacts/Analysis/domain-alignment.json
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-newconcepts.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/domain-newconcepts.md`
- Gap analysis with missing concept identification
- New concept proposals with justification
- Implementation priority and impact assessment

---

## 📊 diagram-generatecollaboration

Generate system interaction diagrams from domain concepts.

### System Interaction Diagrams

```markdown
@workspace Use diagram-generatecollaboration skill to create system interaction models:

Input Sources:
- projects/MBA-2024/artifacts/Analysis/domain-concepts.json
- projects/MBA-2024/artifacts/Analysis/domain-alignment.json
- projects/MBA-2024/artifacts/Analysis/w5h-analysis.json

Generate:
1. User journey sequence diagrams
2. System integration flow diagrams  
3. Data flow diagrams between entities
4. Process flow diagrams for key workflows

Focus on mobile banking user scenarios.

Project ID: MBA-2024
```

### Stakeholder Collaboration Mapping

```markdown
@workspace Use diagram-generatecollaboration skill for stakeholder interaction analysis:

Stakeholder Context (from W5H analysis):
- Bank Customers (primary users)
- Customer Service Representatives
- IT Operations Team
- Compliance Officers
- External Payment Processors

Create collaboration diagrams showing:
- Touchpoint interactions
- Information flow between stakeholders
- Decision points and approvals
- Error handling and escalation paths

Input: All analysis artifacts for MBA-2024
```

### Technical Architecture Visualization

```markdown
@workspace Use diagram-generatecollaboration skill for technical architecture diagrams:

Technical Context:
- Mobile App (iOS/Android)
- API Gateway Layer
- Core Banking System
- Database Systems
- External Service Integrations

Generate:
1. Component interaction diagrams
2. API call sequence diagrams
3. Data persistence flow diagrams
4. Security and authentication flows

Input: Domain and system analysis for MBA-2024
```

### Expected Output Structure
- Creates: `projects/MBA-2024/artifacts/Analysis/collaboration-diagrams.json`
- Creates: `projects/MBA-2024/artifacts/Analysis/collaboration-diagrams.md`
- Mermaid diagram definitions for various interaction types
- Stakeholder maps and interaction patterns
- Technical architecture and data flow visualizations

---

## 🔄 Complete Domain Modeling Workflow

### Full Pipeline (20-30 minutes)

```markdown
@workspace Execute complete domain modeling pipeline:

Prerequisites: Completed requirements processing for MBA-2024

Pipeline Steps:
1. Use domain-extractconcepts skill on requirements.json
2. Use domain-alignentities skill with our existing banking system context
3. Use domain-proposenewconcepts skill for gap filling and innovation
4. Use diagram-generatecollaboration skill for visualization

Output: Complete domain model with visualizations for MBA-2024

Existing Systems Context:
- Core Banking System (Customer, Account, Transaction entities)
- CRM Platform (Lead, Contact, Opportunity entities)  
- Compliance System (RiskProfile, AuditTrail entities)

Innovation Focus: Mobile-first banking, AI personalization, Open Banking APIs
```

### Iterative Domain Refinement

```markdown
@workspace Refine domain model based on stakeholder feedback:

Current State: Initial domain model completed for MBA-2024

Stakeholder Feedback:
1. "Need more granular transaction categorization"
2. "Missing concepts for budgeting and financial planning"
3. "Insufficient detail on compliance and audit workflows"
4. "Need integration concepts for third-party financial services"

Refine:
1. Update domain-extractconcepts with additional detail
2. Re-align with enhanced entity requirements
3. Propose additional concepts for gaps
4. Update collaboration diagrams

Project ID: MBA-2024
```

### Cross-Project Domain Convergence

```markdown
@workspace Align domain models across multiple projects:

Projects to Align:
- MBA-2024 (Mobile Banking App)
- CPC-2024 (Customer Portal Redesign)  
- API-2024 (Open Banking API Platform)

Use domain-alignentities skill to:
1. Identify shared domain concepts across projects
2. Resolve naming and definition conflicts
3. Create unified enterprise domain vocabulary
4. Propose shared services and data models

Generate enterprise domain alignment recommendations.
```

## Pro Tips 💡

### Effective Domain Analysis
- **Start broad, then narrow**: Extract all concepts first, then focus on core entities
- **Include business rules**: Don't just identify entities, capture constraints and rules
- **Think relationships**: Map how entities interact and depend on each other
- **Consider lifecycle**: How entities are created, modified, and archived

### Quality Validation
- **Check completeness**: Do extracted concepts cover all requirement areas?
- **Validate relationships**: Do entity relationships make business sense?
- **Test scenarios**: Can domain model support key user workflows?
- **Review with stakeholders**: Do business experts recognize and validate concepts?

### Common Patterns
```markdown
# Quick Domain Overview
@workspace Quick domain analysis: extract concepts and generate basic diagrams for [PROJECT-ID]

# Detailed Enterprise Analysis  
@workspace Complete domain pipeline with enterprise alignment for [PROJECT-ID]

# Innovation Planning
@workspace Domain analysis with future-state planning and innovation concepts for [PROJECT-ID]
```

## Next Steps

After completing domain modeling:

1. **Scope Definition**: Use `process-scopemin` to prioritize domain concepts for MVP
2. **Project Planning**: Use `project-planning-tracking` to plan domain implementation
3. **Change Management**: Set up `change-management` for domain evolution
4. **Status Reporting**: Use `project-status-reporting` for stakeholder communication

See `planning-and-management-examples.md` for project execution guidance.