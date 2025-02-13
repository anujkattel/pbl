<?php
session_start();
include 'db.php';

// Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = ''; // Store error message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim input data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordInput = $_POST['password'];
    $year_of_joining = trim($_POST['year_of_joining']);
    $branch = trim($_POST['branch']);

    // Validate email domain
    if (!preg_match('/@smit\.smu\.edu\.in$/', $email)) {
        $error = "Invalid email! Use an @smit.smu.edu.in email.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE email = :email OR username = :username");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['username'] === $username) {
                $error = "Username already exists. Please choose another.";
            } elseif ($existingUser['email'] === $email) {
                $error = "Email already exists. Please login instead.";
            }
        } else {
            // Hash password
            $password = password_hash($passwordInput, PASSWORD_BCRYPT);

            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, year_of_joining, branch) VALUES (:name, :username, :email, :password, :year_of_joining, :branch)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':year_of_joining', $year_of_joining);
            $stmt->bindParam(':branch', $branch);

            if ($stmt->execute()) {
                $success = "Signup successful! <a href='login.php'>Login here</a>";
            } else {
                $error = "Signup failed! Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="./css/signup.css">
</head>
<body>
    <div class="container">
        <h2>Signup</h2>
        <?php if (!empty($error)) { echo "<div class='alert error'>$error</div>"; } ?>
        <?php if (!empty($success)) { echo "<div class='alert success'>$success</div>"; } ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="year_of_joining">Year of Joining:</label>
                <select id="year_of_joining" name="year_of_joining" required>
                    <option value="">Select Year</option>
                    <?php
                        $currentYear = date("Y");
                        for ($year = 2015; $year <= $currentYear; $year++) {
                            echo "<option value='$year'>$year</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <label for="branch">Branch:</label>
                <select name="branch" id="branch" required>
                    <option value="BCA">BCA</option>
                    <option value="MCA">MCA</option>
                    <option value="B.Tech">B.Tech</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="CS">CS</option>
                </select>
            </div>
            <button type="submit" class="btn">Signup</button>
        </form>
        <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
