<?php

session_start();

require_once "includes/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE Email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["Password"])) {

            $_SESSION["UserID"] = $user["UserID"];
            $_SESSION["FirstName"] = $user["FirstName"];
            $_SESSION["LastName"] = $user["LastName"];
            $_SESSION["Email"] = $user["Email"];
            $_SESSION["Role"] = $user["Role"];

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid email or password.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VISRS - Login</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <h1>VISRS</h1>

            <p class="login-subtitle">
                Vehicle Information Search & Retrieval System
            </p>

            <h2>Sign In</h2>

            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>

                <button type="submit" class="login-button">
                    Sign In
                </button>

            </form>

        </div>

    </div>

</body>

</html>