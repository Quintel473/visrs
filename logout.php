<?php

/*
 * VISRS Logout
 *
 * Destroys the active session and revokes any
 * persistent remember-me token so that logging out
 * actually logs the user out — everywhere.
 */


/*
|--------------------------------------------------------------------------
| Session Start
|--------------------------------------------------------------------------
|
| Required so we can read the session cookie and the
| remember-me cookie that belong to this visitor.
|
*/

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Revoke Remember-Me Token
|--------------------------------------------------------------------------
|
| Deletes the database row for this visitor's token
| and clears the cookie. Safe to call even if no
| token exists.
|
*/

require_once __DIR__ . "/includes/database.php";

require_once __DIR__ . "/includes/remember.php";

remember_revoke($pdo);


/*
|--------------------------------------------------------------------------
| Clear Login Greeting Cookie
|--------------------------------------------------------------------------
|
| NEW: Clears the cookie that remembers the user's 
| name for the login page greeting.
|
*/

setcookie("last_logged_in_user", "", time() - 3600, "/");


/*
|--------------------------------------------------------------------------
| Destroy Session
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header("Location: /visrs/login.php");

exit;

?>