-- ============================================
-- Tenant Pool - 10 Pre-allocated Tenants
-- Generated on 2025-11-07 18:42:27
-- ============================================

-- Clean tenant_pool table to ensure fresh start
TRUNCATE TABLE \`tenant_pool\`;

INSERT INTO \`tenant_pool\` (\`tenant_id\`, \`is_available\`) VALUES
(1, TRUE), (2, TRUE), (3, TRUE), (4, TRUE), (5, TRUE), (6, TRUE), (7, TRUE), (8, TRUE), (9, TRUE), (10, TRUE);

-- ✅ 10 tenant pool entries created
