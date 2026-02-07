<?php
session_start();
include "database.php";

// ========== SECURITY CHECK ==========
if (!isset($_SESSION['esewa_pending'])) {
    header("Location: index.php?error=no_pending");
    exit;
}

$data = $_SESSION['esewa_pending'];
$user_id          = (int)$data['user_id'];
$items            = $data['items'] ?? [];
$location         = $data['location'] ?? 'Not provided';
$grand_total      = (float)$data['grand_total'];
$grand_total_str  = $data['grand_total_str'];
$transaction_uuid = $data['transaction_uuid'] ?? '';

// Get eSewa parameters (fallback values for testing)
$refId = $_GET['refId'] ?? 'TEST-' . time();

// ========== START TRANSACTION ==========
$conn->begin_transaction();

try {
    $order_group_id = "ORD-" . strtoupper(uniqid('', true));
    $status = 'In Process';

    // ========== SINGLE or GROUPED ORDER HANDLING ==========
    if (count($items) > 1) {
        // Grouped order
        $grouped_items = [];
        foreach ($items as $item) {
            $grouped_items[] = $item['name'] . ' × ' . $item['qty'];
        }
        $item_name = implode(' + ', $grouped_items);
        $quantity = 1;
        $item_total = $grand_total;
        $item_image = $items[0]['image'] ?? 'no-image.jpg';

        // FIX: must be a variable (cannot pass literal 0 in bind_param in PHP 8+)
        $price_grouped = 0;

        $stmt = $conn->prepare("
            INSERT INTO order_history 
            (user_id, group_order_id, order_id, item_name, item_img, price, quantity, total, location, status, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $order_id = "O-" . strtoupper(uniqid('', true));

        $stmt->bind_param(
            "issssdisss",
            $user_id,
            $order_group_id,
            $order_id,
            $item_name,
            $item_image,
            $price_grouped,
            $quantity,
            $item_total,
            $location,
            $status
        );
        $stmt->execute();
        $order_history_id = $conn->insert_id;
        $stmt->close();
    } else {
        // Single item
        $item = $items[0] ?? [];
        $item_name  = $item['name'] ?? 'Unknown Item';
        $item_image = $item['image'] ?? 'no-image.jpg';
        $price      = (float)($item['price'] ?? 0);
        $quantity   = (int)($item['qty'] ?? 1);
        $item_total = $price * $quantity;

        $stmt = $conn->prepare("
            INSERT INTO order_history 
            (user_id, group_order_id, order_id, item_name, item_img, price, quantity, total, location, status, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $order_id = "O-" . strtoupper(uniqid('', true));

        $stmt->bind_param(
            "issssdisss",
            $user_id,
            $order_group_id,
            $order_id,
            $item_name,
            $item_image,
            $price,
            $quantity,
            $item_total,
            $location,
            $status
        );
        $stmt->execute();
        $order_history_id = $conn->insert_id;
        $stmt->close();
    }

    // ========== SAVE PAYMENT RECORD ==========
    $payment_method = 'eSewa';
    $payment_status = 'Completed';
    $tip_amount = 0.00;
    $notes = NULL;

    $stmt2 = $conn->prepare("
        INSERT INTO payments 
        (order_history_id, user_id, payment_amount, payment_method, payment_status, payment_date, tip_amount, transaction_id, notes)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
    ");

    // FIXED: correct bind types → user_id = i (integer), transaction_id = s (string)
    $stmt2->bind_param(
        "iidssdds",
        $order_history_id,
        $user_id,
        $grand_total,
        $payment_method,
        $payment_status,
        $tip_amount,
        $refId,
        $notes
    );
    $stmt2->execute();
    $stmt2->close();

    // ========== COMMIT TRANSACTION ==========
    $conn->commit();

    // ========== CLEAR SESSION ==========
    unset($_SESSION['pending_single_order']);
    unset($_SESSION['pending_checkout']);
    unset($_SESSION['esewa_pending']);
    unset($_SESSION['cart']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order / Payment save failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - RestroDash</title>
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
            text-align:center; padding:100px 20px; max-width:700px; margin:0 auto;
        }
        .success {
            font-size:38px; color:#1c733c; margin-bottom:20px; font-weight:bold;
        }
        .message {
            font-size:20px; color:#555; margin-bottom:40px; line-height:1.5;
        }
        .order-info {
            background:#e8f5e9; border-radius:12px; padding:20px; margin:30px auto;
            max-width:500px; text-align:left;
        }
        .order-info strong { color:#2f4f2f; }
        .btn {
            display:inline-block; padding:16px 40px; border-radius:12px; font-size:20px;
            font-weight:bold; text-decoration:none; transition:0.3s; margin:10px;
        }
        .btn-primary { background:#1c733c; color:white; }
        .btn-primary:hover { background:#14522b; transform:translateY(-2px); }
        .btn-secondary { background:#6c757d; color:white; }
        .btn-secondary:hover { background:#5a6268; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png" alt="RestroDash Logo"></a>
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
    <div class="success">🎉 Payment Successful!</div>
    <div class="message">
        Thank you! Your order has been placed successfully.<br>
        It is now <strong>In Process</strong> — our team will start preparing your food soon.<br>
        <strong>Your cart has been cleared.</strong>
    </div>

    <div class="order-info">
        <strong>Order Group ID:</strong> <?= htmlspecialchars($order_group_id ?? 'N/A') ?><br>
        <strong>Delivery To:</strong> <?= htmlspecialchars($location) ?><br>
        <strong>Total Amount:</strong> Rs <?= number_format($grand_total, 2) ?><br>
        <strong>Status:</strong> In Process
    </div>

    <a href="history.php" class="btn btn-primary">View Order History</a>
    <a href="index.php" class="btn btn-secondary">Back to Home</a>
</div>

</body>
</html>