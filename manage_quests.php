<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

$error = "";
$success = "";

/*
=====================================================
DELETE QUEST
=====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_quest"])) {

    $questId = (int) $_POST["quest_id"];

    if ($questId <= 0) {

        $error = "Invalid quest.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            /*
            DELETE QUESTIONS FIRST
            */

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM quest_questions WHERE quest_id = ?"
            );

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $questId
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);


            /*
            DELETE QUEST
            */

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM quests WHERE id = ?"
            );

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $questId
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            $success = "Quest deleted successfully.";

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = "Failed to delete quest: " . $e->getMessage();
        }
    }
}


/*
=====================================================
GET ALL QUESTS
=====================================================
*/

$sql = "
    SELECT
        q.id,
        q.title,
        q.description,
        q.reward_xp,
        q.difficulty,
        q.status,
        COUNT(qq.id) AS question_count

    FROM quests q

    LEFT JOIN quest_questions qq
        ON qq.quest_id = q.id

    GROUP BY
        q.id,
        q.title,
        q.description,
        q.reward_xp,
        q.difficulty,
        q.status

    ORDER BY q.id DESC
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

<title>Manage Quests - PowerQuest</title>

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
            rgba(124, 58, 237, .12),
            transparent 35%
        ),
        #020617;
}

.quest-card {
    transition: .2s ease;
}

.quest-card:hover {
    transform: translateY(-3px);
    border-color: #475569;
}

</style>

</head>


<body class="bg-slate-950 text-white min-h-screen">


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="
    bg-slate-900
    border-b
    border-slate-800
">

<div class="
    max-w-7xl
    mx-auto
    px-6
    py-4
    flex
    justify-between
    items-center
">

    <div class="flex items-center gap-3">

        <div class="
            w-10
            h-10
            rounded-xl
            bg-gradient-to-br
            from-blue-500
            to-purple-600
            flex
            items-center
            justify-center
        ">

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


    <div class="flex items-center gap-3">

        <a
            href="dashboard.php"
            class="
                px-4
                py-2
                rounded-xl
                text-slate-400
                hover:text-white
                hover:bg-slate-800
                transition
            "
        >

            <i class="fa-solid fa-arrow-left mr-2"></i>

            Dashboard

        </a>

        <a
            href="create_quest.php"
            class="
                px-4
                py-2
                rounded-xl
                bg-purple-600
                hover:bg-purple-500
                transition
                font-semibold
            "
        >

            <i class="fa-solid fa-plus mr-2"></i>

            Create Quest

        </a>

    </div>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="
    max-w-7xl
    mx-auto
    px-6
    py-10
">


<!-- HEADER -->

<div class="mb-8">

    <div class="
        flex
        items-center
        gap-2
        text-purple-400
        text-sm
        font-semibold
    ">

        <i class="fa-solid fa-scroll"></i>

        QUEST MANAGEMENT

    </div>

    <h1 class="
        text-4xl
        md:text-5xl
        font-bold
        mt-2
    ">

        Manage Quests

    </h1>

    <p class="text-slate-400 mt-2">

        Create, edit, and delete quests for your heroes.

    </p>

</div>


<!-- =====================================================
     MESSAGES
===================================================== -->

<?php if ($error): ?>

<div class="
    mb-6
    p-4
    rounded-xl
    bg-red-500/10
    border
    border-red-500/20
    text-red-400
">

    <i class="fa-solid fa-circle-exclamation mr-2"></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<?php if ($success): ?>

<div class="
    mb-6
    p-4
    rounded-xl
    bg-green-500/10
    border
    border-green-500/20
    text-green-400
">

    <i class="fa-solid fa-circle-check mr-2"></i>

    <?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>


<!-- =====================================================
     QUEST COUNT
===================================================== -->

<div class="
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
">

    <i class="fa-solid fa-scroll text-purple-400"></i>

    Total Quests:

    <strong class="text-white">

        <?= mysqli_num_rows($result) ?>

    </strong>

</div>


<!-- =====================================================
     QUEST LIST
===================================================== -->

<div class="
    grid
    grid-cols-1
    lg:grid-cols-2
    gap-6
">


<?php if (mysqli_num_rows($result) > 0): ?>


<?php while ($quest = mysqli_fetch_assoc($result)): ?>


<div class="
    quest-card
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-6
">


<!-- TITLE -->

<div class="
    flex
    justify-between
    items-start
    gap-4
">

    <div>

        <div class="
            flex
            items-center
            gap-2
            mb-2
        ">

            <span class="
                text-xs
                px-3
                py-1
                rounded-full
                bg-purple-500/10
                border
                border-purple-500/20
                text-purple-400
                font-semibold
            ">

                QUEST #<?= (int)$quest["id"] ?>

            </span>


            <?php if ((int)$quest["question_count"] > 0): ?>

                <span class="
                    text-xs
                    px-3
                    py-1
                    rounded-full
                    bg-blue-500/10
                    border
                    border-blue-500/20
                    text-blue-400
                ">

                    <i class="fa-solid fa-circle-question mr-1"></i>

                    QUIZ

                </span>

            <?php else: ?>

                <span class="
                    text-xs
                    px-3
                    py-1
                    rounded-full
                    bg-orange-500/10
                    border
                    border-orange-500/20
                    text-orange-400
                ">

                    <i class="fa-solid fa-person-running mr-1"></i>

                    ACTIVITY

                </span>

            <?php endif; ?>

        </div>


        <h2 class="
            text-xl
            font-bold
            text-white
        ">

            <?= htmlspecialchars($quest["title"]) ?>

        </h2>

    </div>


    <!-- STATUS -->

    <span class="
        px-3
        py-1
        rounded-full
        text-xs
        font-semibold
        bg-green-500/10
        text-green-400
        border
        border-green-500/20
    ">

        <?= strtoupper(
            htmlspecialchars($quest["status"])
        ) ?>

    </span>

</div>


<!-- DESCRIPTION -->

<p class="
    text-slate-400
    text-sm
    mt-4
    leading-relaxed
">

    <?= htmlspecialchars($quest["description"]) ?>

</p>


<!-- INFO -->

<div class="
    grid
    grid-cols-3
    gap-3
    mt-5
">


<div class="
    bg-slate-800/70
    rounded-xl
    p-3
">

    <p class="text-xs text-slate-500">
        Difficulty
    </p>

    <p class="
        font-semibold
        text-yellow-400
        mt-1
    ">

        <?= htmlspecialchars($quest["difficulty"]) ?>

    </p>

</div>


<div class="
    bg-slate-800/70
    rounded-xl
    p-3
">

    <p class="text-xs text-slate-500">
        Reward
    </p>

    <p class="
        font-semibold
        text-green-400
        mt-1
    ">

        <?= (int)$quest["reward_xp"] ?> XP

    </p>

</div>


<div class="
    bg-slate-800/70
    rounded-xl
    p-3
">

    <p class="text-xs text-slate-500">
        Questions
    </p>

    <p class="
        font-semibold
        text-blue-400
        mt-1
    ">

        <?= (int)$quest["question_count"] ?>

    </p>

</div>


</div>


<!-- ACTIONS -->

<div class="
    flex
    gap-3
    mt-6
    pt-5
    border-t
    border-slate-800
">


<!-- EDIT -->

<a
    href="edit_quest.php?id=<?= (int)$quest["id"] ?>"
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

    Edit

</a>


<!-- DELETE -->

<form
    method="POST"
    class="flex-1"
    onsubmit="return confirm(
        'Are you sure you want to delete this quest? This will also delete all quiz questions.'
    );"
>

    <input
        type="hidden"
        name="quest_id"
        value="<?= (int)$quest["id"] ?>"
    >

    <button
        type="submit"
        name="delete_quest"
        class="
            w-full
            py-3
            rounded-xl
            bg-red-600
            hover:bg-red-500
            font-semibold
            transition
        "
    >

        <i class="fa-solid fa-trash mr-2"></i>

        Delete

    </button>

</form>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="
    lg:col-span-2
    text-center
    py-16
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
">

    <i class="
        fa-solid
        fa-scroll
        text-slate-600
        text-5xl
    "></i>

    <h2 class="text-xl font-bold mt-5">

        No Quests Found

    </h2>

    <p class="text-slate-500 mt-2">

        Create your first quest for the heroes.

    </p>

    <a
        href="create_quest.php"
        class="
            inline-block
            mt-5
            px-5
            py-3
            rounded-xl
            bg-purple-600
            hover:bg-purple-500
            font-semibold
        "
    >

        <i class="fa-solid fa-plus mr-2"></i>

        Create Quest

    </a>

</div>


<?php endif; ?>


</div>

</main>

</body>
</html>