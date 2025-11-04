-- ============================================
-- CandyHire Portal Initial Data
-- ============================================

SET NAMES utf8mb4;

-- ============================================
-- Tenant Pool - PRE-CREATED TENANTS
-- ============================================
-- 50 tenant databases are pre-created with empty schema during setup.
-- When a company completes payment, one tenant is assigned from the pool.
-- ============================================

-- Clean tenant_pool table to ensure fresh start
TRUNCATE TABLE `tenant_pool`;

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
(46, TRUE), (47, TRUE), (48, TRUE), (49, TRUE), (50, TRUE);

-- ============================================
-- Insert Default Admin User
-- Username: admin
-- Password: Admin123! (CHANGE THIS IN PRODUCTION!)
-- ============================================

-- Clean admin_users table to ensure fresh start
TRUNCATE TABLE `admin_users`;

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
);

-- Note: In production, change the admin password immediately!

-- ============================================
-- Insert Countries with VAT information
-- ============================================

-- Clean countries table to ensure fresh start
TRUNCATE TABLE `countries`;

INSERT INTO countries (code, name, name_it, name_es, name_en, has_vat, vat_label, requires_sdi, currency, phone_prefix, is_eu) VALUES
-- Italy
('IT', 'Italy', 'Italia', 'Italia', 'Italy', TRUE, 'Partita IVA', TRUE, 'EUR', '+39', TRUE),
-- Spain
('ES', 'Spain', 'Spagna', 'España', 'Spain', TRUE, 'NIF/CIF', FALSE, 'EUR', '+34', TRUE),
-- France
('FR', 'France', 'Francia', 'Francia', 'France', TRUE, 'Numéro TVA', FALSE, 'EUR', '+33', TRUE),
-- Germany
('DE', 'Germany', 'Germania', 'Alemania', 'Germany', TRUE, 'USt-IdNr.', FALSE, 'EUR', '+49', TRUE),
-- UK
('GB', 'United Kingdom', 'Regno Unito', 'Reino Unido', 'United Kingdom', TRUE, 'VAT Number', FALSE, 'GBP', '+44', FALSE),
-- Other EU countries
('AT', 'Austria', 'Austria', 'Austria', 'Austria', TRUE, 'UID-Nummer', FALSE, 'EUR', '+43', TRUE),
('BE', 'Belgium', 'Belgio', 'Bélgica', 'Belgium', TRUE, 'BTW-nummer', FALSE, 'EUR', '+32', TRUE),
('BG', 'Bulgaria', 'Bulgaria', 'Bulgaria', 'Bulgaria', TRUE, 'ДДС номер', FALSE, 'BGN', '+359', TRUE),
('HR', 'Croatia', 'Croazia', 'Croacia', 'Croatia', TRUE, 'OIB', FALSE, 'EUR', '+385', TRUE),
('CY', 'Cyprus', 'Cipro', 'Chipre', 'Cyprus', TRUE, 'ΦΠΑ', FALSE, 'EUR', '+357', TRUE),
('CZ', 'Czech Republic', 'Rep. Ceca', 'República Checa', 'Czech Republic', TRUE, 'DIČ', FALSE, 'CZK', '+420', TRUE),
('DK', 'Denmark', 'Danimarca', 'Dinamarca', 'Denmark', TRUE, 'CVR-nummer', FALSE, 'DKK', '+45', TRUE),
('EE', 'Estonia', 'Estonia', 'Estonia', 'Estonia', TRUE, 'KMKR number', FALSE, 'EUR', '+372', TRUE),
('FI', 'Finland', 'Finlandia', 'Finlandia', 'Finland', TRUE, 'ALV-numero', FALSE, 'EUR', '+358', TRUE),
('GR', 'Greece', 'Grecia', 'Grecia', 'Greece', TRUE, 'ΑΦΜ', FALSE, 'EUR', '+30', TRUE),
('HU', 'Hungary', 'Ungheria', 'Hungría', 'Hungary', TRUE, 'Adószám', FALSE, 'HUF', '+36', TRUE),
('IE', 'Ireland', 'Irlanda', 'Irlanda', 'Ireland', TRUE, 'VAT Number', FALSE, 'EUR', '+353', TRUE),
('LV', 'Latvia', 'Lettonia', 'Letonia', 'Latvia', TRUE, 'PVN', FALSE, 'EUR', '+371', TRUE),
('LT', 'Lithuania', 'Lituania', 'Lituania', 'Lithuania', TRUE, 'PVM', FALSE, 'EUR', '+370', TRUE),
('LU', 'Luxembourg', 'Lussemburgo', 'Luxemburgo', 'Luxembourg', TRUE, 'No. TVA', FALSE, 'EUR', '+352', TRUE),
('MT', 'Malta', 'Malta', 'Malta', 'Malta', TRUE, 'VAT Number', FALSE, 'EUR', '+356', TRUE),
('NL', 'Netherlands', 'Paesi Bassi', 'Países Bajos', 'Netherlands', TRUE, 'BTW-nummer', FALSE, 'EUR', '+31', TRUE),
('PL', 'Poland', 'Polonia', 'Polonia', 'Poland', TRUE, 'NIP', FALSE, 'PLN', '+48', TRUE),
('PT', 'Portugal', 'Portogallo', 'Portugal', 'Portugal', TRUE, 'NIF', FALSE, 'EUR', '+351', TRUE),
('RO', 'Romania', 'Romania', 'Rumanía', 'Romania', TRUE, 'CIF', FALSE, 'RON', '+40', TRUE),
('SK', 'Slovakia', 'Slovacchia', 'Eslovaquia', 'Slovakia', TRUE, 'IČ DPH', FALSE, 'EUR', '+421', TRUE),
('SI', 'Slovenia', 'Slovenia', 'Eslovenia', 'Slovenia', TRUE, 'ID za DDV', FALSE, 'EUR', '+386', TRUE),
('SE', 'Sweden', 'Svezia', 'Suecia', 'Sweden', TRUE, 'Momsnr', FALSE, 'SEK', '+46', TRUE),
-- Non-EU countries
('CH', 'Switzerland', 'Svizzera', 'Suiza', 'Switzerland', TRUE, 'MWST-Nummer', FALSE, 'CHF', '+41', FALSE),
('NO', 'Norway', 'Norvegia', 'Noruega', 'Norway', TRUE, 'MVA-nummer', FALSE, 'NOK', '+47', FALSE),
('US', 'United States', 'Stati Uniti', 'Estados Unidos', 'United States', FALSE, 'Tax ID', FALSE, 'USD', '+1', FALSE),
('CA', 'Canada', 'Canada', 'Canadá', 'Canada', TRUE, 'GST/HST', FALSE, 'CAD', '+1', FALSE),
('AU', 'Australia', 'Australia', 'Australia', 'Australia', TRUE, 'ABN', FALSE, 'AUD', '+61', FALSE),
('JP', 'Japan', 'Giappone', 'Japón', 'Japan', TRUE, '法人番号', FALSE, 'JPY', '+81', FALSE),
('CN', 'China', 'Cina', 'China', 'China', TRUE, '纳税人识别号', FALSE, 'CNY', '+86', FALSE),
('BR', 'Brazil', 'Brasile', 'Brasil', 'Brazil', TRUE, 'CNPJ', FALSE, 'BRL', '+55', FALSE),
('IN', 'India', 'India', 'India', 'India', TRUE, 'GST Number', FALSE, 'INR', '+91', FALSE),
('MX', 'Mexico', 'Messico', 'México', 'Mexico', TRUE, 'RFC', FALSE, 'MXN', '+52', FALSE);

-- ============================================
-- Populate User Directory with Company Admins
-- ============================================
-- This ensures that company admins from companies_registered
-- are automatically added to user_directory for O(1) login lookup
-- ============================================

-- Clean user_directory table to ensure fresh start
TRUNCATE TABLE `user_directory`;

-- Populate user_directory with existing company admins that have been assigned a tenant
INSERT INTO user_directory (email, tenant_id, user_type, user_id, is_active)
SELECT email, tenant_id, 'company_admin', id, is_active
FROM companies_registered
WHERE tenant_id IS NOT NULL AND registration_status = 'active';
