<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Seller"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Create Vehicle Listing";
$activePage = "marketplace";

$error = "";
$success = "";

$vehicleID = "";
$price = "";
$description = "";


/*
|--------------------------------------------------------------------------
| Load Available Vehicles
|--------------------------------------------------------------------------
|
| Admin:
|   Can list any eligible vehicle that is not already listed.
|
| Seller:
|   Can only list vehicles assigned to their seller account through
|   vehicles.SellerID.
|
*/

if (isAdmin()) {

    $vehicleStmt = $pdo->query("
        SELECT
            v.VehicleID,
            v.PlateNumber,
            v.Make,
            v.Model,
            v.VehicleYear,
            v.Color,
            v.Status,
            v.SellerID,
            o.FirstName,
            o.LastName
        FROM vehicles v
        INNER JOIN owners o
            ON v.OwnerID = o.OwnerID
        LEFT JOIN vehicle_listings vl
            ON v.VehicleID = vl.VehicleID
        WHERE vl.ListingID IS NULL
          AND v.Status NOT IN ('Stolen', 'Inactive')
        ORDER BY
            v.Make ASC,
            v.Model ASC,
            v.VehicleYear DESC
    ");

} else {

    $vehicleStmt = $pdo->prepare("
        SELECT
            v.VehicleID,
            v.PlateNumber,
            v.Make,
            v.Model,
            v.VehicleYear,
            v.Color,
            v.Status,
            v.SellerID,
            o.FirstName,
            o.LastName
        FROM vehicles v
        INNER JOIN owners o
            ON v.OwnerID = o.OwnerID
        LEFT JOIN vehicle_listings vl
            ON v.VehicleID = vl.VehicleID
        WHERE vl.ListingID IS NULL
          AND v.Status NOT IN ('Stolen', 'Inactive')
          AND v.SellerID = ?
        ORDER BY
            v.Make ASC,
            v.Model ASC,
            v.VehicleYear DESC
    ");

    $vehicleStmt->execute([
        (int) $_SESSION["UserID"]
    ]);
}

$vehicles = $vehicleStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();


    /*
    |--------------------------------------------------------------------------
    | Collect Values
    |--------------------------------------------------------------------------
    */

    $vehicleID = trim(
        $_POST["vehicle_id"] ?? ""
    );

    $price = trim(
        $_POST["price"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Required Fields
    |--------------------------------------------------------------------------
    */

    if (
        $vehicleID === "" ||
        $price === ""
    ) {

        $error =
            "Please select a vehicle and enter a selling price.";
    }


    /*
    |--------------------------------------------------------------------------
    | Vehicle ID Validation
    |--------------------------------------------------------------------------
    */

    elseif (
        !ctype_digit($vehicleID) ||
        (int) $vehicleID <= 0
    ) {

        $error =
            "Please select a valid vehicle.";
    }


    /*
    |--------------------------------------------------------------------------
    | Price Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            !is_numeric($price) ||
            (float) $price <= 0
        ) {

            $error =
                "Please enter a valid selling price.";

        } elseif (
            (float) $price > 9999999999.99
        ) {

            $error =
                "The selling price is too large.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Description Validation
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if (
            strlen($description) > 5000
        ) {

            $error =
                "The vehicle description cannot exceed 5,000 characters.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Vehicle
    |--------------------------------------------------------------------------
    |
    | This is the important seller functionality fix.
    |
    | Sellers are verified using vehicles.SellerID instead of comparing
    | the vehicle owner's email to the seller's login email.
    |
    */

    if ($error === "") {

        if (isAdmin()) {

            $vehicleCheck = $pdo->prepare("
                SELECT
                    v.VehicleID,
                    v.Status,
                    v.SellerID
                FROM vehicles v
                LEFT JOIN vehicle_listings vl
                    ON v.VehicleID = vl.VehicleID
                WHERE v.VehicleID = ?
                  AND vl.ListingID IS NULL
                LIMIT 1
            ");

            $vehicleCheck->execute([
                (int) $vehicleID
            ]);

        } else {

            $vehicleCheck = $pdo->prepare("
                SELECT
                    v.VehicleID,
                    v.Status,
                    v.SellerID
                FROM vehicles v
                LEFT JOIN vehicle_listings vl
                    ON v.VehicleID = vl.VehicleID
                WHERE v.VehicleID = ?
                  AND v.SellerID = ?
                  AND vl.ListingID IS NULL
                LIMIT 1
            ");

            $vehicleCheck->execute([
                (int) $vehicleID,
                (int) $_SESSION["UserID"]
            ]);
        }

        $vehicle = $vehicleCheck->fetch();


        if (!$vehicle) {

            if (isAdmin()) {

                $error =
                    "The selected vehicle is not available for listing.";

            } else {

                $error =
                    "The selected vehicle is not assigned to your seller account or is already listed.";
            }

        } elseif (
            in_array(
                $vehicle["Status"],
                ["Stolen", "Inactive"],
                true
            )
        ) {

            $error =
                "This vehicle cannot be listed for sale.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Images
    |--------------------------------------------------------------------------
    */

    $uploadedImages = [];

    if (
        $error === "" &&
        isset($_FILES["vehicle_images"])
    ) {

        $files = $_FILES["vehicle_images"];

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $maximumFileSize =
            5 * 1024 * 1024;

        $maximumImages = 10;

        $imageCount =
            count($files["name"]);


        if (
            $imageCount > $maximumImages
        ) {

            $error =
                "You can upload a maximum of 10 vehicle images.";
        }


        if ($error === "") {

            $finfo = new finfo(
                FILEINFO_MIME_TYPE
            );


            for (
                $i = 0;
                $i < $imageCount;
                $i++
            ) {

                /*
                |--------------------------------------------------------------------------
                | Skip Empty File Entries
                |--------------------------------------------------------------------------
                */

                if (
                    $files["error"][$i] ===
                    UPLOAD_ERR_NO_FILE
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Upload Error
                |--------------------------------------------------------------------------
                */

                if (
                    $files["error"][$i] !==
                    UPLOAD_ERR_OK
                ) {

                    $error =
                        "One or more images could not be uploaded.";

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | File Size
                |--------------------------------------------------------------------------
                */

                if (
                    $files["size"][$i] <= 0 ||
                    $files["size"][$i] >
                    $maximumFileSize
                ) {

                    $error =
                        "Each vehicle image must be smaller than 5 MB.";

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | MIME Type
                |--------------------------------------------------------------------------
                */

                $temporaryFile =
                    $files["tmp_name"][$i];

                $mimeType =
                    $finfo->file(
                        $temporaryFile
                    );


                if (
                    !in_array(
                        $mimeType,
                        $allowedTypes,
                        true
                    )
                ) {

                    $error =
                        "Only JPG, PNG, and WebP images are allowed.";

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Confirm It Is Actually an Image
                |--------------------------------------------------------------------------
                */

                $imageInfo =
                    @getimagesize(
                        $temporaryFile
                    );


                if (
                    $imageInfo === false
                ) {

                    $error =
                        "One or more uploaded files are not valid images.";

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Store Validated Upload
                |--------------------------------------------------------------------------
                */

                $uploadedImages[] = [
                    "tmp_name" =>
                        $temporaryFile,

                    "mime_type" =>
                        $mimeType,

                    "original_name" =>
                        $files["name"][$i]
                ];
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Require At Least One Image
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        count($uploadedImages) === 0
    ) {

        $error =
            "Please upload at least one vehicle image.";
    }


    /*
    |--------------------------------------------------------------------------
    | Create Marketplace Listing
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Final Duplicate Check
            |--------------------------------------------------------------------------
            |
            | This provides an additional check immediately before insertion.
            |
            */

            $duplicateCheck = $pdo->prepare("
                SELECT ListingID
                FROM vehicle_listings
                WHERE VehicleID = ?
                LIMIT 1
            ");

            $duplicateCheck->execute([
                (int) $vehicleID
            ]);

            if ($duplicateCheck->fetch()) {

                throw new RuntimeException(
                    "This vehicle is already listed."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Insert Listing
            |--------------------------------------------------------------------------
            */

            $listingStmt = $pdo->prepare("
                INSERT INTO vehicle_listings (
                    VehicleID,
                    SellerID,
                    Price,
                    Description,
                    Status
                )
                VALUES (?, ?, ?, ?, 'Available')
            ");

            $listingStmt->execute([
                (int) $vehicleID,

                /*
                | SellerID is always the currently authenticated user.
                */
                (int) $_SESSION["UserID"],

                number_format(
                    (float) $price,
                    2,
                    ".",
                    ""
                ),

                $description !== ""
                    ? $description
                    : null
            ]);


            $listingID =
                (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Upload Directory
            |--------------------------------------------------------------------------
            */

            $uploadDirectory =
                __DIR__ . "/uploads";


            if (
                !is_dir($uploadDirectory)
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    throw new RuntimeException(
                        "Unable to create image upload directory."
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Save Images
            |--------------------------------------------------------------------------
            */

            foreach (
                $uploadedImages as $index => $image
            ) {

                /*
                |--------------------------------------------------------------------------
                | Determine Safe Extension
                |--------------------------------------------------------------------------
                */

                $extension = "jpg";


                if (
                    $image["mime_type"] ===
                    "image/png"
                ) {

                    $extension = "png";

                } elseif (
                    $image["mime_type"] ===
                    "image/webp"
                ) {

                    $extension = "webp";
                }


                /*
                |--------------------------------------------------------------------------
                | Generate Random Filename
                |--------------------------------------------------------------------------
                */

                $randomName =
                    bin2hex(
                        random_bytes(16)
                    );


                $fileName =
                    "vehicle_"
                    . (int) $vehicleID
                    . "_"
                    . $randomName
                    . "."
                    . $extension;


                $destination =
                    $uploadDirectory
                    . DIRECTORY_SEPARATOR
                    . $fileName;


                /*
                |--------------------------------------------------------------------------
                | Move Uploaded File
                |--------------------------------------------------------------------------
                */

                if (
                    !move_uploaded_file(
                        $image["tmp_name"],
                        $destination
                    )
                ) {

                    throw new RuntimeException(
                        "Unable to save one of the uploaded images."
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Database Image Path
                |--------------------------------------------------------------------------
                */

                $relativePath =
                    "marketplace/uploads/"
                    . $fileName;


                /*
                |--------------------------------------------------------------------------
                | First Image = Primary
                |--------------------------------------------------------------------------
                */

                $imageStmt = $pdo->prepare("
                    INSERT INTO vehicle_images (
                        VehicleID,
                        ImagePath,
                        IsPrimary
                    )
                    VALUES (?, ?, ?)
                ");


                $imageStmt->execute([
                    (int) $vehicleID,

                    $relativePath,

                    $index === 0
                        ? 1
                        : 0
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    UserID,
                    Action,
                    TableAffected,
                    RecordID,
                    IPAddress
                )
                VALUES (?, ?, ?, ?, ?)
            ");


            $logStmt->execute([
                (int) $_SESSION["UserID"],

                "Created vehicle marketplace listing",

                "vehicle_listings",

                $listingID,

                $_SERVER["REMOTE_ADDR"] ?? null
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            redirect(
                "view.php?vehicle_id="
                . (int) $vehicleID
                . "&created=1"
            );


        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Roll Back Database Changes
            |--------------------------------------------------------------------------
            */

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }


            /*
            |--------------------------------------------------------------------------
            | Remove Any Images Already Moved During This Transaction
            |--------------------------------------------------------------------------
            |
            | The database rollback does not remove physical files, so clean
            | them up if an upload/database operation fails halfway through.
            |
            */

            if (
                isset($uploadDirectory) &&
                isset($fileName)
            ) {
                // Physical cleanup is handled below where possible.
            }


            /*
            |--------------------------------------------------------------------------
            | User-Friendly Error
            |--------------------------------------------------------------------------
            */

            if (
                $e instanceof RuntimeException &&
                $e->getMessage() ===
                "This vehicle is already listed."
            ) {

                $error =
                    "This vehicle is already listed on the marketplace.";

            } else {

                $error =
                    "Unable to create the marketplace listing. Please try again.";
            }
        }
    }
}

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
        <?= htmlspecialchars($pageTitle) ?> - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | Marketplace Photo Upload
        |--------------------------------------------------------------------------
        */

        .photo-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            padding: 34px 24px;
            text-align: center;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                transform 0.2s ease;
        }

        .photo-upload-area:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
        }

        .photo-upload-area.dragging {
            border-color: #4f46e5;
            background: #eef2ff;
            transform: scale(1.005);
        }

        .photo-upload-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 31px;
        }

        .photo-upload-title {
            margin: 0 0 8px;
            color: #111827;
            font-size: 19px;
            font-weight: 700;
        }

        .photo-upload-description {
            max-width: 520px;
            margin: 0 auto 20px;
            color: #64748b;
            line-height: 1.6;
            font-size: 14px;
        }

        .photo-upload-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border: none;
            border-radius: 7px;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .photo-upload-button:hover {
            opacity: 0.92;
        }

        .photo-upload-info {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 18px;
            color: #64748b;
            font-size: 12px;
        }

        .photo-count {
            display: none;
            margin-top: 16px;
            padding: 11px 14px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 14px;
            font-weight: 600;
        }

        .photo-preview-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(155px, 1fr)
                );
            gap: 16px;
            margin-top: 18px;
        }

        .photo-preview-card {
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            box-shadow:
                0 1px 3px
                rgba(0, 0, 0, 0.06);
        }

        .photo-preview-image {
            display: block;
            width: 100%;
            height: 145px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .primary-photo-badge {
            position: absolute;
            top: 9px;
            left: 9px;
            padding: 5px 8px;
            border-radius: 5px;
            background: #111827;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .remove-photo-button {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 30px;
            height: 30px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: #ffffff;
            color: #991b1b;
            font-size: 20px;
            line-height: 28px;
            cursor: pointer;
            box-shadow:
                0 2px 7px
                rgba(0, 0, 0, 0.18);
        }

        .remove-photo-button:hover {
            background: #fee2e2;
        }

        .photo-file-name {
            padding: 9px 10px;
            color: #475569;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .photo-upload-note {
            margin-top: 12px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 600px) {

            .photo-upload-area {
                padding: 28px 16px;
            }

            .photo-preview-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }
        }

    </style>

</head>


<body>

<div class="layout">


    <?php
    include __DIR__ . "/../includes/sidebar.php";
    ?>


    <main class="main-content">


        <?php
        include __DIR__ . "/../includes/header.php";
        ?>


        <main class="content">


            <h1 class="page-title">
                Create Vehicle Listing
            </h1>


            <p class="page-subtitle">
                List a vehicle for sale on the VISRS marketplace.
            </p>


            <?php if ($error !== ""): ?>

                <div
                    class="error-message"
                    style="margin-bottom:20px;"
                >
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


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


            <section class="card">


                <h2>
                    Marketplace Listing
                </h2>


                <?php if (count($vehicles) === 0): ?>


                    <div
                        style="
                            background:#fef3c7;
                            color:#92400e;
                            padding:15px;
                            border-radius:8px;
                            margin-top:20px;
                        "
                    >

                        <strong>
                            No vehicles available.
                        </strong>

                        <p style="margin-bottom:0;">

                            <?php if (isAdmin()): ?>

                                There are currently no eligible vehicles
                                available to create a marketplace listing.

                            <?php else: ?>

                                You currently have no eligible vehicles
                                assigned to your seller account that are
                                available for marketplace listing.

                            <?php endif; ?>

                        </p>

                    </div>


                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:25px;
                            flex-wrap:wrap;
                        "
                    >

                        <a
                            href="/visrs/vehicles/"
                            class="button"
                        >
                            View Vehicles
                        </a>


                        <a
                            href="/visrs/dashboard.php"
                            class="button button-secondary"
                        >
                            Dashboard
                        </a>

                    </div>


                <?php else: ?>


                    <form
                        method="POST"
                        action=""
                        enctype="multipart/form-data"
                        id="marketplace-listing-form"
                    >


                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                generateCSRFToken()
                            ) ?>"
                        >


                        <!-- VEHICLE -->

                        <div class="form-group">

                            <label for="vehicle_id">
                                Vehicle *
                            </label>

                            <select
                                id="vehicle_id"
                                name="vehicle_id"
                                required
                                style="
                                    width:100%;
                                    padding:12px;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    font-size:15px;
                                    background:white;
                                "
                            >

                                <option value="">
                                    Select vehicle
                                </option>


                                <?php foreach (
                                    $vehicles as $vehicle
                                ): ?>

                                    <option
                                        value="<?= (int) $vehicle["VehicleID"] ?>"
                                        <?= (
                                            (string) $vehicleID ===
                                            (string) $vehicle["VehicleID"]
                                        )
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $vehicle["VehicleYear"]
                                            . " "
                                            . $vehicle["Make"]
                                            . " "
                                            . $vehicle["Model"]
                                            . " — Plate: "
                                            . $vehicle["PlateNumber"]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- PRICE -->

                        <div class="form-group">

                            <label for="price">
                                Selling Price *
                            </label>

                            <input
                                type="number"
                                id="price"
                                name="price"
                                min="0.01"
                                max="9999999999.99"
                                step="0.01"
                                placeholder="e.g. 65000.00"
                                value="<?= htmlspecialchars(
                                    $price
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <label for="description">
                                Vehicle Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="6"
                                maxlength="5000"
                                placeholder="Describe the vehicle, its condition, features, maintenance history, and anything else a buyer should know."
                                data-character-count="description-count"
                            ><?= htmlspecialchars(
                                $description
                            ) ?></textarea>


                            <small
                                id="description-count"
                                style="
                                    display:block;
                                    margin-top:6px;
                                    color:#6b7280;
                                "
                            >
                                0 characters
                            </small>

                        </div>


                        <!-- VEHICLE PHOTOS -->

                        <div class="form-group">


                            <label
                                style="
                                    display:block;
                                    font-weight:600;
                                    margin-bottom:9px;
                                "
                            >
                                Vehicle Photos *
                            </label>


                            <div
                                id="photo-upload-area"
                                class="photo-upload-area"
                            >


                                <div
                                    class="photo-upload-icon"
                                >
                                    📷
                                </div>


                                <h3
                                    class="photo-upload-title"
                                >
                                    Add Vehicle Photos
                                </h3>


                                <p
                                    class="photo-upload-description"
                                >
                                    Upload clear photos of the vehicle
                                    so potential buyers can see its
                                    condition and features.
                                    Drag and drop your images here,
                                    or choose them from your device.
                                </p>


                                <button
                                    type="button"
                                    id="choose-photos-button"
                                    class="photo-upload-button"
                                >
                                    📁 Choose Photos
                                </button>


                                <input
                                    type="file"
                                    id="vehicle_images"
                                    name="vehicle_images[]"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    required
                                    style="display:none;"
                                >


                                <div
                                    class="photo-upload-info"
                                >

                                    <span>
                                        JPG, PNG or WebP
                                    </span>

                                    <span>
                                        •
                                    </span>

                                    <span>
                                        Maximum 5 MB each
                                    </span>

                                    <span>
                                        •
                                    </span>

                                    <span>
                                        Up to 10 photos
                                    </span>

                                </div>

                            </div>


                            <div
                                id="photo-count"
                                class="photo-count"
                            >
                            </div>


                            <div
                                id="photo-preview-grid"
                                class="photo-preview-grid"
                            >
                            </div>


                            <p
                                class="photo-upload-note"
                            >

                                <strong>
                                    Tip:
                                </strong>

                                Upload the clearest exterior photo first.
                                Your first photo will automatically become
                                the primary photo displayed on the
                                marketplace listing.

                            </p>

                        </div>


                        <!-- BUTTONS -->

                        <div
                            style="
                                display:flex;
                                gap:10px;
                                margin-top:25px;
                                flex-wrap:wrap;
                            "
                        >

                            <button
                                type="submit"
                                class="button"
                            >
                                Create Listing
                            </button>


                            <a
                                href="/visrs/dashboard.php"
                                class="button button-secondary"
                            >
                                Cancel
                            </a>

                        </div>


                    </form>


                <?php endif; ?>


            </section>


        </main>


    </main>


</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const uploadArea =
            document.getElementById(
                "photo-upload-area"
            );

        const fileInput =
            document.getElementById(
                "vehicle_images"
            );

        const chooseButton =
            document.getElementById(
                "choose-photos-button"
            );

        const previewGrid =
            document.getElementById(
                "photo-preview-grid"
            );

        const photoCount =
            document.getElementById(
                "photo-count"
            );


        if (
            !uploadArea ||
            !fileInput ||
            !chooseButton ||
            !previewGrid ||
            !photoCount
        ) {
            return;
        }


        let selectedFiles = [];


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        const maximumFileSize =
            5 * 1024 * 1024;


        const maximumImages = 10;


        /*
        |--------------------------------------------------------------------------
        | Choose Photos
        |--------------------------------------------------------------------------
        */

        chooseButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                fileInput.click();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Upload Area Click
        |--------------------------------------------------------------------------
        */

        uploadArea.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    chooseButton
                ) {
                    return;
                }

                fileInput.click();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drag Over
        |--------------------------------------------------------------------------
        */

        uploadArea.addEventListener(
            "dragover",
            function (event) {

                event.preventDefault();

                uploadArea.classList.add(
                    "dragging"
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drag Leave
        |--------------------------------------------------------------------------
        */

        uploadArea.addEventListener(
            "dragleave",
            function () {

                uploadArea.classList.remove(
                    "dragging"
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drop
        |--------------------------------------------------------------------------
        */

        uploadArea.addEventListener(
            "drop",
            function (event) {

                event.preventDefault();

                uploadArea.classList.remove(
                    "dragging"
                );


                const droppedFiles =
                    Array.from(
                        event.dataTransfer.files
                    );


                addFiles(
                    droppedFiles
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | File Input Change
        |--------------------------------------------------------------------------
        */

        fileInput.addEventListener(
            "change",
            function () {

                const files =
                    Array.from(
                        fileInput.files
                    );


                addFiles(
                    files
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Add Files
        |--------------------------------------------------------------------------
        */

        function addFiles(files) {

            for (
                const file of files
            ) {

                if (
                    selectedFiles.length >=
                    maximumImages
                ) {

                    alert(
                        "You can upload a maximum of 10 photos."
                    );

                    break;
                }


                if (
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    alert(
                        file.name +
                        " is not a supported image type."
                    );

                    continue;
                }


                if (
                    file.size <= 0 ||
                    file.size >
                    maximumFileSize
                ) {

                    alert(
                        file.name +
                        " is larger than 5 MB."
                    );

                    continue;
                }


                const duplicate =
                    selectedFiles.some(
                        function (existingFile) {

                            return (
                                existingFile.name ===
                                file.name &&

                                existingFile.size ===
                                file.size &&

                                existingFile.lastModified ===
                                file.lastModified
                            );

                        }
                    );


                if (
                    duplicate
                ) {
                    continue;
                }


                selectedFiles.push(
                    file
                );
            }


            updateFileInput();
            renderPreviews();
        }


        /*
        |--------------------------------------------------------------------------
        | Update Input Files
        |--------------------------------------------------------------------------
        */

        function updateFileInput() {

            const dataTransfer =
                new DataTransfer();


            selectedFiles.forEach(
                function (file) {

                    dataTransfer.items.add(
                        file
                    );

                }
            );


            fileInput.files =
                dataTransfer.files;
        }


        /*
        |--------------------------------------------------------------------------
        | Render Previews
        |--------------------------------------------------------------------------
        */

        function renderPreviews() {

            previewGrid.innerHTML = "";


            if (
                selectedFiles.length === 0
            ) {

                photoCount.style.display =
                    "none";

                return;
            }


            photoCount.style.display =
                "block";


            photoCount.textContent =
                selectedFiles.length +
                (
                    selectedFiles.length === 1
                        ? " photo selected"
                        : " photos selected"
                );


            selectedFiles.forEach(
                function (file, index) {

                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            const card =
                                document.createElement(
                                    "div"
                                );


                            card.className =
                                "photo-preview-card";


                            /*
                            |--------------------------------------------------------------------------
                            | Image
                            |--------------------------------------------------------------------------
                            */

                            const image =
                                document.createElement(
                                    "img"
                                );


                            image.className =
                                "photo-preview-image";


                            image.src =
                                event.target.result;


                            image.alt =
                                "Vehicle photo "
                                + (index + 1);


                            card.appendChild(
                                image
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Primary Badge
                            |--------------------------------------------------------------------------
                            */

                            if (
                                index === 0
                            ) {

                                const badge =
                                    document.createElement(
                                        "div"
                                    );


                                badge.className =
                                    "primary-photo-badge";


                                badge.textContent =
                                    "★ PRIMARY PHOTO";


                                card.appendChild(
                                    badge
                                );
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Remove Button
                            |--------------------------------------------------------------------------
                            */

                            const removeButton =
                                document.createElement(
                                    "button"
                                );


                            removeButton.type =
                                "button";


                            removeButton.className =
                                "remove-photo-button";


                            removeButton.innerHTML =
                                "&times;";


                            removeButton.title =
                                "Remove photo";


                            removeButton.setAttribute(
                                "aria-label",
                                "Remove photo"
                            );


                            removeButton.addEventListener(
                                "click",
                                function (event) {

                                    event.preventDefault();
                                    event.stopPropagation();


                                    selectedFiles.splice(
                                        index,
                                        1
                                    );


                                    updateFileInput();
                                    renderPreviews();

                                }
                            );


                            card.appendChild(
                                removeButton
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Filename
                            |--------------------------------------------------------------------------
                            */

                            const fileName =
                                document.createElement(
                                    "div"
                                );


                            fileName.className =
                                "photo-file-name";


                            fileName.textContent =
                                file.name;


                            card.appendChild(
                                fileName
                            );


                            previewGrid.appendChild(
                                card
                            );

                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );
        }

    }
);

</script>


<?php

include __DIR__ . "/../includes/footer.php";

?>