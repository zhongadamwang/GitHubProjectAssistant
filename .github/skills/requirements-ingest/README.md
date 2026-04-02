# Requirements Ingest Skill

A comprehensive skill for normalizing requirements from multiple file formats into structured, atomic chunks with classification and traceability. **Now featuring AI-powered processing for superior accuracy.**

## Quick Start

1. **GitHub Copilot Integration (Recommended - No Setup!):**
   ```
   @workspace Use requirements-ingest skill to process this document:
   [PASTE REQUIREMENTS HERE]
   
   Project ID: MY-PROJECT
   Return structured JSON with atomic requirements and classifications.
   ```

2. **Traditional Script with File Output:**
   ```bash
   # Install dependencies
   pip install -r requirements.txt
   
   # Process files (saves to outputs/projects/MY-PROJECT/)
   python src/requirements_ingest.py MY-PROJECT requirements.pdf specs.docx
   
   # Custom output directory
   python src/requirements_ingest.py MY-PROJECT specs.md --output-dir /custom/path
   
   # Console output only (legacy mode)
   python src/requirements_ingest.py MY-PROJECT specs.md --no-save
   ```

3. **Python Integration:**
   ```python
   from src.requirements_ingest import RequirementsIngestor
   
   ingestor = RequirementsIngestor()
   result = ingestor.process_files([\"requirements.pdf\"], \"MY-PROJECT\")
   # Creates: outputs/projects/MY-PROJECT/requirements.json
   ```

## Features

### 🤖 GitHub Copilot Integration (New!)
- **No API Keys Required**: Uses Copilot's built-in AI capabilities
- **Context Aware**: Understands your workspace and project context
- **Interactive Processing**: Refine results through conversation
- **Integrated Workflow**: Works seamlessly within VS Code
- **Cost Effective**: No additional API costs beyond Copilot subscription

### 📁 Dual Format Output
- **Primary Output**: `outputs/projects/{project_id}/Analysis/requirements.md` (Markdown for downstream skills)
- **Secondary Output**: `outputs/projects/{project_id}/Analysis/requirements.json` (JSON for machine processing)
- **Processing Log**: Audit trail with metadata and statistics within Analysis folder
- **Glossary**: Enhanced domain term extraction with context
- **Versioning**: Automatic backup of previous outputs
- **Source Mapping**: Track original file references and integrity
- **Organizational Alignment**: Analysis folder structure matches project organizational model
- **Atomic Chunking**: Breaks requirements into verifiable units (400-600 tokens, optimized for modern LLMs)
- **Rule-based Classification**: Tags as functional/nonfunctional/constraint/assumption/out-of-scope
- **Traceability**: Preserves source file and location references
- **Batch Processing**: High-volume processing for consistent formats
- **JSON Output**: Suitable for downstream processing and tool integration

## Output Format

**Primary Format (Markdown)** - Following original specification for downstream skills:

```markdown
# Requirements Analysis Report

**Project**: PROJECT-001
**Source**: requirements.pdf
**Generated**: 2026-02-08T14:30:00Z
**Total Requirements**: 5

## Requirements

| ID | Section | Text | Tags | Confidence |
|----|---------|------|------|------------|
| R-001 | Authentication | System shall authenticate users within 3 seconds | functional, performance | high |
| R-002 | Performance | API response times should not exceed 200ms | nonfunctional | high |

## Glossary Suspects
- OAuth2
- API
- PCI DSS
```

**Secondary Format (JSON)** - For machine processing:

```json
{
  "project_id": "string",
  "requirements": [
    {
      "id": "R-001",
      "source_file": "requirements.pdf",
      "location_hint": "page 3, para 2", 
      "text": "System shall authenticate users within 2 seconds",
      "tags": ["functional", "nonfunctional"],
      "confidence": 0.95
    }
  ],
  "glossary_suspects": ["authentication", "API", "system"]
}
```

## File Structure

```
requirements-ingest/
├── SKILL.md                    # Skill definition with Copilot integration guide
├── LICENSE                     # MIT license
├── README.md                   # This file
├── requirements.txt            # Python dependencies (minimal)
├── setup.py                    # Setup script
├── src/
│   └── requirements_ingest.py  # Traditional script for batch processing
├── examples/
│   └── copilot_integration.md  # Copilot usage examples (primary method)
├── test_data/                  # Sample requirement documents
└── test_skill.py               # Comprehensive test suite
```

## Dependencies

### For GitHub Copilot Integration (Primary)
- **No dependencies required!** Works with your existing Copilot subscription

### For Traditional Script Processing  
- `pdfplumber` - PDF text extraction
- `python-docx` - DOCX processing  
- `PyPDF2` - Alternative PDF parser

Install with: `pip install -r requirements.txt`

## Testing

### Comprehensive Testing  
```bash
# Full test suite with Copilot guidance and traditional script validation
python test_skill.py
```

### Test with Sample Data
```bash
# Traditional processing with provided samples (creates dual output in Analysis folder)
python src/requirements_ingest.py SAMPLE-001 test_data/*.md test_data/*.txt

# Check outputs
ls outputs/projects/SAMPLE-001/Analysis/
# Shows: requirements.md, requirements.json, processing_log.json, glossary.json
```

### For Downstream Skills
```python
# Standard way to access requirements from another skill (Markdown primary)
def load_requirements_markdown(project_id: str) -> str:
    """Load processed requirements in Markdown format (recommended for downstream skills)"""
    with open(f"outputs/projects/{project_id}/Analysis/requirements.md") as f:
        return f.read()

# Alternative: JSON access for machine processing
def load_requirements_json(project_id: str) -> dict:
    """Load processed requirements in JSON format (for machine processing)"""
    with open(f"outputs/projects/{project_id}/Analysis/requirements.json") as f:
        return json.load(f)

# Usage examples
markdown_content = load_requirements_markdown("MY-PROJECT")
# Process Markdown table for downstream analysis

json_data = load_requirements_json("MY-PROJECT")
# Access: json_data["requirements"], json_data["glossary_suspects"]
```

# Copilot integration (paste content into Copilot Chat)
# See examples/copilot_integration.md for detailed examples
```

### Manual Testing
```python
# Test traditional approach
from src.requirements_ingest import RequirementsIngestor
ingestor = RequirementsIngestor() 
result = ingestor.process_files(["your_file.pdf"], "TEST-001")

# Test Copilot approach
# Use Copilot Chat: "@workspace Use requirements-ingest skill to process this document:"
```

## Classification Rules

- **Functional**: System behavior, user actions, features
- **Nonfunctional**: Performance, security, usability
- **Constraint**: Budget, technology limits, regulations
- **Assumption**: Dependencies, prerequisites, environment
- **Out-of-scope**: Explicitly excluded items

## Quality Assurance

- Chunks are atomic (one requirement each)
- Maximum 200 tokens per chunk
- Location hints specify source context
- Confidence scores indicate text clarity
- Glossary terms appear 2+ times in text

## License

MIT License - see [LICENSE](LICENSE) file for details.