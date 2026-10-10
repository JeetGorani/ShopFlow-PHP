<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail_helper.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare(
            'SELECT id, name, email FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $createTable = "
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token_hash CHAR(64) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (user_id)
                )
            ";
            db()->exec($createTable);

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $stmt = db()->prepare(
                'DELETE FROM password_resets WHERE user_id = ?'
            );
            $stmt->execute([$user['id']]);

            $stmt = db()->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
            );
            $stmt->execute([$user['id'], $tokenHash]);

            $resetUrl = 'http://localhost/ShopFlow-PHP/reset_password.php?token='
                . urlencode($token);

            $html = '
                <h2>ShopFlow Password Reset</h2>
                <p>Hi ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Click the link below to reset your password. This link expires in 30 minutes.</p>
                <p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset Password</a></p>
                <p>If you did not request this, ignore this email.</p>
            ';

            sendShopFlowEmail(
                $user['email'],
                $user['name'],
                'Reset your ShopFlow password',
                $html
            );
        }
    }

    $message = 'If that email is registered, a password reset link will be sent shortly.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password | ShopFlow</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0; min-height: 100vh; display: grid; place-items: center;
    padding: 20px; font-family: Arial, sans-serif; color: #172033;
    background: linear-gradient(135deg, #eef2ff, #f8fafc 55%, #e0e7ff);
}
.card {
    width: 100%; max-width: 410px; padding: 32px;
    background: white; border: 1px solid #e5e7eb;
    border-radius: 20px; box-shadow: 0 20px 60px #17203314;
}
h1 { font-size: 27px; }
p { color: #64748b; line-height: 1.6; }
label { display: block; margin: 20px 0 8px; font-weight: 600; }
input {
    width: 100%; padding: 13px; border: 1px solid #cbd5e1;
    border-radius: 10px; font-size: 16px;
}
button {
    width: 100%; margin-top: 20px; padding: 14px;
    border: 0; border-radius: 10px; color: white;
    background: #4f46e5; font-size: 16px; font-weight: 700;
    cursor: pointer;
}
.message { padding: 12px; background: #eef2ff; border-radius: 8px; }
a { color: #4f46e5; }
</style>
</head>
<body>
<main class="card">
    <h1>Forgot password?</h1>
    <p>Enter your registered email address to request a reset link.</p>

    <?php if ($message !== ''): ?>
        <p class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email"
               autocomplete="email" required>
        <button type="submit">Send reset link</button>
    </form>

    <p><a href="login.php">← Back to login</a></p>
</main>
</body>
</html>
