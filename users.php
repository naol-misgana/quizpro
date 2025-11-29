<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin(); // This ensures only admins can access

// Rest of the code...


if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle user actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;
$message = '';

if ($action === 'activate' && $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = 'User activated successfully!';
} elseif ($action === 'deactivate' && $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = 'User deactivated successfully!';
} elseif ($action === 'delete' && $user_id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'user'");
    $stmt->execute([$user_id]);
    $message = 'User deleted successfully!';
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["user_type = 'user'"];
$params = [];

if ($filter === 'active') {
    $where_conditions[] = "is_active = TRUE";
} elseif ($filter === 'inactive') {
    $where_conditions[] = "is_active = FALSE";
} elseif ($filter === 'pending') {
    $where_conditions[] = "payment_status = 'pending'";
} elseif ($filter === 'verified') {
    $where_conditions[] = "payment_status = 'verified'";
} elseif ($filter === 'rejected') {
    $where_conditions[] = "payment_status = 'rejected'";
}

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get users
$stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'user'")->fetchColumn();
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'user' AND is_active = TRUE")->fetchColumn();
$pending_payments = $pdo->query("SELECT COUNT(*) FROM users WHERE payment_status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
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
                    <a href="payments.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-credit-card"></i> Payments</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="user-info">
            <div>
                <h2 style="margin: 0; color: #1a2a6c;">User Management</h2>
                <p style="margin: 5px 0 0; color: #666;">Manage all system users</p>
            </div>
            <div class="dashboard-stats" style="grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 0;">
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $active_users; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number"><?php echo $pending_payments; ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="">
                    <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 15px; align-items: end;">
                        <div class="form-group">
                            <label class="form-label" for="search">Search Users</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by username, email, or name...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="filter">Filter By</label>
                            <select class="form-control" id="filter" name="filter">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Payment</option>
                                <option value="verified" <?php echo $filter === 'verified' ? 'selected' : ''; ?>>Verified Payment</option>
                                <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected Payment</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn"><i class="fas fa-filter"></i> Apply Filters</button>
                            <a href="users.php" class="btn btn-outline">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">
                    <i class="fas fa-users"></i> 
                    <?php echo ucfirst($filter); ?> Users 
                    (<?php echo count($users); ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-user-slash fa-3x" style="color: #bbdefb; margin-bottom: 20px;"></i>
                        <h4 style="color: #666;">No users found</h4>
                        <p>No users match your search criteria.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Registered</th>
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
                                    <td>
                                        <span class="status-badge <?php echo $user['is_active'] ? 'status-verified' : 'status-rejected'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['payment_status']; ?>">
                                            <?php echo ucfirst($user['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if ($user['is_active']): ?>
                                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-times"></i> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-check"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['payment_status'] === 'pending'): ?>
                                                <a href="payments.php?action=view&id=<?php echo $user['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-credit-card"></i> Review Payment
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" 
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>