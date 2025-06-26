<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-cache, must-revalidate");
include 'db.php';

$user_id = $_SESSION['user_id'];

$stmt_user = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_res = $stmt_user->get_result();
$user_info = $user_res->fetch_assoc();
$profile_pic = !empty($user_info['profile_picture']) ? 'uploads/' . htmlspecialchars($user_info['profile_picture']) : 'uploads/default.jpg';

$error_msg = '';

// Handle Add Account submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_account'])) {
        $account_type = htmlspecialchars(trim($_POST['account_type']));
        $account_name = htmlspecialchars(trim($_POST['account_name']));
        $account_number = htmlspecialchars(trim($_POST['account_number']));

        if (!empty($account_type) && !empty($account_name) && !empty($account_number)) {
            $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_type, account_name, account_number) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $account_type, $account_name, $account_number);
            if ($stmt->execute()) {
                header("Location: account.php?success=1");
                exit;
            } else {
                $error_msg = "Failed to add account: " . $stmt->error;
            }
        } else {
            $error_msg = "Please fill in all fields!";
        }
    }

    // Handle Remove Account submission
    if (isset($_POST['remove_account'])) {
        $account_id = intval($_POST['account_id']);
        // Verify account belongs to user before deleting
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $account_id, $user_id);
        if ($stmt->execute()) {
            header("Location: account.php?remove_success=1");
            exit;
        } else {
            $error_msg = "Failed to remove account: " . $stmt->error;
        }
    }
}

$show_success_popup = isset($_GET['success']) && $_GET['success'] == 1;
$show_remove_success_popup = isset($_GET['remove_success']) && $_GET['remove_success'] == 1;

$stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Your Accounts - Personal Finance Tracker</title>
<style>
    /* ... same styles as before ... */
    * {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #daf3ef;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        background-color: #e6fffb;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.1);
        max-width: 700px;
        width: 100%;
    }
    .top-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
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
        cursor: pointer;
        transition: filter 0.3s ease;
    }
    .top-bar img.profile-thumb:hover {
        filter: brightness(0.9);
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
    h2, h3 {
        color: #333;
        margin-bottom: 20px;
        text-align: center;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 30px;
        font-size: 0.95rem;
    }
    table th, table td {
        border: 1px solid #bbb;
        padding: 12px 15px;
        text-align: left;
        color: #333;
        vertical-align: middle;
    }
    table th {
        background-color: #52b69a;
        color: white;
        font-weight: 600;
    }
    tr:nth-child(even) {
        background-color: #dff6f2;
    }
    form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #444;
        margin-top: 15px;
    }
    form select,
    form input[type="text"] {
        width: 100%;
        padding: 10px;
        font-size: 1rem;
        border: 1.5px solid #ccc;
        border-radius: 6px;
        transition: border-color 0.2s ease;
    }
    form select:focus,
    form input[type="text"]:focus {
        border-color: #52b69a;
        outline: none;
        box-shadow: 0 0 6px #52b69a88;
    }
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 25px;
    }
    .button-group button {
        flex: 1;
        padding: 12px 0;
        font-size: 1.1rem;
        border: none;
        border-radius: 6px;
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .button-group button[type="submit"] {
        background-color: #52b69a;
    }
    .button-group button[type="submit"]:hover {
        background-color: #3f9274;
    }
    .button-group button.clear-btn {
        background-color: #a6a6a6;
    }
    .button-group button.clear-btn:hover {
        background-color: #7a7a7a;
    }
    .error-msg {
        color: red;
        font-weight: 600;
        margin-bottom: 15px;
        text-align: center;
    }
    .remove-btn {
        background-color: #e05252;
        border: none;
        color: white;
        padding: 8px 14px;
        font-weight: 600;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .remove-btn:hover {
        background-color: #b53f3f;
    }
    p.back-link {
        text-align: center;
        margin-top: 30px;
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
<script>
    function clearForm() {
        document.getElementById('accountForm').reset();
    }

    window.onload = function() {
        const showSuccess = <?= $show_success_popup ? 'true' : 'false' ?>;
        const showRemoveSuccess = <?= $show_remove_success_popup ? 'true' : 'false' ?>;
        if (showSuccess) {
            alert('Account added successfully!');
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url.toString());
            }
        }
        if (showRemoveSuccess) {
            alert('Account removed successfully!');
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('remove_success');
                window.history.replaceState({}, document.title, url.toString());
            }
        }
    }
</script>
</head>
<body>

<div class="container">

    <div class="top-bar">
        <a href="profile.php" title="Go to Profile">
            <img src="<?= $profile_pic ?>" alt="Profile Picture" class="profile-thumb" />
        </a>
        <span>Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>

    <h2>Your Accounts</h2>

    <?php if ($error_msg): ?>
        <p class="error-msg"><?= $error_msg ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Number</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['account_type']) ?></td>
                        <td><?= htmlspecialchars($row['account_name']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove this account?');" style="margin:0;">
                                <input type="hidden" name="remove_account" value="1">
                                <input type="hidden" name="account_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; color:#555;">No account records found.</p>
    <?php endif; ?>

    <h3>Add New Account</h3>

    <form method="POST" id="accountForm" novalidate>
        <input type="hidden" name="add_account" value="1">

        <label for="account_type">Account Type:</label>
        <select name="account_type" id="account_type" required>
            <option value="">-- Select Type --</option>
            <option value="Bank">Bank</option>
            <option value="Mobile Banking">Mobile Banking</option>
        </select>

        <label for="account_name">Account Name:</label>
        <select name="account_name" id="account_name" required>
            <option value="">-- Select Account Name --</option>
            <!-- Physical Banks -->
            <optgroup label="Banks">
                <option value="Sonali Bank">Sonali Bank</option>
                <option value="Janata Bank">Janata Bank</option>
                <option value="Agrani Bank">Agrani Bank</option>
                <option value="Rupali Bank">Rupali Bank</option>
                <option value="Dutch Bangla Bank">Dutch Bangla Bank</option>
                <option value="Islami Bank">Islami Bank</option>
                <option value="AB Bank">AB Bank</option>
                <option value="Bank Asia">Bank Asia</option>
                <option value="Eastern Bank">Eastern Bank</option>
            </optgroup>
            <!-- Mobile Banking -->
            <optgroup label="Mobile Banking">
                <option value="bKash">bKash</option>
                <option value="Nagad">Nagad</option>
                <option value="Rocket">Rocket</option>
                <option value="Upay">Upay</option>
                <option value="Tap">Tap</option>
            </optgroup>
        </select>

        <label for="account_number">Account Number:</label>
        <input type="text" name="account_number" id="account_number" placeholder="Account Number" required>

        <div class="button-group">
            <button type="submit">Add Account</button>
            <button type="button" class="clear-btn" onclick="clearForm()">Clear</button>
        </div>
    </form>

    <p class="back-link"><a href="index.php">&larr; Back to Dashboard</a></p>
</div>

</body>
</html>
