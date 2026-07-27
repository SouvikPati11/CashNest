-- =============================================================================
-- CashNest — Production Database Schema (MySQL 8.x / InnoDB)
-- =============================================================================
-- Generated from DATABASE_DESIGN.md (companion to ARCHITECTURE.md, SAD v1.1).
--
-- Contents:
--   1. Session setup (charset, SQL mode, UTC, FK checks off during create)
--   2. Schema — all CREATE TABLE statements (sections A–M), with indexes,
--      foreign keys, and unique constraints. (No table CHECK constraints are
--      used, for maximum MySQL/MariaDB portability — those invariants are
--      enforced in the application layer.)
--   3. Seed data — RBAC roles/permissions, a default super-admin account,
--      currency/reward/withdraw/gateway config, content, and app config.
--
-- Conventions (per design doc §0):
--   * Engine InnoDB, utf8mb4 / utf8mb4_unicode_ci, ROW_FORMAT=DYNAMIC
--     (utf8mb4_unicode_ci is supported by all MySQL 5.7+/8 and MariaDB 10.x).
--   * All datetimes are UTC; conversion happens at the app layer.
--   * Coins  : BIGINT (balances UNSIGNED). Cash : DECIMAL(18,4).
--     Rate   : DECIMAL(18,8). Percent : DECIMAL(6,4) (0.1000 = 10%).
--   * The `wallet_transactions` ledger is append-only; balances mutate only
--     alongside a ledger row inside a row-locked transaction.
--
-- Import:  mysql -u <user> -p <database> < cashnest.sql
-- =============================================================================

SET NAMES utf8mb4;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET @OLD_TIME_ZONE = @@TIME_ZONE;
SET TIME_ZONE = '+00:00';
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- A. IDENTITY & USERS
-- =============================================================================

-- A.1 users --------------------------------------------------------------------
CREATE TABLE `users` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`               CHAR(36) NOT NULL,
  `name`               VARCHAR(120) DEFAULT NULL,
  `email`              VARCHAR(255) DEFAULT NULL,
  `email_verified_at`  DATETIME DEFAULT NULL,
  `phone`              VARCHAR(20) DEFAULT NULL,
  `avatar_url`         VARCHAR(512) DEFAULT NULL,
  `referral_code`      VARCHAR(12) NOT NULL,
  `referred_by`        BIGINT UNSIGNED DEFAULT NULL,
  `coin_balance_cache` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `cash_balance_cache` DECIMAL(18,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `status`             ENUM('active','suspended','banned','deleted') NOT NULL DEFAULT 'active',
  `country_code`       CHAR(2) DEFAULT NULL,
  `locale`             VARCHAR(10) DEFAULT NULL,
  `last_login_at`      DATETIME DEFAULT NULL,
  `registration_ip`    VARCHAR(45) DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`         DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_uuid` (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_referral_code` (`referral_code`),
  KEY `idx_users_referred_by` (`referred_by`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_created_at` (`created_at`),
  KEY `idx_users_country` (`country_code`),
  CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- A.2 user_auth_providers ------------------------------------------------------
CREATE TABLE `user_auth_providers` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `provider`      ENUM('google','email') NOT NULL,
  `provider_uid`  VARCHAR(191) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `email`         VARCHAR(255) DEFAULT NULL,
  `is_primary`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `last_used_at`  DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_uap_provider_uid` (`provider`, `provider_uid`),
  UNIQUE KEY `uq_uap_user_provider` (`user_id`, `provider`),
  KEY `idx_uap_user` (`user_id`),
  CONSTRAINT `fk_uap_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- A.3 user_devices -------------------------------------------------------------
CREATE TABLE `user_devices` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `device_uuid`      VARCHAR(191) NOT NULL,
  `fcm_token`        VARCHAR(255) DEFAULT NULL,
  `platform`         ENUM('android','ios','web') NOT NULL DEFAULT 'android',
  `device_model`     VARCHAR(120) DEFAULT NULL,
  `os_version`       VARCHAR(40) DEFAULT NULL,
  `app_version`      VARCHAR(20) DEFAULT NULL,
  `fingerprint_hash` VARCHAR(191) DEFAULT NULL,
  `is_emulator`      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_rooted`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `last_ip`          VARCHAR(45) DEFAULT NULL,
  `last_seen_at`     DATETIME DEFAULT NULL,
  `push_enabled`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ud_user_device` (`user_id`, `device_uuid`),
  KEY `idx_ud_user` (`user_id`),
  KEY `idx_ud_fcm` (`fcm_token`),
  KEY `idx_ud_fingerprint` (`fingerprint_hash`),
  KEY `idx_ud_last_seen` (`last_seen_at`),
  CONSTRAINT `fk_ud_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- A.4 user_sessions ------------------------------------------------------------
CREATE TABLE `user_sessions` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            BIGINT UNSIGNED NOT NULL,
  `device_id`          BIGINT UNSIGNED DEFAULT NULL,
  `refresh_token_hash` CHAR(64) NOT NULL,
  `jwt_id`             CHAR(36) DEFAULT NULL,
  `ip_address`         VARCHAR(45) DEFAULT NULL,
  `user_agent`         VARCHAR(255) DEFAULT NULL,
  `expires_at`         DATETIME NOT NULL,
  `revoked_at`         DATETIME DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_us_refresh_hash` (`refresh_token_hash`),
  KEY `idx_us_user` (`user_id`),
  KEY `idx_us_expires` (`expires_at`),
  KEY `idx_us_revoked` (`revoked_at`),
  KEY `idx_us_device` (`device_id`),
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_us_device` FOREIGN KEY (`device_id`) REFERENCES `user_devices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- A.5 user_kyc -----------------------------------------------------------------
CREATE TABLE `user_kyc` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`             BIGINT UNSIGNED NOT NULL,
  `full_name`           VARCHAR(150) DEFAULT NULL,
  `document_type`       ENUM('aadhaar','pan','passport','driving_license','other') DEFAULT NULL,
  `document_number_enc` VARBINARY(512) DEFAULT NULL,
  `document_file_url`   VARCHAR(512) DEFAULT NULL,
  `status`              ENUM('pending','submitted','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason`    VARCHAR(255) DEFAULT NULL,
  `reviewed_by`         BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at`         DATETIME DEFAULT NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kyc_user` (`user_id`),
  KEY `idx_kyc_status` (`status`),
  KEY `idx_kyc_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kyc_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- A.6 user_settings ------------------------------------------------------------
CREATE TABLE `user_settings` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`             BIGINT UNSIGNED NOT NULL,
  `notif_push_enabled`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `notif_transactional` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `notif_promotional`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `language`            VARCHAR(10) NOT NULL DEFAULT 'en',
  `theme_mode`          ENUM('system','light','dark') NOT NULL DEFAULT 'system',
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usettings_user` (`user_id`),
  CONSTRAINT `fk_usettings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- B. WALLET & CURRENCY  (the financial core)
-- =============================================================================

-- B.1 wallets ------------------------------------------------------------------
CREATE TABLE `wallets` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`               BIGINT UNSIGNED NOT NULL,
  `coin_balance`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `coin_reserved`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `lifetime_coins_earned` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `lifetime_coins_spent`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `cash_balance`          DECIMAL(18,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `cash_reserved`         DECIMAL(18,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `version`               BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `last_transaction_id`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallets_user` (`user_id`),
  KEY `idx_wallets_last_txn` (`last_transaction_id`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wallets_last_txn` FOREIGN KEY (`last_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- B.2 wallet_transactions (immutable ledger) ----------------------------------
CREATE TABLE `wallet_transactions` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                   CHAR(36) NOT NULL,
  `user_id`                BIGINT UNSIGNED NOT NULL,
  `wallet_id`              BIGINT UNSIGNED NOT NULL,
  `direction`              ENUM('credit','debit') NOT NULL,
  `amount`                 BIGINT UNSIGNED NOT NULL,
  `cash_amount`            DECIMAL(18,4) UNSIGNED DEFAULT NULL,
  `balance_after`          BIGINT UNSIGNED NOT NULL,
  `type`                   ENUM('checkin','scratch','spin','task','offerwall','cpa','referral','referral_commission','withdrawal_hold','withdrawal_release','withdrawal_debit','rewarded_ad','admin_credit','admin_debit','reversal','adjustment') NOT NULL,
  `source_module`          VARCHAR(40) NOT NULL,
  `source_id`              BIGINT UNSIGNED DEFAULT NULL,
  `reference_id`           VARCHAR(191) NOT NULL,
  `related_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `performed_by_admin_id`  BIGINT UNSIGNED DEFAULT NULL,
  `description`            VARCHAR(255) DEFAULT NULL,
  `metadata`               JSON DEFAULT NULL,
  `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wt_reference` (`reference_id`),
  UNIQUE KEY `uq_wt_uuid` (`uuid`),
  KEY `idx_wt_user_created` (`user_id`, `created_at`),
  KEY `idx_wt_wallet` (`wallet_id`),
  KEY `idx_wt_type` (`type`),
  KEY `idx_wt_source` (`source_module`, `source_id`),
  KEY `idx_wt_related` (`related_transaction_id`),
  KEY `idx_wt_created` (`created_at`),
  KEY `idx_wt_admin` (`performed_by_admin_id`),
  -- `amount` is BIGINT UNSIGNED (>= 0); the strict `amount > 0` invariant is
  -- enforced in the application layer. A table CHECK is omitted for portability.
  CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wt_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wt_related` FOREIGN KEY (`related_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wt_admin` FOREIGN KEY (`performed_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- B.3 currency_settings --------------------------------------------------------
CREATE TABLE `currency_settings` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coin_to_cash_rate`    DECIMAL(18,8) NOT NULL,
  `currency_code`        CHAR(3) NOT NULL DEFAULT 'INR',
  `min_withdraw_coins`   BIGINT UNSIGNED NOT NULL,
  `max_withdraw_coins`   BIGINT UNSIGNED DEFAULT NULL,
  `daily_earn_cap_coins` BIGINT UNSIGNED DEFAULT NULL,
  `is_active`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `effective_from`       DATETIME DEFAULT NULL,
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cs_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- C. REWARD MECHANICS
-- =============================================================================

-- C.1 daily_checkins -----------------------------------------------------------
CREATE TABLE `daily_checkins` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `checkin_date`   DATE NOT NULL,
  `streak_day`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `coins_awarded`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dc_user_date` (`user_id`, `checkin_date`),
  KEY `idx_dc_user` (`user_id`),
  KEY `idx_dc_transaction` (`transaction_id`),
  CONSTRAINT `fk_dc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dc_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.2 checkin_rewards_config ---------------------------------------------------
CREATE TABLE `checkin_rewards_config` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `day_number`   SMALLINT UNSIGNED NOT NULL,
  `coins`        BIGINT UNSIGNED NOT NULL,
  `is_milestone` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_crc_day` (`day_number`),
  KEY `idx_crc_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.3 scratch_cards ------------------------------------------------------------
CREATE TABLE `scratch_cards` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `source`         ENUM('daily','task','offer','purchase','admin') NOT NULL DEFAULT 'daily',
  `reward_coins`   BIGINT UNSIGNED DEFAULT NULL,
  `status`         ENUM('issued','revealed','claimed','expired') NOT NULL DEFAULT 'issued',
  `revealed_at`    DATETIME DEFAULT NULL,
  `claimed_at`     DATETIME DEFAULT NULL,
  `expires_at`     DATETIME DEFAULT NULL,
  `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sc_user_status` (`user_id`, `status`),
  KEY `idx_sc_expires` (`expires_at`),
  KEY `idx_sc_transaction` (`transaction_id`),
  CONSTRAINT `fk_sc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sc_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.4 scratch_card_config ------------------------------------------------------
CREATE TABLE `scratch_card_config` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`        VARCHAR(80) NOT NULL,
  `reward_coins` BIGINT UNSIGNED NOT NULL,
  `weight`       INT UNSIGNED NOT NULL,
  `daily_limit`  INT UNSIGNED DEFAULT NULL,
  `is_active`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scc_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.5 spin_wheel_segments ------------------------------------------------------
CREATE TABLE `spin_wheel_segments` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`        VARCHAR(80) NOT NULL,
  `reward_type`  ENUM('coins','scratch_card','nothing','bonus') NOT NULL DEFAULT 'coins',
  `reward_coins` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `weight`       INT UNSIGNED NOT NULL,
  `color_hex`    CHAR(7) DEFAULT NULL,
  `position`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sws_position` (`position`),
  KEY `idx_sws_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.6 spin_history -------------------------------------------------------------
CREATE TABLE `spin_history` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `segment_id`     BIGINT UNSIGNED NOT NULL,
  `reward_coins`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `spin_date`      DATE NOT NULL,
  `source`         ENUM('free','ad','purchase') NOT NULL DEFAULT 'free',
  `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sh_user_date` (`user_id`, `spin_date`),
  KEY `idx_sh_segment` (`segment_id`),
  KEY `idx_sh_transaction` (`transaction_id`),
  CONSTRAINT `fk_sh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sh_segment` FOREIGN KEY (`segment_id`) REFERENCES `spin_wheel_segments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sh_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.7 tasks --------------------------------------------------------------------
CREATE TABLE `tasks` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`             VARCHAR(160) NOT NULL,
  `description`       TEXT DEFAULT NULL,
  `task_type`         ENUM('social','video','install','survey','daily','custom') NOT NULL DEFAULT 'custom',
  `reward_coins`      BIGINT UNSIGNED NOT NULL,
  `action_url`        VARCHAR(512) DEFAULT NULL,
  `verification_type` ENUM('auto','manual','callback') NOT NULL DEFAULT 'manual',
  `max_completions`   INT UNSIGNED DEFAULT NULL,
  `per_user_limit`    INT UNSIGNED NOT NULL DEFAULT 1,
  `icon_url`          VARCHAR(512) DEFAULT NULL,
  `starts_at`         DATETIME DEFAULT NULL,
  `ends_at`           DATETIME DEFAULT NULL,
  `is_active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order`        INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_active_window` (`is_active`, `starts_at`, `ends_at`),
  KEY `idx_tasks_type` (`task_type`),
  KEY `idx_tasks_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- C.8 task_completions ---------------------------------------------------------
CREATE TABLE `task_completions` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `task_id`          BIGINT UNSIGNED NOT NULL,
  `status`           ENUM('started','pending','approved','rejected','credited') NOT NULL DEFAULT 'started',
  `reward_coins`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `proof_url`        VARCHAR(512) DEFAULT NULL,
  `verification_ref` VARCHAR(191) DEFAULT NULL,
  `transaction_id`   BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_by`      BIGINT UNSIGNED DEFAULT NULL,
  `completed_at`     DATETIME DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tc_user_task_ref` (`user_id`, `task_id`, `verification_ref`),
  KEY `idx_tc_user` (`user_id`),
  KEY `idx_tc_task` (`task_id`),
  KEY `idx_tc_status` (`status`),
  KEY `idx_tc_transaction` (`transaction_id`),
  KEY `idx_tc_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tc_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tc_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- D. OFFERWALL & CPA
-- =============================================================================

-- D.1 offerwall_providers ------------------------------------------------------
CREATE TABLE `offerwall_providers` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(80) NOT NULL,
  `slug`                VARCHAR(60) NOT NULL,
  `api_key_enc`         VARBINARY(512) DEFAULT NULL,
  `postback_secret_enc` VARBINARY(512) DEFAULT NULL,
  `ip_allowlist`        JSON DEFAULT NULL,
  `currency_ratio`      DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  `logo_url`            VARCHAR(512) DEFAULT NULL,
  `is_active`           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order`          INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_op_slug` (`slug`),
  KEY `idx_op_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- D.2 offers -------------------------------------------------------------------
CREATE TABLE `offers` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id`       BIGINT UNSIGNED NOT NULL,
  `external_offer_id` VARCHAR(191) DEFAULT NULL,
  `title`             VARCHAR(200) NOT NULL,
  `description`       TEXT DEFAULT NULL,
  `category`          VARCHAR(60) DEFAULT NULL,
  `payout_coins`      BIGINT UNSIGNED NOT NULL,
  `provider_payout`   DECIMAL(18,4) DEFAULT NULL,
  `tracking_url`      VARCHAR(1024) DEFAULT NULL,
  `icon_url`          VARCHAR(512) DEFAULT NULL,
  `countries`         JSON DEFAULT NULL,
  `platforms`         JSON DEFAULT NULL,
  `is_active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order`        INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_offers_provider_ext` (`provider_id`, `external_offer_id`),
  KEY `idx_offers_provider` (`provider_id`),
  KEY `idx_offers_active` (`is_active`),
  KEY `idx_offers_category` (`category`),
  CONSTRAINT `fk_offers_provider` FOREIGN KEY (`provider_id`) REFERENCES `offerwall_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- D.3 offer_clicks -------------------------------------------------------------
CREATE TABLE `offer_clicks` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `offer_id`    BIGINT UNSIGNED DEFAULT NULL,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `click_token` CHAR(36) NOT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `device_id`   BIGINT UNSIGNED DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_oc_click_token` (`click_token`),
  KEY `idx_oc_user_created` (`user_id`, `created_at`),
  KEY `idx_oc_provider` (`provider_id`),
  KEY `idx_oc_offer` (`offer_id`),
  KEY `idx_oc_device` (`device_id`),
  CONSTRAINT `fk_oc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oc_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_oc_provider` FOREIGN KEY (`provider_id`) REFERENCES `offerwall_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oc_device` FOREIGN KEY (`device_id`) REFERENCES `user_devices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- D.4 offer_conversions --------------------------------------------------------
CREATE TABLE `offer_conversions` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id`           BIGINT UNSIGNED NOT NULL,
  `user_id`               BIGINT UNSIGNED NOT NULL,
  `offer_id`              BIGINT UNSIGNED DEFAULT NULL,
  `click_id`              BIGINT UNSIGNED DEFAULT NULL,
  `transaction_id_ext`    VARCHAR(191) NOT NULL,
  `payout_coins`          BIGINT UNSIGNED NOT NULL,
  `provider_revenue`      DECIMAL(18,4) DEFAULT NULL,
  `status`                ENUM('pending','credited','reversed','rejected') NOT NULL DEFAULT 'pending',
  `wallet_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address`            VARCHAR(45) DEFAULT NULL,
  `signature_valid`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ocv_provider_txn` (`provider_id`, `transaction_id_ext`),
  KEY `idx_ocv_user_created` (`user_id`, `created_at`),
  KEY `idx_ocv_status` (`status`),
  KEY `idx_ocv_provider` (`provider_id`),
  KEY `idx_ocv_offer` (`offer_id`),
  KEY `idx_ocv_click` (`click_id`),
  KEY `idx_ocv_wallet_txn` (`wallet_transaction_id`),
  CONSTRAINT `fk_ocv_provider` FOREIGN KEY (`provider_id`) REFERENCES `offerwall_providers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ocv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ocv_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ocv_click` FOREIGN KEY (`click_id`) REFERENCES `offer_clicks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ocv_wallet_txn` FOREIGN KEY (`wallet_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- D.5 cpa_offers ---------------------------------------------------------------
CREATE TABLE `cpa_offers` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id`       BIGINT UNSIGNED NOT NULL,
  `external_offer_id` VARCHAR(191) DEFAULT NULL,
  `title`             VARCHAR(200) NOT NULL,
  `goal`              VARCHAR(160) DEFAULT NULL,
  `payout_coins`      BIGINT UNSIGNED NOT NULL,
  `payout_tiers`      JSON DEFAULT NULL,
  `countries`         JSON DEFAULT NULL,
  `tracking_url`      VARCHAR(1024) DEFAULT NULL,
  `is_active`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cpa_provider_ext` (`provider_id`, `external_offer_id`),
  KEY `idx_cpa_provider` (`provider_id`),
  KEY `idx_cpa_active` (`is_active`),
  CONSTRAINT `fk_cpa_provider` FOREIGN KEY (`provider_id`) REFERENCES `offerwall_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- D.6 postback_logs ------------------------------------------------------------
CREATE TABLE `postback_logs` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id`       BIGINT UNSIGNED DEFAULT NULL,
  `endpoint`          VARCHAR(120) NOT NULL,
  `http_method`       VARCHAR(8) NOT NULL DEFAULT 'GET',
  `raw_payload`       JSON DEFAULT NULL,
  `ip_address`        VARCHAR(45) DEFAULT NULL,
  `signature_valid`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `ip_allowed`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `processing_result` ENUM('credited','duplicate','invalid_signature','ip_blocked','user_not_found','error') NOT NULL DEFAULT 'error',
  `conversion_id`     BIGINT UNSIGNED DEFAULT NULL,
  `error_message`     VARCHAR(512) DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_provider_created` (`provider_id`, `created_at`),
  KEY `idx_pl_result` (`processing_result`),
  KEY `idx_pl_created` (`created_at`),
  KEY `idx_pl_conversion` (`conversion_id`),
  CONSTRAINT `fk_pl_provider` FOREIGN KEY (`provider_id`) REFERENCES `offerwall_providers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pl_conversion` FOREIGN KEY (`conversion_id`) REFERENCES `offer_conversions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- E. REFERRAL
-- =============================================================================

-- E.1 referrals ----------------------------------------------------------------
CREATE TABLE `referrals` (
  `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id`             BIGINT UNSIGNED NOT NULL,
  `referee_id`              BIGINT UNSIGNED NOT NULL,
  `referral_code`           VARCHAR(12) NOT NULL,
  `status`                  ENUM('pending','qualified','rewarded','rejected') NOT NULL DEFAULT 'pending',
  `signup_bonus_coins`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `referee_bonus_coins`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `qualified_at`            DATETIME DEFAULT NULL,
  `rewarded_at`             DATETIME DEFAULT NULL,
  `referrer_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `referee_transaction_id`  BIGINT UNSIGNED DEFAULT NULL,
  `signup_ip`               VARCHAR(45) DEFAULT NULL,
  `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref_referee` (`referee_id`),
  KEY `idx_ref_referrer` (`referrer_id`),
  KEY `idx_ref_status` (`status`),
  -- Invariant `referrer_id <> referee_id` (no self-referral) is enforced in the
  -- application layer; a table CHECK is omitted for MySQL/MariaDB portability.
  CONSTRAINT `fk_ref_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ref_referee` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ref_referrer_txn` FOREIGN KEY (`referrer_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ref_referee_txn` FOREIGN KEY (`referee_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- E.2 referral_config ----------------------------------------------------------
CREATE TABLE `referral_config` (
  `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_bonus_coins`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `referee_bonus_coins`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `commission_percent`       DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
  `qualification_rule`       ENUM('on_signup','on_first_earn','on_first_withdraw') NOT NULL DEFAULT 'on_first_earn',
  `commission_duration_days` INT UNSIGNED DEFAULT NULL,
  `max_referrals_per_user`   INT UNSIGNED DEFAULT NULL,
  `is_active`                TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rc_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- E.3 referral_earnings --------------------------------------------------------
CREATE TABLE `referral_earnings` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referral_id`           BIGINT UNSIGNED NOT NULL,
  `referrer_id`           BIGINT UNSIGNED NOT NULL,
  `referee_id`            BIGINT UNSIGNED NOT NULL,
  `source_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `commission_coins`      BIGINT UNSIGNED NOT NULL,
  `transaction_id`        BIGINT UNSIGNED DEFAULT NULL,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_re_source_txn` (`source_transaction_id`),
  KEY `idx_re_referrer` (`referrer_id`),
  KEY `idx_re_referral` (`referral_id`),
  KEY `idx_re_referee` (`referee_id`),
  KEY `idx_re_transaction` (`transaction_id`),
  CONSTRAINT `fk_re_referral` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_re_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_re_referee` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_re_source_txn` FOREIGN KEY (`source_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_re_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- F. LEADERBOARD
-- =============================================================================

-- F.1 leaderboard_periods ------------------------------------------------------
CREATE TABLE `leaderboard_periods` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period_type`   ENUM('daily','weekly','monthly','all_time') NOT NULL,
  `period_key`    VARCHAR(20) NOT NULL,
  `starts_at`     DATETIME NOT NULL,
  `ends_at`       DATETIME DEFAULT NULL,
  `status`        ENUM('active','closed') NOT NULL DEFAULT 'active',
  `reward_config` JSON DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lp_type_key` (`period_type`, `period_key`),
  KEY `idx_lp_type_status` (`period_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- F.2 leaderboard_entries ------------------------------------------------------
CREATE TABLE `leaderboard_entries` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `period_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `score`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `rank`         INT UNSIGNED DEFAULT NULL,
  `reward_coins` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `rewarded`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_le_period_user` (`period_id`, `user_id`),
  KEY `idx_le_period_rank` (`period_id`, `rank`),
  KEY `idx_le_period_score` (`period_id`, `score`),
  KEY `idx_le_user` (`user_id`),
  CONSTRAINT `fk_le_period` FOREIGN KEY (`period_id`) REFERENCES `leaderboard_periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_le_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- G. WITHDRAWALS
-- =============================================================================

-- G.1 withdraw_methods ---------------------------------------------------------
CREATE TABLE `withdraw_methods` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(80) NOT NULL,
  `code`          VARCHAR(40) NOT NULL,
  `gateway_id`    BIGINT UNSIGNED DEFAULT NULL,
  `min_coins`     BIGINT UNSIGNED NOT NULL,
  `max_coins`     BIGINT UNSIGNED DEFAULT NULL,
  `fee_percent`   DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
  `fee_flat`      DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `detail_schema` JSON DEFAULT NULL,
  `icon_url`      VARCHAR(512) DEFAULT NULL,
  `is_active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order`    INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wm_code` (`code`),
  KEY `idx_wm_active` (`is_active`),
  KEY `idx_wm_gateway` (`gateway_id`),
  CONSTRAINT `fk_wm_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- G.2 withdraw_requests --------------------------------------------------------
CREATE TABLE `withdraw_requests` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                  CHAR(36) NOT NULL,
  `user_id`               BIGINT UNSIGNED NOT NULL,
  `method_id`             BIGINT UNSIGNED NOT NULL,
  `gateway_id`            BIGINT UNSIGNED DEFAULT NULL,
  `coins_amount`          BIGINT UNSIGNED NOT NULL,
  `cash_amount`           DECIMAL(18,4) UNSIGNED NOT NULL,
  `fee_amount`            DECIMAL(18,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `net_amount`            DECIMAL(18,4) UNSIGNED NOT NULL,
  `currency_code`         CHAR(3) NOT NULL DEFAULT 'INR',
  `conversion_rate`       DECIMAL(18,8) NOT NULL,
  `payment_detail`        JSON NOT NULL,
  `status`                ENUM('pending','approved','processing','paid','rejected','cancelled','failed') NOT NULL DEFAULT 'pending',
  `hold_transaction_id`   BIGINT UNSIGNED DEFAULT NULL,
  `debit_transaction_id`  BIGINT UNSIGNED DEFAULT NULL,
  `refund_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `admin_id`              BIGINT UNSIGNED DEFAULT NULL,
  `admin_note`            VARCHAR(512) DEFAULT NULL,
  `external_reference`    VARCHAR(191) DEFAULT NULL,
  `requested_ip`          VARCHAR(45) DEFAULT NULL,
  `processed_at`          DATETIME DEFAULT NULL,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wr_uuid` (`uuid`),
  KEY `idx_wr_user_created` (`user_id`, `created_at`),
  KEY `idx_wr_status` (`status`),
  KEY `idx_wr_gateway` (`gateway_id`),
  KEY `idx_wr_created` (`created_at`),
  KEY `idx_wr_method` (`method_id`),
  KEY `idx_wr_admin` (`admin_id`),
  CONSTRAINT `fk_wr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_method` FOREIGN KEY (`method_id`) REFERENCES `withdraw_methods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_hold_txn` FOREIGN KEY (`hold_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_debit_txn` FOREIGN KEY (`debit_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_refund_txn` FOREIGN KEY (`refund_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wr_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- G.3 withdraw_history ---------------------------------------------------------
CREATE TABLE `withdraw_history` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `withdraw_request_id` BIGINT UNSIGNED NOT NULL,
  `from_status`         VARCHAR(20) DEFAULT NULL,
  `to_status`           VARCHAR(20) NOT NULL,
  `changed_by_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `note`                VARCHAR(512) DEFAULT NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wh_request` (`withdraw_request_id`),
  KEY `idx_wh_created` (`created_at`),
  KEY `idx_wh_admin` (`changed_by_admin_id`),
  CONSTRAINT `fk_wh_request` FOREIGN KEY (`withdraw_request_id`) REFERENCES `withdraw_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wh_admin` FOREIGN KEY (`changed_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- H. ENGAGEMENT & SYSTEM
-- =============================================================================

-- H.1 notifications ------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `campaign_id` BIGINT UNSIGNED DEFAULT NULL,
  `title`       VARCHAR(160) NOT NULL,
  `body`        VARCHAR(1000) NOT NULL,
  `type`        ENUM('transactional','engagement','promotional','system') NOT NULL DEFAULT 'system',
  `deep_link`   VARCHAR(512) DEFAULT NULL,
  `image_url`   VARCHAR(512) DEFAULT NULL,
  `data`        JSON DEFAULT NULL,
  `is_read`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `read_at`     DATETIME DEFAULT NULL,
  `push_status` ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_read_created` (`user_id`, `is_read`, `created_at`),
  KEY `idx_notif_campaign` (`campaign_id`),
  KEY `idx_notif_created` (`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `notification_campaigns` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.2 notification_campaigns ---------------------------------------------------
CREATE TABLE `notification_campaigns` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(160) NOT NULL,
  `body`            VARCHAR(1000) NOT NULL,
  `type`            ENUM('engagement','promotional','system') NOT NULL DEFAULT 'promotional',
  `audience`        ENUM('all','segment','single') NOT NULL DEFAULT 'all',
  `audience_filter` JSON DEFAULT NULL,
  `deep_link`       VARCHAR(512) DEFAULT NULL,
  `image_url`       VARCHAR(512) DEFAULT NULL,
  `scheduled_at`    DATETIME DEFAULT NULL,
  `status`          ENUM('draft','scheduled','sending','sent','cancelled','failed') NOT NULL DEFAULT 'draft',
  `total_targeted`  INT UNSIGNED NOT NULL DEFAULT 0,
  `total_sent`      INT UNSIGNED NOT NULL DEFAULT 0,
  `total_failed`    INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nc_status_scheduled` (`status`, `scheduled_at`),
  KEY `idx_nc_created_by` (`created_by`),
  CONSTRAINT `fk_nc_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.3 support_tickets ----------------------------------------------------------
CREATE TABLE `support_tickets` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36) NOT NULL,
  `user_id`           BIGINT UNSIGNED NOT NULL,
  `subject`           VARCHAR(200) NOT NULL,
  `category`          VARCHAR(60) DEFAULT NULL,
  `status`            ENUM('open','pending','answered','resolved','closed') NOT NULL DEFAULT 'open',
  `priority`          ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `assigned_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `last_reply_at`     DATETIME DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_st_uuid` (`uuid`),
  KEY `idx_st_user` (`user_id`),
  KEY `idx_st_status_priority` (`status`, `priority`),
  KEY `idx_st_assigned` (`assigned_admin_id`),
  CONSTRAINT `fk_st_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_st_assigned` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.4 support_messages ---------------------------------------------------------
CREATE TABLE `support_messages` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id`       BIGINT UNSIGNED NOT NULL,
  `sender_type`     ENUM('user','admin','system') NOT NULL,
  `sender_user_id`  BIGINT UNSIGNED DEFAULT NULL,
  `sender_admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `message`         TEXT NOT NULL,
  `attachment_url`  VARCHAR(512) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_ticket_created` (`ticket_id`, `created_at`),
  KEY `idx_sm_sender_user` (`sender_user_id`),
  KEY `idx_sm_sender_admin` (`sender_admin_id`),
  CONSTRAINT `fk_sm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_sender_user` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_sender_admin` FOREIGN KEY (`sender_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.5 faqs ---------------------------------------------------------------------
CREATE TABLE `faqs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`  BIGINT UNSIGNED DEFAULT NULL,
  `question`     VARCHAR(255) NOT NULL,
  `answer`       TEXT NOT NULL,
  `locale`       VARCHAR(10) NOT NULL DEFAULT 'en',
  `sort_order`   INT UNSIGNED NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faq_category` (`category_id`),
  KEY `idx_faq_published_sort` (`is_published`, `sort_order`),
  CONSTRAINT `fk_faq_category` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.6 faq_categories -----------------------------------------------------------
CREATE TABLE `faq_categories` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fc_slug` (`slug`),
  KEY `idx_fc_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- H.7 app_settings -------------------------------------------------------------
CREATE TABLE `app_settings` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(120) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `value_type`    ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  `group_name`    VARCHAR(60) DEFAULT NULL,
  `is_public`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_as_key` (`setting_key`),
  KEY `idx_as_group` (`group_name`),
  KEY `idx_as_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- I. ADMIN & GOVERNANCE
-- =============================================================================

-- I.2 admin_roles (defined before admins for FK readability) -------------------
CREATE TABLE `admin_roles` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80) NOT NULL,
  `slug`        VARCHAR(60) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ar_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- I.1 admins -------------------------------------------------------------------
CREATE TABLE `admins` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`           BIGINT UNSIGNED NOT NULL,
  `name`              VARCHAR(120) NOT NULL,
  `email`             VARCHAR(255) NOT NULL,
  `password_hash`     VARCHAR(255) NOT NULL,
  `two_fa_secret_enc` VARBINARY(512) DEFAULT NULL,
  `two_fa_enabled`    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `status`            ENUM('active','suspended','disabled') NOT NULL DEFAULT 'active',
  `last_login_at`     DATETIME DEFAULT NULL,
  `last_login_ip`     VARCHAR(45) DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_email` (`email`),
  KEY `idx_admins_role` (`role_id`),
  KEY `idx_admins_status` (`status`),
  CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- I.3 admin_permissions --------------------------------------------------------
CREATE TABLE `admin_permissions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module`      VARCHAR(60) NOT NULL,
  `action`      VARCHAR(60) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ap_slug` (`slug`),
  KEY `idx_ap_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- I.4 admin_role_permissions (junction) ----------------------------------------
CREATE TABLE `admin_role_permissions` (
  `role_id`       BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_arp_permission` (`permission_id`),
  CONSTRAINT `fk_arp_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_arp_permission` FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- I.5 admin_audit_logs ---------------------------------------------------------
CREATE TABLE `admin_audit_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    BIGINT UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(120) NOT NULL,
  `target_type` VARCHAR(80) DEFAULT NULL,
  `target_id`   BIGINT UNSIGNED DEFAULT NULL,
  `before_data` JSON DEFAULT NULL,
  `after_data`  JSON DEFAULT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aal_admin` (`admin_id`),
  KEY `idx_aal_target` (`target_type`, `target_id`),
  KEY `idx_aal_action` (`action`),
  KEY `idx_aal_created` (`created_at`),
  CONSTRAINT `fk_aal_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- I.6 fraud_flags --------------------------------------------------------------
CREATE TABLE `fraud_flags` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `flag_type`     ENUM('multi_account','vpn','emulator','velocity','self_referral','chargeback','manual','other') NOT NULL,
  `severity`      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `source_module` VARCHAR(40) DEFAULT NULL,
  `reference_id`  VARCHAR(191) DEFAULT NULL,
  `details`       JSON DEFAULT NULL,
  `status`        ENUM('open','reviewing','confirmed','dismissed') NOT NULL DEFAULT 'open',
  `action_taken`  ENUM('none','warned','withdrawals_held','suspended','banned') NOT NULL DEFAULT 'none',
  `reviewed_by`   BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at`   DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ff_user_type_ref` (`user_id`, `flag_type`, `reference_id`),
  KEY `idx_ff_user` (`user_id`),
  KEY `idx_ff_status_severity` (`status`, `severity`),
  KEY `idx_ff_type` (`flag_type`),
  KEY `idx_ff_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_ff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ff_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- J. ADS (MULTI-NETWORK MANAGEMENT)
-- =============================================================================

-- J.1 ads_placements -----------------------------------------------------------
CREATE TABLE `ads_placements` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`               VARCHAR(100) NOT NULL,
  `code`               VARCHAR(60) NOT NULL,
  `ad_format`          ENUM('banner','interstitial','rewarded','native','app_open') NOT NULL,
  `reward_coins`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `daily_cap_per_user` INT UNSIGNED DEFAULT NULL,
  `is_active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apl_code` (`code`),
  KEY `idx_apl_active` (`is_active`),
  KEY `idx_apl_format` (`ad_format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- J.2 ad_networks --------------------------------------------------------------
CREATE TABLE `ad_networks` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `network`      ENUM('admob','applovin_max','unity_ads') NOT NULL,
  `display_name` VARCHAR(80) NOT NULL,
  `app_id`       VARCHAR(191) DEFAULT NULL,
  `api_key_enc`  VARBINARY(512) DEFAULT NULL,
  `config`       JSON DEFAULT NULL,
  `is_enabled`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `priority`     INT UNSIGNED NOT NULL DEFAULT 100,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_an_network` (`network`),
  KEY `idx_an_enabled_priority` (`is_enabled`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- J.3 ad_units -----------------------------------------------------------------
CREATE TABLE `ad_units` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `network_id`   BIGINT UNSIGNED NOT NULL,
  `placement_id` BIGINT UNSIGNED NOT NULL,
  `ad_unit_id`   VARCHAR(191) NOT NULL,
  `ad_format`    ENUM('banner','interstitial','rewarded','native','app_open') NOT NULL,
  `country_code` CHAR(2) DEFAULT NULL,
  `priority`     INT UNSIGNED NOT NULL DEFAULT 100,
  `ecpm_floor`   DECIMAL(10,4) DEFAULT NULL,
  `is_enabled`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_au_network_unit` (`network_id`, `ad_unit_id`),
  KEY `idx_au_placement_priority` (`placement_id`, `is_enabled`, `priority`),
  KEY `idx_au_network` (`network_id`),
  KEY `idx_au_country` (`country_code`),
  CONSTRAINT `fk_au_network` FOREIGN KEY (`network_id`) REFERENCES `ad_networks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_au_placement` FOREIGN KEY (`placement_id`) REFERENCES `ads_placements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- J.4 ad_network_events --------------------------------------------------------
CREATE TABLE `ad_network_events` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NOT NULL,
  `network_id`     BIGINT UNSIGNED DEFAULT NULL,
  `placement_id`   BIGINT UNSIGNED DEFAULT NULL,
  `unit_id`        BIGINT UNSIGNED DEFAULT NULL,
  `event_type`     ENUM('impression','click','rewarded_start','rewarded_complete','failed') NOT NULL,
  `ssv_token`      VARCHAR(255) DEFAULT NULL,
  `ssv_verified`   TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `reward_coins`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address`     VARCHAR(45) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ane_ssv_token` (`ssv_token`),
  KEY `idx_ane_user_created` (`user_id`, `created_at`),
  KEY `idx_ane_type` (`event_type`),
  KEY `idx_ane_placement` (`placement_id`),
  KEY `idx_ane_network` (`network_id`),
  KEY `idx_ane_unit` (`unit_id`),
  KEY `idx_ane_transaction` (`transaction_id`),
  CONSTRAINT `fk_ane_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ane_network` FOREIGN KEY (`network_id`) REFERENCES `ad_networks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ane_placement` FOREIGN KEY (`placement_id`) REFERENCES `ads_placements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ane_unit` FOREIGN KEY (`unit_id`) REFERENCES `ad_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ane_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- K. PAYMENTS
-- =============================================================================

-- K.1 payment_gateways ---------------------------------------------------------
CREATE TABLE `payment_gateways` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                 VARCHAR(80) NOT NULL,
  `code`                 VARCHAR(40) NOT NULL,
  `type`                 ENUM('payout','collection','both') NOT NULL DEFAULT 'payout',
  `credentials_enc`      VARBINARY(1024) DEFAULT NULL,
  `config`               JSON DEFAULT NULL,
  `mode`                 ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `fee_percent`          DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
  `fee_flat`             DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `min_amount`           DECIMAL(18,4) DEFAULT NULL,
  `max_amount`           DECIMAL(18,4) DEFAULT NULL,
  `supported_currencies` JSON DEFAULT NULL,
  `is_enabled`           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `priority`             INT UNSIGNED NOT NULL DEFAULT 100,
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pg_code` (`code`),
  KEY `idx_pg_enabled_priority` (`is_enabled`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- K.2 gateway_transactions -----------------------------------------------------
CREATE TABLE `gateway_transactions` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway_id`          BIGINT UNSIGNED NOT NULL,
  `withdraw_request_id` BIGINT UNSIGNED NOT NULL,
  `idempotency_key`     VARCHAR(191) NOT NULL,
  `direction`           ENUM('payout','collection') NOT NULL DEFAULT 'payout',
  `amount`              DECIMAL(18,4) NOT NULL,
  `currency_code`       CHAR(3) NOT NULL DEFAULT 'INR',
  `status`              ENUM('initiated','processing','success','failed','refunded') NOT NULL DEFAULT 'initiated',
  `external_reference`  VARCHAR(191) DEFAULT NULL,
  `request_payload`     JSON DEFAULT NULL,
  `response_payload`    JSON DEFAULT NULL,
  `error_code`          VARCHAR(80) DEFAULT NULL,
  `error_message`       VARCHAR(512) DEFAULT NULL,
  `processed_at`        DATETIME DEFAULT NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gt_idempotency` (`gateway_id`, `idempotency_key`),
  UNIQUE KEY `uq_gt_external` (`gateway_id`, `external_reference`),
  KEY `idx_gt_request` (`withdraw_request_id`),
  KEY `idx_gt_gateway_status` (`gateway_id`, `status`),
  KEY `idx_gt_created` (`created_at`),
  CONSTRAINT `fk_gt_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_gt_request` FOREIGN KEY (`withdraw_request_id`) REFERENCES `withdraw_requests` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- L. CONTENT & CONFIG (SERVER-DRIVEN)
-- =============================================================================

-- L.1 banners ------------------------------------------------------------------
CREATE TABLE `banners` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(160) DEFAULT NULL,
  `image_url`       VARCHAR(512) NOT NULL,
  `placement`       VARCHAR(60) NOT NULL DEFAULT 'home_top',
  `action_type`     ENUM('none','deep_link','url','offer','task') NOT NULL DEFAULT 'none',
  `action_value`    VARCHAR(512) DEFAULT NULL,
  `target_audience` JSON DEFAULT NULL,
  `sort_order`      INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at`       DATETIME DEFAULT NULL,
  `ends_at`         DATETIME DEFAULT NULL,
  `is_active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ban_active_window` (`is_active`, `starts_at`, `ends_at`),
  KEY `idx_ban_placement_sort` (`placement`, `sort_order`),
  KEY `idx_ban_created_by` (`created_by`),
  CONSTRAINT `fk_ban_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.2 announcements ------------------------------------------------------------
CREATE TABLE `announcements` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           VARCHAR(160) NOT NULL,
  `body`            TEXT NOT NULL,
  `display_type`    ENUM('bar','popup','card') NOT NULL DEFAULT 'bar',
  `priority`        INT UNSIGNED NOT NULL DEFAULT 100,
  `action_type`     ENUM('none','deep_link','url') NOT NULL DEFAULT 'none',
  `action_value`    VARCHAR(512) DEFAULT NULL,
  `target_audience` JSON DEFAULT NULL,
  `is_dismissible`  TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `starts_at`       DATETIME DEFAULT NULL,
  `ends_at`         DATETIME DEFAULT NULL,
  `is_active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_active_window` (`is_active`, `starts_at`, `ends_at`),
  KEY `idx_ann_priority` (`priority`),
  KEY `idx_ann_created_by` (`created_by`),
  CONSTRAINT `fk_ann_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.3 announcement_reads -------------------------------------------------------
CREATE TABLE `announcement_reads` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` BIGINT UNSIGNED NOT NULL,
  `user_id`         BIGINT UNSIGNED NOT NULL,
  `seen_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anr_ann_user` (`announcement_id`, `user_id`),
  KEY `idx_anr_user` (`user_id`),
  CONSTRAINT `fk_anr_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_anr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.4 app_versions -------------------------------------------------------------
CREATE TABLE `app_versions` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform`            ENUM('android','ios') NOT NULL DEFAULT 'android',
  `latest_version`      VARCHAR(20) NOT NULL,
  `latest_version_code` INT UNSIGNED NOT NULL,
  `min_supported_code`  INT UNSIGNED NOT NULL,
  `force_update`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `changelog`           TEXT DEFAULT NULL,
  `store_url`           VARCHAR(512) DEFAULT NULL,
  `is_active`           TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_av_platform_active` (`platform`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.5 maintenance_windows ------------------------------------------------------
CREATE TABLE `maintenance_windows` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `is_enabled`         TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `title`              VARCHAR(160) DEFAULT NULL,
  `message`            TEXT DEFAULT NULL,
  `scheduled_start`    DATETIME DEFAULT NULL,
  `scheduled_end`      DATETIME DEFAULT NULL,
  `allow_admin_bypass` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `created_by`         BIGINT UNSIGNED DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mw_enabled` (`is_enabled`),
  KEY `idx_mw_created_by` (`created_by`),
  CONSTRAINT `fk_mw_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.6 remote_configs -----------------------------------------------------------
CREATE TABLE `remote_configs` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `config_key`       VARCHAR(120) NOT NULL,
  `value_type`       ENUM('bool','int','float','string','json') NOT NULL DEFAULT 'string',
  `value`            TEXT DEFAULT NULL,
  `environment`      ENUM('production','staging','all') NOT NULL DEFAULT 'all',
  `audience_segment` JSON DEFAULT NULL,
  `description`      VARCHAR(255) DEFAULT NULL,
  `is_active`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `updated_by`       BIGINT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rc_key_env` (`config_key`, `environment`),
  KEY `idx_rc_active` (`is_active`),
  KEY `idx_rc_env` (`environment`),
  KEY `idx_rc_updated_by` (`updated_by`),
  CONSTRAINT `fk_rc_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.7 cms_pages ----------------------------------------------------------------
CREATE TABLE `cms_pages` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(80) NOT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `body`         MEDIUMTEXT NOT NULL,
  `locale`       VARCHAR(10) NOT NULL DEFAULT 'en',
  `version`      INT UNSIGNED NOT NULL DEFAULT 1,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `effective_at` DATETIME DEFAULT NULL,
  `updated_by`   BIGINT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cms_slug_locale_version` (`slug`, `locale`, `version`),
  KEY `idx_cms_published` (`is_published`),
  KEY `idx_cms_updated_by` (`updated_by`),
  CONSTRAINT `fk_cms_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.8 home_sections ------------------------------------------------------------
CREATE TABLE `home_sections` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_type`    ENUM('banner_carousel','quick_actions','offers','tasks','leaderboard','scratch','spin','custom') NOT NULL,
  `title`           VARCHAR(160) DEFAULT NULL,
  `config`          JSON DEFAULT NULL,
  `sort_order`      INT UNSIGNED NOT NULL DEFAULT 0,
  `target_audience` JSON DEFAULT NULL,
  `is_active`       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `starts_at`       DATETIME DEFAULT NULL,
  `ends_at`         DATETIME DEFAULT NULL,
  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hs_active_sort` (`is_active`, `sort_order`),
  KEY `idx_hs_created_by` (`created_by`),
  CONSTRAINT `fk_hs_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- L.9 themes -------------------------------------------------------------------
CREATE TABLE `themes` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(80) NOT NULL,
  `primary_color`    CHAR(7) NOT NULL,
  `secondary_color`  CHAR(7) DEFAULT NULL,
  `accent_color`     CHAR(7) DEFAULT NULL,
  `background_color` CHAR(7) DEFAULT NULL,
  `logo_url`         VARCHAR(512) DEFAULT NULL,
  `font_family`      VARCHAR(80) DEFAULT NULL,
  `default_mode`     ENUM('system','light','dark') NOT NULL DEFAULT 'system',
  `extra`            JSON DEFAULT NULL,
  `is_active`        TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_themes_name` (`name`),
  KEY `idx_themes_active` (`is_active`),
  KEY `idx_themes_created_by` (`created_by`),
  CONSTRAINT `fk_themes_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- =============================================================================
-- M. OPERATIONS
-- =============================================================================

-- M.1 backup_jobs --------------------------------------------------------------
CREATE TABLE `backup_jobs` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`             ENUM('manual','scheduled') NOT NULL DEFAULT 'manual',
  `status`           ENUM('queued','running','success','failed') NOT NULL DEFAULT 'queued',
  `storage_location` ENUM('local','s3','gcs','ftp','other') NOT NULL DEFAULT 'local',
  `file_path`        VARCHAR(512) DEFAULT NULL,
  `file_size_bytes`  BIGINT UNSIGNED DEFAULT NULL,
  `checksum`         CHAR(64) DEFAULT NULL,
  `is_encrypted`     TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  `started_at`       DATETIME DEFAULT NULL,
  `completed_at`     DATETIME DEFAULT NULL,
  `error_message`    VARCHAR(512) DEFAULT NULL,
  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bj_status` (`status`),
  KEY `idx_bj_created` (`created_at`),
  KEY `idx_bj_created_by` (`created_by`),
  CONSTRAINT `fk_bj_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- M.2 restore_logs -------------------------------------------------------------
CREATE TABLE `restore_logs` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `backup_job_id`     BIGINT UNSIGNED NOT NULL,
  `status`            ENUM('initiated','running','success','failed') NOT NULL DEFAULT 'initiated',
  `checksum_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `performed_by`      BIGINT UNSIGNED DEFAULT NULL,
  `notes`             VARCHAR(512) DEFAULT NULL,
  `error_message`     VARCHAR(512) DEFAULT NULL,
  `started_at`        DATETIME DEFAULT NULL,
  `completed_at`      DATETIME DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rl_backup` (`backup_job_id`),
  KEY `idx_rl_status` (`status`),
  KEY `idx_rl_performed_by` (`performed_by`),
  CONSTRAINT `fk_rl_backup` FOREIGN KEY (`backup_job_id`) REFERENCES `backup_jobs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rl_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- =============================================================================
-- SEED DATA
-- =============================================================================
-- All monetary/reward values below are safe production defaults; tune via the
-- Admin panel. Encrypted (*_enc) credential columns are intentionally left NULL
-- and must be populated (write-only) through the Admin UI.
-- =============================================================================

-- ---- RBAC: roles -------------------------------------------------------------
INSERT INTO `admin_roles` (`name`, `slug`, `description`, `is_system`) VALUES
  ('Super Admin', 'super_admin', 'Full, unrestricted access to every module.', 1),
  ('Finance',     'finance',     'Withdrawals, payments, wallet adjustments and reports.', 1),
  ('Support',     'support',     'User support, tickets and read-only user access.', 1),
  ('Moderator',   'moderator',   'Fraud review, content moderation and user actions.', 1);

-- ---- RBAC: permissions (canonical module.action list) ------------------------
INSERT INTO `admin_permissions` (`module`, `action`, `slug`, `description`) VALUES
  ('dashboard',     'view',    'dashboard.view',     'View the admin dashboard.'),
  ('users',         'view',    'users.view',         'View users.'),
  ('users',         'update',  'users.update',       'Edit user profiles.'),
  ('users',         'ban',     'users.ban',          'Suspend or ban users.'),
  ('users',         'delete',  'users.delete',       'Delete/anonymize users.'),
  ('wallet',        'view',    'wallet.view',        'View wallets and ledger.'),
  ('wallet',        'adjust',  'wallet.adjust',      'Manual credit/debit adjustments.'),
  ('withdrawals',   'view',    'withdrawals.view',   'View withdrawal requests.'),
  ('withdrawals',   'approve', 'withdrawals.approve','Approve withdrawal requests.'),
  ('withdrawals',   'reject',  'withdrawals.reject', 'Reject withdrawal requests.'),
  ('withdrawals',   'process', 'withdrawals.process','Mark withdrawals paid/failed.'),
  ('rewards',       'view',    'rewards.view',       'View reward mechanics config.'),
  ('rewards',       'manage',  'rewards.manage',     'Manage check-in, scratch, spin, tasks.'),
  ('offerwall',     'view',    'offerwall.view',     'View offerwall providers and offers.'),
  ('offerwall',     'manage',  'offerwall.manage',   'Manage offerwall providers and offers.'),
  ('referrals',     'view',    'referrals.view',     'View referrals.'),
  ('referrals',     'manage',  'referrals.manage',   'Manage referral configuration.'),
  ('leaderboard',   'view',    'leaderboard.view',   'View leaderboards.'),
  ('leaderboard',   'manage',  'leaderboard.manage', 'Manage leaderboard periods/prizes.'),
  ('notifications', 'view',    'notifications.view', 'View notifications/campaigns.'),
  ('notifications', 'send',    'notifications.send', 'Create and send campaigns.'),
  ('support',       'view',    'support.view',       'View support tickets.'),
  ('support',       'reply',   'support.reply',      'Reply to and manage tickets.'),
  ('cms',           'view',    'cms.view',           'View CMS content and FAQs.'),
  ('cms',           'manage',  'cms.manage',         'Manage banners, announcements, pages, FAQs.'),
  ('ads',           'view',    'ads.view',           'View ad networks and placements.'),
  ('ads',           'manage',  'ads.manage',         'Manage ad networks, units and placements.'),
  ('payments',      'view',    'payments.view',      'View payment gateways.'),
  ('payments',      'manage',  'payments.manage',    'Manage payment gateways.'),
  ('fraud',         'view',    'fraud.view',         'View fraud flags.'),
  ('fraud',         'manage',  'fraud.manage',       'Review and resolve fraud flags.'),
  ('admins',        'view',    'admins.view',        'View admin accounts.'),
  ('admins',        'manage',  'admins.manage',      'Create/edit admins and roles.'),
  ('settings',      'view',    'settings.view',      'View app settings and remote config.'),
  ('settings',      'manage',  'settings.manage',    'Edit app settings and remote config.'),
  ('backups',       'view',    'backups.view',       'View backup/restore jobs.'),
  ('backups',       'manage',  'backups.manage',     'Run backups and restores.'),
  ('reports',       'view',    'reports.view',       'View reports and analytics.');

-- ---- RBAC: grant every permission to super_admin -----------------------------
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `admin_roles` r
CROSS JOIN `admin_permissions` p
WHERE r.`slug` = 'super_admin';

-- ---- RBAC: sensible defaults for the other built-in roles --------------------
INSERT INTO `admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `admin_roles` r
JOIN `admin_permissions` p
  ON (r.`slug` = 'finance'   AND p.`slug` IN ('dashboard.view','users.view','wallet.view','wallet.adjust',
        'withdrawals.view','withdrawals.approve','withdrawals.reject','withdrawals.process',
        'payments.view','payments.manage','reports.view'))
  OR (r.`slug` = 'support'   AND p.`slug` IN ('dashboard.view','users.view','wallet.view',
        'withdrawals.view','support.view','support.reply','cms.view','notifications.view'))
  OR (r.`slug` = 'moderator' AND p.`slug` IN ('dashboard.view','users.view','users.ban',
        'fraud.view','fraud.manage','cms.view','cms.manage','offerwall.view'));

-- ---- Default super-admin account ---------------------------------------------
-- Email    : admin@cashnest.app
-- Password : ChangeMe!Admin2026   (bcrypt via PHP password_hash / PASSWORD_DEFAULT)
-- SECURITY : change this password and enable 2FA on first login.
INSERT INTO `admins` (`role_id`, `name`, `email`, `password_hash`, `status`)
SELECT r.`id`, 'Super Admin', 'admin@cashnest.app',
       '$2y$12$IW7brKgf7DFNJaKe0NYkveFUADo2VWHm3eHzzLQdq1g6FJoU0AWwi', 'active'
FROM `admin_roles` r WHERE r.`slug` = 'super_admin';

-- ---- Currency / conversion (1000 coins = 1.00 INR; min 10,000 coins) ---------
INSERT INTO `currency_settings`
  (`coin_to_cash_rate`, `currency_code`, `min_withdraw_coins`, `max_withdraw_coins`, `daily_earn_cap_coins`, `is_active`, `effective_from`)
VALUES
  (0.00100000, 'INR', 10000, 5000000, 200000, 1, CURRENT_TIMESTAMP);

-- ---- Daily check-in reward ladder (7-day cycle) ------------------------------
INSERT INTO `checkin_rewards_config` (`day_number`, `coins`, `is_milestone`, `is_active`) VALUES
  (1, 10, 0, 1), (2, 15, 0, 1), (3, 20, 0, 1), (4, 25, 0, 1),
  (5, 30, 0, 1), (6, 40, 0, 1), (7, 100, 1, 1);

-- ---- Scratch card prize pool (weighted) --------------------------------------
INSERT INTO `scratch_card_config` (`label`, `reward_coins`, `weight`, `daily_limit`, `is_active`) VALUES
  ('5 Coins',   5,   500,  NULL, 1),
  ('10 Coins',  10,  300,  NULL, 1),
  ('25 Coins',  25,  150,  NULL, 1),
  ('50 Coins',  50,  40,   NULL, 1),
  ('100 Coins', 100, 9,    1,    1),
  ('500 Coins', 500, 1,    1,    1);

-- ---- Spin wheel segments (8 slices) ------------------------------------------
INSERT INTO `spin_wheel_segments` (`label`, `reward_type`, `reward_coins`, `weight`, `color_hex`, `position`, `is_active`) VALUES
  ('5',        'coins',        5,   300, '#0B6E4F', 0, 1),
  ('10',       'coins',        10,  250, '#12A366', 1, 1),
  ('Try Again','nothing',      0,   200, '#9AA0A6', 2, 1),
  ('20',       'coins',        20,  120, '#0B6E4F', 3, 1),
  ('Scratch',  'scratch_card', 0,   60,  '#F5A623', 4, 1),
  ('50',       'coins',        50,  40,  '#12A366', 5, 1),
  ('Bonus',    'bonus',        0,   20,  '#7B61FF', 6, 1),
  ('100',      'coins',        100, 10,  '#E4572E', 7, 1);

-- ---- Referral configuration --------------------------------------------------
INSERT INTO `referral_config`
  (`referrer_bonus_coins`, `referee_bonus_coins`, `commission_percent`, `qualification_rule`, `commission_duration_days`, `max_referrals_per_user`, `is_active`)
VALUES
  (500, 250, 0.1000, 'on_first_earn', NULL, NULL, 1);

-- ---- Payment gateways (sandbox; credentials configured in Admin) -------------
INSERT INTO `payment_gateways`
  (`name`, `code`, `type`, `mode`, `fee_percent`, `fee_flat`, `min_amount`, `max_amount`, `supported_currencies`, `is_enabled`, `priority`)
VALUES
  ('Razorpay Payouts', 'razorpay', 'payout', 'sandbox', 0.0200, 0.0000, 1.0000, 100000.0000, JSON_ARRAY('INR'),        0, 10),
  ('PayPal Payouts',   'paypal',   'payout', 'sandbox', 0.0000, 0.0000, 1.0000, 100000.0000, JSON_ARRAY('USD','INR'), 0, 20);

-- ---- Withdraw methods (mapped to gateways where applicable) ------------------
INSERT INTO `withdraw_methods`
  (`name`, `code`, `gateway_id`, `min_coins`, `max_coins`, `fee_percent`, `fee_flat`, `detail_schema`, `is_active`, `sort_order`)
VALUES
  ('UPI',              'upi',       (SELECT id FROM payment_gateways WHERE code='razorpay'),
     10000, 5000000, 0.0000, 0.0000,
     JSON_OBJECT('fields', JSON_ARRAY(JSON_OBJECT('key','upi_id','label','UPI ID','type','string','required',true))), 1, 10),
  ('PayPal',           'paypal',    (SELECT id FROM payment_gateways WHERE code='paypal'),
     50000, 5000000, 0.0000, 0.0000,
     JSON_OBJECT('fields', JSON_ARRAY(JSON_OBJECT('key','email','label','PayPal Email','type','email','required',true))), 1, 20),
  ('Amazon Gift Card', 'amazon_gc', NULL,
     20000, 1000000, 0.0000, 0.0000,
     JSON_OBJECT('fields', JSON_ARRAY(JSON_OBJECT('key','email','label','Delivery Email','type','email','required',true))), 1, 30);

-- ---- App settings (public feature flags + config) ----------------------------
INSERT INTO `app_settings` (`setting_key`, `setting_value`, `value_type`, `group_name`, `is_public`) VALUES
  ('app_name',              'CashNest',            'string', 'general',  1),
  ('support_email',         'support@cashnest.app','string', 'general',  1),
  ('min_withdraw_coins',    '10000',               'int',    'wallet',   1),
  ('coin_to_cash_rate',     '0.001',               'string', 'wallet',   1),
  ('currency_code',         'INR',                 'string', 'wallet',   1),
  ('daily_spin_limit',      '3',                   'int',    'rewards',  1),
  ('feature_offerwall',     'true',                'bool',   'features', 1),
  ('feature_referrals',     'true',                'bool',   'features', 1),
  ('feature_leaderboard',   'true',                'bool',   'features', 1),
  ('maintenance_mode',      'false',               'bool',   'system',   1);

-- ---- App versions ------------------------------------------------------------
INSERT INTO `app_versions`
  (`platform`, `latest_version`, `latest_version_code`, `min_supported_code`, `force_update`, `changelog`, `store_url`, `is_active`)
VALUES
  ('android', '1.0.0', 1, 1, 0, 'Initial release.', 'https://play.google.com/store/apps/details?id=com.cashnest.app', 1),
  ('ios',     '1.0.0', 1, 1, 0, 'Initial release.', 'https://apps.apple.com/app/cashnest/id000000000',                1);

-- ---- Maintenance window (disabled by default) --------------------------------
INSERT INTO `maintenance_windows` (`is_enabled`, `title`, `message`, `allow_admin_bypass`) VALUES
  (0, 'Scheduled Maintenance', 'CashNest is temporarily unavailable. Please try again shortly.', 1);

-- ---- Default theme (active; CashNest brand green) ----------------------------
INSERT INTO `themes`
  (`name`, `primary_color`, `secondary_color`, `accent_color`, `background_color`, `font_family`, `default_mode`, `is_active`)
VALUES
  ('CashNest Default', '#0B6E4F', '#12A366', '#F5A623', '#0B1B14', 'Inter', 'system', 1);

-- ---- Ad networks (registered, disabled until keys configured) ----------------
INSERT INTO `ad_networks` (`network`, `display_name`, `is_enabled`, `priority`) VALUES
  ('admob',        'Google AdMob',  0, 10),
  ('applovin_max', 'AppLovin MAX',  0, 20),
  ('unity_ads',    'Unity Ads',     0, 30);

-- ---- Ad placements -----------------------------------------------------------
INSERT INTO `ads_placements` (`name`, `code`, `ad_format`, `reward_coins`, `daily_cap_per_user`, `is_active`) VALUES
  ('Home Banner',        'home_banner',      'banner',       0,  NULL, 1),
  ('Interstitial',       'app_interstitial', 'interstitial', 0,  NULL, 1),
  ('Spin Rewarded',      'spin_rewarded',    'rewarded',     25, 5,    1),
  ('Scratch Rewarded',   'scratch_rewarded', 'rewarded',     25, 5,    1);

-- ---- FAQ categories & starter FAQs -------------------------------------------
INSERT INTO `faq_categories` (`name`, `slug`, `sort_order`, `is_active`) VALUES
  ('Getting Started', 'getting-started', 10, 1),
  ('Earning Coins',   'earning',         20, 1),
  ('Withdrawals',     'withdrawals',     30, 1),
  ('Account',         'account',         40, 1);

INSERT INTO `faqs` (`category_id`, `question`, `answer`, `locale`, `sort_order`, `is_published`) VALUES
  ((SELECT id FROM faq_categories WHERE slug='getting-started'),
   'What is CashNest?',
   'CashNest is a rewards app where you earn coins by completing offers, tasks, daily check-ins, and more, then redeem them for real rewards.',
   'en', 10, 1),
  ((SELECT id FROM faq_categories WHERE slug='earning'),
   'How do I earn coins?',
   'Earn coins through daily check-ins, the spin wheel, scratch cards, offerwall offers, tasks, and referrals.',
   'en', 10, 1),
  ((SELECT id FROM faq_categories WHERE slug='withdrawals'),
   'What is the minimum withdrawal?',
   'You can withdraw once you reach the minimum coin balance shown on the Withdraw screen. Amounts and methods are configured by the CashNest team.',
   'en', 10, 1),
  ((SELECT id FROM faq_categories WHERE slug='account'),
   'How do referrals work?',
   'Share your referral code. When a friend signs up and qualifies, you both receive a bonus, and you may earn a commission on their earnings.',
   'en', 10, 1);

-- ---- CMS pages (legal) -------------------------------------------------------
INSERT INTO `cms_pages` (`slug`, `title`, `body`, `locale`, `version`, `is_published`, `effective_at`) VALUES
  ('privacy-policy', 'Privacy Policy', '<h1>Privacy Policy</h1><p>Replace this placeholder with your production privacy policy.</p>', 'en', 1, 1, CURRENT_TIMESTAMP),
  ('terms',          'Terms of Service', '<h1>Terms of Service</h1><p>Replace this placeholder with your production terms of service.</p>', 'en', 1, 1, CURRENT_TIMESTAMP),
  ('about',          'About CashNest', '<h1>About CashNest</h1><p>CashNest — earn rewards for everyday actions.</p>', 'en', 1, 1, CURRENT_TIMESTAMP);

-- ---- Home layout sections ----------------------------------------------------
INSERT INTO `home_sections` (`section_type`, `title`, `config`, `sort_order`, `is_active`) VALUES
  ('banner_carousel', NULL,            JSON_OBJECT('placement','home_top','autoplay',true), 10, 1),
  ('quick_actions',   'Quick Actions', JSON_OBJECT('actions', JSON_ARRAY('checkin','spin','scratch','offers')), 20, 1),
  ('offers',          'Top Offers',    JSON_OBJECT('limit',10), 30, 1),
  ('tasks',           'Tasks',         JSON_OBJECT('limit',10), 40, 1),
  ('leaderboard',     'Leaderboard',   JSON_OBJECT('period','weekly','limit',10), 50, 1);

-- ---- Remote config (typed feature flags) -------------------------------------
INSERT INTO `remote_configs` (`config_key`, `value_type`, `value`, `environment`, `description`, `is_active`) VALUES
  ('daily_spin_limit',        'int',    '3',     'all', 'Free spins allowed per user per day.', 1),
  ('scratch_daily_limit',     'int',    '3',     'all', 'Free scratch cards per user per day.', 1),
  ('offerwall_enabled',       'bool',   'true',  'all', 'Master switch for the offerwall.', 1),
  ('rewarded_ads_enabled',    'bool',   'true',  'all', 'Master switch for rewarded ads.', 1),
  ('min_app_version_android', 'int',    '1',     'all', 'Minimum supported Android build code.', 1);

-- ---- Leaderboard: an all-time period so entries can accumulate immediately ----
INSERT INTO `leaderboard_periods` (`period_type`, `period_key`, `starts_at`, `ends_at`, `status`) VALUES
  ('all_time', 'all-time', CURRENT_TIMESTAMP, NULL, 'active');

-- =============================================================================
-- Restore session settings
-- =============================================================================
SET SQL_MODE = @OLD_SQL_MODE;
SET TIME_ZONE = @OLD_TIME_ZONE;

-- End of cashnest.sql
