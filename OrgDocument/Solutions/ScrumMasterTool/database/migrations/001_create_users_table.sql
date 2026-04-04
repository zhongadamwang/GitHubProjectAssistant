-- Migration: 001_create_users_table
-- Description: Creates the users table for authentication and role-based access
-- Traceability: R-003, R-011; TeamMember domain entity

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `email`         VARCHAR(255)     NOT NULL,
    `password_hash` VARCHAR(255)     NOT NULL,
    `display_name`  VARCHAR(100)     NOT NULL,
    `github_username` VARCHAR(100)   DEFAULT NULL,
    `role`          ENUM('admin','member') NOT NULL DEFAULT 'member',
    `estimation_accuracy_score` DECIMAL(5,2) DEFAULT NULL,
    `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_github_username` (`github_username`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
