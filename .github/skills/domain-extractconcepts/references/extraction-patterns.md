# Domain Concept Extraction Patterns

Advanced patterns and techniques for extracting domain concepts from requirements with high accuracy and completeness.

## Table of Contents

1. [Entity Extraction Patterns](#entity-extraction-patterns)
2. [Attribute Discovery Techniques](#attribute-discovery-techniques)
3. [Relationship Mapping Strategies](#relationship-mapping-strategies)
4. [Concept Classification Methods](#concept-classification-methods)
5. [Domain Area Organization](#domain-area-organization)
6. [Common Extraction Challenges](#common-extraction-challenges)

## Entity Extraction Patterns

### Primary Entity Indicators

**Direct Noun Patterns**:
```
- "The [system] shall store [user] information"
- "[Customer] places [order] through [portal]"
- "[Administrator] manages [product] catalog"
- "[Report] contains [transaction] details"
```

**Role-Based Patterns**:
```
- "As a [customer], I want to..."
- "[Manager] shall be able to..."
- "Only [authorized personnel] can..."
- "[Guest users] may view..."
```

**Data Storage Patterns**:
```
- "System maintains [user profiles]"
- "[Order history] is preserved"
- "Store [payment methods]"
- "[Audit logs] track changes"
```

**Process Actor Patterns**:
```
- "[Scheduler] runs daily"
- "[Validator] checks input"
- "[Notifier] sends alerts"
- "[Processor] handles requests"
```

### Entity Refinement Rules

1. **Merge Similar Entities**: 
   - "User" + "Customer" → Evaluate if same concept
   - "Product" + "Item" → Usually same entity
   - "Order" + "Purchase" → Context-dependent merge

2. **Split Overloaded Entities**:
   - "User" → "Customer" + "Administrator" + "System User"
   - "Document" → "Invoice" + "Report" + "Contract"

3. **Abstract Implementation Details**:
   - "CreditCardPayment" → "Payment" 
   - "EmailNotification" → "Notification"
   - "DatabaseConnection" → "DataSource"

## Attribute Discovery Techniques

### Attribute Pattern Recognition

**Identifier Attributes**:
```text
- "[Entity] ID", "[Entity] number", "[Entity] code"
- "Unique [attribute] identifier"
- "[Entity] reference", "[Entity] key"
```

**Descriptive Attributes**:
```text
- "[Entity] name", "[Entity] title", "[Entity] description"
- "[Entity] type", "[Entity] category", "[Entity] status"
- "[Entity] value", "[Entity] amount", "[Entity] count"
```

**Temporal Attributes**:
```text
- "[Entity] date", "[Entity] time", "[Entity] timestamp"
- "Created on", "Updated at", "Expires on"
- "[Entity] duration", "[Entity] period"
```

**Relational Attributes**:
```text
- "[Entity] owner", "[Entity] creator", "[Entity] assignee"
- "Parent [entity]", "Related [entity]"
- "[Entity] belongs to", "[Entity] contains"
```

### Attribute Type Classification

| Pattern | Type | Example |
|---------|------|---------|
| ID, Code, Number | Identifier | user_id, order_number |
| Name, Title, Label | String | full_name, product_title |
| Amount, Count, Size | Numeric | price, quantity, file_size |
| Date, Time, Timestamp | Temporal | created_date, updated_at |
| Status, State, Type | Enumeration | order_status, user_type |
| Flag, Enabled, Active | Boolean | is_active, enabled |

### Data Constraint Extraction

**Validation Rules**:
```
- "Email must be valid format"
- "Password minimum 8 characters"
- "Amount must be positive"
- "Date cannot be future"
```

**Business Rules**:
```
- "Manager approval required for amounts > $1000"
- "Orders expire after 30 days"
- "Users limited to 3 login attempts"
- "Items require category assignment"
```

## Relationship Mapping Strategies

### Relationship Pattern Recognition

**Ownership Relationships**:
```
- "[User] owns [account]"
- "[Account] belongs to [user]"
- "[Company] has [employees]"
- "Each [order] is placed by [customer]"
```

**Composition Relationships**:
```
- "[Order] contains [line items]"
- "[Report] includes [sections]"
- "[Form] has [fields]"
- "[Document] comprises [pages]"
```

**Association Relationships**:
```
- "[User] assigned to [project]"
- "[Product] associated with [category]"  
- "[Customer] linked to [account manager]"
- "[Issue] related to [component]"
```

**Hierarchy Relationships**:
```
- "[Department] reports to [organization]"
- "[Manager] supervises [employees]"
- "[Category] has [subcategories]"
- "[Permission] inherits from [role]"
```

### Cardinality Detection

**One-to-One Indicators**:
```
- "Each [A] has exactly one [B]"
- "[A] corresponds to single [B]"
- "One-to-one mapping between [A] and [B]"
```

**One-to-Many Indicators**:
```
- "[A] can have multiple [B]"
- "Each [A] contains several [B]"
- "[A] manages many [B]"
```

**Many-to-Many Indicators**:
```
- "[A] can be assigned to multiple [B]"
- "[B] can belong to several [A]"
- "Many-to-many relationship"
```

## Concept Classification Methods

### Business Concept Categories

**Core Business Processes**:
```
- Registration, Authentication, Authorization
- Order Processing, Payment, Fulfillment
- Inventory Management, Procurement
- Customer Service, Support
```

**Supporting Processes**:
```
- Reporting, Analytics, Monitoring
- Configuration, Administration
- Backup, Recovery, Maintenance
- Audit, Compliance, Security
```

**Data Management Concepts**:
```
- Validation, Verification, Cleansing
- Import, Export, Synchronization
- Archival, Retention, Purging
- Encryption, Masking, Redaction
```

### Technical Concept Categories

**System Integration**:
```
- API, Web Service, Message Queue
- ETL, Data Pipeline, Synchronization
- Webhook, Event Streaming
```

**Infrastructure Concepts**:
```
- Load Balancing, Clustering, Scaling
- Caching, Session Management
- Monitoring, Logging, Alerting
```

**Security Concepts**:
```
- Authentication, Authorization, Access Control
- Encryption, Hashing, Digital Signatures
- Firewall, VPN, SSL/TLS
```

## Domain Area Organization

### Domain Identification Strategy

**Functional Decomposition**:
1. **Primary Functions**: Core business capabilities
2. **Secondary Functions**: Supporting operations
3. **Cross-cutting Concerns**: Security, logging, configuration

**Architectural Decomposition**:
1. **Presentation Layer**: UI concepts, user interactions
2. **Business Layer**: Business logic, workflows, rules
3. **Data Layer**: Persistence, storage, data management
4. **Integration Layer**: External systems, APIs

### Domain Boundary Guidelines

**Strong Domain Boundaries** (Separate domains):
```
- Customer Management vs Order Processing
- Inventory vs Financial Management
- User Management vs Content Management
- Reporting vs Core Business Logic
```

**Weak Domain Boundaries** (Same domain):
```
- User Authentication + User Authorization
- Order Creation + Order Processing
- Product Catalog + Product Search
- Payment Processing + Transaction History
```

## Common Extraction Challenges

### Ambiguity Resolution

**Multiple Meanings**:
```
Problem: "Account" could mean:
- User account (authentication)
- Financial account (banking)
- Customer account (CRM)

Solution: Use context and create qualified names:
- UserAccount, BankAccount, CustomerRecord
```

**Synonym Detection**:
```
Problem: "User", "Customer", "Client" refer to same concept

Solution: Identify primary term and list synonyms:
- Primary: "Customer"
- Synonyms: ["User", "Client", "Account Holder"]
```

### Scope Management

**Over-Extraction** (Too granular):
```
Problem: Extracting implementation details as entities
- "DatabaseConnection", "HTTPRequest", "JSONParser"

Solution: Focus on business-relevant abstractions
- "DataSource", "APIRequest", "MessageFormat"
```

**Under-Extraction** (Too coarse):
```
Problem: Missing important domain distinctions
- Single "User" entity for customers and administrators

Solution: Identify meaningful specializations
- "Customer", "Administrator", "SystemUser"
```

### Context Sensitivity

**Domain-Specific Interpretation**:
```
Healthcare: "Patient" vs "Provider"
E-commerce: "Buyer" vs "Seller" 
Finance: "Debtor" vs "Creditor"
Education: "Student" vs "Instructor"
```

**Process Stage Awareness**:
```
"Order" entity attributes vary by process stage:
- Draft: incomplete data, temporary storage
- Submitted: full validation, immutable core data
- Fulfilled: tracking info, completion timestamps
```

## Advanced Techniques

### Pattern-Based Entity Recognition

Use regular expressions and NLP patterns for systematic recognition:

```python
# Entity pattern examples
ENTITY_PATTERNS = [
    r"(?:the|a|an)\s+(\w+)\s+(?:shall|will|must)",
    r"(\w+)\s+(?:contains|includes|has|stores)",
    r"(?:each|every)\s+(\w+)",
    r"(\w+)\s+(?:entity|object|record|table)"
]

# Attribute pattern examples
ATTRIBUTE_PATTERNS = [
    r"(\w+)\s+(?:ID|identifier|number|code)",
    r"(\w+)\s+(?:name|title|label|description)",
    r"(\w+)\s+(?:date|time|timestamp)",
    r"(\w+)\s+(?:status|state|type|category)"
]
```

### Semantic Analysis for Relationship Detection

Use dependency parsing to identify relationships:

```python
# Relationship pattern examples
RELATIONSHIP_PATTERNS = [
    r"(\w+)\s+(?:owns|has|contains)\s+(\w+)",
    r"(\w+)\s+(?:belongs to|is part of)\s+(\w+)",
    r"(\w+)\s+(?:manages|supervises|controls)\s+(\w+)",
    r"(\w+)\s+(?:uses|requires|depends on)\s+(\w+)"
]
```

### Confidence Scoring Algorithm

```python
def calculate_confidence(extraction_data):
    factors = {
        'explicit_mention': 0.4,  # Direct statement
        'context_support': 0.3,   # Supporting context
        'pattern_match': 0.2,     # Pattern recognition
        'domain_relevance': 0.1   # Domain appropriateness
    }
    
    score = sum(factors[factor] * value 
               for factor, value in extraction_data.items())
    return min(score, 1.0)
```