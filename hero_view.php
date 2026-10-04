<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");


// =====================================================
// GET USER ID
// =====================================================

$userId = (int) ($_GET["id"] ?? 0);

if ($userId <= 0) {
    header("Location: heroes.php");
    exit;
}


// =====================================================
// GET HERO PROFILE
// =====================================================

$stmt = mysqli_prepare(
    $conn,
  "SELECT
    u.id AS user_id,
    u.name,

    hp.id AS hero_id,
    hp.hero_name,
    hp.power,
    hp.level,
    hp.experience,
    hp.power_discovered,
    hp.strength,
    hp.agility,
    hp.intelligence,
    hp.defense

    FROM users u

    INNER JOIN hero_profiles hp
        ON hp.user_id = u.id

    WHERE u.id = ?
    AND u.role = 'hero'

    LIMIT 1"
);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$hero = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// HERO NOT FOUND
// =====================================================

if (!$hero) {
    die("Hero profile not found.");
}


// =====================================================
// SAFE VALUES
// =====================================================

$heroName = $hero["hero_name"] ?: "Unnamed Hero";
$name = $hero["name"] ?: "Unknown User";

$power = $hero["power"] ?: "Not Discovered";

$level = (int) $hero["level"];
$experience = (int) $hero["experience"];

$strength = (int) $hero["strength"];
$agility = (int) $hero["agility"];
$intelligence = (int) $hero["intelligence"];
$defense = (int) $hero["defense"];

$powerDiscovered = (int) $hero["power_discovered"];


// =====================================================
// LEVEL PROGRESS
// 100 XP PER LEVEL
// =====================================================

$xpIntoLevel = $experience % 100;

$xpProgress = $xpIntoLevel;

if ($xpProgress < 0) {
    $xpProgress = 0;
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
<?= htmlspecialchars($heroName) ?> - Hero Profile
</title>


<!-- Tailwind -->

<script src="https://cdn.tailwindcss.com"></script>


<!-- Font Awesome -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- Poppins -->

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

* {
    font-family: "Poppins", sans-serif;
}

.stat-card {
    transition: .2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    border-color: #475569;
}

</style>

</head>


<body class="bg-slate-950 text-white min-h-screen">


<!-- =====================================================
     NAVBAR
====================================================== -->

<nav
    class="
        sticky
        top-0
        z-50
        bg-slate-900/95
        backdrop-blur
        border-b
        border-slate-800
    "
>

<div
    class="
        max-w-6xl
        mx-auto
        px-6
        py-4
        flex
        items-center
        justify-between
    "
>


<!-- LOGO -->

<div class="flex items-center gap-3">

<div
    class="
        w-10
        h-10
        rounded-xl
        bg-gradient-to-br
        from-purple-500
        to-blue-600
        flex
        items-center
        justify-center
    "
>

<i class="fa-solid fa-bolt"></i>

</div>


<div>

<h1 class="font-bold text-xl">
PowerQuest
</h1>

<p class="text-xs text-slate-500">
Administration
</p>

</div>

</div>


<!-- BACK -->

<a
    href="heroes.php"
    class="
        flex
        items-center
        gap-2
        px-4
        py-2
        rounded-xl
        text-slate-300
        hover:bg-slate-800
        transition
    "
>

<i class="fa-solid fa-arrow-left"></i>

Back to Heroes

</a>

</div>

</nav>



<!-- =====================================================
     MAIN
====================================================== -->

<main
    class="
        max-w-6xl
        mx-auto
        px-6
        py-10
    "
>


<!-- =====================================================
     PROFILE HEADER
====================================================== -->

<div
    class="
        bg-slate-900
        border
        border-slate-800
        rounded-3xl
        overflow-hidden
    "
>


<!-- COVER -->

<div
    class="
        h-32
        bg-gradient-to-r
        from-blue-600
        via-purple-600
        to-indigo-600
    "
></div>


<!-- PROFILE -->

<div class="px-8 pb-8">


<div
    class="
        flex
        flex-col
        md:flex-row
        md:items-end
        justify-between
        gap-6
    "
>


<!-- AVATAR + NAME -->

<div
    class="
        flex
        flex-col
        sm:flex-row
        sm:items-end
        gap-5
        -mt-12
    "
>


<!-- AVATAR -->

<div
    class="
        w-24
        h-24
        rounded-3xl
        bg-gradient-to-br
        from-yellow-400
        to-orange-600
        border-4
        border-slate-900
        flex
        items-center
        justify-center
        shadow-xl
    "
>

<i
    class="
        fa-solid
        fa-shield-halved
        text-4xl
        text-white
    "
></i>

</div>


<div class="pb-1">

<div
    class="
        inline-flex
        items-center
        gap-2
        px-3
        py-1
        rounded-lg
        bg-yellow-500/10
        border
        border-yellow-500/20
        text-yellow-400
        text-xs
        font-semibold
        mb-2
    "
>

<i class="fa-solid fa-user-shield"></i>

HERO

</div>


<h1 class="text-3xl font-bold">

<?= htmlspecialchars($heroName) ?>

</h1>


<p class="text-slate-400">

<?= htmlspecialchars($name) ?>

</p>

</div>

</div>


<!-- LEVEL -->

<div
    class="
        px-5
        py-3
        rounded-2xl
        bg-yellow-500/10
        border
        border-yellow-500/20
        text-center
    "
>

<p class="text-xs text-slate-500">
CURRENT LEVEL
</p>

<p class="text-3xl font-bold text-yellow-400">

<?= $level ?>

</p>

</div>


</div>



<!-- =====================================================
     XP BAR
====================================================== -->

<div class="mt-8">

<div
    class="
        flex
        items-center
        justify-between
        mb-2
    "
>

<p class="text-sm text-slate-400">

<i class="fa-solid fa-bolt text-yellow-400 mr-2"></i>

Experience

</p>


<p class="text-sm font-semibold text-green-400">

<?= $experience ?> XP

</p>

</div>


<div
    class="
        h-3
        bg-slate-800
        rounded-full
        overflow-hidden
    "
>

<div
    class="
        h-full
        bg-gradient-to-r
        from-blue-500
        to-purple-500
        rounded-full
    "
    style="width: <?= $xpProgress ?>%;"
></div>

</div>


<p class="text-xs text-slate-500 mt-2">

<?= $xpIntoLevel ?> / 100 XP toward next level

</p>

</div>


</div>

</div>



<!-- =====================================================
     BASIC INFORMATION
====================================================== -->

<div class="mt-8">

<h2
    class="
        text-xl
        font-bold
        mb-4
    "
>

<i class="fa-solid fa-circle-info text-blue-400 mr-2"></i>

Hero Information

</h2>


<div
    class="
        grid
        grid-cols-1
        md:grid-cols-3
        gap-4
    "
>


<!-- HERO NAME -->

<div
    class="
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-10
        h-10
        rounded-xl
        bg-yellow-500/10
        text-yellow-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-mask"></i>

</div>


<p class="text-xs text-slate-500">
Hero Name
</p>

<p class="font-semibold mt-1">

<?= htmlspecialchars($heroName) ?>

</p>

</div>


<!-- CIVILIAN NAME -->

<div
    class="
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-10
        h-10
        rounded-xl
        bg-blue-500/10
        text-blue-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-user"></i>

</div>


<p class="text-xs text-slate-500">
Real Name
</p>

<p class="font-semibold mt-1">

<?= htmlspecialchars($name) ?>

</p>

</div>


<!-- POWER -->

<div
    class="
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-10
        h-10
        rounded-xl
        bg-purple-500/10
        text-purple-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-wand-sparkles"></i>

</div>


<p class="text-xs text-slate-500">
Super Power
</p>

<p class="font-semibold text-purple-400 mt-1">

<?= htmlspecialchars($power) ?>

</p>

</div>


</div>

</div>



<!-- =====================================================
     HERO STATS
====================================================== -->

<div class="mt-10">

<h2
    class="
        text-xl
        font-bold
        mb-4
    "
>

<i class="fa-solid fa-chart-simple text-green-400 mr-2"></i>

Hero Statistics

</h2>


<div
    class="
        grid
        grid-cols-2
        md:grid-cols-4
        gap-4
    "
>


<!-- STRENGTH -->

<div
    class="
        stat-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-11
        h-11
        rounded-xl
        bg-red-500/10
        text-red-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-dumbbell"></i>

</div>


<p class="text-sm text-slate-500">
Strength
</p>

<p class="text-3xl font-bold mt-1">

<?= $strength ?>

</p>

</div>


<!-- AGILITY -->

<div
    class="
        stat-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-11
        h-11
        rounded-xl
        bg-cyan-500/10
        text-cyan-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-person-running"></i>

</div>


<p class="text-sm text-slate-500">
Agility
</p>

<p class="text-3xl font-bold mt-1">

<?= $agility ?>

</p>

</div>


<!-- INTELLIGENCE -->

<div
    class="
        stat-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-11
        h-11
        rounded-xl
        bg-purple-500/10
        text-purple-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-brain"></i>

</div>


<p class="text-sm text-slate-500">
Intelligence
</p>

<p class="text-3xl font-bold mt-1">

<?= $intelligence ?>

</p>

</div>


<!-- DEFENSE -->

<div
    class="
        stat-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-5
    "
>

<div
    class="
        w-11
        h-11
        rounded-xl
        bg-green-500/10
        text-green-400
        flex
        items-center
        justify-center
        mb-4
    "
>

<i class="fa-solid fa-shield"></i>

</div>


<p class="text-sm text-slate-500">
Defense
</p>

<p class="text-3xl font-bold mt-1">

<?= $defense ?>

</p>

</div>


</div>

</div>



<!-- =====================================================
     POWER DISCOVERY
====================================================== -->

<div
    class="
        mt-8
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    "
>


<div class="flex items-center gap-4">


<div
    class="
        w-14
        h-14
        rounded-2xl
        flex
        items-center
        justify-center

        <?= $powerDiscovered
            ? "bg-green-500/10 text-green-400"
            : "bg-slate-800 text-slate-500"
        ?>
    "
>

<?php if ($powerDiscovered): ?>

<i class="fa-solid fa-bolt text-2xl"></i>

<?php else: ?>

<i class="fa-solid fa-lock text-2xl"></i>

<?php endif; ?>

</div>


<div>

<p class="text-xs text-slate-500">
POWER DISCOVERY
</p>


<h3 class="text-xl font-bold mt-1">

<?php if ($powerDiscovered): ?>

<span class="text-green-400">

Power Discovered

</span>

<?php else: ?>

<span class="text-slate-400">

Power Not Yet Discovered

</span>

<?php endif; ?>

</h3>


<p class="text-sm text-slate-500 mt-1">

<?php if ($powerDiscovered): ?>

This hero has discovered their special power.

<?php else: ?>

This hero has not discovered their special power yet.

<?php endif; ?>

</p>

</div>


</div>

</div>



<!-- =====================================================
     ACCOUNT INFORMATION
====================================================== -->

<div class="mt-8">

<h2
    class="
        text-xl
        font-bold
        mb-4
    "
>

<i class="fa-solid fa-id-card text-blue-400 mr-2"></i>

Account Information

</h2>


<div
    class="
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        divide-y
        divide-slate-800
    "
>


<!-- USER ID -->

<div
    class="
        px-6
        py-4
        flex
        justify-between
        gap-4
    "
>

<span class="text-slate-500">

User ID

</span>

<span class="font-semibold">

#<?= (int)$hero["user_id"] ?>

</span>

</div>


<!-- HERO ID -->

<div
    class="
        px-6
        py-4
        flex
        justify-between
        gap-4
    "
>

<span class="text-slate-500">

Hero Profile ID

</span>

<span class="font-semibold">

#<?= (int)$hero["hero_id"] ?>

</span>

</div>


<!-- ROLE -->

<div
    class="
        px-6
        py-4
        flex
        justify-between
        gap-4
    "
>

<span class="text-slate-500">

Role

</span>

<span
    class="
        px-3
        py-1
        rounded-lg
        bg-yellow-500/10
        text-yellow-400
        text-sm
        font-semibold
    "
>

Hero

</span>

</div>


</div>

</div>



<!-- =====================================================
     ACTIONS
====================================================== -->

<div
    class="
        mt-8
        flex
        flex-col
        sm:flex-row
        gap-3
    "
>


<a
    href="heroes.php"
    class="
        flex-1
        text-center
        py-3
        rounded-xl
        bg-slate-800
        hover:bg-slate-700
        font-semibold
        transition
    "
>

<i class="fa-solid fa-arrow-left mr-2"></i>

Back to Heroes

</a>


<a
    href="edit_hero.php?id=<?= (int)$hero["user_id"] ?>"
    class="
        flex-1
        text-center
        py-3
        rounded-xl
        bg-blue-600
        hover:bg-blue-500
        font-semibold
        transition
    "
>

<i class="fa-solid fa-pen-to-square mr-2"></i>

Edit Hero Profile

</a>


</div>


</main>


</body>

</html>

