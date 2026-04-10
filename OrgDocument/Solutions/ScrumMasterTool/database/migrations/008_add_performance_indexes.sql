-- Migration: 008_add_performance_indexes
-- Description: Defensive performance index guard — confirms all query-critical indexes exist.
--              All indexes listed below were already created by migrations 003, 004, and 006.
--              This migration adds them via INFORMATION_SCHEMA checks so the migration is
--              idempotent on any database state (fresh install or upgraded from earlier backups
--              that pre-date those migrations).
-- Traceability: T033 (Performance Optimization & Testing); R-011, R-012

-- ============================================================================
-- Helper procedure: add_index_if_missing
-- Checks INFORMATION_SCHEMA.STATISTICS before executing ALTER TABLE.
-- Compatible with MySQL 5.7 and 8.0.
-- ============================================================================
DROP PROCEDURE IF EXISTS `add_index_if_missing`;

DELIMITER $$

CREATE PROCEDURE `add_index_if_missing`(
    IN  p_schema   VARCHAR(64),
    IN  p_table    VARCHAR(64),
    IN  p_index    VARCHAR(64),
    IN  p_ddl      TEXT
)
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
      FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = p_schema
       AND TABLE_NAME   = p_table
       AND INDEX_NAME   = p_index;

    IF v_count = 0 THEN
        SET @sql_stmt = p_ddl;
        PREPARE stmt FROM @sql_stmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- issues table — project_id  (created in migration 003)
-- ============================================================================
CALL `add_index_if_missing`(
    DATABASE(),
    'issues',
    'idx_issues_project_id',
    'ALTER TABLE `issues` ADD INDEX `idx_issues_project_id` (`project_id`)'
);

-- ============================================================================
-- issues table — (project_id, iteration) composite  (created in migration 003)
-- ============================================================================
CALL `add_index_if_missing`(
    DATABASE(),
    'issues',
    'idx_issues_project_iteration',
    'ALTER TABLE `issues` ADD INDEX `idx_issues_project_iteration` (`project_id`, `iteration`)'
);

-- ============================================================================
-- burndown_daily table — (project_id, iteration, snapshot_date) composite
-- Already covered by the UNIQUE KEY `uq_burndown_project_iteration_date`
-- (created in migration 006) which MySQL uses as a B-tree index.
-- This guard adds an explicit non-unique read index under a distinct name so
-- that EXPLAIN OUTPUT clearly identifies it in query plans.
-- ============================================================================
CALL `add_index_if_missing`(
    DATABASE(),
    'burndown_daily',
    'idx_burndown_project_iteration_date',
    'ALTER TABLE `burndown_daily` ADD INDEX `idx_burndown_project_iteration_date` (`project_id`, `iteration`, `snapshot_date`)'
);

-- ============================================================================
-- time_logs table — issue_id  (created in migration 004)
-- ============================================================================
CALL `add_index_if_missing`(
    DATABASE(),
    'time_logs',
    'idx_time_logs_issue_id',
    'ALTER TABLE `time_logs` ADD INDEX `idx_time_logs_issue_id` (`issue_id`)'
);

-- ============================================================================
-- Cleanup
-- ============================================================================
DROP PROCEDURE IF EXISTS `add_index_if_missing`;
