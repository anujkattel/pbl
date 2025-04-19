<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle mark as read/unread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_read'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $conn->prepare("SELECT is_read FROM notifications WHERE id = :id AND recipient_id = :user_id");
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notification) {
        $new_status = $notification['is_read'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE notifications SET is_read = :is_read WHERE id = :id AND recipient_id = :user_id");
        $stmt->bindParam(':is_read', $new_status);
        $stmt->bindParam(':id', $notification_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    header("Location: notification.php");
    exit();
}

// Handle delete notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = :id AND recipient_id = :user_id");
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    header("Location: notification.php");
    exit();
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    header("Location: notification.php");
    exit();
}

// Fetch all notifications for the logged-in user
$stmt = $conn->prepare("
    SELECT n.id, n.title, n.content, n.created_at, n.is_read, u.username 
    FROM notifications n 
    JOIN users u ON n.sender_id = u.id 
    WHERE n.recipient_id = :user_id 
    ORDER BY n.created_at DESC
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
  }

  .filter-btn {
    background: #e9ecef;
    color: #2c3e50;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s ease, color 0.3s ease;
  }

  .filter-btn.active, .filter-btn:hover {
    background: #4a90e2;
    color: #ffffff;
  }

  .mark-all-btn {
    background: #4a90e2;
    color: #ffffff;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s ease, box-shadow 0.3s ease;
  }

  .mark-all-btn:hover {
    background: #357abd;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4);
  }

  .notification-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .notification-card {
    background: #f8fafd;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
    display: flex;
    gap: 15px;
    align-items: flex-start;
    position: relative;
  }

  .notification-card.unread {
    background: #ffffff;
    border-left: 4px solid #4a90e2;
    animation: fadeIn 0.5s ease-in;
  }

  .notification-card:hover {
    transform: translateY(-3px);
  }

  .notification-icon {
    font-size: 1.2rem;
    color: #4a90e2;
  }

  .notification-body {
    flex: 1;
  }

  .notification-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
  }

  .notification-title.unread {
    font-weight: 700;
  }

  .notification-meta {
    font-size: 0.85rem;
    color: #7f8c8d;
    margin-bottom: 5px;
  }

  .notification-sender {
    font-weight: 500;
    color: #4a90e2;
  }

  .notification-content {
    font-size: 0.9rem;
    line-height: 1.5;
  }

  .notification-content.unread {
    font-weight: 500;
  }

  .notification-actions {
    display: flex;
    gap: 10px;
  }

  .action-btn {
    background: none;
    border: none;
    color: #7f8c8d;
    font-size: 1rem;
    cursor: pointer;
    transition: color 0.3s ease;
  }

  .action-btn:hover {
    color: #4a90e2;
  }

  .action-btn.delete:hover {
    color: #e74c3c;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 20px;
      margin: 15px;
    }

    h1 {
      font-size: 1.5rem;
    }

    .notification-card {
      padding: 15px;
      flex-direction: column;
      gap: 10px;
    }

    .notification-icon {
      font-size: 1.1rem;
    }
  }

  @media (max-width: 576px) {
    .main-content {
      padding: 15px;
      margin: 10px;
    }

    .notification-card {
      padding: 10px;
    }

    .filter-bar {
      flex-direction: column;
      gap: 10px;
    }

    .filter-btn, .mark-all-btn {
      width: 100%;
      text-align: center;
    }
  }
</style>

<main class="main-content">
  <h1>Notifications</h1>
  <div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All</button>
    <button class="filter-btn" data-filter="unread">Unread</button>
    <button class="filter-btn" data-filter="read">Read</button>
    <form method="POST" action="">
      <button type="submit" name="mark_all_read" class="mark-all-btn" onclick="return confirm('Are you sure you want to mark all notifications as read?');">Mark All as Read</button>
    </form>
  </div>
  <div class="notification-list">
    <?php if (empty($notifications)): ?>
      <p class="text-muted">No notifications yet.</p>
    <?php else: ?>
      <?php foreach ($notifications as $notification): ?>
        <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" data-read="<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
          <i class="fas fa-bell notification-icon"></i>
          <div class="notification-body">
            <div class="notification-title <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
              <?php echo htmlspecialchars($notification['title'] ?: 'Notification'); ?>
            </div>
            <div class="notification-meta">
              From <span class="notification-sender"><?php echo htmlspecialchars($notification['username']); ?></span>
              • <?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?>
            </div>
            <div class="notification-content <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
              <?php echo $notification['content']; ?>
            </div>
          </div>
          <div class="notification-actions">
            <form method="POST" action="">
              <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
              <button type="submit" name="toggle_read" class="action-btn" title="<?php echo $notification['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>">
                <i class="fas <?php echo $notification['is_read'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
              </button>
            </form>
            <form method="POST" action="">
              <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
              <button type="submit" name="delete_notification" class="action-btn delete" title="Delete Notification" onclick="return confirm('Are you sure you want to delete this notification?');">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/app.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const notificationCards = document.querySelectorAll('.notification-card');

    filterButtons.forEach(button => {
      button.addEventListener('click', () => {
        // Update active state
        filterButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Filter notifications
        const filter = button.getAttribute('data-filter');
        notificationCards.forEach(card => {
          const isRead = card.getAttribute('data-read');
          if (filter === 'all' || (filter === 'read' && isRead === 'read') || (filter === 'unread' && isRead === 'unread')) {
            card.style.display = 'flex';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  });
</script>
</body>
</html>