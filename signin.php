<?php
session_start();
include "database.php";

$error = '';
$signup_success = isset($_GET['signup']) && $_GET['signup'] === 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, name, password, phone, address, 
               COALESCE(role, 'customer') AS role, 
               admin 
        FROM users 
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user_role;  
            $_SESSION['name']    = $user['name'];
            $_SESSION['admin']   = $user['admin'] ?? null;

            echo "<script>
                    localStorage.setItem('isLoggedIn', 'true');
                    location = 'index.php';
                  </script>";
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - RestroDash</title>
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
            justify-content: center;
            padding: 40px 0;
        }

        .card {
            width: 400px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #2f4f2f;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
            color: #2f4f2f;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            font-weight: 700;
            border: none;
            border-radius: 20px;
            background: #2f4f2f;
            color: white;
            cursor: pointer;
            font-size: 18px;
        }

        button:hover {
            background: #1f3324;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            padding: 8px;
            background: #ffe6e6;
            border-radius: 5px;
        }

        .success {
            color: green;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            padding: 8px;
            background: #e6f7e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <a href="#"><img src="logo.png" alt="logo"></a>
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
    <div class="card">
        <h2>Sign In</h2>

        <?php if ($signup_success): ?>
            <p class="success">Account created successfully! Please sign in.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="signin.php">
            <label>Email</label>
            <input 
                type="email" 
                name="email" 
                placeholder="Enter your Email" 
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                required 
            >

            <label>Password</label>
            <input 
                type="password" 
                name="password" 
                placeholder="Enter Password" 
                required 
            >

            <button type="submit">Sign In</button>
        </form>
    </div>
</div>

<script>
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    localStorage.setItem('isLoggedIn', isLoggedIn);

    function updateNavbar() {
        const authArea = document.getElementById('authArea');
        if (isLoggedIn) {
            authArea.innerHTML = `
                <a href="account.php">
                    <img src="user-icon.png" 
                         class="user-icon" 
                         alt="Account" 
                         style="width:40px; height:40px; border-radius:50%; border:2px solid white;">
                </a>
            `;
        } else {
            authArea.innerHTML = `
                <a href="signin.php">Sign In</a>
                <a href="signup.php">Sign Up</a>
            `;
        }
    }
    updateNavbar();
</script>

</body>
</html>