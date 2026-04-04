# Solutions Registry

**Organization**: GitHub Project Assistant  
**Last Updated**: 2026-04-03  
**Owner**: Architecture Team  

> This document is the authoritative registry of all organization-level applications and solutions.  
> Each solution has its own folder under `OrgDocument/Solutions/<SolutionName>/`.

---

## Registry

| Solution ID | Solution Name | Status | Description | Project Reference | Folder |
|-------------|---------------|--------|-------------|-------------------|--------|
| SOL-001 | ScrumMasterTool | Active — Phase 1 | GitHub-integrated Scrum burndown dashboard with sprint analytics, time tracking, and member efficiency reporting | [PRJ-01](../projects/01%20-%20Scrum%20master%20assistant/main.md) | [Solutions/ScrumMasterTool/](../Solutions/ScrumMasterTool/) |

---

## Adding a New Solution

When a new application is approved:

1. Create a folder `OrgDocument/Solutions/<SolutionName>/`
2. Add a `technical-architecture.md` inside the solution folder (solution-level, derived from the project analysis artifact)
3. Register it in this table
4. Link the corresponding project under `OrgDocument/projects/`
5. Ensure all task files reference `SolutionName` explicitly in their **Target Solution** field

## Naming Convention

- Solution names use **PascalCase**, no spaces: `ScrumMasterTool`, `HRPortal`, `BillingService`
- Solution folder name must match the `Solution Name` column exactly
