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
use Doxa\User\Libraries\Registration as REG;
use Doxa\User\Mail\FacebookLinkEmail;
use Doxa\User\Mail\GoogleLinkEmail;

class SocialAuthService
{
    public const MAGIC_TTL_MINUTES = 15;

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
                'magic_route' => 'auth.google.link.magic',
                'mail' => GoogleLinkEmail::class,
                'label' => 'Google',
            ],
            'facebook' => [
                'id_column' => 'facebook_id',
                'log' => 'auth_facebook',
                'pending_session_key' => 'facebook_auth_pending',
                'magic_cache_prefix' => 'facebook_link:',
                'link_route' => 'auth.facebook.link',
                'magic_route' => 'auth.facebook.link.magic',
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

        Auth::login($user, true);
        request()->session()->regenerate();
        $this->clearPending($this->providerNameFromConfig($provider));

        REG::init();
        REG::setUserFromAuth();

        return [
            'action' => 'redirect',
            'url' => REG::getSuccessAuthUrl(),
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
     * @return array{action: string, message?: string}
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

        $token = Str::random(64);
        Cache::put($provider['magic_cache_prefix'] . $token, [
            'user_id' => $user->id,
            'social_id' => $socialId,
            'email' => $pending['email'],
            'provider' => $providerName,
        ], now()->addMinutes(self::MAGIC_TTL_MINUTES));

        $url = route($provider['magic_route'], ['token' => $token]);
        $mailClass = $provider['mail'];

        Mail::to($user->email)->send(new $mailClass([
            'email' => $user->email,
            'link' => $url,
            'expires_minutes' => self::MAGIC_TTL_MINUTES,
            'provider_label' => $provider['label'],
        ]));

        Clog::write($provider['log'], 'Magic link sent to ' . $user->email, Clog::NOTICE);

        return [
            'action' => 'ok',
            'message' => 'We sent a confirmation link to ' . $user->email . '. It is valid for ' . self::MAGIC_TTL_MINUTES . ' minutes.',
        ];
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    public function linkWithMagicToken(string $token): array
    {
        $payload = null;
        $providerName = null;

        foreach ($this->providers() as $name => $provider) {
            if (!self::isAuthEnabled($name)) {
                continue;
            }
            $payload = Cache::pull($provider['magic_cache_prefix'] . $token);
            if ($payload) {
                $providerName = $name;
                break;
            }
        }

        if (!$payload || empty($payload['user_id']) || empty($payload['social_id']) || !$providerName) {
            return ['action' => 'error', 'message' => 'Link is invalid or expired.'];
        }

        $provider = $this->provider($providerName);
        $user = User::find($payload['user_id']);
        if (!$user) {
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        $this->storePending($providerName, [
            'user_id' => $user->id,
            'email' => $user->email,
            $provider['id_column'] => $payload['social_id'],
        ]);

        return $this->completeLink($user, (string) $payload['social_id'], $providerName);
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
