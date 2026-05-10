<?php
/**
 * register.php
 * CIT University Parking Management System — User Registration
 * Employee-only registration for admin panel access
 */

// Start session but don't redirect if already logged in - instead, show logout option
session_start();

require_once 'connect.php';

// If already logged in as Employee, redirect to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['user_type'] === 'Employee') {
    header('Location: dashboard.php');
    exit();
}

/* Field state */
$full_name   = '';
$email       = '';
$id_number   = '';
$dept_course = '';
$errors      = [];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {

    $full_name   = trim($_POST['full_name']   ?? '');
    $email       = trim($_POST['email']       ?? '');
    $password    =      $_POST['password']    ?? '';
    $confirm_pw  =      $_POST['confirm_pw']  ?? '';
    $id_number   = trim($_POST['id_number']   ?? '');
    $dept_course = trim($_POST['dept_course'] ?? '');

    /* Validation */
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
        $chk = $connection->prepare("SELECT user_id FROM `user` WHERE email = ? LIMIT 1");
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

    if ($confirm_pw === '') {
        $errors['confirm_pw'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_pw) {
        $errors['confirm_pw'] = 'Passwords do not match.';
    }

    if ($id_number === '') {
        $errors['id_number'] = 'Employee ID is required.';
    }
    if ($dept_course === '') {
        $errors['dept_course'] = 'Department is required.';
    }

    /* ── Insert (transaction) ── */
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'Employee';

        $connection->begin_transaction();
        $ok = false;

        try {
            $s1 = $connection->prepare(
                "INSERT INTO `user` (full_name, email, password, user_type)
                 VALUES (?, ?, ?, ?)"
            );
            $s1->bind_param('ssss', $full_name, $email, $hashed, $user_type);
            $s1->execute();
            $new_user_id = (int)$connection->insert_id;
            $s1->close();

            $s2 = $connection->prepare(
                "INSERT INTO `employee` (user_id, employee_id, department) VALUES (?, ?, ?)"
            );
            $s2->bind_param('iss', $new_user_id, $id_number, $dept_course);
            $s2->execute();
            $s2->close();

            $connection->commit();
            $ok = true;

        } catch (Exception $ex) {
            $connection->rollback();
            $errors['_general'] = 'A database error occurred. Please try again.';
        }

        if ($ok) {
            // Clear any existing session first
            $_SESSION = array();
            
            // Set success message
            session_start(); // Restart session for flash message
            $_SESSION['flash_message'] = 'Employee account created successfully! Please log in.';
            header('Location: login.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=Lato:wght@300;400;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/cit-style.css">
    <title>Register &mdash; CIT-U Parking System</title>
</head>
<body class="auth-page">

<div class="auth-bg"
     style="background-image:url('images/cit_building.jpg'); background-size:cover; background-position:center; align-items:flex-start; padding-top:40px; padding-bottom:40px;">

    <div class="auth-card" style="max-width:560px;">

        <h2>Employee Registration</h2>
        <p class="auth-subtitle">Create your staff account</p>

        <?php if (isset($errors['_general'])): ?>
            <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate id="registerForm">

            <div class="form-group-cit">
                <label class="form-label-cit" for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name"
                    class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($full_name); ?>"
                    maxlength="255" required>
                <?php if (isset($errors['full_name'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="email">Email Address *</label>
                <input type="email" id="email" name="email"
                    class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    placeholder="your.email@university.edu"
                    value="<?php echo htmlspecialchars($email); ?>"
                    maxlength="100" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="id_number">Employee ID *</label>
                <input type="text" id="id_number" name="id_number"
                    class="form-control-cit <?php echo isset($errors['id_number']) ? 'is-invalid' : ''; ?>"
                    placeholder="e.g., EMP-2024-001"
                    value="<?php echo htmlspecialchars($id_number); ?>"
                    maxlength="50" required>
                <?php if (isset($errors['id_number'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['id_number']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="dept_course">Department *</label>
                <input type="text" id="dept_course" name="dept_course"
                    class="form-control-cit <?php echo isset($errors['dept_course']) ? 'is-invalid' : ''; ?>"
                    placeholder="e.g., Parking Management Office"
                    value="<?php echo htmlspecialchars($dept_course); ?>"
                    maxlength="100" required>
                <?php if (isset($errors['dept_course'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['dept_course']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="password">Password *</label>
                <input type="password" id="password" name="password"
                    class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                    placeholder="Minimum 8 characters" autocomplete="new-password" required>
                <?php if (isset($errors['password'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="confirm_pw">Confirm Password *</label>
                <input type="password" id="confirm_pw" name="confirm_pw"
                    class="form-control-cit <?php echo isset($errors['confirm_pw']) ? 'is-invalid' : ''; ?>"
                    placeholder="Re-enter your password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_pw'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_pw']); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" name="btnRegister" class="btn-cit-primary btn-block" style="margin-top:8px;">
                Register as Employee
            </button>

        </form>

        <p class="auth-footer-text">
            Already have an account?&nbsp;<a href="login.php">Log in here</a>
        </p>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx"
        crossorigin="anonymous"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
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
    else if (name.length < 2) err('full_name', 'Name must be at least 2 characters.');

    const em = document.getElementById('email').value.trim();
    if (!em) err('email', 'Email is required.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) err('email', 'Enter a valid email address.');

    const idn = document.getElementById('id_number').value.trim();
    if (!idn) err('id_number', 'Employee ID is required.');

    const dept = document.getElementById('dept_course').value.trim();
    if (!dept) err('dept_course', 'Department is required.');

    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_pw').value;
    if (!pw) err('password', 'Password is required.');
    else if (pw.length < 8) err('password', 'Password must be at least 8 characters.');
    if (!cpw) err('confirm_pw', 'Please confirm your password.');
    else if (pw && pw !== cpw) err('confirm_pw', 'Passwords do not match.');

    if (!ok) e.preventDefault();
});
</script>
</body>
</html>