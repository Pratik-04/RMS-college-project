<?php
session_start();
include "database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found!";
    exit();
}
$user = $result->fetch_assoc();

$isEmployee = ($user['role'] ?? '') === 'employee';
$isAdmin    = ($user['admin'] ?? 0) == 1;
$canAccessAdmin = $isAdmin || $isEmployee;

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>
            localStorage.removeItem('isLoggedIn');
            location = 'signin.php';
          </script>";
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']);
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address']);
    $duty_time  = $isEmployee ? trim($_POST['duty_time'] ?? '') : ($user['duty_time'] ?? '');

    $new_role   = $isAdmin ? ($_POST['role'] ?? $user['role']) : $user['role'];
    $new_admin  = $isAdmin ? (isset($_POST['make_admin']) ? 1 : 0) : ($user['admin'] ?? 0);

    if (empty($name) || empty($address)) {
        $message = '<div style="color:red; margin:10px 0; text-align:center;">Name & Address are required.</div>';
    } else {
        $upd = $conn->prepare("
            UPDATE users 
            SET name = ?, phone = ?, address = ?, duty_time = ?, role = ?, admin = ? 
            WHERE id = ?
        ");
        $upd->bind_param("sssssii", $name, $phone, $address, $duty_time, $new_role, $new_admin, $user_id);

        if ($upd->execute()) {
            $message = '<div style="color:green; margin:10px 0; text-align:center;">Profile updated successfully!</div>';
            $user['name']      = $name;
            $user['phone']     = $phone;
            $user['address']   = $address;
            $user['duty_time'] = $duty_time;
            $user['role']      = $new_role;
            $user['admin']     = $new_admin;
            $isEmployee = $new_role === 'employee';
            $isAdmin    = $new_admin == 1;
            $canAccessAdmin = $isAdmin || $isEmployee;
        } else {
            $message = '<div style="color:red; margin:10px 0; text-align:center;">Update failed!</div>';
        }
        $upd->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - RestroDash</title>
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
        .nav-right a {
            font-size: 20px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 15px;
            background: #2f4f2f;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .section-title {
            font-size: 32px;
            font-weight: 700;
            color: #2f4f2f;
            margin-bottom: 20px;
        }

        #employeeLabel {
            font-size: 18px;
            font-weight: bold;
            color: #e67e22;
            margin-bottom: 15px;
        }

        .account-box {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .edit-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ffcc00;
            color: white;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .edit-btn:hover { background: #ffb800; }

        .user-card {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: #ccc;
            border-radius: 50%;
            margin-right: 15px;
            background-image: url('user-icon.png');
            background-size: cover;
            background-position: center;
        }

        .user-info strong {
            display: block;
            font-size: 20px;
            color: #2f4f2f;
        }
        .user-info span { color: #888; font-size: 14px; }

        .label {
            font-weight: bold;
            margin-top: 15px;
            color: #2f4f2f;
        }

        .value-box {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 5px;
            font-size: 16px;
        }

        .admin-banner {
            background: #2f4f2f;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 18px;
        }

        .role-display {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            color: #2f4f2f;
            text-align: center;
            margin: 10px 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #000; }

        .modal-header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            color: #2f4f2f;
        }

        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-group input[readonly] {
            background: #f0f0f0;
            color: #666;
            cursor: not-allowed;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }
        .cancel-btn { background: #ccc; color: #333; }
        .save-btn { background: #2f4f2f; color: white; }
        .save-btn:hover { background: #1f3324; }
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
        <a href="account.php"><img src="user-icon.png" class="user-icon" alt="Account"></a>
    </div>
</div>

<div class="container">
    <div class="section-title">Account Details</div>

    <?php if ($canAccessAdmin): ?>
        <div class="admin-banner">
            <?= $isAdmin ? 'ADMIN MODE' : 'EMPLOYEE MODE' ?> - 
            <a href="admin.php" style="color:white; text-decoration:underline;">Go to Admin Panel</a>
        </div>
    <?php endif; ?>

    <div id="employeeLabel" style="display: <?= $isEmployee ? 'block' : 'none' ?>;">
        Employee Account
    </div>

    <div class="role-display">
        Current Role: <strong><?= htmlspecialchars(ucfirst($user['role'] ?? 'customer')) ?></strong>
        <?php if ($isAdmin): ?><br><small style="color:green;">You are an Admin</small><?php endif; ?>
    </div>

    <?= $message ?>

    <div class="account-box">
        <div class="edit-btn">Edit</div>

        <div class="user-card">
            <div class="user-avatar"></div>
            <div class="user-info">
                <strong id="nameField"><?= htmlspecialchars($user['name']) ?></strong>
                <span id="emailField"><?= htmlspecialchars($user['email']) ?></span>
            </div>
        </div>

        <div class="label">User ID:</div>
        <div class="value-box" style="background:#e8f5e9; color:#2e7d32; font-weight:bold;">
            #<?= $user['id'] ?>
        </div>

        <div class="label">Name:</div>
        <div class="value-box" id="nameBox"><?= htmlspecialchars($user['name']) ?></div>

        <div class="label">Email</div>
        <div class="value-box" style="background:#fff3cd; color:#856404; font-weight:bold;">
            <?= htmlspecialchars($user['email']) ?>
        </div>

        <div class="label">Phone:</div>
        <div class="value-box" id="phoneBox"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></div>

        <div class="label">Address:</div>
        <div class="value-box" id="addressBox"><?= htmlspecialchars($user['address']) ?></div>

        <?php if ($isEmployee): ?>
            <div class="label">Duty Time:</div>
            <div class="value-box" id="dutyBox"><?= htmlspecialchars($user['duty_time'] ?? 'Not set') ?></div>
        <?php endif; ?>
    </div>

    <div style="margin-top:30px; text-align:center;">
        <a href="?logout=true" style="background:#e74c3c; color:white; padding:12px 30px; border-radius:25px; text-decoration:none; font-weight:bold; font-size:18px;">
            Logout
        </a>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <div class="modal-header">Edit Account Details</div>
        <form id="editForm" action="account.php" method="POST">
            <div class="form-group">
                <label>User ID</label>
                <input type="text" value="#<?= $user['id'] ?>" readonly>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email </label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:#f0f0f0; color:#666;">
               
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="98xxxxxxxx">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>" required>
            </div>
            <?php if ($isEmployee): ?>
            <div class="form-group">
                <label>Duty Time</label>
                <input type="text" name="duty_time" value="<?= htmlspecialchars($user['duty_time'] ?? '') ?>" placeholder="e.g. 10AM - 10PM">
            </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label>User Role</label>
                    <select name="role">
                        <option value="customer" <?= ($user['role'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="employee" <?= ($user['role'] ?? 'customer') === 'employee' ? 'selected' : '' ?>>Employee</option>
                        <option value="admin" <?= ($user['role'] ?? 'customer') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="make_admin" id="make_admin" <?= $isAdmin ? 'checked' : '' ?>>
                    <label for="make_admin" style="font-weight:normal;">Make this user Admin</label>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>User Role</label>
                    <input type="text" value="<?= htmlspecialchars(ucfirst($user['role'] ?? 'customer')) ?>" readonly>
                </div>
            <?php endif; ?>

            <div class="btn-group">
                <button type="button" class="btn cancel-btn">Cancel</button>
                <button type="submit" class="btn save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("editModal");
    const editBtn = document.querySelector(".edit-btn");
    const closeBtn = document.querySelector(".close");
    const cancelBtn = document.querySelector(".cancel-btn");

    editBtn.onclick = () => modal.style.display = "flex";
    closeBtn.onclick = cancelBtn.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };

    localStorage.setItem('isLoggedIn', 'true');
</script>

</body>
</html>