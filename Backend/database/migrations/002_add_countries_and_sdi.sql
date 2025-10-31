-- Migration: Add countries table and SDI code field
-- Date: 2025-10-31
-- Description: Create countries table with VAT info and add sdi_code to companies_registered

-- Create countries table
CREATE TABLE IF NOT EXISTS countries (
    code VARCHAR(2) PRIMARY KEY COMMENT 'ISO 3166-1 alpha-2 code',
    name VARCHAR(100) NOT NULL COMMENT 'English name',
    name_it VARCHAR(100) NOT NULL COMMENT 'Italian name',
    name_es VARCHAR(100) NOT NULL COMMENT 'Spanish name',
    name_en VARCHAR(100) NOT NULL COMMENT 'English name (duplicate for consistency)',
    has_vat BOOLEAN DEFAULT TRUE COMMENT 'Does this country use VAT system?',
    vat_label VARCHAR(50) DEFAULT 'VAT Number' COMMENT 'Label for VAT field',
    requires_sdi BOOLEAN DEFAULT FALSE COMMENT 'Requires SDI code (Italy only)',
    currency VARCHAR(3) DEFAULT 'EUR' COMMENT 'Currency code',
    phone_prefix VARCHAR(10) NOT NULL COMMENT 'International phone prefix',
    is_eu BOOLEAN DEFAULT FALSE COMMENT 'Is in European Union?',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add SDI code field to companies_registered
ALTER TABLE companies_registered
ADD COLUMN sdi_code VARCHAR(7) NULL COMMENT 'SDI Code for Italian electronic invoicing'
AFTER vat_number;

-- Add country_code field (if not exists)
ALTER TABLE companies_registered
ADD COLUMN country_code VARCHAR(2) NULL DEFAULT 'IT' COMMENT 'ISO country code'
AFTER country;

-- Insert main European countries
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

-- Create index for faster lookups
CREATE INDEX idx_country_code ON companies_registered(country_code);
CREATE INDEX idx_is_eu ON countries(is_eu);

-- Update existing companies to have country_code = 'IT' (Italy) if country is 'Italy' or NULL
UPDATE companies_registered
SET country_code = 'IT'
WHERE country = 'Italy' OR country IS NULL OR country = '';
