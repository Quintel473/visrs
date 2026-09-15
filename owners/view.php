<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$activePage = "owners";
$pageTitle = "Owner Details";
$pageSubtitle = "Owner profile and associated vehicle information.";

$ownerID = $_GET["id"] ?? "";


/*
|--------------------------------------------------------------------------
| Validate Owner ID
|--------------------------------------------------------------------------
*/

if (!ctype_digit((string) $ownerID) || (int) $ownerID <= 0) {
    header("Location: index.php");
    exit;
}

$ownerID = (int) $ownerID;


/*
|--------------------------------------------------------------------------
| Get Owner Information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        OwnerID,
        FirstName,
        LastName,
        Address,
        Phone,
        Email,
        CreatedAt
    FROM owners
    WHERE OwnerID = ?
    LIMIT 1
");

$stmt->execute([$ownerID]);

$owner = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Check That Owner Exists
|--------------------------------------------------------------------------
*/

if (!$owner) {

    http_response_code(404);

    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Owner Not Found - VISRS</title>

        <link
            rel="stylesheet"
            href="/visrs/css/style.css"
        >

    </head>

    <body>

    <div class="layout">

        <main
            class="main-content"
            style="margin-left:0;width:100%;"
        >

            <div
                class="content"
                style="max-width:700px;margin:80px auto;"
            >

                <div class="card">

                    <div
                        style="
                            background:#fee2e2;
                            color:#991b1b;
                            padding:14px;
                            border-radius:8px;
                            margin-bottom:20px;
                        "
                    >
                        <strong>
                            Owner Not Found
                        </strong>
                    </div>

                    <h2>
                        The requested owner could not be found.
                    </h2>

                    <p>
                        The owner record may have been deleted
                        or the supplied owner ID is invalid.
                    </p>

                    <a
                        href="index.php"
                        class="button"
                    >
                        Return to Owners
                    </a>

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
| Get Vehicles Belonging to Owner
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        VehicleID,
        PlateNumber,
        VIN,
        Make,
        Model,
        VehicleYear,
        Color,
        VehicleType,
        Status
    FROM vehicles
    WHERE OwnerID = ?
    ORDER BY VehicleID DESC
");

$stmt->execute([$ownerID]);

$vehicles = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Permission
|--------------------------------------------------------------------------
|
| All authenticated users can view owner information.
| Only Admin and Police can edit owners.
|
*/

$canManageOwners = in_array(
    $_SESSION["Role"] ?? "",
    ["Admin", "Police"],
    true
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
        VISRS - Owner Details
    </title>

    <link
        rel="stylesheet"
        href="/visrs/css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>


    <div class="main-content">

        <?php
        include __DIR__ . "/../includes/header.php";
        ?>


        <main class="content">


            <!-- PAGE TITLE -->

            <div class="page-title">

                <h1>

                    <?= htmlspecialchars(
                        $owner["FirstName"]
                    ) ?>

                    <?= htmlspecialchars(
                        $owner["LastName"]
                    ) ?>

                </h1>

                <p class="page-subtitle">

                    Owner profile and associated vehicle
                    information.

                </p>

            </div>


            <!-- OWNER INFORMATION -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>


                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                        margin-top:15px;
                    "
                >

                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                                width:35%;
                            "
                        >
                            Full Name
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $owner["FirstName"]
                                . " "
                                . $owner["LastName"]
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Address
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $owner["Address"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Phone
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $owner["Phone"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr
                        style="
                            border-bottom:1px solid #e5e7eb;
                        "
                    >

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Email
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $owner["Email"]
                                ?: "Not provided"
                            ) ?>

                        </td>

                    </tr>


                    <tr>

                        <td
                            style="
                                padding:12px;
                                font-weight:bold;
                            "
                        >
                            Owner ID
                        </td>

                        <td style="padding:12px;">

                            <?= htmlspecialchars(
                                $owner["OwnerID"]
                            ) ?>

                        </td>

                    </tr>

                </table>

            </section>


            <!-- ASSOCIATED VEHICLES -->

            <section
                class="card"
                style="margin-top:20px;"
            >

                <h2>
                    Associated Vehicles
                </h2>

                <p
                    style="
                        color:#6b7280;
                        margin-bottom:20px;
                    "
                >
                    Vehicles currently registered to this owner.
                </p>


                <?php if (count($vehicles) > 0): ?>

                    <div style="overflow-x:auto;">

                        <table
                            style="
                                width:100%;
                                border-collapse:collapse;
                            "
                        >

                            <thead>

                                <tr
                                    style="
                                        border-bottom:2px solid #e5e7eb;
                                        text-align:left;
                                    "
                                >

                                    <th style="padding:12px;">
                                        Plate Number
                                    </th>

                                    <th style="padding:12px;">
                                        VIN
                                    </th>

                                    <th style="padding:12px;">
                                        Vehicle
                                    </th>

                                    <th style="padding:12px;">
                                        Year
                                    </th>

                                    <th style="padding:12px;">
                                        Status
                                    </th>

                                    <th style="padding:12px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach ($vehicles as $vehicle): ?>

                                <tr
                                    style="
                                        border-bottom:1px solid #e5e7eb;
                                    "
                                >

                                    <td style="padding:12px;">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $vehicle["PlateNumber"]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td style="padding:12px;">

                                        <?= htmlspecialchars(
                                            $vehicle["VIN"]
                                        ) ?>

                                    </td>


                                    <td style="padding:12px;">

                                        <?= htmlspecialchars(
                                            $vehicle["Make"]
                                        ) ?>

                                        <?= htmlspecialchars(
                                            $vehicle["Model"]
                                        ) ?>

                                    </td>


                                    <td style="padding:12px;">

                                        <?= htmlspecialchars(
                                            $vehicle["VehicleYear"]
                                        ) ?>

                                    </td>


                                    <td style="padding:12px;">

                                        <?= htmlspecialchars(
                                            $vehicle["Status"]
                                        ) ?>

                                    </td>


                                    <td style="padding:12px;">

                                        <a
                                            href="../vehicles/view.php?id=<?= (int) $vehicle["VehicleID"] ?>"
                                            class="button button-secondary"
                                            style="padding:8px 12px;"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div
                        style="
                            padding:30px;
                            text-align:center;
                            color:#6b7280;
                        "
                    >

                        <p>
                            This owner currently has no vehicles
                            registered.
                        </p>

                    </div>

                <?php endif; ?>

            </section>


            <!-- ACTION BUTTONS -->

            <div
                style="
                    display:flex;
                    gap:10px;
                    margin-top:20px;
                    flex-wrap:wrap;
                "
            >

                <a
                    href="index.php"
                    class="button button-secondary"
                >
                    ← Back to Owners
                </a>


                <?php if ($canManageOwners): ?>

                    <a
                        href="edit.php?id=<?= (int) $owner["OwnerID"] ?>"
                        class="button"
                    >
                        Edit Owner
                    </a>

                <?php endif; ?>

            </div>


        </main>


        <?php require_once __DIR__ . "/../includes/footer.php"; ?>

    </div>

</div>

</body>

</html>