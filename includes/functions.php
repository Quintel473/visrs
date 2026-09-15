<?php

/*
 * VISRS Global Functions
 *
 * Reusable helper functions used throughout
 * the Vehicle Information Search & Retrieval System.
 */


/* =========================================================
   SECURITY / OUTPUT HELPERS
   ========================================================= */

/**
 * Safely escape output for HTML.
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/**
 * Sanitize a basic text value.
 */
function cleanInput($value)
{
    return trim(
        htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


/**
 * Redirect to another page and stop execution.
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}


/* =========================================================
   FLASH MESSAGES
   ========================================================= */

/**
 * Store a temporary session message.
 */
function setFlashMessage($type, $message)
{
    $_SESSION["flash_message"] = [
        "type" => $type,
        "message" => $message
    ];
}


/**
 * Retrieve and remove the temporary session message.
 */
function getFlashMessage()
{
    if (!isset($_SESSION["flash_message"])) {
        return null;
    }

    $message = $_SESSION["flash_message"];

    unset($_SESSION["flash_message"]);

    return $message;
}


/**
 * Display the current flash message.
 */
function displayFlashMessage()
{
    $message = getFlashMessage();

    if (!$message) {
        return;
    }

    $type = e($message["type"]);
    $text = e($message["message"]);

    $class = "alert-info";

    if ($type === "success") {
        $class = "alert-success";
    } elseif ($type === "error") {
        $class = "alert-error";
    } elseif ($type === "warning") {
        $class = "alert-warning";
    }
    ?>

    <div class="alert <?= $class ?> auto-dismiss">
        <?= $text ?>
    </div>

    <?php
}


/* =========================================================
   DATE / TIME HELPERS
   ========================================================= */

/**
 * Format a database date.
 */
function formatDate($date)
{
    if (empty($date)) {
        return "—";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date("M d, Y", $timestamp);
}


/**
 * Format a database timestamp.
 */
function formatDateTime($dateTime)
{
    if (empty($dateTime)) {
        return "—";
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return e($dateTime);
    }

    return date("M d, Y h:i A", $timestamp);
}


/* =========================================================
   VALIDATION HELPERS
   ========================================================= */

/**
 * Check whether an email address is valid.
 */
function isValidEmail($email)
{
    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}


/**
 * Check whether a value is an integer.
 */
function isValidInteger($value)
{
    return filter_var(
        $value,
        FILTER_VALIDATE_INT
    ) !== false;
}


/**
 * Check whether a date is valid.
 */
function isValidDate($date)
{
    if (empty($date)) {
        return false;
    }

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

    return $dateObject !== false &&
        $dateObject->format("Y-m-d") === $date;
}


/**
 * Check whether a string is within a specified length.
 */
function isWithinLength(
    $value,
    $minimum = 0,
    $maximum = 255
) {
    $length = strlen((string) $value);

    return $length >= $minimum &&
        $length <= $maximum;
}


/* =========================================================
   ROLE HELPERS
   ========================================================= */

/**
 * Get the currently logged-in user's role.
 */
function currentUserRole()
{
    return $_SESSION["Role"] ?? null;
}


/**
 * Get the currently logged-in user's ID.
 */
function currentUserId()
{
    return $_SESSION["UserID"] ?? null;
}


/**
 * Check whether the current user has one of
 * the specified roles.
 */
function hasRole($roles)
{
    $currentRole = currentUserRole();

    if (!$currentRole) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array(
        $currentRole,
        $roles,
        true
    );
}


/**
 * Check whether the current user is an administrator.
 */
function isCurrentUserAdmin()
{
    return currentUserRole() === "Admin";
}


/* =========================================================
   AUDIT LOGGING
   ========================================================= */

/**
 * Create an audit log entry.
 *
 * Requires the database connection from database.php.
 */
function logAudit(
    $pdo,
    $action,
    $tableAffected = null,
    $recordId = null
) {
    $userId = $_SESSION["UserID"] ?? null;

    $ipAddress = $_SERVER["REMOTE_ADDR"] ?? null;

    $sql = "
        INSERT INTO audit_logs
        (
            UserID,
            Action,
            TableAffected,
            RecordID,
            IPAddress
        )
        VALUES
        (
            :user_id,
            :action,
            :table_affected,
            :record_id,
            :ip_address
        )
    ";

    $statement = $pdo->prepare($sql);

    $statement->execute([
        ":user_id" => $userId,
        ":action" => $action,
        ":table_affected" => $tableAffected,
        ":record_id" => $recordId,
        ":ip_address" => $ipAddress
    ]);
}


/* =========================================================
   DATABASE HELPERS
   ========================================================= */

/**
 * Determine whether a database record exists.
 */
function recordExists(
    $pdo,
    $table,
    $column,
    $value
) {
    $allowedTables = [
        "users",
        "owners",
        "vehicles",
        "ownership_history",
        "insurance",
        "accidents",
        "audit_logs"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $allowedColumns = [
        "UserID",
        "OwnerID",
        "VehicleID",
        "OwnershipID",
        "InsuranceID",
        "AccidentID",
        "LogID",
        "Email",
        "PlateNumber",
        "VIN"
    ];

    if (!in_array($column, $allowedColumns, true)) {
        return false;
    }

    $sql = "
        SELECT COUNT(*)
        FROM `$table`
        WHERE `$column` = :value
    ";

    $statement = $pdo->prepare($sql);

    $statement->execute([
        ":value" => $value
    ]);

    return (int) $statement->fetchColumn() > 0;
}


/* =========================================================
   STATUS HELPERS
   ========================================================= */

/**
 * Return a CSS class for a common status value.
 */
function statusClass($status)
{
    switch ($status) {

        case "Active":
            return "status-active";

        case "Inactive":
            return "status-inactive";

        case "Stolen":
            return "status-stolen";

        case "Sold":
            return "status-sold";

        case "Expired":
            return "status-expired";

        case "Cancelled":
            return "status-cancelled";

        case "Open":
            return "status-open";

        case "Closed":
            return "status-closed";

        default:
            return "status-default";
    }
}


/**
 * Return a CSS class for accident damage levels.
 */
function damageLevelClass($damageLevel)
{
    switch ($damageLevel) {

        case "Minor":
            return "damage-minor";

        case "Moderate":
            return "damage-moderate";

        case "Major":
            return "damage-major";

        case "Severe":
            return "damage-severe";

        default:
            return "damage-default";
    }
}


/* =========================================================
   DISPLAY HELPERS
   ========================================================= */

/**
 * Display a safe value or an em dash when empty.
 */
function displayValue($value)
{
    if (
        $value === null ||
        $value === ""
    ) {
        return "—";
    }

    return e($value);
}


/**
 * Return a person's full name.
 */
function fullName($firstName, $lastName)
{
    return trim(
        $firstName . " " . $lastName
    );
}


/* =========================================================
   APPLICATION HELPERS
   ========================================================= */

/**
 * Return the current VISRS application URL.
 */
function appUrl($path = "")
{
    return "/visrs/" . ltrim(
        $path,
        "/"
    );
}


/**
 * Determine whether the current request is POST.
 */
function isPostRequest()
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}


/**
 * Determine whether the current request is GET.
 */
function isGetRequest()
{
    return $_SERVER["REQUEST_METHOD"] === "GET";
}

?>