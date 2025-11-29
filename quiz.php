<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requirePaymentVerified(); // This ensures only verified users can access quizzes

// Rest of the code...

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$user = getUserData();




// ... existing code ...

// Get questions for this quiz - randomly if enabled
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY RAND() LIMIT ?");
$stmt->execute([$quiz_id, $quiz['total_questions']]);
$questions = $stmt->fetchAll();

// ... rest of the code ...


// Check if payment is verified
if ($user['payment_status'] !== 'verified') {
    $_SESSION['error'] = 'You need to have your payment verified to access quizzes.';
    redirect('payment.php');
}

$quiz_id = $_GET['id'] ?? 0;

// Get quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND is_active = TRUE");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $_SESSION['error'] = 'Quiz not found or unavailable.';
    redirect('dashboard.php');
}

// Check if user already completed this quiz
$stmt = $pdo->prepare("SELECT id FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? AND completed_at IS NOT NULL");
$stmt->execute([$_SESSION['user_id'], $quiz_id]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'You have already completed this quiz.';
    redirect('dashboard.php');
}

// Get questions for this quiz
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    $_SESSION['error'] = 'No questions available for this quiz.';
    redirect('dashboard.php');
}

// Handle quiz start
if (!isset($_SESSION['quiz_started'])) {
    // Create quiz attempt
    $stmt = $pdo->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, total_questions, started_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $quiz_id, count($questions)]);
    $_SESSION['quiz_attempt_id'] = $pdo->lastInsertId();
    $_SESSION['quiz_started'] = true;
    $_SESSION['current_question'] = 0;
    $_SESSION['user_answers'] = array_fill(0, count($questions), null);
    $_SESSION['quiz_start_time'] = time();
}

$current_question_index = $_SESSION['current_question'] ?? 0;
$current_question = $questions[$current_question_index] ?? null;

if (!$current_question) {
    // Quiz completed
    unset($_SESSION['quiz_started']);
    redirect('quiz_results.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .timer-critical {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand"><?php echo SITE_NAME; ?></a>
                <div>
                    <span style="margin-right: 15px;">Time: <strong id="timer-display"><?php echo $quiz['time_limit']; ?>:00</strong></span>
                    <span style="margin-right: 15px;">Question: <strong><?php echo $current_question_index + 1; ?>/<?php echo count($questions); ?></strong></span>
                    <a href="dashboard.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;" onclick="return confirm('Are you sure you want to leave? Your progress will be lost.')">
                        <i class="fas fa-sign-out-alt"></i> Exit Quiz
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="quiz-container">
            <!-- Timer -->
            <div class="timer" id="timer">
                <i class="fas fa-clock"></i> Time Remaining: <span id="time-remaining"><?php echo $quiz['time_limit']; ?>:00</span>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar" id="quiz-progress" style="width: <?php echo (($current_question_index + 1) / count($questions)) * 100; ?>%"></div>
            </div>

            <!-- Question Counter -->
            <div class="question-counter">
                Question <?php echo $current_question_index + 1; ?> of <?php echo count($questions); ?>
                <div class="difficulty <?php echo $current_question['difficulty']; ?>">
                    <?php echo ucfirst($current_question['difficulty']); ?>
                </div>
            </div>

            <!-- Question Card -->
            <div class="card">
                <div class="card-body">
                    <div class="question">
                        <?php echo htmlspecialchars($current_question['question_text']); ?>
                    </div>

                    <form id="quiz-form" method="POST" action="quiz_process.php">
                        <input type="hidden" name="question_index" value="<?php echo $current_question_index; ?>">
                        <input type="hidden" name="question_id" value="<?php echo $current_question['id']; ?>">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        
                        <div class="options">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="option <?php echo $_SESSION['user_answers'][$current_question_index] == $i ? 'selected' : ''; ?>" 
                                     onclick="selectOption(<?php echo $i; ?>)">
                                    <div class="option-letter"><?php echo chr(64 + $i); ?></div>
                                    <div class="option-text"><?php echo htmlspecialchars($current_question['option' . $i]); ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <input type="hidden" name="selected_option" id="selected_option" value="<?php echo $_SESSION['user_answers'][$current_question_index]; ?>">

                        <div class="navigation" style="margin-top: 30px;">
                            <?php if ($current_question_index > 0): ?>
                                <button type="submit" name="action" value="prev" class="btn btn-nav btn-outline">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </button>
                            <?php else: ?>
                                <div></div> <!-- Spacer -->
                            <?php endif; ?>

                            <?php if ($current_question_index < count($questions) - 1): ?>
                                <button type="submit" name="action" value="next" class="btn btn-nav" id="next-btn">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="finish" class="btn btn-success btn-nav" id="finish-btn">
                                    Finish Quiz <i class="fas fa-flag-checkered"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quiz Info -->
            <div style="text-align: center; margin-top: 20px; color: #666;">
                <p><i class="fas fa-info-circle"></i> You can navigate between questions using Previous/Next buttons.</p>
                <p>Time limit: <?php echo $quiz['time_limit']; ?> minutes | Total questions: <?php echo count($questions); ?></p>
            </div>
        </div>
    </div>

    <script>
        // Timer functionality
        const timeLimit = <?php echo $quiz['time_limit'] * 60; ?>; // in seconds
        const startTime = <?php echo $_SESSION['quiz_start_time']; ?>;
        let timeLeft = timeLimit - (Math.floor(Date.now() / 1000) - startTime);
        
        function updateTimer() {
            if (timeLeft <= 0) {
                document.getElementById('quiz-form').submit();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('time-remaining').textContent = timeString;
            document.getElementById('timer-display').textContent = timeString;
            
            // Add warning styles
            const timerElement = document.getElementById('timer');
            if (timeLeft <= 300) { // 5 minutes
                timerElement.classList.add('timer-critical');
            } else if (timeLeft <= 900) { // 15 minutes
                timerElement.classList.add('timer-warning');
            }
            
            timeLeft--;
        }
        
        // Update timer every second
        setInterval(updateTimer, 1000);
        updateTimer(); // Initial call
        
        // Option selection
        function selectOption(optionNumber) {
            // Remove selected class from all options
            document.querySelectorAll('.option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Update hidden input
            document.getElementById('selected_option').value = optionNumber;
            
            // Enable next button if disabled
            const nextBtn = document.getElementById('next-btn');
            const finishBtn = document.getElementById('finish-btn');
            if (nextBtn) nextBtn.disabled = false;
            if (finishBtn) finishBtn.disabled = false;
        }
        
        // Auto-save on option select
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function() {
                // The form will be submitted on navigation buttons
            });
        });
        
        // Prevent accidental refresh
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
        
        // Enable/disable navigation based on selection
        document.addEventListener('DOMContentLoaded', function() {
            const selectedOption = document.getElementById('selected_option').value;
            const nextBtn = document.getElementById('next-btn');
            const finishBtn = document.getElementById('finish-btn');
            
            if (!selectedOption) {
                if (nextBtn) nextBtn.disabled = true;
                if (finishBtn) finishBtn.disabled = true;
            }
        });
    </script>
</body>
</html>