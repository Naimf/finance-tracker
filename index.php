<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

/// trans delete
if (isset($_GET['delete_tx_id'])) {
    $delete_id = intval($_GET['delete_tx_id']);
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    
    header("Location: index.php");
    exit;
}

// acc dele
if (isset($_GET['delete_account_id'])) {
    $delete_acc_id = intval($_GET['delete_account_id']);
    $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_acc_id, $user_id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// add acc
$account_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $account_type = htmlspecialchars(trim($_POST['account_type']));
    $account_name = htmlspecialchars(trim($_POST['account_name']));
    $account_number = htmlspecialchars(trim($_POST['account_number']));

    if ($account_type && $account_name && $account_number) {
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, account_name, account_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $account_type, $account_name, $account_number);
        if ($stmt->execute()) {
            
            $_SESSION['account_success'] = "Account added successfully.";
        } else {
            $_SESSION['account_error'] = "Failed to add account: " . $conn->error;
        }
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['account_error'] = "Please fill in all account fields.";
        header("Location: index.php");
        exit;
    }
}

// Filter 
$filter_category = $_GET['category'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where_clauses = ["user_id = $user_id"];
if ($filter_category) $where_clauses[] = "category = '" . $conn->real_escape_string($filter_category) . "'";
if ($filter_type && in_array($filter_type, ['income', 'expense'])) $where_clauses[] = "type = '" . $filter_type . "'";
if ($filter_date_from) $where_clauses[] = "date >= '" . $conn->real_escape_string($filter_date_from) . "'";
if ($filter_date_to) $where_clauses[] = "date <= '" . $conn->real_escape_string($filter_date_to) . "'";
$where_sql = implode(' AND ', $where_clauses);

//  Monthly Summary 
$year = date('Y');
$month = date('m');

$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
    FROM transactions
    WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_income = $res['total_income'] ?? 0;
$total_expense = $res['total_expense'] ?? 0;
$net_balance = $total_income - $total_expense;

//  Expense by category (pie chart) 
$category_data = [];
$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM transactions 
    WHERE user_id = ? AND type = 'expense' AND MONTH(date) = ? AND YEAR(date) = ? 
    GROUP BY category");
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $category_data[$row['category']] = (float)$row['total'];
}

//  Monthly income/expense for bar chart 
$monthly_data = [];
for ($i = 1; $i <= 12; $i++) {
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense
        FROM transactions WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("iii", $user_id, $i, $year);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $monthly_data[] = [
        'month' => date("M", mktime(0, 0, 0, $i, 1)),
        'income' => (float)($res['income'] ?? 0),
        'expense' => (float)($res['expense'] ?? 0),
    ];
}

// Fetch Transactions 
$sql_transactions = "SELECT * FROM transactions WHERE $where_sql ORDER BY date DESC";
$result_transactions = $conn->query($sql_transactions);

// Fetch distinct categories for filter dropdown 
$categories_result = $conn->query("SELECT DISTINCT category FROM transactions WHERE user_id = $user_id");

//  Fetch accounts for current user 
$stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_accounts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Dashboard - Personal Finance Tracker</title>
<style>
  * {
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background: #52b69a;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
  }

  .top-bar {
    background: #ffffff;
    padding: 12px 20px;
    text-align: right;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .top-bar img {
    border-radius: 50%;
    vertical-align: middle;
    margin-right: 10px;
    border: 2px solid #ccc;
  }

  .top-bar a {
    text-decoration: none;
    color: #0077cc;
    margin-left: 10px;
  }

  h1, h2, h3 {
    color: #333;
    margin-bottom: 15px;
  }

  section {
    background: #ffffff;
    padding: 25px 30px;
    margin-bottom: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  ul.summary {
    list-style: none;
    padding: 0;
    font-size: 1.1em;
  }

  ul.summary li {
    margin-bottom: 8px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }

  th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
  }

  th {
    background: lightgreen;
  }

  .filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
  }

  .filter-form label {
    display: flex;
    flex-direction: column;
    font-size: 14px;
    color: #333;
  }

  .filter-form input,
  .filter-form select {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
  }

  .filter-form button {
    padding: 8px 16px;
    border: none;
    background: #52b69a;
    color: white;
    border-radius: 5px;
    cursor: pointer;
  }

  .filter-form a {
    text-decoration: none;
    color: #007bff;
    font-size: 14px;
  }

  .charts {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
  }

  .chart-container {
    flex: 1 1 45%;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    min-width: 300px;
  }

  .delete-link {
    color: #d9534f;
    text-decoration: none;
    font-weight: bold;
  }

  .delete-link:hover {
    text-decoration: underline;
  }

  .back-link {
    display: inline-block;
    margin-top: 10px;
    text-decoration: none;
    color: #52b69a;
  }

  .account-table th {
    background-color: lightgreen;
  }

  .account-table td, .account-table th {
    padding: 10px;
  }

  @media (max-width: 768px) {
    .filter-form {
      flex-direction: column;
      align-items: flex-start;
    }

    .charts {
      flex-direction: column;
    }

    .chart-container {
      width: 100%;
    }
  }
</style>

<script>
function confirmDeleteTransaction(id) {
    return confirm('Are you sure you want to delete this transaction?');
}
function confirmDeleteAccount(id) {
    return confirm('Are you sure you want to delete this account?');
}
</script>
</head>
<body>

<div class="top-bar">
  <?php if (!empty($user['profile_picture'])): ?>
    <a href="profile.php">
      <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" width="50" height="50">
    </a>
  <?php endif; ?>
  Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> | 
  <a href="profile.php">Profile</a> | 
  <a href="logout.php">Logout</a>
</div>

<h1>Dashboard</h1>

<!-- Monthly Summary -->
<section>
  <h2>Monthly Summary for <?= date('F Y') ?></h2>
  <ul class="summary">
    <li><strong>Total Income:</strong> <?= number_format($total_income, 2) ?> BDT</li>
    <li><strong>Total Expense:</strong> <?= number_format($total_expense, 2) ?> BDT</li>
    <li><strong>Net Balance:</strong> <?= number_format($net_balance, 2) ?> BDT</li>
  </ul>
</section>

<!-- Filter/Search Transactions -->
<section>
  <h2>Transactions</h2>
  <form class="filter-form" method="GET">
    <label>Category:
      <select name="category">
        <option value="">All</option>
        <?php while ($cat = $categories_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($cat['category']) ?>" <?= ($filter_category === $cat['category']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['category']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>

    <label>Type:
      <select name="type">
        <option value="">All</option>
        <option value="income" <?= ($filter_type === 'income') ? 'selected' : '' ?>>Income</option>
        <option value="expense" <?= ($filter_type === 'expense') ? 'selected' : '' ?>>Expense</option>
      </select>
    </label>

    <label>Date From:
      <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>">
    </label>

    <label>Date To:
      <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>">
    </label>

    <button type="submit">Filter</button>
    <button type="reset" style="margin-left:10px; background: #ccc; color: #000; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">
  Reset
</button>

  </form>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Category</th>
        <th>Amount (BDT)</th>
        <th>Note</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result_transactions->num_rows > 0): ?>
        <?php while ($tx = $result_transactions->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($tx['date']) ?></td>
            <td><?= htmlspecialchars(ucfirst($tx['type'])) ?></td>
            <td><?= htmlspecialchars($tx['category']) ?></td>
            <td><?= number_format($tx['amount'], 2) ?></td>
            <td><?= htmlspecialchars($tx['notes'] ?? '') ?></td>
            <td class="action-links">
              <a href="edit.php?id=<?= $tx['id'] ?>">Edit</a>
              <a href="index.php?delete_tx_id=<?= $tx['id'] ?>" onclick="return confirmDeleteTransaction(<?= $tx['id'] ?>)" class="delete-link">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No transactions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <p><a href="add.php" class="back-link" style="display: inline-block; margin-top: 15px;">&larr; Add New Transaction</a></p>
</section>

<!-- Accounts Management -->
<section>
  <h2>Your Accounts</h2>
  <?php if ($result_accounts->num_rows > 0): ?>
  <table class="account-table">
    <thead>
      <tr>
        <th>Type</th>
        <th>Name</th>
        <th>Number</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($acc = $result_accounts->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($acc['account_type']) ?></td>
          <td><?= htmlspecialchars($acc['account_name']) ?></td>
          <td><?= htmlspecialchars($acc['account_number']) ?></td>
          <td>
            <a href="index.php?delete_account_id=<?= $acc['id'] ?>" onclick="return confirmDeleteAccount(<?= $acc['id'] ?>)" class="delete-link">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>No accounts found.</p>
  <?php endif; ?>

  <p><a href="account.php">&raquo; Add New Account</a></p>
</section>

<!-- Charts at the bottom -->
<section class="charts">
  <div class="chart-container">
    <h3>Expense Breakdown by Category (Pie Chart)</h3>
    <canvas id="expensePieChart"></canvas>
  </div>

  <div class="chart-container">
    <h3>Monthly Income vs Expense (Bar Chart)</h3>
    <canvas id="monthlyBarChart"></canvas>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const categoryLabels = <?= json_encode(array_keys($category_data)) ?>;
const categoryValues = <?= json_encode(array_values($category_data)) ?>;

const monthlyLabels = <?= json_encode(array_column($monthly_data, 'month')) ?>;
const incomeData = <?= json_encode(array_column($monthly_data, 'income')) ?>;
const expenseData = <?= json_encode(array_column($monthly_data, 'expense')) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const ctxPie = document.getElementById('expensePieChart').getContext('2d');
  new Chart(ctxPie, {
    type: 'pie',
    data: {
      labels: categoryLabels,
      datasets: [{
        data: categoryValues,
        backgroundColor: [
          '#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF',
          '#FF9F40','#8BC34A','#E91E63','#009688','#607D8B'
        ]
      }]
    }
  });

  const ctxBar = document.getElementById('monthlyBarChart').getContext('2d');
  new Chart(ctxBar, {
    type: 'bar',
    data: {
      labels: monthlyLabels,
      datasets: [
        {
          label: 'Income',
          backgroundColor: '#4CAF50',
          data: incomeData
        },
        {
          label: 'Expense',
          backgroundColor: '#F44336',
          data: expenseData
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
});
</script>

</body>
</html>
