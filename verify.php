<?php
session_start();
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token exists and is not yet verified
    $stmt = $conn->prepare("SELECT * FROM users WHERE token = :token AND is_verified = 0");
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Update the user to verified
        $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE token = :token");
        $updateStmt->bindParam(':token', $token);
        $updateStmt->execute();

        echo "<p>Account verified successfully! <a href='login.php'>Login</a></p>";
    } else {
        echo "<p>Invalid or already verified token.</p>";
    }
} else {
    echo "<p>Invalid verification link.</p>";
}
?>
