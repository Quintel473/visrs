<?php

require_once "../includes/auth.php";
require_once "../includes/database.php";

$basePath = "../";
$activePage = "insurance";

$insuranceID = (int) ($_GET["id"] ?? 0);

if ($insuranceID <= 0) {
    header("Location: index.php");
    exit;
}


/*
 * Get the insurance record and related vehicle.
 */

$stmt = $pdo->prepare("
    SELECT
        i.InsuranceID,
        i.VehicleID,
        i.ProviderName,
        i.PolicyNumber,
        i.CoverageType,
        i.StartDate,
        i.ExpiryDate,
        i.Status,
        i.CreatedAt,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear,
        v.Color,
        v.VehicleType,
        v.EngineNumber,
        v.RegistrationDate,
        v.Status AS VehicleStatus

    FROM insurance i

    INNER JOIN vehicles v
        ON i.VehicleID = v.VehicleID

    WHERE i.InsuranceID = ?
");

$stmt->execute([
    $insuranceID
]);

$insurance = $stmt->fetch();


if (!$insurance) {
    header("Location: index.php");
    exit;
}


/*
 * Format dates for display.
 */

function formatInsuranceDate($date)
{
    if (!$date) {
        return "Not specified";
    }

    return date(
        "F j, Y",
        strtotime($date)
    );
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
        Insurance Details - VISRS
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once "../includes/sidebar.php"; ?>


    <main class="main-content">

        <!-- TOP BAR -->

        <div class="topbar">

            <div>

                <h1>
                    Insurance Details
                </h1>

                <p>
                    View insurance policy information and
                    associated vehicle details.
                </p>

            </div>


            <div class="user-info">

                <div class="user-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION["FirstName"] ?? "U",
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION["FirstName"] ?? ""
                        ) ?>

                        <?= htmlspecialchars(
                            $_SESSION["LastName"] ?? ""
                        ) ?>

                    </strong>

                </div>

            </div>

        </div>


        <!-- CONTENT -->

        <div class="content">


            <!-- PAGE ACTIONS -->

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:10px;
                    flex-wrap:wrap;
                    margin-bottom:20px;
                "
            >

                <div>

                    <h2>
                        Policy Information
                    </h2>

                </div>


                <div
                    style="
                        display:flex;
                        gap:10px;
                        flex-wrap:wrap;
                    "
                >

                    <a
                        href="edit.php?id=<?= $insurance["InsuranceID"] ?>"
                        class="button"
                    >
                        Edit Insurance
                    </a>


                    <a
                        href="delete.php?id=<?= $insurance["InsuranceID"] ?>"
                        class="button"
                        style="
                            background:#991b1b;
                        "
                    >
                        Delete
                    </a>


                    <a
                        href="index.php"
                        class="button button-secondary"
                    >
                        Back to Insurance
                    </a>

                </div>

            </div>


            <!-- POLICY STATUS -->

            <div class="card">

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h2>
                            <?= htmlspecialchars(
                                $insurance["ProviderName"]
                            ) ?>
                        </h2>

                        <p>
                            Policy #
                            <?= htmlspecialchars(
                                $insurance["PolicyNumber"]
                            ) ?>
                        </p>

                    </div>


                    <?php

                    $status =
                        $insurance["Status"];

                    $statusBackground =
                        "#e5e7eb";

                    $statusColor =
                        "#374151";


                    if ($status === "Active") {

                        $statusBackground =
                            "#dcfce7";

                        $statusColor =
                            "#166534";

                    } elseif (
                        $status === "Expired"
                    ) {

                        $statusBackground =
                            "#fee2e2";

                        $statusColor =
                            "#991b1b";

                    } elseif (
                        $status === "Cancelled"
                    ) {

                        $statusBackground =
                            "#fef3c7";

                        $statusColor =
                            "#92400e";

                    }

                    ?>


                    <span
                        style="
                            display:inline-block;
                            padding:8px 16px;
                            border-radius:20px;
                            background:<?= $statusBackground ?>;
                            color:<?= $statusColor ?>;
                            font-weight:600;
                        "
                    >

                        <?= htmlspecialchars(
                            $status
                        ) ?>

                    </span>

                </div>

            </div>


            <!-- INSURANCE INFORMATION -->

            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(280px, 1fr)
                        );
                    gap:20px;
                    margin-top:20px;
                "
            >


                <!-- POLICY CARD -->

                <div class="card">

                    <h2>
                        Insurance Information
                    </h2>


                    <div
                        style="
                            margin-top:20px;
                        "
                    >

                        <p>
                            <strong>
                                Insurance Provider
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["ProviderName"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Policy Number
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["PolicyNumber"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Coverage Type
                            </strong>
                        </p>

                        <p>
                            <?= $insurance["CoverageType"]
                                ? htmlspecialchars(
                                    $insurance["CoverageType"]
                                )
                                : "Not specified"
                            ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Policy Start Date
                            </strong>
                        </p>

                        <p>
                            <?= formatInsuranceDate(
                                $insurance["StartDate"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Policy Expiry Date
                            </strong>
                        </p>

                        <p>
                            <?= formatInsuranceDate(
                                $insurance["ExpiryDate"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Record Created
                            </strong>
                        </p>

                        <p>
                            <?= formatInsuranceDate(
                                $insurance["CreatedAt"]
                            ) ?>
                        </p>

                    </div>

                </div>


                <!-- VEHICLE CARD -->

                <div class="card">

                    <h2>
                        Insured Vehicle
                    </h2>


                    <div
                        style="
                            margin-top:20px;
                        "
                    >

                        <p>
                            <strong>
                                License Plate
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["PlateNumber"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                VIN
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["VIN"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Make
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["Make"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Model
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["Model"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Year
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["VehicleYear"]
                            ) ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <p>
                            <strong>
                                Color
                            </strong>
                        </p>

                        <p>
                            <?= $insurance["Color"]
                                ? htmlspecialchars(
                                    $insurance["Color"]
                                )
                                : "Not specified"
                            ?>
                        </p>

                    </div>


                    <div
                        style="
                            margin-top:25px;
                        "
                    >

                        <a
                            href="../vehicles/view.php?id=<?= $insurance["VehicleID"] ?>"
                            class="button"
                        >
                            View Vehicle
                        </a>

                    </div>

                </div>

            </div>


            <!-- POLICY PERIOD -->

            <div
                class="card"
                style="
                    margin-top:20px;
                "
            >

                <h2>
                    Policy Period
                </h2>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(220px, 1fr)
                            );
                        gap:20px;
                        margin-top:20px;
                    "
                >

                    <div>

                        <p>
                            <strong>
                                Start Date
                            </strong>
                        </p>

                        <p>
                            <?= formatInsuranceDate(
                                $insurance["StartDate"]
                            ) ?>
                        </p>

                    </div>


                    <div>

                        <p>
                            <strong>
                                Expiry Date
                            </strong>
                        </p>

                        <p>
                            <?= formatInsuranceDate(
                                $insurance["ExpiryDate"]
                            ) ?>
                        </p>

                    </div>


                    <div>

                        <p>
                            <strong>
                                Current Status
                            </strong>
                        </p>

                        <p>
                            <?= htmlspecialchars(
                                $insurance["Status"]
                            ) ?>
                        </p>

                    </div>

                </div>

            </div>


        </div>

    </main>

</div>

</body>

</html>