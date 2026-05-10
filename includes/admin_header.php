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
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap"
          rel="stylesheet">

    <!-- CIT Parking System stylesheet -->
    <link rel="stylesheet" href="assets/cit-style.css">

    <title>CIT-U Parking Admin &mdash; <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin'; ?></title>
</head>
<body>

    <!-- ── CIT University Banner ─────────────────────────────── -->
    <div class="cit-banner">
        <div class="container">
            <img src="images/citlogo.png" alt="CIT University Logo">
            <h1>CEBU INSTITUTE OF TECHNOLOGY</h1>
            <p>UNIVERSITY &nbsp;&middot;&nbsp; PARKING MANAGEMENT SYSTEM</p>
        </div>
    </div>

    <!-- ── Admin Navigation ──────────────────────────────────── -->
    <div class="cit-nav">
        <div class="container">
            <a href="dashboard.php"
               class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            <span class="separator">|</span>
            <a href="admin_list.php"
               class="<?php echo in_array(basename($_SERVER['PHP_SELF']),
                   ['admin_list.php','admin_create.php','admin_edit.php'], true) ? 'active' : ''; ?>">
                Employee Users
            </a>
            <span class="separator">|</span>
            <a href="#">Vehicles</a>
            <span class="separator">|</span>
            <a href="#">Applications</a>
            <span class="separator">|</span>
            <a href="logout.php">Log Out</a>
        </div>
    </div>