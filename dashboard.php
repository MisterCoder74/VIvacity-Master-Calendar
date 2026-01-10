<?php
// Set session name BEFORE session_start()
ini_set('session.name', 'LC_IDENTIFIER');
session_start();

// Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$userPicture = $_SESSION['profile_picture'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VIvacity Master Calendar</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="navbar">
            <h1>VIvacity Master Calendar</h1>
            <div class="user-menu">
                <?php if ($userPicture): ?>
                    <img src="<?php echo htmlspecialchars($userPicture); ?>" alt="Profile" class="user-picture">
                <?php endif; ?>
                <span>Benvenuti, <?php echo htmlspecialchars($userName); ?></span>
                <a href="php/logout.php" class="logout-btn">Logout</a>
            </div>
        </nav>

        <main class="dashboard-main">
            <div class="welcome-section">
                <h2>Benvenuti</h2>
                <p>Welcome to VIvacity Master Calendar</p>
            </div>
        </main>
    </div>
</body>
</html>
