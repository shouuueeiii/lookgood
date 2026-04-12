<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once 'config.php';

if (isset($_POST['register'])) {

    $firstName = $_POST['firstName'] ?? '';
    $lastName  = $_POST['lastName'] ?? '';
    $username  = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = 'user'; // default role for new registrations

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
        $_SESSION['register_error'] = 'All fields are required!';
        $_SESSION['active_form'] = 'register';
        header("Location: login-register.php");
        exit();
    }

    // Sanitize inputs to prevent SQL injection
    $firstName = $conn->real_escape_string($firstName);
    $lastName = $conn->real_escape_string($lastName);
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $password = password_hash($password, PASSWORD_DEFAULT);

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = 'Email already exists!';
        $_SESSION['active_form'] = 'register';
        header("Location: login-register.php");
        exit();
    } else {
        $checkUsername = $conn->query("SELECT username FROM users WHERE username = '$username'");
        if ($checkUsername->num_rows > 0) {
            $_SESSION['register_error'] = 'Username already taken!';
            $_SESSION['active_form'] = 'register';
            header("Location: login-register.php");
            exit();
        }

        $insertResult = $conn->query(
            "INSERT INTO users (first_name, last_name, username, email, password, role)
            VALUES ('$firstName', '$lastName', '$username', '$email', '$password', '$role')"
        );

        if ($insertResult) {
            $_SESSION['register_success'] = 'Account created successfully! Please log in.';
            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['register_error'] = 'Registration failed. Please try again.';
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: login-register.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Fallback admin login via configured credentials.
    if ($email === ADMIN_EMAIL && password_verify($password, ADMIN_PASSWORD)) {
        lg_switch_session_scope('admin');
        $_SESSION['user_id'] = 0;
        $_SESSION['first_name'] = 'Admin';
        $_SESSION['last_name'] = 'User';
        $_SESSION['email'] = ADMIN_EMAIL;
        $_SESSION['role']  = 'admin';

        header("Location: admin/dashboard.php");
        exit();
    }

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result && $result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $targetScope = (($user['role'] ?? '') === 'admin') ? 'admin' : 'user';
            lg_switch_session_scope($targetScope);
            
            $_SESSION['user_id'] = $user['user_id'] ?? null;
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
