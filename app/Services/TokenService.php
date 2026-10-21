<?php
declare(strict_types=1);

final class TokenService
{
    public function __construct(private readonly string $secret)
    {
    }

    public function issue(string $peerId, string $nickname, string $channel): string
    {
        $payload = ['peer' => $peerId, 'nickname' => $nickname, 'channel' => $channel, 'exp' => time() + 86400];
        $encoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        return $encoded . '.' . hash_hmac('sha256', $encoded, $this->secret);
    }

    public function verify(string $token): ?array
    {
        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, '');
        $expected = hash_hmac('sha256', $encoded, $this->secret);
        if ($encoded === '' || !hash_equals($expected, $signature)) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($encoded, '-_', '+/')) ?: '', true);
        return is_array($payload) && ($payload['exp'] ?? 0) >= time() ? $payload : null;
    }
}
