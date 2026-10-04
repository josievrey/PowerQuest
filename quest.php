<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("hero");

$userId = (int) ($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}

$questId = (int) ($_GET["id"] ?? 0);

if ($questId <= 0) {
    header("Location: dashboard.php");
    exit;
}


// GET QUEST
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM quests
     WHERE id = ?
     AND status = 'active'
     LIMIT 1"
);

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
    die("Quest not found.");
}


// GET QUESTIONS
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM quest_questions
     WHERE quest_id = ?
     ORDER BY id ASC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $questId
);

mysqli_stmt_execute($stmt);

$questionsResult = mysqli_stmt_get_result($stmt);

$questions = [];

while ($row = mysqli_fetch_assoc($questionsResult)) {
    $questions[] = $row;
}

mysqli_stmt_close($stmt);


if (count($questions) === 0) {
    die("No questions found for this quest.");
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
    <?= htmlspecialchars($quest["title"]) ?> - PowerQuest
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
            rgba(124, 58, 237, 0.12),
            transparent 35%
        ),
        #020617;
}

.question-card {
    transition: .2s ease;
}

.question-card:hover {
    transform: translateY(-2px);
}

.option {
    transition: .2s ease;
}

.option:hover {
    border-color: rgb(59 130 246);
    background: rgba(59,130,246,.08);
}

</style>

</head>

<body class="bg-slate-950 text-white min-h-screen">

<nav class="
    border-b
    border-slate-800
    bg-slate-900/95
    backdrop-blur
">

<div class="
    max-w-4xl
    mx-auto
    px-6
    py-4
    flex
    items-center
    justify-between
">

    <div class="flex items-center gap-3">

        <div class="
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

            <i class="fa-solid fa-bolt"></i>

        </div>

        <div>

            <h1 class="font-bold">
                PowerQuest
            </h1>

            <p class="text-xs text-slate-500">
                Hero Quest
            </p>

        </div>

    </div>

    <a
        href="dashboard.php"
        class="text-slate-400 hover:text-white"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Dashboard
    </a>

</div>

</nav>


<main class="
    max-w-4xl
    mx-auto
    px-5
    py-10
">


<!-- QUEST HEADER -->

<div class="
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-8
    mb-8
">

    <div class="flex items-center gap-4">

        <div class="
            w-14
            h-14
            rounded-2xl
            bg-red-500/10
            border
            border-red-500/20
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-scroll
                text-red-400
                text-2xl
            "></i>

        </div>

        <div>

            <h1 class="text-3xl font-bold">

                <?= htmlspecialchars(
                    $quest["title"]
                ) ?>

            </h1>

            <p class="text-slate-400 mt-1">

                <?= htmlspecialchars(
                    $quest["description"]
                ) ?>

            </p>

        </div>

    </div>


    <div class="
        flex
        flex-wrap
        gap-3
        mt-6
    ">

        <span class="
            px-3
            py-1.5
            rounded-lg
            bg-yellow-500/10
            border
            border-yellow-500/20
            text-yellow-400
            text-sm
        ">

            <i class="fa-solid fa-star mr-1"></i>

            <?= (int) $quest["reward_xp"] ?> XP

        </span>


        <span class="
            px-3
            py-1.5
            rounded-lg
            bg-blue-500/10
            border
            border-blue-500/20
            text-blue-400
            text-sm
        ">

            <i class="fa-solid fa-signal mr-1"></i>

            <?= htmlspecialchars(
                $quest["difficulty"]
            ) ?>

        </span>


        <span class="
            px-3
            py-1.5
            rounded-lg
            bg-purple-500/10
            border
            border-purple-500/20
            text-purple-400
            text-sm
        ">

            <i class="fa-solid fa-list mr-1"></i>

            <?= count($questions) ?> Questions

        </span>

    </div>

</div>


<!-- QUESTIONS -->

<form
    method="POST"
    action="submit_quest.php"
>

<input
    type="hidden"
    name="quest_id"
    value="<?= $questId ?>"
>


<div class="space-y-6">

<?php foreach (
    $questions as $index => $question
): ?>

<div class="
    question-card
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-6
">

    <div class="flex gap-4">

        <div class="
            w-10
            h-10
            shrink-0
            rounded-xl
            bg-blue-500/10
            border
            border-blue-500/20
            flex
            items-center
            justify-center
            text-blue-400
            font-bold
        ">

            <?= $index + 1 ?>

        </div>


        <div class="flex-1">

            <h2 class="text-lg font-semibold">

                <?= htmlspecialchars(
                    $question["question"]
                ) ?>

            </h2>


            <div class="space-y-3 mt-5">


                <?php
                $options = [
                    "A" => $question["option_a"],
                    "B" => $question["option_b"],
                    "C" => $question["option_c"],
                    "D" => $question["option_d"]
                ];
                ?>


                <?php foreach (
                    $options as $letter => $option
                ): ?>

                <label class="
                    option
                    block
                    cursor-pointer
                    border
                    border-slate-700
                    bg-slate-800/50
                    rounded-xl
                    p-4
                ">

                    <input
                        type="radio"
                        name="answers[<?= (int) $question["id"] ?>]"
                        value="<?= $letter ?>"
                        required
                        class="mr-3"
                    >

                    <span class="font-semibold mr-2">
                        <?= $letter ?>.
                    </span>

                    <?= htmlspecialchars($option) ?>

                </label>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>


<button
    type="submit"
    class="
        w-full
        mt-8
        py-4
        rounded-xl
        bg-gradient-to-r
        from-blue-600
        to-purple-600
        hover:from-blue-500
        hover:to-purple-500
        font-bold
        text-lg
        transition
    "
>

    <i class="fa-solid fa-paper-plane mr-2"></i>

    Submit Quest

</button>

</form>

</main>

</body>

</html>