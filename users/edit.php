<?php

require_once __DIR__ . "/../includes/auth.php";
requireRole(["Admin"]);

require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";

$pageTitle = "Edit User";
$pageSubtitle = "Update VISRS user account information";

$userID = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($userID <= 0) {

    header("Location: index.php");
    exit;

}


/*
 * Retrieve the user.
 */

$stmt = $pdo->prepare("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Role
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$stmt->execute([
    $userID
]);

$user = $stmt->fetch();


if (!$user) {

    header("Location: index.php");
    exit;

}


/*
 * Store current values.
 */

$firstName = $user["FirstName"];
$lastName = $user["LastName"];
$email = $user["Email"];
$role = $user["Role"];

$errors = [];


/*
 * Process form.
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["FirstName"] ?? "");
    $lastName = trim($_POST["LastName"] ?? "");
    $email = trim($_POST["Email"] ?? "");
    $password = $_POST["Password"] ?? "";
    $confirmPassword = $_POST["ConfirmPassword"] ?? "";
    $role = $_POST["Role"] ?? "User";


    /*
     * Validate first name.
     */

    if ($firstName === "") {

        $errors[] = "First name is required.";

    }


    /*
     * Validate last name.
     */

    if ($lastName === "") {

        $errors[] = "Last name is required.";

    }


    /*
     * Validate email.
     */

    if ($email === "") {

        $errors[] = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    /*
     * Validate role.
     */

    $validRoles = [
        "Admin",
        "Police",
        "Seller",
        "User"
    ];

    if (!in_array($role, $validRoles, true)) {

        $errors[] = "Invalid user role selected.";

    }


    /*
     * Password is optional when editing.
     *
     * If the Admin leaves it blank, the existing
     * password remains unchanged.
     */

    if ($password !== "") {

        if (strlen($password) < 8) {

            $errors[] =
                "New password must be at least 8 characters long.";

        }

        if ($password !== $confirmPassword) {

            $errors[] =
                "New passwords do not match.";

        }

    }


    /*
     * Prevent duplicate email addresses.
     */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT UserID
            FROM users
            WHERE Email = ?
            AND UserID != ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $userID
        ]);

        if ($stmt->fetch()) {

            $errors[] =
                "Another user already uses this email address.";

        }

    }


    /*
     * Prevent the currently logged-in Admin
     * from accidentally removing their own Admin role.
     */

    $currentUserID =
        (int) ($_SESSION["UserID"] ?? 0);

    if (
        $userID === $currentUserID &&
        $role !== "Admin"
    ) {

        $errors[] =
            "You cannot remove the Admin role from your own account.";

    }


    /*
     * Update user.
     */

    if (empty($errors)) {

        if ($password !== "") {

            /*
             * Update everything including password.
             */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    FirstName = ?,
                    LastName = ?,
                    Email = ?,
                    Password = ?,
                    Role = ?
                WHERE UserID = ?
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $hashedPassword,
                $role,
                $userID
            ]);

        } else {

            /*
             * Update everything except password.
             */

            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    FirstName = ?,
                    LastName = ?,
                    Email = ?,
                    Role = ?
                WHERE UserID = ?
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $role,
                $userID
            ]);

        }


        /*
         * Return to the user details page.
         */

        header(
            "Location: view.php?id=" . $userID . "&success=updated"
        );

        exit;

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

    <?php

    $activePage = "users";

    include __DIR__ . "/../includes/sidebar.php";

    ?>


    <main class="main-content">

        <?php include __DIR__ . "/../includes/header.php"; ?>


        <div class="content">

            <div class="card">

                <!-- PAGE HEADER -->

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        margin-bottom:25px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h1 class="page-title">
                            Edit User
                        </h1>

                        <p style="
                            color:#6b7280;
                            margin:0;
                        ">
                            Update this user's account information.
                        </p>

                    </div>


                    <a
                        href="view.php?id=<?= $userID ?>"
                        class="button button-secondary"
                    >
                        ← Back to User
                    </a>

                </div>


                <!-- ERRORS -->

                <?php if (!empty($errors)): ?>

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
                            Please correct the following:
                        </strong>

                        <ul
                            style="
                                margin:10px 0 0 20px;
                            "
                        >

                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?= htmlspecialchars($error) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>


                <!-- EDIT FORM -->

                <form
                    method="POST"
                    action=""
                >


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label for="FirstName">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="FirstName"
                            name="FirstName"
                            value="<?= htmlspecialchars($firstName) ?>"
                            placeholder="Enter first name"
                            required
                            class="auto-focus"
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label for="LastName">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="LastName"
                            name="LastName"
                            value="<?= htmlspecialchars($lastName) ?>"
                            placeholder="Enter last name"
                            required
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
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="example@email.com"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="Password">
                            New Password
                        </label>

                        <div
                            style="
                                display:flex;
                                gap:10px;
                                align-items:center;
                            "
                        >

                            <input
                                type="password"
                                id="Password"
                                name="Password"
                                placeholder="Leave blank to keep current password"
                                minlength="8"
                                style="flex:1;"
                            >


                            <button
                                type="button"
                                class="button button-secondary"
                                data-toggle-password="Password"
                            >
                                Show
                            </button>

                        </div>


                        <small
                            style="
                                display:block;
                                color:#6b7280;
                                margin-top:6px;
                            "
                        >
                            Leave blank if you do not want to change the password.
                        </small>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="form-group">

                        <label for="ConfirmPassword">
                            Confirm New Password
                        </label>

                        <div
                            style="
                                display:flex;
                                gap:10px;
                                align-items:center;
                            "
                        >

                            <input
                                type="password"
                                id="ConfirmPassword"
                                name="ConfirmPassword"
                                placeholder="Re-enter new password"
                                minlength="8"
                                style="flex:1;"
                            >


                            <button
                                type="button"
                                class="button button-secondary"
                                data-toggle-password="ConfirmPassword"
                            >
                                Show
                            </button>

                        </div>

                    </div>


                    <!-- ROLE -->

                    <div class="form-group">

                        <label for="Role">
                            User Role
                        </label>


                        <div
                            style="
                                position:relative;
                            "
                        >

                            <select
                                id="Role"
                                name="Role"
                                required
                                style="
                                    width:100%;
                                    padding:12px 42px 12px 14px;
                                    border:1px solid #d1d5db;
                                    border-radius:8px;
                                    background:#ffffff;
                                    color:#111827;
                                    font-size:15px;
                                    font-family:Arial, sans-serif;
                                    cursor:pointer;
                                    appearance:none;
                                    -webkit-appearance:none;
                                    -moz-appearance:none;
                                    box-sizing:border-box;
                                "
                            >

                                <option
                                    value="User"
                                    <?= $role === "User" ? "selected" : "" ?>
                                >
                                    User
                                </option>


                                <option
                                    value="Seller"
                                    <?= $role === "Seller" ? "selected" : "" ?>
                                >
                                    Seller
                                </option>


                                <option
                                    value="Police"
                                    <?= $role === "Police" ? "selected" : "" ?>
                                >
                                    Police
                                </option>


                                <option
                                    value="Admin"
                                    <?= $role === "Admin" ? "selected" : "" ?>
                                >
                                    Administrator
                                </option>

                            </select>


                            <span
                                style="
                                    position:absolute;
                                    right:14px;
                                    top:50%;
                                    transform:translateY(-50%);
                                    pointer-events:none;
                                    color:#6b7280;
                                    font-size:14px;
                                    line-height:1;
                                "
                            >
                                ▼
                            </span>

                        </div>


                        <small
                            style="
                                display:block;
                                color:#6b7280;
                                margin-top:6px;
                            "
                        >
                            Choose the permissions this account should have.
                        </small>

                    </div>


                    <!-- BUTTONS -->

                    <div
                        style="
                            display:flex;
                            gap:12px;
                            margin-top:25px;
                            flex-wrap:wrap;
                        "
                    >

                        <button
                            type="submit"
                            class="button"
                        >
                            Save Changes
                        </button>


                        <a
                            href="view.php?id=<?= $userID ?>"
                            class="button button-secondary"
                        >
                            Cancel
                        </a>

                    </div>


                </form>

            </div>

        </div>

    </main>

</div>


<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>
</html>