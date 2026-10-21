<?php
declare(strict_types=1);

return [
    'name' => 'Walkie Talkie',
    'base_path' => getenv('APP_BASE_PATH') ?: '',
    'signal_secret' => getenv('SIGNAL_SECRET') ?: 'local-development-secret',
    'max_peers' => max(2, (int) (getenv('SIGNAL_MAX_PEERS') ?: 8)),
    'floor_timeout_ms' => max(5000, (int) (getenv('SIGNAL_FLOOR_TIMEOUT_MS') ?: 30000)),
    'max_body' => max(4096, (int) (getenv('SIGNAL_MAX_BODY') ?: 32768)),
    'max_data' => max(1024, (int) (getenv('SIGNAL_MAX_DATA') ?: 12288)),
];
