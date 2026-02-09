<?php
session_start();
include "database.php";

//require login
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        /* NAVBAR - SAME AS index.php */
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
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            font-size: 30px;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-right a {
            font-size: 20px;
            font-weight: 700;
            color: white;
            padding: 10px 20px;
            border-radius: 15px;
            background: #2f4f2f;
            text-decoration: none;
        }

        .nav-right img.user-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Removed cart image styling since cart is gone */
        
        /* Employee Section */
        .employee-section {
            margin: 30px 50px;
        }

        .employee-section h2 {
            margin-bottom: 20px;
            font-size: 32px;
        }

        .employee-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .employee-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }

        .employee-card:hover {
            transform: scale(1.03);
        }

        .employee-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .employee-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .employee-card p {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .employee-card .btn {
            margin: 10px 5px 0;
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .employee-card .btn:hover {
            background: #45a049;
        }

        /* Modal */
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
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        /* Edit Form */
        .edit-form label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
        }

        .edit-form input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .edit-form .btn-group {
            margin-top: 20px;
            text-align: right;
        }

        .edit-form .btn-group button {
            padding: 10px 20px;
            margin-left: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .save-btn {
            background: #4CAF50;
            color: white;
        }

        .cancel-btn {
            background: #f44336;
            color: white;
        }

        /* Add Employee Form */
        .form-section {
            margin: 30px 50px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-section h2 {
            margin-bottom: 15px;
        }

        .form-section input {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-section button {
            padding: 10px 20px;
            background: #2f4f2f;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- NAVBAR - CART REMOVED -->
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
    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php"><img src="user-icon.png" class="user-icon" alt="Account" onerror="this.src='default-user.png'"></a>
        <?php else: ?>
            <a href="signin.php">Sign In</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<!-- Add New Employee Form -->
<section class="form-section">
    <h2>Add New Employee</h2>
    <form id="addEmployeeForm">
        <input type="text" id="empName" placeholder="Name" required>
        <input type="text" id="empPhone" placeholder="Phone" required>
        <input type="email" id="empEmail" placeholder="Email" required>
        <input type="text" id="empAddress" placeholder="Address" required>
        <input type="text" id="empDuty" placeholder="Duty Time" required>
        <input type="text" id="empImg" placeholder="Image URL (optional)">
        <button type="submit">Add Employee</button>
    </form>
</section>

<!-- Employee List -->
<section class="employee-section">
    <h2>Employees</h2>
    <div id="employee-list" class="employee-list"></div>
</section>

<!-- Detail Modal -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <img id="modalImg" src="" alt="Employee Photo" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
        <h3 id="modalName"></h3>
        <p id="modalPhone"></p>
        <p id="modalDuty"></p>
        <p id="modalEmail"></p>
        <p id="modalAddress"></p>
        <button id="editBtn" style="background:#FF9800;color:white;padding:10px 20px;border:none;border-radius:8px;margin-right:10px;">Edit</button>
        <button id="deleteBtn" style="background:#f44336;color:white;padding:10px 20px;border:none;border-radius:8px;">Remove</button>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <h2 style="text-align:center;margin-bottom:20px;">Edit Employee</h2>
        <form class="edit-form" id="editEmployeeForm">
            <label>Name</label>
            <input type="text" id="editName" required>
            <label>Phone</label>
            <input type="text" id="editPhone" required>
            <label>Email</label>
            <input type="email" id="editEmail" required>
            <label>Address</label>
            <input type="text" id="editAddress" required>
            <label>Duty Time</label>
            <input type="text" id="editDuty" required>
            <label>Image URL</label>
            <input type="text" id="editImg">
            <div class="btn-group">
                <button type="button" class="cancel-btn" id="cancelEditBtn">Cancel</button>
                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let employees = JSON.parse(localStorage.getItem('employees')) || [
        { name:"Prince", phone:"9876543210", duty_time:"10 AM - 10 PM", email:"prince@restrodash.com", address:"Kathmandu", img:"user-icon.png" },
        { name:"Ram", phone:"9876543211", duty_time:"10 AM - 10 PM", email:"ram@restrodash.com", address:"Bhaktapur", img:"user-icon.png" }
    ];

    const list = document.getElementById('employee-list');
    const detailModal = document.getElementById('employeeModal');
    const editModal = document.getElementById('editModal');
    let currentIndex = null;

    function render() {
        list.innerHTML = '';
        employees.forEach((emp, i) => {
            const card = document.createElement('div');
            card.className = 'employee-card';
            card.innerHTML = `
                <img src="${emp.img || 'user-icon.png'}" alt="${emp.name}" onerror="this.src='user-icon.png'">
                <h3>${emp.name}</h3>
                <p>Phone: ${emp.phone}</p>
                <p>Duty: ${emp.duty_time}</p>
                <button class="btn">See more...</button>
            `;
            card.querySelector('.btn').onclick = () => {
                currentIndex = i;
                showDetail(emp);
            };
            list.appendChild(card);
        });
        localStorage.setItem('employees', JSON.stringify(employees));
    }

    function showDetail(emp) {
        document.getElementById('modalImg').src = emp.img || 'user-icon.png';
        document.getElementById('modalName').textContent = emp.name;
        document.getElementById('modalPhone').textContent = "Phone: " + emp.phone;
        document.getElementById('modalDuty').textContent = "Duty: " + emp.duty_time;
        document.getElementById('modalEmail').textContent = "Email: " + emp.email;
        document.getElementById('modalAddress').textContent = "Address: " + emp.address;
        detailModal.style.display = 'flex';
    }

    document.querySelectorAll('.close').forEach(btn => {
        btn.onclick = () => {
            detailModal.style.display = 'none';
            editModal.style.display = 'none';
        };
    });

    window.onclick = e => {
        if (e.target === detailModal || e.target === editModal) {
            detailModal.style.display = 'none';
            editModal.style.display = 'none';
        }
    };

    document.getElementById('deleteBtn').onclick = () => {
        if (currentIndex !== null && confirm('Remove this employee?')) {
            employees.splice(currentIndex, 1);
            render();
            detailModal.style.display = 'none';
        }
    };

    document.getElementById('editBtn').onclick = () => {
        if (currentIndex === null) return;
        const emp = employees[currentIndex];
        document.getElementById('editName').value = emp.name;
        document.getElementById('editPhone').value = emp.phone;
        document.getElementById('editEmail').value = emp.email;
        document.getElementById('editAddress').value = emp.address;
        document.getElementById('editDuty').value = emp.duty_time;
        document.getElementById('editImg').value = emp.img || '';
        detailModal.style.display = 'none';
        editModal.style.display = 'flex';
    };

    document.getElementById('cancelEditBtn').onclick = () => editModal.style.display = 'none';

    document.getElementById('editEmployeeForm').onsubmit = e => {
        e.preventDefault();
        if (currentIndex === null) return;
        employees[currentIndex] = {
            name: document.getElementById('editName').value.trim(),
            phone: document.getElementById('editPhone').value.trim(),
            email: document.getElementById('editEmail').value.trim(),
            address: document.getElementById('editAddress').value.trim(),
            duty_time: document.getElementById('editDuty').value.trim(),
            img: document.getElementById('editImg').value.trim() || 'user-icon.png'
        };
        render();
        editModal.style.display = 'none';
    };

    document.getElementById('addEmployeeForm').onsubmit = e => {
        e.preventDefault();
        employees.push({
            name: document.getElementById('empName').value.trim(),
            phone: document.getElementById('empPhone').value.trim(),
            email: document.getElementById('empEmail').value.trim(),
            address: document.getElementById('empAddress').value.trim(),
            duty_time: document.getElementById('empDuty').value.trim(),
            img: document.getElementById('empImg').value.trim() || 'user-icon.png'
        });
        e.target.reset();
        render();
    };

    render();
});
</script>

</body>
</html>