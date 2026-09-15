<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$canManageOwners = in_array(
    $_SESSION["Role"] ?? "",
    ["Admin", "Police"],
    true
);

$activePage = "owners";

$pageTitle = "Owners";
$pageSubtitle = "View and manage vehicle owner records";


/*
|--------------------------------------------------------------------------
| Retrieve Owners
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        OwnerID,
        FirstName,
        LastName,
        Address,
        Phone,
        Email,
        CreatedAt
    FROM owners
    ORDER BY OwnerID DESC
");

$owners = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Status Messages
|--------------------------------------------------------------------------
*/

$deleted = isset($_GET["deleted"]) &&
    $_GET["deleted"] === "1";

$error = $_GET["error"] ?? "";

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
                Owner Management
            </h1>

            <p class="page-subtitle">
                View and manage vehicle owner records.
            </p>


            <!-- SUCCESS MESSAGE -->

            <?php if ($deleted): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:12px;
                        border-radius:6px;
                        margin-bottom:20px;
                    "
                >

                    Owner deleted successfully.

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error === "not_found"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:12px;
                        border-radius:6px;
                        margin-bottom:20px;
                    "
                >

                    The requested owner could not be found.

                </div>

            <?php endif; ?>


            <!-- HEADER CARD -->

            <section class="card">

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:15px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h2 style="margin-bottom:5px;">
                            Registered Owners
                        </h2>

                        <p style="color:#6b7280;">
                            <?= count($owners) ?>
                            owner(s) currently registered.
                        </p>

                    </div>


                    <?php if ($canManageOwners): ?>

                        <a
                            href="add.php"
                            class="button"
                        >
                            + Add Owner
                        </a>

                    <?php endif; ?>

                </div>

            </section>


            <!-- OWNER TABLE -->

            <section
                class="card"
                style="margin-top:20px;"
            >

                <h2>
                    Owner Records
                </h2>


                <?php if (count($owners) > 0): ?>

                    <div style="overflow-x:auto;">

                        <table
                            style="
                                width:100%;
                                border-collapse:collapse;
                            "
                        >

                            <thead>

                                <tr
                                    style="
                                        border-bottom:2px solid #e5e7eb;
                                        text-align:left;
                                    "
                                >

                                    <th style="padding:12px;">
                                        Name
                                    </th>

                                    <th style="padding:12px;">
                                        Phone
                                    </th>

                                    <th style="padding:12px;">
                                        Email
                                    </th>

                                    <th style="padding:12px;">
                                        Address
                                    </th>

                                    <th style="padding:12px;">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($owners as $owner): ?>

                                    <tr
                                        style="
                                            border-bottom:1px solid #e5e7eb;
                                        "
                                    >

                                        <!-- NAME -->

                                        <td style="padding:12px;">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $owner["FirstName"]
                                                    . " "
                                                    . $owner["LastName"]
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- PHONE -->

                                        <td style="padding:12px;">

                                            <?= htmlspecialchars(
                                                $owner["Phone"] ?? ""
                                            ) ?>

                                        </td>


                                        <!-- EMAIL -->

                                        <td style="padding:12px;">

                                            <?= htmlspecialchars(
                                                $owner["Email"] ?? ""
                                            ) ?>

                                        </td>


                                        <!-- ADDRESS -->

                                        <td style="padding:12px;">

                                            <?= htmlspecialchars(
                                                $owner["Address"] ?? ""
                                            ) ?>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td style="padding:12px;">

                                            <div
                                                style="
                                                    display:flex;
                                                    gap:6px;
                                                    flex-wrap:wrap;
                                                "
                                            >

                                                <!-- VIEW -->

                                                <a
                                                    href="view.php?id=<?= (int) $owner["OwnerID"] ?>"
                                                    class="button button-secondary"
                                                    style="
                                                        padding:8px 12px;
                                                    "
                                                >
                                                    View
                                                </a>


                                                <?php if ($canManageOwners): ?>

                                                    <!-- EDIT -->

                                                    <a
                                                        href="edit.php?id=<?= (int) $owner["OwnerID"] ?>"
                                                        class="button"
                                                        style="
                                                            padding:8px 12px;
                                                        "
                                                    >
                                                        Edit
                                                    </a>


                                                    <!-- DELETE -->

                                                    <a
                                                        href="delete.php?id=<?= (int) $owner["OwnerID"] ?>"
                                                        class="button"
                                                        style="
                                                            padding:8px 12px;
                                                            background:#991b1b;
                                                        "
                                                    >
                                                        Delete
                                                    </a>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php else: ?>

                    <!-- EMPTY STATE -->

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            color:#6b7280;
                        "
                    >

                        <p>
                            No owner records have been added yet.
                        </p>


                        <?php if ($canManageOwners): ?>

                            <p style="margin-top:10px;">

                                <a
                                    href="add.php"
                                    class="button"
                                >
                                    Add Your First Owner
                                </a>

                            </p>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
