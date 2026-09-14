-- Auth sessions (web_session + mobile_token). Не путать с user_logins (UserGeo: ip/ua/country journal).
-- Применить вручную на БД хоста. Пакет migrate не накатывает.

CREATE TABLE IF NOT EXISTS `user_auth_sessions` (
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
