-- Migration: 011_create_github_accounts_and_audit_log
-- Description: Admin-managed GitHub accounts (token stored only as ciphertext) and an
--              append-only admin audit log (never contains secrets).
-- Traceability: REQ-ADD-002 (T046); R-013, R-014

CREATE TABLE IF NOT EXISTS `github_accounts` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `label`            VARCHAR(100)  NOT NULL,
    `owner_login`      VARCHAR(100)  NOT NULL,
    `owner_type`       ENUM('organization','user') NOT NULL DEFAULT 'organization',
    `graphql_url`      VARCHAR(255)  NOT NULL DEFAULT 'https://api.github.com/graphql',
    `token_ciphertext` TEXT          NULL DEFAULT NULL COMMENT 'base64 authenticated ciphertext of the access token',
    `token_nonce`      VARCHAR(64)   NULL DEFAULT NULL COMMENT 'base64 nonce / IV used for the ciphertext',
    `token_hint`       VARCHAR(8)    NULL DEFAULT NULL COMMENT 'last characters of the token, for display only',
    `status`           ENUM('unverified','ok','invalid','expired','insufficient_scope','unreachable','needs_reentry')
                                     NOT NULL DEFAULT 'unverified',
    `status_message`   VARCHAR(255)  NULL DEFAULT NULL,
    `last_verified_at` DATETIME      NULL DEFAULT NULL,
    `token_expires_at` DATETIME      NULL DEFAULT NULL,
    `created_by`       INT UNSIGNED  NULL DEFAULT NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_github_accounts_owner_endpoint` (`owner_login`, `graphql_url`),
    CONSTRAINT `fk_github_accounts_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_audit_log` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `actor_user_id` INT UNSIGNED  NULL DEFAULT NULL,
    `action`        VARCHAR(50)   NOT NULL,
    `entity`        VARCHAR(50)   NOT NULL,
    `entity_id`     INT UNSIGNED  NULL DEFAULT NULL,
    `detail`        TEXT          NULL DEFAULT NULL COMMENT 'JSON; must never contain secrets',
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_audit_created` (`created_at`),
    KEY `idx_admin_audit_entity` (`entity`, `entity_id`),
    CONSTRAINT `fk_admin_audit_actor`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
