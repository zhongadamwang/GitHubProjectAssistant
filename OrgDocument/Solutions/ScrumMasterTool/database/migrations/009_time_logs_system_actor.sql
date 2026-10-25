-- Migration: 009_time_logs_system_actor
-- Description: time_logs now also records changes made by the GitHub sync (no user),
--              so changed_by must allow NULL. The FK to users is kept for human actors.
-- Traceability: REQ-CHG-001 (T038)

ALTER TABLE `time_logs`
    MODIFY COLUMN `changed_by` INT UNSIGNED NULL DEFAULT NULL;
