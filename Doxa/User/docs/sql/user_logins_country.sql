-- Страна входа (CF-IPCountry) и устройство для связи со списком сессий.
-- Применить вручную. Пакет migrate не накатывает.

ALTER TABLE `user_logins`
  ADD COLUMN `country` CHAR(2) NULL DEFAULT NULL COMMENT 'ISO2 из CF-IPCountry, если был' AFTER `user_agent`,
  ADD COLUMN `device_id` CHAR(36) NULL DEFAULT NULL COMMENT 'Cookie auth_device, не auth-сессия' AFTER `country`;
