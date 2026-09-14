-- Патч схемы auth-сессий и журнала входов.
-- Проверяет user_auth_sessions и user_logins; если таблицы/колонки/индексы нет — добавляет.
-- Страну на user_auth_sessions не добавляет (она в user_logins.country).
-- Применить вручную. Пакет migrate не накатывает.
-- phpMyAdmin: Delimiter = //

DELIMITER //

DROP PROCEDURE IF EXISTS `_patch_user_sessions`//

CREATE PROCEDURE `_patch_user_sessions`()
BEGIN
  -- user_auth_sessions: таблица
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_auth_sessions'
  ) THEN
    CREATE TABLE `user_auth_sessions` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `product` VARCHAR(64) NOT NULL COMMENT 'Opaque context с хоста (Registration::authSessionContext)',
      `type` ENUM('web_session', 'mobile_token') NOT NULL,
      `session_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Laravel session id для web_session',
      `token_hash` CHAR(64) NULL DEFAULT NULL COMMENT 'sha256 hex plain mobile token',
      `device_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Cookie auth_device, не auth-сессия',
      `label` VARCHAR(512) NULL DEFAULT NULL COMMENT 'Устройство / ОС / браузер',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_used_at` TIMESTAMP NULL DEFAULT NULL,
      `revoked_at` TIMESTAMP NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `user_auth_sessions_user_active_idx` (`user_id`, `revoked_at`),
      KEY `user_auth_sessions_session_id_idx` (`session_id`),
      KEY `user_auth_sessions_token_hash_idx` (`token_hash`),
      KEY `user_auth_sessions_device_idx` (`user_id`, `type`, `device_id`, `revoked_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  -- user_auth_sessions: колонки
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'product'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `product` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'Opaque context с хоста (Registration::authSessionContext)' AFTER `user_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'type'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `type` ENUM('web_session', 'mobile_token') NOT NULL DEFAULT 'web_session' AFTER `product`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'session_id'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `session_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Laravel session id для web_session' AFTER `type`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'token_hash'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `token_hash` CHAR(64) NULL DEFAULT NULL COMMENT 'sha256 hex plain mobile token' AFTER `session_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'device_id'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `device_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Cookie auth_device, не auth-сессия' AFTER `token_hash`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'label'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `label` VARCHAR(512) NULL DEFAULT NULL COMMENT 'Устройство / ОС / браузер' AFTER `device_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'created_at'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `label`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'last_used_at'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `last_used_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND COLUMN_NAME = 'revoked_at'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD COLUMN `revoked_at` TIMESTAMP NULL DEFAULT NULL AFTER `last_used_at`;
  END IF;

  -- user_auth_sessions: индексы
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND INDEX_NAME = 'user_auth_sessions_user_active_idx'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD KEY `user_auth_sessions_user_active_idx` (`user_id`, `revoked_at`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND INDEX_NAME = 'user_auth_sessions_session_id_idx'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD KEY `user_auth_sessions_session_id_idx` (`session_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND INDEX_NAME = 'user_auth_sessions_token_hash_idx'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD KEY `user_auth_sessions_token_hash_idx` (`token_hash`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_auth_sessions' AND INDEX_NAME = 'user_auth_sessions_device_idx'
  ) THEN
    ALTER TABLE `user_auth_sessions`
      ADD KEY `user_auth_sessions_device_idx` (`user_id`, `type`, `device_id`, `revoked_at`);
  END IF;

  -- user_logins: таблица
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_logins'
  ) THEN
    CREATE TABLE `user_logins` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `ip` VARCHAR(45) NULL DEFAULT NULL,
      `user_agent` VARCHAR(512) NULL DEFAULT NULL,
      `country` CHAR(2) NULL DEFAULT NULL COMMENT 'ISO2 из CF-IPCountry, если был',
      `device_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Cookie auth_device, не auth-сессия',
      `logged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_logins_user_logged_idx` (`user_id`, `logged_at`),
      KEY `user_logins_user_device_idx` (`user_id`, `device_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  -- user_logins: колонки
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'user_id'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `user_id` BIGINT UNSIGNED NOT NULL AFTER `id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'ip'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `ip` VARCHAR(45) NULL DEFAULT NULL AFTER `user_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'user_agent'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `user_agent` VARCHAR(512) NULL DEFAULT NULL AFTER `ip`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'country'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `country` CHAR(2) NULL DEFAULT NULL COMMENT 'ISO2 из CF-IPCountry, если был' AFTER `user_agent`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'device_id'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `device_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Cookie auth_device, не auth-сессия' AFTER `country`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND COLUMN_NAME = 'logged_at'
  ) THEN
    ALTER TABLE `user_logins`
      ADD COLUMN `logged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `device_id`;
  END IF;

  -- user_logins: индексы
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND INDEX_NAME = 'user_logins_user_logged_idx'
  ) THEN
    ALTER TABLE `user_logins`
      ADD KEY `user_logins_user_logged_idx` (`user_id`, `logged_at`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_logins' AND INDEX_NAME = 'user_logins_user_device_idx'
  ) THEN
    ALTER TABLE `user_logins`
      ADD KEY `user_logins_user_device_idx` (`user_id`, `device_id`);
  END IF;
END//

DELIMITER ;

CALL `_patch_user_sessions`();
DROP PROCEDURE IF EXISTS `_patch_user_sessions`;
