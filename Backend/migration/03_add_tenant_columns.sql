-- Migration: Add tenant_id and tenant_assigned_at columns to companies_registered table
-- This migration adds columns that were missing from the database

ALTER TABLE `companies_registered`
ADD COLUMN `tenant_id` INT NULL AFTER `subscription_end_date`,
ADD COLUMN `tenant_assigned_at` TIMESTAMP NULL AFTER `tenant_id`;
