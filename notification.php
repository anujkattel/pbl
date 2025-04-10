<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch the logged-in user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Vote for Candidates</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS for styling -->
  <!-- Bootstrap CSS for styling -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome for icons -->
  <script src="https://kit.fontawesome.com/d9b4604fa2.js" crossorigin="anonymous"></script>
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
  <link rel="stylesheet" href="./css/admin.css">
</head>
<body class="d-flex">
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
      <?php if ($role === 'user'): ?>
        <li class="nav-item mb-3">
          <a href="notification.php" class="nav-link text-white">
            <i class="fa-solid fa-bell"></i> <span class="m-2">Notification</span>
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
  <div class="main-content">
    <div class="container mt-4">
      <div class="container mt-4">
        <h2 class="mb-4">Notification</h2>
        <div class="body-notifiction">
            <div class="alert alert-primary" role="alert">
                <h4 class="alert-heading">Welcome to the Voting System!</h4>
                <p>Thank you for using our voting system. You can now vote for your favorite candidate.</p>
                <hr>
                <p class="mb-0">If you have any questions, feel free to contact us.</p>
            </div>
        </div>
      </div>
      <!-- Bootstrap Bundle JS -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>

</html>