#!/usr/bin/env python3
"""
GitHub Issue Create/Update Script - Self-Contained Implementation
================================================================

Creates and updates GitHub Issues from local task markdown files. 
Self-contained with embedded authentication, configuration defaults, and 
complete field mapping functionality. No external dependencies required.

Part of the EDPS (Evolutionary Development Process System) skills.

Usage:
    python create_update_issues.py --file <task_file>
    python create_update_issues.py --project <project_directory>
    python create_update_issues.py --directory <tasks_directory>
    
Environment Variables:
    GITHUB_TOKEN    - GitHub Personal Access Token (required)
    GITHUB_REPO     - Repository in format 'owner/repo' (required)
    GITHUB_DEFAULT_ASSIGNEE - Default assignee username (optional)
"""

import argparse
import json
import os
import sys
import re
import requests
from datetime import datetime
from pathlib import Path
from typing import List, Dict, Any, Optional


class GitHubConfig:
    """Self-contained configuration with embedded defaults."""
    
    def __init__(self, **overrides):
        # Load GitHub configuration from file and environment
        repo_config = self._find_repo_config()
        
        # Set defaults from config file and environment
        self.repository = repo_config.get('repository', '')
        self.token = repo_config.get('token', '') or os.getenv('GITHUB_TOKEN', '')
        self.default_assignee = os.getenv('GITHUB_DEFAULT_ASSIGNEE')
        self.base_url = "https://api.github.com"
        self.timeout = 30
        self.max_retries = 3
        
        # Field mapping defaults
        self.state_mapping = {
            "ready": "open",
            "in-progress": "open", 
            "completed": "closed",
            "cancelled": "closed"
        }
        
        self.priority_labels = {
            "High": {"name": "priority:high", "color": "d73a4a"},
            "Medium": {"name": "priority:medium", "color": "fbca04"},
            "Low": {"name": "priority:low", "color": "0075ca"}
        }
        
        self.additional_labels = ["auto-generated"]
        self.auto_create_labels = True
        
        # Apply any overrides
        for key, value in overrides.items():
            if hasattr(self, key):
                setattr(self, key, value)
        
        # Validate required fields
        if not self.repository or not self.token:
            self._interactive_setup()
    
    def _find_repo_config(self) -> Dict[str, str]:
        """Find and load repository configuration from github-credentials.json."""
        # Look for github-credentials.json in current directory and parent directories
        current_dir = Path.cwd()
        
        for path in [current_dir] + list(current_dir.parents):
            config_file = path / "github-credentials.json"
            if config_file.exists():
                try:
                    with open(config_file) as f:
                        config = json.load(f)
                    
                    github_config = config.get('github', {})
                    default_repo = github_config.get('default_repository', {})
                    
                    owner = default_repo.get('owner', '')
                    name = default_repo.get('name', '')
                    repository = f"{owner}/{name}" if owner and name else ""
                    
                    return {
                        'repository': repository,
                        'token': github_config.get('personal_access_token', '')
                    }
                except (json.JSONDecodeError, KeyError) as e:
                    print(f"⚠️  Warning: Error reading {config_file}: {e}")
                    continue
                
        return {'repository': '', 'token': ''}
    
    def _interactive_setup(self):
        """Interactive setup for missing configuration."""
        print("GitHub Issue Creation Setup")
        print("=" * 30)
        
        if not self.repository:
            self.repository = input("Repository (owner/repo): ").strip()
        
        if not self.token:
            print("\nGet Personal Access Token:")
            print("https://github.com/settings/tokens")
            print("Required scopes: repo (or public_repo)")
            self.token = input("Personal Access Token: ").strip()
        
        if not self.default_assignee:
            assignee = input("Default assignee (optional): ").strip()
            if assignee:
                self.default_assignee = assignee
        
        print(f"\n✅ Configuration complete for {self.repository}")
    
    def get_repo_owner_name(self) -> tuple:
        """Split repository into owner and name."""
        if '/' not in self.repository:
            raise ValueError(f"Repository must be in format 'owner/repo', got: {self.repository}")
        return self.repository.split('/', 1)


class GitHubIssueCreator:
    """Self-contained GitHub Issue creator with embedded functionality."""
    
    def __init__(self, **config_overrides):
        self.config = GitHubConfig(**config_overrides)
        self.repo_owner, self.repo_name = self.config.get_repo_owner_name()
        self.headers = {
            'Authorization': f'token {self.config.token}',
            'Accept': 'application/vnd.github.v3+json',
            'Content-Type': 'application/json'
        }
        self.base_url = f"{self.config.base_url}/repos/{self.repo_owner}/{self.repo_name}"
        self.results = []
    
    def parse_task_file(self, file_path: str) -> Dict:
        """Parse a task markdown file and extract metadata."""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extract title from first heading
        title_match = re.search(r'^#\s+(.+)', content, re.MULTILINE)
        title = title_match.group(1).strip() if title_match else os.path.basename(file_path)
        
        # Check if there's already a GitHub issue reference
        github_issue_match = re.search(r'\*\*GitHub Issue:\*\*\s*#?(\d+)', content)
        existing_issue = int(github_issue_match.group(1)) if github_issue_match else None
        
        # Extract state from content (if present)
        state_match = re.search(r'\*\*State:\*\*\s*(\w+)', content)
        state = state_match.group(1).lower() if state_match else "open"
        
        # Map task state to GitHub state using embedded mapping
        github_state = self.config.state_mapping.get(state, "open")
        
        # Extract labels from content
        labels_match = re.search(r'\*\*Labels:\*\*\s*(.+)', content)
        labels = []
        if labels_match:
            labels = [label.strip() for label in labels_match.group(1).split(',')]
        
        # Add priority label if present
        priority_match = re.search(r'\*\*Priority:\*\*\s*(\w+)', content)
        if priority_match:
            priority = priority_match.group(1)
            if priority in self.config.priority_labels:
                labels.append(self.config.priority_labels[priority]["name"])
        
        # Add additional labels from configuration
        labels.extend(self.config.additional_labels)
        
        # Remove duplicates
        labels = list(set(labels))
        
        # Extract assignees
        assignees = []
        assignees_match = re.search(r'\*\*Assignees:\*\*\s*(.+)', content)
        if assignees_match:
            assignees = [a.strip() for a in assignees_match.group(1).split(',')]
        elif self.config.default_assignee:
            assignees = [self.config.default_assignee]
        
        # Create issue body from content (remove title and metadata)
        body_content = re.sub(r'^#\s+.+\n?', '', content, flags=re.MULTILINE)
        # Remove GitHub metadata if present
        body_content = re.sub(r'\*\*GitHub Issue:\*\*.*\n?', '', body_content)
        body_content = re.sub(r'\*\*Issue URL:\*\*.*\n?', '', body_content)
        body_content = body_content.strip()
        
        return {
            'title': title,
            'body': body_content,
            'state': github_state,
            'labels': labels,
            'assignees': assignees,
            'existing_issue': existing_issue,
            'file_path': file_path,
            'original_content': content
        }

    def create_issue(self, task_data: Dict) -> Optional[int]:
        """Create a new GitHub issue."""
        url = f"{self.base_url}/issues"
        payload = {
            'title': task_data['title'],
            'body': task_data['body'],
            'labels': task_data['labels']
        }
        
        # Add assignees if present
        if task_data['assignees']:
            payload['assignees'] = task_data['assignees']
        
        try:
            response = requests.post(url, headers=self.headers, json=payload)
            response.raise_for_status()
            issue_data = response.json()
            print(f"✅ Created issue #{issue_data['number']}: {task_data['title']}")
            return issue_data['number']
        except requests.exceptions.RequestException as e:
            print(f"❌ Failed to create issue for {task_data['title']}: {e}")
            if hasattr(e, 'response') and hasattr(e.response, 'text'):
                print(f"   Response: {e.response.text}")
            return None

    def update_issue(self, issue_number: int, task_data: Dict) -> bool:
        """Update an existing GitHub issue."""
        url = f"{self.base_url}/issues/{issue_number}"
        payload = {
            'title': task_data['title'],
            'body': task_data['body'],
            'state': task_data['state'],
            'labels': task_data['labels']
        }
        
        try:
            response = requests.patch(url, headers=self.headers, json=payload)
            response.raise_for_status()
            print(f"🔄 Updated issue #{issue_number}: {task_data['title']}")
            return True
        except requests.exceptions.RequestException as e:
            print(f"❌ Failed to update issue #{issue_number}: {e}")
            if hasattr(e, 'response') and hasattr(e.response, 'text'):
                print(f"   Response: {e.response.text}")
            return False

    def update_task_file(self, task_data: Dict, issue_number: int):
        """Update task file with GitHub issue metadata."""
        content = task_data['original_content']
        
        # Check if GitHub metadata already exists
        if '**GitHub Issue:**' in content:
            # Update existing metadata
            content = re.sub(
                r'\*\*GitHub Issue:\*\*\s*#?\d+',
                f'**GitHub Issue:** #{issue_number}',
                content
            )
            content = re.sub(
                r'\*\*Issue URL:\*\*.*',
                f'**Issue URL:** https://github.com/{self.repo_owner}/{self.repo_name}/issues/{issue_number}',
                content
            )
        else:
            # Add metadata after the title
            title_match = re.search(r'^(#\s+.+\n)', content, re.MULTILINE)
            if title_match:
                insert_pos = title_match.end()
                metadata = f"\n**GitHub Issue:** #{issue_number}\n**Issue URL:** https://github.com/{self.repo_owner}/{self.repo_name}/issues/{issue_number}\n"
                content = content[:insert_pos] + metadata + content[insert_pos:]
        
        # Write updated content back to file
        with open(task_data['file_path'], 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"📝 Updated task file: {Path(task_data['file_path']).name}")
    
    def process_single_file(self, file_path: Path) -> Dict[str, Any]:
        """Process a single task file."""
        print(f"📋 Processing {file_path.name}...")
        
        try:
            task_data = self.parse_task_file(str(file_path))
            
            if task_data['existing_issue']:
                # Update existing issue
                success = self.update_issue(task_data['existing_issue'], task_data)
                if success:
                    self.update_task_file(task_data, task_data['existing_issue'])
                return {
                    'file': file_path.name,
                    'operation': 'updated',
                    'issue_number': task_data['existing_issue'],
                    'success': success
                }
            else:
                # Create new issue
                issue_number = self.create_issue(task_data)
                if issue_number:
                    self.update_task_file(task_data, issue_number)
                    return {
                        'file': file_path.name,
                        'operation': 'created',
                        'issue_number': issue_number,
                        'success': True
                    }
                else:
                    return {
                        'file': file_path.name,
                        'operation': 'failed',
                        'success': False
                    }
                
        except Exception as e:
            error_msg = f"❌ Error processing {file_path.name}: {e}"
            print(error_msg)
            return {
                'file': file_path.name,
                'operation': 'error',
                'error': str(e),
                'success': False
            }

    def process_tasks_directory(self, tasks_directory: str):
        """Sync all tasks in the directory to GitHub issues."""
        task_files = list(Path(tasks_directory).glob("T*.md"))
        if not task_files:
            print(f"❌ No task files found in {tasks_directory}")
            return
        
        print(f"🚀 Syncing {len(task_files)} tasks to GitHub...")
        print(f"📁 Repository: {self.repo_owner}/{self.repo_name}")
        print()
        
        for task_file in sorted(task_files):
            result = self.process_single_file(task_file)
            self.results.append(result)
            print()
        
        # Print summary
        successful = sum(1 for r in self.results if r['success'])
        total = len(self.results)
        print(f"✅ {successful}/{total} tasks synced successfully")
        
        return self.results


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description="Create and update GitHub Issues from EDPS task files",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Configuration:
    Requires GitHub authentication and repository information.
    
    Method 1 - Environment Variable + Config File (Recommended):
        1. Set GITHUB_TOKEN environment variable: export GITHUB_TOKEN="ghp_xxxx"
        2. Ensure github-credentials.json exists in repository root with:
           {
             "github": {
               "default_repository": {
                 "owner": "your-username",
                 "name": "your-repo"
               }
             }
           }
    
    Method 2 - Config File Only:
        Create github-credentials.json in repository root with:
        {
          "github": {
            "personal_access_token": "ghp_xxxxxxxxxxxxxxxxxxxx",
            "default_repository": {
              "owner": "your-username", 
              "name": "your-repo"
            }
          }
        }

Examples:
    # Process a single task file
    python create_update_issues.py --file T001-setup.md
    
    # Process all tasks in a directory
    python create_update_issues.py --directory ./tasks
    
    # Process all tasks in a project (looks for tasks/ subdirectory)
    python create_update_issues.py --project ./projects/my-project
        """
    )
    
    parser.add_argument(
        '--file', '-f',
        type=Path,
        help='Process a single task file'
    )
    
    parser.add_argument(
        '--directory', '-d',
        type=Path,
        help='Process all task files in a directory'
    )
    
    parser.add_argument(
        '--project', '-p',
        type=Path,
        help='Process all task files in a project\'s tasks directory'
    )
    
    parser.add_argument(
        '--assignee',
        type=str,
        help='Override default assignee for this execution'
    )
    
    parser.add_argument(
        '--milestone',
        type=str,
        help='Set milestone for created/updated issues'
    )
    
    args = parser.parse_args()
    
    # Validate arguments
    if not any([args.file, args.directory, args.project]):
        parser.error("Must specify --file, --directory, or --project")
    
    try:
        # Build configuration overrides
        config_overrides = {}
        if args.assignee:
            config_overrides['default_assignee'] = args.assignee
        
        # Initialize the issue creator
        creator = GitHubIssueCreator(**config_overrides)
        
        # Process based on arguments
        if args.file:
            if not args.file.exists():
                print(f"❌ File not found: {args.file}")
                sys.exit(1)
            
            result = creator.process_single_file(args.file)
            if result['success']:
                print("✅ Task successfully synced to GitHub!")
            else:
                print("❌ Sync failed!")
                sys.exit(1)
                
        elif args.directory:
            if not args.directory.exists():
                print(f"❌ Directory not found: {args.directory}")
                sys.exit(1)
            
            creator.process_tasks_directory(str(args.directory))
            
        elif args.project:
            if not args.project.exists():
                print(f"❌ Project not found: {args.project}")
                sys.exit(1)
            
            tasks_dir = args.project / "tasks"
            if not tasks_dir.exists():
                print(f"❌ Tasks directory not found: {tasks_dir}")
                sys.exit(1)
            
            creator.process_tasks_directory(str(tasks_dir))
    
    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()