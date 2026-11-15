<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$currentRole = $_SESSION["Role"] ?? 'User';
$currentUserId = $_SESSION["UserID"] ?? 0;

// Allow Admin, Police, and Sellers
if (!in_array($currentRole, ['Admin', 'Police', 'Seller'])) {
    http_response_code(403);
    die("Access Denied: You do not have permission to delete listings.");
}

$basePath = "../";
$listingID = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($listingID <= 0) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Get the listing details
    $stmt = $pdo->prepare("SELECT VehicleID, SellerID FROM vehicle_listings WHERE ListingID = ? LIMIT 1");
    $stmt->execute([$listingID]);
    $listing = $stmt->fetch();

    if ($listing) {
        // SECURITY CHECK: If they are a Seller, they can only delete their own listing
        if ($currentRole === 'Seller' && (int)$listing["SellerID"] !== $currentUserId) {
            throw new Exception("You can only delete your own listings.");
        }

        $vehicleID = $listing["VehicleID"];

        // 2. Delete associated messages
        $msgStmt = $pdo->prepare("DELETE FROM vehicle_messages WHERE ListingID = ?");
        $msgStmt->execute([$listingID]);

        // 3. Delete associated interests
        $intStmt = $pdo->prepare("DELETE FROM vehicle_interests WHERE ListingID = ?");
        $intStmt->execute([$listingID]);

        // 4. Delete the listing itself
        $delStmt = $pdo->prepare("DELETE FROM vehicle_listings WHERE ListingID = ?");
        $delStmt->execute([$listingID]);

        // 5. Log the action
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs (UserID, Action, TableAffected, RecordID, IPAddress)
            VALUES (?, ?, ?, ?, ?)
        ");
        $auditStmt->execute([
            $currentUserId,
            "Removed vehicle listing",
            "vehicle_listings",
            $listingID,
            $_SERVER["REMOTE_ADDR"] ?? null
        ]);
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // If a Seller tries to hack the URL, they get this error
    die("Error deleting listing: " . htmlspecialchars($e->getMessage()));
}

// Redirect back to the marketplace
header("Location: index.php");
exit;