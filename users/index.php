<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "users";

$pageTitle = "User Management";
$pageSubtitle = "Manage VISRS system users and account roles";


/*
 * Handle success and error messages.
 */

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";


/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/

$perPage = 25;

$countStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalUsers, $perPage);


/*
 * Retrieve users for current page.
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
    ORDER BY CreatedAt DESC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll();


/*
 * Current logged-in user.
 */

$currentUserID =
    (int) ($_SESSION["UserID"] ?? 0);


/*
|--------------------------------------------------------------------------
| Bulk Action Flash Message
|--------------------------------------------------------------------------
*/

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);


/*
 * FIX: Global <head> (was inline <!DOCTYPE> + <head> block
 * with hardcoded /visrs/css/style.css path).
 */

require_once "../includes/head.php";

?>

<!-- FIX: Page-specific CSS moved here from <head> (head.php closes </head>) -->
<style>

    /*
    |--------------------------------------------------------------------------
    | VISRS User Role Badges
    |--------------------------------------------------------------------------
    */

    .user-role {

        display:inline-block;

        padding:6px 10px;

        border-radius:999px;

        font-size:12px;

        font-weight:600;

    }


    .user-role-default {

        background:#f3f4f6;

        color:#374151;

    }


    .user-role-admin {

        background:#dbeafe;

        color:#1d4ed8;

    }


    .user-role-police {

        background:#dcfce7;

        color:#166534;

    }


    .user-role-seller {

        background:#fef3c7;

        color:#92400e;

    }

</style>


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


            <!-- SUCCESS MESSAGE -->

            <?php if ($success === "created"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        User created successfully.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        The new user account has been added to VISRS.
                    </p>

                </div>


            <?php elseif ($success === "deleted"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        User deleted successfully.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        The user account has been removed from VISRS.
                    </p>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGES -->

            <?php if ($error === "self_delete"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        Account cannot be deleted.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        You cannot delete the account you are currently
                        using.
                    </p>

                </div>


            <?php elseif ($error === "not_found"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        User not found.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        The requested user account could not be found.
                    </p>

                </div>


            <?php elseif ($error === "invalid_request"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        Invalid request.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        The requested operation could not be completed.
                    </p>

                </div>


            <?php elseif ($error === "has_audit_logs"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        User cannot be deleted.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        This user has existing audit records.
                        The account cannot be deleted because
                        the VISRS audit trail must remain intact.
                    </p>

                </div>


            <?php elseif ($error === "delete_failed"): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:14px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <strong>
                        User could not be deleted.
                    </strong>

                    <p style="margin:6px 0 0 0;">
                        The system could not complete the deletion.
                        Please try again.
                    </p>

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
                        padding:14px;
                        border-radius:8px;
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


            <!-- USERS CARD -->

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
                            <?= $pagination["totalRows"] ?> user(s) registered
                        </p>

                    </div>


                    <!-- SEARCH -->

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

                    <!-- NO USERS -->

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
                            There are currently no registered users
                            in the system.
                        </p>

                    </div>

                <?php else: ?>


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


                        <!-- USERS TABLE -->

                        <div style="overflow-x:auto; margin-top:15px;">

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
                                            width:36px;
                                        ">

                                            <input
                                                type="checkbox"
                                                id="selectAll"
                                                aria-label="Select all users"
                                            >

                                        </th>


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

                                        /*
                                         * Determine role badge.
                                         */

                                        $roleClass =
                                            "user-role user-role-default";


                                        if (
                                            $user["Role"] === "Admin"
                                        ) {

                                            $roleClass =
                                                "user-role user-role-admin";

                                        } elseif (
                                            $user["Role"] === "Police"
                                        ) {

                                            $roleClass =
                                                "user-role user-role-police";

                                        } elseif (
                                            $user["Role"] === "Seller"
                                        ) {

                                            $roleClass =
                                                "user-role user-role-seller";

                                        }


                                        $listedUserID =
                                            (int) $user["UserID"];

                                        $isSelf =
                                            $listedUserID === $currentUserID;

                                        ?>


                                        <tr>


                                            <!-- SELECT CHECKBOX -->

                                            <td style="
                                                padding:14px;
                                                border-bottom:1px solid #f1f5f9;
                                            ">

                                                <?php if ($isSelf): ?>

                                                    <input
                                                        type="checkbox"
                                                        disabled
                                                        title="You cannot delete your own account"
                                                        aria-label="Cannot select your own account"
                                                    >

                                                <?php else: ?>

                                                    <input
                                                        type="checkbox"
                                                        name="selected_ids[]"
                                                        value="<?= $listedUserID ?>"
                                                        class="row-checkbox"
                                                        aria-label="Select this user"
                                                    >

                                                <?php endif; ?>

                                            </td>


                                            <!-- NAME -->

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

                                                <?php if ($isSelf): ?>

                                                    <span
                                                        style="
                                                            margin-left:6px;
                                                            color:#6b7280;
                                                            font-size:12px;
                                                        "
                                                    >
                                                        (You)
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- EMAIL -->

                                            <td style="
                                                padding:14px;
                                                border-bottom:1px solid #f1f5f9;
                                            ">

                                                <?= htmlspecialchars(
                                                    $user["Email"]
                                                ) ?>

                                            </td>


                                            <!-- ROLE -->

                                            <td style="
                                                padding:14px;
                                                border-bottom:1px solid #f1f5f9;
                                            ">

                                                <span
                                                    class="<?= htmlspecialchars($roleClass) ?>"
                                                >

                                                    <?php

                                                    if (
                                                        $user["Role"] === "Admin"
                                                    ) {

                                                        echo "Administrator";

                                                    } else {

                                                        echo htmlspecialchars(
                                                            $user["Role"]
                                                        );

                                                    }

                                                    ?>

                                                </span>

                                            </td>


                                            <!-- CREATED -->

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


                                            <!-- ACTIONS -->

                                            <td style="
                                                padding:14px;
                                                border-bottom:1px solid #f1f5f9;
                                                text-align:right;
                                                white-space:nowrap;
                                            ">


                                                <!-- VIEW -->

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


                                                <!-- EDIT -->

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


                                                <?php if (!$isSelf): ?>

                                                    <!-- DELETE -->

                                                    <a
                                                        href="delete.php?id=<?= $listedUserID ?>"
                                                        class="button"
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

                    </form>

                    <?php renderPagination($pagination); ?>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<?php

include __DIR__ . "/../includes/footer.php";

?>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one user to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected user(s)?\n\n" +
        "Rules applied automatically:\n" +
        "• Your own account cannot be selected\n" +
        "• Users with audit logs will be skipped\n\n" +
        "This action cannot be undone."
    );
}

</script>