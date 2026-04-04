-- Migration: 003_create_issues_table
-- Description: Creates the issues table for GitHub issues augmented with time-tracking fields
-- Traceability: R-003, R-004; EnhancedIssue domain entity

CREATE TABLE IF NOT EXISTS `issues` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `project_id`        INT UNSIGNED     NOT NULL,
    `github_issue_id`   VARCHAR(100)     NOT NULL,
    `title`             VARCHAR(512)     NOT NULL,
    `status`            VARCHAR(100)     NOT NULL DEFAULT 'open',
    `assignee`          VARCHAR(100)     DEFAULT NULL,
    `labels`            JSON             DEFAULT NULL,
    `iteration`         VARCHAR(100)     DEFAULT NULL,
    `estimated_time`    DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `remaining_time`    DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `actual_time`       DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
    `github_updated_at` DATETIME         DEFAULT NULL,
    `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_issues_project_github` (`project_id`, `github_issue_id`),
    KEY `idx_issues_project_id` (`project_id`),
    KEY `idx_issues_assignee` (`assignee`),
    KEY `idx_issues_iteration` (`iteration`),
    KEY `idx_issues_status` (`status`),
    KEY `idx_issues_project_iteration` (`project_id`, `iteration`),
    CONSTRAINT `fk_issues_project_id`
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
