<?php
/**
 * Foreign Key Constraint Fix Script
 * This script updates the students table foreign keys to use CASCADE DELETE
 * to prevent foreign key constraint violations when deleting users
 */

require_once 'config/dbconnection.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Foreign Key Constraints</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #4CAF50;
        }
        .constraint-list {
            margin: 10px 0;
        }
        .constraint-item {
            padding: 8px;
            margin: 5px 0;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Foreign Key Constraint Fix</h1>
        
        <?php
        echo "<h2>📊 Step 1: Checking Current Foreign Key Constraints</h2>";
        
        try {
            // Check current foreign keys on students table
            $stmt = $pdo->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME,
                    DELETE_RULE,
                    UPDATE_RULE
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'students'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY CONSTRAINT_NAME
            ");
            
            $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($constraints) {
                echo "<div class='info'>Found " . count($constraints) . " foreign key constraint(s) on students table:</div>";
                echo "<div class='constraint-list'>";
                foreach ($constraints as $constraint) {
                    echo "<div class='constraint-item'>";
                    echo "<strong>{$constraint['CONSTRAINT_NAME']}</strong>: ";
                    echo "{$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})";
                    echo "</div>";
                }
                echo "</div>";
                
                // Check delete rules
                $stmt = $pdo->query("
                    SELECT 
                        CONSTRAINT_NAME,
                        DELETE_RULE,
                        UPDATE_RULE
                    FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'students'
                ");
                
                $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Current Delete Rules:</h3>";
                echo "<div class='constraint-list'>";
                foreach ($rules as $rule) {
                    $needsFix = ($rule['DELETE_RULE'] !== 'CASCADE');
                    $class = $needsFix ? 'warning' : 'success';
                    echo "<div class='constraint-item' style='border-left-color: " . ($needsFix ? '#ffc107' : '#28a745') . "'>";
                    echo "<strong>{$rule['CONSTRAINT_NAME']}</strong>: ";
                    echo "DELETE = {$rule['DELETE_RULE']}, UPDATE = {$rule['UPDATE_RULE']}";
                    if ($needsFix) {
                        echo " ⚠️ <em>(needs fix)</em>";
                    } else {
                        echo " ✅ <em>(already correct)</em>";
                    }
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<div class='warning'>No foreign key constraints found on students table!</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>Error checking constraints: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Now apply the fixes
        echo "<h2>🔨 Step 2: Applying CASCADE DELETE Fixes</h2>";
        
        try {
            $pdo->beginTransaction();
            
            $updates = [];
            
            // Drop existing constraints
            $dropConstraints = [
                "students_ibfk_1" => "ALTER TABLE students DROP FOREIGN KEY students_ibfk_1",
                "students_ibfk_2" => "ALTER TABLE students DROP FOREIGN KEY students_ibfk_2"
            ];
            
            foreach ($dropConstraints as $name => $sql) {
                try {
                    $pdo->exec($sql);
                    $updates[] = ['type' => 'success', 'message' => "Dropped constraint: $name"];
                } catch (PDOException $e) {
                    // Constraint might not exist, that's okay
                    $updates[] = ['type' => 'warning', 'message' => "Could not drop $name: " . $e->getMessage()];
                }
            }
            
            // Add new constraints with CASCADE DELETE
            $addConstraints = [
                "fk_students_user" => "ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
                "fk_students_parent" => "ALTER TABLE students ADD CONSTRAINT fk_students_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE"
            ];
            
            foreach ($addConstraints as $name => $sql) {
                try {
                    $pdo->exec($sql);
                    $updates[] = ['type' => 'success', 'message' => "Added constraint: $name"];
                } catch (PDOException $e) {
                    $updates[] = ['type' => 'error', 'message' => "Error adding $name: " . $e->getMessage()];
                    throw $e; // Re-throw to rollback
                }
            }
            
            $pdo->commit();
            
            echo "<div class='success'><strong>✅ All constraints updated successfully!</strong></div>";
            
            foreach ($updates as $update) {
                echo "<div class='{$update['type']}'>{$update['message']}</div>";
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div class='error'><strong>❌ Transaction rolled back due to error:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
            
            foreach ($updates as $update) {
                echo "<div class='{$update['type']}'>{$update['message']}</div>";
            }
        }
        
        // Verify the changes
        echo "<h2>✅ Step 3: Verification</h2>";
        
        try {
            $stmt = $pdo->query("
                SELECT 
                    rc.CONSTRAINT_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE,
                    rc.UPDATE_RULE
                FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                JOIN information_schema.KEY_COLUMN_USAGE kcu 
                    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = 'students'
                ORDER BY rc.CONSTRAINT_NAME
            ");
            
            $finalConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($finalConstraints) {
                echo "<div class='success'>Current foreign key constraints after update:</div>";
                echo "<div class='constraint-list'>";
                foreach ($finalConstraints as $constraint) {
                    echo "<div class='constraint-item' style='border-left-color: #28a745'>";
                    echo "<strong>{$constraint['CONSTRAINT_NAME']}</strong>: ";
                    echo "{$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})<br>";
                    echo "<em>ON DELETE {$constraint['DELETE_RULE']} | ON UPDATE {$constraint['UPDATE_RULE']}</em>";
                    echo "</div>";
                }
                echo "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>Error verifying constraints: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        echo "<h2>📝 Summary</h2>";
        echo "<div class='info'>";
        echo "<p><strong>What was fixed:</strong></p>";
        echo "<ul>";
        echo "<li>✅ <strong>user_id</strong> foreign key: Now uses <code>ON DELETE CASCADE</code> - When a user is deleted, their student record is automatically deleted</li>";
        echo "<li>✅ <strong>parent_id</strong> foreign key: Now uses <code>ON DELETE SET NULL</code> - When a parent user is deleted, the student's parent_id is set to NULL (student record is preserved)</li>";
        echo "</ul>";
        echo "<p><strong>Impact:</strong></p>";
        echo "<ul>";
        echo "<li>You can now delete users without getting foreign key constraint errors</li>";
        echo "<li>Related student records will be handled automatically</li>";
        echo "<li>Data integrity is maintained with proper cascading rules</li>";
        echo "</ul>";
        echo "</div>";
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">
            <h3 style="margin-top: 0;">ℹ️ About CASCADE DELETE</h3>
            <p><strong>ON DELETE CASCADE:</strong> When the parent record (user) is deleted, all related child records (students) are automatically deleted.</p>
            <p><strong>ON DELETE SET NULL:</strong> When the parent record is deleted, the foreign key in the child record is set to NULL (if the column allows NULL values).</p>
            <p>This prevents the foreign key constraint violation error you were experiencing.</p>
        </div>
        
        <a href="index.php" class="button">← Back to Home</a>
    </div>
</body>
</html>
