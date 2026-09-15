<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

requireRole([
    "Admin",
    "Police",
    "Seller",
    "User"
]);

$pageTitle = "I'm Interested";
$activePage = "marketplace";

$message = "";
$errors = [];


/*
|--------------------------------------------------------------------------
| Get Listing ID
|--------------------------------------------------------------------------
*/

$listingId = filter_input(
    INPUT_GET,
    "listing_id",
    FILTER_VALIDATE_INT
);

if (!$listingId || $listingId <= 0) {

    http_response_code(404);

    $errorTitle = "Listing Not Found";
    $errorMessage = "The marketplace listing you requested could not be found.";

} else {

    /*
    |--------------------------------------------------------------------------
    | Get Listing
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

            v.PlateNumber,
            v.VIN,
            v.Make,
            v.Model,
            v.VehicleYear,
            v.Color,
            v.VehicleType,
            v.Status AS VehicleStatus,

            u.FirstName AS SellerFirstName,
            u.LastName AS SellerLastName

        FROM vehicle_listings vl

        INNER JOIN vehicles v
            ON vl.VehicleID = v.VehicleID

        INNER JOIN users u
            ON vl.SellerID = u.UserID

        WHERE vl.ListingID = ?

        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $listingId
    ]);

    $listing = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Check Listing
    |--------------------------------------------------------------------------
    */

    if (!$listing) {

        http_response_code(404);

        $errorTitle = "Listing Not Found";
        $errorMessage =
            "This vehicle does not currently have a marketplace listing.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Current User
        |--------------------------------------------------------------------------
        */

        $currentUserId =
            (int) currentUserId();


        /*
        |--------------------------------------------------------------------------
        | Prevent Seller From Showing Interest In Own Vehicle
        |--------------------------------------------------------------------------
        */

        if (
            $currentUserId ===
            (int) $listing["SellerID"]
        ) {

            http_response_code(403);

            $errorTitle = "Action Not Available";

            $errorMessage =
                "You cannot submit an interest request for your own vehicle.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Check Existing Interest
            |--------------------------------------------------------------------------
            */

            $existingSql = "
                SELECT
                    InterestID,
                    Status,
                    Message,
                    CreatedAt

                FROM vehicle_interests

                WHERE ListingID = ?
                AND BuyerID = ?

                LIMIT 1
            ";

            $existingStmt =
                $pdo->prepare(
                    $existingSql
                );

            $existingStmt->execute([
                $listingId,
                $currentUserId
            ]);

            $existingInterest =
                $existingStmt->fetch();


            /*
            |--------------------------------------------------------------------------
            | Form Values
            |--------------------------------------------------------------------------
            */

            $message = "";

            $errors = [];


            /*
            |--------------------------------------------------------------------------
            | Process Form
            |--------------------------------------------------------------------------
            */

            if ($_SERVER["REQUEST_METHOD"] === "POST") {

                requireValidCSRF();


                $message =
                    trim(
                        $_POST["message"] ?? ""
                    );


                /*
                |--------------------------------------------------------------------------
                | Validate Message
                |--------------------------------------------------------------------------
                */

                if (
                    strlen($message) >
                    1000
                ) {

                    $errors[] =
                        "Your message cannot exceed 1000 characters.";

                }


                /*
                |--------------------------------------------------------------------------
                | Existing Interest
                |--------------------------------------------------------------------------
                */

                if ($existingInterest) {

                    $errors[] =
                        "You have already submitted an interest request for this vehicle.";

                }


                /*
                |--------------------------------------------------------------------------
                | Listing Status
                |--------------------------------------------------------------------------
                */

                if (
                    $listing["ListingStatus"] !==
                    "Available"
                ) {

                    $errors[] =
                        "This vehicle is no longer available for new interest requests.";

                }


                /*
                |--------------------------------------------------------------------------
                | Vehicle Status
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $listing["VehicleStatus"],
                        [
                            "Stolen",
                            "Inactive"
                        ],
                        true
                    )
                ) {

                    $errors[] =
                        "This vehicle is not currently eligible for marketplace inquiries.";

                }


                /*
                |--------------------------------------------------------------------------
                | Insert Interest
                |--------------------------------------------------------------------------
                */

                if (
                    count($errors) === 0
                ) {

                    try {

                        $pdo->beginTransaction();


                        /*
                        |--------------------------------------------------------------------------
                        | Insert
                        |--------------------------------------------------------------------------
                        */

                        $insertSql = "
                            INSERT INTO vehicle_interests
                            (
                                ListingID,
                                VehicleID,
                                BuyerID,
                                SellerID,
                                Message,
                                Status
                            )
                            VALUES
                            (
                                :listing_id,
                                :vehicle_id,
                                :buyer_id,
                                :seller_id,
                                :message,
                                'Pending'
                            )
                        ";


                        $insertStmt =
                            $pdo->prepare(
                                $insertSql
                            );


                        $insertStmt->execute([
                            ":listing_id" =>
                                $listing["ListingID"],

                            ":vehicle_id" =>
                                $listing["VehicleID"],

                            ":buyer_id" =>
                                $currentUserId,

                            ":seller_id" =>
                                $listing["SellerID"],

                            ":message" =>
                                $message !== ""
                                    ? $message
                                    : null
                        ]);


                        $interestId =
                            (int) $pdo->lastInsertId();


                        /*
                        |--------------------------------------------------------------------------
                        | Audit Log
                        |--------------------------------------------------------------------------
                        */

                        logAudit(
                            $pdo,
                            "Submitted vehicle interest request",
                            "vehicle_interests",
                            $interestId
                        );


                        $pdo->commit();


                        /*
                        |--------------------------------------------------------------------------
                        | Redirect
                        |--------------------------------------------------------------------------
                        */

                        header(
                            "Location: /visrs/marketplace/view.php?vehicle_id="
                            . (int) $listing["VehicleID"]
                            . "&interest=success"
                        );

                        exit;


                    } catch (
                        PDOException $exception
                    ) {

                        if (
                            $pdo->inTransaction()
                        ) {

                            $pdo->rollBack();

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Duplicate Request Safety
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $exception->getCode() === "23000"
                        ) {

                            $errors[] =
                                "You have already submitted an interest request for this vehicle.";

                        } else {

                            $errors[] =
                                "Unable to submit your interest request. Please try again.";

                        }

                    }

                }

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
        <?= e($pageTitle) ?> - VISRS
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

    <style>

        .interest-container {

            max-width:850px;

            margin:0 auto;

        }


        .interest-card {

            background:#ffffff;

            border:1px solid #e5e7eb;

            border-radius:12px;

            box-shadow:
                0 2px 7px
                rgba(0,0,0,0.06);

            overflow:hidden;

        }


        .interest-header {

            padding:22px;

            border-bottom:1px solid #e5e7eb;

        }


        .interest-header h1 {

            margin:0 0 6px;

            font-size:24px;

        }


        .interest-header p {

            margin:0;

            color:#64748b;

            font-size:14px;

        }


        .interest-body {

            padding:22px;

        }


        .vehicle-summary {

            display:grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap:12px;

            margin-bottom:22px;

            padding:18px;

            border-radius:8px;

            background:#f8fafc;

        }


        .vehicle-summary-item {

            padding:10px;

        }


        .vehicle-summary-label {

            margin-bottom:4px;

            color:#64748b;

            font-size:11px;

            font-weight:600;

            text-transform:uppercase;

        }


        .vehicle-summary-value {

            color:#111827;

            font-size:14px;

            font-weight:600;

        }


        .interest-price {

            margin-top:5px;

            color:#111827;

            font-size:20px;

            font-weight:800;

        }


        .form-group {

            margin-bottom:18px;

        }


        .form-group label {

            display:block;

            margin-bottom:7px;

            color:#374151;

            font-size:13px;

            font-weight:600;

        }


        .form-group textarea {

            width:100%;

            min-height:140px;

            box-sizing:border-box;

            padding:11px 12px;

            border:1px solid #d1d5db;

            border-radius:7px;

            resize:vertical;

            font-family:inherit;

            font-size:14px;

        }


        .form-group textarea:focus {

            outline:none;

            border-color:#111827;

        }


        .character-count {

            margin-top:5px;

            color:#94a3b8;

            font-size:11px;

            text-align:right;

        }


        .interest-actions {

            display:flex;

            gap:10px;

            flex-wrap:wrap;

            margin-top:20px;

        }


        .interest-actions .button {

            cursor:pointer;

        }


        .error-box {

            margin-bottom:18px;

            padding:14px;

            border:1px solid #fecaca;

            border-radius:7px;

            background:#fef2f2;

            color:#991b1b;

            font-size:13px;

        }


        .error-box ul {

            margin:0;

            padding-left:20px;

        }


        .error-box li {

            margin-bottom:4px;

        }


        .error-box li:last-child {

            margin-bottom:0;

        }


        .information-box {

            margin-bottom:20px;

            padding:14px;

            border:1px solid #e5e7eb;

            border-radius:7px;

            background:#f8fafc;

            color:#475569;

            font-size:13px;

            line-height:1.6;

        }


        @media (max-width:600px) {

            .vehicle-summary {

                grid-template-columns:1fr;

            }


            .interest-body {

                padding:18px;

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


            <a
                href="/visrs/marketplace/"
                style="
                    display:inline-block;
                    margin-bottom:18px;
                    color:#475569;
                    text-decoration:none;
                    font-size:13px;
                    font-weight:600;
                "
            >

                ← Back to Marketplace

            </a>


            <?php if (
                isset($errorTitle, $errorMessage)
            ): ?>


                <div
                    class="interest-container"
                >

                    <div
                        class="interest-card"
                    >

                        <div
                            class="interest-header"
                        >

                            <h1>
                                <?= e(
                                    $errorTitle
                                ) ?>
                            </h1>

                            <p>
                                <?= e(
                                    $errorMessage
                                ) ?>
                            </p>

                        </div>


                        <div
                            class="interest-body"
                        >

                            <a
                                href="/visrs/marketplace/"
                                class="button"
                            >

                                Back to Marketplace

                            </a>

                        </div>

                    </div>

                </div>


            <?php elseif (
                $existingInterest
            ): ?>


                <div
                    class="interest-container"
                >

                    <div
                        class="interest-card"
                    >

                        <div
                            class="interest-header"
                        >

                            <h1>
                                Interest Already Submitted
                            </h1>

                            <p>
                                You have already expressed interest in this vehicle.
                            </p>

                        </div>


                        <div
                            class="interest-body"
                        >

                            <div
                                class="vehicle-summary"
                            >

                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Vehicle
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= e(
                                            $listing["Make"]
                                            . " "
                                            . $listing["Model"]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Price
                                    </div>

                                    <div
                                        class="interest-price"
                                    >

                                        $
                                        <?= number_format(
                                            (float)
                                            $listing["Price"],
                                            2
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Request Status
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= e(
                                            $existingInterest["Status"]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Submitted
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= formatDateTime(
                                            $existingInterest["CreatedAt"]
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <?php if (
                                !empty(
                                    $existingInterest["Message"]
                                )
                            ): ?>

                                <div
                                    class="information-box"
                                >

                                    <strong>
                                        Your message:
                                    </strong>

                                    <br>

                                    <?= nl2br(
                                        e(
                                            $existingInterest[
                                                "Message"
                                            ]
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <div
                                class="interest-actions"
                            >

                                <a
                                    href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $listing["VehicleID"] ?>"
                                    class="button"
                                >

                                    Back to Vehicle

                                </a>

                                <a
                                    href="/visrs/marketplace/"
                                    class="button button-secondary"
                                >

                                    Marketplace

                                </a>

                            </div>

                        </div>

                    </div>

                </div>


            <?php else: ?>


                <div
                    class="interest-container"
                >

                    <div
                        class="interest-card"
                    >


                        <div
                            class="interest-header"
                        >

                            <h1>
                                I'm Interested
                            </h1>

                            <p>
                                Let the seller know that you're interested in this vehicle.
                            </p>

                        </div>


                        <div
                            class="interest-body"
                        >


                            <!-- VEHICLE SUMMARY -->

                            <div
                                class="vehicle-summary"
                            >

                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Vehicle
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= e(
                                            $listing["Make"]
                                            . " "
                                            . $listing["Model"]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Year
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= e(
                                            $listing["VehicleYear"]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Color
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
                                    >

                                        <?= displayValue(
                                            $listing["Color"]
                                        ) ?>

                                    </div>

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Seller
                                    </div>

                                    <div
                                        class="vehicle-summary-value"
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

                                </div>


                                <div
                                    class="vehicle-summary-item"
                                    style="
                                        grid-column:1 / -1;
                                    "
                                >

                                    <div
                                        class="vehicle-summary-label"
                                    >
                                        Asking Price
                                    </div>

                                    <div
                                        class="interest-price"
                                    >

                                        $
                                        <?= number_format(
                                            (float)
                                            $listing["Price"],
                                            2
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <?php if (
                                count($errors) > 0
                            ): ?>

                                <div
                                    class="error-box"
                                >

                                    <ul>

                                        <?php foreach (
                                            $errors
                                            as $error
                                        ): ?>

                                            <li>
                                                <?= e(
                                                    $error
                                                ) ?>
                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                </div>

                            <?php endif; ?>


                            <div
                                class="information-box"
                            >

                                Your interest request will be sent to the
                                seller. You can optionally include a short
                                message to let the seller know what you're
                                interested in or ask a question about the vehicle.

                            </div>


                            <form
                                method="POST"
                                action=""
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(
                                        generateCSRFToken()
                                    ) ?>"
                                >


                                <div
                                    class="form-group"
                                >

                                    <label
                                        for="message"
                                    >

                                        Message to Seller
                                        <span
                                            style="
                                                color:#94a3b8;
                                                font-weight:400;
                                            "
                                        >
                                            (Optional)
                                        </span>

                                    </label>


                                    <textarea
                                        id="message"
                                        name="message"
                                        maxlength="1000"
                                        data-character-count="interestMessageCount"
                                        placeholder="Example: Hi, I'm interested in this vehicle and would like to know if it is still available."
                                    ><?= e(
                                        $message
                                    ) ?></textarea>


                                    <div
                                        id="interestMessageCount"
                                        class="character-count"
                                    >

                                        0 characters

                                    </div>

                                </div>


                                <div
                                    class="interest-actions"
                                >

                                    <button
                                        type="submit"
                                        class="button"
                                    >

                                        Submit Interest

                                    </button>


                                    <a
                                        href="/visrs/marketplace/view.php?vehicle_id=<?= (int) $listing["VehicleID"] ?>"
                                        class="button button-secondary"
                                    >

                                        Cancel

                                    </a>

                                </div>

                            </form>


                        </div>

                    </div>

                </div>


            <?php endif; ?>


        </main>


    </main>


</div>


<?php

require_once __DIR__ .
    "/../includes/footer.php";

?>