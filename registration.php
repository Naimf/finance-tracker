<?php
include 'db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $gender = $_POST["gender"];
    $dob = $_POST["dob"];
    $password = trim($_POST["password"]);
    $confirm = trim($_POST["confirm_password"]);

    // Basic validations
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($gender) || empty($dob) || empty($password) || empty($confirm)) {
        $error = "⚠️ Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Invalid email format.";
    } elseif ($password !== $confirm) {
        $error = "⚠️ Passwords do not match.";
    } else {
        // All good, insert into DB
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, username, gender, dob, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $first_name, $last_name, $email, $username, $gender, $dob, $password);

        if ($stmt->execute()) {
            $success = "✅ Registration successful! Redirecting to login...";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 1000);
            </script>";
        } else {
            $error = "⚠️ Registration failed: " . htmlspecialchars($stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registration - Personal Finance Tracker</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
      background-color: #daf3ef;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    form {
      max-width: 600px;
      width: 100%;
      background: #e6fffb;
      padding: 30px 35px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      box-sizing: border-box;
      color: #333;
    }

    .project-title {
      text-align: center;
      font-size: 1.8rem;
      color: #52b69a;
      margin-bottom: 15px;
      font-weight: bold;
      user-select: none;
    }

    h1 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      font-size: 2rem;
      color: #222;
    }

    .flex-row {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }

    .flex-item {
      flex: 1 1 48%;
      display: flex;
      flex-direction: column;
    }

    .full-width {
      display: flex;
      flex-direction: column;
      margin-bottom: 15px;
    }

    label {
      font-weight: 600;
      margin-bottom: 6px;
      color: #444;
    }

    input, select {
      padding: 10px;
      font-size: 1rem;
      border: 1.5px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      transition: border-color 0.2s ease;
    }

    input:focus, select:focus {
      border-color: #52b69a;
      outline: none;
      box-shadow: 0 0 5px #52b69a88;
    }

    button {
      width: 100%;
      padding: 14px 0;
      background: #52b69a;
      color: white;
      font-weight: 700;
      font-size: 1.1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.25s ease;
    }

    button:hover {
      background: #439376;
    }

    p {
      text-align: center;
      margin-top: 20px;
      color: #555;
    }

    p a {
      color: #52b69a;
      text-decoration: none;
      font-weight: 600;
    }

    p a:hover {
      text-decoration: underline;
    }

    .error-message {
      background: #ffdddd;
      border: 1px solid #cc0000;
      color: #cc0000;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }

    .success-message {
      background: #d4edda;
      border: 1px solid #28a745;
      color: #155724;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }

    @media (max-width: 480px) {
      form {
        padding: 25px 20px;
      }
      .flex-row {
        flex-direction: column;
      }
      .flex-item {
        flex: 1 1 100%;
      }
    }
  </style>
</head>
<body>

<form method="POST" onsubmit="return validateForm()">
  <div class="project-title">💰 Personal Finance Tracker</div>
  <h1>Sign Up</h1>

  <?php if (!empty($error)): ?>
    <div class="error-message"><?= $error ?></div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="success-message"><?= $success ?></div>
  <?php endif; ?>

  <div class="flex-row">
    <div class="flex-item">
      <label for="first_name">First Name:</label>
      <input type="text" id="first_name" name="first_name" required>
    </div>
    <div class="flex-item">
      <label for="last_name">Last Name:</label>
      <input type="text" id="last_name" name="last_name" required>
    </div>
  </div>

  <div class="full-width">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>
  </div>

  <div class="full-width">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
  </div>

  <div class="flex-row">
    <div class="flex-item">
      <label for="dob">DOB:</label>
      <input type="date" id="dob" name="dob" required>
    </div>
    <div class="flex-item">
      <label for="gender">Gender:</label>
      <select id="gender" name="gender" required>
        <option value="">Select</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>
    </div>
  </div>

  <div class="flex-row">
    <div class="flex-item">
      <label for="pass">Password:</label>
      <input type="password" id="pass" name="password" required>
    </div>
    <div class="flex-item">
      <label for="cpass">Confirm Password:</label>
      <input type="password" id="cpass" name="confirm_password" required>
    </div>
  </div>

  <button type="submit">Sign Up</button>

  <p>Already have an account? <a href="login.php">Login here</a></p>
</form>

<script>
function validateForm() {
  const pass = document.getElementById("pass").value;
  const cpass = document.getElementById("cpass").value;
  if (pass !== cpass) {
    alert("Passwords do not match!");
    return false;
  }
  return true;
}
</script>

</body>
</html>
