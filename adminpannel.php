<?php
session_start();
include 'db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role']; // Define $role

// Fetch all users from the database
$stmt = $conn->prepare("SELECT id, name, username, email, year_of_joining, branch, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    margin: 20px;
  }

  .main-content h1 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 25px;
  }

  .user-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .user-table th,
  .user-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
  }

  .user-table th {
    background: #f8fafd;
    color: #2c3e50;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9rem;
  }

  .user-table tr:last-child td {
    border-bottom: none;
  }

  .user-table tr:hover {
    background: #f1f5f9;
    transition: background 0.3s ease;
  }

  .btnn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
  }

  .btnn.edit {
    background: #4a90e2;
    color: #ffffff;
    margin-right: 10px;
  }

  .btnn.edit:hover {
    background: #357abd;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
    transform: translateY(-2px);
  }

  .btnn.delete {
    background: #e74c3c;
    color: #ffffff;
  }

  .btnn.delete:hover {
    background: #c0392b;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    transform: translateY(-2px);
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 20px;
      margin: 15px;
    }

    h1 {
      font-size: 1.5rem;
    }

    .user-table th,
    .user-table td {
      padding: 10px;
      font-size: 0.85rem;
    }

    .btnn {
      padding: 6px 12px;
      font-size: 0.8rem;
    }
  }

  @media (max-width: 576px) {
    .user-table {
      display: block;
      overflow-x: auto;
    }

    .user-table th,
    .user-table td {
      font-size: 0.8rem;
    }
  }
</style>

<main class="main-content">
  <h1>Manage Users</h1>
  <table class="user-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Year of Joining</th>
        <th>Branch</th>
        <th>Role</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?php echo $user['id']; ?></td>
          <td><?php echo htmlspecialchars($user['name']); ?></td>
          <td><?php echo htmlspecialchars($user['username']); ?></td>
          <td><?php echo htmlspecialchars($user['email']); ?></td>
          <td><?php echo $user['year_of_joining']; ?></td>
          <td><?php echo htmlspecialchars($user['branch']); ?></td>
          <td><?php echo htmlspecialchars($user['role']); ?></td>
          <td>
            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btnn edit">Edit</a>
            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btnn delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/app.js"></script>
</body>
</html>