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

// ✅ Fetch only approved candidates from the votes table
$stmt = $conn->prepare("SELECT * FROM votes WHERE status = 'approved'");
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Check if the user has already voted in the candidates table
$stmt = $conn->prepare("SELECT * FROM candidates WHERE voter_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$hasVoted = $stmt->rowCount() > 0;

// ✅ Handle voting logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && !$hasVoted) {
  // Use the value from the vote button
  $candidate_id = $_POST['vote'];

  // Ensure the selected candidate is approved
  $stmt = $conn->prepare("SELECT * FROM votes WHERE id = :candidate_id AND status = 'approved'");
  $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
  $stmt->execute();

  if ($stmt->rowCount() == 0) {
    echo "<script>alert('Invalid candidate selection.');</script>";
  } else {
    // Record the vote in the candidates table
    $stmt = $conn->prepare("INSERT INTO candidates (voter_id, candidate_id, voted_at) VALUES (:user_id, :candidate_id, NOW())");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
    $stmt->execute();

    // Increase the candidate's vote count in the votes table
    $stmt = $conn->prepare("UPDATE votes SET votes = votes + 1 WHERE id = :candidate_id");
    $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
    $stmt->execute();

    echo "<script>alert('Thank you for voting!'); window.location.href='vote.php';</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vote for Candidates</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
  <div class="content" id="content">
    <div class="container mt-4">
      <h2 class="mb-4">Vote for Your Candidate</h2>
      <?php if ($hasVoted): ?>
        <div class="alert alert-warning">You have already voted!</div>
      <?php else: ?>
        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to vote? This action cannot be undone.');">
          <div class="row">
            <?php foreach ($candidates as $candidate): ?>
              <div class="col-md-4">
                <div class="card p-3 mb-3">
                  <h4><?php echo htmlspecialchars($candidate['name']); ?></h4>
                  <p><?php echo htmlspecialchars($candidate['email']); ?></p>
                  <p><strong>Votes:</strong> <?php echo $candidate['votes'] ?? 0; ?></p>
                  <!-- The vote button sends the candidate's id as the vote value -->
                  <button type="submit" name="vote" value="<?php echo $candidate['id']; ?>" class="btn btn-primary">Vote</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
