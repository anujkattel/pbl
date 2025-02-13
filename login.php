<?php
session_start();
include 'db.php';

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php"); // Redirect to welcome screen
    exit();
}

$error = ''; // Store error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name']; // Store user name for welcome page

        // If user is admin, open admin panel in a new tab
        if ($user['role'] === 'admin') {
            echo '<script>
                    window.open("adminpanel.php", "_blank");
                    window.location.href="welcome.php";
                  </script>';
            exit();
        } else {
            header("Location: welcome.php"); // Redirect to welcome screen
            exit();
        }
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p>Don't have an account? <a href="signup.php">Signup here</a></p>
    </div>
</body>
</html>
