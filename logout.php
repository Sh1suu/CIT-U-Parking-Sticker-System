<?php
/**
 * logout.php
 * Destroys the current admin session and redirects to login.php.
 * A flash message is set before the session is destroyed so the
 * login page can display a "You have been logged out" confirmation.
 */

session_start();

/* ── Capture name before destroying session (for the goodbye message) ── */
$name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';

/* ── Destroy all session data ── */
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

/* ── Start a fresh session just to pass the flash message ── */
session_start();
$_SESSION['flash_message'] = $name
    ? 'You have been logged out successfully. See you soon, ' . htmlspecialchars($name) . '!'
    : 'You have been logged out successfully.';

header('Location: login.php');
exit();