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

// Check if voting_end_time column exists
$column_exists = false;
try {
  $conn->query("SELECT voting_end_time FROM settings LIMIT 1");
  $column_exists = true;
} catch (PDOException $e) {
  // Column doesn't exist
}

// Get semester-wise voting status and end time
$general_voting_open = false;
$caas_voting_open = false;
$voting_end_time = null;

$setting_stmt = $conn->prepare("SELECT election_type, voting_open" . ($column_exists ? ", voting_end_time" : "") . " FROM settings WHERE semester = :semester");
$setting_stmt->bindParam(':semester', $semester);
$setting_stmt->execute();
$settings = $setting_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($settings as $setting) {
  $is_open = $setting['voting_open'] == 1;
  if ($column_exists && $is_open && !empty($setting['voting_end_time'])) {
    $end_time = strtotime($setting['voting_end_time']);
    $now = time();
    $is_open = $end_time > $now;
    if ($is_open) {
      $voting_end_time = $setting['voting_end_time'];
    }
  }
  if ($setting['election_type'] === 'general' && $is_open) {
    $general_voting_open = true;
  }
  if ($setting['election_type'] === 'CAAS' && $is_open) {
    $caas_voting_open = true;
  }
}

// Fetch candidates
$stmt = $conn->prepare("SELECT * FROM candidates WHERE status = 'approved' AND semester = :semester AND election_type != 'CAAS'");
$stmt->bindParam(':semester', $semester);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$caas_stmt = $conn->prepare("SELECT * FROM candidates WHERE status = 'approved' AND semester = :semester AND election_type = 'CAAS'");
$caas_stmt->bindParam(':semester', $semester);
$caas_stmt->execute();
$caas_candidates = $caas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user already voted
$stmt = $conn->prepare("SELECT * FROM votes WHERE voter_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$hasVoted = $stmt->rowCount() > 0;

// Fetch voted candidate details if already voted
$votedCandidate = null;
if ($hasVoted) {
  $stmt = $conn->prepare("SELECT c.* FROM votes v JOIN candidates c ON v.candidate_id = c.id WHERE v.voter_id = :user_id");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $votedCandidate = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle voting logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && !$hasVoted) {
  $candidate_id = $_POST['vote'];

  // Check if selected candidate exists and matches user’s semester
  $stmt = $conn->prepare("SELECT * FROM candidates WHERE id = :candidate_id AND status = 'approved' AND semester = :semester");
  $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
  $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
  $stmt->execute();
  $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$candidate) {
    echo "<script>alert('Invalid candidate selection.');</script>";
  } else {
    // Check if voting is open for that election type
    $election_type = $candidate['election_type'];
    if (($election_type === 'general' && !$general_voting_open) || ($election_type === 'CAAS' && !$caas_voting_open)) {
      echo "<script>alert('Voting for this election type is currently closed.');</script>";
    } else {
      // Record vote
      $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, voted_at) VALUES (:user_id, :candidate_id, NOW())");
      $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
      $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_INT);
      $stmt->execute();

      echo "<script>alert('Thank you for voting!'); window.location.href='vote.php';</script>";
      exit();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vote for Your Candidate</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f7fc;
      font-family: 'Inter', sans-serif;
    }

    .candidate-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .candidate-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .voting-closed {
      opacity: 0.7;
      pointer-events: none;
    }

    .timer-progress {
      height: 8px;
      border-radius: 4px;
      background-color: #e9ecef;
      overflow: hidden;
    }

    .timer-progress-bar {
      height: 100%;
      background-color: #28a745;
      transition: width 1s linear;
    }
  </style>
</head>

<body>
  <?php include 'include/sidebar.php'; ?>

  <div class="main-content">
    <div class="container mt-5">
      <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Vote for Your Candidate</h2>
        <p class="text-gray-600">Semester: <?php echo htmlspecialchars($semester); ?></p>
      </div>

      <?php if (!$general_voting_open && !$caas_voting_open): ?>
        <div class="alert alert-danger rounded-lg shadow-sm">
          <strong>Voting Closed!</strong> Voting is currently closed for your semester.
        </div>
      <?php elseif ($hasVoted): ?>
        <div class="alert alert-warning rounded-lg shadow-sm">
          <strong>Vote Recorded!</strong> You have already cast your vote.
        </div>
        <?php if ($votedCandidate): ?>
          <div class="bg-white rounded-lg shadow-lg p-6 mt-4">
            <h5 class="text-lg font-semibold text-gray-800">You Voted For:</h5>
            <div class="mt-3">
              <p><strong>Name:</strong> <?php echo htmlspecialchars($votedCandidate['name']); ?></p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($votedCandidate['email']); ?></p>
              <p><strong>Election Type:</strong> <?php echo htmlspecialchars($votedCandidate['election_type']); ?></p>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <?php if (($general_voting_open || $caas_voting_open) && $voting_end_time && $column_exists): ?>
          <div class="bg-white rounded-lg shadow-lg p-6 mb-6" id="vote-timer-container">
            <h5 class="text-lg font-semibold text-gray-800">Voting Closes In:</h5>
            <p class="text-2xl font-bold text-gray-900" id="vote-timer">Loading...</p>
            <div class="timer-progress mt-3">
              <div class="timer-progress-bar" id="timer-progress-bar"></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($general_voting_open && count($candidates) > 0): ?>
          <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h4 class="text-xl font-semibold text-gray-800 mb-4">General Elections</h4>
            <div class="row g-4">
              <?php foreach ($candidates as $candidate): ?>
                <div class="col-md-4">
                  <div class="candidate-card bg-gray-50 rounded-lg shadow-sm p-5 border border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($candidate['name']); ?></h5>
                    <p class="text-gray-600"><?php echo htmlspecialchars($candidate['email']); ?></p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to vote for <?php echo htmlspecialchars($candidate['name']); ?>?');">
                      <button type="submit" name="vote" value="<?php echo $candidate['id']; ?>" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Vote
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($caas_voting_open && count($caas_candidates) > 0): ?>
          <div class="bg-white rounded-lg shadow-lg p-6">
            <h4 class="text-xl font-semibold text-gray-800 mb-4">CAAS Elections</h4>
            <div class="row g-4">
              <?php foreach ($caas_candidates as $candidate): ?>
                <div class="col-md-4">
                  <div class="candidate-card bg-gray-50 rounded-lg shadow-sm p-5 border border-green-200">
                    <h5 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($candidate['name']); ?></h5>
                    <p class="text-gray-600"><?php echo htmlspecialchars($candidate['email']); ?></p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to vote for <?php echo htmlspecialchars($candidate['name']); ?> in CAAS elections?');">
                      <button type="submit" name="vote" value="<?php echo $candidate['id']; ?>" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Vote CAAS
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (count($candidates) === 0 && count($caas_candidates) === 0): ?>
          <div class="alert alert-info rounded-lg shadow-sm">
            <strong>No Candidates!</strong> No candidates are available for your semester right now.
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Voting Timer with Progress Bar
    const endTime = new Date('<?php echo $voting_end_time; ?>').getTime();
    const timer = document.getElementById('vote-timer');
    const timerContainer = document.getElementById('vote-timer-container');
    const progressBar = document.getElementById('timer-progress-bar');
    const totalDuration = endTime - new Date('<?php echo date('Y-m-d H:i:s', strtotime($voting_end_time) - 24 * 3600); ?>').getTime();

    function updateTimer() {
      if (!endTime || isNaN(endTime) || !timer || !timerContainer) {
        if (timer) timer.innerHTML = 'No end time set';
        return;
      }

      const now = new Date().getTime();
      const distance = endTime - now;

      if (distance < 0) {
        timer.innerHTML = 'Voting has ended!';
        if (progressBar) progressBar.style.width = '0%';
        setTimeout(() => location.reload(), 1000);
        return;
      }

      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      timer.innerHTML = `${hours}h ${minutes}m ${seconds}s`;

      if (progressBar) {
        const progress = (distance / totalDuration) * 100;
        progressBar.style.width = `${progress}%`;
      }
    }

    if (timer) {
      setInterval(updateTimer, 1000);
      updateTimer();
    }
  </script>
</body>

</html>