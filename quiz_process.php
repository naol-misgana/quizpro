<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requirePaymentVerified(); // This ensures only verified users can access quizzes

// Rest of the code...

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

if (!isset($_SESSION['quiz_started'])) {
    $_SESSION['error'] = 'No active quiz session found.';
    redirect('dashboard.php');
}

$action = $_POST['action'] ?? '';
$question_index = $_POST['question_index'] ?? 0;
$question_id = $_POST['question_id'] ?? 0;
$selected_option = $_POST['selected_option'] ?? null;
$quiz_id = $_POST['quiz_id'] ?? 0;

// Save current answer
if ($selected_option !== null) {
    $_SESSION['user_answers'][$question_index] = (int)$selected_option;
}

// Handle navigation
if ($action === 'prev') {
    $_SESSION['current_question'] = max(0, $question_index - 1);
} elseif ($action === 'next') {
    $_SESSION['current_question'] = $question_index + 1;
} elseif ($action === 'finish') {
    // Calculate score and save results
    calculateAndSaveQuizResults($quiz_id);
    unset($_SESSION['quiz_started']);
    unset($_SESSION['current_question']);
    unset($_SESSION['user_answers']);
    unset($_SESSION['quiz_start_time']);
    
    $_SESSION['success'] = 'Quiz completed successfully!';
    redirect('results.php?id=' . $_SESSION['quiz_attempt_id']);
}

redirect('quiz.php?id=' . $quiz_id);

function calculateAndSaveQuizResults($quiz_id) {
    global $pdo;
    
    // Get questions and correct answers
    $stmt = $pdo->prepare("SELECT id, correct_option FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    
    $correct_answers = 0;
    $user_answers = $_SESSION['user_answers'];
    
    // Calculate score
    foreach ($questions as $index => $question) {
        if (isset($user_answers[$index]) && $user_answers[$index] == $question['correct_option']) {
            $correct_answers++;
        }
    }
    
    $total_questions = count($questions);
    $score = calculatePercentage($correct_answers, $total_questions);
    $time_taken = time() - $_SESSION['quiz_start_time'];
    
    // Update quiz attempt
    $stmt = $pdo->prepare("UPDATE quiz_attempts 
                          SET score = ?, correct_answers = ?, time_taken = ?, completed_at = NOW(), user_answers = ?
                          WHERE id = ?");
    $stmt->execute([
        $score,
        $correct_answers,
        $time_taken,
        json_encode($user_answers),
        $_SESSION['quiz_attempt_id']
    ]);
    
    return $score;
}
?>