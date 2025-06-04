<?php
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';
session_unset();

function showError($error) {
    return !empty($error) ? "<div class='alert alert-danger mb-4'>$error</div>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'd-block' : 'd-none';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETAKOM MART</title>
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
            --dark-color: #343a40;
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
        
        .form-switch {
            text-align: center;
            margin-top: 15px;
        }
        
        .role-selector {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
        }
        
        .role-option.active {
            border-color: var(--primary-color);
            background-color: rgba(116, 148, 236, 0.1);
        }
        
        .role-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        input[type="radio"].role-radio {
            display: none;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
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
                <!-- Login Form -->
                <div class="form-box <?= isActiveForm('login', $activeForm) ?>" id="login-form">
                    <form action="login_register.php" method="post">
                        <h2 class="text-center mb-4" style="font-weight: 500;">Login</h2>
                        <?= showError($errors['login']) ?>
                        
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                        </div>
                        
                        <div class="mb-3 password-container">
                            <input type="password" id="login-password" name="password" class="form-control" placeholder="Password" required>
                            <span class="password-toggle" onclick="togglePassword('login-password', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        
                        <div class="role-selector">
                            <label class="role-option active">
                                <input type="radio" name="role" value="staff" class="role-radio" checked>
                                <i class="fas fa-user-tie"></i>
                                <div>Staff</div>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="customer" class="role-radio">
                                <i class="fas fa-user"></i>
                                <div>Customer</div>
                            </label>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-auth btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                        
                        <div class="form-switch">
                            <p class="mb-2">Don't have an account? <a href="#" onclick="showForm('register-form')" class="auth-link">Register</a></p>
                            <p class="mb-0"><a href="forgot_password.php" class="auth-link">Forgot your password?</a></p>
                        </div>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div class="form-box <?= isActiveForm('register', $activeForm) ?>" id="register-form">
                    <form action="login_register.php" method="post">
                        <h2 class="text-center mb-4" style="font-weight: 500;">Register</h2>
                        <?= showError($errors['register']) ?>
                        
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                        </div>
                        
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>
                        
                        <div class="mb-3 password-container">
                            <input type="password" id="register-password" name="password" class="form-control" placeholder="Create Password" required onfocus="showPasswordHint()" onblur="hidePasswordHint()">
                            <span class="password-toggle" onclick="togglePassword('register-password', this)">
                                <i class="fas fa-eye"></i>
                            </span>
                            
                            <!-- Hint text, initially hidden -->
                            <div id="password-hint" class="form-text d-none">
                                At least 8 characters long includes number, and symbol.
                            </div>
                        </div>

                        <!-- Hidden role field set to customer -->
                        <input type="hidden" name="role" value="customer">
                        
                        <button type="submit" name="register" class="btn btn-auth btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </button>
                        
                        <div class="form-switch">
                            <p class="mb-0">Already have an account? <a href="#" onclick="showForm('login-form')" class="auth-link">Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showForm(formId) {
            document.querySelectorAll('.form-box').forEach(form => {
                form.classList.add('d-none');
                form.classList.remove('d-block');
            });
            document.getElementById(formId).classList.add('d-block');
            document.getElementById(formId).classList.remove('d-none');
        }

        // Role selection functionality
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options in this group
                const group = this.closest('.role-selector');
                group.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Add active class to clicked option
                this.classList.add('active');
                
                // Check the corresponding radio button
                const radio = this.querySelector('.role-radio');
                radio.checked = true;
            });
        });
    </script>

    <script>
        function togglePassword(id, icon) {
            const input = document.getElementById(id);
            const eyeIcon = icon.querySelector('i');
            
            if (input.type === "password") {
                input.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>

    <script>
        function showPasswordHint() {
            document.getElementById('password-hint').classList.remove('d-none');
        }

        function hidePasswordHint() {
            document.getElementById('password-hint').classList.add('d-none');
        }
    </script>

</body>
</html>