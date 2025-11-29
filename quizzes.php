<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin(); // This ensures only admins can access

// Rest of the code...
<?php
<a href="questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-success" style="flex: 1; text-align: center; padding: 8px;">
    <i class="fas fa-question-circle"></i> Questions
</a>

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$action = $_GET['action'] ?? '';
$quiz_id = $_GET['id'] ?? 0;
$message = '';

// Handle quiz actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_quiz'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $category = sanitize($_POST['category']);
        $time_limit = (int)$_POST['time_limit'];
        $total_questions = (int)$_POST['total_questions'];
        $passing_score = (int)$_POST['passing_score'];
        
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, category, time_limit, total_questions, passing_score, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $category, $time_limit, $total_questions, $passing_score, $_SESSION['user_id']])) {
            $message = 'Quiz created successfully!';
        }
    } elseif (isset($_POST['update_quiz'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $category = sanitize($_POST['category']);
        $time_limit = (int)$_POST['time_limit'];
        $total_questions = (int)$_POST['total_questions'];
        $passing_score = (int)$_POST['passing_score'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, category = ?, time_limit = ?, total_questions = ?, passing_score = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $category, $time_limit, $total_questions, $passing_score, $is_active, $quiz_id])) {
            $message = 'Quiz updated successfully!';
        }
    }
}

if ($action === 'delete' && $quiz_id) {
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $message = 'Quiz deleted successfully!';
} elseif ($action === 'toggle' && $quiz_id) {
    $stmt = $pdo->prepare("UPDATE quizzes SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $message = 'Quiz status updated!';
}

// Get all quizzes
$stmt = $pdo->query("SELECT q.*, COUNT(qa.id) as attempt_count 
                     FROM quizzes q 
                     LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id 
                     GROUP BY q.id 
                     ORDER BY q.created_at DESC");
$quizzes = $stmt->fetchAll();

// Get specific quiz for editing
$current_quiz = null;
if ($quiz_id && ($action === 'edit' || $action === 'view')) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $current_quiz = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="dashboard.php" class="navbar-brand"><?php echo SITE_NAME; ?> Admin</a>
                <div>
                    <a href="dashboard.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="users.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-users"></i> Users</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="user-info">
            <div>
                <h2 style="margin: 0; color: #1a2a6c;">Quiz Management</h2>
                <p style="margin: 5px 0 0; color: #666;">Create and manage quizzes</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($action === 'create' || ($action === 'edit' && $current_quiz)): ?>
            <!-- Create/Edit Quiz Form -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-<?php echo $action === 'create' ? 'plus' : 'edit'; ?>"></i>
                        <?php echo $action === 'create' ? 'Create New Quiz' : 'Edit Quiz: ' . htmlspecialchars($current_quiz['title']); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label" for="title">Quiz Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo $current_quiz ? htmlspecialchars($current_quiz['title']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="category">Category *</label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       value="<?php echo $current_quiz ? htmlspecialchars($current_quiz['category']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="time_limit">Time Limit (minutes) *</label>
                                <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                       value="<?php echo $current_quiz ? $current_quiz['time_limit'] : '60'; ?>" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="total_questions">Total Questions *</label>
                                <input type="number" class="form-control" id="total_questions" name="total_questions" 
                                       value="<?php echo $current_quiz ? $current_quiz['total_questions'] : '50'; ?>" min="1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="passing_score">Passing Score (%) *</label>
                                <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                       value="<?php echo $current_quiz ? $current_quiz['passing_score'] : '60'; ?>" min="1" max="100" required>
                            </div>
                            <?php if ($current_quiz): ?>
                                <div class="form-group">
                                    <label class="form-label" for="is_active">Status</label>
                                    <div style="margin-top: 8px;">
                                        <input type="checkbox" id="is_active" name="is_active" <?php echo $current_quiz['is_active'] ? 'checked' : ''; ?>>
                                        <label for="is_active" style="font-weight: normal;">Active Quiz</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $current_quiz ? htmlspecialchars($current_quiz['description']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; gap: 15px;">
                            <button type="submit" name="<?php echo $action === 'create' ? 'create_quiz' : 'update_quiz'; ?>" class="btn">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'create' ? 'Create Quiz' : 'Update Quiz'; ?>
                            </button>
                            <a href="quizzes.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'view' && $current_quiz): ?>
            <!-- Quiz Details View -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;"><i class="fas fa-info-circle"></i> Quiz Details: <?php echo htmlspecialchars($current_quiz['title']); ?></h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div>
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">Quiz Information</h4>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 140px;">Title:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_quiz['title']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Category:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_quiz['category']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Description:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_quiz['description']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Time Limit:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_quiz['time_limit']; ?> minutes</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">Quiz Settings</h4>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 140px;">Total Questions:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_quiz['total_questions']; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Passing Score:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_quiz['passing_score']; ?>%</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Status:</td>
                                    <td style="padding: 8px 0;">
                                        <span class="status-badge <?php echo $current_quiz['is_active'] ? 'status-verified' : 'status-rejected'; ?>">
                                            <?php echo $current_quiz['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Created:</td>
                                    <td style="padding: 8px 0;"><?php echo formatDate($current_quiz['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Attempts:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_quiz['attempt_count']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e1f5fe;">
                        <h4 style="color: #1a2a6c; margin-bottom: 15px;">Quick Actions</h4>
                        <div style="display: flex; gap: 15px;">
                            <a href="?action=edit&id=<?php echo $current_quiz['id']; ?>" class="btn">
                                <i class="fas fa-edit"></i> Edit Quiz
                            </a>
                            <a href="questions.php?quiz_id=<?php echo $current_quiz['id']; ?>" class="btn btn-success">
                                <i class="fas fa-question-circle"></i> Manage Questions
                            </a>
                            <a href="?action=toggle&id=<?php echo $current_quiz['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-power-off"></i> 
                                <?php echo $current_quiz['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="quizzes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Quizzes List -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;"><i class="fas fa-book"></i> All Quizzes (<?php echo count($quizzes); ?>)</h3>
                    <a href="?action=create" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create New Quiz
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($quizzes)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-book-open fa-3x" style="color: #bbdefb; margin-bottom: 20px;"></i>
                            <h4 style="color: #666;">No quizzes found</h4>
                            <p>Create your first quiz to get started.</p>
                            <a href="?action=create" class="btn" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Create First Quiz
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                            <?php foreach ($quizzes as $quiz): ?>
                                <div style="border: 1px solid #e1f5fe; border-radius: 10px; padding: 20px; background: #f8f9fa; position: relative;">
                                    <?php if (!$quiz['is_active']): ?>
                                        <div style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            Inactive
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h4 style="margin: 0 0 10px; color: #1a2a6c;"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                    <p style="margin: 0 0 15px; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                    
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem;">
                                        <span><i class="fas fa-clock"></i> <?php echo $quiz['time_limit']; ?> mins</span>
                                        <span><i class="fas fa-question-circle"></i> <?php echo $quiz['total_questions']; ?> questions</span>
                                        <span><i class="fas fa-trophy"></i> <?php echo $quiz['passing_score']; ?>% to pass</span>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem;">
                                        <span><i class="fas fa-play-circle"></i> <?php echo $quiz['attempt_count']; ?> attempts</span>
                                        <span><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($quiz['category']); ?></span>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <a href="?action=view&id=<?php echo $quiz['id']; ?>" class="btn" style="flex: 1; text-align: center; padding: 8px;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?action=edit&id=<?php echo $quiz['id']; ?>" class="btn" style="flex: 1; text-align: center; padding: 8px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?action=delete&id=<?php echo $quiz['id']; ?>" class="btn btn-danger" style="padding: 8px 12px;" 
                                           onclick="return confirm('Are you sure you want to delete this quiz? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
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
</body>
</html>