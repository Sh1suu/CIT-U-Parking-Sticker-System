<?php
/**
 * register.php
 * CIT University Parking Management System — User Registration
 *
 * Schema notes (dbcit_stickerapp):
 *
 *   `user`     : user_id (PK AI), full_name, email, password, user_type ENUM('Student','Employee')
 *   `student`  : user_id (FK → user.user_id CASCADE), student_no, course
 *   `employee` : user_id (FK → user.user_id CASCADE), employee_id, department
 *
 *   Registration inserts ONE row into `user` then ONE row into the
 *   matching child table (`student` or `employee`) inside a transaction.
 *   Both rows must succeed or neither is kept.
 *
 *   The "User Type" dropdown maps:
 *     'Student'  → user_type = 'Student'  → child table: student  (student_no, course)
 *     'Employee' → user_type = 'Employee' → child table: employee (employee_id, department)
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'connect.php';

/* Field state */
$full_name   = '';
$email       = '';
$user_type   = 'Student';
$id_number   = '';     /* student_no OR employee_id */
$dept_course = '';     /* course OR department */
$errors      = [];

/* ── Handle POST ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {

    $full_name   = trim($_POST['full_name']   ?? '');
    $email       = trim($_POST['email']       ?? '');
    $password    =      $_POST['password']    ?? '';
    $confirm_pw  =      $_POST['confirm_pw']  ?? '';
    $user_type   =      $_POST['user_type']   ?? 'Student';
    $id_number   = trim($_POST['id_number']   ?? '');
    $dept_course = trim($_POST['dept_course'] ?? '');

    /* Sanitise user_type */
    if (!in_array($user_type, ['Student','Employee'], true)) {
        $user_type = 'Student';
    }

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
        /* Uniqueness check — user.email has UNIQUE KEY */
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

    $id_label   = $user_type === 'Student' ? 'Student number' : 'Employee ID';
    $dept_label = $user_type === 'Student' ? 'Course'         : 'Department';

    if ($id_number === '') {
        $errors['id_number']   = $id_label . ' is required.';
    }
    if ($dept_course === '') {
        $errors['dept_course'] = $dept_label . ' is required.';
    }

    /* ── Insert (transaction) ── */
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $connection->begin_transaction();
        $ok = false;

        try {
            /* 1. Insert into `user` */
            $s1 = $connection->prepare(
                "INSERT INTO `user` (full_name, email, password, user_type)
                 VALUES (?, ?, ?, ?)"
            );
            $s1->bind_param('ssss', $full_name, $email, $hashed, $user_type);
            $s1->execute();
            $new_user_id = (int)$connection->insert_id;
            $s1->close();

            /* 2. Insert into child table */
            if ($user_type === 'Student') {
                /* student: user_id, student_no, course */
                $s2 = $connection->prepare(
                    "INSERT INTO `student` (user_id, student_no, course) VALUES (?, ?, ?)"
                );
                $s2->bind_param('iss', $new_user_id, $id_number, $dept_course);
            } else {
                /* employee: user_id, employee_id, department */
                $s2 = $connection->prepare(
                    "INSERT INTO `employee` (user_id, employee_id, department) VALUES (?, ?, ?)"
                );
                $s2->bind_param('iss', $new_user_id, $id_number, $dept_course);
            }
            $s2->execute();
            $s2->close();

            $connection->commit();
            $ok = true;

        } catch (Exception $ex) {
            $connection->rollback();
            $errors['_general'] = 'A database error occurred. Please try again.';
        }

        if ($ok) {
            $_SESSION['flash_message'] = 'Account created successfully! Please log in.';
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

        <h2>Register</h2>
        <p class="auth-subtitle">Create your parking account</p>

        <?php if (isset($errors['_general'])): ?>
            <div class="flash-error"><?php echo htmlspecialchars($errors['_general']); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate id="registerForm">

            <!-- Full Name -->
            <div class="form-group-cit">
                <label class="form-label-cit" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                    class="form-control-cit <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($full_name); ?>"
                    maxlength="255" required>
                <?php if (isset($errors['full_name'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group-cit">
                <label class="form-label-cit" for="email">Email</label>
                <input type="email" id="email" name="email"
                    class="form-control-cit <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                    placeholder="your.email@university.edu"
                    value="<?php echo htmlspecialchars($email); ?>"
                    maxlength="100" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group-cit">
                <label class="form-label-cit" for="password">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control-cit <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                    placeholder="Create Password" autocomplete="new-password" required>
                <?php if (isset($errors['password'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="form-group-cit">
                <label class="form-label-cit" for="confirm_pw">Confirm Password</label>
                <input type="password" id="confirm_pw" name="confirm_pw"
                    class="form-control-cit <?php echo isset($errors['confirm_pw']) ? 'is-invalid' : ''; ?>"
                    placeholder="Re-enter your password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_pw'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['confirm_pw']); ?></span>
                <?php endif; ?>
            </div>

            <!-- User Type (maps to user.user_type) -->
            <div class="form-group-cit">
                <label class="form-label-cit" for="user_type">User Type</label>
                <select id="user_type" name="user_type"
                    class="form-control-cit"
                    onchange="adaptFields(this.value)">
                    <option value="Student"  <?php echo $user_type === 'Student'  ? 'selected' : ''; ?>>Student</option>
                    <option value="Employee" <?php echo $user_type === 'Employee' ? 'selected' : ''; ?>>Faculty / Staff</option>
                </select>
            </div>

            <!-- ID number (student_no OR employee_id) -->
            <div class="form-group-cit">
                <label class="form-label-cit" id="id_label" for="id_number">Student Number</label>
                <input type="text" id="id_number" name="id_number"
                    class="form-control-cit <?php echo isset($errors['id_number']) ? 'is-invalid' : ''; ?>"
                    placeholder="e.g., 24-2702-884"
                    value="<?php echo htmlspecialchars($id_number); ?>"
                    maxlength="50" required>
                <?php if (isset($errors['id_number'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['id_number']); ?></span>
                <?php endif; ?>
            </div>

            <!-- Course / Department (course OR department) -->
            <div class="form-group-cit">
                <label class="form-label-cit" id="dept_label" for="dept_course">Course</label>
                <input type="text" id="dept_course" name="dept_course"
                    class="form-control-cit <?php echo isset($errors['dept_course']) ? 'is-invalid' : ''; ?>"
                    placeholder="e.g., Information Technology"
                    value="<?php echo htmlspecialchars($dept_course); ?>"
                    maxlength="100" required>
                <?php if (isset($errors['dept_course'])): ?>
                    <span class="invalid-feedback-cit"><?php echo htmlspecialchars($errors['dept_course']); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" name="btnRegister" class="btn-cit-primary btn-block" style="margin-top:8px;">
                Register
            </button>

        </form>

        <p class="auth-footer-text">
            Already have an account?&nbsp;<a href="login.php">Log in</a>
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
function adaptFields(type) {
    const idLbl   = document.getElementById('id_label');
    const deptLbl = document.getElementById('dept_label');
    const idIn    = document.getElementById('id_number');
    const deptIn  = document.getElementById('dept_course');
    if (type === 'Employee') {
        idLbl.textContent   = 'Employee ID';
        deptLbl.textContent = 'Department';
        idIn.placeholder    = 'e.g., EMP-2024-001';
        deptIn.placeholder  = 'e.g., College of Engineering';
    } else {
        idLbl.textContent   = 'Student Number';
        deptLbl.textContent = 'Course';
        idIn.placeholder    = 'e.g., 24-2702-884';
        deptIn.placeholder  = 'e.g., Information Technology';
    }
}
adaptFields(document.getElementById('user_type').value);

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

    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_pw').value;
    if (!pw) err('password', 'Password is required.');
    else if (pw.length < 8) err('password', 'Password must be at least 8 characters.');
    if (!cpw) err('confirm_pw', 'Please confirm your password.');
    else if (pw && pw !== cpw) err('confirm_pw', 'Passwords do not match.');

    const type = document.getElementById('user_type').value;
    const idn  = document.getElementById('id_number').value.trim();
    const dept = document.getElementById('dept_course').value.trim();
    if (!idn) err('id_number', (type === 'Employee' ? 'Employee ID' : 'Student number') + ' is required.');
    if (!dept) err('dept_course', (type === 'Employee' ? 'Department' : 'Course') + ' is required.');

    if (!ok) e.preventDefault();
});
</script>
</body>
</html>