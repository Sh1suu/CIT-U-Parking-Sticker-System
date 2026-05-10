<?php
/**
 * auth_guard.php
 * Include at the top of every protected admin page.
 * Starts the session if not already started, then redirects
 * unauthenticated visitors back to login.php.
 *
 * Session keys set by login.php:
 *   $_SESSION['user_id']   — user.user_id (int)
 *   $_SESSION['full_name'] — user.full_name (string)
 *   $_SESSION['user_type'] — 'Employee' (the only role allowed to log in here)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Employee') {
    header('Location: login.php');
    exit();
}