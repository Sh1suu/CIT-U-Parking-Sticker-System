<?php
/**
 * login.php
 * CIT University Parking Management System — Admin Login
 *
 * Design: full-screen maroon/building background with a centered white card.
 * Matches the prototype screenshot exactly.
 *
 * ERD table: tbuser  (user_id, full_name, email, password, role, department, student_or_employee_id)
 */

session_start();

/* ── Already logged in? Skip straight to dashboard ── */
if (isset($_SESSION['username']) && $_SESSION['userType'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'connect.php';

/* ── Field state ── */
$email  = '';
$errors = [];
$flash  = '';

/* ── Consume logout flash ── */
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin'])) {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    /* Basic presence checks */
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        /* Fetch the admin record — prepared statement, no injection */
        $stmt = $connection->prepare(
            "SELECT user_id, full_name, password, role
             FROM   tbuser
             WHERE  email = ? AND role = 'admin'
             LIMIT  1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password'])) {
            /* ── Successful login ── */
            session_regenerate_id(true);                /* prevent session fixation */
            $_SESSION['username']  = $row['user_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['userType']  = $row['role'];

            header('Location: dashboard.php');
            exit();
        } else {
            /* Generic error — do not reveal whether email or password was wrong */
            $errors['_general'] = 'Invalid email address or password. Please try again.';
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

    <title>Log In &mdash; CIT-U Parking System</title>
</head>
<body class="auth-page">

    <!-- ── Full-screen background wrapper ──────────────────────────── -->
    <div class="auth-bg"
         style="background-image: url('images/cit_building.jpg'); background-size: cover; background-position: center;">

        <!-- White card -->
        <div class="auth-card">

            <!-- Heading -->
            <h2>Log In</h2>
            <p class="auth-subtitle">Access your account</p>

            <!-- Logout flash -->
            <?php if ($flash): ?>
                <div class="flash-success"><?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <!-- General error -->
            <?php if (isset($errors['_general'])): ?>
                <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="POST" action="login.php" novalidate id="loginForm">

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
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Remember me + Forgot password -->
                <div class="auth-meta">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <a href="#">Forgot Password?</a>
                </div>

                <!-- Submit -->
                <button type="submit" name="btnLogin" class="btn-cit-primary btn-block">
                    Login
                </button>

            </form>

            <!-- Link to register -->
            <p class="auth-footer-text">
                Don&apos;t have an account?&nbsp;<a href="register.php">Register</a>
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
    /* ── Lightweight client-side validation ── */
    document.getElementById('loginForm').addEventListener('submit', function (e) {
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

        const emailVal = document.getElementById('email').value.trim();
        if (!emailVal) showErr('email', 'Email address is required.');
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) showErr('email', 'Enter a valid email address.');

        if (!document.getElementById('password').value) showErr('password', 'Password is required.');

        if (!valid) e.preventDefault();
    });
    </script>
</body>
</html>