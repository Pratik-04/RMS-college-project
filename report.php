<?php
session_start();
include "database.php";

// Only show orders with status = 'Completed' in sales report
$stmt = $conn->prepare("
    SELECT item_name, item_img, quantity, total, order_date, location 
    FROM order_history 
    WHERE status = 'Completed'
    ORDER BY order_date DESC
");

$stmt->execute();
$result = $stmt->get_result();
$completedOrders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - RestroDash</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; min-height: 100vh; }

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
        .user-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid transparent;
        }
        .menu-toggle { display: none; font-size: 32px; cursor: pointer; }

        .container {
            max-width: 1100px;
            margin: 130px auto 60px;
            padding: 20px;
        }
        h2 {
            text-align: center;
            font-size: 38px;
            color: #2f4f2f;
            margin-bottom: 40px;
            font-weight: 700;
        }
        .report-card {
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 28px;
            color: #2f4f2f;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .input-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        input[type="date"], input[type="month"] {
            padding: 15px;
            border: 2px solid #ccc;
            border-radius: 12px;
            font-size: 18px;
            min-width: 250px;
        }
        button {
            padding: 15px 35px;
            background: #2f4f2f;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover { background: #1e4a1e; }

        .note {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }

        .result {
            margin-top: 25px;
            padding: 30px;
            background: #f8fff8;
            border: 3px solid #27ae60;
            border-radius: 15px;
            font-size: 19px;
            line-height: 2;
            min-height: 120px;
        }
        .highlight { color: #27ae60; font-weight: bold; font-size: 32px; }
        .location-tag {
            background: #ffcc00;
            color: black;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
            display: inline-block;
        }
        .top-items {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            border: 1px dashed #aaa;
        }
        .top-items li { padding: 8px 0; font-size: 18px; }

        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; height: 80px; }
            .nav-left img { height: 80px; }
            .nav-links {
                position: fixed; top: 80px; left: -100%; width: 100%;
                background: #2f4f2f; flex-direction: column; transition: 0.4s; padding-top: 30px;
            }
            .nav-links.active { left: 0; }
            .nav-links a { font-size: 26px; padding: 20px; }
            .menu-toggle { display: block; }
            .input-group { flex-direction: column; }
            input, button { width: 100%; }
            h2 { font-size: 32px; }
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
        <a href="book-table.php">BOOK TABLE</a>
    </div>
    <div class="nav-right">
        <a href="account.php">
            <img src="user-icon.png" class="user-icon" alt="User">
        </a>
    </div>
    <div class="menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('active')">
        Menu
    </div>
</div>

<div class="container">
    <h2>Sales Report</h2>

    <!-- Daily Report -->
    <div class="report-card">
        <div class="section-title">Daily Sales Report</div>
        <div class="input-group">
            <input type="date" id="dailyDate" max="<?= date('Y-m-d') ?>">
            <button onclick="generateReport('daily')">Generate Daily Report</button>
        </div>
        <div class="note">Only dates up to today can be selected</div>
        <div id="dailyResult" class="result">Select a date to view completed sales</div>
    </div>

    <!-- Monthly Report -->
    <div class="report-card">
        <div class="section-title">Monthly Sales Report</div>
        <div class="input-group">
            <input type="month" id="monthlyDate" max="<?= date('Y-m') ?>">
            <button onclick="generateReport('monthly')">Generate Monthly Report</button>
        </div>
        <div class="note">Only months up to the current month can be selected</div>
        <div id="monthlyResult" class="result">Select a month to view completed sales</div>
    </div>
</div>

<script>
    const orders = <?= json_encode($completedOrders) ?>;

    function generateReport(type) {
        const dailyInput   = document.getElementById('dailyDate').value;
        const monthlyInput = document.getElementById('monthlyDate').value;

        if ((type === 'daily' && !dailyInput) || (type === 'monthly' && !monthlyInput)) {
            alert("Please select a " + (type === 'daily' ? "date" : "month"));
            return;
        }

        let totalRevenue = 0;
        let totalItems   = 0;
        let itemsMap     = {};
        let locationMap  = {};

        const selectedDate = type === 'daily' 
            ? new Date(dailyInput + 'T00:00:00')
            : (() => {
                const [y, m] = monthlyInput.split('-');
                return new Date(y, m-1, 1);
            })();

        orders.forEach(order => {
            const orderDate = new Date(order.order_date);

            let match = false;
            if (type === 'daily') {
                match = orderDate.toDateString() === selectedDate.toDateString();
            } else {
                match = (
                    orderDate.getFullYear() === selectedDate.getFullYear() &&
                    orderDate.getMonth() === selectedDate.getMonth()
                );
            }

            if (match) {
                const qty   = parseInt(order.quantity)   || 0;
                const total = parseFloat(order.total)    || 0;

                totalRevenue += total;
                totalItems   += qty;

                const name = order.item_name?.trim() || 'Unknown Item';
                itemsMap[name] = (itemsMap[name] || 0) + qty;

                const loc = (order.location?.trim() || "Unknown").replace(/\s+/g, ' ');
                locationMap[loc] = (locationMap[loc] || 0) + qty;
            }
        });

        const resultDiv = document.getElementById(type + 'Result');

        if (totalItems === 0) {
            resultDiv.innerHTML = `
                <div style="color:#7f8c8d; font-size:22px; font-weight:600; margin-bottom:12px;">
                    No completed orders found
                </div>
                <p style="font-size:17px;">
                    No sales data available for the selected period.<br>
                    Either no orders were completed, or try another date/month.
                </p>
            `;
            return;
        }

        // Find top location
        let topLocation = "N/A";
        let maxCount = 0;
        for (let loc in locationMap) {
            if (locationMap[loc] > maxCount) {
                maxCount = locationMap[loc];
                topLocation = loc;
            }
        }

        const topItemsList = Object.entries(itemsMap)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10)
            .map(([name, qty]) => `<li><b>${name}</b> → ${qty} sold</li>`)
            .join('');

        resultDiv.innerHTML = `
            <div style="font-size:26px; color:#27ae60; margin-bottom:18px; font-weight:700;">
                ${type === 'daily' ? 'Daily' : 'Monthly'} Completed Sales
            </div>
            <p>Total Revenue: <span class="highlight">Rs ${totalRevenue.toLocaleString('en-US')}</span></p>
            <p>Total Items Sold: <b>${totalItems.toLocaleString('en-US')}</b></p>
            <p>Top Delivery Location: <span class="location-tag">${topLocation} (${maxCount} items)</span></p>
            ${topItemsList ? `
                <div class="top-items">
                    <b>Top Selling Items (this period):</b>
                    <ul>${topItemsList}</ul>
                </div>
            ` : ''}
        `;
    }
</script>

</body>
</html>