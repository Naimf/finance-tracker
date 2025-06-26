<?php
include 'db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-cache, must-revalidate");

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$transaction_id = intval($_GET['id']);

// Fetch transaction to edit
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Transaction not found or access denied.";
    exit;
}

$transaction = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $category = htmlspecialchars(trim($_POST['category']));
    $amount = floatval($_POST['amount']);
    $notes = htmlspecialchars(trim($_POST['notes']));
    $date = $_POST['date'];

    $update_stmt = $conn->prepare("UPDATE transactions SET type = ?, category = ?, amount = ?, notes = ?, date = ? WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("ssdssii", $type, $category, $amount, $notes, $date, $transaction_id, $user_id);

    if ($update_stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Failed to update transaction: " . $conn->error;
    }
}
?>

<h2>Edit Transaction</h2>

<form method="POST">
    Type:
    <select name="type" required>
        <option value="income" <?= $transaction['type'] === 'income' ? 'selected' : '' ?>>Income</option>
        <option value="expense" <?= $transaction['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
    </select><br>

    Category: <input type="text" name="category" value="<?= htmlspecialchars($transaction['category']) ?>" required><br>
    Amount: <input type="number" step="0.01" name="amount" value="<?= $transaction['amount'] ?>" required><br>
    Notes (optional): <input type="text" name="notes" value="<?= htmlspecialchars($transaction['notes']) ?>"><br>
    Date: <input type="date" name="date" value="<?= $transaction['date'] ?>" required><br>

    <button type="submit">Update Transaction</button>
</form>

<p><a href="index.php">&larr; Back to Dashboard</a></p>
