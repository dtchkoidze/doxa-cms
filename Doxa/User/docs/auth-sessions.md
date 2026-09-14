# Auth sessions (`user_auth_sessions`)

Отдельная таблица от `user_logins` (журнал IP/UA/страны через `UserGeo`). Страна входа — в `user_logins.country` (`CF-IPCountry`), не в `user_auth_sessions`.

## SQL

Применить на БД хоста (пакет migrate не накатывает):

- `Doxa/Patches/2026-09-14-user-sessions.patch.sql` — проверка `user_auth_sessions` и `user_logins`, недостающее добавляет
- `docs/sql/user_auth_sessions.sql` — CREATE, если таблицы ещё нет
- `docs/sql/user_logins_country.sql` — `user_logins.country` + `user_logins.device_id`
- `docs/sql/user_auth_sessions_drop_country.sql` — снять `country` с `user_auth_sessions`, если колонку уже добавляли

## Запись после login

`Registration::recordLoginArtifacts()`:
1. `UserGeo::record` → `user_logins` (ip, ua, country, device_id)
2. `AuthSessionService::recordWebSessionAfterLogin($userId, $this->authSessionContext())`

Хост переопределяет `authSessionContext(): ?string`. `null` / `''` — web_session не пишется.

## Клиентский driver (`config/user.auth_sessions`)

| Ключ | Значение |
|------|----------|
| `auth_driver` | `session` (default) или `mobile_token` |
| `token_storage_key` | localStorage key (default `mobile_api_token`) |
| `auth_success_url` | redirect после mobile login |

При `mobile_token`: `Login.vue` → `POST` mobile login → localStorage → redirect. Хост выставляет driver через `config([...])` (например sim-host).

## API

Пути в `config/auth_sessions.php`. Middleware alias `resolve_auth_session`.
