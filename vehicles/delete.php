<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Delete Vehicle";
$pageSubtitle = "Review the vehicle before permanently deleting the record";

$activePage = "vehicles";

$error = "";


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($vehicleID <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Retrieve Vehicle
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        VehicleID,
        PlateNumber,
        VIN,
        OwnerID,
        Make,
        Model,
        VehicleYear,
        Color,
        VehicleType,
        EngineNumber,
        RegistrationDate,
        Status
    FROM vehicles
    WHERE VehicleID = ?
    LIMIT 1
");

$stmt->execute([
    $vehicleID
]);

$vehicle = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Vehicle Not Found
|--------------------------------------------------------------------------
*/

if (!$vehicle) {

    http_response_code(404);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Vehicle Not Found - VISRS
        </title>

        <link
            rel="stylesheet"
            href="/visrs/css/style.css"
        >

    </head>

    <body>

    <div class="layout">

        <main
            class="main-content"
            style="
                margin-left:0;
                width:100%;
            "
        >

            <div
                class="content"
                style="
                    max-width:700px;
                    margin:80px auto;
                "
            >

                <div class="card">

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >

                        <strong>
                            Vehicle Not Found
                        </strong>

                    </div>

                    <h2>
                        The requested vehicle does not exist.
                    </h2>

                    <p>
                        The vehicle may have already been deleted
                        or the requested ID is invalid.
                    </p>

                    <a
                        href="index.php"
                        class="button"
                    >
                        Return to Vehicles
                    </a>

                </div>

            </div>

        </main>

    </div>

    </body>

    </html>

    <?php

    exit;

}


/*
|--------------------------------------------------------------------------
| Check Related Records
|--------------------------------------------------------------------------
|
| A vehicle should not be deleted when related records exist.
| This protects ownership history, insurance records, and
| accident records from becoming disconnected.
|
*/

$relatedTables = [

    "ownership_history" =>
        "ownership history",

    "insurance" =>
        "insurance records",

    "accidents" =>
        "accident records"

];

$relatedRecords = [];


foreach ($relatedTables as $table => $label) {

    $checkStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM {$table}
        WHERE VehicleID = ?
    ");

    $checkStmt->execute([
        $vehicleID
    ]);

    $count =
        (int) $checkStmt->fetchColumn();


    if ($count > 0) {

        $relatedRecords[] =
            $count . " " . $label;

    }

}


/*
|--------------------------------------------------------------------------
| Handle Delete Confirmation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    requireValidCSRF();


    /*
    |--------------------------------------------------------------------------
    | Verify Submitted Vehicle ID
    |--------------------------------------------------------------------------
    */

    $submittedVehicleID =
        isset($_POST["VehicleID"])
            ? (int) $_POST["VehicleID"]
            : 0;


    if (
        $submittedVehicleID !==
        $vehicleID
    ) {

        $error =
            "Invalid vehicle deletion request.";

    }


    /*
    |--------------------------------------------------------------------------
    | Re-check Related Records
    |--------------------------------------------------------------------------
    |
    | The related records are checked again during POST processing.
    | This prevents deletion if a related record was created after
    | the confirmation page was originally loaded.
    |
    */

    if ($error === "") {

        $relatedRecords = [];


        foreach (
            $relatedTables
            as $table => $label
        ) {

            $checkStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM {$table}
                WHERE VehicleID = ?
            ");

            $checkStmt->execute([
                $vehicleID
            ]);

            $count =
                (int) $checkStmt->fetchColumn();


            if ($count > 0) {

                $relatedRecords[] =
                    $count . " " . $label;

            }

        }


        if (!empty($relatedRecords)) {

            $error =
                "This vehicle cannot be deleted because related records exist.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Delete Vehicle
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            /*
            |--------------------------------------------------------------------------
            | Begin Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Delete Vehicle
            |--------------------------------------------------------------------------
            */

            $deleteStmt = $pdo->prepare("
                DELETE FROM vehicles
                WHERE VehicleID = ?
            ");

            $deleteStmt->execute([
                $vehicleID
            ]);


            /*
            |--------------------------------------------------------------------------
            | Confirm Vehicle Was Deleted
            |--------------------------------------------------------------------------
            */

            if ($deleteStmt->rowCount() !== 1) {

                throw new RuntimeException(
                    "Vehicle could not be deleted."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Record Audit Log
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

                (int) $_SESSION["UserID"],

                "Deleted vehicle",

                "vehicles",

                $vehicleID,

                $_SERVER["REMOTE_ADDR"] ?? null

            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Return to Vehicle Management
            |--------------------------------------------------------------------------
            */

            header(
                "Location: index.php?deleted=1"
            );

            exit;


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Roll Back Failed Transaction
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                "Unable to delete the vehicle. Please try again.";

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

    <title>
        <?= htmlspecialchars($pageTitle) ?> - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>


<body>

<div class="layout">


    <?php

    $activePage = "vehicles";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">


        <?php include __DIR__ . "/../includes/header.php"; ?>


        <main class="content">


            <h1 class="page-title">
                Delete Vehicle
            </h1>


            <p class="page-subtitle">
                Review the vehicle before permanently deleting the record.
            </p>


            <?php if ($error !== ""): ?>

                <div
                    class="error-message"
                    style="
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <section class="card">


                <h2>
                    Vehicle to Delete
                </h2>


                <!-- PLATE NUMBER -->

                <p style="margin-bottom:10px;">

                    <strong>
                        Plate Number:
                    </strong>

                    <?= htmlspecialchars(
                        $vehicle["PlateNumber"]
                    ) ?>

                </p>


                <!-- VEHICLE -->

                <p style="margin-bottom:10px;">

                    <strong>
                        Vehicle:
                    </strong>

                    <?= htmlspecialchars(
                        $vehicle["Make"]
                    ) ?>

                    <?= htmlspecialchars(
                        $vehicle["Model"]
                    ) ?>

                    <?php if (!empty($vehicle["VehicleYear"])): ?>

                        (<?= (int) $vehicle["VehicleYear"] ?>)

                    <?php endif; ?>

                </p>


                <!-- VIN -->

                <p style="margin-bottom:10px;">

                    <strong>
                        VIN:
                    </strong>

                    <?= htmlspecialchars(
                        $vehicle["VIN"]
                    ) ?>

                </p>


                <!-- VEHICLE ID -->

                <p style="margin-bottom:20px;">

                    <strong>
                        Vehicle ID:
                    </strong>

                    <?= (int) $vehicle["VehicleID"] ?>

                </p>


                <?php if (!empty($relatedRecords)): ?>


                    <!-- RELATED RECORD WARNING -->

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:15px;
                            border-radius:6px;
                            margin-bottom:20px;
                        "
                    >

                        <strong>
                            This vehicle cannot be deleted.
                        </strong>


                        <p style="margin-top:8px;">

                            The vehicle has related records:

                        </p>


                        <ul
                            style="
                                margin:10px 0 0 20px;
                            "
                        >

                            <?php foreach (
                                $relatedRecords as $record
                            ): ?>

                                <li>
                                    <?= htmlspecialchars(
                                        $record
                                    ) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>


                        <p style="margin-top:12px;">

                            These records must remain connected
                            to the vehicle for the VISRS audit
                            and historical data to remain intact.

                        </p>

                    </div>


                    <a
                        href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                        class="button"
                    >
                        Return to Vehicle
                    </a>


                <?php else: ?>


                    <!-- DELETE WARNING -->

                    <div
                        style="
                            background:#fef3c7;
                            color:#92400e;
                            padding:15px;
                            border-radius:6px;
                            margin-bottom:20px;
                        "
                    >

                        <strong>
                            Warning
                        </strong>


                        <p style="margin-top:8px;">

                            This action will permanently delete
                            this vehicle record.

                        </p>


                        <p style="margin-top:8px;">

                            This action cannot be undone.

                        </p>

                    </div>


                    <!-- DELETE FORM -->

                    <form
                        method="POST"
                        action=""
                    >


                        <!-- CSRF TOKEN -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                generateCSRFToken()
                            ) ?>"
                        >


                        <!-- VEHICLE ID -->

                        <input
                            type="hidden"
                            name="VehicleID"
                            value="<?= (int) $vehicle["VehicleID"] ?>"
                        >


                        <button
                            type="submit"
                            class="button"
                            style="
                                background:#991b1b;
                            "
                        >
                            Yes, Delete Vehicle
                        </button>


                        <a
                            href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>


                    </form>


                <?php endif; ?>


            </section>


        </main>


    </main>


</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>