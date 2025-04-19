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

        header {
            background: linear-gradient(to right, #5a0db5, #1e90ff);
            color: white;
            padding: 60px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.15;
        }

        header .left {
            flex: 1;
            text-align: left;
        }

        header h1 {
            font-size: 3.2rem;
            margin-bottom: 15px;
            animation: fadeInDown 1s ease-out;
        }

        header p {
            font-size: 1.4rem;
            margin: 0;
            animation: fadeInUp 1s ease-out 0.3s;
            animation-fill-mode: backwards;
        }

        header .right {
            flex: 0 0 300px;
            text-align: right;
        }

        header .right img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        header .right img:hover {
            transform: scale(1.05);
        }

        .vote-banner {
            text-align: center;
            padding: 20px;
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideInUp 1s ease-out;
        }

        .vote-banner h3 {
            margin: 0;
            font-size: 1.8rem;
        }

        .vote-banner #vote-timer {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .cta {
            text-align: center;
            margin: 40px 0;
            animation: fadeIn 1s ease-out;
        }

        .profile-pic {
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            transition: transform 0.3s;
        }

        .profile-pic:hover {
            transform: scale(1.05);
        }

        .cta p {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: #444;
        }

        .cta a {
            display: inline-block;
            text-decoration: none;
            background: #1e90ff;
            color: white;
            padding: 15px 40px;
            margin: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(30, 144, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .cta a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }

        .cta a:hover::before {
            left: 100%;
        }

        .cta a:hover {
            background: #1a5cd8;
            transform: translateY(-3px);
        }

        .section {
            margin-bottom: 80px;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: slideInUp 1s ease-out;
            background: url('https://www.transparenttextures.com/patterns/subtle-dots.png');
        }

        .section h2 {
            color: #5a0db5;
            font-size: 2.2rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section ul {
            list-style-type: none;
            padding: 0;
        }

        .section ul li {
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
            font-size: 1.2rem;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section ul li:hover {
            transform: translateX(10px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .section ul li i {
            color: #28a745;
            font-size: 1.5rem;
        }

        footer {
            background: #1a1a1a;
            color: #ccc;
            text-align: center;
            padding: 30px 10px;
            font-size: 1rem;
            position: relative;
            width: 100%;
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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            header {
                flex-direction: column;
                text-align: center;
            }

            header .left {
                text-align: center;
            }

            header .right {
                margin-top: 20px;
                flex: 0 0 200px;
            }

            header h1 {
                font-size: 2.5rem;
            }

            header p {
                font-size: 1.2rem;
            }

            .section {
                padding: 20px;
            }

            .section h2 {
                font-size: 1.9rem;
            }

            .cta a {
                padding: 12px 30px;
                font-size: 1.1rem;
            }

            .vote-banner h3 {
                font-size: 1.5rem;
            }

            .vote-banner #vote-timer {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>

    <div id="particles"></div>

    <header id="header">
        <div class="left">
            <h1>Online Voting Portal</h1>
            <p>Your voice matters. Cast your vote securely and easily!</p>
        </div>
        <div class="right">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFmN7kE6YWUVmYdY0KszA__CYMlhY-q6B6Nw&s" alt="Election Campaign" />
        </div>
    </header>

    <div class="container">
        <?php if ($general_voting_open || $caas_voting_open): ?>
            <div class="vote-banner">
                <h3>🗳️ Voting is Open!</h3>
                <div id="vote-timer"><?php echo $voting_end_time ? 'Loading...' : 'No end time set'; ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['name'])): ?>
            <div class="cta">
                <?php
                $profilePic = !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'https://i.pinimg.com/474x/0a/52/d5/0a52d5e52f7b81f96538d6b16ed5dc2b.jpg';
                ?>
                <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic" width="150" height="150">
                <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>!</p>
                <a href="vote.php" data-bs-toggle="tooltip" title="Cast your vote now!">Go to Vote</a>
                <a href="logout.php" data-bs-toggle="tooltip" title="Sign out of your account">Logout</a>
            </div>
        <?php else: ?>
            <div class="cta">
                <p>Ready to make your vote count?</p>
                <a href="login.php" data-bs-toggle="tooltip" title="Sign in to vote">Login</a>
                <a href="signup.php" data-bs-toggle="tooltip" title="Create an account">Register</a>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-vote-yea"></i> How to Vote</h2>
            <ul>
                <li><i class="fas fa-check-circle"></i> Register or log in with your college ID.</li>
                <li><i class="fas fa-check-circle"></i> Go to the election section after login.</li>
                <li><i class="fas fa-check-circle"></i> Choose the election type (e.g., General, CAAS).</li>
                <li><i class="fas fa-check-circle"></i> View candidates based on your semester.</li>
                <li><i class="fas fa-check-circle"></i> Select your candidate and submit your vote.</li>
                <li><i class="fas fa-check-circle"></i> Your vote is recorded and admin is notified.</li>
            </ul>
        </div>

        <div class="section">
            <h2><i class="fas fa-bullhorn"></i> Why Vote?</h2>
            <ul>
                <li><i class="fas fa-star"></i> Empower your student council.</li>
                <li><i class="fas fa-star"></i> Shape future decisions in the college.</li>
                <li><i class="fas fa-star"></i> Make your voice heard in democratic processes.</li>
            </ul>
        </div>
    </div>

    <footer>
        © <?php echo date('Y'); ?> Online Voting System | All rights reserved.
    </footer>

    <a href="#header" class="back-to-top" aria-label="Back to top"><i class="fas fa-chevron-up"></i></a>

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

        function updateTimer() {
            if (!endTime || isNaN(endTime)) {
                if (timer) timer.innerHTML = 'No end time set';
                return;
            }

            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                if (timer) timer.innerHTML = 'Voting has ended!';
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            if (timer) timer.innerHTML = `Time left: ${hours}h ${minutes}m ${seconds}s`;
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
    </script>
</body>

</html>