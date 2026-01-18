<?php
/**
 * Simple Foreign Key Constraint Fix
 * Fixes the CASCADE DELETE issue on students table
 */

require_once 'config/dbconnection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix CASCADE DELETE Constraints</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 4px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            font-size: 1.3rem;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .success {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 500;
        }
        .error {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 500;
        }
        .warning {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        .sql-code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        ul {
            line-height: 1.8;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 20px;
            transition: transform 0.3s;
            font-weight: 600;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="icon">🔧</span>Foreign Key CASCADE DELETE Fix</h1>
        
        <?php
        $steps = [];
        $hasError = false;
        
        try {
            // Step 1: Show current constraints
            echo "<div class='step'>";
            echo "<h2><span class='icon'>📊</span>Step 1: Checking Current Constraints</h2>";
            
            $stmt = $pdo->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'students'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY CONSTRAINT_NAME
            ");
            
            $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($constraints) {
                echo "<div class='info'><strong>Found " . count($constraints) . " foreign key constraint(s):</strong><ul>";
                foreach ($constraints as $constraint) {
                    echo "<li><code>{$constraint['CONSTRAINT_NAME']}</code>: ";
                    echo "{$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})</li>";
                }
                echo "</ul></div>";
            } else {
                echo "<div class='warning'>No foreign key constraints found!</div>";
            }
            echo "</div>";
            
            // Step 2: Apply fixes
            echo "<div class='step'>";
            echo "<h2><span class='icon'>🔨</span>Step 2: Applying CASCADE DELETE</h2>";
            
            $pdo->beginTransaction();
            
            $operations = [];
            
            // Drop existing constraints
            $constraintsToDrop = ['students_ibfk_1', 'students_ibfk_2'];
            
            foreach ($constraintsToDrop as $constraint) {
                try {
                    $sql = "ALTER TABLE students DROP FOREIGN KEY $constraint";
                    $pdo->exec($sql);
                    $operations[] = ['success', "✓ Dropped constraint: <code>$constraint</code>"];
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), '1091') !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
                        $operations[] = ['warning', "⚠ Constraint <code>$constraint</code> does not exist (skipped)"];
                    } else {
                        $operations[] = ['error', "✗ Error dropping <code>$constraint</code>: " . $e->getMessage()];
                    }
                }
            }
            
            // Add new constraints with CASCADE
            $newConstraints = [
                [
                    'name' => 'fk_students_user',
                    'sql' => "ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'description' => "user_id → users(id) with CASCADE DELETE"
                ],
                [
                    'name' => 'fk_students_parent',
                    'sql' => "ALTER TABLE students ADD CONSTRAINT fk_students_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
                    'description' => "parent_id → users(id) with SET NULL"
                ]
            ];
            
            foreach ($newConstraints as $constraint) {
                try {
                    $pdo->exec($constraint['sql']);
                    $operations[] = ['success', "✓ Added constraint: <code>{$constraint['name']}</code><br><small>{$constraint['description']}</small>"];
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), '1061') !== false || strpos($e->getMessage(), 'Duplicate key') !== false) {
                        $operations[] = ['warning', "⚠ Constraint <code>{$constraint['name']}</code> already exists"];
                    } else {
                        $operations[] = ['error', "✗ Error adding <code>{$constraint['name']}</code>: " . $e->getMessage()];
                        $hasError = true;
                        throw $e;
                    }
                }
            }
            
            $pdo->commit();
            
            // Display operations
            foreach ($operations as $op) {
                $class = $op[0];
                $message = $op[1];
                echo "<div class='$class'>$message</div>";
            }
            
            if (!$hasError) {
                echo "<div class='success'><strong><span class='icon'>✅</span>All constraints updated successfully!</strong></div>";
            }
            
            echo "</div>";
            
            // Step 3: Verify
            echo "<div class='step'>";
            echo "<h2><span class='icon'>✅</span>Step 3: Verification</h2>";
            
            $stmt = $pdo->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'students'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY CONSTRAINT_NAME
            ");
            
            $finalConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($finalConstraints) {
                echo "<div class='success'><strong>Current foreign key constraints:</strong><ul>";
                foreach ($finalConstraints as $constraint) {
                    echo "<li><code>{$constraint['CONSTRAINT_NAME']}</code>: ";
                    echo "{$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})</li>";
                }
                echo "</ul></div>";
            }
            
            echo "</div>";
            
            // Summary
            echo "<div class='step'>";
            echo "<h2><span class='icon'>📝</span>Summary</h2>";
            echo "<div class='info'>";
            echo "<p><strong>What was fixed:</strong></p>";
            echo "<ul>";
            echo "<li><strong>user_id</strong> foreign key: Now uses <code>ON DELETE CASCADE</code><br>";
            echo "<small>→ When a user is deleted, their student record is automatically deleted</small></li>";
            echo "<li><strong>parent_id</strong> foreign key: Now uses <code>ON DELETE SET NULL</code><br>";
            echo "<small>→ When a parent user is deleted, the student's parent_id is set to NULL</small></li>";
            echo "</ul>";
            echo "<p><strong>✨ Result:</strong> You can now delete users from the <code>admin-access-editor.php</code> without getting foreign key constraint errors!</p>";
            echo "</div>";
            echo "</div>";
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<div class='error'><strong><span class='icon'>❌</span>Transaction rolled back due to error:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "</div>";
            echo "</div>"; // Close step div
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="auth/php/admin-access-editor.php" class="btn">← Back to Admin Access Editor</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>
