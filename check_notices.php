<?php
// Simple script to check if there are notices in the database
require_once 'config/dbconnection.php';

try {
    // Query to check if notices table exists and count records
    $checkTableQuery = "SHOW TABLES LIKE 'notices'";
    $tableResult = $pdo->query($checkTableQuery);
    
    if ($tableResult->rowCount() == 0) {
        echo "ERROR: notices table does not exist in the database.\n";
    } else {
        echo "SUCCESS: notices table exists.\n";
        
        // Count total notices
        $countQuery = "SELECT COUNT(*) as total FROM notices";
        $countResult = $pdo->query($countQuery);
        $countData = $countResult->fetch(PDO::FETCH_ASSOC);
        echo "Total notices in database: " . $countData['total'] . "\n";
        
        // Count published notices
        $publishedCountQuery = "SELECT COUNT(*) as total FROM notices WHERE is_published = 1";
        $publishedResult = $pdo->query($publishedCountQuery);
        $publishedData = $publishedResult->fetch(PDO::FETCH_ASSOC);
        echo "Published notices in database: " . $publishedData['total'] . "\n";
        
        // Get some sample notices
        $sampleQuery = "SELECT id, title, content, notice_type, is_published, created_at FROM notices ORDER BY created_at DESC LIMIT 5";
        $sampleResult = $pdo->query($sampleQuery);
        
        if ($sampleResult->rowCount() > 0) {
            echo "\nSample notices from database:\n";
            while ($row = $sampleResult->fetch(PDO::FETCH_ASSOC)) {
                echo "- ID: {$row['id']}, Title: {$row['title']}, Type: {$row['notice_type']}, Published: {$row['is_published']}, Date: {$row['created_at']}\n";
            }
        } else {
            echo "\nNo notices found in database.\n";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>