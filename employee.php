<?php
session_start();
include "database.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details + role check
$stmt = $conn->prepare("SELECT name, phone, role, admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$is_admin = ($user['admin'] ?? 0) == 1;
$is_employee = ($user['role'] ?? '') === 'employee';

// If admin OR employee → show Employee Dashboard
if ($is_admin || $is_employee) {
    // Fetch all possible employee details (safe columns only)
    $employees_result = $conn->query("SELECT name, phone, duty_time, email, address FROM users WHERE role = 'employee'");
    $employees = $employees_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Dashboard</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; min-height: 100vh; }
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
    .container { max-width: 1200px; margin: 130px auto 60px; padding: 20px; }
    .employee-section { margin: 30px 50px; }
    .employee-section h2 { margin-bottom: 40px; font-size: 38px; text-align: center; color: #2f4f2f; font-weight: 700; }
    .employee-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .employee-card {
        background: white; padding: 25px; border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; transition: 0.2s;
    }
    .employee-card:hover { transform: scale(1.03); }
    .employee-card img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
    .employee-card h3 { font-size: 22px; margin-bottom: 10px; color: #2f4f2f; }
    .employee-card p { font-size: 16px; margin-bottom: 5px; color: #555; }
    .employee-card .btn {
        margin-top: 15px; background: #27ae60; color: white; padding: 12px 18px;
        border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;
    }
    .employee-card .btn:hover { background: #219653; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; padding: 20px; }
    .modal-content { background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; }
    .modal-content img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; }
    .close { position: absolute; top: 20px; right: 20px; font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer; }
    .close:hover { color: #000; }
    .modal-content h3 { font-size: 28px; margin-bottom: 15px; color: #2f4f2f; }
    .modal-content p { font-size: 18px; margin-bottom: 12px; color: #444; }

    @media (max-width: 768px) {
        .navbar { padding: 15px 20px; height: 80px; }
        .nav-left img { height: 80px; }
        .nav-links { position: fixed; top: 80px; left: -100%; width: 100%; background: #2f4f2f; flex-direction: column; transition: 0.4s; padding-top: 30px; }
        .nav-links.active { left: 0; }
        .nav-links a { font-size: 26px; padding: 20px; }
        .menu-toggle { display: block; }
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
        <a href="book_table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
        <a href="account.php"><img src="user-icon.png" class="user-icon" alt="User"></a>
    </div>
    <div class="menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('active')">☰</div>
</div>

<div class="container">
    <section class="employee-section">
        <h2>Our Employees</h2>
        <div class="employee-list">
            <?php if (empty($employees)): ?>
                <p style="grid-column: 1 / -1; text-align:center; font-size:24px; color:#888;">No employees found.</p>
            <?php else: ?>
                <?php foreach ($employees as $emp): ?>
                <div class="employee-card">
                    <img src="user-icon.png" alt="<?= htmlspecialchars($emp['name']) ?>">
                    <h3><?= htmlspecialchars($emp['name']) ?></h3>
                    <p>Phone: <?= htmlspecialchars($emp['phone'] ?? 'N/A') ?></p>
                    <?php if (!empty($emp['duty_time'])): ?>
                        <p>Duty Time: <?= htmlspecialchars($emp['duty_time']) ?></p>
                    <?php endif; ?>
                    <button class="btn" onclick="openEmployeeModal(
                        '<?= htmlspecialchars($emp['name']) ?>',
                        '<?= htmlspecialchars($emp['phone'] ?? '') ?>',
                        '<?= htmlspecialchars($emp['duty_time'] ?? '') ?>',
                        '<?= htmlspecialchars($emp['email'] ?? '') ?>',
                        '<?= htmlspecialchars($emp['address'] ?? '') ?>'
                    )">See more...</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Employee Modal -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEmployeeModal()">×</span>
        <img src="user-icon.png" alt="Employee Photo">
        <h3 id="modalName"></h3>
        <p id="modalPhone"></p>
        <p id="modalDuty"></p>
        <p id="modalEmail"></p>
        <p id="modalAddress"></p>
    </div>
</div>

<script>
function openEmployeeModal(name, phone, duty, email, address) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalPhone').textContent = phone ? "Phone: " + phone : "";
    document.getElementById('modalDuty').textContent = duty ? "Duty Time: " + duty : "";
    document.getElementById('modalEmail').textContent = email ? "Email: " + email : "";
    document.getElementById('modalAddress').textContent = address ? "Address: " + address : "";
    document.getElementById('employeeModal').style.display = "flex";
}
function closeEmployeeModal() {
    document.getElementById('employeeModal').style.display = "none";
}
window.onclick = function(event) {
    const modal = document.getElementById('employeeModal');
    if (event.target == modal) closeEmployeeModal();
}
</script>

</body>
</html>
<?php
    exit(); // Stop here for admin/employee
}

// Normal customer booking page continues...
$current_date = date('Y-m-d');
$selected_date = $_POST['booking_date'] ?? $current_date;
$selected_time = $_POST['booking_time'] ?? '';
$party_size = (int)($_POST['party_size'] ?? 2);

$message = "";
$error = "";

// Handle booking
if (isset($_POST['place_order'])) {
    $table_id = (int)$_POST['table_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $party_size = (int)$_POST['party_size'];
    $special_requests = trim($_POST['special_requests'] ?? '');
    $order_items = $_POST['order_items'] ?? [];

    $stmt = $conn->prepare("SELECT capacity FROM restaurant_tables WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    $stmt->fetch();
    $stmt->close();

    if ($party_size > $capacity) {
        $error = "This table only has capacity for $capacity guests.";
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE table_id = ? 
              AND booking_date = ? 
              AND status IN ('pending','confirmed','seated')
              AND (
                (booking_time <= ? AND ADDTIME(booking_time, '02:00:00') > ?) OR
                (booking_time < ADDTIME(?, '02:00:00') AND ADDTIME(booking_time, '02:00:00') > ?)
              )
        ");
        $stmt->bind_param("isssss", $table_id, $booking_date, $booking_time, $booking_time, $booking_time, $booking_time);
        $stmt->execute();
        $stmt->bind_result($conflict);
        $stmt->fetch();
        $stmt->close();

        if ($conflict > 0) {
            $error = "This table is already booked or overlapping.";
        } else {
            $conn->autocommit(false);
            try {
                $stmt = $conn->prepare("INSERT INTO bookings 
                    (table_id, booking_date, booking_time, party_size, customer_name, customer_phone, special_requests, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
                $stmt->bind_param("ississs", $table_id, $booking_date, $booking_time, $party_size, $user['name'], $user['phone'], $special_requests);
                $stmt->execute();
                $booking_id = $conn->insert_id;
                $stmt->close();

                if (!empty($order_items)) {
                    $item_stmt = $conn->prepare("INSERT INTO table_orders (booking_id, menu_item_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
                    foreach ($order_items as $item_id => $qty) {
                        if ($qty > 0) {
                            $price_stmt = $conn->prepare("SELECT price FROM menu_items WHERE id = ?");
                            $price_stmt->bind_param("i", $item_id);
                            $price_stmt->execute();
                            $price_stmt->bind_result($price);
                            $price_stmt->fetch();
                            $price_stmt->close();

                            $item_stmt->bind_param("iiid", $booking_id, $item_id, $qty, $price);
                            $item_stmt->execute();
                        }
                    }
                    $item_stmt->close();
                }

                $conn->commit();
                $message = "Booking confirmed successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Booking failed.";
            }
            $conn->autocommit(true);
        }
    }
}

$tables_result = $conn->query("SELECT id, table_number, capacity FROM restaurant_tables ORDER BY table_number");
$menu_items = $conn->query("SELECT id, name, price, description FROM menu_items ORDER BY name")->fetch_all(MYSQLI_ASSOC);

function isTableBooked($table_id, $date, $time, $conn) {
    if (empty($time)) return false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE table_id = ? 
          AND booking_date = ? 
          AND status IN ('pending','confirmed','seated')
          AND (
            (booking_time <= ? AND ADDTIME(booking_time, '02:00:00') > ?) OR
            (booking_time < ADDTIME(?, '02:00:00') AND ADDTIME(booking_time, '02:00:00') > ?)
          )
    ");
    $stmt->bind_param("isssss", $table_id, $date, $time, $time, $time, $time);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Table & Order Food</title>
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
    .container { max-width: 1200px; margin: 130px auto 60px; padding: 20px; }
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
    .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:2000; justify-content:center; align-items:flex-start; overflow-y:auto; padding:20px 0; }
    .modal-content { background:white; width:90%; max-width:900px; border-radius:20px; overflow:hidden; max-height:95vh; display:flex; flex-direction:column; }
    .modal-header { background:#2f4f2f; color:white; padding:25px; text-align:center; font-size:30px; position:relative; flex-shrink:0; }
    .close-btn { position:absolute; top:15px; right:25px; font-size:40px; background:none; border:none; color:white; cursor:pointer; }
    .modal-body { padding:40px; overflow-y:auto; flex-grow:1; }
    .user-info { background:#f0f8f0; padding:20px; border-radius:12px; margin-bottom:40px; font-size:18px; }
    .menu-list { max-width:700px; margin:0 auto; }
    .menu-item-row { display:flex; justify-content:space-between; align-items:center; padding:20px; background:#f9f9f9; border-radius:12px; margin-bottom:15px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    .menu-item-info { flex:1; }
    .menu-item-name { font-size:22px; font-weight:bold; color:#2f4f2f; }
    .menu-item-desc { font-size:15px; color:#666; margin-top:5px; }
    .menu-item-price { font-size:24px; font-weight:bold; color:#e74c3c; margin-top:8px; }
    .quantity-modal { display:flex; justify-content:center; align-items:center; gap:15px; }
    .quantity-modal button { width:45px; height:45px; background:#ffcc00; border:none; border-radius:50%; font-size:26px; cursor:pointer; font-weight:bold; }
    .quantity-modal input { width:80px; text-align:center; padding:10px; font-size:18px; border:1px solid #ccc; border-radius:8px; }
    .place-order-btn { width:100%; background:#27ae60; color:white; padding:20px; border:none; border-radius:15px; font-size:24px; font-weight:bold; cursor:pointer; margin-top:40px; flex-shrink:0; }

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
        <a href="book_table.php" class="active">BOOK TABLE</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
    </div>
    <div class="nav-right">
        <a href="account.php"><img src="user-icon.png" class="user-icon" alt="User"></a>
    </div>
    <div class="menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('active')">☰</div>
</div>

<div class="container">
    <h2>Book a Table & Order Food</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="controls">
        <form method="POST">
            <div>
                <label>Date</label>
                <input type="date" name="booking_date" min="<?= $current_date ?>" value="<?= htmlspecialchars($selected_date) ?>" required>
            </div>
            <div>
                <label>Time</label>
                <select name="booking_time" required>
                    <option value="">Select Time</option>
                    <?php for ($h=11; $h<=22; $h++) { foreach ([0,30] as $m) { if ($h==22 && $m>0) continue; $t=sprintf("%02d:%02d:00",$h,$m); $sel=($selected_time==$t)?'selected':''; echo "<option value='$t' $sel>".date('h:i A',strtotime($t))."</option>"; } } ?>
                </select>
            </div>
            <div>
                <label>Guests</label>
                <input type="number" name="party_size" min="1" max="50" value="<?= $party_size ?>" required>
            </div>
            <button type="submit">Check Available Tables</button>
        </form>
    </div>

    <?php if ($selected_time): ?>
    <div class="tables-grid">
        <?php 
        $tables_result->data_seek(0);
        while ($table = $tables_result->fetch_assoc()): 
            $capacity_ok = ($table['capacity'] >= $party_size);
            $booked = isTableBooked($table['id'], $selected_date, $selected_time, $conn);
            $available = $capacity_ok && !$booked;
            $class = $available ? 'available' : 'booked';
            $status_text = $booked ? 'BOOKED' : ($capacity_ok ? 'Click to Order' : 'Too Small');
        ?>
            <div class="table-card <?= $class ?>" <?= $available ? 'onclick="openModal('.$table['id'].', \''.htmlspecialchars($table['table_number']).'\')"' : '' ?>>
                <h3><?= htmlspecialchars($table['table_number']) ?></h3>
                <div class="capacity">Capacity: <?= $table['capacity'] ?> guests</div>
                <div class="status-text"><?= $status_text ?></div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            Order Food - Table <span id="modalTableNumber"></span>
            <button class="close-btn" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="table_id" id="modalTableId">
                <input type="hidden" name="booking_date" value="<?= htmlspecialchars($selected_date) ?>">
                <input type="hidden" name="booking_time" value="<?= htmlspecialchars($selected_time) ?>">
                <input type="hidden" name="party_size" value="<?= $party_size ?>">

                <div class="user-info">
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></p>
                </div>

                <div style="margin-bottom:40px;">
                    <label>Special Requests (Optional)</label>
                    <textarea name="special_requests" rows="3" placeholder="e.g. Birthday celebration..." style="width:100%;"></textarea>
                </div>

                <h3 style="text-align:center; margin:40px 0; color:#2f4f2f; font-size:32px;">Select Menu Items</h3>
                <div class="menu-list">
                    <?php foreach ($menu_items as $item): ?>
                    <div class="menu-item-row">
                        <div class="menu-item-info">
                            <div class="menu-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if ($item['description']): ?>
                                <div class="menu-item-desc"><?= htmlspecialchars($item['description']) ?></div>
                            <?php endif; ?>
                            <div class="menu-item-price">₹<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <div class="quantity-modal">
                            <button type="button" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                            <input type="number" name="order_items[<?= $item['id'] ?>]" value="0" min="0" max="20" readonly>
                            <button type="button" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="place_order" class="place-order-btn">Place Order & Confirm Booking</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(tableId, tableNumber) {
    document.getElementById('modalTableId').value = tableId;
    document.getElementById('modalTableNumber').textContent = tableNumber;
    document.getElementById('orderModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}
function changeQty(id, delta) {
    const input = document.querySelector(`input[name="order_items[${id}]"]`);
    let val = parseInt(input.value) || 0;
    val += delta;
    if (val < 0) val = 0;
    if (val > 20) val = 20;
    input.value = val;
}
window.onclick = function(event) {
    const modal = document.getElementById('orderModal');
    if (event.target == modal) closeModal();
}
</script>

</body>
</html>