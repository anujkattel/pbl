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
    $year_of_joining = trim($_POST['year_of_joining']);
    $branch = trim($_POST['branch']);
    $semester = trim($_POST['semester']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ✅ Validate the email domain
    if (!preg_match('/@gmail\.com$/', $email)) {
        $error = "Invalid email! Only '@gmail.com' emails are allowed.";
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
            $stmt = $conn->prepare("INSERT INTO users 
                (name, username, email, password, year_of_joining, branch,semester, token, is_verified) 
                VALUES (:name, :username, :email, :password, :year_of_joining, :branch,:semester, :token, 0)");

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':year_of_joining', $year_of_joining);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':semester', $semester);
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

                $verificationLink = "http://localhost/projects/group5/verify.php?token=$token";
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <h2 style='color: #4CAF50;'>Verify Your Email</h2>
        <p>Thank you for registering. Please verify your email by clicking the button below:</p>
        <a href='$verificationLink' style='display: inline-block; padding: 10px 20px; font-size: 16px; color: #fff; background-color: #007BFF; text-decoration: none; border-radius: 5px;'>Verify Email</a>
        <p>If you did not request this, please ignore this email.</p>
        <hr>
        <p style='font-size: 12px; color: #888;'>© 2025 <a href='https://smu.edu.in/smit.html/'>smit.</a> All rights reserved.</p>
    </div>

                ";

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

        input,
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus,
        select:focus {
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

        a {
            color: #2575fc;
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: #1e63d9;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            form {
                padding: 20px;
            }

            input,
            select,
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

        <label>Username:</label>
        <input type="text" name="username" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Year of Joining:</label>
        <select name="year_of_joining" required>
            <?php
            $currentYear = date('Y');
            for ($year = 2000; $year <= $currentYear; $year++) {
                echo "<option value='$year'>$year</option>";
            }
            ?>
        </select>

        <label>Branch:</label>
        <select name="branch" required>
            <option value="BCA">BCA</option>
            <option value="CSE">Computer Science (CSE)</option>
            <option value="ECE">Electronics (ECE)</option>
            <option value="ME">Mechanical (ME)</option>
            <option value="EE">Electrical Engineering (EE)</option>
            <option value="civil">Civil</option>
        </select>
        <label>Semester:</label>
        <select name="semester" required>
            <option value="first">first </option>
            <option value="second">second </option>
            <option value="third">Third </option>
            <option value="fourth">fourth</option>
            <option value="fifth">fifth</option>
            <option value="sixth">sixth</option>
        </select>
        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Signup</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>

</body>

</html>