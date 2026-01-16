<?php
session_start();

// If user is already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro Notes | Capture Your Thoughts</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/landing.css">
</head>
<body>

    <nav class="navbar">
        <a href="#" class="logo">
            <span style="font-size: 1.5rem;">📓</span> Pro Notes
        </a>
        <div class="nav-links">
            <a href="signin.php" class="btn btn-ghost">Sign In</a>
            <a href="signup.php" class="btn btn-primary">Get Started</a>
        </div>
    </nav>

    <header class="hero">
        <h1>Capture Your Ideas, <br> Anywhere.</h1>
        <p>The simplest way to keep notes, lists, and ideas. Fast, secure, and beautifully designed for focus.</p>
        <div class="hero-cta">
            <a href="signup.php" class="btn btn-primary">Start Taking Notes</a>
            <a href="signin.php" class="btn btn-ghost">Log In</a>
        </div>
    </header>

    <section class="preview-section">
        <div class="container">
            <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 10px;">Designed for Focus</h2>
            <p style="color: var(--text-muted);">A clutter-free interface that puts your content first.</p>
            <!-- Preview Image -->
            <img src="styles/app_preview.png" alt="App Screenshot" class="preview-img">
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="grid">
                <div class="feature-card">
                    <span class="icon">⚡</span>
                    <h3>Lightning Fast</h3>
                    <p>Instant load times and real-time saving. Never lose a thought again.</p>
                </div>
                <div class="feature-card">
                    <span class="icon">🔒</span>
                    <h3>Secure & Private</h3>
                    <p>Your notes are private by default. Active sessions and secure authentication.</p>
                </div>
                <div class="feature-card">
                    <span class="icon">✨</span>
                    <h3>Beautifully Simple</h3>
                    <p>No clutter. Just you and your thoughts in a distraction-free environment.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Pro Notes. All rights reserved.</p>
    </footer>

</body>
</html>
