<?php
session_start();
$order_id = $_GET['order_id'] ?? 'N/A';
$txid     = $_GET['txid'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful - RestroDash</title>
<style>
    body {font-family:Arial,sans-serif; background:#f8f9fa; margin:0; padding:0;}
    .container {max-width:700px; margin:100px auto; padding:40px; background:white; border-radius:16px; box-shadow:0 8px 30px rgba(0,0,0,0.1); text-align:center;}
    h1 {color:#2f4f4f;}
    .success {font-size:80px; color:#28a745; margin:20px 0;}
    .btn {background:#2f4f4f; color:white; padding:14px 30px; border-radius:30px; text-decoration:none; margin:10px; display:inline-block;}
    .btn:hover {background:#ffcc00; color:#2f4f4f;}
</style>
</head>
<body>

<!-- Your navbar here -->

<div class="container">
    <div class="success">✓</div>
    <h1>Payment Successful!</h1>
    <p>Your order has been placed successfully.</p>
    <p><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?><br>
       <strong>Transaction ID:</strong> <?= htmlspecialchars($txid) ?></p>

    <a href="history.php" class="btn">View Orders</a>
    <a href="menu.php" class="btn">Continue Shopping</a>
</div>

</body>
</html>