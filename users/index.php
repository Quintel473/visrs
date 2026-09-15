<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "User Management";
$pageSubtitle = "Manage VISRS system users and account roles";

$stmt = $pdo->query("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Role,
        CreatedAt
    FROM users
    ORDER BY CreatedAt DESC
");

$users = $stmt->fetchAll();

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

    $activePage = "users";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>


        <div class="content">

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
                        User Management
                    </h1>

                    <p style="
                        color:#6b7280;
                        margin:0;
                    ">
                        View and manage registered VISRS users.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="button"
                >
                    + Add User
                </a>

            </div>


            <div class="card">

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        margin-bottom:20px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h2 style="margin:0;">
                            Registered Users
                        </h2>

                        <p style="
                            color:#6b7280;
                            margin:6px 0 0 0;
                        ">
                            <?= count($users) ?> user(s) registered
                        </p>

                    </div>


                    <input
                        type="text"
                        data-table-search="usersTable"
                        placeholder="Search users..."
                        style="
                            width:260px;
                            max-width:100%;
                            padding:10px 12px;
                            border:1px solid #d1d5db;
                            border-radius:8px;
                            font-size:14px;
                        "
                    >

                </div>


                <?php if (empty($users)): ?>

                    <div
                        style="
                            background:#f3f4f6;
                            color:#374151;
                            padding:20px;
                            border-radius:8px;
                            text-align:center;
                        "
                    >

                        <strong>
                            No users found.
                        </strong>

                        <p style="margin:8px 0 0 0;">
                            There are currently no registered users in the system.
                        </p>

                    </div>

                <?php else: ?>

                    <div style="overflow-x:auto;">

                        <table
                            id="usersTable"
                            style="
                                width:100%;
                                border-collapse:collapse;
                            "
                        >

                            <thead>

                                <tr>

                                    <th style="
                                        text-align:left;
                                        padding:14px;
                                        border-bottom:1px solid #e5e7eb;
                                    ">
                                        Name
                                    </th>

                                    <th style="
                                        text-align:left;
                                        padding:14px;
                                        border-bottom:1px solid #e5e7eb;
                                    ">
                                        Email
                                    </th>

                                    <th style="
                                        text-align:left;
                                        padding:14px;
                                        border-bottom:1px solid #e5e7eb;
                                    ">
                                        Role
                                    </th>

                                    <th style="
                                        text-align:left;
                                        padding:14px;
                                        border-bottom:1px solid #e5e7eb;
                                    ">
                                        Created
                                    </th>

                                    <th style="
                                        text-align:right;
                                        padding:14px;
                                        border-bottom:1px solid #e5e7eb;
                                    ">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($users as $user): ?>

                                    <?php

                                    $roleClass = "user-role user-role-default";

                                    if ($user["Role"] === "Admin") {

                                        $roleClass = "user-role user-role-admin";

                                    } elseif ($user["Role"] === "Police") {

                                        $roleClass = "user-role user-role-police";

                                    } elseif ($user["Role"] === "Seller") {

                                        $roleClass = "user-role user-role-seller";

                                    }

                                    $currentUserID =
                                        (int) ($_SESSION["UserID"] ?? 0);

                                    $listedUserID =
                                        (int) $user["UserID"];

                                    ?>


                                    <tr>

                                        <td style="
                                            padding:14px;
                                            border-bottom:1px solid #f1f5f9;
                                        ">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $user["FirstName"]
                                                    . " "
                                                    . $user["LastName"]
                                                ) ?>
                                            </strong>

                                        </td>


                                        <td style="
                                            padding:14px;
                                            border-bottom:1px solid #f1f5f9;
                                        ">

                                            <?= htmlspecialchars(
                                                $user["Email"]
                                            ) ?>

                                        </td>


                                        <td style="
                                            padding:14px;
                                            border-bottom:1px solid #f1f5f9;
                                        ">

                                            <span class="<?= $roleClass ?>">
                                                <?= htmlspecialchars(
                                                    $user["Role"]
                                                ) ?>
                                            </span>

                                        </td>


                                        <td style="
                                            padding:14px;
                                            border-bottom:1px solid #f1f5f9;
                                        ">

                                            <?= htmlspecialchars(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $user["CreatedAt"]
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td style="
                                            padding:14px;
                                            border-bottom:1px solid #f1f5f9;
                                            text-align:right;
                                            white-space:nowrap;
                                        ">

                                            <a
                                                href="view.php?id=<?= $listedUserID ?>"
                                                class="button button-secondary"
                                                style="
                                                    padding:8px 12px;
                                                    font-size:13px;
                                                "
                                            >
                                                View
                                            </a>


                                            <a
                                                href="edit.php?id=<?= $listedUserID ?>"
                                                class="button"
                                                style="
                                                    padding:8px 12px;
                                                    font-size:13px;
                                                "
                                            >
                                                Edit
                                            </a>


                                            <?php if (
                                                $listedUserID !== $currentUserID
                                            ): ?>

                                                <a
                                                    href="delete.php?id=<?= $listedUserID ?>"
                                                    class="button confirm-action"
                                                    data-confirm-message="Are you sure you want to delete this user?"
                                                    style="
                                                        padding:8px 12px;
                                                        font-size:13px;
                                                        background:#991b1b;
                                                    "
                                                >
                                                    Delete
                                                </a>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>


<style>

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

</style>