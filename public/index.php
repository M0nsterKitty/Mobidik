<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../lib/csrf.php';

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anonim İtiraf</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <main class="container">
        <header>
            <h1>Anonim İtiraf</h1>
            <p>Kimliğinizi paylaşmadan güvenle mesaj bırakın.</p>
        </header>

        <section class="form-card">
            <label for="message">İtirafını yaz</label>
            <textarea id="message" minlength="20" maxlength="500" placeholder="20 ile 500 karakter arasında yazın..."></textarea>
            <div class="form-actions">
                <span id="char-count">0 / 500</span>
                <button id="submit-btn">Gönder</button>
            </div>
            <p id="status" class="status" role="alert"></p>
        </section>

        <section>
            <h2>Son İtiraflar</h2>
            <div id="messages" class="messages"></div>
        </section>
    </main>

    <script src="/assets/app.js" defer></script>
</body>
</html>
