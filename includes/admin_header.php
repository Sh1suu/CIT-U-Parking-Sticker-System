<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <title>CIT-U Parking Admin — <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin'; ?></title>
    <style>
        * { box-sizing: border-box; }

        body {
            background-color: #f5f5f5;
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* ── Top CIT Banner ── */
        .cit-banner {
            background: linear-gradient(135deg, #800000 0%, #a00000 100%);
            padding: 14px 0;
            text-align: center;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .cit-banner img {
            height: 48px;
            width: auto;
            margin-bottom: 6px;
        }
        .cit-banner h1 {
            font-family: 'EB Garamond', serif;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0 0 2px;
        }
        .cit-banner p {
            font-size: 0.72rem;
            letter-spacing: 4px;
            opacity: 0.85;
            margin: 0;
        }

        /* ── Navigation ── */
        .cit-nav {
            background-color: #ffffff;
            border-bottom: 3px solid #ffd700;
            padding: 10px 0;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .cit-nav a {
            color: #800000;
            text-decoration: none;
            margin: 0 18px;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 5px 0;
            transition: color 0.2s;
            position: relative;
        }
        .cit-nav a:hover { color: #a00000; }
        .cit-nav a.active {
            border-bottom: 2px solid #ffd700;
            padding-bottom: 6px;
        }
        .cit-nav .separator { color: #ddd; font-weight: 300; }

        /* ── Alert / Flash messages ── */
        .flash-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .flash-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* ── Cards ── */
        .admin-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .admin-card-header {
            background: linear-gradient(135deg, #800000 0%, #a00000 100%);
            color: white;
            padding: 18px 28px;
        }
        .admin-card-header h4 {
            margin: 0;
            font-family: 'EB Garamond', serif;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .admin-card-body { padding: 28px; }

        /* ── Form controls ── */
        .form-control-cit {
            border: 1.5px solid #ddd;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.93rem;
            width: 100%;
            background: #f9f9f9;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            color: #333;
        }
        .form-control-cit:focus {
            border-color: #800000;
            box-shadow: 0 0 0 3px rgba(128,0,0,0.1);
            background: #fff;
        }
        .form-control-cit.is-invalid { border-color: #dc3545; }

        .form-label-cit {
            font-weight: 700;
            font-size: 0.85rem;
            color: #444;
            margin-bottom: 5px;
            display: block;
        }
        .form-group-cit { margin-bottom: 18px; }

        .invalid-feedback-cit {
            color: #dc3545;
            font-size: 0.78rem;
            margin-top: 4px;
            display: block;
        }

        /* ── Buttons ── */
        .btn-cit-primary {
            background: #ffd700;
            color: #800000;
            border: none;
            border-radius: 30px;
            padding: 11px 32px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            font-family: 'Lato', sans-serif;
        }
        .btn-cit-primary:hover {
            background: #e6c200;
            transform: translateY(-1px);
        }
        .btn-cit-outline {
            background: transparent;
            color: #800000;
            border: 2px solid #800000;
            border-radius: 30px;
            padding: 9px 28px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            font-family: 'Lato', sans-serif;
        }
        .btn-cit-outline:hover {
            background: #800000;
            color: white;
            text-decoration: none;
        }
        .btn-cit-danger {
            background: #800000;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 7px 20px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
            font-family: 'Lato', sans-serif;
        }
        .btn-cit-danger:hover { background: #a00000; color: white; text-decoration: none; }

        .btn-cit-edit {
            background: #ffd700;
            color: #800000;
            border: none;
            border-radius: 30px;
            padding: 7px 20px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
            font-family: 'Lato', sans-serif;
        }
        .btn-cit-edit:hover { background: #e6c200; color: #800000; text-decoration: none; }

        /* ── Table ── */
        .cit-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cit-table thead tr {
            background: #800000;
            color: white;
        }
        .cit-table thead th {
            padding: 13px 16px;
            font-size: 0.83rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .cit-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.15s;
        }
        .cit-table tbody tr:hover { background: #fff8e1; }
        .cit-table tbody td {
            padding: 13px 16px;
            font-size: 0.88rem;
            color: #444;
            vertical-align: middle;
        }

        /* ── Role badge ── */
        .role-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .role-admin { background: #800000; color: white; }

        /* ── Search bar ── */
        .search-wrap {
            position: relative;
            max-width: 320px;
        }
        .search-wrap input {
            padding-left: 36px;
        }
        .search-wrap .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 0.85rem;
        }

        /* ── Page footer ── */
        .cit-footer {
            text-align: center;
            padding: 18px 0;
            color: #aaa;
            font-size: 0.78rem;
            border-top: 1px solid #eee;
            margin-top: 48px;
        }
    </style>
</head>
<body>

    <!-- CIT University Banner -->
    <div class="cit-banner">
        <div class="container">
            <img src="images/citlogo.png" alt="CIT Logo">
            <h1>CEBU INSTITUTE OF TECHNOLOGY</h1>
            <p>UNIVERSITY · PARKING MANAGEMENT SYSTEM</p>
        </div>
    </div>

    <!-- Navigation -->
    <div class="cit-nav">
        <div class="container">
            <a href="admin_list.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_list.php') ? 'active' : ''; ?>">
                Admin Users
            </a>
            <span class="separator">|</span>
            <a href="dashboard.php">Announcements</a>
            <span class="separator">|</span>
            <a href="#">Vehicles</a>
            <span class="separator">|</span>
            <a href="#">Applications</a>
            <span class="separator">|</span>
            <a href="logout.php">Log Out</a>
        </div>
    </div>

