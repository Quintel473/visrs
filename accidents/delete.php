<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "accidents";

requireRole(["Admin", "Police"]);

$pageTitle = "Delete Accident Record";
$pageSubtitle = "Remove an accident record from the system.";

$accidentID = (int) (
    $_GET["id"]
    ?? $_POST["AccidentID"]
    ?? 0
);

if ($accidentID <= 0) {

    header("Location: index.php");
    exit;
}


/*
 * Load accident record.
 */

$stmt = $pdo->prepare("
    SELECT
        a.AccidentID,
        a.VehicleID,
        a.AccidentDate,
        a.Location,
        a.Description,
        a.DamageLevel,
        a.ReportNumber,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM accidents a

    INNER JOIN vehicles v
        ON a.VehicleID = v.VehicleID

    WHERE a.AccidentID = ?
");

$stmt->execute([
    $accidentID
]);

$accident = $stmt->fetch();


if (!$accident) {

    header("Location: index.php");
    exit;
}


$error = "";


/*
 * Delete accident record.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $pdo->beginTransaction();


        /*
         * Create audit log before deleting
         * the accident record.
         */

        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs
            (
                UserID,
                Action,
                TableAffected,
                RecordID,
                IPAddress
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        $auditStmt->execute([

            $_SESSION["UserID"],

            "Deleted accident record #" .
            $accidentID .
            " for vehicle " .
            $accident["PlateNumber"],

            "accidents",

            $accidentID,

            $_SERVER["REMOTE_ADDR"] ?? null

        ]);


        /*
         * Delete the accident.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM accidents
            WHERE AccidentID = ?
        ");


        $deleteStmt->execute([
            $accidentID
        ]);


        $pdo->commit();


        header("Location: index.php");

        exit;

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }

        $error =
            "Unable to delete the accident record.";
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
        Delete Accident Record - VISRS
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">


    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">


        <?php include __DIR__ . "/../includes/header.php"; ?>


        <div class="content">


            <div class="card">


                <?php if ($error !== ""): ?>

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <h2>
                    Are you sure you want to delete this record?
                </h2>


                <p>

                    <strong>
                        Accident ID:
                    </strong>

                    <?= htmlspecialchars(
                        $accident["AccidentID"]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Vehicle:
                    </strong>

                    <?= htmlspecialchars(
                        $accident["PlateNumber"]
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $accident["Make"]
                    ) ?>

                    <?= htmlspecialchars(
                        $accident["Model"]
                    ) ?>

                    (<?= htmlspecialchars(
                        $accident["VehicleYear"]
                    ) ?>)

                </p>


                <p>

                    <strong>
                        Accident Date:
                    </strong>

                    <?= htmlspecialchars(
                        date(
                            "F j, Y",
                            strtotime(
                                $accident["AccidentDate"]
                            )
                        )
                    ) ?>

                </p>


                <p>

                    <strong>
                        Location:
                    </strong>

                    <?= $accident["Location"]
                        ? htmlspecialchars(
                            $accident["Location"]
                        )
                        : "Not specified"
                    ?>

                </p>


                <p>

                    <strong>
                        Damage Level:
                    </strong>

                    <?= htmlspecialchars(
                        $accident["DamageLevel"]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Report Number:
                    </strong>

                    <?= $accident["ReportNumber"]
                        ? htmlspecialchars(
                            $accident["ReportNumber"]
                        )
                        : "Not specified"
                    ?>

                </p>


                <div
                    style="
                        background:#fff7ed;
                        color:#9a3412;
                        padding:14px;
                        border-radius:8px;
                        margin-top:20px;
                    "
                >

                    <strong>
                        Warning:
                    </strong>

                    Deleting this accident record is permanent
                    and cannot be undone.

                </div>


                <form
                    method="POST"
                    style="margin-top:25px;"
                >

                    <input
                        type="hidden"
                        name="AccidentID"
                        value="<?= $accidentID ?>"
                    >


                    <button
                        type="submit"
                        class="button"
                        style="background:#991b1b;"
                    >
                        Yes, Delete Record
                    </button>


                    <a
                        href="view.php?id=<?= $accidentID ?>"
                        class="button button-secondary"
                    >
                        Cancel
                    </a>

                </form>


            </div>

        </div>

    </main>

</div>

</body>

</html>