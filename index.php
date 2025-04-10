<?php
session_start();
include 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}
else{
    header("location: login.php");
    exit();
}
?>