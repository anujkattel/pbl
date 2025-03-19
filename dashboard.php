<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Retrieve current user data securely
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit();
}

// Check if the user has already applied
$stmt = $conn->prepare("SELECT * FROM votes WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$application = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Apply request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
  $stmt = $conn->prepare("INSERT INTO votes (user_id, name, username, email, status) 
                          VALUES (:user_id, :name, :username, :email, 'pending')");
  $stmt->bindParam(':user_id', $user_id);
  $stmt->bindParam(':name', $user['name']);
  $stmt->bindParam(':username', $user['username']);
  $stmt->bindParam(':email', $user['email']);
  $stmt->execute();

  header("Location: dashboard.php");
  exit();
}


// Handle Cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
  $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = :user_id");
  $stmt->bindParam(':user_id', $user_id);
  $stmt->execute();

  header("Location: dashboard.php");
  exit();
}
?>
<?php
// Handle Approve or Reject request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['approve']) && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $stmt = $conn->prepare("UPDATE votes SET status = 'approved' WHERE user_id = :app_id");
    $stmt->bindParam(':app_id', $app_id);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
  }

  if (isset($_POST['reject']) && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $stmt = $conn->prepare("UPDATE votes SET status = 'rejected' WHERE user_id = :app_id");
    $stmt->bindParam(':app_id', $app_id);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/d9b4604fa2.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="./css/admin.css">
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

  <div class="main-content">
    <div class="container mt-4">
      <div class="card">
        <div class="card-header">Dashboard</div>
        <div class="card-body">
          <h4 class="card-title">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h4>
          <form>
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
            </div>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
          </form>
        </div>
      </div>

      <?php if ($role === 'user'): ?>
        <div class="card mt-4">
          <div class="card-header">Application Status</div>
          <div class="card-body text-center">
            <?php if ($application): ?>
              <?php if ($application['status'] == 'pending'): ?>
                <div class="alert alert-info">Your application is under review.</div>
                <form method="POST" action="">
                  <button type="submit" name="cancel" class="btn btn-danger">Cancel Request</button>
                </form>
              <?php elseif ($application['status'] == 'approved'): ?>
                <div class="alert alert-success">Congratulations! Your application has been approved.</div>
                <form method="POST" action="">
                  <button type="submit" name="cancel" class="btn btn-danger">Withdraw Application</button>
                </form>
              <?php elseif ($application['status'] == 'rejected'): ?>
                <div class="alert alert-danger">Sorry, your application has been rejected.</div>
                <form method="POST" action="">
                  <button type="submit" name="apply" class="btn btn-success">Retry Again</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <form method="POST" action="">
                <button type="submit" name="apply" class="btn btn-primary">Apply for Candidate</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
        <div class="card mt-4">
          <div class="card-header">Manage Applications</div>
          <div class="card-body">
            <table class="table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $conn->prepare("SELECT * FROM votes");
                $stmt->execute();
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($applications as $app): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                    <td><?php echo htmlspecialchars($app['username']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td>
                      <?php
                      if ($app['status'] == 'approved') {
                        echo "<span class='badge bg-success'>Approved</span>";
                      } elseif ($app['status'] == 'rejected') {
                        echo "<span class='badge bg-danger'>Rejected</span>";
                      } else {
                        echo "<span class='badge bg-warning'>Pending</span>";
                      }
                      ?>
                    </td>
                    <td>
                      <?php if ($app['status'] == 'pending'): ?>
                        <form method="POST" action="">
                          <input type="hidden" name="app_id" value="<?php echo $app['user_id']; ?>">
                          <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                          <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                      <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>No Action</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>