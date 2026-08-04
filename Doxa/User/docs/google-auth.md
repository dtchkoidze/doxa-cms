# Авторизация через Google

Описание реализации OAuth-входа через Google в пакете `Doxa/User`.

## Общая идея

Google auth реализован **отдельным потоком**, параллельно обычной регистрации (`email → код → пароль`).

- Обычная регистрация идёт через `Registration` + middleware `authorization`.
- Google OAuth идёт через `SocialAuthService` + `GoogleController`.
- Google-маршруты **не** проходят через middleware `authorization`, чтобы не сбрасывать cookies и не ломать pending-состояние обычной регистрации.

После успешного входа используется стандартная Laravel session (`Auth::login`) и общий редирект `Registration::getSuccessAuthUrl()`.

## Зависимости

- **Laravel Socialite** — в host-приложении (`composer require laravel/socialite`)
- **Laravel session auth** — тот же guard `web`, что и у обычного login
- **Google OAuth 2.0** — Client ID / Secret в Google Cloud Console

## База данных

Соцвход **не требует email**. Обязателен только `google_id` (для Facebook позже — `facebook_id`).

```sql
ALTER TABLE `users`
  MODIFY COLUMN `email` VARCHAR(255) NULL;

ALTER TABLE `users`
  ADD COLUMN `google_id` VARCHAR(64) NULL AFTER `email`,
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`);
```

Готовый SQL: `Doxa/User/docs/update.sql`

В модели `App\Models\User` на хосте желательно добавить `google_id` в `$fillable`.  
В коде `google_id` также сохраняется через `forceFill()`, если поле не в `$fillable`.

## Настройка на хосте

### `.env`

```env
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

Важно: без `GOOGLE_AUTH_ENABLED=true` Google auth **выключен** (роуты не регистрируются, кнопок нет).

Важно: имя переменной redirect — **`GOOGLE_REDIRECT_URI`**, не `GOOGLE_CALLBACK_URL`.

### `config/services.php`

```php
'google' => [
    'auth_enabled' => filter_var(env('GOOGLE_AUTH_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

Пример: `Doxa/User/config/services_google.php`

### Google Cloud Console

В OAuth 2.0 Client (Web application):

- **Authorized redirect URI** должен совпадать с `GOOGLE_REDIRECT_URI` символ в символ  
  Пример prod: `https://fanbrio.com/auth/google/callback`  
  Пример local: `http://localhost/auth/google/callback`

Для локальной разработки `.loc`-домены Google часто не принимает — используйте `localhost`.

После смены `.env`:

```bash
php artisan config:clear
```

## Маршруты

Префикс: `config('app.auth_prefix')` (обычно `/auth`).

| Метод | URL | Имя | Назначение |
|-------|-----|-----|------------|
| GET | `/google/redirect` | `auth.google.redirect` | Редирект на Google |
| GET | `/google/callback` | `auth.google.callback` | Callback от Google |
| GET | `/google/link` | `auth.google.link` | Страница привязки Google к существующему email |
| POST | `/google/link/password` | `auth.google.link.password` | Привязка по паролю |
| POST | `/google/link/magic` | `auth.google.link.send_magic` | Отправка magic link на email |
| GET | `/google/link/magic/{token}` | `auth.google.link.magic` | Подтверждение по ссылке из письма |
| GET | `/google/link/cancel` | `auth.google.link.cancel` | Отмена привязки |

Файл маршрутов: `Doxa/User/src/Routes/user.php`

## Ключевые файлы

| Файл | Роль |
|------|------|
| `Doxa/User/src/Libraries/SocialAuthService.php` | Бизнес-логика: find/create/link/login |
| `Doxa/User/src/Http/Controllers/SocialAuth/GoogleController.php` | HTTP: redirect, callback, link page, API |
| `Doxa/User/src/Resources/views/auth/google-link.blade.php` | Blade-обёртка страницы привязки |
| `Doxa/User/src/Resources/assets/js/apps/GoogleLink.vue` | UI привязки (пароль / magic link) |
| `Doxa/User/src/Mail/GoogleLinkEmail.php` | Письмо с magic link |
| `Doxa/User/src/Resources/views/emails/google-link-email.blade.php` | Шаблон письма |
| `Doxa/User/src/Resources/assets/js/apps/Login.vue` | Кнопка «Continue with Google» |
| `Doxa/User/src/Resources/assets/js/apps/Register.vue` | Кнопка «Continue with Google» |

## Сценарии

Ключ идентификации — **`google_id`**. Email опционален.

### 1. Уже привязан (`users.google_id` совпадает)

1. Callback → поиск по `google_id`.
2. Сразу `Auth::login()` → редирект.

### 2. Новый пользователь (нет `google_id`)

1. Пользователь нажимает «Continue with Google».
2. Callback → `SocialAuthService::handleGoogleUser()`.
3. Создаётся user:
   - `google_id` из Google (**обязательно**)
   - `email` из Google, если есть; иначе `NULL`
   - `name` из Google или fallback
   - `active = 1`, `status = READY`, `admin = 0`
   - `password` = `NULL` (вход через соцсеть или recovery)
   - `user_profile` через `mr('user_profile')->create($user, 0)`
4. `Auth::login()` → редирект.

### 3. Email есть и уже зарегистрирован, Google ещё не привязан

Только если Google вернул email (и он verified):

1. User найден по `email`, `google_id` пустой.
2. Pending в session → страница `/auth/google/link`.
3. Подтверждение: пароль или magic link.
4. Запись `google_id` → login.

Если email нет или не verified — пункт 3 не срабатывает, создаётся отдельный social-аккаунт (`email = NULL` или с email, если свободен).

### 4. Ошибки и блокировки

- Email привязан к другому `google_id` → ошибка.
- User suspended → редирект на `auth.suspended`.
- User не active → ошибка.
- Pending session истёк → «Session expired».
- Отсутствие email — **не ошибка**.

## Состояние между шагами

| Механизм | Ключ | TTL |
|----------|------|-----|
| Laravel session | `google_auth_pending` | до logout / cancel / успешного link |
| Cache | `google_link:{token}` | 15 минут (magic link) |

Magic link одноразовый: после перехода токен удаляется из cache (`Cache::pull`).

## UI

### Кнопки на login/register

Ссылка на `/auth/google/redirect` в:

- `Doxa/User/src/Resources/assets/js/apps/Login.vue`
- `Doxa/User/src/Resources/assets/js/apps/Register.vue`

### Страница привязки

- View: `user::auth.google-link`
- Компонент: `GoogleLink.vue`
- Wrapper: `REG::authWrapper()` — **тот же**, что у login/register (важно для кастомных wrapper'ов проекта)

После изменений Vue нужен build:

```bash
cd Doxa/User
npm install
npm run build
```

## Отличия от обычной регистрации

| | Обычная регистрация | Google |
|--|---------------------|--------|
| Шаги | email → код → пароль | OAuth → (опц.) link |
| Email | обязателен | опционален (`NULL` допустим) |
| Middleware | `authorization` | только `web` |
| Статусы | 2 → 3 → 1 | сразу READY + active |
| Профиль | `createUserProfile()` | `mr('user_profile')->create()` |
| Admin | не создаётся через публичный register | `admin = 0` всегда |

## Деплой

1. Обновить пакет `doxa/doxa-cms` (у вас обычно `dev-main` + `composer update`).
2. Выполнить SQL из `update.sql` (если ещё не делали).
3. Настроить `.env` и `config/services.php`.
4. Убедиться, что на проде задеплоен **собранный** `dist` (после `npm run build` в `Doxa/User`).
5. При необходимости: `php artisan vendor:publish --tag=laravel-assets` (если assets публикуются в `public/doxa/user`).

## Локальное тестирование

1. OAuth client в Google с redirect `http://localhost/auth/google/callback` (или ваш локальный URL).
2. `APP_URL` и `GOOGLE_REDIRECT_URI` должны совпадать с тем, как открываете сайт в браузере.
3. Для host с `eventer.loc` Google может не принять origin — используйте `localhost` или ngrok.

## Логи

Канал логов: `auth_google`.

Пишутся события: callback errors, создание user, link, отправка magic link.
