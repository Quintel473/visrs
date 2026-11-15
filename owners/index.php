<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "owners";

$pageTitle = "Owners";
$pageSubtitle = "View and manage vehicle owner records";


/*
|--------------------------------------------------------------------------
| Role-Based Access Control (Block URL Guessing)
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$canManageOwners = in_array($currentRole, ['Admin', 'Police'], true);

// If they aren't Admin or Police, they cannot view the master Owners list.
if (!$canManageOwners) {
    http_response_code(403);
    $pageTitle = "Access Denied";
    require_once "../includes/head.php";
    ?>
    <body>
    <div class="layout">
        <main class="main-content" style="margin-left:0; width:100%;">
            <div class="content" style="max-width:700px; margin:80px auto;">
                <div class="card">
                    <div style="background:#fee2e2; color:#991b1b; padding:14px; border-radius:8px; margin-bottom:20px;">
                        <strong>Access Denied</strong>
                    </div>
                    <h2>You do not have permission to view this page.</h2>
                    <p>The Owner Management directory is restricted to Administrators and Police Officers.</p>
                    <a href="../dashboard.php" class="button">Return to Dashboard</a>
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
| Retrieve Owners
|--------------------------------------------------------------------------
*/

/* Pagination setup */
$perPage = 25;

/* Count total rows */
$countStmt = $pdo->query("SELECT COUNT(*) FROM owners");
$totalOwners = (int) $countStmt->fetchColumn();

/* Compute pagination metadata */
$pagination = paginationMeta($totalOwners, $perPage);

/* Load only the current page */
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
    ORDER BY OwnerID DESC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$owners = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Status Messages
|--------------------------------------------------------------------------
*/

$deleted = isset($_GET["deleted"]) &&
    $_GET["deleted"] === "1";

$error = $_GET["error"] ?? "";


/*
|--------------------------------------------------------------------------
| Bulk Action Flash Message
|--------------------------------------------------------------------------
*/

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);


/*
 * Global <head>
 */

require_once "../includes/head.php";

?>

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


            <!-- BULK ACTION MESSAGE -->

            <?php if ($bulkMessage): ?>

                <?php
                $bulkBg = "#fef3c7";
                $bulkColor = "#92400e";

                if ($bulkMessage["type"] === "success") {
                    $bulkBg = "#dcfce7";
                    $bulkColor = "#166534";
                } elseif ($bulkMessage["type"] === "error") {
                    $bulkBg = "#fee2e2";
                    $bulkColor = "#991b1b";
                }
                ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:<?= $bulkBg ?>;
                        color:<?= $bulkColor ?>;
                        padding:12px;
                        border-radius:6px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($bulkMessage["text"]) ?>

                    <?php if (!empty($bulkMessage["skipped"])): ?>

                        <ul
                            style="
                                margin:8px 0 0 20px;
                                padding:0;
                            "
                        >

                            <?php foreach ($bulkMessage["skipped"] as $item): ?>

                                <li>
                                    <?= htmlspecialchars($item) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php endif; ?>

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
                            <?= $pagination["totalRows"] ?>
                            owner(s) currently registered.
                        </p>

                    </div>


                    <a
                        href="add.php"
                        class="button"
                    >
                        + Add Owner
                    </a>

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

                    <form
                        method="POST"
                        action="bulk_delete.php"
                        id="bulkForm"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                        >


                        <!-- BULK ACTION BAR -->

                        <div
                            class="bulk-action-bar"
                            id="bulkActionBar"
                        >

                            <span class="bulk-action-count">
                                <strong id="bulkSelectedCount">0</strong>
                                selected
                            </span>


                            <div class="bulk-action-buttons">

                                <button
                                    type="button"
                                    class="button button-secondary"
                                    id="bulkClearSelection"
                                >
                                    Clear
                                </button>


                                <button
                                    type="submit"
                                    class="button"
                                    style="background:#991b1b;"
                                    onclick="return confirmBulkDelete();"
                                >
                                    Delete Selected
                                </button>

                            </div>

                        </div>


                        <div style="overflow-x:auto; margin-top:15px;">

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

                                        <th
                                            style="
                                                padding:12px;
                                                width:36px;
                                            "
                                        >

                                            <input
                                                type="checkbox"
                                                id="selectAll"
                                                aria-label="Select all owners"
                                            >

                                        </th>


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

                                            <td style="padding:12px;">

                                                <input
                                                    type="checkbox"
                                                    name="selected_ids[]"
                                                    value="<?= (int) $owner["OwnerID"] ?>"
                                                    class="row-checkbox"
                                                    aria-label="Select this owner"
                                                >

                                            </td>


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

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </form>

                    <?php renderPagination($pagination); ?>


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


                        <p style="margin-top:10px;">

                            <a
                                href="add.php"
                                class="button"
                            >
                                Add Your First Owner
                            </a>

                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one owner to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected owner(s)?\n\n" +
        "Owners with registered vehicles or ownership history " +
        "will be skipped automatically.\n\n" +
        "This action cannot be undone."
    );
}

</script>


<?php include __DIR__ . "/../includes/footer.php"; ?>