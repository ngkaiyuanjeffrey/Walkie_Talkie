<?php
declare(strict_types=1);

require __DIR__ . '/app/Core/Env.php';
require __DIR__ . '/app/Core/Http.php';
require __DIR__ . '/app/Services/TokenService.php';
require __DIR__ . '/app/Services/RoomService.php';

load_env(__DIR__ . '/.env');
$config = require __DIR__ . '/config/app.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$rooms = new RoomService(__DIR__ . '/storage/rooms', $config['max_peers'], $config['floor_timeout_ms']);
$tokens = new TokenService($config['signal_secret']);
$api = $_GET['api'] ?? '';

try {
    if ($api !== '') {
        $input = request_json($config['max_body']);
        if (($input['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
            json_response(['ok' => false, 'error' => 'Your session token is invalid. Refresh and try again.'], 419);
        }
        $action = $input['action'] ?? $api;
        if ($action === 'join') {
            $nickname = trim((string) ($input['nickname'] ?? ''));
            $channel = strtolower(trim((string) ($input['channel'] ?? '')));
            $nickname = preg_replace('/[^\\p{L}\\p{N} _-]/u', '', $nickname) ?: '';
            $channel = preg_replace('/[^a-z0-9_-]+/i', '-', $channel) ?: '';
            if ($nickname === '' || mb_strlen($nickname) > 24 || $channel === '' || strlen($channel) > 64) {
                json_response(['ok' => false, 'error' => 'Enter a nickname up to 24 characters and a valid channel name.'], 422);
            }
            $peer = bin2hex(random_bytes(8));
            $token = $tokens->issue($peer, $nickname, $channel);
            $state = $rooms->join($channel, $peer, $nickname);
            $_SESSION['walkie'] = compact('peer', 'nickname', 'channel', 'token');
            json_response(['ok' => true, 'peer' => $peer, 'nickname' => $nickname, 'channel' => $channel, 'token' => $token, 'csrf' => $_SESSION['csrf'], 'state' => $state]);
        }
        $token = (string) ($input['token'] ?? ($_SESSION['walkie']['token'] ?? ''));
        $identity = $tokens->verify($token);
        if (!$identity || ($identity['channel'] ?? '') !== ($_SESSION['walkie']['channel'] ?? $identity['channel'] ?? '')) {
            json_response(['ok' => false, 'error' => 'The communication session has expired.'], 401);
        }
        $channel = (string) $identity['channel'];
        $peer = (string) $identity['peer'];
        $result = match ($action) {
            'poll' => $rooms->poll($channel, $peer, max(0, (int) ($input['after'] ?? 0))),
            'ptt_request' => $rooms->ptt($channel, $peer, (string) $identity['nickname'], false),
            'ptt_release' => $rooms->ptt($channel, $peer, (string) $identity['nickname'], true),
            'signal' => $rooms->signal($channel, $peer, ['to' => (string) ($input['to'] ?? ''), 'data' => $input['data'] ?? null]),
            'leave' => $rooms->leave($channel, $peer),
            default => throw new InvalidArgumentException('Unknown signaling action.'),
        };
        json_response(['ok' => true] + $result);
    }

    $view = isset($_GET['channel']) || isset($_SESSION['walkie']) ? 'channel' : 'join';
    require __DIR__ . '/views/' . $view . '.php';
} catch (Throwable $error) {
    if ($api !== '') {
        json_response(['ok' => false, 'error' => $error instanceof RuntimeException ? $error->getMessage() : 'Unable to connect to the communication server.'], 500);
    }
    http_response_code(500);
    echo 'Unable to load Walkie Talkie.';
}
