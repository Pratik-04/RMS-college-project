<?php
session_start();
include "database.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// === REDIRECT EMPLOYEES AND ADMINS TO ADMIN PANEL ===
$stmt_check = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($row_check = $result_check->fetch_assoc()) {
    $role = $row_check['role'];
    $is_admin = $row_check['admin'];

    if ($role === 'employee' || $is_admin == 1) {
        header("Location: admin.php");
        exit;
    }
} else {
    // Invalid user - force logout
    session_destroy();
    header("Location: signin.php");
    exit;
}
$stmt_check->close();
// === END REDIRECT LOGIC ===

// Cancel order
if (isset($_POST['cancel_order'])) {
    $order_id = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE order_history SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'In Process'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: history.php");
    exit;
}

// Fetch user's orders
$stmt = $conn->prepare("SELECT * FROM order_history WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }

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
        .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-right a {
            font-size: 20px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 15px;
            background: #2f4f2f;
            transition: background 0.3s;
        }
        .nav-right a img { vertical-align: middle; }

        .container { width:90%; max-width:1000px; margin:40px auto; }
        h2 { text-align:center; font-size:36px; margin-bottom:30px; color:#2f4f2f; }

        .order-card {
            background:white; padding:25px; margin-bottom:20px; border-radius:15px;
            box-shadow:0 4px 12px rgba(0,0,0,0.1); display:flex; align-items:center; gap:25px;
            border-left:6px solid #ffcc00;
        }
        .order-card img { width:110px; height:110px; object-fit:cover; border-radius:12px; }
        .details { flex:1; }
        .order-id { font-size:18px; font-weight:bold; color:#2f4f2f; }
        .name { font-size:26px; font-weight:bold; color:#2f4f2f; margin:8px 0; }
        .info { font-size:18px; color:#444; margin:5px 0; }
        .time { font-size:14px; color:#666; font-family:monospace; }

        .status-badge { padding:12px 20px; border-radius:25px; font-weight:bold; font-size:17px; }
        .status-inprocess { background:#ffcc00; color:black; }
        .status-completed { background:#27ae60; color:white; }
        .status-cancelled { background:#e74c3c; color:white; }

        .btn-group { display:flex; gap:15px; align-items:center; }
        .cancel-btn, .view-btn {
            padding:12px 20px; border:none; border-radius:30px; font-weight:bold;
            font-size:16px; cursor:pointer; text-decoration:none; text-align:center;
        }
        .cancel-btn { background:#e74c3c; color:white; }
        .cancel-btn:hover { background:#c0392b; }
        .view-btn { background:#2f4f2f; color:white; }
        .view-btn:hover { background:#3e5e3e; }

        .no-orders { text-align:center; font-size:26px; color:#888; margin-top:80px; }

        .modal {
            display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%;
            background:rgba(0,0,0,0.8); justify-content:center; align-items:center;
        }
        .modal-content {
            background:white; padding:40px; border-radius:20px; max-width:550px; width:90%;
            text-align:center; box-shadow:0 15px 40px rgba(0,0,0,0.3); position:relative;
        }
        .close { position:absolute; top:15px; right:20px; font-size:42px; cursor:pointer; color:#aaa; }
        .close:hover { color:#000; }
        .modal-img { width:200px; height:200px; object-fit:cover; border-radius:18px; margin:25px auto; display:block; box-shadow:0 8px 20px rgba(0,0,0,0.15); }
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
        <a href="mycart.php"><img src="cart.png" style="height:40px"></a>
        <a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%;"></a>
    </div>
</div>

<div class="container">
    <h2>My Order History</h2>

    <?php if ($orders->num_rows == 0): ?>
        <p class="no-orders">No orders found.</p>
    <?php else: while ($row = $orders->fetch_assoc()): 
        $imgPath = getImagePath($row['item_img'] ?? '');
        $status = strtolower(str_replace(' ', '', $row['status']));

        // FIXED: Replaced chained ternary with if/elseif (PHP 8+ compatible)
        if ($status == 'completed') {
            $badgeClass = 'status-completed';
        } elseif ($status == 'cancelled') {
            $badgeClass = 'status-cancelled';
        } else {
            $badgeClass = 'status-inprocess';
        }

        // Improved location display - avoid showing "0"
        $display_location = ($row['location'] && $row['location'] !== '0' && trim($row['location']) !== '')
            ? htmlspecialchars($row['location'])
            : 'Not provided';
    ?>
        <div class="order-card">
            <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($row['item_name']) ?>" onerror="this.src='food.png'">

            <div class="details">
                <div class="order-id">#ORD-<?= sprintf('%04d', $row['id']) ?></div>
                <div class="name"><?= (int)$row['quantity'] ?> × <?= htmlspecialchars($row['item_name']) ?></div>
                <div class="info">Total: Rs <?= number_format($row['total']) ?></div>
                <div class="info">Location: <?= $display_location ?></div>
                <div class="time">Time: <?= date("D, M d, Y - h:i A", strtotime($row['order_date'])) ?></div>
            </div>

            <span class="status-badge <?= $badgeClass ?>"><?= ucwords($row['status']) ?></span>

            <div class="btn-group">
                <?php if ($status === 'inprocess'): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="cancel_order" class="cancel-btn">Cancel Order</button>
                    </form>
                <?php endif; ?>
                
                <button onclick='openModal(<?= json_encode([
                    'id' => $row['id'],
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'total' => $row['total'],
                    'location' => $display_location,
                    'order_date' => $row['order_date'],
                    'status' => $row['status'],
                    'item_img_path' => $imgPath
                ], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="view-btn">View Details</button>
            </div>
        </div>
    <?php endwhile; endif; ?>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        
        <h2 id="mId" style="color:#2f4f2f; margin-bottom:15px; font-size:32px;"></h2>
        <img id="mImg" class="modal-img" src="food.png" alt="Food Item">
        
        <h3 id="mName" style="margin:20px 0 10px; font-size:28px; color:#2f4f2f;"></h3>
        
        <div style="background:#f8f9fa; padding:25px; border-radius:18px; margin:25px 0;">
            <p style="margin:12px 0; font-size:19px;"><strong>Quantity:</strong> <span id="mQty" style="font-weight:bold; color:#2f4f2f;"></span></p>
            <p style="margin:12px 0; font-size:19px;"><strong>Location:</strong> <span id="mLoc" style="color:#444;"></span></p>
            <p style="margin:12px 0; font-size:19px;"><strong>Total Amount:</strong> Rs <span id="mTotal" style="font-weight:bold; color:#e67e22; font-size:22px;"></span></p>
            <p style="margin:12px 0; font-size:19px;"><strong>Ordered On:</strong> <span id="mTime" style="color:#444;"></span></p>
        </div>

        <p style="margin-top:25px;">
            <strong style="font-size:20px;">Status: </strong>
            <span id="mStatus" style="padding:14px 35px; border-radius:35px; font-weight:bold; font-size:22px; display:inline-block; min-width:160px;"></span>
        </p>
    </div>
</div>

<script>
function openModal(order) {
    document.getElementById('mId').textContent = '#ORD-' + ('0000' + order.id).slice(-4);
    document.getElementById('mName').textContent = order.item_name;
    document.getElementById('mQty').textContent = order.quantity;
    document.getElementById('mLoc').textContent = order.location;
    document.getElementById('mTotal').textContent = order.total;
    document.getElementById('mTime').textContent = new Date(order.order_date).toLocaleString();

    const s = document.getElementById('mStatus');
    s.textContent = order.status;
    if (order.status.includes('Completed')) {
        s.style.background = '#27ae60'; s.style.color = 'white';
    } else if (order.status.includes('Cancelled')) {
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

// Close modal when clicking outside
document.getElementById('modal').onclick = e => {
    if (e.target === document.getElementById('modal')) closeModal();
};
</script>

</body>
</html>

<?php
function getImagePath($dbImage) {
    $img = trim($dbImage ?? '');
    if (empty($img) || strlen($img) < 3 || in_array($img, ['a', 'uploads/', 'food.png'])) {
        return 'food.png';
    }
    return strpos($img, 'Uploads/') === 0 ? $img : 'Uploads/' . basename($img);
}

$orders->free();
$stmt->close();
$conn->close();
?>