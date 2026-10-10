<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "ownership_history";

$canManage =
    isset($_SESSION["Role"]) &&
    in_array(
        $_SESSION["Role"],
        ["Admin", "Police"],
        true
    );


/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/

$perPage = 25;

$countStmt = $pdo->query("SELECT COUNT(*) FROM ownership_history");
$totalRecords = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalRecords, $perPage);


/*
|--------------------------------------------------------------------------
| Load Ownership History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        oh.OwnershipID,
        oh.VehicleID,
        oh.OwnerID,
        oh.StartDate,
        oh.EndDate,
        oh.TransferReason,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,

        o.FirstName,
        o.LastName

    FROM ownership_history oh

    INNER JOIN vehicles v
        ON oh.VehicleID = v.VehicleID

    INNER JOIN owners o
        ON oh.OwnerID = o.OwnerID

    ORDER BY
        oh.StartDate DESC,
        oh.OwnershipID DESC

    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$history = $stmt->fetchAll();


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

$pageTitle = "Ownership History";

require_once "../includes/head.php";

?>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php

        $pageTitle = "Ownership History";
        $pageSubtitle =
            "View the ownership records associated with vehicles in VISRS.";

        include __DIR__ . "/../includes/header.php";

        ?>


        <div class="content">

            <div class="page-title">

                <div>

                    <h1>
                        Ownership History
                    </h1>

                    <p class="page-subtitle">
                        View the ownership records associated with vehicles in VISRS.
                    </p>

                </div>


                <?php if ($canManage): ?>

                    <div>

                        <a
                            href="add.php"
                            class="button"
                        >
                            Add Ownership Record
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if (
                isset($_GET["deleted"]) &&
                $_GET["deleted"] === "1"
            ): ?>

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

                    Ownership record deleted successfully.

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


            <!-- OWNERSHIP HISTORY TABLE -->

            <section class="card">

                <?php if (count($history) === 0): ?>

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                        "
                    >

                        <h2>
                            No Ownership History
                        </h2>

                        <p
                            style="
                                margin-top:10px;
                                color:#6b7280;
                            "
                        >
                            No ownership history records have been
                            added to the system yet.
                        </p>


                        <?php if ($canManage): ?>

                            <div style="margin-top:20px;">

                                <a
                                    href="add.php"
                                    class="button"
                                >
                                    Add First Record
                                </a>

                            </div>

                        <?php endif; ?>

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

                        <?php if ($canManage): ?>

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

                        <?php endif; ?>


                        <div
                            style="
                                overflow-x:auto;
                            "
                        >

                            <table
                                style="
                                    width:100%;
                                    border-collapse:collapse;
                                "
                            >

                                <thead>

                                    <tr
                                        style="
                                            border-bottom:1px solid #e5e7eb;
                                            text-align:left;
                                        "
                                    >

                                        <?php if ($canManage): ?>

                                            <th
                                                style="
                                                    padding:12px;
                                                    width:36px;
                                                "
                                            >

                                                <input
                                                    type="checkbox"
                                                    id="selectAll"
                                                    aria-label="Select all ownership records"
                                                >

                                            </th>

                                        <?php endif; ?>


                                        <th style="padding:12px;">
                                            Vehicle
                                        </th>

                                        <th style="padding:12px;">
                                            VIN
                                        </th>

                                        <th style="padding:12px;">
                                            Owner
                                        </th>

                                        <th style="padding:12px;">
                                            Start Date
                                        </th>

                                        <th style="padding:12px;">
                                            End Date
                                        </th>

                                        <th style="padding:12px;">
                                            Transfer Reason
                                        </th>

                                        <th style="padding:12px;">
                                            Actions
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $history
                                        as $record
                                    ): ?>

                                        <tr
                                            style="
                                                border-bottom:1px solid #f1f5f9;
                                            "
                                        >

                                            <?php if ($canManage): ?>

                                                <td style="padding:12px;">

                                                    <input
                                                        type="checkbox"
                                                        name="selected_ids[]"
                                                        value="<?= (int) $record["OwnershipID"] ?>"
                                                        class="row-checkbox"
                                                        aria-label="Select this ownership record"
                                                    >

                                                </td>

                                            <?php endif; ?>


                                            <!-- VEHICLE -->

                                            <td style="padding:12px;">

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $record["PlateNumber"]
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small
                                                    style="
                                                        color:#6b7280;
                                                    "
                                                >

                                                    <?= htmlspecialchars(
                                                        $record["Make"]
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        $record["Model"]
                                                    ) ?>

                                                    (<?= htmlspecialchars(
                                                        $record["VehicleYear"]
                                                    ) ?>)

                                                </small>

                                            </td>


                                            <!-- VIN -->

                                            <td style="padding:12px;">

                                                <span
                                                    style="
                                                        font-family:monospace;
                                                    "
                                                >
                                                    <?= htmlspecialchars(
                                                        $record["VIN"]
                                                    ) ?>
                                                </span>

                                            </td>


                                            <!-- OWNER -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $record["FirstName"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $record["LastName"]
                                                ) ?>

                                            </td>


                                            <!-- START DATE -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $record["StartDate"]
                                                ) ?>

                                            </td>


                                            <!-- END DATE -->

                                            <td style="padding:12px;">

                                                <?php if (
                                                    $record["EndDate"] === null ||
                                                    $record["EndDate"] === ""
                                                ): ?>

                                                    <span
                                                        style="
                                                            color:#166534;
                                                            font-weight:600;
                                                        "
                                                    >
                                                        Current Owner
                                                    </span>

                                                <?php else: ?>

                                                    <?= htmlspecialchars(
                                                        $record["EndDate"]
                                                    ) ?>

                                                <?php endif; ?>

                                            </td>


                                            <!-- TRANSFER REASON -->

                                            <td style="padding:12px;">

                                                <?php if (
                                                    !empty(
                                                        $record["TransferReason"]
                                                    )
                                                ): ?>

                                                    <?= htmlspecialchars(
                                                        $record["TransferReason"]
                                                    ) ?>

                                                <?php else: ?>

                                                    <span
                                                        style="
                                                            color:#9ca3af;
                                                        "
                                                    >
                                                        —
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    white-space:nowrap;
                                                "
                                            >

                                                <a
                                                    href="view.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                    class="button button-secondary"
                                                    style="
                                                        padding:8px 12px;
                                                    "
                                                >
                                                    View
                                                </a>


                                                <?php if ($canManage): ?>

                                                    <a
                                                        href="edit.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                        class="button"
                                                        style="
                                                            padding:8px 12px;
                                                            margin-left:5px;
                                                        "
                                                    >
                                                        Edit
                                                    </a>


                                                    <a
                                                        href="delete.php?id=<?= (int) $record["OwnershipID"] ?>"
                                                        class="button button-danger"
                                                        style="
                                                            padding:8px 12px;
                                                            margin-left:5px;
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

            </section>

        </div>

    </main>

</div>


<!-- FIX: moved footer.php OUT of <main> and removed the duplicate
     </body></html> that followed. footer.php closes those tags. -->
<?php require_once __DIR__ . "/../includes/footer.php"; ?>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one ownership record to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected ownership record(s)?\n\n" +
        "Current-owner records cannot be bulk-deleted and will be skipped.\n\n" +
        "This action cannot be undone."
    );
}

</script>