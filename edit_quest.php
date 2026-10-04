<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");

$error = "";
$success = "";

$questId = (int) ($_GET["id"] ?? $_POST["quest_id"] ?? 0);

if ($questId <= 0) {
    die("Invalid Quest ID.");
}


/*
=====================================================
GET QUEST
=====================================================
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM quests
     WHERE id = ?"
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


/*
=====================================================
UPDATE QUEST
=====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $difficulty = $_POST["difficulty"] ?? "Easy";
    $reward_xp = (int) ($_POST["reward_xp"] ?? 0);
    $type = $_POST["type"] ?? "activity";

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
            UPDATE QUEST
            */

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE quests
                 SET title = ?,
                     description = ?,
                     reward_xp = ?,
                     difficulty = ?
                 WHERE id = ?"
            );

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "ssisi",
                $title,
                $description,
                $reward_xp,
                $difficulty,
                $questId
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);


            /*
            DELETE OLD QUESTIONS
            */

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM quest_questions
                 WHERE quest_id = ?"
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
            ADD NEW QUESTIONS
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
                    $answer = strtoupper(
                        trim($correct[$i] ?? "")
                    );


                    if (
                        $question === "" ||
                        $a === "" ||
                        $b === "" ||
                        $c === "" ||
                        $d === "" ||
                        !in_array(
                            $answer,
                            ["A", "B", "C", "D"]
                        )
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

            $success = "Quest updated successfully!";


            /*
            REFRESH QUEST DATA
            */

            $stmt = mysqli_prepare(
                $conn,
                "SELECT *
                 FROM quests
                 WHERE id = ?"
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

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = "Failed to update quest: " . $e->getMessage();
        }
    }
}


/*
=====================================================
GET QUESTIONS
=====================================================
*/

$questions = [];

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

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $questions[] = $row;
}

mysqli_stmt_close($stmt);


/*
=====================================================
DETERMINE TYPE
=====================================================
*/

$type = count($questions) > 0
    ? "quiz"
    : "activity";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Edit Quest - PowerQuest</title>

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


<nav class="
    bg-slate-900
    border-b
    border-slate-800
">

<div class="
    max-w-5xl
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
        href="manage_quests.php"
        class="text-slate-400 hover:text-white"
    >

        <i class="fa-solid fa-arrow-left mr-2"></i>

        Manage Quests

    </a>

</div>

</nav>


<main class="
    max-w-5xl
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
        bg-blue-500/10
        border
        border-blue-500/20
        flex
        items-center
        justify-center
    ">

        <i class="
            fa-solid
            fa-pen-to-square
            text-blue-400
            text-2xl
        "></i>

    </div>


    <div>

        <h1 class="text-2xl font-bold">
            Edit Quest
        </h1>

        <p class="text-slate-400 text-sm">
            Update quest information and quiz questions.
        </p>

    </div>

</div>


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

    <i class="fa-solid fa-circle-exclamation mr-2"></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


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

    <i class="fa-solid fa-circle-check mr-2"></i>

    <?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>


<form method="POST" class="space-y-6">

<input
    type="hidden"
    name="quest_id"
    value="<?= $questId ?>"
>


<!-- TITLE -->

<div>

<label class="text-sm text-slate-300">
    Quest Title
</label>

<input
    type="text"
    name="title"
    required
    value="<?= htmlspecialchars($quest["title"]) ?>"
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
>

</div>


<!-- DESCRIPTION -->

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
><?= htmlspecialchars($quest["description"]) ?></textarea>

</div>


<!-- TYPE -->

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

<option
    value="quiz"
    <?= $type === "quiz" ? "selected" : "" ?>
>
    Quiz
</option>

<option
    value="activity"
    <?= $type === "activity" ? "selected" : "" ?>
>
    Activity
</option>

</select>

</div>


<!-- DIFFICULTY -->

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
    "
>

<?php foreach (
    ["Easy", "Medium", "Hard", "Legendary"]
    as $difficulty
): ?>

<option
    value="<?= $difficulty ?>"
    <?= $quest["difficulty"] === $difficulty
        ? "selected"
        : ""
    ?>
>

    <?= $difficulty ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- XP -->

<div>

<label class="text-sm text-slate-300">
    XP Reward
</label>

<input
    type="number"
    name="reward_xp"
    min="1"
    required
    value="<?= (int)$quest["reward_xp"] ?>"
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


<!-- QUESTIONS -->

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
            Edit the questions and correct answers.
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


<?php foreach ($questions as $index => $question): ?>


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
    Question <?= $index + 1 ?>
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
    value="<?= htmlspecialchars($question["question"]) ?>"
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
    value="<?= htmlspecialchars($question["option_a"]) ?>"
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
    value="<?= htmlspecialchars($question["option_b"]) ?>"
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
    value="<?= htmlspecialchars($question["option_c"]) ?>"
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
    value="<?= htmlspecialchars($question["option_d"]) ?>"
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

<option
    value="A"
    <?= $question["correct_answer"] === "A"
        ? "selected"
        : ""
    ?>
>
    Correct Answer: A
</option>

<option
    value="B"
    <?= $question["correct_answer"] === "B"
        ? "selected"
        : ""
    ?>
>
    Correct Answer: B
</option>

<option
    value="C"
    <?= $question["correct_answer"] === "C"
        ? "selected"
        : ""
    ?>
>
    Correct Answer: C
</option>

<option
    value="D"
    <?= $question["correct_answer"] === "D"
        ? "selected"
        : ""
    ?>
>
    Correct Answer: D
</option>

</select>

</div>


<?php endforeach; ?>


<?php if (count($questions) === 0): ?>

<!-- EMPTY QUESTION -->

<div class="
    question-box
    bg-slate-800
    border
    border-slate-700
    rounded-2xl
    p-6
    mb-5
">


<h3 class="font-bold mb-4">
    Question 1
</h3>


<input
    type="text"
    name="question[]"
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
    placeholder="Option A"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
    "
>

<input
    type="text"
    name="option_b[]"
    placeholder="Option B"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
    "
>

<input
    type="text"
    name="option_c[]"
    placeholder="Option C"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
    "
>

<input
    type="text"
    name="option_d[]"
    placeholder="Option D"
    class="
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
    "
>

</div>


<select
    name="correct_answer[]"
    class="
        w-full
        mt-4
        bg-slate-900
        border
        border-slate-700
        rounded-xl
        p-3
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

<?php endif; ?>


</div>

</div>


<!-- SUBMIT -->

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
    "
>

    <i class="fa-solid fa-floppy-disk mr-2"></i>

    Save Changes

</button>


</form>

</div>

</main>


<script>

let questionNumber =
    <?= max(count($questions), 1) ?>;


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

    `;


    container.appendChild(box);
}


toggleQuestions();

</script>

</body>
</html>