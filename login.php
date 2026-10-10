<?php
session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== '') {
        $stmt = db()->prepare(
            'SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

       

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];

    // Claim the first welcome email for this user.
    $claim = db()->prepare(
        'UPDATE users
         SET welcome_email_sent = 1
         WHERE id = ? AND welcome_email_sent = 0'
    );
    $claim->execute([$user['id']]);

    // Send only when this login claimed the first email.
    if ($claim->rowCount() === 1) {
        require_once __DIR__ . '/mail_helper.php';

        $safeName = htmlspecialchars(
            $user['name'],
            ENT_QUOTES,
            'UTF-8'
        );

        $welcomeHtml = '
            <div style="font-family:Arial,sans-serif;line-height:1.6;color:#172033">
                <h2 style="color:#4f46e5">Welcome to ShopFlow!</h2>
                <p>Hi ' . $safeName . ',</p>
                <p>You have successfully signed in to your ShopFlow account.</p>
                <p>You can now manage your clothing inventory from your dashboard.</p>
                <p>Thanks for using ShopFlow!</p>
                <p style="color:#64748b">— Team ShopFlow</p>
            </div>
        ';

        sendShopFlowEmail(
            $user['email'],
            $user['name'],
            'Welcome to ShopFlow!',
            $welcomeHtml
        );
    }

    header('Location: index.php');
    exit;
}


    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | ShopFlow</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: #172033;
            background: linear-gradient(135deg, #eef2ff, #f8fafc 55%, #e0e7ff);
        }

        .card {
            width: 100%;
            max-width: 410px;
            padding: 36px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            box-shadow: 0 20px 60px #17203314;
        }

        .brand {
            font-size: 26px;
            font-weight: 800;
            color: #4f46e5;
        }

        h1 {
            margin-bottom: 8px;
            font-size: 28px;
        }

        .muted {
            color: #64748b;
            line-height: 1.5;
        }

        label {
            display: block;
            margin: 20px 0 8px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 16px;
        }

        input:focus {
            outline: 2px solid #818cf8;
            border-color: #4f46e5;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            color: white;
            background: #4f46e5;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #4338ca;
        }

        .error {
            padding: 12px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="brand">ShopFlow</div>
        <h1>Welcome back</h1>
        <p class="muted">Sign in to manage your clothing inventory.</p>

        <?php if ($error !== ''): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" autocomplete="username" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>

            <p style="text-align:right; margin-top:12px;">
                <a href="forgot_password.php" style="color:#4f46e5; text-decoration:none; font-size:14px;">
                    Forgot password?
                </a>
            </p>

            <button type="submit">Sign in</button>
        </form>
    </main>
</body>

</html>