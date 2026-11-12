-- MCP / OAuth: клиенты (ChatGPT регистрирует себя сам), коды авторизации, токены, журнал вызовов /mcp.
-- Применить вручную на БД хоста. Пакет migrate не накатывает.

CREATE TABLE IF NOT EXISTS `mcp_oauth_clients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` CHAR(40) NOT NULL,
  `client_name` VARCHAR(255) NULL DEFAULT NULL,
  `redirect_uris` TEXT NOT NULL COMMENT 'JSON-список разрешённых redirect_uri',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_oauth_clients_client_id_unique` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mcp_oauth_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code_hash` CHAR(64) NOT NULL COMMENT 'sha256 hex кода',
  `client_id` CHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(64) NOT NULL COMMENT 'Продукт хоста, где выдан код',
  `redirect_uri` VARCHAR(2048) NOT NULL,
  `code_challenge` VARCHAR(128) NOT NULL COMMENT 'PKCE S256',
  `scope` VARCHAR(255) NOT NULL,
  `resource` VARCHAR(2048) NULL DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_oauth_codes_code_hash_unique` (`code_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mcp_oauth_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` CHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(64) NOT NULL COMMENT 'Токен действует только на хосте этого продукта',
  `scope` VARCHAR(255) NOT NULL,
  `access_token_hash` CHAR(64) NOT NULL COMMENT 'sha256 hex',
  `access_expires_at` DATETIME NOT NULL,
  `refresh_token_hash` CHAR(64) NOT NULL COMMENT 'sha256 hex, меняется при каждом продлении',
  `refresh_expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME NULL DEFAULT NULL,
  `revoked_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_oauth_tokens_access_unique` (`access_token_hash`),
  UNIQUE KEY `mcp_oauth_tokens_refresh_unique` (`refresh_token_hash`),
  KEY `mcp_oauth_tokens_user_idx` (`user_id`, `revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mcp_call_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(64) NOT NULL,
  `rpc_method` VARCHAR(64) NOT NULL COMMENT 'JSON-RPC method: initialize, tools/list, tools/call…',
  `tool` VARCHAR(128) NULL DEFAULT NULL,
  `arguments` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'JSON аргументов tools/call',
  `http_status` SMALLINT UNSIGNED NOT NULL,
  `is_error` TINYINT(1) NOT NULL DEFAULT 0,
  `duration_ms` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mcp_call_log_user_idx` (`user_id`, `created_at`),
  KEY `mcp_call_log_token_idx` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
