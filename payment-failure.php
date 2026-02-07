<?php
session_start();
include "database.php";

// Clear session data
unset($_SESSION['esewa_pending']);
unset($_SESSION['pending_checkout']);
unset($_SESSION['pending_single_order']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Failed - RestroDash</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; background:#f5f5f5; color:#333; }

.navbar {
    height:90px; padding:20px 50px; background:#2f4f2f; color:white;
    display:flex; justify-content:space-between; align-items:center;
}
.nav-left img { height:100px; }
.nav-links { margin-left:auto; margin-right:40px; display:flex; gap:30px; align-items:center; }
.nav-links a { font-size:30px; font-weight:700; color:white; text-decoration:none; }
.nav-right a img { height:55px; border-radius:50%; }

.content { 
    text-align:center; padding:120px 20px; max-width:700px; margin:0 auto;
}
.failure {
    font-size:42px; color:#c0392b; margin-bottom:25px; font-weight:bold;
}
.message {
    font-size:20px; color:#555; margin-bottom:40px; line-height:1.5;
}
.btn {
    display:inline-block; padding:16px 40px; border-radius:12px; font-size:20px;
    font-weight:bold; text-decoration:none; transition:0.3s; margin:10px;
}
.btn-retry { background:#e67e22; color:white; }
.btn-retry:hover { background:#d35400; transform:translateY(-2px); }
.btn-home { background:#6c757d; color:white; }
.btn-home:hover { background:#5a6268; }
</style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png" alt="Logo"></a>
    </div>
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
        <a href="mycart.php"><img src="cart.png" alt="Cart"></a>
        <a href="account.php"><img src="user-icon.png" alt="Account"></a>
    </div>
</div>

<div class="content">
    <div class="failure">❌ Payment Failed</div>

    <div class="message">
        Sorry, your payment could not be completed.<br>
        Please try again or contact support if the problem persists.
    </div>

    <a href="payment.php" class="btn btn-retry">Retry Payment</a>
    <a href="index.php" class="btn btn-home">Back to Home</a>
</div>

</body>
</html>