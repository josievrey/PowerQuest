<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $difficulty = $_POST["difficulty"] ?? "Easy";
    $reward_xp = (int) ($_POST["reward_xp"] ?? 0);
    $type = $_POST["type"] ?? "quiz";

    $allowedDifficulty = [
        "Easy",
        "Medium",
        "Hard",
        "Legendary"
    ];

    $allowedType = [
        "quiz",
        "activity"
    ];

    if (
        $title === "" ||
        $description === "" ||
        $reward_xp <= 0 ||
        !in_array($difficulty, $allowedDifficulty) ||
        !in_array($type, $allowedType)
    ) {

        $error = "Please complete all fields correctly.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            /*
            =====================================================
            CREATE QUEST
            =====================================================
            */

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO quests
                (title, description, reward_xp, difficulty, status)
                VALUES (?, ?, ?, ?, 'active')"
            );

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "ssis",
                $title,
                $description,
                $reward_xp,
                $difficulty
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            $questId = mysqli_insert_id($conn);

            mysqli_stmt_close($stmt);


            /*
            =====================================================
            CREATE QUIZ QUESTIONS
            =====================================================
            */

            if ($type === "quiz") {

                $questions = $_POST["question"] ?? [];
                $optionA = $_POST["option_a"] ?? [];
                $optionB = $_POST["option_b"] ?? [];
                $optionC = $_POST["option_c"] ?? [];
                $optionD = $_POST["option_d"] ?? [];
                $correct = $_POST["correct_answer"] ?? [];

                $questionStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO quest_questions
                    (
                        quest_id,
                        question,
                        option_a,
                        option_b,
                        option_c,
                        option_d,
                        correct_answer
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$questionStmt) {
                    throw new Exception(mysqli_error($conn));
                }

                for ($i = 0; $i < count($questions); $i++) {

                    $question = trim($questions[$i] ?? "");
                    $a = trim($optionA[$i] ?? "");
                    $b = trim($optionB[$i] ?? "");
                    $c = trim($optionC[$i] ?? "");
                    $d = trim($optionD[$i] ?? "");
                    $answer = strtoupper(trim($correct[$i] ?? ""));

                    if (
                        $question === "" ||
                        $a === "" ||
                        $b === "" ||
                        $c === "" ||
                        $d === "" ||
                        !in_array($answer, ["A", "B", "C", "D"])
                    ) {
                        continue;
                    }

                    mysqli_stmt_bind_param(
                        $questionStmt,
                        "issssss",
                        $questId,
                        $question,
                        $a,
                        $b,
                        $c,
                        $d,
                        $answer
                    );

                    if (!mysqli_stmt_execute($questionStmt)) {
                        throw new Exception(
                            mysqli_stmt_error($questionStmt)
                        );
                    }
                }

                mysqli_stmt_close($questionStmt);
            }


            mysqli_commit($conn);

            $success = "Quest created successfully!";

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = "Failed to create quest: " . $e->getMessage();
        }
    }
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

<title>Create Quest - PowerQuest</title>

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

.question-box {
    transition: .2s ease;
}

.question-box:hover {
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
    max-w-6xl
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

        <span class="font-bold text-xl">
            PowerQuest
        </span>

    </div>


    <a
        href="dashboard.php"
        class="
            text-slate-400
            hover:text-white
        "
    >

        <i class="fa-solid fa-arrow-left mr-2"></i>

        Admin Dashboard

    </a>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="
    max-w-4xl
    mx-auto
    px-6
    py-10
">


<div class="
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    p-8
">


<!-- HEADER -->

<div class="
    flex
    items-center
    gap-4
    mb-8
">

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
            fa-scroll
            text-purple-400
            text-2xl
        "></i>

    </div>


    <div>

        <h1 class="text-2xl font-bold">

            Create Quest

        </h1>


        <p class="text-slate-400 text-sm">

            Create a quiz or activity for heroes.

        </p>

    </div>

</div>


<!-- ERROR -->

<?php if ($error): ?>

<div class="
    mb-5
    p-4
    rounded-xl
    bg-red-500/10
    border
    border-red-500/20
    text-red-400
">

    <i class="
        fa-solid
        fa-circle-exclamation
        mr-2
    "></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<!-- SUCCESS -->

<?php if ($success): ?>

<div class="
    mb-5
    p-4
    rounded-xl
    bg-green-500/10
    border
    border-green-500/20
    text-green-400
">

    <i class="
        fa-solid
        fa-circle-check
        mr-2
    "></i>

    <?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>


<form method="POST" class="space-y-6">


<!-- =====================================================
     TITLE
===================================================== -->

<div>

<label class="text-sm text-slate-300">

    Quest Title

</label>

<input
    type="text"
    name="title"
    required
    class="
        w-full
        mt-2
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        px-4
        text-white
        outline-none
        focus:border-purple-500
    "
    placeholder="Example: Hero Safety Challenge"
>

</div>


<!-- =====================================================
     DESCRIPTION
===================================================== -->

<div>

<label class="text-sm text-slate-300">

    Quest Description

</label>

<textarea
    name="description"
    required
    rows="4"
    class="
        w-full
        mt-2
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        p-4
        text-white
        outline-none
        focus:border-purple-500
    "
    placeholder="Describe the quest..."
></textarea>

</div>


<!-- =====================================================
     TYPE
===================================================== -->

<div>

<label class="text-sm text-slate-300">

    Quest Type

</label>

<select
    name="type"
    id="questType"
    onchange="toggleQuestions()"
    class="
        w-full
        mt-2
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        p-3
        text-white
        outline-none
    "
>

<option value="quiz">
    Quiz
</option>

<option value="activity">
    Activity
</option>

</select>

</div>


<!-- =====================================================
     DIFFICULTY
===================================================== -->

<div>

<label class="text-sm text-slate-300">

    Difficulty

</label>

<select
    name="difficulty"
    class="
        w-full
        mt-2
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        p-3
        text-white
        outline-none
    "
>

<option value="Easy">
    Easy
</option>

<option value="Medium">
    Medium
</option>

<option value="Hard">
    Hard
</option>

<option value="Legendary">
    Legendary
</option>

</select>

</div>


<!-- =====================================================
     XP
===================================================== -->

<div>

<label class="text-sm text-slate-300">

    XP Reward

</label>

<input
    type="number"
    name="reward_xp"
    min="1"
    value="100"
    required
    class="
        w-full
        mt-2
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        px-4
        text-white
        outline-none
        focus:border-yellow-500
    "
>

</div>


<!-- =====================================================
     QUESTIONS
===================================================== -->

<div id="questionsSection">


<div class="
    flex
    justify-between
    items-center
    mb-4
">

    <div>

        <h2 class="text-xl font-bold">

            Quiz Questions

        </h2>

        <p class="text-slate-500 text-sm">

            Add questions and choose the correct answer.

        </p>

    </div>


    <button
        type="button"
        onclick="addQuestion()"
        class="
            px-4
            py-2
            rounded-xl
            bg-blue-600
            hover:bg-blue-500
            font-semibold
        "
    >

        <i class="fa-solid fa-plus mr-2"></i>

        Add Question

    </button>

</div>


<div id="questionContainer">


<!-- QUESTION 1 -->

<div class="
    question-box
    bg-slate-800
    border
    border-slate-700
    rounded-2xl
    p-6
    mb-5
">

<div class="
    flex
    justify-between
    items-center
    mb-4
">

<h3 class="font-bold">

Question 1

</h3>

<button
    type="button"
    onclick="this.closest('.question-box').remove()"
    class="text-red-400 hover:text-red-300"
>

<i class="fa-solid fa-trash"></i>

</button>

</div>


<input
    type="text"
    name="question[]"
    required
    placeholder="Enter question"
    class="
        w-full
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        mb-4
        outline-none
    "
>


<div class="grid md:grid-cols-2 gap-4">


<input
    type="text"
    name="option_a[]"
    required
    placeholder="Option A"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        outline-none
    "
>


<input
    type="text"
    name="option_b[]"
    required
    placeholder="Option B"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        outline-none
    "
>


<input
    type="text"
    name="option_c[]"
    required
    placeholder="Option C"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        outline-none
    "
>


<input
    type="text"
    name="option_d[]"
    required
    placeholder="Option D"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        outline-none
    "
>

</div>


<select
    name="correct_answer[]"
    required
    class="
        w-full
        mt-4
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
        outline-none
    "
>

<option value="A">
    Correct Answer: A
</option>

<option value="B">
    Correct Answer: B
</option>

<option value="C">
    Correct Answer: C
</option>

<option value="D">
    Correct Answer: D
</option>

</select>

</div>

</div>

</div>


<!-- =====================================================
     SUBMIT
===================================================== -->

<button
    type="submit"
    class="
        w-full
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

<i class="fa-solid fa-plus mr-2"></i>

Create Quest

</button>


</form>

</div>

</main>


<script>

let questionNumber = 1;


function toggleQuestions() {

    const type =
        document.getElementById("questType").value;

    const section =
        document.getElementById("questionsSection");

    if (type === "quiz") {

        section.style.display = "block";

    } else {

        section.style.display = "none";

    }
}


function addQuestion() {

    questionNumber++;

    const container =
        document.getElementById("questionContainer");


    const box =
        document.createElement("div");


    box.className =
        "question-box bg-slate-800 border border-slate-700 rounded-2xl p-6 mb-5";


    box.innerHTML = `

        <div class="flex justify-between items-center mb-4">

            <h3 class="font-bold">
                Question ${questionNumber}
            </h3>

            <button
                type="button"
                onclick="this.closest('.question-box').remove()"
                class="text-red-400 hover:text-red-300"
            >

                <i class="fa-solid fa-trash"></i>

            </button>

        </div>


        <input
            type="text"
            name="question[]"
            required
            placeholder="Enter question"
            class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 mb-4 outline-none"
        >


        <div class="grid md:grid-cols-2 gap-4">

            <input
                type="text"
                name="option_a[]"
                required
                placeholder="Option A"
                class="bg-slate-900 border border-slate-700 rounded-xl p-3 outline-none"
            >

            <input
                type="text"
                name="option_b[]"
                required
                placeholder="Option B"
                class="bg-slate-900 border border-slate-700 rounded-xl p-3 outline-none"
            >

            <input
                type="text"
                name="option_c[]"
                required
                placeholder="Option C"
                class="bg-slate-900 border border-slate-700 rounded-xl p-3 outline-none"
            >

            <input
                type="text"
                name="option_d[]"
                required
                placeholder="Option D"
                class="bg-slate-900 border border-slate-700 rounded-xl p-3 outline-none"
            >

        </div>


        <select
            name="correct_answer[]"
            required
            class="w-full mt-4 bg-slate-900 border border-slate-700 rounded-xl p-3 outline-none"
        >

            <option value="A">
                Correct Answer: A
            </option>

            <option value="B">
                Correct Answer: B
            </option>

            <option value="C">
                Correct Answer: C
            </option>

            <option value="D">
                Correct Answer: D
            </option>

        </select>
    `;


    container.appendChild(box);
}

</script>

</body>

</html>