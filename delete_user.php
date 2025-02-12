<?php
session_start();

// Check if user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Delete the user based on the username passed in the URL
if (isset($_GET['id'])) {
    $username = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);

    header("Location: adminpannel.php");
    exit();
} else {
    die("Invalid request.");
}
?>