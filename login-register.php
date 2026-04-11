<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {

    $firstName = $_POST['firstname'];
    $lastName  = $_POST['lastname'];
    $username  = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = 'Email already exists!';
        $_SESSION['active_form'] = 'register';
    } else {
        $conn->query(
            "INSERT INTO users (first_name, last_name, username, email, password, role)
            VALUES ('$firstName', '$lastName', '$username', '$email', '$password', '$role')"
        );
    }

    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result && $result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role']  = $user['role'];

            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: " . ($redirect ?: "user/mainpage.php"));
            }

            exit();
        }
    }

    $_SESSION['login_error'] = 'Invalid email or password!';
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}
