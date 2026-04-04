-- Migration: 006_create_burndown_daily_table
-- Description: Daily burndown snapshots per project and iteration for chart rendering
-- Traceability: R-005, R-006; BurndownChart domain entity

CREATE TABLE IF NOT EXISTS `burndown_daily` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `project_id`        INT UNSIGNED     NOT NULL,
    `iteration`         VARCHAR(100)     NOT NULL,
    `snapshot_date`     DATE             NOT NULL,
    `total_estimated`   DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `ideal_remaining`   DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `actual_remaining`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `open_count`        INT UNSIGNED     NOT NULL DEFAULT 0,
    `closed_count`      INT UNSIGNED     NOT NULL DEFAULT 0,
    `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_burndown_project_iteration_date` (`project_id`, `iteration`, `snapshot_date`),
    KEY `idx_burndown_project_id` (`project_id`),
    KEY `idx_burndown_iteration` (`iteration`),
    CONSTRAINT `fk_burndown_project_id`
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
