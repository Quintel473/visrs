<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["UserID"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Role Authorization
|--------------------------------------------------------------------------
*/

function requireRole($allowedRoles)
{
    if (!in_array($_SESSION["Role"], $allowedRoles)) {

        http_response_code(403);

        echo "<h1>Access Denied</h1>";

        echo "<p>You do not have permission to access this page.</p>";

        echo '<p><a href="dashboard.php">Return to Dashboard</a></p>';

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Vehicle Management Authorization
|--------------------------------------------------------------------------
*/

function canManageVehicles()
{
    return in_array(
        $_SESSION["Role"],
        ["Admin", "Police", "Seller"]
    );
}

?>