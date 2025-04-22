<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
  header("Location: login.php");
  exit();
}

// Include database connection
require_once 'db.php';

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['created'])) {
  $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
  session_regenerate_id(true);
  $_SESSION['created'] = time();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
try {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
  }
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
  $_SESSION['error'] = "Database error. Please try again later.";
  header("Location: dashboard.php");
  exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  // Verify CSRF token
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: dashboard.php");
    exit();
  }

  // Validate inputs
  $edit_name = trim(filter_input(INPUT_POST, 'edit_name', FILTER_SANITIZE_STRING));
  $edit_username = trim(filter_input(INPUT_POST, 'edit_username', FILTER_SANITIZE_STRING));
  $edit_email = trim(filter_input(INPUT_POST, 'edit_email', FILTER_SANITIZE_EMAIL));

  if (empty($edit_name) || empty($edit_username) || empty($edit_email)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: dashboard.php");
    exit();
  }

  if (!filter_var($edit_email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: dashboard.php");
    exit();
  }

  // Handle profile pic upload
  $profile_pic_path = $user['profile_pic'];
  if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
    // Validate uploaded file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['profile_pic']['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
      $_SESSION['error'] = "Only JPG, PNG, and GIF images are allowed.";
      header("Location: dashboard.php");
      exit();
    }

    // Check file size (max 2MB)
    if ($_FILES['profile_pic']['size'] > 2097152) {
      $_SESSION['error'] = "Image size must be less than 2MB.";
      header("Location: dashboard.php");
      exit();
    }

    $targetDir = "Uploads/";
    if (!is_dir($targetDir)) {
      mkdir($targetDir, 0755, true);
    }

    // Generate unique filename
    $fileExt = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('profile_', true) . '.' . strtolower($fileExt);
    $targetFile = $targetDir . $fileName;

    // Delete previous profile picture if it exists and is not default
    if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg' && file_exists($user['profile_pic'])) {
      unlink($user['profile_pic']);
    }

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
      $profile_pic_path = $targetFile;
    } else {
      $_SESSION['error'] = "Failed to upload profile picture.";
      header("Location: dashboard.php");
      exit();
    }
  }

  // Update user profile
  try {
    $stmt = $conn->prepare("UPDATE users SET name = :name, username = :username, email = :email, profile_pic = :pic WHERE id = :id");
    $stmt->bindParam(':name', $edit_name, PDO::PARAM_STR);
    $stmt->bindParam(':username', $edit_username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $edit_email, PDO::PARAM_STR);
    $stmt->bindParam(':pic', $profile_pic_path, PDO::PARAM_STR);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: dashboard.php");
    exit();
  } catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to update profile. Please try again.";
    header("Location: dashboard.php");
    exit();
  }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_profile_pic'])) {
  // Verify CSRF token
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: dashboard.php");
    exit();
  }

  if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg' && file_exists($user['profile_pic'])) {
    unlink($user['profile_pic']);
  }
  
  try {
    $stmt = $conn->prepare("UPDATE users SET profile_pic = 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg' WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success'] = "Profile picture removed successfully!";
    header("Location: dashboard.php");
    exit();
  } catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to remove profile picture. Please try again.";
    header("Location: dashboard.php");
    exit();
  }
}

// Handle candidate application
try {
  $stmt = $conn->prepare("SELECT * FROM candidates WHERE user_id = :user_id");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $application = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
  $_SESSION['error'] = "Failed to check application status. Please try again.";
  header("Location: dashboard.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token for all POST actions
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: dashboard.php");
    exit();
  }

  if (isset($_POST['apply'])) {
    try {
      $stmt = $conn->prepare("INSERT INTO candidates (user_id, name, username, email, status, semester, election_type) 
                              VALUES (:user_id, :name, :username, :email, 'pending', :semester, 'CR')");
      $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
      $stmt->bindParam(':name', $user['name'], PDO::PARAM_STR);
      $stmt->bindParam(':username', $user['username'], PDO::PARAM_STR);
      $stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
      $stmt->bindParam(':semester', $user['semester'], PDO::PARAM_STR);
      $stmt->execute();
      
      $_SESSION['success'] = "Application submitted successfully!";
      header("Location: dashboard.php");
      exit();
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $_SESSION['error'] = "Failed to submit application. Please try again.";
      header("Location: dashboard.php");
      exit();
    }
  }

  if (isset($_POST['cancel'])) {
    try {
      $stmt = $conn->prepare("DELETE FROM candidates WHERE user_id = :user_id");
      $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
      $stmt->execute();
      
      $_SESSION['success'] = "Application cancelled successfully!";
      header("Location: dashboard.php");
      exit();
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $_SESSION['error'] = "Failed to cancel application. Please try again.";
      header("Location: dashboard.php");
      exit();
    }
  }

  if (isset($_POST['approve']) && isset($_POST['app_id'])) {
    $app_id = filter_input(INPUT_POST, 'app_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
      $stmt = $conn->prepare("UPDATE candidates SET status = 'approved' WHERE user_id = :app_id");
      $stmt->bindParam(':app_id', $app_id, PDO::PARAM_INT);
      $stmt->execute();
      
      $_SESSION['success'] = "Application approved successfully!";
      header("Location: dashboard.php");
      exit();
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $_SESSION['error'] = "Failed to approve application. Please try again.";
      header("Location: dashboard.php");
      exit();
    }
  }

  if (isset($_POST['reject']) && isset($_POST['app_id'])) {
    $app_id = filter_input(INPUT_POST, 'app_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
      $stmt = $conn->prepare("UPDATE candidates SET status = 'rejected' WHERE user_id = :app_id");
      $stmt->bindParam(':app_id', $app_id, PDO::PARAM_INT);
      $stmt->execute();
      
      $_SESSION['success'] = "Application rejected successfully!";
      header("Location: dashboard.php");
      exit();
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      $_SESSION['error'] = "Failed to reject application. Please try again.";
      header("Location: dashboard.php");
      exit();
    }
  }
}
?>

<?php include 'include/sidebar.php'; ?>
<style>
  body {
    background-color: #f4f7fc;
    font-family: 'Inter', sans-serif;
    color: #333;
  }

  .main-content {
    padding: 30px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  }

  .card {
    border: none;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  /* .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
  } */

  .card-header {
    background: #4a90e2;
    color: #ffffff;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    font-weight: 600;
    padding: 15px 20px;
  }

  .profile-hero {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 25px;
  }

  .profile-pic {
    border-radius: 50%;
    border: 4px solid #4a90e2;
    transition: transform 0.3s ease;
  }

  .profile-pic:hover {
    transform: scale(1.05);
  }

  .btn-primary {
    background: #4a90e2;
    border: none;
    color: #ffffff;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    transition: background 0.3s ease, box-shadow 0.3s ease;
  }

  .btn-primary:hover {
    background: #357abd;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
  }

  .btn-danger {
    background: #e74c3c;
    border: none;
    color: #ffffff;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    transition: background 0.3s ease, box-shadow 0.3s ease;
  }

  .btn-danger:hover {
    background: #c0392b;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
  }

  .modal-content {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    animation: fadeIn 0.3s ease-in-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .modal-header {
    background: #4a90e2;
    color: #ffffff;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    border-bottom: none;
  }

  #profilePreview {
    border-radius: 50%;
    border: 3px solid #4a90e2;
    transition: transform 0.3s ease;
  }

  #profilePreview:hover {
    transform: rotate(5deg);
  }

  .form-control {
    border-radius: 8px;
    border: 1px solid #d1d9e6;
    padding: 10px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }

  .form-control:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 8px rgba(74, 144, 226, 0.3);
    outline: none;
  }

  .form-label {
    font-weight: 500;
    color: #333;
  }

  .alert {
    border-radius: 8px;
    padding: 15px;
    animation: slideIn 0.5s ease-in-out;
  }

  @keyframes slideIn {
    from {
      transform: translateY(-20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .table {
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
  }

  .table th {
    background: #f8fafd;
    color: #333;
    font-weight: 600;
  }

  .table td {
    vertical-align: middle;
  }

  .badge {
    padding: 8px 12px;
    border-radius: 12px;
    font-weight: 500;
  }

  @media (max-width: 576px) {
    .modal-dialog {
      margin: 15px;
    }

    .profile-hero {
      flex-direction: column;
      text-align: center;
    }

    .profile-pic {
      width: 120px !important;
      height: 120px !important;
    }

    .modal-body {
      padding: 20px;
    }

    .card-header {
      font-size: 1.1rem;
    }
  }
</style>
<div class="main-content">
  <div class="container mt-4">
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Dashboard</div>
      <div class="card-body">
        <div class="profile-hero">
          <?php
          $profilePic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg';
          ?>
          <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic" width="150" height="150">
          <div>
            <h4 class="card-title">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h4>
            <p class="text-muted">Manage your profile and applications seamlessly.</p>
          </div>
        </div>
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
          <div class="mb-3">
            <label class="form-label">Semester</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['semester']); ?>" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label">Year of Joining</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['year_of_joining'] ?? 'N/A'); ?>" disabled>
          </div>
        </form>

        <div class="mt-4">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile</button>
        </div>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form method="POST" action="" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <div class="mb-3 text-center">
                    <label class="form-label">Profile Picture</label>
                    <div>
                      <img id="profilePreview" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Preview" class="img-thumbnail mb-3" width="150" height="150">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Upload New Photo (Max 2MB)</label>
                    <input type="file" class="form-control" name="profile_pic" id="profilePicInput" accept="image/jpeg, image/png, image/gif">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="edit_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="edit_username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="edit_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                  </div>
                  <button type="submit" name="update_profile" class="btn btn-primary w-100">Save Changes</button>
                </form>
                <form method="POST" action="" class="mt-3">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button type="submit" name="remove_profile_pic" class="btn btn-danger btn-sm w-100">Remove Profile Picture</button>
                </form>
              </div>
            </div>
          </div>
        </div>
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
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="cancel" class="btn btn-danger">Cancel Request</button>
              </form>
            <?php elseif ($application['status'] == 'approved'): ?>
              <div class="alert alert-success">Congratulations! Your application has been approved.</div>
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="cancel" class="btn btn-danger">Withdraw Application</button>
              </form>
            <?php elseif ($application['status'] == 'rejected'): ?>
              <div class="alert alert-danger">Sorry, your application has been rejected.</div>
              <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="apply" class="btn btn-primary">Retry Application</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <form method="POST" action="">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                <th>Semester</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $stmt = $conn->prepare("SELECT * FROM candidates");
                $stmt->execute();
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($applications as $app): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                    <td><?php echo htmlspecialchars($app['username']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td><?php echo htmlspecialchars($app['semester']); ?></td>
                    <td>
                      <?php
                      if ($app['status'] == 'approved') {
                        echo "<span class='badge bg-success'>Approved</span>";
                      } elseif ($app['status'] == 'rejected') {
                        echo "<span class='badge bg-danger'>Rejected</span>";
                      } else {
                        echo "<span class='badge bg-warning text-dark'>Pending</span>";
                      }
                      ?>
                    </td>
                    <td>
                      <?php if ($app['status'] == 'pending'): ?>
                        <form method="POST" action="">
                          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                          <input type="hidden" name="app_id" value="<?php echo $app['user_id']; ?>">
                          <button type="submit" name="approve" class="btn btn-success btn-sm me-2">Approve</button>
                          <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                      <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>No Action</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach;
              } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                echo "<tr><td colspan='6' class='text-center text-danger'>Error loading applications</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  document.getElementById('profilePicInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      // Check file size
      if (file.size > 2097152) {
        alert('File size must be less than 2MB');
        event.target.value = '';
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('profilePreview').src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/app.js"></script>
</body>
</html>