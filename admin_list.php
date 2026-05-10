<?php
/**
 * admin_list.php
 * Displays all Employee users with their employee profile details.
 *
 * Schema notes (dbcit_stickerapp):
 *   `user`     : user_id (PK), full_name, email, password, user_type ENUM('Student','Employee')
 *   `employee` : user_id (FK → user.user_id CASCADE), employee_id, department
 *
 *   We JOIN user + employee to show: full_name, email, employee_id, department.
 *   Left join ensures users without an employee row still appear (data integrity
 *   gap — should not happen after registration, but handled gracefully).
 *
 *   Search filters on: full_name, email, department (all from user/employee).
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Employee Users';

/* ── Flash message ─────────────────────────────────────────── */
$flash      = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash      = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

/* ── Search ────────────────────────────────────────────────── */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$like   = '%' . $search . '%';

/*
 * SELECT:
 *   u.user_id, u.full_name, u.email     ← user table
 *   e.employee_id, e.department          ← employee table
 * WHERE:
 *   u.user_type = 'Employee'             ← only employee accounts
 *   AND search filter on name/email/dept
 */
$stmt = $connection->prepare(
    "SELECT u.user_id, u.full_name, u.email,
            e.employee_id, e.department
       FROM `user` u
       LEFT JOIN `employee` e ON e.user_id = u.user_id
      WHERE u.user_type = 'Employee'
        AND (u.full_name LIKE ? OR u.email LIKE ? OR e.department LIKE ?)
      ORDER BY u.user_id DESC"
);
$stmt->bind_param('sss', $like, $like, $like);
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
                Employee User Management
            </h2>
            <p style="color:#777; font-size:0.85rem; margin:4px 0 0;">
                Manage employee accounts for the Parking Management System
            </p>
        </div>
        <a href="admin_create.php" class="btn-cit-primary" style="text-decoration:none; padding:11px 28px;">
            + Add New Employee
        </a>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="flash-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <h4>All Employees</h4>
            <form method="GET" action="admin_list.php" class="search-wrap" style="max-width:280px;">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search"
                    class="form-control-cit"
                    placeholder="Search name, email, dept…"
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="background:#fff; border-color:#fff; padding-left:36px;"
                    onchange="this.form.submit()">
            </form>
        </div>

        <div class="admin-card-body" style="padding:0;">
            <?php if ($result->num_rows === 0): ?>
                <div class="text-center py-5" style="color:#aaa;">
                    <?php if ($search): ?>
                        No employees matched &ldquo;<strong><?php echo htmlspecialchars($search); ?></strong>&rdquo;.
                        <a href="admin_list.php" style="color:#800000;">Clear search</a>
                    <?php else: ?>
                        No employee accounts found.
                        <a href="admin_create.php" style="color:#800000;">Create one now.</a>
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
                                <th>Type</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $n = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color:#bbb; font-size:0.78rem;"><?php echo $n++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php echo $row['department']
                                        ? htmlspecialchars($row['department'])
                                        : '<span style="color:#ccc;">—</span>'; ?>
                                </td>
                                <td>
                                    <?php echo $row['employee_id']
                                        ? htmlspecialchars($row['employee_id'])
                                        : '<span style="color:#ccc;">—</span>'; ?>
                                </td>
                                <td><span class="role-badge role-employee">Employee</span></td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="admin_edit.php?id=<?php echo (int)$row['user_id']; ?>"
                                       class="btn-cit-edit" style="margin-right:6px;">Edit</a>
                                    <a href="admin_delete.php?id=<?php echo (int)$row['user_id']; ?>"
                                       class="btn-cit-danger"
                                       onclick="return confirm('Delete this employee account? This cannot be undone.');">
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
    </div>

</div>

<?php require_once 'includes/admin_footer.php'; ?>