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


/*
 * Validate accident ID.
 */

if ($accidentID <= 0) {

    header("Location: index.php");
    exit;
}


$error = "";
$accident = null;


/*
 * Load accident record.
 */

function loadAccidentRecord($pdo, $accidentID)
{
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

    return $stmt->fetch();
}


/*
 * Initial record lookup.
 */

$accident = loadAccidentRecord(
    $pdo,
    $accidentID
);


if (!$accident) {

    header("Location: index.php");
    exit;
}


/*
 * Process deletion.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Verify CSRF token.
     */

    requireValidCSRF();


    /*
     * Re-check the record immediately before deletion.
     *
     * This prevents deleting a record based on
     * stale confirmation-page information.
     */

    $accident = loadAccidentRecord(
        $pdo,
        $accidentID
    );


    if (!$accident) {

        header("Location: index.php");
        exit;
    }


    try {

        $pdo->beginTransaction();


        /*
         * Delete the accident record.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM accidents
            WHERE AccidentID = ?
        ");

        $deleteStmt->execute([
            $accidentID
        ]);


        /*
         * Confirm that a record was actually deleted.
         */

        if ($deleteStmt->rowCount() !== 1) {

            throw new Exception(
                "Accident record could not be deleted."
            );
        }


        /*
         * Create audit log.
         *
         * The audit entry is created after the deletion
         * but before the transaction is committed.
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
         * Commit both operations.
         */

        $pdo->commit();


        /*
         * Redirect to accident list.
         */

        header(
            "Location: index.php?deleted=1"
        );

        exit;


    } catch (Exception $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }

        $error =
            "Unable to delete the accident record.";
    }
}


/*
 * Generate CSRF token for the confirmation form.
 */

$csrfToken = generateCSRFToken();

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
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php require_once "../includes/header.php"; ?>


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


                <?php if (
                    !empty($accident["Description"])
                ): ?>

                    <p>

                        <strong>
                            Description:
                        </strong>

                        <?= htmlspecialchars(
                            $accident["Description"]
                        ) ?>

                    </p>

                <?php endif; ?>


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

                    <!-- CSRF PROTECTION -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="AccidentID"
                        value="<?= htmlspecialchars(
                            $accidentID
                        ) ?>"
                    >


                    <button
                        type="submit"
                        class="button"
                        style="background:#991b1b;"
                    >
                        Yes, Delete Record
                    </button>


                    <a
                        href="view.php?id=<?= htmlspecialchars(
                            $accidentID
                        ) ?>"
                        class="button button-secondary"
                    >
                        Cancel
                    </a>

                </form>


            </div>

        </div>

    </main>

</div>


<?php require_once "../includes/footer.php"; ?>

</body>
</html>