<?php
// contact.php
session_start();
include "database.php";

// Define $isLoggedIn (needed for navbar)
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Must be logged in + role check
if (!$isLoggedIn) {
    header("Location: signin.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// === REDIRECT EMPLOYEES AND ADMINS TO ADMIN PANEL ===
$stmt_check = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($row_check = $result_check->fetch_assoc()) {
    $role = $row_check['role'] ?? '';
    $is_admin = (int)($row_check['admin'] ?? 0);

    if ($role === 'employee' || $is_admin === 1) {
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

// Fetch name & email for pre-fill
$name  = '';
$email = '';

$stmt_user = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($row_user = $result_user->fetch_assoc()) {
    $name  = $row_user['name'] ?? '';
    $email = $row_user['email'] ?? '';
}
$stmt_user->close();

// Handle form submission
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_name    = trim($_POST['name'] ?? '');
    $form_email   = trim($_POST['email'] ?? '');
    $form_message = trim($_POST['message'] ?? '');

    if ($form_name === '' || $form_email === '' || $form_message === '') {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt_insert = $conn->prepare("
            INSERT INTO contact_messages 
            (user_id, name, email, message, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt_insert->bind_param("isss", $user_id, $form_name, $form_email, $form_message);

        if ($stmt_insert->execute()) {
            $success = true;
        } else {
            $error = "Cannot save message: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>RestroDash - Contact</title>
<style>
/* Your original beautiful styles - unchanged */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial, sans-serif; background:#f5f5f5; }

.navbar { height:90px; padding:20px 50px; background:#2f4f2f; color:white; display:flex; justify-content:space-between; align-items:center; }
.nav-left img { height:100px; }
.nav-links { margin-left:auto; margin-right:40px; display:flex; align-items:center; gap:30px; }
.nav-links a { font-size:30px; font-weight:700; color:white; text-decoration:none; }
.nav-right { display:flex; align-items:center; gap:10px; }
.nav-right a { font-size:20px; font-weight:700; color:white; text-decoration:none; padding:10px 20px; border-radius:15px; background:#2f4f2f; }
.nav-right img.user-icon { width:50px; height:50px; border-radius:50%; object-fit:cover; }
.nav-right img[alt="cart"] { height:40px; }

.section-title { margin:25px 40px; font-size:28px; font-weight:700; }
.container { max-width:900px; margin:50px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h1 { text-align:center; margin-bottom:30px; color:#000; }
.contact-info { display:flex; justify-content:space-around; margin-bottom:30px; }
.contact-info div { text-align:center; }
.contact-info div h3 { margin-bottom:10px; color:#000; }
form { display:flex; flex-direction:column; gap:15px; }
input, textarea { padding:12px; border:1px solid #ccc; border-radius:8px; font-size:16px; width:100%; }
textarea { resize:none; height:150px; }
button { padding:15px; background:#3c833c; color:white; border:none; border-radius:8px; font-size:18px; cursor:pointer; }
button:hover { background:#2f4f2f; }

.message { padding:14px; margin-bottom:20px; border-radius:6px; text-align:center; font-weight:500; }
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

@media(max-width:600px) { .contact-info { flex-direction:column; gap:20px; } }
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
        <?php if ($isLoggedIn): ?>
            <a href="mycart.php"><img src="cart.png" alt="cart"></a>
            <a href="account.php"><img src="user-icon.png" class="user-icon" alt="Account" onerror="this.src='default-user.png'"></a>
        <?php else: ?>
            <a href="signin.php">Sign In</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<h2 class="section-title">Contact Us</h2>

<div class="container">
    <h1>Contact Us</h1>

    <div class="contact-info">
        <div><h3>Address</h3><p>123 Main Street,<br>Nepal</p></div>
        <div><h3>Phone</h3><p>+977 980-123-4567</p></div>
        <div><h3>Email</h3><p>support@restrodash.com</p></div>
    </div>

    <?php if ($success): ?>
        <div class="message success">Thank you! Your message has been sent successfully.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text"    name="name"    value="<?= htmlspecialchars($name) ?>"    placeholder="Your Name"    required>
        <input type="email"   name="email"   value="<?= htmlspecialchars($email) ?>"   placeholder="Your Email"   required>
        <textarea name="message" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
    </form>
</div>

</body>
</html>