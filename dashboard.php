<?php
// index.php (DB-backed Notes App - keeps your existing UI/UX)
// Requires: db.php (PDO) + auth_guard.php (session login required)

declare(strict_types=1);

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: signin.php');
    exit;
}

// CSRF
if (empty($_SESSION['csrf']))
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf'];

$errors = [];
$activeId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$openNew = isset($_GET['new']);

// Small helper: fetch note for this user only
function fetchNote(PDO $pdo, int $userId, int $noteId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM notes WHERE id = :id AND user_id = :uid LIMIT 1');
    $stmt->execute([':id' => $noteId, ':uid' => $userId]);
    $n = $stmt->fetch(PDO::FETCH_ASSOC);
    return $n ?: null;
}

// Handle POST: save note (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $csrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $isFavourite = ((string) ($_POST['is_favourite'] ?? '0') === '1') ? 1 : 0;

        if ($title === '')
            $title = 'Untitled Note';

        try {
            if ($id > 0) {
                // Update existing (only if belongs to user)
                $existing = fetchNote($pdo, $userId, $id);
                if (!$existing) {
                    $errors[] = 'Note not found or access denied.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE notes
                         SET title = :title, content = :content, is_favourite = :fav
                         WHERE id = :id AND user_id = :uid'
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':content' => $content,
                        ':fav' => $isFavourite,
                        ':id' => $id,
                        ':uid' => $userId
                    ]);
                    // rotate CSRF after successful write
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header("Location: dashboard.php?id={$id}");
                    exit;
                }
            } else {
                // Insert new
                $stmt = $pdo->prepare(
                    'INSERT INTO notes (user_id, title, content, is_favourite)
                     VALUES (:uid, :title, :content, :fav)'
                );
                $stmt->execute([
                    ':uid' => $userId,
                    ':title' => $title,
                    ':content' => $content,
                    ':fav' => $isFavourite
                ]);
                $newId = (int) $pdo->lastInsertId();
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header("Location: dashboard.php?id={$newId}");
                exit;
            }
        } catch (Throwable $t) {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

// Handle GET: toggle favourite (star)
if (isset($_GET['toggle_star'])) {
    $noteId = (int) $_GET['toggle_star'];

    $csrf = (string) ($_GET['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        try {
            $note = fetchNote($pdo, $userId, $noteId);
            if (!$note) {
                $errors[] = 'Note not found or access denied.';
            } else {
                $newFav = ((int) $note['is_favourite'] === 1) ? 0 : 1;
                $stmt = $pdo->prepare(
                    'UPDATE notes SET is_favourite = :fav WHERE id = :id AND user_id = :uid'
                );
                $stmt->execute([':fav' => $newFav, ':id' => $noteId, ':uid' => $userId]);

                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header("Location: dashboard.php?id={$noteId}");
                exit;
            }
        } catch (Throwable $t) {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

// Handle GET: delete
if (isset($_GET['delete'])) {
    $noteId = (int) $_GET['delete'];

    $csrf = (string) ($_GET['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM notes WHERE id = :id AND user_id = :uid');
            $stmt->execute([':id' => $noteId, ':uid' => $userId]);

            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: dashboard.php');
            exit;
        } catch (Throwable $t) {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
// 4. Fetch Notes list (Check for filter)
$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT id, title, content, is_favourite, created_at 
        FROM notes 
        WHERE user_id = :uid';

if ($filter === 'starred') {
    $sql .= ' AND is_favourite = 1';
}

$sql .= ' ORDER BY updated_at DESC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userId]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Active note
$activeNote = null;
if ($activeId) {
    $activeNote = fetchNote($pdo, $userId, $activeId);
    if (!$activeNote)
        $activeId = null; // if invalid id
}

// New: Determine which view to show
$view = $_GET['view'] ?? 'notes';
$filter = $_GET['filter'] ?? 'all';

// Fetch current user data for the Settings view
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

// ✅ Define once for all page renders (GET/POST)
$DEFAULT_AVATAR = 'default-avatar.png';
$profilePic = trim((string) ($currentUser['profile_picture'] ?? ''));

$profilePicUrl = ($profilePic !== '' && file_exists(__DIR__ . '/' . $profilePic))
    ? $profilePic
    : $DEFAULT_AVATAR;

// For editor initial state (new note)
if ($openNew && !$activeId) {
    $activeNote = [
        'id' => 0,
        'title' => '',
        'content' => '',
        'is_favourite' => 0
    ];
}

// Handle POST: update profile (New combined logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $csrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $sex = (string) ($_POST['sex'] ?? '');

        // Basic validation
        if ($fullName === '' || mb_strlen($fullName) < 3)
            $errors[] = 'Full name must be at least 3 characters.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Enter a valid email.';
        if ($username === '' || mb_strlen($username) < 3)
            $errors[] = 'Username must be at least 3 characters.';
        if ($sex !== '' && !in_array($sex, ['female', 'male'], true))
            $errors[] = 'Sex must be Female or Male.';


        // If image selected, validate and upload
        if (!empty($_FILES['avatar']['name'])) {
            if (!is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                $errors[] = 'Upload failed. Please try again.';
            } else {
                $maxSize = 2 * 1024 * 1024; // 2MB
                if (($_FILES['avatar']['size'] ?? 0) > $maxSize)
                    $errors[] = 'Profile picture must be <= 2MB.';

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['avatar']['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime]))
                    $errors[] = 'Only JPG, PNG, or WEBP allowed.';

                if (!$errors) {
                    $targetDir = __DIR__ . "/uploads/avatars/";
                    if (!is_dir($targetDir))
                        mkdir($targetDir, 0777, true);

                    $ext = $allowed[$mime];
                    $fileName = "user_" . $userId . "_" . time() . "." . $ext;
                    $absPath = $targetDir . $fileName;
                    $relPath = "uploads/avatars/" . $fileName;

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $absPath)) {
                        $profilePic = $relPath;

                        // Optional: delete old file (only if it was in uploads/avatars/)
                        if (!empty($currentUser['profile_picture']) && str_starts_with((string) $currentUser['profile_picture'], 'uploads/avatars/')) {
                            $oldAbs = __DIR__ . '/' . $currentUser['profile_picture'];
                            if (is_file($oldAbs))
                                @unlink($oldAbs);
                        }
                    } else {
                        $errors[] = 'Could not save image file.';
                    }
                }
            }
        }

        // Uniqueness checks (email + username)
        if (!$errors) {
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $st->execute([$email, $userId]);
            if ($st->fetch())
                $errors[] = 'Email is already used by another account.';

            $st = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $st->execute([$username, $userId]);
            if ($st->fetch())
                $errors[] = 'Username is already taken.';
        }

        // Update
        if (!$errors) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET full_name = ?, email = ?, username = ?, phone = ?, sex = ?, profile_picture = ?
                     WHERE id = ?"
                );
                $stmt->execute([$fullName, $email, $username, ($phone === '' ? null : $phone), $sex, ($profilePic === '' ? null : $profilePic), $userId]);

                // Refresh session display name/email (optional)
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;

                // Rotate CSRF after successful update
                $_SESSION['csrf'] = bin2hex(random_bytes(32));

                header("Location: dashboard.php?view=settings&success=1");
                exit;
            } catch (Throwable $t) {
                $errors[] = 'Could not update profile.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pro Notes | Web</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e1e26;
            --list-bg: #ffffff;
            --editor-bg: #ffffff;
            --accent: #ff6b4a;
            --border: #e0e0e0;
            --text-main: #202124;
            --text-muted: #5f6368;
            --star-color: #ffb400;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            background: #fff;
        }

        .wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        .nav-sidebar {
            width: 70px;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 25px 0;
            flex-shrink: 0;
        }

        .new-btn {
            width: 45px;
            height: 45px;
            background: var(--accent);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 24px;
            margin-bottom: 30px;
            transition: transform 0.2s;
        }

        .new-btn:hover {
            transform: scale(1.05);
        }

        .nav-icon {
            margin-bottom: 25px;
            font-size: 20px;
            color: #888;
            text-decoration: none;
            cursor: pointer;
        }

        .nav-icon.active {
            color: white;
        }

        .list-pane {
            width: 350px;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            background: var(--list-bg);
        }

        .list-header {
            padding: 30px 24px 20px;
        }

        .list-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .note-stats {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .scroll-area {
            flex: 1;
            overflow-y: auto;
        }

        .note-card {
            padding: 20px 24px;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: background 0.2s;
        }

        .note-card:hover {
            background: #fcfcfc;
        }

        .note-card.active {
            background: #fff8f6;
            border-right: 4px solid var(--accent);
        }

        .card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .card-title {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .star-indicator {
            color: var(--star-color);
            font-size: 1rem;
        }

        .card-preview {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 4px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-date {
            font-size: 0.75rem;
            color: #aaa;
            margin-top: 8px;
            display: block;
        }

        .editor-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--editor-bg);
            overflow-y: auto;
            transition: all 0.3s ease;
            /* Smooth transition when list disappears */
        }

        .editor-toolbar {
            padding: 15px 40px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .editor-container {
            padding: 40px 60px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .input-title {
            width: 100%;
            border: none;
            outline: none;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            font-family: inherit;
            color: var(--text-main);
        }

        .editor-textarea {
            width: 100%;
            flex: 1;
            border: none;
            outline: none;
            font-size: 1.15rem;
            line-height: 1.8;
            color: #3c4043;
            font-family: inherit;
            resize: none;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-family: inherit;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-save {
            background: var(--accent);
            color: white;
        }

        .btn-star {
            background: #f1f3f4;
            color: var(--text-muted);
            margin-right: 10px;
        }

        .btn-star.active {
            background: #fff8e1;
            color: var(--star-color);
        }

        .btn-delete {
            color: #ea4335;
            background: transparent;
            font-weight: 500;
        }

        .empty-view {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #bbb;
            text-align: center;
        }

        /* Optional: clean error box */
        .alert {
            margin: 14px 40px 0;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #7f1d1d;
            font-size: 14px;
        }

        .alert ul {
            margin: 0;
            padding-left: 18px;
        }

        .settings-container {
            padding: 40px 60px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
            /* Ensure the container doesn't collapse and hides button */
            display: block;
            padding-bottom: 100px;
            /* Extra space at bottom so button isn't cramped */
        }

        .profile-img-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid var(--accent);
        }

        .settings-group {
            margin-bottom: 20px;
        }

        .settings-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .settings-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
        }

        .sidebar-user-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: auto;
            border: 2px solid #444;
            cursor: pointer;
        }

        .logout-btn {
            transition: all 0.2s ease-in-out;
            padding: 10px 0;
            border-radius: 8px;
            /* Rounds the hover background */
        }

        .logout-btn:hover {
            background-color: rgba(255, 77, 77, 0.1);
            /* Very subtle red tint background */
            transform: scale(1.05);
            /* Slightly pops out */
        }

        .logout-btn:hover span {
            color: #ff8080 !important;
            text-shadow: 0 0 10px rgba(255, 92, 92, 0.5);
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <nav class="nav-sidebar" style="display: flex; flex-direction: column; height: 100%; padding: 20px 0;">
            <a href="dashboard.php?view=settings" style="margin-bottom: 20px; text-align: center; text-decoration: none;">
                <img src="<?= e($profilePicUrl) ?>" class="sidebar-user-pic"
                    style="width: 45px; height: 45px; border: 2px solid var(--accent);" title="Settings">
                <div style="color: white; font-size: 14px; margin-top: 5px; opacity: 0.8;">
                    <?= e(explode(' ', $currentUser['username'])[0]) ?>
                </div>
            </a>

            <a href="dashboard.php?new=true" class="new-btn">+</a>

            <a href="dashboard.php" class="nav-icon <?= $filter === 'all' && $view !== 'settings' ? 'active' : '' ?>"
                title="All Notes">📓</a>
            <a href="dashboard.php?filter=starred" class="nav-icon <?= $filter === 'starred' ? 'active' : '' ?>"
                title="Starred">⭐</a>
            <a href="dashboard.php?view=settings" class="nav-icon <?= $view === 'settings' ? 'active' : '' ?>"
                title="Settings">⚙️</a>

            <a href="logout.php" class="nav-icon logout-btn" title="Logout"
                style="margin-top: auto; margin-bottom: 20px; text-decoration: none; display: flex; flex-direction: column; align-items: center; width: 100%; box-sizing: border-box;">

                <span style="font-size: 28px; margin-bottom: 4px; display: block;">
                    <i class="fas fa-sign-out-alt"></i> <?php /* OR Use the Door Emoji as seen in your sidebar: */ ?> 🚪
                </span>

                <span
                    style="font-size: 11px; color: #ff5c5c; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; width: 100%; text-align: center;">
                    LOGOUT
                </span>
            </a>
        </nav>

        <?php if ($view !== 'settings'): ?>
            <div class="list-pane">
                <div class="list-header">
                    <h2>Notes</h2>
                    <div class="note-stats">
                        <?php echo count($notes); ?> notes collected
                    </div>
                </div>
                <div class="scroll-area">
                    <?php foreach ($notes as $note): ?>
                        <a href="?id=<?php echo (int) $note['id']; ?>"
                            class="note-card <?php echo ($activeId == (int) $note['id']) ? 'active' : ''; ?>">
                            <div class="card-title-row">
                                <span class="card-title">
                                    <?php echo e($note['title'] ?? 'Untitled'); ?>
                                </span>
                                <?php if (!empty($note['is_favourite'])): ?>
                                    <span class="star-indicator">★</span>
                                <?php endif; ?>
                            </div>
                            <span class="card-preview">
                                <?php echo e(mb_substr(trim(strip_tags((string) ($note['content'] ?? ''))), 0, 50)); ?>...
                            </span>
                            <span class="card-date">
                                <?php echo e(date('j M Y', strtotime((string) $note['created_at']))); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="editor-pane">

            <?php if ($view === 'settings'): ?>
                <div class="settings-container">
                    <?php if (!empty($_GET['success'])): ?>
                        <div class="alert" style="border-color:#bbf7d0;background:#f0fdf4;color:#14532d;">
                            Profile updated successfully.
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert">
                            <ul>
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="dashboard.php?view=settings" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                        <h1 style="margin-bottom: 30px;">Profile Settings</h1>

                        <div style="text-align:center; margin-bottom: 30px;">
                            <img id="profile-preview" src="<?= e($profilePicUrl) ?>" class="profile-img-large"
                                alt="Profile">

                            <label style="display:block; margin-top:10px; cursor:pointer; color:var(--accent);">
                                Change Photo
                                <input type="file" name="avatar" id="avatar-input" style="display:none;"
                                    onchange="previewImage(this)">
                            </label>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">JPG/PNG/WEBP, max 2MB</div>
                        </div>

                        <div class="settings-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="settings-input"
                                value="<?= e($currentUser['full_name'] ?? '') ?>" required>
                        </div>

                        <div class="settings-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="settings-input"
                                value="<?= e($currentUser['email'] ?? '') ?>" required>
                        </div>

                        <div class="settings-group">
                            <label>Username</label>
                            <input type="text" name="username" class="settings-input"
                                value="<?= e($currentUser['username'] ?? '') ?>" required>
                        </div>

                        <div class="settings-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="settings-input"
                                value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="+252...">
                        </div>

                        <div class="settings-group">
                            <label>Sex</label>
                            <select name="sex" class="settings-input" required>
                                <option value="female" <?= (($currentUser['sex'] ?? '') === 'female') ? 'selected' : '' ?>>
                                    Female</option>
                                <option value="male" <?= (($currentUser['sex'] ?? '') === 'male') ? 'selected' : '' ?>>Male
                                </option>
                            </select>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-save" style="width:100%;">Update
                            Account</button>
                        <div
                            style="margin-top: 30px; text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
                            <a href="logout.php"
                                style="color: #ea4335; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                Log out of account
                            </a>
                        </div>
                    </form>
                </div>

            <?php elseif ($activeNote): ?>
                <form action="dashboard.php" method="POST" style="height: 100%; display: flex; flex-direction: column;">
                    <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int) $activeNote['id'] ?>">
                    <input type="hidden" name="is_favourite" id="fav_input"
                        value="<?= (int) $activeNote['is_favourite'] ?>">

                    <div class="editor-toolbar">
                        <div class="toolbar-left">
                            <a href="?toggle_star=<?= (int) $activeNote['id'] ?>&csrf=<?= e($csrfToken) ?>"
                                class="btn btn-star <?= $activeNote['is_favourite'] ? 'active' : '' ?>" title="Star Note">
                                <?= $activeNote['is_favourite'] ? '★ Starred' : '☆ Star' ?>
                            </a>
                            <?php if ($activeNote['id'] > 0): ?>
                                <a href="?delete=<?= (int) $activeNote['id'] ?>&csrf=<?= e($csrfToken) ?>"
                                    class="btn btn-delete" onclick="return confirm('Delete this note?')">Delete</a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="save_note" class="btn btn-save">Save Changes</button>
                    </div>

                    <div class="editor-container">
                        <input type="text" name="title" class="input-title" placeholder="Note Title"
                            value="<?= e($activeNote['title']) ?>" autofocus>
                        <textarea name="content" class="editor-textarea"
                            placeholder="Start writing..."><?= e($activeNote['content']) ?></textarea>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-view">
                    <div style="font-size: 4rem; margin-bottom: 10px;">📝</div>
                    <h3>Select a note to view or edit</h3>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    // This updates the 'src' of your image immediately
                    document.getElementById('profile-preview').src = e.target.result;
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>