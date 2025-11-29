<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$attempt_id = $_GET['id'] ?? 0;

// Get quiz attempt details
$stmt = $pdo->prepare("SELECT qa.*, q.title, q.passing_score, u.full_name 
                       FROM quiz_attempts qa 
                       JOIN quizzes q ON qa.quiz_id = q.id 
                       JOIN users u ON qa.user_id = u.id 
                       WHERE qa.id = ? AND qa.user_id = ?");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    $_SESSION['error'] = 'Quiz results not found.';
    redirect('dashboard.php');
}

// Get questions and answers for review
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$attempt['quiz_id']]);
$questions = $stmt->fetchAll();

$user_answers = json_decode($attempt['user_answers'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand"><?php echo SITE_NAME; ?></a>
                <div>
                    <a href="dashboard.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card results">
            <div class="card-header">
                <h1 style="margin: 0; font-size: 2rem;"><i class="fas fa-chart-bar"></i> Quiz Results</h1>
            </div>
            <div class="card-body">
                <!-- Score Summary -->
                <div class="score-circle">
                    <div class="score-value"><?php echo $attempt['score']; ?>%</div>
                    <div class="score-text">Final Score</div>
                </div>

                <div class="result-message">
                    <?php if ($attempt['score'] >= $attempt['passing_score']): ?>
                        <i class="fas fa-trophy" style="color: #f5af19;"></i> Congratulations! You passed the quiz!
                    <?php else: ?>
                        <i class="fas fa-redo" style="color: #dc3545;"></i> Keep practicing! You can try again.
                    <?php endif; ?>
                </div>

                <div class="results-grid">
                    <div class="result-card">
                        <h3><i class="fas fa-user"></i> Candidate</h3>
                        <div class="value"><?php echo htmlspecialchars($attempt['full_name']); ?></div>
                    </div>
                    <div class="result-card">
                        <h3><i class="fas fa-book"></i> Quiz</h3>
                        <div class="value"><?php echo htmlspecialchars($attempt['title']); ?></div>
                    </div>
                    <div class="result-card">
                        <h3><i class="fas fa-check-circle"></i> Correct Answers</h3>
                        <div class="value"><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?></div>
                    </div>
                    <div class="result-card">
                        <h3><i class="fas fa-clock"></i> Time Taken</h3>
                        <div class="value"><?php echo gmdate("H:i:s", $attempt['time_taken']); ?></div>
                    </div>
                </div>

                <!-- Detailed Review -->
                <div class="card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h3 style="margin: 0;"><i class="fas fa-file-alt"></i> Detailed Review</h3>
                    </div>
                    <div class="card-body">
                        <div class="review-section">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="review-item">
                                    <div class="review-question">
                                        <?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?>
                                        <span class="difficulty <?php echo $question['difficulty']; ?>" style="float: right;">
                                            <?php echo ucfirst($question['difficulty']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php 
                                    $user_answer = $user_answers[$index] ?? null;
                                    $is_correct = $user_answer === $question['correct_option'];
                                    ?>
                                    
                                    <div class="<?php echo $is_correct ? 'correct-answer' : 'user-answer'; ?>">
                                        Your Answer: 
                                        <?php if ($user_answer): ?>
                                            <?php echo chr(64 + $user_answer) . '. ' . htmlspecialchars($question['option' . $user_answer]); ?>
                                            <?php if (!$is_correct): ?>
                                                <span style="color: #dc3545; font-weight: bold;"> (Incorrect)</span>
                                            <?php else: ?>
                                                <span style="color: #28a745; font-weight: bold;"> (Correct)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">Not answered</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$is_correct && $user_answer): ?>
                                        <div class="correct-answer">
                                            Correct Answer: <?php echo chr(64 + $question['correct_option']) . '. ' . htmlspecialchars($question['option' . $question['correct_option']]); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question['explanation']): ?>
                                        <div class="explanation">
                                            <strong>Explanation:</strong> <?php echo htmlspecialchars($question['explanation']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Back to Dashboard</a>
                    <?php if ($attempt['score'] < $attempt['passing_score']): ?>
                        <a href="quiz.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to retake this quiz? Your previous attempt will be preserved.');">
                            <i class="fas fa-redo"></i> Retake Quiz
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>