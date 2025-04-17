<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>voting app</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/d9b4604fa2.js" crossorigin="anonymous"></script>
  <style>
    body {
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
      flex-direction: row;
    }

    #sidebar {
      background-color: black;
      color: white;
      height: 100vh;
      width: 250px;
      transition: all 0.3s ease;
      position: sticky;
      top: 0;
    }

    .main-content {
      flex: 1;
      padding: 20px;
      overflow-x: hidden;
    }

    img.img-thumbnail {
      height: 150px;
      aspect-ratio: 1/1;
      object-fit: cover;
      border-radius: 50%;
    }

    .sidebar-toggle-btn {
      display: none;
    }

    /* Responsive behavior */
    @media (max-width: 768px) {
      #sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        z-index: 1000;
      }

      #sidebar.open {
        left: 0;
      }

      .sidebar-toggle-btn {
        display: block;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1100;
      }

      .main-content {
        padding: 20px;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar Toggle Button -->
  <button class="btn btn-primary sidebar-toggle-btn" id="sidebarToggle">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <nav class="d-flex flex-column p-3 text-white" id="sidebar">
    <h1 class="nav-item mb-3">Voting App</h1>
    <ul class="nav flex-column">
      <li class="nav-item mb-3">
        <a href="dashboard.php" class="nav-link text-white">
          <i class="fa-solid fa-house"></i> <span class="m-2">Home</span>
        </a>
      </li>
      <?php
      $role = $_SESSION['role']; 
      if ($role === 'user'): ?>
        <li class="nav-item mb-3">
          <a href="vote.php" class="nav-link text-white">
            <i class="fa-solid fa-user-check"></i> <span class="m-2">Vote</span>
          </a>
        </li>
        <li class="nav-item mb-3">
          <a href="notification.php" class="nav-link text-white">
            <i class="fa-solid fa-bell"></i> <span class="m-2">Notification</span>
          </a>
        </li>
      <?php endif; ?>

      <?php if ($role === 'admin'): ?>
        <li class="nav-item mb-3">
          <a href="adminpannel.php" class="nav-link text-white">
            <i class="fa-solid fa-tools"></i> <span class="m-2">Admin Panel</span>
          </a>
        </li>
        <li class="nav-item mb-3">
          <a href="result.php" class="nav-link text-white">
            <i class="fa-solid fa-chart-line"></i> <span class="m-2">Results</span>
          </a>
        </li>
        <li class="nav-item mb-3">
          <a href="sendnotifications.php" class="nav-link text-white">
            <i class="fa-solid fa-upload"></i> <span class="m-2">upload</span>
          </a>
        </li>
        <li class="nav-item mb-3">
          <a href="admin_voting_control.php" class="nav-link text-white">
          <i class="fa-solid fa-lock-open"></i><span class="m-2">open close voting</span>
          </a>
        </li>
      <?php endif; ?>

      <li class="nav-item mb-3">
        <a href="logout.php" class="nav-link text-white">
          <i class="fa-solid fa-right-from-bracket"></i> <span class="m-2">Logout</span>
        </a>
      </li>
    </ul>
  </nav>
