<?php
// Script to create an admin user with specific credentials
require_once 'config/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from POST request
    $email = $_POST['email'] ?? 'skb@gmail.com';
    $password = $_POST['password'] ?? 'adminpass';
    $fullName = $_POST['fullName'] ?? 'Admin User';
    $role = $_POST['role'] ?? 'admin';
} else {
    // Default values for direct script access
    $email = 'skb@gmail.com'; // Based on your request "gmail SKB"
    $password = 'adminpass'; // Default admin password - change as needed
    $fullName = 'Admin User';
    $role = 'admin';
}
try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existingUser) {
        echo "Admin user with email '$email' already exists!<br>";
        echo "Email: $email<br>";
        echo "Role: $role<br>";
    } else {
        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        // Create the admin user
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $fullName, $passwordHash, $role]);
        $userId = $pdo->lastInsertId();
        // Create user profile entry
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        echo "Admin user created successfully!<br>";
        echo "Email: $email<br>";
        echo "Role: $role<br>";
        echo "Full Name: $fullName<br>";
        echo "(Password has been securely hashed)<br>";
    }
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage() . "<br>";
}
?>