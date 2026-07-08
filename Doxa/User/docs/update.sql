ALTER TABLE `users`
  ADD COLUMN `google_id` VARCHAR(64) NULL AFTER `email`,
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`);