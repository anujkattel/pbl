<?php
session_start();
include 'db.php';

// Ensure user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

$role = $_SESSION['role']; // Define $role

// Fetch filter values from the form submission
$candidate_filter = isset($_GET['candidate_filter']) ? $_GET['candidate_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Base query
$query = "
    SELECT users.name AS voter_name, users.email AS voter_email, 
           candidates.name AS candidate_name, candidates.email AS candidate_email, 
           votes.voted_at 
    FROM votes 
    JOIN users ON votes.voter_id = users.id 
    JOIN candidates ON votes.candidate_id = candidates.id 
    WHERE 1 = 1
";

// Apply filters if selected
$params = [];

if (!empty($candidate_filter)) {
  $query .= " AND candidates.name = :candidate_filter";
  $params[':candidate_filter'] = $candidate_filter;
}

if (!empty($date_filter)) {
  $query .= " AND DATE(votes.voted_at) = :date_filter";
  $params[':date_filter'] = $date_filter;
}

$query .= " ORDER BY votes.voted_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all candidates for the filter dropdown
$candidateStmt = $conn->prepare("SELECT DISTINCT name FROM candidates");
$candidateStmt->execute();
$candidates = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total number of votes
$totalVotesQuery = "SELECT COUNT(*) AS total_votes FROM votes";
$totalVotesStmt = $conn->prepare($totalVotesQuery);
$totalVotesStmt->execute();
$totalVotes = $totalVotesStmt->fetch(PDO::FETCH_ASSOC)['total_votes'];

// Count total votes by current user
$currentUserVotesQuery = "SELECT COUNT(*) AS user_votes FROM votes WHERE voter_id = :user_id";
$currentUserVotesStmt = $conn->prepare($currentUserVotesQuery);
$currentUserVotesStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$currentUserVotesStmt->execute();
$currentUserVotes = $currentUserVotesStmt->fetch(PDO::FETCH_ASSOC)['user_votes'];

// Count total votes per candidate
$candidateVotesQuery = "
    SELECT candidates.name AS candidate_name, COUNT(votes.id) AS vote_count
    FROM votes
    JOIN candidates ON votes.candidate_id = candidates.id
    GROUP BY votes.candidate_id
    ORDER BY vote_count DESC
";
$candidateVotesStmt = $conn->prepare($candidateVotesQuery);
$candidateVotesStmt->execute();
$candidateVotes = $candidateVotesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'include/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
  <div class="container mt-4">
    <h2 class="mb-4">Voters List with Filters</h2>

    <div class="votes-count">
      <div class="container mb-4">
        <div class="row">
          <div class="col-md-4">
            <div class="card bg-info text-white">
              <div class="card-body">
                <h5 class="card-title">Total Votes</h5>
                <p class="card-text fs-4"><?= $totalVotes ?></p>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card bg-warning text-dark">
              <div class="card-body">
                <h5 class="card-title">Votes by Candidate</h5>
                <ul class="list-group">
                  <?php foreach ($candidateVotes as $cv): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <?= htmlspecialchars($cv['candidate_name']) ?>
                      <span class="badge bg-primary rounded-pill"><?= $cv['vote_count'] ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" class="filter-form mb-4">
      <div class="row">
        <div class="col-md-4">
          <label for="candidate_filter">Filter by Candidate:</label>
          <select name="candidate_filter" id="candidate_filter" class="form-control">
            <option value="">All Candidates</option>
            <?php foreach ($candidates as $candidate): ?>
              <option value="<?= htmlspecialchars($candidate['name']); ?>"
                <?= ($candidate_filter == $candidate['name']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($candidate['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label for="date_filter">Filter by Date:</label>
          <input type="date" name="date_filter" id="date_filter" class="form-control" value="<?= $date_filter; ?>">
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary me-2">Filter</button>
          <a href="voters_list.php" class="btn btn-secondary">Clear</a>
        </div>
      </div>
    </form>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Voter Name</th>
          <th>Voter Email</th>
          <th>Candidate Name</th>
          <th>Candidate Email</th>
          <th>Voted At</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($voters)): ?>
          <tr>
            <td colspan="5" class="text-center">No records found</td>
          </tr>
        <?php else: ?>
          <?php foreach ($voters as $voter): ?>
            <tr>
              <td><?= htmlspecialchars($voter['voter_name']); ?></td>
              <td><?= htmlspecialchars($voter['voter_email']); ?></td>
              <td><?= htmlspecialchars($voter['candidate_name']); ?></td>
              <td><?= htmlspecialchars($voter['candidate_email']); ?></td>
              <td><?= htmlspecialchars($voter['voted_at']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/app.js"></script>
</body>
</html>
