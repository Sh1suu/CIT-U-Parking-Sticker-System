<?php
/**
 * admin_edit.php
 * Pre-filled form to update an existing admin account.
 * Password field is optional — leave blank to keep the current password.
 * Protected: admin session required.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Edit Admin';

/* ── Load the record (GET request) ──────────────────────────────── */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: admin_list.php');
    exit();
}

$edit_id = (int)$_GET['id'];

$fetch = $connection->prepare(
    "SELECT user_id, full_name, email, department, student_or_employee_id
     FROM   tbuser
     WHERE  user_id = ? AND role = 'admin'
     LIMIT  1"
);
$fetch->bind_param('i', $edit_id);
$fetch->execute();
$record = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$record) {
    /* ID not found or not an admin — back to list */
    $_SESSION['flash_message'] = 'Admin account not found.';
    $_SESSION['flash_type']    = 'error';
    header('Location: admin_list.php');
    exit();
}

/* ── Populate fields from DB (may be overwritten by POST below) ── */
$full_name              = $record['full_name'];
$email                  = $record['email'];
$department             = $record['department'];
$student_or_employee_id = $record['student_or_employee_id'];

$errors = [];

/* ── Handle form submission (POST request) ───────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdate'])) {

    $edit_id                = (int)($_POST['user_id'] ?? 0);
    $full_name              = trim($_POST['full_name']              ?? '');
    $email                  = trim($_POST['email']                  ?? '');
    $password               = $_POST['password']                    ?? '';
    $confirm_password       = $_POST['confirm_password']            ?? '';
    $department             = trim($_POST['department']             ?? '');
    $student_or_employee_id = trim($_POST['student_or_employee_id'] ?? '');

    /* ── Server-side validation ── */

    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) > 150) {
        $errors['full_name'] = 'Full name must not exceed 150 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        /* Uniqueness check — exclude the current record */
        $chk = $connection->prepare(
            "SELECT user_id FROM tbuser WHERE email = ? AND user_id != ?"
        );
        $chk->bind_param('si', $email, $edit_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors['email'] = 'This email is already used by another account.';
        }
        $chk->close();
    }

    if ($department === '') {
        $errors['department'] = 'Department is required.';
    }

    if ($student_or_employee_id === '') {
        $errors['student_or_employee_id'] = 'Employee ID is required.';
    }

    /* Password is optional on edit */
    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors['password'] = 'New password must be at least 8 characters.';
        }
        if ($confirm_password === '') {
            $errors['confirm_password'] = 'Please confirm the new password.';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    /* ── Update if no errors ── */
    if (empty($errors)) {
        if ($password !== '') {
            /* Update including new hashed password */
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $connection->prepare(
                "UPDATE tbuser
                 SET    full_name = ?, email = ?, password = ?,
                        department = ?, student_or_employee_id = ?
                 WHERE  user_id = ? AND role = 'admin'"
            );
            $stmt->bind_param('sssssi',
                $full_name, $email, $hashed,
                $department, $student_or_employee_id, $edit_id
            );
        } else {
            /* Update without touching the password */
            $stmt = $connection->prepare(
                "UPDATE tbuser
                 SET    full_name = ?, email = ?,
                        department = ?, student_or_employee_id = ?
                 WHERE  user_id = ? AND role = 'admin'"
            );
            $stmt->bind_param('ssssi',
                $full_name, $email,
                $department, $student_or_employee_id, $edit_id
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_message'] = 'Admin account updated successfully.';
            $_SESSION['flash_type']    = 'success';
            header('Location: admin_list.php');
            exit();
        } else {
            $stmt->close();
            $errors['_general'] = 'A database error occurred. Please try again.';
        }
    }
}

require_once 'includes/admin_header.php';
?>

<div class="container mt-4 pb-5">

    <!-- Breadcrumb -->
    <nav style="font-size:0.82rem; margin-bottom:20px; color:#999;">
        <a href="admin_list.php" style="color:#800000; text-decoration:none;">Admin Users</a>
        <span style="margin:0 8px;">›</span>
        <span>Edit Administrator</span>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="admin-card">
                <div class="admin-card-header">
                    <h4>&#9998; Edit Administrator</h4>
                </div>
                <div class="admin-card-body">

                    <?php if (isset($errors['_general'])): ?>
                        <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="admin_edit.php?id=<?php echo $edit_id; ?>" novalidate id="editAdminForm">
                        <!-- Hidden ID -->
                        <input type="hidden" name="user_id" value="<?php echo $edit_id; ?>">

                        <!-- Full Name -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="full_name">Full Name <span style="color:#dc3545;">*</span></label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                placeholder="Enter full name"
                                maxlength="150"
                                required
                            >
                            <?php if (isset($errors['full_name'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="email">Email Address <span style="color:#dc3545;">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($email); ?>"
                                placeholder="your.email@university.edu"
                                maxlength="200"
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Department -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="department">Department <span style="color:#dc3545;">*</span></label>
                            <input
                                type="text"
                                id="department"
                                name="department"
                                class="form-control-cit <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($department); ?>"
                                placeholder="e.g., Parking Management Office"
                                maxlength="150"
                                required
                            >
                            <?php if (isset($errors['department'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['department']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Employee ID -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="student_or_employee_id">Employee ID <span style="color:#dc3545;">*</span></label>
                            <input
                                type="text"
                                id="student_or_employee_id"
                                name="student_or_employee_id"
                                class="form-control-cit <?php echo isset($errors['student_or_employee_id']) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($student_or_employee_id); ?>"
                                placeholder="e.g., EMP-2024-001"
                                maxlength="100"
                                required
                            >
                            <?php if (isset($errors['student_or_employee_id'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['student_or_employee_id']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Divider -->
                        <hr style="border-color:#f0f0f0; margin:24px 0;">
                        <p style="color:#888; font-size:0.82rem; margin-bottom:16px;">
                            &#128274; Leave the password fields blank to keep the current password unchanged.
                        </p>

                        <!-- New Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="password">New Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Minimum 8 characters (leave blank to keep current)"
                            >
                            <?php if (isset($errors['password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="confirm_password">Confirm New Password</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control-cit <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Re-enter new password"
                            >
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Action buttons -->
                        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap" style="gap:12px;">
                            <a href="admin_list.php" class="btn-cit-outline">Cancel</a>
                            <button type="submit" name="btnUpdate" class="btn-cit-primary">
                                Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div><!-- /.admin-card -->

        </div>
    </div>
</div>

<script>
/* ── Client-side validation ── */
document.getElementById('editAdminForm').addEventListener('submit', function (e) {
    let valid = true;

    document.querySelectorAll('.client-error').forEach(el => el.remove());
    document.querySelectorAll('.form-control-cit').forEach(el => el.classList.remove('is-invalid'));

    const show = (id, msg) => {
        const input = document.getElementById(id);
        input.classList.add('is-invalid');
        const span = document.createElement('span');
        span.className = 'invalid-feedback-cit client-error';
        span.textContent = msg;
        input.parentNode.appendChild(span);
        valid = false;
    };

    const fullName = document.getElementById('full_name').value.trim();
    if (!fullName) show('full_name', 'Full name is required.');

    const email = document.getElementById('email').value.trim();
    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) show('email', 'Email address is required.');
    else if (!emailRe.test(email)) show('email', 'Please enter a valid email address.');

    const dept = document.getElementById('department').value.trim();
    if (!dept) show('department', 'Department is required.');

    const empId = document.getElementById('student_or_employee_id').value.trim();
    if (!empId) show('student_or_employee_id', 'Employee ID is required.');

    /* Password optional, but if filled both fields must match and be >= 8 chars */
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    if (pw !== '') {
        if (pw.length < 8) show('password', 'New password must be at least 8 characters.');
        if (!cpw) show('confirm_password', 'Please confirm the new password.');
        else if (pw !== cpw) show('confirm_password', 'Passwords do not match.');
    }

    if (!valid) e.preventDefault();
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
