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
// GET USER + HERO PROFILE
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        u.name,
        u.email,

        hp.id,
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

     LIMIT 1"
);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
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
// SUCCESS / ERROR MESSAGE
// =====================================================

$success = "";
$error = "";

if (isset($_GET["updated"])) {
    $success = "Profile updated successfully!";
}

if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "empty":
            $error = "Name and email are required.";
            break;

        case "email":
            $error = "Please enter a valid email address.";
            break;

        case "password":
            $error = "Password must be at least 6 characters.";
            break;

        case "email_exists":
            $error = "That email address is already being used.";
            break;

        case "failed":
            $error = "Failed to update profile.";
            break;

        default:
            $error = "Something went wrong.";
            break;
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

<title>Hero Profile - PowerQuest</title>


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

.card {
    transition: .2s ease;
}

.card:hover {
    transform: translateY(-2px);
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
    bg-slate-900/95
    backdrop-blur
    border-b
    border-slate-800
">

<div class="
    max-w-6xl
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

            <h1 class="font-bold text-xl">
                PowerQuest
            </h1>

            <p class="text-xs text-slate-500">
                Hero Profile
            </p>

        </div>

    </div>


    <!-- NAV BUTTONS -->

    <div class="flex items-center gap-2">

        <a
            href="dashboard.php"
            class="
                px-4
                py-2
                rounded-xl
                text-slate-300
                hover:bg-slate-800
                transition
            "
        >

            <i class="fa-solid fa-arrow-left mr-2"></i>

            Dashboard

        </a>


        <a
            href="../logout.php"
            class="
                px-4
                py-2
                rounded-xl
                text-red-400
                hover:bg-red-500/10
                transition
            "
        >

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="
    max-w-6xl
    mx-auto
    px-6
    py-10
">


<!-- =====================================================
     SUCCESS MESSAGE
===================================================== -->

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
     ERROR MESSAGE
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


<!-- =====================================================
     PROFILE HEADER
===================================================== -->

<div class="
    bg-slate-900
    border
    border-slate-800
    rounded-3xl
    p-8
    mb-8
">

<div class="
    flex
    flex-col
    md:flex-row
    items-center
    gap-6
">


    <!-- AVATAR -->

    <div class="
        w-28
        h-28
        rounded-3xl
        bg-gradient-to-br
        from-yellow-400
        via-orange-500
        to-red-600
        flex
        items-center
        justify-center
        shadow-lg
    ">

        <i class="
            fa-solid
            fa-shield-halved
            text-5xl
            text-white
        "></i>

    </div>


    <!-- PROFILE INFO -->

    <div class="
        text-center
        md:text-left
        flex-1
    ">

        <p class="
            text-yellow-400
            text-sm
            font-semibold
            uppercase
        ">

            <i class="fa-solid fa-shield-halved mr-1"></i>

            Hero Profile

        </p>


        <h1 class="
            text-3xl
            md:text-4xl
            font-bold
            mt-1
        ">

            <?= htmlspecialchars(
                $hero["hero_name"] ?: $hero["name"]
            ) ?>

        </h1>


        <p class="text-slate-400 mt-2">

            <?= htmlspecialchars($hero["email"]) ?>

        </p>


        <div class="
            flex
            justify-center
            md:justify-start
            gap-3
            mt-4
        ">


            <span class="
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

                Level <?= (int) $hero["level"] ?>

            </span>


            <span class="
                px-4
                py-2
                rounded-xl
                bg-blue-500/10
                border
                border-blue-500/20
                text-blue-400
                text-sm
                font-semibold
            ">

                <?= (int) $hero["experience"] ?> XP

            </span>

        </div>

    </div>

</div>

</div>


<!-- =====================================================
     EDIT ACCOUNT INFORMATION
===================================================== -->

<div class="
    bg-slate-900
    border
    border-slate-800
    rounded-3xl
    p-8
    mb-8
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
            fa-user-pen
            text-blue-400
            text-xl
        "></i>

    </div>


    <div>

        <h2 class="text-2xl font-bold">
            Edit Profile
        </h2>

        <p class="text-slate-400 text-sm">
            You can change your name, email, and password.
        </p>

    </div>

</div>


<form
    method="POST"
    action="update_profile.php"
    class="space-y-6"
>


<!-- =====================================================
     NAME
===================================================== -->

<div>

<label class="
    block
    text-sm
    font-semibold
    text-slate-300
    mb-2
">

    Full Name

</label>


<div class="relative">

    <i class="
        fa-solid
        fa-user
        absolute
        left-4
        top-1/2
        -translate-y-1/2
        text-slate-500
    "></i>


    <input
        type="text"
        name="name"
        value="<?= htmlspecialchars($hero["name"]) ?>"
        maxlength="100"
        required
        class="
            w-full
            bg-slate-800
            border
            border-slate-700
            rounded-xl
            px-12
            py-3
            text-white
            focus:outline-none
            focus:border-blue-500
        "
        placeholder="Enter your full name"
    >

</div>

</div>


<!-- =====================================================
     EMAIL
===================================================== -->

<div>

<label class="
    block
    text-sm
    font-semibold
    text-slate-300
    mb-2
">

    Email Address

</label>


<div class="relative">

    <i class="
        fa-solid
        fa-envelope
        absolute
        left-4
        top-1/2
        -translate-y-1/2
        text-slate-500
    "></i>


    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($hero["email"]) ?>"
        maxlength="150"
        required
        class="
            w-full
            bg-slate-800
            border
            border-slate-700
            rounded-xl
            px-12
            py-3
            text-white
            focus:outline-none
            focus:border-blue-500
        "
        placeholder="Enter your email"
    >

</div>

</div>


<!-- =====================================================
     PASSWORD
===================================================== -->

<div>

<label class="
    block
    text-sm
    font-semibold
    text-slate-300
    mb-2
">

    New Password

</label>


<div class="relative">

    <i class="
        fa-solid
        fa-lock
        absolute
        left-4
        top-1/2
        -translate-y-1/2
        text-slate-500
    "></i>


    <input
        type="password"
        name="password"
        id="password"
        minlength="6"
        class="
            w-full
            bg-slate-800
            border
            border-slate-700
            rounded-xl
            px-12
            py-3
            pr-12
            text-white
            focus:outline-none
            focus:border-blue-500
        "
        placeholder="Leave blank to keep current password"
    >


    <button
        type="button"
        onclick="togglePassword()"
        class="
            absolute
            right-4
            top-1/2
            -translate-y-1/2
            text-slate-500
            hover:text-white
        "
    >

        <i
            id="passwordIcon"
            class="fa-solid fa-eye"
        ></i>

    </button>

</div>


<p class="
    text-xs
    text-slate-500
    mt-2
">

    Leave this blank if you don't want to change your password.

</p>

</div>


<!-- =====================================================
     BUTTONS
===================================================== -->

<div class="
    pt-4
    flex
    flex-col
    sm:flex-row
    gap-3
">


<button
    type="submit"
    class="
        flex-1
        py-3
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

    <i class="fa-solid fa-floppy-disk mr-2"></i>

    Save Changes

</button>


<a
    href="dashboard.php"
    class="
        flex-1
        py-3
        rounded-xl
        bg-slate-800
        hover:bg-slate-700
        text-center
        font-semibold
    "
>

    Cancel

</a>

</div>


</form>

</div>


<!-- =====================================================
     HERO INFORMATION - READ ONLY
===================================================== -->

<div class="
    bg-slate-900
    border
    border-slate-800
    rounded-3xl
    p-8
">


<div class="flex items-center gap-4 mb-6">

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
            fa-shield-halved
            text-purple-400
            text-xl
        "></i>

    </div>


    <div>

        <h2 class="text-2xl font-bold">
            Hero Information
        </h2>

        <p class="text-slate-400 text-sm">
            These values are managed by the PowerQuest system.
        </p>

    </div>

</div>


<!-- HERO NAME + POWER -->

<div class="
    grid
    grid-cols-1
    md:grid-cols-2
    gap-5
    mb-5
">


    <!-- HERO NAME -->

    <div class="
        bg-slate-800
        rounded-xl
        p-5
    ">

        <p class="text-xs text-slate-500">
            HERO NAME
        </p>

        <p class="
            text-xl
            font-bold
            mt-2
        ">

            <?= htmlspecialchars(
                $hero["hero_name"] ?: "Not set"
            ) ?>

        </p>

    </div>


    <!-- POWER -->

    <div class="
        bg-slate-800
        rounded-xl
        p-5
    ">

        <p class="text-xs text-slate-500">
            POWER
        </p>

        <p class="
            text-xl
            font-bold
            text-yellow-400
            mt-2
        ">

            <?= htmlspecialchars(
                $hero["power"] ?: "Not discovered"
            ) ?>

        </p>

    </div>

</div>


<!-- =====================================================
     ATTRIBUTES
===================================================== -->

<div class="
    grid
    grid-cols-2
    md:grid-cols-4
    gap-4
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

        <p class="
            text-2xl
            font-bold
            mt-1
        ">

            <?= (int) $hero["strength"] ?>

        </p>

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

        <p class="
            text-2xl
            font-bold
            mt-1
        ">

            <?= (int) $hero["agility"] ?>

        </p>

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

        <p class="
            text-2xl
            font-bold
            mt-1
        ">

            <?= (int) $hero["intelligence"] ?>

        </p>

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

        <p class="
            text-2xl
            font-bold
            mt-1
        ">

            <?= (int) $hero["defense"] ?>

        </p>

    </div>

</div>


<!-- NOTE -->

<div class="
    mt-6
    p-4
    rounded-xl
    bg-slate-800/50
    border
    border-slate-700
    text-sm
    text-slate-400
">

    <i class="fa-solid fa-circle-info text-blue-400 mr-2"></i>

    Hero name, power, level, experience, and attributes
    cannot be changed from this page.

</div>


</div>


</main>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("passwordIcon");


    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");

    }

}

</script>


</body>
</html>