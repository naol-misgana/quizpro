<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? 0;
$message = '';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $action_type = $_POST['action_type'];
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($action_type === 'verify') {
        $stmt = $pdo->prepare("UPDATE users SET payment_status = 'verified', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id'], $user_id])) {
            $message = 'Payment verified successfully!';
        }
    } elseif ($action_type === 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET payment_status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id'], $user_id])) {
            $message = 'Payment rejected successfully!';
        }
    }
    
    // Record in payments table
    if ($message) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, currency, status, verified_by, verified_at, notes) 
                              VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([
            $user_id, 
            PAYMENT_AMOUNT, 
            PAYMENT_CURRENCY, 
            $action_type === 'verify' ? 'completed' : 'failed',
            $_SESSION['user_id'],
            $notes
        ]);
        
        // Redirect to stay on the same page
        header("Location: payments.php?action=view&id=" . $user_id . "&message=" . urlencode($message));
        exit();
    }
}

// Check for message in URL
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

// Get payment statistics
$pending_count = $pdo->query("SELECT COUNT(*) FROM users WHERE payment_status = 'pending'")->fetchColumn();
$verified_count = $pdo->query("SELECT COUNT(*) FROM users WHERE payment_status = 'verified'")->fetchColumn();
$rejected_count = $pdo->query("SELECT COUNT(*) FROM users WHERE payment_status = 'rejected'")->fetchColumn();

// Get users based on filter
$filter = $_GET['filter'] ?? 'pending';
$where_clause = "WHERE u.user_type = 'user'";
if ($filter === 'pending') $where_clause .= " AND u.payment_status = 'pending'";
elseif ($filter === 'verified') $where_clause .= " AND u.payment_status = 'verified'";
elseif ($filter === 'rejected') $where_clause .= " AND u.payment_status = 'rejected'";

$stmt = $pdo->prepare("SELECT u.*, a.username as approved_by_name 
                       FROM users u 
                       LEFT JOIN users a ON u.approved_by = a.id 
                       $where_clause 
                       ORDER BY u.payment_date DESC");
$stmt->execute();
$users = $stmt->fetchAll();

// Get specific user for detailed view
$current_user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT u.*, a.username as approved_by_name 
                           FROM users u 
                           LEFT JOIN users a ON u.approved_by = a.id 
                           WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - <?php echo SITE_NAME; ?></title>
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
                <h2 style="margin: 0; color: #1a2a6c;">Payment Management</h2>
                <p style="margin: 5px 0 0; color: #666;">Verify and manage user payments</p>
            </div>
            <div class="dashboard-stats" style="grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 0;">
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $verified_count; ?></div>
                    <div class="stat-label">Verified</div>
                </div>
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $rejected_count; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div style="margin-bottom: 20px; border-bottom: 2px solid #e1f5fe;">
            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? '' : 'btn-outline'; ?>" style="border-radius: 20px 20px 0 0; margin-right: 5px;">
                <i class="fas fa-clock"></i> Pending (<?php echo $pending_count; ?>)
            </a>
            <a href="?filter=verified" class="btn <?php echo $filter === 'verified' ? '' : 'btn-outline'; ?>" style="border-radius: 20px 20px 0 0; margin-right: 5px;">
                <i class="fas fa-check-circle"></i> Verified (<?php echo $verified_count; ?>)
            </a>
            <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? '' : 'btn-outline'; ?>" style="border-radius: 20px 20px 0 0;">
                <i class="fas fa-times-circle"></i> Rejected (<?php echo $rejected_count; ?>)
            </a>
        </div>

        <?php if ($action === 'view' && $current_user): ?>
            <!-- Detailed User View -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;"><i class="fas fa-user"></i> Payment Review - <?php echo htmlspecialchars($current_user['full_name']); ?></h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <!-- User Information -->
                        <div>
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">User Information</h4>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 120px;">Username:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_user['username']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Full Name:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_user['full_name']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Email:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_user['email']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Phone:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_user['phone']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Institution:</td>
                                    <td style="padding: 8px 0;"><?php echo htmlspecialchars($current_user['institution']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Registered:</td>
                                    <td style="padding: 8px 0;"><?php echo formatDate($current_user['created_at']); ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Payment Information -->
                        <div>
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">Payment Information</h4>
                            <?php if ($current_user['payment_proof']): ?>
                                <div style="margin-bottom: 20px;">
                                    <strong>Payment Proof:</strong><br>
                                    <?php
                                    $file_extension = pathinfo($current_user['payment_proof'], PATHINFO_EXTENSION);
                                    $file_path = '../uploads/payments/' . $current_user['payment_proof'];
                                    ?>
                                    <?php if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <img src="<?php echo $file_path; ?>" alt="Payment Proof" style="max-width: 100%; border: 2px solid #e1f5fe; border-radius: 8px; margin-top: 10px;">
                                    <?php else: ?>
                                        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center; margin-top: 10px;">
                                            <i class="fas fa-file-pdf fa-3x" style="color: #dc3545;"></i>
                                            <p style="margin: 10px 0 0;">PDF Document</p>
                                            <a href="<?php echo $file_path; ?>" target="_blank" class="btn" style="margin-top: 10px;">
                                                <i class="fas fa-download"></i> Download PDF
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 120px;">Status:</td>
                                    <td style="padding: 8px 0;">
                                        <span class="status-badge status-<?php echo $current_user['payment_status']; ?>">
                                            <?php echo ucfirst($current_user['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Payment Date:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_user['payment_date'] ? formatDate($current_user['payment_date']) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Verified By:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_user['approved_by_name'] ?: 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Verified At:</td>
                                    <td style="padding: 8px 0;"><?php echo $current_user['approved_at'] ? formatDate($current_user['approved_at']) : 'N/A'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($current_user['payment_status'] === 'pending'): ?>
                        <!-- Action Buttons -->
                        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e1f5fe;">
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;">Take Action</h4>
                            <form method="POST" action="payments.php?action=view&id=<?php echo $current_user['id']; ?>" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <input type="hidden" name="user_id" value="<?php echo $current_user['id']; ?>">
                                
                                <div>
                                    <label class="form-label" for="notes_verify"><i class="fas fa-check-circle"></i> Verification Notes (Optional)</label>
                                    <textarea class="form-control" id="notes_verify" name="notes" rows="3" placeholder="Add notes for verification..."></textarea>
                                    <button type="submit" name="action_type" value="verify" class="btn btn-success" style="width: 100%; margin-top: 10px;">
                                        <i class="fas fa-check"></i> Verify Payment
                                    </button>
                                </div>

                                <div>
                                    <label class="form-label" for="notes_reject"><i class="fas fa-times-circle"></i> Rejection Reason (Optional)</label>
                                    <textarea class="form-control" id="notes_reject" name="notes" rows="3" placeholder="Add reason for rejection..."></textarea>
                                    <button type="submit" name="action_type" value="reject" class="btn btn-danger" style="width: 100%; margin-top: 10px;">
                                        <i class="fas fa-times"></i> Reject Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="payments.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Payments</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-list"></i> 
                        <?php echo ucfirst($filter); ?> Payments 
                        (<?php echo count($users); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox fa-3x" style="color: #bbdefb; margin-bottom: 20px;"></i>
                            <h4 style="color: #666;">No <?php echo $filter; ?> payments found</h4>
                            <p>There are no users with <?php echo $filter; ?> payment status.</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Verified By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($user['full_name']); ?></small><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($user['institution']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['email']); ?><br>
                                            <small><?php echo htmlspecialchars($user['phone']); ?></small>
                                        </td>
                                        <td><?php echo $user['payment_date'] ? formatDate($user['payment_date']) : 'N/A'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['payment_status']; ?>">
                                                <?php echo ucfirst($user['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $user['approved_by_name'] ?: 'N/A'; ?><br>
                                            <?php if ($user['approved_at']): ?>
                                                <small><?php echo formatDate($user['approved_at']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?action=view&id=<?php echo $user['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($user['payment_status'] === 'pending'): ?>
                                                <a href="?action=view&id=<?php echo $user['id']; ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-check"></i> Verify
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>