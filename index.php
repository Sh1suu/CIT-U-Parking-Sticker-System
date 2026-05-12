<?php    
    include 'connect.php';
    //include 'readrecords.php';   
    //require_once 'includes/header.php'; 
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

                <header class="hero-text">
                    <span class="eyebrow">CIT Parking Sticker Application</span>
                    <h1>Get Your Sticker <br>At <span>CIT</span></h1>
                    <div class="gold-divider"></div>
                    <p>Secure a parking ticket and travel safe.</p>
                </header>


<div>
	<a href="register.php"  class="btn-cit-primary btn-block" >REGISTER NEW USER</a><br>
	<a href="login.php"  class="btn-cit-primary btn-block">LOGIN</a>
</div>


<?php require_once 'includes/admin_footer.php'; ?>