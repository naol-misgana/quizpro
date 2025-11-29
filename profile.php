<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireUser(); // This ensures only regular users can access

// Rest of the code...


if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$user = getUserData();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $institution = sanitize($_POST['institution']);
    
    // Update profile
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, institution = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $phone, $institution, $user['id']])) {
        $_SESSION['full_name'] = $full_name;
        $success = 'Profile updated successfully!';
        $user = getUserData(); // Refresh user data
    } else {
        $error = 'Failed to update profile. Please try again.';
    }
    
    // Change password if provided
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($current_password && $new_password && $confirm_password) {
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user['id']])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
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
        <div class="card">
            <div class="card-header">
                <h1 style="margin: 0; font-size: 2rem;"><i class="fas fa-user-cog"></i> Profile Settings</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Profile Information -->
                    <div>
                        <h3 style="color: #1a2a6c; margin-bottom: 20px; border-bottom: 2px solid #e1f5fe; padding-bottom: 10px;">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small style="color: #666;">Username cannot be changed</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small style="color: #666;">Email cannot be changed</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="full_name">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="institution">Institution/School</label>
                                <input type="text" class="form-control" id="institution" name="institution" value="<?php echo htmlspecialchars($user['institution']); ?>">
                            </div>

                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Account Security -->
                    <div>
                        <h3 style="color: #1a2a6c; margin-bottom: 20px; border-bottom: 2px solid #e1f5fe; padding-bottom: 10px;">
                            <i class="fas fa-shield-alt"></i> Account Security
                        </h3>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <small style="color: #666;">Leave blank to keep current password</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>

                        <!-- Account Statistics -->
                        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 10px;">
                            <h4 style="color: #1a2a6c; margin-bottom: 15px;"><i class="fas fa-chart-bar"></i> Account Statistics</h4>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold; width: 140px;">Member Since:</td>
                                    <td style="padding: 8px 0;"><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Account Status:</td>
                                    <td style="padding: 8px 0;">
                                        <span class="status-badge <?php echo $user['is_active'] ? 'status-verified' : 'status-rejected'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Payment Status:</td>
                                    <td style="padding: 8px 0;">
                                        <span class="status-badge status-<?php echo $user['payment_status']; ?>">
                                            <?php echo ucfirst($user['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                                // Get quiz attempts count
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ?");
                                $stmt->execute([$user['id']]);
                                $attempts_count = $stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td style="padding: 8px 0; font-weight: bold;">Quiz Attempts:</td>
                                    <td style="padding: 8px 0;"><?php echo $attempts_count; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>