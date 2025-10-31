-- ============================================
-- CandyHire Portal Master Database Schema
-- MySQL 8.0+
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Table: companies_registered
-- Stores all companies that register on the portal
-- ============================================
DROP TABLE IF EXISTS `companies_registered`;
CREATE TABLE `companies_registered` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `company_name` VARCHAR(255) NOT NULL,
  `vat_number` VARCHAR(50) UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50),
  `website` VARCHAR(500),

  -- Address
  `address` VARCHAR(500),
  `city` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `province` VARCHAR(100),
  `country` VARCHAR(100) NOT NULL DEFAULT 'Italy',

  -- Company Info
  `industry` VARCHAR(100),
  `employees_count` VARCHAR(50),
  `description` TEXT,

  -- Legal Representative
  `legal_rep_first_name` VARCHAR(100) NOT NULL,
  `legal_rep_last_name` VARCHAR(100) NOT NULL,
  `legal_rep_email` VARCHAR(255) NOT NULL,
  `legal_rep_phone` VARCHAR(50),

  -- Authentication
  `password_hash` VARCHAR(255) NOT NULL,

  -- Status & Payment
  `registration_status` ENUM('pending', 'payment_pending', 'payment_completed', 'active', 'suspended', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `subscription_plan` VARCHAR(50),
  `subscription_start_date` DATE,
  `subscription_end_date` DATE,

  -- Tenant Assignment
  `tenant_id` INT NULL,
  `tenant_assigned_at` TIMESTAMP NULL,

  -- PayPal
  `paypal_subscription_id` VARCHAR(255),
  `paypal_payer_id` VARCHAR(255),

  -- Flags
  `is_active` BOOLEAN DEFAULT FALSE,
  `email_verified` BOOLEAN DEFAULT FALSE,
  `terms_accepted` BOOLEAN DEFAULT FALSE,
  `privacy_accepted` BOOLEAN DEFAULT FALSE,

  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,

  INDEX idx_company_email (`email`),
  INDEX idx_company_status (`registration_status`),
  INDEX idx_payment_status (`payment_status`),
  INDEX idx_tenant_id (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: tenant_pool
-- Pool of available tenant IDs for single-database multi-tenancy
-- ============================================
DROP TABLE IF EXISTS `tenant_pool`;
CREATE TABLE `tenant_pool` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL UNIQUE COMMENT 'Unique tenant identifier (1-100)',
  `is_available` BOOLEAN DEFAULT TRUE,
  `company_id` VARCHAR(50) NULL,
  `assigned_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`company_id`) REFERENCES `companies_registered`(`id`) ON DELETE SET NULL,
  INDEX idx_tenant_available (`is_available`),
  INDEX idx_tenant_company (`company_id`),
  INDEX idx_tenant_id (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: payment_transactions
-- Track all payment transactions
-- ============================================
DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `company_id` VARCHAR(50) NOT NULL,
  `transaction_type` ENUM('subscription', 'renewal', 'upgrade', 'refund') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'EUR',
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',

  -- PayPal Details
  `paypal_order_id` VARCHAR(255),
  `paypal_subscription_id` VARCHAR(255),
  `paypal_payer_id` VARCHAR(255),
  `paypal_payer_email` VARCHAR(255),
  `paypal_transaction_id` VARCHAR(255),

  -- Metadata
  `metadata` JSON,
  `error_message` TEXT,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`company_id`) REFERENCES `companies_registered`(`id`) ON DELETE CASCADE,
  INDEX idx_transaction_company (`company_id`),
  INDEX idx_transaction_status (`status`),
  INDEX idx_paypal_order (`paypal_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: admin_users
-- Portal administrators
-- ============================================
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` VARCHAR(50) NOT NULL PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `role` ENUM('super_admin', 'admin', 'support') NOT NULL DEFAULT 'admin',
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_admin_email (`email`),
  INDEX idx_admin_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: activity_logs
-- Audit trail for all activities
-- ============================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` VARCHAR(50),
  `action` VARCHAR(100) NOT NULL,
  `user_id` VARCHAR(50),
  `user_type` ENUM('company', 'admin') NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `metadata` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_log_entity (`entity_type`, `entity_id`),
  INDEX idx_log_action (`action`),
  INDEX idx_log_user (`user_id`, `user_type`),
  INDEX idx_log_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
