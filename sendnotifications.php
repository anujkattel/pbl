<?php
session_start();
include 'db.php';
require_once 'mail.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, username, email FROM users ORDER BY username");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
  $content = trim($_POST['notification_content']);
  $recipient_id = $_POST['recipient_id'];
  $send_email = isset($_POST['send_email']);

  if (!empty($content)) {
    if ($recipient_id === 'all') {
      foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO notifications (sender_id, recipient_id, content, created_at) VALUES (:sender_id, :recipient_id, :content, NOW())");
        $stmt->bindParam(':sender_id', $user_id);
        $stmt->bindParam(':recipient_id', $user['id']);
        $stmt->bindParam(':content', $content);
        $stmt->execute();

        if ($send_email && !empty($user['email'])) {
          sendEmail($user['email'], "New Notification from Admin", $content);
        }
      }
    } else {
      $stmt = $conn->prepare("INSERT INTO notifications (sender_id, recipient_id, content, created_at) VALUES (:sender_id, :recipient_id, :content, NOW())");
      $stmt->bindParam(':sender_id', $user_id);
      $stmt->bindParam(':recipient_id', $recipient_id);
      $stmt->bindParam(':content', $content);
      $stmt->execute();

      $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
      $stmt->bindParam(':id', $recipient_id);
      $stmt->execute();
      $email = $stmt->fetchColumn();

      if ($send_email && $email) {
        sendEmail($email, "New Notification from Admin", $content);
      }
    }

    echo "<script>alert('Notification sent" . ($send_email ? " with email" : "") . "'); window.location.href='admin_send_notification.php';</script>";
    exit();
  } else {
    $error = "Notification content cannot be empty.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Send Notification</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
</head>

<body>
  <?php include 'include/sidebar.php'; ?>

  <main class="main-content">

    <h1 class="mb-4">Send Notification</h1>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label for="recipient_id" class="form-label">Recipient</label>
        <select name="recipient_id" class="form-select" required>
          <option value="all">All Users</option>
          <?php foreach ($users as $user): ?>
            <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Notification Content</label>
        <div id="editor" style="background:#fff;"></div>
        <input type="hidden" name="notification_content" id="notification_content">
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="send_email" id="send_email">
        <label class="form-check-label" for="send_email">
          Also send as email
        </label>
      </div>

      <button type="submit" name="send_notification" class="btn btn-primary">Send Notification</button>
    </form>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>
  <script>
    const quill = new Quill('#editor', {
      theme: 'snow',
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline'],
          ['link'],
          [{
            'list': 'ordered'
          }, {
            'list': 'bullet'
          }],
          ['clean']
        ]
      }
    });

    document.querySelector('form').addEventListener('submit', function() {
      document.getElementById('notification_content').value = quill.root.innerHTML;
    });
  </script>
  <script src="./js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>