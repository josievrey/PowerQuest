<?php

session_start();

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("hero");

$userId = $_SESSION["user_id"];


// Get hero profile
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM hero_profiles WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$hero = mysqli_fetch_assoc($result);


// If already discovered
if ($hero["power_discovered"] == 1) {

    header("Location: dashboard.php");
    exit;

}


// Available powers

$powers = [

    [
        "name" => "Lightning Manipulation",
        "icon" => "fa-bolt",
        "color" => "yellow",
        "strength" => 8,
        "agility" => 9,
        "intelligence" => 6,
        "defense" => 4
    ],

    [
        "name" => "Fire Control",
        "icon" => "fa-fire",
        "color" => "red",
        "strength" => 9,
        "agility" => 6,
        "intelligence" => 6,
        "defense" => 5
    ],

    [
        "name" => "Ice Manipulation",
        "icon" => "fa-snowflake",
        "color" => "cyan",
        "strength" => 7,
        "agility" => 6,
        "intelligence" => 9,
        "defense" => 7
    ],

    [
        "name" => "Wind Control",
        "icon" => "fa-wind",
        "color" => "sky",
        "strength" => 6,
        "agility" => 10,
        "intelligence" => 7,
        "defense" => 4
    ],

    [
        "name" => "Energy Shield",
        "icon" => "fa-shield-halved",
        "color" => "blue",
        "strength" => 5,
        "agility" => 5,
        "intelligence" => 8,
        "defense" => 10
    ],

    [
        "name" => "Super Strength",
        "icon" => "fa-hand-fist",
        "color" => "orange",
        "strength" => 10,
        "agility" => 5,
        "intelligence" => 4,
        "defense" => 9
    ]

];


// Random power

$power = $powers[array_rand($powers)];


// Save power

$update = mysqli_prepare(
    $conn,
    "UPDATE hero_profiles
     SET
        power = ?,
        power_discovered = 1,
        strength = ?,
        agility = ?,
        intelligence = ?,
        defense = ?
     WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $update,
    "siiiii",
    $power["name"],
    $power["strength"],
    $power["agility"],
    $power["intelligence"],
    $power["defense"],
    $userId
);

mysqli_stmt_execute($update);


// Redirect dashboard

header("Location: dashboard.php?power=discovered");

exit;

?>