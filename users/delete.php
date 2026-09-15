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
 * Prevent the currently logged-in Admin
 * from deleting their own account.
 */

$currentUserID =
    (int) ($_SESSION["UserID"] ?? 0);


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
 * Process deletion.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Verify the confirmation ID.
     */

    $confirmedUserID =
        (int) ($_POST["UserID"] ?? 0);


    if ($confirmedUserID !== $userID) {

        header("Location: index.php?error=invalid_request");
        exit;

    }


    /*
     * Delete the user.
     */

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE UserID = ?
    ");

    $stmt->execute([
        $userID
    ]);


    /*
     * Return to User Management.
     */

    header("Location: index.php?success=deleted");

    exit;

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

        <?php include __DIR__ . "/../includes/header.php"; ?>


        <div class="content">

            <div class="card">


                <!-- ERROR HEADER -->

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


                <!-- WARNING -->

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
                        This action cannot be undone.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        Deleting this account will permanently remove
                        the user from the VISRS system.
                    </p>

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

                    <input
                        type="hidden"
                        name="UserID"
                        value="<?= (int) $user["UserID"] ?>"
                    >


                    <button
                        type="submit"
                        class="button"
                        style="
                            background:#991b1b;
                        "
                    >
                        Yes, Delete User
                    </button>


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


<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>
</html>