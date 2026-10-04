<?php

session_start();

require_once __DIR__ . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role     = $_POST["role"] ?? "";

    // =====================================================
    // VALIDATION
    // =====================================================

    if ($name === "" || $email === "" || $password === "") {

        $error = "Please fill in all required fields.";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }

    elseif (!in_array($role, ["civilian", "hero"], true)) {

        $error = "Please select a valid role.";

    }

    elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    }

    else {

        // =================================================
        // CHECK IF EMAIL ALREADY EXISTS
        // =================================================

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if (!$check) {

            $error = "Database error while checking email.";

        } else {

            mysqli_stmt_bind_param(
                $check,
                "s",
                $email
            );

            mysqli_stmt_execute($check);

            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {

                $error = "Email already exists.";

                mysqli_stmt_close($check);

            } else {

                mysqli_stmt_close($check);

                // =================================================
                // HASH PASSWORD
                // =================================================

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // =================================================
                // CREATE USER
                // =================================================

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role
                    )
                    VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {

                    $error = "Unable to create account.";

                } else {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssss",
                        $name,
                        $email,
                        $hashedPassword,
                        $role
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        $userId = mysqli_insert_id($conn);

                        mysqli_stmt_close($stmt);

                        // =================================================
                        // CREATE HERO PROFILE
                        // =================================================

                        if ($role === "hero") {

                            $hero = mysqli_prepare(
                                $conn,
                                "INSERT INTO hero_profiles
                                (
                                    user_id,
                                    hero_name,
                                    power,
                                    level,
                                    experience
                                )
                                VALUES (?, ?, ?, 1, 0)"
                            );

                            if ($hero) {

                                $power = "Undiscovered Power";

                                mysqli_stmt_bind_param(
                                    $hero,
                                    "iss",
                                    $userId,
                                    $name,
                                    $power
                                );

                                mysqli_stmt_execute($hero);

                                mysqli_stmt_close($hero);
                            }
                        }

                        // =================================================
                        // SUCCESS
                        // =================================================

                        header("Location: index.php");
                        exit;

                    } else {

                        $error = "Something went wrong while creating your account.";

                        mysqli_stmt_close($stmt);
                    }
                }
            }
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

<title>Register - PowerQuest</title>

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
        box-sizing: border-box;
    }

    html,
    body {
        min-height: 100%;
    }


    body {
        margin: 0;
        overflow-x: hidden;
    }

    .background-image {

        position: fixed;

        inset: 0;

        width: 100%;
        height: 100%;

        z-index: -20;

        background-image: url("assets/img/bck.jpg");

        background-size: cover;

        background-position: center center;

        background-repeat: no-repeat;

        transform: scale(1.03);

    }



    .background-overlay {

        position: fixed;

        inset: 0;

        width: 100%;
        height: 100%;

        z-index: -10;

        background:

            linear-gradient(
                135deg,
                rgba(2, 6, 23, 0.90),
                rgba(15, 23, 42, 0.82),
                rgba(2, 6, 23, 0.94)
            );

    }


    /* =====================================================
       SUBTLE BACKGROUND BLUR

       Remove "filter: blur()" if you want the background
       to be sharper.
    ====================================================== */

    .background-image {

        filter: blur(2px);

    }


    /* =====================================================
       REGISTER CARD
    ====================================================== */

    .register-card {

        background: rgba(15, 23, 42, 0.88);

        backdrop-filter: blur(18px);

        -webkit-backdrop-filter: blur(18px);

        border: 1px solid rgba(148, 163, 184, 0.20);

        box-shadow:
            0 25px 70px rgba(0, 0, 0, 0.60);

        animation: cardAppear 0.6s ease-out;

    }


    /* =====================================================
       LOGO IMAGE

       IMPORTANT:
       This is separate from the background.
       
       Logo:
       assets/img/logo.png
    ====================================================== */

    .logo-image {

        width: 90px;

        height: 90px;

        object-fit: cover;

        border-radius: 22px;

        border: 2px solid rgba(255, 255, 255, 0.18);

        box-shadow:
            0 10px 35px rgba(0, 0, 0, 0.45);

    }


    /* =====================================================
       CARD ANIMATION
    ====================================================== */

    @keyframes cardAppear {

        from {

            opacity: 0;

            transform:
                translateY(25px)
                scale(0.97);

        }

        to {

            opacity: 1;

            transform:
                translateY(0)
                scale(1);

        }

    }


    /* =====================================================
       ROLE CARD
    ====================================================== */

    .role-card {

        transition:
            transform 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease,
            box-shadow 0.2s ease;

    }


    .role-card:hover {

        transform: translateY(-3px);

    }


    /* =====================================================
       FORM INPUT
    ====================================================== */

    .form-input {

        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease,
            background 0.2s ease;

    }


    .form-input:focus {

        border-color: rgb(59, 130, 246);

        box-shadow:
            0 0 0 3px rgba(59, 130, 246, 0.12);

    }


    /* =====================================================
       BUTTON
    ====================================================== */

    .register-button {

        transition:
            transform 0.15s ease,
            box-shadow 0.2s ease;

    }


    .register-button:hover {

        transform: translateY(-1px);

        box-shadow:
            0 12px 30px rgba(59, 130, 246, 0.25);

    }


    .register-button:active {

        transform: scale(0.98);

    }


    /* =====================================================
       SCROLLBAR
    ====================================================== */

    ::-webkit-scrollbar {

        width: 8px;

    }


    ::-webkit-scrollbar-track {

        background: #020617;

    }


    ::-webkit-scrollbar-thumb {

        background: #334155;

        border-radius: 10px;

    }


    ::-webkit-scrollbar-thumb:hover {

        background: #475569;

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
        px-4
        py-8
    "
>

<!-- =====================================================
     FULL PAGE BACKGROUND
====================================================== -->

<div class="background-image"></div>

<div class="background-overlay"></div>

<!-- =====================================================
     REGISTER CONTAINER
====================================================== -->

<div
    class="
        relative
        w-full
        max-w-lg
        my-4
    "
>

<!-- =================================================
     REGISTER CARD
================================================== -->

<div
    class="
        register-card
        rounded-3xl
        p-6
        sm:p-8
    "
>


    <!-- =================================================
         LOGO
    ================================================== -->

    <div class="text-center mb-8">

        <div
            class="
                flex
                justify-center
                mb-4
            "
        >

            <!-- SEPARATE LOGO IMAGE -->

            <img
                src="assets/img/logo.png"
                alt="PowerQuest Logo"
                class="logo-image"
            >

        </div>


        <h1
            class="
                text-3xl
                sm:text-4xl
                font-bold
                text-white
            "
        >

            PowerQuest

        </h1>


        <p
            class="
                text-slate-400
                mt-2
                text-sm
                sm:text-base
            "
        >

            Create your account

        </p>

    </div>


    <!-- =================================================
         ERROR MESSAGE
    ================================================== -->

    <?php if ($error): ?>

    <div
        class="
            bg-red-500/10
            border
            border-red-500/30
            text-red-400
            rounded-xl
            p-3
            mb-5
            text-sm
            flex
            items-start
            gap-3
        "
    >

        <i
            class="
                fa-solid
                fa-circle-exclamation
                mt-0.5
            "
        ></i>

        <span>

            <?= htmlspecialchars($error) ?>

        </span>

    </div>

    <?php endif; ?>


    <!-- =================================================
         REGISTRATION FORM
    ================================================== -->

    <form
        method="POST"
        action=""
        class="space-y-5"
    >


        <!-- =================================================
             NAME
        ================================================== -->

        <div>

            <label
                class="
                    text-sm
                    text-slate-300
                    font-medium
                "
            >

                Full Name

                <span class="text-red-400">*</span>

            </label>


            <div class="relative mt-2">

                <i
                    class="
                        fa-solid
                        fa-user
                        absolute
                        left-4
                        top-4
                        text-slate-500
                    "
                ></i>


                <input
                    type="text"
                    name="name"
                    required
                    maxlength="100"
                    value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                    class="
                        form-input
                        w-full
                        bg-slate-800/80
                        border
                        border-slate-700
                        rounded-xl
                        py-3
                        pl-11
                        pr-4
                        text-white
                        placeholder-slate-500
                        outline-none
                    "
                    placeholder="Enter your full name"
                >

            </div>

        </div>


        <!-- =================================================
             EMAIL
        ================================================== -->

        <div>

            <label
                class="
                    text-sm
                    text-slate-300
                    font-medium
                "
            >

                Email

                <span class="text-red-400">*</span>

            </label>


            <div class="relative mt-2">

                <i
                    class="
                        fa-solid
                        fa-envelope
                        absolute
                        left-4
                        top-4
                        text-slate-500
                    "
                ></i>


                <input
                    type="email"
                    name="email"
                    required
                    maxlength="150"
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                    class="
                        form-input
                        w-full
                        bg-slate-800/80
                        border
                        border-slate-700
                        rounded-xl
                        py-3
                        pl-11
                        pr-4
                        text-white
                        placeholder-slate-500
                        outline-none
                    "
                    placeholder="Enter your email"
                >

            </div>

        </div>


        <!-- =================================================
             ROLE
        ================================================== -->

        <div>

            <label
                class="
                    text-sm
                    text-slate-300
                    font-medium
                "
            >

                Choose Your Role

                <span class="text-red-400">*</span>

            </label>


            <div
                class="
                    grid
                    grid-cols-2
                    gap-3
                    sm:gap-4
                    mt-3
                "
            >


                <!-- =========================================
                     CIVILIAN
                ========================================== -->

                <label class="cursor-pointer">

                    <input
                        type="radio"
                        name="role"
                        value="civilian"
                        class="peer hidden"
                        required
                        <?= (($_POST["role"] ?? "") === "civilian") ? "checked" : "" ?>
                    >


                    <div
                        class="
                            role-card
                            p-4
                            sm:p-5
                            rounded-2xl
                            border-2
                            border-slate-700
                            bg-slate-800/80
                            text-center

                            peer-checked:border-blue-500
                            peer-checked:bg-blue-500/10
                            peer-checked:shadow-lg
                            peer-checked:shadow-blue-500/10

                            hover:border-slate-500
                        "
                    >

                        <div
                            class="
                                w-14
                                h-14
                                mx-auto
                                rounded-xl
                                bg-blue-500/20
                                flex
                                items-center
                                justify-center
                            "
                        >

                            <i
                                class="
                                    fa-solid
                                    fa-user
                                    text-blue-400
                                    text-2xl
                                "
                            ></i>

                        </div>


                        <h3
                            class="
                                text-white
                                font-bold
                                text-lg
                                mt-3
                            "
                        >

                            Civilian

                        </h3>


                        <p
                            class="
                                text-slate-400
                                text-xs
                                mt-1
                            "
                        >

                            Regular PowerQuest user

                        </p>


                        <div
                            class="
                                mt-3
                                text-blue-400
                                text-xs
                                font-semibold
                            "
                        >

                            SELECT CIVILIAN

                        </div>

                    </div>

                </label>


                <!-- =========================================
                     HERO
                ========================================== -->

                <label class="cursor-pointer">

                    <input
                        type="radio"
                        name="role"
                        value="hero"
                        class="peer hidden"
                        <?= (($_POST["role"] ?? "") === "hero") ? "checked" : "" ?>
                    >


                    <div
                        class="
                            role-card
                            p-4
                            sm:p-5
                            rounded-2xl
                            border-2
                            border-slate-700
                            bg-slate-800/80
                            text-center

                            peer-checked:border-yellow-500
                            peer-checked:bg-yellow-500/10
                            peer-checked:shadow-lg
                            peer-checked:shadow-yellow-500/10

                            hover:border-slate-500
                        "
                    >

                        <div
                            class="
                                w-14
                                h-14
                                mx-auto
                                rounded-xl
                                bg-yellow-500/20
                                flex
                                items-center
                                justify-center
                            "
                        >

                            <i
                                class="
                                    fa-solid
                                    fa-shield-halved
                                    text-yellow-400
                                    text-2xl
                                "
                            ></i>

                        </div>


                        <h3
                            class="
                                text-white
                                font-bold
                                text-lg
                                mt-3
                            "
                        >

                            Hero

                        </h3>


                        <p
                            class="
                                text-slate-400
                                text-xs
                                mt-1
                            "
                        >

                            Take on heroic missions

                        </p>


                        <div
                            class="
                                mt-3
                                text-yellow-400
                                text-xs
                                font-semibold
                            "
                        >

                            SELECT HERO

                        </div>

                    </div>

                </label>

            </div>

        </div>


        <!-- =================================================
             PASSWORD
        ================================================== -->

        <div>

            <label
                class="
                    text-sm
                    text-slate-300
                    font-medium
                "
            >

                Password

                <span class="text-red-400">*</span>

            </label>


            <div class="relative mt-2">

                <i
                    class="
                        fa-solid
                        fa-lock
                        absolute
                        left-4
                        top-4
                        text-slate-500
                    "
                ></i>


                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    minlength="6"
                    class="
                        form-input
                        w-full
                        bg-slate-800/80
                        border
                        border-slate-700
                        rounded-xl
                        py-3
                        pl-11
                        pr-12
                        text-white
                        placeholder-slate-500
                        outline-none
                    "
                    placeholder="Minimum 6 characters"
                >


                <!-- SHOW PASSWORD -->

                <button
                    type="button"
                    onclick="togglePassword()"
                    class="
                        absolute
                        right-4
                        top-3.5
                        text-slate-500
                        hover:text-white
                        transition
                    "
                    aria-label="Show password"
                >

                    <i
                        id="passwordIcon"
                        class="fa-solid fa-eye"
                    ></i>

                </button>

            </div>

        </div>


        <!-- =================================================
             REGISTER BUTTON
        ================================================== -->

        <button
            type="submit"
            class="
                register-button
                w-full
                py-3
                rounded-xl
                bg-gradient-to-r
                from-blue-600
                to-purple-600
                hover:from-blue-500
                hover:to-purple-500
                text-white
                font-semibold
                transition
                shadow-lg
                shadow-blue-500/20
            "
        >

            <i
                class="
                    fa-solid
                    fa-user-plus
                    mr-2
                "
            ></i>

            Create PowerQuest Account

        </button>

    </form>


    <!-- =================================================
         LOGIN
    ================================================== -->

    <div
        class="
            text-center
            mt-6
        "
    >

        <p
            class="
                text-slate-400
                text-sm
            "
        >

            Already have an account?

            <a
                href="index.php"
                class="
                    text-blue-400
                    hover:text-blue-300
                    font-semibold
                    transition
                "
            >

                Login

            </a>

        </p>

    </div>

</div>


</div>


