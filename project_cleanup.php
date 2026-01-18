<?php
/**
 * Project Cleanup Script
 * Removes test files and organizes the project structure
 */

// Files to delete (test and debug files)
$filesToDelete = [
    // Test files
    'test.php',
    'test_db_connection.php',
    'test_db_tables.php',
    'test_email.php',
    'test_avatar_upload.php',
    'test_parent_pages.php',
    'test_parent_student_link.php',
    'test_student_deletion.php',
    'test_login_record_manager.php',
    'test_login_manager_fix.php',
    
    // Database update scripts (keep one main update script)
    'cli_update_db.php',
    'update_database.php',
    'update_database_web.php',
    'update_user_profiles_table.php',
    'run_manual_update.php',
    'apply_cascade_deletes.php',
    'apply_certificate_column_update.php',
    'update_parent_schema.php',
    'update_push_notifications_tables.php',
    
    // Foreign key fix scripts (keep the comprehensive one)
    'fix_foreign_keys.php',
    'fix_cascade_delete.php',
    
    // Diagnostic scripts
    'diagnose_push_notifications.php',
    'check_db.php',
    'check_db_web.php',
    
    // Test user/subscription creation
    'create_test_user.php',
    'create_test_subscription.php',
    'create_test_subscriptions.php',
    'create_admin_user.php',
    
    // Cleanup scripts
    'cleanup_diagnostics.php',
    'cleanup_test_files.php',
    
    // Other utility scripts
    'ensure_parent_id_column.php',
    'test_registration_fix.php'
];

// Directories to create for better organization
$directoriesToCreate = [
    'maintenance',  // For maintenance scripts
    'backup',       // For database backups
    'logs'          // For log files
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Project Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .file-list { background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .directory-list { background: #e8f5e8; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .button { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background-color: #45a049; }
    </style>
</head>
<body>
<div class='container'>
<h1>🧹 Project Cleanup Tool</h1>";

// Create directories first
echo "<h2>📁 Creating Organization Directories</h2>";
echo "<div class='directory-list'>";

foreach ($directoriesToCreate as $dir) {
    $dirPath = __DIR__ . '/' . $dir;
    if (!is_dir($dirPath)) {
        if (mkdir($dirPath, 0755, true)) {
            echo "<p class='success'>✓ Created directory: $dir</p>";
        } else {
            echo "<p class='error'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p class='info'>ℹ Directory already exists: $dir</p>";
    }
}
echo "</div>";

// Move essential maintenance files to maintenance directory
echo "<h2>📦 Moving Essential Maintenance Files</h2>";
$maintenanceFiles = [
    'fix_all_foreign_keys.php',
    'fix_foreign_keys.php'
];

foreach ($maintenanceFiles as $file) {
    $source = __DIR__ . '/' . $file;
    $destination = __DIR__ . '/maintenance/' . $file;
    
    if (file_exists($source) && !file_exists($destination)) {
        if (rename($source, $destination)) {
            echo "<p class='success'>✓ Moved to maintenance: $file</p>";
        } else {
            echo "<p class='error'>✗ Failed to move: $file</p>";
        }
    } elseif (file_exists($destination)) {
        echo "<p class='info'>ℹ Already in maintenance: $file</p>";
    }
}

// Delete test files
echo "<h2>🗑️ Removing Test and Debug Files</h2>";
echo "<div class='file-list'>";

$deletedCount = 0;
$errorCount = 0;

foreach ($filesToDelete as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "<p class='success'>✓ Deleted: $file</p>";
            $deletedCount++;
        } else {
            echo "<p class='error'>✗ Failed to delete: $file</p>";
            $errorCount++;
        }
    } else {
        echo "<p class='info'>ℹ Not found: $file</p>";
    }
}

echo "</div>";

// Summary
echo "<h2>📊 Cleanup Summary</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<p><strong>Files deleted:</strong> $deletedCount</p>";
echo "<p><strong>Errors encountered:</strong> $errorCount</p>";

if ($deletedCount > 0) {
    echo "<p class='success'>✅ Cleanup completed successfully!</p>";
    echo "<p>Your project directory is now cleaner and more organized.</p>";
} else {
    echo "<p>No files were deleted. Your project may already be clean.</p>";
}

echo "</div>";

// Show remaining files
echo "<h2>📂 Current Project Structure</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
echo "<p><strong>Important files kept:</strong></p>";
echo "<ul>";

$importantFiles = [
    'index.php',
    'auth/',
    'config/',
    'css/',
    'js/',
    'pages/',
    'api/',
    '.env',
    'README.md',
    'school_management.sql'
];

foreach ($importantFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<li>✓ $file</li>";
    } else {
        echo "<li style='color: #666;'>○ $file (missing)</li>";
    }
}

echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='button'>🏠 Go to Homepage</a>";
echo "<a href='auth/php/admin-dashboard.php' class='button'>🔐 Admin Dashboard</a>";
echo "</div>";

echo "</div></body></html>";

// Self-delete this cleanup script
if (file_exists(__FILE__)) {
    unlink(__FILE__);
}
?>
