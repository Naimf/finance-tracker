<?php
session_start();
include 'db.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Plain text password check (not recommended but as per your request)
        if ($user["password"] === $password) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["first_name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Personal Finance Tracker</title>
    <style>
        /* Full page wrapper */
        .full-page-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #d0f0eb, #52b69a);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Form card container */
        .form-card {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            text-align: center;
        }

        /* Project title */
        .project-title {
            margin: 0 0 15px 0;
            font-size: 1.8rem;
            color: #52b69a;
            user-select: none;
        }

        /* Headings */
        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Error message styling */
        .error {
            background-color: #ffdede;
            color: #d93025;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Form styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
            text-align: left;
        }

        /* Labels */
        label {
            font-weight: 600;
            color: #444;
            display: flex;
            flex-direction: column;
            font-size: 1rem;
        }

        /* Inputs */
        input[type="email"],
        input[type="password"] {
            margin-top: 6px;
            padding: 12px 14px;
            font-size: 1rem;
            border: 1.8px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.3s ease;
            outline-offset: 2px;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #52b69a;
            outline: none;
            box-shadow: 0 0 5px #52b69a66;
        }

        /* Submit button */
        button[type="submit"] {
            background-color: #52b69a;
            color: white;
            border: none;
            padding: 14px 0;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #409074;
        }

        /* Bottom text */
        p {
            font-size: 0.9rem;
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
    </style>
</head>
<body>

<div class="full-page-wrapper">
    <div class="form-card">
        <h1 class="project-title">💰 Personal Finance Tracker</h1>
        <h2>Login</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Email:
                <input type="email" name="email" placeholder="example@email.com" required>
            </label>
            <label>Password:
                <input type="password" name="password" placeholder="********" required>
            </label>
            <button type="submit">Login</button>
        </form>

        <p style="margin-top: 10px;">
            Don’t have an account? <a href="registration.php">Sign Up Here</a>
        </p>
    </div>
</div>

</body>
</html>
