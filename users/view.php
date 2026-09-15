<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "View User";
$pageSubtitle = "View VISRS user account information";

$activePage = "users";


/*
|--------------------------------------------------------------------------
| Get User ID
|--------------------------------------------------------------------------
*/

$userID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($userID <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Retrieve User
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| User Does Not Exist
|--------------------------------------------------------------------------
*/

if (!$user) {
    header("Location: index.php?error=not_found");
    exit;
}


/*
|--------------------------------------------------------------------------
| Determine Role Badge
|--------------------------------------------------------------------------
*/

$roleClass = "user-role user-role-default";

if ($user["Role"] === "Admin") {

    $roleClass = "user-role user-role-admin";

} elseif ($user["Role"] === "Police") {

    $roleClass = "user-role user-role-police";

} elseif ($user["Role"] === "Seller") {

    $roleClass = "user-role user-role-seller";
}


/*
|--------------------------------------------------------------------------
| Display-Friendly Role Name
|--------------------------------------------------------------------------
*/

$displayRole = $user["Role"];

if ($user["Role"] === "Admin") {
    $displayRole = "Administrator";
}


/*
|--------------------------------------------------------------------------
| Determine Whether This Is the Current User
|--------------------------------------------------------------------------
*/

$currentUserID = (int) ($_SESSION["UserID"] ?? 0);

$isCurrentUser =
    ((int) $user["UserID"] === $currentUserID);

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

    <style>

        /*
        |--------------------------------------------------------------------------
        | User Role Badges
        |--------------------------------------------------------------------------
        */

        .user-role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .user-role-default {
            background: #f3f4f6;
            color: #374151;
        }

        .user-role-admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .user-role-police {
            background: #dcfce7;
            color: #166534;
        }

        .user-role-seller {
            background: #fef3c7;
            color: #92400e;
        }


        /*
        |--------------------------------------------------------------------------
        | User Detail Rows
        |--------------------------------------------------------------------------
        */

        .user-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .user-detail-row:last-child {
            border-bottom: none;
        }

        .user-detail-label {
            color: #6b7280;
            font-size: 14px;
        }

        .user-detail-value {
            color: #111827;
            font-size: 15px;
            font-weight: 500;
            text-align: right;
        }


        /*
        |--------------------------------------------------------------------------
        | Mobile Layout
        |--------------------------------------------------------------------------
        */

        @media (max-width: 600px) {

            .user-detail-row {
                display: block;
            }

            .user-detail-value {
                text-align: left;
                margin-top: 5px;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="card">

                <!-- PAGE HEADER -->

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        margin-bottom:25px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h1 class="page-title">
                            User Details
                        </h1>

                        <p style="
                            color:#6b7280;
                            margin:0;
                        ">
                            View information about this VISRS account.
                        </p>

                    </div>

                    <a
                        href="index.php"
                        class="button button-secondary"
                    >
                        ← Back to Users
                    </a>

                </div>


                <!-- USER INFORMATION -->

                <div
                    style="
                        background:#f8fafc;
                        padding:20px;
                        border-radius:10px;
                    "
                >

                    <!-- USER ID -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            User ID
                        </div>

                        <div class="user-detail-value">
                            #<?= (int) $user["UserID"] ?>
                        </div>

                    </div>


                    <!-- FULL NAME -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            Full Name
                        </div>

                        <div class="user-detail-value">

                            <?= htmlspecialchars(
                                $user["FirstName"]
                                . " "
                                . $user["LastName"]
                            ) ?>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            Email Address
                        </div>

                        <div class="user-detail-value">

                            <?= htmlspecialchars(
                                $user["Email"]
                            ) ?>

                        </div>

                    </div>


                    <!-- ROLE -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            Account Role
                        </div>

                        <div class="user-detail-value">

                            <span class="<?= htmlspecialchars($roleClass) ?>">

                                <?= htmlspecialchars(
                                    $displayRole
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <!-- ACCOUNT CREATED DATE -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            Account Created
                        </div>

                        <div class="user-detail-value">

                            <?= htmlspecialchars(
                                date(
                                    "F d, Y",
                                    strtotime(
                                        $user["CreatedAt"]
                                    )
                                )
                            ) ?>

                        </div>

                    </div>


                    <!-- ACCOUNT CREATED TIME -->

                    <div class="user-detail-row">

                        <div class="user-detail-label">
                            Account Created Time
                        </div>

                        <div class="user-detail-value">

                            <?= htmlspecialchars(
                                date(
                                    "h:i A",
                                    strtotime(
                                        $user["CreatedAt"]
                                    )
                                )
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- ACTION BUTTONS -->

                <div
                    style="
                        display:flex;
                        gap:12px;
                        margin-top:25px;
                        flex-wrap:wrap;
                    "
                >

                    <!-- EDIT USER -->

                    <a
                        href="edit.php?id=<?= (int) $user["UserID"] ?>"
                        class="button"
                    >
                        Edit User
                    </a>


                    <?php if (!$isCurrentUser): ?>

                        <!--
                        IMPORTANT:
                        This link intentionally does NOT use
                        .confirm-action or JavaScript confirmation.

                        The dedicated delete.php page provides
                        the VISRS confirmation screen.
                        -->

                        <a
                            href="delete.php?id=<?= (int) $user["UserID"] ?>"
                            class="button"
                            style="
                                background:#991b1b;
                            "
                        >
                            Delete User
                        </a>

                    <?php endif; ?>


                    <!-- BACK -->

                    <a
                        href="index.php"
                        class="button button-secondary"
                    >
                        Back
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>