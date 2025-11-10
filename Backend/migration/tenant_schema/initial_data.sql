-- ============================================
-- Initial Data for Tenant Databases
-- Document Types for different entities
-- ============================================

-- NOTE: tenant_id must be replaced with actual tenant ID during provisioning
-- This is a template that will be executed for each tenant

-- ============================================
-- Document Types for Candidates
-- ============================================
INSERT INTO `document_types` (`tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `required`, `is_active`) VALUES
('{{TENANT_ID}}', 'CV/Resume', 'candidate', 'Candidate curriculum vitae or resume', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Cover Letter', 'candidate', 'Candidate cover letter', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Identity Document', 'candidate', 'Identity card, passport, or driving license', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'Educational Certificate', 'candidate', 'Degree, diploma, or certification', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'Reference Letter', 'candidate', 'Professional reference or recommendation letter', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Portfolio', 'candidate', 'Work portfolio or samples', 'bi-file-earmark', '["application/pdf","application/zip","image/jpeg","image/png","video/mp4"]', 20, 0, 1);

-- ============================================
-- Document Types for Companies
-- ============================================
INSERT INTO `document_types` (`tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `required`, `is_active`) VALUES
('{{TENANT_ID}}', 'Company Registration', 'company', 'Business registration certificate', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'Tax Document', 'company', 'Tax ID or VAT registration', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'Company Profile', 'company', 'Company presentation or brochure', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Contract', 'company', 'Service or partnership contract', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1);

-- ============================================
-- Document Types for Recruiters
-- ============================================
INSERT INTO `document_types` (`tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `required`, `is_active`) VALUES
('{{TENANT_ID}}', 'Identity Document', 'recruiter', 'Identity card or passport', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'Certification', 'recruiter', 'Professional certification or training certificate', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1),
('{{TENANT_ID}}', 'NDA Agreement', 'recruiter', 'Non-disclosure agreement', 'bi-file-earmark', '["application/pdf"]', 20, 0, 1);

-- ============================================
-- Document Types for Jobs
-- ============================================
INSERT INTO `document_types` (`tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `required`, `is_active`) VALUES
('{{TENANT_ID}}', 'Job Description', 'job', 'Detailed job description document', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Job Posting', 'job', 'Published job advertisement', 'bi-file-earmark', '["application/pdf","image/jpeg","image/png"]', 20, 0, 1);

-- ============================================
-- Document Types for Interviews
-- ============================================
INSERT INTO `document_types` (`tenant_id`, `name`, `entity_type`, `description`, `icon`, `accepted_formats`, `max_size_mb`, `required`, `is_active`) VALUES
('{{TENANT_ID}}', 'Interview Notes', 'interview', 'Interview evaluation and notes', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document"]', 20, 0, 1),
('{{TENANT_ID}}', 'Interview Recording', 'interview', 'Audio or video recording of interview', 'bi-file-earmark', '["video/mp4","video/quicktime","video/x-msvideo"]', 20, 0, 1),
('{{TENANT_ID}}', 'Technical Test', 'interview', 'Technical assessment or test results', 'bi-file-earmark', '["application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document","application/zip"]', 20, 0, 1);

-- ============================================
-- Currencies
-- International currency support
-- ============================================
INSERT INTO `currencies` (`tenant_id`, `code`, `name`, `symbol`, `decimal_places`, `is_active`, `is_default`, `exchange_rate_to_base`) VALUES
-- Major currencies
('{{TENANT_ID}}', 'USD', 'US Dollar', '$', 2, TRUE, FALSE, 1.000000),
('{{TENANT_ID}}', 'EUR', 'Euro', '€', 2, TRUE, TRUE, 0.920000),
('{{TENANT_ID}}', 'GBP', 'British Pound', '£', 2, TRUE, FALSE, 0.790000),
('{{TENANT_ID}}', 'CHF', 'Swiss Franc', 'CHF', 2, TRUE, FALSE, 0.880000),
-- Western Europe
('{{TENANT_ID}}', 'SEK', 'Swedish Krona', 'kr', 2, TRUE, FALSE, 10.500000),
('{{TENANT_ID}}', 'NOK', 'Norwegian Krone', 'kr', 2, TRUE, FALSE, 10.700000),
('{{TENANT_ID}}', 'DKK', 'Danish Krone', 'kr', 2, TRUE, FALSE, 6.850000),
-- Eastern Europe
('{{TENANT_ID}}', 'PLN', 'Polish Złoty', 'zł', 2, TRUE, FALSE, 4.050000),
('{{TENANT_ID}}', 'CZK', 'Czech Koruna', 'Kč', 2, TRUE, FALSE, 23.500000),
('{{TENANT_ID}}', 'HUF', 'Hungarian Forint', 'Ft', 0, TRUE, FALSE, 360.000000),
('{{TENANT_ID}}', 'RON', 'Romanian Leu', 'lei', 2, TRUE, FALSE, 4.600000),
('{{TENANT_ID}}', 'BGN', 'Bulgarian Lev', 'лв', 2, TRUE, FALSE, 1.800000),
('{{TENANT_ID}}', 'HRK', 'Croatian Kuna', 'kn', 2, TRUE, FALSE, 7.500000),
('{{TENANT_ID}}', 'RSD', 'Serbian Dinar', 'дин', 2, TRUE, FALSE, 108.000000),
('{{TENANT_ID}}', 'RUB', 'Russian Ruble', '₽', 2, TRUE, FALSE, 92.000000),
('{{TENANT_ID}}', 'UAH', 'Ukrainian Hryvnia', '₴', 2, TRUE, FALSE, 37.000000),
-- Americas
('{{TENANT_ID}}', 'CAD', 'Canadian Dollar', 'CA$', 2, TRUE, FALSE, 1.360000),
('{{TENANT_ID}}', 'MXN', 'Mexican Peso', 'MX$', 2, TRUE, FALSE, 17.200000),
('{{TENANT_ID}}', 'BRL', 'Brazilian Real', 'R$', 2, TRUE, FALSE, 5.000000),
('{{TENANT_ID}}', 'ARS', 'Argentine Peso', '$', 2, TRUE, FALSE, 350.000000),
('{{TENANT_ID}}', 'CLP', 'Chilean Peso', '$', 0, TRUE, FALSE, 900.000000),
('{{TENANT_ID}}', 'COP', 'Colombian Peso', '$', 2, TRUE, FALSE, 4000.000000),
('{{TENANT_ID}}', 'PEN', 'Peruvian Sol', 'S/', 2, TRUE, FALSE, 3.750000),
-- Asia-Pacific
('{{TENANT_ID}}', 'JPY', 'Japanese Yen', '¥', 0, TRUE, FALSE, 150.000000),
('{{TENANT_ID}}', 'CNY', 'Chinese Yuan', '¥', 2, TRUE, FALSE, 7.200000),
('{{TENANT_ID}}', 'KRW', 'South Korean Won', '₩', 0, TRUE, FALSE, 1320.000000),
('{{TENANT_ID}}', 'INR', 'Indian Rupee', '₹', 2, TRUE, FALSE, 83.000000),
('{{TENANT_ID}}', 'SGD', 'Singapore Dollar', 'S$', 2, TRUE, FALSE, 1.350000),
('{{TENANT_ID}}', 'HKD', 'Hong Kong Dollar', 'HK$', 2, TRUE, FALSE, 7.830000),
('{{TENANT_ID}}', 'AUD', 'Australian Dollar', 'A$', 2, TRUE, FALSE, 1.530000),
('{{TENANT_ID}}', 'NZD', 'New Zealand Dollar', 'NZ$', 2, TRUE, FALSE, 1.650000),
('{{TENANT_ID}}', 'THB', 'Thai Baht', '฿', 2, TRUE, FALSE, 35.500000),
('{{TENANT_ID}}', 'MYR', 'Malaysian Ringgit', 'RM', 2, TRUE, FALSE, 4.700000),
('{{TENANT_ID}}', 'IDR', 'Indonesian Rupiah', 'Rp', 0, TRUE, FALSE, 15700.000000),
('{{TENANT_ID}}', 'PHP', 'Philippine Peso', '₱', 2, TRUE, FALSE, 56.000000),
('{{TENANT_ID}}', 'VND', 'Vietnamese Dong', '₫', 0, TRUE, FALSE, 24500.000000),
('{{TENANT_ID}}', 'PKR', 'Pakistani Rupee', '₨', 2, TRUE, FALSE, 280.000000),
('{{TENANT_ID}}', 'BDT', 'Bangladeshi Taka', '৳', 2, TRUE, FALSE, 110.000000),
('{{TENANT_ID}}', 'LKR', 'Sri Lankan Rupee', 'Rs', 2, TRUE, FALSE, 325.000000),
-- Middle East
('{{TENANT_ID}}', 'AED', 'UAE Dirham', 'د.إ', 2, TRUE, FALSE, 3.670000),
('{{TENANT_ID}}', 'SAR', 'Saudi Riyal', 'ر.س', 2, TRUE, FALSE, 3.750000),
('{{TENANT_ID}}', 'ILS', 'Israeli Shekel', '₪', 2, TRUE, FALSE, 3.700000),
('{{TENANT_ID}}', 'TRY', 'Turkish Lira', '₺', 2, TRUE, FALSE, 28.500000),
('{{TENANT_ID}}', 'QAR', 'Qatari Riyal', 'ر.ق', 2, TRUE, FALSE, 3.640000),
('{{TENANT_ID}}', 'KWD', 'Kuwaiti Dinar', 'د.ك', 3, TRUE, FALSE, 0.307000),
('{{TENANT_ID}}', 'BHD', 'Bahraini Dinar', 'د.ب', 3, TRUE, FALSE, 0.377000),
('{{TENANT_ID}}', 'OMR', 'Omani Rial', 'ر.ع.', 3, TRUE, FALSE, 0.385000),
('{{TENANT_ID}}', 'JOD', 'Jordanian Dinar', 'د.ا', 3, TRUE, FALSE, 0.709000),
('{{TENANT_ID}}', 'LBP', 'Lebanese Pound', 'ل.ل', 2, TRUE, FALSE, 89500.000000),
-- Africa
('{{TENANT_ID}}', 'ZAR', 'South African Rand', 'R', 2, TRUE, FALSE, 18.800000),
('{{TENANT_ID}}', 'EGP', 'Egyptian Pound', 'E£', 2, TRUE, FALSE, 31.000000),
('{{TENANT_ID}}', 'NGN', 'Nigerian Naira', '₦', 2, TRUE, FALSE, 800.000000),
('{{TENANT_ID}}', 'KES', 'Kenyan Shilling', 'KSh', 2, TRUE, FALSE, 150.000000),
('{{TENANT_ID}}', 'GHS', 'Ghanaian Cedi', '₵', 2, TRUE, FALSE, 12.500000),
('{{TENANT_ID}}', 'TND', 'Tunisian Dinar', 'د.ت', 3, TRUE, FALSE, 3.150000),
('{{TENANT_ID}}', 'MAD', 'Moroccan Dirham', 'د.م.', 2, TRUE, FALSE, 10.100000),
('{{TENANT_ID}}', 'UGX', 'Ugandan Shilling', 'USh', 0, TRUE, FALSE, 3750.000000),
('{{TENANT_ID}}', 'TZS', 'Tanzanian Shilling', 'TSh', 0, TRUE, FALSE, 2500.000000),
-- Other notable currencies
('{{TENANT_ID}}', 'ISK', 'Icelandic Króna', 'kr', 0, TRUE, FALSE, 137.000000),
('{{TENANT_ID}}', 'ALL', 'Albanian Lek', 'L', 2, TRUE, FALSE, 95.000000),
('{{TENANT_ID}}', 'MKD', 'Macedonian Denar', 'ден', 2, TRUE, FALSE, 56.500000),
('{{TENANT_ID}}', 'BAM', 'Bosnia-Herzegovina Convertible Mark', 'KM', 2, TRUE, FALSE, 1.800000);
