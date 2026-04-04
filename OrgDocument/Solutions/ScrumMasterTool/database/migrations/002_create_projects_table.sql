-- Migration: 002_create_projects_table
-- Description: Creates the projects table mapping GitHub Projects v2 to local records
-- Traceability: R-001, R-002; GitHubProject domain entity

CREATE TABLE IF NOT EXISTS `projects` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `github_project_id` VARCHAR(100)     NOT NULL,
    `github_owner`      VARCHAR(100)     NOT NULL,
    `github_repo`       VARCHAR(100)     NOT NULL,
    `project_number`    INT UNSIGNED     NOT NULL,
    `name`              VARCHAR(255)     NOT NULL,
    `current_iteration` VARCHAR(100)     DEFAULT NULL,
    `sync_timestamp`    DATETIME         DEFAULT NULL,
    `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_projects_github_project_id` (`github_project_id`),
    KEY `idx_projects_owner_repo` (`github_owner`, `github_repo`),
    KEY `idx_projects_iteration` (`current_iteration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
