-- Способы 2FA: email и totp у одного пользователя — две строки.
-- Код письма при входе/подтверждении — users.secret, не эта таблица.
-- secret здесь только у totp (шифровать в приложении).
-- is_default: один на пользователя; при установке снять с остальных его строк.
-- confirmed_at NULL у totp — QR показан, на вход не действует.
-- Применить вручную. Пакет migrate не накатывает.

CREATE TABLE IF NOT EXISTS `user_two_factor_methods` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `method` ENUM('email', 'totp') NOT NULL,
  `secret` VARCHAR(512) NULL DEFAULT NULL COMMENT 'Только totp, ciphertext; для email всегда NULL',
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'NULL — способ не действует на вход',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Первый способ на экране входа; максимум один на user_id',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_two_factor_methods_user_method_uq` (`user_id`, `method`),
  KEY `user_two_factor_methods_user_confirmed_idx` (`user_id`, `confirmed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Резервная почта. NULL у многих строк ок. Не совпадает с users.email этого и чужих.
ALTER TABLE `users`
  ADD COLUMN `backup_email` VARCHAR(191) NULL DEFAULT NULL AFTER `email`,
  ADD UNIQUE KEY `users_backup_email_unique` (`backup_email`);
