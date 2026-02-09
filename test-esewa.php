<?php
session_start();

// Simulate successful payment
$transaction_id = "MOCK-" . time();
$total_amount = 10.00;
$order_id = $_SESSION['esewa_pending']['order_id'] ?? 'TEST-ORDER';

$_SESSION['payment_success'] = [
    'transaction_id' => $transaction_id,
    'amount' => $total_amount,
    'order_id' => $order_id,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mock Payment Success</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 100px; background: #e0ffe0; }
        h1 { color: green; }
        .box { background: white; padding: 40px; border-radius: 12px; max-width: 500px; margin: auto; box-shadow: 0 5px 20px #ccc; }
        button { padding: 15px 40px; background: #2e7d32; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; }
    </style>
</head>
<body>
<div class="box">
    <h1>Payment Successful! (Mock)</h1>
    <p>This is a simulated success for project demo.</p>
    <p>Order ID: <?= htmlspecialchars($order_id) ?></p>
    <p>Amount: Rs. <?= number_format($total_amount, 2) ?></p>
    <p>Transaction ID: <?= $transaction_id ?></p>
    <br>
    <a href="payment-success.php"><button>Go to Real Success Page (for demo)</button></a>
</div>
</body>
</html>