-- Migration: 007_alter_sync_history_project_id_nullable
-- Description: Makes sync_history.project_id nullable so failed syncs that
--              occur before project metadata is fetched can still write a
--              history record without violating the FK constraint.
-- Traceability: T012 integration test requirement

ALTER TABLE `sync_history`
    DROP FOREIGN KEY `fk_sync_history_project_id`;

ALTER TABLE `sync_history`
    MODIFY COLUMN `project_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `sync_history`
    ADD CONSTRAINT `fk_sync_history_project_id`
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;
