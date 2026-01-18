<?php
// Quick database check
require_once 'config/dbconnection.php';

echo "<h2>Database Connection Check</h2>";

try {
    // Check if notices table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notices'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✓ Notices table exists</p>";
        
        // Count notices
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notices");
        $count = $stmt->fetch()['count'];
        echo "<p>Total notices in database: <strong>$count</strong></p>";
        
        // Count published notices
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notices WHERE is_published = 1");
        $published_count = $stmt->fetch()['count'];
        echo "<p>Published notices: <strong>$published_count</strong></p>";
        
        if ($published_count > 0) {
            echo "<p style='color:green;'><strong>Notice board should now display content!</strong></p>";
            echo "<p><a href='pages/notice.php'>View Notice Board</a></p>";
        } else {
            echo "<p style='color:orange;'>No published notices found. Click 'Add Sample Notices' in admin tools.</p>";
        }
        
    } else {
        echo "<p style='color:red;'>✗ Notices table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
<br>
<a href="admin_tools.php">Go to Admin Tools</a> | 
<a href="pages/notice.php">View Notice Board</a>