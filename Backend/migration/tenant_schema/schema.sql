-- ============================================
-- CandyHire Database Schema - Multi-Tenant
-- MySQL 8.0+
-- tenant_id isolates data between companies
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Table: roles
-- User roles and permissions
-- ============================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `permissions` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_role_name_per_tenant (`name`, `tenant_id`),
  INDEX idx_roles_tenant (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: system_users
-- Users who can login to the system
-- ============================================
DROP TABLE IF EXISTS `system_users`;
CREATE TABLE `system_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL COMMENT 'Isolates user data per tenant',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(100),
  `avatar` TEXT,
  `role_id` BIGINT UNSIGNED,
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_email_per_tenant (`email`, `tenant_id`),
  INDEX idx_system_users_tenant (`tenant_id`),
  INDEX idx_system_users_email (`email`),
  INDEX idx_system_users_role (`role_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: companies
-- Client companies and suppliers
-- ============================================
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `logo` TEXT,
  `industry` VARCHAR(100) NOT NULL,
  `website` VARCHAR(500),
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50),
  `address` VARCHAR(500),
  `city` VARCHAR(100) NOT NULL,
  `country` VARCHAR(100) NOT NULL,
  `employees_count` VARCHAR(50),
  `founded_year` INT,
  `description` TEXT,
  `type` ENUM('Client', 'Supplier', 'Both') NOT NULL DEFAULT 'Client',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_companies_tenant (`tenant_id`),
  INDEX idx_companies_type (`type`),
  INDEX idx_companies_city (`city`),
  INDEX idx_companies_industry (`industry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: recruiters
-- Internal recruiters/HR staff
-- NOTE: Recruiters are system_users with recruiter role
--       Created automatically when system_user has recruiter role
--       Use Recruiters page only for editing profile (avatar, jobs, etc.)
-- ============================================
DROP TABLE IF EXISTS `recruiters`;
CREATE TABLE `recruiters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `system_user_id` BIGINT UNSIGNED NULL COMMENT 'Link to system_users table',
  `tenant_id` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50),
  `avatar` TEXT,
  `role` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100),
  `specialization` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_recruiter_email_per_tenant (`email`, `tenant_id`),
  UNIQUE KEY unique_recruiter_system_user (`system_user_id`),
  INDEX idx_recruiters_tenant (`tenant_id`),
  INDEX idx_recruiters_email (`email`),
  INDEX idx_recruiters_role (`role`),
  INDEX idx_recruiters_system_user (`system_user_id`),
  FOREIGN KEY (`system_user_id`) REFERENCES `system_users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: jobs
-- Job openings/positions
-- ============================================
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `location_type` ENUM('Remote', 'On-site', 'Hybrid') NOT NULL DEFAULT 'Hybrid',
  `job_type` ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL DEFAULT 'Full-time',
  `status` ENUM('Open', 'Closed', 'Draft') NOT NULL DEFAULT 'Open',
  `description` TEXT NOT NULL,
  `requirements` TEXT,
  `responsibilities` TEXT,
  `salary_min` DECIMAL(12,2),
  `salary_max` DECIMAL(12,2),
  `salary_currency` VARCHAR(10) DEFAULT 'USD',
  `salary_range` VARCHAR(100),
  `applicants_count` INT DEFAULT 0,
  `posted_date` DATE,
  `start_date` DATE,
  `end_date` DATE,
  `company_id` BIGINT UNSIGNED,
  `created_by` BIGINT UNSIGNED,
  `employment_type` VARCHAR(50),
  `work_location_type` VARCHAR(50),
  `experience_level` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `system_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_jobs_tenant (`tenant_id`),
  INDEX idx_jobs_company (`company_id`),
  INDEX idx_jobs_status (`status`),
  INDEX idx_jobs_type (`job_type`),
  INDEX idx_jobs_location_type (`location_type`),
  INDEX idx_jobs_posted_date (`posted_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: job_recruiters (Many-to-Many)
-- Assigns recruiters to jobs
-- ============================================
DROP TABLE IF EXISTS `job_recruiters`;
CREATE TABLE `job_recruiters` (
  `job_id` BIGINT UNSIGNED NOT NULL,
  `recruiter_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`job_id`, `recruiter_id`),
  FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`recruiter_id`) REFERENCES `recruiters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_job_recruiters_job (`job_id`),
  INDEX idx_job_recruiters_recruiter (`recruiter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: candidates
-- Job candidates/applicants
-- ============================================
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50),
  `avatar` TEXT,
  `resume_url` TEXT,
  `cover_letter` TEXT,
  `linkedin` VARCHAR(500),
  `portfolio` VARCHAR(500),
  `skills` JSON,
  `experience` INT DEFAULT 0 COMMENT 'Years of experience',
  `current_position` VARCHAR(255),
  `current_company` VARCHAR(255),
  `status` ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') NOT NULL DEFAULT 'New',
  `rating` TINYINT CHECK (`rating` >= 1 AND `rating` <= 5),
  `candidate_type` ENUM('Employee', 'Freelancer', 'Supplier') NOT NULL DEFAULT 'Employee',
  `daily_rate` DECIMAL(10,2) COMMENT 'For freelancers in EUR',
  `annual_salary` DECIMAL(12,2) COMMENT 'For employees RAL in EUR',
  `expected_salary` DECIMAL(12,2) COMMENT 'Expected annual or daily rate',
  `supplier_company_id` BIGINT UNSIGNED,
  `supplier_company_name` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_candidates_tenant (`tenant_id`),
  INDEX idx_candidates_email (`email`),
  INDEX idx_candidates_status (`status`),
  INDEX idx_candidates_type (`candidate_type`),
  INDEX idx_candidates_supplier (`supplier_company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: job_applications (Many-to-Many)
-- Links candidates to jobs they applied for
-- ============================================
DROP TABLE IF EXISTS `job_applications`;
CREATE TABLE `job_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `company_id` BIGINT UNSIGNED,
  `status` ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') NOT NULL DEFAULT 'New',
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `referral_company_id` BIGINT UNSIGNED,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`referral_company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_job_applications_tenant (`tenant_id`),
  INDEX idx_job_applications_candidate (`candidate_id`),
  INDEX idx_job_applications_job (`job_id`),
  INDEX idx_job_applications_status (`status`),
  INDEX idx_job_applications_applied_at (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: job_application_recruiters (Many-to-Many)
-- Assigns recruiters to job applications
-- ============================================
DROP TABLE IF EXISTS `job_application_recruiters`;
CREATE TABLE `job_application_recruiters` (
  `job_application_id` BIGINT UNSIGNED NOT NULL,
  `recruiter_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`job_application_id`, `recruiter_id`),
  FOREIGN KEY (`job_application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`recruiter_id`) REFERENCES `recruiters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: candidate_notes
-- Notes about candidates
-- ============================================
DROP TABLE IF EXISTS `candidate_notes`;
CREATE TABLE `candidate_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `recruiter_id` BIGINT UNSIGNED NOT NULL,
  `recruiter_name` VARCHAR(255) NOT NULL,
  `recruiter_avatar` TEXT,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`recruiter_id`) REFERENCES `recruiters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_candidate_notes_tenant (`tenant_id`),
  INDEX idx_candidate_notes_candidate (`candidate_id`),
  INDEX idx_candidate_notes_recruiter (`recruiter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: interviews
-- Interview schedules
-- ============================================
DROP TABLE IF EXISTS `interviews`;
CREATE TABLE `interviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `candidate_name` VARCHAR(255) NOT NULL,
  `job_id` BIGINT UNSIGNED,
  `job_title` VARCHAR(255),
  `company_id` BIGINT UNSIGNED,
  `company_name` VARCHAR(255),
  `type` ENUM('Phone', 'Video', 'In-person', 'Technical') NOT NULL DEFAULT 'Video',
  `status` ENUM('Scheduled', 'Completed', 'Cancelled', 'Rescheduled') NOT NULL DEFAULT 'Scheduled',
  `scheduled_at` TIMESTAMP NOT NULL,
  `duration` INT NOT NULL COMMENT 'Duration in minutes',
  `location` VARCHAR(500),
  `meeting_link` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_interviews_tenant (`tenant_id`),
  INDEX idx_interviews_candidate (`candidate_id`),
  INDEX idx_interviews_job (`job_id`),
  INDEX idx_interviews_status (`status`),
  INDEX idx_interviews_scheduled (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: interview_interviewers (Many-to-Many)
-- Assigns interviewers to interviews
-- ============================================
DROP TABLE IF EXISTS `interview_interviewers`;
CREATE TABLE `interview_interviewers` (
  `interview_id` BIGINT UNSIGNED NOT NULL,
  `recruiter_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`interview_id`, `recruiter_id`),
  FOREIGN KEY (`interview_id`) REFERENCES `interviews`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`recruiter_id`) REFERENCES `recruiters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: interview_feedback
-- Feedback from interviewers
-- ============================================
DROP TABLE IF EXISTS `interview_feedback`;
CREATE TABLE `interview_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `interview_id` BIGINT UNSIGNED NOT NULL,
  `interviewer_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comments` TEXT NOT NULL,
  `recommendation` ENUM('Hire', 'Maybe', 'No Hire') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`interview_id`) REFERENCES `interviews`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`interviewer_id`) REFERENCES `recruiters`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_feedback_tenant (`tenant_id`),
  INDEX idx_feedback_interview (`interview_id`),
  INDEX idx_feedback_interviewer (`interviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: referents
-- Company contact persons
-- ============================================
DROP TABLE IF EXISTS `referents`;
CREATE TABLE `referents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('HR Manager', 'HR Director', 'Recruiting Manager', 'Talent Acquisition', 'Department Head', 'Account Manager', 'Sales Manager', 'Business Partner', 'Operations Manager') NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_referents_tenant (`tenant_id`),
  INDEX idx_referents_company (`company_id`),
  INDEX idx_referents_email (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: blacklist
-- Blacklisted entities
-- ============================================
DROP TABLE IF EXISTS `blacklist`;
CREATE TABLE `blacklist` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `entity_type` ENUM('candidate', 'company', 'recruiter') NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `entity_name` VARCHAR(255) NOT NULL,
  `reason` TEXT NOT NULL,
  `added_by` BIGINT UNSIGNED,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_permanent` BOOLEAN DEFAULT FALSE,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`added_by`) REFERENCES `system_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_blacklist_tenant (`tenant_id`),
  INDEX idx_blacklist_entity (`entity_type`, `entity_id`),
  INDEX idx_blacklist_added_by (`added_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: document_types
-- Types of documents that can be uploaded
-- ============================================
DROP TABLE IF EXISTS `document_types`;
CREATE TABLE `document_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `entity_type` ENUM('candidate', 'company', 'job', 'interview', 'recruiter') NOT NULL,
  `description` TEXT,
  `icon` VARCHAR(100),
  `accepted_formats` JSON NOT NULL COMMENT 'Array of MIME types',
  `max_size_mb` INT NOT NULL DEFAULT 10,
  `is_required` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_document_types_tenant (`tenant_id`),
  INDEX idx_document_types_entity (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: documents
-- Uploaded documents
-- ============================================
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `entity_type` ENUM('candidate', 'company', 'job', 'interview', 'recruiter') NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `document_type_id` BIGINT UNSIGNED NOT NULL,
  `document_type_name` VARCHAR(100) NOT NULL,
  `file_name` VARCHAR(500) NOT NULL,
  `file_size` BIGINT NOT NULL COMMENT 'Size in bytes',
  `file_type` VARCHAR(100) NOT NULL COMMENT 'MIME type',
  `file_url` TEXT NOT NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `uploader_name` VARCHAR(255) NOT NULL,
  `notes` TEXT,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`document_type_id`) REFERENCES `document_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `system_users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_documents_tenant (`tenant_id`),
  INDEX idx_documents_entity (`entity_type`, `entity_id`),
  INDEX idx_documents_type (`document_type_id`),
  INDEX idx_documents_uploaded_by (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: activity_logs
-- Audit trail of all activities
-- ============================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT UNSIGNED,
  `action` VARCHAR(100) NOT NULL,
  `user_id` BIGINT UNSIGNED,
  `user_email` VARCHAR(255),
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `metadata` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `system_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_activity_logs_tenant (`tenant_id`),
  INDEX idx_activity_logs_entity (`entity_type`, `entity_id`),
  INDEX idx_activity_logs_action (`action`),
  INDEX idx_activity_logs_user (`user_id`),
  INDEX idx_activity_logs_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- End of Multi-Tenant Schema
-- ============================================
