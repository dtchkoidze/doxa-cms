-- Host table for User package onboarding (developer applies).
-- Feature is on when config onboarding.onboarding_query_keys is non-empty.

CREATE TABLE IF NOT EXISTS `onboarding` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(64) NULL DEFAULT NULL,
    `target` VARCHAR(64) NOT NULL,
    `value` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `onboarding_user_type_target` (`user_id`, `type`, `target`),
    KEY `onboarding_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If table already exists without created_at / unique:
-- ALTER TABLE `onboarding`
--     ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `value`;
-- ALTER TABLE `onboarding`
--     ADD UNIQUE KEY `onboarding_user_type_target` (`user_id`, `type`, `target`);
