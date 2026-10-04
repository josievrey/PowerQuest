<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireRole("admin");


// =========================================
// UPDATE USER
// =========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {

    $id       = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
    $name     = trim($_POST["name"] ?? "");
    $role     = strtolower(trim($_POST["role"] ?? ""));
    $password = trim($_POST["password"] ?? "");

    // Allowed roles
    $allowedRoles = ["admin", "hero", "civilian"];

    // Validate ID
    if ($id <= 0) {
        header("Location: users.php?error=invalid_user");
        exit;
    }

    // Validate username
    if ($name === "") {
        header("Location: users.php?error=empty_name");
        exit;
    }

    // Validate role
    if (!in_array($role, $allowedRoles, true)) {
        header("Location: users.php?error=invalid_role");
        exit;
    }


    // =========================================
    // CHECK IF USERNAME ALREADY EXISTS
    // =========================================

    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM users WHERE name = ? AND id != ? LIMIT 1"
    );

    if (!$check) {
        header("Location: users.php?error=update_failed");
        exit;
    }

    mysqli_stmt_bind_param(
        $check,
        "si",
        $name,
        $id
    );

    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {

        mysqli_stmt_close($check);

        header("Location: users.php?error=name_exists");
        exit;
    }

    mysqli_stmt_close($check);


    // =========================================
    // UPDATE WITH PASSWORD
    // =========================================

    if ($password !== "") {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET name = ?, role = ?, password = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            header("Location: users.php?error=update_failed");
            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $name,
            $role,
            $hashedPassword,
            $id
        );

    }

    // =========================================
    // UPDATE WITHOUT PASSWORD
    // =========================================

    else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET name = ?, role = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            header("Location: users.php?error=update_failed");
            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $name,
            $role,
            $id
        );
    }


    // =========================================
    // EXECUTE UPDATE
    // =========================================

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: users.php?success=updated");
        exit;

    } else {

        mysqli_stmt_close($stmt);

        header("Location: users.php?error=update_failed");
        exit;
    }
}



// =========================================
// DELETE USER
// =========================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {

    $id = isset($_POST["id"])
        ? intval($_POST["id"])
        : 0;

    // Current logged-in user
    $currentUserId = $_SESSION["user_id"] ?? 0;


    // Invalid ID
    if ($id <= 0) {

        header("Location: users.php?error=invalid_user");
        exit;
    }


    // Prevent deleting yourself
    if ($id == $currentUserId) {

        header("Location: users.php?error=cannot_delete_self");
        exit;
    }


    // Delete user
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ?"
    );

    if (!$stmt) {

        header("Location: users.php?error=delete_failed");
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );


    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: users.php?success=deleted");
        exit;

    } else {

        mysqli_stmt_close($stmt);

        header("Location: users.php?error=delete_failed");
        exit;
    }
}



// =========================================
// GET ALL USERS
// =========================================

$result = mysqli_query(
    $conn,
    "SELECT id, name, role
     FROM users
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Manage Users - PowerQuest</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
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
            rgba(124,58,237,.15),
            transparent 35%
        ),
        #020617;
}

.user-card {
    transition: .25s ease;
}

.user-card:hover {

    transform: translateY(-2px);

    border-color:
        rgba(168,85,247,.5);
}

.modal-bg {

    background:
        rgba(0,0,0,.75);

    backdrop-filter:
        blur(8px);
}

</style>

</head>


<body class="min-h-screen text-white">


<!-- =========================================
     NAVBAR
========================================= -->

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
    max-w-7xl
    mx-auto
    px-6
    py-4
    flex
    items-center
    justify-between
">


    <!-- BRAND -->

    <div class="flex items-center gap-3">

        <div class="
            w-10
            h-10
            rounded-xl
            bg-gradient-to-br
            from-purple-500
            to-blue-600
            flex
            items-center
            justify-center
            shadow-lg
            shadow-purple-500/20
        ">

            <i class="fa-solid fa-bolt"></i>

        </div>


        <div>

            <h1 class="font-bold text-xl">
                PowerQuest
            </h1>

            <p class="text-xs text-slate-500">
                User Management
            </p>

        </div>

    </div>


    <!-- BACK -->

    <a
        href="dashboard.php"
        class="
            flex
            items-center
            gap-2
            px-4
            py-2
            rounded-xl
            bg-slate-800
            hover:bg-slate-700
            transition
        "
    >

        <i class="fa-solid fa-arrow-left"></i>

        Dashboard

    </a>

</div>

</nav>



<!-- =========================================
     MAIN
========================================= -->

<main class="
    max-w-7xl
    mx-auto
    px-5
    md:px-6
    py-10
">


<!-- HEADER -->

<div class="
    flex
    flex-col
    md:flex-row
    md:items-center
    md:justify-between
    gap-5
">

    <div>

        <div class="
            flex
            items-center
            gap-2
            text-purple-400
            text-sm
            font-medium
        ">

            <i class="fa-solid fa-users"></i>

            Administration

        </div>


        <h1 class="
            text-4xl
            md:text-5xl
            font-bold
            mt-2
        ">

            Manage Users

        </h1>


        <p class="text-slate-400 mt-2">

            View, update, and delete PowerQuest users.

        </p>

    </div>


    <!-- USER COUNT -->

    <div class="
        bg-slate-900
        border
        border-slate-800
        rounded-2xl
        px-6
        py-4
    ">

        <p class="text-slate-500 text-sm">
            Total Users
        </p>

        <p class="
            text-3xl
            font-bold
            text-purple-400
        ">

            <?= mysqli_num_rows($result) ?>

        </p>

    </div>

</div>



<!-- =========================================
     SUCCESS MESSAGE
========================================= -->

<?php if (isset($_GET["success"])): ?>

<div class="
    mt-6
    p-4
    rounded-xl
    bg-green-500/10
    border
    border-green-500/30
    text-green-400
">

    <i class="fa-solid fa-circle-check mr-2"></i>

    <?php

    if ($_GET["success"] === "updated") {

        echo "User successfully updated.";

    }

    elseif ($_GET["success"] === "deleted") {

        echo "User successfully deleted.";

    }

    ?>

</div>

<?php endif; ?>



<!-- =========================================
     ERROR MESSAGE
========================================= -->

<?php if (isset($_GET["error"])): ?>

<div class="
    mt-6
    p-4
    rounded-xl
    bg-red-500/10
    border
    border-red-500/30
    text-red-400
">

    <i class="fa-solid fa-triangle-exclamation mr-2"></i>

    <?php

    $error = $_GET["error"];

    if ($error === "name_exists") {

        echo "Username already exists.";

    }

    elseif ($error === "empty_name") {

        echo "Username cannot be empty.";

    }

    elseif ($error === "invalid_role") {

        echo "Invalid role selected.";

    }

    elseif ($error === "invalid_user") {

        echo "Invalid user.";

    }

    elseif ($error === "cannot_delete_self") {

        echo "You cannot delete your own admin account.";

    }

    elseif ($error === "update_failed") {

        echo "Failed to update user.";

    }

    elseif ($error === "delete_failed") {

        echo "Failed to delete user.";

    }

    else {

        echo "Something went wrong.";

    }

    ?>

</div>

<?php endif; ?>



<!-- =========================================
     USERS TABLE
========================================= -->

<div class="
    mt-8
    bg-slate-900
    border
    border-slate-800
    rounded-2xl
    overflow-hidden
">


    <!-- TABLE HEADER -->

    <div class="
        px-6
        py-5
        border-b
        border-slate-800
        flex
        items-center
        gap-3
    ">

        <div class="
            w-10
            h-10
            rounded-xl
            bg-blue-500/10
            border
            border-blue-500/20
            flex
            items-center
            justify-center
        ">

            <i class="
                fa-solid
                fa-users
                text-blue-400
            "></i>

        </div>


        <div>

            <h2 class="font-bold text-xl">
                All Users
            </h2>

            <p class="text-slate-500 text-xs">
                Manage registered PowerQuest accounts
            </p>

        </div>

    </div>



    <!-- TABLE -->

    <div class="overflow-x-auto">

        <table class="w-full text-left">

            <thead class="bg-slate-950/70">

                <tr>

                    <th class="
                        px-6
                        py-4
                        text-slate-500
                        text-xs
                        uppercase
                    ">
                        ID
                    </th>

                    <th class="
                        px-6
                        py-4
                        text-slate-500
                        text-xs
                        uppercase
                    ">
                        Username
                    </th>

                    <th class="
                        px-6
                        py-4
                        text-slate-500
                        text-xs
                        uppercase
                    ">
                        Role
                    </th>

                    <th class="
                        px-6
                        py-4
                        text-slate-500
                        text-xs
                        uppercase
                        text-right
                    ">
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-slate-800">


            <?php while ($user = mysqli_fetch_assoc($result)): ?>


                <tr class="
                    user-card
                    hover:bg-slate-800/40
                ">


                    <!-- ID -->

                    <td class="px-6 py-5">

                        <span class="text-slate-500">

                            #<?= htmlspecialchars(
                                $user["id"]
                            ) ?>

                        </span>

                    </td>



                    <!-- USERNAME -->

                    <td class="px-6 py-5">

                        <div class="
                            flex
                            items-center
                            gap-3
                        ">

                            <div class="
                                w-10
                                h-10
                                rounded-xl
                                bg-purple-500/10
                                border
                                border-purple-500/20
                                flex
                                items-center
                                justify-center
                            ">

                                <i class="
                                    fa-solid
                                    fa-user
                                    text-purple-400
                                "></i>

                            </div>


                            <div>

                                <p class="font-semibold">

                                    <?= htmlspecialchars(
                                        $user["name"]
                                    ) ?>

                                </p>

                                <p class="text-xs text-slate-500">

                                    PowerQuest Account

                                </p>

                            </div>

                        </div>

                    </td>



                    <!-- ROLE -->

                    <td class="px-6 py-5">

                        <?php

                        $role = strtolower(
                            $user["role"]
                        );

                        if ($role === "admin") {

                            $roleClass =
                                "bg-purple-500/10 text-purple-400 border-purple-500/20";

                            $icon =
                                "fa-user-shield";

                        }

                        elseif ($role === "hero") {

                            $roleClass =
                                "bg-yellow-500/10 text-yellow-400 border-yellow-500/20";

                            $icon =
                                "fa-shield-halved";

                        }

                        else {

                            $roleClass =
                                "bg-green-500/10 text-green-400 border-green-500/20";

                            $icon =
                                "fa-user";

                        }

                        ?>

                        <span class="
                            inline-flex
                            items-center
                            gap-2
                            px-3
                            py-1.5
                            rounded-full
                            border
                            text-xs
                            font-semibold
                            <?= $roleClass ?>
                        ">

                            <i class="
                                fa-solid
                                <?= $icon ?>
                            "></i>

                            <?= ucfirst(
                                htmlspecialchars($role)
                            ) ?>

                        </span>

                    </td>



                    <!-- ACTIONS -->

                    <td class="
                        px-6
                        py-5
                        text-right
                    ">

                        <div class="
                            flex
                            justify-end
                            gap-2
                        ">


                            <!-- EDIT -->

                            <button
                                type="button"
                                onclick='openEditModal(
                                    <?= json_encode(
                                        $user,
                                        JSON_HEX_TAG |
                                        JSON_HEX_APOS |
                                        JSON_HEX_AMP |
                                        JSON_HEX_QUOT
                                    ) ?>
                                )'
                                class="
                                    w-10
                                    h-10
                                    rounded-xl
                                    bg-blue-500/10
                                    border
                                    border-blue-500/20
                                    text-blue-400
                                    hover:bg-blue-500
                                    hover:text-white
                                    transition
                                "
                                title="Edit User"
                            >

                                <i class="
                                    fa-solid
                                    fa-pen
                                "></i>

                            </button>



                            <!-- DELETE -->

                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Are you sure you want to delete this user?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= htmlspecialchars(
                                        $user["id"]
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_user"
                                    class="
                                        w-10
                                        h-10
                                        rounded-xl
                                        bg-red-500/10
                                        border
                                        border-red-500/20
                                        text-red-400
                                        hover:bg-red-500
                                        hover:text-white
                                        transition
                                    "
                                    title="Delete User"
                                >

                                    <i class="
                                        fa-solid
                                        fa-trash
                                    "></i>

                                </button>

                            </form>


                        </div>

                    </td>


                </tr>


            <?php endwhile; ?>


            </tbody>

        </table>

    </div>

</div>


</main>



<!-- =========================================
     EDIT USER MODAL
========================================= -->

<div
    id="editModal"
    class="
        hidden
        fixed
        inset-0
        z-[999]
        modal-bg
        items-center
        justify-center
        p-5
    "
>


<div class="
    w-full
    max-w-lg
    bg-slate-900
    border
    border-slate-700
    rounded-2xl
    shadow-2xl
">


    <!-- MODAL HEADER -->

    <div class="
        px-6
        py-5
        border-b
        border-slate-800
        flex
        items-center
        justify-between
    ">

        <div>

            <h2 class="text-xl font-bold">
                Edit User
            </h2>

            <p class="text-xs text-slate-500">
                Update account information
            </p>

        </div>


        <button
            type="button"
            onclick="closeEditModal()"
            class="
                w-9
                h-9
                rounded-lg
                bg-slate-800
                hover:bg-slate-700
            "
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>



    <!-- FORM -->

    <form
        method="POST"
        class="p-6"
        onsubmit="return validateEditForm();"
    >

        <!-- USER ID -->

        <input
            type="hidden"
            name="id"
            id="editId"
        >


        <!-- USERNAME -->

        <label class="
            block
            text-sm
            text-slate-400
            mb-2
        ">

            Username

        </label>


        <div class="
            flex
            items-center
            bg-slate-950
            border
            border-slate-700
            rounded-xl
            mb-2
        ">

            <i class="
                fa-solid
                fa-user
                text-slate-500
                ml-4
            "></i>


            <input
                type="text"
                name="name"
                id="editUsername"
                required
                autocomplete="off"
                class="
                    w-full
                    bg-transparent
                    outline-none
                    px-4
                    py-3
                    text-white
                "
                placeholder="Enter username"
            >

        </div>


        <p
            id="usernameError"
            class="
                hidden
                text-red-400
                text-xs
                mb-5
            "
        >
            Username cannot be empty.
        </p>



        <!-- ROLE -->

        <label class="
            block
            text-sm
            text-slate-400
            mb-2
            mt-5
        ">

            Role

        </label>


        <select
            name="role"
            id="editRole"
            required
            class="
                w-full
                bg-slate-950
                border
                border-slate-700
                rounded-xl
                px-4
                py-3
                outline-none
                mb-5
            "
        >

            <option value="admin">
                Admin
            </option>

            <option value="hero">
                Hero
            </option>

            <option value="civilian">
                Civilian
            </option>

        </select>



        <!-- PASSWORD -->

        <label class="
            block
            text-sm
            text-slate-400
            mb-2
        ">

            New Password

            <span class="text-slate-600">

                (leave blank to keep current password)

            </span>

        </label>


        <div class="
            flex
            items-center
            bg-slate-950
            border
            border-slate-700
            rounded-xl
            mb-6
        ">

            <i class="
                fa-solid
                fa-lock
                text-slate-500
                ml-4
            "></i>


            <input
                type="password"
                name="password"
                id="editPassword"
                placeholder="New password"
                autocomplete="new-password"
                class="
                    w-full
                    bg-transparent
                    outline-none
                    px-4
                    py-3
                "
            >

        </div>



        <!-- BUTTONS -->

        <div class="
            flex
            gap-3
        ">


            <button
                type="button"
                onclick="closeEditModal()"
                class="
                    flex-1
                    py-3
                    rounded-xl
                    bg-slate-800
                    hover:bg-slate-700
                    font-semibold
                "
            >

                Cancel

            </button>


            <button
                type="submit"
                name="update_user"
                class="
                    flex-1
                    py-3
                    rounded-xl
                    bg-purple-600
                    hover:bg-purple-500
                    font-semibold
                "
            >

                <i class="
                    fa-solid
                    fa-floppy-disk
                    mr-2
                "></i>

                Save Changes

            </button>

        </div>

    </form>

</div>

</div>



<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>


// =========================================
// OPEN EDIT MODAL
// =========================================

function openEditModal(user) {

    document.getElementById("editId").value =
        user.id;

    document.getElementById("editUsername").value =
        user.name;

    document.getElementById("editRole").value =
        user.role.toLowerCase();

    document.getElementById("editPassword").value =
        "";

    document
        .getElementById("usernameError")
        .classList.add("hidden");


    const modal =
        document.getElementById("editModal");

    modal.classList.remove("hidden");

    modal.classList.add("flex");


    // Focus username
    setTimeout(function() {

        document
            .getElementById("editUsername")
            .focus();

    }, 100);
}



// =========================================
// CLOSE EDIT MODAL
// =========================================

function closeEditModal() {

    const modal =
        document.getElementById("editModal");

    modal.classList.add("hidden");

    modal.classList.remove("flex");
}



// =========================================
// VALIDATE EDIT FORM
// =========================================

function validateEditForm() {

    const username =
        document
            .getElementById("editUsername")
            .value
            .trim();

    const error =
        document.getElementById("usernameError");


    if (username === "") {

        error.classList.remove("hidden");

        document
            .getElementById("editUsername")
            .focus();

        return false;
    }


    error.classList.add("hidden");

    return true;
}



// =========================================
// CLOSE WHEN CLICKING OUTSIDE MODAL
// =========================================

document
    .getElementById("editModal")
    .addEventListener(
        "click",
        function(event) {

            if (event.target === this) {

                closeEditModal();

            }

        }
    );



// =========================================
// CLOSE WITH ESC KEY
// =========================================

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeEditModal();

        }

    }
);

</script>


</body>
</html>
