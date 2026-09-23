<?php

namespace Doxa\User\Libraries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Doxa\Core\Libraries\Logging\Clog;
use Doxa\User\Libraries\Onboarding;
use Doxa\User\Libraries\Registration as REG;
use Doxa\User\Mail\FacebookLinkEmail;
use Doxa\User\Mail\GoogleLinkEmail;

class SocialAuthService
{
    public const MAGIC_TTL_MINUTES = 15;

    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {
    }

    public static function isAuthEnabled(string $provider): bool
    {
        return (bool) config("services.{$provider}.auth_enabled");
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function providers(): array
    {
        return [
            'google' => [
                'id_column' => 'google_id',
                'log' => 'auth_google',
                'pending_session_key' => 'google_auth_pending',
                'magic_cache_prefix' => 'google_link:',
                'link_route' => 'auth.google.link',
                'mail' => GoogleLinkEmail::class,
                'label' => 'Google',
            ],
            'facebook' => [
                'id_column' => 'facebook_id',
                'log' => 'auth_facebook',
                'pending_session_key' => 'facebook_auth_pending',
                'magic_cache_prefix' => 'facebook_link:',
                'link_route' => 'auth.facebook.link',
                'mail' => FacebookLinkEmail::class,
                'label' => 'Facebook',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function provider(string $name): array
    {
        $providers = $this->providers();
        if (!isset($providers[$name])) {
            throw new \InvalidArgumentException('Unknown social auth provider: ' . $name);
        }

        return $providers[$name];
    }

    /**
     * Handle Socialite user after OAuth callback.
     *
     * @return array{action: string, url?: string, message?: string}
     */
    public function handleSocialUser(SocialiteUser $socialUser, string $providerName): array
    {
        if (!self::isAuthEnabled($providerName)) {
            return ['action' => 'error', 'message' => 'This sign-in method is disabled.'];
        }

        $provider = $this->provider($providerName);
        $idColumn = $provider['id_column'];
        $socialId = (string) $socialUser->getId();

        Clog::write(REG::LOG, 'Onboarding social handleSocialUser', [
            'provider' => $providerName,
            'session' => session(Onboarding::SESSION_KEY),
        ], Clog::NOTICE);

        if ($socialId === '') {
            return ['action' => 'error', 'message' => $provider['label'] . ' account id is missing.'];
        }

        // Email is optional for social auth (Facebook often has none).
        $email = strtolower(trim((string) $socialUser->getEmail()));
        if ($email === '') {
            $email = null;
        }

        $bySocial = User::where($idColumn, $socialId)->first();
        if ($bySocial) {
            return $this->loginExisting($bySocial, $provider);
        }

        // Link flow only when provider gave a verified/usable email that already exists.
        if ($email !== null) {
            if ($providerName === 'google') {
                $verified = $socialUser->user['email_verified']
                    ?? $socialUser->user['verified_email']
                    ?? true;

                if (!$verified) {
                    // Unverified email must not be used for matching/linking —
                    // create a social-only account instead.
                    $email = null;
                }
            }
        }

        if ($email !== null) {
            $byEmail = User::where('email', $email)->first();
            if ($byEmail) {
                $existingSocialId = (string) ($byEmail->{$idColumn} ?? '');

                if ($existingSocialId !== '' && $existingSocialId !== $socialId) {
                    return [
                        'action' => 'error',
                        'message' => 'This email is linked to another ' . $provider['label'] . ' account.',
                    ];
                }

                if ($existingSocialId !== '' && $existingSocialId === $socialId) {
                    return $this->loginExisting($byEmail, $provider);
                }

                $this->storePending($providerName, [
                    'user_id' => $byEmail->id,
                    'email' => $email,
                    $idColumn => $socialId,
                    'name' => $socialUser->getName(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                return [
                    'action' => 'link',
                    'url' => route($provider['link_route']),
                ];
            }
        }

        $user = $this->createUserFromSocial($email, $socialId, $socialUser->getName(), $provider);
        return $this->loginExisting($user, $provider);
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    public function handleGoogleUser(SocialiteUser $googleUser): array
    {
        return $this->handleSocialUser($googleUser, 'google');
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    public function handleFacebookUser(SocialiteUser $facebookUser): array
    {
        return $this->handleSocialUser($facebookUser, 'facebook');
    }

    /**
     * @param array<string, mixed> $provider
     */
    protected function createUserFromSocial(?string $email, string $socialId, ?string $name, array $provider): User
    {
        $idColumn = $provider['id_column'];
        $displayName = trim((string) $name);
        if ($displayName === '') {
            $displayName = $email ? Str::before($email, '@') : ($provider['label'] . ' user');
        }

        $user = User::create([
            'email' => $email,
            'name' => $displayName,
            'admin' => 0,
            'active' => 1,
            'status' => REG::READY_STATUS,
            'password' => null,
            'v_hash' => Str::random(32),
            'secret' => Hash::make(Str::random(40)),
        ]);

        $user->forceFill([$idColumn => $socialId])->save();

        Clog::write(REG::LOG, 'Onboarding social createUserFromSocial', [
            'user_id' => $user->id,
            'provider' => $provider['label'],
            'session' => session(Onboarding::SESSION_KEY),
        ], Clog::NOTICE);

        if (function_exists('mr')) {
            try {
                mr('user_profile')->create($user, 0);
            } catch (\Throwable $e) {
                Clog::write($provider['log'], 'user_profile create skipped: ' . $e->getMessage(), Clog::WARNING);
            }
        }

        Clog::write(
            $provider['log'],
            'Created user from ' . $provider['label'] . ': ' . ($email ?: ('no email, ' . $idColumn . '=' . $socialId)),
            Clog::NOTICE
        );

        return $user;
    }

    /**
     * @param array<string, mixed> $provider
     * @return array{action: string, url?: string, message?: string}
     */
    protected function loginExisting(User $user, array $provider): array
    {
        if (method_exists($user, 'isSuspended') && $user->isSuspended()) {
            return ['action' => 'redirect', 'url' => route('auth.suspended')];
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            return [
                'action' => 'error',
                'message' => 'This account is not active yet. Please wait for activation or contact support.',
            ];
        }

        if ($this->twoFactor->hasConfirmedMethod((int) $user->id)) {
            $this->twoFactor->storePendingLogin((int) $user->id, true, 'session');
            $this->clearPending($this->providerNameFromConfig($provider));

            return [
                'action' => 'redirect',
                'url' => route('auth.two_factor'),
            ];
        }

        Auth::login($user, true);
        request()->session()->regenerate();
        $this->clearPending($this->providerNameFromConfig($provider));

        REG::init();
        REG::setUserFromAuth();
        REG::persistOnboarding(clearSession: true, replaceSuccessUrl: true);
        REG::recordLoginArtifacts();

        $url = REG::getSuccessAuthUrl();
        Clog::write(
            REG::LOG,
            'Onboarding: после social-login (' . $this->providerNameFromConfig($provider) . ') '
            . 'редирект user_id=' . $user->id . ' → ' . $url . '.',
            Clog::NOTICE
        );

        return [
            'action' => 'redirect',
            'url' => $url,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function storePending(string $providerName, array $data): void
    {
        $provider = $this->provider($providerName);
        session([$provider['pending_session_key'] => $data]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPending(string $providerName): ?array
    {
        $provider = $this->provider($providerName);
        $data = session($provider['pending_session_key']);

        return is_array($data) ? $data : null;
    }

    public function clearPending(string $providerName): void
    {
        $provider = $this->provider($providerName);
        session()->forget($provider['pending_session_key']);
    }

    /**
     * @return array{action: string, url?: string, message?: string, errors?: array}
     */
    public function linkWithPassword(string $password, string $providerName): array
    {
        if (!self::isAuthEnabled($providerName)) {
            return ['action' => 'error', 'message' => 'This sign-in method is disabled.'];
        }

        $provider = $this->provider($providerName);
        $idColumn = $provider['id_column'];
        $pending = $this->getPending($providerName);

        if (!$pending) {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        $user = User::find($pending['user_id']);
        if (!$user) {
            $this->clearPending($providerName);
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'action' => 'error',
                'errors' => ['password' => 'Password is incorrect.'],
            ];
        }

        $socialId = (string) ($pending[$idColumn] ?? '');
        if ($socialId === '') {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        return $this->completeLink($user, $socialId, $providerName);
    }

    /**
     * Шлёт на email код подтверждения привязки соц.аккаунта (не ссылку).
     * Лимиты resend — config/registration.php (как у регистрации).
     *
     * @return array{action: string, message?: string, timer?: int, confirmation?: array<string, mixed>}
     */
    public function sendMagicLink(string $providerName): array
    {
        if (!self::isAuthEnabled($providerName)) {
            return ['action' => 'error', 'message' => 'This sign-in method is disabled.'];
        }

        $provider = $this->provider($providerName);
        $idColumn = $provider['id_column'];
        $pending = $this->getPending($providerName);

        if (!$pending) {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        $user = User::find($pending['user_id']);
        if (!$user) {
            $this->clearPending($providerName);
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        $socialId = (string) ($pending[$idColumn] ?? '');
        if ($socialId === '') {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        $userId = (int) $user->id;
        $timer = $this->getLinkResendTimer($providerName, $userId);
        if ($timer > 0) {
            return [
                'action' => 'wait',
                'timer' => $timer,
                'confirmation' => [
                    'type' => 'warning',
                    'title' => 'To often!',
                    'message' => 'You can\'t resend code too often. Wait please while timer will be finished.',
                    'buttons' => [
                        ['title' => 'Resend code', 'style' => 'primary', 'callback' => 'sendCode', 'timer' => $timer],
                        ['title' => 'Cancel', 'type' => 'cancel'],
                    ],
                ],
            ];
        }

        $plainCode = (string) random_int(100000, 999999);
        $expireMinutes = $this->linkCodeExpireMinutes();
        Cache::put($this->linkCodeCacheKey($providerName, $userId), [
            'secret' => Hash::make($plainCode),
            'user_id' => $user->id,
            'social_id' => $socialId,
            'email' => $pending['email'],
            'provider' => $providerName,
            'sent_at' => time(),
        ], now()->addMinutes(max($expireMinutes, (int) ceil($this->linkResendRateLimitTimeMinutes()))));

        $mailClass = $provider['mail'];
        Mail::to($user->email)->send(new $mailClass([
            'email' => $user->email,
            'code' => $plainCode,
            'code_expire_in' => $expireMinutes,
            'provider_label' => $provider['label'],
        ]));

        $this->checkAndIncrementLinkResendAttempts($userId);

        Clog::write($provider['log'], 'Link confirmation code sent to ' . $user->email, Clog::NOTICE);

        $resendTimer = $this->getLinkResendTimer($providerName, $userId);

        return [
            'action' => 'ok',
            'timer' => $resendTimer,
            'message' => 'We sent a confirmation code to ' . $user->email . '. It is valid for ' . $expireMinutes . ' minutes.',
            'confirmation' => [
                'type' => 'success',
                'title' => 'Code sent',
                'message' => 'Code has been sent to your email. Code valid for ' . $expireMinutes . ' minutes.',
                'buttons' => [
                    ['title' => 'OK', 'type' => 'cancel'],
                ],
            ],
        ];
    }

    /**
     * Подтверждает привязку кодом из письма.
     *
     * @return array{action: string, url?: string, message?: string, errors?: array<string, string>}
     */
    public function linkWithCode(string $code, string $providerName): array
    {
        if (!self::isAuthEnabled($providerName)) {
            return ['action' => 'error', 'message' => 'This sign-in method is disabled.'];
        }

        $provider = $this->provider($providerName);
        $pending = $this->getPending($providerName);

        if (!$pending) {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        $userId = (int) ($pending['user_id'] ?? 0);
        if ($userId < 1) {
            return ['action' => 'error', 'message' => 'Link session expired. Try ' . $provider['label'] . ' sign-in again.'];
        }

        $cacheKey = $this->linkCodeCacheKey($providerName, $userId);
        $payload = Cache::get($cacheKey);
        if (!is_array($payload) || empty($payload['secret']) || empty($payload['social_id'])) {
            return [
                'action' => 'error',
                'errors' => ['code' => vocab('verification_code_expired')],
            ];
        }

        $code = trim($code);
        if ($code === '' || !Hash::check($code, $payload['secret'])) {
            return [
                'action' => 'error',
                'errors' => ['code' => vocab('invalid_verification_code')],
            ];
        }

        Cache::forget($cacheKey);

        $user = User::find($userId);
        if (!$user) {
            $this->clearPending($providerName);
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        return $this->completeLink($user, (string) $payload['social_id'], $providerName);
    }

    /**
     * Секунды до следующей разрешённой отправки (как REG::getResendCodeTimer).
     */
    private function getLinkResendTimer(string $providerName, int $userId): int
    {
        $payload = Cache::get($this->linkCodeCacheKey($providerName, $userId));
        if (!is_array($payload) || empty($payload['sent_at'])) {
            return 0;
        }

        $rateLimitDelayFlag = Cache::get($this->linkResendRateLimitDelayKey($userId), 0);
        $delayMinutes = $rateLimitDelayFlag > 0
            ? $this->linkResendRateLimitTimeMinutes()
            : $this->linkResendDelayMinutes();

        $timer = (int) $payload['sent_at'] + (int) ceil($delayMinutes * 60) - time();
        if ($timer <= 0) {
            return 0;
        }

        return $timer;
    }

    /**
     * Счётчик попыток resend — те же ключи cache, что у регистрации.
     */
    private function checkAndIncrementLinkResendAttempts(int $userId): void
    {
        $cacheKey = 'verification_code_resend_counter_' . $userId;
        $timeLimitSeconds = (int) ceil($this->linkResendRateLimitTimeMinutes() * 60);
        $attempts = (int) Cache::get($cacheKey, 0);
        $attempts++;
        Cache::put($cacheKey, $attempts, $timeLimitSeconds);

        if ($attempts === $this->linkResendRateLimitMaxAttempts()) {
            Cache::put($this->linkResendRateLimitDelayKey($userId), 1, $timeLimitSeconds);
        }
    }

    private function linkResendRateLimitDelayKey(int $userId): string
    {
        return 'verification_code_rate_limit_delay_' . $userId;
    }

    private function linkResendDelayMinutes(): float
    {
        return (float) (config('registration.verification_code_delay') ?? 1);
    }

    private function linkResendRateLimitMaxAttempts(): int
    {
        return (int) (config('registration.resend_code_rate_limit_max_attempts') ?? 3);
    }

    private function linkResendRateLimitTimeMinutes(): float
    {
        return (float) (config('registration.resend_code_rate_limit_time') ?? 10);
    }

    private function linkCodeExpireMinutes(): int
    {
        return (int) (config('registration.verification_code_expire_in') ?? self::MAGIC_TTL_MINUTES);
    }

    /**
     * Ключ кеша кода привязки для пользователя и провайдера.
     */
    private function linkCodeCacheKey(string $providerName, int $userId): string
    {
        $provider = $this->provider($providerName);

        return $provider['magic_cache_prefix'] . 'code:' . $userId;
    }

    /**
     * @deprecated Ссылка больше не используется — оставлен для старых писем, всегда ошибка.
     *
     * @return array{action: string, message?: string}
     */
    public function linkWithMagicToken(string $token): array
    {
        return ['action' => 'error', 'message' => 'This confirmation link is no longer valid. Request a new code from the link page.'];
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    protected function completeLink(User $user, string $socialId, string $providerName): array
    {
        $provider = $this->provider($providerName);
        $idColumn = $provider['id_column'];

        $taken = User::where($idColumn, $socialId)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            $this->clearPending($providerName);
            return [
                'action' => 'error',
                'message' => 'This ' . $provider['label'] . ' account is already linked to another user.',
            ];
        }

        $user->forceFill([$idColumn => $socialId])->save();
        Clog::write($provider['log'], 'Linked ' . $provider['label'] . ' to user ' . $user->id, Clog::NOTICE);

        Clog::write(REG::LOG, 'Onboarding social completeLink', [
            'user_id' => $user->id,
            'provider' => $providerName,
            'session' => session(Onboarding::SESSION_KEY),
        ], Clog::NOTICE);

        return $this->loginExisting($user->fresh(), $provider);
    }

    /**
     * @param array<string, mixed> $provider
     */
    protected function providerNameFromConfig(array $provider): string
    {
        foreach ($this->providers() as $name => $config) {
            if ($config['pending_session_key'] === $provider['pending_session_key']) {
                return $name;
            }
        }

        return 'google';
    }
}
