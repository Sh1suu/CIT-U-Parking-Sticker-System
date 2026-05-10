<?php
/**
 * admin_delete.php
 * Deletes an Employee account from the database.
 *
 * Schema notes (dbcit_stickerapp):
 *   `user`     : user_id (PK), full_name, email, password, user_type
 *   `employee` : user_id (FK → user.user_id ON DELETE CASCADE)
 *
 *   Deleting from `user` automatically CASCADE-deletes the `employee` row.
 *   No need to delete from `employee` separately.
 *
 *   Safety checks:
 *     - ID must be a positive integer (ctype_digit)
 *     - Target must exist and have user_type = 'Employee'
 *     - Logged-in user cannot delete their own account
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

/* ── Validate ID ── */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: admin_list.php');
    exit();
}

$delete_id    = (int)$_GET['id'];
$logged_in_id = (int)$_SESSION['user_id'];   /* set by login.php as $_SESSION['user_id'] */

/* ── Prevent self-deletion ── */
if ($delete_id === $logged_in_id) {
    $_SESSION['flash_message'] = 'You cannot delete your own account.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

/* ── Confirm target exists and is an Employee ── */
$check = $connection->prepare(
    "SELECT full_name FROM `user`
      WHERE user_id = ? AND user_type = 'Employee'
      LIMIT 1"
);
$check->bind_param('i', $delete_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if (!$row) {
    $_SESSION['flash_message'] = 'Employee account not found.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

$deleted_name = $row['full_name'];

/* ── Execute delete (CASCADE removes employee row automatically) ── */
$stmt = $connection->prepare(
    "DELETE FROM `user` WHERE user_id = ? AND user_type = 'Employee'"
);
$stmt->bind_param('i', $delete_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    $_SESSION['flash_message'] = 'Account for "' . htmlspecialchars($deleted_name) . '" has been deleted.';
    $_SESSION['flash_type']    = 'success';
} else {
    $stmt->close();
    $_SESSION['flash_message'] = 'Could not delete the account. Please try again.';
    $_SESSION['flash_type']    = 'error';
}

header('Location: admin_list.php');
exit();