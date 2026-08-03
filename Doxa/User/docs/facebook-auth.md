# Авторизация через Facebook

OAuth-вход через Facebook в `Doxa/User`. Общая логика с Google — см. [google-auth.md](google-auth.md).

## Идея

- Отдельный поток вне middleware `authorization`
- Идентификатор — **`facebook_id`**
- **Email не обязателен** (`NULL` допустим)
- Если email есть и уже занят — страница привязки (пароль / magic link)

## Настройка хоста

### `.env`

```env
FACEBOOK_CLIENT_ID=...
FACEBOOK_CLIENT_SECRET=...
FACEBOOK_REDIRECT_URI=${APP_URL}/auth/facebook/callback
```

Для локалки:

```env
APP_URL=http://localhost
FACEBOOK_REDIRECT_URI=http://localhost/auth/facebook/callback
```

### `config/services.php`

```php
'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
],
```

Пример: `Doxa/User/config/services_facebook.php`

### SQL

```sql
ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(255) NULL;

ALTER TABLE `users`
  ADD COLUMN `facebook_id` VARCHAR(64) NULL AFTER `google_id`,
  ADD UNIQUE KEY `users_facebook_id_unique` (`facebook_id`);
```

Полный скрипт: `Doxa/User/docs/update.sql`

## Meta for Developers — локальное тестирование

1. Приложение в **Development** (Unpublished) — ок.
2. **App settings → Basic** — скопировать App ID / App Secret в `.env`.
3. **App roles → Roles** — добавить себя как Admin/Developer/Tester.
4. **Facebook Login → Settings**:
   - Client OAuth login: Yes
   - Web OAuth login: Yes
   - JS SDK: No
   - `http://localhost` в Valid OAuth Redirect URIs **не добавлять** — в Development он разрешён автоматически.
5. Сайт открывать как `http://localhost/...` (не `.loc`).
6. `php artisan config:clear`
7. На login нажать Continue with Facebook.

Quickstart с Facebook JS SDK **не нужен** — у нас Socialite (серверный OAuth).

## Маршруты

| Метод | URL | Имя |
|-------|-----|-----|
| GET | `/facebook/redirect` | `auth.facebook.redirect` |
| GET | `/facebook/callback` | `auth.facebook.callback` |
| GET | `/facebook/link` | `auth.facebook.link` |
| POST | `/facebook/link/password` | `auth.facebook.link.password` |
| POST | `/facebook/link/magic` | `auth.facebook.link.send_magic` |
| GET | `/facebook/link/magic/{token}` | `auth.facebook.link.magic` |
| GET | `/facebook/link/cancel` | `auth.facebook.link.cancel` |

## Сценарии

1. Есть `facebook_id` → login  
2. Нет `facebook_id`, email пустой → создать user (`email = NULL`), login  
3. Нет `facebook_id`, email свободен → создать с email, login  
4. Нет `facebook_id`, email занят → страница link  

## Файлы

| Файл | Роль |
|------|------|
| `SocialAuthService.php` | Google + Facebook |
| `FacebookController.php` | HTTP |
| `FacebookAuthButton.vue` | Кнопка на login/register |
| `FacebookLink.vue` | UI привязки |
| `FacebookLinkEmail.php` | Magic link письмо |

## Логи

Канал: `auth_facebook`
