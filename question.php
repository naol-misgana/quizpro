<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$quiz_id = $_GET['quiz_id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$question_id = $_GET['id'] ?? 0;
$message = '';

// Get quiz details
$quiz = null;
if ($quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        $_SESSION['error'] = 'Quiz not found.';
        redirect('quizzes.php');
    }
}

// Handle question actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_question'])) {
        $question_text = sanitize($_POST['question_text']);
        $option1 = sanitize($_POST['option1']);
        $option2 = sanitize($_POST['option2']);
        $option3 = sanitize($_POST['option3']);
        $option4 = sanitize($_POST['option4']);
        $correct_option = (int)$_POST['correct_option'];
        $explanation = sanitize($_POST['explanation']);
        $category = sanitize($_POST['category']);
        $difficulty = sanitize($_POST['difficulty']);
        
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option1, option2, option3, option4, correct_option, explanation, category, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$quiz_id, $question_text, $option1, $option2, $option3, $option4, $correct_option, $explanation, $category, $difficulty])) {
            $message = 'Question added successfully!';
        }
    } elseif (isset($_POST['update_question'])) {
        $question_text = sanitize($_POST['question_text']);
        $option1 = sanitize($_POST['option1']);
        $option2 = sanitize($_POST['option2']);
        $option3 = sanitize($_POST['option3']);
        $option4 = sanitize($_POST['option4']);
        $correct_option = (int)$_POST['correct_option'];
        $explanation = sanitize($_POST['explanation']);
        $category = sanitize($_POST['category']);
        $difficulty = sanitize($_POST['difficulty']);
        
        $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_option = ?, explanation = ?, category = ?, difficulty = ? WHERE id = ?");
        if ($stmt->execute([$question_text, $option1, $option2, $option3, $option4, $correct_option, $explanation, $category, $difficulty, $question_id])) {
            $message = 'Question updated successfully!';
        }
    }
}

if ($action === 'delete' && $question_id) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $message = 'Question deleted successfully!';
}

// Get questions for the quiz
$questions = [];
if ($quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
}

// Get specific question for editing
$current_question = null;
if ($question_id && ($action === 'edit' || $action === 'view')) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $current_question = $stmt->fetch();
}

// Get question statistics
$total_questions = count($questions);
$easy_count = 0;
$medium_count = 0;
$hard_count = 0;

foreach ($questions as $q) {
    if ($q['difficulty'] === 'easy') $easy_count++;
    elseif ($q['difficulty'] === 'medium') $medium_count++;
    elseif ($q['difficulty'] === 'hard') $hard_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .question-form textarea {
            min-height: 100px;
            resize: vertical;
        }
        .option-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .correct-option {
            background: #d4edda !important;
            border-color: #28a745 !important;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand"><?php echo SITE_NAME; ?> Admin</a>
                <div>
                    <a href="dashboard.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="quizzes.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-book"></i> Quizzes</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="user-info">
            <div>
                <h2 style="margin: 0; color: #1a2a6c;">
                    <i class="fas fa-question-circle"></i> 
                    Question Management
                </h2>
                <p style="margin: 5px 0 0; color: #666;">
                    <?php if ($quiz): ?>
                        Managing questions for: <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                    <?php else: ?>
                        Select a quiz to manage questions
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($quiz): ?>
                <div class="dashboard-stats" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 0;">
                    <div class="stat-card" style="padding: 15px;">
                        <div class="stat-number"><?php echo $total_questions; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card" style="padding: 15px;">
                        <div class="stat-number"><?php echo $easy_count; ?></div>
                        <div class="stat-label">Easy</div>
                    </div>
                    <div class="stat-card" style="padding: 15px;">
                        <div class="stat-number"><?php echo $medium_count; ?></div>
                        <div class="stat-label">Medium</div>
                    </div>
                    <div class="stat-card" style="padding: 15px;">
                        <div class="stat-number"><?php echo $hard_count; ?></div>
                        <div class="stat-label">Hard</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!$quiz): ?>
            <!-- No quiz selected -->
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 40px;">
                    <i class="fas fa-book-open fa-3x" style="color: #bbdefb; margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 15px;">No Quiz Selected</h3>
                    <p>Please select a quiz to manage its questions.</p>
                    <a href="quizzes.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                </div>
            </div>

        <?php elseif ($action === 'create' || ($action === 'edit' && $current_question)): ?>
            <!-- Create/Edit Question Form -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-<?php echo $action === 'create' ? 'plus' : 'edit'; ?>"></i>
                        <?php echo $action === 'create' ? 'Add New Question' : 'Edit Question'; ?>
                    </h3>
                </div>
                <div class="card-body question-form">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="question_text">Question Text *</label>
                            <textarea class="form-control" id="question_text" name="question_text" required><?php echo $current_question ? htmlspecialchars($current_question['question_text']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Options *</label>
                            
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <?php 
                                $option_value = $current_question ? htmlspecialchars($current_question['option' . $i]) : '';
                                $is_correct = $current_question && $current_question['correct_option'] == $i;
                                ?>
                                <div class="option-container">
                                    <div style="width: 30px; text-align: center; font-weight: bold; color: #1a2a6c;">
                                        <?php echo chr(64 + $i); ?>
                                    </div>
                                    <input type="text" class="form-control <?php echo $is_correct ? 'correct-option' : ''; ?>" 
                                           name="option<?php echo $i; ?>" 
                                           value="<?php echo $option_value; ?>" 
                                           placeholder="Option <?php echo chr(64 + $i); ?>" required>
                                    <div>
                                        <input type="radio" id="correct<?php echo $i; ?>" name="correct_option" 
                                               value="<?php echo $i; ?>" 
                                               <?php echo $is_correct ? 'checked' : ''; ?> required>
                                        <label for="correct<?php echo $i; ?>" style="margin: 0;">Correct</label>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label" for="category">Category</label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       value="<?php echo $current_question ? htmlspecialchars($current_question['category']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="difficulty">Difficulty</label>
                                <select class="form-control" id="difficulty" name="difficulty" required>
                                    <option value="easy" <?php echo $current_question && $current_question['difficulty'] === 'easy' ? 'selected' : ''; ?>>Easy</option>
                                    <option value="medium" <?php echo $current_question && $current_question['difficulty'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="hard" <?php echo $current_question && $current_question['difficulty'] === 'hard' ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="explanation">Explanation</label>
                            <textarea class="form-control" id="explanation" name="explanation" rows="3"><?php echo $current_question ? htmlspecialchars($current_question['explanation']) : ''; ?></textarea>
                        </div>

                        <div style="margin-top: 20px; display: flex; gap: 15px;">
                            <button type="submit" name="<?php echo $action === 'create' ? 'create_question' : 'update_question'; ?>" class="btn">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'create' ? 'Add Question' : 'Update Question'; ?>
                            </button>
                            <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'view' && $current_question): ?>
            <!-- Question Details View -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;"><i class="fas fa-eye"></i> Question Details</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #1a2a6c; margin-bottom: 15px;">Question</h4>
                        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #1a2a6c;">
                            <?php echo htmlspecialchars($current_question['question_text']); ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <h4 style="color: #1a2a6c; margin-bottom: 15px;">Options</h4>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: <?php echo $current_question['correct_option'] == $i ? '#d4edda' : '#f8f9fa'; ?>; border-radius: 8px; border: 2px solid <?php echo $current_question['correct_option'] == $i ? '#28a745' : '#dee2e6'; ?>;">
                                    <div style="width: 30px; height: 30px; background: <?php echo $current_question['correct_option'] == $i ? '#28a745' : '#6c757d'; ?>; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        <?php echo chr(64 + $i); ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <?php echo htmlspecialchars($current_question['option' . $i]); ?>
                                    </div>
                                    <?php if ($current_question['correct_option'] == $i): ?>
                                        <div style="color: #28a745; font-weight: bold;">
                                            <i class="fas fa-check-circle"></i> Correct Answer
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php if ($current_question['explanation']): ?>
                        <div style="margin-bottom: 25px;">
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">Explanation</h4>
                            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
                                <?php echo htmlspecialchars($current_question['explanation']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <h4 style="color: #1a2a6c; margin-bottom: 10px;">Question Details</h4>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 100px;">Category:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_question['category']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Difficulty:</td>
                                    <td style="padding: 8px 0;">
                                        <span class="difficulty <?php echo $current_question['difficulty']; ?>">
                                            <?php echo ucfirst($current_question['difficulty']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Created:</td>
                                    <td style="padding: 8px 0;"><?php echo formatDate($current_question['created_at']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <a href="?quiz_id=<?php echo $quiz_id; ?>&action=edit&id=<?php echo $current_question['id']; ?>" class="btn">
                            <i class="fas fa-edit"></i> Edit Question
                        </a>
                        <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Questions
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Questions List -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">
                        <i class="fas fa-list"></i> 
                        Questions for: <?php echo htmlspecialchars($quiz['title']); ?>
                        (<?php echo count($questions); ?>/<?php echo $quiz['total_questions']; ?>)
                    </h3>
                    <div>
                        <a href="?quiz_id=<?php echo $quiz_id; ?>&action=create" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Question
                        </a>
                        <a href="quizzes.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($questions)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-question-circle fa-3x" style="color: #bbdefb; margin-bottom: 20px;"></i>
                            <h4 style="color: #666;">No questions found</h4>
                            <p>This quiz doesn't have any questions yet. Add your first question to get started.</p>
                            <a href="?quiz_id=<?php echo $quiz_id; ?>&action=create" class="btn" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Add First Question
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 20px;">
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo ($total_questions / $quiz['total_questions']) * 100; ?>%"></div>
                            </div>
                            <div style="text-align: center; color: #666; font-size: 0.9rem;">
                                Progress: <?php echo $total_questions; ?>/<?php echo $quiz['total_questions']; ?> questions added
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                            <?php foreach ($questions as $index => $question): ?>
                                <div style="border: 1px solid #e1f5fe; border-radius: 10px; padding: 20px; background: #f8f9fa;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <h4 style="margin: 0; color: #1a2a6c; flex: 1;">
                                            Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars(substr($question['question_text'], 0, 100)); ?><?php echo strlen($question['question_text']) > 100 ? '...' : ''; ?>
                                        </h4>
                                        <div style="display: flex; gap: 10px;">
                                            <span class="difficulty <?php echo $question['difficulty']; ?>">
                                                <?php echo ucfirst($question['difficulty']); ?>
                                            </span>
                                            <span class="status-badge status-verified" style="background: #d1edff;">
                                                Correct: <?php echo chr(64 + $question['correct_option']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <strong>Options:</strong>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">
                                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <div style="padding: 8px; background: <?php echo $question['correct_option'] == $i ? '#d4edda' : 'white'; ?>; border-radius: 5px; border: 1px solid <?php echo $question['correct_option'] == $i ? '#28a745' : '#dee2e6'; ?>;">
                                                    <strong><?php echo chr(64 + $i); ?>:</strong> 
                                                    <?php echo htmlspecialchars(substr($question['option' . $i], 0, 50)); ?><?php echo strlen($question['option' . $i]) > 50 ? '...' : ''; ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 10px;">
                                        <a href="?quiz_id=<?php echo $quiz_id; ?>&action=view&id=<?php echo $question['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?quiz_id=<?php echo $quiz_id; ?>&action=edit&id=<?php echo $question['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?quiz_id=<?php echo $quiz_id; ?>&action=delete&id=<?php echo $question['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" 
                                           onclick="return confirm('Are you sure you want to delete this question? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Highlight correct option when selected
        document.querySelectorAll('input[type="radio"][name="correct_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove correct-option class from all options
                document.querySelectorAll('.option-container input[type="text"]').forEach(input => {
                    input.classList.remove('correct-option');
                });
                
                // Add correct-option class to the selected option
                const optionNumber = this.value;
                const selectedInput = document.querySelector(`input[name="option${optionNumber}"]`);
                if (selectedInput) {
                    selectedInput.classList.add('correct-option');
                }
            });
        });

        // Initialize correct option on page load
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[type="radio"][name="correct_option"]:checked');
            if (checkedRadio) {
                checkedRadio.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>