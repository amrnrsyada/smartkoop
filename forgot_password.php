<?php
session_start();
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(16)); // generate 32-char token
        $conn->query("UPDATE users SET reset_token = '$token' WHERE email = '$email'");
        $message = "Reset link: <a href='reset_password.php?token=$token'>Click here to reset your password</a>";
    } else {
        $message = "If an account exists with this email, a reset link will be sent";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PETAKOM MART</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7494ec;
            --primary-hover: #6884d3;
            --gradient-start: #e2e2ee;
            --gradient-end: #c9d6ff;
        }
        
        body {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            font-family: "Poppins", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        
        .auth-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .brand-logo {
            height: 80px;
            margin-bottom: 15px;
        }
        
        .brand-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0;
            font-size: 2rem;
        }
        
        .form-box {
            padding: 30px;
        }
        
        .form-control {
            background: #f8f9fa;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            height: 45px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(116, 148, 236, 0.2);
        }
        
        .btn-auth {
            background: var(--primary-color);
            border: none;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            width: 100%;
        }
        
        .btn-auth:hover {
            background: var(--primary-hover);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .auth-link {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
        }
        
        .auth-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .info-message {
            background-color: #e7f1ff;
            color: #0a58ca;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    
    <div class="container">
        <div class="auth-container">
            <!-- Brand Header -->
            <div style="text-align: center" class="mb-4">
                <img src="uploads/logo.png" alt="PETAKOM MART" class="brand-logo">
                <h1 class="brand-title">PETAKOM MART</h1>
            </div>
            
            <!-- Auth Card -->
            <div class="auth-card">
                <div class="form-box">
                    <form method="post">
                        <h2 class="text-center mb-4" style="font-weight: 500;">Forgot Password</h2>
                        
                        <?php if (!empty($message)): ?>
                            <div class="info-message">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        
                        <button type="submit" class="btn btn-auth btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                        </button>
                        
                        <div class="auth-footer">
                            <p><a href="index.php" class="auth-link"><i class="fas fa-arrow-left me-1"></i> Back to Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>