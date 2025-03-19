<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $username = trim($_POST['username']);

    // Validate email
    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    // Validate username (you can extend this to more validation if necessary)
    if (empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Username cannot be empty.']);
        exit;
    }

    // Check if the email already exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
        exit;
    }

    // Check if the username already exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
        exit;
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    session_start();
    $_SESSION['otp'] = $otp;

    // Send OTP via email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'anujkattel6@gmail.com';
        $mail->Password = 'tvfnfoijsmbrpffh';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('sender@gmail.com', 'Welcome to voting app');
        $mail->addAddress($email); // Receiver's email address

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Your OTP code is <strong>$otp</strong>";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Could not send OTP.']);
    }
}
?>
