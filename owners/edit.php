<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin", "Police"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Edit Owner";
$pageSubtitle = "Update the owner's contact and registration information";

$activePage = "owners";

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| Get Owner ID
|--------------------------------------------------------------------------
*/

$ownerID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["OwnerID"] ?? 0);

if ($ownerID <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Retrieve Existing Owner
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

$stmt->execute([
    $ownerID
]);

$owner = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Owner Does Not Exist
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

        <title>
            Owner Not Found - VISRS
        </title>

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
                        The owner may have been removed or the
                        requested record does not exist.
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
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();


    /*
    |--------------------------------------------------------------------------
    | Verify Submitted Owner ID
    |--------------------------------------------------------------------------
    */

    $submittedOwnerID = (int) ($_POST["OwnerID"] ?? 0);

    if ($submittedOwnerID !== $ownerID) {

        http_response_code(400);

        $error = "Invalid owner record.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Get Submitted Values
        |--------------------------------------------------------------------------
        */

        $firstName = trim($_POST["FirstName"] ?? "");
        $lastName = trim($_POST["LastName"] ?? "");
        $address = trim($_POST["Address"] ?? "");
        $phone = trim($_POST["Phone"] ?? "");
        $email = trim($_POST["Email"] ?? "");


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if ($firstName === "" || $lastName === "") {

            $error = "First name and last name are required.";

        } elseif (strlen($firstName) > 100) {

            $error = "First name cannot exceed 100 characters.";

        } elseif (strlen($lastName) > 100) {

            $error = "Last name cannot exceed 100 characters.";

        } elseif (strlen($address) > 255) {

            $error = "Address cannot exceed 255 characters.";

        } elseif (strlen($phone) > 30) {

            $error = "Phone number cannot exceed 30 characters.";

        } elseif (strlen($email) > 150) {

            $error = "Email address cannot exceed 150 characters.";

        } elseif (
            $email !== "" &&
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $error = "Please enter a valid email address.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update Owner + Audit Log
            |--------------------------------------------------------------------------
            */

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Update Owner
                |--------------------------------------------------------------------------
                */

                $updateStmt = $pdo->prepare("
                    UPDATE owners
                    SET
                        FirstName = ?,
                        LastName = ?,
                        Address = ?,
                        Phone = ?,
                        Email = ?
                    WHERE OwnerID = ?
                ");

                $updateStmt->execute([
                    $firstName,
                    $lastName,
                    $address !== "" ? $address : null,
                    $phone !== "" ? $phone : null,
                    $email !== "" ? $email : null,
                    $ownerID
                ]);


                /*
                |--------------------------------------------------------------------------
                | Record Audit Log
                |--------------------------------------------------------------------------
                */

                $auditStmt = $pdo->prepare("
                    INSERT INTO audit_logs (
                        UserID,
                        Action,
                        TableAffected,
                        RecordID,
                        IPAddress
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $auditStmt->execute([
                    (int) $_SESSION["UserID"],
                    "Updated owner",
                    "owners",
                    $ownerID,
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
                | Success
                |--------------------------------------------------------------------------
                */

                $success =
                    "Owner information updated successfully.";


                /*
                |--------------------------------------------------------------------------
                | Reload Updated Owner
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

                $stmt->execute([
                    $ownerID
                ]);

                $owner = $stmt->fetch();


            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    "Unable to update the owner record. Please try again.";
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

</head>

<body>

<div class="layout">

    <?php include __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>

        <div class="content">

            <h1 class="page-title">
                Edit Owner
            </h1>

            <p class="page-subtitle">
                Update the owner's contact and registration information.
            </p>


            <!-- SUCCESS MESSAGE -->

            <?php if ($success !== ""): ?>

                <div
                    class="auto-dismiss"
                    style="
                        background:#dcfce7;
                        color:#166534;
                        padding:15px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>

                <div
                    style="
                        background:#fee2e2;
                        color:#991b1b;
                        padding:15px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- EDIT FORM -->

            <section class="card">

                <h2>
                    Owner Information
                </h2>

                <form
                    method="POST"
                    action=""
                    style="margin-top:20px;"
                >

                    <!-- OWNER ID -->

                    <input
                        type="hidden"
                        name="OwnerID"
                        value="<?= (int) $owner["OwnerID"] ?>"
                    >


                    <!-- CSRF TOKEN -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(generateCSRFToken()) ?>"
                    >


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label for="FirstName">
                            First Name *
                        </label>

                        <input
                            type="text"
                            id="FirstName"
                            name="FirstName"
                            value="<?= htmlspecialchars($owner["FirstName"]) ?>"
                            maxlength="100"
                            autocomplete="given-name"
                            required
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label for="LastName">
                            Last Name *
                        </label>

                        <input
                            type="text"
                            id="LastName"
                            name="LastName"
                            value="<?= htmlspecialchars($owner["LastName"]) ?>"
                            maxlength="100"
                            autocomplete="family-name"
                            required
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="Address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="Address"
                            name="Address"
                            value="<?= htmlspecialchars($owner["Address"] ?? "") ?>"
                            maxlength="255"
                            autocomplete="street-address"
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="Phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="Phone"
                            name="Phone"
                            value="<?= htmlspecialchars($owner["Phone"] ?? "") ?>"
                            maxlength="30"
                            autocomplete="tel"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="Email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="Email"
                            name="Email"
                            value="<?= htmlspecialchars($owner["Email"] ?? "") ?>"
                            maxlength="150"
                            autocomplete="email"
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:20px;
                            flex-wrap:wrap;
                        "
                    >

                        <a
                            href="view.php?id=<?= (int) $ownerID ?>"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="button"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>