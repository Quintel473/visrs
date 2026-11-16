<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "ownership_history";

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

$canManage = in_array($currentRole, ['Admin', 'Police'], true);
$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);

$viewScope = $_GET["scope"] ?? "all";
if (!$isAdminOrPolice) {
    $viewScope = "mine";
}

$whereParts = [];
$queryParams = [];

if ($viewScope === "mine") {
    $whereParts[] = "(v.SellerID = ? OR o.UserID = ?)";
    $queryParams[] = $currentUserId;
    $queryParams[] = $currentUserId;
}

$filterSearch = trim($_GET["search"] ?? "");
$filterReason = trim($_GET["reason"] ?? "");

if ($filterSearch !== "") {
    $whereParts[] = "(v.PlateNumber LIKE ? OR v.VIN LIKE ? OR o.FirstName LIKE ? OR o.LastName LIKE ? OR CONCAT(o.FirstName, ' ', o.LastName) LIKE ?)";
    $searchLike = "%" . $filterSearch . "%";
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
}

if ($filterReason !== "") {
    $whereParts[] = "oh.TransferReason = ?";
    $queryParams[] = $filterReason;
}

$whereClause = count($whereParts) > 0 ? " WHERE " . implode(" AND ", $whereParts) : "";

$perPage = 25;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ownership_history oh INNER JOIN vehicles v ON oh.VehicleID = v.VehicleID INNER JOIN owners o ON v.OwnerID = o.OwnerID {$whereClause}");
$countStmt->execute($queryParams);
$totalRecords = (int) $countStmt->fetchColumn();
$pagination = paginationMeta($totalRecords, $perPage);

$stmt = $pdo->prepare("
    SELECT oh.OwnershipID, oh.VehicleID, oh.OwnerID, oh.StartDate, oh.EndDate, oh.TransferReason,
           v.PlateNumber, v.VIN, v.Make, v.Model, v.VehicleYear,
           o.FirstName, o.LastName
    FROM ownership_history oh
    INNER JOIN vehicles v ON oh.VehicleID = v.VehicleID
    INNER JOIN owners o ON v.OwnerID = o.OwnerID
    {$whereClause}
    ORDER BY oh.StartDate DESC, oh.OwnershipID DESC
    LIMIT ? OFFSET ?
");

$paramIndex = 1;
foreach ($queryParams as $param) { $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR); }
$stmt->bindValue($paramIndex++, $pagination["perPage"], PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $pagination["offset"], PDO::PARAM_INT);
$stmt->execute();
$history = $stmt->fetchAll();

$bulkMessage = $_SESSION["bulk_message"] ?? null;
unset($_SESSION["bulk_message"]);

$pageTitle = "Ownership History";
$hasFilters = ($filterSearch !== "" || $filterReason !== "");
$pagination["extraQuery"] = ($filterSearch !== "" ? "&search=" . urlencode($filterSearch) : "") . ($filterReason !== "" ? "&reason=" . urlencode($filterReason) : "") . ($viewScope === "mine" ? "&scope=mine" : "");

require_once "../includes/head.php";
?>

<style>
    .filter-bar { display: grid; grid-template-columns: 2fr 1.5fr auto; gap: 12px; align-items: end; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .filter-field { display: flex; flex-direction: column; min-width: 0; }
    .filter-field label { margin-bottom: 6px; font-size: 12px; font-weight: 600; color: #374151; }
    .filter-field input, .filter-field select { width: 100%; padding: 9px 11px; font-size: 13px; background: #ffffff; color: #111827; border: 1px solid #d1d5db; border-radius: 7px; font-family: inherit; }
    .filter-actions { display: flex; gap: 8px; align-items: center; }
    .filter-actions .button { padding: 9px 14px; font-size: 13px; white-space: nowrap; }
    .filter-summary { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; font-size: 13px; color: #64748b; }
    .scope-toggle { display: inline-flex; background: #e5e7eb; border-radius: 8px; padding: 4px; margin-bottom: 20px; }
    .scope-toggle a { padding: 8px 20px; font-size: 14px; font-weight: 600; color: #4b5563; text-decoration: none; border-radius: 6px; transition: all 0.2s ease; }
    .scope-toggle a.active { background: #ffffff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    @media (max-width: 800px) { .filter-bar { grid-template-columns: 1fr; } }
</style>

<body>
<div class="layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>
    <main class="main-content">
        <?php include __DIR__ . "/../includes/header.php"; ?>
        <div class="content">

            <div class="page-title">
                <div>
                    <h1>Ownership History</h1>
                    <p class="page-subtitle">View the ownership records associated with vehicles in VISRS.</p>
                </div>
                <?php if ($canManage): ?>
                    <div><a href="add.php" class="button">Add Ownership Record</a></div>
                <?php endif; ?>
            </div>

            <?php if ($isAdminOrPolice): ?>
                <div class="scope-toggle">
                    <a href="?scope=all<?= $filterSearch !== "" ? '&search=' . urlencode($filterSearch) : '' ?>" class="<?= $viewScope === 'all' ? 'active' : '' ?>">All Records</a>
                    <a href="?scope=mine<?= $filterSearch !== "" ? '&search=' . urlencode($filterSearch) : '' ?>" class="<?= $viewScope === 'mine' ? 'active' : '' ?>">My Vehicles Only</a>
                </div>
            <?php endif; ?>

            <form method="GET" action="" class="filter-bar">
                <?php if ($viewScope === 'mine'): ?><input type="hidden" name="scope" value="mine"><?php endif; ?>
                <div class="filter-field">
                    <label for="search">Search History</label>
                    <input type="text" id="search" name="search" placeholder="Plate, VIN, or Owner name..." value="<?= htmlspecialchars($filterSearch) ?>">
                </div>
                <div class="filter-field">
                    <label for="reason">Transfer Reason</label>
                    <select id="reason" name="reason">
                        <option value="">All Reasons</option>
                        <option value="Original purchase" <?= $filterReason === "Original purchase" ? "selected" : "" ?>>Original purchase</option>
                        <option value="Sale of vehicle" <?= $filterReason === "Sale of vehicle" ? "selected" : "" ?>>Sale of vehicle</option>
                        <option value="Bought from previous owner" <?= $filterReason === "Bought from previous owner" ? "selected" : "" ?>>Bought from previous owner</option>
                        <option value="Bought" <?= $filterReason === "Bought" ? "selected" : "" ?>>Bought</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="button">Apply</button>
                    <?php if ($hasFilters): ?><a href="index.php<?= $viewScope === 'mine' ? '?scope=mine' : '' ?>" class="button button-secondary">Clear</a><?php endif; ?>
                </div>
            </form>

            <section class="card">
                <?php if (count($history) === 0): ?>
                    <div style="padding:30px; text-align:center;">
                        <h2>No Ownership History</h2>
                        <p style="margin-top:10px; color:#6b7280;">No ownership history records match your criteria.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="bulk_delete.php" id="bulkForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">
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
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:1px solid #e5e7eb; text-align:left;">
                                        <?php if ($canManage): ?><th style="padding:12px; width:36px;"><input type="checkbox" id="selectAll"></th><?php endif; ?>
                                        <th style="padding:12px;">Vehicle</th>
                                        <th style="padding:12px;">VIN</th>
                                        <th style="padding:12px;">Owner</th>
                                        <th style="padding:12px;">Start Date</th>
                                        <th style="padding:12px;">End Date</th>
                                        <th style="padding:12px;">Transfer Reason</th>
                                        <th style="padding:12px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $record): ?>
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <?php if ($canManage): ?><td style="padding:12px;"><input type="checkbox" name="selected_ids[]" value="<?= (int) $record["OwnershipID"] ?>" class="row-checkbox"></td><?php endif; ?>
                                            <td style="padding:12px;"><strong><?= htmlspecialchars($record["PlateNumber"]) ?></strong><br><small><?= htmlspecialchars($record["Make"] . " " . $record["Model"]) ?></small></td>
                                            <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($record["VIN"]) ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars($record["FirstName"] . " " . $record["LastName"]) ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars($record["StartDate"]) ?></td>
                                            <td style="padding:12px;"><?= $record["EndDate"] ? htmlspecialchars($record["EndDate"]) : "<span style='color:#166534; font-weight:600;'>Current Owner</span>" ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars($record["TransferReason"] ?? "—") ?></td>
                                            <td style="padding:12px; white-space:nowrap;">
                                                <a href="view.php?id=<?= (int) $record["OwnershipID"] ?>" class="button button-secondary" style="padding:8px 12px;">View</a>
                                                <?php if ($canManage): ?>
                                                    <a href="edit.php?id=<?= (int) $record["OwnershipID"] ?>" class="button" style="padding:8px 12px; margin-left:5px;">Edit</a>
                                                    <a href="delete.php?id=<?= (int) $record["OwnershipID"] ?>" class="button button-danger" style="padding:8px 12px; margin-left:5px;">Delete</a>
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
<?php require_once __DIR__ . "/../includes/footer.php"; ?>