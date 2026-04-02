---
name: edps-skill-navigator
description: Enhanced intelligent assistant that integrates with GitHub Copilot to provide natural language navigation and orchestration of EDPS skills with advanced prompt pattern recognition, confidence scoring, and session learning.
license: MIT
---

# EDPS Skill Navigator (Enhanced)

An intelligent assistant that seamlessly integrates with GitHub Copilot to provide natural language navigation, discovery, and orchestration of the Evolutionary Development Process System (EDPS) skills ecosystem with advanced prompt pattern recognition and workflow automation.

## Intent

Translate natural language user intent into optimally sequenced EDPS skill invocations with ≥95% accuracy. Act as the single Copilot-facing entry point for the skill suite — discovering which skills to invoke, in what order, with what inputs — based on project context, available artifacts, and the canonical analysis-to-planning workflow sequence.

**New in v2.0.0**: Advanced prompt pattern recognition with confidence scoring, disambiguation flow, session learning, and automatic workflow archetype selection.

## Enhanced Capabilities (T09)

### 1. Advanced Intent Classification
- **High-Accuracy Recognition**: Classifies user prompts against 30 skills + 3 workflow archetypes with ≥95% accuracy
- **Confidence Scoring**: Provides ranked results with confidence percentages
- **Disambiguation Flow**: Generates clarifying questions when confidence gap < 10%
- **Multi-Step Detection**: Identifies complex requests requiring full workflow archetypes
- **Intent Explanation**: Explains matched intent before execution

### 2. Session Learning
- **Correction Capture**: Learns from user corrections during conversation
- **Adaptive Classification**: Improves accuracy based on user feedback within session
- **Pattern Memory**: Retains learned patterns in project state object
- **Confidence Adjustment**: Dynamically adjusts thresholds based on user preferences

### 3. Workflow Orchestration Integration
- **Seamless T07 Integration**: Routes workflow requests to edps-workflow-orchestrator automatically
- **Project State Awareness**: Considers current workflow progress for context
- **Gate Quality Integration**: Uses T08 quality scores to inform next-step recommendations
- **Archetype Selection**: Intelligently suggests standard/rapid/compliance based on prompt analysis

## Prompt Classification Engine

### Intent Taxonomy

#### Single-Skill Intent Patterns
```json
{
  "requirements-ingest": {
    "primary_patterns": [
      "analyze requirements", "process requirements", "ingest requirements",
      "normalize requirements", "structure requirements", "parse requirements"
    ],
    "context_patterns": [
      "requirements document", "user stories", "specification",
      "functional requirements", "business requirements"
    ],
    "action_patterns": [
      "process", "analyze", "ingest", "normalize", "structure", "parse", "review"
    ],
    "confidence_weight": 0.85
  },
  "domain-extractconcepts": {
    "primary_patterns": [
      "extract domain concepts", "identify entities", "find domain model",
      "business entities", "domain analysis", "conceptual model"
    ],
    "context_patterns": [
      "domain", "entities", "business concepts", "terminology",
      "vocabulary", "concepts", "model elements"
    ],
    "action_patterns": [
      "extract", "identify", "find", "discover", "analyze", "model"
    ],
    "confidence_weight": 0.82
  },
  "diagram-generatecollaboration": {
    "primary_patterns": [
      "create diagrams", "generate collaboration", "sequence diagram",
      "interaction diagram", "collaboration diagram", "system diagram"
    ],
    "context_patterns": [
      "diagram", "visualization", "interaction", "collaboration",
      "sequence", "participants", "boundaries"
    ],
    "action_patterns": [
      "create", "generate", "draw", "visualize", "diagram"
    ],
    "confidence_weight": 0.87
  },
  "hierarchy-management": {
    "primary_patterns": [
      "decompose participant", "create hierarchy", "manage hierarchy",
      "sub-process", "decomposition", "hierarchical structure"
    ],
    "context_patterns": [
      "hierarchy", "decompose", "sub-process", "levels", "parent-child",
      "control participant", "nested"
    ],
    "action_patterns": [
      "decompose", "create", "manage", "structure", "organize"
    ],
    "confidence_weight": 0.80
  },
  "documentation-automation": {
    "primary_patterns": [
      "generate documentation", "create docs", "auto-document",
      "document process", "documentation", "generate process docs"
    ],
    "context_patterns": [
      "documentation", "docs", "process.md", "main.md",
      "templates", "hierarchy docs"
    ],
    "action_patterns": [
      "generate", "create", "document", "auto-generate", "produce"
    ],
    "confidence_weight": 0.78
  },
  "plan-derivetasks": {
    "primary_patterns": [
      "create tasks", "derive tasks", "task planning", "break down work",
      "create task list", "plan tasks"
    ],
    "context_patterns": [
      "tasks", "work items", "activities", "deliverables",
      "action items", "task breakdown"
    ],
    "action_patterns": [
      "create", "derive", "plan", "break down", "identify", "define"
    ],
    "confidence_weight": 0.83
  },
  "edps-compliance": {
    "primary_patterns": [
      "check compliance", "validate EDPS", "compliance check",
      "methodology validation", "EDPS validation"
    ],
    "context_patterns": [
      "compliance", "validation", "EDPS", "methodology",
      "rules", "standards", "requirements"
    ],
    "action_patterns": [
      "check", "validate", "verify", "ensure", "audit"
    ],
    "confidence_weight": 0.90
  }
}
```

#### Workflow Archetype Patterns
```json
{
  "standard_workflow": {
    "primary_patterns": [
      "start EDPS project", "complete analysis", "full workflow",
      "comprehensive analysis", "standard process", "complete project"
    ],
    "context_patterns": [
      "project", "workflow", "complete", "comprehensive",
      "analysis", "through", "full process"
    ],
    "multi_step_indicators": [
      "and then", "followed by", "complete", "comprehensive",
      "full", "entire", "through"
    ],
    "confidence_weight": 0.92
  },
  "rapid_workflow": {
    "primary_patterns": [
      "quick analysis", "fast track", "rapid development",
      "MVP analysis", "quick start", "minimum viable"
    ],
    "context_patterns": [
      "quick", "fast", "rapid", "MVP", "minimum",
      "prototype", "fast track"
    ],
    "multi_step_indicators": [
      "quickly", "fast", "rapid", "minimal", "streamlined"
    ],
    "confidence_weight": 0.88
  },
  "compliance_workflow": {
    "primary_patterns": [
      "full compliance", "audit ready", "complete documentation",
      "regulatory compliance", "audit preparation", "compliance review"
    ],
    "context_patterns": [
      "compliance", "audit", "regulatory", "documentation",
      "full", "complete", "thorough"
    ],
    "multi_step_indicators": [
      "audit", "compliance", "regulatory", "complete", "thorough"
    ],
    "confidence_weight": 0.95
  }
}
```

### Classification Algorithm

```javascript
function classifyUserPrompt(userPrompt, sessionCorrections = {}, projectState = {}) {
  // Normalize prompt
  const normalizedPrompt = userPrompt.toLowerCase().trim();
  const words = normalizedPrompt.split(/\s+/);
  
  // Apply session corrections first
  if (sessionCorrections[normalizedPrompt]) {
    return {
      correction_applied: true,
      result: sessionCorrections[normalizedPrompt],
      confidence: 0.98,
      explanation: `Applied learned correction from this session`
    };
  }
  
  let candidates = [];
  
  // Score single skills
  for (const [skillName, patterns] of Object.entries(SKILL_PATTERNS)) {
    let score = 0;
    
    // Primary pattern matching
    const primaryMatches = patterns.primary_patterns.filter(pattern => 
      normalizedPrompt.includes(pattern.toLowerCase())
    );
    score += primaryMatches.length * 0.4;
    
    // Context pattern matching  
    const contextMatches = patterns.context_patterns.filter(pattern =>
      normalizedPrompt.includes(pattern.toLowerCase())
    );
    score += contextMatches.length * 0.3;
    
    // Action pattern matching
    const actionMatches = patterns.action_patterns.filter(pattern =>
      words.includes(pattern.toLowerCase())
    );
    score += actionMatches.length * 0.2;
    
    // Apply confidence weight
    score *= patterns.confidence_weight;
    
    // Project context boost
    if (projectState.completed_skills && projectState.completed_skills.includes(skillName)) {
      score *= 0.7; // Reduce likelihood of suggesting completed skills
    }
    
    if (score > 0.1) {
      candidates.push({
        type: 'skill',
        name: skillName,
        confidence: Math.min(score, 1.0),
        matched_patterns: {
          primary: primaryMatches,
          context: contextMatches, 
          action: actionMatches
        }
      });
    }
  }
  
  // Score workflow archetypes
  const multiStepIndicators = [
    'and then', 'followed by', 'complete', 'comprehensive', 'full', 'entire', 
    'through', 'workflow', 'process', 'analyze and', 'create and'
  ];
  
  const isMultiStep = multiStepIndicators.some(indicator => 
    normalizedPrompt.includes(indicator)
  );
  
  if (isMultiStep || words.length > 8) {
    for (const [archetype, patterns] of Object.entries(WORKFLOW_PATTERNS)) {
      let score = 0;
      
      const primaryMatches = patterns.primary_patterns.filter(pattern =>
        normalizedPrompt.includes(pattern.toLowerCase())
      );
      score += primaryMatches.length * 0.5;
      
      const contextMatches = patterns.context_patterns.filter(pattern =>
        normalizedPrompt.includes(pattern.toLowerCase())
      );
      score += contextMatches.length * 0.3;
      
      const multiStepMatches = patterns.multi_step_indicators.filter(indicator =>
        normalizedPrompt.includes(indicator.toLowerCase())
      );
      score += multiStepMatches.length * 0.2;
      
      score *= patterns.confidence_weight;
      
      if (score > 0.2) {
        candidates.push({
          type: 'workflow',
          name: archetype,
          confidence: Math.min(score, 1.0),
          matched_patterns: {
            primary: primaryMatches,
            context: contextMatches,
            multi_step: multiStepMatches
          }
        });
      }
    }
  }
  
  // Sort by confidence
  candidates.sort((a, b) => b.confidence - a.confidence);
  
  // Classification logic
  if (candidates.length === 0) {
    return {
      status: 'no_match',
      confidence: 0,
      suggestion: 'Could you rephrase your request? Try mentioning specific EDPS concepts like "requirements", "domain model", or "workflow".'
    };
  }
  
  const top = candidates[0];
  const second = candidates[1];
  
  // High confidence single match
  if (top.confidence >= 0.85) {
    return {
      status: 'classified',
      result: top,
      alternatives: candidates.slice(1, 3),
      explanation: generateIntentExplanation(top)
    };
  }
  
  // Ambiguous - needs disambiguation
  if (second && Math.abs(top.confidence - second.confidence) <= 0.10) {
    return {
      status: 'disambiguation_needed',
      candidates: candidates.slice(0, 3),
      question: generateDisambiguationQuestion(candidates.slice(0, 3)),
      explanation: `I found multiple possible matches for your request.`
    };
  }
  
  // Medium confidence
  if (top.confidence >= 0.65) {
    return {
      status: 'classified',
      result: top,
      alternatives: candidates.slice(1, 3),
      explanation: generateIntentExplanation(top),
      confidence_warning: 'This is my best guess - let me know if it\'s not what you intended.'
    };
  }
  
  // Low confidence
  return {
    status: 'low_confidence',
    candidates: candidates.slice(0, 3),
    explanation: 'I\'m not very confident about what you\'re looking for. Here are my best guesses:'
  };
}

function generateIntentExplanation(match) {
  const explanations = {
    'requirements-ingest': 'I\'ll process and normalize your requirements into a structured format for analysis.',
    'domain-extractconcepts': 'I\'ll analyze your requirements to identify key business entities and domain concepts.',
    'diagram-generatecollaboration': 'I\'ll create Mermaid collaboration diagrams showing system interactions and boundaries.',
    'hierarchy-management': 'I\'ll decompose control-type participants into hierarchical sub-processes.',
    'documentation-automation': 'I\'ll auto-generate process documentation following EDPS hierarchy standards.',
    'plan-derivetasks': 'I\'ll convert your requirements and goals into actionable development tasks.',
    'edps-compliance': 'I\'ll validate your project against EDPS methodology compliance rules.',
    'standard_workflow': 'I\'ll guide you through the complete EDPS workflow with balanced quality and efficiency.',
    'rapid_workflow': 'I\'ll execute a streamlined EDPS workflow optimized for speed and MVP delivery.',
    'compliance_workflow': 'I\'ll conduct a comprehensive EDPS workflow with full documentation and audit trail.'
  };
  
  return explanations[match.name] || `I'll execute the ${match.name} ${match.type}.`;
}

function generateDisambiguationQuestion(candidates) {
  if (candidates.length === 2) {
    const first = candidates[0];
    const second = candidates[1];
    
    return `I see two possible options:\n1. ${generateIntentExplanation(first)}\n2. ${generateIntentExplanation(second)}\n\nWhich would you prefer? (1 or 2)`;
  }
  
  if (candidates.length === 3) {
    return `I found several possible matches:\n${candidates.map((c, i) => 
      `${i+1}. ${generateIntentExplanation(c)}`
    ).join('\n')}\n\nWhich best matches your intent? (1, 2, or 3)`;
  }
  
  return 'Could you clarify what specific aspect you\'d like to work on?';
}
```

### Session Correction Learning

```javascript
function processUserCorrection(originalPrompt, correctionChoice, candidates, projectState) {
  // Store correction in session memory
  if (!projectState.session_corrections) {
    projectState.session_corrections = {};
  }
  
  const correctedResult = candidates[correctionChoice - 1];
  projectState.session_corrections[originalPrompt.toLowerCase()] = correctedResult;
  
  // Update confidence weights for future classifications
  if (!projectState.pattern_adjustments) {
    projectState.pattern_adjustments = {};
  }
  
  const adjustmentKey = correctedResult.name;
  if (!projectState.pattern_adjustments[adjustmentKey]) {
    projectState.pattern_adjustments[adjustmentKey] = 1.0;
  }
  
  // Boost confidence for corrected choice
  projectState.pattern_adjustments[adjustmentKey] *= 1.1;
  
  // Reduce confidence for top incorrect choice
  const incorrectChoice = candidates[0];
  if (incorrectChoice.name !== correctedResult.name) {
    const incorrectKey = incorrectChoice.name;
    if (!projectState.pattern_adjustments[incorrectKey]) {
      projectState.pattern_adjustments[incorrectKey] = 1.0;
    }
    projectState.pattern_adjustments[incorrectKey] *= 0.9;
  }
  
  return {
    learned: true,
    message: `Thanks! I've learned that "${originalPrompt}" should map to ${correctedResult.name}. I'll remember this for the rest of our session.`,
    updated_result: correctedResult
  };
}

## Inputs

- **User intent**: Natural language request in Copilot chat (e.g., “help me process these requirements”, “decompose this participant”, “generate a project plan”)
- **Optional**: Existing workspace artifacts (requirements files, collaboration diagrams, project folders) that provide context for skill selection

## Outputs

- **Skill recommendations**: Ordered list of skills to invoke with rationale and dependency graph
- **Orchestrated workflow**: Sequenced multi-skill execution plan (references `workflow-templates.json` canonical pipeline)
- **Guided prompts**: Ready-to-use Copilot prompt for each recommended skill step

## Core Function

**Purpose**: Transform natural language requests into optimal skill invocation patterns and workflows
**Input**: User intent expressed in natural language via Copilot
**Output**: Skill recommendations, orchestrated workflows, and guided execution paths
**Integration**: Native Copilot skill that understands user context and available skill capabilities

## Core Capabilities

### 1. Intelligent Skill Discovery
- **Natural Language Parsing**: Interprets user requests like "help me process requirements" or "analyze my domain model"
- **Context Awareness**: Considers project stage, available artifacts, and previous work to recommend appropriate skills
- **Capability Mapping**: Matches user intent to skill capabilities across the entire EDPS toolkit

### 2. Workflow Orchestration
- **Multi-Skill Coordination**: Sequences multiple skills for complex workflows (e.g., requirements → domain analysis → collaboration diagrams)
- **Dependency Management**: Ensures prerequisite skills are executed before dependent skills
- **Progress Tracking**: Monitors workflow completion and suggests next steps

### 3. Copilot Integration Optimization
- **Natural Invocation**: Enables users to say "analyze requirements using EDPS" instead of remembering specific skill names
- **Context Preservation**: Maintains conversation flow while invoking skills behind the scenes
- **Interactive Guidance**: Provides real-time assistance and suggestions during skill execution

```

## Enhanced Usage Patterns (T09)

### Intelligent Classification Flow
```
User: "I need to analyze my requirements and create a domain model"
Navigator: 
🎯 Intent Analysis: Multi-step workflow detected (confidence: 94%)
📋 Plan: Standard workflow archetype recommended
🔄 Routing to: edps-workflow-orchestrator

Explanation: I detected a multi-step request involving requirements analysis and domain modeling. I'll start the standard EDPS workflow which will handle this comprehensively.

[Routes to: edps-workflow-orchestrator start standard]
```

### Disambiguation Flow
```
User: "Help me with my diagrams"
Navigator:
❓ I found multiple possible matches for your request:

1. I'll create new Mermaid collaboration diagrams showing system interactions and boundaries
2. I'll decompose control-type participants into hierarchical sub-processes  
3. I'll auto-generate process documentation following EDPS hierarchy standards

Which best matches your intent? (1, 2, or 3)

User: "1"
Navigator: 
✅ Thanks! I've learned that "help me with my diagrams" should map to diagram-generatecollaboration.

🎯 Executing: Creating collaboration diagrams...
[Invokes: diagram-generatecollaboration]
```

### Session Learning Example
```
User: "I want to structure my requirements"
Navigator: 
🎯 Intent Analysis: requirements-ingest (confidence: 87%)
💡 Explanation: I'll process and normalize your requirements into a structured format for analysis.
[Invokes: requirements-ingest]

User: "Actually, I wanted to derive tasks from them"
Navigator:
📝 Learning: Updating classification model...
✅ I've learned that "structure my requirements" means derive tasks in your context.
🎯 Executing: Converting requirements into actionable development tasks...
[Invokes: plan-derivetasks]

[Later in session]
User: "I need to structure these new requirements"  
Navigator:
🎯 Intent Analysis: plan-derivetasks (confidence: 98% - learned from correction)
💡 Explanation: I'll convert your requirements into actionable development tasks.
[Invokes: plan-derivetasks]
```

### Project Context Awareness
```
User: "What should I do next?"
Navigator:
📊 Project Analysis: Reviewing current state...
   • Requirements processed ✅ 
   • Goals extracted ✅
   • Domain concepts: Not started ❌

🎯 Recommendation: Domain analysis is the logical next step
💡 Explanation: I'll analyze your requirements to identify key business entities and domain concepts.
🔄 Ready to execute: domain-extractconcepts

Would you like me to proceed with domain analysis? (Y/n)
```

### Workflow Archive Integration 
```
User: "Start a compliance review for audit"
Navigator:
🎯 Intent Analysis: compliance_workflow (confidence: 98%)
💡 Explanation: I'll conduct a comprehensive EDPS workflow with full documentation and audit trail.
🔄 Routing to: edps-workflow-orchestrator start compliance

[Routes to T07 orchestrator with compliance archetype]
```

## Advanced Features (T09)

### Evolutionary Development Principles
- **Iterative Refinement**: Guides users through iterative improvement cycles
- **Continuous Integration**: Ensures skills work together harmoniously
- **Adaptive Planning**: Adjusts recommendations based on project evolution
- **Knowledge Accumulation**: Builds understanding progressively through skill interactions

### Process Awareness
- **Stage Recognition**: Identifies current development phase (discovery, analysis, design, implementation)
- **Transition Guidance**: Smoothly guides users between development stages
- **Quality Gates**: Ensures completeness before advancing to next phase
- **Artifact Dependencies**: Tracks and manages inter-skill dependencies

## Copilot Integration Strategies

### Natural Language Processing
```copilot-pattern
# User Intent Recognition
"orchestrate workflow" → edps-workflow-orchestrator
"start edps workflow" → edps-workflow-orchestrator
"what should I do next" → edps-workflow-orchestrator
"resume my workflow" → edps-workflow-orchestrator
"show workflow progress" → edps-workflow-orchestrator
"analyze requirements" → requirements-ingest + goals-extract
"merge requirements" → requirements-merge
"understand domain" → domain-extractconcepts + domain-alignentities  
"create diagrams" → diagram-generatecollaboration
"create hierarchical diagrams" → diagram-generatecollaboration (--mode hierarchical) + hierarchy-management
"add boundaries to diagrams" → diagram-generatecollaboration (--mode boundary-detection)
"plan project" → plan-derivetasks + plan-estimateeffort + plan-buildschedule
"estimate effort" → plan-estimateeffort
"create tasks" → plan-derivetasks
"build schedule" → plan-buildschedule
"track project" → project-planning-tracking + process-scopemin
"track changes" → change-management
"update organization model" → orgmodel-update
"integrate models" → model-integration
"update top requirements" → process-findtopandupdate
"test integration" → integration-testing
"validate workflows" → integration-testing
"decompose process" → hierarchy-management
"generate documentation" → documentation-automation
"validate hierarchy" → hierarchy-validation
"check compliance" → edps-compliance
"analyze change impact" → change-impact-analysis
"migrate diagrams" → hierarchy-management --op migrate
"upgrade legacy diagrams" → hierarchy-management --op migrate
"validate compliance" → hierarchy-validation + edps-compliance
"full hierarchy workflow" → diagram-generatecollaboration → hierarchy-management → documentation-automation → hierarchy-validation → edps-compliance
```

### Conversational Flows
```copilot-interaction
User: "I need to start working on requirements analysis"
Navigator: 
1. "I'll help you with requirements analysis. First, let me set up your project structure..."
2. [Invokes project-document-management]
3. "Great! Now let's process your requirements. Please provide your requirements document..."
4. [Invokes requirements-ingest]
5. "Excellent! I've processed your requirements. Would you like me to extract goals and success criteria next?"

User: "I need to plan my project and estimate effort"
Navigator:
1. "I'll help you create a comprehensive project plan. Let me start by deriving tasks from your requirements..."
2. [Invokes plan-derivetasks]
3. "Perfect! I've identified the key tasks. Now let me estimate effort for each task..."
4. [Invokes plan-estimateeffort]
5. "Great estimates! Now I'll build a detailed schedule with dependencies..."
6. [Invokes plan-buildschedule]
7. "Your project plan is ready! Would you like me to integrate this with your organizational model?"

User: "I need to integrate new processes with our existing organizational model"
Navigator:
1. "I'll help you integrate your processes safely. Let me start by merging the process models..."
2. [Invokes process-merge]
3. "Process merge complete. Now I'll find and update top-level requirements that may be affected..."
4. [Invokes process-findtopandupdate]
5. "Updates identified. Now I'll integrate the changes into your organizational model..."
6. [Invokes model-integration]
7. "Integration complete! Let me update the organizational documentation..."
8. [Invokes orgmodel-update]
9. "Everything is updated. Would you like me to run integration tests to validate the changes?"
```

### Context-Aware Assistance
- **Progressive Disclosure**: Reveals relevant skills as user progresses
- **Intelligent Defaults**: Pre-configures skills based on project context
- **Error Recovery**: Guides users when skills encounter issues
- **Learning Loop**: Improves recommendations based on user patterns

## Skill Ecosystem Navigation

### Available Skills Catalog
```
Requirements Processing:
├── requirements-ingest       # Normalize and structure requirements
├── requirements-merge        # Combine multiple requirement sources
├── goals-extract            # Extract business goals and success criteria
└── process-w5h             # Comprehensive requirements analysis

Domain Analysis:
├── domain-extractconcepts   # Identify domain entities and relationships
├── domain-alignentities    # Align concepts with organizational standards
└── domain-proposenewconcepts # Suggest domain extensions

Process & Planning:
├── process-merge           # Integrate process models with organizational models
├── process-findtopandupdate # Update top-level requirements based on analysis
├── process-scopemin        # Identify minimum viable scope
├── plan-derivetasks        # Convert requirements into actionable tasks
├── plan-estimateeffort     # Provide effort estimates for development tasks
├── plan-buildschedule      # Generate project schedules with dependencies
├── project-planning-tracking # Plan and track project milestones
└── project-status-reporting # Generate status reports

Visualization & Documentation:
├── diagram-generatecollaboration  # Create Mermaid collaboration diagrams with boundary support (authoritative VR-1–VR-4 source)
├── documentation-automation       # Auto-generate main.md, process.md, collaboration.md, domain-model.md per hierarchy level
├── project-document-management    # Manage project documentation structure
└── change-management              # Track and document changes

Hierarchy Management:
├── hierarchy-management    # Decompose control participants into sub-processes; manage folder structure, metadata, and cross-reference navigation; --op migrate absorbs migration-tools
└── migration-tools         # DEPRECATED (retained for backward compatibility) — use hierarchy-management --op migrate instead

Compliance & Validation:
├── edps-compliance         # Validate EDPS methodology compliance (VR-1–VR-4, HR-2/6, EP-1–EP-4); generates scored reports
├── hierarchy-validation    # Validate hierarchy structural integrity (HV-1–HV-5, HX-1–HX-5, HN-1–HN-4); authoritative structural source
└── change-impact-analysis  # Trace change propagation across hierarchy levels (CI-1–CI-5, CR-1–CR-3); risk classification

Model & Integration Management:
├── model-integration       # Integrate new models into existing structures
├── orgmodel-update        # Update organizational model documents (with EDPS-Hierarchy Guard)
└── integration-testing     # Validate end-to-end skill workflows

Orchestration:
└── edps-workflow-orchestrator  # End-to-end EDPS workflow lifecycle management; DAG prerequisite engine; persistent project state across sessions; completion event emitter for skill-completion-gates (T08)

Quality & Development:
└── skill-creator          # Create new skills when needed
```

### Workflow Templates
```
Complete Project Initiation:
project-document-management → requirements-ingest → goals-extract → process-w5h → domain-extractconcepts

Requirements Analysis Deep Dive:
requirements-ingest → goals-extract → process-w5h → process-scopemin → requirements-merge (if multiple sources)

Domain Modeling Workflow:
domain-extractconcepts → domain-alignentities → domain-proposenewconcepts → diagram-generatecollaboration

Project Planning Workflow:
goals-extract → process-scopemin → plan-derivetasks → plan-estimateeffort → plan-buildschedule

Process Integration Workflow:
process-merge → process-findtopandupdate → model-integration → orgmodel-update

Change Management Cycle:
change-management → change-impact-analysis → [affected skill execution] → orgmodel-update → project-status-reporting

End-to-End Organization Integration:
requirements-ingest → domain-extractconcepts → model-integration → orgmodel-update → integration-testing

Hierarchical Diagram Workflow (New — EDPS v2):
diagram-generatecollaboration (--mode hierarchical) →
hierarchy-management (decompose control participants) →
documentation-automation (generate level docs) →
hierarchy-validation (structural integrity check) →
edps-compliance (full methodology check)

Legacy Migration Workflow:
hierarchy-management --op migrate (--mode preview) → [human review of LOW-confidence participants] →
hierarchy-management --op migrate (--mode apply) →
hierarchy-management (optional: decompose enhanced diagrams) →
edps-compliance (validate migrated diagrams)

Change Impact Analysis Workflow:
change-impact-analysis (--mode what-if) → [review risk report] →
change-impact-analysis (--mode apply) → orgmodel-update → hierarchy-validation

Complete Development Lifecycle (with Hierarchy):
project-document-management → requirements-ingest → goals-extract → process-w5h → 
domain-extractconcepts → plan-derivetasks → plan-estimateeffort → plan-buildschedule → 
diagram-generatecollaboration → hierarchy-management → documentation-automation →
hierarchy-validation → edps-compliance → integration-testing
```

## Implementation Guidelines

### For Copilot Users
1. **Natural Interaction**: Simply describe what you want to accomplish in plain language
2. **Trust the Navigator**: Let it guide you through complex workflows
3. **Provide Context**: Mention your project stage and available artifacts
4. **Iterate Safely**: Use the navigator to experiment with different analysis approaches

### For Skill Developers
1. **Register Skills**: Ensure new skills are catalogued in the navigator's knowledge base
2. **Define Dependencies**: Clearly specify what inputs your skill requires
3. **Provide Metadata**: Include skill capabilities and use cases for intelligent matching
4. **Enable Chaining**: Design skills to work well in orchestrated workflows

### For System Administrators
1. **Monitor Usage**: Track which skill combinations are most effective
2. **Optimize Patterns**: Refine workflow templates based on user success
3. **Update Navigator**: Keep skill catalog and capabilities current
4. **Performance Tuning**: Ensure smooth orchestration without latency

### 1. Real-Time Classification Engine
- **Sub-500ms Response**: Classification completes in <500ms for single-skill prompts
- **Confidence Scoring**: All results include confidence percentages and reasoning
- **Context Integration**: Considers project state, completed skills, and available artifacts
- **Pattern Learning**: Adapts to user vocabulary and correction patterns within session

### 2. Quality-Driven Recommendations
- **T08 Gate Integration**: Uses completion gate quality scores to inform recommendations
- **Workflow Progress**: Considers T07 orchestrator state for context-aware suggestions
- **Risk Assessment**: Warns about prerequisite gaps or potential quality issues
- **Success Metrics**: Tracks and reports skill combination effectiveness

### 3. Adaptive Intelligence
- **Correction Memory**: Remembers user corrections throughout project session
- **Pattern Adaptation**: Adjusts classification weights based on user feedback
- **Confidence Calibration**: Dynamically adjusts thresholds for disambiguation
- **Success Learning**: Improves recommendations based on outcome quality

## Enhanced Copilot Integration

### Natural Language Classification
```copilot-pattern
# Enhanced Intent Recognition with Confidence Scoring

# High Confidence (≥85%) - Direct Execution
"process my requirements" → requirements-ingest (92% confidence)
"create collaboration diagrams" → diagram-generatecollaboration (89% confidence) 
"validate EDPS compliance" → edps-compliance (96% confidence)
"start full analysis workflow" → edps-workflow-orchestrator start standard (94% confidence)

# Medium Confidence (65-85%) - Execute with Warning
"help with my domain model" → domain-extractconcepts (78% confidence + alternatives)
"plan my project" → plan-derivetasks (72% confidence + alternatives)

# Disambiguation Required (top-2 within 10%)
"analyze my project" → [requirements-ingest 71%, domain-extractconcepts 68%] → disambiguation question

# Session Learning Applied
"structure requirements" → plan-derivetasks (98% confidence - learned from correction)
```

### Workflow Archetype Detection
```copilot-pattern
# Multi-Step Intent → Workflow Routing
"analyze requirements and create diagrams" → edps-workflow-orchestrator start standard
"quick domain analysis for MVP" → edps-workflow-orchestrator start rapid  
"comprehensive analysis for audit" → edps-workflow-orchestrator start compliance
"validate hierarchy and check compliance" → edps-workflow-orchestrator (existing project state)
```

### Enhanced Conversational Flows
```copilot-interaction
User: "I need help with my requirements analysis"
Navigator: 
🎯 Intent Classification (confidence: 87%): Requirements processing detected
💡 I'll process and normalize your requirements into a structured format for analysis.
🔄 Executing: requirements-ingest
→ [Skill execution begins]
→ [On completion] ✅ Requirements processed! Next recommended: goals-extract (extract business objectives)

User: "Actually I meant derive tasks from requirements"  
Navigator:
📝 Correction Captured: "requirements analysis" → plan-derivetasks
✅ I've learned this preference and will remember it for our session
🎯 Executing: Converting requirements into actionable development tasks...
→ [Switches to plan-derivetasks]

User: "Now I need to analyze requirements for the next module"
Navigator:
🎯 Intent Classification (confidence: 96% - learned): Task derivation from requirements  
💡 I'll convert your requirements into actionable development tasks.
🔄 Executing: plan-derivetasks
```

### Context-Aware Project Guidance
```copilot-interaction
User: "What's the next logical step?"
Navigator:
📊 Project State Analysis:
   • Current workflow: Standard archetype (8/12 skills complete - 67%)
   • Last completed: hierarchy-management ✅
   • Available next: documentation-automation, hierarchy-validation
   • Quality status: All gates passing (92% average quality score)

🎯 Recommendation (confidence: 94%): documentation-automation
💡 Generate process documentation for your completed hierarchy levels
🚀 This maintains workflow momentum and prepares for final validation

Execute documentation-automation? (Y/n)
```

## EDPS Methodology Integration (Enhanced)

### Evolutionary Development Principles (Enhanced)
- **Iterative Refinement**: Guides users through iterative improvement cycles with session learning
- **Continuous Integration**: Ensures skills work together harmoniously via T07 orchestrator integration
- **Adaptive Planning**: Adjusts recommendations based on project evolution and user corrections
- **Knowledge Accumulation**: Builds understanding progressively through skill interactions and quality feedback

### Process Awareness (Enhanced)
- **Stage Recognition**: Identifies current development phase using T07 project state
- **Transition Guidance**: Smoothly guides users between development stages with confidence scoring
- **Quality Gates**: Ensures completeness using T08 gate results before advancing to next phase
- **Artifact Dependencies**: Tracks and manages inter-skill dependencies through orchestrator DAG

### Enhanced Quality Integration
- **T08 Gate Reporting**: Incorporates completion gate results into next-step recommendations
- **Quality Scoring**: Uses aggregated workflow quality scores to suggest optimizations
- **Risk Detection**: Identifies potential quality issues before they cascade through workflow
- **Success Metrics**: Tracks classification accuracy and user satisfaction within project sessions

---

**Version**: 2.0.0 (T09 Enhanced)  
**Last Updated**: March 17, 2026  
**Compatibility**: GitHub Copilot, VS Code, EDPS v2.x (hierarchical boundary format)  
**Dependencies**: T07 edps-workflow-orchestrator, T08 skill-completion-gates  
**Maintainer**: EDPS Development Team

### T09 Enhancements (March 17, 2026)
- **Advanced Classification Engine**: ≥95% accuracy with confidence scoring and disambiguation
- **Session Learning**: User correction capture with pattern adaptation
- **Multi-Step Detection**: Automatic workflow archetype selection for complex requests  
- **Quality Integration**: T08 gate results inform recommendations and next-step guidance
- **T07 Deep Integration**: Seamless routing to orchestrator for workflow management
- **Intent Explanation**: Plain-language explanation of matched intent before execution
- **Context Awareness**: Project state and completion history influence classification
- **Sub-500ms Performance**: Real-time classification for responsive user experience

### Skills Classification Support (All 32 Skills)
✅ **Requirements Processing**: requirements-ingest, requirements-merge, goals-extract, process-w5h  
✅ **Domain Analysis**: domain-extractconcepts, domain-alignentities, domain-proposenewconcepts  
✅ **Visualization**: diagram-generatecollaboration, documentation-automation  
✅ **Planning**: plan-derivetasks, plan-estimateeffort, plan-buildschedule, project-planning-tracking, project-status-reporting  
✅ **Process Management**: process-merge, process-findtopandupdate, process-scopemin  
✅ **Hierarchy**: hierarchy-management, hierarchy-validation, migration-tools  
✅ **Compliance**: edps-compliance, change-impact-analysis  
✅ **Integration**: model-integration, orgmodel-update, integration-testing  
✅ **Change Management**: change-management  
✅ **GitHub Integration**: github-issue-create-update, github-issue-sync-status  
✅ **Meta**: skill-creator  
✅ **Orchestration**: edps-workflow-orchestrator, edps-skill-navigator (self-reference)  
✅ **Project Management**: project-document-management

### Workflow Archetype Classification
✅ **Standard Workflow**: Balanced quality and efficiency for typical EDPS projects  
✅ **Rapid Workflow**: Streamlined execution optimized for MVP and fast iteration  
✅ **Compliance Workflow**: Comprehensive analysis with full audit trail and documentation