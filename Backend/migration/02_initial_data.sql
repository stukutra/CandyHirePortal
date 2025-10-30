-- ============================================
-- CandyHire Portal Initial Data
-- ============================================

SET NAMES utf8mb4;

-- ============================================
-- Insert Tenant Pool (4 available schemas)
-- ============================================
INSERT INTO `tenant_pool` (`schema_name`, `is_available`) VALUES
('candyhire_tenant_1', TRUE),
('candyhire_tenant_2', TRUE),
('candyhire_tenant_3', TRUE),
('candyhire_tenant_4', TRUE)
ON DUPLICATE KEY UPDATE `schema_name` = VALUES(`schema_name`);

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
