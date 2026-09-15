<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Delete Owner";
$pageSubtitle = "Remove an owner record from the system";

$activePage = "owners";

$error = "";


/*
|--------------------------------------------------------------------------
| Get Owner ID
|--------------------------------------------------------------------------
*/

$ownerID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["OwnerID"] ?? 0);

if ($ownerID <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Retrieve Owner
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        OwnerID,
        FirstName,
        LastName,
        Address,
        Phone,
        Email,
        CreatedAt
    FROM owners
    WHERE OwnerID = ?
    LIMIT 1
");

$stmt->execute([
    $ownerID
]);

$owner = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Owner Does Not Exist
|--------------------------------------------------------------------------
*/

if (!$owner) {

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
            Owner Not Found - VISRS
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
            style="margin-left:0;width:100%;"
        >

            <div
                class="content"
                style="max-width:700px;margin:80px auto;"
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
                            Owner Not Found
                        </strong>

                    </div>

                    <h2>
                        The requested owner could not be found.
                    </h2>

                    <p>
                        The owner may have already been deleted
                        or the requested record does not exist.
                    </p>

                    <a
                        href="index.php"
                        class="button"
                    >
                        Return to Owners
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
| Check Whether Owner Has Vehicles
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS VehicleCount
    FROM vehicles
    WHERE OwnerID = ?
");

$stmt->execute([
    $ownerID
]);

$vehicleCount = (int) $stmt->fetch()["VehicleCount"];


/*
|--------------------------------------------------------------------------
| Handle Deletion
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();


    /*
    |--------------------------------------------------------------------------
    | Verify Submitted Owner ID
    |--------------------------------------------------------------------------
    */

    $submittedOwnerID = (int) ($_POST["OwnerID"] ?? 0);

    if ($submittedOwnerID !== $ownerID) {

        http_response_code(400);
        $error = "Invalid owner record.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Recheck Vehicle Association
        |--------------------------------------------------------------------------
        |
        | This protects against the owner gaining a vehicle between
        | the initial page load and the deletion request.
        |
        */

        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) AS VehicleCount
            FROM vehicles
            WHERE OwnerID = ?
        ");

        $checkStmt->execute([
            $ownerID
        ]);

        $vehicleCount = (int) $checkStmt->fetch()["VehicleCount"];


        if ($vehicleCount > 0) {

            $error =
                "This owner cannot be deleted because vehicles are still associated with this owner.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Delete Owner
            |--------------------------------------------------------------------------
            */

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Delete Ownership History
                |--------------------------------------------------------------------------
                */

                $historyStmt = $pdo->prepare("
                    DELETE FROM ownership_history
                    WHERE OwnerID = ?
                ");

                $historyStmt->execute([
                    $ownerID
                ]);


                /*
                |--------------------------------------------------------------------------
                | Delete Owner
                |--------------------------------------------------------------------------
                */

                $deleteStmt = $pdo->prepare("
                    DELETE FROM owners
                    WHERE OwnerID = ?
                ");

                $deleteStmt->execute([
                    $ownerID
                ]);


                /*
                |--------------------------------------------------------------------------
                | Confirm Owner Was Deleted
                |--------------------------------------------------------------------------
                */

                if ($deleteStmt->rowCount() !== 1) {

                    throw new RuntimeException(
                        "Owner deletion failed."
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Record Audit Log
                |--------------------------------------------------------------------------
                */

                $auditStmt = $pdo->prepare("
                    INSERT INTO audit_logs (
                        UserID,
                        Action,
                        TableAffected,
                        RecordID,
                        IPAddress
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $auditStmt->execute([
                    (int) $_SESSION["UserID"],
                    "Deleted owner",
                    "owners",
                    $ownerID,
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
                | Return to Owners
                |--------------------------------------------------------------------------
                */

                header("Location: index.php?deleted=1");
                exit;


            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    "Unable to delete the owner record. Please try again.";
            }

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

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <h1 class="page-title">
                Delete Owner
            </h1>

            <p class="page-subtitle">
                Remove an owner record from the system.
            </p>


            <?php if ($error !== ""): ?>

                <div
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:15px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <section class="card">

                <h2>

                    <?= htmlspecialchars(
                        $owner["FirstName"]
                        . " "
                        . $owner["LastName"]
                    ) ?>

                </h2>


                <?php if ($vehicleCount > 0): ?>

                    <!-- OWNER CANNOT BE DELETED -->

                    <div
                        style="
                            background:#fef3c7;
                            color:#92400e;
                            padding:15px;
                            border-radius:8px;
                            margin-top:20px;
                        "
                    >

                        <strong>
                            Owner cannot be deleted.
                        </strong>

                        <p style="margin-top:8px;">

                            This owner currently has
                            <?= (int) $vehicleCount ?>
                            vehicle(s) associated with their record.

                        </p>

                        <p style="margin-top:8px;">

                            Remove or transfer the associated vehicles
                            before deleting this owner.

                        </p>

                    </div>


                    <div style="margin-top:20px;">

                        <a
                            href="view.php?id=<?= (int) $ownerID ?>"
                            class="button button-secondary"
                        >
                            Return to Owner
                        </a>

                    </div>


                <?php else: ?>

                    <!-- DELETE WARNING -->

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:15px;
                            border-radius:8px;
                            margin-top:20px;
                        "
                    >

                        <strong>
                            Warning
                        </strong>

                        <p style="margin-top:8px;">

                            You are about to permanently delete
                            this owner record.

                        </p>

                        <p style="margin-top:8px;">

                            This action cannot be undone.

                        </p>

                    </div>


                    <!-- CONFIRMATION FORM -->

                    <form
                        method="POST"
                        action=""
                        style="margin-top:20px;"
                    >

                        <input
                            type="hidden"
                            name="OwnerID"
                            value="<?= (int) $ownerID ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                        >


                        <div
                            style="
                                display:flex;
                                gap:10px;
                                flex-wrap:wrap;
                            "
                        >

                            <a
                                href="view.php?id=<?= (int) $ownerID ?>"
                                class="button button-secondary"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="button"
                                style="
                                    background:#991b1b;
                                "
                            >
                                Delete Owner
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>