<?php
/**
 * register.php
 * CIT University Parking Management System — User Registration
 *
 * Design: full-screen maroon background with a centered white card.
 * Matches the prototype screenshot exactly — including the User Type
 * dropdown and the dynamic student/employee ID field.
 *
 * Roles stored in tbuser.role: 'student' | 'faculty' | 'admin'
 * The register page supports student and faculty self-registration.
 * Admin accounts are created through admin_create.php.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 */

session_start();

/* ── Already logged in? ── */
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'connect.php';

/* ── Field state (repopulated on validation failure) ── */
$full_name              = '';
$email                  = '';
$role                   = 'student';
$department             = '';
$student_or_employee_id = '';

$errors = [];

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {

    $full_name              = trim($_POST['full_name']              ?? '');
    $email                  = trim($_POST['email']                  ?? '');
    $password               = $_POST['password']                    ?? '';
    $confirm_password       = $_POST['confirm_password']            ?? '';
    $role                   = $_POST['role']                        ?? 'student';
    $department             = trim($_POST['department']             ?? '');
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
        $chk = $connection->prepare("SELECT user_id FROM tbuser WHERE email = ? LIMIT 1");
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors['email'] = 'This email is already registered. Please log in.';
        }
        $chk->close();
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    /* Sanitise role to allowed values only */
    $allowed_roles = ['student', 'faculty'];
    if (!in_array($role, $allowed_roles, true)) {
        $role = 'student';
    }

    if ($student_or_employee_id === '') {
        $label = ($role === 'student') ? 'Student number' : 'Employee ID';
        $errors['student_or_employee_id'] = $label . ' is required.';
    }

    /* Department is the "Course" field for students, "Department" for faculty */
    if ($department === '') {
        $label = ($role === 'student') ? 'Course' : 'Department';
        $errors['department'] = $label . ' is required.';
    }

    /* ── Insert if valid ── */
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare(
            "INSERT INTO tbuser (full_name, email, password, role, department, student_or_employee_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssss',
            $full_name, $email, $hashed, $role, $department, $student_or_employee_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_message'] = 'Account created successfully! Please log in.';
            header('Location: login.php');
            exit();
        } else {
            $stmt->close();
            $errors['_general'] = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap 4.5.3 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2"
          crossorigin="anonymous">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=Lato:wght@300;400;700&display=swap"
          rel="stylesheet">

    <!-- Project stylesheet -->
    <link rel="stylesheet" href="assets/style.css">

    <title>Register &mdash; CIT-U Parking System</title>
</head>
<body class="auth-page">

    <div class="auth-bg"
         style="background-image: url('images/cit_building.jpg'); background-size: cover; background-position: center; align-items: flex-start; padding-top: 40px; padding-bottom: 40px;">

        <div class="auth-card" style="max-width: 560px;">

            <!-- Heading -->
            <h2>Register</h2>
            <p class="auth-subtitle">Create your parking account</p>

            <!-- General error -->
            <?php if (isset($errors['_general'])): ?>
                <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
            <?php endif; ?>

            <!-- Registration form -->
            <form method="POST" action="register.php" novalidate id="registerForm">

                <!-- Full Name -->
                <div class="form-group-cit">
                    <label class="form-label-cit" for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                        placeholder="Enter your full name"
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
                    <label class="form-label-cit" for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        placeholder="your.email@university.edu"
                        value="<?php echo htmlspecialchars($email); ?>"
                        maxlength="200"
                        autocomplete="email"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group-cit">
                    <label class="form-label-cit" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                        placeholder="Create Password"
                        autocomplete="new-password"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Confirm Password (not in prototype screenshot but required for usability) -->
                <div class="form-group-cit">
                    <label class="form-label-cit" for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control-cit <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        required
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- User Type -->
                <div class="form-group-cit">
                    <label class="form-label-cit" for="role">User Type</label>
                    <select
                        id="role"
                        name="role"
                        class="form-control-cit <?php echo isset($errors['role']) ? 'is-invalid' : ''; ?>"
                        onchange="adaptFields(this.value)"
                    >
                        <option value="student" <?php echo ($role === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="faculty" <?php echo ($role === 'faculty') ? 'selected' : ''; ?>>Faculty / Staff</option>
                    </select>
                </div>

                <!-- Student Number / Employee ID  (label changes with role) -->
                <div class="form-group-cit">
                    <label class="form-label-cit" id="id_label" for="student_or_employee_id">Student Number</label>
                    <input
                        type="text"
                        id="student_or_employee_id"
                        name="student_or_employee_id"
                        class="form-control-cit <?php echo isset($errors['student_or_employee_id']) ? 'is-invalid' : ''; ?>"
                        placeholder="e.g., 24-2702-884"
                        value="<?php echo htmlspecialchars($student_or_employee_id); ?>"
                        maxlength="100"
                        required
                    >
                    <?php if (isset($errors['student_or_employee_id'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['student_or_employee_id']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Course / Department  (label changes with role) -->
                <div class="form-group-cit">
                    <label class="form-label-cit" id="dept_label" for="department">Course</label>
                    <input
                        type="text"
                        id="department"
                        name="department"
                        class="form-control-cit <?php echo isset($errors['department']) ? 'is-invalid' : ''; ?>"
                        placeholder="e.g., Information Technology"
                        value="<?php echo htmlspecialchars($department); ?>"
                        maxlength="150"
                        required
                    >
                    <?php if (isset($errors['department'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['department']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Submit -->
                <button type="submit" name="btnRegister" class="btn-cit-primary btn-block" style="margin-top: 8px;">
                    Register
                </button>

            </form>

            <!-- Link to login -->
            <p class="auth-footer-text">
                Already have an account?&nbsp;<a href="login.php">Log in</a>
            </p>

        </div><!-- /.auth-card -->
    </div><!-- /.auth-bg -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
            integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx"
            crossorigin="anonymous"></script>

    <script>
    /* ── Adapt labels based on selected user type ── */
    function adaptFields(role) {
        const idLabel   = document.getElementById('id_label');
        const deptLabel = document.getElementById('dept_label');
        const idInput   = document.getElementById('student_or_employee_id');
        const deptInput = document.getElementById('department');

        if (role === 'faculty') {
            idLabel.textContent   = 'Employee ID';
            deptLabel.textContent = 'Department';
            idInput.placeholder   = 'e.g., EMP-2024-001';
            deptInput.placeholder = 'e.g., College of Engineering';
        } else {
            idLabel.textContent   = 'Student Number';
            deptLabel.textContent = 'Course';
            idInput.placeholder   = 'e.g., 24-2702-884';
            deptInput.placeholder = 'e.g., Information Technology';
        }
    }

    /* Initialise labels on page load (handles server-side repopulation) */
    adaptFields(document.getElementById('role').value);

    /* ── Client-side validation ── */
    document.getElementById('registerForm').addEventListener('submit', function (e) {
        let valid = true;

        document.querySelectorAll('.client-err').forEach(el => el.remove());
        document.querySelectorAll('.form-control-cit').forEach(el => el.classList.remove('is-invalid'));

        const showErr = (id, msg) => {
            const el = document.getElementById(id);
            el.classList.add('is-invalid');
            const span = document.createElement('span');
            span.className = 'invalid-feedback-cit client-err';
            span.textContent = msg;
            el.parentNode.appendChild(span);
            valid = false;
        };

        const name = document.getElementById('full_name').value.trim();
        if (!name) showErr('full_name', 'Full name is required.');
        else if (name.length < 2) showErr('full_name', 'Name must be at least 2 characters.');

        const em = document.getElementById('email').value.trim();
        if (!em) showErr('email', 'Email is required.');
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) showErr('email', 'Enter a valid email address.');

        const pw  = document.getElementById('password').value;
        const cpw = document.getElementById('confirm_password').value;
        if (!pw) showErr('password', 'Password is required.');
        else if (pw.length < 8) showErr('password', 'Password must be at least 8 characters.');

        if (!cpw) showErr('confirm_password', 'Please confirm your password.');
        else if (pw && pw !== cpw) showErr('confirm_password', 'Passwords do not match.');

        const sid  = document.getElementById('student_or_employee_id').value.trim();
        const role = document.getElementById('role').value;
        if (!sid) showErr('student_or_employee_id', (role === 'faculty' ? 'Employee ID' : 'Student number') + ' is required.');

        const dept = document.getElementById('department').value.trim();
        if (!dept) showErr('department', (role === 'faculty' ? 'Department' : 'Course') + ' is required.');

        if (!valid) e.preventDefault();
    });
    </script>
</body>
</html>