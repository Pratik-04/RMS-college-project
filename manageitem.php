<?php
session_start();
include "database.php";

// require login
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// ADD or UPDATE item
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name        = trim($_POST['itemName']);
    $price       = (float)$_POST['itemPrice'];
    $category    = trim($_POST['itemCategory']);
    $description = trim($_POST['itemDescription'] ?? '');
    $id          = $_POST['edit_id'] ?? '';

    // Handle Image Upload
    $imageName = "";
    if (!empty($_FILES['itemImg']['name'])) {
        $imageName = time() . "_" . basename($_FILES['itemImg']['name']);
        move_uploaded_file($_FILES['itemImg']['tmp_name'], "uploads/" . $imageName);
    }

    if ($id == "") {
        // Insert new item
        if ($imageName == "") {
            $sql = "INSERT INTO menu_items (name, price, category, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdss", $name, $price, $category, $description);
        } else {
            $sql = "INSERT INTO menu_items (name, price, category, description, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsss", $name, $price, $category, $description, $imageName);
        }
    } else {
        // Update existing item
        if ($imageName == "") {
            $sql = "UPDATE menu_items SET name=?, price=?, category=?, description=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssi", $name, $price, $category, $description, $id);
        } else {
            $sql = "UPDATE menu_items SET name=?, price=?, category=?, description=?, image=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imageName, $id);
        }
    }

    $stmt->execute();
    header("Location: manageitem.php");
    exit;
}

// DELETE ITEM
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM menu_items WHERE id = $deleteId");
    header("Location: manageitem.php");
    exit;
}

// FETCH ITEMS
$items = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu Items</title>

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial, sans-serif; background:#f5f5f5; }
        .navbar {
            height:90px; padding:20px 50px; background:#2f4f2f; color:white;
            display:flex; justify-content:space-between; align-items:center;
        }
        .nav-left img { height:100px; }
        .nav-links {
            margin-left:auto; margin-right:40px; display:flex; align-items:center; gap:30px;
        }
        .nav-links a {
            font-size:30px; font-weight:700; color:white; text-decoration:none;
        }
        .container { max-width:1350px; margin:40px auto; padding:20px; }
        h1 { text-align:center; font-size:42px; color:#2f4f2f; margin-bottom:30px; }
        .add-form {
            background:white; padding:30px; border-radius:18px;
            box-shadow:0 8px 25px rgba(0,0,0,0.1); margin-bottom:40px;
            border-left:8px solid #ffcc00;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 0.9fr 1.2fr 1.6fr 1.4fr auto;
            gap: 15px;
            align-items: end;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        select {
            padding:14px; border-radius:10px; border:1px solid #ccc; font-size:17px;
        }
        select { 
            appearance: none; 
            background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="6"><polygon points="0,0 12,0 6,6" fill="%23333"/></svg>') no-repeat right 14px center; 
            background-size:12px; 
        }
        input[type="file"]::file-selector-button {
            background:#2f4f2f; color:white; padding:12px 20px; border:none;
            border-radius:8px; cursor:pointer; font-weight:bold; margin-right:12px; font-size:16px;
        }
        input[type="file"]::file-selector-button:hover { background:#3e5e3e; }
        textarea { resize:vertical; min-height:44px; }
        button {
            background:#2f4f2f; color:white; padding:14px 24px; border:none;
            border-radius:10px; font-weight:bold; cursor:pointer; font-size:17px;
        }
        button:hover { background:#3e5e3e; }
        #currentImageBox { margin-top:15px; grid-column:1 / -1; }
        #currentImagePreview { margin-top:8px; border-radius:10px; max-height:150px; }

        /* ────────────────────────────────────────
           FORM-SPECIFIC STYLING – name, price & description same height
        ──────────────────────────────────────── */
        #itemName,
        #itemPrice,
        #itemDescription {
            font-size: 15px;
            padding: 11px 14px;
            height: 44px;
            line-height: 1.4;
        }

        #itemDescription {
            resize: vertical;           /* still allow user to expand if needed */
            min-height: 44px;
        }

        /* Placeholders to match the compact size */
        #itemName::placeholder,
        #itemPrice::placeholder,
        #itemDescription::placeholder {
            font-size: 14px;
            color: #999;
        }

        /* Category, file and button stay comfortable */
        #itemCategory,
        input[type="file"] {
            font-size: 16px;
            padding: 14px;
        }

        #submitBtn {
            font-size: 16px;
            padding: 14px 28px;
            min-height: 50px;
        }

        /* Table styles */
        table {
            width:100%; background:white; border-radius:18px; overflow:hidden;
            box-shadow:0 8px 25px rgba(0,0,0,0.1); table-layout:fixed;
        }
        th { background:#2f4f2f; color:white; padding:20px 18px; text-align:left; font-size:18px; }
        td { padding:20px 18px; border-bottom:1px solid #eee; vertical-align:top; word-wrap:break-word; line-height:1.5; }
        td img { width:80px; height:80px; object-fit:cover; border-radius:12px; }
        .price-cell { font-size:15px; color:#666; }
        .category-cell {
            font-weight:500;
            color: #27ae60;
        }
        .category-cell.non-veg { color: #c0392b; }
        .action-btn {
            padding:8px 14px; margin:0 5px; border:none; border-radius:8px;
            color:white; cursor:pointer; font-weight:bold;
        }
        .edit-btn { background:#f39c12; }
        .edit-btn:hover { background:#e67e22; }
        .delete-btn { background:#e74c3c; }
        .delete-btn:hover { background:#c0392b; }
        .no-items { text-align:center; padding:60px; color:#888; font-size:24px; }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 4%; }
        th:nth-child(2), td:nth-child(2) { width: 10%; }
        th:nth-child(3), td:nth-child(3) { width: 18%; }
        th:nth-child(4), td:nth-child(4) { width: 10%; }
        th:nth-child(5), td:nth-child(5) { width: 10%; }
        th:nth-child(6), td:nth-child(6) { width: 32%; }
        th:nth-child(7), td:nth-child(7) { width: 16%; }

        @media (max-width: 1100px) {
            .form-grid { grid-template-columns: 1fr 1fr; }
            .form-grid > *:last-child { grid-column: 1 / -1; }
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
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
        <div class="nav-right" id="authArea"></div>
    </div>

    <div class="container">
        <h1>Manage Menu Items</h1>

        <!-- ADD / EDIT FORM -->
        <div class="add-form">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="edit_id">

                <div class="form-grid">
                    <input type="text"    id="itemName"        name="itemName"        placeholder="Item Name" required>
                    <input type="number"  id="itemPrice"       name="itemPrice"       placeholder="Price (Rs)" min="1" step="0.01" required>
                    <select name="itemCategory" id="itemCategory" required>
                        <option value="" disabled selected>Select Category</option>
                        <option value="Veg">Veg</option>
                        <option value="Non-veg">Non-veg</option>
                        <option value="Beverage">Beverage</option>
                        <option value="Dessert">Dessert</option>
                    </select>
                    <textarea id="itemDescription" name="itemDescription" placeholder="Item Description (optional)" rows="1"></textarea>
                    <input type="file" id="itemImg" name="itemImg" accept="image/*">
                    <button id="submitBtn">Add Item</button>
                </div>

                <div id="currentImageBox" style="display:none;">
                    <p>Current Image:</p>
                    <img id="currentImagePreview" src="" width="120" height="120">
                </div>
            </form>
        </div>

        <!-- MENU TABLE -->
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0) :
                    $i = 1; ?>
                    <?php while ($row = $items->fetch_assoc()) : ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt=""></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td class="category-cell <?= strtolower(str_replace(' ','-',$row['category'])) ?>">
                                <?= htmlspecialchars($row['category'] ?: '—') ?>
                            </td>
                            <td class="price-cell">Rs <?= number_format($row['price'], 2) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['description'] ?? '—')) ?></td>
                            <td>
                                <button class="action-btn edit-btn"
                                    onclick="editItem(
                                        <?= $row['id'] ?>,
                                        '<?= addslashes(htmlspecialchars($row['name'])) ?>',
                                        <?= $row['price'] ?>,
                                        '<?= addslashes(htmlspecialchars($row['category'])) ?>',
                                        '<?= addslashes(htmlspecialchars($row['description'] ?? '')) ?>',
                                        '<?= $row['image'] ?>'
                                    )">
                                    Edit
                                </button>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this item permanently?')">
                                    <button class="action-btn delete-btn">Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="no-items">No items in menu yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        let isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        document.getElementById('authArea').innerHTML = isLoggedIn
            ? `<a href="account.php"><img src="user-icon.png" alt="User Profile" style="width:50px; height:50px; border-radius:50%; object-fit:cover;"></a>`
            : `<a href="signin.php">Sign In</a><a href="signup.php" style="margin-left:15px;">Sign Up</a>`;

        function editItem(id, name, price, category, description, image) {
            document.getElementById("edit_id").value = id;
            document.getElementById("itemName").value = name;
            document.getElementById("itemPrice").value = price;
            document.getElementById("itemCategory").value = category;
            document.getElementById("itemDescription").value = description.replace(/\\n/g, '\n');

            if (image && image !== "") {
                document.getElementById("currentImageBox").style.display = "block";
                document.getElementById("currentImagePreview").src = "uploads/" + image;
            } else {
                document.getElementById("currentImageBox").style.display = "none";
            }

            document.getElementById("submitBtn").textContent = "Update Item";
            window.scrollTo({ top: 0, behavior: "smooth" });
        }

        window.addEventListener('pageshow', function() {
            if (document.getElementById("edit_id").value === "") {
                document.getElementById("submitBtn").textContent = "Add Item";
                document.getElementById("currentImageBox").style.display = "none";
                document.getElementById("itemCategory").value = "";
            }
        });
    </script>

</body>
</html>