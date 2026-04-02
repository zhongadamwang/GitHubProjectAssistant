#!/usr/bin/env python3
"""
GitHub Issue Sync Status Script - Self-Contained Implementation
===============================================================

Updates local task status from GitHub Issue state changes while preserving 
local file format and metadata. Self-contained with embedded authentication,
configuration defaults, and complete sync functionality. No external dependencies.

Usage:
    python sync_status.py --file <task_file>
    python sync_status.py --project <project_directory>
    python sync_status.py --directory <tasks_directory>
    python sync_status.py --issue <issue_number>
    
Environment Variables:
    GITHUB_TOKEN    - GitHub Personal Access Token (required)
    GITHUB_REPO     - Repository in format 'owner/repo' (required)
"""

import argparse
import json
import os
import sys
import re
import requests
from datetime import datetime, timezone
from pathlib import Path
from typing import List, Dict, Any, Optional, Tuple


class SyncConfig:
    """Self-contained sync configuration with embedded defaults."""
    
    def __init__(self, **overrides):
        # Load GitHub configuration from file and environment
        repo_config = self._find_repo_config()
        
        # Set defaults from config file and environment
        self.repository = repo_config.get('repository', '')
        self.token = repo_config.get('token', '') or os.getenv('GITHUB_TOKEN', '')
        self.base_url = "https://api.github.com"
        self.timeout = 30
        self.max_retries = 3
        
        # Sync behavior defaults
        self.preserve_manual_changes = True
        self.conflict_resolution = "manual"  # manual, github_wins, local_wins
        self.dry_run = False
        self.add_completion_date = True
        self.create_sync_log = True
        
        # State mapping defaults
        self.issue_to_task_mapping = {
            "open": "ready",
            "closed": "completed"
        }
        
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
        print("GitHub Issue Sync Setup")
        print("=" * 25)
        
        if not self.repository:
            self.repository = input("Repository (owner/repo): ").strip()
        
        if not self.token:
            print("\nGet Personal Access Token:")
            print("https://github.com/settings/tokens")
            print("Required scopes: repo (read access to issues)")
            self.token = input("Personal Access Token: ").strip()
        
        print(f"\n✅ Sync configuration complete for {self.repository}")
    
    def get_repo_owner_name(self) -> tuple:
        """Split repository into owner and name."""
        if '/' not in self.repository:
            raise ValueError(f"Repository must be in format 'owner/repo', got: {self.repository}")
        return self.repository.split('/', 1)


class GitHubStatusSyncer:
    """Self-contained GitHub Issue status syncer with embedded functionality."""
    
    def __init__(self, **config_overrides):
        self.config = SyncConfig(**config_overrides)
        self.repo_owner, self.repo_name = self.config.get_repo_owner_name()
        self.headers = {
            'Authorization': f'token {self.config.token}',
            'Accept': 'application/vnd.github.v3+json',
            'Content-Type': 'application/json'
        }
        self.base_url = f"{self.config.base_url}/repos/{self.repo_owner}/{self.repo_name}"
        self.results = []
        self.conflicts = []
    
    def parse_task_file(self, file_path: Path) -> Dict:
        """Parse a task markdown file and extract metadata."""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extract current state
        state_match = re.search(r'\*\*State:\*\*\s*(\w+)', content)
        current_state = state_match.group(1).lower() if state_match else "ready"
        
        # Extract GitHub issue number
        github_issue_match = re.search(r'\*\*GitHub Issue:\*\*\s*#?(\d+)', content)
        github_issue_number = int(github_issue_match.group(1)) if github_issue_match else None
        
        # Extract last synced time if present
        last_synced_match = re.search(r'\*\*Last Synced:\*\*\s*(.+)', content)
        last_synced = last_synced_match.group(1) if last_synced_match else None
        
        return {
            'file_path': file_path,
            'current_state': current_state,
            'github_issue_number': github_issue_number,
            'last_synced': last_synced,
            'original_content': content
        }
    
    def get_github_issue_state(self, issue_number: int) -> Optional[Dict]:
        """Get GitHub issue state."""
        url = f"{self.base_url}/issues/{issue_number}"
        
        try:
            response = requests.get(url, headers=self.headers)
            response.raise_for_status()
            issue_data = response.json()
            
            return {
                'state': issue_data['state'],  # 'open' or 'closed'
                'updated_at': issue_data['updated_at'],
                'closed_at': issue_data.get('closed_at'),
                'title': issue_data['title'],
                'assignees': [a['login'] for a in issue_data.get('assignees', [])]
            }
        except requests.exceptions.RequestException as e:
            print(f"❌ Failed to get issue state for #{issue_number}: {e}")
            return None
    
    def detect_conflict(self, task_data: Dict, github_state: Dict) -> Optional[str]:
        """Detect conflicts between local and GitHub states."""
        if not self.config.preserve_manual_changes:
            return None
        
        current_state = task_data['current_state']
        github_issue_state = github_state['state']
        target_state = self.config.issue_to_task_mapping.get(github_issue_state, current_state)
        
        # Check for potential conflicts
        if current_state == "in-progress" and target_state == "completed":
            # Task marked as in-progress but issue is closed
            return "task_in_progress_issue_closed"
        
        if current_state == "completed" and target_state == "ready":
            # Task marked as completed but issue is reopened
            return "task_completed_issue_reopened"
        
        return None
    
    def update_task_file_status(self, task_data: Dict, new_state: str, github_state: Dict) -> bool:
        """Update task file with new status while preserving format."""
        content = task_data['original_content']
        file_path = task_data['file_path']
        
        if self.config.dry_run:
            print(f"🔍 [DRY RUN] Would update {file_path.name} state: {task_data['current_state']} → {new_state}")
            return True
        
        try:
            # Update state
            content = re.sub(
                r'\*\*State:\*\*\s*\w+',
                f'**State:** {new_state}',
                content
            )
            
            # Add completion date if transitioning to completed
            if new_state == "completed" and self.config.add_completion_date:
                completion_date = datetime.now().strftime('%Y-%m-%d')
                # Check if completion date already exists
                if not re.search(r'\*\*Completed Date:\*\*', content):
                    # Find a good place to insert completion date (after State)
                    state_line_match = re.search(r'(\*\*State:\*\*[^\n]*\n)', content)
                    if state_line_match:
                        insert_pos = state_line_match.end()
                        completion_line = f"**Completed Date:** {completion_date}\n"
                        content = content[:insert_pos] + completion_line + content[insert_pos:]
            
            # Update last synced timestamp
            sync_timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            if '**Last Synced:**' in content:
                content = re.sub(
                    r'\*\*Last Synced:\*\*.*',
                    f'**Last Synced:** {sync_timestamp}',
                    content
                )
            else:
                # Add sync timestamp after GitHub metadata
                github_url_match = re.search(r'(\*\*Issue URL:\*\*[^\n]*\n)', content)
                if github_url_match:
                    insert_pos = github_url_match.end()
                    sync_line = f"**Last Synced:** {sync_timestamp}\n"
                    content = content[:insert_pos] + sync_line + content[insert_pos:]
            
            # Write updated content back to file
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            print(f"📝 Updated {file_path.name}: {task_data['current_state']} → {new_state}")
            return True
            
        except Exception as e:
            print(f"❌ Failed to update {file_path.name}: {e}")
            return False
    
    def sync_single_file(self, file_path: Path) -> Dict[str, Any]:
        """Sync status for a single task file."""
        print(f"🔍 Syncing: {file_path.name}")
        
        try:
            # Parse task file
            task_data = self.parse_task_file(file_path)
            
            # Check if file has GitHub issue metadata
            if not task_data['github_issue_number']:
                print(f"ℹ️  No GitHub issue metadata found in {file_path.name}")
                return {
                    'file': file_path.name,
                    'operation': 'skipped',
                    'reason': 'no_github_issue',
                    'success': True
                }
            
            # Get GitHub issue state
            github_state = self.get_github_issue_state(task_data['github_issue_number'])
            if not github_state:
                return {
                    'file': file_path.name,
                    'operation': 'error',
                    'reason': 'failed_to_get_issue_state',
                    'success': False
                }
            
            # Map GitHub state to task state
            current_state = task_data['current_state']
            github_issue_state = github_state['state']
            target_state = self.config.issue_to_task_mapping.get(github_issue_state, current_state)
            
            # Check if sync is needed
            if current_state == target_state:
                print(f"✅ {file_path.name} already in sync (state: {current_state})")
                return {
                    'file': file_path.name,
                    'operation': 'no_change',
                    'current_state': current_state,
                    'success': True
                }
            
            # Check for conflicts
            conflict_type = self.detect_conflict(task_data, github_state)
            if conflict_type and self.config.conflict_resolution == "manual":
                print(f"⚠️  Conflict detected in {file_path.name}: {conflict_type}")
                conflict_info = {
                    'file': file_path.name,
                    'operation': 'conflict',
                    'conflict_type': conflict_type,
                    'current_state': current_state,
                    'github_state': github_issue_state,
                    'target_state': target_state,
                    'success': False
                }
                self.conflicts.append(conflict_info)
                return conflict_info
            
            # Perform sync
            if self.config.conflict_resolution == "github_wins":
                target_state = self.config.issue_to_task_mapping.get(github_issue_state, current_state)
            elif self.config.conflict_resolution == "local_wins":
                print(f"🔒 Preserving local state for {file_path.name}: {current_state}")
                return {
                    'file': file_path.name,
                    'operation': 'local_preserved',
                    'current_state': current_state,
                    'success': True
                }
            
            # Update the file
            success = self.update_task_file_status(task_data, target_state, github_state)
            
            return {
                'file': file_path.name,
                'operation': 'synced' if success else 'failed',
                'previous_state': current_state,
                'new_state': target_state,
                'github_state': github_issue_state,
                'success': success
            }
        
        except Exception as e:
            error_msg = f"Error syncing {file_path.name}: {e}"
            print(f"❌ {error_msg}")
            return {
                'file': file_path.name,
                'operation': 'error',
                'error': str(e),
                'success': False
            }
    
    def find_task_files_with_github_issues(self, directory: Path) -> List[Path]:
        """Find task files that have GitHub issue metadata."""
        task_files = []
        
        for file_path in directory.glob("*.md"):
            if file_path.name.startswith('.'):
                continue
            
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                if re.search(r'\*\*GitHub Issue:\*\*\s*#?\d+', content):
                    task_files.append(file_path)
            except Exception:
                continue
        
        return sorted(task_files)
    
    def sync_directory(self, directory: Path) -> List[Dict]:
        """Sync all task files in directory that have GitHub issues."""
        task_files = self.find_task_files_with_github_issues(directory)
        
        if not task_files:
            print(f"ℹ️  No task files with GitHub issues found in {directory}")
            return []
        
        print(f"🔄 Syncing {len(task_files)} task files with GitHub issues...")
        print(f"📁 Repository: {self.repo_owner}/{self.repo_name}")
        print()
        
        results = []
        for task_file in task_files:
            result = self.sync_single_file(task_file)
            results.append(result)
            self.results.append(result)
            print()
        
        # Print summary
        successful = sum(1 for r in results if r['success'])
        total = len(results)
        conflicts = len(self.conflicts)
        
        print(f"📊 Sync Summary:")
        print(f"  ✅ Synced: {successful}/{total}")
        if conflicts > 0:
            print(f"  ⚠️  Conflicts: {conflicts}")
            print("\nConflicts require manual resolution:")
            for conflict in self.conflicts:
                print(f"  - {conflict['file']}: {conflict['conflict_type']}")
        
        return results


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description="Sync GitHub Issue status to local task files",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Configuration:
    The tool requires GitHub authentication and repository information.
    
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
    
    # Sync a single task file
    python sync_status.py --file T001-auth.md
    
    # Sync all task files in a directory
    python sync_status.py --directory ./tasks
    
    # Sync all task files in a project (looks for tasks/ subdirectory)
    python sync_status.py --project ./projects/my-project
    
    # Sync specific issue number
    python sync_status.py --issue 123
    
    # Dry run to see what would change
    python sync_status.py --directory ./tasks --dry-run
        """
    )
    
    parser.add_argument(
        '--file', '-f',
        type=Path,
        help='Sync a single task file'
    )
    
    parser.add_argument(
        '--directory', '-d',
        type=Path,
        help='Sync all task files in a directory'
    )
    
    parser.add_argument(
        '--project', '-p',
        type=Path,
        help='Sync all task files in a project\'s tasks directory'
    )
    
    parser.add_argument(
        '--issue', '-i',
        type=int,
        help='Sync specific GitHub issue number'
    )
    
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Show what would be synced without making changes'
    )
    
    parser.add_argument(
        '--conflict-resolution',
        choices=['manual', 'github_wins', 'local_wins'],
        default='manual',
        help='How to handle conflicts between local and GitHub states'
    )
    
    args = parser.parse_args()
    
    # Validate arguments
    if not any([args.file, args.directory, args.project, args.issue]):
        parser.error("Must specify --file, --directory, --project, or --issue")
    
    try:
        # Build configuration overrides
        config_overrides = {
            'dry_run': args.dry_run,
            'conflict_resolution': args.conflict_resolution
        }
        
        # Initialize the syncer
        syncer = GitHubStatusSyncer(**config_overrides)
        
        # Process based on arguments
        if args.file:
            if not args.file.exists():
                print(f"❌ File not found: {args.file}")
                sys.exit(1)
            
            result = syncer.sync_single_file(args.file)
            if not result['success']:
                print("❌ Sync failed!")
                sys.exit(1)
            else:
                print("✅ Sync complete!")
                
        elif args.directory:
            if not args.directory.exists():
                print(f"❌ Directory not found: {args.directory}")
                sys.exit(1)
            
            results = syncer.sync_directory(args.directory)
            if any(not r['success'] for r in results):
                print("❌ Some syncs failed!")
                sys.exit(1)
            
        elif args.project:
            if not args.project.exists():
                print(f"❌ Project not found: {args.project}")
                sys.exit(1)
            
            tasks_dir = args.project / "tasks"
            if not tasks_dir.exists():
                print(f"❌ Tasks directory not found: {tasks_dir}")
                sys.exit(1)
            
            results = syncer.sync_directory(tasks_dir)
            if any(not r['success'] for r in results):
                print("❌ Some syncs failed!")
                sys.exit(1)
        
        elif args.issue:
            # Find task file for specific issue
            task_files = []
            search_dirs = [Path.cwd(), Path.cwd() / "tasks"]
            
            for search_dir in search_dirs:
                if search_dir.exists():
                    task_files.extend(syncer.find_task_files_with_github_issues(search_dir))
            
            # Find the file that references this issue
            target_file = None
            for task_file in task_files:
                try:
                    task_data = syncer.parse_task_file(task_file)
                    if task_data['github_issue_number'] == args.issue:
                        target_file = task_file
                        break
                except Exception:
                    continue
            
            if not target_file:
                print(f"❌ No local task file found for GitHub issue #{args.issue}")
                sys.exit(1)
            
            result = syncer.sync_single_file(target_file)
            if not result['success']:
                print("❌ Sync failed!")
                sys.exit(1)
            else:
                print("✅ Sync complete!")
    
    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()