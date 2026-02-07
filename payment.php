<?php
session_start();
include "database.php";

// ---------- LOGIN CHECK ----------
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ---------- REDIRECT EMPLOYEES/ADMINS ----------
$stmt = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if ($row['role'] === 'employee' || $row['admin'] == 1) {
        header("Location: admin.php");
        exit;
    }
} else {
    session_destroy();
    header("Location: index.php");
    exit;
}
$stmt->close();

// ---------- ORDER DATA – support BOTH flows ----------
$subTotal = 0;
$items    = [];
$location = 'Not provided';

// 1. Cart checkout
if (isset($_SESSION['pending_checkout']) && !empty($_SESSION['pending_checkout']['cart'])) {
    $checkout = $_SESSION['pending_checkout'];
    $items    = $checkout['cart'] ?? [];
    $location = $checkout['location'] ?? 'Not provided';

    foreach ($items as $item) {
        $subTotal += ($item['price'] ?? 0) * ($item['qty'] ?? 1);
    }
}
// 2. Single item quick order
elseif (isset($_SESSION['pending_single_order'])) {
    $order = $_SESSION['pending_single_order'];
    $subTotal = ($order['item']['price'] ?? 0) * ($order['quantity'] ?? 1);

    $items[] = [
        'name'  => $order['item']['name']   ?? 'Unknown Item',
        'price' => $order['item']['price']  ?? 0,
        'qty'   => $order['quantity']       ?? 1,
        'image' => $order['item']['image']  ?? 'food.png'
    ];

    $location = $order['location'] ?? 'Not provided';
}
// No valid pending order → redirect
else {
    header("Location: index.php");
    exit;
}

// ---------- CALCULATE TAX & TOTAL ----------
$taxAmount  = round($subTotal * 0.13, 2);
$grandTotal = $subTotal + $taxAmount;

$subTotalStr   = number_format($subTotal, 2, '.', '');
$taxAmountStr  = number_format($taxAmount, 2, '.', '');
$grandTotalStr = number_format($grandTotal, 2, '.', '');

// ---------- eSewa CONFIG ----------
$product_code     = 'EPAYTEST';
$secret_key       = '8gBm/:&EnhH.1/q';
$esewa_url        = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';

$transaction_uuid = "REQ-" . strtoupper(uniqid('', true));

$base_url     = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$base_url     = rtrim($base_url, '/');
$success_url  = $base_url . '/payment-success.php';
$failure_url  = $base_url . '/payment-failure.php';

// ---------- SIGNATURE ----------
$message   = "total_amount=$grandTotalStr,transaction_uuid=$transaction_uuid,product_code=$product_code";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// ---------- SAVE TO SESSION ----------
$_SESSION['esewa_pending'] = [
    'user_id'         => $user_id,
    'items'           => $items,
    'location'        => $location,
    'grand_total'     => $grandTotal,
    'grand_total_str' => $grandTotalStr,
    'transaction_uuid'=> $transaction_uuid
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>eSewa Payment - RestroDash</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; background:#f5f5f5; }
.navbar {
    height:90px; padding:20px 50px; background:#2f4f2f; color:white;
    display:flex; justify-content:space-between; align-items:center;
}
.nav-left img { height:100px; }
.nav-links { margin-left:auto; margin-right:40px; display:flex; gap:30px; align-items:center; }
.nav-links a { font-size:30px; font-weight:700; color:white; text-decoration:none; }
.nav-right a img { height:55px; border-radius:50%; }

.payment-card {
    max-width:480px; margin:60px auto; background:white; padding:40px;
    border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.12); text-align:center;
}
.esewa-logo { width:180px; margin-bottom:25px; }
h3 { font-size:26px; color:#2f4f2f; margin-bottom:20px; }

.order-items {
    text-align:left; margin:25px 0; list-style:none; background:#f9f9f9;
    padding:15px; border-radius:12px;
}
.order-items li {
    padding:8px 0 8px 25px; position:relative; font-size:17px;
}
.order-items li:before {
    content:"•"; position:absolute; left:0; color:#2f4f2f; font-size:24px; top:4px;
}

.delivery-section {
    background:#e8f5e9; border-radius:12px; padding:20px; margin:30px 0;
    text-align:left; border-left:6px solid #1c733c;
}
.delivery-section strong { display:block; font-size:18px; color:#2f4f2f; margin-bottom:8px; }
.delivery-section p { margin:0; font-size:16px; color:#333; line-height:1.5; word-break:break-word; }

.amount { font-size:28px; font-weight:bold; color:#1c733c; margin:30px 0; }

.btn-pay {
    background:#1c733c; color:white; border:none; padding:18px 70px;
    border-radius:12px; font-size:21px; cursor:pointer; transition:0.3s;
}
.btn-pay:hover { background:#14522b; transform:translateY(-3px); }
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

<div class="payment-card">
    <img class="esewa-logo" src="esewa.png" alt="eSewa">

    <h3>Order Summary</h3>

    <ul class="order-items">
        <?php foreach ($items as $i): ?>
            <li>
                <?= htmlspecialchars($i['name']) ?> × <?= $i['qty'] ?>
                — Rs <?= number_format(($i['price'] ?? 0) * $i['qty'], 2) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="delivery-section">
        <strong>Delivery Address:</strong>
        <p><?= htmlspecialchars($location) ?></p>
    </div>

    <div class="amount">
        Total: Rs <?= $grandTotalStr ?>
    </div>

    <form action="<?= $esewa_url ?>" method="POST">
        <input type="hidden" name="amount"                 value="<?= $subTotalStr ?>">
        <input type="hidden" name="tax_amount"             value="<?= $taxAmountStr ?>">
        <input type="hidden" name="total_amount"           value="<?= $grandTotalStr ?>">
        <input type="hidden" name="transaction_uuid"       value="<?= $transaction_uuid ?>">
        <input type="hidden" name="product_code"           value="<?= $product_code ?>">
        <input type="hidden" name="product_service_charge" value="0">
        <input type="hidden" name="product_delivery_charge" value="0">
        <input type="hidden" name="success_url"            value="<?= $success_url ?>">
        <input type="hidden" name="failure_url"            value="<?= $failure_url ?>">
        <input type="hidden" name="signed_field_names"     value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature"              value="<?= $signature ?>">

        <button type="submit" class="btn-pay">Pay Now with eSewa</button>
    </form>
</div>

</body>
</html>