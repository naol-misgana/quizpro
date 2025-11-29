<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (isLoggedIn() && isAdmin()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (loginAdmin($username, $password)) {
            redirect('dashboard.php');
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 500px; margin: 100px auto;">
            <div class="card-header">
                <h1 style="margin: 0; font-size: 2rem;"><i class="fas fa-lock"></i> Admin Login</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username"><i class="fas fa-user"></i> Admin Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-sign-in-alt"></i> Admin Login</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="../index.php"><i class="fas fa-home"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>