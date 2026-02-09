<?php
session_start(); // Required for session check
include "database.php";

// Redirect employees and admins to admin panel
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $role = $row['role'];
        $is_admin = $row['admin'];
        
        if ($role === 'employee' || $is_admin == 1) {
            header("Location: admin.php");
            exit();
        }
    } else {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    $stmt->close();
}

// Fetch ALL items
$all_items = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");

// Fetch top 4 most sold
$popular_items = $conn->query("
    SELECT 
        mi.*,
        COALESCE(SUM(oh.quantity), 0) AS total_sold
    FROM menu_items mi
    LEFT JOIN order_history oh ON mi.name = oh.item_name
    GROUP BY mi.id
    ORDER BY total_sold DESC
    LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu - RestroDash</title>

<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; background:#f5f5f5; }

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
}

.section-title {
    margin:40px 40px 20px; 
    font-size:32px; 
    font-weight:700; 
    color:#2f4f2f;
}

/* Search + Filter Row */
.filter-search-row {
    max-width: 900px;
    margin: 30px auto 40px;
    padding: 0 40px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.search-container {
    flex: 1;
    position: relative;
    min-width: 280px;
}
.search-container input {
    width: 100%;
    padding: 16px 20px 16px 50px;
    font-size: 18px;
    border: 2px solid #ccc;
    border-radius: 50px;
    outline: none;
    transition: all 0.3s;
}
.search-container input:focus {
    border-color: #2f4f2f;
    box-shadow: 0 0 0 3px rgba(47,79,47,0.15);
}
.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 22px;
    color: #777;
}

.filter-container {
    min-width: 220px;
}
.filter-container select {
    width: 100%;
    padding: 16px 20px;
    font-size: 18px;
    border: 2px solid #ccc;
    border-radius: 50px;
    background: white;
    cursor: pointer;
    outline: none;
}
.filter-container select:focus {
    border-color: #2f4f2f;
    box-shadow: 0 0 0 3px rgba(47,79,47,0.15);
}

.hidden { display: none !important; }

.cards {
    display:flex; 
    gap:25px; 
    padding:0 40px 50px; 
    flex-wrap:wrap; 
    justify-content:flex-start;
}
.card {
    width:22%; 
    min-width:200px; 
    background:white; 
    padding:25px; 
    border-radius:20px;
    box-shadow:0 3px 8px rgba(0,0,0,0.15); 
    position:relative; 
    text-align:center;
    transition:transform 0.3s;
}
.card:hover { transform:translateY(-10px); }
.card img {
    width:200px; 
    height:170px; 
    object-fit:contain; 
    margin:15px 0; 
    border-radius:12px;
}
.card h3 { margin:15px 0 8px; font-size:26px; font-weight:700; color:#2f4f2f; }
.price { margin:12px 0; font-size:24px; font-weight:700; color:#e67e22; }
.add-btn {
    background:#ffcc00; 
    color:white; 
    width:60px; height:60px; 
    border-radius:50%;
    font-size:36px; font-weight:bold; 
    display:flex; align-items:center; justify-content:center;
    position:absolute; right:15px; bottom:15px; 
    text-decoration:none;
    box-shadow:0 4px 10px rgba(0,0,0,0.2); 
    transition:all 0.3s;
}
.add-btn:hover { background:#ffb800; transform:scale(1.15); }

.no-results {
    text-align: center;
    padding: 60px 20px;
    font-size: 24px;
    color: #888;
    display: none;
}

@media(max-width:768px){
    .cards { flex-direction:column; align-items:center; }
    .card { width:80%; margin-bottom:20px; }
    .filter-search-row { 
        flex-direction: column; 
        gap: 16px; 
        padding: 0 20px; 
    }
    .search-icon { left: 20px; }
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
            <a href="account.php"><img src="user-icon.png" style="width:50px;height:50px;border-radius:50%"></a>
        <?php else: ?>
            <a href="signin.php">Sign In</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<!-- Search + Filter Row -->
<div class="filter-search-row">
    <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search menu items..." autocomplete="off">
    </div>
    
    <div class="filter-container">
        <select id="categoryFilter">
            <option value="all">All Items</option>
            <option value="veg">Veg Only</option>
            <option value="nonveg">Non-Veg Only</option>
        </select>
    </div>
</div>

<!-- Popular Items Section (hidden when filtering/searching) -->
<div id="popularSection">
    <h2 class="section-title">Popular Items</h2>
    <div class="cards" id="popularCards">
    <?php 
    if ($popular_items->num_rows > 0):
        while($row = $popular_items->fetch_assoc()):
            $img = trim($row['image'] ?? '');
            if (empty($img) || $img === 'a' || strlen($img) < 3) $img = 'food.png';
            
            // Normalize category for data-category attribute
            $raw_cat = trim($row['category'] ?? '');
            $cat = strtolower(str_replace(['-', ' '], '', $raw_cat));
            if ($cat === '') $cat = 'nonveg';
    ?>
        <div class="card" 
             data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>"
             data-category="<?= $cat ?>">
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <img src="uploads/<?= htmlspecialchars($img) ?>" 
                 alt="<?= htmlspecialchars($row['name']) ?>"
                 onerror="this.src='food.png'">
            <div class="price">Rs <?= number_format($row['price'], 2) ?></div>
            <a href="add.php?id=<?= $row['id'] ?>" class="add-btn">+</a>
        </div>
    <?php 
        endwhile;
    else: ?>
        <p style="width:100%;text-align:center;padding:50px;color:#999;font-size:24px;">
            No popular items yet.
        </p>
    <?php endif; ?>
    </div>
</div>

<!-- No results message -->
<div id="noResults" class="no-results">No matching menu items found.</div>

<!-- All Items Section -->
<h2 class="section-title" id="allItemsTitle">All Menu Items</h2>
<div class="cards" id="allCards">
<?php 
if ($all_items->num_rows > 0):
    while($row = $all_items->fetch_assoc()):
        $img = trim($row['image'] ?? '');
        if (empty($img) || $img === 'a' || strlen($img) < 3) $img = 'food.png';
        
        // Normalize category for data-category attribute
        $raw_cat = trim($row['category'] ?? '');
        $cat = strtolower(str_replace(['-', ' '], '', $raw_cat));
        if ($cat === '') $cat = 'nonveg';
?>
        <div class="card" 
             data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>"
             data-category="<?= $cat ?>">
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <img src="uploads/<?= htmlspecialchars($img) ?>" 
                 alt="<?= htmlspecialchars($row['name']) ?>"
                 onerror="this.src='food.png'">
            <div class="price">Rs <?= number_format($row['price'], 2) ?></div>
            <a href="add.php?id=<?= $row['id'] ?>" class="add-btn">+</a>
        </div>
<?php 
    endwhile;
else: ?>
    <p style="width:100%;text-align:center;padding:80px;color:#888;font-size:26px;">
        No items in menu. Add items from admin panel.
    </p>
<?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput    = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const popularSection = document.getElementById('popularSection');
    const noResults      = document.getElementById('noResults');

    const allCards = [
        ...document.querySelectorAll('#popularCards .card'),
        ...document.querySelectorAll('#allCards .card')
    ];

    function applyFilters() {
        const query     = searchInput.value.toLowerCase().trim();
        const catFilter = categoryFilter.value;
        const isActive  = query.length > 0 || catFilter !== 'all';

        popularSection.classList.toggle('hidden', isActive);

        let visibleCount = 0;

        allCards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            let cat    = (card.getAttribute('data-category') || 'nonveg').toLowerCase();

            const nameMatch = name.includes(query);
            let catMatch = true;

            if (catFilter === 'veg')    catMatch = (cat === 'veg');
            if (catFilter === 'nonveg') catMatch = (cat === 'nonveg');

            const show = nameMatch && catMatch;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        noResults.style.display = (isActive && visibleCount === 0) ? 'block' : 'none';
    }

    searchInput.addEventListener('input', applyFilters);
    categoryFilter.addEventListener('change', applyFilters);

    // Run once on load
    applyFilters();
});
</script>

</body>
</html>