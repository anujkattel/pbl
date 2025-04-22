<?php
session_start();
include 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $year_of_joining = trim($_POST['year_of_joining']);
    $branch = trim($_POST['branch']);
    $semester = trim($_POST['semester']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (!preg_match('/@gmail\.com$/', $email)) {
        $error = "Invalid email! Only '@gmail.com' emails are allowed.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Email already exists. Please login.";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 300; // 5 minutes
            $_SESSION['signup_data'] = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'year_of_joining' => $year_of_joining,
                'branch' => $branch,
                'semester' => $semester
            ];

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $_ENV['SMTP_PORT'];

                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Account OTP');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Signup';
                $mail->Body = "
                <!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Caveat&display=swap');
                
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: 'Poppins', sans-serif;
                            background: linear-gradient(135deg, #1c1c2d, #3a3a52);
                            color: #ffffff;
                            overflow-x: hidden;
                        }
                
                        .container {
                            max-width: 600px;
                            margin: 20px auto;
                            background: rgba(255, 255, 255, 0.05);
                            border-radius: 20px;
                            padding: 40px 30px;
                            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.6);
                            backdrop-filter: blur(10px);
                        }
                
                        .header {
                            text-align: center;
                            padding-bottom: 30px;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                        }
                
                        .header h1 {
                            font-family: 'Caveat', cursive;
                            font-size: 48px;
                            color: #ff7f50;
                            margin: 0;
                            text-shadow: 0 0 12px rgba(255, 127, 80, 0.7);
                        }
                
                        .content {
                            text-align: center;
                            padding: 30px 0;
                        }
                
                        .content p {
                            font-size: 18px;
                            line-height: 1.6;
                            color: #e2e2e2;
                            margin: 20px 0;
                        }
                
                        .otp-box {
                            display: inline-block;
                            margin: 25px auto;
                            padding: 20px 40px;
                            font-size: 42px;
                            font-weight: bold;
                            letter-spacing: 8px;
                            color: #ff4d4d;
                            background: linear-gradient(135deg, #fff, #f1f1f1);
                            border-radius: 15px;
                            box-shadow: 0 0 30px rgba(255, 77, 77, 0.6), inset 0 0 15px rgba(255, 77, 77, 0.4);
                            animation: pulse-glow 3s ease-in-out infinite;
                        }
                
                        @keyframes pulse-glow {
                            0%, 100% {
                                transform: scale(1);
                                box-shadow: 0 0 25px rgba(255, 77, 77, 0.6);
                            }
                            50% {
                                transform: scale(1.05);
                                box-shadow: 0 0 45px rgba(255, 77, 77, 1);
                            }
                        }
                
                        .footer {
                            text-align: center;
                            padding-top: 25px;
                            border-top: 1px solid rgba(255, 255, 255, 0.2);
                            font-size: 14px;
                            color: #bbbbbb;
                        }
                
                        @media (max-width: 600px) {
                            .otp-box {
                                font-size: 32px;
                                padding: 15px 30px;
                            }
                
                            .header h1 {
                                font-size: 36px;
                            }
                
                            .content p {
                                font-size: 16px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>OTP Mystique</h1>
                        </div>
                        <div class='content'>
                            <p>Hello Adventurer,</p>
                            <p>Your magical OTP is revealed below. Use it wisely, for it fades in <strong>5 minutes</strong>:</p>
                            <div class='otp-box'>$otp</div>
                            <p>Keep this code a secret and complete your quest promptly.</p>
                        </div>
                        <div class='footer'>
                            <p>© 2025 Your Legendary App. Enchantments Reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $mail->send();
                setcookie("otpverify", "true", time() + 3600 * 30, "/");
                header("Location: verify_otp.php");
                exit();
            } catch (Exception $e) {
                $error = "Failed to send OTP email. Error: {$mail->ErrorInfo}";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Campus Connect</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --danger: #dc3545;
            --success: #28a745;
            --border-radius: 0.5rem;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* padding: 1rem; */
            line-height: 1.6;
            color: var(--dark);
        }

        .signup-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .signup-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 2rem;
            text-align: center;
        }

        .signup-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .signup-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .signup-form {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e1e5ee;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 1rem;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 0.85rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            grid-column: span 2;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .password-strength {
            height: 4px;
            background: #e1e5ee;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: var(--danger);
            transition: var(--transition);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .alert {
                grid-column: span 1;
            }
        }

        @media (max-width: 576px) {
            .signup-container {
                max-width: 100%;
            }
            
            .signup-header {
                padding: 1.25rem 1.5rem;
            }
            
            .signup-form {
                padding: 1.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .signup-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <div class="signup-header">
            <p>Create your account to get started</p>
        </div>
        
        <form method="POST" action="" class="signup-form">
            <div class="form-grid">
                <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="johndoe123" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="year_of_joining">Year of Joining</label>
                    <select id="year_of_joining" name="year_of_joining" class="form-control" required>
                        <?php
                        $currentYear = date('Y');
                        for ($year = $currentYear; $year >= 2000; $year--) {
                            echo "<option value='$year'>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="branch">Branch</label>
                    <select id="branch" name="branch" class="form-control" required>
                        <option value="" disabled selected>Select your branch</option>
                        <option value="BCA">BCA</option>
                        <option value="CSE">Computer Science (CSE)</option>
                        <option value="ECE">Electronics (ECE)</option>
                        <option value="ME">Mechanical (ME)</option>
                        <option value="EE">Electrical Engineering (EE)</option>
                        <option value="civil">Civil</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" class="form-control" required>
                        <option value="" disabled selected>Select semester</option>
                        <option value="first">First</option>
                        <option value="second">Second</option>
                        <option value="third">Third</option>
                        <option value="fourth">Fourth</option>
                        <option value="fifth">Fifth</option>
                        <option value="sixth">Sixth</option>
                    </select>
                </div>
                
                <div class="form-group full-width">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" class="btn">Create Account</button>
                </div>
                
                <div class="form-footer full-width">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const passwordToggle = document.querySelector('.password-toggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordToggle.textContent = type === 'password' ? '👁️' : '🔒';
        });

        // Password strength indicator
        passwordInput.addEventListener('input', function() {
            const strengthBar = document.getElementById('password-strength-bar');
            const strength = calculatePasswordStrength(this.value);
            
            if (strength < 30) {
                strengthBar.style.backgroundColor = 'var(--danger)';
            } else if (strength < 70) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = 'var(--success)';
            }
            
            strengthBar.style.width = strength + '%';
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length contributes up to 40%
            strength += Math.min(password.length * 5, 40);
            
            // Presence of different character types
            if (password.match(/[a-z]/)) strength += 10;
            if (password.match(/[A-Z]/)) strength += 10;
            if (password.match(/[0-9]/)) strength += 10;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 10;
            
            // Bonus for length over 12
            if (password.length > 12) strength += 10;
            
            // Bonus for mixed case and numbers/symbols
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 10;
            if (password.match(/\d/) && password.match(/\W/)) strength += 10;
            
            return Math.min(strength, 100);
        }
    </script>
</body>

</html>