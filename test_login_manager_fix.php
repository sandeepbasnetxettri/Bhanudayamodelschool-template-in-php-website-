<?php
// Test script to verify the login record manager fix
session_start();

// Set admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_email'] = 'admin@test.com';

// Database connection
require_once 'config/dbconnection.php';

echo "<h2>Testing Login Record Manager Fix</h2>";

try {
    // Test the corrected query
    echo "<h3>Testing Database Query...</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.email,
            u.full_name,
            u.role,
            u.created_at,
            CASE 
                WHEN s.id IS NOT NULL THEN 'Yes'
                WHEN t.id IS NOT NULL THEN 'Yes'
                WHEN up.id IS NOT NULL AND u.role = 'parent' THEN 'Yes'
                ELSE 'No'
            END as has_profile
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id  
        LEFT JOIN user_profiles up ON u.id = up.user_id AND u.role = 'parent'
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Query executed successfully!</p>";
    echo "<p>Found " . count($records) . " records</p>";
    
    if (!empty($records)) {
        echo "<h4>Sample Records:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Has Profile</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['id']) . "</td>";
            echo "<td>" . htmlspecialchars($record['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($record['email']) . "</td>";
            echo "<td>" . htmlspecialchars($record['role']) . "</td>";
            echo "<td>" . htmlspecialchars($record['has_profile']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test if we can access the login record manager
    echo "<h3>Testing Page Access...</h3>";
    echo "<p><a href='auth/php/login-record-manager.php' target='_blank'>Click here to test the Login Record Manager</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Check if it's the specific error we're fixing
    if (strpos($e->getMessage(), 'Base table or view not found') !== false && 
        strpos($e->getMessage(), 'parents') !== false) {
        echo "<p style='color: orange;'>⚠️ This is the exact error we're fixing!</p>";
        echo "<p>The fix has been applied to the login-record-manager.php file.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
?>