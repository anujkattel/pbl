<?php
session_start();
include 'db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Handle bulk semester update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update'])) {
  $new_semester = filter_input(INPUT_POST, 'bulk_semester', FILTER_SANITIZE_NUMBER_INT);
  $selected_users = $_POST['selected_users'] ?? [];
  $filter_semester = isset($_POST['filter_semester']) ? filter_input(INPUT_POST, 'filter_semester', FILTER_SANITIZE_NUMBER_INT) : null;

  // Validate semester (1-8)
  if ($new_semester >= 1 && $new_semester <= 8) {
    if (!empty($selected_users)) {
      // Prepare the SQL statement
      $placeholders = implode(',', array_fill(0, count($selected_users), '?'));
      $sql = "UPDATE users SET semester = ? WHERE id IN ($placeholders)";
      $stmt = $conn->prepare($sql);

      // Bind parameters
      $params = array_merge([$new_semester], $selected_users);
      $stmt->execute($params);

      $updated_count = $stmt->rowCount();
      $_SESSION['success'] = "Successfully updated $updated_count user(s) to Semester $new_semester!";
    } else {
      $_SESSION['error'] = "No users selected for update.";
    }
  } else {
    $_SESSION['error'] = "Invalid semester selected.";
  }

  // Preserve filter in redirect
  $filter_param = $filter_semester ? "?filter=$filter_semester" : "";
  header("Location: adminpannel.php$filter_param");
  exit();
}

// Get filter from query parameter
$filter_semester = isset($_GET['filter']) ? filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT) : null;

// Build SQL query with optional filter
$sql = "SELECT id, name, email, year_of_joining, semester, branch, role FROM users";
if ($filter_semester >= 1 && $filter_semester <= 8) {
  $sql .= " WHERE semester = :filter_semester";
}

$stmt = $conn->prepare($sql);
if ($filter_semester >= 1 && $filter_semester <= 8) {
  $stmt->bindParam(':filter_semester', $filter_semester, PDO::PARAM_INT);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'include/sidebar.php'; ?>
<style>
  :root {
    --primary-color: #4a90e2;
    --primary-hover: #357abd;
    --danger-color: #e74c3c;
    --danger-hover: #c0392b;
    --success-color: #2ecc71;
    --success-hover: #27ae60;
    --warning-color: #f39c12;
    --warning-hover: #e67e22;
    --text-color: #2c3e50;
    --light-bg: #f8fafd;
    --border-color: #e9ecef;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
  }

  body {
    background-color: #f4f7fc;
    font-family: 'Inter', sans-serif;
    color: var(--text-color);
    line-height: 1.6;
  }

  .main-content {
    padding: 2rem;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin: 1.5rem;
    overflow-x: auto;
  }

  .main-content h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .bulk-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
  }

  .bulk-select {
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    font-size: 0.875rem;
    min-width: 180px;
  }

  .current-semester {
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    font-size: 0.875rem;
    min-width: 180px;
    background-color: #f8f9fa;
    color: #6c757d;
  }

  .user-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow);
  }

  .user-table th,
  .user-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
  }

  .user-table th {
    background: var(--light-bg);
    color: var(--text-color);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
  }

  .user-table tr:last-child td {
    border-bottom: none;
  }

  .user-table tr:hover {
    background: rgba(241, 245, 249, 0.5);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    gap: 0.5rem;
  }

  .btn-primary {
    background: var(--primary-color);
    color: white;
  }

  .btn-primary:hover {
    background: var(--primary-hover);
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
    transform: translateY(-2px);
  }

  .btn-danger {
    background: var(--danger-color);
    color: white;
  }

  .btn-danger:hover {
    background: var(--danger-hover);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    transform: translateY(-2px);
  }

  .btn-success {
    background: var(--success-color);
    color: white;
  }

  .btn-success:hover {
    background: var(--success-hover);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
    transform: translateY(-2px);
  }

  .btn-warning {
    background: var(--warning-color);
    color: white;
  }

  .btn-warning:hover {
    background: var(--warning-hover);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
    transform: translateY(-2px);
  }

  .checkbox-cell {
    width: 40px;
    text-align: center;
  }

  .checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .select-all-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .select-all-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  .semester-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .semester-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--text-color);
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 1.5rem;
      margin: 1rem;
    }

    .bulk-actions {
      flex-direction: column;
      align-items: flex-start;
    }

    .user-table th,
    .user-table td {
      padding: 0.75rem;
      font-size: 0.8125rem;
    }

    .btn {
      padding: 0.5rem 1rem;
      font-size: 0.8125rem;
    }

    .semester-controls {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
  }

  @media (max-width: 576px) {
    .main-content {
      padding: 1rem;
      margin: 0.75rem;
    }

    .main-content h1 {
      font-size: 1.5rem;
    }

    .user-table {
      font-size: 0.75rem;
    }

    .checkbox {
      width: 16px;
      height: 16px;
    }
  }

  .filter-controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
  }

  .filter-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--text-color);
    white-space: nowrap;
  }

  .filter-select {
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    font-size: 0.875rem;
    min-width: 180px;
  }

  .filter-btn {
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    background: var(--warning-color);
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: var(--transition);
  }

  .filter-btn:hover {
    background: var(--warning-hover);
    transform: translateY(-2px);
  }

  .filter-clear {
    padding: 0.75rem 1.25rem;
    border-radius: 6px;
    background: var(--danger-color);
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .filter-clear:hover {
    background: var(--danger-hover);
    transform: translateY(-2px);
  }

  .filter-active {
    background: var(--light-bg);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: 1rem;
    font-size: 0.875rem;
  }

  .filter-active span {
    font-weight: 600;
  }

  @media (max-width: 768px) {
    .filter-controls {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>

<main class="main-content">
  <h1>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    Manage Users
    <?php if ($filter_semester): ?>
      <div class="filter-active">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        <span>Filter:</span> Showing Semester <?php echo $filter_semester; ?> only
      </div>
    <?php endif; ?>
  </h1>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <?php echo $_SESSION['success'];
      unset($_SESSION['success']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <?php echo $_SESSION['error'];
      unset($_SESSION['error']); ?>
    </div>
  <?php endif; ?>

  <form method="GET" class="filter-controls">
    <span class="filter-label">Filter by Current Semester:</span>
    <select name="filter" class="filter-select">
      <option value="">All Semesters</option>
      <?php for ($i = 1; $i <= 8; $i++): ?>
        <option value="<?php echo $i; ?>" <?php echo $filter_semester == $i ? 'selected' : ''; ?>>
          Semester <?php echo $i; ?>
        </option>
      <?php endfor; ?>
    </select>
    <button type="submit" class="filter-btn">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
      </svg>
      Apply Filter
    </button>
    <?php if ($filter_semester): ?>
      <a href="adminpannel.php" class="filter-clear">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Clear Filter
      </a>
    <?php endif; ?>
  </form>

  <form method="POST" id="bulkForm">
    <input type="hidden" name="filter_semester" value="<?php echo $filter_semester; ?>">
    <div class="bulk-actions">
      <div class="select-all-container">
        <input type="checkbox" id="selectAll" class="select-all-checkbox">
        <label for="selectAll">Select All</label>
      </div>

      <div class="semester-controls">
        <div>
          <span class="semester-label">Update To:</span>
          <select name="bulk_semester" class="bulk-select" required>
            <option value="">-- Select Semester --</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
              <option value="<?php echo $i; ?>" <?php echo $filter_semester == $i ? 'disabled' : ''; ?>>
                Semester <?php echo $i; ?>
                <?php echo $filter_semester == $i ? '(current)' : ''; ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <button type="submit" name="bulk_update" class="btn btn-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        Update Selected
      </button>
    </div>

    <table class="user-table">
      <thead>
        <tr>
          <th class="checkbox-cell">
            <!-- Select all checkbox handled by JavaScript -->
          </th>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Year</th>
          <th>Current Semester</th>
          <th>Branch</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="9" class="text-center" style="padding: 2rem;">
              No users found <?php echo $filter_semester ? "in Semester $filter_semester" : ""; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $user): ?>
            <tr>
              <td class="checkbox-cell">
                <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" class="checkbox">
              </td>
              <td><?php echo $user['id']; ?></td>
              <td><?php echo htmlspecialchars($user['name']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td><?php echo $user['year_of_joining']; ?></td>
              <td>Semester <?php echo $user['semester']; ?></td>
              <td><?php echo htmlspecialchars($user['branch']); ?></td>
              <td><?php echo htmlspecialchars($user['role']); ?></td>
              <td>
                <div style="display: flex; gap: 0.5rem;">
                  <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                  </a>
                  <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </form>
</main>

<script>
  // Select all checkbox functionality
  document.getElementById('selectAll').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.checkbox');
    checkboxes.forEach(checkbox => {
      checkbox.checked = e.target.checked;
    });
  });

  // Form validation
  document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const selectedUsers = document.querySelectorAll('.checkbox:checked');
    const semesterSelect = document.querySelector('select[name="bulk_semester"]');
    const filterSemester = <?php echo $filter_semester ?: 'null'; ?>;

    if (selectedUsers.length === 0) {
      alert('Please select at least one user to update.');
      e.preventDefault();
      return;
    }

    if (semesterSelect.value === '') {
      alert('Please select a semester.');
      e.preventDefault();
      return;
    }

    if (filterSemester && semesterSelect.value == filterSemester) {
      if (!confirm('You are about to update users to their current semester. Continue anyway?')) {
        e.preventDefault();
        return;
      }
    }

    if (!confirm(`Are you sure you want to update ${selectedUsers.length} user(s) to Semester ${semesterSelect.value}?`)) {
      e.preventDefault();
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/app.js"></script>
</body>

</html>