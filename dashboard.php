<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $edit_name = $_POST['edit_name'];
  $edit_username = $_POST['edit_username'];
  $edit_email = $_POST['edit_email'];

  // Handle profile pic upload
  if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $targetDir = "Uploads/";
    $fileName = basename($_FILES["profile_pic"]["name"]);
    $targetFile = $targetDir . time() . "_" . $fileName;

    // Delete previous profile picture if it exists and is not default.png
    if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png' && file_exists($user['profile_pic'])) {
      unlink($user['profile_pic']);
    }

    move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile);

    $stmt = $conn->prepare("UPDATE users SET name = :name, username = :username, email = :email, profile_pic = :pic WHERE id = :id");
    $stmt->bindParam(':pic', $targetFile);
  } else {
    $stmt = $conn->prepare("UPDATE users SET name = :name, username = :username, email = :email WHERE id = :id");
  }

  $stmt->bindParam(':name', $edit_name);
  $stmt->bindParam(':username', $edit_username);
  $stmt->bindParam(':email', $edit_email);
  $stmt->bindParam(':id', $user_id);
  $stmt->execute();

  header("Location: dashboard.php");
  exit();
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_profile_pic'])) {
  if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg' && file_exists($user['profile_pic'])) {
    unlink($user['profile_pic']);
  }
  $stmt = $conn->prepare("UPDATE users SET profile_pic = 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg' WHERE id = :id");
  $stmt->bindParam(':id', $user_id);
  $stmt->execute();
  header("Location: dashboard.php");
  exit();
}

// Handle candidate application
$stmt = $conn->prepare("SELECT * FROM candidates WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['apply'])) {
    $stmt = $conn->prepare("INSERT INTO candidates (user_id, name, username, email, status, semester, election_type) 
                            VALUES (:user_id, :name, :username, :email, 'pending', :semester, 'CR')");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':name', $user['name']);
    $stmt->bindParam(':username', $user['username']);
    $stmt->bindParam(':email', $user['email']);
    $stmt->bindParam(':semester', $user['semester']);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
  }

  if (isset($_POST['cancel'])) {
    $stmt = $conn->prepare("DELETE FROM candidates WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
  }

  if (isset($_POST['approve']) && isset($_POST['app_id'])) {
    $stmt = $conn->prepare("UPDATE candidates SET status = 'approved' WHERE user_id = :app_id");
    $stmt->bindParam(':app_id', $_POST['app_id']);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
  }

  if (isset($_POST['reject']) && isset($_POST['app_id'])) {
    $stmt = $conn->prepare("UPDATE candidates SET status = 'rejected' WHERE user_id = :app_id");
    $stmt->bindParam(':app_id', $_POST['app_id']);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
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
                  <div class="mb-3 text-center">
                    <label class="form-label">Profile Picture</label>
                    <div>
                      <img id="profilePreview" src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Preview" class="img-thumbnail mb-3" width="150" height="150">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Upload New Photo</label>
                    <input type="file" class="form-control" name="profile_pic" id="profilePicInput" accept="image/*">
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
                <button type="submit" name="apply" class="btn btn-primary">Retry Application</button>
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
              $stmt = $conn->prepare("SELECT * FROM candidates");
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
                      echo "<span class='badge bg-warning text-dark'>Pending</span>";
                    }
                    ?>
                  </td>
                  <td>
                    <?php if ($app['status'] == 'pending'): ?>
                      <form method="POST" action="">
                        <input type="hidden" name="app_id" value="<?php echo $app['user_id']; ?>">
                        <button type="submit" name="approve" class="btn btn-success btn-sm me-2">Approve</button>
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

<script>
  document.getElementById('profilePicInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
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