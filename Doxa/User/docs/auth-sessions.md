# Auth sessions (`user_auth_sessions`)

Отдельная таблица от `user_logins` (журнал IP/UA через `UserGeo`).

## SQL

Применить `docs/sql/user_auth_sessions.sql` на БД хоста.

## Запись после login

`Registration::recordLoginArtifacts()`:
1. `UserGeo::record` → `user_logins`
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
