<?php

declare(strict_types=1);

function get_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return $ip;
}

function get_ip_hash(string $ip, string $salt): string
{
    return hash('sha256', $ip . $salt);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function filter_message(string $message): string
{
    $message = strip_tags($message);

    $message = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $message);
    $message = preg_replace('/\bhttps?:\/\/\S+/i', '[link kaldırıldı]', $message);
    $message = preg_replace('/\bwww\.\S+/i', '[link kaldırıldı]', $message);

    $badWords = [
        'küfür1',
        'küfür2',
        'kufur3',
    ];

    foreach ($badWords as $badWord) {
        $pattern = '/' . preg_quote($badWord, '/') . '/iu';
        $message = preg_replace($pattern, '***', $message);
    }

    return trim($message);
}

function log_action(PDO $pdo, string $action, string $ipHash): void
{
    $stmt = $pdo->prepare('INSERT INTO logs (action, created_at, ip_hash) VALUES (:action, NOW(), :ip_hash)');
    $stmt->execute([
        ':action' => $action,
        ':ip_hash' => $ipHash,
    ]);
}
