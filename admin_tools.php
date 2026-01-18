<?php
// Admin Tools for School Management System
session_start();

// Simple authentication - in production, use proper authentication
$authenticated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    // Simple password check - in production, use proper authentication
    if ($_POST['password'] === 'admin123') { // Change this in production!
        $_SESSION['admin_authenticated'] = true;
        $authenticated = true;
    }
}

if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { background: #007cba; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
            button:hover { background: #005a87; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin Tools Login</h2>
            <form method="post">
                <input type="password" name="password" placeholder="Enter admin password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_notices':
                require_once 'add_sample_notices.php';
                $message = 'Sample notices added successfully!';
                break;
                
            case 'check_notices':
                ob_start();
                require_once 'check_notices.php';
                $message = nl2br(ob_get_clean());
                break;
                
            case 'create_user':
                // Create a new user
                require_once 'config/dbconnection.php';
                $email = $_POST['email'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'student';
                
                if ($email && $full_name && $password) {
                    try {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
                        $result = $stmt->execute([$email, $full_name, $hashed_password, $role]);
                        
                        if ($result) {
                            $message = "User '$full_name' created successfully!";
                        } else {
                            $message = "Failed to create user.";
                        }
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                    }
                } else {
                    $message = "Please fill in all required fields.";
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Tools</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        h1 { color: #007cba; }
        h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>_school Management System - Admin Tools</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Notice Board Management</h2>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="check_notices">
                <button type="submit" class="btn">Check Current Notices</button>
            </form>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="add_notices">
                <button type="submit" class="btn btn-success">Add Sample Notices</button>
            </form>
            <p><small>Click "Add Sample Notices" to populate the notice board with demo content.</small></p>
        </div>
        
        <div class="card">
            <h2>Create New User</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_user">
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Create User</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Quick Links</h2>
            <a href="pages/notice.php" class="btn">View Notice Board</a>
            <a href="auth/html/login.html" class="btn">Login Page</a>
            <a href="auth/html/register.html" class="btn">Registration Page</a>
            <a href="index.php" class="btn">Homepage</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <form method="post">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>
    
    <?php
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>