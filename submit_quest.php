<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("hero");


// =====================================================
// GET LOGGED-IN HERO
// =====================================================

$userId = (int) ($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}


// =====================================================
// CHECK POST REQUEST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}


// =====================================================
// GET QUEST ID
// =====================================================

$questId = (int) ($_POST["quest_id"] ?? 0);

$answers = $_POST["answers"] ?? [];

if ($questId <= 0) {
    die("Invalid quest.");
}


// =====================================================
// GET HERO
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM hero_profiles
     WHERE user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
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


if (!$hero) {
    die("Hero profile not found.");
}

$heroId = (int) $hero["id"];


// =====================================================
// GET QUEST
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        title,
        reward_xp,
        points
     FROM quests
     WHERE id = ?
     AND status = 'active'
     LIMIT 1"
);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $questId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$quest = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$quest) {
    die("Quest not found or inactive.");
}


// =====================================================
// GET QUESTIONS
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        correct_answer
     FROM quest_questions
     WHERE quest_id = ?
     ORDER BY id ASC"
);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $questId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// =====================================================
// CHECK ANSWERS
// =====================================================

$totalQuestions = 0;
$correctAnswers = 0;

while ($question = mysqli_fetch_assoc($result)) {

    $questionId = (int) $question["id"];

    $correctAnswer = strtoupper(
        trim($question["correct_answer"])
    );

    $userAnswer = strtoupper(
        trim($answers[$questionId] ?? "")
    );


    $totalQuestions++;


    if (
        $userAnswer !== ""
        &&
        $userAnswer === $correctAnswer
    ) {

        $correctAnswers++;

    }

}

mysqli_stmt_close($stmt);


// =====================================================
// CALCULATE SCORE
// =====================================================

$percentage = 0;

if ($totalQuestions > 0) {

    $percentage =
        ($correctAnswers / $totalQuestions) * 100;

}


// =====================================================
// XP REWARD
// =====================================================

$rewardXP = (int) $quest["reward_xp"];


// Only give reward if there are questions
if ($totalQuestions <= 0) {
    die("This quest has no questions.");
}


// =====================================================
// DATABASE TRANSACTION
// =====================================================

mysqli_begin_transaction($conn);

try {


    // =================================================
    // ADD XP
    // =================================================

    $updateHero = mysqli_prepare(
        $conn,
        "UPDATE hero_profiles
         SET experience = experience + ?
         WHERE id = ?"
    );

    if (!$updateHero) {
        throw new Exception(
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $updateHero,
        "ii",
        $rewardXP,
        $heroId
    );

    if (!mysqli_stmt_execute($updateHero)) {

        throw new Exception(
            mysqli_stmt_error($updateHero)
        );

    }

    mysqli_stmt_close($updateHero);


    // =================================================
    // COMMIT
    // =================================================

    mysqli_commit($conn);


} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Failed to submit quest: "
        . htmlspecialchars($e->getMessage())
    );

}


// =====================================================
// SHOW RESULT
// =====================================================

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
            circle at top,
            rgba(124, 58, 237, .15),
            transparent 40%
        ),
        #020617;
}

</style>

</head>


<body class="
    min-h-screen
    bg-slate-950
    text-white
    flex
    items-center
    justify-center
    p-6
">


<div class="
    w-full
    max-w-xl
    bg-slate-900
    border
    border-slate-800
    rounded-3xl
    p-8
    text-center
">


    <!-- ICON -->

    <div class="
        w-24
        h-24
        mx-auto
        rounded-full
        bg-green-500/10
        border
        border-green-500/20
        flex
        items-center
        justify-center
    ">

        <i class="
            fa-solid
            fa-circle-check
            text-green-400
            text-5xl
        "></i>

    </div>


    <!-- TITLE -->

    <h1 class="
        text-3xl
        font-bold
        mt-6
    ">

        Quest Completed!

    </h1>


    <p class="
        text-slate-400
        mt-2
    ">

        <?= htmlspecialchars(
            $quest["title"]
        ) ?>

    </p>


    <!-- SCORE -->

    <div class="
        mt-8
        bg-slate-800
        rounded-2xl
        p-6
    ">

        <p class="
            text-slate-400
            text-sm
        ">

            Your Score

        </p>


        <h2 class="
            text-5xl
            font-bold
            mt-2
        ">

            <?= $correctAnswers ?>
            /
            <?= $totalQuestions ?>

        </h2>


        <p class="
            text-purple-400
            font-semibold
            mt-2
        ">

            <?= round($percentage) ?>%

        </p>

    </div>


    <!-- REWARD -->

    <div class="
        mt-5
        bg-blue-500/10
        border
        border-blue-500/20
        rounded-2xl
        p-5
    ">

        <p class="
            text-slate-400
            text-sm
        ">

            XP Reward

        </p>


        <p class="
            text-3xl
            font-bold
            text-blue-400
            mt-1
        ">

            +<?= $rewardXP ?> XP

        </p>

    </div>


    <!-- BUTTON -->

    <a
        href="dashboard.php"
        class="
            mt-8
            w-full
            inline-flex
            items-center
            justify-center
            gap-2
            px-6
            py-3
            rounded-xl
            bg-purple-600
            hover:bg-purple-500
            font-semibold
            transition
        "
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>

</div>


</body>

</html>