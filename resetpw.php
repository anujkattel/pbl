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
    <title>Reset Password</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: #333;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            transition: 0.3s;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #6a11cb;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        button:hover {
            background: #1e63d9;
        }

        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #333;
        }

        p[style*='color:red'] {
            color: #ff4d4d !important;
            background: #ffecec;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ff4d4d;
            margin-bottom: 15px;
        }

        p[style*='color:green'] {
            color: #28a745 !important;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #28a745;
            margin-bottom: 15px;
        }

        a {
            color: #2575fc;
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: #1e63d9;
            text-decoration: underline;
        }

        @media (max-width:600px) {
            form {
                padding: 20px;
            }

            input,
            button {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <form method="POST" action="">
        <h2>Reset Password</h2>
        <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

        <?php if (!isset($_SESSION['otp'])) { ?>
            <!-- Step 1: Request OTP -->
            <label>Email:</label>
            <input type="email" name="email" required>
            <button type="submit">Send OTP</button>
        <?php } elseif (isset($_SESSION['otp']) && !isset($_SESSION['otp_verified'])) { ?>
            <!-- Step 2: Verify OTP -->
            <label>Enter OTP:</label>
            <input type="text" name="otp" required>
            <button type="submit">Verify OTP</button>
        <?php } elseif (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) { ?>
            <!-- Step 3: Reset Password (only shown after OTP verification) -->
            <label>New Password:</label>
            <input type="password" name="password" required>
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            <button type="submit">Reset Password</button>
        <?php } ?>

        <p>Remember your password? <a href="login.php">Login here</a></p>
    </form>
</body>

</html>