<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("civilian");


// =====================================================
// GET RESULT
// =====================================================

if (!isset($_SESSION["quest_result"])) {

    header("Location: dashboard.php");
    exit;
}

$result = $_SESSION["quest_result"];


// =====================================================
// CLEAR RESULT AFTER READING
// =====================================================

unset($_SESSION["quest_result"]);


$title = $result["title"] ?? "Quest";

$score = (int) ($result["score"] ?? 0);

$total = (int) ($result["total"] ?? 0);

$percentage = (int) ($result["percentage"] ?? 0);

$earnedXP = (int) ($result["earned_xp"] ?? 0);

$civicPower = (int) ($result["civic_power_earned"] ?? 0);

$newExperience = (int) ($result["new_experience"] ?? 0);

$oldLevel = (int) ($result["old_level"] ?? 1);

$newLevel = (int) ($result["new_level"] ?? 1);

$isPerfect = !empty($result["perfect"]);

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
    Quest Result - PowerQuest
</title>

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

body {

    background:
        radial-gradient(
            circle at top right,
            rgba(124, 58, 237, .15),
            transparent 35%
        ),
        #020617;

}


/* =====================================================
   CONFETTI
===================================================== */

.confetti {

    position: fixed;

    width: 10px;
    height: 18px;

    top: -30px;

    z-index: 9999;

    animation:
        fall 4s linear forwards,
        spin 1s linear infinite;

}


@keyframes fall {

    to {

        transform:
            translateY(110vh)
            rotate(720deg);

    }

}


@keyframes spin {

    from {
        margin-left: 0;
    }

    to {
        margin-left: 20px;
    }

}


/* =====================================================
   PERFECT
===================================================== */

.perfect-icon {

    animation:
        bouncePerfect 1s infinite alternate;

}


@keyframes bouncePerfect {

    from {

        transform:
            scale(1)
            rotate(-5deg);

    }

    to {

        transform:
            scale(1.12)
            rotate(5deg);

    }

}

</style>

</head>

<body
    class="
        min-h-screen
        text-white
        flex
        items-center
        justify-center
        px-6
        py-10
    "
>

<?php if ($isPerfect): ?>

<!-- =====================================================
     CONFETTI
====================================================== -->

<div id="confettiContainer"></div>

<script>

function createConfetti() {

    const container =
        document.getElementById(
            "confettiContainer"
        );

    for (
        let i = 0;
        i < 100;
        i++
    ) {

        const confetti =
            document.createElement("div");

        confetti.className =
            "confetti";

        confetti.style.left =
            Math.random() * 100 + "vw";

        confetti.style.animationDelay =
            Math.random() * 2 + "s";

        confetti.style.width =
            (Math.random() * 8 + 6) + "px";

        confetti.style.height =
            (Math.random() * 12 + 10) + "px";

        const colors = [

            "#ef4444",
            "#f59e0b",
            "#22c55e",
            "#3b82f6",
            "#a855f7",
            "#ec4899"

        ];

        confetti.style.background =
            colors[
                Math.floor(
                    Math.random() *
                    colors.length
                )
            ];

        container.appendChild(confetti);

    }

}

createConfetti();

</script>

<?php endif; ?>

<!-- =====================================================
     RESULT CARD
====================================================== -->

<div
    class="
        w-full
        max-w-2xl
        bg-slate-900
        border
        border-slate-800
        rounded-3xl
        p-8
        text-center
    "
>

<?php if ($isPerfect): ?>

<!-- =====================================================
     PERFECT RESULT
====================================================== -->

<div
    class="
        perfect-icon
        w-24
        h-24
        mx-auto
        rounded-full
        bg-yellow-500/10
        border
        border-yellow-500/20
        flex
        items-center
        justify-center
    "
>

<i
class="
fa-solid
fa-trophy
text-yellow-400
text-5xl
"

> </i>

</div>

<h1
    class="
        text-4xl
        font-extrabold
        mt-6
        text-yellow-400
    "
>

🎉 PERFECT! 🎉

</h1>

<p class="text-slate-300 mt-3">

Amazing! You answered every question correctly!

</p>

<?php else: ?>

<!-- =====================================================
     NORMAL RESULT
====================================================== -->

<div
    class="
        w-24
        h-24
        mx-auto
        rounded-full
        bg-blue-500/10
        border
        border-blue-500/20
        flex
        items-center
        justify-center
    "
>

<i
class="
fa-solid
fa-flag-checkered
text-blue-400
text-4xl
"

> </i>

</div>

<h1
    class="
        text-3xl
        font-bold
        mt-6
    "
>

Quest Completed!

</h1>

<p class="text-slate-400 mt-2">

Good job! Keep completing quests to gain more XP.

</p>

<?php endif; ?>

<!-- =====================================================
     TITLE
====================================================== -->

<p
    class="
        text-slate-500
        text-sm
        mt-6
    "
>

QUEST

</p>

<h2
    class="
        text-xl
        font-bold
        mt-1
    "
>

<?= htmlspecialchars($title) ?>

</h2>

<!-- =====================================================
     SCORE
====================================================== -->

<div
    class="
        mt-8
        p-6
        rounded-2xl
        bg-slate-800
    "
>

<p
    class="
        text-xs
        text-slate-500
        uppercase
    "
>

Your Score

</p>

<p
    class="
        text-5xl
        font-extrabold
        mt-2
        text-blue-400
    "
>

<?= $score ?>

<span class="text-2xl text-slate-500">
    / <?= $total ?>
</span>

</p>

<p
    class="
        mt-2
        font-semibold
    "
>

<?= $percentage ?>%

</p>

</div>

<!-- =====================================================
     REWARDS
====================================================== -->

<div
    class="
        grid
        grid-cols-2
        gap-4
        mt-5
    "
>

<div
    class="
        p-5
        rounded-2xl
        bg-yellow-500/10
        border
        border-yellow-500/20
    "
>

<i
class="
fa-solid
fa-bolt
text-yellow-400
text-xl
"

> </i>

<p
    class="
        text-xs
        text-slate-500
        mt-2
    "
>

XP EARNED

</p>

<p
    class="
        text-2xl
        font-bold
        text-yellow-400
    "
>

+<?= $earnedXP ?>

</p>

</div>

<div
    class="
        p-5
        rounded-2xl
        bg-purple-500/10
        border
        border-purple-500/20
    "
>

<i
class="
fa-solid
fa-star
text-purple-400
text-xl
"

> </i>

<p
    class="
        text-xs
        text-slate-500
        mt-2
    "
>

CIVIC POWER

</p>

<p
    class="
        text-2xl
        font-bold
        text-purple-400
    "
>

+<?= $civicPower ?>

</p>

</div>

</div>

<!-- =====================================================
     LEVEL
====================================================== -->

<div
    class="
        mt-5
        p-5
        rounded-2xl
        bg-slate-800
    "
>

<div
    class="
        flex
        items-center
        justify-between
    "
>

<div class="text-left">

<p class="text-xs text-slate-500">
    CURRENT LEVEL
</p>

<p class="text-xl font-bold">
    Level <?= $newLevel ?>
</p>

</div>

<?php if ($newLevel > $oldLevel): ?>

<div
    class="
        px-4
        py-2
        rounded-xl
        bg-green-500/10
        text-green-400
        font-bold
    "
>

<i class="fa-solid fa-arrow-up mr-1"></i>

LEVEL UP!

</div>

<?php endif; ?>

</div>

</div>

<!-- =====================================================
     BUTTON
====================================================== -->

<a
href="dashboard.php"
class="
block
w-full
mt-7
py-4
rounded-xl
bg-gradient-to-r
from-blue-600
to-purple-600
hover:from-blue-500
hover:to-purple-500
font-bold
transition
"

>

<i class="fa-solid fa-arrow-left mr-2"></i>

Back to Dashboard

</a>

</div>

</body>

</html>
