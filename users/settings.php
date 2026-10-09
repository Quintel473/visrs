<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/remember.php";
require_once __DIR__ . "/../includes/password_policy.php";

$basePath = "../";
$activePage = "settings";

$pageTitle = "My Settings";

$currentUserID = (int) currentUserId();

$successMessage = "";
$errorMessage = "";
$errors = [];


/*
|--------------------------------------------------------------------------
| Load Current User
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->prepare("
    SELECT
        UserID,
        FirstName,
        LastName,
        Email,
        Role,
        ProfileImage,
        CreatedAt
    FROM users
    WHERE UserID = ?
    LIMIT 1
");

$userStmt->execute([$currentUserID]);

$user = $userStmt->fetch();

if (!$user) {
    redirect("/visrs/logout.php");
}


/*
|--------------------------------------------------------------------------
| Form Values (default to current values)
|--------------------------------------------------------------------------
*/

$firstName = $user["FirstName"];
$lastName  = $user["LastName"];
$email     = $user["Email"];
$profileImage = $user["ProfileImage"] ?? "";


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    requireValidCSRF();

    $action = $_POST["action"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | ACTION: Update Profile (name + email)
    |--------------------------------------------------------------------------
    */

    if ($action === "update_profile") {

        $firstName = trim($_POST["FirstName"] ?? "");
        $lastName  = trim($_POST["LastName"] ?? "");
        $email     = trim($_POST["Email"] ?? "");


        /*
         * Validate First Name
         */

        if ($firstName === "") {
            $errors[] = "First name is required.";
        } elseif (strlen($firstName) > 100) {
            $errors[] = "First name cannot exceed 100 characters.";
        }


        /*
         * Validate Last Name
         */

        if ($lastName === "") {
            $errors[] = "Last name is required.";
        } elseif (strlen($lastName) > 100) {
            $errors[] = "Last name cannot exceed 100 characters.";
        }


        /*
         * Validate Email
         */

        if ($email === "") {
            $errors[] = "Email address is required.";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        } elseif (strlen($email) > 150) {
            $errors[] = "Email address cannot exceed 150 characters.";
        }


        /*
         * Check email is not used by another account
         */

        if (empty($errors)) {

            $emailCheck = $pdo->prepare("
                SELECT UserID
                FROM users
                WHERE Email = ?
                AND UserID != ?
                LIMIT 1
            ");

            $emailCheck->execute([$email, $currentUserID]);

            if ($emailCheck->fetch()) {
                $errors[] = "That email address is already in use by another account.";
            }
        }


        /*
         * Update if no errors
         */

        if (empty($errors)) {

            try {

                $pdo->beginTransaction();


                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET
                        FirstName = ?,
                        LastName = ?,
                        Email = ?
                    WHERE UserID = ?
                ");

                $updateStmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $currentUserID
                ]);


                /*
                 * Keep session in sync
                 */

                $_SESSION["FirstName"] = $firstName;
                $_SESSION["LastName"]  = $lastName;
                $_SESSION["Email"]     = $email;


                /*
                 * Audit
                 */

                logAudit(
                    $pdo,
                    "Updated own profile information",
                    "users",
                    $currentUserID
                );


                $pdo->commit();

                $successMessage = "Your profile has been updated successfully.";


                /*
                 * Reload user data
                 */

                $user["FirstName"] = $firstName;
                $user["LastName"]  = $lastName;
                $user["Email"]     = $email;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] = "Unable to update your profile. Please try again.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTION: Change Password
    |--------------------------------------------------------------------------
    */

    elseif ($action === "change_password") {

        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword     = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";


        /*
         * Validate current password is provided
         */

        if ($currentPassword === "") {
            $errors[] = "Please enter your current password.";
        }


        /*
         * Validate new password against policy
         */

        if ($newPassword === "") {

            $errors[] = "Please enter a new password.";

        } else {

            $policyError = password_policy_check($newPassword, [
                "email"      => $user["Email"],
                "first name" => $user["FirstName"],
                "last name"  => $user["LastName"]
            ]);

            if ($policyError !== null) {
                $errors[] = $policyError;
            }
        }


        /*
         * Confirm new passwords match
         */

        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        }


        /*
         * Verify current password
         */

        if (empty($errors)) {

            $pwStmt = $pdo->prepare("
                SELECT Password
                FROM users
                WHERE UserID = ?
                LIMIT 1
            ");

            $pwStmt->execute([$currentUserID]);

            $pwRow = $pwStmt->fetch();

            if (!$pwRow || !password_verify($currentPassword, $pwRow["Password"])) {
                $errors[] = "Your current password is incorrect.";
            }
        }


        /*
         * Update password
         */

        if (empty($errors)) {

            try {

                $pdo->beginTransaction();


                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);


                $updateStmt = $pdo->prepare("
                    UPDATE users
                    SET Password = ?
                    WHERE UserID = ?
                ");

                $updateStmt->execute([$hashed, $currentUserID]);


                /*
                 * Revoke all remember-me tokens.
                 *
                 * A password change should log the user out
                 * everywhere else, so any stolen "Remember me"
                 * cookies are invalidated.
                 */

                remember_revoke_all($pdo, $currentUserID);


                logAudit(
                    $pdo,
                    "Changed own account password",
                    "users",
                    $currentUserID
                );


                $pdo->commit();

                $successMessage = "Your password has been changed successfully.";

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] = "Unable to change your password. Please try again.";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTION: Upload Profile Picture
    |--------------------------------------------------------------------------
    */

    elseif ($action === "upload_avatar") {

        if (
            !isset($_FILES["profile_image"]) ||
            $_FILES["profile_image"]["error"] === UPLOAD_ERR_NO_FILE
        ) {

            $errors[] = "Please select an image to upload.";

        } else {

            $file = $_FILES["profile_image"];

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $maximumFileSize = 5 * 1024 * 1024;


            /*
             * Upload errors
             */

            if ($file["error"] !== UPLOAD_ERR_OK) {

                $errors[] = "The image could not be uploaded. Please try again.";

            } elseif ($file["size"] <= 0 || $file["size"] > $maximumFileSize) {

                $errors[] = "The image must be smaller than 5 MB.";

            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file["tmp_name"]);


                if (!in_array($mimeType, $allowedTypes, true)) {

                    $errors[] = "Only JPG, PNG, and WebP images are allowed.";

                } elseif (@getimagesize($file["tmp_name"]) === false) {

                    $errors[] = "The uploaded file is not a valid image.";

                } else {

                    /*
                     * Determine extension
                     */

                    $extension = "jpg";

                    if ($mimeType === "image/png") {
                        $extension = "png";
                    } elseif ($mimeType === "image/webp") {
                        $extension = "webp";
                    }


                    /*
                     * Upload directory
                     */

                    $uploadDirectory = __DIR__ . "/uploads/avatars";

                    if (!is_dir($uploadDirectory)) {

                        if (!mkdir($uploadDirectory, 0755, true)) {
                            $errors[] = "Unable to prepare the upload directory.";
                        }
                    }


                    if (empty($errors)) {

                        try {

                            $pdo->beginTransaction();


                            /*
                             * Save new file
                             */

                            $randomName = bin2hex(random_bytes(16));

                            $fileName =
                                "avatar_"
                                . $currentUserID
                                . "_"
                                . $randomName
                                . "."
                                . $extension;

                            $destination =
                                $uploadDirectory
                                . DIRECTORY_SEPARATOR
                                . $fileName;


                            if (!move_uploaded_file($file["tmp_name"], $destination)) {
                                throw new RuntimeException("Unable to save the uploaded image.");
                            }


                            /*
                             * Delete old avatar file (if any)
                             */

                            $oldImage = $user["ProfileImage"] ?? "";

                            if ($oldImage !== "") {

                                $oldPath =
                                    __DIR__
                                    . "/"
                                    . str_replace(
                                        "users/uploads/avatars/",
                                        "uploads/avatars/",
                                        $oldImage
                                    );

                                if (is_file($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }


                            /*
                             * Update database
                             */

                            $relativePath = "users/uploads/avatars/" . $fileName;


                            $updateStmt = $pdo->prepare("
                                UPDATE users
                                SET ProfileImage = ?
                                WHERE UserID = ?
                            ");

                            $updateStmt->execute([$relativePath, $currentUserID]);


                            /*
                             * Keep session in sync
                             */

                            $_SESSION["ProfileImage"] = $relativePath;


                            /*
                             * Audit
                             */

                            logAudit(
                                $pdo,
                                "Updated profile picture",
                                "users",
                                $currentUserID
                            );


                            $pdo->commit();


                            $user["ProfileImage"] = $relativePath;
                            $profileImage         = $relativePath;

                            $successMessage = "Your profile picture has been updated.";

                        } catch (Throwable $e) {

                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }

                            $errors[] = "Unable to update your profile picture. Please try again.";
                        }
                    }
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTION: Remove Profile Picture
    |--------------------------------------------------------------------------
    */

    elseif ($action === "remove_avatar") {

        try {

            $pdo->beginTransaction();


            /*
             * Delete file (best effort)
             */

            $oldImage = $user["ProfileImage"] ?? "";

            if ($oldImage !== "") {

                $oldPath =
                    __DIR__
                    . "/"
                    . str_replace(
                        "users/uploads/avatars/",
                        "uploads/avatars/",
                        $oldImage
                    );

                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }


            /*
             * Clear database column
             */

            $updateStmt = $pdo->prepare("
                UPDATE users
                SET ProfileImage = NULL
                WHERE UserID = ?
            ");

            $updateStmt->execute([$currentUserID]);


            /*
             * Clear session
             */

            unset($_SESSION["ProfileImage"]);


            /*
             * Audit
             */

            logAudit(
                $pdo,
                "Removed profile picture",
                "users",
                $currentUserID
            );


            $pdo->commit();


            $user["ProfileImage"] = null;
            $profileImage         = "";

            $successMessage = "Your profile picture has been removed.";

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Unable to remove your profile picture. Please try again.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTION: Log out of all devices
    |--------------------------------------------------------------------------
    |
    | Deletes every remember-me token for this user, then
    | destroys the current session so this device logs out
    | as well. The visitor is redirected to the login page.
    |
    */

    elseif ($action === "logout_all_devices") {

        /*
         * Revoke every persistent remember-me token.
         */

        remember_revoke_all($pdo, $currentUserID);


        /*
         * Audit
         */

        logAudit(
            $pdo,
            "Logged out of all devices",
            "users",
            $currentUserID
        );


        /*
         * Clear the remember-me cookie on this device.
         */

        setcookie(
            "VISRS_REMEMBER",
            "",
            [
                "expires"  => time() - 3600,
                "path"     => "/visrs/",
                "httponly" => true,
                "samesite" => "Lax"
            ]
        );


        /*
         * Destroy the active session.
         */

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                [
                    "expires"  => time() - 42000,
                    "path"     => $params["path"],
                    "domain"   => $params["domain"],
                    "secure"   => $params["secure"],
                    "httponly" => $params["httponly"],
                    "samesite" => "Lax"
                ]
            );
        }

        session_destroy();


        /*
         * Redirect to login.
         */

        header("Location: /visrs/login.php");
        exit;
    }
}


/*
 * FIX: Global <head>
 */

$pageTitle = "My Settings";

require_once "../includes/head.php";

?>

<!-- FIX: Page-specific CSS moved here from <head> -->
<style>

    .settings-page {
        max-width: 820px;
        margin: 0 auto;
    }

    .settings-header {
        margin-bottom: 25px;
    }

    .settings-header h1 {
        margin: 0 0 6px;
        font-size: 25px;
        color: var(--text, #111827);
    }

    .settings-header p {
        margin: 0;
        color: var(--text-muted, #64748b);
        font-size: 14px;
    }

    .settings-alert {
        padding: 13px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .settings-alert-success {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .settings-alert-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .settings-alert-error ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    .settings-alert-error li {
        margin-bottom: 4px;
        font-weight: 500;
    }

    .settings-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 2px 7px rgba(0,0,0,0.04);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .settings-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .settings-card-header h2 {
        margin: 0;
        font-size: 17px;
        color: #111827;
    }

    .settings-card-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .settings-card-body {
        padding: 22px;
    }

    /* ---------- Avatar Section ---------- */

    .avatar-section {
        display: flex;
        align-items: center;
        gap: 22px;
        flex-wrap: wrap;
    }

    .avatar-current {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: #111827;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        font-weight: 700;
        flex-shrink: 0;
        overflow: hidden;
        border: 3px solid #e5e7eb;
    }

    .avatar-current img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .avatar-controls {
        flex: 1;
        min-width: 220px;
    }

    .avatar-controls p {
        margin: 0 0 12px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    .avatar-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* ---------- Form Fields ---------- */

    .settings-field {
        margin-bottom: 16px;
    }

    .settings-field:last-child {
        margin-bottom: 0;
    }

    .settings-field label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .settings-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 13px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
        font-family: inherit;
    }

    .settings-field input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
    }

    .settings-field-help {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
    }

    .settings-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    /* ---------- Account Info (read-only) ---------- */

    .settings-info-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
    }

    .settings-info-row:last-child {
        border-bottom: 0;
    }

    .settings-info-label {
        color: #64748b;
        font-weight: 500;
    }

    .settings-info-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }

    /* ---------- Dark Mode ---------- */

    [data-theme="dark"] .settings-card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-theme="dark"] .settings-card-header {
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .settings-card-header h2 {
        color: #f1f5f9;
    }

    [data-theme="dark"] .settings-card-header p,
    [data-theme="dark"] .settings-header p,
    [data-theme="dark"] .settings-field-help,
    [data-theme="dark"] .avatar-controls p {
        color: #94a3b8;
    }

    [data-theme="dark"] .settings-header h1,
    [data-theme="dark"] .settings-info-value,
    [data-theme="dark"] .settings-field label {
        color: #e2e8f0;
    }

    [data-theme="dark"] .avatar-current {
        border-color: #334155;
        background: #1e293b;
    }

    [data-theme="dark"] .settings-field input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-theme="dark"] .settings-info-row {
        border-color: #334155;
    }

    [data-theme="dark"] .settings-info-label {
        color: #94a3b8;
    }

    [data-theme="dark"] .settings-alert-success {
        background: #064e3b;
        border-color: #065f46;
        color: #6ee7b7;
    }

    [data-theme="dark"] .settings-alert-error {
        background: #7f1d1d;
        border-color: #991b1b;
        color: #fecaca;
    }

    @media (max-width: 600px) {

        .avatar-section {
            flex-direction: column;
            text-align: center;
        }

        .avatar-buttons {
            justify-content: center;
        }

        .settings-info-row {
            flex-direction: column;
            gap: 4px;
        }

        .settings-info-value {
            text-align: left;
        }
    }

</style>

<body>

<div class="layout">

    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php require_once __DIR__ . "/../includes/header.php"; ?>

        <main class="content">

            <div class="settings-page">

                <div class="settings-header">

                    <h1>My Settings</h1>

                    <p>
                        Manage your profile information, password, and profile picture.
                    </p>

                </div>


                <!-- =============================================
                     SUCCESS MESSAGE
                     ============================================= -->

                <?php if ($successMessage !== ""): ?>

                    <div class="settings-alert settings-alert-success">

                        <?= e($successMessage) ?>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                     ERROR MESSAGES
                     ============================================= -->

                <?php if (!empty($errors)): ?>

                    <div class="settings-alert settings-alert-error">

                        <strong>Please correct the following:</strong>

                        <ul>

                            <?php foreach ($errors as $err): ?>

                                <li><?= e($err) ?></li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>


                <!-- =============================================
                     PROFILE PICTURE
                     ============================================= -->

                <div class="settings-card">

                    <div class="settings-card-header">

                        <h2>Profile Picture</h2>

                        <p>
                            Upload a photo to appear beside your name across VISRS.
                        </p>

                    </div>

                    <div class="settings-card-body">

                        <div class="avatar-section">

                            <div class="avatar-current">

                                <?php if (!empty($user["ProfileImage"])): ?>

                                    <img
                                        src="/visrs/<?= e($user["ProfileImage"]) ?>"
                                        alt="Profile picture"
                                    >

                                <?php else: ?>

                                    <?= e(
                                        strtoupper(
                                            substr($user["FirstName"], 0, 1)
                                        )
                                    ) ?>

                                <?php endif; ?>

                            </div>


                            <div class="avatar-controls">

                                <p>
                                    JPG, PNG, or WebP. Maximum 5 MB.
                                </p>


                                <form
                                    method="POST"
                                    enctype="multipart/form-data"
                                    style="display:inline;"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(generateCSRFToken()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="upload_avatar"
                                    >

                                    <div class="avatar-buttons">

                                        <label
                                            for="profile_image"
                                            class="button"
                                            style="cursor:pointer;"
                                        >
                                            Choose Photo
                                        </label>

                                        <input
                                            type="file"
                                            id="profile_image"
                                            name="profile_image"
                                            accept="image/jpeg,image/png,image/webp"
                                            style="display:none;"
                                            onchange="this.form.submit();"
                                        >


                                        <?php if (!empty($user["ProfileImage"])): ?>

                                            <button
                                                type="submit"
                                                name="action"
                                                value="remove_avatar"
                                                class="button button-secondary"
                                                onclick="return confirm('Remove your profile picture?');"
                                            >
                                                Remove Photo
                                            </button>

                                        <?php endif; ?>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =============================================
                     PROFILE INFORMATION
                     ============================================= -->

                <div class="settings-card">

                    <div class="settings-card-header">

                        <h2>Profile Information</h2>

                        <p>
                            Update your name and email address.
                        </p>

                    </div>

                    <div class="settings-card-body">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(generateCSRFToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="update_profile"
                            >


                            <div class="settings-field">

                                <label for="FirstName">First Name</label>

                                <input
                                    type="text"
                                    id="FirstName"
                                    name="FirstName"
                                    maxlength="100"
                                    required
                                    value="<?= e($user["FirstName"]) ?>"
                                >

                            </div>


                            <div class="settings-field">

                                <label for="LastName">Last Name</label>

                                <input
                                    type="text"
                                    id="LastName"
                                    name="LastName"
                                    maxlength="100"
                                    required
                                    value="<?= e($user["LastName"]) ?>"
                                >

                            </div>


                            <div class="settings-field">

                                <label for="Email">Email Address</label>

                                <input
                                    type="email"
                                    id="Email"
                                    name="Email"
                                    maxlength="150"
                                    required
                                    value="<?= e($user["Email"]) ?>"
                                >

                                <span class="settings-field-help">
                                    Changing your email will change how you log in.
                                </span>

                            </div>


                            <div class="settings-actions">

                                <button type="submit" class="button">
                                    Save Profile
                                </button>

                            </div>

                        </form>

                    </div>

                </div>


                <!-- =============================================
                     CHANGE PASSWORD
                     ============================================= -->

                <div class="settings-card">

                    <div class="settings-card-header">

                        <h2>Change Password</h2>

                        <p>
                            Choose a strong password you don't use anywhere else.
                        </p>

                    </div>

                    <div class="settings-card-body">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(generateCSRFToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="change_password"
                            >


                            <div class="settings-field">

                                <label for="current_password">Current Password</label>

                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                >

                            </div>


                            <div class="settings-field">

                                <label for="new_password">New Password</label>

                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="8"
                                    maxlength="128"
                                    required
                                    autocomplete="new-password"
                                >

                                <span class="settings-field-help">
                                    At least 8 characters. Avoid common
                                    passwords and don't include your name
                                    or email.
                                </span>

                            </div>


                            <div class="settings-field">

                                <label for="confirm_password">Confirm New Password</label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    maxlength="128"
                                    required
                                    autocomplete="new-password"
                                >

                            </div>


                            <div class="settings-actions">

                                <button type="submit" class="button">
                                    Change Password
                                </button>

                            </div>

                        </form>

                    </div>

                </div>


                <!-- =============================================
                     ACTIVE SESSIONS / LOG OUT ALL DEVICES
                     ============================================= -->

                <div class="settings-card">

                    <div class="settings-card-header">

                        <h2>Active Sessions</h2>

                        <p>
                            Manage devices where you've chosen to stay signed in.
                        </p>

                    </div>

                    <div class="settings-card-body">

                        <p
                            style="
                                margin:0 0 16px 0;
                                color:#64748b;
                                font-size:14px;
                                line-height:1.55;
                            "
                        >
                            Logging out of all devices will end every
                            "Remember me" session on your account,
                            including this one. You will be returned
                            to the login page and will need to sign
                            in again.
                        </p>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(generateCSRFToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="logout_all_devices"
                            >

                            <button
                                type="submit"
                                class="button button-danger"
                                onclick="return confirm('Log out of all devices? You will need to sign in again everywhere.');"
                            >
                                Log out of all devices
                            </button>

                        </form>

                    </div>

                </div>


                <!-- =============================================
                     ACCOUNT INFORMATION (READ-ONLY)
                     ============================================= -->

                <div class="settings-card">

                    <div class="settings-card-header">

                        <h2>Account Information</h2>

                        <p>
                            These details are managed by the system.
                        </p>

                    </div>

                    <div class="settings-card-body">

                        <div class="settings-info-row">

                            <span class="settings-info-label">User ID</span>

                            <span class="settings-info-value">
                                #<?= (int) $user["UserID"] ?>
                            </span>

                        </div>


                        <div class="settings-info-row">

                            <span class="settings-info-label">Role</span>

                            <span class="settings-info-value">
                                <?= e($user["Role"]) ?>
                            </span>

                        </div>


                        <div class="settings-info-row">

                            <span class="settings-info-label">Account Created</span>

                            <span class="settings-info-value">
                                <?= e(formatDateTime($user["CreatedAt"])) ?>
                            </span>

                        </div>

                    </div>

                </div>


            </div>

        </main>

    </main>

</div>


<?php require_once __DIR__ . "/../includes/footer.php"; ?>