<?php
session_start();
include 'db.php';


if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn->set_charset("utf8mb4");

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? null;

    if ($first_name && $last_name && in_array($gender, ['Male', 'Female', 'Other'])) {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, dob=?, gender=? WHERE id=?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $dob, $gender, $user_id);
        $stmt->execute();
        $_SESSION['msg'] = "User updated successfully.";
    } else {
        $_SESSION['msg'] = "Please fill in all required fields correctly.";
    }
    header("Location: admin.php");
    exit;
}

// user deletion
if (isset($_GET['delete_user_id'])) {
    $delete_id = intval($_GET['delete_user_id']);
    // Delete user and related data (optional: transactions, accounts)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

  

    $_SESSION['msg'] = "User deleted successfully.";
    header("Location: admin.php");
    exit;
}

//////////////////////Normal login
$stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin' ORDER BY id ASC");
$stmt->execute();
$result_users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Panel - Personal Finance Tracker</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #e6f2e6;
    margin: 0; padding: 0;
  }
  .container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  }
  h1 {
    color: #52b69a;
    text-align: center;
    margin-bottom: 25px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  thead th {
    background: #52b69a;
    color: white;
    padding: 12px 10px;
    text-align: left;
  }
  tbody tr:nth-child(even) {
    background: #f9f9f9;
  }
  tbody tr:hover {
    background: #d3f0e0;
  }
  td, th {
    padding: 12px 10px;
    border: 1px solid #ddd;
    vertical-align: middle;
  }
  input[type="text"], input[type="date"], select {
    width: 100%;
    padding: 6px 8px;
    font-size: 1rem;
    border: 1.5px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
  }
  input[type="text"]:disabled {
    background: #eee;
  }
  select:disabled {
    background: #eee;
  }
  .profile-pic {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #52b69a;
  }
  button {
    background-color: #52b69a;
    border: none;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #409074;
  }
  .delete-link {
    color: #d9534f;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
  }
  .delete-link:hover {
    text-decoration: underline;
  }
  .msg {
    background-color: #dff0d8;
    color: #3c763d;
    border-radius: 8px;
    padding: 10px 15px;
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
  }
  .monthly-summary-row {
    background-color: #f1f9f1;
    font-style: italic;
    color: #3c763d;
  }
  @media (max-width: 768px) {
    td, th {
      font-size: 14px;
      padding: 8px 6px;
    }
    button {
      padding: 6px 10px;
      font-size: 0.9rem;
    }
  }
</style>
<script>
function confirmDelete(id) {
    return confirm('Are you sure you want to delete user ID ' + id + '? This action cannot be undone.');
}
</script>
</head>
<body>

<div class="container">

  <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <div style="font-weight: 600; color: #409074;">
      Logged in as: <?= htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email'] ?? 'Admin') ?>
    </div>
    <div>
      <a href="logout.php" style="
          background-color: #d9534f;
          color: white;
          padding: 6px 12px;
          border-radius: 6px;
          text-decoration: none;
          font-weight: 600;
          transition: background-color 0.3s ease;
        "
        onmouseover="this.style.backgroundColor='#c9302c'"
        onmouseout="this.style.backgroundColor='#d9534f'"
      >Logout</a>
    </div>
  </div>

  <h1>Admin Panel - User Management</h1>

  <?php if (!empty($_SESSION['msg'])): ?>
    <div class="msg"><?= htmlspecialchars($_SESSION['msg']) ?></div>
    <?php unset($_SESSION['msg']); ?>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>Profile</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email (read-only)</th>
        <th>Password (read-only)</th>
        <th>Date of Birth</th>
        <th>Gender</th>
        <th>Role (read-only)</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($user = $result_users->fetch_assoc()): ?>
      <tr>
        <form method="POST" onsubmit="return confirm('Save changes for this user?');">
          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
          <td>
            <?php if ($user['profile_picture']): ?>
              <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-pic" />
            <?php else: ?>
              <img src="default-avatar.png" alt="No Picture" class="profile-pic" />
            <?php endif; ?>
          </td>
          <td><input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required></td>
          <td><input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required></td>
          <td><input type="text" value="<?= htmlspecialchars($user['email']) ?>" disabled></td>
          <td><input type="text" value="<?= htmlspecialchars($user['password']) ?>" disabled></td>
          <td><input type="date" name="dob" value="<?= htmlspecialchars($user['dob']) ?>"></td>
          <td>
            <select name="gender" required>
              <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
              <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
          </td>
          <td><input type="text" value="<?= htmlspecialchars($user['role']) ?>" disabled></td>
          <td>
            <button type="submit" name="update_user">Save</button>
            <a href="admin.php?delete_user_id=<?= $user['id'] ?>" onclick="return confirmDelete(<?= $user['id'] ?>)" class="delete-link">Delete</a>
          </td>
        </form>
      </tr>

      <?php
      // Fetch monthly summary for this user
      $year = date('Y');
      $month = date('m');
      $stmtSum = $conn->prepare("SELECT 
        SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS total_income,
        SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
      $stmtSum->bind_param("iii", $user['id'], $year, $month);
      $stmtSum->execute();
      $summary = $stmtSum->get_result()->fetch_assoc();
      $total_income = $summary['total_income'] ?? 0;
      $total_expense = $summary['total_expense'] ?? 0;
      $net_balance = $total_income - $total_expense;
      ?>

      <tr class="monthly-summary-row">
        <td colspan="9">
          <strong>Monthly Summary (<?= date('F Y') ?>):</strong>
          Income: <?= number_format($total_income, 2) ?> BDT &nbsp; | &nbsp;
          Expense: <?= number_format($total_expense, 2) ?> BDT &nbsp; | &nbsp;
          Net Balance: <?= number_format($net_balance, 2) ?> BDT
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>
