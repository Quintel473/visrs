<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole(["Admin", "Seller"]);

$basePath = "../";
$pageTitle = "Edit Listing";
$activePage = "marketplace";

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;
$isAdmin = ($currentRole === 'Admin' || $currentRole === 'Police');

$listingID = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$listingID) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Listing & Verify Permission
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT vl.*, v.Make, v.Model, v.VehicleYear, v.PlateNumber 
    FROM vehicle_listings vl
    INNER JOIN vehicles v ON vl.VehicleID = v.VehicleID
    WHERE vl.ListingID = ?
    LIMIT 1
");
$stmt->execute([$listingID]);
$listing = $stmt->fetch();

if (!$listing) {
    die("Listing not found.");
}

// Security Check: Only Admin or the specific Seller can edit this
if (!$isAdmin && (int)$listing["SellerID"] !== $currentUserId) {
    http_response_code(403);
    die("Access Denied: You can only edit your own listings.");
}

$error = "";
$success = "";

/*
|--------------------------------------------------------------------------
| Handle Delete Image Action
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete_image"])) {
    $imgID = (int) $_GET["delete_image"];
    
    // Verify the image belongs to this vehicle
    $imgCheck = $pdo->prepare("SELECT ImagePath FROM vehicle_images WHERE ImageID = ? AND VehicleID = ?");
    $imgCheck->execute([$imgID, $listing["VehicleID"]]);
    $imgData = $imgCheck->fetch();
    
    if ($imgData) {
        // Delete physical file
        $filePath = __DIR__ . "/../" . $imgData["ImagePath"];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from DB
        $delImg = $pdo->prepare("DELETE FROM vehicle_images WHERE ImageID = ?");
        $delImg->execute([$imgID]);
        
        // If we deleted the primary image, make another one primary
        $checkPrimary = $pdo->prepare("SELECT COUNT(*) FROM vehicle_images WHERE VehicleID = ? AND IsPrimary = 1");
        $checkPrimary->execute([$listing["VehicleID"]]);
        if ((int)$checkPrimary->fetchColumn() === 0) {
            $firstImg = $pdo->prepare("SELECT ImageID FROM vehicle_images WHERE VehicleID = ? LIMIT 1");
            $firstImg->execute([$listing["VehicleID"]]);
            $firstID = $firstImg->fetchColumn();
            if ($firstID) {
                $pdo->prepare("UPDATE vehicle_images SET IsPrimary = 1 WHERE ImageID = ?")->execute([$firstID]);
            }
        }
        
        $success = "Image deleted successfully.";
    }
}

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireValidCSRF();

    $price = trim($_POST["price"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status = trim($_POST["status"] ?? "Available");

    if (!is_numeric($price) || (float)$price <= 0) {
        $error = "Please enter a valid price.";
    } elseif (!in_array($status, ["Available", "Reserved", "Sold", "Inactive"])) {
        $error = "Invalid status.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update Listing Details
            $updateStmt = $pdo->prepare("
                UPDATE vehicle_listings 
                SET Price = ?, Description = ?, Status = ?
                WHERE ListingID = ?
            ");
            $updateStmt->execute([
                number_format((float)$price, 2, ".", ""),
                $description !== "" ? $description : null,
                $status,
                $listingID
            ]);

            // 2. Handle New Image Uploads
            if (isset($_FILES["new_images"]) && $_FILES["new_images"]["error"][0] !== UPLOAD_ERR_NO_FILE) {
                
                $files = $_FILES["new_images"];
                $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
                $maximumFileSize = 5 * 1024 * 1024;

                $uploadDirectory = __DIR__ . "/uploads";
                if (!is_dir($uploadDirectory)) { mkdir($uploadDirectory, 0755, true); }

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                for ($i = 0; $i < count($files["name"]); $i++) {
                    if ($files["error"][$i] !== UPLOAD_ERR_OK) continue;
                    if ($files["size"][$i] > $maximumFileSize) continue;
                    
                    $mimeType = $finfo->file($files["tmp_name"][$i]);
                    if (!in_array($mimeType, $allowedTypes)) continue;

                    $extension = "jpg";
                    if ($mimeType === "image/png") $extension = "png";
                    elseif ($mimeType === "image/webp") $extension = "webp";

                    $randomName = bin2hex(random_bytes(16));
                    $fileName = "vehicle_" . (int) $listing["VehicleID"] . "_" . $randomName . "." . $extension;
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

                    if (move_uploaded_file($files["tmp_name"][$i], $destination)) {
                        $relativePath = "marketplace/uploads/" . $fileName;
                        
                        // Check if this is the very first image
                        $checkAny = $pdo->prepare("SELECT COUNT(*) FROM vehicle_images WHERE VehicleID = ?");
                        $checkAny->execute([$listing["VehicleID"]]);
                        $isPrimary = ((int)$checkAny->fetchColumn() === 0) ? 1 : 0;

                        $imgStmt = $pdo->prepare("INSERT INTO vehicle_images (VehicleID, ImagePath, IsPrimary) VALUES (?, ?, ?)");
                        $imgStmt->execute([$listing["VehicleID"], $relativePath, $isPrimary]);
                    }
                }
            }

            $pdo->commit();
            $success = "Listing updated successfully.";
            
            // Refresh data
            $listing["Price"] = $price;
            $listing["Description"] = $description;
            $listing["Status"] = $status;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = "Unable to update the listing. Please try again.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch Current Images
|--------------------------------------------------------------------------
*/

$imgStmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE VehicleID = ? ORDER BY IsPrimary DESC, ImageID ASC");
$imgStmt->execute([$listing["VehicleID"]]);
$currentImages = $imgStmt->fetchAll();

require_once "../includes/head.php";
?>

<style>
    /* Removed max-width so it stretches full width like the marketplace */
    .edit-layout {
        width: 100%;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
        font-size: 14px;
    }
    
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        box-sizing: border-box;
        background: #ffffff;
    }
    
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    /* Image gallery that stretches across the full width */
    .image-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        margin-top: 10px;
    }
    
    .image-card {
        position: relative;
        width: 100%;
        aspect-ratio: 4/3;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    
    .image-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-card .delete-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        background: rgba(153, 27, 27, 0.9);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 20px;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        transition: background 0.2s ease;
    }
    
    .image-card .delete-btn:hover {
        background: #7f1d1d;
    }
    
    .primary-badge {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background: #111827;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 5px;
        letter-spacing: 0.5px;
    }
    
    @media (max-width: 600px) {
        .form-row { grid-template-columns: 1fr; }
    }
</style>

<body>
<div class="layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>
    
    <main class="main-content">
        <?php require_once __DIR__ . "/../includes/header.php"; ?>
        
        <main class="content">
            <div class="edit-layout">
                <h1 class="page-title" style="margin-bottom:5px;">Edit Listing</h1>
                <p class="page-subtitle" style="margin-bottom:25px;">Update the details and photos for your marketplace listing.</p>

                <div class="card">
                    <h2 style="margin-top:0; margin-bottom:5px;">
                        <?= htmlspecialchars($listing["VehicleYear"] . " " . $listing["Make"] . " " . $listing["Model"]) ?>
                    </h2>
                    <p style="color:#64748b; margin-bottom:30px; font-size:15px;">
                        Plate: <strong><?= htmlspecialchars($listing["PlateNumber"]) ?></strong>
                    </p>

                    <?php if ($error !== ""): ?>
                        <div style="background:#fee2e2; color:#991b1b; padding:14px; border-radius:8px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success !== ""): ?>
                        <div class="auto-dismiss" style="background:#dcfce7; color:#166534; padding:14px; border-radius:8px; margin-bottom:20px;"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Selling Price *</label>
                                <input type="number" name="price" value="<?= htmlspecialchars($listing["Price"]) ?>" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Listing Status *</label>
                                <select name="status" required>
                                    <option value="Available" <?= $listing["Status"] === "Available" ? "selected" : "" ?>>Available</option>
                                    <option value="Reserved" <?= $listing["Status"] === "Reserved" ? "selected" : "" ?>>Reserved</option>
                                    <option value="Sold" <?= $listing["Status"] === "Sold" ? "selected" : "" ?>>Sold</option>
                                    <option value="Inactive" <?= $listing["Status"] === "Inactive" ? "selected" : "" ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:30px;">
                            <label>Description</label>
                            <textarea name="description" rows="5"><?= htmlspecialchars($listing["Description"] ?? "") ?></textarea>
                        </div>

                        <!-- CURRENT IMAGES -->
                        <div class="form-group" style="margin-bottom:30px;">
                            <label>Current Photos</label>
                            
                            <?php if (count($currentImages) > 0): ?>
                                <div class="image-gallery-grid">
                                    <?php foreach ($currentImages as $img): ?>
                                        <div class="image-card">
                                            <img src="/visrs/<?= htmlspecialchars($img["ImagePath"]) ?>" alt="Vehicle Photo">
                                            
                                            <?php if ($img["IsPrimary"]): ?>
                                                <span class="primary-badge">PRIMARY</span>
                                            <?php endif; ?>

                                            <a href="?id=<?= $listingID ?>&delete_image=<?= $img["ImageID"] ?>" 
                                               onclick="return confirm('Are you sure you want to delete this photo?');"
                                               class="delete-btn"
                                               title="Delete Photo">
                                                &times;
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color:#64748b; font-size:14px; padding:15px; background:#f8fafc; border-radius:8px;">No photos currently attached to this listing.</p>
                            <?php endif; ?>
                        </div>

                        <!-- ADD NEW IMAGES -->
                        <div class="form-group" style="margin-bottom:35px;">
                            <label>Add New Photos</label>
                            <input type="file" name="new_images[]" accept="image/jpeg,image/png,image/webp" multiple style="padding:15px; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; cursor:pointer;">
                            <small style="display:block; margin-top:8px; color:#64748b;">You can select multiple images at once to add them to this listing.</small>
                        </div>

                        <div style="display:flex; gap:15px; flex-wrap:wrap; border-top:1px solid #e5e7eb; padding-top:25px;">
                            <button type="submit" class="button" style="padding: 12px 24px;">Save Changes</button>
                            <a href="view.php?vehicle_id=<?= (int)$listing["VehicleID"] ?>" class="button button-secondary" style="padding: 12px 24px;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </main>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>