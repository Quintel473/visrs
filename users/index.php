<?php

require_once "../includes/auth.php";

requireRole(["Admin"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VISRS - User Management</title>

</head>

<body>

    <h1>User Management</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["FirstName"]) ?>
        <?= htmlspecialchars($_SESSION["LastName"]) ?>
    </p>

    <p>
        Only administrators can access this page.
    </p>

    <a href="../dashboard.php">Back to Dashboard</a>

</body>

</html>