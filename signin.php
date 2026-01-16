<?php
// signin.php (fixed validation + clean error box)
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$fieldErrors = ['email' => '', 'password' => ''];
$values = ['email' => ''];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $values['email'] = $email;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Enter a valid email.';
        $errors[] = 'Please enter a valid email address.';
    }

    if (trim($password) === '') {
        $fieldErrors['password'] = 'Password is required.';
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, email, password_hash, user_status, user_type
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
            } elseif (($user['user_status'] ?? 'active') !== 'active') {
                $errors[] = 'Your account is not active. Please contact admin.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_email'] = (string)$user['email'];
                $_SESSION['user_name'] = (string)$user['full_name'];
                $_SESSION['user_type'] = (string)$user['user_type'];

                $_SESSION['csrf'] = bin2hex(random_bytes(32));

                header('Location: dashboard.php');
                exit;
            }
        } catch (Throwable $t) {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Sign In</title>
    <link rel="stylesheet" href="styles/signin.css">
</head>
<body>
<main class="wrapper">
    <div class="signup-card">
        <div class="brand-section">
            <div class="brand-content">
                <h2>Design your future.</h2>
                <p>Join over 10,000 professionals building better interfaces together.</p>
            </div>
        </div>

        <div class="form-section">
            <header>
                <h1>Sign In</h1>
                <p>Welcome Back to your Favourite Notes App.</p>
            </header>

            <?php if ($errors): ?>
                <div class="alert" role="alert" aria-live="polite">
                    <svg class="alert__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        <path d="M10.3 4.2 2.6 18.2A2 2 0 0 0 4.3 21h15.4a2 2 0 0 0 1.7-2.8l-7.7-14a2 2 0 0 0-3.4 0Z"
                              stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <div>
                        <div class="alert__title">Please fix the following</div>
                        <ul class="alert__list">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="signin.php" novalidate>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input
                        class="<?= $fieldErrors['email'] ? 'input-error' : '' ?>"
                        type="email"
                        id="email"
                        name="email"
                        placeholder="name@company.com"
                        value="<?= e($values['email']) ?>"
                        required
                    >
                    <?php if ($fieldErrors['email']): ?>
                        <div class="helper-text"><?= e($fieldErrors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input
                        class="<?= $fieldErrors['password'] ? 'input-error' : '' ?>"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                    <?php if ($fieldErrors['password']): ?>
                        <div class="helper-text"><?= e($fieldErrors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="submit-btn">Sign In</button>
            </form>

            <footer>
                <p>Not Registered Yet? <a href="/notes_app/signup.php">Signup</a></p>
            </footer>
        </div>
    </div>
</main>
</body>
</html>
