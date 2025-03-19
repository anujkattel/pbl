<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userOtp = trim($_POST['otp']);

    if ($userOtp == $_SESSION['otp']) {
        echo "<p>OTP verified successfully!</p>";
        unset($_SESSION['otp']);
    } else {
        echo "<p>Invalid OTP. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>
    <form method="POST" action="">
        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="otp" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
