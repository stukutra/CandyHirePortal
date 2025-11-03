-- ============================================
-- CandyHire Portal Initial Data
-- ============================================

SET NAMES utf8mb4;

-- ============================================
-- Insert Tenant Pool (100 available tenant schemas)
-- Single-database multi-tenancy approach
-- ============================================
INSERT INTO `tenant_pool` (`schema_name`, `is_available`) VALUES
('CandyHire_Tenant_1', TRUE), ('CandyHire_Tenant_2', TRUE), ('CandyHire_Tenant_3', TRUE), ('CandyHire_Tenant_4', TRUE), ('CandyHire_Tenant_5', TRUE),
('CandyHire_Tenant_6', TRUE), ('CandyHire_Tenant_7', TRUE), ('CandyHire_Tenant_8', TRUE), ('CandyHire_Tenant_9', TRUE), ('CandyHire_Tenant_10', TRUE),
('CandyHire_Tenant_11', TRUE), ('CandyHire_Tenant_12', TRUE), ('CandyHire_Tenant_13', TRUE), ('CandyHire_Tenant_14', TRUE), ('CandyHire_Tenant_15', TRUE),
('CandyHire_Tenant_16', TRUE), ('CandyHire_Tenant_17', TRUE), ('CandyHire_Tenant_18', TRUE), ('CandyHire_Tenant_19', TRUE), ('CandyHire_Tenant_20', TRUE),
('CandyHire_Tenant_21', TRUE), ('CandyHire_Tenant_22', TRUE), ('CandyHire_Tenant_23', TRUE), ('CandyHire_Tenant_24', TRUE), ('CandyHire_Tenant_25', TRUE),
('CandyHire_Tenant_26', TRUE), ('CandyHire_Tenant_27', TRUE), ('CandyHire_Tenant_28', TRUE), ('CandyHire_Tenant_29', TRUE), ('CandyHire_Tenant_30', TRUE),
('CandyHire_Tenant_31', TRUE), ('CandyHire_Tenant_32', TRUE), ('CandyHire_Tenant_33', TRUE), ('CandyHire_Tenant_34', TRUE), ('CandyHire_Tenant_35', TRUE),
('CandyHire_Tenant_36', TRUE), ('CandyHire_Tenant_37', TRUE), ('CandyHire_Tenant_38', TRUE), ('CandyHire_Tenant_39', TRUE), ('CandyHire_Tenant_40', TRUE),
('CandyHire_Tenant_41', TRUE), ('CandyHire_Tenant_42', TRUE), ('CandyHire_Tenant_43', TRUE), ('CandyHire_Tenant_44', TRUE), ('CandyHire_Tenant_45', TRUE),
('CandyHire_Tenant_46', TRUE), ('CandyHire_Tenant_47', TRUE), ('CandyHire_Tenant_48', TRUE), ('CandyHire_Tenant_49', TRUE), ('CandyHire_Tenant_50', TRUE),
('CandyHire_Tenant_51', TRUE), ('CandyHire_Tenant_52', TRUE), ('CandyHire_Tenant_53', TRUE), ('CandyHire_Tenant_54', TRUE), ('CandyHire_Tenant_55', TRUE),
('CandyHire_Tenant_56', TRUE), ('CandyHire_Tenant_57', TRUE), ('CandyHire_Tenant_58', TRUE), ('CandyHire_Tenant_59', TRUE), ('CandyHire_Tenant_60', TRUE),
('CandyHire_Tenant_61', TRUE), ('CandyHire_Tenant_62', TRUE), ('CandyHire_Tenant_63', TRUE), ('CandyHire_Tenant_64', TRUE), ('CandyHire_Tenant_65', TRUE),
('CandyHire_Tenant_66', TRUE), ('CandyHire_Tenant_67', TRUE), ('CandyHire_Tenant_68', TRUE), ('CandyHire_Tenant_69', TRUE), ('CandyHire_Tenant_70', TRUE),
('CandyHire_Tenant_71', TRUE), ('CandyHire_Tenant_72', TRUE), ('CandyHire_Tenant_73', TRUE), ('CandyHire_Tenant_74', TRUE), ('CandyHire_Tenant_75', TRUE),
('CandyHire_Tenant_76', TRUE), ('CandyHire_Tenant_77', TRUE), ('CandyHire_Tenant_78', TRUE), ('CandyHire_Tenant_79', TRUE), ('CandyHire_Tenant_80', TRUE),
('CandyHire_Tenant_81', TRUE), ('CandyHire_Tenant_82', TRUE), ('CandyHire_Tenant_83', TRUE), ('CandyHire_Tenant_84', TRUE), ('CandyHire_Tenant_85', TRUE),
('CandyHire_Tenant_86', TRUE), ('CandyHire_Tenant_87', TRUE), ('CandyHire_Tenant_88', TRUE), ('CandyHire_Tenant_89', TRUE), ('CandyHire_Tenant_90', TRUE),
('CandyHire_Tenant_91', TRUE), ('CandyHire_Tenant_92', TRUE), ('CandyHire_Tenant_93', TRUE), ('CandyHire_Tenant_94', TRUE), ('CandyHire_Tenant_95', TRUE),
('CandyHire_Tenant_96', TRUE), ('CandyHire_Tenant_97', TRUE), ('CandyHire_Tenant_98', TRUE), ('CandyHire_Tenant_99', TRUE), ('CandyHire_Tenant_100', TRUE)
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

-- ============================================
-- Insert Countries with VAT information
-- ============================================
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
('MX', 'Mexico', 'Messico', 'México', 'Mexico', TRUE, 'RFC', FALSE, 'MXN', '+52', FALSE)
ON DUPLICATE KEY UPDATE name = VALUES(name);
