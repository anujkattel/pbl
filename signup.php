<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ✅ Validate the email domain
    if ((!preg_match('/@gmail\.com$/', $email))) {
        $error = "Invalid email! Only '@smit.smu.edu.in' emails are allowed.";
    } else {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Email already exists. Please login.";
        } else {
            $token = bin2hex(random_bytes(32)); // Generate verification token

            // Insert into database with `is_verified` flag set to 0
            $stmt = $conn->prepare("INSERT INTO users (name,username, email, password, token, is_verified) VALUES (:name,:username, :email, :password, :token, 0)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            // Send verification email
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $_ENV['SMTP_PORT'];

                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Account Verification');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email';

                $verificationLink = "http://localhost/group5/verify.php?token=$token";
                $mail->Body = "Click the link to verify your account: <a href='$verificationLink'>Verify Email</a>";

                $mail->send();
                $success = "Signup successful! Please check your email to verify your account.";
            } catch (Exception $e) {
                $error = "Failed to send verification email. Error: {$mail->ErrorInfo}";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Signup</title>
    <link rel="stylesheet" href="./css/signup.css">
    <style>
        /* General styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        /* Body styling */
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: #333;
        }

        /* Container for the form */
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

        /* Form fields */
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

        /* Input focus effect */
        input:focus {
            outline: none;
            border-color: #6a11cb;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        /* Button styling */
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

        /* Message styles */
        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #333;

        }

        /* Error and success messages */
        p[style*="color:red"] {
            color: #ff4d4d !important;
            background: #ffecec;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ff4d4d;
            margin-bottom: 15px;
        }

        p[style*="color:green"] {
            color: #28a745 !important;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #28a745;
            margin-bottom: 15px;
        }

        /* Link styling */
        a {
            color: #2575fc;
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: #1e63d9;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
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
        <h2>Signup</h2>
        <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>username:</label>
        <input type="text" name="username" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Signup</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
</body>

</html>