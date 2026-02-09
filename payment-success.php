<?php
session_start();
include "database.php";

/* ========== SECURITY CHECK ========== */
if (!isset($_SESSION['esewa_pending'])) {
    header("Location: index.php?error=no_pending");
    exit;
}

$data = $_SESSION['esewa_pending'];

$user_id          = (int)$data['user_id'];
$items            = $data['items'] ?? [];
$location         = $data['location'] ?? 'Not provided';
$grand_total      = (float)$data['grand_total'];
$grand_total_str  = $data['grand_total_str'] ?? '';
$transaction_uuid = $data['transaction_uuid'] ?? '';

/* If no items → error */
if (empty($items)) {
    header("Location: index.php?error=empty_order");
    exit;
}

/* ========== GENERATE RMS TRANSACTION ID ========== */
/* Format: RMS-userid-YYYYMMDD-XXXXXX */
$datePart   = date('Ymd');
$randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$refId      = "RMS-{$user_id}-{$datePart}-{$randomPart}";

/* ========== START TRANSACTION ========== */
$conn->begin_transaction();

try {
    $group_order_id = "ORD-" . strtoupper(uniqid());
    $status = 'In Process';
    $order_history_ids = [];

    /* ========== SAVE EACH ITEM ========== */
    foreach ($items as $item) {

        $item_name  = $item['name'] ?? 'Unknown Item';
        $item_image = $item['img'] ?? $item['image'] ?? 'no-image.jpg';
        $price      = (float)($item['price'] ?? 0);
        $quantity   = (int)($item['qty'] ?? 1);
        $item_total = $price * $quantity;

        $order_id = "O-" . strtoupper(uniqid());

        $stmt = $conn->prepare("
            INSERT INTO order_history
            (user_id, group_order_id, order_id, item_name, item_img, price, quantity, total, location, status, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "issssdiiss",
            $user_id,
            $group_order_id,
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
        $order_history_ids[] = $conn->insert_id;
        $stmt->close();
    }

    /* ========== SAVE PAYMENT (ONCE PER GROUP ORDER) ========== */
    $payment_method = 'eSewa';
    $payment_status = 'Completed';
    $tip_amount     = 0.00;
    $notes          = NULL;

    $first_order_history_id = $order_history_ids[0] ?? null;

    if ($first_order_history_id) {

        $stmt2 = $conn->prepare("
            INSERT INTO payments
            (order_history_id, user_id, payment_amount, payment_method, payment_status, payment_date, tip_amount, transaction_id, notes)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ");

        /* IMPORTANT: transaction_id MUST be 's' */
        $stmt2->bind_param(
            "iidsssss",
            $first_order_history_id,
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
    }

    /* ========== COMMIT ========== */
    $conn->commit();

    /* ========== CLEAR SESSION ========== */
    unset($_SESSION['pending_single_order']);
    unset($_SESSION['pending_checkout']);
    unset($_SESSION['esewa_pending']);
    unset($_SESSION['cart']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Order / Payment save failed: " . $e->getMessage());
    die("Sorry, there was a problem processing your order.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful - RestroDash</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f5f5f5}
.navbar{height:90px;padding:20px 50px;background:#2f4f2f;color:#fff;display:flex;align-items:center}
.nav-links{margin-left:auto;display:flex;gap:30px}
.nav-links a{color:#fff;text-decoration:none;font-size:24px;font-weight:bold}
.content{text-align:center;padding:80px 20px}
.success{font-size:42px;color:#1c733c;font-weight:bold}
.order-summary{background:#e8f5e9;padding:24px;border-radius:12px;max-width:600px;margin:30px auto;text-align:left}
.item-row{display:flex;justify-content:space-between;padding:6px 0}
.btn{display:inline-block;padding:14px 32px;border-radius:10px;font-size:18px;text-decoration:none;margin:10px}
.btn-primary{background:#1c733c;color:#fff}
.btn-secondary{background:#6c757d;color:#fff}
</style>
</head>

<body>

<div class="navbar">
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
    </div>
</div>

<div class="content">
    <div class="success">🎉 Payment Successful!</div>
    <p>Your order is <strong>In Process</strong>.</p>

    <div class="order-summary">
        <strong>Group Order ID:</strong> <?= htmlspecialchars($group_order_id) ?><br>
        <strong>Transaction ID:</strong> <?= htmlspecialchars($refId) ?><br>
        <strong>Delivery To:</strong> <?= htmlspecialchars($location) ?><br><br>

        <?php foreach ($items as $item): ?>
        <div class="item-row">
            <span><?= htmlspecialchars($item['name']) ?> × <?= (int)$item['qty'] ?></span>
            <span>Rs <?= number_format($item['price'] * $item['qty'], 2) ?></span>
        </div>
        <?php endforeach; ?>

        <div style="text-align:right;font-weight:bold;margin-top:10px">
            Total: Rs <?= number_format($grand_total, 2) ?>
        </div>
    </div>

    <a href="history.php" class="btn btn-primary">View Order History</a>
    <a href="index.php" class="btn btn-secondary">Back to Home</a>
</div>

</body>
</html>
