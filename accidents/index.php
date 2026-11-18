<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";
require_once "../includes/functions.php";

$basePath = "../";
$activePage = "accidents";

$pageTitle = "Accident Records";
$pageSubtitle = "Manage and review vehicle accident records.";


/*
|--------------------------------------------------------------------------
| Role-Based Access Control Setup
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);
$canManage = in_array($currentRole, ['Admin', 'Police'], true);

// Determine if the user wants to see ONLY their vehicles (Admin/Police only)
$viewScope = $_GET["scope"] ?? "all"; 
if (!$isAdminOrPolice) {
    $viewScope = "mine"; // Sellers and Users are forced to "mine"
}


/*
|--------------------------------------------------------------------------
| Filter state.
|--------------------------------------------------------------------------
*/

$filterFrom         = trim($_GET["from"]         ?? "");
$filterTo           = trim($_GET["to"]           ?? "");
$filterDamage       = trim($_GET["damage"]       ?? "");
$filterVehicle      = trim($_GET["vehicle"]      ?? "");

$filterVehicleId = isset($_GET["vehicle_id"]) ? (int) $_GET["vehicle_id"] : 0;
if ($filterVehicleId < 0) { $filterVehicleId = 0; }

/* Load the scoped vehicle's basic info for the page header. */
$scopedVehicle = null;

if ($filterVehicleId > 0) {
    $svStmt = $pdo->prepare("
        SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.VehicleYear, v.Status, o.UserID AS OwnerUserID, v.SellerID
        FROM vehicles v
        INNER JOIN owners o ON v.OwnerID = o.OwnerID
        WHERE v.VehicleID = ?
        LIMIT 1
    ");
    $svStmt->execute([$filterVehicleId]);
    $scopedVehicle = $svStmt->fetch() ?: null;

    if (!$scopedVehicle) {
        $filterVehicleId = 0;
    } else {
        // SECURITY CHECK: If they aren't Admin/Police, verify access based on role.
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
                                <h2>You do not have permission to view this vehicle's accidents.</h2>
                                <p>You can only view accident records for vehicles that are linked to your account.</p>
                                <a href="index.php" class="button">Return to Accident Records</a>
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


$validDamageLevels = ["Minor", "Moderate", "Major", "Severe"];
if (!in_array($filterDamage, $validDamageLevels, true)) { $filterDamage = ""; }
if ($filterFrom !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) { $filterFrom = ""; }
if ($filterTo !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) { $filterTo = ""; }


/*
|--------------------------------------------------------------------------
| Build WHERE clause.
|--------------------------------------------------------------------------
*/

$whereParts  = [];
$whereParams = [];

if ($filterFrom !== "") {
    $whereParts[]  = "a.AccidentDate >= ?";
    $whereParams[] = $filterFrom;
}

if ($filterTo !== "") {
    $whereParts[]  = "a.AccidentDate <= ?";
    $whereParams[] = $filterTo;
}

if ($filterDamage !== "") {
    $whereParts[]  = "a.DamageLevel = ?";
    $whereParams[] = $filterDamage;
}

if ($filterVehicleId > 0) {
    $whereParts[]  = "a.VehicleID = ?";
    $whereParams[] = $filterVehicleId;
}

if ($filterVehicle !== "") {
    $whereParts[]  = "(v.PlateNumber LIKE ? OR v.Make LIKE ? OR v.Model LIKE ? OR CONCAT(v.Make, ' ', v.Model) LIKE ?)";
    $vehicleLike   = "%" . $filterVehicle . "%";
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
    $whereParams[] = $vehicleLike;
}

// Apply the view scope (My Vehicles vs All System)
if ($viewScope === "mine" && $filterVehicleId === 0) {
    $whereParts[] = "(v.SellerID = ? OR o.UserID = ?)";
    $whereParams[] = $currentUserId;
    $whereParams[] = $currentUserId;
}

$whereSql = $whereParts ? "WHERE " . implode(" AND ", $whereParts) : "";


/*
|--------------------------------------------------------------------------
| Pagination setup.
|--------------------------------------------------------------------------
*/

$perPage = 25;

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    {$whereSql}
");

$countStmt->execute($whereParams);
$totalAccidents = (int) $countStmt->fetchColumn();

$pagination = paginationMeta($totalAccidents, $perPage);


/*
|--------------------------------------------------------------------------
| Load accident records for current page.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT a.AccidentID, a.VehicleID, a.AccidentDate, a.Location, a.Description, a.DamageLevel, a.ReportNumber,
           v.PlateNumber, v.VIN, v.Make, v.Model, v.VehicleYear
    FROM accidents a
    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    {$whereSql}
    ORDER BY a.AccidentDate DESC, a.AccidentID DESC
    LIMIT ? OFFSET ?
");

$paramIndex = 1;
foreach ($whereParams as $p) { $stmt->bindValue($paramIndex++, $p); }
$stmt->bindValue($paramIndex++, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $pagination["offset"],  PDO::PARAM_INT);
$stmt->execute();

$accidents = $stmt->fetchAll();


/* Any filters active? Used to show a "Clear filters" link. */
$hasFilters = ($filterFrom !== "" || $filterTo !== "" || $filterDamage !== "" || $filterVehicle !== "");

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);

$filterQuery = ($filterFrom !== "" ? "&from=" . urlencode($filterFrom) : "") .
               ($filterTo !== "" ? "&to=" . urlencode($filterTo) : "") .
               ($filterDamage !== "" ? "&damage=" . urlencode($filterDamage) : "") .
               ($filterVehicle !== "" ? "&vehicle=" . urlencode($filterVehicle) : "") .
               ($filterVehicleId > 0 ? "&vehicle_id=" . urlencode($filterVehicleId) : "") .
               ($viewScope === "mine" ? "&scope=mine" : "");

$exportQuery = $filterQuery !== "" ? "?" . ltrim($filterQuery, "&") : "";

require_once "../includes/head.php";

?>

<style>
    .filter-bar { display: grid; grid-template-columns: 1.1fr 1.1fr 1fr 1.4fr auto; gap: 12px; align-items: end; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .filter-field { display: flex; flex-direction: column; min-width: 0; }
    .filter-field label { margin-bottom: 6px; font-size: 12px; font-weight: 600; color: #374151; }
    .filter-field input, .filter-field select { width: 100%; padding: 9px 11px; font-size: 13px; background: #ffffff; color: #111827; border: 1px solid #d1d5db; border-radius: 7px; font-family: inherit; }
    .filter-field input:focus, .filter-field select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.10); }
    .filter-actions { display: flex; gap: 8px; align-items: center; }
    .filter-actions .button { padding: 9px 14px; font-size: 13px; white-space: nowrap; }
    .filter-summary { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; font-size: 13px; color: #64748b; }
    .filter-summary a { color: #2563eb; font-weight: 600; text-decoration: none; }
    .filter-summary a:hover { text-decoration: underline; }
    
    .scope-toggle { display: inline-flex; background: #e5e7eb; border-radius: 8px; padding: 4px; margin-bottom: 20px; }
    .scope-toggle a { padding: 8px 20px; font-size: 14px; font-weight: 600; color: #4b5563; text-decoration: none; border-radius: 6px; transition: all 0.2s ease; }
    .scope-toggle a.active { background: #ffffff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    .vehicle-scope-banner { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding: 16px 20px; margin-bottom: 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; }
    .vehicle-scope-banner .scope-info { display: flex; flex-direction: column; gap: 4px; }
    .vehicle-scope-banner .scope-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #1d4ed8; }
    .vehicle-scope-banner .scope-vehicle { font-size: 18px; font-weight: 700; color: #111827; }
    .vehicle-scope-banner .scope-meta { font-size: 13px; color: #475569; }
    .vehicle-scope-banner .scope-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    
    /* Table base styles */
    .accident-table { width: 100%; border-collapse: collapse; }
    .accident-table th { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
    .accident-table td { padding: 12px; border-bottom: 1px solid #eee; }
    .accident-table th:first-child, .accident-table td:first-child { width: 36px; }

    @media (max-width: 900px) { .filter-bar { grid-template-columns: 1fr 1fr; } .filter-actions { grid-column: 1 / -1; } }
    @media (max-width: 600px) { .filter-bar { grid-template-columns: 1fr; } }

    /* =========================================================
       DARK MODE OVERRIDES
       ========================================================= */
       
    [data-theme="dark"] .vehicle-scope-banner { background: #172554; border-color: #1e40af; }
    [data-theme="dark"] .vehicle-scope-banner .scope-vehicle { color: #e2e8f0; }
    [data-theme="dark"] .vehicle-scope-banner .scope-meta { color: #cbd5e1; }
    [data-theme="dark"] .vehicle-scope-banner .scope-eyebrow { color: #93c5fd; }
    
    [data-theme="dark"] .filter-bar { background: #0f172a; border-color: #334155; }
    [data-theme="dark"] .filter-field label { color: #cbd5e1; }
    [data-theme="dark"] .filter-field input, [data-theme="dark"] .filter-field select { background: #1e293b; color: #e2e8f0; border-color: #334155; }
    [data-theme="dark"] .filter-field input:focus, [data-theme="dark"] .filter-field select:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,0.20); }
    [data-theme="dark"] .filter-summary { color: #94a3b8; }
    [data-theme="dark"] .filter-summary a { color: #60a5fa; }
    
    /* Border added to the dark mode scope toggle */
    [data-theme="dark"] .scope-toggle { 
        background: #1e293b; 
        border: 1px solid #334155; 
    }
    
    [data-theme="dark"] .scope-toggle a { color: #94a3b8; }
    [data-theme="dark"] .scope-toggle a.active { background: #334155; color: #ffffff; }

    /* Fix table borders */
    [data-theme="dark"] .accident-table th { border-bottom-color: #475569; }
    [data-theme="dark"] .accident-table td { border-bottom-color: #334155; }

    /* Fix the empty state hardcoded background */
    [data-theme="dark"] div[style*="background:#f8fafc"] {
        background: #1e293b !important;
        color: #e2e8f0 !important;
    }
</style>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <div class="card">

                <?php if (isset($_GET["deleted"]) && $_GET["deleted"] === "1"): ?>
                    <div class="auto-dismiss" style="background:#dcfce7; color:#166534; padding:14px; border-radius:8px; margin-bottom:20px;">Accident record deleted successfully.</div>
                <?php endif; ?>

                <?php if ($bulkMessage): ?>
                    <?php $bulkBg = "#fef3c7"; $bulkColor = "#92400e"; if ($bulkMessage["type"] === "success") { $bulkBg = "#dcfce7"; $bulkColor = "#166534"; } elseif ($bulkMessage["type"] === "error") { $bulkBg = "#fee2e2"; $bulkColor = "#991b1b"; } ?>
                    <div class="auto-dismiss" style="background:<?= $bulkBg ?>; color:<?= $bulkColor ?>; padding:14px; border-radius:8px; margin-bottom:20px;"><?= htmlspecialchars($bulkMessage["text"]) ?></div>
                <?php endif; ?>

                <!-- VEHICLE SCOPE BANNER -->
                <?php if ($scopedVehicle): ?>
                    <div class="vehicle-scope-banner">
                        <div class="scope-info">
                            <span class="scope-eyebrow">Scoped to vehicle</span>
                            <span class="scope-vehicle"><?= htmlspecialchars($scopedVehicle["PlateNumber"]) ?> — <?= htmlspecialchars($scopedVehicle["Make"] . " " . $scopedVehicle["Model"]) ?> (<?= htmlspecialchars((string) $scopedVehicle["VehicleYear"]) ?>)</span>
                            <span class="scope-meta">Showing only accident records for this vehicle.</span>
                        </div>
                        <div class="scope-actions">
                            <a href="../vehicles/view.php?id=<?= (int) $scopedVehicle["VehicleID"] ?>" class="button button-secondary">← Back to Vehicle</a>
                            <a href="index.php" class="button button-secondary">Show All My Accidents</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:20px; flex-wrap:wrap;">
                    <div>
                        <h2><?= $scopedVehicle ? "Vehicle Accidents" : "Accident Records" ?></h2>
                        <p><?= $pagination["totalRows"] ?> accident record(s) found.</p>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="export_csv.php<?= htmlspecialchars($exportQuery) ?>" class="button button-secondary" title="Download CSV">📄 Export CSV</a>
                        <a href="export_pdf.php<?= htmlspecialchars($exportQuery) ?>" target="_blank" class="button button-secondary" title="Export PDF">🖨 Export PDF</a>
                        <?php if ($canManage): ?><a href="add.php" class="button">+ Add Accident</a><?php endif; ?>
                    </div>
                </div>

                <!-- SCOPE TOGGLE (Only for Admin/Police) -->
                <?php if ($isAdminOrPolice && !$scopedVehicle): ?>
                    <div class="scope-toggle">
                        <a href="?scope=all<?= $filterVehicle !== "" ? '&vehicle=' . urlencode($filterVehicle) : '' ?><?= $filterDamage !== "" ? '&damage=' . urlencode($filterDamage) : '' ?>" class="<?= $viewScope === 'all' ? 'active' : '' ?>">All System Records</a>
                        <a href="?scope=mine<?= $filterVehicle !== "" ? '&vehicle=' . urlencode($filterVehicle) : '' ?><?= $filterDamage !== "" ? '&damage=' . urlencode($filterDamage) : '' ?>" class="<?= $viewScope === 'mine' ? 'active' : '' ?>">My Vehicles Only</a>
                    </div>
                <?php endif; ?>

                <!-- FILTER BAR -->
                <form method="GET" action="" class="filter-bar">
                    <?php if ($filterVehicleId > 0): ?><input type="hidden" name="vehicle_id" value="<?= (int) $filterVehicleId ?>"><?php endif; ?>
                    <?php if ($viewScope === 'mine'): ?><input type="hidden" name="scope" value="mine"><?php endif; ?>
                    
                    <div class="filter-field">
                        <label for="from">From Date</label>
                        <input type="date" id="from" name="from" value="<?= htmlspecialchars($filterFrom) ?>">
                    </div>
                    <div class="filter-field">
                        <label for="to">To Date</label>
                        <input type="date" id="to" name="to" value="<?= htmlspecialchars($filterTo) ?>">
                    </div>
                    <div class="filter-field">
                        <label for="damage">Damage Level</label>
                        <select id="damage" name="damage">
                            <option value="">All levels</option>
                            <?php foreach ($validDamageLevels as $level): ?>
                                <option value="<?= htmlspecialchars($level) ?>" <?= $filterDamage === $level ? "selected" : "" ?>><?= htmlspecialchars($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="vehicle">Vehicle (plate, make, or model)</label>
                        <input type="text" id="vehicle" name="vehicle" placeholder="e.g. ABC-123, Toyota, Corolla" value="<?= htmlspecialchars($filterVehicle) ?>" maxlength="100">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="button">Apply</button>
                        <?php if ($hasFilters): ?>
                            <a href="index.php?<?= $viewScope === 'mine' ? 'scope=mine' : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . (int) $filterVehicleId : '' ?>" class="button button-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($hasFilters): ?>
                    <div class="filter-summary">
                        <span><?= $pagination["totalRows"] ?> result(s) match your filters.</span>
                        <a href="index.php?<?= $viewScope === 'mine' ? 'scope=mine' : '' ?><?= $filterVehicleId > 0 ? '&vehicle_id=' . (int) $filterVehicleId : '' ?>">Reset all filters</a>
                    </div>
                <?php endif; ?>

                <!-- LISTINGS -->
                <?php if (empty($accidents)): ?>
                    <div style="padding:30px; text-align:center; background:#f8fafc; border-radius:8px;">
                        <h3><?= $scopedVehicle ? "No Accidents Recorded for This Vehicle" : ($hasFilters ? "No Matching Accidents" : "No Accident Records") ?></h3>
                        <p><?= $scopedVehicle ? "This vehicle has no accident records in the system." : ($hasFilters ? "No accidents match the current filter criteria." : "There are currently no accident records in the system.") ?></p>
                        <?php if ($scopedVehicle): ?>
                            <a href="../vehicles/view.php?id=<?= (int) $scopedVehicle["VehicleID"] ?>" class="button" style="margin-top:10px;">← Back to Vehicle</a>
                        <?php elseif ($hasFilters): ?>
                            <a href="index.php" class="button" style="margin-top:10px;">Clear Filters</a>
                        <?php elseif ($canManage): ?>
                            <a href="add.php" class="button" style="margin-top:10px;">Add First Accident Record</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="bulk_delete.php" id="bulkForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
                        <?php if ($filterVehicleId > 0): ?><input type="hidden" name="return_vehicle_id" value="<?= (int) $filterVehicleId ?>"><?php endif; ?>

                        <?php if ($canManage): ?>
                            <div class="bulk-action-bar" id="bulkActionBar">
                                <span class="bulk-action-count"><strong id="bulkSelectedCount">0</strong> selected</span>
                                <div class="bulk-action-buttons">
                                    <button type="button" class="button button-secondary" id="bulkClearSelection">Clear</button>
                                    <button type="submit" class="button" style="background:#991b1b;" onclick="return confirmBulkDelete();">Delete Selected</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="overflow-x:auto;">
                            <table class="accident-table">
                                <thead>
                                    <tr>
                                        <?php if ($canManage): ?><th><input type="checkbox" id="selectAll"></th><?php endif; ?>
                                        <th>Date</th>
                                        <th>Vehicle</th>
                                        <th>Location</th>
                                        <th>Damage Level</th>
                                        <th>Report Number</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accidents as $accident): ?>
                                        <tr>
                                            <?php if ($canManage): ?><td><input type="checkbox" name="selected_ids[]" value="<?= (int) $accident["AccidentID"] ?>" class="row-checkbox"></td><?php endif; ?>
                                            <td><?= htmlspecialchars(date("M j, Y", strtotime($accident["AccidentDate"]))) ?></td>
                                            <td><strong><?= htmlspecialchars($accident["PlateNumber"]) ?></strong><br><small><?= htmlspecialchars($accident["Make"] . " " . $accident["Model"]) ?> (<?= htmlspecialchars($accident["VehicleYear"]) ?>)</small></td>
                                            <td><?= $accident["Location"] ? htmlspecialchars($accident["Location"]) : "Not specified" ?></td>
                                            <td><?= htmlspecialchars($accident["DamageLevel"]) ?></td>
                                            <td><?= $accident["ReportNumber"] ? htmlspecialchars($accident["ReportNumber"]) : "Not specified" ?></td>
                                            <td style="white-space:nowrap;">
                                                <a href="view.php?id=<?= (int) $accident["AccidentID"] ?>" class="button button-secondary">View</a>
                                                <?php if ($canManage): ?>
                                                    <a href="edit.php?id=<?= (int) $accident["AccidentID"] ?>" class="button">Edit</a>
                                                    <a href="delete.php?id=<?= (int) $accident["AccidentID"] ?>" class="button" style="background:#991b1b; margin-left:5px;">Delete</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <?php $pagination["extraQuery"] = $filterQuery; renderPagination($pagination); ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once "../includes/footer.php"; ?>

<script>
function confirmBulkDelete() {
    var count = document.querySelectorAll(".row-checkbox:checked").length;
    if (count === 0) {
        alert("Please select at least one accident record to delete.");
        return false;
    }
    return confirm("Delete " + count + " selected accident record(s)?\n\nThis action cannot be undone.");
}
</script>