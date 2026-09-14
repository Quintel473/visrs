<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "owners";

$ownerID = $_GET["id"] ?? $_POST["OwnerID"] ?? "";

if (!is_numeric($ownerID)) {
    header("Location: index.php");
    exit;
}

/* Get owner */
$stmt = $pdo->prepare("
    SELECT *
    FROM owners
    WHERE OwnerID = ?
    LIMIT 1
");
$stmt->execute([$ownerID]);
$owner = $stmt->fetch();

if (!$owner) {
    http_response_code(404);
    echo "<h1>Owner Not Found</h1>";
    echo "<p>The requested owner could not be found.</p>";
    echo '<p><a href="index.php">Return to Owners</a></p>';
    exit;
}

/* Check whether owner has vehicles */
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS VehicleCount
    FROM vehicles
    WHERE OwnerID = ?
");
$stmt->execute([$ownerID]);
$vehicleCount = $stmt->fetch()["VehicleCount"];

/* Delete only after confirmation */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($vehicleCount > 0) {

        $error = "This owner cannot be deleted because vehicles are still associated with this owner.";

    } else {

        try {

            $pdo->beginTransaction();

            /* Delete ownership history records */
            $stmt = $pdo->prepare("
                DELETE FROM ownership_history
                WHERE OwnerID = ?
            ");
            $stmt->execute([$ownerID]);

            /* Delete owner */
            $stmt = $pdo->prepare("
                DELETE FROM owners
                WHERE OwnerID = ?
            ");
            $stmt->execute([$ownerID]);

            /* Audit the deletion */
            $audit = $pdo->prepare("
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

            $audit->execute([
                $_SESSION["UserID"],
                "Deleted owner",
                "owners",
                $ownerID,
                $_SERVER["REMOTE_ADDR"] ?? null
            ]);

            $pdo->commit();

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Unable to delete the owner record.";
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

    <title>VISRS - Delete Owner</title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <aside class="sidebar">

       <?php require_once "../includes/sidebar.php"; ?>

    </aside>


    <div class="main-content">

        <header class="topbar">

            <div class="topbar-title">
                Delete Owner
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

            <div class="page-title">

                <h1>Delete Owner</h1>

                <p class="page-subtitle">
                    Remove an owner record from the system.
                </p>

            </div>


            <?php if (isset($error)): ?>

                <div
                    style="
                        background: #fee2e2;
                        color: #991b1b;
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <section class="card">

                <h2>
                    <?= htmlspecialchars($owner["FirstName"]) ?>
                    <?= htmlspecialchars($owner["LastName"]) ?>
                </h2>


                <?php if ($vehicleCount > 0): ?>

                    <div
                        style="
                            background: #fef3c7;
                            color: #92400e;
                            padding: 15px;
                            border-radius: 8px;
                            margin-top: 20px;
                        "
                    >

                        <strong>
                            Owner cannot be deleted.
                        </strong>

                        <p style="margin-top: 8px;">

                            This owner currently has
                            <?= htmlspecialchars($vehicleCount) ?>
                            vehicle(s) associated with their record.

                        </p>

                        <p style="margin-top: 8px;">

                            Remove or transfer the associated vehicles
                            before deleting this owner.

                        </p>

                    </div>


                    <div style="margin-top: 20px;">

                        <a
                            href="view.php?id=<?= $ownerID ?>"
                            class="button button-secondary"
                        >
                            Return to Owner
                        </a>

                    </div>


                <?php else: ?>


                    <div
                        style="
                            background: #fee2e2;
                            color: #991b1b;
                            padding: 15px;
                            border-radius: 8px;
                            margin-top: 20px;
                        "
                    >

                        <strong>
                            Warning
                        </strong>

                        <p style="margin-top: 8px;">

                            You are about to permanently delete this
                            owner record.

                        </p>

                        <p style="margin-top: 8px;">

                            This action cannot be undone.

                        </p>

                    </div>


                    <form
                        method="POST"
                        action=""
                        style="margin-top: 20px;"
                    >

                        <input
                            type="hidden"
                            name="OwnerID"
                            value="<?= htmlspecialchars($ownerID) ?>"
                        >


                        <div
                            style="
                                display: flex;
                                gap: 10px;
                                flex-wrap: wrap;
                            "
                        >

                            <a
                                href="view.php?id=<?= $ownerID ?>"
                                class="button button-secondary"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="button"
                                style="background: #991b1b;"
                                onclick="return confirm('Are you sure you want to permanently delete this owner?');"
                            >
                                Delete Owner
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>


<script src="../js/app.js"></script>

</body>

</html>