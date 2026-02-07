<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/security.php';
require __DIR__ . '/../../lib/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$content = trim((string) ($input['content'] ?? ''));
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!csrf_validate($token)) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$min = $config['app']['message_min'];
$max = $config['app']['message_max'];

if (mb_strlen($content) < $min || mb_strlen($content) > $max) {
    echo json_encode(['success' => false, 'message' => "Mesaj {$min}-{$max} karakter olmalıdır."], JSON_UNESCAPED_UNICODE);
    exit;
}

$filtered = filter_message($content);
if ($filtered === '') {
    echo json_encode(['success' => false, 'message' => 'Mesaj geçersiz içerik içeriyor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$ipHash = get_ip_hash(get_client_ip(), $config['app']['ip_salt']);

$stmt = $pdo->prepare('SELECT created_at FROM messages WHERE ip_hash = :ip_hash ORDER BY created_at DESC LIMIT 1');
$stmt->execute([':ip_hash' => $ipHash]);
$last = $stmt->fetchColumn();

if ($last) {
    $lastTime = new DateTime($last);
    $diff = (new DateTime())->getTimestamp() - $lastTime->getTimestamp();
    if ($diff < $config['app']['rate_limit_seconds']) {
        echo json_encode(['success' => false, 'message' => 'Lütfen biraz bekleyip tekrar deneyin.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$isHidden = $config['app']['require_approval'] ? 1 : 0;

$stmt = $pdo->prepare('INSERT INTO messages (content, created_at, ip_hash, likes, is_hidden) VALUES (:content, NOW(), :ip_hash, 0, :is_hidden)');
$stmt->execute([
    ':content' => $filtered,
    ':ip_hash' => $ipHash,
    ':is_hidden' => $isHidden,
]);

$id = (int) $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'Mesaj alındı.',
    'data' => [
        'id' => $id,
        'content' => $filtered,
        'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
        'likes' => 0,
    ],
], JSON_UNESCAPED_UNICODE);
