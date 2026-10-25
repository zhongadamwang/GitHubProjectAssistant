# Requirements Analysis Report

**Project**: PRJ-01 - Scrum Master Assistant  
**Source**: 04-02_GitHub-Integrated-Scrum-Dashboard-Requirements.md  
**Generated**: 2026-04-02T14:30:00Z  
**Total Requirements**: 12  

## Requirements

| ID | Section | Text | Tags | Confidence |
|----|---------|------|---------|------------|
| R-001 | Data Integration | System will periodically pull project and issue data from GitHub account through API | functional, integration | high |
| R-002 | Data Sync | System will obtain GitHub data in read-only mode and periodically update local database; GitHub is the source of truth, including the time-tracking hours (REQ-CHG-001) | functional, data | high |
| R-003 | Historical Data | Each data snapshot from synchronization will be preserved as historical version for tracking and auditing | functional, audit | high |
| R-004 | Custom Attributes | System will read estimated, remaining and actual hours from GitHub Project number fields ("Estimate Hours", "Remains Hours", "Actual Hours") for each Issue; the dashboard displays them read-only (REQ-CHG-001) | functional, extension | high |
| R-005 | Burndown Visualization | System will render ideal burndown curve based on sum of estimated time for all tasks | functional, visualization | high |
| R-006 | Real-time Progress | System will render actual burndown curve as team members update remaining time daily | functional, real-time | high |
| R-007 | Sprint Health Dashboard | Burndown chart serves as core dashboard for iteration health to determine project status | functional, dashboard | high |
| R-008 | Efficiency Analysis | System will quantify estimation accuracy by comparing estimated time vs actual time per member | functional, analytics | high |
| R-009 | Shared Hosting Deploy | System must be easily deployable to shared hosting environments | nonfunctional, deployment | high |
| R-010 | CI/CD Integration | System must support automated releases through GitHub Actions | nonfunctional, automation | high |
| R-011 | Lightweight Database | System should use text databases or embedded databases to avoid complex management | nonfunctional, architecture | medium |
| R-012 | Code Quality | Final code must have high readability, maintainability, and comprehensive documentation | nonfunctional, quality | high |
| R-013 | Admin-Managed GitHub Accounts | Admins add, edit, test and delete GitHub accounts and their access tokens in the application instead of `.env` (REQ-ADD-002, Phase 8) | functional, configuration | high |
| R-014 | Credential Protection | Tokens are stored only as authenticated ciphertext (key from `.env`), never returned by the API or written to logs; all configuration changes are audited (REQ-ADD-002, Phase 8) | nonfunctional, security | high |
| R-015 | Multiple Projects | Admins choose which GitHub Projects (organization or user) to track; per-project field mapping; untracking keeps data (REQ-ADD-003, Phase 8) | functional, scope | high |
| R-016 | Per-Project Sync | Cron and manual sync handle every tracked project independently, with failure isolation, rate-limit awareness and per-project snapshots/history (REQ-ADD-003, Phase 8) | functional, integration | high |
| R-017 | Project Selection | Users select the active project in one place; Dashboard, Issues, Members and Sync follow it and the choice is remembered (REQ-ADD-003, Phase 8) | functional, usability | high |

## Glossary Suspects
- GitHub API
- Burndown Chart
- Sprint
- Iteration
- CI/CD
- GitHub Actions
- Shared Hosting
- Embedded Database