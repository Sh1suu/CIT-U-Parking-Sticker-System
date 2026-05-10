<?php
/**
 * login.php
 * CIT University Parking Management System — Login
 */

session_start();

require_once 'connect.php';

// Only redirect if user is actually logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Redirect based on user type
    if ($_SESSION['user_type'] === 'Employee') {
        header('Location: dashboard.php');
        exit();
    } else {
        // For students, redirect to register or student page
        header('Location: register.php');
        exit();
    }
}

$email  = '';
$errors = [];
$flash  = '';

/* Consume flash from logout or register */
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin'])) {

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $connection->prepare(
            "SELECT user_id, full_name, password, user_type
               FROM `user`
              WHERE email = ?
              LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $row['user_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['user_type'] = $row['user_type'];

            // Redirect based on user type
            if ($row['user_type'] === 'Employee') {
                header('Location: dashboard.php');
            } else {
                // For students, show message and redirect to register
                $_SESSION['flash_message'] = 'Student accounts are not yet supported. Please use an Employee account.';
                header('Location: register.php');
            }
            exit();
        } else {
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
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=Lato:wght@300;400;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/cit-style.css">
    <title>Log In &mdash; CIT-U Parking System</title>
</head>
<body class="auth-page">

<div class="auth-bg"
     style="background-image:url('images/cit_building.jpg'); background-size:cover; background-position:center;">

    <div class="auth-card">

        <h2>Log In</h2>
        <p class="auth-subtitle">Access your employee account</p>

        <?php if ($flash): ?>
            <div class="flash-success"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>
        <?php if (isset($errors['_general'])): ?>
            <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate id="loginForm">

            <div class="form-group-cit">
                <label class="form-label-cit" for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    placeholder="your.email@university.edu"
                    value="<?php echo htmlspecialchars($email); ?>"
                    autocomplete="email" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group-cit">
                <label class="form-label-cit" for="password">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                    placeholder="Enter your password"
                    autocomplete="current-password" required>
                <?php if (isset($errors['password'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <div class="auth-meta">
                <label><input type="checkbox" name="remember" value="1"> Remember me</label>
                <a href="#">Forgot Password?</a>
            </div>

            <button type="submit" name="btnLogin" class="btn-cit-primary btn-block">Login to Dashboard</button>

        </form>

        <p class="auth-footer-text">
            Don't have an employee account?&nbsp;<a href="register.php">Register here</a>
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
document.getElementById('loginForm').addEventListener('submit', function(e) {
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

    const ev = document.getElementById('email').value.trim();
    if (!ev) err('email', 'Email address is required.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ev)) err('email', 'Enter a valid email address.');
    
    const pw = document.getElementById('password').value;
    if (!pw) err('password', 'Password is required.');
    
    if (!ok) e.preventDefault();
});
</script>
</body>
</html>