-- ============================================
-- Subscription Tiers Management
-- Migration: 04_subscription_tiers.sql
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop table if exists
DROP TABLE IF EXISTS `subscription_tiers`;

-- ============================================
-- Table: subscription_tiers
-- Stores all available subscription plans/tiers
-- ============================================
CREATE TABLE `subscription_tiers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'Display name (e.g., "CandyHire Premium")',
  `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique identifier (e.g., "premium", "entry")',
  `category` VARCHAR(50) NOT NULL COMMENT 'Category/tier level (e.g., "Premium", "Entry", "Professional")',
  `description` TEXT COMMENT 'Short description of the tier',

  -- Pricing
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Price amount',
  `currency` VARCHAR(3) DEFAULT 'EUR' COMMENT 'Currency code',
  `billing_period` ENUM('monthly', 'yearly', 'one_time') DEFAULT 'yearly' COMMENT 'Billing frequency',
  `original_price` DECIMAL(10,2) NULL COMMENT 'Original price (for showing discounts)',

  -- Features
  `features` JSON NOT NULL COMMENT 'Array of features with title and description',
  `highlights` JSON NULL COMMENT 'Array of special highlights/benefits',

  -- Display & Status
  `badge_text` VARCHAR(50) NULL COMMENT 'Badge text (e.g., "Most Popular", "Early Bird")',
  `badge_icon` VARCHAR(50) NULL COMMENT 'Bootstrap icon class for badge',
  `is_featured` BOOLEAN DEFAULT FALSE COMMENT 'Show as featured/highlighted tier',
  `is_enabled` BOOLEAN DEFAULT TRUE COMMENT 'Is this tier available for selection?',
  `sort_order` INT DEFAULT 0 COMMENT 'Display order (lower = first)',

  -- Metadata
  `metadata` JSON NULL COMMENT 'Additional metadata for flexibility',

  -- Timestamps
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_tier_enabled (`is_enabled`),
  INDEX idx_tier_featured (`is_featured`),
  INDEX idx_tier_slug (`slug`),
  INDEX idx_tier_sort (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Initial Tier (Professional - Early Bird)
-- Based on current pricing component
-- ============================================
INSERT INTO `subscription_tiers` (
  `name`,
  `slug`,
  `category`,
  `description`,
  `price`,
  `currency`,
  `billing_period`,
  `original_price`,
  `features`,
  `highlights`,
  `badge_text`,
  `badge_icon`,
  `is_featured`,
  `is_enabled`,
  `sort_order`
) VALUES (
  'CandyHire Professional',
  'professional',
  'Professional',
  'Piano completo per gestire tutto il tuo processo di recruiting in modo professionale',
  999.00,
  'EUR',
  'yearly',
  1299.00,
  JSON_ARRAY(
    JSON_OBJECT(
      'icon', 'bi-graph-up',
      'title', 'Dashboard & Analytics Avanzate',
      'description', 'Visualizzazioni intuitive e report dettagliati per monitorare tutto il processo di recruiting'
    ),
    JSON_OBJECT(
      'icon', 'bi-briefcase-fill',
      'title', 'Posizioni di Lavoro Illimitate',
      'description', 'Gestisci tutte le posizioni aperte che vuoi, senza limiti o costi aggiuntivi'
    ),
    JSON_OBJECT(
      'icon', 'bi-person-badge-fill',
      'title', 'Database Candidati Illimitato',
      'description', 'Archivia e gestisci tutti i candidati con profili completi e cronologia delle interazioni'
    ),
    JSON_OBJECT(
      'icon', 'bi-people-fill',
      'title', 'Gestione Team Completa',
      'description', 'Aggiungi colleghi e collaboratori con ruoli personalizzati e permessi granulari'
    ),
    JSON_OBJECT(
      'icon', 'bi-building',
      'title', 'Gestione Clienti Multi-Azienda',
      'description', 'Ideale per agenzie: gestisci candidature e posizioni per più aziende clienti'
    ),
    JSON_OBJECT(
      'icon', 'bi-calendar-check',
      'title', 'Calendario Colloqui Integrato',
      'description', 'Pianifica e gestisci tutti i tuoi colloqui con sincronizzazione Google Calendar'
    ),
    JSON_OBJECT(
      'icon', 'bi-coin',
      'title', 'Gestione Revenue & Fatturato',
      'description', 'Traccia entrate, commissioni e KPI finanziari legati al recruiting'
    ),
    JSON_OBJECT(
      'icon', 'bi-cloud-check-fill',
      'title', 'Backup Automatico Quotidiano',
      'description', 'I tuoi dati sono sempre al sicuro con backup giornalieri automatici'
    ),
    JSON_OBJECT(
      'icon', 'bi-gift-fill',
      'title', 'Demo Personalizzata Gratuita',
      'description', 'Sessione dimostrativa one-to-one per scoprire tutte le funzionalità',
      'isBonus', true
    ),
    JSON_OBJECT(
      'icon', 'bi-gift-fill',
      'title', 'Supporto Dedicato Italiano',
      'description', 'Assistenza prioritaria in italiano via email e chat',
      'isBonus', true
    ),
    JSON_OBJECT(
      'icon', 'bi-gift-fill',
      'title', 'Aggiornamenti Gratuiti Lifetime',
      'description', 'Ricevi tutte le nuove funzionalità e miglioramenti senza costi aggiuntivi',
      'isBonus', true
    )
  ),
  JSON_ARRAY(
    'Candidati illimitati',
    'Posizioni illimitate',
    'Team members illimitati',
    'Gestione multi-cliente',
    'Backup giornaliero automatico'
  ),
  'Early Bird Special',
  'bi-star-fill',
  true,
  true,
  1
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- End of Subscription Tiers Migration
-- ============================================
