<?php

declare(strict_types=1);

return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=mobidick;charset=utf8mb4',
        'user' => 'root',
        'pass' => '',
    ],
    'app' => [
        'ip_salt' => 'change_this_to_a_long_random_secret',
        'require_approval' => false,
        'message_min' => 20,
        'message_max' => 500,
        'rate_limit_seconds' => 60,
    ],
];
