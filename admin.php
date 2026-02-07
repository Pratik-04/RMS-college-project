<?php
session_start();
include "database.php";

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

$isEmployee = ($user['role'] ?? '') === 'employee';
$isAdmin    = ($user['admin'] ?? 0) == 1;

// Only employees and admins can access this page (customers are redirected earlier in other pages)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RestroDash</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
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

        .nav-left img {
            height: 100px;
        }

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
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-right img.user-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            text-align: center;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .card-link {
            display: block;
            background: white;
            padding: 40px 30px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            text-align: center;
            text-decoration: none;
            color: #333;
            border-left: 8px solid #ffcc00;
            transition: 0.4s;
            cursor: pointer;
        }

        .card-link:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            background: #fffdf0;
        }

        .card-link h3 {
            font-size: 30px;
            color: #2f4f2f;
            margin: 20px 0 10px;
        }

        .card-link p {
            font-size: 19px;
            color: #666;
            line-height: 1.5;
        }

        .disabled-card {
            opacity: 0.5;
            pointer-events: none;
            border-left-color: #ccc;
            cursor: not-allowed;
            background: #f9f9f9;
        }

        .disabled-card h3 {
            color: #aaa;
        }

        .disabled-card p {
            color: #bbb;
        }

        .denied {
            background: #ffe6e6;
            color: red;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
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
        <a href="account.php">
            <img src="user-icon.png" class="user-icon" alt="Account" onerror="this.src='food.png'">
        </a>
    </div>
</div>

<div class="container">
    <h1 style="font-size:48px; color:#2f4f2f; margin-bottom:10px;">Admin Dashboard</h1>
    <p style="font-size:22px; color:#666; margin-bottom:50px;">Click any card to manage</p>

    <?php if (!$isAdmin && $isEmployee): ?>
        <div class="denied">
            Employee Access: You can manage items, orders, and table bookings, but <u>cannot change user roles or generate reports</u>.
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">

        <!-- Manage Menu Items -->
        <a href="manageitem.php" class="card-link">
            <h3>Manage Items</h3>
            <p>Add, edit or remove menu items</p>
        </a>

        <!-- Update Order Status -->
        <a href="updatestatus.php" class="card-link">
            <h3>View/Update Orders</h3>
            <p>Change order status (In Process → Completed)</p>
        </a>

        <!-- NEW: Manage Table Bookings -->
        <a href="admin_bookings.php" class="card-link">
            <h3>Manage Table Bookings</h3>
            <p>Manage table reservations</p>
        </a>

        <!-- View Employees (optional - kept if useful) -->
        <a href="employee.php" class="card-link">
            <h3>View Employees</h3>
            <p>See all registered staff members</p>
        </a>

        <!-- ADMIN ONLY: Change User Role -->
        <?php if ($isAdmin): ?>
            <a href="changerole.php" class="card-link">
                <h3>Change User Role</h3>
                <p>Promote users to employee or admin</p>
            </a>
        <?php else: ?>
            <div class="card-link disabled-card">
                <h3>Change User Role</h3>
                <p>Admin only</p>
            </div>
        <?php endif; ?>

        <!-- ADMIN ONLY: Generate Report -->
        <?php if ($isAdmin): ?>
            <a href="report.php" class="card-link">
                <h3>Generate Report</h3>
                <p>View daily & monthly sales reports</p>
            </a>
        <?php else: ?>
            <div class="card-link disabled-card">
                <h3>Generate Report</h3>
                <p>Admin only</p>
            </div>
        <?php endif; ?>

         <a href="contact_msg.php" class="card-link">
            <h3>Messages</h3>
            <p>See all registered staff members</p>
        </a>
        

    </div>
</div>

<script>
    // Keep login state (optional - if you're using it elsewhere)
    localStorage.setItem('isLoggedIn', 'true');
</script>

</body>
</html>