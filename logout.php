<?php
/**
 * logout.php
 * Destroys the current session and redirects to login.php
 * with a goodbye flash message.
 */

session_start();

$name = $_SESSION['full_name'] ?? '';

/* Wipe session data */
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

/* New session just to carry the flash */
session_start();
$_SESSION['flash_message'] = $name
    ? 'You have been logged out. See you soon, ' . htmlspecialchars($name) . '!'
    : 'You have been logged out successfully.';

header('Location: login.php');
exit();