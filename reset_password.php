<?php
session_start();
require_once __DIR__ . '/config.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

if (
    !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)
) {
    $error = 'This reset link is invalid or expired.';
} else {
    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT id, user_id FROM password_resets
         WHERE token_hash = ? AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = 'This reset link is invalid or expired.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = db();
            $pdo->beginTransaction();

            try {
                // Re-check and lock the token to prevent reuse.
                $check = $pdo->prepare(
                    'SELECT id, user_id FROM password_resets
                     WHERE id = ? AND token_hash = ? AND expires_at > NOW()
                     FOR UPDATE'
                );
                $check->execute([$reset['id'], $tokenHash]);
                $validReset = $check->fetch();

                if (!$validReset) {
                    throw new RuntimeException('This reset link is invalid or expired.');
                }

                $update = $pdo->prepare(
                    'UPDATE users SET password_hash = ? WHERE id = ?'
                );
                $update->execute([
                    password_hash($password, PASSWORD_DEFAULT),
                    $validReset['user_id']
                ]);

                $delete = $pdo->prepare(
                    'DELETE FROM password_resets WHERE user_id = ?'
                );
                $delete->execute([$validReset['user_id']]);

                $pdo->commit();

                $_SESSION = [];
                session_regenerate_id(true);

                $success = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    error_log('Password reset failed: ' . $e->getMessage());
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password | ShopFlow</title>
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
    width: 100%; margin-top: 22px; padding: 14px;
    border: 0; border-radius: 10px; color: white;
    background: #4f46e5; font-size: 16px; font-weight: 700;
    cursor: pointer;
}
.error { color: #b91c1c; }
a { color: #4f46e5; }
</style>
</head>
<body>
<main class="card">
    <h1>Set new password</h1>
    <p>Choose a new password for your ShopFlow account.</p>

    <?php if ($success): ?>
        <p>Your password has been reset successfully.</p>
        <p><a href="login.php">Sign in with your new password</a></p>
    <?php else: ?>
        <?php if ($error !== ''): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== 'This reset link is invalid or expired.'): ?>
            <form method="post">
                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <label for="password">New password</label>
                <input id="password" type="password" name="password"
                       minlength="8" autocomplete="new-password" required>

                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" type="password"
                       name="confirm_password" minlength="8"
                       autocomplete="new-password" required>

                <button type="submit">Update password</button>
            </form>
        <?php endif; ?>

        <p><a href="login.php">← Back to login</a></p>
    <?php endif; ?>
</main>
</body>
</html>
