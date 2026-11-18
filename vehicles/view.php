<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$basePath = "../";
$activePage = "vehicles";

$pageTitle = "Vehicles";
$pageSubtitle = "View complete vehicle information";


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($vehicleID <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Load Vehicle and Owner
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        v.VehicleID,
        v.PlateNumber,
        v.VIN,
        v.OwnerID,
        v.SellerID,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Mileage,
        v.LastServiceDate,
        v.OilChangeIntervalKm,
        v.LastOilChangeDate,
        v.LastOilChangeMileage,
        v.Color,
        v.VehicleType,
        v.EngineNumber,
        v.RegistrationDate,
        v.Status,
        o.FirstName AS OwnerFirstName,
        o.LastName AS OwnerLastName,
        o.Address AS OwnerAddress,
        o.Phone AS OwnerPhone,
        o.Email AS OwnerEmail,
        o.UserID AS OwnerUserID
    FROM vehicles v
    INNER JOIN owners o
        ON v.OwnerID = o.OwnerID
    WHERE v.VehicleID = ?
    LIMIT 1
");

$stmt->execute([$vehicleID]);
$vehicle = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Vehicle Not Found
|--------------------------------------------------------------------------
*/

if (!$vehicle) {
    http_response_code(404);
    $pageTitle = "Vehicle Not Found";
    require_once "../includes/head.php";
    ?>
    <body>
    <div class="layout">
        <main class="main-content" style="margin-left:0; width:100%;">
            <div class="content" style="max-width:700px; margin:80px auto;">
                <div class="card">
                    <div style="background:#fee2e2; color:#991b1b; padding:14px; border-radius:8px; margin-bottom:20px;">
                        <strong>Vehicle Not Found</strong>
                    </div>
                    <h2>The requested vehicle could not be found.</h2>
                    <p>The vehicle may have been deleted or the requested vehicle ID is invalid.</p>
                    <a href="index.php" class="button">Return to Vehicles</a>
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
| Role-Based Access Control (Block URL Guessing)
|--------------------------------------------------------------------------
*/

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

$isAdminOrPolice = in_array($currentRole, ['Admin', 'Police']);

if (!$isAdminOrPolice) {
    if ($currentRole === 'Seller') {
        $isSelling = (int)$vehicle["SellerID"] === $currentUserId;
        $isOwning = (int)$vehicle["OwnerUserID"] === $currentUserId;
        
        if (!$isSelling && !$isOwning) {
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
                            <h2>You do not have permission to view this vehicle.</h2>
                            <p>You can only view vehicles that are assigned to your Seller account.</p>
                            <a href="index.php" class="button">Return to My Vehicles</a>
                        </div>
                    </div>
                </main>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    } elseif ((int)$vehicle["OwnerUserID"] !== $currentUserId) {
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
                        <h2>You do not have permission to view this vehicle.</h2>
                        <p>You can only view vehicles that are linked to your account.</p>
                        <a href="index.php" class="button">Return to My Vehicles</a>
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


/*
|--------------------------------------------------------------------------
| Check for Active Marketplace Listing
|--------------------------------------------------------------------------
*/

$listingStmt = $pdo->prepare("SELECT ListingID FROM vehicle_listings WHERE VehicleID = ? LIMIT 1");
$listingStmt->execute([$vehicleID]);
$activeListing = $listingStmt->fetch();


/*
|--------------------------------------------------------------------------
| Load Related Record Counts
|--------------------------------------------------------------------------
*/

$ownershipStmt = $pdo->prepare("SELECT COUNT(*) FROM ownership_history WHERE VehicleID = ?");
$ownershipStmt->execute([$vehicleID]);
$ownershipCount = (int) $ownershipStmt->fetchColumn();

$insuranceStmt = $pdo->prepare("SELECT COUNT(*) FROM insurance WHERE VehicleID = ?");
$insuranceStmt->execute([$vehicleID]);
$insuranceCount = (int) $insuranceStmt->fetchColumn();

$accidentStmt = $pdo->prepare("SELECT COUNT(*) FROM accidents WHERE VehicleID = ?");
$accidentStmt->execute([$vehicleID]);
$accidentCount = (int) $accidentStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Maintenance Calculations
|--------------------------------------------------------------------------
*/

$currentMileage = $vehicle["Mileage"] !== null ? (int) $vehicle["Mileage"] : null;
$oilInterval = $vehicle["OilChangeIntervalKm"] !== null ? (int) $vehicle["OilChangeIntervalKm"] : null;
$lastOilMileage = $vehicle["LastOilChangeMileage"] !== null ? (int) $vehicle["LastOilChangeMileage"] : null;

$mileageUntilOilChange = null;
if ($currentMileage !== null && $lastOilMileage !== null && $oilInterval !== null && $oilInterval > 0) {
    $nextOilChangeMileage = $lastOilMileage + $oilInterval;
    $mileageUntilOilChange = $nextOilChangeMileage - $currentMileage;
}

$oilChangeStatus = "Not enough information";
if ($mileageUntilOilChange !== null) {
    if ($mileageUntilOilChange <= 0) {
        $oilChangeStatus = "Oil change due";
    } elseif ($mileageUntilOilChange <= 1000) {
        $oilChangeStatus = "Oil change approaching";
    } else {
        $oilChangeStatus = "Oil change not yet due";
    }
}

$statusClass = "status-inactive";
if ($vehicle["Status"] === "Active") $statusClass = "status-active";
if ($vehicle["Status"] === "Stolen") $statusClass = "status-stolen";
if ($vehicle["Status"] === "Sold") $statusClass = "status-sold";

$pageTitle = $vehicle["PlateNumber"];

require_once "../includes/head.php";

?>

<style>
    .maintenance-section { margin-top:20px; }
    .maintenance-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px; margin-top:20px; }
    .maintenance-card { padding:18px; border:1px solid #e5e7eb; border-radius:8px; background:#ffffff; }
    .maintenance-card h3 { margin:0 0 8px; font-size:13px; color:#6b7280; font-weight:600; }
    .maintenance-card-value { font-size:20px; font-weight:700; color:#111827; word-break:break-word; }
    .maintenance-card-help { margin-top:6px; font-size:12px; color:#6b7280; line-height:1.4; }
    .maintenance-status { margin-top:20px; padding:14px 16px; border-radius:8px; background:#f9fafb; border:1px solid #e5e7eb; }
    .maintenance-status-title { margin:0 0 5px; font-size:13px; color:#6b7280; }
    .maintenance-status-value { font-size:16px; font-weight:700; color:#111827; }
    .maintenance-status-due { background:#fef2f2; border-color:#fecaca; }
    .maintenance-status-due .maintenance-status-value { color:#991b1b; }
    .maintenance-status-warning { background:#fffbeb; border-color:#fde68a; }
    .maintenance-status-warning .maintenance-status-value { color:#92400e; }
    .maintenance-status-ok { background:#f0fdf4; border-color:#bbf7d0; }
    .maintenance-status-ok .maintenance-status-value { color:#166534; }
    .maintenance-info-note { margin-top:18px; padding:14px 16px; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; color:#4b5563; font-size:12px; line-height:1.6; }
    .maintenance-info-note strong { color:#111827; }
    @media (max-width:700px) { .maintenance-grid { grid-template-columns:1fr; } }

    /* =========================================================
       DARK MODE OVERRIDES FOR VEHICLE DETAILS
       ========================================================= */
    
    [data-theme="dark"] .maintenance-card {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .maintenance-card h3,
    [data-theme="dark"] .maintenance-card-help {
        color: #94a3b8;
    }
    [data-theme="dark"] .maintenance-card-value {
        color: #f8fafc;
    }

    [data-theme="dark"] .maintenance-status {
        background: #0f172a;
        border-color: #334155;
    }
    [data-theme="dark"] .maintenance-status-title {
        color: #94a3b8;
    }
    [data-theme="dark"] .maintenance-status-value {
        color: #e2e8f0;
    }

    [data-theme="dark"] .maintenance-status-due {
        background: #450a0a;
        border-color: #7f1d1d;
    }
    [data-theme="dark"] .maintenance-status-due .maintenance-status-value {
        color: #fecaca;
    }

    [data-theme="dark"] .maintenance-status-warning {
        background: #78350f;
        border-color: #b45309;
    }
    [data-theme="dark"] .maintenance-status-warning .maintenance-status-value {
        color: #fcd34d;
    }

    [data-theme="dark"] .maintenance-status-ok {
        background: #064e3b;
        border-color: #047857;
    }
    [data-theme="dark"] .maintenance-status-ok .maintenance-status-value {
        color: #6ee7b7;
    }

    [data-theme="dark"] .maintenance-info-note {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
    [data-theme="dark"] .maintenance-info-note strong {
        color: #f8fafc;
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

            <!-- PROFILE HEADER -->
            <div class="profile-header">
                
                <?php 
                $carName = $vehicle["Make"] . " " . $vehicle["Model"];
                $cleanName = strtolower($carName);
                $cleanName = preg_replace('/[^a-z0-9]+/', '-', $cleanName); 
                $cleanName = trim($cleanName, '-');
                $imagePath = "../images/vehicles/" . $cleanName . ".jpg";
                ?>

                <div class="profile-avatar-large">
                    <?php if (file_exists(__DIR__ . "/" . $imagePath)): ?>
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($vehicle["Make"] . ' ' . $vehicle["Model"]) ?>" class="photo-lightbox-trigger" data-full-src="<?= $imagePath ?>" data-caption="<?= htmlspecialchars($vehicle["VehicleYear"] . ' ' . $vehicle["Make"] . ' ' . $vehicle["Model"]) ?>" style="cursor: zoom-in;">
                    <?php else: ?>
                        🚗
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <h1><?= htmlspecialchars($vehicle["Make"] . " " . $vehicle["Model"]) ?></h1>
                    <p class="profile-meta">
                        Year: <strong><?= htmlspecialchars($vehicle["VehicleYear"]) ?></strong> &bull; 
                        Plate: <strong><?= htmlspecialchars($vehicle["PlateNumber"]) ?></strong>
                    </p>

                    <div class="profile-tags">
                        <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($vehicle["Status"]) ?></span>
                        <?php if (!empty($vehicle["VehicleType"])): ?><span class="user-role user-role-default"><?= htmlspecialchars($vehicle["VehicleType"]) ?></span><?php endif; ?>
                        <?php if (!empty($vehicle["Color"])): ?><span class="user-role user-role-default">🎨 <?= htmlspecialchars($vehicle["Color"]) ?></span><?php endif; ?>
                    </div>

                    <div class="profile-quick-stats">
                        <div class="profile-stat"><strong><?= $ownershipCount ?></strong> Owners</div>
                        <div class="profile-stat"><strong><?= $insuranceCount ?></strong> Policies</div>
                        <div class="profile-stat"><strong><?= $accidentCount ?></strong> Accidents</div>
                    </div>
                </div>
            </div>


            <!-- VEHICLE INFORMATION -->
            <section class="card">
                <h2>Vehicle Information</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">VIN (Chassis Number)</span>
                        <div class="detail-value" style="font-family: monospace; font-size: 16px;"><?= htmlspecialchars($vehicle["VIN"]) ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Engine Number</span>
                        <div class="detail-value" style="font-family: monospace; font-size: 16px;"><?= htmlspecialchars($vehicle["EngineNumber"] ?: "Not provided") ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Registration Date</span>
                        <div class="detail-value"><?= $vehicle["RegistrationDate"] ? htmlspecialchars(formatDate($vehicle["RegistrationDate"])) : "Not provided" ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <div class="detail-value"><strong><?= htmlspecialchars($vehicle["Status"]) ?></strong></div>
                    </div>
                </div>
            </section>


            <!-- MAINTENANCE & SERVICE -->
            <section class="card maintenance-section">
                <h2>Maintenance & Service</h2>
                <p style="color:#6b7280; margin:0; font-size:13px;">Current mileage, service history, and oil-change information.</p>

                <div class="maintenance-grid">
                    <div class="maintenance-card">
                        <h3>Current Mileage</h3>
                        <div class="maintenance-card-value"><?php if ($currentMileage !== null): ?><?= number_format($currentMileage) ?> km<?php else: ?>Not provided<?php endif; ?></div>
                        <div class="maintenance-card-help">Current recorded vehicle mileage.</div>
                    </div>
                    <div class="maintenance-card">
                        <h3>Last Service</h3>
                        <div class="maintenance-card-value"><?= $vehicle["LastServiceDate"] ? htmlspecialchars(formatDate($vehicle["LastServiceDate"])) : "Not provided" ?></div>
                        <div class="maintenance-card-help">Most recent recorded service date.</div>
                    </div>
                    <div class="maintenance-card">
                        <h3>Oil Change Interval</h3>
                        <div class="maintenance-card-value"><?php if ($oilInterval !== null): ?><?= number_format($oilInterval) ?> km<?php else: ?>Not provided<?php endif; ?></div>
                        <div class="maintenance-card-help">Recommended distance between oil changes.</div>
                    </div>
                    <div class="maintenance-card">
                        <h3>Last Oil Change</h3>
                        <div class="maintenance-card-value"><?= $vehicle["LastOilChangeDate"] ? htmlspecialchars(formatDate($vehicle["LastOilChangeDate"])) : "Not provided" ?></div>
                        <div class="maintenance-card-help">Date of the most recent oil change.</div>
                    </div>
                    <div class="maintenance-card">
                        <h3>Mileage at Last Oil Change</h3>
                        <div class="maintenance-card-value"><?php if ($lastOilMileage !== null): ?><?= number_format($lastOilMileage) ?> km<?php else: ?>Not provided<?php endif; ?></div>
                        <div class="maintenance-card-help">Mileage recorded when the oil was last changed.</div>
                    </div>
                    <div class="maintenance-card">
                        <h3>Next Oil Change Mileage</h3>
                        <div class="maintenance-card-value"><?php if ($lastOilMileage !== null && $oilInterval !== null && $oilInterval > 0): ?><?= number_format($lastOilMileage + $oilInterval) ?> km<?php else: ?>Not available<?php endif; ?></div>
                        <div class="maintenance-card-help">Estimated mileage when the next oil change is due.</div>
                    </div>
                </div>

                <?php
                $oilStatusClass = "";
                if ($mileageUntilOilChange !== null) {
                    if ($mileageUntilOilChange <= 0) { $oilStatusClass = "maintenance-status-due"; } 
                    elseif ($mileageUntilOilChange <= 1000) { $oilStatusClass = "maintenance-status-warning"; } 
                    else { $oilStatusClass = "maintenance-status-ok"; }
                }
                ?>

                <div class="maintenance-status <?= $oilStatusClass ?>">
                    <div class="maintenance-status-title">Oil Change Status</div>
                    <div class="maintenance-status-value">
                        <?= htmlspecialchars($oilChangeStatus) ?>
                        <?php if ($mileageUntilOilChange !== null && $mileageUntilOilChange > 0): ?>— <?= number_format($mileageUntilOilChange) ?> km remaining<?php endif; ?>
                    </div>
                </div>
            </section>


            <!-- CURRENT OWNER -->
            <section class="card" style="margin-top:20px;">
                <h2>Current Owner</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <div class="detail-value"><strong><?= htmlspecialchars($vehicle["OwnerFirstName"] . " " . $vehicle["OwnerLastName"]) ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <div class="detail-value"><?= htmlspecialchars($vehicle["OwnerPhone"] ?: "Not provided") ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address</span>
                        <div class="detail-value"><?= htmlspecialchars($vehicle["OwnerAddress"] ?: "Not provided") ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <div class="detail-value"><?= htmlspecialchars($vehicle["OwnerEmail"] ?: "Not provided") ?></div>
                    </div>
                </div>
            </section>


            <!-- RELATED RECORDS & ACTIONS -->
            <section class="card" style="margin-top:20px;">
                <h2>Related Records</h2>
                <p style="color:#6b7280; margin-bottom:20px; font-size:13px;">Jump directly to records associated with this vehicle.</p>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom: 25px;">
                    <a href="../ownership_history/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>&scope=all" class="button button-secondary">Ownership History (<?= $ownershipCount ?>)</a>
                    <a href="../insurance/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>" class="button button-secondary">Insurance Records (<?= $insuranceCount ?>)</a>
                    <a href="../accidents/?vehicle_id=<?= (int) $vehicle["VehicleID"] ?>" class="button button-secondary">Accident Records (<?= $accidentCount ?>)</a>
                    
                    <?php if (hasRole(["Admin", "Police", "Seller"])): ?>
                        <a href="documents.php?id=<?= (int) $vehicle["VehicleID"] ?>" class="button button-secondary">📁 Documents</a>
                    <?php endif; ?>
                </div>

                <div class="button-group" style="border-top: 1px solid #e5e7eb; padding-top: 20px; display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="index.php" class="button button-secondary">← Back to Vehicles</a>
                    <button type="button" onclick="window.print()" class="button button-success">🖨 Print / Save as PDF</button>
                    
                    <?php if (canManageVehicles()): ?>
                        <a href="edit.php?id=<?= (int) $vehicle["VehicleID"] ?>" class="button">Edit Vehicle</a>
                    <?php endif; ?>

                    <!-- NEW: Edit Marketplace Listing Button -->
                    <?php if ($activeListing && ($isAdminOrPolice || $currentRole === 'Seller')): ?>
                        <a href="../marketplace/edit.php?id=<?= (int) $activeListing["ListingID"] ?>" class="button" style="background:#f59e0b; color:#fff;">
                            ✏️ Edit Marketplace Listing
                        </a>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </main>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>