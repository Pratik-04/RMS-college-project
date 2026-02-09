<?php
session_start();
include "database.php";

// Must be logged in to add to cart / order (adjust if you want to allow guest cart)
if (!isset($_SESSION['user_id'])) {
    $showLoginMessage = true;
} else {
    $showLoginMessage = false;
    $user_id = $_SESSION['user_id'];
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function addToCart($name, $price, $img, $qty) {
    $key = md5($name . $price);
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'name'  => $name,
            'price' => (float)$price,
            'img'   => $img,
            'qty'   => $qty
        ];
    }
}

// Fetch item
$id = $_GET['id'] ?? 0;
$item = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$item) {
    die("<h2 style='text-align:center;margin-top:50px;color:red;'>Item not found!</h2>");
}

// Determine category badge
$category = trim($item['category'] ?? 'Non-Veg');
$cat_lower = strtolower($category);
if (str_contains($cat_lower, 'veg') && !str_contains($cat_lower, 'non')) {
    $badge_class = 'veg-badge';
    $badge_text  = 'Veg';
} else {
    $badge_class = 'nonveg-badge';
    $badge_text  = 'Non-Veg';
}

// Handle Add to Cart
$orderMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if ($showLoginMessage) {
        header("Location: signin.php");
        exit;
    }

    $qty = (int)($_POST['quantity'] ?? 0);
    if ($qty > 0) {
        addToCart($item['name'], $item['price'], $item['image'], $qty);
        
        $_SESSION['cart_success'] = "Added '{$item['name']}' (×$qty) to cart!";
        header("Location: mycart.php");
        exit;
    } else {
        $orderMessage = "Please select a valid quantity!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($item['name']) ?> - RestroDash</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #f5f5f5; }
  .navbar { height: 90px; padding: 20px 50px; background: #2f4f2f; color: white; display: flex; justify-content: space-between; align-items: center; }
  .nav-left img { height: 100px; }
  .nav-links { margin-left: auto; margin-right: 40px; display: flex; align-items: center; gap: 30px; }
  .nav-links a { font-size: 30px; font-weight: 700; color: white; text-decoration: none; }
  .nav-right { display: flex; align-items: center; gap: 15px; }
  .nav-right img.user-icon { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
  .container { width: 90%; max-width: 500px; margin: 40px auto; background: white; padding: 35px; border-radius: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
  h2 { font-size: 32px; margin-bottom: 8px; color: #2f4f2f; }
  .category-badge {
      display: inline-block;
      padding: 5px 14px;
      border-radius: 12px;
      font-size: 15px;
      font-weight: bold;
      margin-bottom: 12px;
  }
  .veg-badge    { background: #27ae60; color: white; }
  .nonveg-badge { background: #c0392b; color: white; }
  .item-img { width: 180px; height: 180px; object-fit: contain; margin: 15px 0; border-radius: 12px; float: left; margin-right: 20px; }
  .price-line { font-size: 24px; font-weight: 700; margin: 20px 0; clear: both; color: #2f4f2f; }
  .description { font-size: 18px; line-height: 1.6; color: #444; margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #2f4f2f; border-radius: 8px; }
  #totalPrice { font-size: 28px; font-weight: bold; color: #2f4f2f; margin: 25px 0; padding-top: 10px; border-top: 2px dashed #ccc; }
  label { font-size: 20px; font-weight: 600; display: block; margin-top: 20px; }
  .quantity-container { display: flex; align-items: center; justify-content: center; gap: 30px; margin-top: 10px; }
  .qty-btn { width: 70px; height: 70px; background: #2f4f2f; color: white; border: none; border-radius: 50%; font-size: 40px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; user-select: none; }
  .qty-btn:hover { background: #3e5e3e; }
  #quantityDisplay { font-size: 36px; font-weight: bold; min-width: 100px; text-align: center; }
  button#add-to-cart-btn { margin-top: 25px; padding: 16px 40px; background: #ffcc00; color: black; border: none; border-radius: 30px; font-size: 24px; width: 100%; cursor: pointer; transition: background 0.3s; }
  button#add-to-cart-btn:hover { background: #ffb800; }
  .error-message { padding: 15px; margin: 20px 0; border-radius: 10px; text-align: center; font-weight: bold; font-size: 18px; background: #f8d7da; color: #721c24; }
  .login-message { text-align: center; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin: 40px auto; max-width: 500px; }
  .login-btn { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #2f4f2f; color: white; text-decoration: none; border-radius: 30px; font-size: 20px; }
</style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png"></a>
    </div>
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right" id="authArea"></div>
</div>

<div class="container">
    <?php if ($showLoginMessage): ?>
        <div class="login-message">
            <h3>Please Login First</h3>
            <p>You need to be signed in to add items to your cart.</p>
            <a href="signin.php" class="login-btn">Go to Sign In</a>
        </div>
    <?php else: ?>
        <?php if ($orderMessage): ?>
            <div class="error-message"><?= htmlspecialchars($orderMessage) ?></div>
        <?php endif; ?>

        <h2><?= htmlspecialchars($item['name']) ?></h2>
        <span class="category-badge <?= $badge_class ?>"><?= $badge_text ?></span>

        <img class="item-img" src="uploads/<?= htmlspecialchars($item['image']) ?>" onerror="this.src='food.png'">

        <div class="price-line">
            Price: Rs <?= number_format($item['price'], 2) ?>
        </div>

        <div class="description">
            <?= nl2br(htmlspecialchars($item['description'] ?? 'No description available.')) ?>
        </div>

        <label>Quantity</label>
        <div class="quantity-container">
            <div class="qty-btn" id="decrease">-</div>
            <span id="quantityDisplay">1</span>
            <div class="qty-btn" id="increase">+</div>
        </div>

        <div id="totalPrice">Total: Rs <?= number_format($item['price'], 2) ?></div>

        <!-- Only Add to Cart form remains -->
        <form method="post">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="quantity" id="cart-qty" value="1">
            <button type="submit" id="add-to-cart-btn">Add to Cart</button>
        </form>

    <?php endif; ?>
</div>

<script>
let isLoggedIn = <?= $showLoginMessage ? 'false' : 'true' ?>;
document.getElementById('authArea').innerHTML = isLoggedIn ?
    `<a href="account.php"><img class="user-icon" src="user-icon.png" alt="Account"></a>` :
    `<a href="signin.php">Sign In</a><a href="signup.php">Sign Up</a>`;

let quantity = 1;
const unitPrice = <?= $item['price'] ?? 0 ?>;

function updateTotal() {
    document.getElementById("totalPrice").textContent = 
        "Total: Rs " + (unitPrice * quantity).toFixed(2);
    document.getElementById("cart-qty").value = quantity;
    document.getElementById("quantityDisplay").textContent = quantity;
}

document.getElementById("increase").onclick = () => {
    if (quantity < 15) {
        quantity++;
        updateTotal();
    }
};

document.getElementById("decrease").onclick = () => {
    if (quantity > 1) {
        quantity--;
        updateTotal();
    }
};

updateTotal();
</script>
</body>
</html>