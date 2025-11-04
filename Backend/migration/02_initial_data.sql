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

INSERT INTO `tenant_pool` (`schema_name`, `is_available`) VALUES
('candyhire_tenant_1', TRUE), ('candyhire_tenant_2', TRUE), ('candyhire_tenant_3', TRUE), ('candyhire_tenant_4', TRUE), ('candyhire_tenant_5', TRUE),
('candyhire_tenant_6', TRUE), ('candyhire_tenant_7', TRUE), ('candyhire_tenant_8', TRUE), ('candyhire_tenant_9', TRUE), ('candyhire_tenant_10', TRUE),
('candyhire_tenant_11', TRUE), ('candyhire_tenant_12', TRUE), ('candyhire_tenant_13', TRUE), ('candyhire_tenant_14', TRUE), ('candyhire_tenant_15', TRUE),
('candyhire_tenant_16', TRUE), ('candyhire_tenant_17', TRUE), ('candyhire_tenant_18', TRUE), ('candyhire_tenant_19', TRUE), ('candyhire_tenant_20', TRUE),
('candyhire_tenant_21', TRUE), ('candyhire_tenant_22', TRUE), ('candyhire_tenant_23', TRUE), ('candyhire_tenant_24', TRUE), ('candyhire_tenant_25', TRUE),
('candyhire_tenant_26', TRUE), ('candyhire_tenant_27', TRUE), ('candyhire_tenant_28', TRUE), ('candyhire_tenant_29', TRUE), ('candyhire_tenant_30', TRUE),
('candyhire_tenant_31', TRUE), ('candyhire_tenant_32', TRUE), ('candyhire_tenant_33', TRUE), ('candyhire_tenant_34', TRUE), ('candyhire_tenant_35', TRUE),
('candyhire_tenant_36', TRUE), ('candyhire_tenant_37', TRUE), ('candyhire_tenant_38', TRUE), ('candyhire_tenant_39', TRUE), ('candyhire_tenant_40', TRUE),
('candyhire_tenant_41', TRUE), ('candyhire_tenant_42', TRUE), ('candyhire_tenant_43', TRUE), ('candyhire_tenant_44', TRUE), ('candyhire_tenant_45', TRUE),
('candyhire_tenant_46', TRUE), ('candyhire_tenant_47', TRUE), ('candyhire_tenant_48', TRUE), ('candyhire_tenant_49', TRUE), ('candyhire_tenant_50', TRUE);

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
