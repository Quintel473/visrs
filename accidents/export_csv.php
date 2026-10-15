<?php

/*
 * VISRS — Export Accident Records to CSV
 *
 * Streams a CSV file of accident records to the browser.
 * Honours the same filters used on the list page.
 */

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/database.php";
require_once __DIR__ . "/../includes/functions.php";


/* ---------- Read filter state ---------- */

$filterFrom    = trim($_GET["from"]    ?? "");
$filterTo      = trim($_GET["to"]      ?? "");
$filterDamage  = trim($_GET["damage"]  ?? "");
$filterVehicle = trim($_GET["vehicle"] ?? "");

$validDamageLevels = ["Minor", "Moderate", "Major", "Severe"];

if (!in_array($filterDamage, $validDamageLevels, true)) {
    $filterDamage = "";
}

if ($filterFrom !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) {
    $filterFrom = "";
}

if ($filterTo !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) {
    $filterTo = "";
}


/* ---------- Build WHERE clause (same logic as list) ---------- */

$whereParts  = [];
$whereParams = [];

if ($filterFrom !== "") {
    $whereParts[]  = "a.AccidentDate >= ?";
    $whereParams[] = $filterFrom;
}

if ($filterTo !== "") {
    $whereParts[]  = "a.AccidentDate <= ?";
    $whereParams[] = $filterTo;
}

if ($filterDamage !== "") {
    $whereParts[]  = "a.DamageLevel = ?";
    $whereParams[] = $filterDamage;
}

if ($filterVehicle !== "") {
    $whereParts[]  = "(
        v.PlateNumber LIKE ?
        OR v.Make LIKE ?
        OR v.Model LIKE ?
        OR CONCAT(v.Make, ' ', v.Model) LIKE ?
    )";
    $like = "%" . $filterVehicle . "%";
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}

$whereSql = $whereParts
    ? "WHERE " . implode(" AND ", $whereParts)
    : "";


/* ---------- Fetch all matching records ---------- */

$stmt = $pdo->prepare("
    SELECT
        a.AccidentID,
        a.AccidentDate,
        a.Location,
        a.DamageLevel,
        a.ReportNumber,
        a.Description,
        a.CreatedAt,

        v.PlateNumber,
        v.VIN,
        v.Make,
        v.Model,
        v.VehicleYear

    FROM accidents a

    INNER JOIN vehicles v ON a.VehicleID = v.VehicleID

    {$whereSql}

    ORDER BY a.AccidentDate DESC, a.AccidentID DESC
");

$stmt->execute($whereParams);
$rows = $stmt->fetchAll();


/* ---------- Log the export action ---------- */

logAudit(
    $pdo,
    "Exported accident records to CSV (" . count($rows) . " rows)",
    "accidents",
    0
);


/* ---------- Stream the CSV ---------- */

$filename = "accidents_export_"
    . date("Y-m-d_His")
    . ".csv";

header("Content-Type: text/csv; charset=utf-8");
header(
    "Content-Disposition: attachment; filename=\""
    . $filename
    . "\""
);
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

/* UTF-8 BOM so Excel opens it correctly */
fwrite($output, "\xEF\xBB\xBF");


/* Header row */

fputcsv($output, [
    "Accident ID",
    "Date",
    "Plate Number",
    "VIN",
    "Make",
    "Model",
    "Year",
    "Location",
    "Damage Level",
    "Report Number",
    "Description",
    "Record Created"
]);


/* Data rows */

foreach ($rows as $r) {

    fputcsv($output, [
        $r["AccidentID"],
        $r["AccidentDate"],
        $r["PlateNumber"],
        $r["VIN"],
        $r["Make"],
        $r["Model"],
        $r["VehicleYear"],
        $r["Location"],
        $r["DamageLevel"],
        $r["ReportNumber"],
        $r["Description"],
        $r["CreatedAt"]
    ]);
}

fclose($output);
exit;