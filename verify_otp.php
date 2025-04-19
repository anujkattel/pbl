<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path if not using Composer

$error = '';
$success = '';
if (isset($_COOKIE["otpverify"])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $entered_otp = $_POST['otp'];

        if (time() > $_SESSION['otp_expiry']) {
            $error = "OTP expired. Please go back and try again.";
        } elseif ($entered_otp == $_SESSION['otp']) {
            $data = $_SESSION['signup_data'];

            $stmt = $conn->prepare("INSERT INTO users 
            (name, username, email, password, year_of_joining, branch, semester, is_verified) 
            VALUES (:name, :username, :email, :password, :year_of_joining, :branch, :semester, 1)");

            $stmt->execute([
                ':name' => $data['name'],
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':year_of_joining' => $data['year_of_joining'],
                ':branch' => $data['branch'],
                ':semester' => $data['semester']
            ]);

            // Send verification email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $_ENV['SMTP_PORT'];

                // Recipients
                $mail->setFrom($_ENV['SMTP_USERNAME'], 'welcome to voting app');
                $mail->addAddress($data['email'], $data['name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification Successful';
                $mail->Body = "
                    <h2>Email Verified</h2>
                    <p>Dear <b>{$data['name']},</b></p>
                    <p>Your email has been successfully verified. You can now log in to your account.</p>
                    <p>Thank you!</p>
                ";
                $mail->AltBody = "Dear {$data['name']},\n\nYour email has been successfully verified. You can now log in to your account.\n\nThank you!";

                $mail->send();
                $success = "OTP verified. Registration complete! A confirmation email has been sent.";
            } catch (Exception $e) {
                $success = "OTP verified. Registration complete! Failed to send confirmation email: {$mail->ErrorInfo}";
            }

            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            unset($_SESSION['signup_data']);

            $_SESSION['loggedin'] = true;
            $_SESSION['user_email'] = $data['email'];

            setcookie("otpverify", "true", time() - 3600000, "/");
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid OTP. Try again.";
        }
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(to right, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        input[type="text"]:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 5px rgba(118, 75, 162, 0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #5a67d8;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .error {
            background-color: #ffe6e6;
            color: #cc0000;
        }

        .success {
            background-color: #e6ffea;
            color: #28a745;
        }

        @media (max-width: 500px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Enter OTP</h2>
        <?php if (isset($error)) echo "<div class='message error'>$error</div>"; ?>
        <?php if (isset($success)) echo "<div class='message success'>$success</div>"; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <button type="submit">Verify</button>
        </form>
    </div>
</body>
<script>
    // Hide error and success messages on page load
    document.addEventListener('DOMContentLoaded', function() {
        const errorMessage = document.querySelector('.error');
        const successMessage = document.querySelector('.success');
        
        if (errorMessage) errorMessage.style.display = 'none';
        if (successMessage) successMessage.style.display = 'none';
    });
</script>
</html>