# GitHub Issue Sync Status Skill - Self-Contained

## Overview
This skill updates local task status from GitHub Issue state changes while preserving local file format and metadata. It's completely self-contained with embedded authentication, configuration defaults, and complete conflict resolution functionality. No external dependencies required.

## Files

- **`SKILL.md`** - Complete skill definition with embedded implementation template
- **`sync_status.py`** - Self-contained Python implementation
- **`task-template.md`** - Template for creating new task files
- **`README.md`** - This file

## Quick Start

1. **Set Environment Variables:**
   ```bash
   export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
   export GITHUB_REPO="organization/repository-name"
   ```

2. **Run the Skill:**
   ```bash
   # Sync all task files with GitHub issues
   python sync_status.py --directory ./tasks/
   
   # Sync a single task file
   python sync_status.py --file ./tasks/T1-feature.md
   
   # Dry run to see what would change
   python sync_status.py --directory ./tasks/ --dry-run
   ```

## Features

- **Self-Contained Authentication**: Uses environment variables only
- **Embedded Configuration**: All defaults built-in, no config files needed
- **Intelligent Conflict Resolution**: Multiple resolution strategies
- **Format Preservation**: Maintains original task file format and structure
- **State Mapping**: Maps GitHub issue states to local task states
- **Conflict Detection**: Identifies and handles state conflicts

## How It Works

The script:
1. **Finds task files** with GitHub issue metadata (`**GitHub Issue:** #123`)
2. **Fetches current state** from GitHub for each issue
3. **Compares states** between local tasks and GitHub issues
4. **Updates local files** while preserving format and content
5. **Handles conflicts** based on configuration settings

## State Mapping

| GitHub State | Local State | Logic |
|-------------|-------------|-------|
| open (unassigned) | ready | Issue is available for work |
| open (assigned) | in-progress | Issue is being worked on |
| closed | completed | Issue is finished |

## Conflict Resolution

The script detects conflicts between local and GitHub states and offers multiple resolution strategies:

### Available Strategies

#### `manual` (Default)
- Mark conflicts in task files with comment markers
- Report conflicts for manual resolution
- No automatic state changes for conflicted items

#### `github_wins`
- Always use GitHub state
- Override local changes
- Good for GitHub-primary workflows

#### `local_wins`
- Preserve local state
- Update sync timestamp only
- Good for local-primary workflows

## Task File Requirements

Files must have GitHub issue metadata to be synced:

```markdown
# Task Title

**State:** ready
**GitHub Issue:** #123
**Issue URL:** https://github.com/owner/repo/issues/123

## Description
Task description...
```

After syncing, the file is updated with sync information:

```markdown
**State:** completed
**Last Synced:** 2026-02-24 14:30:00
**Completed Date:** 2026-02-24
```

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `GITHUB_TOKEN` | Yes | GitHub Personal Access Token (read access) |
| `GITHUB_REPO` | Yes | Repository in format 'owner/repo' |

## Command Line Options

```bash
# Sync single task file
python sync_status.py --file ./tasks/T1-feature.md

# Sync all files in directory
python sync_status.py --directory ./tasks/

# Sync project (looks for tasks/ subdirectory)
python sync_status.py --project ./projects/my-project/

# Sync specific GitHub issue number
python sync_status.py --issue 123

# Dry run to see what would change
python sync_status.py --directory ./tasks/ --dry-run

# Use specific conflict resolution strategy
python sync_status.py --directory ./tasks/ --conflict-resolution github_wins
```

## Conflict Markers

When conflicts are detected in manual mode, the script adds markers:

```markdown
<!-- SYNC CONFLICT DETECTED -->
<!-- Local State: completed -->
<!-- GitHub State: open -->
<!-- Conflict: task_completed_issue_reopened -->
<!-- Please resolve manually and remove this comment -->

**State:** completed
```

## No External Dependencies

This skill is completely self-contained:
- ✅ No shared utility imports
- ✅ No external configuration files required
- ✅ No dependency on other skills or systems
- ✅ All authentication embedded
- ✅ All configuration defaults embedded
- ✅ Complete conflict resolution logic embedded