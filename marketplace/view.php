<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Vehicle Details";
$activePage = "marketplace";


/*
|--------------------------------------------------------------------------
| Get Vehicle ID
|--------------------------------------------------------------------------
*/

$vehicleId = filter_input(
    INPUT_GET,
    "vehicle_id",
    FILTER_VALIDATE_INT
);

if (!$vehicleId || $vehicleId <= 0) {

    http_response_code(404);

    echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Vehicle Not Found - VISRS</title>
            <link rel='stylesheet' href='/visrs/css/style.css'>
        </head>
        <body>

            <div style='
                max-width:700px;
                margin:80px auto;
                padding:30px;
                text-align:center;
            '>

                <h1>Vehicle Not Found</h1>

                <p style='color:#64748b;'>
                    The vehicle listing you requested could not be found.
                </p>

                <a
                    href='/visrs/marketplace/'
                    class='button'
                >
                    Back to Marketplace
                </a>

            </div>

        </body>
        </html>
    ";

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Marketplace Listing
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        vl.ListingID,
        vl.VehicleID,
        vl.SellerID,
        vl.Price,
        vl.Description,
        vl.Status AS ListingStatus,
        vl.CreatedAt AS ListingCreatedAt,
        vl.UpdatedAt AS ListingUpdatedAt,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.EngineNumber,
        v.RegistrationDate,
        v.Status AS VehicleStatus,

        u.FirstName AS SellerFirstName,
        u.LastName AS SellerLastName,
        u.Email AS SellerEmail

    FROM vehicle_listings vl

    INNER JOIN vehicles v
        ON vl.VehicleID = v.VehicleID

    INNER JOIN users u
        ON vl.SellerID = u.UserID

    WHERE vl.VehicleID = ?

    LIMIT 1
";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    $vehicleId
]);

$listing = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Listing Not Found
|--------------------------------------------------------------------------
*/

if (!$listing) {

    http_response_code(404);

    echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Vehicle Not Found - VISRS</title>
            <link rel='stylesheet' href='/visrs/css/style.css'>
        </head>
        <body>

            <div style='
                max-width:700px;
                margin:80px auto;
                padding:30px;
                text-align:center;
            '>

                <h1>Vehicle Not Found</h1>

                <p style='color:#64748b;'>
                    This vehicle does not currently have a marketplace listing.
                </p>

                <a
                    href='/visrs/marketplace/'
                    class='button'
                >
                    Back to Marketplace
                </a>

            </div>

        </body>
        </html>
    ";

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Vehicle Images
|--------------------------------------------------------------------------
*/

$imageSql = "
    SELECT
        ImageID,
        ImagePath,
        IsPrimary,
        CreatedAt

    FROM vehicle_images

    WHERE VehicleID = ?

    ORDER BY
        IsPrimary DESC,
        ImageID ASC
";


$imageStmt = $pdo->prepare($imageSql);

$imageStmt->execute([
    $vehicleId
]);

$images = $imageStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Determine Primary Image
|--------------------------------------------------------------------------
*/

$primaryImage = null;

foreach ($images as $image) {

    if ((int) $image["IsPrimary"] === 1) {

        $primaryImage = $image;

        break;
    }
}


/*
|--------------------------------------------------------------------------
| Fallback To First Image
|--------------------------------------------------------------------------
*/

if (!$primaryImage && count($images) > 0) {

    $primaryImage = $images[0];
}


/*
|--------------------------------------------------------------------------
| Get Accident Information
|--------------------------------------------------------------------------
*/

$accidentSql = "
    SELECT
        AccidentID,
        AccidentDate,
        Location,
        Description,
        DamageLevel,
        ReportNumber

    FROM accidents

    WHERE VehicleID = ?

    ORDER BY AccidentDate DESC
";


$accidentStmt = $pdo->prepare($accidentSql);

$accidentStmt->execute([
    $vehicleId
]);

$accidents = $accidentStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Insurance Information
|--------------------------------------------------------------------------
*/

$insuranceSql = "
    SELECT
        InsuranceID,
        ProviderName,
        PolicyNumber,
        CoverageType,
        StartDate,
        ExpiryDate,
        Status

    FROM insurance

    WHERE VehicleID = ?

    ORDER BY StartDate DESC
";


$insuranceStmt = $pdo->prepare($insuranceSql);

$insuranceStmt->execute([
    $vehicleId
]);

$insuranceRecords = $insuranceStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Determine Whether Current User Is Seller
|--------------------------------------------------------------------------
*/

$currentUserId = currentUserId();

$isSeller =
    $currentUserId &&
    (int) $currentUserId === (int) $listing["SellerID"];


/*
|--------------------------------------------------------------------------
| Status CSS Class
|--------------------------------------------------------------------------
*/

$listingStatusClass =
    strtolower(
        $listing["ListingStatus"]
    );


/*
|--------------------------------------------------------------------------
| Vehicle Status CSS Class
|--------------------------------------------------------------------------
*/

$vehicleStatusClass =
    strtolower(
        $listing["VehicleStatus"]
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e(
            $listing["Make"]
            . " "
            . $listing["Model"]
        ) ?>
        - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        */

        .vehicle-page-header {

            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:20px;

            margin-bottom:25px;

            flex-wrap:wrap;

        }


        .vehicle-page-header h1 {

            margin:0;

        }


        .vehicle-page-header p {

            margin:6px 0 0;

            color:#64748b;

        }


        .vehicle-page-actions {

            display:flex;

            gap:10px;

            flex-wrap:wrap;

        }


        /*
        |--------------------------------------------------------------------------
        | Main Vehicle Layout
        |--------------------------------------------------------------------------
        */

        .vehicle-detail-grid {

            display:grid;

            grid-template-columns:
                minmax(0, 1.25fr)
                minmax(320px, 0.75fr);

            gap:25px;

            margin-bottom:25px;

        }


        /*
        |--------------------------------------------------------------------------
        | Cards
        |--------------------------------------------------------------------------
        */

        .vehicle-detail-card {

            background:#ffffff;

            border:1px solid #e5e7eb;

            border-radius:12px;

            box-shadow:
                0 2px 7px
                rgba(0, 0, 0, 0.06);

            overflow:hidden;

        }


        .vehicle-detail-card-content {

            padding:22px;

        }


        /*
        |--------------------------------------------------------------------------
        | Gallery
        |--------------------------------------------------------------------------
        */

        .vehicle-gallery {

            background:#f1f5f9;

        }


        .vehicle-primary-image {

            position:relative;

            width:100%;

            height:430px;

            background:#e2e8f0;

        }


        .vehicle-primary-image img {

            display:block;

            width:100%;

            height:100%;

            object-fit:cover;

        }


        .vehicle-no-image {

            width:100%;

            height:100%;

            display:flex;

            flex-direction:column;

            align-items:center;

            justify-content:center;

            color:#94a3b8;

            gap:10px;

        }


        .vehicle-no-image-icon {

            font-size:64px;

        }


        .vehicle-image-count {

            position:absolute;

            bottom:12px;

            right:12px;

            padding:7px 11px;

            border-radius:20px;

            background:rgba(17,24,39,0.80);

            color:#ffffff;

            font-size:12px;

            font-weight:600;

        }


        .vehicle-thumbnails {

            display:flex;

            gap:10px;

            padding:14px;

            overflow-x:auto;

            background:#ffffff;

        }


        .vehicle-thumbnail {

            flex:0 0 82px;

            width:82px;

            height:62px;

            padding:0;

            border:2px solid transparent;

            border-radius:7px;

            overflow:hidden;

            background:#f1f5f9;

            cursor:pointer;

        }


        .vehicle-thumbnail.active {

            border-color:#111827;

        }


        .vehicle-thumbnail img {

            display:block;

            width:100%;

            height:100%;

            object-fit:cover;

        }


        /*
        |--------------------------------------------------------------------------
        | Listing Information
        |--------------------------------------------------------------------------
        */

        .listing-main-info {

            padding:25px;

        }


        .listing-status-badge {

            display:inline-block;

            margin-bottom:12px;

            padding:6px 11px;

            border-radius:20px;

            background:#166534;

            color:#ffffff;

            font-size:11px;

            font-weight:700;

        }


        .listing-status-badge.reserved {

            background:#92400e;

        }


        .listing-status-badge.sold {

            background:#991b1b;

        }


        .listing-status-badge.inactive {

            background:#475569;

        }


        .listing-main-info h1 {

            margin:0 0 5px;

            color:#111827;

            font-size:29px;

        }


        .vehicle-subtitle {

            margin-bottom:18px;

            color:#64748b;

            font-size:14px;

        }


        .vehicle-price {

            margin-bottom:22px;

            color:#111827;

            font-size:30px;

            font-weight:800;

        }


        /*
        |--------------------------------------------------------------------------
        | Action Buttons
        |--------------------------------------------------------------------------
        */

        .vehicle-action-buttons {

            display:flex;

            flex-direction:column;

            gap:10px;

            margin-bottom:22px;

        }


        .vehicle-action-button {

            display:block;

            width:100%;

            box-sizing:border-box;

            padding:12px 16px;

            border:0;

            border-radius:7px;

            background:#111827;

            color:#ffffff;

            text-align:center;

            text-decoration:none;

            font-size:14px;

            font-weight:700;

            cursor:pointer;

        }


        .vehicle-action-button:hover {

            opacity:0.9;

        }


        .vehicle-action-button.secondary {

            background:#ffffff;

            color:#111827;

            border:1px solid #d1d5db;

        }


        .vehicle-action-button.secondary:hover {

            background:#f8fafc;

        }


        /*
        |--------------------------------------------------------------------------
        | Vehicle Specification Grid
        |--------------------------------------------------------------------------
        */

        .vehicle-spec-grid {

            display:grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            border-top:1px solid #e5e7eb;

            border-left:1px solid #e5e7eb;

        }


        .vehicle-spec {

            padding:14px;

            border-right:1px solid #e5e7eb;

            border-bottom:1px solid #e5e7eb;

        }


        .vehicle-spec-label {

            margin-bottom:4px;

            color:#64748b;

            font-size:11px;

            font-weight:600;

            text-transform:uppercase;

            letter-spacing:0.03em;

        }


        .vehicle-spec-value {

            color:#111827;

            font-size:14px;

            font-weight:600;

            word-break:break-word;

        }


        /*
        |--------------------------------------------------------------------------
        | Section Headers
        |--------------------------------------------------------------------------
        */

        .vehicle-section {

            margin-bottom:25px;

        }


        .vehicle-section:last-child {

            margin-bottom:0;

        }


        .vehicle-section-header {

            display:flex;

            justify-content:space-between;

            align-items:center;

            gap:15px;

            margin-bottom:15px;

        }


        .vehicle-section-header h2 {

            margin:0;

            font-size:20px;

        }


        .vehicle-section-count {

            color:#64748b;

            font-size:13px;

        }


        /*
        |--------------------------------------------------------------------------
        | Description
        |--------------------------------------------------------------------------
        */

        .vehicle-description {

            color:#475569;

            font-size:14px;

            line-height:1.7;

            white-space:pre-line;

        }


        /*
        |--------------------------------------------------------------------------
        | Seller Card
        |--------------------------------------------------------------------------
        */

        .seller-card {

            display:flex;

            align-items:center;

            gap:14px;

        }


        .seller-avatar {

            width:48px;

            height:48px;

            display:flex;

            align-items:center;

            justify-content:center;

            flex:0 0 48px;

            border-radius:50%;

            background:#111827;

            color:#ffffff;

            font-size:18px;

            font-weight:700;

        }


        .seller-name {

            margin-bottom:3px;

            color:#111827;

            font-size:15px;

            font-weight:700;

        }


        .seller-role {

            color:#64748b;

            font-size:12px;

        }


        /*
        |--------------------------------------------------------------------------
        | Record Tables
        |--------------------------------------------------------------------------
        */

        .vehicle-record-table-wrapper {

            overflow-x:auto;

        }


        .vehicle-record-table {

            width:100%;

            border-collapse:collapse;

            font-size:13px;

        }


        .vehicle-record-table th {

            padding:11px 12px;

            border-bottom:1px solid #e5e7eb;

            background:#f8fafc;

            color:#475569;

            text-align:left;

            font-size:11px;

            font-weight:700;

            text-transform:uppercase;

        }


        .vehicle-record-table td {

            padding:12px;

            border-bottom:1px solid #f1f5f9;

            color:#475569;

            vertical-align:top;

        }


        .vehicle-record-table tr:last-child td {

            border-bottom:0;

        }


        /*
        |--------------------------------------------------------------------------
        | Status Labels
        |--------------------------------------------------------------------------
        */

        .record-status {

            display:inline-block;

            padding:4px 8px;

            border-radius:5px;

            background:#f1f5f9;

            color:#475569;

            font-size:11px;

            font-weight:600;

        }


        .record-status.active {

            background:#dcfce7;

            color:#166534;

        }


        .record-status.expired {

            background:#fee2e2;

            color:#991b1b;

        }


        .record-status.cancelled {

            background:#f1f5f9;

            color:#475569;

        }


        .damage-level {

            display:inline-block;

            padding:4px 8px;

            border-radius:5px;

            font-size:11px;

            font-weight:600;

        }


        .damage-level.minor {

            background:#dcfce7;

            color:#166534;

        }


        .damage-level.moderate {

            background:#fef3c7;

            color:#92400e;

        }


        .damage-level.major {

            background:#ffedd5;

            color:#9a3412;

        }


        .damage-level.severe {

            background:#fee2e2;

            color:#991b1b;

        }


        /*
        |--------------------------------------------------------------------------
        | Empty Record
        |--------------------------------------------------------------------------
        */

        .vehicle-empty-record {

            padding:25px;

            text-align:center;

            color:#64748b;

            font-size:13px;

        }


        /*
        |--------------------------------------------------------------------------
        | Back Link
        |--------------------------------------------------------------------------
        */

        .back-marketplace {

            display:inline-block;

            margin-bottom:18px;

            color:#475569;

            text-decoration:none;

            font-size:13px;

            font-weight:600;

        }


        .back-marketplace:hover {

            color:#111827;

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width:900px) {

            .vehicle-detail-grid {

                grid-template-columns:1fr;

            }


            .vehicle-primary-image {

                height:350px;

            }

        }


        @media (max-width:600px) {

            .vehicle-primary-image {

                height:270px;

            }


            .listing-main-info {

                padding:18px;

            }


            .listing-main-info h1 {

                font-size:24px;

            }


            .vehicle-price {

                font-size:26px;

            }


            .vehicle-spec-grid {

                grid-template-columns:1fr;

            }

        }

    </style>

</head>


<body>


<div class="layout">


    <?php

    require_once __DIR__ .
        "/../includes/sidebar.php";

    ?>


    <main class="main-content">


        <?php

        require_once __DIR__ .
            "/../includes/header.php";

        ?>


        <main class="content">


            <!-- =================================================
                 BACK TO MARKETPLACE
                 ================================================= -->

            <a
                href="/visrs/marketplace/"
                class="back-marketplace"
            >

                ← Back to Marketplace

            </a>


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="vehicle-page-header">

                <div>

                    <h1>
                        Vehicle Details
                    </h1>

                    <p>
                        Complete marketplace information for this vehicle.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 MAIN VEHICLE AREA
                 ================================================= -->

            <div class="vehicle-detail-grid">


                <!-- =================================================
                     LEFT - IMAGE GALLERY
                     ================================================= -->

                <div class="vehicle-detail-card vehicle-gallery">


                    <div
                        class="vehicle-primary-image"
                        id="vehiclePrimaryImage"
                    >

                        <?php if ($primaryImage): ?>

                            <img
                                src="/visrs/<?= e(
                                    $primaryImage["ImagePath"]
                                ) ?>"
                                alt="<?= e(
                                    $listing["Make"]
                                    . " "
                                    . $listing["Model"]
                                ) ?>"
                                id="primaryVehicleImage"
                            >

                        <?php else: ?>

                            <div class="vehicle-no-image">

                                <div class="vehicle-no-image-icon">
                                    🚗
                                </div>

                                <div>
                                    No photo available
                                </div>

                            </div>

                        <?php endif; ?>


                        <?php if (count($images) > 0): ?>

                            <div class="vehicle-image-count">

                                <?= count($images) ?>

                                <?= count($images) === 1
                                    ? "Photo"
                                    : "Photos"
                                ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- THUMBNAILS -->

                    <?php if (count($images) > 1): ?>

                        <div class="vehicle-thumbnails">

                            <?php foreach (
                                $images as $index => $image
                            ): ?>

                                <button
                                    type="button"
                                    class="vehicle-thumbnail <?= (
                                        $primaryImage &&
                                        $image["ImageID"] ===
                                        $primaryImage["ImageID"]
                                    )
                                        ? "active"
                                        : ""
                                    ?>"
                                    data-image="<?= e(
                                        "/visrs/"
                                        . $image["ImagePath"]
                                    ) ?>"
                                    aria-label="View vehicle photo <?= $index + 1 ?>"
                                >

                                    <img
                                        src="/visrs/<?= e(
                                            $image["ImagePath"]
                                        ) ?>"
                                        alt="Vehicle photo <?= $index + 1 ?>"
                                    >

                                </button>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                </div>


                <!-- =================================================
                     RIGHT - LISTING INFORMATION
                     ================================================= -->

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="listing-main-info"
                    >


                        <!-- STATUS -->

                        <span
                            class="listing-status-badge <?= e(
                                $listingStatusClass
                            ) ?>"
                        >

                            <?= e(
                                $listing["ListingStatus"]
                            ) ?>

                        </span>


                        <!-- TITLE -->

                        <h1>

                            <?= e(
                                $listing["Make"]
                                . " "
                                . $listing["Model"]
                            ) ?>

                        </h1>


                        <div
                            class="vehicle-subtitle"
                        >

                            <?= e(
                                $listing["VehicleYear"]
                            ) ?>

                            <?php if (
                                !empty(
                                    $listing["Color"]
                                )
                            ): ?>

                                •
                                <?= e(
                                    $listing["Color"]
                                ) ?>

                            <?php endif; ?>

                            <?php if (
                                !empty(
                                    $listing["VehicleType"]
                                )
                            ): ?>

                                •
                                <?= e(
                                    $listing["VehicleType"]
                                ) ?>

                            <?php endif; ?>

                        </div>


                        <!-- PRICE -->

                        <div
                            class="vehicle-price"
                        >

                            $
                            <?= number_format(
                                (float)
                                $listing["Price"],
                                2
                            ) ?>

                        </div>


                        <!-- ACTIONS -->

                        <?php if (!$isSeller): ?>

                            <div
                                class="vehicle-action-buttons"
                            >

                                <a
                                    href="/visrs/marketplace/interest.php?listing_id=<?= (int) $listing["ListingID"] ?>"
                                    class="vehicle-action-button"
                                >

                                    I'm Interested

                                </a>


                                <a
                                    href="/visrs/marketplace/message.php?listing_id=<?= (int) $listing["ListingID"] ?>"
                                    class="vehicle-action-button secondary"
                                >

                                    Message Seller

                                </a>

                            </div>

                        <?php else: ?>

                            <div
                                class="vehicle-action-buttons"
                            >

                                <a
                                    href="/visrs/marketplace/edit.php?listing_id=<?= (int) $listing["ListingID"] ?>"
                                    class="vehicle-action-button"
                                >

                                    Edit Listing

                                </a>

                            </div>

                        <?php endif; ?>


                        <!-- QUICK DETAILS -->

                        <div
                            class="vehicle-spec-grid"
                        >

                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Year
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["VehicleYear"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Make
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["Make"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Model
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["Model"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Color
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= displayValue(
                                        $listing["Color"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Vehicle Type
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= displayValue(
                                        $listing["VehicleType"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Plate Number
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["PlateNumber"]
                                    ) ?>

                                </div>

                            </div>


                        </div>


                    </div>

                </div>


            </div>


            <!-- =================================================
                 VEHICLE DESCRIPTION
                 ================================================= -->

            <div
                class="vehicle-section"
            >

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="vehicle-detail-card-content"
                    >

                        <div
                            class="vehicle-section-header"
                        >

                            <h2>
                                Listing Description
                            </h2>

                        </div>


                        <div
                            class="vehicle-description"
                        >

                            <?php

                            $description =
                                trim(
                                    $listing["Description"] ?? ""
                                );

                            if (
                                $description === ""
                            ) {

                                echo "No description provided for this vehicle.";

                            } else {

                                echo e(
                                    $description
                                );

                            }

                            ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 VEHICLE INFORMATION
                 ================================================= -->

            <div
                class="vehicle-section"
            >

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="vehicle-detail-card-content"
                    >

                        <div
                            class="vehicle-section-header"
                        >

                            <h2>
                                Vehicle Information
                            </h2>

                        </div>


                        <div
                            class="vehicle-spec-grid"
                        >


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    VIN
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["VIN"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Plate Number
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= e(
                                        $listing["PlateNumber"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Engine Number
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= displayValue(
                                        $listing["EngineNumber"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Registration Date
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= formatDate(
                                        $listing["RegistrationDate"]
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Vehicle Status
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <span
                                        class="record-status <?= e(
                                            $vehicleStatusClass
                                        ) ?>"
                                    >

                                        <?= e(
                                            $listing["VehicleStatus"]
                                        ) ?>

                                    </span>

                                </div>

                            </div>


                            <div
                                class="vehicle-spec"
                            >

                                <div
                                    class="vehicle-spec-label"
                                >
                                    Listing Date
                                </div>

                                <div
                                    class="vehicle-spec-value"
                                >

                                    <?= formatDate(
                                        $listing["ListingCreatedAt"]
                                    ) ?>

                                </div>

                            </div>


                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SELLER INFORMATION
                 ================================================= -->

            <div
                class="vehicle-section"
            >

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="vehicle-detail-card-content"
                    >

                        <div
                            class="vehicle-section-header"
                        >

                            <h2>
                                Seller Information
                            </h2>

                        </div>


                        <div
                            class="seller-card"
                        >

                            <div
                                class="seller-avatar"
                            >

                                <?= e(
                                    strtoupper(
                                        substr(
                                            $listing[
                                                "SellerFirstName"
                                            ],
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </div>


                            <div>

                                <div
                                    class="seller-name"
                                >

                                    <?= e(
                                        $listing[
                                            "SellerFirstName"
                                        ]
                                        . " "
                                        . $listing[
                                            "SellerLastName"
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="seller-role"
                                >

                                    Marketplace Seller

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 INSURANCE
                 ================================================= -->

            <div
                class="vehicle-section"
            >

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="vehicle-detail-card-content"
                    >

                        <div
                            class="vehicle-section-header"
                        >

                            <h2>
                                Insurance Information
                            </h2>

                            <span
                                class="vehicle-section-count"
                            >

                                <?= count(
                                    $insuranceRecords
                                ) ?>

                                <?= count(
                                    $insuranceRecords
                                ) === 1
                                    ? "record"
                                    : "records"
                                ?>

                            </span>

                        </div>


                        <?php if (
                            count(
                                $insuranceRecords
                            ) > 0
                        ): ?>

                            <div
                                class="vehicle-record-table-wrapper"
                            >

                                <table
                                    class="vehicle-record-table"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                Provider
                                            </th>

                                            <th>
                                                Policy Number
                                            </th>

                                            <th>
                                                Coverage
                                            </th>

                                            <th>
                                                Start Date
                                            </th>

                                            <th>
                                                Expiry Date
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            $insuranceRecords
                                            as $insurance
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <?= e(
                                                        $insurance[
                                                            "ProviderName"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= e(
                                                        $insurance[
                                                            "PolicyNumber"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= displayValue(
                                                        $insurance[
                                                            "CoverageType"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= formatDate(
                                                        $insurance[
                                                            "StartDate"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= formatDate(
                                                        $insurance[
                                                            "ExpiryDate"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <span
                                                        class="record-status <?= e(
                                                            strtolower(
                                                                $insurance[
                                                                    "Status"
                                                                ]
                                                            )
                                                        ) ?>"
                                                    >

                                                        <?= e(
                                                            $insurance[
                                                                "Status"
                                                            ]
                                                        ) ?>

                                                    </span>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div
                                class="vehicle-empty-record"
                            >

                                No insurance records are currently available
                                for this vehicle.

                            </div>

                        <?php endif; ?>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 ACCIDENT HISTORY
                 ================================================= -->

            <div
                class="vehicle-section"
            >

                <div
                    class="vehicle-detail-card"
                >

                    <div
                        class="vehicle-detail-card-content"
                    >

                        <div
                            class="vehicle-section-header"
                        >

                            <h2>
                                Accident History
                            </h2>

                            <span
                                class="vehicle-section-count"
                            >

                                <?= count(
                                    $accidents
                                ) ?>

                                <?= count(
                                    $accidents
                                ) === 1
                                    ? "record"
                                    : "records"
                                ?>

                            </span>

                        </div>


                        <?php if (
                            count(
                                $accidents
                            ) > 0
                        ): ?>

                            <div
                                class="vehicle-record-table-wrapper"
                            >

                                <table
                                    class="vehicle-record-table"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                Date
                                            </th>

                                            <th>
                                                Location
                                            </th>

                                            <th>
                                                Damage
                                            </th>

                                            <th>
                                                Report Number
                                            </th>

                                            <th>
                                                Description
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            $accidents
                                            as $accident
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <?= formatDate(
                                                        $accident[
                                                            "AccidentDate"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= displayValue(
                                                        $accident[
                                                            "Location"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <span
                                                        class="damage-level <?= e(
                                                            strtolower(
                                                                $accident[
                                                                    "DamageLevel"
                                                                ]
                                                            )
                                                        ) ?>"
                                                    >

                                                        <?= e(
                                                            $accident[
                                                                "DamageLevel"
                                                            ]
                                                        ) ?>

                                                    </span>

                                                </td>


                                                <td>

                                                    <?= displayValue(
                                                        $accident[
                                                            "ReportNumber"
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    $accidentDescription =
                                                        trim(
                                                            $accident[
                                                                "Description"
                                                            ] ?? ""
                                                        );

                                                    echo $accidentDescription !== ""
                                                        ? e(
                                                            $accidentDescription
                                                        )
                                                        : "—";

                                                    ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div
                                class="vehicle-empty-record"
                            >

                                No accident records are currently available
                                for this vehicle.

                            </div>

                        <?php endif; ?>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 BACK TO MARKETPLACE
                 ================================================= -->

            <div
                style="
                    margin-top:10px;
                    margin-bottom:20px;
                "
            >

                <a
                    href="/visrs/marketplace/"
                    class="button button-secondary"
                >

                    ← Back to Marketplace

                </a>

            </div>


        </main>


    </main>


</div>


<?php

require_once __DIR__ .
    "/../includes/footer.php";

?>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const primaryImage =
            document.getElementById(
                "primaryVehicleImage"
            );

        const thumbnails =
            document.querySelectorAll(
                ".vehicle-thumbnail"
            );


        if (
            !primaryImage ||
            thumbnails.length === 0
        ) {
            return;
        }


        thumbnails.forEach(
            function (thumbnail) {

                thumbnail.addEventListener(
                    "click",
                    function () {

                        const imagePath =
                            thumbnail.getAttribute(
                                "data-image"
                            );


                        if (!imagePath) {
                            return;
                        }


                        primaryImage.src =
                            imagePath;


                        thumbnails.forEach(
                            function (item) {

                                item.classList.remove(
                                    "active"
                                );

                            }
                        );


                        thumbnail.classList.add(
                            "active"
                        );

                    }
                );

            }
        );

    }
);

</script>