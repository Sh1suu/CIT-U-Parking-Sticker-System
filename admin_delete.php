<?php
/**
 * admin_delete.php
 * Deletes an admin account from tbuser.
 * This is a GET-request handler (confirmation is done client-side via JS confirm()).
 * After deletion redirects to admin_list.php with a flash message.
 * Protected: admin session required.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 *
 * Security:
 *  - Requires admin session.
 *  - ID is cast to int to prevent injection.
 *  - Restricts DELETE to role = 'admin' rows only.
 *  - Prevents an admin from deleting their own account.
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

/* ── Validate ID ── */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: admin_list.php');
    exit();
}

$delete_id     = (int)$_GET['id'];
$logged_in_id  = (int)$_SESSION['username']; /* session stores user_id in 'username' key per login.php */

/* ── Prevent self-deletion ── */
if ($delete_id === $logged_in_id) {
    $_SESSION['flash_message'] = 'You cannot delete your own administrator account.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

/* ── Confirm the record exists and is an admin before deleting ── */
$check = $connection->prepare(
    "SELECT full_name FROM tbuser WHERE user_id = ? AND role = 'admin' LIMIT 1"
);
$check->bind_param('i', $delete_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if (!$row) {
    $_SESSION['flash_message'] = 'Admin account not found.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

$deleted_name = $row['full_name'];

/* ── Execute delete ── */
$stmt = $connection->prepare(
    "DELETE FROM tbuser WHERE user_id = ? AND role = 'admin'"
);
$stmt->bind_param('i', $delete_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    $_SESSION['flash_message'] = 'Admin account for "' . $deleted_name . '" has been deleted.';
    $_SESSION['flash_type']    = 'success';
} else {
    $stmt->close();
    $_SESSION['flash_message'] = 'Could not delete the account. Please try again.';
    $_SESSION['flash_type']    = 'error';
}

header('Location: admin_list.php');
exit();
