<?php
ob_start();
session_start();
require_once "database.php";

$error = '';
$name = $email = $phone = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password) || empty($cpassword)) {
        $error = "All fields are required!";
    }
    elseif (strlen($name) < 3) {
        $error = "Name must be at least 3 characters long!";
    }
    elseif (str_word_count($name) < 2) {
        $error = "Please enter your full name (at least two words)!";
    }
    elseif (!preg_match('/^[a-zA-Z\s\.\-]+$/', $name)) {
        $error = "Name can only contain letters, spaces, dots (.) and hyphens (-).\nNumbers and symbols like @#$% are not allowed!";
    }
    elseif (!preg_match('/^(97|98)\d{8}$/', $phone)) {
        $error = "Phone must start with 97 or 98 and be exactly 10 digits!\nExample: 9841234567";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    elseif ($password !== $cpassword) {
        $error = "Passwords do not match!";
    }
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/', $password)) {
        $error = "Password must be at least 6 characters and contain:\n• 1 uppercase\n• 1 lowercase\n• 1 number\n• 1 special character (@$!%*?&)";
    }
    else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "This email is already registered!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            $stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed);

            if ($stmt->execute()) {
                header("Location: signin.php?signup=success");
                exit;
            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - RestroDash</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar {
            height: 90px; padding: 20px 50px; background: #2f4f2f; color: white;
            display: flex; justify-content: space-between; align-items: center;
        }
        .nav-left img { height: 100px; }
        .nav-links { margin-left: auto; margin-right: 40px; display: flex; gap: 30px; align-items: center; }
        .nav-links a { font-size: 30px; font-weight: 700; color: white; text-decoration: none; }
        .nav-right a {
            font-size: 20px; font-weight: 700; color: white; padding: 10px 20px;
            border-radius: 15px; background: #2f4f2f; text-decoration: none; margin-left: 10px;
        }
        .container { display: flex; justify-content: center; padding: 40px 0; }
        .card {
            width: 420px; background: white; padding: 35px; border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card h2 { text-align: center; margin-bottom: 25px; font-size: 28px; color: #2f4f2f; font-weight: bold; }
        label { display: block; font-weight: bold; margin-top: 12px; color: #2f4f2f; }
        input {
            width: 100%; padding: 12px; margin-top: 6px; border: 1px solid #ccc;
            border-radius: 8px; font-size: 16px;
        }
        input.error-field { border-color: #d8000c; }
        button {
            width: 100%; padding: 14px; margin-top: 25px; background: #2f4f2f; color: white;
            border: none; border-radius: 20px; font-size: 18px; font-weight: 700; cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #1f3324; }
        .error {
            color: #d8000c; background: #ffbaba; padding: 12px; border-radius: 8px;
            text-align: center; margin: 15px 0; font-weight: bold; white-space: pre-line;
        }
        .login-link { text-align: center; margin-top: 20px; font-size: 15px; }
        .login-link a { color: #2f4f2f; font-weight: bold; text-decoration: none; }
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
    <div class="nav-right" id="authArea"></div>
</div>

<div class="container">
    <div class="card">
        <h2>Create Account</h2>

        <!-- Server-side error -->
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Client-side error will be inserted here -->
        <div id="clientError" class="error" style="display:none;"></div>

        <form id="signupForm" method="POST" action="signup.php">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" placeholder="Ram Bahadur Thapa" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="example@gmail.com" required>

            <label>Phone Number</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="9841234567" maxlength="10" required>

            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($address ?? '') ?>" placeholder="Kathmandu, Nepal" required>

            <label>Password</label>
            <input type="password" name="password" id="password" placeholder="Enter strong password" required>

            <label>Confirm Password</label>
            <input type="password" name="cpassword" id="cpassword" placeholder="Re-type password" required>

            <button type="submit">Sign Up</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="signin.php">Sign In</a>
        </div>
    </div>
</div>

<script>
    // Clear previous error highlighting
    function clearErrors() {
        document.querySelectorAll('input').forEach(input => {
            input.classList.remove('error-field');
        });
        document.getElementById('clientError').style.display = 'none';
        document.getElementById('clientError').innerHTML = '';
    }

    // Show error message above form and highlight field
    function showError(message, fieldName = null) {
        clearErrors();
        document.getElementById('clientError').style.display = 'block';
        document.getElementById('clientError').innerHTML = message.replace(/\n/g, '<br>');

        if (fieldName) {
            const field = document.querySelector(`input[name="${fieldName}"]`) || document.getElementById(fieldName);
            if (field) {
                field.classList.add('error-field');
                field.focus();
            }
        }
    }

    document.getElementById('signupForm').addEventListener('submit', function(e) {
        clearErrors();

        const name = document.querySelector('input[name="name"]').value.trim();
        const password = document.getElementById('password').value;
        const cpassword = document.getElementById('cpassword').value;

        // Name validation
        const nameRegex = /^[a-zA-Z\s\.\-]+$/;
        if (!nameRegex.test(name)) {
            e.preventDefault();
            showError(
                "Invalid name format!\n\nName can only contain:\n• Letters\n• Spaces\n• Dots (.)\n• Hyphens (-)\n\nNumbers and symbols (@#$% etc.) are not allowed.",
                'name'
            );
            return;
        }

        // Password strength
        const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/;
        if (!strongRegex.test(password)) {
            e.preventDefault();
            showError(
                "Weak password!\n\nPassword must contain:\n• At least 6 characters\n• 1 uppercase letter\n• 1 lowercase letter\n• 1 number\n• 1 special character (@$!%*?&)",
                'password'
            );
            return;
        }

        // Confirm password
        if (password !== cpassword) {
            e.preventDefault();
            showError("Passwords do not match!", 'cpassword');
            return;
        }

        // If all pass, form will submit normally (server-side validation will still run)
    });

    // Clear error when user starts typing in any field
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', clearErrors);
    });

    // Dynamic navbar
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    document.getElementById('authArea').innerHTML = isLoggedIn
        ? `<a href="account.php"><img src="user-icon.png" style="width:40px;height:40px;border-radius:50%;border:2px solid white;" alt="Account"></a>`
        : `<a href="signin.php">Sign In</a><a href="signup.php">Sign Up</a>`;
</script>

</body>
</html>