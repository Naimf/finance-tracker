<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_picture = null;

    if ($new_password !== '' || $confirm_password !== '') {
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New password and confirm password do not match.";
            header("Location: profile.php");
            exit;
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error'] = "Password should be at least 6 characters.";
            header("Location: profile.php");
            exit;
        }
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '.' . $ext;
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture = $filename;
        } else {
            $_SESSION['error'] = "Failed to upload profile picture.";
            header("Location: profile.php");
            exit;
        }
    }

    if (empty($_SESSION['error'])) {
        if ($profile_picture && $new_password !== '') {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, dob=?, gender=?, profile_picture=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $first_name, $last_name, $username, $email, $dob, $gender, $profile_picture, $new_password, $user_id);
        } elseif ($profile_picture) {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, dob=?, gender=?, profile_picture=? WHERE id=?");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $username, $email, $dob, $gender, $profile_picture, $user_id);
        } elseif ($new_password !== '') {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, dob=?, gender=?, password=? WHERE id=?");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $username, $email, $dob, $gender, $new_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, dob=?, gender=? WHERE id=?");
            $stmt->bind_param("ssssssi", $first_name, $last_name, $username, $email, $dob, $gender, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating profile.";
        }

        header("Location: profile.php");
        exit;
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #daf3ef;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 650px;
            background: #e6fffb;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .profile-card {
            width: 100%;
        }

        .top-bar {
            text-align: right;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #444;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-square {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #52b69a;
        }

        .profile-form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            color: #444;
        }

        .profile-form input,
        .profile-form select {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
        }

        .profile-form input:focus,
        .profile-form select:focus {
            border-color: #52b69a;
            outline: none;
            box-shadow: 0 0 5px #52b69a88;
        }

        .profile-form button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #52b69a;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .profile-form button:hover {
            background: #3f9274;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 14px 20px;
            font-size: 1rem;
            color: white;
            background: #28a745;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 1;
            transition: opacity 0.5s ease;
            z-index: 9999;
        }

        .toast.error {
            background: #cc0000;
        }

        a {
            color: #52b69a;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        p {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-card">
        <div class="top-bar">
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> |
            <a href="logout.php">Logout</a>
        </div>

        <h2>Your Profile</h2>

        <div class="profile-section">
            <a href="uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.jpg') ?>" target="_blank">
                <img src="uploads/<?= htmlspecialchars($user['profile_picture'] ?? 'default.jpg') ?>" alt="Profile Picture" class="profile-square">
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <label>First Name:
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </label>
            <label>Last Name:
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
            </label>
            <label>Username:
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </label>
            <label>Email:
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </label>
            <label>Date of Birth:
                <input type="date" name="dob" value="<?= htmlspecialchars($user['dob']) ?>" required>
            </label>
            <label>Gender:
                <select name="gender" required>
                    <option value="">Select</option>
                    <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $user['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </label>
            <label>New Password (optional):
                <input type="password" name="new_password" minlength="6">
            </label>
            <label>Confirm New Password:
                <input type="password" name="confirm_password" minlength="6">
            </label>
            <label>Profile Picture (optional):
                <input type="file" name="profile_picture" accept="image/*">
            </label>

            <button type="submit">Update Profile</button>
        </form>

        <p><a href="index.php">&larr; Back to Dashboard</a></p>
    </div>
</div>

<div id="toast" class="toast" style="display:none;"></div>

<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.innerText = message;
        toast.className = 'toast ' + type;
        toast.style.display = 'block';
        toast.style.opacity = '1';

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.style.display = 'none', 500);
        }, 3000);
    }

    <?php if ($success): ?>
        showToast("<?= $success ?>", "success");
    <?php endif; ?>
    <?php if ($error): ?>
        showToast("<?= $error ?>", "error");
    <?php endif; ?>
</script>

</body>
</html>
