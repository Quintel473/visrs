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
| Role-Based Access Control Setup
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);

// Determine if the user wants to see ONLY their vehicles (Admin/Police only)
$viewScope = $_GET["scope"] ?? "all"; 
if (!$isAdminOrPolice) {
    $viewScope = "mine"; // Sellers and Users are forced to "mine"
}


/*
|--------------------------------------------------------------------------
| Vehicle Scoping Logic
|--------------------------------------------------------------------------
*/

$filterVehicleId = isset($_GET["vehicle_id"]) ? (int) $_GET["vehicle_id"] : 0;
$scopedVehicle = null;

if ($filterVehicleId > 0) {
    $svStmt = $pdo->prepare("
        SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.VehicleYear, o.UserID AS OwnerUserID, v.SellerID
        FROM vehicles v
        INNER JOIN owners o ON v.OwnerID = o.OwnerID
        WHERE v.VehicleID = ?
        LIMIT 1
    ");
    $svStmt->execute([$filterVehicleId]);
    $scopedVehicle = $svStmt->fetch() ?: null;

    if (!$scopedVehicle) {
        $filterVehicleId = 0; // Invalid ID, fallback to all
    } else {
        // Security check
        if (!$isAdminOrPolice) {
            $hasAccess = false;
            if ($currentRole === 'Seller' && (int)$scopedVehicle["SellerID"] === $currentUserId) {
                $hasAccess = true;
            } elseif ((int)$scopedVehicle["OwnerUserID"] === $currentUserId) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
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
                                <h2>You do not have permission to view this vehicle's insurance.</h2>
                                <p>You can only view insurance records for vehicles that are linked to your account.</p>
                                <a href="index.php" class="button">Return to Insurance Records</a>
                            </div>
                        </div>
                    </main>
                </div>
                </body>
                </html>
                <?php
                exit;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$filterSearch = trim($_GET["search"] ?? "");
$filterStatus = trim($_GET["status"] ?? "");


/*
|--------------------------------------------------------------------------
| Base Query Constraints
|--------------------------------------------------------------------------
*/

$whereParts = [];
$queryParams = [];

// 1. Apply Role-based filtering
if ($viewScope === "mine") {
    $whereParts[] = "(v.SellerID = ? OR o.UserID = ?)";
    $queryParams[] = $currentUserId;
    $queryParams[] = $currentUserId;
}

// 2. Apply specific vehicle scope filter
if ($filterVehicleId > 0) {
    $whereParts[] = "i.VehicleID = ?";
    $queryParams[] = $filterVehicleId;
}

// 3. Apply search filter
if ($filterSearch !== "") {
    $whereParts[] = "(v.PlateNumber LIKE ? OR i.PolicyNumber LIKE ? OR i.ProviderName LIKE ?)";
    $searchLike = "%" . $filterSearch . "%";
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
}

// 4. Apply status filter
if ($filterStatus !== "") {
    $whereParts[] = "i.Status = ?";
    $queryParams[] = $filterStatus;
}

$whereClause = count($whereParts) > 0 ? " WHERE " . implode(" AND ", $whereParts) : "";


/*
|--------------------------------------------------------------------------
| Success Message
|--------------------------------------------------------------------------
*/

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {
    $success = "Insurance record deleted successfully.";
}


/*
|--------------------------------------------------------------------------
| Pagination Setup
|--------------------------------------------------------------------------
*/

$perPage = 25;

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM insurance i
    INNER JOIN vehicles v ON i.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    {$whereClause}
");
$countStmt->execute($queryParams);
$totalInsurance = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalInsurance, $perPage);


/*
|--------------------------------------------------------------------------
| Get Insurance Records
|--------------------------------------------------------------------------
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
        
    INNER JOIN owners o
        ON v.OwnerID = o.OwnerID

    {$whereClause}

    ORDER BY
        i.ExpiryDate ASC,
        i.InsuranceID DESC

    LIMIT ? OFFSET ?
");

$paramIndex = 1;
foreach ($queryParams as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($paramIndex++, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $pagination["offset"],  PDO::PARAM_INT);

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
$pageSubtitle = "Manage vehicle insurance records and policies.";

$hasFilters = ($filterSearch !== "" || $filterStatus !== "");

$pagination["extraQuery"] = 
    ($filterSearch !== "" ? "&search=" . urlencode($filterSearch) : "") .
    ($filterStatus !== "" ? "&status=" . urlencode($filterStatus) : "") .
    ($filterVehicleId > 0 ? "&vehicle_id=" . $filterVehicleId : "") .
    ($viewScope === "mine" ? "&scope=mine" : "");

require_once "../includes/head.php";

?>

<style>
    .filter-bar { display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; align-items: end; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .filter-field { display: flex; flex-direction: column; min-width: 0; }
    .filter-field label { margin-bottom: 6px; font-size: 12px; font-weight: 600; color: #374151; }
    .filter-field input, .filter-field select { width: 100%; padding: 9px 11px; font-size: 13px; background: #ffffff; color: #111827; border: 1px solid #d1d5db; border-radius: 7px; font-family: inherit; }
    .filter-field input:focus, .filter-field select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.10); }
    .filter-actions { display: flex; gap: 8px; align-items: center; }
    .filter-actions .button { padding: 9px 14px; font-size: 13px; white-space: nowrap; }
    .filter-summary { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; font-size: 13px; color: #64748b; }
    .filter-summary a { color: #2563eb; font-weight: 600; text-decoration: none; }
    .filter-summary a:hover { text-decoration: underline; }
    
    /* New Scope Toggle Styles */
    .scope-toggle { display: inline-flex; background: #e5e7eb; border-radius: 8px; padding: 4px; margin-bottom: 20px; }
    .scope-toggle a { padding: 8px 20px; font-size: 14px; font-weight: 600; color: #4b5563; text-decoration: none; border-radius: 6px; transition: all 0.2s ease; }
    .scope-toggle a.active { background: #ffffff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    @media (max-width: 800px) { .filter-bar { grid-template-columns: 1fr; } }
</style>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php require_once __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <!-- VEHICLE SCOPE BANNER -->

            <?php if ($scopedVehicle): ?>

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:16px 20px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <div>
                        <span style="font-size:11px; font-weight:700; color:#1d4ed8; text-transform:uppercase; letter-spacing:0.06em;">Scoped to vehicle</span><br>
                        <span style="font-size:18px; font-weight:700; color:#111827;"><?= htmlspecialchars($scopedVehicle["PlateNumber"]) ?> — <?= htmlspecialchars($scopedVehicle["Make"] . " " . $scopedVehicle["Model"]) ?> (<?= htmlspecialchars((string) $scopedVehicle["VehicleYear"]) ?>)</span><br>
                        <span style="font-size:13px; color:#475569;">Showing only insurance records for this vehicle.</span>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <a href="../vehicles/view.php?id=<?= (int) $scopedVehicle["VehicleID"] ?>" class="button button-secondary">← Back to Vehicle</a>
                        <a href="index.php" class="button button-secondary">Show All My Insurance</a>
                    </div>
                </div>

            <?php endif; ?>


            <!-- PAGE HEADER -->

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; gap:15px; flex-wrap:wrap;">

                <div>
                    <h2 style="margin-bottom:5px;">Insurance Records</h2>
                    <p>View and manage insurance policies associated with registered vehicles.</p>
                </div>

                <?php if (canManageInsurance()): ?>
                    <a href="add.php" class="button">+ Add Insurance</a>
                <?php endif; ?>

            </div>


            <!-- SCOPE TOGGLE (Only for Admin/Police) -->
            <?php if ($isAdminOrPolice): ?>
                <div class="scope-toggle">
                    <a href="?scope=all<?= $filterSearch !== "" ? '&search=' . urlencode($filterSearch) : '' ?><?= $filterStatus !== "" ? '&status=' . urlencode($filterStatus) : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . $filterVehicleId : '' ?>" class="<?= $viewScope === 'all' ? 'active' : '' ?>">
                        All System Records
                    </a>
                    <a href="?scope=mine<?= $filterSearch !== "" ? '&search=' . urlencode($filterSearch) : '' ?><?= $filterStatus !== "" ? '&status=' . urlencode($filterStatus) : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . $filterVehicleId : '' ?>" class="<?= $viewScope === 'mine' ? 'active' : '' ?>">
                        My Vehicles Only
                    </a>
                </div>
            <?php endif; ?>


            <!-- SUCCESS MESSAGE -->

            <?php if ($success !== ""): ?>
                <div class="auto-dismiss" style="background:#dcfce7; color:#166534; padding:14px; border-radius:8px; margin-bottom:20px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>
                <div class="auto-dismiss" style="background:#fee2e2; color:#991b1b; padding:14px; border-radius:8px; margin-bottom:20px;">
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

                <div class="auto-dismiss" style="background:<?= $bulkBg ?>; color:<?= $bulkColor ?>; padding:14px; border-radius:8px; margin-bottom:20px;">
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

            <form method="GET" action="" class="filter-bar">
                <?php if ($filterVehicleId > 0): ?>
                    <input type="hidden" name="vehicle_id" value="<?= (int) $filterVehicleId ?>">
                <?php endif; ?>
                <?php if ($viewScope === 'mine'): ?>
                    <input type="hidden" name="scope" value="mine">
                <?php endif; ?>
                
                <div class="filter-field">
                    <label for="search">Search Insurance</label>
                    <input type="text" id="search" name="search" placeholder="Policy number, provider, plate..." value="<?= htmlspecialchars($filterSearch) ?>">
                </div>
                <div class="filter-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="Active" <?= $filterStatus === "Active" ? "selected" : "" ?>>Active</option>
                        <option value="Expired" <?= $filterStatus === "Expired" ? "selected" : "" ?>>Expired</option>
                        <option value="Cancelled" <?= $filterStatus === "Cancelled" ? "selected" : "" ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="button">Apply</button>
                    <?php if ($hasFilters): ?>
                        <a href="index.php?<?= $viewScope === 'mine' ? 'scope=mine' : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . $filterVehicleId : '' ?>" class="button button-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($hasFilters): ?>
                <div class="filter-summary">
                    <span><?= $pagination["totalRows"] ?> result(s) match your filters.</span>
                    <a href="index.php?<?= $viewScope === 'mine' ? 'scope=mine' : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . $filterVehicleId : '' ?>">Reset all filters</a>
                </div>
            <?php endif; ?>


            <!-- INSURANCE TABLE -->

            <div class="card">

                <?php if (count($insuranceRecords) > 0): ?>

                    <form method="POST" action="bulk_delete.php" id="bulkForm">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">

                        <!-- BULK ACTION BAR -->

                        <?php if (canManageInsurance()): ?>

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


                        <div style="overflow-x:auto;">

                            <table style="width:100%; border-collapse:collapse;">

                                <thead>
                                    <tr>
                                        <?php if (canManageInsurance()): ?>
                                            <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd; width:36px;">
                                                <input type="checkbox" id="selectAll" aria-label="Select all insurance records">
                                            </th>
                                        <?php endif; ?>

                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Vehicle</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Provider</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Policy Number</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Coverage</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Expiry Date</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Status</th>
                                        <th style="text-align:left; padding:12px; border-bottom:1px solid #ddd;">Actions</th>
                                    </tr>
                                </thead>


                                <tbody>

                                    <?php foreach ($insuranceRecords as $insurance): ?>

                                        <tr>

                                            <?php if (canManageInsurance()): ?>
                                                <td style="padding:12px; border-bottom:1px solid #eee;">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= (int) $insurance["InsuranceID"] ?>" class="row-checkbox" aria-label="Select this insurance record">
                                                </td>
                                            <?php endif; ?>


                                            <!-- VEHICLE -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">
                                                <strong><?= htmlspecialchars($insurance["PlateNumber"]) ?></strong>
                                                <br>
                                                <small><?= htmlspecialchars($insurance["Make"]) ?> <?= htmlspecialchars($insurance["Model"]) ?> (<?= htmlspecialchars($insurance["VehicleYear"]) ?>)</small>
                                            </td>


                                            <!-- PROVIDER -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">
                                                <?= htmlspecialchars($insurance["ProviderName"]) ?>
                                            </td>


                                            <!-- POLICY NUMBER -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">
                                                <?= htmlspecialchars($insurance["PolicyNumber"]) ?>
                                            </td>


                                            <!-- COVERAGE -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">
                                                <?= $insurance["CoverageType"] ? htmlspecialchars($insurance["CoverageType"]) : "Not specified" ?>
                                            </td>


                                            <!-- EXPIRY -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">
                                                <?= $insurance["ExpiryDate"] ? htmlspecialchars(date("M d, Y", strtotime($insurance["ExpiryDate"]))) : "Not specified" ?>
                                            </td>


                                            <!-- STATUS -->
                                            <td style="padding:12px; border-bottom:1px solid #eee;">

                                                <?php
                                                $status = $insurance["Status"];
                                                $statusBackground = "#e5e7eb";
                                                $statusColor = "#374151";

                                                if ($status === "Active") {
                                                    $statusBackground = "#dcfce7";
                                                    $statusColor = "#166534";
                                                } elseif ($status === "Expired") {
                                                    $statusBackground = "#fee2e2";
                                                    $statusColor = "#991b1b";
                                                } elseif ($status === "Cancelled") {
                                                    $statusBackground = "#fef3c7";
                                                    $statusColor = "#92400e";
                                                }
                                                ?>

                                                <span style="display:inline-block; padding:6px 10px; border-radius:20px; background:<?= $statusBackground ?>; color:<?= $statusColor ?>; font-size:13px; font-weight:600;">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>

                                            </td>


                                            <!-- ACTIONS -->
                                            <td style="padding:12px; border-bottom:1px solid #eee; white-space:nowrap;">

                                                <a href="view.php?id=<?= (int) $insurance["InsuranceID"] ?>" class="button button-secondary" style="padding:8px 12px; margin-right:4px;">
                                                    View
                                                </a>

                                                <?php if (canManageInsurance()): ?>

                                                    <a href="edit.php?id=<?= (int) $insurance["InsuranceID"] ?>" class="button" style="padding:8px 12px; margin-right:4px;">
                                                        Edit
                                                    </a>

                                                    <a href="delete.php?id=<?= (int) $insurance["InsuranceID"] ?>" class="button" style="padding:8px 12px; background:#991b1b;">
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
                    <div style="text-align:center; padding:50px 20px;">

                        <div style="font-size:48px; margin-bottom:15px;">🛡️</div>

                        <h2>No Insurance Records</h2>

                        <p style="margin:10px 0 25px;">No vehicle insurance policies match your criteria.</p>

                        <?php if ($hasFilters): ?>
                            <a href="index.php?<?= $viewScope === 'mine' ? 'scope=mine' : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . $filterVehicleId : '' ?>" class="button">Clear Filters</a>
                        <?php elseif (canManageInsurance()): ?>
                            <a href="add.php" class="button">+ Add First Insurance Record</a>
                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

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