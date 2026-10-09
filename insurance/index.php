<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "insurance";

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["deleted"]) &&
    $_GET["deleted"] === "1"
) {

    $success =
        "Insurance record deleted successfully.";
}


/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/

$perPage = 25;

$countStmt = $pdo->query("SELECT COUNT(*) FROM insurance");
$totalInsurance = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalInsurance, $perPage);


/*
|--------------------------------------------------------------------------
| Get All Insurance Records
|--------------------------------------------------------------------------
|
| Each insurance record is connected to a vehicle.
|
*/

$stmt = $pdo->prepare("
    SELECT
        i.InsuranceID,
        i.VehicleID,
        i.ProviderName,
        i.PolicyNumber,
        i.CoverageType,
        i.StartDate,
        i.ExpiryDate,
        i.Status,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM insurance i

    INNER JOIN vehicles v
        ON i.VehicleID = v.VehicleID

    ORDER BY
        i.ExpiryDate ASC,
        i.InsuranceID DESC

    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$insuranceRecords = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Bulk Action Flash Message
|--------------------------------------------------------------------------
*/

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);


/*
|--------------------------------------------------------------------------
| Page Header Information
|--------------------------------------------------------------------------
*/

$pageTitle = "Insurance";
$pageSubtitle =
    "Manage vehicle insurance records and policies.";


/*
 * FIX: Global <head> (was inline <!DOCTYPE> + <head> block
 * with hardcoded /visrs/css/style.css path).
 */

require_once "../includes/head.php";

?>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <main class="main-content">

        <?php require_once __DIR__ . "/../includes/header.php"; ?>


        <div class="content">


            <!-- PAGE HEADER -->

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:25px;
                    gap:15px;
                    flex-wrap:wrap;
                "
            >

                <div>

                    <h2 style="margin-bottom:5px;">
                        Insurance Records
                    </h2>

                    <p>
                        View and manage insurance policies associated
                        with registered vehicles.
                    </p>

                </div>


                <?php if (canManageInsurance()): ?>

                    <a
                        href="add.php"
                        class="button"
                    >
                        + Add Insurance
                    </a>

                <?php endif; ?>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if ($success !== ""): ?>

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

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>

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

                    <?= htmlspecialchars($error) ?>

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


            <!-- INSURANCE TABLE -->

            <div class="card">

                <?php if (count($insuranceRecords) > 0): ?>


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

                        <?php if (canManageInsurance()): ?>

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

                                    <tr>

                                        <?php if (canManageInsurance()): ?>

                                            <th
                                                style="
                                                    text-align:left;
                                                    padding:12px;
                                                    border-bottom:1px solid #ddd;
                                                    width:36px;
                                                "
                                            >

                                                <input
                                                    type="checkbox"
                                                    id="selectAll"
                                                    aria-label="Select all insurance records"
                                                >

                                            </th>

                                        <?php endif; ?>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Vehicle
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Provider
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Policy Number
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Coverage
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Expiry Date
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Status
                                        </th>


                                        <th
                                            style="
                                                text-align:left;
                                                padding:12px;
                                                border-bottom:1px solid #ddd;
                                            "
                                        >
                                            Actions
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $insuranceRecords
                                        as $insurance
                                    ): ?>

                                        <tr>


                                            <?php if (canManageInsurance()): ?>

                                                <td
                                                    style="
                                                        padding:12px;
                                                        border-bottom:1px solid #eee;
                                                    "
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="selected_ids[]"
                                                        value="<?= (int) $insurance["InsuranceID"] ?>"
                                                        class="row-checkbox"
                                                        aria-label="Select this insurance record"
                                                    >

                                                </td>

                                            <?php endif; ?>


                                            <!-- VEHICLE -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $insurance["PlateNumber"]
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small>

                                                    <?= htmlspecialchars(
                                                        $insurance["Make"]
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        $insurance["Model"]
                                                    ) ?>

                                                    (<?= htmlspecialchars(
                                                        $insurance["VehicleYear"]
                                                    ) ?>)

                                                </small>

                                            </td>


                                            <!-- PROVIDER -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $insurance["ProviderName"]
                                                ) ?>

                                            </td>


                                            <!-- POLICY NUMBER -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $insurance["PolicyNumber"]
                                                ) ?>

                                            </td>


                                            <!-- COVERAGE -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <?= $insurance["CoverageType"]
                                                    ? htmlspecialchars(
                                                        $insurance["CoverageType"]
                                                    )
                                                    : "Not specified"
                                                ?>

                                            </td>


                                            <!-- EXPIRY -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <?= $insurance["ExpiryDate"]
                                                    ? htmlspecialchars(
                                                        date(
                                                            "M d, Y",
                                                            strtotime(
                                                                $insurance["ExpiryDate"]
                                                            )
                                                        )
                                                    )
                                                    : "Not specified"
                                                ?>

                                            </td>


                                            <!-- STATUS -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                "
                                            >

                                                <?php

                                                $status =
                                                    $insurance["Status"];

                                                $statusBackground =
                                                    "#e5e7eb";

                                                $statusColor =
                                                    "#374151";


                                                if (
                                                    $status === "Active"
                                                ) {

                                                    $statusBackground =
                                                        "#dcfce7";

                                                    $statusColor =
                                                        "#166534";

                                                } elseif (
                                                    $status === "Expired"
                                                ) {

                                                    $statusBackground =
                                                        "#fee2e2";

                                                    $statusColor =
                                                        "#991b1b";

                                                } elseif (
                                                    $status === "Cancelled"
                                                ) {

                                                    $statusBackground =
                                                        "#fef3c7";

                                                    $statusColor =
                                                        "#92400e";
                                                }

                                                ?>

                                                <span
                                                    style="
                                                        display:inline-block;
                                                        padding:6px 10px;
                                                        border-radius:20px;
                                                        background:<?= $statusBackground ?>;
                                                        color:<?= $statusColor ?>;
                                                        font-size:13px;
                                                        font-weight:600;
                                                    "
                                                >

                                                    <?= htmlspecialchars(
                                                        $status
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    border-bottom:1px solid #eee;
                                                    white-space:nowrap;
                                                "
                                            >

                                                <a
                                                    href="view.php?id=<?= (int) $insurance["InsuranceID"] ?>"
                                                    class="button button-secondary"
                                                    style="
                                                        padding:8px 12px;
                                                        margin-right:4px;
                                                    "
                                                >
                                                    View
                                                </a>


                                                <?php if (
                                                    canManageInsurance()
                                                ): ?>

                                                    <a
                                                        href="edit.php?id=<?= (int) $insurance["InsuranceID"] ?>"
                                                        class="button"
                                                        style="
                                                            padding:8px 12px;
                                                            margin-right:4px;
                                                        "
                                                    >
                                                        Edit
                                                    </a>


                                                    <a
                                                        href="delete.php?id=<?= (int) $insurance["InsuranceID"] ?>"
                                                        class="button"
                                                        style="
                                                            padding:8px 12px;
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


                <?php else: ?>

                    <!-- EMPTY STATE -->

                    <div
                        style="
                            text-align:center;
                            padding:50px 20px;
                        "
                    >

                        <div
                            style="
                                font-size:48px;
                                margin-bottom:15px;
                            "
                        >
                            🛡️
                        </div>


                        <h2>
                            No Insurance Records
                        </h2>


                        <p
                            style="
                                margin:10px 0 25px;
                            "
                        >
                            No vehicle insurance policies have been
                            added to the system yet.
                        </p>


                        <?php if (
                            canManageInsurance()
                        ): ?>

                            <a
                                href="add.php"
                                class="button"
                            >
                                + Add First Insurance Record
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<!-- FIX: moved footer.php OUT of <main> (it was inside the content
     wrapper) and removed the duplicate </body></html> that followed.
     footer.php already closes those tags. -->
<?php require_once __DIR__ . "/../includes/footer.php"; ?>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one insurance record to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected insurance record(s)?\n\n" +
        "This action cannot be undone."
    );
}

</script>