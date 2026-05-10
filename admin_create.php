<?php
/**
 * admin_create.php
 * Creates a new Employee user account.
 *
 * Schema notes (dbcit_stickerapp):
 *   `user`     : user_id (PK AI), full_name, email, password, user_type ENUM('Student','Employee')
 *   `employee` : user_id (FK → user.user_id CASCADE), employee_id VARCHAR(50), department VARCHAR(100)
 *
 *   INSERT strategy (transaction):
 *     1. INSERT INTO `user`     (full_name, email, password, user_type='Employee')
 *     2. INSERT INTO `employee` (user_id, employee_id, department)
 *   Both rows are rolled back if either fails.
 */

require_once 'includes/auth_guard.php';
require_once 'connect.php';

$pageTitle = 'Add Employee';

/* Form field state */
$full_name   = '';
$email       = '';
$employee_id = '';
$department  = '';
$errors      = [];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnCreate'])) {

    $full_name      = trim($_POST['full_name']   ?? '');
    $email          = trim($_POST['email']        ?? '');
    $password       =      $_POST['password']     ?? '';
    $confirm_pw     =      $_POST['confirm_pw']   ?? '';
    $employee_id    = trim($_POST['employee_id']  ?? '');
    $department     = trim($_POST['department']   ?? '');

    /* ── Validation ── */
    if ($full_name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 255) {
        $errors['full_name'] = 'Full name must be between 2 and 255 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        /* user.email has UNIQUE KEY */
        $chk = $connection->prepare("SELECT user_id FROM `user` WHERE email = ? LIMIT 1");
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors['email'] = 'This email is already registered in the system.';
        }
        $chk->close();
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm_pw === '') {
        $errors['confirm_pw'] = 'Please confirm the password.';
    } elseif ($password !== $confirm_pw) {
        $errors['confirm_pw'] = 'Passwords do not match.';
    }

    if ($employee_id === '') {
        $errors['employee_id'] = 'Employee ID is required.';
    } elseif (strlen($employee_id) > 50) {
        $errors['employee_id'] = 'Employee ID must not exceed 50 characters.';
    }

    if ($department === '') {
        $errors['department'] = 'Department is required.';
    }

    /* ── Insert (transaction) ── */
    if (empty($errors)) {
        $hashed    = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'Employee';

        $connection->begin_transaction();

        try {
            /* Step 1 — user row */
            $s1 = $connection->prepare(
                "INSERT INTO `user` (full_name, email, password, user_type)
                 VALUES (?, ?, ?, ?)"
            );
            $s1->bind_param('ssss', $full_name, $email, $hashed, $user_type);
            $s1->execute();
            $new_user_id = (int)$connection->insert_id;
            $s1->close();

            /* Step 2 — employee row */
            $s2 = $connection->prepare(
                "INSERT INTO `employee` (user_id, employee_id, department)
                 VALUES (?, ?, ?)"
            );
            $s2->bind_param('iss', $new_user_id, $employee_id, $department);
            $s2->execute();
            $s2->close();

            $connection->commit();

            $_SESSION['flash_message'] = 'Employee account for "' . $full_name . '" was created successfully.';
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
        <span>Add New Employee</span>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="admin-card">
                <div class="admin-card-header">
                    <h4>+ Create New Employee Account</h4>
                </div>
                <div class="admin-card-body">

                    <?php if (isset($errors['_general'])): ?>
                        <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="admin_create.php" novalidate id="createForm">

                        <!-- Full Name -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="full_name">
                                Full Name <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name"
                                class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                placeholder="Enter full name"
                                value="<?php echo htmlspecialchars($full_name); ?>"
                                maxlength="255" required>
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
                                placeholder="employee@university.edu"
                                value="<?php echo htmlspecialchars($email); ?>"
                                maxlength="100" required>
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
                                placeholder="e.g., EMP-2024-001"
                                value="<?php echo htmlspecialchars($employee_id); ?>"
                                maxlength="50" required>
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
                                placeholder="e.g., Parking Management Office"
                                value="<?php echo htmlspecialchars($department); ?>"
                                maxlength="100" required>
                            <?php if (isset($errors['department'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['department']); ?></span>
                            <?php endif; ?>
                        </div>

                        <hr style="border-color:#f0f0f0; margin:24px 0;">

                        <!-- Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="password">
                                Password <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="password" id="password" name="password"
                                class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                placeholder="Minimum 8 characters" required>
                            <?php if (isset($errors['password'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group-cit">
                            <label class="form-label-cit" for="confirm_pw">
                                Confirm Password <span style="color:#dc3545;">*</span>
                            </label>
                            <input type="password" id="confirm_pw" name="confirm_pw"
                                class="form-control-cit <?php echo isset($errors['confirm_pw']) ? 'is-invalid' : ''; ?>"
                                placeholder="Re-enter your password" required>
                            <?php if (isset($errors['confirm_pw'])): ?>
                                <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_pw']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex align-items-center justify-content-between mt-4 flex-wrap" style="gap:12px;">
                            <a href="admin_list.php" class="btn-cit-outline">Cancel</a>
                            <button type="submit" name="btnCreate" class="btn-cit-primary">
                                Create Employee Account
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('createForm').addEventListener('submit', function(e) {
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
    else if (name.length < 2) err('full_name', 'Full name must be at least 2 characters.');

    const em = document.getElementById('email').value.trim();
    if (!em) err('email', 'Email address is required.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) err('email', 'Enter a valid email address.');

    if (!document.getElementById('employee_id').value.trim())
        err('employee_id', 'Employee ID is required.');

    if (!document.getElementById('department').value.trim())
        err('department', 'Department is required.');

    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_pw').value;
    if (!pw) err('password', 'Password is required.');
    else if (pw.length < 8) err('password', 'Password must be at least 8 characters.');
    if (!cpw) err('confirm_pw', 'Please confirm the password.');
    else if (pw && pw !== cpw) err('confirm_pw', 'Passwords do not match.');

    if (!ok) e.preventDefault();
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>