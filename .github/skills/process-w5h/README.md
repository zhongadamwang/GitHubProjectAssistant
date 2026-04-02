# Process W5H Analysis Skill

A comprehensive requirements analysis skill that uses the Who, What, When, Where, Why, How framework to provide multi-dimensional analysis of project requirements.

## Overview

The Process W5H skill transforms requirements documents into structured analyses that examine:
- **WHO**: Stakeholders, roles, and responsibilities  
- **WHAT**: Functional and non-functional requirements
- **WHEN**: Timeline, milestones, and dependencies
- **WHERE**: Context, environment, and integration points
- **WHY**: Business drivers, success metrics, and rationale
- **HOW**: Implementation approach, methodology, and risk mitigation

## Quick Start

### With GitHub Copilot (Recommended)

```markdown
@workspace Use the process-w5h skill to analyze this document:

[PASTE YOUR REQUIREMENTS DOCUMENT HERE]

Project ID: MY-PROJECT-001

Perform comprehensive W5H analysis extracting stakeholders, requirements, timeline, context, business rationale, and implementation approach.
```

### Command Line Usage

```bash
# Analyze requirements document
python process_w5h.py PROJECT-001 requirements.md

# Output files created:
#   outputs/projects/PROJECT-001/Analysis/w5h-analysis.md
#   outputs/projects/PROJECT-001/Analysis/w5h-analysis.json
```

## Example Output

The skill generates a comprehensive W5H analysis report with sections for each dimension:

- **WHO Section**: Stakeholder mapping with roles, responsibilities, and authority levels
- **WHAT Section**: Requirements categorization with functional/non-functional breakdown
- **WHEN Section**: Timeline analysis with phases, milestones, and dependencies  
- **WHERE Section**: Environmental context and integration points
- **WHY Section**: Business drivers, success metrics, and value proposition
- **HOW Section**: Implementation strategy with methodology and risk management

## Integration

This skill works as part of the broader skills framework:

- **Input**: Raw requirements (from requirements-ingest skill)
- **Output**: W5H analysis (feeds into goals-extract and planning skills)
- **Format**: Markdown primary, JSON secondary for machine processing

## Benefits

- ✅ **Comprehensive**: Ensures all critical dimensions are analyzed
- ✅ **Structured**: Consistent format for downstream processing
- ✅ **Actionable**: Provides concrete insights for implementation
- ✅ **Traceable**: Maintains links to source requirements
- ✅ **Stakeholder-Focused**: Clear identification of who is involved

## File Structure

```
process-w5h/
├── SKILL.md              # Main skill definition and documentation
├── README.md             # This file
├── requirements.txt      # Python dependencies
├── examples/             # Sample W5H analyses
│   ├── basic-example.md
│   └── complex-example.md
└── test_data/           # Sample requirements for testing
    ├── simple-requirements.md
    └── complex-requirements.md
```

## See Also

- [SKILL.md](SKILL.md) - Complete skill specification
- [examples/](examples/) - Sample W5H analyses
- [test_data/](test_data/) - Sample input requirements