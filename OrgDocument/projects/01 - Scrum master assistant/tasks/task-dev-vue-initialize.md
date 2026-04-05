# T018 — Initialize Vue 3 + Vite Project

**Task ID**: T018  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Scaffold the Vue 3 frontend project with Vite, Vue Router, Pinia, Chart.js, and Axios. The frontend lives under `OrgDocument/Solutions/ScrumMasterTool/frontend/` and its production build outputs to `../public/dist/` so it is served by the same PHP entry point on cPanel.

### Acceptance Criteria
- [ ] `frontend/package.json` declares dependencies: `vue@3`, `vue-router@4`, `pinia`, `chart.js@4`, `axios`
- [ ] `frontend/vite.config.js` sets `build.outDir` to `../public/dist` (relative to `frontend/`)
- [ ] Dev server proxy configured: `/api/*` → `http://localhost:8080` (PHP dev server)
- [ ] `npm run build` completes without errors and produces `public/dist/index.html`
- [ ] `frontend/src/App.vue` contains `<router-view>` as the only content
- [ ] `frontend/src/main.js` mounts `App`, registers `router` and `pinia`
- [ ] `frontend/src/router/index.js` exports a router with at least a placeholder `/login` route
- [ ] `.gitignore` excludes `frontend/node_modules/` and `frontend/dist/`

### Tasks/Subtasks
- [ ] Run `npm create vite@latest frontend -- --template vue` inside `OrgDocument/Solutions/ScrumMasterTool/`
- [ ] Install additional dependencies: `npm install vue-router@4 pinia chart.js@4 axios`
- [ ] Update `vite.config.js` — add `server.proxy` and `build.outDir`
- [ ] Create `frontend/src/router/index.js` — `createRouter` with `createWebHistory`; stub `/login` route
- [ ] Create `frontend/src/stores/` directory (empty placeholder for Pinia stores)
- [ ] Create `frontend/src/services/` directory (empty placeholder for API service)
- [ ] Update `frontend/src/App.vue` — replace boilerplate with `<router-view>`
- [ ] Update `frontend/src/main.js` — import and use `router` and `pinia`
- [ ] Update `.gitignore` at solution root to exclude `frontend/node_modules/` and `public/dist/`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] `npm run dev` starts dev server on port 5173 with working `/api` proxy
- [ ] `npm run build` produces `public/dist/index.html` without errors
- [ ] Project structure matches `frontend/src/{views,components,stores,services,router}` layout

### Dependencies
- Phase 2 (T007–T012) — GitHub sync APIs functional (needed for Phase 4 as a whole, not T018 scaffold itself)
- Phase 3 (T013–T017) — Analytics APIs functional

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — All other Phase 4 tasks depend on this scaffold

### Labels/Tags
- Category: development
- Component: frontend, scaffold
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Place frontend under `OrgDocument/Solutions/ScrumMasterTool/frontend/` not a separate repo
- Use Composition API throughout (`<script setup>`)
- No TypeScript for v1 (plain `.js` / `.vue`) — keep stack simple for cPanel shared hosting context
- Source Requirements: R-012, ADR-3

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
