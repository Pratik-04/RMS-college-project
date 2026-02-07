<?php
// admin-view-messages.php
session_start();
include "database.php";

// Must be logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// === ONLY EMPLOYEES & ADMINS ALLOWED ===
$stmt_check = $conn->prepare("SELECT role, admin FROM users WHERE id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($row_check = $result_check->fetch_assoc()) {
    $role = $row_check['role'] ?? '';
    $is_admin = (int)($row_check['admin'] ?? 0);

    if ($role !== 'employee' && $is_admin !== 1) {
        header("Location: index.php");
        exit;
    }
} else {
    session_destroy();
    header("Location: signin.php");
    exit;
}
$stmt_check->close();

// Fetch all messages
$stmt = $conn->prepare("
    SELECT id, user_id, name, email, message, created_at 
    FROM contact_messages 
    ORDER BY created_at DESC
");
$stmt->execute();
$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>View Contact Messages - RestroDash</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial, sans-serif; background:#f5f5f5; }

.navbar { height:90px; padding:20px 50px; background:#2f4f2f; color:white; display:flex; justify-content:space-between; align-items:center; }
.nav-left img { height:100px; }
.nav-links { margin-left:auto; margin-right:40px; display:flex; align-items:center; gap:30px; }
.nav-links a { font-size:30px; font-weight:700; color:white; text-decoration:none; }
.nav-right { display:flex; align-items:center; gap:10px; }
.nav-right a { font-size:20px; font-weight:700; color:white; text-decoration:none; padding:10px 20px; border-radius:15px; background:#2f4f2f; }
.nav-right img.user-icon { width:50px; height:50px; border-radius:50%; object-fit:cover; }

.container { max-width:1200px; margin:40px auto; padding:0 20px; }
h1 { text-align:center; color:#2f4f2f; margin-bottom:30px; font-size:36px; }

table { width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 12px rgba(0,0,0,0.1); border-radius:12px; overflow:hidden; }
th, td { padding:14px 16px; text-align:left; border-bottom:1px solid #eee; }
th { background:#2f4f2f; color:white; font-weight:700; }
tr:hover { background:#f9f9f9; }
tr:last-child td { border-bottom:none; }

.message-cell { max-width:400px; white-space:pre-wrap; word-wrap:break-word; }
.date-cell { white-space:nowrap; color:#555; font-size:0.95em; }

.btn-group { display:flex; gap:10px; }
.view-btn {
    padding:8px 16px; background:#2f4f2f; color:white; border:none;
    border-radius:20px; cursor:pointer; font-size:14px;
}
.view-btn:hover { background:#3e5e3e; }

.no-messages { text-align:center; font-size:24px; color:#888; margin:100px 0; }

.modal {
    display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%;
    background:rgba(0,0,0,0.7); justify-content:center; align-items:center;
}
.modal-content {
    background:white; padding:35px; border-radius:16px; max-width:600px; width:90%;
    position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3);
}
.close { position:absolute; top:15px; right:20px; font-size:36px; cursor:pointer; color:#888; }
.close:hover { color:#333; }
.modal-header { margin-bottom:20px; color:#2f4f2f; }
.modal-info { margin:15px 0; font-size:16px; }
.modal-info strong { color:#2f4f2f; }
.modal-message { background:#f8f9fa; padding:20px; border-radius:10px; white-space:pre-wrap; margin-top:20px; }
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
      
        <a href="account.php"><img src="user-icon.png" class="user-icon" alt="Account" onerror="this.src='default-user.png'"></a>
    </div>
</div>

<div class="container">
    <h1>Contact Messages</h1>

    <?php if ($messages->num_rows === 0): ?>
        <p class="no-messages">No messages received yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message Preview</th>
                    <th>Sent At</th>
                    <th>User</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $messages->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td class="message-cell">
                            <?= htmlspecialchars(substr($row['message'], 0, 100)) . (strlen($row['message']) > 100 ? '...' : '') ?>
                        </td>
                        <td class="date-cell"><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
                        <td>
                            <?php if ($row['user_id']): ?>
                                <?= $row['user_id'] ?>
                            <?php else: ?>
                                Guest
                            <?php endif; ?>
                        </td>
                        <td class="btn-group">
                            <button class="view-btn" onclick='openMessageModal(<?= json_encode([
                                'id' => $row['id'],
                                'name' => $row['name'],
                                'email' => $row['email'],
                                'message' => $row['message'],
                                'created_at' => $row['created_at'],
                                'user_id' => $row['user_id'] ?? 'Guest'
                            ], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)'>
                                View
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal for viewing full message -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">×</span>
        <h2 class="modal-header">Message Details</h2>

        <div class="modal-info"><strong>ID:</strong> <span id="msgId"></span></div>
        <div class="modal-info"><strong>Name:</strong> <span id="msgName"></span></div>
        <div class="modal-info"><strong>Email:</strong> <span id="msgEmail"></span></div>
        <div class="modal-info"><strong>Sent:</strong> <span id="msgDate"></span></div>
        <div class="modal-info"><strong>User ID:</strong> <span id="msgUser"></span></div>

        <div class="modal-message" id="msgContent"></div>
    </div>
</div>

<script>
function openMessageModal(msg) {
    document.getElementById('msgId').textContent = '#' + msg.id;
    document.getElementById('msgName').textContent = msg.name;
    document.getElementById('msgEmail').textContent = msg.email;
    document.getElementById('msgDate').textContent = new Date(msg.created_at).toLocaleString();
    document.getElementById('msgUser').textContent = msg.user_id;
    document.getElementById('msgContent').textContent = msg.message;

    document.getElementById('messageModal').style.display = 'flex';
}

function closeMessageModal() {
    document.getElementById('messageModal').style.display = 'none';
}

document.getElementById('messageModal').onclick = function(e) {
    if (e.target === this) closeMessageModal();
};
</script>

</body>
</html>

<?php
$messages->free();
$stmt->close();
$conn->close();
?>  