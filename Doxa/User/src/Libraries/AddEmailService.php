<?php

namespace Doxa\User\Libraries;

use Doxa\User\Mail\AddEmailVerificationEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AddEmailService
{
    private const SESSION_KEY = 'profile_add_email';
    private const CODE_TTL_MINUTES = 5;
    private const RESEND_SECONDS = 60;

    /**
     * @return array{timer: int, code_expire_in: int, throttled?: bool}|string
     */
    public function requestVerification(string $email): array|string
    {
        $user = Auth::user();
        if (!$user) {
            return 'Unauthorized';
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return vocab('invalid_email');
        }

        $current = strtolower(trim((string) ($user->email ?? '')));
        if ($current !== '') {
            return vocab('email_already_set');
        }

        $taken = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($taken) {
            return vocab('email_already_taken');
        }

        $pending = session(self::SESSION_KEY);
        if (is_array($pending) && isset($pending['sent_at'])) {
            $elapsed = time() - (int) $pending['sent_at'];
            if ($elapsed < self::RESEND_SECONDS) {
                return [
                    'timer' => self::RESEND_SECONDS - $elapsed,
                    'code_expire_in' => self::CODE_TTL_MINUTES,
                    'throttled' => true,
                ];
            }
        }

        $plainCode = (string) random_int(100000, 999999);

        session([
            self::SESSION_KEY => [
                'email' => $email,
                'secret' => Hash::make($plainCode),
                'sent_at' => time(),
                'expires_at' => time() + (self::CODE_TTL_MINUTES * 60),
            ],
        ]);

        Mail::to($email)->send(new AddEmailVerificationEmail([
            'code' => $plainCode,
            'code_expire_in' => self::CODE_TTL_MINUTES,
            'email' => $email,
        ]));

        return [
            'timer' => self::RESEND_SECONDS,
            'code_expire_in' => self::CODE_TTL_MINUTES,
        ];
    }

    /**
     * @return true|string
     */
    public function confirm(string $code): bool|string
    {
        $user = Auth::user();
        if (!$user) {
            return 'Unauthorized';
        }

        $current = strtolower(trim((string) ($user->email ?? '')));
        if ($current !== '') {
            return vocab('email_already_set');
        }

        $pending = session(self::SESSION_KEY);
        if (!is_array($pending) || empty($pending['email']) || empty($pending['secret'])) {
            return vocab('verification_code_expired');
        }

        if (time() > (int) ($pending['expires_at'] ?? 0)) {
            session()->forget(self::SESSION_KEY);
            return vocab('verification_code_expired');
        }

        $code = trim($code);
        if ($code === '' || !Hash::check($code, $pending['secret'])) {
            return vocab('invalid_verification_code');
        }

        $email = strtolower(trim((string) $pending['email']));

        $taken = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($taken) {
            session()->forget(self::SESSION_KEY);
            return vocab('email_already_taken');
        }

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'email' => $email,
                'updated_at' => now(),
            ]);

        $user->email = $email;
        session()->forget(self::SESSION_KEY);

        return true;
    }
}
