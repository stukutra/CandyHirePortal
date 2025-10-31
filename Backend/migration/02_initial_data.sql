-- ============================================
-- CandyHire Portal Initial Data
-- ============================================

SET NAMES utf8mb4;

-- ============================================
-- Insert Tenant Pool (100 available tenant IDs)
-- Single-database multi-tenancy approach
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
(96, TRUE), (97, TRUE), (98, TRUE), (99, TRUE), (100, TRUE)
ON DUPLICATE KEY UPDATE `tenant_id` = VALUES(`tenant_id`);

-- ============================================
-- Insert Default Admin User
-- Username: admin
-- Password: Admin123! (CHANGE THIS IN PRODUCTION!)
-- ============================================
INSERT INTO `admin_users`
(`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_active`)
VALUES
(
  'admin-001',
  'admin',
  'admin@candyhire.com',
  -- Password: Admin123! (bcrypt hash)
  '$2y$12$jww8fM.wv4Ae2hvavL03Q.LZWrpHZvFOxNvLzPlLVAEg3jjnlQK4G',
  'System',
  'Administrator',
  'super_admin',
  TRUE
)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Note: In production, change the admin password immediately!
