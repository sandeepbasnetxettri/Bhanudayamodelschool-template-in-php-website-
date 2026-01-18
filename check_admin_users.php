<?php
// Check all admin users in the database
require_once 'config/dbconnection.php';

echo "<h2>Checking Admin Users in Database</h2>";

try {
    // Query all admin users
    $stmt = $pdo->query("SELECT id, email, full_name, role FROM users WHERE role = 'admin'");
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($adminUsers)) {
        echo "<p>No admin users found in the database.</p>";
    } else {
        echo "<h3>Admin Users Found:</h3>";
        foreach ($adminUsers as $user) {
            echo "<p>ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Role: {$user['role']}</p>";
        }
    }
    
    // Query all users to see what's available
    $stmt = $pdo->query("SELECT id, email, full_name, role FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Users in Database:</h3>";
    if (empty($allUsers)) {
        echo "<p>No users found in the database.</p>";
    } else {
        foreach ($allUsers as $user) {
            $isAdmin = ($user['role'] === 'admin') ? " <strong>(ADMIN)</strong>" : "";
            echo "<p>ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Role: {$user['role']}{$isAdmin}</p>";
        }
    }

    // Create or update admin user with the specific credentials you need
    echo "<hr><h3>Creating/Updating admin user with credentials: admin / admin123</h3>";
    
    $email = 'admin';
    $password = 'admin123';
    $fullName = 'Administrator';
    $role = 'admin';
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<p>Admin user already exists. Updating password...</p>";
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $fullName, $email]);
        echo "<p>✅ Password updated successfully for email: $email</p>";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $fullName, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        
        // Create user profile
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        echo "<p>✅ Admin user created successfully with email: $email</p>";
    }
    
    // Also try with admin@gmail.com to cover both possibilities
    $email2 = 'admin@gmail.com';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email2]);
    $existingUser2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser2) {
        echo "<p>Admin user with email: $email2 already exists. Updating password...</p>";
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $fullName, $email2]);
        echo "<p>✅ Password updated successfully for email: $email2</p>";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email2, $fullName, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        
        // Create user profile
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        echo "<p>✅ Admin user created successfully with email: $email2</p>";
    }
    
    echo "<hr><h3>Final Login Credentials:</h3>";
    echo "<p><strong>Email Options:</strong> admin OR admin@gmail.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Role:</strong> admin</p><br>";
    
    echo "<a href='auth/php/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login Page</a>";
    echo "<a href='setup_admin_test.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Re-check Setup</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>