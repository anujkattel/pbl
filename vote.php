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

// Get semester-wise voting status
$general_voting_open = false;
$caas_voting_open = false;

$setting_stmt = $conn->prepare("SELECT election_type, voting_open FROM settings WHERE semester = :semester");
$setting_stmt->bindParam(':semester', $semester);
$setting_stmt->execute();
$settings = $setting_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($settings as $setting) {
    if ($setting['election_type'] === 'general' && $setting['voting_open'] == 1) {
        $general_voting_open = true;
    }
    if ($setting['election_type'] === 'CAAS' && $setting['voting_open'] == 1) {
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

<?php include 'include/sidebar.php'; ?>

<div class="main-content container mt-4">
  <h2 class="mb-4">Vote for Your Candidate (Semester: <?php echo htmlspecialchars($semester); ?>)</h2>

  <?php if (!$general_voting_open && !$caas_voting_open): ?>
    <div class="alert alert-danger">Voting is currently closed for your semester.</div>
  <?php elseif ($hasVoted): ?>
    <div class="alert alert-warning">You have already voted!</div>
    <?php if ($votedCandidate): ?>
      <div class="card mt-3 p-3">
        <h5>You voted for:</h5>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($votedCandidate['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($votedCandidate['email']); ?></p>
        <p><strong>Election Type:</strong> <?php echo htmlspecialchars($votedCandidate['election_type']); ?></p>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <?php if ($general_voting_open && count($candidates) > 0): ?>
      <form method="POST" onsubmit="return confirm('Are you sure you want to vote?');">
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

    <?php if ($caas_voting_open && count($caas_candidates) > 0): ?>
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

    <?php if (count($candidates) === 0 && count($caas_candidates) === 0): ?>
      <div class="alert alert-info">No candidates available for your semester right now.</div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script src="./js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
