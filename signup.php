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
                            margin: 40px auto;
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