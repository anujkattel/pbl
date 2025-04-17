<?php
session_start();
include 'db.php';

// Admin access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch semesters dynamically (or manually)
$semesters_stmt = $conn->query("SELECT DISTINCT semester FROM users ORDER BY semester");
$semesters = $semesters_stmt->fetchAll(PDO::FETCH_COLUMN);

// Default selections
$semester = $_POST['semester'] ?? $semesters[0] ?? '1';
$election_type = $_POST['election_type'] ?? 'general';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voting_status'])) {
    $new_status = $_POST['voting_status'] === 'open' ? '1' : '0';

    $stmt = $conn->prepare("INSERT INTO settings (semester, election_type, voting_open)
                            VALUES (:semester, :election_type, :status)
                            ON DUPLICATE KEY UPDATE voting_open = :status");
    $stmt->bindParam(':semester', $semester);
    $stmt->bindParam(':election_type', $election_type);
    $stmt->bindParam(':status', $new_status);
    $stmt->execute();
}

// Fetch current voting status
$stmt = $conn->prepare("SELECT voting_open FROM settings WHERE semester = :semester AND election_type = :election_type");
$stmt->bindParam(':semester', $semester);
$stmt->bindParam(':election_type', $election_type);
$stmt->execute();
$current_status = $stmt->fetchColumn() ?: '0';
?>
<?php
include './include/sidebar.php';
?>
<main class="main-content">

    <h2>Semester-Wise Voting Portal Control</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="semester">Select Semester:</label>
            <select name="semester" id="semester" class="form-select" onchange="this.form.submit()">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?= $sem ?>" <?= $sem === $semester ? 'selected' : '' ?>>Semester <?= htmlspecialchars($sem) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="election_type">Election Type:</label>
            <select name="election_type" class="form-select" onchange="this.form.submit()">
                <option value="general" <?= $election_type === 'general' ? 'selected' : '' ?>>General</option>
                <option value="CAAS" <?= $election_type === 'CAAS' ? 'selected' : '' ?>>CAAS</option>
            </select>
        </div>

        <p><strong>Current Status:</strong> <?= $current_status === '1' ? 'Open' : 'Closed'; ?></p>

        <select name="voting_status" class="form-select mb-3">
            <option value="open" <?= $current_status === '1' ? 'selected' : '' ?>>Open</option>
            <option value="closed" <?= $current_status === '0' ? 'selected' : '' ?>>Closed</option>
        </select>

        <button type="submit" class="btn btn-primary">Update Status</button>
    </form>


</main>
<script src="./js/app.js"></script>

</body>

</html>