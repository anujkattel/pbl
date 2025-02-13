<?php
session_start();
include 'db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role']; // Define $role

// Fetch all users from the database
$stmt = $conn->prepare("SELECT id, name, username, email, year_of_joining, branch, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome for icons -->
  <script src="https://kit.fontawesome.com/d9b4604fa2.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/admin.css">
    <style>
    #sidebar {
      background-color: black;
      height: 100vh;
      width: 250px;
    }
    .content {
      flex: 1;
      padding: 20px;
    }
  </style>
</head>
<body class="d-flex">
  <!-- Sidebar Navigation -->
  <nav class="d-flex flex-column p-3 text-white" id="sidebar">
    <h4 class="text-center mt-3">Dashboard</h4>
    <ul class="nav flex-column">
      <li class="nav-item mb-3">
        <a href="dashboard.php" class="nav-link text-white">
          <i class="fa-solid fa-house"></i> <span class="m-2">Home</span>
        </a>
      </li>
      <?php if ($role === 'user'): ?>
        <li class="nav-item mb-3">
          <a href="vote.php" class="nav-link text-white">
            <i class="fa-solid fa-gear"></i> <span class="m-2">Vote User</span>
          </a>
        </li>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
        <li class="nav-item mb-3">
          <a href="adminpannel.php" class="nav-link text-white">
            <i class="fa-solid fa-gear"></i> <span class="m-2">Admin Panel</span>
          </a>
        </li>
        <li class="nav-item mb-3">
          <a href="result.php" class="nav-link text-white">
            <i class="fa-solid fa-chart-simple"></i> <span class="m-2">Result</span>
          </a>
        </li>
      <?php endif; ?>
      <li class="nav-item mb-3">
        <a href="logout.php" class="nav-link text-white">
          <i class="fa-solid fa-arrow-right"></i> <span class="m-2">Logout</span>
        </a>
      </li>
    </ul>
  </nav>
    <!-- Main Content -->
    <main class="main-content">
        <h1>Manage Users</h1>
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Year of Joining</th>
                    <th>Branch</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['year_of_joining']; ?></td>
                        <td><?php echo htmlspecialchars($user['branch']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn edit">Edit</a>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

</body>
</html>
