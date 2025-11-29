<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireUser(); // This ensures only regular users can access

// Rest of the code...


if (!isLoggedIn()) {
    redirect('../login.php');
}

if (isAdmin()) {
    redirect('../admin/dashboard.php');
}

$user = getUserData();

// Get user's quiz attempts
$stmt = $pdo->prepare("SELECT q.title, qa.score, qa.total_questions, qa.correct_answers, qa.started_at, qa.completed_at 
                       FROM quiz_attempts qa 
                       JOIN quizzes q ON qa.quiz_id = q.id 
                       WHERE qa.user_id = ? 
                       ORDER BY qa.completed_at DESC 
                       LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$quiz_attempts = $stmt->fetchAll();

// Get total attempts and average score
$stmt = $pdo->prepare("SELECT COUNT(*) as total_attempts, AVG(score) as avg_score 
                       FROM quiz_attempts 
                       WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand"><?php echo SITE_NAME; ?></a>
                <div>
                    <span style="margin-right: 15px;">Welcome, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
                    <a href="payment.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-credit-card"></i> Payment</a>
                    <a href="profile.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="user-info">
            <div>
                <h2 style="margin: 0; color: #1a2a6c;">Student Dashboard</h2>
                <p style="margin: 5px 0 0; color: #666;">Track your progress and take quizzes</p>
            </div>
            <div style="text-align: right;">
                <div class="status-badge status-<?php echo $user['payment_status']; ?>">
                    Payment: <?php echo ucfirst($user['payment_status']); ?>
                </div>
                <p style="margin: 5px 0 0; font-size: 0.9rem;">Member since <?php echo formatDate($user['created_at']); ?></p>
            </div>
        </div>

        <?php if ($user['payment_status'] !== 'verified'): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Payment Verification Required:</strong> 
                You need to complete the payment process and get verified to access quizzes. 
                <a href="payment.php" style="color: #856404; text-decoration: underline;">Complete Payment Now</a>
            </div>
        <?php endif; ?>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_attempts'] ?: 0; ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_score'] ? round($stats['avg_score']) : 0; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $user['payment_status'] === 'verified' ? 'Active' : 'Pending'; ?></div>
                <div class="stat-label">Account Status</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;"><i class="fas fa-history"></i> Recent Quiz Attempts</h3>
            </div>
            <div class="card-body">
                <?php if (empty($quiz_attempts)): ?>
                    <p>You haven't attempted any quizzes yet.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Correct Answers</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                                    <td><?php echo $attempt['score']; ?>%</td>
                                    <td><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?></td>
                                    <td><?php echo formatDate($attempt['completed_at']); ?></td>
                                    <td>
                                        <a href="results.php?id=<?php echo $attempt['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($user['payment_status'] === 'verified'): ?>
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;"><i class="fas fa-play-circle"></i> Available Quizzes</h3>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE is_active = TRUE");
                    $stmt->execute();
                    $quizzes = $stmt->fetchAll();
                    ?>
                    <?php if (empty($quizzes)): ?>
                        <p>No quizzes available at the moment. Please check back later.</p>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php foreach ($quizzes as $quiz): ?>
                                <div style="border: 1px solid #e1f5fe; border-radius: 10px; padding: 20px; background: #f8f9fa;">
                                    <h4 style="margin: 0 0 10px; color: #1a2a6c;"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                    <p style="margin: 0 0 15px; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                        <span><i class="fas fa-clock"></i> <?php echo $quiz['time_limit']; ?> mins</span>
                                        <span><i class="fas fa-question-circle"></i> <?php echo $quiz['total_questions']; ?> questions</span>
                                        <span><i class="fas fa-trophy"></i> <?php echo $quiz['passing_score']; ?>% to pass</span>
                                    </div>
                                    <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn" style="width: 100%;">Start Quiz</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>



// Add this to the existing dashboard.php after the existing stats
<?php
// Get more detailed statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_attempts,
    AVG(score) as avg_score,
    MAX(score) as best_score,
    COUNT(DISTINCT quiz_id) as quizzes_taken
    FROM quiz_attempts 
    WHERE user_id = ? AND completed_at IS NOT NULL");
$stmt->execute([$_SESSION['user_id']]);
$detailed_stats = $stmt->fetch();
?>

<!-- Add this to the dashboard stats section -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_attempts'] ?: 0; ?></div>
        <div class="stat-label">Quiz Attempts</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $detailed_stats['avg_score'] ? round($detailed_stats['avg_score']) : 0; ?>%</div>
        <div class="stat-label">Average Score</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $detailed_stats['best_score'] ?: 0; ?>%</div>
        <div class="stat-label">Best Score</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $detailed_stats['quizzes_taken'] ?: 0; ?></div>
        <div class="stat-label">Quizzes Taken</div>
    </div>
</div>

