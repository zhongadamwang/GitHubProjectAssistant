# Alignment Patterns and Conflict Resolution Strategies

This reference provides detailed patterns for aligning domain concepts and resolving conflicts between extracted concepts and organizational models.

## Alignment Pattern Catalog

### Entity Alignment Patterns

#### Pattern: Direct Name Match
**Description**: Extracted entity name exactly matches organizational entity name  
**Confidence**: 0.9-1.0  
**Action**: Map directly unless definition conflicts exist

**Example**:
- Extracted: "User" with attributes [id, name, email]
- Organizational: "User" with attributes [user_id, full_name, email_address]
- **Alignment**: Direct match with attribute name variations

#### Pattern: Semantic Equivalence
**Description**: Different names but equivalent semantic meaning and purpose  
**Confidence**: 0.7-0.9  
**Action**: Map to organizational term, suggest renaming extracted concept

**Example**:
- Extracted: "Customer" 
- Organizational: "Client"
- **Alignment**: Same role, different terminology preference

#### Pattern: Specialization Relationship
**Description**: Extracted entity is a specific type of broader organizational entity  
**Confidence**: 0.6-0.8  
**Action**: Define as specialization, extend organizational model

**Example**:
- Extracted: "ExternalUser"
- Organizational: "User" 
- **Alignment**: ExternalUser specializes User with additional constraints

#### Pattern: Aggregation Relationship  
**Description**: Extracted entity represents a collection or container of organizational entities  
**Confidence**: 0.6-0.8  
**Action**: Define aggregation relationship, update model structure

**Example**:
- Extracted: "UserGroup"
- Organizational: "User"
- **Alignment**: UserGroup aggregates multiple Users

### Terminology Alignment Patterns

#### Pattern: Exact Definition Match
**Description**: Term and definition exactly match vocabulary entry  
**Confidence**: 1.0  
**Action**: Use organizational term consistently

#### Pattern: Synonym Relationship  
**Description**: Different term, equivalent meaning  
**Confidence**: 0.8-0.9  
**Action**: Map to canonical organizational term

**Examples**:
- "Login" → "Authentication" (canonical org term)
- "Validate" → "Verify" (organizational standard)

#### Pattern: Narrower Term  
**Description**: Extracted term is more specific than organizational term  
**Confidence**: 0.7-0.8  
**Action**: Define as specialization of broader organizational term

**Example**:
- Extracted: "Password Authentication"
- Organizational: "Authentication"
- **Alignment**: Password Authentication is a type of Authentication

#### Pattern: Context-Specific Usage
**Description**: Same term used differently in different contexts  
**Confidence**: 0.5-0.7  
**Action**: Document context distinction, avoid forcing alignment

**Example**:
- Project "Assessment": Evaluation of user requirements
- Organizational "Assessment": Skills evaluation process
- **Resolution**: Maintain separate definitions with context qualifiers

### Relationship Alignment Patterns

#### Pattern: Equivalent Relationship Structure
**Description**: Same relationship pattern with equivalent entities  
**Confidence**: 0.8-1.0  
**Action**: Map to existing organizational pattern

**Example**:
- Extracted: User "has" Profile
- Organizational: Team Member "has" Skill Profile  
- **Alignment**: Same ownership pattern, map entities accordingly

#### Pattern: Inverse Relationship
**Description**: Relationship exists but from opposite perspective  
**Confidence**: 0.7-0.9  
**Action**: Normalize to organizational perspective

**Example**:
- Extracted: Role "assigned to" User
- Organizational: User "has" Role
- **Alignment**: Flip relationship direction to match organizational model

#### Pattern: Missing Intermediate Entity
**Description**: Direct relationship where organizational model has intermediate entity  
**Confidence**: 0.6-0.8  
**Action**: Introduce intermediate entity to match organizational pattern

**Example**:
- Extracted: User "completes" Task
- Organizational: User "creates" Assignment, Assignment "references" Task
- **Alignment**: Introduce Assignment entity to match organizational pattern

## Conflict Resolution Strategies

### Naming Conflicts

#### Strategy: Adopt Organizational Standard
**When to Use**: Extracted term is less specific or less established  
**Implementation**:
1. Rename extracted entity to match organizational term
2. Update all references and relationships
3. Document mapping in alignment report

#### Strategy: Define Specialization
**When to Use**: Extracted term represents valid specialization of organizational term  
**Implementation**:
1. Define extracted term as subtype of organizational term
2. Document additional attributes or constraints
3. Update organizational model with specialization

#### Strategy: Context Qualification  
**When to Use**: Same term serves different valid purposes in different contexts  
**Implementation**:
1. Add context qualifiers to both terms
2. Document usage contexts clearly
3. Maintain separate definitions

### Definition Conflicts

#### Strategy: Reconcile Through Generalization
**When to Use**: Definitions can be unified at higher abstraction level  
**Implementation**:
1. Identify common aspects of both definitions
2. Create generalized definition encompassing both
3. Define specific variants as needed

#### Strategy: Maintain Distinct Definitions
**When to Use**: Definitions serve different purposes and cannot be unified  
**Implementation**:
1. Document both definitions with clear usage contexts
2. Establish naming conventions to avoid confusion
3. Create cross-reference mapping between terms

### Structural Conflicts

#### Strategy: Attribute Harmonization
**When to Use**: Same entity has different attributes in different models  
**Implementation**:
1. Identify core vs. optional attributes
2. Harmonize attribute names and types
3. Define attribute dependencies and constraints

#### Strategy: Relationship Pattern Standardization
**When to Use**: Similar relationships modeled differently  
**Implementation**:
1. Analyze relationship semantics and cardinalities
2. Choose more expressive or established pattern
3. Transform conflicting patterns to standard form

## Model Evolution Guidelines

### Organizational Model Extensions

#### Criteria for Adding New Entities
1. **Reusability**: Likely to appear in multiple projects
2. **Distinctness**: Not covered by existing organizational entities
3. **Business Value**: Represents important business concept
4. **Stability**: Concept definition is well-established

#### Criteria for Adding New Relationships
1. **Pattern Consistency**: Follows established relationship patterns
2. **Semantic Clarity**: Relationship meaning is unambiguous
3. **Cardinality Logic**: Relationship constraints make business sense
4. **Model Completeness**: Fills genuine gap in organizational model

### Vocabulary Extensions

#### Criteria for Adding New Terms
1. **Organizational Relevance**: Term will be used across multiple projects
2. **Definition Precision**: Term has clear, unambiguous definition
3. **Terminology Gap**: Concept not adequately covered by existing vocabulary
4. **Stakeholder Consensus**: Term usage agreed upon by domain experts

## Quality Metrics

### Alignment Quality Indicators

#### High Quality Alignment
- **Confidence > 0.8** for entity mappings
- **< 10% conflicts** in terminology alignment  
- **Relationship patterns preserved** across mappings
- **Traceability maintained** to source requirements

#### Alignment Issues
- **Confidence < 0.5** for multiple entity mappings
- **> 25% conflicts** in terminology
- **Broken relationship chains** after mapping
- **Loss of semantic precision** in alignments

### Model Evolution Metrics

#### Healthy Evolution
- **< 20% new entities** relative to existing organizational model
- **Backward compatible** relationship changes
- **Vocabulary growth < 15%** of existing terms
- **Clear documentation** of all extensions

#### Evolution Red Flags  
- **> 50% new entities** suggests misaligned project or inadequate organizational model
- **Breaking relationship changes** impact existing projects
- **Vocabulary explosion** indicates terminology drift
- **Undocumented changes** create technical debt

## Implementation Workflow

### Pre-Alignment Setup
1. **Load organizational models** from `orgModel/**/*domain-model.md`
2. **Load organizational vocabularies** from `orgModel/**/*vocabulary.md`
3. **Parse extracted concepts** from `domain-concepts.json`
4. **Initialize alignment matrices** for entities, terms, and relationships

### Alignment Execution
1. **Entity alignment**: Apply entity pattern matching algorithms
2. **Terminology alignment**: Execute vocabulary matching and conflict detection
3. **Relationship alignment**: Analyze and standardize relationship patterns
4. **Conflict resolution**: Apply resolution strategies based on conflict types
5. **Quality assessment**: Calculate confidence scores and quality metrics

### Post-Alignment Activities
1. **Generate alignment artifacts**: Create JSON and markdown reports
2. **Document recommendations**: Prioritize alignment actions
3. **Propose model changes**: Suggest organizational model extensions
4. **Create change requests**: Generate formal change documentation

This systematic approach ensures consistent domain alignment across projects while supporting controlled evolution of organizational domain models.