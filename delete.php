<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-cache, must-revalidate");
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_GET['id']);


$stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaction_id, $user_id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit;
} else {
    echo "Failed to delete transaction: " . $conn->error;
}
?>
