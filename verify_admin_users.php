<?php
// Verify admin users in the database
require_once 'config/dbconnection.php';

echo "<h2>Verifying Admin Users in Database</h2>";

try {
    // Query all users to see what's available
    $stmt = $pdo->query("SELECT id, email, full_name, role, created_at FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Users in Database:</h3>";
    if (empty($allUsers)) {
        echo "<p>No users found in the database.</p>";
    } else {
        foreach ($allUsers as $user) {
            $isAdmin = ($user['role'] === 'admin') ? " <strong>(ADMIN)</strong>" : "";
            echo "<p>ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Role: {$user['role']}, Created: {$user['created_at']}{$isAdmin}</p>";
        }
    }

    echo "<hr><h3>Available Login Options:</h3>";
    echo "<p><strong>Option 1:</strong> Use the existing admin account if available</p>";
    echo "<p><strong>Option 2:</strong> Create a new admin account with secure password</p>";
    
    // Create or update admin user with a known password
    echo "<hr><h3>Setting up admin user with credentials: admin / admin123</h3>";
    
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
    
    // Check for the original admin user that might have been created
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['skb@gmail.com']);
    $existingSkbUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSkbUser) {
        echo "<p>Found original admin user: skb@gmail.com. Updating password...</p>";
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$passwordHash, 'skb@gmail.com']);
        echo "<p>✅ Password updated successfully for email: skb@gmail.com</p>";
    }
    
    echo "<hr><h3>Final Login Credentials (try in this order):</h3>";
    echo "<ol>";
    echo "<li><strong>Email:</strong> admin <strong>Password:</strong> admin123</li>";
    echo "<li><strong>Email:</strong> admin@gmail.com <strong>Password:</strong> admin123</li>";
    echo "<li><strong>Email:</strong> skb@gmail.com <strong>Password:</strong> admin123</li>";
    echo "</ol>";
    
    echo "<a href='auth/php/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login Page</a>";
    echo "<a href='check_admin_users.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Check Again</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>