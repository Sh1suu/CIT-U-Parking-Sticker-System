<?php
/**
 * admin_edit.php
 * Pre-filled form to update an existing Employee account.
 *
 * Schema notes (dbcit_stickerapp):
 *   `user`     : user_id (PK), full_name, email, password, user_type ENUM('Student','Employee')
 *   `employee` : user_id (FK → user.user_id CASCADE), employee_id VARCHAR(50), department VARCHAR(100)
 *
 *   SELECT:  JOIN user + employee WHERE u.user_id = ? AND u.user_type = 'Employee'
 *   UPDATE:  Two separate prepared statements — one for `user`, one for `employee`
 *            Password update is optional; leave blank to keep current hash.
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Edit Employee';

/* ── Validate ID from URL ────────────────────────────────── */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: admin_list.php');
    exit();
}

$edit_id = (int)$_GET['id'];

/* ── Fetch existing record ───────────────────────────────── */
$fetch = $connection->prepare(
    "SELECT u.user_id, u.full_name, u.email,
            e.employee_id, e.department
       FROM `user` u
       LEFT JOIN `employee` e ON e.user_id = u.user_id
      WHERE u.user_id = ? AND u.user_type = 'Employee'
      LIMIT 1"
);
$fetch->bind_param('i', $edit_id);
$fetch->execute();
$record = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$record) {
    $_SESSION['flash_message'] = 'Employee account not found.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

/* Populate from DB (overwritten by POST on validation failure) */
$full_name   = $record['full_name'];
$email       = $record['email'];
$employee_id = $record['employee_id'] ?? '';
$department  = $record['department']  ?? '';
$errors      = [];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdate'])) {

    $edit_id     = (int)($_POST['user_id']    ?? 0);
    $full_name   = trim($_POST['full_name']   ?? '');
    $email       = trim($_POST['email']       ?? '');
    $password    =      $_POST['password']    ?? '';
    $confirm_pw  =      $_POST['confirm_pw']  ?? '';
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department  = trim($_POST['department']  ?? '');

    /* ── Validation ── */
    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) > 255) {
        $errors['full_name'] = 'Full name must not exceed 255 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        /* Uniqueness — exclude current user */
        $chk = $connection->prepare(
            "SELECT user_id FROM `user` WHERE email = ? AND user_id != ? LIMIT 1"
        );
        $chk->bind_param('si', $email, $edit_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors['email'] = 'This email is already used by another account.';
        }
        $chk->close();
    }

    if ($employee_id === '') {
        $errors['employee_id'] = 'Employee ID is required.';
    } elseif (strlen($employee_id) > 50) {
        $errors['employee_id'] = 'Employee ID must not exceed 50 characters.';
    }

    if ($department === '') {
        $errors['department'] = 'Department is required.';
    }

    /* Password optional on edit */
    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors['password'] = 'New password must be at least 8 characters.';
        }
        if ($confirm_pw === '') {
            $errors['confirm_pw'] = 'Please confirm the new password.';
        } elseif ($password !== $confirm_pw) {
            $errors['confirm_pw'] = 'Passwords do not match.';
        }
    }

    /* ── Update ── */
    if (empty($errors)) {
        $connection->begin_transaction();

        try {
            /* Update `user` — with or without password */
            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $s1 = $connection->prepare(
                    "UPDATE `user`
                        SET full_name = ?, email = ?, password = ?
                      WHERE user_id = ? AND user_type = 'Employee'"
                );
                $s1->bind_param('sssi', $full_name, $email, $hashed, $edit_id);
            } else {
                $s1 = $connection->prepare(
                    "UPDATE `user`
                        SET full_name = ?, email = ?
                      WHERE user_id = ? AND user_type = 'Employee'"
                );
                $s1->bind_param('ssi', $full_name, $email, $edit_id);
            }
            $s1->execute();
            $s1->close();

            /*
             * Update `employee` — use INSERT … ON DUPLICATE KEY UPDATE to
             * handle the edge case where the employee row might be missing.
             * Columns: user_id (PK/FK), employee_id, department
             */
            $s2 = $connection->prepare(
                "INSERT INTO `employee` (user_id, employee_id, department)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   employee_id = VALUES(employee_id),
                   department  = VALUES(department)"
            );
            $s2->bind_param('iss', $edit_id, $employee_id, $department);
            $s2->execute();
            $s2->close();

            $connection->commit();

            $_SESSION['flash_message'] = 'Employee account updated successfully.';
            $_SESSION['flash_type']    = 'success';
            header('Location: admin_list.php');
            exit();

        } catch (Exception $ex) {
            $connection->rollback();
            $errors['_general'] = 'A database error occurred. Please try again.';
        }
    }
}

require_once 'includes/admin_header.php';
?>

<div class="container mt-4 pb-5">

    <!-- Breadcrumb -->
    <nav style="font-size:0.82rem; margin-bottom:20px; color:#999;">
        <a href="admin_list.php" style="color:#800000; text-decoration:none;">Employee Users</a>
        <span style="margin:0 8px;">›</span>
        <span>Edit Employee</span>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="admin-card">
                <div class="admin-card-header">
                    <h4>&#9998; Edit Employee Account</h4>
                </div>
                <div class="admin-card-body">

                    <?php if (isset($errors['_general'])): ?>
                        <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="admin_edit.php?id=<?php echo $edit_id; ?>"
                          novalidate id="editForm">
                        <input type="hidden" name="user_id" value="<?php echo $edit_id; ?>">

                        <!-- Full Name -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="full_name">
                                Full Name <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name"
                                class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                placeholder="Enter full name" maxlength="255" required>
                            <?php if (isset($errors['full_name'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="email">
                                Email Address <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="email" id="email" name="email"
                                class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($email); ?>"
                                placeholder="employee@university.edu" maxlength="100" required>
                            <?php if (isset($errors['email'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Employee ID (→ employee.employee_id) -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="employee_id">
                                Employee ID <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="text" id="employee_id" name="employee_id"
                                class="form-control-cit <?php echo isset($errors['employee_id']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($employee_id); ?>"
                                placeholder="e.g., EMP-2024-001" maxlength="50" required>
                            <?php if (isset($errors['employee_id'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['employee_id']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Department (→ employee.department) -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="department">
                                Department <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="text" id="department" name="department"
                                class="form-control-cit <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($department); ?>"
                                placeholder="e.g., Parking Management Office" maxlength="100" required>
                            <?php if (isset($errors['department'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['department']); ?></span>
                            <?php endif; ?>
                        </div>

                        <hr style="border-color:#f0f0f0; margin:24px 0;">
                        <p style="color:#888; font-size:0.82rem; margin-bottom:16px;">
                            &#128274; Leave password fields blank to keep the current password unchanged.
                        </p>

                        <!-- New Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="password">New Password</label>
                            <input type="password" id="password" name="password"
                                class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Minimum 8 characters (optional)">
                            <?php if (isset($errors['password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="confirm_pw">Confirm New Password</label>
                            <input type="password" id="confirm_pw" name="confirm_pw"
                                class="form-control-cit <?php echo isset($errors['confirm_pw']) ? 'is-invalid' : ''; ?>"
                                placeholder="Re-enter new password">
                            <?php if (isset($errors['confirm_pw'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_pw']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap" style="gap:12px;">
                            <a href="admin_list.php" class="btn-cit-outline">Cancel</a>
                            <button type="submit" name="btnUpdate" class="btn-cit-primary">
                                Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('editForm').addEventListener('submit', function(e) {
    let ok = true;
    document.querySelectorAll('.client-err').forEach(el => el.remove());
    document.querySelectorAll('.form-control-cit').forEach(el => el.classList.remove('is-invalid'));

    const err = (id, msg) => {
        const el = document.getElementById(id);
        el.classList.add('is-invalid');
        const s = document.createElement('span');
        s.className = 'invalid-feedback-cit client-err';
        s.textContent = msg;
        el.parentNode.appendChild(s);
        ok = false;
    };

    const name = document.getElementById('full_name').value.trim();
    if (!name) err('full_name', 'Full name is required.');

    const em = document.getElementById('email').value.trim();
    if (!em) err('email', 'Email address is required.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) err('email', 'Enter a valid email address.');

    if (!document.getElementById('employee_id').value.trim())
        err('employee_id', 'Employee ID is required.');
    if (!document.getElementById('department').value.trim())
        err('department', 'Department is required.');

    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_pw').value;
    if (pw !== '') {
        if (pw.length < 8) err('password', 'New password must be at least 8 characters.');
        if (!cpw) err('confirm_pw', 'Please confirm the new password.');
        else if (pw !== cpw) err('confirm_pw', 'Passwords do not match.');
    }

    if (!ok) e.preventDefault();
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>