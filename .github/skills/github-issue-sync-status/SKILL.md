---
name: github-issue-sync-status
description: Self-contained skill that updates local task status from GitHub Issue state changes while preserving local file format and metadata. Includes embedded authentication, conflict resolution, and complete sync functionality with no external dependencies required.
license: MIT
---

# GitHub Issue Sync Status Skill - Self-Contained

## Intent
Self-contained synchronization of local development task status from GitHub Issues for seamless project management. Updates local task files when GitHub issue states change, preserving file format while maintaining consistency between GitHub project tracking and local development workflow. All configuration, authentication, and functionality is embedded within the skill.

## Inputs
- **Source**: Local task file path(s) or project directory containing files with GitHub issue metadata
- **Authentication**: Environmental variable `GITHUB_TOKEN` or interactive prompt setup  
- **Repository**: Environmental variable `GITHUB_REPO` or execution parameter
- **Mode**: Runtime parameter (check_only, update_only, check_and_update, dry_run)
- **Configuration**: Optional runtime parameters (conflict resolution strategy) - all defaults embedded

## Outputs
**Sync Results:**
- Updated task files with synchronized status
- Sync operation summary (updated, skipped, failed)
- Issue state change details
- Conflict resolution reports for manual review

**File Updates:**
- Task state synchronized with GitHub issue state
- Last sync timestamp metadata
- Conflict markers for manual resolution when needed

## Core Functionality

### GitHub Issue State Mapping
Maps GitHub issue states to local task states:

| GitHub State | Local Task State | Sync Action |
|-------------|------------------|-------------|
| open | ready, in-progress | Update to ready if unassigned, in-progress if assigned |
| closed (completed) | completed | Update to completed, add completion date |
| closed (not planned) | cancelled | Update to cancelled with reason |
| reopened | ready | Revert to ready state |

### Issue Detection & Mapping
Identifies local task files with GitHub issue tracking:
- Scans for `**GitHub Issue:** #123` metadata in task files
- Extracts issue URLs from `**Issue URL:**` fields
- Maps issue numbers to local file paths
- Validates issue accessibility via GitHub API

### Local File Updates
Preserves local task file format while updating status:
- Updates `**State:**` field based on GitHub issue state
- Adds/updates `**Last Synced:**` timestamp
- Preserves all existing metadata and content structure
- Adds sync status indicators for tracking

### Dual Integration Approach

**Method 1: GitHub CLI (Preferred)**
```bash
gh issue list --repo owner/repo --state all --json number,state,title
gh issue view 123 --repo owner/repo --json state,closedAt,stateReason
```

**Method 2: REST API (Fallback)**
```http
GET /repos/{owner}/{repo}/issues/{issue_number}
Accept: application/vnd.github.v3+json
Authorization: Bearer TOKEN
```

## Self-Contained Configuration

### Embedded Authentication Management
The skill includes all authentication methods directly without external dependencies:

**Authentication Methods (Priority Order)**:
1. **Environment Variable** (Recommended for security)
   ```bash
   export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
   export GITHUB_REPO="owner/repository-name"
   ```

2. **Interactive Prompt** (First-time setup)
   ```
   GitHub Sync Status Setup:
   1. Repository (e.g., owner/repo): _______
   2. Personal Access Token: _______
      Get token: https://github.com/settings/tokens
      Required scopes: repo (read access to issues)
   3. Sync preferences: [manual/scheduled]
   ```

3. **Runtime Configuration** (For specific executions)
   ```python
   config = {
     "repository": "owner/repo-name",
     "token": os.getenv('GITHUB_TOKEN'),
     "sync_mode": "check_and_update",
     "preserve_manual_changes": True
   }
   ```

**Security Best Practices**: 
- Never hardcode tokens; always use environment variables
- Token requires only read permissions for public repos
- For private repos, requires `repo` scope for issue access

### Embedded Default Configuration
The skill includes comprehensive default settings:

```python
DEFAULT_SYNC_CONFIG = {
    "authentication": {
        "token_env_var": "GITHUB_TOKEN",
        "repo_env_var": "GITHUB_REPO",
        "interactive_setup": True
    },
    "sync_behavior": {
        "auto_sync": False,
        "preserve_manual_changes": True,
        "conflict_resolution": "manual",
        "sync_frequency": "on_demand",
        "dry_run": False
    },
    "state_mapping": {
        "issue_to_task": {
            "open": "ready",
            "closed": "completed"
        },
        "preserve_in_progress": True,
        "add_completion_date": True,
        "respect_assignee_state": True
    },
    "file_handling": {
        "backup_before_sync": False,
        "create_sync_log": True,
        "preserve_format": True,
        "conflict_markers": True
    },
    "api_settings": {
        "base_url": "https://api.github.com",
        "timeout": 30,
        "rate_limit_delay": 1,
        "max_retries": 3
    }
}
```

## Usage Examples

### Single File Sync
```markdown
// Sync specific task file with its GitHub issue
Input: ./tasks/T1-github-integration.md (contains GitHub Issue: #123)
Process: Check GitHub issue #123 state → Update local task state
Output: Task file updated, sync status reported
```

### Project Directory Sync
```markdown
// Sync all task files in project with tracked GitHub issues
Input: ./projects/01-building-skills/tasks/
Process: 
- Scan for files with GitHub issue metadata
- Batch check issue states via GitHub API
- Update local files with state changes
Output: 
- 5 files scanned, 3 with GitHub issues
- T1: No change (already completed)
- T2: Updated from ready → in-progress (issue assigned)
- T3: Updated from in-progress → completed (issue closed)
```

### Conflict Detection
```markdown
// Handle conflicts between local and GitHub states
Scenario: Local task marked "completed" but GitHub issue reopened
Action: 
- Detect conflict between local:completed vs github:open
- Add conflict marker to file
- Report conflict for manual resolution
- Maintain current state until resolved
```

## Self-Contained Implementation Architecture

### Complete Implementation Template
All functionality embedded within the skill - no external dependencies required:

```python
#!/usr/bin/env python3
"""
GitHub Issue Sync Status Skill - Self-Contained Implementation  
Synchronizes local task file status with GitHub Issue states
"""

import os
import sys
import json
import subprocess
import requests
from typing import Dict, List, Optional, Union, Tuple
from dataclasses import dataclass, field
from pathlib import Path
from datetime import datetime
import re

@dataclass
class SyncConfig:
    """Self-contained sync configuration with sensible defaults"""
    repository: str = ""  # Set via env GITHUB_REPO or parameter  
    token: str = ""       # Set via env GITHUB_TOKEN
    sync_mode: str = "check_and_update"  # check_only, update_only, check_and_update
    dry_run: bool = False
    preserve_manual_changes: bool = True
    conflict_resolution: str = "manual"  # manual, github_wins, local_wins, smart
    
    # State mapping defaults
    issue_to_task_mapping: Dict[str, str] = field(default_factory=lambda: {
        "open": "ready", "closed": "completed"
    })
    
    # File handling defaults  
    backup_before_sync: bool = False
    preserve_format: bool = True
    add_completion_date: bool = True
    create_sync_log: bool = True

class GitHubSyncImplementation:
    """Self-contained GitHub Issue sync status skill"""
    
    def __init__(self, config_override: Optional[Dict] = None):
        self.config = self._load_config(config_override)
        self.github_client = self._create_github_client()
        self.sync_log = []
    
    def _load_config(self, override: Optional[Dict] = None) -> SyncConfig:
        """Load configuration from environment and parameters"""
        config = SyncConfig()
        
        # Load from environment variables
        config.repository = os.getenv('GITHUB_REPO', config.repository)
        config.token = os.getenv('GITHUB_TOKEN', config.token)
        
        # Apply overrides
        if override:
            for key, value in override.items():
                if hasattr(config, key):
                    setattr(config, key, value)
        
        # Interactive setup if missing required fields
        if not config.repository or not config.token:
            config = self._interactive_setup(config)
        
        return config
    
    def _interactive_setup(self, config: SyncConfig) -> SyncConfig:
        """Interactive setup for missing configuration"""
        print("GitHub Issue Sync Setup")
        print("=" * 25)
        
        if not config.repository:
            config.repository = input("Repository (owner/repo): ").strip()
        
        if not config.token:
            print("\nGet Personal Access Token:")
            print("https://github.com/settings/tokens") 
            print("Required scopes: repo (read access to issues)")
            config.token = input("Personal Access Token: ").strip()
        
        print(f"\n✅ Sync configuration complete for {config.repository}")
        return config

    def execute(self, task_file_paths: Union[str, List[str]], 
                **execution_config) -> Dict:
        """Main skill execution - sync task status from GitHub issues"""
        
        if isinstance(task_file_paths, str):
            if os.path.isdir(task_file_paths):
                task_file_paths = self._find_task_files(task_file_paths)
            else:
                task_file_paths = [task_file_paths]
        
        results = {
            "scanned": [],
            "synced": [],
            "conflicts": [],
            "skipped": [],
            "errors": [],
            "summary": {}
        }
        
        for task_path in task_file_paths:
            try:
                task_data = self._parse_task_file(task_path)
                github_issue_id = self._extract_github_issue_id(task_data)
                
                if not github_issue_id:
                    results["skipped"].append({
                        "file": task_path, 
                        "reason": "No GitHub issue ID found"
                    })
                    continue
                
                # Get current issue state from GitHub
                issue_state = self._get_github_issue_state(github_issue_id)
                if not issue_state:
                    results["errors"].append({
                        "file": task_path,
                        "error": f"Could not fetch issue #{github_issue_id}"
                    })
                    continue
                
                # Check if sync is needed
                sync_result = self._sync_task_status(
                    task_path, task_data, issue_state, github_issue_id
                )
                
                if sync_result["action"] == "synced":
                    results["synced"].append(sync_result)
                elif sync_result["action"] == "conflict":
                    results["conflicts"].append(sync_result)
                else:
                    results["skipped"].append(sync_result)
                    
                results["scanned"].append(task_path)
                
            except Exception as e:
                error = {"file": task_path, "error": str(e)}
                results["errors"].append(error)
        
        results["summary"] = {
            "total_scanned": len(results["scanned"]),
            "synced_count": len(results["synced"]),
            "conflict_count": len(results["conflicts"]),
            "skipped_count": len(results["skipped"]),
            "error_count": len(results["errors"])
        }
        
        if self.config.create_sync_log:
            self._write_sync_log(results)
        
        return results

    def _sync_task_status(self, task_path: str, task_data: Dict, 
                         issue_state: Dict, issue_id: str) -> Dict:
        """Sync individual task status with GitHub issue state"""
        
        current_task_state = task_data.get("state", "").lower()
        github_state = issue_state.get("state", "open")
        
        # Map GitHub state to task state
        target_task_state = self.config.issue_to_task_mapping.get(
            github_state, current_task_state
        )
        
        sync_result = {
            "file": task_path,
            "issue_id": issue_id,
            "github_state": github_state,
            "current_task_state": current_task_state,
            "target_task_state": target_task_state,
            "action": "no_change"
        }
        
        # Check if sync is needed
        if current_task_state == target_task_state:
            sync_result["action"] = "no_change"
            sync_result["reason"] = "States already match"
            return sync_result
        
        # Handle conflicts (manual changes vs GitHub state)
        if self.config.preserve_manual_changes and self._detect_conflict(
            current_task_state, target_task_state, issue_state
        ):
            sync_result["action"] = "conflict" 
            sync_result["conflict_type"] = "manual_vs_github"
            return sync_result
        
        # Perform sync if not in dry run mode
        if not self.config.dry_run:
            success = self._update_task_file_status(
                task_path, target_task_state, issue_state
            )
            if success:
                sync_result["action"] = "synced"
                sync_result["updated_state"] = target_task_state
            else:
                sync_result["action"] = "error"
                sync_result["error"] = "Failed to update task file"
        else:
            sync_result["action"] = "dry_run"
            sync_result["would_update_to"] = target_task_state
        
        return sync_result

# ... (Additional implementation methods would continue here)
```

### Execution Examples

**Command Line Usage:**
```bash
# Set environment variables
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
export GITHUB_REPO="organization/repository-name"

# Sync single task file
python github_sync_skill.py ./tasks/T1-feature.md

# Sync all tasks in directory  
python github_sync_skill.py ./tasks/

# Dry run to see what would change
python github_sync_skill.py ./tasks/ --dry-run

# Sync with custom conflict resolution
python github_sync_skill.py ./tasks/ --conflict-resolution="github_wins"
```

**Programmatic Usage:**
```python
from github_sync_skill import GitHubSyncImplementation

# Initialize with custom config
config = {
    "repository": "myorg/myrepo",
    "token": os.getenv('GITHUB_TOKEN'),
    "sync_mode": "check_and_update",
    "preserve_manual_changes": True,
    "dry_run": False
}

skill = GitHubSyncImplementation(config)

# Execute sync
results = skill.execute("./tasks/")

print(f"Synced {results['summary']['synced_count']} task files")
print(f"Detected {results['summary']['conflict_count']} conflicts")

# Handle conflicts
for conflict in results['conflicts']:
    print(f"Conflict in {conflict['file']}: "
          f"task={conflict['current_task_state']} vs "  
          f"github={conflict['github_state']}")
```

## Advanced Features

### Conflict Resolution
```python
class ConflictResolver:
    STRATEGIES = {
        "github_wins": "Always use GitHub state",
        "local_wins": "Preserve local state", 
        "manual": "Mark conflicts for review",
        "smart": "Use timestamps and context"
    }
```

### Sync Logging
```markdown
## Sync Log - 2026-02-24 14:30:00

### Issues Processed
- #123: T1-github-integration.md → No change (both completed)
- #124: T2-requirements.md → Updated ready → in-progress
- #125: T3-goals.md → CONFLICT detected (manual review needed)

### Summary
- Files scanned: 8
- Issues found: 3  
- Updated: 1
- Conflicts: 1
- Errors: 0
```

## Quality Criteria
- **Accuracy**: Local task states accurately reflect GitHub issue states
- **Preservation**: Local file format and metadata completely preserved
- **Resilience**: Robust handling of API failures, rate limits, and network issues
- **Conflict Handling**: Clear detection and resolution of state conflicts
- **Performance**: Efficient batch processing with minimal API calls
- **Traceability**: Complete audit trail of sync operations and changes

## Self-Contained Integration

**Direct Execution**: No external configuration files required
```bash
# Direct command line execution with environment variables
export GITHUB_TOKEN="ghp_token" 
export GITHUB_REPO="owner/repo"
python github_sync_skill.py ./tasks/

# Single file sync with parameters
python github_sync_skill.py ./tasks/T1-feature.md --dry-run

# Custom conflict resolution
python github_sync_skill.py ./tasks/ --conflict-resolution="github_wins"
```

**Standalone Script Integration**: Self-contained execution
```python
#!/usr/bin/env python3
"""Standalone sync script - no external dependencies""" 

import os
from github_sync_skill import GitHubSyncImplementation

def main():
    # Configuration via environment variables
    config = {
        "repository": os.getenv('GITHUB_REPO', 'myorg/myproject'), 
        "token": os.getenv('GITHUB_TOKEN'),
        "sync_mode": "check_and_update",
        "dry_run": "--dry-run" in sys.argv
    }
    
    skill = GitHubSyncImplementation(config)
    
    # Execute sync on current directory
    task_dir = "./tasks/" if os.path.exists("./tasks/") else "."
    results = skill.execute(task_dir)
    
    # Print results
    summary = results["summary"]
    print(f"✅ Synced {summary['synced_count']} files")
    print(f"⚠️  {summary['conflict_count']} conflicts detected")
    print(f"❌ {summary['error_count']} errors")

if __name__ == "__main__":
    main()
```

**GitHub Copilot Integration**: Execute via natural language
```
User: "Sync all task statuses with their GitHub issues"
Copilot: Executes sync skill on current project, reports results

User: "Check which local tasks are out of sync with GitHub"  
Copilot: Runs skill in dry-run mode, shows status differences

User: "Update task T1-auth.md status from its GitHub issue"
Copilot: Syncs specific task file with its linked GitHub issue
```

**Project Integration**: Works with any project structure
```
my-project/
├── tasks/
│   ├── T1-auth.md        # GitHub Issue: #42
│   ├── T2-frontend.md    # GitHub Issue: #43  
│   └── T3-backend.md     # GitHub Issue: #44
├── docs/
└── src/

# Execute from project root
python github_sync_skill.py ./tasks/
# Automatically finds and syncs all files with GitHub issue metadata
```

## Security & Privacy

### Self-Contained Security Model
- **Environment Variables Only**: Uses `GITHUB_TOKEN` environment variable, never stores tokens in files
- **Read-Only Operations**: Only reads GitHub issue data, never modifies GitHub repository state  
- **Minimal Permissions**: Requires only `public_repo` scope for public repositories, `repo` for private
- **Local File Safety**: Optional backup creation before modifications (configurable)
- **Audit Trail**: Maintains detailed logs of all sync operations within skill execution
- **No External Dependencies**: All security handling embedded within skill implementation

### Token Security Best Practices
```bash
# ✅ DO: Set token as environment variable  
export GITHUB_TOKEN="ghp_secure_token_here"

# ✅ DO: Use token with minimal required scopes
# Public repos: public_repo scope only
# Private repos: repo scope only

# ❌ DON'T: Store tokens in files
echo "ghp_token" > token.txt  # Never do this

# ❌ DON'T: Hardcode tokens in scripts  
TOKEN = "ghp_hardcoded_bad"  # Never do this
```

### Interactive Security Setup
If token is not found, skill provides secure setup guidance:
```
🔒 GitHub Authentication Required

Choose setup method:
1. Environment Variable (Recommended)
   export GITHUB_TOKEN="your_token"
   
2. Interactive Setup (One-time)
   • Token will be requested securely
   • No token storage in files
   • Must re-enter for each session

Token Requirements:
• Public repos: public_repo scope
• Private repos: repo scope (read access)
• Get token: https://github.com/settings/tokens
```

### Privacy Protection
- **Local Processing**: All file analysis performed locally
- **Minimal API Calls**: Only fetches specific issue state data  
- **No Data Collection**: Skill collects no usage analytics or user data
- **Execution Isolation**: Each execution is independent with no persistent state
- **Content Privacy**: Task file contents never transmitted to external services beyond GitHub API