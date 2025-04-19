<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Voting Control - Online Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #d4fcff, #f3d4ff);
            color: #333;
            overflow-x: hidden;
            position: relative;
        }

        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: transparent;
        }

        .main-content {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        h2 {
            color: #5a0db5;
            font-size: 2.5rem;
            margin-bottom: 30px;
            animation: fadeInDown 1s ease-out;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            background: url('https://www.transparenttextures.com/patterns/subtle-dots.png');
            animation: slideInUp 1s ease-out;
        }

        .form-select, .form-control {
            border-radius: 10px;
            font-size: 1.1rem;
            padding: 10px;
        }

        .btn-primary {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            background: #1e90ff;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #1a5cd8;
            transform: translateY(-2px);
        }

        .time-remaining {
            margin-top: 20px;
            font-size: 1.2rem;
            color: #28a745;
            font-weight: 600;
        }

        .alert {
            border-radius: 10px;
            font-size: 1.1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #5a0db5;
            color: white;
            padding: 15px;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            display: none;
            transition: opacity 0.3s;
        }

        .back-to-top:hover {
            background: #4a0c9a;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            h2 {
                font-size: 2rem;
            }

            .card {
                padding: 20px;
            }

            .form-select, .form-control {
                font-size: 1rem;
            }

            .btn-primary {
                padding: 10px 20px;
            }
        }
    </style>
<?php
session_start();
include 'db.php';

// Set timezone to Indian Standard Time (IST, UTC+5:30)
date_default_timezone_set('Asia/Kolkata');

// Set database timezone
$conn->exec("SET time_zone = '+05:30'");

// Admin access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch semesters dynamically
try {
    $semesters_stmt = $conn->query("SELECT DISTINCT semester FROM users ORDER BY semester");
    $semesters = $semesters_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $semesters = ['1'];
    $error_message = "Error fetching semesters: " . $e->getMessage();
}

// Default selections
$semester = $_POST['semester'] ?? ($semesters[0] ?? '1');
$election_type = $_POST['election_type'] ?? 'general';

// Validate inputs
$valid_semesters = $semesters ?: ['1'];
$valid_election_types = ['general', 'CAAS'];
if (!in_array($semester, $valid_semesters)) {
    $semester = $valid_semesters[0];
}
if (!in_array($election_type, $valid_election_types)) {
    $election_type = 'general';
}

// Check if voting_end_time column exists
$column_exists = false;
try {
    $conn->query("SELECT voting_end_time FROM settings LIMIT 1");
    $column_exists = true;
} catch (PDOException $e) {
    // Column doesn't exist
}

// Process form submission
$error_message = null;
$success_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voting_status'])) {
    $new_status = $_POST['voting_status'] === 'open' ? '1' : '0';
    $hours = isset($_POST['voting_hours']) ? max(1, min(168, (int)$_POST['voting_hours'])) : 24;
    $end_time = $new_status === '1' && $column_exists ? date('Y-m-d H:i:s', strtotime("+$hours hours")) : null;

    try {
        $query = "INSERT INTO settings (semester, election_type, voting_open" . ($column_exists ? ", voting_end_time" : "") . ")
                  VALUES (:semester, :election_type, :status" . ($column_exists ? ", :end_time" : "") . ")
                  ON DUPLICATE KEY UPDATE voting_open = :status" . ($column_exists ? ", voting_end_time = :end_time" : "");
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':election_type', $election_type);
        $stmt->bindParam(':status', $new_status);
        if ($column_exists) {
            $stmt->bindParam(':end_time', $end_time);
        }
        $stmt->execute();

        $success_message = "Voting status updated successfully. Duration set to $hours hours.";
        header("Location: admin_voting_control.php?semester=" . urlencode($semester) . "&election_type=" . urlencode($election_type) . "&hours=" . urlencode($hours) . "&success=1");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating voting status: " . $e->getMessage();
    }
}

// Auto-close voting if end time has passed
try {
    if ($column_exists) {
        $result = $conn->exec("UPDATE settings SET voting_open = 0, voting_end_time = NULL WHERE voting_end_time IS NOT NULL AND voting_end_time < NOW()");
        if ($result > 0) {
            error_log("Auto-closed $result voting sessions at " . date('Y-m-d H:i:s'));
        }
    }
} catch (PDOException $e) {
    $error_message = $error_message ?? "Error auto-closing voting: " . $e->getMessage();
}

// Fetch current voting status and end time
try {
    $query = "SELECT voting_open" . ($column_exists ? ", voting_end_time" : "") . " FROM settings WHERE semester = :semester AND election_type = :election_type";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':semester', $semester);
    $stmt->bindParam(':election_type', $election_type);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        // Insert default row if none exists
        $query = "INSERT INTO settings (semester, election_type, voting_open) VALUES (:semester, :election_type, '0')";
        $default_stmt = $conn->prepare($query);
        $default_stmt->bindParam(':semester', $semester);
        $default_stmt->bindParam(':election_type', $election_type);
        $default_stmt->execute();
        $current_status = '0';
        $voting_end_time = null;
    } else {
        $current_status = $settings['voting_open'] ?? '0';
        $voting_end_time = $column_exists ? ($settings['voting_end_time'] ?? null) : null;
    }
} catch (PDOException $e) {
    $current_status = '0';
    $voting_end_time = null;
    $error_message = $error_message ?? "Error fetching voting status: " . $e->getMessage();
}

// Handle GET parameters for page refresh
if (isset($_GET['semester']) && isset($_GET['election_type'])) {
    $semester = in_array($_GET['semester'], $valid_semesters) ? $_GET['semester'] : $valid_semesters[0];
    $election_type = in_array($_GET['election_type'], $valid_election_types) ? $_GET['election_type'] : 'general';
    if (isset($_GET['success'])) {
        $hours = isset($_GET['hours']) ? max(1, min(168, (int)$_GET['hours'])) : 24;
        $success_message = "Voting status updated successfully. Duration set to $hours hours.";
    }
}
?>
<body>
    <div id="particles"></div>
    <?php include './include/sidebar.php'; ?>
    <main class="main-content">
        <h2>Semester-Wise Voting Portal Control</h2>
        <?php if (!$column_exists): ?>
            <div class="alert alert-warning">Warning: The 'voting_end_time' column is missing in the settings table. Please add it to enable timed voting.</div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (empty($semesters)): ?>
            <div class="alert alert-info">No semesters found in the users table. Please add users to enable semester selection.</div>
        <?php endif; ?>
        <div class="card">
            <form method="POST" onsubmit="return confirm('Are you sure you want to update the voting status?');" id="votingForm">
                <input type="hidden" name="voting_status" value="<?php echo htmlspecialchars($current_status === '1' ? 'open' : 'closed'); ?>">
                <div class="mb-3">
                    <label for="semester" class="form-label">Select Semester:</label>
                    <select name="semester" id="semester" class="form-select" aria-label="Select semester">
                        <?php foreach ($valid_semesters as $sem): ?>
                            <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo $sem === $semester ? 'selected' : ''; ?>>Semester <?php echo htmlspecialchars($sem); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="election_type" class="form-label">Election Type:</label>
                    <select name="election_type" id="election_type" class="form-select" aria-label="Select election type">
                        <option value="general" <?php echo $election_type === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="CAAS" <?php echo $election_type === 'CAAS' ? 'selected' : ''; ?>>CAAS</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="voting_status" class="form-label">Voting Status:</label>
                    <select name="voting_status" id="voting_status" class="form-select" aria-label="Select voting status">
                        <option value="open" <?php echo $current_status === '1' ? 'selected' : ''; ?>>Open</option>
                        <option value="closed" <?php echo $current_status === '0' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="voting_hours" class="form-label">Voting Duration (Hours):</label>
                    <input type="number" name="voting_hours" id="voting_hours" class="form-control" min="1" max="168" value="24" required data-bs-toggle="tooltip" title="Set how many hours voting should remain open (1-168)">
                </div>
                <p><strong>Current Status:</strong> <?php echo $current_status == '1' ? 'Open' : 'Closed'; ?></p>
                <?php if ($current_status == '1' && $voting_end_time && $column_exists): ?>
                    <p class="time-remaining">Time Remaining: <span id="vote-timer">Loading...</span></p>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Update voting settings">Update Status</button>
            </form>
        </div>
    </main>
    <a href="#" class="back-to-top" aria-label="Back to top"><i class="fas fa-chevron-up"></i></a>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Particle Background
        particlesJS('particles', {
            particles: {
                number: { value: 60, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.4, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.3, width: 1 },
                move: { enable: true, speed: 2, direction: 'none', random: false }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' } },
                modes: { repulse: { distance: 100 }, push: { particles_nb: 4 } }
            },
            retina_detect: true
        });

        // Voting Timer
        const endTime = new Date('<?php echo $voting_end_time; ?>').getTime();
        const timer = document.getElementById('vote-timer');
        if (isNaN(endTime)) {
            console.error('Invalid voting_end_time:', '<?php echo $voting_end_time; ?>');
        }
        function updateTimer() {
            if (!endTime || isNaN(endTime)) {
                if (timer) timer.innerHTML = 'No end time set';
                return;
            }
            const now = new Date().getTime();
            const distance = endTime - now;
            if (distance < 0) {
                if (timer) timer.innerHTML = 'Voting has ended!';
                setTimeout(() => location.reload(), 1000);
                return;
            }
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            if (timer) timer.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
        }
        if (timer) {
            setInterval(updateTimer, 1000);
            updateTimer();
        }
        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        // Back to Top
        const backToTop = document.querySelector('.back-to-top');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });
        // Tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        // Form submission for semester and election type
        document.getElementById('semester').addEventListener('change', function() {
            document.getElementById('votingForm').onsubmit = null;
            document.getElementById('votingForm').submit();
        });
        document.getElementById('election_type').addEventListener('change', function() {
            document.getElementById('votingForm').onsubmit = null;
            document.getElementById('votingForm').submit();
        });
    </script>
</body>
</html>