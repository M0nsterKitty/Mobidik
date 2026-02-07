<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/security.php';
require __DIR__ . '/../../lib/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';
$ipHash = get_ip_hash(get_client_ip(), $config['app']['ip_salt']);

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, content, created_at, likes FROM messages WHERE is_hidden = 0 ORDER BY created_at DESC LIMIT 100');
    $stmt->execute();
    $messages = $stmt->fetchAll();

    $likedIds = [];
    if (!empty($messages)) {
        $ids = array_column($messages, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $likeStmt = $pdo->prepare("SELECT message_id FROM likes WHERE ip_hash = ? AND message_id IN ($placeholders)");
        $likeStmt->execute(array_merge([$ipHash], $ids));
        $likedIds = array_column($likeStmt->fetchAll(), 'message_id');
    }

    $messages = array_map(function ($msg) use ($likedIds) {
        return [
            'id' => (int) $msg['id'],
            'content' => $msg['content'],
            'created_at' => $msg['created_at'],
            'likes' => (int) $msg['likes'],
            'liked' => in_array($msg['id'], $likedIds, true),
        ];
    }, $messages);

    echo json_encode(['success' => true, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Mesajlar yüklenemedi.'], JSON_UNESCAPED_UNICODE);
}
