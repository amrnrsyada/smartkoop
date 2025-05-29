<?php
session_start();
require_once 'config.php';

if(isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'customer'; // Fixed role for registration

    // Validate inputs
    if(empty($name) || empty($email) || empty($password)) {
        $_SESSION['register_error'] = 'All fields are required';
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Invalid email format';
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }

    if(strlen($password) < 6) {
        $_SESSION['register_error'] = 'Password must be at least 6 characters';
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = 'Email is already registered';
        $_SESSION['active_form'] = 'register';
        $stmt->close();
        header("Location: index.php");
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    
    if($stmt->execute()) {
        $_SESSION['registration_success'] = true;
        $_SESSION['active_form'] = 'login';
        $stmt->close();
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['register_error'] = 'Registration failed. Please try again.';
        $_SESSION['active_form'] = 'register';
        $stmt->close();
        header("Location: index.php");
        exit();
    }
}

if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $selected_role = $_POST['role'] ?? 'customer'; // Default to customer if not set

    // Validate inputs
    if(empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Email and password are required';
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }

    // First, get user by email only
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = 'Email not found';
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Then verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_error'] = 'Incorrect password';
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }
    
    // Then verify role
    if ($user['role'] !== $selected_role) {
        $_SESSION['login_error'] = 'Please select the correct role for this account';
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }
    
    // If all checks passed, login successful
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    // Redirect based on role
    if ($user['role'] === 'staff') {
        header("Location: adminDashboard.php");
    } else {
        header("Location: makeOrder.php");
    }
    exit();
}
?>