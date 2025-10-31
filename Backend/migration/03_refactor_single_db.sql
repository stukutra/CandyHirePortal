-- ============================================
-- Migration: Refactor to Single-Database Multi-Tenancy
-- Date: 2025-10-31
-- Description: Change from separate databases to tenant_id isolation
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Step 1: Update tenant_pool table
-- Replace schema_name with tenant_id
-- ============================================

-- Drop old tenant_pool table
DROP TABLE IF EXISTS `tenant_pool`;

-- Create new tenant_pool with tenant_id approach
CREATE TABLE `tenant_pool` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL UNIQUE COMMENT 'Unique tenant identifier (1-100)',
  `is_available` BOOLEAN DEFAULT TRUE,
  `company_id` VARCHAR(50) NULL,
  `assigned_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`company_id`) REFERENCES `companies_registered`(`id`) ON DELETE SET NULL,
  INDEX idx_tenant_available (`is_available`),
  INDEX idx_tenant_company (`company_id`),
  INDEX idx_tenant_id (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pool of available tenant IDs for single-database multi-tenancy';

-- ============================================
-- Step 2: Populate tenant pool with 100 tenant IDs
-- ============================================

INSERT INTO `tenant_pool` (`tenant_id`, `is_available`) VALUES
(1, TRUE), (2, TRUE), (3, TRUE), (4, TRUE), (5, TRUE),
(6, TRUE), (7, TRUE), (8, TRUE), (9, TRUE), (10, TRUE),
(11, TRUE), (12, TRUE), (13, TRUE), (14, TRUE), (15, TRUE),
(16, TRUE), (17, TRUE), (18, TRUE), (19, TRUE), (20, TRUE),
(21, TRUE), (22, TRUE), (23, TRUE), (24, TRUE), (25, TRUE),
(26, TRUE), (27, TRUE), (28, TRUE), (29, TRUE), (30, TRUE),
(31, TRUE), (32, TRUE), (33, TRUE), (34, TRUE), (35, TRUE),
(36, TRUE), (37, TRUE), (38, TRUE), (39, TRUE), (40, TRUE),
(41, TRUE), (42, TRUE), (43, TRUE), (44, TRUE), (45, TRUE),
(46, TRUE), (47, TRUE), (48, TRUE), (49, TRUE), (50, TRUE),
(51, TRUE), (52, TRUE), (53, TRUE), (54, TRUE), (55, TRUE),
(56, TRUE), (57, TRUE), (58, TRUE), (59, TRUE), (60, TRUE),
(61, TRUE), (62, TRUE), (63, TRUE), (64, TRUE), (65, TRUE),
(66, TRUE), (67, TRUE), (68, TRUE), (69, TRUE), (70, TRUE),
(71, TRUE), (72, TRUE), (73, TRUE), (74, TRUE), (75, TRUE),
(76, TRUE), (77, TRUE), (78, TRUE), (79, TRUE), (80, TRUE),
(81, TRUE), (82, TRUE), (83, TRUE), (84, TRUE), (85, TRUE),
(86, TRUE), (87, TRUE), (88, TRUE), (89, TRUE), (90, TRUE),
(91, TRUE), (92, TRUE), (93, TRUE), (94, TRUE), (95, TRUE),
(96, TRUE), (97, TRUE), (98, TRUE), (99, TRUE), (100, TRUE);

-- ============================================
-- Step 3: Update companies_registered table
-- Replace tenant_schema with tenant_id
-- ============================================

ALTER TABLE `companies_registered`
  DROP COLUMN `tenant_schema`,
  ADD COLUMN `tenant_id` INT NULL AFTER `subscription_end_date`,
  ADD CONSTRAINT `fk_company_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `tenant_pool`(`tenant_id`) ON DELETE SET NULL,
  ADD INDEX `idx_tenant_id` (`tenant_id`);

-- ============================================
-- Step 4: Update schema.sql (main schema file)
-- NOTE: This migration will be applied to existing DB
-- The main 01_schema.sql should be manually updated
-- ============================================

SET FOREIGN_KEY_CHECKS = 1;

-- Migration complete
SELECT 'Migration 03: Single-database multi-tenancy refactor completed successfully!' AS status;
