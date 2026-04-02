---
name: requirements-ingest
description: Normalize requirements from any format into consistent, chunked representation with traceability and classification.
license: MIT
---

# Requirements Ingest

## Intent

Transforms requirements documents (PDF/DOCX/Markdown/Email) into structured, atomic chunks with classification and traceability.

## Inputs

- **Sources**: Raw requirement files (PDF, DOCX, Markdown, Email)
- **Parameters**: `project_id`

## Outputs

**Files Generated:**
- `outputs/projects/{project_id}/Analysis/requirements.md` — Markdown format for downstream skills
- `outputs/projects/{project_id}/Analysis/requirements.json` — JSON format for machine processing
- Auto-created project directory with Analysis subfolder, processing logs, and glossary files

## Core Function

## Usage

**GitHub Copilot Integration (Recommended):**
```markdown
Use this skill directly in Copilot by providing requirements documents.
Copilot will automatically extract and classify requirements using its built-in AI.

Example prompt:
"Use requirements-ingest skill to process this requirements document and return structured JSON with atomic requirements, classifications, and traceability."
```

**Traditional Script Approach:**
```python
from requirements_ingest import RequirementsIngestor

ingestor = RequirementsIngestor()
result = ingestor.process_files(["requirements.pdf"], "MY-PROJECT")
# Creates: 
#   outputs/projects/MY-PROJECT/requirements.md (primary)
#   outputs/projects/MY-PROJECT/requirements.json (secondary)
```

**Command Line:**
```bash
python requirements_ingest.py PROJECT-001 requirements.pdf specs.docx
# Saves to: outputs/projects/PROJECT-001/
#   📄 requirements.md (Markdown - for downstream skills)
#   📋 requirements.json (JSON - for machine processing)
```

## Output Schema

**Primary Format (Markdown)** - Following Original Specification:

```markdown
# Requirements Analysis Report

**Project**: PROJECT-001
**Source**: requirements.pdf
**Generated**: 2026-02-08T14:30:00Z
**Total Requirements**: 5

## Requirements

| ID | Section | Text | Tags | Confidence |
|----|---------|------|---------|------------|
| R-001 | Authentication | System shall authenticate users within 3 seconds | functional, performance | high |
| R-002 | Performance | API response times should not exceed 200ms | nonfunctional | high |

## Glossary Suspects
- OAuth2
- API
- PCI DSS
```

**Secondary Format (JSON)** - For Machine Processing:

```json
{
  "project_id": "PROJECT-001",
  "generated_at": "2026-02-08T14:30:00Z",
  "total_requirements": 5,
  "requirements": [
    {
      "id": "R-001",
      "source_file": "requirements.pdf",
      "location_hint": "page 3, para 2",
      "text": "System shall authenticate users within 3 seconds",
      "tags": ["functional", "performance"],
      "confidence": 0.95
    }
  ],
  "glossary_suspects": ["OAuth2", "API", "PCI DSS"]
}
```

## GitHub Copilot Integration

### Direct Usage in Copilot Chat
Simply paste your requirements document and ask:

```
@workspace Use the requirements-ingest skill to process this document:

[PASTE YOUR REQUIREMENTS DOCUMENT HERE]

Project ID: MY-PROJECT-001

Extract atomic requirements with:
- Unique IDs (R-XXX format)  
- Source traceability
- Classification tags
- Confidence scores
- Glossary terms

Return structured JSON following the schema.
```

### Copilot Prompt Template
```
Analyze requirements document using requirements-ingest methodology:

1. EXTRACT: Break into atomic requirements (max 400-600 tokens each, optimized for modern LLMs)
2. CLASSIFY: Tag as functional|nonfunctional|constraint|assumption|out-of-scope  
3. TRACE: Preserve source location (section, page, paragraph)
4. SCORE: Confidence 0.0-1.0 based on clarity
5. GLOSSARY: Identify domain terms (2+ occurrences)

Output exact JSON schema with project_id, requirements array, glossary_suspects.
```

**Advantages of Copilot Integration:**
- ✅ **No API Keys Required**: Uses Copilot's built-in AI capabilities
- ✅ **Context Aware**: Understands your workspace and project context  
- ✅ **Interactive**: Can ask follow-up questions and refine results
- ✅ **Integrated Workflow**: Works seamlessly with your development process

## AI Classification Prompt

```
Classify each requirement using these tags:

- **functional**: What the system does (features, user actions, behaviors)
- **nonfunctional**: How well it does it (performance, security, usability)
- **constraint**: External limitations (budget, technology, regulations)
- **assumption**: Dependencies and prerequisites
- **out-of-scope**: Explicitly excluded items

Multiple tags allowed. Explain reasoning for complex cases.
```

## Processing Rules

1. **Chunk Size**: Max 400-600 tokens per requirement (optimized for modern LLMs like Claude/GPT-4)
2. **Atomic**: One verifiable requirement per chunk (priority over token limits)
3. **Traceability**: Preserve source file + location hint
4. **Confidence**: 0.0-1.0 based on clarity and context
5. **Dual Output**: Markdown (primary for downstream) + JSON (machine processing)
6. **File Organization**: Auto-created project directories with requirements.md, requirements.json, processing_log.json, and glossary.json
7. **Versioning**: Previous outputs backed up to versions/ subfolder
8. **Downstream Integration**: Use requirements.md for compatibility with original specification