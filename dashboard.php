<?php
/**
 * dashboard.php
 * CIT University Parking Management System — Admin Dashboard
 *
 * Shown immediately after login.  Displays:
 *  - Personalised welcome strip with the logged-in admin's name
 *  - Four stat cards: total users, total admins, total students/faculty,
 *    and total vehicles (counts pulled live from the database)
 *  - Quick-link tiles to each section of the system
 *
 * Protected by auth_guard.php — non-admin sessions are redirected.
 *
 * ERD tables queried (read-only): tbuser, Vehicle (if it exists)
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Dashboard';

/* ── Flash message from redirect ────────────────────────────────── */
$flash      = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash      = $_SESSION['flash_message'];
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

/* ── Live statistics ─────────────────────────────────────────────
   All queries use aggregate COUNT() — no user data is returned,
   so no prepared statement parameters are needed here.
─────────────────────────────────────────────────────────────────── */

/* Total registered users (all roles) */
$r             = $connection->query("SELECT COUNT(*) AS c FROM tbuser");
$total_users   = (int)$r->fetch_assoc()['c'];

/* Total admins */
$r             = $connection->query("SELECT COUNT(*) AS c FROM tbuser WHERE role = 'admin'");
$total_admins  = (int)$r->fetch_assoc()['c'];

/* Total students */
$r             = $connection->query("SELECT COUNT(*) AS c FROM tbuser WHERE role = 'student'");
$total_students = (int)$r->fetch_assoc()['c'];

/* Total faculty/staff */
$r              = $connection->query("SELECT COUNT(*) AS c FROM tbuser WHERE role = 'faculty'");
$total_faculty  = (int)$r->fetch_assoc()['c'];

/* Total vehicles — table may not exist yet; fail gracefully */
$total_vehicles = 0;
$vcheck = $connection->query("SHOW TABLES LIKE 'Vehicle'");
if ($vcheck && $vcheck->num_rows > 0) {
    $r = $connection->query("SELECT COUNT(*) AS c FROM Vehicle");
    $total_vehicles = (int)$r->fetch_assoc()['c'];
}

/* Total pending sticker applications — fail gracefully */
$total_pending = 0;
$acheck = $connection->query("SHOW TABLES LIKE 'StickerApplication'");
if ($acheck && $acheck->num_rows > 0) {
    $r = $connection->query("SELECT COUNT(*) AS c FROM StickerApplication WHERE status = 'Pending'");
    $total_pending = (int)$r->fetch_assoc()['c'];
}

/* ── Logged-in admin name ── */
$admin_name = htmlspecialchars($_SESSION['full_name'] ?? 'Administrator');
$greeting   = (date('H') < 12) ? 'Good morning' : ((date('H') < 18) ? 'Good afternoon' : 'Good evening');

require_once 'includes/admin_header.php';
?>

<div class="container mt-4 pb-5">

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="flash-<?php echo $flash_type; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <!-- ── Welcome strip ──────────────────────────────────────── -->
    <div class="dashboard-welcome">
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px;">
            <div>
                <h2><?php echo $greeting; ?>, <?php echo $admin_name; ?>!</h2>
                <p>
                    <?php echo date('l, F j, Y'); ?>
                    &nbsp;&middot;&nbsp;
                    Parking Management System &mdash; Admin Panel
                </p>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">&#128663;</div>
        </div>
    </div>

    <!-- ── Stat cards row ─────────────────────────────────────── -->
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
                <div class="stat-number"><?php echo number_format($total_admins); ?></div>
                <div class="stat-label">Administrators</div>
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

    </div><!-- /.row stat cards -->

    <!-- ── Secondary stats ────────────────────────────────────── -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4>User Breakdown</h4>
                </div>
                <div class="admin-card-body" style="padding: 20px 28px;">
                    <table style="width:100%; font-size:0.9rem;">
                        <tr style="border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                            <td style="padding: 10px 0; color:#555;">
                                <span class="role-badge role-admin" style="margin-right:10px;">Admin</span>
                                Administrators
                            </td>
                            <td style="text-align:right; font-weight:700; color:#800000; font-size:1.1rem;">
                                <?php echo number_format($total_admins); ?>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding: 10px 0; color:#555;">
                                <span class="role-badge role-student" style="margin-right:10px;">Student</span>
                                Students
                            </td>
                            <td style="text-align:right; font-weight:700; color:#800000; font-size:1.1rem;">
                                <?php echo number_format($total_students); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; color:#555;">
                                <span class="role-badge role-faculty" style="margin-right:10px;">Faculty</span>
                                Faculty &amp; Staff
                            </td>
                            <td style="text-align:right; font-weight:700; color:#800000; font-size:1.1rem;">
                                <?php echo number_format($total_faculty); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="admin-card" style="height:100%;">
                <div class="admin-card-header">
                    <h4>System Status</h4>
                </div>
                <div class="admin-card-body d-flex flex-column justify-content-center" style="padding: 20px 28px;">
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
    </div><!-- /.row secondary stats -->

    <!-- ── Quick links ─────────────────────────────────────────── -->
    <h5 style="color:#800000; font-family:'EB Garamond',serif; font-size:1.2rem; margin-bottom:16px; font-weight:700;">
        Quick Access
    </h5>
    <div class="row mb-4">

        <div class="col-6 col-md-3 mb-3">
            <a href="admin_list.php" class="quick-link-card">
                <span class="ql-icon">&#128737;</span>
                <div class="ql-label">Admin Users</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Manage administrators</div>
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

        <div class="col-6 col-md-3 mb-3">
            <a href="admin_create.php" class="quick-link-card">
                <span class="ql-icon">&#43;&#128100;</span>
                <div class="ql-label">Add Admin</div>
                <div style="font-size:0.75rem; color:#999; margin-top:4px;">Create an admin account</div>
            </a>
        </div>

    </div><!-- /.row quick links -->

</div><!-- /.container -->

<?php require_once 'includes/admin_footer.php'; ?>