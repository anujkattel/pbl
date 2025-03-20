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
           votes.name AS candidate_name, votes.email AS candidate_email, 
           candidates.voted_at 
    FROM candidates 
    JOIN users ON candidates.voter_id = users.id 
    JOIN votes ON candidates.candidate_id = votes.id 
    WHERE 1 = 1
";

// Apply filters if selected
$params = [];

if (!empty($candidate_filter)) {
    $query .= " AND votes.name = :candidate_filter";
    $params[':candidate_filter'] = $candidate_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(candidates.voted_at) = :date_filter";
    $params[':date_filter'] = $date_filter;
}

$query .= " ORDER BY candidates.voted_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all candidates for the filter dropdown
$candidateStmt = $conn->prepare("SELECT DISTINCT name FROM votes");
$candidateStmt->execute();
$candidates = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Voters List with Filters</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    .filter-form {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    .filter-form select, .filter-form input {
      padding: 5px;
    }
    .table th, .table td {
      vertical-align: middle;
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
    <h2 class="mb-4">Voters List with Filters</h2>

    <!-- Filter Form -->
    <form method="GET" action="" class="filter-form">
      <div>
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

      <div>
        <label for="date_filter">Filter by Date:</label>
        <input type="date" name="date_filter" id="date_filter" class="form-control" value="<?= $date_filter; ?>">
      </div>

      <div>
        <button type="submit" class="btn btn-primary mt-4">Filter</button>
        <a href="voters_list.php" class="btn btn-secondary mt-4">Clear</a>
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
</body>

</html>
