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
| Role-Based Access Control Setup
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

// Admin and Police see ALL vehicles.
$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);

$whereParts = [];
$queryParams = [];

if ($currentRole === 'Seller') {
    // Sellers only see vehicles assigned to them in the marketplace
    $whereParts[] = "v.SellerID = ?";
    $queryParams[] = $currentUserId;
} elseif (!$isAdminOrPolice) {
    // Basic Users only see vehicles they own
    $whereParts[] = "o.UserID = ?";
    $queryParams[] = $currentUserId;
}


/*
|--------------------------------------------------------------------------
| Filter State
|--------------------------------------------------------------------------
*/

$filterSearch = trim($_GET["search"] ?? "");
$filterStatus = trim($_GET["status"] ?? "");

if ($filterSearch !== "") {
    $whereParts[] = "(
        v.PlateNumber LIKE ?
        OR v.VIN LIKE ?
        OR v.Make LIKE ?
        OR v.Model LIKE ?
        OR CONCAT(v.Make, ' ', v.Model) LIKE ?
        OR o.FirstName LIKE ?
        OR o.LastName LIKE ?
        OR CONCAT(o.FirstName, ' ', o.LastName) LIKE ?
    )";
    $searchLike = "%" . $filterSearch . "%";
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
}

if ($filterStatus !== "") {
    $whereParts[] = "v.Status = ?";
    $queryParams[] = $filterStatus;
}

$whereClause = count($whereParts) > 0 
    ? " WHERE " . implode(" AND ", $whereParts) 
    : "";


/*
|--------------------------------------------------------------------------
| Load Vehicles
|--------------------------------------------------------------------------
*/

/* Pagination setup */
$perPage = 25;

/* Count total rows (respecting the role filter + search filters) */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM vehicles v
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    {$whereClause}
");
$countStmt->execute($queryParams);
$totalVehicles = (int) $countStmt->fetchColumn();

/* Compute pagination metadata */
$pagination = paginationMeta($totalVehicles, $perPage);

/* Load only the current page (respecting the role filter + search filters) */
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
    {$whereClause}
    ORDER BY v.VehicleID DESC
    LIMIT ? OFFSET ?
");

/* Bind the UserID (if restricted) + Search filters + Pagination limits */
$paramIndex = 1;
foreach ($queryParams as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($paramIndex++, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $pagination["offset"],  PDO::PARAM_INT);

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
|--------------------------------------------------------------------------
| Query string to preserve filters across pagination
|--------------------------------------------------------------------------
*/

$filterQuery = 
    ($filterSearch !== "" ? "&search=" . urlencode($filterSearch) : "") .
    ($filterStatus !== "" ? "&status=" . urlencode($filterStatus) : "");


require_once "../includes/head.php";

?>

<style>
    .vehicle-filter-bar {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 12px;
        align-items: end;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 20px;
    }
    .vehicle-filter-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .vehicle-filter-field label {
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }
    .vehicle-filter-field input,
    .vehicle-filter-field select {
        width: 100%;
        padding: 9px 11px;
        font-size: 13px;
        background: #ffffff;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        font-family: inherit;
    }
    .vehicle-filter-field input:focus,
    .vehicle-filter-field select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
    }
    .vehicle-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .vehicle-filter-actions .button {
        padding: 9px 14px;
        font-size: 13px;
        white-space: nowrap;
    }
    .filter-summary {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #64748b;
    }
    .filter-summary a {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
    }
    .filter-summary a:hover {
        text-decoration: underline;
    }
    [data-theme="dark"] .vehicle-filter-bar {
        background: #0f172a;
        border-color: #334155;
    }
    [data-theme="dark"] .vehicle-filter-field label {
        color: #cbd5e1;
    }
    [data-theme="dark"] .vehicle-filter-field input,
    [data-theme="dark"] .vehicle-filter-field select {
        background: #1e293b;
        color: #e2e8f0;
        border-color: #334155;
    }
    @media (max-width: 800px) {
        .vehicle-filter-bar {
            grid-template-columns: 1fr;
        }
    }
</style>

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
                <div class="auto-dismiss" style="background:#dcfce7; color:#166534; padding:12px; border-radius:6px; margin-bottom:20px;">
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

                <div class="auto-dismiss" style="background:<?= $bulkBg ?>; color:<?= $bulkColor ?>; padding:12px; border-radius:6px; margin-bottom:20px;">

                    <?= htmlspecialchars($bulkMessage["text"]) ?>

                    <?php if (!empty($bulkMessage["skipped"])): ?>
                        <ul style="margin:8px 0 0 20px; padding:0;">
                            <?php foreach ($bulkMessage["skipped"] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- FILTER BAR -->

            <form method="GET" action="" class="vehicle-filter-bar">

                <div class="vehicle-filter-field">
                    <label for="search">Search (Plate, VIN, Make, Model, Owner)</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        placeholder="e.g. GND-4827, Toyota, John Doe..." 
                        value="<?= htmlspecialchars($filterSearch) ?>"
                    >
                </div>

                <div class="vehicle-filter-field">
                    <label for="status">Vehicle Status</label>
                    <select id="status" name="status">
                        <option value="">All statuses</option>
                        <option value="Active" <?= $filterStatus === "Active" ? "selected" : "" ?>>Active</option>
                        <option value="Inactive" <?= $filterStatus === "Inactive" ? "selected" : "" ?>>Inactive</option>
                        <option value="Stolen" <?= $filterStatus === "Stolen" ? "selected" : "" ?>>Stolen</option>
                        <option value="Sold" <?= $filterStatus === "Sold" ? "selected" : "" ?>>Sold</option>
                    </select>
                </div>

                <div class="vehicle-filter-actions">
                    <button type="submit" class="button">Apply</button>
                    <?php if ($filterSearch !== "" || $filterStatus !== ""): ?>
                        <a href="index.php" class="button button-secondary">Clear</a>
                    <?php endif; ?>
                </div>

            </form>

            <?php if ($filterSearch !== "" || $filterStatus !== ""): ?>
                <div class="filter-summary">
                    <span><?= $pagination["totalRows"] ?> result(s) match your filters.</span>
                    <a href="index.php">Reset all filters</a>
                </div>
            <?php endif; ?>


            <!-- ACTION BAR -->

            <section class="card">

                <div style="display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">

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

                        <a href="add.php" class="button">
                            + Add Vehicle
                        </a>

                    <?php endif; ?>

                </div>

            </section>


            <!-- VEHICLE TABLE -->

            <section class="card" style="margin-top:20px;">

                <h2>
                    Vehicle Records
                </h2>


                <?php if (!empty($vehicles)): ?>


                    <form method="POST" action="bulk_delete.php" id="bulkForm">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">


                        <!-- BULK ACTION BAR -->

                        <?php if (canManageVehicles()): ?>

                            <div class="bulk-action-bar" id="bulkActionBar">

                                <span class="bulk-action-count">
                                    <strong id="bulkSelectedCount">0</strong> selected
                                </span>

                                <div class="bulk-action-buttons">

                                    <button type="button" class="button button-secondary" id="bulkClearSelection">
                                        Clear
                                    </button>

                                    <button type="submit" class="button" style="background:#991b1b;" onclick="return confirmBulkDelete();">
                                        Delete Selected
                                    </button>

                                </div>

                            </div>

                        <?php endif; ?>


                        <div style="overflow-x:auto; margin-top:15px;">

                            <table id="vehiclesTable" class="mobile-stack-table" style="width:100%; border-collapse:collapse;">

                                <thead>

                                    <tr style="border-bottom:2px solid #e5e7eb; text-align:left;">

                                        <?php if (canManageVehicles()): ?>

                                            <th style="padding:12px; width:36px;">
                                                <input type="checkbox" id="selectAll" aria-label="Select all vehicles">
                                            </th>

                                        <?php endif; ?>

                                        <th style="padding:12px;">Plate Number</th>
                                        <th style="padding:12px;">VIN</th>
                                        <th style="padding:12px;">Vehicle</th>
                                        <th style="padding:12px;">Year</th>
                                        <th style="padding:12px;">Owner</th>
                                        <th style="padding:12px;">Status</th>
                                        <th style="padding:12px;">Action</th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($vehicles as $vehicle): ?>

                                        <tr style="border-bottom:1px solid #e5e7eb;">

                                            <?php if (canManageVehicles()): ?>

                                                <td style="padding:12px;" data-label="Select">

                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int) $vehicle["VehicleID"] ?>" class="row-checkbox" aria-label="Select this vehicle">

                                                </td>

                                            <?php endif; ?>


                                            <!-- PLATE -->

                                            <td style="padding:12px;" data-label="Plate Number">
                                                <strong><?= htmlspecialchars($vehicle["PlateNumber"]) ?></strong>
                                            </td>


                                            <!-- VIN -->

                                            <td style="padding:12px;" data-label="VIN">
                                                <?= htmlspecialchars($vehicle["VIN"]) ?>
                                            </td>


                                            <!-- VEHICLE -->

                                            <td style="padding:12px;" data-label="Vehicle">
                                                <?= htmlspecialchars($vehicle["Make"]) ?>
                                                <?= htmlspecialchars($vehicle["Model"]) ?>

                                                <?php if (!empty($vehicle["VehicleType"])): ?>
                                                    <div style="color:#6b7280; font-size:13px; margin-top:3px;">
                                                        <?= htmlspecialchars($vehicle["VehicleType"]) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>


                                            <!-- YEAR -->

                                            <td style="padding:12px;" data-label="Year">
                                                <?= htmlspecialchars($vehicle["VehicleYear"]) ?>
                                            </td>


                                            <!-- OWNER -->

                                            <td style="padding:12px;" data-label="Owner">
                                                <?= htmlspecialchars($vehicle["FirstName"]) ?>
                                                <?= htmlspecialchars($vehicle["LastName"]) ?>
                                            </td>


                                            <!-- STATUS -->

                                            <td style="padding:12px;" data-label="Status">

                                                <?php

                                                $statusClass = "";

                                                switch ($vehicle["Status"]) {
                                                    case "Active": $statusClass = "status-active"; break;
                                                    case "Inactive": $statusClass = "status-inactive"; break;
                                                    case "Stolen": $statusClass = "status-stolen"; break;
                                                    case "Sold": $statusClass = "status-sold"; break;
                                                }

                                                ?>

                                                <span class="<?= htmlspecialchars($statusClass) ?>">
                                                    <?= htmlspecialchars($vehicle["Status"]) ?>
                                                </span>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td style="padding:12px; white-space:nowrap;" data-label="Actions">

                                                <a href="view.php?id=<?= (int) $vehicle["VehicleID"] ?>" class="button" style="padding:8px 12px;">
                                                    View
                                                </a>

                                                <?php if (canManageVehicles()): ?>

                                                    <a href="edit.php?id=<?= (int) $vehicle["VehicleID"] ?>" class="button button-secondary" style="padding:8px 12px;">
                                                        Edit
                                                    </a>

                                                    <a href="delete.php?id=<?= (int) $vehicle["VehicleID"] ?>" class="button" style="padding:8px 12px; background:#991b1b;">
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

                    <?php 
                    $pagination["extraQuery"] = $filterQuery;
                    renderPagination($pagination); 
                    ?>


                <?php else: ?>

                    <div style="padding:30px; text-align:center; color:#6b7280;">

                        <p>
                            No vehicle records found matching your criteria.
                        </p>

                        <?php if ($filterSearch !== "" || $filterStatus !== ""): ?>
                            <p style="margin-top:10px;">
                                <a href="index.php" class="button">Clear Filters</a>
                            </p>
                        <?php elseif (canManageVehicles()): ?>

                            <p style="margin-top:10px;">
                                <a href="add.php" class="button">
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