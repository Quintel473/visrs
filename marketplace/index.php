<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Vehicle Marketplace";
$activePage = "marketplace";


/*
|--------------------------------------------------------------------------
| Search / Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$minPrice = trim($_GET["min_price"] ?? "");
$maxPrice = trim($_GET["max_price"] ?? "");
$status = trim($_GET["status"] ?? "Available");


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        vl.ListingID,
        vl.VehicleID,
        vl.SellerID,
        vl.Price,
        vl.Description,
        vl.Status,
        vl.CreatedAt,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.Status AS VehicleStatus,

        u.FirstName AS SellerFirstName,
        u.LastName AS SellerLastName,

        vi.ImagePath

    FROM vehicle_listings vl

    INNER JOIN vehicles v
        ON vl.VehicleID = v.VehicleID

    INNER JOIN users u
        ON vl.SellerID = u.UserID

    LEFT JOIN vehicle_images vi
        ON vi.VehicleID = v.VehicleID
        AND vi.IsPrimary = 1

    WHERE 1 = 1
";


$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "
        AND (
            v.PlateNumber LIKE ?
            OR v.VIN LIKE ?
            OR v.Make LIKE ?
            OR v.Model LIKE ?
            OR v.VehicleYear LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Minimum Price
|--------------------------------------------------------------------------
*/

if (
    $minPrice !== "" &&
    is_numeric($minPrice)
) {

    $sql .= "
        AND vl.Price >= ?
    ";

    $params[] =
        (float) $minPrice;
}


/*
|--------------------------------------------------------------------------
| Maximum Price
|--------------------------------------------------------------------------
*/

if (
    $maxPrice !== "" &&
    is_numeric($maxPrice)
) {

    $sql .= "
        AND vl.Price <= ?
    ";

    $params[] =
        (float) $maxPrice;
}


/*
|--------------------------------------------------------------------------
| Listing Status
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "Available",
    "Reserved",
    "Sold",
    "Inactive"
];


if (
    $status !== "" &&
    in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $sql .= "
        AND vl.Status = ?
    ";

    $params[] = $status;

}


/*
|--------------------------------------------------------------------------
| Ordering
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        vl.CreatedAt DESC
";


/*
|--------------------------------------------------------------------------
| Execute Query
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare($sql);

$stmt->execute(
    $params
);

$listings =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Total Listings
|--------------------------------------------------------------------------
*/

$totalListings =
    count($listings);

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
        | Marketplace Header
        |--------------------------------------------------------------------------
        */

        .marketplace-header {

            display:flex;

            justify-content:space-between;

            align-items:center;

            gap:20px;

            margin-bottom:25px;

            flex-wrap:wrap;

        }


        .marketplace-header h1 {

            margin:0;

        }


        .marketplace-header p {

            margin:6px 0 0;

            color:#64748b;

        }


        .marketplace-actions {

            display:flex;

            gap:10px;

            flex-wrap:wrap;

        }


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        .marketplace-filters {

            padding:20px;

            margin-bottom:25px;

            border:1px solid #e5e7eb;

            border-radius:10px;

            background:#ffffff;

        }


        .filter-grid {

            display:grid;

            grid-template-columns:
                minmax(220px, 2fr)
                minmax(140px, 1fr)
                minmax(140px, 1fr)
                minmax(160px, 1fr)
                auto;

            gap:12px;

            align-items:end;

        }


        .filter-group label {

            display:block;

            margin-bottom:6px;

            font-size:13px;

            font-weight:600;

            color:#374151;

        }


        .filter-group input,
        .filter-group select {

            width:100%;

            box-sizing:border-box;

            padding:10px 12px;

            border:1px solid #d1d5db;

            border-radius:6px;

            background:#ffffff;

            font-size:14px;

        }


        .filter-buttons {

            display:flex;

            gap:8px;

        }


        /*
        |--------------------------------------------------------------------------
        | Listing Grid
        |--------------------------------------------------------------------------
        */

        .marketplace-grid {

            display:grid;

            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(270px, 1fr)
                );

            gap:22px;

        }


        /*
        |--------------------------------------------------------------------------
        | Listing Card
        |--------------------------------------------------------------------------
        */

        .vehicle-listing-card {

            overflow:hidden;

            border:1px solid #e5e7eb;

            border-radius:12px;

            background:#ffffff;

            box-shadow:
                0 2px 7px
                rgba(0, 0, 0, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

        }


        .vehicle-listing-card:hover {

            transform:translateY(-3px);

            box-shadow:
                0 8px 20px
                rgba(0, 0, 0, 0.10);

        }


        /*
        |--------------------------------------------------------------------------
        | Vehicle Image
        |--------------------------------------------------------------------------
        */

        .listing-image-container {

            position:relative;

            width:100%;

            height:210px;

            background:#f1f5f9;

        }


        .listing-image {

            display:block;

            width:100%;

            height:100%;

            object-fit:cover;

        }


        .listing-no-image {

            width:100%;

            height:100%;

            display:flex;

            align-items:center;

            justify-content:center;

            flex-direction:column;

            color:#94a3b8;

            font-size:14px;

            gap:8px;

        }


        .listing-no-image-icon {

            font-size:42px;

        }


        .listing-status {

            position:absolute;

            top:12px;

            right:12px;

            padding:6px 10px;

            border-radius:20px;

            background:#166534;

            color:#ffffff;

            font-size:11px;

            font-weight:700;

        }


        .listing-status.reserved {

            background:#92400e;

        }


        .listing-status.sold {

            background:#991b1b;

        }


        .listing-status.inactive {

            background:#475569;

        }


        /*
        |--------------------------------------------------------------------------
        | Listing Content
        |--------------------------------------------------------------------------
        */

        .listing-content {

            padding:18px;

        }


        .listing-title {

            margin:0 0 5px;

            font-size:18px;

            font-weight:700;

            color:#111827;

        }


        .listing-year {

            color:#64748b;

            font-size:13px;

            margin-bottom:12px;

        }


        .listing-price {

            margin-bottom:15px;

            color:#111827;

            font-size:21px;

            font-weight:800;

        }


        .listing-details {

            display:flex;

            flex-wrap:wrap;

            gap:6px;

            margin-bottom:15px;

        }


        .listing-detail {

            padding:5px 8px;

            border-radius:5px;

            background:#f1f5f9;

            color:#475569;

            font-size:11px;

        }


        .listing-description {

            min-height:42px;

            margin-bottom:15px;

            color:#64748b;

            font-size:13px;

            line-height:1.5;

        }


        .listing-seller {

            padding-top:12px;

            margin-bottom:15px;

            border-top:1px solid #f1f5f9;

            color:#64748b;

            font-size:12px;

        }


        .listing-seller strong {

            color:#374151;

        }


        .listing-button {

            display:block;

            width:100%;

            box-sizing:border-box;

            padding:10px 14px;

            border-radius:6px;

            background:#111827;

            color:#ffffff;

            text-align:center;

            text-decoration:none;

            font-size:13px;

            font-weight:600;

        }


        .listing-button:hover {

            opacity:0.9;

        }


        /*
        |--------------------------------------------------------------------------
        | Empty State
        |--------------------------------------------------------------------------
        */

        .marketplace-empty {

            padding:60px 25px;

            text-align:center;

            border:1px solid #e5e7eb;

            border-radius:12px;

            background:#ffffff;

        }


        .marketplace-empty-icon {

            margin-bottom:12px;

            font-size:48px;

        }


        .marketplace-empty h2 {

            margin:0 0 8px;

        }


        .marketplace-empty p {

            margin:0 auto 20px;

            max-width:500px;

            color:#64748b;

            line-height:1.6;

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (
            max-width:900px
        ) {

            .filter-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

            }

        }


        @media (
            max-width:600px
        ) {

            .filter-grid {

                grid-template-columns:1fr;

            }


            .marketplace-grid {

                grid-template-columns:1fr;

            }


            .marketplace-header {

                align-items:flex-start;

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
                 PAGE HEADER
                 ================================================= -->

            <div class="marketplace-header">

                <div>

                    <h1>
                        Vehicle Marketplace
                    </h1>

                    <p>
                        Browse vehicles currently available for sale.
                    </p>

                </div>


                <?php if (
                    hasRole(["Admin", "Seller"])
                ): ?>

                    <div class="marketplace-actions">

                        <a
                            href="/visrs/marketplace/add.php"
                            class="button"
                        >
                            + List Vehicle for Sale
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 FILTERS
                 ================================================= -->

            <form
                method="GET"
                action=""
                class="marketplace-filters"
            >

                <div class="filter-grid">


                    <!-- SEARCH -->

                    <div class="filter-group">

                        <label for="search">
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Make, model, plate, VIN..."
                            value="<?= htmlspecialchars(
                                $search
                            ) ?>"
                        >

                    </div>


                    <!-- MIN PRICE -->

                    <div class="filter-group">

                        <label for="min_price">
                            Min Price
                        </label>

                        <input
                            type="number"
                            id="min_price"
                            name="min_price"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            value="<?= htmlspecialchars(
                                $minPrice
                            ) ?>"
                        >

                    </div>


                    <!-- MAX PRICE -->

                    <div class="filter-group">

                        <label for="max_price">
                            Max Price
                        </label>

                        <input
                            type="number"
                            id="max_price"
                            name="max_price"
                            min="0"
                            step="0.01"
                            placeholder="100000.00"
                            value="<?= htmlspecialchars(
                                $maxPrice
                            ) ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="filter-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <?php foreach (
                                $allowedStatuses
                                as $listingStatus
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $listingStatus
                                    ) ?>"
                                    <?= $status ===
                                        $listingStatus
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $listingStatus
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- BUTTONS -->

                    <div
                        class="filter-buttons"
                    >

                        <button
                            type="submit"
                            class="button"
                        >
                            Search
                        </button>


                        <a
                            href="/visrs/marketplace/"
                            class="button button-secondary"
                        >
                            Reset
                        </a>

                    </div>


                </div>

            </form>


            <!-- =================================================
                 RESULTS SUMMARY
                 ================================================= -->

            <div
                style="
                    margin-bottom:15px;
                    color:#64748b;
                    font-size:14px;
                "
            >

                <?= $totalListings ?>

                <?=
                    $totalListings === 1
                        ? "vehicle"
                        : "vehicles"
                ?>

                found

            </div>


            <!-- =================================================
                 LISTINGS
                 ================================================= -->

            <?php if (
                count($listings) > 0
            ): ?>


                <div class="marketplace-grid">


                    <?php foreach (
                        $listings as $listing
                    ): ?>


                        <article
                            class="vehicle-listing-card"
                        >


                            <!-- IMAGE -->

                            <div
                                class="listing-image-container"
                            >

                                <?php if (
                                    !empty(
                                        $listing["ImagePath"]
                                    )
                                ): ?>

                                    <img
                                        src="/visrs/<?= htmlspecialchars(
                                            $listing["ImagePath"]
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $listing["Make"]
                                            . " "
                                            . $listing["Model"]
                                        ) ?>"
                                        class="listing-image"
                                    >

                                <?php else: ?>

                                    <div
                                        class="listing-no-image"
                                    >

                                        <div
                                            class="listing-no-image-icon"
                                        >
                                            🚗
                                        </div>

                                        <div>
                                            No photo available
                                        </div>

                                    </div>

                                <?php endif; ?>


                                <?php

                                $statusClass =
                                    strtolower(
                                        $listing["Status"]
                                    );

                                ?>

                                <span
                                    class="listing-status <?= htmlspecialchars(
                                        $statusClass
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $listing["Status"]
                                    ) ?>

                                </span>

                            </div>


                            <!-- CONTENT -->

                            <div
                                class="listing-content"
                            >


                                <h2
                                    class="listing-title"
                                >

                                    <?= htmlspecialchars(
                                        $listing["Make"]
                                        . " "
                                        . $listing["Model"]
                                    ) ?>

                                </h2>


                                <div
                                    class="listing-year"
                                >

                                    <?= htmlspecialchars(
                                        $listing["VehicleYear"]
                                    ) ?>

                                    <?php if (
                                        !empty(
                                            $listing["Color"]
                                        )
                                    ): ?>

                                        •
                                        <?= htmlspecialchars(
                                            $listing["Color"]
                                        ) ?>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="listing-price"
                                >

                                    $
                                    <?= number_format(
                                        (float)
                                        $listing["Price"],
                                        2
                                    ) ?>

                                </div>


                                <!-- DETAILS -->

                                <div
                                    class="listing-details"
                                >

                                    <span
                                        class="listing-detail"
                                    >

                                        Plate:
                                        <?= htmlspecialchars(
                                            $listing["PlateNumber"]
                                        ) ?>

                                    </span>


                                    <?php if (
                                        !empty(
                                            $listing["VehicleType"]
                                        )
                                    ): ?>

                                        <span
                                            class="listing-detail"
                                        >

                                            <?= htmlspecialchars(
                                                $listing["VehicleType"]
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <!-- DESCRIPTION -->

                                <div
                                    class="listing-description"
                                >

                                    <?php

                                    $description =
                                        trim(
                                            $listing["Description"]
                                            ?? ""
                                        );

                                    if (
                                        $description === ""
                                    ) {

                                        echo
                                            "No description provided.";

                                    } elseif (
                                        strlen(
                                            $description
                                        ) > 120
                                    ) {

                                        echo htmlspecialchars(
                                            substr(
                                                $description,
                                                0,
                                                120
                                            )
                                            . "..."
                                        );

                                    } else {

                                        echo htmlspecialchars(
                                            $description
                                        );

                                    }

                                    ?>

                                </div>


                                <!-- SELLER -->

                                <div
                                    class="listing-seller"
                                >

                                    Seller:

                                    <strong>

                                        <?= htmlspecialchars(
                                            $listing[
                                                "SellerFirstName"
                                            ]
                                            . " "
                                            . $listing[
                                                "SellerLastName"
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- VIEW BUTTON -->

                                <a
                                    href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $listing["VehicleID"] ?>"
                                    class="listing-button"
                                >

                                    View Vehicle

                                </a>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div
                    class="marketplace-empty"
                >

                    <div
                        class="marketplace-empty-icon"
                    >
                        🚗
                    </div>


                    <h2>
                        No Vehicles Found
                    </h2>


                    <p>
                        There are no marketplace vehicles
                        matching your current search or filter.
                        Try changing your search criteria.
                    </p>


                    <a
                        href="/visrs/marketplace/"
                        class="button"
                    >
                        View All Vehicles
                    </a>

                </div>


            <?php endif; ?>


        </main>


    </main>


</div>


<?php

require_once __DIR__ .
    "/../includes/footer.php";

?>