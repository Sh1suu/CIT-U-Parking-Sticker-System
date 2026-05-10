<?php
/**
 * auth_guard.php
 * Include this at the very top of every admin-protected page.
 * Starts the session and redirects unauthenticated or non-admin visitors.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.php');
    exit();
}
