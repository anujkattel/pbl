<?php
session_start();
include 'db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
  header("Location: users.php");
  exit();
}

$user_id = $_GET['id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo "<script>alert('User not found!'); window.location.href='users.php';</script>";
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = trim($_POST['name']);
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $year_of_joining = trim($_POST['year_of_joining']);
  $branch = trim($_POST['branch']);
  $role = $_POST['role'];

  // Update user details
  $updateStmt = $conn->prepare("UPDATE users SET name = :name, username = :username, email = :email, year_of_joining = :year_of_joining, branch = :branch, role = :role WHERE id = :id");
  $updateStmt->bindParam(':name', $name);
  $updateStmt->bindParam(':username', $username);
  $updateStmt->bindParam(':email', $email);
  $updateStmt->bindParam(':year_of_joining', $year_of_joining);
  $updateStmt->bindParam(':branch', $branch);
  $updateStmt->bindParam(':role', $role);
  $updateStmt->bindParam(':id', $user_id);

  if ($updateStmt->execute()) {
    echo "<script>alert('User updated successfully!'); window.location.href='adminpannel.php';</script>";
  } else {
    echo "<script>alert('Update failed!');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User</title>
  <style>
    .admin-container {
      width: 50%;
      margin: 50px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    h2 {
      text-align: center;
      color: #333;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      font-weight: bold;
    }

    input,
    select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .btn {
      padding: 10px;
      border: none;
      cursor: pointer;
      font-size: 16px;
      border-radius: 5px;
    }

    .btn:hover {
      opacity: 0.8;
    }

    .btn {
      background: #28a745;
      color: white;
    }

    .cancel {
      background: #dc3545;
      text-decoration: none;
      color: white;
      text-align: center;
      display: block;
    }
  </style>
</head>

<body>
  <div class="admin-container">
    <h2>Edit User</h2>
    <form method="POST">
      <label>Name:</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

      <label>Username:</label>
      <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

      <label>Email:</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

      <label>Year of Joining:</label>
      <select name="year_of_joining" required>
        <?php
        $currentYear = date("Y");
        for ($year = 2015; $year <= $currentYear; $year++) {
          echo "<option value='$year' " . ($user['year_of_joining'] == $year ? 'selected' : '') . ">$year</option>";
        }
        ?>
      </select>

      <label>Branch:</label>
      <select name="branch" required>
        <option value="BCA" <?php echo ($user['branch'] == "BCA") ? 'selected' : ''; ?>>BCA</option>
        <option value="MCA" <?php echo ($user['branch'] == "MCA") ? 'selected' : ''; ?>>MCA</option>
        <option value="B.Tech" <?php echo ($user['branch'] == "B.Tech") ? 'selected' : ''; ?>>B.Tech</option>
        <option value="B.Sc" <?php echo ($user['branch'] == "B.Sc") ? 'selected' : ''; ?>>B.Sc</option>
        <option value="CS" <?php echo ($user['branch'] == "CS") ? 'selected' : ''; ?>>CS</option>
      </select>

      <label>Role:</label>
      <select name="role" required>
        <option value="user" <?php echo ($user['role'] == "user") ? 'selected' : ''; ?>>User</option>
        <option value="admin" <?php echo ($user['role'] == "admin") ? 'selected' : ''; ?>>Admin</option>
      </select>

      <button type="submit" class="btn">Update User</button>
      <a href="users.php" class="btn cancel">Cancel</a>
    </form>
  </div>
</body>

</html>