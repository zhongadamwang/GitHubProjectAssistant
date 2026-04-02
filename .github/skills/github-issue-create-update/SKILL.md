---
name: github-issue-create-update
description: Self-contained skill that creates and updates GitHub Issues from local task markdown files. Includes embedded authentication, configuration defaults, and complete field mapping with no external dependencies required.
license: MIT
---

# GitHub Issue Create/Update Skill - Self-Contained

## Intent
Self-contained synchronization of local development task files with GitHub Issues for seamless project management. Creates new GitHub Issues from task markdown files and updates existing issues when task files are modified. All configuration, authentication, and functionality is embedded within the skill - no external configuration files or dependencies required.

## Inputs
- **Source**: Local task file path(s) or project directory
- **Format**: Markdown task files following EDPS task structure (title, state, labels, description, acceptance criteria)
- **Authentication**: Environmental variable `GITHUB_TOKEN` or interactive prompt setup
- **Repository**: Environmental variable `GITHUB_REPO` or execution parameter
- **Configuration**: Optional runtime parameters (milestone, assignee, labels) - all defaults embedded

## Outputs
**Operation Results:**
- Issue URLs for created/updated issues
- Operation status (created, updated, skipped, failed)
- Field mapping summary
- Error details for failed operations

**File Updates:**
- Task files updated with GitHub issue numbers for tracking
- Issue ID metadata embedded in task file frontmatter

## Core Functionality

### Task File Parsing
Extracts structured data from markdown task files:

```markdown
# Issue: T1 - GitHub Integration Skill
**State:** ready
**Labels:** feature, github-integration, mvp
**Assignees:** adam.wang
**Priority:** High
**Estimated Effort:** 1.5 days
```

**Parsed Fields:**
- **Title**: Extracted from first heading or "Issue:" line
- **Description**: Content from Description section + Tasks + Acceptance Criteria
- **State**: Maps to GitHub issue state (open/closed)
- **Labels**: Direct mapping to GitHub labels
- **Assignees**: Maps to GitHub usernames
- **Priority**: Converted to priority label

## Dual Integration Approach

The skill supports both GitHub CLI and REST API for maximum reliability and flexibility:

**Method 1: GitHub CLI (Preferred)**
- Simplified authentication via `gh auth login`
- Better error handling and user experience
- Automatic token management
- Native GitHub integration

```bash
# Create issue
gh issue create --repo owner/repo --title "Task Title" --body "Description" --label "feature,priority:high" --assignee "username"

# Update issue
gh issue edit 123 --title "Updated Title" --add-label "completed"

# Close issue
gh issue close 123 --reason completed
```

**Method 2: REST API (Fallback)**
- Direct HTTP API integration
- Custom error handling and retry logic
- Token-based authentication
- Fine-grained control over requests

```http
POST /repos/{owner}/{repo}/issues
PATCH /repos/{owner}/{repo}/issues/{issue_number}
```

### Field Mapping

| Task File Field | GitHub Issue Field | Mapping Logic |
|-----------------|-------------------|---------------|
| Title/Header | title | First heading or "Issue:" line |
| Description + Tasks + Criteria | body | Concatenated markdown content |
| State | state | ready/in-progress → open, completed/closed → closed |
| Labels | labels | Direct mapping, auto-create missing |
| Assignees | assignees | GitHub usernames |
| Priority | labels | High → priority:high, Medium → priority:medium |
| Estimated Effort | labels | effort:1.5-days format |

### Issue Tracking & Updates

**Create vs Update Logic:**
1. Check task file for existing `GitHub Issue:` field
2. If no issue number → Create new issue
3. If issue number exists → Update existing issue
4. Store issue number in task file metadata for future updates

**Metadata Storage:**
```markdown
**GitHub Issue:** #123
**Issue URL:** https://github.com/owner/repo/issues/123
```

## Self-Contained Usage Examples

### Environment Setup (One-time)
```bash
# Set required environment variables
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
export GITHUB_REPO="your-org/your-repo"

# Optional: Set default assignee
export GITHUB_DEFAULT_ASSIGNEE="your-username"
```

### Single Task File Execution
```bash
# Basic execution - uses environment variables
python github_issue_skill.py ./tasks/T1-authentication.md

# Output:
# ✅ Created GitHub Issue #123: T1 - Authentication Feature
# 🔗 https://github.com/your-org/your-repo/issues/123
# 📝 Updated task file with GitHub metadata
```

### Project Directory Batch Processing
```bash
# Process all task files in directory
python github_issue_skill.py ./tasks/

# With custom parameters
python github_issue_skill.py ./tasks/ \
  --milestone="Sprint-1" \
  --assignee="project-lead" \
  --labels="mvp,feature,priority:high"

# Output:  
# Processing 3 task files...
# ✅ T1-auth.md → Created issue #123 [assignee: project-lead]
# ✅ T2-frontend.md → Updated issue #124 [milestone: Sprint-1] 
# ✅ T3-backend.md → Created issue #125 [labels: mvp,feature]
```

### Programmatic Integration
```python
from github_issue_skill import GitHubSkillImplementation

# Initialize with environment variables
skill = GitHubSkillImplementation()

# Or initialize with custom config
config = {
    "repository": "myorg/myproject",
    "token": os.getenv('GITHUB_TOKEN'),
    "default_assignee": "team-lead",
    "milestone": "Q1-2026"
}
skill = GitHubSkillImplementation(config)

# Execute on task files
results = skill.execute([
    "./tasks/T1-authentication.md",
    "./tasks/T2-user-dashboard.md"
])

# Check results
for created in results["created"]:
    print(f"Created: {created['title']} → {created['url']}")

for updated in results["updated"]:
    print(f"Updated: {updated['title']} → {updated['url']}")
```

### Update Workflow
```markdown
// Modify existing task file and re-run skill
Modified: T1-github-integration.md (state: ready → in-progress)
Output: Updated issue #123 - changed state to open, added in-progress label
```

### Implementation Methods

**GitHub CLI Implementation (Primary)**
```python
class GitHubCLIClient:
    def create_issue(self, repo, title, body, labels=None, assignees=None):
        cmd = ['gh', 'issue', 'create', '--repo', repo, '--title', title, '--body', body]
        if labels:
            cmd.extend(['--label', ','.join(labels)])
        if assignees:
            for assignee in assignees:
                cmd.extend(['--assignee', assignee])
        result = subprocess.run(cmd, capture_output=True, text=True)
        return self._parse_issue_url(result.stdout)
    
    def update_issue(self, repo, issue_number, **updates):
        cmd = ['gh', 'issue', 'edit', str(issue_number), '--repo', repo]
        for field, value in updates.items():
            if field == 'state' and value == 'closed':
                cmd = ['gh', 'issue', 'close', str(issue_number), '--repo', repo]
            elif field == 'title':
                cmd.extend(['--title', value])
            elif field == 'labels':
                cmd.extend(['--add-label', ','.join(value)])
        return subprocess.run(cmd, capture_output=True, text=True)
```

**REST API Implementation (Fallback)**
```python
class GitHubAPIClient:
    def create_issue(self, owner, repo, title, body, labels=None, assignees=None):
        url = f"https://api.github.com/repos/{owner}/{repo}/issues"
        payload = {"title": title, "body": body}
        if labels: payload["labels"] = labels
        if assignees: payload["assignees"] = assignees
        response = requests.post(url, headers=self.headers, json=payload)
        return response.json()
```

### Authentication Priority Order

1. **Local Credentials File** (Preferred - Secure)
   ```bash
   # Skill automatically reads github-credentials.json
   # File is Git-ignored for security
   ```

2. **GitHub CLI** (If available and authenticated)
   ```bash
   gh auth login
   gh auth status
   ```

3. **Environment Variable**
   ```bash
   export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
   ```

4. **Interactive Setup** (First-time users)
   - Prompts for GitHub username and Personal Access Token
   - Creates secure local credentials file
   - Provides step-by-step setup guidance

### Core Components

1. **ConfigurationManager**: Load and merge hierarchical configurations
2. **AuthenticationHandler**: Manage GitHub CLI and token-based authentication
3. **GitHubClientFactory**: Create CLI or REST API clients based on availability
4. **TaskFileParser**: Extract structure and metadata from markdown
5. **FieldMapper**: Convert task fields to GitHub issue fields with config customization
6. **IssueManager**: Handle create vs update logic with both CLI and API
7. **MetadataUpdater**: Update task files with issue numbers and sync status
8. **ErrorHandler**: Robust error handling with automatic fallback between methods

### Error Handling
- **Configuration Errors**: Validation and helpful error messages for invalid configs
- **API Failures**: Retry with exponential backoff based on configuration settings
- **Authentication**: Clear error messages for token or permission issues
- **Rate Limits**: Automatic waiting and progress updates per configuration
- **Missing Fields**: Graceful defaults from configuration and validation warnings
- **File Permissions**: Skip files that cannot be updated with detailed logging

## Quality Criteria
- **Accuracy**: All task file fields correctly mapped to GitHub issue fields
- **Reliability**: Robust handling of API failures and network issues  
- **Idempotency**: Re-running skill produces same results without duplicates
- **Traceability**: Clear mapping between local tasks and GitHub issues
- **Performance**: Batch operations with progress reporting for large projects

## Self-Contained Configuration

### Embedded Authentication Management
The skill includes all authentication methods directly without external dependencies:

**Authentication Methods (Priority Order)**:
1. **Environment Variable** (Recommended for security)
   ```bash
   export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
   ```

2. **Interactive Prompt** (First-time setup)
   ```
   GitHub Integration Setup:
   1. Repository (e.g., owner/repo): _______
   2. Personal Access Token: _______
      Get token: https://github.com/settings/tokens
      Required scopes: repo (or public_repo for public repos)
   3. Default assignee (optional): _______
   ```

3. **Inline Configuration** (For specific executions)
   ```python
   config = {
     "repository": "owner/repo-name",
     "token": "ghp_xxxxxxxxxxxxxxxxxxxx",  # Use environment variable instead
     "default_assignee": "username"
   }
   ```

**Secure Token Usage**: Never hardcode tokens in files. Always use environment variables:
```bash
# Set token in your shell profile
echo 'export GITHUB_TOKEN="your_token_here"' >> ~/.bashrc
source ~/.bashrc
```

### Embedded Default Configuration
The skill includes comprehensive default settings that can be overridden as needed:

```python
DEFAULT_CONFIG = {
    "api": {
        "base_url": "https://api.github.com",
        "timeout": 30,
        "rate_limit_delay": 1,
        "max_retries": 3
    },
    "authentication": {
        "token_env_var": "GITHUB_TOKEN",
        "interactive_setup": True
    },
    "issue_defaults": {
        "auto_create_labels": True,
        "add_github_metadata": True,
        "backup_on_update": False
    },
    "field_mapping": {
        "state_mapping": {
            "ready": "open",
            "in-progress": "open",
            "completed": "closed",
            "cancelled": "closed"
        },
        "priority_labels": {
            "High": {"name": "priority:high", "color": "d73a4a"},
            "Medium": {"name": "priority:medium", "color": "fbca04"},
            "Low": {"name": "priority:low", "color": "0075ca"}
        },
        "effort_label_prefix": "effort:",
        "additional_labels": ["auto-generated"],
        "exclude_labels": ["internal"]
    },
    "batch_processing": {
        "max_concurrent": 5,
        "progress_reporting": True,
        "stop_on_error": False
    }
}
```

**Configuration Override**: Override any defaults at execution time:
```python
custom_config = {
    "issue_defaults": {
        "default_assignee": "project-lead",
        "milestone": "Sprint 1"
    },
    "field_mapping": {
        "additional_labels": ["project-alpha", "mvp"]
    }
}
# Config is merged: defaults + custom_config
```

## Configuration Best Practices

### Environment-Based Configuration Strategy
- **Repository Settings**: Set via environment variables or execution parameters
- **Authentication**: Always use `GITHUB_TOKEN` environment variable
- **API Settings**: Use embedded defaults, override only when necessary
- **Label Standards**: Define via execution parameters for project consistency

### Project Execution Strategy
- **Minimal Setup**: Only repository and token required for basic operation
- **Runtime Parameters**: Pass project-specific settings directly to skill execution
- **Label Consistency**: Use skill defaults with project-specific additions
- **Environment Variables**: Set once, use across all projects

### Security Considerations
```bash
# ✅ DO: Use environment variables for sensitive data
export GITHUB_TOKEN="ghp_your_secure_token_here"
export GITHUB_REPO="organization/repository-name"

# ✅ DO: Pass non-sensitive config at runtime
python github_skill.py --assignee="project-lead" --milestone="Sprint-1"

# ❌ DON'T: Hardcode tokens anywhere
config = {"token": "ghp_hardcoded_bad"}

# ❌ DON'T: Store tokens in files
echo "ghp_token" > token.txt
```

### Configuration Validation
The skill automatically validates all settings and provides helpful error messages:
- Missing required environment variables with setup instructions
- Invalid repository names with format examples
- Token permission validation with required scopes
- Label validation with format examples

## Self-Contained Implementation

### Complete Implementation Template
All functionality is embedded within the skill - no external dependencies required:

```python
#!/usr/bin/env python3
"""
GitHub Issue Create/Update Skill - Self-Contained Implementation
Converts local task markdown files to GitHub Issues with full field mapping
"""

import os
import sys
import json
import subprocess
import requests
from typing import Dict, List, Optional, Union
from dataclasses import dataclass, field
from pathlib import Path

@dataclass
class GitHubConfig:
    """Self-contained configuration with sensible defaults"""
    repository: str = ""  # Set via env GITHUB_REPO or parameter
    token: str = ""       # Set via env GITHUB_TOKEN
    default_assignee: Optional[str] = None
    milestone: Optional[str] = None
    base_url: str = "https://api.github.com"
    timeout: int = 30
    rate_limit_delay: int = 1
    max_retries: int = 3
    
    # Field mapping defaults
    state_mapping: Dict[str, str] = field(default_factory=lambda: {
        "ready": "open", "in-progress": "open", 
        "completed": "closed", "cancelled": "closed"
    })
    
    priority_labels: Dict[str, Dict] = field(default_factory=lambda: {
        "High": {"name": "priority:high", "color": "d73a4a"},
        "Medium": {"name": "priority:medium", "color": "fbca04"},
        "Low": {"name": "priority:low", "color": "0075ca"}
    })
    
    auto_create_labels: bool = True
    effort_label_prefix: str = "effort:"
    additional_labels: List[str] = field(default_factory=lambda: ["auto-generated"])

class GitHubSkillImplementation:
    """Self-contained GitHub Issue creation and update skill"""
    
    def __init__(self, config_override: Optional[Dict] = None):
        self.config = self._load_config(config_override)
        self.github_client = self._create_github_client()
    
    def _load_config(self, override: Optional[Dict] = None) -> GitHubConfig:
        """Load configuration from environment and parameters"""
        config = GitHubConfig()
        
        # Load from environment variables
        config.repository = os.getenv('GITHUB_REPO', config.repository)
        config.token = os.getenv('GITHUB_TOKEN', config.token)
        config.default_assignee = os.getenv('GITHUB_DEFAULT_ASSIGNEE')
        
        # Apply overrides
        if override:
            for key, value in override.items():
                if hasattr(config, key):
                    setattr(config, key, value)
        
        # Interactive setup if missing required fields
        if not config.repository or not config.token:
            config = self._interactive_setup(config)
        
        return config
    
    def _interactive_setup(self, config: GitHubConfig) -> GitHubConfig:
        """Interactive setup for missing configuration"""
        print("GitHub Issue Integration Setup")
        print("=" * 35)
        
        if not config.repository:
            config.repository = input("Repository (owner/repo): ").strip()
        
        if not config.token:
            print("\nGet Personal Access Token:")
            print("https://github.com/settings/tokens")
            print("Required scopes: repo (or public_repo)")
            config.token = input("Personal Access Token: ").strip()
        
        if not config.default_assignee:
            assignee = input("Default assignee (optional): ").strip()
            if assignee:
                config.default_assignee = assignee
        
        print(f"\n✅ Configuration complete for {config.repository}")
        return config

    def execute(self, task_file_paths: Union[str, List[str]], 
                **execution_config) -> Dict:
        """Main skill execution - create/update GitHub issues from task files"""
        
        if isinstance(task_file_paths, str):
            task_file_paths = [task_file_paths]
        
        results = {
            "processed": [],
            "created": [],
            "updated": [],
            "errors": [],
            "summary": {}
        }
        
        for task_path in task_file_paths:
            try:
                task_data = self._parse_task_file(task_path)
                if self._has_github_issue(task_data):
                    result = self._update_github_issue(task_data, task_path)
                    results["updated"].append(result)
                else:
                    result = self._create_github_issue(task_data, task_path)
                    results["created"].append(result)
                
                results["processed"].append(task_path)
                
            except Exception as e:
                error = {"file": task_path, "error": str(e)}
                results["errors"].append(error)
        
        results["summary"] = {
            "total_processed": len(results["processed"]),
            "created_count": len(results["created"]),
            "updated_count": len(results["updated"]),
            "error_count": len(results["errors"])
        }
        
        return results

# ... (Additional implementation methods would continue here)
```

### Execution Examples

**Command Line Usage:**
```bash
# Set environment variables
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
export GITHUB_REPO="organization/repository-name"

# Execute skill on single file
python github_issue_skill.py ./tasks/T1-feature.md

# Execute on multiple files
python github_issue_skill.py ./tasks/*.md --assignee="project-lead"

# Execute with custom configuration
python github_issue_skill.py ./tasks/ --milestone="Sprint-1" \
  --labels="mvp,priority:high" --auto-assign
```

**Programmatic Usage:**
```python
from github_issue_skill import GitHubSkillImplementation

# Initialize with custom config
config = {
    "repository": "myorg/myrepo",
    "token": os.getenv('GITHUB_TOKEN'),
    "default_assignee": "team-lead",
    "milestone": "Q1-2026",
    "additional_labels": ["project-alpha", "auto-created"]
}

skill = GitHubSkillImplementation(config)

# Execute on task files
results = skill.execute([
    "./tasks/T1-authentication.md",
    "./tasks/T2-user-interface.md"
])

print(f"Created {results['summary']['created_count']} issues")
print(f"Updated {results['summary']['updated_count']} issues")
```

## Self-Contained Integration

**Direct Execution**: No external VS Code configuration required
```bash
# Direct command line execution
python github_issue_create_skill.py ./tasks/

# With custom parameters
python github_issue_create_skill.py ./tasks/T1-feature.md \
  --assignee="developer" --milestone="Sprint1" --labels="feature,mvp"
```

**GitHub Copilot Integration**: Execute via natural language commands
```
User: "Create GitHub issues for all task files in the current project"
Copilot: Executes skill with current directory, provides progress updates

User: "Update the GitHub issue for T1-authentication.md with high priority"
Copilot: Executes skill with priority label addition
```

**Standalone Operation**: No external configuration files required
- All settings embedded in skill or passed as parameters
- Authentication via environment variables only
- Self-contained error handling and user guidance
- Complete functionality in single skill file

**Project Integration**: Works with any project structure
```
my-project/
├── tasks/
│   ├── T1-feature.md     # Contains task metadata
│   └── T2-bugfix.md      # Will be converted to issues
├── docs/
└── src/

# Execute from project root
export GITHUB_TOKEN="ghp_token"
export GITHUB_REPO="myorg/my-project"
python path/to/github_issue_create_skill.py ./tasks/
```