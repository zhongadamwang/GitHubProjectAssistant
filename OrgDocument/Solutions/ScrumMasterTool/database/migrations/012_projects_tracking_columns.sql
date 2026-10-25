-- Migration: 012_projects_tracking_columns
-- Description: Link projects to a GitHub account and add tracking state, per-project
--              field mapping and last-sync status. Existing projects stay tracked.
-- Traceability: REQ-ADD-003 (T046); R-015, R-016

ALTER TABLE `projects`
    ADD COLUMN `account_id`       INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
    ADD COLUMN `is_tracked`       TINYINT(1)   NOT NULL DEFAULT 1 AFTER `current_iteration`,
    ADD COLUMN `archived_at`      DATETIME     NULL DEFAULT NULL AFTER `is_tracked`,
    ADD COLUMN `field_mapping`    TEXT         NULL DEFAULT NULL COMMENT 'JSON overrides: estimated, remaining, actual, iteration' AFTER `archived_at`,
    ADD COLUMN `last_sync_status` VARCHAR(20)  NULL DEFAULT NULL AFTER `field_mapping`,
    ADD COLUMN `last_sync_at`     DATETIME     NULL DEFAULT NULL AFTER `last_sync_status`,
    ADD KEY `idx_projects_account` (`account_id`),
    ADD KEY `idx_projects_tracked` (`is_tracked`),
    ADD CONSTRAINT `fk_projects_account`
        FOREIGN KEY (`account_id`) REFERENCES `github_accounts` (`id`) ON DELETE RESTRICT;
