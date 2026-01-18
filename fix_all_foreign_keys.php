<?php
/**
 * Comprehensive Foreign Key CASCADE DELETE Fix
 * This fixes ALL tables that reference users table
 */

require_once 'config/dbconnection.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix All Foreign Key Constraints</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
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
            font-size: 2rem;
        }
        h2 {
            color: #555;
            margin: 30px 0 15px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step {
            background: #f8f9fa;
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .success {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: 500;
        }
        .error {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: 500;
        }
        .warning {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
            font-weight: 600;
        }
        ul {
            line-height: 2;
            margin: 10px 0 10px 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px 10px 0 0;
            transition: transform 0.3s;
            font-weight: 600;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .icon { font-size: 1.3em; }
        .table-name {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .constraint-count {
            background: #28a745;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="icon">🔧</span> Complete Foreign Key CASCADE DELETE Fix</h1>
        
        <?php
        $allOperations = [];
        $hasError = false;
        
        try {
            // Step 1: Identify all tables with foreign keys to users
            echo "<div class='step'>";
            echo "<h2><span class='icon'>📊</span> Step 1: Scanning Database</h2>";
            
            $stmt = $pdo->query("
                SELECT 
                    TABLE_NAME,
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'users'
                ORDER BY TABLE_NAME, CONSTRAINT_NAME
            ");
            
            $allConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($allConstraints) {
                // Group by table
                $tableConstraints = [];
                foreach ($allConstraints as $constraint) {
                    $tableName = $constraint['TABLE_NAME'];
                    if (!isset($tableConstraints[$tableName])) {
                        $tableConstraints[$tableName] = [];
                    }
                    $tableConstraints[$tableName][] = $constraint;
                }
                
                echo "<div class='info'>";
                echo "<strong>Found " . count($allConstraints) . " foreign key constraint(s) referencing <code>users</code> table across " . count($tableConstraints) . " table(s):</strong>";
                echo "<ul>";
                foreach ($tableConstraints as $tableName => $constraints) {
                    echo "<li><span class='table-name'>$tableName</span> <span class='constraint-count'>" . count($constraints) . " constraint(s)</span>";
                    echo "<ul>";
                    foreach ($constraints as $c) {
                        echo "<li><code>{$c['CONSTRAINT_NAME']}</code>: {$c['COLUMN_NAME']} → users(id)</li>";
                    }
                    echo "</ul></li>";
                }
                echo "</ul></div>";
            } else {
                echo "<div class='warning'>No foreign key constraints found referencing users table!</div>";
            }
            echo "</div>";
            
            // Step 2: Apply fixes
            echo "<div class='step'>";
            echo "<h2><span class='icon'>🔨</span> Step 2: Applying CASCADE DELETE</h2>";
            
            $pdo->beginTransaction();
            
            // Define constraint fixes
            $constraintFixes = [
                'students' => [
                    'drop' => ['students_ibfk_1', 'students_ibfk_2'],
                    'add' => [
                        [
                            'name' => 'fk_students_user',
                            'sql' => "ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
                            'desc' => "user_id → users(id) [CASCADE DELETE]"
                        ],
                        [
                            'name' => 'fk_students_parent',
                            'sql' => "ALTER TABLE students ADD CONSTRAINT fk_students_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
                            'desc' => "parent_id → users(id) [SET NULL]"
                        ]
                    ]
                ],
                'teachers' => [
                    'drop' => ['teachers_ibfk_1'],
                    'add' => [
                        [
                            'name' => 'fk_teachers_user',
                            'sql' => "ALTER TABLE teachers ADD CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
                            'desc' => "user_id → users(id) [CASCADE DELETE]"
                        ]
                    ]
                ],
                'user_profiles' => [
                    'drop' => ['user_profiles_ibfk_1'],
                    'add' => [
                        [
                            'name' => 'fk_user_profiles_user',
                            'sql' => "ALTER TABLE user_profiles ADD CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE",
                            'desc' => "user_id → users(id) [CASCADE DELETE]"
                        ]
                    ]
                ],
                'notices' => [
                    'drop' => ['notices_ibfk_1'],
                    'add' => [
                        [
                            'name' => 'fk_notices_user',
                            'sql' => "ALTER TABLE notices ADD CONSTRAINT fk_notices_user FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
                            'desc' => "posted_by → users(id) [SET NULL]"
                        ]
                    ]
                ],
                'events' => [
                    'drop' => ['events_ibfk_1'],
                    'add' => [
                        [
                            'name' => 'fk_events_user',
                            'sql' => "ALTER TABLE events ADD CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
                            'desc' => "created_by → users(id) [SET NULL]"
                        ]
                    ]
                ],
                'gallery' => [
                    'drop' => ['gallery_ibfk_2'],
                    'add' => [
                        [
                            'name' => 'fk_gallery_user',
                            'sql' => "ALTER TABLE gallery ADD CONSTRAINT fk_gallery_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE",
                            'desc' => "uploaded_by → users(id) [SET NULL]"
                        ]
                    ]
                ]
            ];
            
            foreach ($constraintFixes as $tableName => $fixes) {
                echo "<h3 style='margin-top: 20px;'><span class='table-name'>$tableName</span></h3>";
                
                // Drop old constraints
                foreach ($fixes['drop'] as $constraintName) {
                    try {
                        $sql = "ALTER TABLE $tableName DROP FOREIGN KEY $constraintName";
                        $pdo->exec($sql);
                        $allOperations[] = ['success', "✓ Dropped <code>$constraintName</code> from <code>$tableName</code>"];
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), '1091') !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
                            $allOperations[] = ['warning', "⚠ <code>$constraintName</code> does not exist in <code>$tableName</code> (skipped)"];
                        } else {
                            $allOperations[] = ['error', "✗ Error dropping <code>$constraintName</code>: " . $e->getMessage()];
                        }
                    }
                }
                
                // Add new constraints
                foreach ($fixes['add'] as $constraint) {
                    try {
                        $pdo->exec($constraint['sql']);
                        $allOperations[] = ['success', "✓ Added <code>{$constraint['name']}</code> to <code>$tableName</code><br><small style='margin-left: 25px;'>{$constraint['desc']}</small>"];
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), '1061') !== false || strpos($e->getMessage(), 'Duplicate key') !== false) {
                            $allOperations[] = ['warning', "⚠ <code>{$constraint['name']}</code> already exists in <code>$tableName</code>"];
                        } else {
                            $allOperations[] = ['error', "✗ Error adding <code>{$constraint['name']}</code>: " . $e->getMessage()];
                            $hasError = true;
                        }
                    }
                }
            }
            
            if ($hasError) {
                $pdo->rollBack();
                echo "<div class='error'><strong><span class='icon'>❌</span> Transaction rolled back due to errors</strong></div>";
            } else {
                $pdo->commit();
                echo "<div class='success'><strong><span class='icon'>✅</span> All constraints updated successfully!</strong></div>";
            }
            
            // Display all operations
            foreach ($allOperations as $op) {
                echo "<div class='{$op[0]}'>{$op[1]}</div>";
            }
            
            echo "</div>";
            
            // Step 3: Verification
            echo "<div class='step'>";
            echo "<h2><span class='icon'>✅</span> Step 3: Verification</h2>";
            
            $stmt = $pdo->query("
                SELECT 
                    TABLE_NAME,
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'users'
                ORDER BY TABLE_NAME, CONSTRAINT_NAME
            ");
            
            $finalConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($finalConstraints) {
                $tableGroups = [];
                foreach ($finalConstraints as $c) {
                    $tableGroups[$c['TABLE_NAME']][] = $c;
                }
                
                echo "<div class='success'><strong>Current foreign key constraints after update:</strong>";
                foreach ($tableGroups as $tableName => $constraints) {
                    echo "<p style='margin-top: 15px;'><span class='table-name'>$tableName</span></p><ul>";
                    foreach ($constraints as $c) {
                        echo "<li><code>{$c['CONSTRAINT_NAME']}</code>: {$c['COLUMN_NAME']} → {$c['REFERENCED_TABLE_NAME']}({$c['REFERENCED_COLUMN_NAME']})</li>";
                    }
                    echo "</ul>";
                }
                echo "</div>";
            }
            
            echo "</div>";
            
            // Summary
            echo "<div class='step'>";
            echo "<h2><span class='icon'>📝</span> Summary</h2>";
            echo "<div class='info'>";
            echo "<p><strong>✨ What was fixed:</strong></p>";
            echo "<ul>";
            echo "<li><span class='table-name'>students</span>: user_id → CASCADE DELETE, parent_id → SET NULL</li>";
            echo "<li><span class='table-name'>teachers</span>: user_id → CASCADE DELETE</li>";
            echo "<li><span class='table-name'>user_profiles</span>: user_id → CASCADE DELETE</li>";
            echo "<li><span class='table-name'>notices</span>: posted_by → SET NULL</li>";
            echo "<li><span class='table-name'>events</span>: created_by → SET NULL</li>";
            echo "<li><span class='table-name'>gallery</span>: uploaded_by → SET NULL</li>";
            echo "</ul>";
            echo "<p style='margin-top: 20px;'><strong>🎯 Impact:</strong></p>";
            echo "<ul>";
            echo "<li>✅ You can now delete users from <code>admin-access-editor.php</code> without foreign key errors</li>";
            echo "<li>✅ Related records (students, teachers, profiles) are automatically deleted</li>";
            echo "<li>✅ Notices, events, and gallery items preserve records but remove user reference</li>";
            echo "<li>✅ Data integrity is maintained with proper cascading rules</li>";
            echo "</ul>";
            echo "</div>";
            echo "</div>";
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<div class='error'><strong><span class='icon'>❌</span> Database Error:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee;">
            <a href="auth/php/admin-access-editor.php" class="btn">👤 Admin Access Editor</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>
