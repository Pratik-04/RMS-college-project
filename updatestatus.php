<?php
session_start();
include "database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Update status
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = trim($_POST['new_status']);
    $valid = ['In Process', 'Completed', 'Cancelled'];
    if (in_array($new_status, $valid)) {
        $stmt = $conn->prepare("UPDATE order_history SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: updatestatus.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM order_history ORDER BY id DESC");
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Orders</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f9f9f9; }

        /* YOUR EXACT ORIGINAL NAVBAR - NO CHANGE */
        .navbar {
            height: 90px;
            padding: 20px 50px;
            background: #2f4f2f;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
        }
        .nav-left img { height: 100px; }
        .nav-links {
            margin-left: auto;
            margin-right: 40px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .nav-links a {
            font-size: 30px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        .nav-right a {
            font-size: 20px;
            font-weight: 700;
            color: white;
            padding: 10px 20px;
            border-radius: 15px;
            background: #2f4f2f;
            text-decoration: none;
            transition: background 0.3s;
        }

        .container { width:90%; max-width:1100px; margin:40px auto; }
        h2 { text-align:center; font-size:38px; margin-bottom:40px; color:#2f4f2f; font-weight:700; }

        .order-card {
            background: white;
            padding: 28px;
            margin-bottom: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 30px;
            border-left: 8px solid #ffcc00;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .order-card img {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border-radius: 16px;
            border: 3px solid #fff;
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .details { flex: 1; }
        .order-id { font-size: 24px; font-weight: bold; color: #2f4f2f; margin-bottom: 8px; }
        .item-name { font-size: 28px; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        .info { font-size: 19px; color: #444; margin: 8px 0; }
        .time { font-size: 16px; color: #777; font-family: monospace; margin-top: 12px; }

        .status-select {
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            min-width: 160px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .status-inprocess { background:#ffcc00; color:#000; }
        .status-completed { background:#27ae60; color:white; }
        .status-cancelled { background:#e74c3c; color:white; }

        .view-btn {
            background: #2f4f2f;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 17px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .view-btn:hover { background: #3e5e3e; }

        .no-orders {
            text-align: center;
            font-size: 28px;
            color: #888;
            margin-top: 100px;
        }

        /* ONLY CHANGE: SMALLER MODAL - EXACTLY WHAT YOU ASKED */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 30px 25px;
            border-radius: 20px;
            max-width: 400px;     /* ← SMALLER */
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            animation: pop 0.35s ease;
        }
        @keyframes pop {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 40px;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #e74c3c; }

        .modal-img {
            width: 170px;        /* ← SMALLER IMAGE */
            height: 170px;
            object-fit: cover;
            border-radius: 16px;
            margin: 18px auto;
            display: block;
            border: 4px solid #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .modal-content h2 { font-size: 32px; color: #2f4f2f; margin: 8px 0; }
        .modal-content h3 { font-size: 26px; color: #2c3e50; margin: 15px 0 10px; }
        .modal-content p { font-size: 18px; margin: 10px 0; }
        #mStatus {
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 20px;
            display: inline-block;
            min-width: 160px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png" alt="logo"></a>
    </div>
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
       
       
        <a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%;"></a>
    </div>
</div>

<div class="container">
    <h2>Manage Orders</h2>

    <?php if ($orders->num_rows == 0): ?>
        <p class="no-orders">No orders found.</p>
    <?php else: ?>
        <?php while ($row = $orders->fetch_assoc()):
            $imgPath = !empty(trim($row['item_img'])) ? 'uploads/' . basename($row['item_img']) : 'food.png';
            $statusClass = strtolower(str_replace(' ', '', $row['status']));
            $statusClass = $statusClass === 'completed' ? 'status-completed' : ($statusClass === 'cancelled' ? 'status-cancelled' : 'status-inprocess');
        ?>
            <div class="order-card">
                <img src="<?= $imgPath ?>" alt="Food" onerror="this.src='food.png'">

                <div class="details">
                    <div class="order-id">#ORD-<?= sprintf('%04d', $row['id']) ?></div>
                    <div class="item-name"><?= htmlspecialchars($row['item_name']) ?> × <?= $row['quantity'] ?></div>
                    <div class="info"><strong>Total:</strong> Rs <?= number_format($row['total']) ?></div>
                    <div class="info"><strong>Location:</strong> <?= htmlspecialchars($row['location'] ?? 'N/A') ?></div>
                    <div class="time"><?= date("D, M d, Y - h:i A", strtotime($row['order_date'])) ?></div>
                </div>

                <div style="display:flex; gap:20px; align-items:center;">
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        <select name="new_status" class="status-select <?= $statusClass ?>" onchange="this.form.submit()">
                            <option value="In Process" <?= $row['status'] === 'In Process' ? 'selected' : '' ?>>In Process</option>
                            <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>

                    <button onclick='openModal(<?= json_encode([
                        "id" => $row['id'],
                        "user_id" => $row['user_id'],
                        "item_name" => $row['item_name'],
                        "quantity" => $row['quantity'],
                        "total" => $row['total'],
                        "location" => $row['location'] ?? 'N/A',
                        "order_date" => $row['order_date'],
                        "status" => $row['status'],
                        "item_img_path" => $imgPath
                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="view-btn">View Details</button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- SMALLER MODAL - ONLY CHANGE MADE -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        
        <h2 id="mId" style="color:#2f4f2f;"></h2>
        <p style="font-size:18px;"><strong>User ID:</strong> <span id="mUserId" style="color:#2f4f2f;font-weight:bold;"></span></p>
        
        <img id="mImg" class="modal-img" src="food.png" alt="Food">
        
        <h3 id="mName" style="margin:18px 0 12px;"></h3>
        
        <div style="background:#f8f9fa; padding:25px; border-radius:16px; margin:20px 0;">
            <p><strong>Quantity:</strong> <span id="mQty" style="color:#2f4f2f;font-weight:bold;"></span></p>
            <p><strong>Location:</strong> <span id="mLoc"></span></p>
            <p><strong>Total:</strong> Rs <span id="mTotal" style="color:#e67e22;font-weight:bold;"></span></p>
            <p><strong>Ordered:</strong> <span id="mTime"></span></p>
        </div>

        <p><strong>Status:</strong>
            <span id="mStatus"></span>
        </p>
    </div>
</div>

<script>
    let isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    document.getElementById('authArea').innerHTML = isLoggedIn ?
      `<a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%;"></a>` :
      `<a href="signin.php">Sign In</a><a href="signup.php" style="margin-left:15px;">Sign Up</a>`;

    function openModal(order) {
        document.getElementById('mId').textContent = '#ORD-' + String(order.id).padStart(4, '0');
        document.getElementById('mUserId').textContent = order.user_id;
        document.getElementById('mName').textContent = order.item_name + ' × ' + order.quantity;
        document.getElementById('mQty').textContent = order.quantity;
        document.getElementById('mLoc').textContent = order.location;
        document.getElementById('mTotal').textContent = order.total;
        document.getElementById('mTime').textContent = new Date(order.order_date).toLocaleString();

        const s = document.getElementById('mStatus');
        s.textContent = order.status;
        if (order.status === 'Completed') {
            s.style.background = '#27ae60'; s.style.color = 'white';
        } else if (order.status === 'Cancelled') {
            s.style.background = '#e74c3c'; s.style.color = 'white';
        } else {
            s.style.background = '#ffcc00'; s.style.color = 'black';
        }

        document.getElementById('mImg').src = order.item_img_path;
        document.getElementById('modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target === document.getElementById('modal')) closeModal();
    }
</script>

</body>
</html>

<?php
$orders->free();
$stmt->close();
$conn->close();
?>