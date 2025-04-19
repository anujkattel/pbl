<?php
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Count unread notifications for the badge
$unreadCount = 0;
if ($role === 'user') {
  $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE recipient_id = :user_id AND is_read = 0");
  $stmt->bindParam(':user_id', $user_id);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $unreadCount = $result['unread_count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Voting App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/d9b4604fa2.js" crossorigin="anonymous"></script>
  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --dark-color: #1a1a2e;
      --light-color: #f8f9fa;
      --accent-color: #4cc9f0;
      --danger-color: #f72585;
      --success-color: #4ad66d;
      --sidebar-width: 280px;
      --sidebar-collapsed-width: 80px;
    }

    body {
      margin: 0;
      padding: 0;
      list-style-type: none;
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7fa;
      display: flex;
      min-height: 100vh;
    }

    #sidebar {
      background: linear-gradient(135deg, var(--dark-color), #16213e);
      color: white;
      height: 100vh;
      width: var(--sidebar-width);
      position: sticky;
      top: 0;
      transition: all 0.3s ease;
      box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      z-index: 1000;
    }

    .sidebar-header {
      padding: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      text-align: center;
    }

    .sidebar-header h1 {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
      color: white;
      transition: all 0.3s ease;
    }

    .sidebar-logo {
      width: 40px;
      height: 40px;
      margin-right: 10px;
    }

    .nav {
      flex: 1;
      overflow-y: auto;
      padding: 1rem 0;
    }

    .nav-item {
      margin: 0.25rem 1rem;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .nav-item:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-item.active {
      background-color: var(--primary-color);
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.8);
      padding: 0.75rem 1rem;
      display: flex;
      align-items: center;
      text-decoration: none;
      transition: all 0.2s ease;
      border-radius: 8px;
    }

    .nav-link:hover {
      color: white;
      transform: translateX(5px);
    }

    .nav-link i {
      width: 24px;
      text-align: center;
      margin-right: 12px;
      font-size: 1.1rem;
    }

    .nav-link .link-text {
      transition: opacity 0.3s ease;
    }

    .badge-notification {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.7rem;
      padding: 0.25rem 0.5rem;
    }


    .sidebar-toggle-btn {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1100;
      background-color: var(--primary-color);
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      color: white;
      cursor: pointer;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .sidebar-toggle-btn:hover {
      background-color: var(--secondary-color);
      transform: scale(1.05);
    }

    /* Collapsed sidebar */
    #sidebar.collapsed {
      width: var(--sidebar-collapsed-width);
    }

    #sidebar.collapsed .sidebar-header h1,
    #sidebar.collapsed .link-text {
      opacity: 0;
      width: 0;
      height: 0;
      overflow: hidden;
    }

    #sidebar.collapsed .nav-link {
      justify-content: center;
    }

    #sidebar.collapsed .nav-link i {
      margin-right: 0;
      font-size: 1.3rem;
    }

    #sidebar.collapsed .badge-notification {
      right: 5px;
    }

    .main-content {
      width: 100%;
    }

    /* Responsive behavior */
    @media (max-width: 992px) {
      #sidebar {
        position: fixed;
        left: 0;
        transform: translateX(-100%);
        z-index: 1000;
      }

      #sidebar.open {
        transform: translateX(0);
      }

      .sidebar-toggle-btn {
        display: flex;
        align-items: center;
        justify-content: center;
      }

    }
  </style>
</head>

<body>

  <!-- Sidebar Toggle Button -->
  <button class="sidebar-toggle-btn" id="sidebarToggle">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-center">
      <img src="https://atlas-content-cdn.pixelsquid.com/stock-images/vote-check-mark-symbol-L8eyn1B-600.jpg" alt="Logo" class="sidebar-logo d-none d-lg-inline">
      <h1 class="d-none d-lg-inline">Voting App</h1>
    </div>

    <ul class="nav flex-column">
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link">
          <i class="fa-solid fa-house"></i>
          <span class="link-text">Dashboard</span>
        </a>
      </li>

      <?php if ($role === 'user'): ?>
        <li class="nav-item">
          <a href="vote.php" class="nav-link">
            <i class="fa-solid fa-check-to-slot"></i>
            <span class="link-text">Cast Vote</span>
          </a>
        </li>

        <li class="nav-item position-relative">
          <a href="notification.php" class="nav-link">
            <i class="fa-solid fa-bell"></i>
            <span class="link-text">Notifications</span>
            <?php if ($unreadCount > 0): ?>
              <span class="badge bg-danger badge-notification"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
        <li class="nav-item">
          <a href="adminpannel.php" class="nav-link">
            <i class="fa-solid fa-sliders"></i>
            <span class="link-text">Admin Panel</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="result.php" class="nav-link">
            <i class="fa-solid fa-chart-pie"></i>
            <span class="link-text">Election Results</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="sendnotifications.php" class="nav-link">
            <i class="fa-solid fa-bullhorn"></i>
            <span class="link-text">Send Alerts</span>
          </a>
        </li>

        <li class="nav-item">
          <a href="admin_voting_control.php" class="nav-link">
            <i class="fa-solid fa-toggle-on"></i>
            <span class="link-text">Voting Controls</span>
          </a>
        </li>
      <?php endif; ?>

      <div class="mt-auto p-3">
          <li class="nav-item">
            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');" class="nav-link text-danger">
              <i class="fa-solid fa-power-off"></i>
              <span class="link-text">Logout</span>
            </a>
          </li>
      </div>
    </ul>
  </nav>