# Domain Concepts Analysis

**Project**: PRJ-01 - Scrum Master Assistant  
**Generated**: 2026-04-02T14:40:00Z  
**Sources**: requirements.json, goals.json  
**Total Entities**: 8 | **Total Concepts**: 15 | **Confidence**: 93%

## Core Domain Entities

### 1. GitHubProject *(ENT-001)*
**Domain**: Project Management | **Confidence**: 95%

**Description**: GitHub project containing issues and metadata synchronized to local system

**Key Attributes**: 
- `project_id` - GitHub project unique identifier
- `sync_timestamp` - Last synchronization time with GitHub  
- `iteration` - Current sprint/iteration identifier

**Operations**:
- `syncWithGitHub()` - Pull latest project and issue data from GitHub API
- `generateBurndown()` - Calculate burndown chart data from issue time estimates

*Source: R-001:Data Integration, R-007:Sprint Health Dashboard*

---

### 2. EnhancedIssue *(ENT-002)*
**Domain**: Issue Management | **Confidence**: 95%

**Description**: GitHub issue enhanced with local time tracking attributes

**Key Attributes**:
- `estimated_time` - Initial time estimate in hours
- `remaining_time` - Current remaining work time in hours
- `actual_time` - Total time actually spent on issue
- `assignee` - GitHub username of assigned team member

**Operations**:
- `updateTimeTracking()` - Update estimated, remaining, or actual time values
- `calculateProgress()` - Calculate completion percentage based on time estimates

*Source: R-004:Custom Attributes*

---

### 3. BurndownChart *(ENT-003)* 
**Domain**: Analytics | **Confidence**: 95%

**Description**: Visual representation of sprint progress comparing ideal vs actual work remaining

**Key Attributes**:
- `ideal_curve` - Calculated ideal progress points
- `actual_curve` - Real progress based on remaining time updates

**Operations**:
- `generateIdealCurve()` - Calculate ideal burndown based on total estimated time
- `updateActualCurve()` - Update actual progress based on current remaining times

*Source: R-005:Burndown Visualization, R-006:Real-time Progress*

---

### 4. TeamMember *(ENT-004)*
**Domain**: Team Analytics | **Confidence**: 90%

**Description**: Individual contributor with time estimation and tracking capabilities

**Key Attributes**:
- `github_username` - GitHub account username
- `estimation_accuracy_score` - Historical accuracy of time estimates (0-100)

**Operations**:
- `calculateEstimationAccuracy()` - Analyze historical estimate vs actual time patterns
- `getEfficiencyReport()` - Generate member efficiency analysis report

*Source: R-008:Efficiency Analysis*

---

### 5. GitHubAPIClient *(ENT-007)*
**Domain**: Integration | **Confidence**: 95%

**Description**: Service responsible for GitHub API communication and rate limiting

**Key Attributes**:
- `rate_limit_remaining` - Current API calls remaining in rate limit window
- `last_request_time` - Timestamp of most recent API call

**Operations**:
- `fetchProjects()` - Retrieve all accessible GitHub projects
- `fetchIssues()` - Get issues for specific project with pagination
- `checkRateLimit()` - Verify API rate limit status before requests

*Source: R-001:Data Integration, R-002:Data Sync*

## Key Business Concepts

### Sprint Health *(CON-001)*
**Definition**: Overall assessment of sprint progress based on burndown analysis and team metrics  
**Synonyms**: iteration health, sprint status, project health  
*Source: R-007:Sprint Health Dashboard*

### Estimation Accuracy *(CON-002)*
**Definition**: Measure of how closely actual time matches initial estimates  
**Synonyms**: prediction accuracy, planning precision  
*Source: R-008:Efficiency Analysis*

### Data Synchronization *(CON-003)*
**Definition**: Process of maintaining consistency between GitHub and local data stores  
**Synonyms**: data sync, API synchronization  
*Source: R-002:Data Sync*

### Burndown Analysis *(CON-004)*
**Definition**: Visual and analytical comparison of planned vs actual work progress over time  
**Synonyms**: progress tracking, sprint analysis  
*Source: R-005:Burndown Visualization, R-006:Real-time Progress*

### Decision Support *(CON-005)*
**Definition**: System capability to provide data-driven insights for project management decisions  
**Synonyms**: analytics support, insight generation  
*Source: goals.json:goal_statement*

## Entity Relationships

### Core Relationships
- **GitHubProject** `contains` **EnhancedIssue** *(1:many)* - Project aggregates multiple enhanced issues
- **EnhancedIssue** `assigned_to` **TeamMember** *(1:1)* - Issue has single assignee for efficiency tracking
- **GitHubProject** `generates` **BurndownChart** *(1:many)* - Project creates burndown charts per sprint
- **Dashboard** `displays` **BurndownChart** *(1:many)* - Dashboard shows multiple chart visualizations
- **GitHubAPIClient** `creates` **SyncHistory** *(1:many)* - API client maintains synchronization audit trail

## Domain Areas Map

### **Project Management**
- GitHubProject

### **Analytics & Intelligence** 
- BurndownChart, TeamMember
- Estimation Accuracy, Burndown Analysis, Decision Support

### **Integration Layer**
- GitHubAPIClient, SyncHistory
- Data Synchronization

### **User Experience**
- Dashboard, EnhancedIssue 
- Sprint Health

### **Infrastructure** 
- DeploymentConfig

## Terminology Glossary

**GitHub API**: Application Programming Interface for accessing GitHub project and issue data  
**Burndown Chart**: Visual representation showing work remaining over time in a sprint  
**Shared Hosting**: Web hosting environment where resources are shared among multiple websites  
**CI/CD**: Continuous Integration and Continuous Deployment automation

---
**Domain Analysis Complete** | **Next Phase**: Collaboration Diagram Generation  
**Extraction Confidence**: 93% | **Ready for Visualization**: ✅