-- Social auth: email and password optional for social-only users
ALTER TABLE `users`
  MODIFY COLUMN `email` VARCHAR(255) NULL;

ALTER TABLE `users`
  MODIFY COLUMN `password` VARCHAR(255) NULL;

-- If google_id already exists, skip this block
ALTER TABLE `users`
  ADD COLUMN `google_id` VARCHAR(64) NULL AFTER `email`,
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`);

-- If facebook_id already exists, skip this block
ALTER TABLE `users`
  ADD COLUMN `facebook_id` VARCHAR(64) NULL AFTER `google_id`,
  ADD UNIQUE KEY `users_facebook_id_unique` (`facebook_id`);
