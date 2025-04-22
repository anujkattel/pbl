<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata');

session_start();
include 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// STEP 1: Request OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['otp']) && !isset($_POST['password'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 300;
            $_SESSION['reset_email'] = $email;
            unset($_SESSION['otp_verified']); // Always reset

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com';
                $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'your-app-password';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

                $mail->setFrom($mail->Username, 'Password Reset OTP');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Password Reset';
                $mail->Body = "<p>Your OTP is: <strong>$otp</strong></p>";
                $mail->send();
                $success = "OTP sent to your email!";
            } catch (Exception $e) {
                $error = "OTP email failed. Error: {$mail->ErrorInfo}";
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email']);
            }
        } else {
            $error = "No account found with that email!";
        }
    }
}

// STEP 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp']);

    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
        $error = "OTP expired or invalid. Request a new one.";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email'], $_SESSION['otp_verified']);
    } elseif ($entered_otp != $_SESSION['otp']) {
        $error = "Incorrect OTP. Try again.";
    } else {
        $_SESSION['otp_verified'] = true;
        $success = "OTP verified. You can now reset your password.";
    }
}

// STEP 3: Reset Password (Only if OTP verified)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        $error = "You must verify OTP before resetting your password.";
        // Clear all session variables if OTP is not verified
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email'], $_SESSION['otp_verified']);
    } else {
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $email = $_SESSION['reset_email'];
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();

                // Optional: send confirmation email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com';
                    $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'your-app-password';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

                    $mail->setFrom($mail->Username, 'Password Reset Confirmation');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Changed';
                    $mail->Body = "<p>Your password has been reset successfully.</p>";
                    $mail->send();
                } catch (Exception $e) {
                    // Optional: handle silently
                }

                $success = "Password has been reset! <a href='login.php'>Login here</a>.";
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email'], $_SESSION['otp_verified']);
            } else {
                $error = "Unexpected error. Try again.";
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
    <title>Reset Password | Campus Connect</title>
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
            padding: 1rem;
            line-height: 1.6;
            color: var(--dark);
        }

        .reset-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .reset-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 2rem;
            text-align: center;
        }

        .reset-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .reset-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .reset-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
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
            margin-top: 1.5rem;
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

        .otp-input {
            letter-spacing: 2px;
            font-family: monospace;
            font-size: 1.2rem;
            text-align: center;
        }

        .timer {
            text-align: center;
            margin: 0.5rem 0;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .resend-link {
            text-align: center;
            margin-top: 0.5rem;
        }

        .resend-link a {
            color: var(--primary);
            cursor: pointer;
        }

        .resend-link.disabled {
            color: var(--gray);
            cursor: not-allowed;
        }

        @media (max-width: 576px) {
            .reset-container {
                max-width: 100%;
            }
            
            .reset-header {
                padding: 1.25rem 1.5rem;
            }
            
            .reset-form {
                padding: 1.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reset-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Reset Your Password</h1>
            <p>Follow the steps to secure your account</p>
        </div>
        
        <form method="POST" action="" class="reset-form">
            <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

            <?php if (!isset($_SESSION['otp'])) { ?>
                <!-- Step 1: Request OTP -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="btn">Send Verification Code</button>
                
            <?php } elseif (isset($_SESSION['otp']) && !isset($_SESSION['otp_verified'])) { ?>
                <!-- Step 2: Verify OTP -->
                <div class="form-group">
                    <label for="otp">Verification Code</label>
                    <input type="text" id="otp" name="otp" class="form-control otp-input" placeholder="Enter 6-digit code" required maxlength="6">
                </div>
                <div class="timer" id="timer">Code expires in 04:59</div>
                <button type="submit" class="btn">Verify Code</button>
                <div class="resend-link" id="resend-link">
                    Didn't receive code? <a onclick="resendOTP()">Resend</a>
                </div>
                
            <?php } elseif (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) { ?>
                <!-- Step 3: Reset Password -->
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create new password" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            <?php } ?>

            <div class="form-footer">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const input = e.target.parentElement.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                e.target.textContent = type === 'password' ? '👁️' : '🔒';
            });
        });

        // OTP timer functionality
        <?php if (isset($_SESSION['otp_expiry'])): ?>
            let timeLeft = <?php echo $_SESSION['otp_expiry'] - time(); ?>;
            const timer = document.getElementById('timer');
            const resendLink = document.getElementById('resend-link');
            
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timer.textContent = `Code expires in ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timer.textContent = "Code expired";
                    resendLink.classList.remove('disabled');
                } else {
                    timeLeft--;
                }
            }
            
            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer();
            
            function resendOTP() {
                if (timeLeft > 0) return;
                
                // In a real implementation, this would make an AJAX call to resend OTP
                alert('New verification code sent to your email');
                timeLeft = 300; // Reset to 5 minutes
                resendLink.classList.add('disabled');
                updateTimer();
                timerInterval = setInterval(updateTimer, 1000);
            }
        <?php endif; ?>

        // Auto-advance OTP input
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
    </script>
</body>

</html>