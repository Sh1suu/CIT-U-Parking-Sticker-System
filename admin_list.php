<?php
/**
 * admin_list.php
 * Displays all users with role = 'admin'.
 * Supports live search by name, email, or department.
 * Protected: admin session required.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Admin Users';

/* ── Flash message from redirect ─────────────────────────────────── */
$flash        = '';
$flash_type   = '';
if (isset($_SESSION['flash_message'])) {
    $flash      = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

/* ── Search / filter ─────────────────────────────────────────────── */
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_esc = '%' . $search . '%';

/* ── Fetch admin users (prepared statement) ──────────────────────── */
$stmt = $connection->prepare(
    "SELECT user_id, full_name, email, department, student_or_employee_id
     FROM   tbuser
     WHERE  role = 'admin'
       AND  (full_name LIKE ? OR email LIKE ? OR department LIKE ?)
     ORDER BY user_id DESC"
);
$stmt->bind_param('sss', $search_esc, $search_esc, $search_esc);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

require_once 'includes/admin_header.php';
?>

<div class="container mt-4 pb-5">

    <!-- Page heading -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap:12px;">
        <div>
            <h2 style="color:#800000; font-family:'EB Garamond',serif; margin:0; font-size:1.8rem;">
                Admin User Management
            </h2>
            <p style="color:#777; font-size:0.85rem; margin:4px 0 0;">
                Manage administrator accounts for the Parking Management System
            </p>
        </div>
        <a href="admin_create.php" class="btn-cit-primary" style="text-decoration:none; padding:11px 28px;">
            + Add New Admin
        </a>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="flash-<?php echo $flash_type; ?>">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <!-- Admin card -->
    <div class="admin-card">
        <div class="admin-card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <h4>All Administrators</h4>
            <!-- Search -->
            <form method="GET" action="admin_list.php" class="search-wrap" style="max-width:280px;">
                <span class="search-icon">&#128269;</span>
                <input
                    type="text"
                    name="search"
                    class="form-control-cit"
                    placeholder="Search by name, email, dept…"
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="background:#fff; border-color:#fff; border-radius:8px; padding-left:36px;"
                    onchange="this.form.submit()"
                >
            </form>
        </div>

        <div class="admin-card-body" style="padding:0;">
            <?php if ($result->num_rows === 0): ?>
                <div class="text-center py-5" style="color:#aaa;">
                    <?php if ($search): ?>
                        No administrators matched "<strong><?php echo htmlspecialchars($search); ?></strong>".
                        <a href="admin_list.php" style="color:#800000;">Clear search</a>
                    <?php else: ?>
                        No admin accounts found. <a href="admin_create.php" style="color:#800000;">Create one now.</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="cit-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Employee ID</th>
                                <th>Role</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color:#bbb; font-size:0.78rem;"><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['department'] ? htmlspecialchars($row['department']) : '<span style="color:#ccc;">—</span>'; ?></td>
                                <td><?php echo $row['student_or_employee_id'] ? htmlspecialchars($row['student_or_employee_id']) : '<span style="color:#ccc;">—</span>'; ?></td>
                                <td><span class="role-badge role-admin">Admin</span></td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="admin_edit.php?id=<?php echo (int)$row['user_id']; ?>"
                                       class="btn-cit-edit"
                                       style="margin-right:6px;">
                                        Edit
                                    </a>
                                    <a href="admin_delete.php?id=<?php echo (int)$row['user_id']; ?>"
                                       class="btn-cit-danger"
                                       onclick="return confirm('Delete this admin account? This cannot be undone.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div><!-- /.admin-card -->

</div><!-- /.container -->

<?php require_once 'includes/admin_footer.php'; ?>
