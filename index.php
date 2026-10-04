<?php

session_start();

require_once __DIR__ . "/config/database.php";

// ==========================================
// IF ALREADY LOGGED IN
// ==========================================

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// ==========================================
// LOGIN PROCESS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        // ==========================================
        // GET USER
        // ==========================================

        $sql = "
            SELECT
                id,
                name,
                email,
                password,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $user = mysqli_fetch_assoc($result);

            // ==========================================
            // CHECK USER
            // ==========================================

            if (!$user) {

                $error = "Account not found.";

            }

            // ==========================================
            // CHECK STATUS
            // ==========================================

            elseif (
                isset($user["status"]) &&
                $user["status"] !== "active"
            ) {

                $error = "Your account is inactive.";

            }

            // ==========================================
            // CHECK PASSWORD
            // ==========================================

            elseif (
                !password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                $error = "Incorrect password.";

            }

            // ==========================================
            // LOGIN SUCCESS
            // ==========================================

            else {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];

                $_SESSION["name"] = $user["name"];

                $_SESSION["email"] = $user["email"];

                $_SESSION["role"] = strtolower(
                    trim($user["role"])
                );

                header("Location: dashboard.php");
                exit;
            }

            mysqli_stmt_close($stmt);
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

<title>PowerQuest - Login</title>


<!-- =====================================================
     TAILWIND CSS
====================================================== -->

<script src="https://cdn.tailwindcss.com"></script>


<!-- =====================================================
     FONT AWESOME
====================================================== -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<!-- =====================================================
     POPPINS
====================================================== -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet"
>


<!-- =====================================================
     CUSTOM CSS
====================================================== -->

<style>

* {
    font-family: "Poppins", sans-serif;
}


/* =====================================================
   FULL PAGE BACKGROUND
====================================================== */

body {
    min-height: 100vh;
    overflow-x: hidden;
}


/* =====================================================
   BACKGROUND IMAGE
====================================================== */

.background-image {

    position: fixed;

    inset: 0;

    z-index: -3;

    background-image:
        url("assets/img/bck.jpg");

    background-size: cover;

    background-position: center;

    background-repeat: no-repeat;

    filter: blur(4px);

    transform: scale(1.05);
}


/* =====================================================
   DARK OVERLAY
====================================================== */

.background-overlay {

    position: fixed;

    inset: 0;

    z-index: -2;

    background:
        linear-gradient(
            135deg,
            rgba(2, 6, 23, 0.88),
            rgba(15, 23, 42, 0.82),
            rgba(30, 41, 59, 0.90)
        );
}


/* =====================================================
   EXTRA DARK OVERLAY
====================================================== */

.background-dark {

    position: fixed;

    inset: 0;

    z-index: -1;

    background:
        radial-gradient(
            circle at center,
            transparent 0%,
            rgba(2, 6, 23, 0.30) 60%,
            rgba(2, 6, 23, 0.70) 100%
        );
}


/* =====================================================
   LOGIN CARD
====================================================== */

.login-card {

    background:
        rgba(15, 23, 42, 0.90);

    backdrop-filter:
        blur(20px);

    -webkit-backdrop-filter:
        blur(20px);

    border:
        1px solid rgba(148, 163, 184, 0.20);

    box-shadow:
        0 25px 70px rgba(0, 0, 0, 0.60);

    animation:
        cardAppear 0.6s ease-out;
}


/* =====================================================
   CARD ANIMATION
====================================================== */

@keyframes cardAppear {

    from {

        opacity: 0;

        transform:
            translateY(30px)
            scale(0.96);

    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);

    }
}


/* =====================================================
   LOGO IMAGE
====================================================== */

.logo-image {

    width: 88px;

    height: 88px;

    object-fit: cover;

    border-radius: 22px;

    border:
        2px solid
        rgba(255, 255, 255, 0.18);

    box-shadow:
        0 0 35px
        rgba(59, 130, 246, 0.35),

        0 12px 35px
        rgba(0, 0, 0, 0.45);

    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}


.logo-image:hover {

    transform:
        scale(1.05)
        rotate(2deg);

    box-shadow:
        0 0 45px
        rgba(59, 130, 246, 0.50),

        0 15px 40px
        rgba(0, 0, 0, 0.50);
}


/* =====================================================
   INPUT
====================================================== */

.form-input {

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}


.form-input:focus {

    border-color:
        rgb(59, 130, 246);

    box-shadow:
        0 0 0 3px
        rgba(59, 130, 246, 0.12);
}


/* =====================================================
   LOGIN BUTTON
====================================================== */

.login-button {

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}


.login-button:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 10px 30px
        rgba(59, 130, 246, 0.25);
}


.login-button:active {

    transform:
        scale(0.98);
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


/* =====================================================
   MOBILE
====================================================== */

@media (max-width: 640px) {

    .login-card {

        border-radius: 24px;

        padding: 24px;
    }

    .logo-image {

        width: 76px;

        height: 76px;

        border-radius: 18px;
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
        px-4
        py-8
    "
>


<!-- =====================================================
     FULL PAGE BACKGROUND
====================================================== -->

<div class="background-image"></div>

<div class="background-overlay"></div>

<div class="background-dark"></div>


<!-- =====================================================
     LOGIN CONTAINER
====================================================== -->

<div
    class="
        relative
        w-full
        max-w-md
    "
>


    <!-- =================================================
         LOGIN CARD
    ================================================== -->

    <div
        class="
            login-card
            rounded-3xl
            p-6
            sm:p-8
        "
    >


        <!-- =============================================
             LOGO
        ============================================== -->

        <div
            class="
                text-center
                mb-8
            "
        >

            <div
                class="
                    flex
                    justify-center
                    mb-5
                "
            >

                <img
                    src="assets/img/logo.png"
                    alt="PowerQuest Logo"
                    class="logo-image"
                >

            </div>


            <!-- =========================================
                 TITLE
            ========================================== -->

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
                "
            >

                Role Based System

            </p>

        </div>


        <!-- =============================================
             ERROR MESSAGE
        ============================================== -->

        <?php if ($error): ?>

        <div
            class="
                bg-red-500/10
                border
                border-red-500/30
                text-red-400
                rounded-xl
                p-4
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


        <!-- =============================================
             LOGIN FORM
        ============================================== -->

        <form
            method="POST"
            action=""
            class="space-y-5"
        >


            <!-- =========================================
                 EMAIL
            ========================================== -->

            <div>

                <label
                    class="
                        block
                        text-sm
                        text-slate-300
                        font-medium
                        mb-2
                    "
                >

                    Email

                </label>


                <div
                    class="
                        relative
                    "
                >

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
                        autocomplete="email"

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


            <!-- =========================================
                 PASSWORD
            ========================================== -->

            <div>

                <label
                    class="
                        block
                        text-sm
                        text-slate-300
                        font-medium
                        mb-2
                    "
                >

                    Password

                </label>


                <div
                    class="
                        relative
                    "
                >

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
                        autocomplete="current-password"

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

                        placeholder="Enter your password"
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
                    >

                        <i
                            id="passwordIcon"
                            class="
                                fa-solid
                                fa-eye
                            "
                        ></i>

                    </button>

                </div>

            </div>


            <!-- =========================================
                 LOGIN BUTTON
            ========================================== -->

            <button
                type="submit"

                class="
                    login-button
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
                        fa-right-to-bracket
                        mr-2
                    "
                ></i>

                Login

            </button>

        </form>


        <!-- =============================================
             DIVIDER
        ============================================== -->

        <div
            class="
                flex
                items-center
                gap-3
                my-6
            "
        >

            <div class="h-px bg-slate-700 flex-1"></div>

            <span
                class="
                    text-xs
                    text-slate-500
                "
            >

                OR

            </span>

            <div class="h-px bg-slate-700 flex-1"></div>

        </div>


        <!-- =============================================
             REGISTER
        ============================================== -->

        <div
            class="
                text-center
            "
        >

            <p
                class="
                    text-slate-400
                    text-sm
                "
            >

                Don't have an account?

                <a
                    href="register.php"

                    class="
                        text-blue-400
                        hover:text-blue-300
                        font-semibold
                        transition
                    "
                >

                    Register

                </a>

            </p>

        </div>


        <!-- =============================================
             FOOTER
        ============================================== -->

        <div
            class="
                text-center
                mt-6
                pt-5
                border-t
                border-slate-800
            "
        >

            <p
                class="
                    text-xs
                    text-slate-500
                "
            >

                <i
                    class="
                        fa-solid
                        fa-shield-halved
                        mr-1
                    "
                ></i>

                PowerQuest Role Based System

            </p>

        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

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