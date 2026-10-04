<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");


// =====================================================
// GET ALL HEROES
// =====================================================

$sql = "
    SELECT
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

    WHERE u.role = 'hero'

    ORDER BY hp.level DESC, hp.experience DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
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

<title>Hero Management - PowerQuest</title>


<script src="https://cdn.tailwindcss.com"></script>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

* {
    font-family: "Poppins", sans-serif;
}

.hero-card {
    transition: .25s ease;
}

.hero-card:hover {
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
        max-w-7xl
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


<!-- NAV LINKS -->

<div class="flex items-center gap-3">

<a
    href="dashboard.php"
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

Dashboard

</a>


<a
    href="../logout.php"
    class="
        flex
        items-center
        gap-2
        px-4
        py-2
        rounded-xl
        text-red-400
        hover:bg-red-500/10
        transition
    "
>

<i class="fa-solid fa-right-from-bracket"></i>

Logout

</a>

</div>

</div>

</nav>



<!-- =====================================================
     MAIN
====================================================== -->

<main
    class="
        max-w-7xl
        mx-auto
        px-6
        py-10
    "
>


<!-- HEADER -->

<div class="mb-8">

<div
    class="
        flex
        items-center
        gap-2
        text-yellow-400
        text-sm
        font-semibold
    "
>

<i class="fa-solid fa-shield-halved"></i>

HERO MANAGEMENT

</div>


<h1
    class="
        text-4xl
        md:text-5xl
        font-bold
        mt-2
    "
>

All Heroes

</h1>


<p class="text-slate-400 mt-2">

Monitor heroes, their powers, statistics,
and progression.

</p>

</div>



<!-- HERO COUNT -->

<div
    class="
        mb-6
        inline-flex
        items-center
        gap-2
        px-4
        py-2
        rounded-xl
        bg-slate-900
        border
        border-slate-800
        text-slate-300
    "
>

<i class="fa-solid fa-users text-yellow-400"></i>

Total Heroes:

<strong class="text-white">

<?= mysqli_num_rows($result) ?>

</strong>

</div>



<!-- =====================================================
     HERO LIST
====================================================== -->

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">


<?php if (mysqli_num_rows($result) > 0): ?>


<?php while ($hero = mysqli_fetch_assoc($result)): ?>


<!-- HERO CARD -->

<div
    class="
        hero-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    "
>


<!-- HERO HEADER -->

<div
    class="
        flex
        items-start
        justify-between
        gap-4
    "
>


<div class="flex items-center gap-4">


<!-- ICON -->

<div
    class="
        w-14
        h-14
        rounded-2xl
        bg-gradient-to-br
        from-yellow-400
        to-orange-600
        flex
        items-center
        justify-center
    "
>

<i
    class="
        fa-solid
        fa-shield-halved
        text-xl
    "
></i>

</div>


<!-- NAME -->

<div>

<h2 class="text-xl font-bold">

<?= htmlspecialchars(
    $hero["hero_name"] ?: $hero["name"]
) ?>

</h2>


<p class="text-slate-500 text-sm">

<?= htmlspecialchars(
    $hero["name"]
) ?>

</p>

</div>

</div>


<!-- LEVEL -->

<div
    class="
        px-3
        py-1
        rounded-lg
        bg-yellow-500/10
        border
        border-yellow-500/20
        text-yellow-400
        text-sm
        font-semibold
    "
>

LEVEL <?= (int)$hero["level"] ?>

</div>

</div>



<!-- =====================================================
     HERO INFORMATION
====================================================== -->

<div
    class="
        grid
        grid-cols-2
        md:grid-cols-4
        gap-3
        mt-6
    "
>


<!-- POWER -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Power
</p>

<p class="font-semibold text-blue-400 mt-1">

<?= htmlspecialchars(
    $hero["power"] ?: "Unknown"
) ?>

</p>

</div>


<!-- EXPERIENCE -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Experience
</p>

<p class="font-semibold text-green-400 mt-1">

<?= (int)$hero["experience"] ?> XP

</p>

</div>


<!-- STRENGTH -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Strength
</p>

<p class="font-semibold mt-1">

<?= (int)$hero["strength"] ?>

</p>

</div>


<!-- AGILITY -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Agility
</p>

<p class="font-semibold mt-1">

<?= (int)$hero["agility"] ?>

</p>

</div>


<!-- INTELLIGENCE -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Intelligence
</p>

<p class="font-semibold mt-1">

<?= (int)$hero["intelligence"] ?>

</p>

</div>


<!-- DEFENSE -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Defense
</p>

<p class="font-semibold mt-1">

<?= (int)$hero["defense"] ?>

</p>

</div>


<!-- POWER DISCOVERED -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Power Status
</p>

<p
    class="
        font-semibold
        mt-1
        <?= $hero["power_discovered"]
            ? "text-green-400"
            : "text-slate-500"
        ?>
    "
>

<?php if ($hero["power_discovered"]): ?>

<i class="fa-solid fa-check-circle mr-1"></i>

Discovered

<?php else: ?>

<i class="fa-solid fa-lock mr-1"></i>

Undiscovered

<?php endif; ?>

</p>

</div>


<!-- HERO ID -->

<div
    class="
        bg-slate-800/70
        rounded-xl
        p-3
    "
>

<p class="text-xs text-slate-500">
Hero ID
</p>

<p class="font-semibold mt-1">

#<?= (int)$hero["hero_id"] ?>

</p>

</div>


</div>



<!-- =====================================================
     FOOTER
====================================================== -->

<div
    class="
        mt-6
        pt-5
        border-t
        border-slate-800
        flex
        items-center
        justify-between
    "
>


<div
    class="
        flex
        items-center
        gap-2
        text-sm
        text-slate-500
    "
>

<i class="fa-solid fa-chart-line"></i>

Hero Progress

</div>


<a
    href="hero_view.php?id=<?= (int)$hero["user_id"] ?>"
    class="
        px-4
        py-2
        rounded-xl
        bg-blue-600
        hover:bg-blue-500
        text-sm
        font-semibold
        transition
    "
>

<i class="fa-solid fa-eye mr-1"></i>

View Profile

</a>

</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<!-- NO HEROES -->

<div
    class="
        lg:col-span-2
        text-center
        py-16
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
    "
>

<i
    class="
        fa-solid
        fa-shield-halved
        text-slate-600
        text-5xl
    "
></i>


<h2 class="text-xl font-bold mt-5">

No Heroes Found

</h2>


<p class="text-slate-500 mt-2">

There are currently no registered heroes.

</p>

</div>


<?php endif; ?>


</div>

</main>


</body>

</html>
```
