<?php
// Simple test to check database connection and create admin user
echo "Testing database connection...<br>";

try {
    require_once 'config/dbconnection.php';
    echo "✅ Database connection successful!<br><br>";
    
    // Check existing users
    $stmt = $pdo->query("SELECT email, full_name, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Existing Users:</h3>";
    if (empty($users)) {
        echo "No users found in database.<br>";
    } else {
        foreach ($users as $user) {
            echo "- {$user['email']} ({$user['role']}) - {$user['full_name']}<br>";
        }
    }
    
    echo "<br><h3>Creating Admin User...</h3>";
    
    $email = 'admin';
    $password = 'admin123';
    $fullName = 'Administrator';
    $role = 'admin';
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "Admin user already exists. Updating password...<br>";
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $fullName, $email]);
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $fullName, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        
        // Create user profile
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        echo "Admin user created successfully!<br>";
    }
    
    echo "<br>✅ <strong>Admin user ready!</strong><br>";
    echo "<strong>Email:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123<br>";
    echo "<strong>Role:</strong> admin<br><br>";
    
    echo "<a href='auth/php/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a><br><br>";
    echo "<a href='auth/php/admin-dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Please make sure XAMPP is running and the database is set up correctly.<br>";
}
?>