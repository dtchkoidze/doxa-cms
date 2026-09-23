<?php

namespace Doxa\User\Libraries;

use Doxa\User\Mail\TwoFactorCodeEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Способы 2FA, challenge входа, резервная почта и смена основной.
 * Product/домен не резолвит: issuer и письма берёт из текущего config (хост накладывает по каналу).
 */
class TwoFactorService
{
    public const TABLE = 'user_two_factor_methods';

    public const METHOD_EMAIL = 'email';

    public const METHOD_TOTP = 'totp';

    public const CHANNEL_BACKUP = 'backup';

    public const PENDING_KEY = 'two_factor_pending';

    public const BACKUP_PENDING_KEY = 'two_factor_backup_pending';

    public const BACKUP_REMOVE_KEY = 'two_factor_backup_remove';

    public const DISABLE_KEY = 'two_factor_disable';

    public const EMAIL_CHANGE_KEY = 'two_factor_email_change';

    public function __construct(
        private readonly TwoFactorTotp $totp = new TwoFactorTotp(),
    ) {
    }

    /**
     * Возвращает true, если есть хотя бы один подтверждённый способ.
     */
    public function hasConfirmedMethod(int $userId): bool
    {
        return DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNotNull('confirmed_at')
            ->exists();
    }

    /**
     * Возвращает состояние настроек без секретов (профиль / actor).
     *
     * @return array{
     *     enabled: bool,
     *     email: ?string,
     *     backup_email: ?string,
     *     methods: list<array{method: string, confirmed: bool, is_default: bool}>,
     *     can_enable_email: bool
     * }
     */
    public function profileState(int $userId): array
    {
        $user = $this->userRow($userId);
        $email = $this->normalizedEmail($user->email ?? null);
        $backup = $this->normalizedEmail($user->backup_email ?? null);
        $rows = DB::table(self::TABLE)->where('user_id', $userId)->get();
        $methods = [];
        foreach ($rows as $row) {
            $methods[] = [
                'method' => $row->method,
                'confirmed' => $row->confirmed_at !== null,
                'is_default' => (int) $row->is_default === 1,
            ];
        }

        return [
            'enabled' => $this->hasConfirmedMethod($userId),
            'email' => $email,
            'backup_email' => $backup,
            'methods' => $methods,
            'can_enable_email' => $email !== null,
        ];
    }

    /**
     * Пишет pending входа до Auth::login. Вызывающий не логинит.
     *
     * @param 'session'|'mobile_token' $source
     */
    public function storePendingLogin(int $userId, bool $remember, string $source): void
    {
        session([self::PENDING_KEY => [
            'user_id' => $userId,
            'remember' => $remember,
            'source' => $source,
            'channel' => $this->defaultChallengeChannel($userId),
        ]]);
        $this->maybeSendChallengeEmail($userId);
    }

    /**
     * Возвращает pending входа или null.
     *
     * @return array{user_id: int, remember: bool, source: string, channel: string}|null
     */
    public function pendingLogin(): ?array
    {
        $pending = session(self::PENDING_KEY);
        if (!is_array($pending) || empty($pending['user_id'])) {
            return null;
        }

        return [
            'user_id' => (int) $pending['user_id'],
            'remember' => (bool) ($pending['remember'] ?? false),
            'source' => (string) ($pending['source'] ?? 'session'),
            'channel' => (string) ($pending['channel'] ?? self::METHOD_TOTP),
        ];
    }

    public function clearPendingLogin(): void
    {
        session()->forget(self::PENDING_KEY);
    }

    /**
     * Возвращает данные экрана challenge или строку ошибки.
     *
     * @return array<string, mixed>|string
     */
    public function challengeState(): array|string
    {
        $pending = $this->pendingLogin();
        if ($pending === null) {
            return 'expired';
        }

        return $this->challengePayload($pending['user_id'], $pending['channel']);
    }

    /**
     * Переключает канал challenge. Письмо — только для email/backup.
     *
     * @return array<string, mixed>|string
     */
    public function switchChallengeChannel(string $channel): array|string
    {
        $pending = $this->pendingLogin();
        if ($pending === null) {
            return 'expired';
        }

        $allowed = $this->availableChallengeChannels($pending['user_id']);
        if (!in_array($channel, $allowed, true)) {
            return 'invalid_channel';
        }

        $pending['channel'] = $channel;
        session([self::PENDING_KEY => $pending]);
        if ($channel === self::METHOD_EMAIL || $channel === self::CHANNEL_BACKUP) {
            $send = $this->sendEmailForChannel($pending['user_id'], $channel);
            if (is_string($send)) {
                return $send;
            }
        }

        return $this->challengePayload($pending['user_id'], $channel);
    }

    /**
     * Проверяет код challenge. Успех — данные для логина у вызывающего, не Auth::login здесь.
     *
     * @return array{ok: true, user_id: int, remember: bool, source: string, via: string}|array{ok: false, error: string, retry_after?: int}
     */
    public function verifyChallenge(string $code): array
    {
        $pending = $this->pendingLogin();
        if ($pending === null) {
            return ['ok' => false, 'error' => 'expired'];
        }

        $userId = $pending['user_id'];
        $lock = $this->attemptLock($userId);
        if (RateLimiter::tooManyAttempts($lock, (int) config('user.two_factor.max_attempts', 5))) {
            return [
                'ok' => false,
                'error' => 'locked',
                'retry_after' => RateLimiter::availableIn($lock),
            ];
        }

        $channel = $pending['channel'];
        $valid = match ($channel) {
            self::METHOD_TOTP => $this->verifyTotp($userId, $code),
            self::METHOD_EMAIL => $this->verifyUserSecret($userId, $code),
            self::CHANNEL_BACKUP => $this->verifyUserSecret($userId, $code),
            default => false,
        };

        if (!$valid) {
            RateLimiter::hit($lock, (int) config('user.two_factor.decay_minutes', 15) * 60);

            return ['ok' => false, 'error' => 'invalid_code'];
        }

        RateLimiter::clear($lock);
        if ($channel === self::METHOD_EMAIL || $channel === self::METHOD_TOTP) {
            $this->markDefault($userId, $channel);
        }
        if ($channel === self::CHANNEL_BACKUP) {
            session(['two_factor_logged_via_backup' => true]);
        } else {
            session()->forget('two_factor_logged_via_backup');
        }
        $this->invalidateUserSecret($userId);
        $this->clearPendingLogin();

        return [
            'ok' => true,
            'user_id' => $userId,
            'remember' => $pending['remember'],
            'source' => $pending['source'],
            'via' => $channel,
        ];
    }

    /**
     * Начинает привязку totp. Возвращает секрет и QR; на вход ещё не действует.
     *
     * @return array{secret: string, otpauth_uri: string, qr_svg: string}|string
     */
    public function startTotp(int $userId): array|string
    {
        $user = $this->userRow($userId);
        $secret = $this->totp->generateSecret();
        $account = $this->normalizedEmail($user->email ?? null) ?? ('user-' . $userId);
        $issuer = $this->issuer();
        $uri = $this->totp->otpauthUri($secret, $issuer, $account);
        $svg = $this->qrSvg($uri);
        if ($svg === '') {
            throw new \RuntimeException('QR generator is not available');
        }

        $existing = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', self::METHOD_TOTP)
            ->first();

        $payload = [
            'secret' => Crypt::encryptString($secret),
            'confirmed_at' => null,
            'is_default' => 0,
            'updated_at' => now(),
        ];

        if ($existing) {
            if ($existing->confirmed_at !== null) {
                return 'already_enabled';
            }
            DB::table(self::TABLE)->where('id', $existing->id)->update($payload);
        } else {
            $payload['user_id'] = $userId;
            $payload['method'] = self::METHOD_TOTP;
            $payload['created_at'] = now();
            DB::table(self::TABLE)->insert($payload);
        }

        return [
            'secret' => $secret,
            'otpauth_uri' => $uri,
            'qr_svg' => $svg,
        ];
    }

    /**
     * Подтверждает totp кодом из приложения.
     *
     * @return true|string
     */
    public function confirmTotp(int $userId, string $code): bool|string
    {
        $row = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', self::METHOD_TOTP)
            ->first();
        if (!$row || $row->secret === null || $row->secret === '') {
            return 'not_started';
        }
        if ($row->confirmed_at !== null) {
            return 'already_enabled';
        }

        $plain = Crypt::decryptString($row->secret);
        if (!$this->totp->verify($plain, $code)) {
            return 'invalid_code';
        }

        $this->confirmMethodRow($userId, self::METHOD_TOTP);

        return true;
    }

    /**
     * Шлёт код на основную почту для включения email-2FA.
     *
     * @return array{timer: int, code_expire_in: int}|string
     */
    public function startEmailEnable(int $userId): array|string
    {
        $user = $this->userRow($userId);
        $email = $this->normalizedEmail($user->email ?? null);
        if ($email === null) {
            return 'no_email';
        }
        if ($this->methodConfirmed($userId, self::METHOD_EMAIL)) {
            return 'already_enabled';
        }

        return $this->issueAndMail($userId, $email);
    }

    /**
     * Подтверждает включение email-2FA.
     *
     * @return true|string
     */
    public function confirmEmailEnable(int $userId, string $code): bool|string
    {
        if ($this->methodConfirmed($userId, self::METHOD_EMAIL)) {
            return 'already_enabled';
        }
        $user = $this->userRow($userId);
        if ($this->normalizedEmail($user->email ?? null) === null) {
            return 'no_email';
        }
        if (!$this->verifyUserSecret($userId, $code)) {
            return 'invalid_code';
        }

        $this->upsertConfirmedMethod($userId, self::METHOD_EMAIL);
        $this->invalidateUserSecret($userId);

        return true;
    }

    /**
     * Возвращает канал подтверждения снятия способа.
     *
     * @return array{channel: string, timer?: int, code_expire_in?: int}|string
     */
    public function startDisable(int $userId, string $method): array|string
    {
        if ($method !== self::METHOD_EMAIL && $method !== self::METHOD_TOTP) {
            return 'invalid_method';
        }
        if (!$this->methodConfirmed($userId, $method)) {
            return 'not_enabled';
        }

        $channel = $this->disableChannel($userId, $method);
        session([self::DISABLE_KEY => [
            'user_id' => $userId,
            'method' => $method,
            'channel' => $channel,
        ]]);

        if ($channel === 'none') {
            return ['channel' => 'none'];
        }
        if ($channel === self::METHOD_TOTP) {
            return ['channel' => self::METHOD_TOTP];
        }

        $send = $this->sendEmailForChannel($userId, $channel);
        if (is_string($send)) {
            return $send;
        }

        return array_merge(['channel' => $channel], $send);
    }

    /**
     * Снимает способ после кода или страшилки (channel none).
     *
     * @return true|string
     */
    public function confirmDisable(int $userId, string $method, ?string $code, bool $acceptRisk): bool|string
    {
        $ctx = session(self::DISABLE_KEY);
        if (!is_array($ctx) || (int) $ctx['user_id'] !== $userId || ($ctx['method'] ?? '') !== $method) {
            return 'expired';
        }

        $channel = (string) $ctx['channel'];
        if ($channel === 'none') {
            if (!$acceptRisk) {
                return 'risk_not_accepted';
            }
        } else {
            $valid = match ($channel) {
                self::METHOD_TOTP => $this->verifyTotp($userId, (string) $code),
                self::METHOD_EMAIL, self::CHANNEL_BACKUP => $this->verifyUserSecret($userId, (string) $code),
                default => false,
            };
            if (!$valid) {
                return 'invalid_code';
            }
            $this->invalidateUserSecret($userId);
        }

        $wasDefault = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->where('is_default', 1)
            ->exists();

        DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->delete();

        if ($wasDefault) {
            $other = DB::table(self::TABLE)
                ->where('user_id', $userId)
                ->whereNotNull('confirmed_at')
                ->orderBy('id')
                ->first();
            if ($other) {
                $this->markDefault($userId, $other->method);
            }
        }

        session()->forget(self::DISABLE_KEY);

        return true;
    }

    /**
     * Запрашивает код на новый резервный ящик.
     *
     * @return array{timer: int, code_expire_in: int}|string
     */
    public function startBackupEmail(int $userId, string $email): array|string
    {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }
        $user = $this->userRow($userId);
        $primary = $this->normalizedEmail($user->email ?? null);
        if ($primary !== null && $primary === $email) {
            return 'same_as_primary';
        }
        if ($this->emailTaken($email, $userId)) {
            return 'email_taken';
        }

        $plain = (string) random_int(100000, 999999);
        $minutes = (int) config('user.two_factor.code_ttl_minutes', 5);
        session()->forget(self::BACKUP_REMOVE_KEY);
        session([self::BACKUP_PENDING_KEY => [
            'user_id' => $userId,
            'email' => $email,
            'secret' => Hash::make($plain),
            'expires_at' => time() + $minutes * 60,
            'sent_at' => time(),
        ]]);
        $this->mailCode($email, $plain, $minutes);

        return [
            'timer' => (int) config('user.two_factor.resend_seconds', 60),
            'code_expire_in' => $minutes,
        ];
    }

    /**
     * Подтверждает резервную почту.
     *
     * @return true|string
     */
    public function confirmBackupEmail(int $userId, string $code): bool|string
    {
        $pending = session(self::BACKUP_PENDING_KEY);
        if (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== $userId) {
            return 'expired';
        }
        if (time() > (int) $pending['expires_at']) {
            session()->forget(self::BACKUP_PENDING_KEY);

            return 'expired';
        }
        if (!Hash::check(trim($code), $pending['secret'])) {
            return 'invalid_code';
        }
        $email = strtolower((string) $pending['email']);
        if ($this->emailTaken($email, $userId)) {
            session()->forget(self::BACKUP_PENDING_KEY);

            return 'email_taken';
        }

        DB::table('users')->where('id', $userId)->update([
            'backup_email' => $email,
            'updated_at' => now(),
        ]);
        session()->forget(self::BACKUP_PENDING_KEY);

        return true;
    }

    /**
     * Шлёт код на текущий резервный ящик, чтобы его снять.
     *
     * @return array{timer: int, code_expire_in: int}|string
     */
    public function startRemoveBackupEmail(int $userId): array|string
    {
        $user = $this->userRow($userId);
        $email = $this->normalizedEmail($user->backup_email ?? null);
        if ($email === null) {
            return 'no_backup';
        }

        $plain = (string) random_int(100000, 999999);
        $minutes = (int) config('user.two_factor.code_ttl_minutes', 5);
        session()->forget(self::BACKUP_PENDING_KEY);
        session([self::BACKUP_REMOVE_KEY => [
            'user_id' => $userId,
            'email' => $email,
            'secret' => Hash::make($plain),
            'expires_at' => time() + $minutes * 60,
        ]]);
        $this->mailCode($email, $plain, $minutes);

        return [
            'timer' => (int) config('user.two_factor.resend_seconds', 60),
            'code_expire_in' => $minutes,
        ];
    }

    /**
     * Снимает резервный ящик после кода с этого адреса.
     *
     * @return true|string
     */
    public function confirmRemoveBackupEmail(int $userId, string $code): bool|string
    {
        $pending = session(self::BACKUP_REMOVE_KEY);
        if (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== $userId) {
            return 'expired';
        }
        if (time() > (int) $pending['expires_at']) {
            session()->forget(self::BACKUP_REMOVE_KEY);

            return 'expired';
        }
        if (!Hash::check(trim($code), $pending['secret'])) {
            return 'invalid_code';
        }
        $user = $this->userRow($userId);
        $backup = $this->normalizedEmail($user->backup_email ?? null);
        if ($backup === null || $backup !== strtolower((string) $pending['email'])) {
            session()->forget(self::BACKUP_REMOVE_KEY);

            return 'no_backup';
        }

        DB::table('users')->where('id', $userId)->update([
            'backup_email' => null,
            'updated_at' => now(),
        ]);
        session()->forget(self::BACKUP_REMOVE_KEY);

        return true;
    }

    /**
     * Меняет основную и резервную местами.
     *
     * @return true|string
     */
    public function swapBackupToPrimary(int $userId, bool $viaBackupLogin): bool|string
    {
        $user = $this->userRow($userId);
        $primary = $this->normalizedEmail($user->email ?? null);
        $backup = $this->normalizedEmail($user->backup_email ?? null);
        if ($primary === null || $backup === null) {
            return 'no_backup';
        }
        if (!$viaBackupLogin && !session('two_factor_logged_via_backup')) {
            return 'need_backup_login_or_2fa';
        }

        DB::table('users')->where('id', $userId)->update([
            'email' => $backup,
            'backup_email' => $primary,
            'updated_at' => now(),
        ]);
        session()->forget('two_factor_logged_via_backup');

        return true;
    }

    /**
     * Начинает смену основной: канал 2FA (без 2FA нельзя).
     *
     * @return array{channel: string, timer?: int, code_expire_in?: int}|string
     */
    public function startEmailChange(int $userId): array|string
    {
        if (!$this->hasConfirmedMethod($userId)) {
            return 'two_factor_required';
        }

        $channel = $this->emailChangeChannel($userId);
        session([self::EMAIL_CHANGE_KEY => [
            'user_id' => $userId,
            'step' => '2fa',
            'channel' => $channel,
        ]]);

        if ($channel === self::METHOD_TOTP) {
            return ['channel' => self::METHOD_TOTP];
        }

        $send = $this->sendEmailForChannel($userId, $channel);
        if (is_string($send)) {
            return $send;
        }

        return array_merge(['channel' => $channel], $send);
    }

    /**
     * Подтверждает 2FA на смене почты, затем ждёт новый ящик.
     *
     * @return true|string
     */
    public function confirmEmailChangeTwoFactor(int $userId, string $code): bool|string
    {
        $ctx = session(self::EMAIL_CHANGE_KEY);
        if (!is_array($ctx) || (int) $ctx['user_id'] !== $userId || ($ctx['step'] ?? '') !== '2fa') {
            return 'expired';
        }

        $channel = (string) $ctx['channel'];
        $valid = match ($channel) {
            self::METHOD_TOTP => $this->verifyTotp($userId, $code),
            self::METHOD_EMAIL, self::CHANNEL_BACKUP => $this->verifyUserSecret($userId, $code),
            default => false,
        };
        if (!$valid) {
            return 'invalid_code';
        }

        $this->invalidateUserSecret($userId);
        $ctx['step'] = 'new_email';
        session([self::EMAIL_CHANGE_KEY => $ctx]);

        return true;
    }

    /**
     * Шлёт код на новый основной ящик (после 2FA).
     *
     * @return array{timer: int, code_expire_in: int}|string
     */
    public function requestEmailChangeNew(int $userId, string $email): array|string
    {
        $ctx = session(self::EMAIL_CHANGE_KEY);
        if (!is_array($ctx) || (int) $ctx['user_id'] !== $userId || ($ctx['step'] ?? '') !== 'new_email') {
            return 'expired';
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email';
        }
        $user = $this->userRow($userId);
        $primary = $this->normalizedEmail($user->email ?? null);
        if ($primary === $email) {
            return 'same_as_primary';
        }
        $backup = $this->normalizedEmail($user->backup_email ?? null);
        if ($backup === $email) {
            $ctx['new_email'] = $email;
            $ctx['swap'] = true;
            session([self::EMAIL_CHANGE_KEY => $ctx]);

            return ['timer' => 0, 'code_expire_in' => 0, 'swap' => true];
        }
        if ($this->emailTaken($email, $userId)) {
            return 'email_taken';
        }

        $plain = (string) random_int(100000, 999999);
        $minutes = (int) config('user.two_factor.code_ttl_minutes', 5);
        $ctx['new_email'] = $email;
        $ctx['new_secret'] = Hash::make($plain);
        $ctx['new_expires_at'] = time() + $minutes * 60;
        $ctx['swap'] = false;
        session([self::EMAIL_CHANGE_KEY => $ctx]);
        $this->mailCode($email, $plain, $minutes);

        return [
            'timer' => (int) config('user.two_factor.resend_seconds', 60),
            'code_expire_in' => $minutes,
        ];
    }

    /**
     * Подтверждает новый основной ящик.
     *
     * @return true|string
     */
    public function confirmEmailChangeNew(int $userId, string $code): bool|string
    {
        $ctx = session(self::EMAIL_CHANGE_KEY);
        if (!is_array($ctx) || (int) $ctx['user_id'] !== $userId || ($ctx['step'] ?? '') !== 'new_email') {
            return 'expired';
        }
        $newEmail = strtolower((string) ($ctx['new_email'] ?? ''));
        if ($newEmail === '') {
            return 'expired';
        }

        if (!empty($ctx['swap'])) {
            $result = $this->swapBackupToPrimary($userId, true);
            session()->forget(self::EMAIL_CHANGE_KEY);

            return $result;
        }

        if (time() > (int) ($ctx['new_expires_at'] ?? 0)) {
            session()->forget(self::EMAIL_CHANGE_KEY);

            return 'expired';
        }
        if (!Hash::check(trim($code), $ctx['new_secret'] ?? '')) {
            return 'invalid_code';
        }
        if ($this->emailTaken($newEmail, $userId)) {
            session()->forget(self::EMAIL_CHANGE_KEY);

            return 'email_taken';
        }

        DB::table('users')->where('id', $userId)->update([
            'email' => $newEmail,
            'updated_at' => now(),
        ]);
        session()->forget(self::EMAIL_CHANGE_KEY);

        return true;
    }

    /**
     * Меняет пароль. Возвращает true или строку ошибки.
     *
     * @return true|string
     */
    public function changePassword(int $userId, string $password): bool|string
    {
        $user = $this->userRow($userId);
        $current = $user->password ?? null;
        if ($current !== null && $current !== '' && password_verify($password, $current)) {
            return vocab('password_is_the_same');
        }

        $hashed = Hash::make($password);
        DB::table('users')->where('id', $userId)->update([
            'password' => $hashed,
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * @return list<string>
     */
    private function availableChallengeChannels(int $userId): array
    {
        $channels = [];
        if ($this->methodConfirmed($userId, self::METHOD_TOTP)) {
            $channels[] = self::METHOD_TOTP;
        }
        if ($this->methodConfirmed($userId, self::METHOD_EMAIL)) {
            $channels[] = self::METHOD_EMAIL;
        }
        $user = $this->userRow($userId);
        if ($this->normalizedEmail($user->backup_email ?? null) !== null) {
            $channels[] = self::CHANNEL_BACKUP;
        }

        return $channels;
    }

    private function defaultChallengeChannel(int $userId): string
    {
        $default = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('is_default', 1)
            ->whereNotNull('confirmed_at')
            ->value('method');
        if (is_string($default) && $default !== '') {
            return $default;
        }
        if ($this->methodConfirmed($userId, self::METHOD_TOTP)) {
            return self::METHOD_TOTP;
        }

        return self::METHOD_EMAIL;
    }

    /**
     * @return array<string, mixed>
     */
    private function challengePayload(int $userId, string $channel): array
    {
        $user = $this->userRow($userId);

        return [
            'channel' => $channel,
            'channels' => $this->availableChallengeChannels($userId),
            'masked_email' => $this->maskEmail($this->normalizedEmail($user->email ?? null)),
            'masked_backup' => $this->maskEmail($this->normalizedEmail($user->backup_email ?? null)),
            'code_expire_in' => (int) config('user.two_factor.code_ttl_minutes', 5),
        ];
    }

    private function maybeSendChallengeEmail(int $userId): void
    {
        $pending = $this->pendingLogin();
        if ($pending === null) {
            return;
        }
        if ($pending['channel'] !== self::METHOD_EMAIL) {
            return;
        }
        $this->sendEmailForChannel($userId, self::METHOD_EMAIL);
    }

    /**
     * @return array{timer: int, code_expire_in: int}|string
     */
    private function sendEmailForChannel(int $userId, string $channel): array|string
    {
        $user = $this->userRow($userId);
        $email = match ($channel) {
            self::METHOD_EMAIL => $this->normalizedEmail($user->email ?? null),
            self::CHANNEL_BACKUP => $this->normalizedEmail($user->backup_email ?? null),
            default => null,
        };
        if ($email === null) {
            return 'no_email';
        }

        return $this->issueAndMail($userId, $email);
    }

    /**
     * @return array{timer: int, code_expire_in: int}|string
     */
    private function issueAndMail(int $userId, string $email): array|string
    {
        $wait = (int) config('user.two_factor.resend_seconds', 60);
        $user = $this->userRow($userId);
        if (!empty($user->code_sent_at)) {
            $elapsed = time() - strtotime((string) $user->code_sent_at);
            if ($elapsed >= 0 && $elapsed < $wait) {
                return [
                    'timer' => $wait - $elapsed,
                    'code_expire_in' => (int) config('user.two_factor.code_ttl_minutes', 5),
                ];
            }
        }

        $plain = (string) random_int(100000, 999999);
        $minutes = (int) config('user.two_factor.code_ttl_minutes', 5);
        DB::table('users')->where('id', $userId)->update([
            'secret' => Hash::make($plain),
            'code_sent_at' => now(),
            'updated_at' => now(),
        ]);
        $this->mailCode($email, $plain, $minutes);

        return [
            'timer' => $wait,
            'code_expire_in' => $minutes,
        ];
    }

    private function mailCode(string $email, string $plain, int $minutes): void
    {
        Mail::to($email)->send(new TwoFactorCodeEmail([
            'code' => $plain,
            'code_expire_in' => $minutes,
            'email' => $email,
        ]));
    }

    private function verifyTotp(int $userId, string $code): bool
    {
        $row = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', self::METHOD_TOTP)
            ->whereNotNull('confirmed_at')
            ->first();
        if (!$row || $row->secret === null || $row->secret === '') {
            return false;
        }

        return $this->totp->verify(Crypt::decryptString($row->secret), $code);
    }

    private function verifyUserSecret(int $userId, string $code): bool
    {
        $user = $this->userRow($userId);
        $code = trim($code);
        if ($code === '' || empty($user->secret)) {
            return false;
        }

        return Hash::check($code, $user->secret);
    }

    private function invalidateUserSecret(int $userId): void
    {
        DB::table('users')->where('id', $userId)->update([
            'secret' => Hash::make(Str::random(40)),
            'updated_at' => now(),
        ]);
    }

    private function methodConfirmed(int $userId, string $method): bool
    {
        return DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->whereNotNull('confirmed_at')
            ->exists();
    }

    private function confirmMethodRow(int $userId, string $method): void
    {
        $hasDefault = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNotNull('confirmed_at')
            ->where('is_default', 1)
            ->exists();

        DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->update([
                'confirmed_at' => now(),
                'is_default' => $hasDefault ? 0 : 1,
                'updated_at' => now(),
            ]);
        if (!$hasDefault) {
            $this->markDefault($userId, $method);
        }
    }

    private function upsertConfirmedMethod(int $userId, string $method): void
    {
        $existing = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->first();
        $hasDefault = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNotNull('confirmed_at')
            ->where('is_default', 1)
            ->exists();

        if ($existing) {
            DB::table(self::TABLE)->where('id', $existing->id)->update([
                'confirmed_at' => now(),
                'is_default' => $hasDefault ? (int) $existing->is_default : 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table(self::TABLE)->insert([
                'user_id' => $userId,
                'method' => $method,
                'secret' => null,
                'confirmed_at' => now(),
                'is_default' => $hasDefault ? 0 : 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (!$hasDefault) {
            $this->markDefault($userId, $method);
        }
    }

    private function markDefault(int $userId, string $method): void
    {
        DB::table(self::TABLE)->where('user_id', $userId)->update(['is_default' => 0, 'updated_at' => now()]);
        DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('method', $method)
            ->update(['is_default' => 1, 'updated_at' => now()]);
    }

    private function disableChannel(int $userId, string $removing): string
    {
        $other = $removing === self::METHOD_TOTP ? self::METHOD_EMAIL : self::METHOD_TOTP;
        if ($this->methodConfirmed($userId, $other)) {
            return $other;
        }
        $user = $this->userRow($userId);
        if ($this->normalizedEmail($user->email ?? null) !== null) {
            return self::METHOD_EMAIL;
        }
        if ($this->normalizedEmail($user->backup_email ?? null) !== null) {
            return self::CHANNEL_BACKUP;
        }

        return 'none';
    }

    private function emailChangeChannel(int $userId): string
    {
        $default = $this->defaultChallengeChannel($userId);
        if ($default === self::METHOD_TOTP && $this->methodConfirmed($userId, self::METHOD_TOTP)) {
            return self::METHOD_TOTP;
        }
        if ($this->methodConfirmed($userId, self::METHOD_EMAIL)) {
            return self::METHOD_EMAIL;
        }
        if ($this->methodConfirmed($userId, self::METHOD_TOTP)) {
            return self::METHOD_TOTP;
        }

        return self::METHOD_EMAIL;
    }

    private function emailTaken(string $email, int $exceptUserId): bool
    {
        $asPrimary = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $exceptUserId)
            ->exists();
        $asBackup = DB::table('users')
            ->whereRaw('LOWER(backup_email) = ?', [$email])
            ->where('id', '!=', $exceptUserId)
            ->exists();

        return $asPrimary || $asBackup;
    }

    /**
     * Возвращает issuer для otpauth (бренд текущего product, хост обязан наложить config).
     */
    private function issuer(): string
    {
        $fromConfig = trim((string) config('user.two_factor.issuer'));
        if ($fromConfig === '') {
            throw new \RuntimeException('user.two_factor.issuer is empty; host must set it from the current product');
        }

        return $fromConfig;
    }

    private function qrSvg(string $uri): string
    {
        if (!class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return '';
        }

        return (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::encoding('UTF-8')->size(200)->generate($uri);
    }

    private function attemptLock(int $userId): string
    {
        return 'two-factor:' . $userId . ':' . request()->getHost();
    }

    private function userRow(int $userId): object
    {
        $row = DB::table('users')->where('id', $userId)->first();
        if (!$row) {
            throw new \RuntimeException('users row missing for two-factor user_id=' . $userId);
        }

        return $row;
    }

    private function normalizedEmail(mixed $value): ?string
    {
        $email = strtolower(trim((string) $value));

        return $email !== '' ? $email : null;
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $at = strpos($email, '@');
        if ($at === false) {
            return $email;
        }
        $name = substr($email, 0, $at);
        $domain = substr($email, $at);
        $length = strlen($name);
        if ($length <= 2) {
            return $name . '***' . $domain;
        }
        $tailLength = $length >= 4 ? 2 : 1;

        return substr($name, 0, 2) . '***' . substr($name, -$tailLength) . $domain;
    }
}
