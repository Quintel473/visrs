<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "vehicles";

$pageTitle = "Vehicles";
$pageSubtitle = "View and manage vehicle records stored in the VISRS database";


/*
|--------------------------------------------------------------------------
| Load Vehicles
|--------------------------------------------------------------------------
*/

/* Pagination setup */
$perPage = 25;

/* Count total rows */
$countStmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
$totalVehicles = (int) $countStmt->fetchColumn();

/* Compute pagination metadata */
$pagination = paginationMeta($totalVehicles, $perPage);

/* Load only the current page */
$stmt = $pdo->prepare("
    SELECT
        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.Status,
        o.FirstName,
        o.LastName
    FROM vehicles v
    INNER JOIN owners o
        ON v.OwnerID = o.OwnerID
    ORDER BY v.VehicleID DESC
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue(2, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$vehicles = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Success Messages
|--------------------------------------------------------------------------
*/

$success = "";

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {

    $success = "Vehicle deleted successfully.";

}


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


<body>

<div class="layout">


    <?php

    $activePage = "vehicles";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">


        <?php include __DIR__ . "/../includes/header.php"; ?>


        <main class="content">


            <h1 class="page-title">
                Vehicle Management
            </h1>


            <p class="page-subtitle">
                View and manage vehicle records stored in the VISRS database.
            </p>


            <?php if ($success !== ""): ?>

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

                    <?= htmlspecialchars($success) ?>

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


            <!-- ACTION BAR -->

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
                            Registered Vehicles
                        </h2>

                        <p style="color:#6b7280;">
                            <?= $pagination["totalRows"] ?>
                            vehicle(s) currently registered.
                        </p>

                    </div>


                    <?php if (canManageVehicles()): ?>

                        <a
                            href="add.php"
                            class="button"
                        >
                            + Add Vehicle
                        </a>

                    <?php endif; ?>


                </div>

            </section>


            <!-- VEHICLE TABLE -->

            <section
                class="card"
                style="margin-top:20px;"
            >

                <h2>
                    Vehicle Records
                </h2>


                <?php if (!empty($vehicles)): ?>


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

                        <?php if (canManageVehicles()): ?>

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
                                margin-top:15px;
                            "
                        >

                            <table
                                id="vehiclesTable"
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

                                        <?php if (canManageVehicles()): ?>

                                            <th
                                                style="
                                                    padding:12px;
                                                    width:36px;
                                                "
                                            >

                                                <input
                                                    type="checkbox"
                                                    id="selectAll"
                                                    aria-label="Select all vehicles"
                                                >

                                            </th>

                                        <?php endif; ?>


                                        <th style="padding:12px;">
                                            Plate Number
                                        </th>

                                        <th style="padding:12px;">
                                            VIN
                                        </th>

                                        <th style="padding:12px;">
                                            Vehicle
                                        </th>

                                        <th style="padding:12px;">
                                            Year
                                        </th>

                                        <th style="padding:12px;">
                                            Owner
                                        </th>

                                        <th style="padding:12px;">
                                            Status
                                        </th>

                                        <th style="padding:12px;">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach (
                                        $vehicles as $vehicle
                                    ): ?>


                                        <tr
                                            style="
                                                border-bottom:1px solid #e5e7eb;
                                            "
                                        >


                                            <?php if (canManageVehicles()): ?>

                                                <td style="padding:12px;">

                                                    <input
                                                        type="checkbox"
                                                        name="selected_ids[]"
                                                        value="<?= (int) $vehicle["VehicleID"] ?>"
                                                        class="row-checkbox"
                                                        aria-label="Select this vehicle"
                                                    >

                                                </td>

                                            <?php endif; ?>


                                            <!-- PLATE -->

                                            <td style="padding:12px;">

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $vehicle["PlateNumber"]
                                                    ) ?>
                                                </strong>

                                            </td>


                                            <!-- VIN -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $vehicle["VIN"]
                                                ) ?>

                                            </td>


                                            <!-- VEHICLE -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $vehicle["Make"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $vehicle["Model"]
                                                ) ?>

                                                <?php if (
                                                    !empty(
                                                        $vehicle["VehicleType"]
                                                    )
                                                ): ?>

                                                    <div
                                                        style="
                                                            color:#6b7280;
                                                            font-size:13px;
                                                            margin-top:3px;
                                                        "
                                                    >

                                                        <?= htmlspecialchars(
                                                            $vehicle["VehicleType"]
                                                        ) ?>

                                                    </div>

                                                <?php endif; ?>

                                            </td>


                                            <!-- YEAR -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $vehicle["VehicleYear"]
                                                ) ?>

                                            </td>


                                            <!-- OWNER -->

                                            <td style="padding:12px;">

                                                <?= htmlspecialchars(
                                                    $vehicle["FirstName"]
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $vehicle["LastName"]
                                                ) ?>

                                            </td>


                                            <!-- STATUS -->

                                            <td style="padding:12px;">

                                                <?php

                                                $statusClass = "";

                                                switch (
                                                    $vehicle["Status"]
                                                ) {

                                                    case "Active":
                                                        $statusClass =
                                                            "status-active";
                                                        break;

                                                    case "Inactive":
                                                        $statusClass =
                                                            "status-inactive";
                                                        break;

                                                    case "Stolen":
                                                        $statusClass =
                                                            "status-stolen";
                                                        break;

                                                    case "Sold":
                                                        $statusClass =
                                                            "status-sold";
                                                        break;

                                                }

                                                ?>


                                                <span
                                                    class="<?= htmlspecialchars(
                                                        $statusClass
                                                    ) ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $vehicle["Status"]
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td
                                                style="
                                                    padding:12px;
                                                    white-space:nowrap;
                                                "
                                            >


                                                <a
                                                    href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                                                    class="button"
                                                    style="
                                                        padding:8px 12px;
                                                    "
                                                >
                                                    View
                                                </a>


                                                <?php if (
                                                    canManageVehicles()
                                                ): ?>


                                                    <a
                                                        href="edit.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                                                        class="button button-secondary"
                                                        style="
                                                            padding:8px 12px;
                                                        "
                                                    >
                                                        Edit
                                                    </a>


                                                    <a
                                                        href="delete.php?id=<?= (int) $vehicle["VehicleID"] ?>"
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


                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            color:#6b7280;
                        "
                    >

                        <p>
                            No vehicle records have been added yet.
                        </p>


                        <?php if (
                            canManageVehicles()
                        ): ?>

                            <p style="margin-top:10px;">

                                <a
                                    href="add.php"
                                    class="button"
                                >
                                    Add Your First Vehicle
                                </a>

                            </p>

                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </section>


        </main>


    </main>


</div>


<script>

function confirmBulkDelete() {

    var count = document.querySelectorAll(".row-checkbox:checked").length;

    if (count === 0) {
        alert("Please select at least one vehicle to delete.");
        return false;
    }

    return confirm(
        "Delete " + count + " selected vehicle(s)?\n\n" +
        "Records with related data (insurance, accidents, ownership history) " +
        "will be skipped automatically.\n\n" +
        "This action cannot be undone."
    );
}

</script>


<?php include __DIR__ . "/../includes/footer.php"; ?>