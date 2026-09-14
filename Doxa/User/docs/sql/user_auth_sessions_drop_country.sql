-- Страна не в сессиях. Применить вручную, если колонку уже добавляли.
-- Пакет migrate не накатывает.

ALTER TABLE `user_auth_sessions`
  DROP COLUMN `country`;
