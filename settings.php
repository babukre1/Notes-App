<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db.php';

$userId = (int) $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $profilePic = $user['profile_picture']; // Default to old one

    // Handle File Upload
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "uploads/avatars/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);

        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = "user_" . $userId . "_" . time() . "." . $fileExt;
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFilePath)) {
            $profilePic = $targetFilePath;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$fullName, $email, $profilePic, $userId])) {
            $success = "Profile updated successfully!";
            header("Refresh:1");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Settings | Pro Notes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            padding: 50px;
        }

        .settings-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
        }

        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #ff6b4a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .btn-save {
            background: #ff6b4a;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="settings-card">
        <h2 style="margin-top:0">Account Settings</h2>

        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p><?php endif; ?>

        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <div style="text-align: center;">
                <img src="<?= $user['profile_picture'] ?? 'default-avatar.png' ?>" class="profile-preview" alt="Avatar">
            </div>

            <div class="form-group">
                <label>Profile Picture</label>
                <input type="file" name="avatar" accept="image/*">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <button type="submit" class="btn-save">Update Profile</button>
        </form>

        <a href="index.php" class="back-link">← Back to Notes</a>
    </div>

</body>

</html>