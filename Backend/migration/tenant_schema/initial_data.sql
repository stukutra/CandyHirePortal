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
