<?php
// signup.php (with clean error UI + field highlights)
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$fieldErrors = ['name' => '', 'email' => '', 'password' => '', 'sex' => ''];
$values = ['name' => '', 'email' => '', 'sex' => ''];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    $csrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $sex = (string) ($_POST['sex'] ?? '');

    $values['name'] = $name;
    $values['email'] = $email;
    $values['sex'] = $sex;

    // Validation
    if ($name === '' || mb_strlen($name) < 3) {
        $fieldErrors['name'] = 'Full Name must be at least 3 characters.';
        $errors[] = $fieldErrors['name'];
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Please enter a valid email address.';
        $errors[] = $fieldErrors['email'];
    }

    if (strlen($password) < 8) {
        $fieldErrors['password'] = 'Password must be at least 8 characters.';
        $errors[] = $fieldErrors['password'];
    }

    if (!in_array($sex, ['female', 'male'], true)) {
        $fieldErrors['sex'] = 'Please select Sex.';
        $errors[] = $fieldErrors['sex'];
    }

    if (!$errors) {
        try {
            // Unique email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $fieldErrors['email'] = 'Email is already registered.';
                $errors[] = $fieldErrors['email'];
            } else {
                // Auto-generate unique username
                $base = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name));
                $base = trim($base, '.');
                if ($base === '')
                    $base = 'user';

                $username = $base;
                $i = 0;
                while (true) {
                    $check = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
                    $check->execute([':u' => $username]);
                    if (!$check->fetch())
                        break;
                    $i++;
                    $username = $base . $i;
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare(
                    'INSERT INTO users (full_name, username, email, sex, password_hash, user_type, user_status)
                     VALUES (:full_name, :username, :email, :sex, :password_hash, :user_type, :user_status)'
                );

                $insert->execute([
                    ':full_name' => $name,
                    ':username' => $username,
                    ':email' => $email,
                    ':sex' => $sex,
                    ':password_hash' => $hash,
                    ':user_type' => 'user',
                    ':user_status' => 'active',
                ]);

                // Optional auto-login
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;

                // New CSRF token
                $_SESSION['csrf'] = bin2hex(random_bytes(32));

                header('Location: index.php'); // change if needed
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
    <title>Modern Signup</title>
    <link rel="stylesheet" href="styles/signup.css">
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
                    <h1>Create account</h1>
                    <p>Enter your details to get started.</p>
                </header>

                <?php if ($errors): ?>
                    <div class="alert" role="alert" aria-live="polite">
                        <svg class="alert__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 9v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            <path d="M10.3 4.2 2.6 18.2A2 2 0 0 0 4.3 21h15.4a2 2 0 0 0 1.7-2.8l-7.7-14a2 2 0 0 0-3.4 0Z"
                                stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
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

                <form method="post" action="signup.php" novalidate>
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                    <div class="input-row">
                        <div class="input-group">
                            <label for="name">Full Name</label>
                            <input class="<?= $fieldErrors['name'] ? 'input-error' : '' ?>" type="text" id="name"
                                name="name" placeholder="John Doe" value="<?= e($values['name']) ?>" required>
                            <?php if ($fieldErrors['name']): ?>
                                <div class="helper-text"><?= e($fieldErrors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input class="<?= $fieldErrors['email'] ? 'input-error' : '' ?>" type="email" id="email"
                            name="email" placeholder="name@company.com" value="<?= e($values['email']) ?>" required>
                        <?php if ($fieldErrors['email']): ?>
                            <div class="helper-text"><?= e($fieldErrors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input class="<?= $fieldErrors['password'] ? 'input-error' : '' ?>" type="password"
                            id="password" name="password" placeholder="••••••••" required>
                        <?php if ($fieldErrors['password']): ?>
                            <div class="helper-text"><?= e($fieldErrors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label>Sex</label>
                        <div class="radio-group"
                            style="<?= $fieldErrors['sex'] ? 'padding:10px;border-radius:12px;border:1px solid #fca5a5;background:#fff;' : '' ?>">
                            <label class="radio-item">
                                <input type="radio" name="sex" value="female" <?= $values['sex'] === 'female' ? 'checked' : '' ?>>
                                <span>Female</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="sex" value="male" <?= $values['sex'] === 'male' ? 'checked' : '' ?>>
                                <span>Male</span>
                            </label>
                        </div>
                        <?php if ($fieldErrors['sex']): ?>
                            <div class="helper-text"><?= e($fieldErrors['sex']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="submit-btn">Sign Up</button>
                </form>

                <footer>
                    <p>Already have an account? <a href="/notes_app/signin.php">Log in</a></p>
                </footer>
            </div>
        </div>
    </main>
</body>

</html>