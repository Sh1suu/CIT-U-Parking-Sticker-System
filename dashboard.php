<?php
/**
 * dashboard.php
 * CIT University Parking Management System — Admin Dashboard
 *
 * Schema notes (dbcit_stickerapp):
 *   `user`               : user_id, full_name, email, password, user_type ENUM('Student','Employee')
 *   `vehicle`            : vehicle_id, user_id (FK), plate_number, vehicle_type, brand, color, registration_date
 *   `sticker_application`: application_id, vehicle_id (FK), application_date, status, remarks
 *   `parking_sticker`    : sticker_id, vehicle_id (FK), issue_date, expiry_date, status
 *
 *   Stat counts:
 *     Total users      → COUNT(*) FROM `user`
 *     Total employees  → COUNT(*) FROM `user` WHERE user_type = 'Employee'
 *     Total students   → COUNT(*) FROM `user` WHERE user_type = 'Student'
 *     Total vehicles   → COUNT(*) FROM `vehicle`
 *     Pending apps     → COUNT(*) FROM `sticker_application` WHERE status = 'Pending'
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Dashboard';

/* ── Flash message ── */
$flash      = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash      = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

/* ── Statistics ── */

/* Total users (all types) */
$r           = $connection->query("SELECT COUNT(*) AS c FROM `user`");
$total_users = (int)$r->fetch_assoc()['c'];

/* Employees (admin-panel users) */
$r              = $connection->query("SELECT COUNT(*) AS c FROM `user` WHERE user_type = 'Employee'");
$total_employees = (int)$r->fetch_assoc()['c'];

/* Students */
$r               = $connection->query("SELECT COUNT(*) AS c FROM `user` WHERE user_type = 'Student'");
$total_students  = (int)$r->fetch_assoc()['c'];

/* Vehicles */
$r               = $connection->query("SELECT COUNT(*) AS c FROM `vehicle`");
$total_vehicles  = (int)$r->fetch_assoc()['c'];

/* Pending sticker applications */
$r               = $connection->query("SELECT COUNT(*) AS c FROM `sticker_application` WHERE status = 'Pending'");
$total_pending   = (int)$r->fetch_assoc()['c'];

/* Logged-in admin */
$admin_name = htmlspecialchars($_SESSION['full_name'] ?? 'Administrator');
$hour       = (int)date('H');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

require_once 'includes/admin_header.php';
?>

<div class="container mt-4 pb-5">

    <?php if ($flash): ?>
        <div class="flash-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <!-- ── Welcome strip ── -->
    <div class="dashboard-welcome">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
            <div>
                <h2><?php echo $greeting; ?>, <?php echo $admin_name; ?>!</h2>
                <p>
                    <?php echo date('l, F j, Y'); ?>
                    &nbsp;&middot;&nbsp;Parking Management System &mdash; Admin Panel
                </p>
            </div>
            <div style="font-size:3rem; opacity:0.28;">&#128663;</div>
        </div>
    </div>

    <!-- ── Stat cards ── -->
    <div class="row mb-4">

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card stat-gold">
                <span class="stat-icon">&#128101;</span>
                <div class="stat-number"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card stat-maroon">
                <span class="stat-icon">&#128737;</span>
                <div class="stat-number"><?php echo number_format($total_employees); ?></div>
                <div class="stat-label">Employees</div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card stat-green">
                <span class="stat-icon">&#128663;</span>
                <div class="stat-number"><?php echo number_format($total_vehicles); ?></div>
                <div class="stat-label">Registered Vehicles</div>
            </div>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card stat-blue">
                <span class="stat-icon">&#9203;</span>
                <div class="stat-number"><?php echo number_format($total_pending); ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
        </div>

    </div>

    <!-- ── Secondary stats ── -->
    <div class="row mb-4">

        <div class="col-md-6 mb-3">
            <div class="admin-card">
                <div class="admin-card-header"><h4>User Breakdown</h4></div>
                <div class="admin-card-body" style="padding:20px 28px;">
                    <table style="width:100%; font-size:0.9rem;">
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:10px 0; color:#555;">
                                <span class="role-badge role-employee" style="margin-right:10px;">Employee</span>
                                Employees &amp; Faculty
                            </td>
                            <td style="text-align:right; font-weight:700; color:#800000; font-size:1.1rem;">
                                <?php echo number_format($total_employees); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:10px 0; color:#555;">
                                <span class="role-badge role-student" style="margin-right:10px;">Student</span>
                                Students
                            </td>
                            <td style="text-align:right; font-weight:700; color:#800000; font-size:1.1rem;">
                                <?php echo number_format($total_students); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="admin-card" style="height:100%;">
                <div class="admin-card-header"><h4>System Status</h4></div>
                <div class="admin-card-body d-flex flex-column justify-content-center" style="padding:20px 28px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span style="font-size:0.88rem; color:#555;">Database</span>
                        <span style="background:#d4edda; color:#155724; border-radius:20px; padding:3px 14px; font-size:0.78rem; font-weight:700;">
                            &#10003; Connected
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span style="font-size:0.88rem; color:#555;">Session</span>
                        <span style="background:#d4edda; color:#155724; border-radius:20px; padding:3px 14px; font-size:0.78rem; font-weight:700;">
                            &#10003; Active
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-size:0.88rem; color:#555;">Server Time</span>
                        <span style="font-size:0.88rem; color:#800000; font-weight:700;">
                            <?php echo date('h:i A'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Quick links ── -->
    <h5 style="color:#800000; font-family:'EB Garamond',serif; font-size:1.2rem; margin-bottom:16px; font-weight:700;">
        Quick Access
    </h5>
    <div class="row mb-4">

        <div class="col-6 col-md-3 mb-3">
            <a href="admin_list.php" class="quick-link-card">
                <span class="ql-icon">&#128737;</span>
                <div class="ql-label">Employee Users</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Manage employees</div>
            </a>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <a href="admin_create.php" class="quick-link-card">
                <span class="ql-icon">&#43;&#128100;</span>
                <div class="ql-label">Add Employee</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Create a new account</div>
            </a>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <a href="#" class="quick-link-card">
                <span class="ql-icon">&#128663;</span>
                <div class="ql-label">Vehicles</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Registered vehicles</div>
            </a>
        </div>

        <div class="col-6 col-md-3 mb-3">
            <a href="#" class="quick-link-card">
                <span class="ql-icon">&#128196;</span>
                <div class="ql-label">Applications</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Sticker applications</div>
            </a>
        </div>

    </div>

</div>

<?php require_once 'includes/admin_footer.php'; ?>