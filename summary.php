<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-cache, must-revalidate");

include 'db.php';

$user_id = $_SESSION['user_id'];
$year = date('Y');
$month = date('m');


echo "<div style='text-align:right; margin-bottom:10px;'>
        Logged in as <strong>{$_SESSION['username']}</strong> | 
        <a href='profile.php'>Profile</a> | 
        <a href='logout.php'>Logout</a>
      </div>";

// 1. Monthly Summary (income, expense, net)
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

// 2. Expense by Category (Pie Chart data)
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

// 3. Monthly Income and Expense for bar chart
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Monthly Summary & Charts</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  h2, h3 { margin-top: 1.5em; }
  ul { list-style: none; padding: 0; }
  ul li { margin-bottom: 0.5em; }
  .chart-container { max-width: 700px; margin-top: 40px; }
</style>
</head>
<body>

<h2>Monthly Summary for <?= date('F Y') ?></h2>
<ul>
    <li><strong>Total Income:</strong> <?= number_format($total_income, 2) ?> BDT</li>
    <li><strong>Total Expense:</strong> <?= number_format($total_expense, 2) ?> BDT</li>
    <li><strong>Net Balance:</strong> <?= number_format($net_balance, 2) ?> BDT</li>
</ul>

<div class="chart-container">
  <h3>Expense Breakdown by Category (Pie Chart)</h3>
  <canvas id="expensePieChart"></canvas>
</div>

<div class="chart-container">
  <h3>Monthly Income vs Expense (Bar Chart)</h3>
  <canvas id="monthlyBarChart"></canvas>
</div>

<script>
const categoryLabels = <?= json_encode(array_keys($category_data)) ?>;
const categoryValues = <?= json_encode(array_values($category_data)) ?>;

const monthlyLabels = <?= json_encode(array_column($monthly_data, 'month')) ?>;
const incomeData = <?= json_encode(array_column($monthly_data, 'income')) ?>;
const expenseData = <?= json_encode(array_column($monthly_data, 'expense')) ?>;

// Pie chart for expenses
const ctxPie = document.getElementById('expensePieChart').getContext('2d');
const expensePieChart = new Chart(ctxPie, {
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

// Bar chart for monthly income and expense
const ctxBar = document.getElementById('monthlyBarChart').getContext('2d');
const monthlyBarChart = new Chart(ctxBar, {
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
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<p><a href="index.php">&larr; Back to Dashboard</a></p>

</body>
</html>
