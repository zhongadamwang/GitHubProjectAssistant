# Hierarchy Metadata Schema

## Table of Contents
1. [Schema Overview](#schema-overview)
2. [Top-Level Fields](#top-level-fields)
3. [Node Object](#node-object)
4. [Hierarchy Statistics](#hierarchy-statistics)
5. [Example Document](#example-document)
6. [Update Rules](#update-rules)

---

## Schema Overview

`hierarchy-metadata.json` tracks the full process decomposition tree rooted at a given process folder. One file lives at the root level; sub-folders may have their own scoped copies.

---

## Top-Level Fields

```json
{
  "schema_version": "1.2",
  "root_process": {
    "id": "string — unique process identifier (slug, e.g. 'customer-order-journey')",
    "name": "string — human-readable process name",
    "folder": "string — relative path to root folder",
    "level": 0
  },
  "generated_at": "ISO8601 timestamp",
  "last_updated": "ISO8601 timestamp",
  "complexity_thresholds": {
    "level_0_max_interactions": 7,
    "level_n_max_interactions": 12,
    "decomposition_candidate_min_interactions": 5,
    "note": "Override any value to customise warning thresholds for this hierarchy."
  },
  "nodes": { },
  "hierarchy_statistics": { },
  "performance_baseline": {
    "last_benchmarked": "ISO8601 date — date of most recent T16 benchmark run",
    "schema_version": "string — schema version at time of benchmark",
    "median_decomp_time_s": "number — median single-decomposition elapsed time in seconds",
    "worst_case_decomp_time_s": "number — worst observed single-decomposition time in seconds",
    "level_5_cascade_total_s": "number — total elapsed time for 5-level cascade in seconds",
    "link_integrity_20_nodes_s": "number — link-integrity check time for a 20-node tree in seconds",
    "vs_code_render_pass_rate": "string — fraction of VS Code rendering tests that passed (e.g. '7/7')",
    "regression_threshold_pct": "number — percentage increase above baseline that triggers a regression alert (default: 20)",
    "note": "Optional. Populate after running performance benchmarks (T16). Used for regression detection."
  }
}
```

> **`complexity_thresholds`** (optional, defaults applied when absent):  
> - `level_0_max_interactions` — interaction count above which a Level 0 diagram triggers a complexity warning (default: 7)  
> - `level_n_max_interactions` — interaction count above which a Level N (N ≥ 1) diagram triggers a complexity warning (default: 12)  
> - `decomposition_candidate_min_interactions` — minimum interactions for a control-type participant to be flagged as a decomposition candidate (default: 5)

---

## Node Object

Each key in `nodes` is the participant's identifier (PascalCase slug). Each value:

```json
{
  "id": "string — PascalCase slug, e.g. 'OrderService'",
  "label": "string — human-readable display name, e.g. 'Order Management Service'",
  "type": "actor | boundary | control | entity",
  "level": "number — 0-based depth in hierarchy",
  "status": "available | decomposed | leaf",
  "folder": "string — relative path to this node's folder (null for non-decomposed nodes)",
  "parent_id": "string | null — id of parent node (null for root)",
  "children": ["array of child node ids"],
  "source_diagram": "string — relative path to the collaboration.md where this participant is defined",
  "decomposition_link": "string | null — relative path to child collaboration.md (set when status=decomposed)",
  "complexity_metrics": {
    "interaction_count": "number — total message sends/receives in this node's source_diagram",
    "participant_count": "number — total participants in this node's source_diagram",
    "nesting_depth": "number — number of nested box levels in this node's source_diagram",
    "complexity_warning": "none | advisory | critical",
    "decomposition_candidates": ["array of participant ids that exceed decomposition_candidate_min_interactions threshold"]
  }
}
```

**Status values:**
- `available` — control-type, not yet decomposed; eligible for decomposition
- `decomposed` — decomposed into a child sub-process
- `leaf` — non-control type (actor, boundary, entity) or a control that will not be further decomposed

**`complexity_metrics.complexity_warning` values:**
- `none` — interaction count is within threshold
- `advisory` — interaction count exceeds 80 % of the threshold (early warning)
- `critical` — interaction count exceeds the threshold (action recommended)

---

## Hierarchy Statistics

```json
{
  "hierarchy_statistics": {
    "total_nodes": "number",
    "max_depth": "number — deepest level index",
    "leaf_count": "number — nodes with no children",
    "decomposed_count": "number — nodes with status='decomposed'",
    "available_count": "number — control nodes with status='available'",
    "boundary_count": "number — nodes with type='boundary'",
    "nodes_by_level": {
      "0": "number",
      "1": "number",
      "2": "number"
    },
    "breadth_at_deepest_level": "number",
    "scale_management": {
      "critical_warnings": ["array of node ids with complexity_warning='critical'"],
      "advisory_warnings": ["array of node ids with complexity_warning='advisory'"],
      "decomposition_candidates": ["array of node ids flagged across all levels"],
      "total_interactions_by_level": {
        "0": "number",
        "1": "number"
      }
    }
  }
}
```

---

## Example Document

```json
{
  "schema_version": "1.2",
  "root_process": {
    "id": "customer-order-journey",
    "name": "Customer Order Journey",
    "folder": "01-CustomerOrderJourney",
    "level": 0
  },
  "generated_at": "2026-03-14T10:00:00Z",
  "last_updated": "2026-03-14T12:30:00Z",
  "complexity_thresholds": {
    "level_0_max_interactions": 7,
    "level_n_max_interactions": 12,
    "decomposition_candidate_min_interactions": 5
  },
  "nodes": {
    "Customer": {
      "id": "Customer",
      "label": "Customer",
      "type": "actor",
      "level": 0,
      "status": "leaf",
      "folder": null,
      "parent_id": null,
      "children": [],
      "source_diagram": "collaboration.md",
      "decomposition_link": null,
      "complexity_metrics": null
    },
    "ECommercePlatform": {
      "id": "ECommercePlatform",
      "label": "E-commerce Platform",
      "type": "control",
      "level": 0,
      "status": "decomposed",
      "folder": "01-EcommercePlatformBoundary",
      "parent_id": null,
      "children": ["WebFrontend", "CartService", "OrderService", "InventoryService"],
      "source_diagram": "collaboration.md",
      "decomposition_link": "01-EcommercePlatformBoundary/collaboration.md",
      "complexity_metrics": {
        "interaction_count": 6,
        "participant_count": 5,
        "nesting_depth": 1,
        "complexity_warning": "advisory",
        "decomposition_candidates": []
      }
    },
    "WebFrontend": {
      "id": "WebFrontend",
      "label": "Web Frontend",
      "type": "boundary",
      "level": 1,
      "status": "leaf",
      "folder": null,
      "parent_id": "ECommercePlatform",
      "children": [],
      "source_diagram": "01-EcommercePlatformBoundary/collaboration.md",
      "decomposition_link": null,
      "complexity_metrics": null
    },
    "OrderService": {
      "id": "OrderService",
      "label": "Order Management Service",
      "type": "control",
      "level": 1,
      "status": "decomposed",
      "folder": "01-EcommercePlatformBoundary/01-OrderManagementBoundary",
      "parent_id": "ECommercePlatform",
      "children": ["OrderAPI", "OrderValidator", "OrderEngine", "OrderRepository"],
      "source_diagram": "01-EcommercePlatformBoundary/collaboration.md",
      "decomposition_link": "01-EcommercePlatformBoundary/01-OrderManagementBoundary/collaboration.md",
      "complexity_metrics": {
        "interaction_count": 14,
        "participant_count": 4,
        "nesting_depth": 1,
        "complexity_warning": "critical",
        "decomposition_candidates": ["OrderEngine"]
      }
    },
    "CartService": {
      "id": "CartService",
      "label": "Shopping Cart Service",
      "type": "control",
      "level": 1,
      "status": "available",
      "folder": null,
      "parent_id": "ECommercePlatform",
      "children": [],
      "source_diagram": "01-EcommercePlatformBoundary/collaboration.md",
      "decomposition_link": null,
      "complexity_metrics": {
        "interaction_count": 8,
        "participant_count": 3,
        "nesting_depth": 1,
        "complexity_warning": "none",
        "decomposition_candidates": []
      }
    },
    "OrderAPI": {
      "id": "OrderAPI",
      "label": "Order API",
      "type": "boundary",
      "level": 2,
      "status": "leaf",
      "folder": null,
      "parent_id": "OrderService",
      "children": [],
      "source_diagram": "01-EcommercePlatformBoundary/01-OrderManagementBoundary/collaboration.md",
      "decomposition_link": null,
      "complexity_metrics": null
    },
    "OrderEngine": {
      "id": "OrderEngine",
      "label": "Order Processing Engine",
      "type": "control",
      "level": 2,
      "status": "available",
      "folder": null,
      "parent_id": "OrderService",
      "children": [],
      "source_diagram": "01-EcommercePlatformBoundary/01-OrderManagementBoundary/collaboration.md",
      "decomposition_link": null,
      "complexity_metrics": {
        "interaction_count": 9,
        "participant_count": 4,
        "nesting_depth": 1,
        "complexity_warning": "none",
        "decomposition_candidates": []
      }
    }
  },
  "hierarchy_statistics": {
    "total_nodes": 7,
    "max_depth": 2,
    "leaf_count": 4,
    "decomposed_count": 2,
    "available_count": 2,
    "boundary_count": 2,
    "nodes_by_level": {
      "0": 2,
      "1": 4,
      "2": 4
    },
    "breadth_at_deepest_level": 4,
    "scale_management": {
      "critical_warnings": ["OrderService"],
      "advisory_warnings": ["ECommercePlatform"],
      "decomposition_candidates": ["OrderEngine"],
      "total_interactions_by_level": {
        "0": 6,
        "1": 22,
        "2": 9
      }
    }
  },
  "performance_baseline": {
    "last_benchmarked": "2026-03-15",
    "schema_version": "1.2",
    "median_decomp_time_s": 11,
    "worst_case_decomp_time_s": 14,
    "level_5_cascade_total_s": 58,
    "link_integrity_20_nodes_s": 18,
    "vs_code_render_pass_rate": "7/7",
    "regression_threshold_pct": 20
  }
}
```

---

## Update Rules

When a decomposition is performed, update `hierarchy-metadata.json` as follows:

1. **Parent node**: set `status` → `"decomposed"`, set `folder`, set `decomposition_link`, append child IDs to `children`
2. **New child nodes**: add one node entry per participant in the new sub-process diagram (using stereotype classification to assign `type`)
3. **Statistics**: recompute all `hierarchy_statistics` fields from the updated `nodes` map, including `scale_management` aggregates
4. **`complexity_metrics`**: calculate for all new control-type nodes; set `null` for actor/boundary/entity leaf nodes
5. **`last_updated`**: set to current ISO8601 timestamp

When a rollback is performed:
1. Remove all child node entries that were added by the decomposition
2. Reset parent node: `status` → `"available"`, `folder` → `null`, `decomposition_link` → `null`, `children` → `[]`
3. Recompute `hierarchy_statistics` (including `scale_management`)
4. Update `last_updated`
