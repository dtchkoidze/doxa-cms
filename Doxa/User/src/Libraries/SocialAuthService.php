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
use Doxa\User\Mail\GoogleLinkEmail;

class SocialAuthService
{
    public const LOG = 'auth_google';

    public const PENDING_SESSION_KEY = 'google_auth_pending';

    public const MAGIC_CACHE_PREFIX = 'google_link:';

    public const MAGIC_TTL_MINUTES = 15;

    /**
     * Handle Socialite user after Google callback.
     *
     * @return array{action: string, url?: string, message?: string}
     */
    public function handleGoogleUser(SocialiteUser $googleUser): array
    {
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();

        if ($email === '' || $googleId === '') {
            return ['action' => 'error', 'message' => 'Google account did not provide email.'];
        }

        // Socialite maps user.email_verified / user['email_verified'] depending on driver version
        $verified = $googleUser->user['email_verified']
            ?? $googleUser->user['verified_email']
            ?? true;

        if (!$verified) {
            return ['action' => 'error', 'message' => 'Google email is not verified.'];
        }

        $byGoogle = User::where('google_id', $googleId)->first();
        if ($byGoogle) {
            return $this->loginExisting($byGoogle);
        }

        $byEmail = User::where('email', $email)->first();
        if ($byEmail) {
            if (!empty($byEmail->google_id) && $byEmail->google_id !== $googleId) {
                return ['action' => 'error', 'message' => 'This email is linked to another Google account.'];
            }

            // Already linked somehow — treat as login
            if (!empty($byEmail->google_id) && $byEmail->google_id === $googleId) {
                return $this->loginExisting($byEmail);
            }

            // Need explicit link confirmation (password or magic link)
            $this->storePending([
                'user_id' => $byEmail->id,
                'email' => $email,
                'google_id' => $googleId,
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            return [
                'action' => 'link',
                'url' => route('auth.google.link'),
            ];
        }

        $user = $this->createUserFromGoogle($email, $googleId, $googleUser->getName());
        return $this->loginExisting($user);
    }

    protected function createUserFromGoogle(string $email, string $googleId, ?string $name): User
    {
        $user = User::create([
            'email' => $email,
            'name' => $name ?: Str::before($email, '@'),
            'admin' => 0,
            'active' => 1,
            'status' => REG::READY_STATUS,
            // Unusable random password — login via Google (or set via recovery later)
            'password' => Hash::make(Str::random(64)),
            'v_hash' => Str::random(32),
            'secret' => Hash::make(Str::random(40)),
        ]);

        // Host User model may not have google_id in $fillable
        $user->forceFill(['google_id' => $googleId])->save();

        if (function_exists('mr')) {
            try {
                mr('user_profile')->create($user, 0);
            } catch (\Throwable $e) {
                Clog::write(self::LOG, 'user_profile create skipped: ' . $e->getMessage(), Clog::WARNING);
            }
        }

        Clog::write(self::LOG, 'Created user from Google: ' . $email, Clog::NOTICE);

        return $user;
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    protected function loginExisting(User $user): array
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
        $this->clearPending();

        REG::init();
        REG::setUserFromAuth();

        return [
            'action' => 'redirect',
            'url' => REG::getSuccessAuthUrl(),
        ];
    }

    public function storePending(array $data): void
    {
        session([self::PENDING_SESSION_KEY => $data]);
    }

    public function getPending(): ?array
    {
        $data = session(self::PENDING_SESSION_KEY);
        return is_array($data) ? $data : null;
    }

    public function clearPending(): void
    {
        session()->forget(self::PENDING_SESSION_KEY);
    }

    /**
     * Link Google using account password.
     *
     * @return array{action: string, url?: string, message?: string, errors?: array}
     */
    public function linkWithPassword(string $password): array
    {
        $pending = $this->getPending();
        if (!$pending) {
            return ['action' => 'error', 'message' => 'Link session expired. Try Google sign-in again.'];
        }

        $user = User::find($pending['user_id']);
        if (!$user) {
            $this->clearPending();
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'action' => 'error',
                'errors' => ['password' => 'Password is incorrect.'],
            ];
        }

        return $this->completeLink($user, $pending['google_id']);
    }

    /**
     * Send magic link to existing account email to confirm Google link.
     *
     * @return array{action: string, message?: string}
     */
    public function sendMagicLink(): array
    {
        $pending = $this->getPending();
        if (!$pending) {
            return ['action' => 'error', 'message' => 'Link session expired. Try Google sign-in again.'];
        }

        $user = User::find($pending['user_id']);
        if (!$user) {
            $this->clearPending();
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        $token = Str::random(64);
        Cache::put(self::MAGIC_CACHE_PREFIX . $token, [
            'user_id' => $user->id,
            'google_id' => $pending['google_id'],
            'email' => $pending['email'],
        ], now()->addMinutes(self::MAGIC_TTL_MINUTES));

        $url = route('auth.google.link.magic', ['token' => $token]);

        Mail::to($user->email)->send(new GoogleLinkEmail([
            'email' => $user->email,
            'link' => $url,
            'expires_minutes' => self::MAGIC_TTL_MINUTES,
        ]));

        Clog::write(self::LOG, 'Magic link sent to ' . $user->email, Clog::NOTICE);

        return [
            'action' => 'ok',
            'message' => 'We sent a confirmation link to ' . $user->email . '. It is valid for ' . self::MAGIC_TTL_MINUTES . ' minutes.',
        ];
    }

    /**
     * Complete link via magic token from email.
     *
     * @return array{action: string, url?: string, message?: string}
     */
    public function linkWithMagicToken(string $token): array
    {
        $payload = Cache::pull(self::MAGIC_CACHE_PREFIX . $token);
        if (!$payload || empty($payload['user_id']) || empty($payload['google_id'])) {
            return ['action' => 'error', 'message' => 'Link is invalid or expired.'];
        }

        $user = User::find($payload['user_id']);
        if (!$user) {
            return ['action' => 'error', 'message' => 'User not found.'];
        }

        // Refresh pending so completeLink can clear it
        $this->storePending([
            'user_id' => $user->id,
            'email' => $user->email,
            'google_id' => $payload['google_id'],
        ]);

        return $this->completeLink($user, $payload['google_id']);
    }

    /**
     * @return array{action: string, url?: string, message?: string}
     */
    protected function completeLink(User $user, string $googleId): array
    {
        $taken = User::where('google_id', $googleId)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            $this->clearPending();
            return ['action' => 'error', 'message' => 'This Google account is already linked to another user.'];
        }

        // Host User model may not have google_id in $fillable
        $user->forceFill(['google_id' => $googleId])->save();
        Clog::write(self::LOG, 'Linked Google to user ' . $user->id, Clog::NOTICE);

        return $this->loginExisting($user->fresh());
    }
}
