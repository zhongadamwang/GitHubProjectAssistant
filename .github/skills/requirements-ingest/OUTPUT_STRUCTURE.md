# Requirements Ingest - Output Structure & Guidelines

## Folder Structure

```
outputs/
├── projects/                           # Project-based organization
│   ├── {project_id}/                   # Individual project folder  
│   │   └── Analysis/                   # 🎯 Analysis artifacts (aligned with org structure)
│   │       ├── requirements.md        # PRIMARY: Markdown output for downstream skills
│   │       ├── requirements.json      # SECONDARY: JSON output for machine processing
│   │       ├── processing_log.json    # Processing metadata & audit trail
│   │       ├── glossary.json         # Extracted domain terms
│   │       ├── source_files/         # Source file references & copies
│   │       │   ├── file_mapping.json # Source file tracking
│   │       │   └── originals/        # Optional: source file copies
│   │       └── versions/             # Version history (both .md and .json)
│   │           ├── v1_2026-02-08_14-30.md
│   │           ├── v1_2026-02-08_14-30.json 
│   │           └── v2_2026-02-08_15-45.md
│   └── another_project_id/
│       └── Analysis/
├── templates/                         # Standard templates
│   ├── requirements_schema.json      # JSON schema validation
│   └── processing_template.json      # Processing log template
└── README.md                         # Output folder documentation
```

## File Specifications

### 1. `requirements.md` - Primary Output (Markdown)
**Purpose**: Main file for downstream skill consumption (follows original AI Agent Skillpack specification)
**Format**: Markdown table with structured requirements  
```markdown
# Requirements Analysis Report

**Project**: PROJECT-001
**Source**: requirements.pdf
**Generated**: 2026-02-08T14:30:00Z
**Total Requirements**: 5

## Requirements

| ID | Section | Text | Tags | Confidence |
|----|---------|------|------|-----------|
| R-001 | Authentication | System shall authenticate users within 3 seconds | functional, performance | high |
| R-002 | Performance | API response times should not exceed 200ms | nonfunctional | high |

## Glossary Suspects
- OAuth2
- API
- PCI DSS

## Processing Summary
- **Files processed**: 3
- **Requirements extracted**: 5
- **Average confidence**: 0.87
- **Processing time**: 2.5s
```

### 2. `requirements.json` - Secondary Output (JSON)
**Purpose**: Machine processing and internal analysis
**Schema**: Structured JSON with detailed metadata
```json
{
  "project_id": "PROJECT-001",
  "generated_at": "2026-02-08T14:30:00Z",
  "version": "1.0",
  "total_requirements": 25,
  "requirements": [
    {
      "id": "R-001",
      "source_file": "requirements.pdf",
      "location_hint": "page 3, para 2",
      "text": "The system shall authenticate users within 2 seconds",
      "tags": ["functional", "performance"],
      "confidence": 0.95
    }
  ],
  "glossary_suspects": ["authentication", "API", "system"],
  "processing_summary": {
    "files_processed": 3,
    "requirements_extracted": 25,
    "avg_confidence": 0.87,
    "processing_time_seconds": 2.5
  }
}
```

### 2. `processing_log.json` - Audit Trail
**Purpose**: Processing metadata, errors, and audit information
```json
{
  "project_id": "PROJECT-001",
  "processing_session": {
    "timestamp": "2026-02-08T14:30:00Z",
    "version": "1.0",
    "tool_version": "requirements-ingest-v2.1",
    "user": "adam.wang",
    "method": "copilot" // or "traditional_script"
  },
  "input_files": [
    {
      "file_path": "spec.pdf",
      "file_size": 1024000,
      "file_hash": "sha256:abc123...",
      "processed_successfully": true,
      "requirements_extracted": 12
    }
  ],
  "processing_stats": {
    "total_files": 3,
    "successful_files": 3,
    "failed_files": 0,
    "total_requirements": 25,
    "avg_confidence": 0.87,
    "processing_time_seconds": 2.5
  },
  "errors": [],
  "warnings": [
    "Low confidence requirement R-015 (0.45)"
  ]
}
```

### 3. `glossary.json` - Domain Terms
**Purpose**: Extracted glossary for domain modeling
```json
{
  "project_id": "PROJECT-001",
  "extracted_terms": [
    {
      "term": "authentication",
      "frequency": 8,
      "contexts": ["user authentication", "API authentication"],
      "confidence": 0.92
    }
  ],
  "suggested_definitions": [
    {
      "term": "OAuth2",
      "suggested_definition": "Industry standard authorization protocol",
      "sources": ["R-001", "R-007"]
    }
  ]
}
```

## File Naming Conventions

### Project IDs
- **Format**: `PROJ-YYYYMMDD-NNN` or custom identifiers
- **Examples**: `PROJ-20260208-001`, `ECOM-PHASE1`, `USER-AUTH-REQ`

### Versioning
- **Format**: `v{major}_{YYYY-MM-DD}_{HH-mm}`
- **Examples**: `v1_2026-02-08_14-30.json`

### File Names
- **Requirements**: `requirements.json` (fixed name for downstream tools)
- **Logs**: `processing_log.json`
- **Glossary**: `glossary.json`
- **Versions**: `v{version}_{timestamp}.json`

## Downstream Integration Guidelines

### For Skills Consuming Output (Priority Order)
1. **Primary Path (Recommended)**: `outputs/projects/{project_id}/Analysis/requirements.md` - Markdown format matching original specification
2. **Secondary Path**: `outputs/projects/{project_id}/Analysis/requirements.json` - JSON for machine processing
3. **Metadata Access**: `outputs/projects/{project_id}/Analysis/processing_log.json` for warnings/errors and audit trail
4. **Glossary Access**: `outputs/projects/{project_id}/Analysis/glossary.json` for domain terms

### File Access Patterns
```python
# RECOMMENDED: Load Markdown output (follows original specification)
def load_requirements_markdown(project_id: str) -> str:
    \"\"\"Primary method for downstream skills\"\"\"
    base_path = f"outputs/projects/{project_id}"
    
    with open(f"{base_path}/requirements.md", 'r') as f:
        return f.read()

# ALTERNATIVE: Load JSON output (for machine processing)
def load_requirements_json(project_id: str) -> dict:
    \"\"\"Secondary method for machine processing\"\"\"
    base_path = f"outputs/projects/{project_id}"
    
    with open(f"{base_path}/requirements.json") as f:
        return json.load(f)

# OPTIONAL: Check processing status
def check_processing_log(project_id: str) -> dict:
    base_path = f"outputs/projects/{project_id}"
    
    with open(f"{base_path}/processing_log.json") as f:
        return json.load(f)
```

## Directory Management

### Auto-Creation
- Create `outputs/projects/{project_id}/` if not exists
- Create subdirectories as needed
- Handle permissions appropriately

### Backup Strategy
- Previous versions moved to `versions/` folder
- Keep last 5 versions by default
- Option to disable versioning for space

### Cleanup Policy
- Archive projects older than 90 days (configurable)
- Compress archived projects
- Clean empty directories

## Configuration Options

### Output Location
- **Default**: `./outputs/` (relative to execution directory)
- **Environment Variable**: `REQUIREMENTS_OUTPUT_DIR`
- **Config File**: `config.json` in skill directory

### Processing Options
- **Versioning**: enabled/disabled
- **Source File Copying**: enabled/disabled
- **Compression**: enabled for archives
- **Validation**: JSON schema validation on/off