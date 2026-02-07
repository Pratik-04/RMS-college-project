<?php
session_start(); // Must be at the very top
include "database.php";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Securely fetch the user's role from the database
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $role = $row['role'];
        
        // Redirect BOTH 'employee' and 'admin' to the admin panel
        if ($role === 'employee' || $role === 'admin') {
            header("Location: admin.php");
            exit();
        }
        // If role is 'customer', continue normally (no redirect)
    } else {
        // Invalid user ID — force logout
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    $stmt->close();
}

// Fetch POPULAR items — top 4 by total quantity sold
$popular = $conn->query("
    SELECT 
        mi.*,
        COALESCE(SUM(oh.quantity), 0) AS total_sold
    FROM menu_items mi
    LEFT JOIN order_history oh ON mi.name = oh.item_name
    GROUP BY mi.id, mi.name, mi.image, mi.price
    ORDER BY total_sold DESC
    LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RestroDash - Home</title>

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
.nav-right a {
    font-size: 20px;
    font-weight: 700;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 15px;
    background: #2f4f2f;
}

.hero {
    height: 70vh;
    overflow: hidden;
}
.hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.section-title {
    margin: 30px 40px 20px;
    font-size: 32px;
    font-weight: 700;
    color: #2f4f2f;
}

.cards {
    display: flex;
    gap: 25px;
    padding: 0 40px 50px;
    flex-wrap: wrap;
    justify-content: flex-start;
}
.card {
    width: 22%;
    min-width: 200px;
    background: white;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    position: relative;
    text-align: center;
    transition: transform 0.3s;
}
.card:hover {
    transform: translateY(-10px);
}
.card img {
    width: 200px;
    height: 170px;
    object-fit: contain;
    margin: 15px 0;
    border-radius: 12px;
}
.card h3 {
    margin: 15px 0 8px;
    font-size: 26px;
    font-weight: 700;
    color: black;
}
.price {
    margin: 12px 0;
    font-size: 24px;
    font-weight: 700;
    color: #e67e22;
}
.add-btn {
    background: #ffcc00;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 36px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    right: 15px;
    bottom: 15px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    transition: all 0.3s;
}
.add-btn:hover {
    background: #ffb800;
    transform: scale(1.15);
}

@media(max-width:768px){
    .cards {
        flex-direction: column;
        align-items: center;
    }
    .card {
        width: 80%;
        margin-bottom: 20px;
    }
}
</style>
</head>
<body>

<!-- Navbar -->
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="mycart.php"><img src="cart.png" style="height:40px"></a>
            <a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%;"></a>
        <?php else: ?>
            <a href="signin.php">Sign In</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<!-- Hero Section -->
<section class="hero">
    <img src="promote.jpg" alt="Delicious Food">
</section>

<!-- Popular Items -->
<h2 class="section-title">Popular Items</h2>
<div class="cards">

<?php if ($popular->num_rows > 0): ?>
    <?php while ($row = $popular->fetch_assoc()): 
        $image = trim($row['image'] ?? '');
        if (empty($image) || $image === 'a' || strlen($image) < 3) {
            $image = 'food.png';
        }
    ?>
        <div class="card">
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <img src="uploads/<?= htmlspecialchars($image) ?>"
                 alt="<?= htmlspecialchars($row['name']) ?>"
                 onerror="this.src='food.png'">
            <div class="price">
                Rs <?= rtrim(rtrim(number_format($row['price'], 2), '0'), '.') ?>
            </div>
            <a href="add.php?id=<?= $row['id'] ?>" class="add-btn">+</a>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; width:100%; padding:20px; font-size:18px;">No popular items found.</p>
<?php endif; ?>

</div>

</body>
</html>