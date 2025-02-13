<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];  // Get user’s name
$role = $_SESSION['role'];  // Get user’s role

// Redirect URL based on role
$redirectUrl = ($role === 'admin') ? 'adminpannel.php' : 'dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="css/welcome.css">
    <script>
        // Redirect after 5 seconds
        setTimeout(function() {
            document.querySelector('.welcome-container').classList.add('explode'); // Trigger explosion effect
            setTimeout(function() {
                window.location.href = "<?php echo $redirectUrl; ?>";
            }, 1000); // Redirect after explosion animation
        }, 2000);
    </script>
</head>
<body>
    <div class="welcome-container">
        <h1 class="boom-text">Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
        <p class="fade-in">You have successfully logged in.</p>
        <a href="<?php echo $redirectUrl; ?>" class="btn">Go Now</a>
        <a href="logout.php" class="btn logout">Logout</a>
    </div>
</body>
</html>
