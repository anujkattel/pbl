<?php
session_start();
include 'db.php';

// Check if voting_end_time column exists
$column_exists = false;
try {
    $conn->query("SELECT voting_end_time FROM settings LIMIT 1");
    $column_exists = true;
} catch (PDOException $e) {
    // Column doesn't exist
}

// Fetch voting status and end time
$general_voting_open = false;
$caas_voting_open = false;
$voting_end_time = null;

try {
    $query = "SELECT election_type, voting_open" . ($column_exists ? ", voting_end_time" : "") . " FROM settings";
    $setting_stmt = $conn->prepare($query);
    $setting_stmt->execute();
    $settings = $setting_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($settings as $setting) {
        if ($setting['election_type'] === 'general' && $setting['voting_open'] == 1) {
            $general_voting_open = true;
            $voting_end_time = $column_exists && isset($setting['voting_end_time']) ? $setting['voting_end_time'] : null;
        }
        if ($setting['election_type'] === 'CAAS' && $setting['voting_open'] == 1) {
            $caas_voting_open = true;
            $voting_end_time = $column_exists && isset($setting['voting_end_time']) ? $setting['voting_end_time'] : null;
        }
    }
} catch (PDOException $e) {
    // Handle query errors silently; default to closed voting
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }

        .hero {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            padding: 5rem 1rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/asfalt-light.png');
            opacity: 0.1;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .hero-text {
            flex: 1;
        }

        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .hero img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .vote-alert {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: -2rem auto 3rem auto;
            max-width: 1200px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .vote-alert.closed {
            border-left-color: var(--danger);
        }

        .vote-alert i {
            font-size: 1.5rem;
            color: var(--success);
        }

        .vote-alert.closed i {
            color: var(--danger);
        }

        .vote-alert-content {
            flex: 1;
        }

        .vote-alert h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .vote-timer {
            font-weight: 600;
            color: var(--dark);
        }

        .auth-section {
            text-align: center;
            margin: 3rem 0;
        }

        .profile-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 500px;
            margin: 0 auto;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .profile-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: #f1f5ff;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #0d9f6e;
            transform: translateY(-2px);
        }

        .section {
            background: white;
            border-radius: 1rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .section h2 {
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .step-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .step-card {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .step-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .step-card h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .step-card p {
            color: var(--gray);
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .benefit-card {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .benefit-icon {
            color: var(--success);
            font-size: 1.5rem;
            margin-top: 0.25rem;
        }

        .benefit-content h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }

        .benefit-content p {
            color: var(--gray);
        }

        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 1rem;
            margin-top: 3rem;
            text-align: center;
        }

        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 100;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                text-align: center;
            }

            .hero-text {
                text-align: center;
            }

            .hero p {
                margin-left: auto;
                margin-right: auto;
            }

            .btn-group {
                flex-direction: column;
            }

            .vote-alert {
                flex-direction: column;
                text-align: center;
                margin-top: -4rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
    <header class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Shape Your Community's Future</h1>
                <p>Cast your vote securely in our online elections and make your voice heard in important decisions that affect everyone.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="signup.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register to Vote
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1551524559-8af4e6624178?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80" alt="Voting illustration">
            </div>
        </div>
    </header>

    <div class="container">
        

        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['name'])): ?>
            <div class="auth-section fade-in">
                <div class="profile-card">
                    <?php
                    $profilePic = !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                    ?>
                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                    <div class="btn-group">
                        <a href="vote.php" class="btn btn-primary">
                            <i class="fas fa-vote-yea"></i> Go to Voting
                        </a>
                        <a href="logout.php" class="btn btn-outline">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-section fade-in">
                <div class="profile-card">
                    <h2>Ready to make your voice heard?</h2>
                    <p>Login or register to participate in the current election.</p>
                    <div class="btn-group">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="signup.php" class="btn btn-outline">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="section fade-in">
            <h2><i class="fas fa-walking"></i> How Voting Works</h2>
            <div class="step-list">
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Create Your Account</h3>
                    <p>Register with your student credentials to verify your eligibility to vote.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3>Login Securely</h3>
                    <p>Access the voting portal with your unique credentials.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <h3>View Candidates</h3>
                    <p>Browse all candidates running in your specific election.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Cast Your Vote</h3>
                    <p>Select your preferred candidates and submit your ballot.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Receive Confirmation</h3>
                    <p>Get instant verification that your vote was recorded.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Secure & Anonymous</h3>
                    <p>Rest assured your vote remains confidential and tamper-proof.</p>
                </div>
            </div>
        </div>

        <div class="section fade-in">
            <h2><i class="fas fa-star"></i> Why Your Vote Matters</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Amplify Your Voice</h3>
                        <p>Elections determine who represents your interests in important decisions.</p>
                    </div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Shape Your Community</h3>
                        <p>Elect leaders who will work to improve your academic environment.</p>
                    </div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Exercise Your Rights</h3>
                        <p>Voting is both a privilege and responsibility in a democratic system.</p>
                    </div>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="benefit-content">
                        <h3>Influence Change</h3>
                        <p>Collective votes lead to tangible improvements in policies and services.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Online Voting System. All rights reserved.</p>
            <p>A secure platform for democratic decision-making.</p>
        </div>
    </footer>

    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Voting Timer
        const endTime = new Date('<?php echo $voting_end_time; ?>').getTime();
        const timer = document.getElementById('vote-timer');

        function updateTimer() {
            if (!endTime || isNaN(endTime)) {
                if (timer) timer.innerHTML = 'Voting is currently active';
                return;
            }

            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                if (timer) timer.innerHTML = 'Voting period has ended';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let timeString = '';
            if (days > 0) timeString += `${days}d `;
            if (hours > 0 || days > 0) timeString += `${hours}h `;
            if (minutes > 0 || hours > 0 || days > 0) timeString += `${minutes}m `;
            timeString += `${seconds}s`;

            if (timer) timer.innerHTML = `Time remaining: ${timeString}`;
        }

        if (timer) {
            setInterval(updateTimer, 1000);
            updateTimer();
        }

        // Back to Top Button
        const backToTop = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Add fade-in animation to elements as they come into view
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        fadeElements.forEach(el => {
            el.style.opacity = 0;
            el.style.transition = 'opacity 0.6s ease-out';
            observer.observe(el);
        });
    </script>
</body>

</html>