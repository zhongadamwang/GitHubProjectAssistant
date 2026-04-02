---
name: domain-extractconcepts
description: Extract domain-specific entities, concepts, terminology, and relationships from analyzed requirements. Identifies business domain elements and categorizes them by functional area to build foundations for domain modeling and conceptual understanding.
license: MIT
---

# Domain Extract Concepts

Extract and structure domain-specific concepts, entities, and terminology from requirements to build foundational domain models.

## Intent
Systematically identify and extract domain entities, business concepts, terminology, and relationships from structured requirements. Creates comprehensive domain concept maps with categorization, definitions, and traceability for downstream domain modeling tasks.

## Inputs
- **Source**: `projects/[project-name]/artifacts/Analysis/requirements.json` (from requirements-ingest skill)
- **Secondary**: `projects/[project-name]/artifacts/Analysis/goals.json` (from goals-extract skill, optional)
- **Format**: Structured requirements with IDs, classifications, and text content

## Outputs
**Files Generated:**
- `projects/[project-name]/artifacts/Analysis/domain-concepts.json` - Structured domain data for programmatic use
- `projects/[project-name]/artifacts/Analysis/domain-concepts.md` - Human-readable domain documentation

### JSON Structure (`domain-concepts.json`)
```json
{
  "project_id": "string",
  "extraction_metadata": {
    "generated_at": "ISO8601",
    "source_files": ["requirements.json", "goals.json"],
    "total_concepts": "number",
    "total_entities": "number",
    "confidence_score": "0.0-1.0"
  },
  "domain_entities": [
    {
      "id": "ENT-001",
      "name": "User",
      "description": "System user who interacts with the application",
      "attributes": [
        {
          "name": "user_id",
          "type": "identifier",
          "description": "Unique user identifier",
          "visibility": "public"
        },
        {
          "name": "role",
          "type": "enumeration",
          "description": "User access level",
          "values": ["admin", "user", "guest"],
          "visibility": "public"
        }
      ],
      "operations": [
        {
          "name": "authenticate",
          "description": "Verify user credentials",
          "parameters": ["username", "password"],
          "return_type": "boolean",
          "visibility": "public"
        },
        {
          "name": "updateProfile",
          "description": "Modify user profile data",
          "parameters": ["profile_data"],
          "return_type": "void",
          "visibility": "public"
        }
      ],
      "domain_area": "Authentication",
      "source_refs": ["R-001:section1", "R-003:section2"],
      "confidence": "0.0-1.0"
    }
  ],
  "business_concepts": [
    {
      "id": "CON-001",
      "name": "Authentication",
      "definition": "Process of verifying user identity",
      "synonyms": ["login", "sign-in", "verification"],
      "domain_area": "Security",
      "source_refs": ["R-001:section1"],
      "confidence": "0.0-1.0"
    }
  ],
  "terminology": [
    {
      "term": "API",
      "definition": "Application Programming Interface",
      "context": "Interface for system interactions",
      "domain_area": "Technical",
      "source_refs": ["R-005:section3"]
    }
  ],
  "relationships": [
    {
      "id": "REL-001",
      "type": "association",
      "source_entity": "User",
      "target_entity": "Role",
      "description": "User has assigned role",
      "cardinality": "many-to-one",
      "source_refs": ["R-002:section1"]
    }
  ],
  "domain_areas": [
    {
      "name": "Authentication",
      "description": "User identity verification and access control",
      "entities": ["User", "Role", "Session"],
      "concepts": ["Authentication", "Authorization"],
      "key_processes": ["Login", "Logout", "Password Reset"]
    }
  ]
}
```

### Markdown Structure (`domain-concepts.md`)
```markdown
# Domain Concepts Analysis

**Project**: [project_id]  
**Generated**: [timestamp]  
**Source**: requirements.json, goals.json  
**Total Entities**: [count] | **Total Concepts**: [count]

## Domain Areas

### Authentication & Security
**Key Entities**: User, Role, Session  
**Core Concepts**: Authentication, Authorization, Access Control  
**Primary Processes**: Login, Logout, Password Reset

### [Additional Domain Areas...]

## Entities

### User *(ENT-001)*
**Domain Area**: Authentication  
**Description**: System user who interacts with the application

**Attributes**:
- `user_id` (identifier): Unique user identifier [public]
- `role` (enumeration): User access level [admin, user, guest] [public]
- `email` (string): User contact information [public]

**Operations**:
- `authenticate(username, password)` → boolean: Verify user credentials [public]
- `updateProfile(profile_data)` → void: Modify user profile data [public]
- `validateSession()` → boolean: Check session validity [private]

**Source References**: [R-001:section1], [R-003:section2]

### [Additional Entities...]

## Business Concepts

### Authentication *(CON-001)*
**Domain Area**: Security  
**Definition**: Process of verifying user identity  
**Synonyms**: login, sign-in, verification  
**Source References**: [R-001:section1]

### [Additional Concepts...]

## Terminology Glossary

| Term | Definition | Domain Area | Context |
|------|------------|-------------|---------|
| API | Application Programming Interface | Technical | Interface for system interactions |
| OAuth | Open Authorization standard | Security | Third-party authentication protocol |

## Entity Relationships

### User ↔ Role *(REL-001)*
**Type**: Association (many-to-one)  
**Description**: User has assigned role  
**Source**: [R-002:section1]

### [Additional Relationships...]

---
**Traceability**: Extracted from requirements [R-001, R-002, R-003...]  
**Confidence Score**: 0.85/1.0  
**Generated**: [timestamp]
```

## Core Extraction Process

### 1. Entity Identification
**Pattern Recognition**:
- **Nouns as entities**: User, Order, Product, Payment
- **Process actors**: Customer, Administrator, System
- **Data containers**: Profile, Transaction, Report
- **Physical objects**: Card, Device, Document

**Attribute Discovery**:
- **Identifiers**: ID fields, unique keys, reference numbers
- **Properties**: Names, descriptions, status fields
- **Relationships**: Foreign keys, associations, dependencies
- **Constraints**: Required fields, validation rules, business rules

### 2. Concept Extraction
**Business Process Identification**:
- **Actions**: Create, Update, Delete, Process, Validate
- **Workflows**: Registration, Checkout, Approval, Notifications
- **States**: Active, Pending, Completed, Cancelled
- **Rules**: Business logic, validation criteria, approval processes

**Terminology Mining**:
- **Domain-specific terms**: Technical jargon, industry terminology
- **Acronyms**: API, REST, SSL, PCI, GDPR
- **Business language**: KPIs, SLAs, ROI, conversion rates
- **Synonyms**: Multiple terms for same concept

### 3. Relationship Discovery
**Association Patterns**:
- **Ownership**: User owns Account, Account contains Transactions
- **Hierarchy**: Organization has Departments, Departments have Teams
- **Dependencies**: Order requires Payment, Payment needs Validation
- **Temporal**: Event triggers Action, Action produces Result

### 4. Categorization Strategy
**Domain Areas**:
- **Core Business**: Primary business functions and processes
- **Supporting Systems**: Authentication, reporting, configuration
- **Technical Infrastructure**: APIs, databases, integrations
- **Compliance & Security**: Audit, permissions, data protection

## Processing Guidelines

### Entity Extraction Rules
1. **Focus on business-relevant nouns** that represent persistent data or active system participants
2. **Abstract concrete implementations** - prefer "Payment" over "CreditCardPayment" for initial modeling
3. **Prioritize entities with attributes and operations** - standalone terms may be concepts rather than entities
4. **Extract behavioral aspects** - identify key operations/methods that entities perform
5. **Capture visibility indicators** - distinguish between public and private attributes/operations
6. **Maintain traceability** to source requirements for validation and refinement

### Operation/Method Extraction
1. **Identify entity behaviors** from action verbs in requirements (authenticate, validate, process)
2. **Extract parameters** from context - what data does the operation need
3. **Determine return types** based on expected outcomes
4. **Infer visibility** from business context - public for external interactions, private for internal logic
5. **Group related operations** to understand entity responsibilities

### Concept Classification
1. **Business Concepts**: Domain-specific processes, rules, and workflows
2. **Technical Concepts**: Implementation patterns, protocols, standards
3. **Data Concepts**: Information structures, formats, validation rules
4. **Process Concepts**: Workflows, state transitions, business logic

### Relationship Identification
1. **Explicit relationships**: Clearly stated associations in requirements
2. **Implied relationships**: Logical connections based on context
3. **Temporal relationships**: Process flows and state transitions
4. **Hierarchical relationships**: Parent-child, container-contained patterns

## Quality Assurance

### Validation Checks
- **Entity completeness**: All major business objects identified
- **Attribute coverage**: Key properties captured for each entity
- **Operation completeness**: Essential behaviors and methods identified
- **Relationship consistency**: Bidirectional associations properly mapped
- **Terminology accuracy**: Definitions align with business context
- **Traceability integrity**: All extractions linked to source requirements
- **Diagram readiness**: Entities structured for effective class diagram generation

### Confidence Scoring
- **High (0.8-1.0)**: Explicitly stated in requirements with clear definitions
- **Medium (0.5-0.79)**: Strongly implied or partially defined
- **Low (0.2-0.49)**: Inferred from context or weakly supported

For detailed extraction patterns and advanced analysis techniques, see [extraction-patterns.md](references/extraction-patterns.md).

## Usage Pattern
```
1. Call after requirements-ingest skill completion
2. Load requirements.json and optional goals.json
3. Extract domain entities, concepts, terminology, and relationships
4. Generate structured domain-concepts.json for programmatic use
5. Create human-readable domain-concepts.md documentation
6. Trigger diagram-generatecollaboration skill for visual domain modeling (optional)
7. Feed results to domain-alignentities skill for organizational alignment
```

## Integration with Diagram Generation
This skill can trigger automatic class diagram generation through integration with the diagram-generatecollaboration skill:

### Diagram Integration Parameters
```json
{
  "generate_class_diagram": true,
  "update_domain_model": "orgModel/01-skill-dev/domain-model.md",
  "diagram_style": "comprehensive|overview|focused",
  "include_operations": true
}
```

### Class Diagram Generation Flow
1. **Extract entities with operations** - Capture both static and behavioral aspects
2. **Generate domain-concepts.json** - Include detailed entity metadata
3. **Call diagram-generatecollaboration** - Create class diagram from extracted concepts
4. **Update domain-model.md** - Embed generated diagram in organizational model
5. **Maintain traceability** - Link diagram elements back to source requirements

## Cross-Skill Integration

### Input Dependencies:
- requirements-ingest skill → requirements.json
- goals-extract skill → goals.json (optional)

### Output Consumers:
- domain-alignentities skill ← domain-concepts.json
- diagram-generatecollaboration skill ← domain-concepts.json (for class diagrams)
- domain-proposenewconcepts skill ← domain-concepts.json

### Diagram Generation Integration:
When integrated with diagram-generatecollaboration skill:
- Extracted entities automatically generate class diagram elements with inline styling (`:::category`)
- Operations and attributes populate class diagram structure with proper visibility markers
- Relationships become diagram associations and inheritance with clear labels
- Domain areas organize diagram layout and grouping
- Styling definitions (`classDef`) must be included for proper rendering:
  - `classDef actor fill:#e1f5fe` - for primary and supporting actors
  - `classDef entity fill:#f3e5f5` - for core business entities
  - `classDef enum fill:#fff3e0` - for enumeration types
  - `classDef ai fill:#e8f5e8` - for AI-specific entities and components