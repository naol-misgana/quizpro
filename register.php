<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $institution = sanitize($_POST['institution']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Register user        
// ... in the registration part ...

if ($user_id) {
    // Set session for the new user
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_type'] = 'user'; // Explicitly set as regular user
    $_SESSION['payment_status'] = 'pending';
    $_SESSION['full_name'] = $full_name;
    
    redirect('user/dashboard.php');
}
// ... rest of the code ...
            $user_id = registerUser([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'full_name' => $full_name,
                'phone' => $phone,
                'institution' => $institution
            ]);

            if ($user_id) {
                // Auto login after registration
                if (loginUser($username, $password)) {
                    redirect('user/dashboard.php');
                } else {
                    $success = 'Registration successful! Please login.';
                }
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 50px auto;">
            <div class="card-header">
                <h1 style="margin: 0; font-size: 2rem;"><i class="fas fa-user-plus"></i> Student Registration</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username"><i class="fas fa-user"></i> Username *</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="full_name"><i class="fas fa-id-card"></i> Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="institution"><i class="fas fa-school"></i> Institution/School</label>
                        <input type="text" class="form-control" id="institution" name="institution" value="<?php echo isset($_POST['institution']) ? $_POST['institution'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-user-plus"></i> Register</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>