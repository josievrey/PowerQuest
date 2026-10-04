<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("hero");

$userId = (int) ($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// GET HERO PROFILE
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM hero_profiles
     WHERE user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$hero = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$hero) {
    die("Hero profile not found.");
}


// =====================================================
// HERO VALUES
// =====================================================

$heroId = (int) $hero["id"];

$level = max(
    1,
    (int) ($hero["level"] ?? 1)
);

$xp = max(
    0,
    (int) ($hero["experience"] ?? 0)
);

$xpNeeded = $level * 100;

$xpProgress = $xpNeeded > 0
    ? min(100, ($xp / $xpNeeded) * 100)
    : 0;

$powerDiscovered = (int) (
    $hero["power_discovered"] ?? 0
);


// =====================================================
// GET ACTIVE QUESTS
// =====================================================

$questQuery = mysqli_prepare(
    $conn,
    "SELECT
        id,
        title,
        description,
        reward_xp,
        points,
        difficulty,
        status,
        created_at
     FROM quests
     WHERE status = 'active'
     ORDER BY created_at DESC"
);

if (!$questQuery) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_execute($questQuery);

$quests = mysqli_stmt_get_result($questQuery);

$totalQuests = mysqli_num_rows($quests);

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
    Hero Dashboard - PowerQuest
</title>


<script src="https://cdn.tailwindcss.com"></script>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>


<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<style>

* {
    font-family: "Poppins", sans-serif;
}

body {

    background:

        radial-gradient(
            circle at top right,
            rgba(124, 58, 237, 0.12),
            transparent 35%
        ),

        #020617;
}

.dashboard-card {
    transition: .25s ease;
}

.dashboard-card:hover {
    transform: translateY(-4px);
}

.logo-glow {

    box-shadow:
        0 0 25px rgba(234,179,8,.25);
}

.hero-glow {

    box-shadow:
        0 0 40px rgba(234,179,8,.18);
}

.xp-bar {
    transition: width .6s ease;
}

.quest-card {
    transition: .25s ease;
}

.quest-card:hover {
    transform: translateY(-3px);
}

</style>

</head>


<body class="bg-slate-950 text-white min-h-screen">


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="
    sticky
    top-0
    z-50
    border-b
    border-slate-800
    bg-slate-900/95
    backdrop-blur
">

<div class="
    max-w-7xl
    mx-auto
    px-6
    py-4
    flex
    items-center
    justify-between
">


    <!-- LOGO -->

    <div class="flex items-center gap-3">

        <div class="
            logo-glow
            w-10
            h-10
            rounded-xl
            bg-gradient-to-br
            from-yellow-400
            to-orange-600
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-bolt
                text-white
            "></i>

        </div>


        <div>

            <h1 class="font-bold text-xl">
                PowerQuest
            </h1>

            <p class="text-xs text-slate-500">
                Hero Dashboard
            </p>

        </div>

    </div>


    <!-- HERO BADGE -->

    <div class="
        hidden
        sm:flex
        items-center
        gap-2
        px-4
        py-2
        rounded-xl
        bg-yellow-500/10
        border
        border-yellow-500/20
        text-yellow-400
        text-sm
        font-semibold
    ">

        <i class="fa-solid fa-shield-halved"></i>

        HERO

    </div>

    <a
    href="profile.php"
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
    <i class="fa-solid fa-user"></i>

    <span class="hidden sm:inline">
        Profile
    </span>
</a>
    <!-- LOGOUT -->

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

        <span class="hidden sm:inline">
            Logout
        </span>

    </a>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="
    max-w-7xl
    mx-auto
    px-5
    md:px-6
    py-10
">


<!-- =====================================================
     HERO PROFILE
===================================================== -->

<div class="
    flex
    flex-col
    sm:flex-row
    sm:items-center
    gap-5
">


    <div class="
        hero-glow
        w-20
        h-20
        rounded-2xl
        bg-gradient-to-br
        from-yellow-400
        via-orange-500
        to-red-600
        flex
        items-center
        justify-center
        shrink-0
    ">

        <i class="
            fa-solid
            fa-shield-halved
            text-white
            text-3xl
        "></i>

    </div>


    <div>

        <div class="
            flex
            items-center
            gap-2
            text-slate-400
            text-sm
        ">

            <i class="
                fa-solid
                fa-bolt
                text-yellow-400
            "></i>

            Welcome, Hero

        </div>


        <h1 class="
            text-4xl
            md:text-5xl
            font-bold
            mt-1
        ">

            <?= htmlspecialchars(
                $_SESSION["name"] ?? "Hero"
            ) ?>

        </h1>


        <p class="text-slate-500 mt-2">

            Your heroic journey continues.

        </p>

    </div>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="
    grid
    grid-cols-1
    md:grid-cols-3
    gap-6
    mt-10
">


    <!-- LEVEL -->

    <div class="
        dashboard-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    ">

        <p class="text-slate-400 text-sm">
            Hero Level
        </p>


        <h2 class="text-4xl font-bold mt-2">

            <?= $level ?>

        </h2>


        <p class="text-xs text-slate-500 mt-1">

            Current hero level

        </p>

    </div>


    <!-- XP -->

    <div class="
        dashboard-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    ">

        <p class="text-slate-400 text-sm">
            Experience
        </p>


        <h2 class="text-4xl font-bold mt-2">

            <?= $xp ?> XP

        </h2>


        <div class="mt-4">

            <div class="
                flex
                justify-between
                text-xs
                text-slate-500
                mb-2
            ">

                <span>
                    Progress to Level <?= $level + 1 ?>
                </span>

                <span>
                    <?= $xp ?> / <?= $xpNeeded ?> XP
                </span>

            </div>


            <div class="
                h-2
                bg-slate-800
                rounded-full
                overflow-hidden
            ">

                <div
                    class="
                        xp-bar
                        h-full
                        bg-gradient-to-r
                        from-blue-500
                        to-cyan-400
                        rounded-full
                    "
                    style="width: <?= $xpProgress ?>%"
                ></div>

            </div>

        </div>

    </div>


    <!-- RANK -->

    <div class="
        dashboard-card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    ">

        <p class="text-slate-400 text-sm">
            Hero Rank
        </p>


        <h2 class="
            text-2xl
            font-bold
            mt-2
            text-yellow-400
        ">

            HERO

        </h2>


        <p class="text-xs text-slate-500 mt-1">
            PowerQuest rank
        </p>

    </div>

</div>


<!-- =====================================================
     POWER DISCOVERY
===================================================== -->

<div class="
    mt-10
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-8
">


    <div class="flex items-center gap-4">

        <div class="
            w-14
            h-14
            rounded-2xl
            bg-purple-500/10
            border
            border-purple-500/20
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-wand-magic-sparkles
                text-purple-400
                text-2xl
            "></i>

        </div>


        <div>

            <h2 class="text-2xl font-bold">
                Power Discovery
            </h2>


            <p class="text-slate-400 text-sm mt-1">
                Discover your unique heroic ability.
            </p>

        </div>

    </div>


<?php if (!$powerDiscovered): ?>


    <div class="
        mt-8
        text-center
        border
        border-dashed
        border-purple-500/30
        bg-purple-500/5
        rounded-2xl
        p-8
    ">


        <div class="
            w-20
            h-20
            mx-auto
            rounded-full
            bg-slate-800
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-question
                text-purple-400
                text-3xl
            "></i>

        </div>


        <h3 class="text-xl font-bold mt-5">

            Your Power Is Still Hidden

        </h3>


        <p class="text-slate-400 text-sm mt-2">

            Every hero has a unique ability.
            Discover yours and begin your journey.

        </p>


        <a
            href="discover_power.php"
            class="
                inline-flex
                items-center
                gap-2
                mt-6
                px-6
                py-3
                rounded-xl
                bg-gradient-to-r
                from-purple-600
                to-blue-600
                hover:from-purple-500
                hover:to-blue-500
                font-semibold
                transition
            "
        >

            <i class="fa-solid fa-wand-magic-sparkles"></i>

            Discover My Power

        </a>

    </div>


<?php else: ?>


    <div class="
        mt-8
        p-6
        rounded-2xl
        border
        border-yellow-500/20
        bg-gradient-to-br
        from-yellow-500/10
        to-orange-500/5
    ">


        <p class="
            text-yellow-400
            text-sm
            font-semibold
        ">

            <i class="fa-solid fa-circle-check"></i>

            POWER DISCOVERED

        </p>


        <h3 class="
            text-3xl
            font-bold
            mt-2
        ">

            <?= htmlspecialchars(
                $hero["power"] ?? "Unknown Power"
            ) ?>

        </h3>


        <p class="text-slate-400 text-sm mt-2">

            Your heroic ability has been awakened.

        </p>

    </div>


<?php endif; ?>

</div>


<!-- =====================================================
     HERO ATTRIBUTES
===================================================== -->

<div class="
    mt-10
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-8
">


    <div class="flex items-center gap-4">

        <div class="
            w-14
            h-14
            rounded-2xl
            bg-blue-500/10
            border
            border-blue-500/20
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-chart-simple
                text-blue-400
                text-2xl
            "></i>

        </div>


        <div>

            <h2 class="text-2xl font-bold">
                Hero Attributes
            </h2>


            <p class="text-slate-400 text-sm">
                Your current hero abilities.
            </p>

        </div>

    </div>


    <div class="
        grid
        grid-cols-2
        md:grid-cols-4
        gap-4
        mt-8
    ">


        <!-- STRENGTH -->

        <div class="
            bg-slate-800
            rounded-xl
            p-5
        ">

            <i class="
                fa-solid
                fa-dumbbell
                text-red-400
                text-xl
            "></i>


            <p class="
                text-slate-400
                text-sm
                mt-3
            ">

                Strength

            </p>


            <h3 class="text-2xl font-bold mt-1">

                <?= (int) ($hero["strength"] ?? 0) ?>

            </h3>

        </div>


        <!-- AGILITY -->

        <div class="
            bg-slate-800
            rounded-xl
            p-5
        ">

            <i class="
                fa-solid
                fa-person-running
                text-green-400
                text-xl
            "></i>


            <p class="
                text-slate-400
                text-sm
                mt-3
            ">

                Agility

            </p>


            <h3 class="text-2xl font-bold mt-1">

                <?= (int) ($hero["agility"] ?? 0) ?>

            </h3>

        </div>


        <!-- INTELLIGENCE -->

        <div class="
            bg-slate-800
            rounded-xl
            p-5
        ">

            <i class="
                fa-solid
                fa-brain
                text-purple-400
                text-xl
            "></i>


            <p class="
                text-slate-400
                text-sm
                mt-3
            ">

                Intelligence

            </p>


            <h3 class="text-2xl font-bold mt-1">

                <?= (int) ($hero["intelligence"] ?? 0) ?>

            </h3>

        </div>


        <!-- DEFENSE -->

        <div class="
            bg-slate-800
            rounded-xl
            p-5
        ">

            <i class="
                fa-solid
                fa-shield
                text-blue-400
                text-xl
            "></i>


            <p class="
                text-slate-400
                text-sm
                mt-3
            ">

                Defense

            </p>


            <h3 class="text-2xl font-bold mt-1">

                <?= (int) ($hero["defense"] ?? 0) ?>

            </h3>

        </div>

    </div>

</div>


<!-- =====================================================
     HERO QUESTS
===================================================== -->

<div class="
    mt-10
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-8
">


    <!-- HEADER -->

    <div class="
        flex
        flex-col
        sm:flex-row
        sm:items-center
        sm:justify-between
        gap-4
    ">


        <div class="flex items-center gap-4">

            <div class="
                w-14
                h-14
                rounded-2xl
                bg-purple-500/10
                border
                border-purple-500/20
                flex
                items-center
                justify-center
            ">

                <i class="
                    fa-solid
                    fa-puzzle-piece
                    text-purple-400
                    text-2xl
                "></i>

            </div>


            <div>

                <h2 class="text-2xl font-bold">
                    Hero Quests
                </h2>


                <p class="text-slate-400 text-sm">
                    Complete quests and earn XP rewards.
                </p>

            </div>

        </div>


        <div class="
            px-4
            py-2
            rounded-xl
            bg-purple-500/10
            border
            border-purple-500/20
            text-purple-400
            text-sm
            font-semibold
        ">

            <i class="fa-solid fa-list-check mr-2"></i>

            <?= $totalQuests ?> Quests

        </div>

    </div>


    <!-- QUEST LIST -->

    <div class="
        mt-8
        grid
        grid-cols-1
        md:grid-cols-2
        gap-5
    ">


<?php if ($totalQuests === 0): ?>


    <!-- NO QUESTS -->

    <div class="
        md:col-span-2
        border
        border-dashed
        border-slate-700
        rounded-2xl
        p-10
        text-center
    ">

        <div class="
            w-20
            h-20
            mx-auto
            rounded-full
            bg-slate-800
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-puzzle-piece
                text-slate-500
                text-3xl
            "></i>

        </div>


        <h3 class="
            text-lg
            font-semibold
            mt-5
        ">

            No Quests Available

        </h3>


        <p class="
            text-slate-500
            text-sm
            mt-2
        ">

            New quests will appear here.

        </p>

    </div>


<?php else: ?>


<?php while (
    $quest = mysqli_fetch_assoc($quests)
): ?>


<?php

$difficulty = $quest["difficulty"] ?? "Easy";

switch ($difficulty) {

    case "Medium":

        $difficultyClass =
            "text-yellow-400 bg-yellow-500/10 border-yellow-500/20";

        $difficultyIcon =
            "fa-fire";

        break;


    case "Hard":

        $difficultyClass =
            "text-orange-400 bg-orange-500/10 border-orange-500/20";

        $difficultyIcon =
            "fa-fire-flame-curved";

        break;


    case "Legendary":

        $difficultyClass =
            "text-red-400 bg-red-500/10 border-red-500/20";

        $difficultyIcon =
            "fa-skull-crossbones";

        break;


    default:

        $difficultyClass =
            "text-green-400 bg-green-500/10 border-green-500/20";

        $difficultyIcon =
            "fa-leaf";

        break;
}

?>


<!-- QUEST CARD -->

<div class="
    quest-card
    bg-slate-800/70
    border
    border-slate-700
    rounded-2xl
    p-6
">


    <!-- ICON -->

    <div class="
        w-14
        h-14
        rounded-2xl
        bg-purple-500/10
        border
        border-purple-500/20
        flex
        items-center
        justify-center
    ">

        <i class="
            fa-solid
            fa-puzzle-piece
            text-purple-400
            text-xl
        "></i>

    </div>


    <!-- TITLE -->

    <h3 class="
        text-xl
        font-bold
        mt-5
    ">

        <?= htmlspecialchars(
            $quest["title"]
        ) ?>

    </h3>


    <!-- DESCRIPTION -->

    <p class="
        text-slate-400
        text-sm
        mt-2
        min-h-[48px]
    ">

        <?= nl2br(
            htmlspecialchars(
                $quest["description"] ?? ""
            )
        ) ?>

    </p>


    <!-- DETAILS -->

    <div class="
        flex
        flex-wrap
        gap-2
        mt-5
    ">


        <!-- DIFFICULTY -->

        <span class="
            inline-flex
            items-center
            gap-2
            px-3
            py-1.5
            rounded-lg
            border
            text-xs
            font-semibold
            <?= $difficultyClass ?>
        ">

            <i class="
                fa-solid
                <?= $difficultyIcon ?>
            "></i>

            <?= htmlspecialchars($difficulty) ?>

        </span>


        <!-- XP -->

        <span class="
            inline-flex
            items-center
            gap-2
            px-3
            py-1.5
            rounded-lg
            border
            border-blue-500/20
            text-blue-400
            bg-blue-500/10
            text-xs
            font-semibold
        ">

            <i class="fa-solid fa-bolt"></i>

            +<?= (int) $quest["reward_xp"] ?> XP

        </span>


        <!-- POINTS -->

        <span class="
            inline-flex
            items-center
            gap-2
            px-3
            py-1.5
            rounded-lg
            border
            border-yellow-500/20
            text-yellow-400
            bg-yellow-500/10
            text-xs
            font-semibold
        ">

            <i class="fa-solid fa-star"></i>

            <?= (int) ($quest["points"] ?? 0) ?> Points

        </span>

    </div>


    <!-- START QUEST -->

    <a
        href="quest.php?id=<?= (int) $quest["id"] ?>"
        class="
            mt-6
            w-full
            inline-flex
            items-center
            justify-center
            gap-2
            px-5
            py-3
            rounded-xl
            bg-purple-600
            hover:bg-purple-500
            font-semibold
            transition
        "
    >

        <i class="fa-solid fa-play"></i>

        Start Quest

    </a>

</div>


<?php endwhile; ?>


<?php endif; ?>


    </div>

</div>


</main>

</body>

</html>