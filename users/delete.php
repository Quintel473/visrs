<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";


/*
 * Get User ID
 */

$userID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($userID <= 0) {

    header("Location: index.php");
    exit;

}


/*
 * Get the currently logged-in Admin.
 */

$currentUserID = (int) ($_SESSION["UserID"] ?? 0);


/*
 * Prevent the currently logged-in Admin
 * from deleting their own account.
 */

if ($userID === $currentUserID) {

    header("Location: index.php?error=self_delete");
    exit;

}


/*
 * Retrieve the user.
 */

$stmt = $pdo->prepare("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Role,
        CreatedAt
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$stmt->execute([
    $userID
]);

$user = $stmt->fetch();


/*
 * User does not exist.
 */

if (!$user) {

    header("Location: index.php?error=not_found");
    exit;

}


/*
 * Check whether the user has audit records.
 *
 * Audit records must remain intact.
 */

$auditCheck = $pdo->prepare("
    SELECT COUNT(*)
    FROM audit_logs
    WHERE UserID = ?
");

$auditCheck->execute([
    $userID
]);

$auditCount = (int) $auditCheck->fetchColumn();


/*
 * Process deletion only after the user
 * confirms on the VISRS confirmation page.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Verify CSRF token.
     */

    requireValidCSRF();


    /*
     * Verify the submitted User ID.
     */

    $confirmedUserID =
        (int) ($_POST["UserID"] ?? 0);


    if ($confirmedUserID !== $userID) {

        header("Location: index.php?error=invalid_request");
        exit;

    }


    /*
     * Prevent self-deletion again during POST processing.
     */

    if ($userID === $currentUserID) {

        header("Location: index.php?error=self_delete");
        exit;

    }


    /*
     * Re-check the user before deletion.
     *
     * This protects against the record changing
     * between the confirmation page and submission.
     */

    $verifyStmt = $pdo->prepare("
        SELECT
            UserID,
            FirstName,
            LastName,
            Email,
            Role
        FROM users
        WHERE UserID = ?
        LIMIT 1
    ");

    $verifyStmt->execute([
        $userID
    ]);

    $currentUser = $verifyStmt->fetch();


    if (!$currentUser) {

        header("Location: index.php?error=not_found");
        exit;

    }


    /*
     * Re-check audit records immediately before deletion.
     */

    $auditCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE UserID = ?
    ");

    $auditCheck->execute([
        $userID
    ]);

    $auditCount = (int) $auditCheck->fetchColumn();


    /*
     * Do not delete a user that has audit history.
     */

    if ($auditCount > 0) {

        header(
            "Location: index.php?error=has_audit_logs"
        );

        exit;

    }


    /*
     * Delete the user.
     */

    try {

        $pdo->beginTransaction();


        $deleteStmt = $pdo->prepare("
            DELETE FROM users
            WHERE UserID = ?
        ");

        $deleteStmt->execute([
            $userID
        ]);


        /*
         * Confirm that a record was actually deleted.
         */

        if ($deleteStmt->rowCount() !== 1) {

            throw new RuntimeException(
                "User deletion did not affect exactly one record."
            );

        }


        /*
         * Commit the deletion.
         */

        $pdo->commit();


        /*
         * Return to User Management.
         */

        header(
            "Location: index.php?success=deleted"
        );

        exit;

    } catch (Throwable $e) {

        /*
         * Roll back if anything failed.
         */

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }


        /*
         * Return to User Management with
         * a generic error message.
         */

        header(
            "Location: index.php?error=delete_failed"
        );

        exit;

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
        Delete User - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>


<body>

<div class="layout">

    <?php

    $activePage = "users";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">

        <?php

        include __DIR__ . "/../includes/header.php";

        ?>


        <div class="content">

            <div class="card">


                <!-- WARNING HEADER -->

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
                        Warning
                    </strong>

                </div>


                <!-- CONFIRMATION -->

                <h2>
                    Are you sure you want to delete this user?
                </h2>


                <p>
                    <strong>User ID:</strong>
                    <?= (int) $user["UserID"] ?>
                </p>


                <p>
                    <strong>Name:</strong>
                    <?= htmlspecialchars(
                        $user["FirstName"]
                        . " "
                        . $user["LastName"]
                    ) ?>
                </p>


                <p>
                    <strong>Email:</strong>
                    <?= htmlspecialchars(
                        $user["Email"]
                    ) ?>
                </p>


                <p>
                    <strong>Role:</strong>

                    <?php

                    if ($user["Role"] === "Admin") {

                        echo "Administrator";

                    } else {

                        echo htmlspecialchars(
                            $user["Role"]
                        );

                    }

                    ?>

                </p>


                <p>
                    <strong>Account Created:</strong>

                    <?= htmlspecialchars(
                        date(
                            "F d, Y",
                            strtotime(
                                $user["CreatedAt"]
                            )
                        )
                    ) ?>

                </p>


                <!-- DELETE WARNING -->

                <div
                    style="
                        background:#fff7ed;
                        color:#9a3412;
                        padding:14px;
                        border-radius:8px;
                        margin-top:20px;
                    "
                >

                    <?php if ($auditCount > 0): ?>

                        <strong>
                            User Cannot Be Deleted
                        </strong>

                        <p style="margin:6px 0 0 0;">
                            This account has
                            <?= $auditCount ?>
                            existing audit
                            <?= $auditCount === 1 ? "record" : "records" ?>.
                            The account cannot be deleted because the
                            VISRS audit trail must remain intact.
                        </p>

                    <?php else: ?>

                        <strong>
                            This action cannot be undone.
                        </strong>

                        <p style="margin:6px 0 0 0;">
                            Deleting this account will permanently remove
                            the user from the VISRS system.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- DELETE FORM -->

                <form
                    method="POST"
                    action=""
                    style="
                        display:flex;
                        gap:12px;
                        margin-top:25px;
                        flex-wrap:wrap;
                    "
                >

                    <!-- CSRF TOKEN -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            generateCSRFToken()
                        ) ?>"
                    >


                    <!-- USER ID -->

                    <input
                        type="hidden"
                        name="UserID"
                        value="<?= (int) $user["UserID"] ?>"
                    >


                    <?php if ($auditCount === 0): ?>

                        <button
                            type="submit"
                            class="button"
                            style="
                                background:#991b1b;
                            "
                        >
                            Yes, Delete User
                        </button>

                    <?php else: ?>

                        <button
                            type="button"
                            class="button"
                            style="
                                background:#9ca3af;
                                cursor:not-allowed;
                            "
                            disabled
                        >
                            User Cannot Be Deleted
                        </button>

                    <?php endif; ?>


                    <a
                        href="index.php"
                        class="button button-secondary"
                    >
                        Cancel
                    </a>

                </form>

            </div>

        </div>

    </main>

</div>


<?php

include __DIR__ . "/../includes/footer.php";

?>