# Copilot Integration Patterns

## Natural Language to Skill Mapping

### Intent Recognition Framework

The EDPS Skill Navigator uses pattern matching and context analysis to interpret natural language requests and map them to appropriate skill invocations.

#### Primary Intent Categories

1. **Initiation Intents** - Starting new work
2. **Analysis Intents** - Understanding existing work  
3. **Creation Intents** - Generating new artifacts
4. **Management Intents** - Tracking and organizing work
5. **Integration Intents** - Combining multiple sources
6. **Navigation Intents** - Finding and accessing information

### Copilot Conversation Patterns

#### Pattern 1: Progressive Discovery
```
User: "I have some requirements I need to work with"

Navigator Response:
"I'll help you get started with your requirements! Let me first analyze what you have and set up the right structure.

First, I'll create a proper project structure for your requirements work..."

[Auto-invokes: project-document-management]

"Great! Now I can process your requirements. Please share your requirements document, and I'll normalize it into our standard format..."

[Awaits user input, then auto-invokes: requirements-ingest]

"Perfect! I've processed your requirements and found [X] key requirements. Would you like me to:
1. Extract the business goals and success criteria
2. Perform a comprehensive analysis (Who, What, When, Where, Why, How)
3. Start modeling the domain concepts
4. All of the above (recommended for thorough analysis)

What would be most helpful for your project?"
```

#### Pattern 2: Context-Aware Recommendations
```
User: "What should I do next?"

Navigator Analysis:
- Scan current project structure
- Identify completed artifacts
- Determine current stage
- Recommend next logical steps

Navigator Response:
"Based on your current project state, I can see you've completed:
✅ Requirements processing (47 requirements identified)
✅ Goals extraction (5 business goals, 12 success criteria)
⏳ Domain analysis (in progress - 23 concepts identified)

I recommend completing your domain analysis next. I can:
1. **Align your domain concepts** with organizational standards
2. **Propose new concepts** to fill any gaps I identify  
3. **Create collaboration diagrams** to visualize your system interactions

Which would you like to tackle first? I'd suggest starting with domain alignment since you have a good foundation of concepts already identified."
```

#### Pattern 3: Error Handling and Recovery
```
User: "Create collaboration diagrams for my project"

Navigator Analysis:
- Check for required prerequisites
- Domain concepts missing → Need domain analysis first
- Requirements exist → Can start domain pipeline

Navigator Response:
"I'd love to help create collaboration diagrams! However, I need to analyze your domain concepts first to create accurate diagrams.

I can see you have processed requirements, so let me quickly:
1. **Extract domain concepts** from your requirements (2-3 minutes)
2. **Align concepts** with any existing standards (1-2 minutes)  
3. **Generate collaboration diagrams** showing system interactions (2-3 minutes)

This will give you much better diagrams than if I tried to create them directly from requirements. Should I proceed with this approach?"

[If user agrees, auto-invokes: domain-extractconcepts → domain-alignentities → diagram-generatecollaboration]
```

### Natural Language Processing Rules

#### Action Verbs → Skill Mapping
```
"analyze" → Multiple skills based on object:
  - "analyze requirements" → requirements-ingest + goals-extract + process-w5h
  - "analyze domain" → domain-extractconcepts + domain-alignentities
  - "analyze scope" → process-scopemin

"create" → Creation skills:
  - "create diagrams" → diagram-generatecollaboration (+ prerequisites)
  - "create project" → project-document-management
  - "create plan" → project-planning-tracking

"extract" → Extraction skills:
  - "extract goals" → goals-extract
  - "extract concepts" → domain-extractconcepts
  - "extract requirements" → requirements-ingest

"manage" → Management skills:
  - "manage changes" → change-management
  - "manage project" → project-planning-tracking
  - "manage documents" → project-document-management

"merge" → Integration skills:
  - "merge requirements" → process-merge
  - "merge sources" → process-merge

"track" → Tracking skills:
  - "track progress" → project-status-reporting
  - "track changes" → change-management
```

#### Object Nouns → Context Identification
```
"requirements" → Requirements processing pipeline
"domain" → Domain modeling pipeline  
"goals" → Goals extraction and analysis
"scope" → Scope analysis and minimization
"project" → Project management activities
"changes" → Change management workflows
"diagrams" → Visualization and documentation
"plan" → Project planning and tracking
```

#### Qualifier Adjectives → Workflow Modification
```
"quick" / "fast" / "minimal" → Quick Start pattern (minimum viable analysis)
"thorough" / "comprehensive" / "complete" → Full analysis patterns
"new" → Initiation patterns
"existing" / "current" → Update/modification patterns
```

### Copilot Integration Best Practices

#### 1. Seamless Handoff
```python
# Navigator should never break Copilot conversation flow
# Instead of: "I'm now invoking the requirements-ingest skill..."
# Say: "Let me process these requirements for you..."

# Good pattern:
user_input = "analyze my requirements document"
navigator_response = """I'll analyze your requirements document right away. 

*Processing your requirements...*

I've identified 23 key requirements across 4 main functional areas:
1. User Management (8 requirements)
2. Content Processing (7 requirements)  
3. Integration APIs (5 requirements)
4. Reporting & Analytics (3 requirements)

Would you like me to extract the business goals next, or dive deeper into the domain concepts I've identified?"""
```

#### 2. Progressive Disclosure
```python
# Start simple, offer complexity as options
# Don't overwhelm with all possibilities

# Good pattern:
initial_response = """I've processed your requirements! Here's what I found:
- 23 requirements identified
- 4 main functional areas
- 5 key stakeholder types

**Quick next steps:**
1. Extract business goals (2 minutes)
2. Analyze domain concepts (5 minutes)

**Or go comprehensive:**
- Full requirements analysis including goals, domain modeling, and collaboration diagrams (15 minutes)

What would be most helpful?"""
```

#### 3. Context Preservation
```python
# Remember conversation context across skill invocations
# Build on previous interactions

conversation_memory = {
    "user_preferences": ["thorough analysis", "visual diagrams"],
    "project_context": "e-commerce platform",
    "completed_skills": ["requirements-ingest", "goals-extract"],
    "current_focus": "domain_modeling"
}

# Use context to personalize responses
contextualized_response = f"""Based on your preference for thorough analysis and visual diagrams, I recommend we complete your domain modeling with collaboration diagrams.

Since you're building an {conversation_memory['project_context']}, I'll pay special attention to:
- Customer interaction patterns
- Payment processing workflows  
- Inventory management concepts

This will set you up perfectly for the visual diagrams you mentioned wanting."""
```

### Interactive Patterns

#### 1. Guided Discovery
```
Navigator: "I notice this is an e-commerce project. Let me tailor my analysis..."

[Invokes domain-extractconcepts with e-commerce context]

Navigator: "I've identified several e-commerce-specific concepts like 'Shopping Cart', 'Product Catalog', and 'Order Processing'. 

I also notice you have some concepts that might align with standard e-commerce patterns. Should I check these against common e-commerce domain models to ensure consistency?"

[If yes, invokes domain-alignentities]

Navigator: "Great alignment! I found matches with standard patterns for 80% of your concepts. I did identify 3 concepts that seem unique to your business model. 

Would you like me to explore whether these represent genuine innovations or if they could be aligned with existing patterns?"
```

#### 2. Choice Architecture
```
# Provide clear, actionable choices rather than open-ended questions

# Poor pattern:
"What would you like to do next with your requirements?"

# Good pattern:  
"I can help you move forward in a few ways:

**Understand your domain better:**
🔍 Analyze domain concepts (5 min) → Identify key business entities
🔗 Align with standards (3 min) → Ensure consistency with existing models

**Plan your project:**
🎯 Define minimum scope (7 min) → Identify MVP features
📋 Create project plan (10 min) → Timeline and milestones

**Create documentation:**
📊 Generate diagrams (8 min) → Visual system overview
📄 Status report (3 min) → Current progress summary

Which direction interests you most?"
```

#### 3. Adaptive Conversation
```python
# Adjust communication style based on user responses

if user_shows_expertise:
    navigator_style = "technical, detailed, options-rich"
elif user_shows_time_pressure:
    navigator_style = "efficient, defaults, quick wins"  
elif user_shows_uncertainty:
    navigator_style = "guided, educational, step-by-step"

# Example adaptation:
technical_user_response = """I'll run domain-extractconcepts with advanced entity recognition, then pipe the output through domain-alignentities using your organizational ontology. 

Based on the alignment results, I can invoke domain-proposenewconcepts if we identify coverage gaps, or proceed directly to diagram-generatecollaboration for sequence and collaboration views.

Preference on the analysis depth parameters?"""

novice_user_response = """I'll help you understand what concepts and ideas are in your requirements, then make sure they align with how your organization typically thinks about these things.

This will help ensure your project fits well with existing systems and processes. It usually takes about 10 minutes and gives you a much clearer picture of what you're building.

Sound good?"""
```

### Error Handling Patterns

#### 1. Graceful Degradation
```
# When skill fails, provide alternatives rather than error messages

# Poor pattern:
"Error: domain-extractconcepts failed due to insufficient context"

# Good pattern:
"I had trouble automatically extracting domain concepts from your requirements. This sometimes happens with highly technical or domain-specific language.

Let me try a different approach:
1. **Manual guidance**: I can walk you through identifying key concepts step by step
2. **Simplified extraction**: Focus on the clearest, most obvious concepts first  
3. **Alternative source**: If you have other documents (diagrams, glossaries), I can work with those

Which approach would work best for you?"
```

#### 2. Learning from Failures
```python
# Track what doesn't work and improve recommendations

failure_pattern = {
    "skill": "domain-extractconcepts",
    "context": "highly_technical_requirements",  
    "failure_reason": "insufficient_business_context",
    "successful_alternative": "manual_guided_extraction"
}

# Update future recommendations
updated_pattern = """For technical requirements like these, I find it works better to start with a guided approach rather than fully automated extraction.

Let me walk you through identifying the key business concepts first, then I can run the automated analysis to catch anything we missed."""
```

### Performance and Efficiency

#### 1. Parallel Opportunities
```python
# Identify skills that can run simultaneously
parallel_skills = {
    "after_requirements_ingest": [
        "goals-extract",  # Can run immediately 
        "process-w5h"     # Can start with requirements
    ],
    "after_domain_concepts": [
        "domain-alignentities",   # Works with concepts
        "domain-proposenewconcepts"  # Builds on concepts  
    ]
}

# Execute in parallel when possible
navigator_message = """I'll work on extracting your business goals while simultaneously analyzing the comprehensive context (who, what, when, where, why, how) of your requirements.

This parallel approach will save time while ensuring we capture all the important aspects of your project."""
```

#### 2. Smart Defaults
```python
# Use intelligent defaults to reduce user decision fatigue

smart_defaults = {
    "new_project": "complete_initiation_pattern",
    "requirements_only": "analysis_focus_pattern", 
    "domain_questions": "domain_modeling_pattern",
    "planning_needs": "quick_start_pattern"
}

# Apply defaults with gentle confirmation
default_application = """Based on your request to "start a new project," I'll set you up with our complete project initiation workflow. This includes:

✓ Project structure setup
✓ Requirements processing  
✓ Goals extraction
✓ Comprehensive analysis
✓ Domain modeling

This gives you a solid foundation for any project. If you'd prefer a different approach, just let me know!"""
```

---

**Document Version**: 1.0.0
**Last Updated**: 2026-02-17
**Integration Target**: GitHub Copilot in VS Code
**Compatibility**: EDPS v1.x, Copilot API v2.x