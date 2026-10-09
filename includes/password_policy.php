<?php

/*
 * VISRS Password Policy
 *
 * Validates new passwords before hashing.
 * Follows NIST SP 800-63B guidance:
 *   - length is the primary factor
 *   - blocks common passwords
 *   - blocks user-specific strings (email, name)
 *   - no arbitrary composition rules
 */


/* ---------- Configuration ---------- */

const PW_MIN_LENGTH = 8;
const PW_MAX_LENGTH = 128;


/**
 * Validate a new password.
 * Returns null if valid, or a string error message.
 *
 * $context is an optional array of user-specific
 * strings that must NOT appear in the password,
 * e.g. ["email" => $email, "first name" => $name].
 */
function password_policy_check(
    string $password,
    array $context = []
): ?string {

    /*
     * Length
     */

    if (strlen($password) < PW_MIN_LENGTH) {

        return "Password must be at least "
            . PW_MIN_LENGTH
            . " characters long.";
    }

    if (strlen($password) > PW_MAX_LENGTH) {

        return "Password cannot exceed "
            . PW_MAX_LENGTH
            . " characters.";
    }


    /*
     * Do not allow the password to be entirely whitespace.
     */

    if (trim($password) === "") {

        return "Password cannot be blank.";
    }


    /*
     * Common password check.
     */

    if (password_is_common($password)) {

        return "That password is too common. "
            . "Please choose something less predictable.";
    }


    /*
     * Context check — password must not contain
     * the user's email, first name, or last name.
     */

    $lower = strtolower($password);

    foreach ($context as $key => $value) {

        if (!is_string($value) || $value === "") {
            continue;
        }

        $needle = strtolower(trim($value));

        if (strlen($needle) < 3) {
            continue;
        }

        if (strpos($lower, $needle) !== false) {

            return "Password must not contain your "
                . htmlspecialchars($key)
                . ".";
        }
    }


    return null;
}


/**
 * Is this a known-common password?
 *
 * Uses a small embedded list of the most common
 * passwords. For production, swap for a file-based
 * list or an API lookup.
 */
function password_is_common(string $password): bool
{
    static $common = null;

    if ($common === null) {

        $common = [

            /* Top offenders */
            "password", "password1", "password123",
            "12345678", "123456789", "1234567890",
            "qwerty", "qwerty123", "qwertyuiop",
            "abc123", "letmein", "welcome",
            "admin", "administrator", "root",
            "iloveyou", "monkey", "dragon",
            "sunshine", "princess", "football",
            "baseball", "starwars", "master",
            "login", "passw0rd", "p@ssw0rd",

            /* Common VISRS-relevant words */
            "visrs", "vehicle", "cars", "car",
            "police", "officer", "seller", "admin",

            /* Years + common words */
            "welcome1", "welcome123",
            "admin123", "root123",
            "changeme", "changeme1"
        ];
    }

    return in_array(strtolower($password), $common, true);
}

?>