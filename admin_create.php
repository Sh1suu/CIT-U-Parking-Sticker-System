<?php
/**
 * admin_create.php
 * Form to create a new administrator account.
 * Fields: full_name, email, password, department, student_or_employee_id
 * Role is set to 'admin' automatically.
 * Password is hashed with PASSWORD_DEFAULT (bcrypt).
 * Protected: admin session required.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Add Admin';

/* ── Form field state (repopulated on validation failure) ────────── */
$full_name            = '';
$email                = '';
$department           = '';
$student_or_employee_id = '';

/* ── Per-field error messages ────────────────────────────────────── */
$errors = [];

/* ── Handle form submission ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnCreate'])) {

    /* Retrieve and sanitise raw input */
    $full_name              = trim($_POST['full_name']   ?? '');
    $email                  = trim($_POST['email']       ?? '');
    $password               = $_POST['password']         ?? '';
    $confirm_password       = $_POST['confirm_password'] ?? '';
    $department             = trim($_POST['department']  ?? '');
    $student_or_employee_id = trim($_POST['student_or_employee_id'] ?? '');

    /* ── Server-side validation ── */

    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 150) {
        $errors['full_name'] = 'Full name must be between 2 and 150 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        /* Check email uniqueness */
        $check = $connection->prepare("SELECT user_id FROM tbuser WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors['email'] = 'This email is already registered in the system.';
        }
        $check->close();
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm the password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if ($department === '') {
        $errors['department'] = 'Department is required.';
    }

    if ($student_or_employee_id === '') {
        $errors['student_or_employee_id'] = 'Employee ID is required.';
    }

    /* ── Insert if no errors ── */
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role            = 'admin';

        $stmt = $connection->prepare(
            "INSERT INTO tbuser (full_name, email, password, role, department, student_or_employee_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssss',
            $full_name, $email, $hashed_password, $role, $department, $student_or_employee_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_message'] = 'Admin account for "' . $full_name . '" was created successfully.';
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
        <span>Add New Admin</span>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="admin-card">
                <div class="admin-card-header">
                    <h4>&#43; Create New Administrator</h4>
                </div>
                <div class="admin-card-body">

                    <?php if (isset($errors['_general'])): ?>
                        <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="admin_create.php" novalidate id="createAdminForm">

                        <!-- Full Name -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="full_name">Full Name <span style="color:#dc3545;">*</span></label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                placeholder="Enter full name"
                                value="<?php echo htmlspecialchars($full_name); ?>"
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
                                placeholder="your.email@university.edu"
                                value="<?php echo htmlspecialchars($email); ?>"
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
                                placeholder="e.g., Parking Management Office"
                                value="<?php echo htmlspecialchars($department); ?>"
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
                                placeholder="e.g., EMP-2024-001"
                                value="<?php echo htmlspecialchars($student_or_employee_id); ?>"
                                maxlength="100"
                                required
                            >
                            <?php if (isset($errors['student_or_employee_id'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['student_or_employee_id']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Divider -->
                        <hr style="border-color:#f0f0f0; margin:24px 0;">

                        <!-- Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="password">Password <span style="color:#dc3545;">*</span></label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Minimum 8 characters"
                                required
                            >
                            <?php if (isset($errors['password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="confirm_password">Confirm Password <span style="color:#dc3545;">*</span></label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control-cit <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Re-enter your password"
                                required
                            >
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Action buttons -->
                        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap" style="gap:12px;">
                            <a href="admin_list.php" class="btn-cit-outline">Cancel</a>
                            <button type="submit" name="btnCreate" class="btn-cit-primary">
                                Create Administrator
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
document.getElementById('createAdminForm').addEventListener('submit', function (e) {
    let valid = true;

    /* Clear previous client errors */
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
    else if (fullName.length < 2) show('full_name', 'Full name must be at least 2 characters.');

    const email = document.getElementById('email').value.trim();
    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) show('email', 'Email address is required.');
    else if (!emailRe.test(email)) show('email', 'Please enter a valid email address.');

    const dept = document.getElementById('department').value.trim();
    if (!dept) show('department', 'Department is required.');

    const empId = document.getElementById('student_or_employee_id').value.trim();
    if (!empId) show('student_or_employee_id', 'Employee ID is required.');

    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    if (!pw) show('password', 'Password is required.');
    else if (pw.length < 8) show('password', 'Password must be at least 8 characters.');

    if (!cpw) show('confirm_password', 'Please confirm the password.');
    else if (pw && pw !== cpw) show('confirm_password', 'Passwords do not match.');

    if (!valid) e.preventDefault();
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
