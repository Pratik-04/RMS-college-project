<?php
session_start();
include "database.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user role and admin status
$stmt = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$is_admin = ($user['admin'] ?? 0) == 1;
$is_employee = ($user['role'] ?? '') === 'employee';

// Redirect if NOT admin AND NOT employee
if (!$is_admin && !$is_employee) {
    header("Location: index.php");
    exit();
}

// Add new table
$message = $error = "";
if (isset($_POST['add_table'])) {
    $table_number = trim($_POST['table_number']);
    $capacity = (int)$_POST['capacity'];

    if (empty($table_number)) {
        $error = "Table number is required.";
    } elseif ($capacity < 1) {
        $error = "Capacity must be at least 1.";
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO restaurant_tables (table_number, capacity) VALUES (?, ?)");
        $stmt->bind_param("si", $table_number, $capacity);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "Table '$table_number' added successfully!";
        } else {
            $error = "Table number already exists or invalid.";
        }
        $stmt->close();
    }
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'assign' && isset($_GET['table_id'])) {
        $table_id = (int)$_GET['table_id'];
        $stmt = $conn->prepare("UPDATE bookings SET table_id = ?, status = 'confirmed' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $table_id, $id);
        $stmt->execute();
        $stmt->close();
    } elseif (in_array($action, ['completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_bookings.php");
    exit();
}

// Pending count
$pending_res = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pending_count = $pending_res->fetch_row()[0];

// All bookings
$bookings = $conn->query("
    SELECT b.*, rt.table_number, rt.capacity
    FROM bookings b
    LEFT JOIN restaurant_tables rt ON b.table_id = rt.id
    ORDER BY b.booking_date DESC, b.booking_time DESC
");

// All tables for assignment and layout
$tables_all = $conn->query("SELECT id, table_number, capacity FROM restaurant_tables ORDER BY table_number");

// Current time for live status
$current_datetime = date('Y-m-d H:i:s');
$current_date = date('Y-m-d');
$current_time = date('H:i:00');

// Function to check if table is currently occupied (2-hour window)
function isTableOccupiedNow($table_id, $conn) {
    global $current_date, $current_time;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE table_id = ? 
          AND booking_date = ? 
          AND status IN ('confirmed','seated')
          AND (
            (booking_time <= ? AND ADDTIME(booking_time, '02:00:00') > ?) OR
            (booking_time < ADDTIME(?, '02:00:00') AND ADDTIME(booking_time, '02:00:00') > ?)
          )
    ");
    $stmt->bind_param("isssss", $table_id, $current_date, $current_time, $current_time, $current_time, $current_time);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Function to get ordered items
function getOrderItems($booking_id, $conn) {
    $stmt = $conn->prepare("
        SELECT mi.name, mi.price, to2.quantity, (mi.price * to2.quantity) as subtotal
        FROM table_orders to2
        JOIN menu_items mi ON to2.menu_item_id = mi.id
        WHERE to2.booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $items;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Booking Management</title>
<style>
   * { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; min-height: 100vh; overflow-x: hidden; }
    
.navbar {
    height: 90px;
    padding: 20px 50px;
    background: #2f4f2f;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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
.user-icon { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; }
.menu-toggle { display: none; font-size: 32px; cursor: pointer; }
.container { max-width: 1200px; margin: 130px auto 80px; padding: 20px; }
h2 { text-align: center; font-size: 38px; color: #2f4f2f; margin-bottom: 40px; font-weight: 700; }
.alert { padding:15px; margin:20px 0; border-radius:12px; text-align:center; font-weight:bold; font-size:18px; }
.alert-success { background:#d4edda; color:#155724; }
.alert-danger { background:#f8d7da; color:#721c24; }
    
.controls { background:white; padding:30px; border-radius:18px; box-shadow:0 8px 25px rgba(0,0,0,0.12); margin-bottom:40px; }
.controls form { display:flex; gap:20px; flex-wrap:wrap; justify-content:center; align-items:end; }
.controls label { font-weight:bold; font-size:18px; display:block; margin-bottom:8px; }
.controls input, .controls select { padding:15px; font-size:16px; border-radius:10px; border:1px solid #ccc; min-width:200px; }
.controls button { background:#ffcc00; border:none; padding:15px 30px; font-size:18px; font-weight:bold; border-radius:10px; cursor:pointer; }
.tables-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:30px; }
.table-card { background:white; padding:30px; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,0.15); text-align:center; transition:0.3s; }
.table-card.available { border-left:10px solid #27ae60; cursor:pointer; }
.table-card.available:hover { transform:translateY(-10px); }
.table-card.booked { border-left:10px solid #e74c3c; opacity:0.7; cursor:not-allowed; }
.table-card h3 { font-size:32px; margin-bottom:15px; color:#2f4f2f; }
.table-card .capacity { font-size:20px; color:#555; margin-bottom:20px; }
.status-text { font-size:22px; font-weight:bold; }
.available .status-text { color:#27ae60; }
.booked .status-text { color:#e74c3c; }

.modal { 
    display:none; 
    position:fixed; 
    inset:0; 
    background:rgba(0,0,0,0.8); 
    z-index:2000; 
    justify-content:center; 
    align-items:flex-start; 
    overflow-y:auto; 
    padding:20px 0;
}
.modal-content { 
    background:white; 
    width:90%; 
    max-width:900px; 
    border-radius:20px; 
    overflow:hidden; 
    max-height:95vh;
    display:flex;
    flex-direction:column;
}
.modal-header { 
    background:#2f4f2f; 
    color:white; 
    padding:25px; 
    text-align:center; 
    font-size:30px; 
    position:relative; 
    flex-shrink:0;
}
.close-btn { 
    position:absolute; 
    top:15px; 
    right:25px; 
    font-size:40px; 
    background:none; 
    border:none; 
    color:white; 
    cursor:pointer; 
}
.modal-body { 
    padding:40px; 
    overflow-y:auto; 
    flex-grow:1;
}
.user-info { 
    background:#f0f8f0; 
    padding:20px; 
    border-radius:12px; 
    margin-bottom:40px; 
    font-size:18px; 
}
.menu-list { max-width:700px; margin:0 auto; }
.menu-item-row { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:20px; 
    background:#f9f9f9; 
    border-radius:12px; 
    margin-bottom:15px; 
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
.menu-item-info { flex:1; }
.menu-item-name { font-size:22px; font-weight:bold; color:#2f4f2f; }
.menu-item-desc { font-size:15px; color:#666; margin-top:5px; }
.menu-item-price { font-size:24px; font-weight:bold; color:#e74c3c; margin-top:8px; }
.quantity-modal { display:flex; justify-content:center; align-items:center; gap:15px; }
.quantity-modal button { width:45px; height:45px; background:#ffcc00; border:none; border-radius:50%; font-size:26px; cursor:pointer; font-weight:bold; }
.quantity-modal input { width:80px; text-align:center; padding:10px; font-size:18px; border:1px solid #ccc; border-radius:8px; }
.place-order-btn { width:100%; background:#27ae60; color:white; padding:20px; border:none; border-radius:15px; font-size:24px; font-weight:bold; cursor:pointer; margin-top:40px; flex-shrink:0; }

    /* My Bookings Section */
.my-bookings {
    margin-top: 70px;
    background: white;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}
.my-bookings h2 {
    font-size: 32px;
    color: #2f4f2f;
    margin-bottom: 30px;
    text-align: center;
    font-weight: 700;
}
.booking-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 12px;
    overflow: hidden;
}
.booking-table th,
.booking-table td {
    padding: 16px 20px;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}
.booking-table th {
    background: #2f4f2f;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 15px;
}
.booking-table tr:last-child td {
    border-bottom: none;
}
.booking-table tr:hover {
    background-color: #f8fafc;
}
.status-pending    { color: #d97706; font-weight: 600; background: rgba(251,191,36,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; }
.status-confirmed  { color: #15803d; font-weight: 600; background: rgba(34,197,94,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; }
.status-seated     { color: #1e40af; font-weight: 600; background: rgba(59,130,246,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; }
.status-completed  { color: #4b5563; font-weight: 600; background: rgba(156,163,175,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; }
.status-cancelled  { color: #b91c1c; font-weight: 600; background: rgba(239,68,68,0.15); padding: 6px 12px; border-radius: 6px; display: inline-block; }

.btn-cancel-small {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-cancel-small:hover {
    background: #dc2626;
    transform: translateY(-1px);
}
.btn-cancel-small:active {
    transform: translateY(0);
}
.no-bookings {
    text-align: center;
    color: #6b7280;
    font-size: 18px;
    padding: 50px 20px;
    font-style: italic;
}

@media (max-width: 768px) {
    .navbar { padding: 15px 20px; height: 80px; }
    .nav-left img { height: 80px; }
    .nav-links { position: fixed; top: 80px; left: -100%; width: 100%; background: #2f4f2f; flex-direction: column; transition: 0.4s; padding-top: 30px; }
    .nav-links.active { left: 0; }
    .nav-links a { font-size: 26px; padding: 20px; }
    .menu-toggle { display: block; }
    .modal-body { padding:20px; }
    .menu-item-row { flex-direction:column; align-items:flex-start; }
    .quantity-modal { margin-top:15px; align-self:flex-end; }
    .booking-table th, .booking-table td { padding: 12px 14px; font-size: 14px; }
    .my-bookings { padding: 25px 18px; }
    .my-bookings h2 { font-size: 26px; }
}
.add-table-form {
    background: white;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    margin-bottom: 40px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.add-table-form input {
    padding: 15px;
    font-size: 16px;
    border-radius: 10px;
    border: 1px solid #ccc;
    min-width: 220px;
    flex: 1;
    max-width: 300px;
}

.add-table-form button {
    background: #ffcc00;
    border: none;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: bold;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
}

.add-table-form button:hover {
    background: #ffbb00;
}

/* Mobile adjustment for form */
@media (max-width: 768px) {
    .add-table-form {
        flex-direction: column;
        align-items: stretch;
    }
    .add-table-form input,
    .add-table-form button {
        width: 100%;
        max-width: none;
    }
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.25s ease;
    min-width: 100px;
    text-align: center;
}

/* Assign - blue */
.btn-assign {
    background: #3b82f6;
    color: white;
}

/* Complete - green */
.btn-complete {
    background: #10b981;
    color: white;
}

/* Cancel - red */
.btn-cancel {
    background: #ef4444;
    color: white;
}

/* Hover for all buttons */
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    opacity: 0.95;
}

/* Clicked state */
.btn:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Gap between buttons in Actions column */
.booking-table td:last-child {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;                /* equal gap on all sides between buttons */
    align-items: center;
}

/* Mobile adjustment for buttons */
@media (max-width: 768px) {
    .booking-table td:last-child {
        flex-direction: column;
        gap: 10px;
    }
    .btn {
        width: 100%;
        max-width: none;
    }
}
</style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png" alt="Logo"></a>
    </div>
    <div class="nav-links" id="navLinks">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
        <a href="account.php"><img src="user-icon.png" class="user-icon" alt="User"></a>
    </div>
    <div class="menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('active')">☰</div>
</div>

<div class="container">
    <h2>Admin Booking Management
        <?php if ($pending_count > 0): ?>
            <span class="pending-notification">(<?= $pending_count ?> Pending)</span>
        <?php endif; ?>
    </h2>

    <!-- Add New Table -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="add-table-form" method="POST">
        <input type="text" name="table_number" placeholder="Table Number (e.g. T6, VIP-3)" required>
        <input type="number" name="capacity" placeholder="Max Guests" min="1" required>
        <button type="submit" name="add_table">Add Table</button>
    </form>

    <!-- Current Table Layout (Live Status) -->
    <div class="table-layout">
        <h3>Current Restaurant Layout (Live Status)</h3>
        <div class="tables-grid">
            <?php 
            $tables_all->data_seek(0);
            while ($table = $tables_all->fetch_assoc()): 
                $occupied = isTableOccupiedNow($table['id'], $conn);
                $class = $occupied ? 'occupied' : 'available';
                $status = $occupied ? 'OCCUPIED' : 'AVAILABLE';
            ?>
                <div class="table-card <?= $class ?>">
                    <h3><?= htmlspecialchars($table['table_number']) ?></h3>
                    <div class="capacity">Capacity: <?= $table['capacity'] ?> guests</div>
                    <div class="table-status"><?= $status ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Bookings List -->
    <h3 style="margin-top:60px;">All Bookings</h3>
    <?php if ($bookings->num_rows == 0): ?>
        <p style="text-align:center; font-size:28px; color:#888; margin-top:100px;">No bookings yet.</p>
    <?php else: ?>
        <table class="booking-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                    <th>Guests</th>
                    <th>Table</th>
                    <th>Status</th>
                    <th>Requests</th>
                    <th>Food Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $bookings->fetch_assoc()): 
                    $items = getOrderItems($row['id'], $conn);
                    $total = array_sum(array_column($items, 'subtotal'));
                ?>
                <tr>
                    <td>#BK-<?= sprintf('%04d', $row['id']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['customer_name']) ?></strong><br>
                        <?= htmlspecialchars($row['customer_phone'] ?? '-') ?>
                    </td>
                    <td>
                        <?= date('D, M d, Y', strtotime($row['booking_date'])) ?><br>
                        <?= date('h:i A', strtotime($row['booking_time'])) ?>
                    </td>
                    <td><?= $row['party_size'] ?></td>
                    <td>
                        <?= htmlspecialchars($row['table_number'] ?? 'Not Assigned') ?>
                        <?php if ($row['table_number']): ?>
                            <br><small>(<?= $row['capacity'] ?> seats)</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="status status-<?= strtolower($row['status']) ?>"><?= strtoupper($row['status']) ?></span></td>
                    <td><?= htmlspecialchars($row['special_requests'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($items)): ?>
                            <div class="order-list">
                                <?php foreach ($items as $item): ?>
                                    <div class="order-item">
                                        <?= $item['quantity'] ?> × <?= htmlspecialchars($item['name']) ?><br>
                                        <small>Rs<?= number_format($item['subtotal'], 2) ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <div class="order-total">Total: Rs<?= number_format($total, 2) ?></div>
                            </div>
                        <?php else: ?>
                            No food ordered
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="GET" style="display:inline;">
                                <select name="table_id" required style="padding:10px; margin:5px 0; border-radius:8px;">
                                    <option value="">Assign Table</option>
                                    <?php 
                                    $tables_all->data_seek(0);
                                    while ($t = $tables_all->fetch_assoc()): 
                                        if ($t['capacity'] >= $row['party_size']):
                                    ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['table_number']) ?> (<?= $t['capacity'] ?> seats)</option>
                                    <?php endif; endwhile; ?>
                                </select>
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-assign">Assign</button>
                            </form>
                            <a href="?action=cancelled&id=<?= $row['id'] ?>" class="btn btn-cancel" onclick="return confirm('Cancel this booking?')">Cancel</a>
                        <?php elseif (in_array($row['status'], ['confirmed','seated'])): ?>
                            <a href="?action=completed&id=<?= $row['id'] ?>" class="btn btn-complete" onclick="return confirm('Mark as completed?')">Complete</a>
                            <a href="?action=cancelled&id=<?= $row['id'] ?>" class="btn btn-cancel" onclick="return confirm('Cancel this booking?')">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>