<?php
session_start();
include "database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header("Location: mycart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart    = $_SESSION['cart'];
$message = "";

// ---- SAFE TOTAL CALCULATION ----
$grandTotal = 0;
foreach ($cart as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $qty   = isset($item['qty']) ? (int)$item['qty'] : 1;
    $grandTotal += $price * $qty;
}

// ---- FORM SUBMIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_checkout'])) {
    $city   = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');

    // Normalize city
    $city = preg_replace('/\s+/', ' ', $city);
    $city = ucfirst(strtolower($city));

    if (!in_array($city, ['Kathmandu', 'Lalitpur', 'Bhaktapur'])) {
        $message = "Please select a valid city/area!";
    } elseif (strlen($street) < 5) {
        $message = "Please enter your street / detailed location (at least 5 characters)!";
    } else {
        $full_location = $street . ', ' . $city;

        $_SESSION['pending_checkout'] = [
            'cart'        => $cart,
            'location'    => htmlspecialchars($full_location),
            'grand_total' => $grandTotal
        ];

        header("Location: payment.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - RestroDash</title>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    min-height: 100vh;
}

.navbar {
    height: 90px;
    padding: 20px 50px;
    background: #2f4f2f;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-left img { height: 100px; }

.nav-links {
    margin-left: auto;
    margin-right: 40px;
    display: flex;
    gap: 30px;
    align-items: center;
}

.nav-links a {
    font-size: 30px;
    font-weight: 700;
    color: white;
    text-decoration: none;
}

.user-icon {
    height: 55px;
    width: 55px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid transparent;
}

.container {
    background: white;
    max-width: 1000px;
    margin: 40px auto;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

h2 {
    font-size: 32px;
    color: #2f4f2f;
    text-align: center;
    margin-bottom: 30px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.order-item img {
    width: 90px;
    height: 90px;
    border-radius: 12px;
    object-fit: cover;
    margin-right: 20px;
    border: 2px solid #ddd;
}

.item-details h3 {
    font-size: 22px;
    margin-bottom: 8px;
}

.item-details p {
    color: #555;
    font-size: 18px;
}

.item-price {
    margin-left: auto;
    font-size: 22px;
    font-weight: bold;
    color: #2f4f2f;
}

.total-box {
    text-align: right;
    font-size: 28px;
    font-weight: bold;
    color: #2f4f2f;
    margin: 30px 0 20px;
}

label {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    display: block;
    margin: 15px 0 8px;
}

input[type="text"], select {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    border: 1px solid #999;
    border-radius: 10px;
}

select {
    appearance: none;
    background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="gray"><polygon points="0,0 12,0 6,12"/></svg>') no-repeat right 15px center;
    background-size: 12px;
}

.btn {
    display: block;
    width: 100%;
    padding: 18px;
    background: #0b6e3a;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 22px;
    cursor: pointer;
    margin-top: 25px;
    text-align: center;
}

.btn:hover { background: #094d2a; }

.error {
    background: #ffe6e6;
    color: #c00;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
    font-size: 18px;
}
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
        <a href="account.php">
            <img src="user-icon.png" class="user-icon" alt="User">
        </a>
    </div>
</div>

<div class="container">

    <h2>Checkout (<?= count($cart) ?> items)</h2>

    <?php if ($message): ?>
    <div class="error">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php foreach ($cart as $item): ?>
    <?php
    // Use the EXACT same image key as stored in addToCart (it's 'img')
    $image = $item['img'] ?? 'no-image.jpg';

    // Safe path check + fallback
    $image_path = file_exists("uploads/" . $image) ? "uploads/" . $image : "uploads/no-image.jpg";
    ?>
    <div class="order-item">
        <img src="<?= htmlspecialchars($image_path) ?>"
             onerror="this.src='uploads/no-image.jpg'; this.onerror=null;"
             alt="<?= htmlspecialchars($item['name']) ?>">
        <div class="item-details">
            <h3><?= htmlspecialchars($item['name']) ?></h3>
            <p>
                Rs <?= number_format($item['price']) ?>
                × <?= $item['qty'] ?>
            </p>
        </div>
        <div class="item-price">
            Rs <?= number_format($item['price'] * $item['qty']) ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="total-box">
        Grand Total: Rs <?= number_format($grandTotal) ?>
    </div>

    <form method="post" id="checkoutForm">
        <input type="hidden" name="proceed_checkout" value="1">

        <label>City / Area</label>
        <select name="city" id="citySelect" required>
              <option value="" disabled selected>Select your city</option>
                <option value="Kathmandu">Kathmandu</option>
                <option value="Lalitpur">Lalitpur</option>
                <option value="Bhaktapur">Bhaktapur</option>
        </select>

        <label>Street / Tole / Detailed Location</label>
        <input type="text" name="street" id="streetInput"
               value="<?= htmlspecialchars($_POST['street'] ?? '') ?>"
               required
               placeholder="e.g. Lakeside-6, near Barahi Temple, house no. 123">

        <button type="submit" class="btn">
            Proceed to Payment
        </button>
    </form>

</div>

<script>
// Client-side validation
document.getElementById("checkoutForm").addEventListener('submit', function(e) {
    const city   = document.getElementById("citySelect").value.trim();
    const street = document.getElementById("streetInput").value.trim();

    if (!city) {
        alert("Please select your city / area");
        e.preventDefault();
        return;
    }

    if (street.length < 5) {
        alert("Please enter your street / detailed location (at least 5 characters)");
        e.preventDefault();
        return;
    }
});
</script>

</body>
</html>