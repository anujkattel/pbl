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

// Fetch logged-in user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$semester = $user['semester'];

// Fetch general election candidates (non-CAAS)
$stmt = $conn->prepare("SELECT * FROM candidates WHERE status = 'approved' AND semester = :semester AND election_type != 'CAAS'");
$stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch CAAS election candidates
$caas_stmt = $conn->prepare("SELECT * FROM candidates WHERE status = 'approved' AND semester = :semester AND election_type = 'CAAS'");
$caas_stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
$caas_stmt->execute();
$caas_candidates = $caas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user already voted
$stmt = $conn->prepare("SELECT * FROM votes WHERE voter_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$hasVoted = $stmt->rowCount() > 0;

// Handle voting logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && !$hasVoted) {
    $candidate_id = $_POST['vote'];

    // Validate selected candidate with semester match
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE id = :candidate_id AND status = 'approved' AND semester = :semester");
    $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
    $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "<script>alert('Invalid candidate selection.');</script>";
    } else {
        // Record the vote
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, voted_at) VALUES (:user_id, :candidate_id, NOW())");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
        $stmt->execute();

        echo "<script>alert('Thank you for voting!'); window.location.href='vote.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vote for Candidates</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
</head>
<body class="d-flex">

<!-- Sidebar -->
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
<div class="main-content container mt-4">
  <h2 class="mb-4">Vote for Your Candidate (Semester: <?php echo htmlspecialchars($semester); ?>)</h2>

  <?php if ($hasVoted): ?>
    <div class="alert alert-warning">You have already voted!</div>
  <?php else: ?>
    <!-- General Election Section -->
    <?php if (count($candidates) > 0): ?>
      <form method="POST" onsubmit="return confirm('Are you sure you want to vote? This action cannot be undone.');">
        <h4>General Elections</h4>
        <div class="row">
          <?php foreach ($candidates as $candidate): ?>
            <div class="col-md-4">
              <div class="card p-3 mb-3">
                <h4><?php echo htmlspecialchars($candidate['name']); ?></h4>
                <p><?php echo htmlspecialchars($candidate['email']); ?></p>
                <button type="submit" name="vote" value="<?php echo $candidate['id']; ?>" class="btn btn-primary">Vote</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </form>
    <?php endif; ?>

    <!-- CAAS Election Section -->
    <?php if (count($caas_candidates) > 0): ?>
      <form method="POST" onsubmit="return confirm('Are you sure you want to vote in CAAS elections?');">
        <h4 class="mt-5">CAAS Elections</h4>
        <div class="row">
          <?php foreach ($caas_candidates as $candidate): ?>
            <div class="col-md-4">
              <div class="card border-success p-3 mb-3">
                <h4><?php echo htmlspecialchars($candidate['name']); ?></h4>
                <p><?php echo htmlspecialchars($candidate['email']); ?></p>
                <button type="submit" name="vote" value="<?php echo $candidate['id']; ?>" class="btn btn-success">Vote CAAS</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
