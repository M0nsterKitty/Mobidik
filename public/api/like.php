<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/security.php';
require __DIR__ . '/../../lib/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../config/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$messageId = (int) ($input['message_id'] ?? 0);
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!csrf_validate($token)) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($messageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz mesaj.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$ipHash = get_ip_hash(get_client_ip(), $config['app']['ip_salt']);

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare('SELECT 1 FROM likes WHERE message_id = :message_id AND ip_hash = :ip_hash');
    $check->execute([
        ':message_id' => $messageId,
        ':ip_hash' => $ipHash,
    ]);

    if ($check->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bu mesajı zaten beğendiniz.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $insert = $pdo->prepare('INSERT INTO likes (message_id, ip_hash) VALUES (:message_id, :ip_hash)');
    $insert->execute([
        ':message_id' => $messageId,
        ':ip_hash' => $ipHash,
    ]);

    $update = $pdo->prepare('UPDATE messages SET likes = likes + 1 WHERE id = :id');
    $update->execute([':id' => $messageId]);

    $likesStmt = $pdo->prepare('SELECT likes FROM messages WHERE id = :id');
    $likesStmt->execute([':id' => $messageId]);
    $likes = (int) $likesStmt->fetchColumn();

    $pdo->commit();

    echo json_encode(['success' => true, 'likes' => $likes], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Beğeni alınamadı.'], JSON_UNESCAPED_UNICODE);
}
