<?php
// auth_guard.php (include this at the top of any protected page, e.g., index.php)
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}