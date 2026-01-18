<?php
// Debug script to check login issues
require_once 'config/dbconnection.php';

echo "<h2>Debugging Login Issues</h2>";

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

    echo "<hr><h3>Testing Password Verification</h3>";
    
    // Test credentials that should work
    $testCredentials = [
        ['email' => 'admin', 'password' => 'admin123'],
        ['email' => 'admin@gmail.com', 'password' => 'admin123'],
        ['email' => 'skb@gmail.com', 'password' => 'admin123']
    ];
    
    foreach ($testCredentials as $cred) {
        $stmt = $pdo->prepare("SELECT id, email, full_name, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$cred['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>Testing {$cred['email']}: ";
            if (password_verify($cred['password'], $user['password_hash'])) {
                echo "<span style='color: green;'>✅ VALID CREDENTIALS</span></p>";
            } else {
                echo "<span style='color: red;'>❌ INVALID PASSWORD</span></p>";
            }
        } else {
            echo "<p>Testing {$cred['email']}: <span style='color: red;'>❌ USER NOT FOUND</span></p>";
        }
    }
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<h3 style='color: red;'>❌ USERS TABLE DOES NOT EXIST!</h3>";
        echo "<p>The users table is missing from your database. You need to run the database setup first.</p>";
    } else {
        echo "<h3 style='color: green;'>✅ USERS TABLE EXISTS</h3>";
    }
    
    // Check if user_profiles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_profiles'");
    $profilesTableExists = $stmt->fetch();
    
    if (!$profilesTableExists) {
        echo "<h3 style='color: orange;'>⚠️ USER_PROFILES TABLE DOES NOT EXIST!</h3>";
        echo "<p>This might cause issues with user management.</p>";
    } else {
        echo "<h3 style='color: green;'>✅ USER_PROFILES TABLE EXISTS</h3>";
    }

    echo "<hr><h3>Fixing Admin User Accounts</h3>";
    
    // Create or update admin user with the correct credentials
    $credentials = [
        ['email' => 'admin', 'password' => 'admin123', 'name' => 'Administrator'],
        ['email' => 'admin@gmail.com', 'password' => 'admin123', 'name' => 'Admin User'],
        ['email' => 'skb@gmail.com', 'password' => 'admin123', 'name' => 'SKB Admin']
    ];
    
    foreach ($credentials as $cred) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$cred['email']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            echo "<p>{$cred['email']}: Updating password... ";
            $passwordHash = password_hash($cred['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE email = ?");
            $stmt->execute([$passwordHash, $cred['name'], $cred['email']]);
            echo "<span style='color: green;'>✅ Done</span></p>";
        } else {
            echo "<p>{$cred['email']}: Creating user... ";
            $passwordHash = password_hash($cred['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$cred['email'], $cred['name'], $passwordHash]);
            $userId = $pdo->lastInsertId();
            
            // Create user profile
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
            $stmt->execute([$userId]);
            
            echo "<span style='color: green;'>✅ Done</span></p>";
        }
    }
    
    echo "<hr><h3>Final Login Credentials:</h3>";
    echo "<p>Try these credentials in order until one works:</p>";
    echo "<ol>";
    echo "<li><strong>Email:</strong> admin <strong>Password:</strong> admin123</li>";
    echo "<li><strong>Email:</strong> admin@gmail.com <strong>Password:</strong> admin123</li>";
    echo "<li><strong>Email:</strong> skb@gmail.com <strong>Password:</strong> admin123</li>";
    echo "</ol>";
    
    echo "<a href='auth/php/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login Page</a>";
    echo "<a href='.' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Home</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please make sure your database is running and properly configured.</p>";
}
?>