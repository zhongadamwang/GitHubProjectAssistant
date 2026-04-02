# GitHub Issue Create/Update Skill - Self-Contained

## Overview
This skill creates and updates GitHub Issues from local task markdown files. It's completely self-contained with embedded authentication, configuration defaults, and complete field mapping functionality. No external dependencies or configuration files required.

## Files

- **`SKILL.md`** - Complete skill definition with embedded implementation template
- **`create_update_issues.py`** - Self-contained Python implementation
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
   # Create issues from all task files in a directory
   python create_update_issues.py --directory ./tasks/
   
   # Create issue from a single file
   python create_update_issues.py --file ./tasks/T1-feature.md
   ```

## Features

- **Self-Contained Authentication**: Uses environment variables only
- **Embedded Configuration**: All defaults built-in, no config files needed
- **Complete Field Mapping**: Task metadata → GitHub issue fields
- **Create & Update**: Handles both new issues and updates to existing ones
- **Task File Updates**: Automatically adds GitHub metadata to task files

## Task File Format

Use the `task-template.md` as a starting point for new tasks:

```markdown
# Issue: T1 - Feature Name
**State:** ready
**Labels:** feature, priority-medium
**Assignees:** username
**Priority:** Medium
**Estimated Effort:** 2 days

## Description
Brief description of the feature.

## Tasks
- [ ] Implement core functionality
- [ ] Add tests
- [ ] Update documentation

## Acceptance Criteria
- [ ] Feature works as expected
- [ ] Tests pass
- [ ] Documentation updated
```

This will automatically be converted to a GitHub Issue with all metadata properly mapped.

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `GITHUB_TOKEN` | Yes | GitHub Personal Access Token |
| `GITHUB_REPO` | Yes | Repository in format 'owner/repo' |
| `GITHUB_DEFAULT_ASSIGNEE` | No | Default assignee for issues |

## Command Line Options

```bash
# Process single task file
python create_update_issues.py --file ./tasks/T1-feature.md

# Process all files in directory
python create_update_issues.py --directory ./tasks/

# Process project (looks for tasks/ subdirectory)
python create_update_issues.py --project ./projects/my-project/

# Override assignee for this execution
python create_update_issues.py --directory ./tasks/ --assignee "team-lead"
```

## No External Dependencies

This skill is completely self-contained:
- ✅ No shared utility imports
- ✅ No external configuration files required
- ✅ No dependency on other skills or systems
- ✅ All authentication embedded
- ✅ All configuration defaults embedded