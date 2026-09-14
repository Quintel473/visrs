<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "vehicles";


/*
|--------------------------------------------------------------------------
| Check Permission
|--------------------------------------------------------------------------
*/

if (!canManageVehicles()) {

    http_response_code(403);

    echo "<h1>Access Denied</h1>";
    echo "<p>You do not have permission to delete vehicle records.</p>";
    echo '<p><a href="index.php">Return to Vehicles</a></p>';

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = $_GET["id"] ?? "";

if (!is_numeric($vehicleID)) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Check Vehicle Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        VehicleID,
        PlateNumber,
        Make,
        Model
    FROM vehicles
    WHERE VehicleID = ?
    LIMIT 1
");

$stmt->execute([$vehicleID]);

$vehicle = $stmt->fetch();

if (!$vehicle) {

    http_response_code(404);

    echo "<h1>Vehicle Not Found</h1>";
    echo "<p>The requested vehicle does not exist.</p>";
    echo '<p><a href="index.php">Return to Vehicles</a></p>';

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Related Records
|--------------------------------------------------------------------------
*/

$relatedTables = [
    "ownership_history" => "ownership history",
    "insurance" => "insurance records",
    "accidents" => "accident records"
];

$relatedRecords = [];


foreach ($relatedTables as $table => $label) {

    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM {$table}
        WHERE VehicleID = ?
    ");

    $checkStmt->execute([$vehicleID]);

    $count = $checkStmt->fetch()["total"];

    if ($count > 0) {

        $relatedRecords[] = $count . " " . $label;

    }

}


/*
|--------------------------------------------------------------------------
| Handle Delete Confirmation
|--------------------------------------------------------------------------
*/

$error = "";
$success = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!empty($relatedRecords)) {

        $error = "This vehicle cannot be deleted because related records exist.";

    } else {

        try {

            $deleteStmt = $pdo->prepare("
                DELETE FROM vehicles
                WHERE VehicleID = ?
            ");

            $deleteStmt->execute([$vehicleID]);


            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    UserID,
                    Action,
                    TableAffected,
                    RecordID,
                    IPAddress
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $logStmt->execute([
                $_SESSION["UserID"],
                "Deleted vehicle",
                "vehicles",
                $vehicleID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            header("Location: index.php?deleted=1");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to delete the vehicle. Please try again.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>VISRS - Delete Vehicle</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

       <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <!-- MAIN CONTENT -->

    <div class="main-content">

        <header class="topbar">

            <div class="topbar-title">
                Delete Vehicle
            </div>

            <div class="user-info">

                <span>
                    <?= htmlspecialchars($_SESSION["FirstName"]) ?>
                    <?= htmlspecialchars($_SESSION["LastName"]) ?>
                </span>

                <div class="user-avatar">

                    <?= strtoupper(
                        substr($_SESSION["FirstName"], 0, 1)
                    ) ?>

                </div>

            </div>

        </header>


        <main class="content">

            <h1 class="page-title">
                Delete Vehicle
            </h1>

            <p class="page-subtitle">
                Review the vehicle before permanently deleting the record.
            </p>


            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <section class="card">

                <h2>
                    Vehicle to Delete
                </h2>

                <p style="margin-bottom: 10px;">

                    <strong>Plate Number:</strong>

                    <?= htmlspecialchars($vehicle["PlateNumber"]) ?>

                </p>

                <p style="margin-bottom: 10px;">

                    <strong>Vehicle:</strong>

                    <?= htmlspecialchars($vehicle["Make"]) ?>

                    <?= htmlspecialchars($vehicle["Model"]) ?>

                </p>

                <p style="margin-bottom: 20px;">

                    <strong>Vehicle ID:</strong>

                    <?= htmlspecialchars($vehicle["VehicleID"]) ?>

                </p>


                <?php if (!empty($relatedRecords)): ?>

                    <div style="
                        background: #fee2e2;
                        color: #991b1b;
                        padding: 15px;
                        border-radius: 6px;
                        margin-bottom: 20px;
                    ">

                        <strong>
                            This vehicle cannot be deleted.
                        </strong>

                        <p style="margin-top: 8px;">

                            The vehicle has related records:

                        </p>

                        <ul style="margin: 10px 0 0 20px;">

                            <?php foreach ($relatedRecords as $record): ?>

                                <li>
                                    <?= htmlspecialchars($record) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                    <a
                        href="view.php?id=<?= $vehicle["VehicleID"] ?>"
                        class="button"
                    >
                        Return to Vehicle
                    </a>

                <?php else: ?>

                    <div style="
                        background: #fef3c7;
                        color: #92400e;
                        padding: 15px;
                        border-radius: 6px;
                        margin-bottom: 20px;
                    ">

                        <strong>
                            Warning
                        </strong>

                        <p style="margin-top: 8px;">

                            This action will permanently delete this
                            vehicle record.

                        </p>

                    </div>


                    <form method="POST">

                        <button
                            type="submit"
                            class="button"
                            onclick="return confirm('Are you sure you want to permanently delete this vehicle?');"
                        >
                            Yes, Delete Vehicle
                        </button>

                        <a
                            href="view.php?id=<?= $vehicle["VehicleID"] ?>"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </form>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<script src="../js/app.js"></script>

</body>

</html>