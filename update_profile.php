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
// ONLY POST REQUEST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}


// =====================================================
// GET FORM DATA
// =====================================================

$name = trim($_POST["name"] ?? "");

$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";


// =====================================================
// VALIDATE NAME
// =====================================================

if ($name === "") {
    header("Location: profile.php?error=empty");
    exit;
}


// =====================================================
// VALIDATE EMAIL
// =====================================================

if ($email === "") {
    header("Location: profile.php?error=empty");
    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: profile.php?error=email");
    exit;
}


// =====================================================
// VALIDATE PASSWORD
// =====================================================

if ($password !== "" && strlen($password) < 6) {

    header("Location: profile.php?error=password");

    exit;
}


// =====================================================
// CHECK IF EMAIL IS USED BY ANOTHER USER
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM users
     WHERE email = ?
     AND id != ?
     LIMIT 1"
);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $email,
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$emailExists = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if ($emailExists) {

    header("Location: profile.php?error=email_exists");

    exit;
}


// =====================================================
// UPDATE PROFILE
// =====================================================

mysqli_begin_transaction($conn);

try {


    // =================================================
    // UPDATE NAME + EMAIL
    // =================================================

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET name = ?,
             email = ?
         WHERE id = ?"
    );

    if (!$stmt) {
        throw new Exception(
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $name,
        $email,
        $userId
    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    // =================================================
    // UPDATE PASSWORD ONLY IF ENTERED
    // =================================================

    if ($password !== "") {


        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );


        if (!$stmt) {

            throw new Exception(
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $hashedPassword,
            $userId
        );


        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        mysqli_stmt_close($stmt);

    }


    // =================================================
    // COMMIT
    // =================================================

    mysqli_commit($conn);


    // =================================================
    // UPDATE SESSION NAME
    // =================================================

    $_SESSION["name"] = $name;


    // =================================================
    // SUCCESS
    // =================================================

    header("Location: profile.php?updated=1");

    exit;


} catch (Exception $e) {


    mysqli_rollback($conn);


    header("Location: profile.php?error=failed");

    exit;

}