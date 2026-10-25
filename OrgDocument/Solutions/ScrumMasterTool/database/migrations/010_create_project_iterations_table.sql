-- Migration: 010_create_project_iterations_table
-- Description: Stores the GitHub iteration (sprint) configuration of a project so the API
--              can list sprints with their dates, including ones that have no issues yet.
-- Traceability: REQ-CHG-002 (T041)

CREATE TABLE IF NOT EXISTS `project_iterations` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `project_id`    INT UNSIGNED  NOT NULL,
    `title`         VARCHAR(100)  NOT NULL,
    `start_date`    DATE          NULL DEFAULT NULL,
    `duration_days` INT UNSIGNED  NULL DEFAULT NULL,
    `is_completed`  TINYINT(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_project_iterations_title` (`project_id`, `title`),
    CONSTRAINT `fk_project_iterations_project_id`
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
