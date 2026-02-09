<?php
session_start();
include "database.php";

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Check if current user is admin (using the 'admin' column)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if ($row['admin'] != 1) {
        header("Location: index.php");
        exit();
    }
} else {
    session_destroy();
    header("Location: signin.php");
    exit();
}
$stmt->close();

$message = "";

if (isset($_POST['change'])) {
    $email = trim($_POST['email']);
    $selected_role = $_POST['role'];

    if ($selected_role === 'admin') {
        $role = 'employee';
        $is_admin = 1;
    } elseif ($selected_role === 'employee') {
        $role = 'employee';
        $is_admin = 0;
    } else {
        $role = 'customer';
        $is_admin = 0;
    }

    $stmt = $conn->prepare("UPDATE users SET role = ?, admin = ? WHERE email = ?");
    $stmt->bind_param("sis", $role, $is_admin, $email);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $display_role = ($selected_role === 'admin') ? 'Admin' : ucfirst($role);
            $message = "<p class='success'>Role updated successfully!<br>
                        <strong>$email</strong> → <strong>$display_role</strong> 
                        (admin flag: " . ($is_admin ? 'Yes' : 'No') . ")</p>";
        } else {
            $message = "<p class='error'>No user found with email: <strong>$email</strong></p>";
        }
    } else {
        $message = "<p class='error'>Database error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change User Role - RestroDash</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background:#f5f5f5; }

        /* EXACT SAME NAVBAR STYLING AS CUSTOMER PAGES */
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

        .form-section {
            margin:60px auto;
            max-width:500px;
            background:white;
            padding:40px;
            border-radius:15px;
            box-shadow:0 8px 25px rgba(0,0,0,0.1);
        }
        .form-section h2 {
            text-align:center;
            margin-bottom:30px;
            color:#2f4f2f;
            font-size:32px;
        }
        .form-section label {
            display:block;
            margin:15px 0 8px;
            font-weight:bold;
        }
        .form-section input, .form-section select {
            width:100%;
            padding:14px;
            border:2px solid #ddd;
            border-radius:8px;
            font-size:16px;
        }
        .form-section input:focus, .form-section select:focus {
            border-color:#2f4f2f;
            outline:none;
        }
        .form-section button {
            width:100%;
            padding:15px;
            margin-top:25px;
            background:#2f4f2f;
            color:white;
            border:none;
            border-radius:25px;
            font-size:18px;
            font-weight:bold;
            cursor:pointer;
        }
        .form-section button:hover {
            background:#1e3520;
        }
        .back {
            display:block;
            text-align:center;
            margin-top:20px;
            color:#2f4f2f;
            text-decoration:underline;
            font-weight:bold;
        }

        .success {
            background:#d4edda;
            color:#155724;
            padding:15px;
            border-radius:8px;
            margin:20px 0;
            border:1px solid #c3e6cb;
        }
        .error {
            background:#f8d7da;
            color:#721c24;
            padding:15px;
            border-radius:8px;
            margin:20px 0;
            border:1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<!-- CLEAN NAVBAR - ONLY PROFILE ICON ON RIGHT -->
<div class="navbar">
    <div class="nav-left">
        <a href="index.php"><img src="logo.png" alt="RestroDash"></a>
    </div>
    <div class="nav-links">
        <a href="index.php">HOME</a>
        <a href="menu.php">MENU</a>
        <a href="history.php">HISTORY</a>
        <a href="contact.php">CONTACT</a>
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
        <!-- Only user profile icon - no cart, no logout, no extra buttons -->
        <a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%;"></a>
    </div>
</div>

<!-- Change Role Form -->
<section class="form-section">
    <h2>Change User Role / Privileges</h2>

    <?php if ($message) echo $message; ?>

    <form method="POST">
        <label>User Email</label>
        <input type="email" name="email" placeholder="Enter user email" required autofocus>

        <label>New Role / Privilege</label>
        <select name="role" required>
            <option value="customer">Customer (Regular User)</option>
            <option value="employee">Employee (Staff)</option>
            <option value="admin">Admin (Full Access)</option>
        </select>

        <button type="submit" name="change">Update Role</button>
    </form>

    <a href="admin.php" class="back">← Back to Admin Dashboard</a>
</section>

</body>
</html>