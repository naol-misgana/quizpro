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
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = $_FILES['payment_proof']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . $user['id'] . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $file_path)) {
                // Update user payment status and file path
                $stmt = $pdo->prepare("UPDATE users SET payment_status = 'pending', payment_proof = ?, payment_date = NOW() WHERE id = ?");
                if ($stmt->execute([$filename, $user['id']])) {
                    $_SESSION['payment_status'] = 'pending';
                    $success = 'Payment proof uploaded successfully! Please wait for admin verification.';
                } else {
                    $error = 'Failed to update payment information.';
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        } else {
            $error = 'Invalid file type. Please upload JPG, PNG, or PDF files only.';
        }
    } else {
        $error = 'Please select a payment proof file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
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
                    <a href="dashboard.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="../logout.php" class="btn btn-danger" style="padding: 8px 15px; font-size: 0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 style="margin: 0; font-size: 2rem;"><i class="fas fa-credit-card"></i> Payment Verification</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h3 style="color: #1a2a6c; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Payment Instructions</h3>
                    <p><strong>Payment Amount:</strong> <?php echo PAYMENT_AMOUNT; ?> <?php echo PAYMENT_CURRENCY; ?></p>
                    <p><strong>Payment Methods:</strong></p>
                    <ul style="margin-left: 20px;">
                        <li><strong>TeleBirr:</strong> 2519XXXXXXXX</li>
                        <li><strong>CBE Birr:</strong> 2519XXXXXXXX</li>
                        <li><strong>Bank Transfer:</strong> Commercial Bank of Ethiopia, Account: XXXXXXXXX</li>
                    </ul>
                    <p><strong>Note:</strong> After making payment, upload your payment proof (screenshot or receipt) below.</p>
                </div>

                <?php if ($user['payment_status'] === 'pending'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-clock"></i> 
                        Your payment proof is under review. Please wait for admin verification.
                        <?php if ($user['payment_date']): ?>
                            <br><small>Submitted on: <?php echo formatDate($user['payment_date']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php elseif ($user['payment_status'] === 'verified'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        Your payment has been verified! You can now access all quizzes.
                        <?php if ($user['approved_at']): ?>
                            <br><small>Verified on: <?php echo formatDate($user['approved_at']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php elseif ($user['payment_status'] === 'rejected'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> 
                        Your payment proof was rejected. Please upload a valid payment proof.
                        <br><small>Reason: Invalid or unclear payment proof</small>
                    </div>
                <?php endif; ?>

                <?php if ($user['payment_status'] !== 'verified'): ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label" for="payment_proof"><i class="fas fa-file-upload"></i> Upload Payment Proof</label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small style="color: #666;">Accepted formats: JPG, PNG, PDF (Max: 2MB)</small>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-upload"></i> Submit Payment Proof</button>
                    </form>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>