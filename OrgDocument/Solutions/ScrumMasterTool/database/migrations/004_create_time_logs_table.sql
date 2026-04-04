-- Migration: 004_create_time_logs_table
-- Description: Creates append-only audit trail for time-tracking field changes on issues
-- Traceability: R-004, R-011; TimeLog domain entity

CREATE TABLE IF NOT EXISTS `time_logs` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `issue_id`      INT UNSIGNED     NOT NULL,
    `changed_by`    INT UNSIGNED     NOT NULL,
    `field_name`    ENUM('estimated_time','remaining_time','actual_time') NOT NULL,
    `old_value`     DECIMAL(8,2)     NOT NULL,
    `new_value`     DECIMAL(8,2)     NOT NULL,
    `changed_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_time_logs_issue_id` (`issue_id`),
    KEY `idx_time_logs_changed_by` (`changed_by`),
    KEY `idx_time_logs_issue_field` (`issue_id`, `field_name`),
    CONSTRAINT `fk_time_logs_issue_id`
        FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_time_logs_changed_by`
        FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
