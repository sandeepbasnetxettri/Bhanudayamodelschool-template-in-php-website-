<?php
// Create admin user with specific credentials: admin / admin123
require_once 'config/dbconnection.php';

$email = 'admin';
$password = 'admin123';
$fullName = 'Administrator';
$role = 'admin';

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "Admin user with email '$email' already exists!<br>";
        echo "Updating password...<br>";
        
        // Update the password for existing user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $fullName, $email]);
        echo "Password updated successfully!<br>";
    } else {
        // Create new admin user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $fullName, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        
        // Create user profile entry
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        echo "Admin user created successfully!<br>";
    }
    
    echo "<br><strong>Login Credentials:</strong><br>";
    echo "Email: $email<br>";
    echo "Password: $password<br>";
    echo "Role: $role<br>";
    echo "Full Name: $fullName<br>";
    echo "<br>You can now login at: <a href='auth/php/login.php'>Login Page</a><br>";
    echo "Or go directly to: <a href='auth/php/admin-dashboard.php'>Admin Dashboard</a><br>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>