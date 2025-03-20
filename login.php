<?php
session_start();
include 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}

$error = '';  // Store error message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch the user details by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if the user is verified
        if ($user['is_verified'] == 0) {
            $error = "Please verify your email first.";
        } elseif (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name']; // Store user name for welcome page

            // Redirect admin and user
            if ($user['role'] === 'admin') {
                echo '<script>
                        window.open("adminpanel.php", "_blank");
                        window.location.href = "welcome.php";
                      </script>';
                exit();
            } else {
                header("Location: welcome.php");
                exit();
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "User does not exist!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        /* General Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        /* Body Styling */
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: #333;
        }

        /* Container Styling */
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

        /* Form Fields */
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            transition: 0.3s;
        }

        /* Input Focus Effect */
        input:focus {
            outline: none;
            border-color: #6a11cb;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        /* Button Styling */
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

        /* Message Styles */
        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #333;

        }

        /* Error and Success Messages */
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

        /* Link Styling */
        a {
            color: #2575fc;
            text-decoration: none;
            transition: 0.3s;
        }

        a:hover {
            color: #1e63d9;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            form {
                padding: 20px;
            }

            input,
            button {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <form method="POST" action="">
        <h2>Login</h2>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
        <div class="input-group">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" required>
        </div>
        <div class="input-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
        <p>Don't have an account? <a href="signup.php">Signup here</a></p>
    </form>
</body>

</html>