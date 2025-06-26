<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-cache, must-revalidate");
include 'db.php';

$user_id = $_SESSION['user_id'];

// user info for profile picture
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();
$profile_pic = !empty($user_info['profile_picture']) ? 'uploads/' . htmlspecialchars($user_info['profile_picture']) : 'uploads/default.jpg';

//  form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $category = htmlspecialchars(trim($_POST['category']));
    $amount = floatval($_POST['amount']);
    $notes = isset($_POST['notes']) ? htmlspecialchars(trim($_POST['notes'])) : null;
    $date = $_POST['date'];

    if (!empty($type) && !empty($category) && $amount > 0 && !empty($date)) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, notes, date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $user_id, $type, $category, $amount, $notes, $date);

        if ($stmt->execute()) {
            echo "<script>
        alert('Transaction added successfully!');
        window.location.href = 'add.php';
    </script>";
          //  header("Location: add.php");
            
            exit;
        } else {
            echo "<p style='color:red;'>Failed to add transaction: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Please fill all required fields correctly.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Transaction - Personal Finance Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #daf3ef;
            margin: 0;
            padding: 40px 20px 20px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            max-width: 650px;
            width: 100%;
        }
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            color: #444;
            font-weight: 600;
        }
        .top-bar img.profile-thumb {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #52b69a;
            margin-right: 8px;
        }
        .top-bar a {
            color: #52b69a;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .top-bar a:hover {
            color: #3f9274;
            text-decoration: underline;
        }
        .form-card {
            background: #e6fffb;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
            width: 100%;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #444;
            margin-top: 15px;
        }
        form select,
        form input[type="text"],
        form input[type="number"],
        form input[type="date"] {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
        }
        form select:focus,
        form input[type="text"]:focus,
        form input[type="number"]:focus,
        form input[type="date"]:focus {
            border-color: #52b69a;
            outline: none;
            box-shadow: 0 0 6px #52b69a88;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        button {
            cursor: pointer;
            font-size: 1.1rem;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            color: white;
            background-color: #52b69a;
            font-weight: bold;
            transition: background-color 0.3s ease;
            flex: 1;
            margin: 0 5px;
        }
        button:hover {
            background-color: #3f9274;
        }
        button.clear-btn {
            background-color: #a6a6a6;
        }
        button.clear-btn:hover {
            background-color: #7a7a7a;
        }
        p.back-link {
            text-align: center;
            margin-top: 20px;
        }
        p.back-link a {
            color: #52b69a;
            font-weight: 600;
            text-decoration: none;
        }
        p.back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="top-bar">
    <a href="profile.php" title="Go to Profile">
        <img src="<?= $profile_pic ?>" alt="Profile Picture" class="profile-thumb">
    </a>
    <span>Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
    <a href="profile.php">Profile</a>
    <a href="logout.php">Logout</a>
    </div>


    <div class="form-card">
        <h2>Add Transaction</h2>

        <form method="POST" id="transactionForm">
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="">Select</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>

            <label for="category">Category:</label>
            <input type="text" name="category" id="category" required>

            <label for="amount">Amount:</label>
            <input type="number" step="0.01" name="amount" id="amount" required>

            <label for="notes">Note/Comment (optional):</label>
            <input type="text" name="notes" id="notes">

            <label for="date">Date:</label>
            <input type="date" name="date" id="date" required>

            <div class="btn-group">
                <button type="submit">Add Transaction</button>
                <button type="button" class="clear-btn" onclick="document.getElementById('transactionForm').reset();">Clear</button>
            </div>
        </form>

        <p class="back-link"><a href="index.php">&larr; Back to Dashboard</a></p>
    </div>

</div>

</body>
</html>
