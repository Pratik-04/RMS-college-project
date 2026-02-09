<?php
session_start();
include "database.php";

// Create cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Remove item from cart (AJAX)
if (isset($_POST['remove_item'])) {
    $key = $_POST['key'];
    unset($_SESSION['cart'][$key]);

    // Recalculate totals
    $grandTotal = 0;
    $totalItems = 0;
    foreach ($_SESSION['cart'] as $item) {
        $grandTotal += $item['price'] * $item['qty'];
        $totalItems += $item['qty'];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'grandTotal' => number_format($grandTotal),
        'totalItems' => $totalItems
    ]);
    exit;
}

// Update quantity (AJAX)
if (isset($_POST['update_qty'])) {
    $key = $_POST['key'];
    $qty = (int)$_POST['qty'];

    $currentTotalItems = 0;
    foreach ($_SESSION['cart'] as $k => $item) {
        if ($k !== $key) {
            $currentTotalItems += $item['qty'];
        }
    }
    $currentTotalItems += $qty; // New total after change

    if ($currentTotalItems > 50) {
        // Reject if exceeds 50 total items
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Maximum 50 items allowed in cart!']);
        exit;
    }

    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } elseif ($qty > 15) {
        $qty = 15;
    }

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }

    // Recalculate
    $grandTotal = 0;
    $totalItems = 0;
    foreach ($_SESSION['cart'] as $item) {
        $grandTotal += $item['price'] * $item['qty'];
        $totalItems += $item['qty'];
    }

    $itemTotal = $_SESSION['cart'][$key]['price'] * $qty ?? 0;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'grandTotal' => number_format($grandTotal),
        'totalItems' => $totalItems,
        'itemTotal' => number_format($itemTotal),
        'currentQty' => $qty
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - RestroDash</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; min-height: 100vh; }
        .navbar { height: 90px; padding: 20px 50px; background: #2f4f2f; color: white; display: flex; justify-content: space-between; align-items: center; }
        .nav-left img { height: 100px; }
        .nav-links { margin-left: auto; margin-right: 40px; display: flex; gap: 30px; }
        .nav-links a { font-size: 30px; font-weight: 700; color: white; text-decoration: none; }
        .user-icon { height: 55px; width: 55px; border-radius: 50%; object-fit: cover; border: 3px solid transparent; }
        .container { background: white; max-width: 1000px; margin: 40px auto; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h2 { font-size: 32px; color: #2f4f2f; text-align: center; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 15px; text-align: center; font-size: 18px; border-bottom: 1px solid #ddd; }
        th { background: #2f4f2f; color: white; font-size: 20px; }
        .food-img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 2px solid #eee; }

        .qty-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .qty-btn {
            width: 40px;
            height: 40px;
            background: #2f4f2f;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .qty-btn:hover:not(:disabled) { background: #3e5e3e; }
        .qty-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .qty-display {
            min-width: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }

        .remove-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
        }
        .remove-btn:hover { background: #c0392b; }

        .btn {
            padding: 15px 40px;
            background: #0b6e3a;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 22px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #094d2a; }

        .total-box {
            text-align: right;
            font-size: 28px;
            font-weight: bold;
            color: #2f4f2f;
            margin: 20px 0;
        }
        .total-items {
            text-align: right;
            font-size: 20px;
            color: <?= $totalItems >= 50 ? '#e74c3c' : '#555' ?>;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .limit-warning {
            text-align: center;
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        .browse-lower { margin-top: 50px; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="nav-left"><a href="index.php"><img src="logo.png" alt="Logo"></a></div>
        <div class="nav-links">
            <a href="index.php">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="history.php">HISTORY</a>
            <a href="contact.php">CONTACT</a>
            <a href="book-table.php">BOOK TABLE</a>
        </div>
        <div class="nav-right">
            <a href="account.php"><img src="user-icon.png" class="user-icon" alt="User"></a>
        </div>
    </div>

    <div class="container">
        <h2>My Cart (<?= count($_SESSION['cart']) ?> items)</h2>

        <?php if (empty($_SESSION['cart'])): ?>
            <p style="text-align:center; font-size:22px; color:#777; margin:50px 0;">
                Your cart is empty!
            </p>
            <center class="browse-lower">
                <a href="menu.php" class="btn">Browse Menu</a>
            </center>

        <?php else: ?>
            <?php 
            $grandTotal = 0;
            $totalItems = 0;
            foreach ($_SESSION['cart'] as $item) {
                $grandTotal += $item['price'] * $item['qty'];
                $totalItems += $item['qty'];
            }
            $isMaxReached = $totalItems >= 50;
            ?>

            <div class="total-items">
                Total Items in Cart: <?= $totalItems ?> / 50
                <?php if ($isMaxReached): ?>
                    <span class="limit-warning">— Maximum limit reached!</span>
                <?php endif; ?>
            </div>

            <?php if ($isMaxReached): ?>
                <div class="limit-warning">
                    You have reached the maximum of 50 items. Remove some to add more.
                </div>
            <?php endif; ?>

            <table>
                <tr>
                    <th>Image</th>
                    <th>Food Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($_SESSION['cart'] as $key => $item): 
                    $itemTotal = $item['price'] * $item['qty'];
                ?>
                <tr>
                    <td>
                        <img src="uploads/<?= htmlspecialchars($item['img']) ?>" 
                             class="food-img" 
                             alt="<?= htmlspecialchars($item['name']) ?>"
                             onerror="this.src='uploads/no-image.jpg';">
                    </td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>Rs <?= number_format($item['price']) ?></td>

                    <td>
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" onclick="changeQty('<?= $key ?>', -1)">-</button>
                            <span class="qty-display" id="display-<?= $key ?>"><?= $item['qty'] ?></span>
                            <button type="button" class="qty-btn" id="plus-<?= $key ?>"
                                <?= ($item['qty'] >= 15 || $isMaxReached) ? 'disabled' : '' ?>
                                onclick="changeQty('<?= $key ?>', 1)">
                                +
                            </button>
                        </div>
                    </td>

                    <td id="item-total-<?= $key ?>">Rs <?= number_format($itemTotal) ?></td>

                    <td>
                        <button type="button" class="remove-btn" onclick="removeItem('<?= $key ?>')">
                            Remove
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="total-box">
                Grand Total: Rs <span id="grand-total"><?= number_format($grandTotal) ?></span>
            </div>

            <center>
                <a href="checkout.php" class="btn">Proceed to Checkout</a>
            </center>

        <?php endif; ?>
    </div>

    <script>
        const MAX_PER_ITEM = 15;
        const MAX_TOTAL_ITEMS = 50;

        function changeQty(key, change) {
            const display = document.getElementById('display-' + key);
            const plusBtn = document.getElementById('plus-' + key);
            let qty = parseInt(display.textContent);

            const newQty = qty + change;

            // Prevent going below 1
            if (newQty < 1) return;

            // Prevent exceeding per-item or total limit
            if (newQty > MAX_PER_ITEM) {
                alert("Maximum 15 per item!");
                return;
            }

            // Send AJAX request
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'update_qty=1&key=' + encodeURIComponent(key) + '&qty=' + newQty
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    display.textContent = data.currentQty;
                    document.getElementById('item-total-' + key).textContent = 
                        'Rs ' + data.itemTotal;

                    document.getElementById('grand-total').textContent = data.grandTotal;
                    document.querySelector('.total-items').innerHTML = 
                        'Total Items in Cart: ' + data.totalItems + ' / 50' +
                        (data.totalItems >= 50 ? ' <span class="limit-warning">— Maximum limit reached!</span>' : '');

                    // Disable all + buttons if total limit reached
                    const allPlusBtns = document.querySelectorAll('[id^="plus-"]');
                    const atMax = data.totalItems >= MAX_TOTAL_ITEMS;
                    allPlusBtns.forEach(btn => {
                        btn.disabled = atMax || (parseInt(btn.parentElement.querySelector('.qty-display').textContent) >= MAX_PER_ITEM);
                    });

                    if (atMax && change > 0) {
                        document.querySelector('.limit-warning').style.display = 'inline';
                    }
                } else {
                    alert(data.message || 'Cannot add more items!');
                }
            })
            .catch(() => alert('Error updating cart.'));
        }

        function removeItem(key) {
            if (!confirm('Remove this item from cart?')) return;

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'remove_item=1&key=' + encodeURIComponent(key)
            })
            .then(() => location.reload());
        }

        // Initial disable check on load
        window.onload = function() {
            const totalItems = <?= $totalItems ?>;
            if (totalItems >= 50) {
                document.querySelectorAll('[id^="plus-"]').forEach(btn => btn.disabled = true);
            }
        };
    </script>

</body>
</html>