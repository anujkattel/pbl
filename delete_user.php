<?php
session_start();
include 'db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prevent deleting the logged-in admin
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own account!'); window.location.href='adminpannel.php';</script>";
        exit();
    }

    // Delete the user from the database
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        echo "<script>alert('User deleted successfully!'); window.location.href='adminpannel.php';</script>";
    } else {
        echo "<script>alert('Failed to delete user.'); window.location.href='adminpannel.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href='adminpannel.php';</script>";
}
?>
