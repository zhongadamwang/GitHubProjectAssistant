-- Migration: 005_create_sync_history_table
-- Description: Records each GitHub synchronisation run result and GraphQL usage
-- Traceability: R-001, R-002; SyncHistory domain entity

CREATE TABLE IF NOT EXISTS `sync_history` (
    `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `project_id`            INT UNSIGNED     NOT NULL,
    `synced_at`             DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`                ENUM('success','failed','partial') NOT NULL,
    `issues_added`          INT UNSIGNED     NOT NULL DEFAULT 0,
    `issues_updated`        INT UNSIGNED     NOT NULL DEFAULT 0,
    `issues_removed`        INT UNSIGNED     NOT NULL DEFAULT 0,
    `graphql_points_used`   INT UNSIGNED     NOT NULL DEFAULT 0,
    `snapshot_file`         VARCHAR(255)     DEFAULT NULL,
    `error_message`         TEXT             DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sync_history_project_id` (`project_id`),
    KEY `idx_sync_history_synced_at` (`synced_at`),
    KEY `idx_sync_history_status` (`status`),
    CONSTRAINT `fk_sync_history_project_id`
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
