<?php

namespace Doxa\User\Libraries;

/**
 * TOTP (RFC 6238): секрет, проверка кода, otpauth URI для QR.
 */
class TwoFactorTotp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    /**
     * Возвращает новый секрет в Base32.
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Возвращает otpauth:// URI для приложения-аутентификатора.
     */
    public function otpauthUri(string $secret, string $issuer, string $account): string
    {
        $label = rawurlencode($issuer . ':' . $account);

        return 'otpauth://totp/' . $label . '?' . http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Возвращает true, если код совпадает с текущим или соседним окном 30 сек.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = time();
        for ($offset = -1; $offset <= 1; $offset++) {
            $expected = $this->at($secret, intdiv($now, self::PERIOD) + $offset);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    private function at(string $secret, int $counter): string
    {
        $binary = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $counter), $binary, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($truncated % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bits = 0;
        $out = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $out .= $alphabet[($buffer >> $bits) & 31];
            }
        }
        if ($bits > 0) {
            $out .= $alphabet[($buffer << (5 - $bits)) & 31];
        }

        return $out;
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bits = 0;
        $out = '';
        $length = strlen($secret);
        for ($i = 0; $i < $length; $i++) {
            $pos = strpos($alphabet, $secret[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 255);
            }
        }

        return $out;
    }
}
