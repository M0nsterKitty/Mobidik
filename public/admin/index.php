<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/security.php';
require __DIR__ . '/../../lib/csrf.php';

$config = require __DIR__ . '/../../config/config.php';
$pdo = db();
$ipHash = get_ip_hash(get_client_ip(), $config['app']['ip_salt']);

$token = csrf_token();
$errors = [];
$success = '';

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_user']);
    header('Location: /admin');
    exit;
}

if (empty($_SESSION['admin_user']) && isset($_POST['login'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $stmt = $pdo->prepare('SELECT username, password_hash FROM admin WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_user'] = $admin['username'];
            log_action($pdo, 'Admin giriş yaptı', $ipHash);
            header('Location: /admin');
            exit;
        }
        $errors[] = 'Giriş bilgileri hatalı.';
    }
}

if (!empty($_SESSION['admin_user']) && isset($_POST['action'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $messageId = (int) ($_POST['message_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');

        if ($messageId > 0) {
            if ($action === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
                $stmt->execute([':id' => $messageId]);
                log_action($pdo, "Mesaj silindi: {$messageId}", $ipHash);
                $success = 'Mesaj silindi.';
            } elseif ($action === 'hide') {
                $stmt = $pdo->prepare('UPDATE messages SET is_hidden = 1 WHERE id = :id');
                $stmt->execute([':id' => $messageId]);
                log_action($pdo, "Mesaj gizlendi: {$messageId}", $ipHash);
                $success = 'Mesaj gizlendi.';
            } elseif ($action === 'show') {
                $stmt = $pdo->prepare('UPDATE messages SET is_hidden = 0 WHERE id = :id');
                $stmt->execute([':id' => $messageId]);
                log_action($pdo, "Mesaj onaylandı: {$messageId}", $ipHash);
                $success = 'Mesaj onaylandı.';
            }
        }
    }
}

$pendingStmt = $pdo->prepare('SELECT id, content, created_at FROM messages WHERE is_hidden = 1 ORDER BY created_at DESC');
$pendingStmt->execute();
$pendingMessages = $pendingStmt->fetchAll();

$visibleStmt = $pdo->prepare('SELECT id, content, created_at, likes FROM messages WHERE is_hidden = 0 ORDER BY created_at DESC LIMIT 200');
$visibleStmt->execute();
$visibleMessages = $visibleStmt->fetchAll();

$statsStmt = $pdo->prepare('SELECT DATE(created_at) as day, COUNT(*) as total FROM messages GROUP BY day ORDER BY day DESC LIMIT 7');
$statsStmt->execute();
$stats = $statsStmt->fetchAll();

$adminUser = $_SESSION['admin_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <main class="container">
        <header>
            <h1>Admin Paneli</h1>
            <p>Mesaj yönetimi ve istatistikler.</p>
        </header>

        <?php if (!$adminUser): ?>
            <section class="form-card">
                <h2>Giriş</h2>
                <?php foreach ($errors as $error): ?>
                    <p class="status" style="color:#ff7b7b;"><?php echo e($error); ?></p>
                <?php endforeach; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" name="username" id="username" required>
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" required>
                    <button type="submit" name="login">Giriş</button>
                </form>
            </section>
        <?php else: ?>
            <section class="form-card">
                <div class="form-actions">
                    <strong>Merhaba, <?php echo e($adminUser); ?></strong>
                    <form method="post">
                        <button type="submit" name="logout">Çıkış</button>
                    </form>
                </div>
                <?php if ($success): ?>
                    <p class="status"><?php echo e($success); ?></p>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <p class="status" style="color:#ff7b7b;"><?php echo e($error); ?></p>
                <?php endforeach; ?>
            </section>

            <section class="form-card">
                <h2>Günlük Mesaj Sayısı (Son 7 Gün)</h2>
                <ul>
                    <?php foreach ($stats as $row): ?>
                        <li><?php echo e($row['day']); ?>: <?php echo e((string) $row['total']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="form-card">
                <h2>Onay Bekleyenler</h2>
                <?php if (empty($pendingMessages)): ?>
                    <p class="status">Bekleyen mesaj yok.</p>
                <?php else: ?>
                    <?php foreach ($pendingMessages as $msg): ?>
                        <div class="message">
                            <p><?php echo e($msg['content']); ?></p>
                            <div class="message-footer">
                                <span><?php echo e($msg['created_at']); ?></span>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                    <input type="hidden" name="message_id" value="<?php echo (int) $msg['id']; ?>">
                                    <button type="submit" name="action" value="show">Onayla</button>
                                    <button type="submit" name="action" value="delete">Sil</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="form-card">
                <h2>Yayındaki Mesajlar</h2>
                <?php foreach ($visibleMessages as $msg): ?>
                    <div class="message">
                        <p><?php echo e($msg['content']); ?></p>
                        <div class="message-footer">
                            <span><?php echo e($msg['created_at']); ?> • ❤ <?php echo e((string) $msg['likes']); ?></span>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                <input type="hidden" name="message_id" value="<?php echo (int) $msg['id']; ?>">
                                <button type="submit" name="action" value="hide">Gizle</button>
                                <button type="submit" name="action" value="delete">Sil</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
