<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");


// =====================================================
// CURRENT ADMIN
// =====================================================

$userId = (int) ($_SESSION["user_id"] ?? 0);

$error = "";
$success = "";


// =====================================================
// GET ADMIN INFORMATION
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, role, created_at
     FROM users
     WHERE id = ?
     AND role = 'admin'
     LIMIT 1"
);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$admin = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$admin) {
    die("Admin account not found.");
}


// =====================================================
// UPDATE PROFILE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    // =================================================
    // UPDATE PROFILE INFORMATION
    // =================================================

    if ($action === "update_profile") {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");


        if ($name === "" || $email === "") {

            $error = "Name and email are required.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            // Check if email is already used
            $check = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM users
                 WHERE email = ?
                 AND id != ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $check,
                "si",
                $email,
                $userId
            );

            mysqli_stmt_execute($check);

            $checkResult = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($checkResult) > 0) {

                $error = "That email address is already being used.";

            } else {

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET name = ?, email = ?
                     WHERE id = ?
                     AND role = 'admin'"
                );

                mysqli_stmt_bind_param(
                    $update,
                    "ssi",
                    $name,
                    $email,
                    $userId
                );

                if (mysqli_stmt_execute($update)) {

                    $_SESSION["name"] = $name;

                    $success = "Profile updated successfully.";

                    // Refresh displayed information
                    $admin["name"] = $name;
                    $admin["email"] = $email;

                } else {

                    $error = "Failed to update profile.";
                }

                mysqli_stmt_close($update);
            }

            mysqli_stmt_close($check);
        }
    }


    // =================================================
    // CHANGE PASSWORD
    // =================================================

    if ($action === "change_password") {

        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";


        if (
            $currentPassword === "" ||
            $newPassword === "" ||
            $confirmPassword === ""
        ) {

            $error = "Please complete all password fields.";

        } elseif (strlen($newPassword) < 8) {

            $error = "New password must be at least 8 characters.";

        } elseif ($newPassword !== $confirmPassword) {

            $error = "New passwords do not match.";

        } else {

            // Get current password
            $passwordStmt = mysqli_prepare(
                $conn,
                "SELECT password
                 FROM users
                 WHERE id = ?
                 AND role = 'admin'
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $passwordStmt,
                "i",
                $userId
            );

            mysqli_stmt_execute($passwordStmt);

            $passwordResult =
                mysqli_stmt_get_result($passwordStmt);

            $account =
                mysqli_fetch_assoc($passwordResult);

            mysqli_stmt_close($passwordStmt);


            if (
                !$account ||
                !password_verify(
                    $currentPassword,
                    $account["password"]
                )
            ) {

                $error = "Current password is incorrect.";

            } else {

                $hashedPassword =
                    password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                $updatePassword = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET password = ?
                     WHERE id = ?
                     AND role = 'admin'"
                );

                mysqli_stmt_bind_param(
                    $updatePassword,
                    "si",
                    $hashedPassword,
                    $userId
                );

                if (
                    mysqli_stmt_execute(
                        $updatePassword
                    )
                ) {

                    $success =
                        "Password changed successfully.";

                } else {

                    $error =
                        "Failed to change password.";
                }

                mysqli_stmt_close(
                    $updatePassword
                );
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

<title>
    Admin Settings - PowerQuest
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
    href="https://fonts.gstatic.com"
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
            rgba(124, 58, 237, .15),
            transparent 35%
        ),
        #020617;
}

.card {
    transition: .25s ease;
}

.card:hover {
    transform: translateY(-2px);
}

</style>

</head>


<body class="bg-slate-950 text-white min-h-screen">


<!-- =====================================================
     NAVBAR
====================================================== -->

<nav
    class="
        sticky
        top-0
        z-50
        bg-slate-900/95
        backdrop-blur
        border-b
        border-slate-800
    "
>

<div
    class="
        max-w-7xl
        mx-auto
        px-6
        py-4
        flex
        items-center
        justify-between
    "
>

    <!-- LOGO -->

    <div class="flex items-center gap-3">

        <div
            class="
                w-11
                h-11
                rounded-xl
                bg-gradient-to-br
                from-purple-500
                to-blue-600
                flex
                items-center
                justify-center
            "
        >

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


    <!-- NAV LINKS -->

    <div class="flex items-center gap-3">

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

            <i class="fa-solid fa-house mr-2"></i>

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

            <i class="fa-solid fa-right-from-bracket mr-2"></i>

            Logout

        </a>

    </div>

</div>

</nav>



<!-- =====================================================
     MAIN
====================================================== -->

<main
    class="
        max-w-6xl
        mx-auto
        px-6
        py-10
    "
>


<!-- HEADER -->

<div class="mb-8">

    <div
        class="
            flex
            items-center
            gap-2
            text-purple-400
            text-sm
            font-semibold
        "
    >

        <i class="fa-solid fa-gear"></i>

        ADMIN SETTINGS

    </div>


    <h1
        class="
            text-4xl
            md:text-5xl
            font-bold
            mt-2
        "
    >

        My Profile

    </h1>


    <p class="text-slate-400 mt-2">

        Manage your administrator account and security settings.

    </p>

</div>



<!-- =====================================================
     ALERTS
====================================================== -->

<?php if ($error): ?>

<div
    class="
        mb-6
        p-4
        rounded-xl
        bg-red-500/10
        border
        border-red-500/20
        text-red-400
    "
>

    <i
        class="
            fa-solid
            fa-circle-exclamation
            mr-2
        "
    ></i>

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<?php if ($success): ?>

<div
    class="
        mb-6
        p-4
        rounded-xl
        bg-green-500/10
        border
        border-green-500/20
        text-green-400
    "
>

    <i
        class="
            fa-solid
            fa-circle-check
            mr-2
        "
    ></i>

    <?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>



<!-- =====================================================
     PROFILE HEADER
====================================================== -->

<div
    class="
        card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-8
        mb-6
    "
>

<div
    class="
        flex
        flex-col
        md:flex-row
        md:items-center
        gap-6
    "
>


<!-- AVATAR -->

<div
    class="
        w-24
        h-24
        rounded-3xl
        bg-gradient-to-br
        from-purple-500
        to-blue-600
        flex
        items-center
        justify-center
        shadow-lg
    "
>

    <i
        class="
            fa-solid
            fa-user-shield
            text-4xl
        "
    ></i>

</div>


<!-- NAME -->

<div class="flex-1">

    <p class="text-sm text-slate-500">
        Administrator Account
    </p>

    <h2 class="text-3xl font-bold mt-1">

        <?= htmlspecialchars($admin["name"]) ?>

    </h2>

    <p class="text-slate-400 mt-1">

        <?= htmlspecialchars($admin["email"]) ?>

    </p>

</div>


<!-- ROLE -->

<div>

    <span
        class="
            inline-flex
            items-center
            gap-2
            px-4
            py-2
            rounded-full
            bg-purple-500/10
            border
            border-purple-500/20
            text-purple-400
            font-semibold
        "
    >

        <i class="fa-solid fa-shield-halved"></i>

        ADMIN

    </span>

</div>

</div>

</div>



<!-- =====================================================
     CONTENT GRID
====================================================== -->

<div
    class="
        grid
        grid-cols-1
        lg:grid-cols-2
        gap-6
    "
>


<!-- =====================================================
     ACCOUNT INFORMATION
====================================================== -->

<div
    class="
        card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    "
>

<div class="flex items-center gap-3 mb-6">

    <div
        class="
            w-11
            h-11
            rounded-xl
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
                fa-user
                text-blue-400
            "
        ></i>

    </div>

    <div>

        <h2 class="font-bold text-xl">
            Account Information
        </h2>

        <p class="text-xs text-slate-500">
            Your account details
        </p>

    </div>

</div>



<!-- USER ID -->

<div
    class="
        flex
        items-center
        justify-between
        py-4
        border-b
        border-slate-800
    "
>

<div class="flex items-center gap-3">

    <i class="fa-solid fa-fingerprint text-slate-500"></i>

    <span class="text-slate-400">
        User ID
    </span>

</div>

<strong>
    #<?= (int)$admin["id"] ?>
</strong>

</div>



<!-- NAME -->

<div
    class="
        flex
        items-center
        justify-between
        py-4
        border-b
        border-slate-800
    "
>

<div class="flex items-center gap-3">

    <i class="fa-solid fa-id-card text-slate-500"></i>

    <span class="text-slate-400">
        Name
    </span>

</div>

<strong>
    <?= htmlspecialchars($admin["name"]) ?>
</strong>

</div>



<!-- EMAIL -->

<div
    class="
        flex
        items-center
        justify-between
        py-4
        border-b
        border-slate-800
    "
>

<div class="flex items-center gap-3">

    <i class="fa-solid fa-envelope text-slate-500"></i>

    <span class="text-slate-400">
        Email
    </span>

</div>

<strong class="text-sm">
    <?= htmlspecialchars($admin["email"]) ?>
</strong>

</div>



<!-- ROLE -->

<div
    class="
        flex
        items-center
        justify-between
        py-4
        border-b
        border-slate-800
    "
>

<div class="flex items-center gap-3">

    <i class="fa-solid fa-shield text-slate-500"></i>

    <span class="text-slate-400">
        Role
    </span>

</div>

<span class="text-purple-400 font-semibold">
    <?= strtoupper(htmlspecialchars($admin["role"])) ?>
</span>

</div>



<!-- CREATED -->

<div
    class="
        flex
        items-center
        justify-between
        py-4
    "
>

<div class="flex items-center gap-3">

    <i class="fa-solid fa-calendar text-slate-500"></i>

    <span class="text-slate-400">
        Account Created
    </span>

</div>

<span class="text-sm">

<?php if (!empty($admin["created_at"])): ?>

<?= date(
    "M d, Y",
    strtotime($admin["created_at"])
) ?>

<?php else: ?>

—

<?php endif; ?>

</span>

</div>

</div>



<!-- =====================================================
     EDIT PROFILE
====================================================== -->

<div
    class="
        card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
    "
>

<div class="flex items-center gap-3 mb-6">

    <div
        class="
            w-11
            h-11
            rounded-xl
            bg-green-500/10
            border
            border-green-500/20
            flex
            items-center
            justify-center
        "
    >

        <i
            class="
                fa-solid
                fa-pen
                text-green-400
            "
        ></i>

    </div>

    <div>

        <h2 class="font-bold text-xl">
            Edit Profile
        </h2>

        <p class="text-xs text-slate-500">
            Update your information
        </p>

    </div>

</div>


<form method="POST" class="space-y-5">

<input
    type="hidden"
    name="action"
    value="update_profile"
>


<!-- NAME -->

<div>

<label class="text-sm text-slate-300">

    Full Name

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
    value="<?= htmlspecialchars($admin["name"]) ?>"
    class="
        w-full
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        pl-11
        pr-4
        text-white
        outline-none
        focus:border-green-500
    "
>

</div>

</div>



<!-- EMAIL -->

<div>

<label class="text-sm text-slate-300">

    Email Address

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
    value="<?= htmlspecialchars($admin["email"]) ?>"
    class="
        w-full
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        pl-11
        pr-4
        text-white
        outline-none
        focus:border-green-500
    "
>

</div>

</div>



<button
    type="submit"
    class="
        w-full
        py-3
        rounded-xl
        bg-green-600
        hover:bg-green-500
        font-semibold
        transition
    "
>

<i class="fa-solid fa-floppy-disk mr-2"></i>

Save Profile

</button>

</form>

</div>



<!-- =====================================================
     CHANGE PASSWORD
====================================================== -->

<div
    class="
        card
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        p-6
        lg:col-span-2
    "
>

<div class="flex items-center gap-3 mb-6">

    <div
        class="
            w-11
            h-11
            rounded-xl
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
                fa-lock
                text-yellow-400
            "
        ></i>

    </div>

    <div>

        <h2 class="font-bold text-xl">
            Change Password
        </h2>

        <p class="text-xs text-slate-500">
            Keep your administrator account secure.
        </p>

    </div>

</div>



<form
    method="POST"
    class="
        grid
        grid-cols-1
        md:grid-cols-3
        gap-5
    "
>

<input
    type="hidden"
    name="action"
    value="change_password"
>


<!-- CURRENT PASSWORD -->

<div>

<label class="text-sm text-slate-300">

    Current Password

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
    name="current_password"
    required
    class="
        w-full
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        pl-11
        pr-4
        text-white
        outline-none
        focus:border-yellow-500
    "
>

</div>

</div>



<!-- NEW PASSWORD -->

<div>

<label class="text-sm text-slate-300">

    New Password

</label>

<div class="relative mt-2">

<i
    class="
        fa-solid
        fa-key
        absolute
        left-4
        top-4
        text-slate-500
    "
></i>

<input
    type="password"
    name="new_password"
    minlength="8"
    required
    class="
        w-full
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        pl-11
        pr-4
        text-white
        outline-none
        focus:border-yellow-500
    "
>

</div>

</div>



<!-- CONFIRM -->

<div>

<label class="text-sm text-slate-300">

    Confirm Password

</label>

<div class="relative mt-2">

<i
    class="
        fa-solid
        fa-check
        absolute
        left-4
        top-4
        text-slate-500
    "
></i>

<input
    type="password"
    name="confirm_password"
    minlength="8"
    required
    class="
        w-full
        bg-slate-800
        border
        border-slate-700
        rounded-xl
        py-3
        pl-11
        pr-4
        text-white
        outline-none
        focus:border-yellow-500
    "
>

</div>

</div>



<div class="md:col-span-3">

<button
    type="submit"
    class="
        w-full
        py-3
        rounded-xl
        bg-gradient-to-r
        from-yellow-500
        to-orange-500
        hover:from-yellow-400
        hover:to-orange-400
        text-slate-950
        font-bold
        transition
    "
>

<i class="fa-solid fa-key mr-2"></i>

Change Password

</button>

</div>

</form>

</div>

</div>

</main>

</body>

</html>