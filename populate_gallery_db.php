<?php
/**
 * Populate Gallery Database
 * This script adds the local gallery images to the database if they're not already there
 */

require_once 'config/dbconnection.php';

echo "<h1>Populating Gallery Database</h1>";

try {
    // Check if gallery table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'gallery'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Gallery table does not exist. Creating it...</p>";
        
        $createTableSql = "
        CREATE TABLE gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_url VARCHAR(500) NOT NULL,
            thumbnail_url VARCHAR(500),
            category ENUM('events', 'activities', 'achievements', 'facilities', 'staff', 'students', 'other') NOT NULL,
            event_id INT,
            uploaded_by INT DEFAULT 1,
            upload_date DATE DEFAULT (CURRENT_DATE),
            tags TEXT,
            is_published BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        ";
        
        $pdo->exec($createTableSql);
        echo "<p style='color: green;'>✓ Gallery table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ Gallery table already exists</p>";
    }
    
    // Check how many items are already in the database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE file_url LIKE '../Gallery/%'");
    $existingLocalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($existingLocalCount > 0) {
        echo "<p>Found $existingLocalCount local gallery items already in the database.</p>";
        
        // Ask if they want to add more or update existing
        echo "<p>Local gallery items are already in the database.</p>";
    } else {
        echo "<p>No local gallery items found in the database. Adding from Gallery folder...</p>";
        
        // Get all image files from the Gallery folder
        $galleryDir = __DIR__ . '/Gallery';
        $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        
        if (empty($imageFiles)) {
            echo "<p style='color: orange;'>No image files found in the Gallery folder.</p>";
        } else {
            echo "<p>Found " . count($imageFiles) . " image files in the Gallery folder.</p>";
            
            // Prepare statement for inserting gallery items
            $insertStmt = $pdo->prepare(
                "INSERT INTO gallery (title, description, file_url, thumbnail_url, category, is_published, uploaded_by) 
                 VALUES (?, ?, ?, ?, ?, 1, 1)"
            );
            
            $categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
            $addedCount = 0;
            
            foreach ($imageFiles as $index => $imageFile) {
                $filename = basename($imageFile);
                $fileInfo = pathinfo($filename);
                $imageNumber = intval($fileInfo['filename']);
                
                // Generate title based on file number
                $title = "Gallery Image " . $imageNumber;
                $description = "School gallery image " . $imageNumber;
                
                // Determine category based on image number
                $category = $categories[$imageNumber % count($categories)];
                
                // File URL relative to web root
                $fileUrl = "../Gallery/" . $filename;
                
                try {
                    $insertStmt->execute([
                        $title,
                        $description,
                        $fileUrl,
                        $fileUrl, // using same image as thumbnail for now
                        $category
                    ]);
                    $addedCount++;
                    echo "<p>✓ Added: $filename</p>";
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>✗ Failed to add $filename: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<p style='color: green; font-weight: bold;'>Successfully added $addedCount images to the gallery database.</p>";
        }
    }
    
    // Show total count of published items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE is_published = 1");
    $publishedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total published gallery items in database: <strong>$publishedCount</strong></p>";
    
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<p><a href='pages/gallery.php' target='_blank'>View Gallery Page</a></p>";
    echo "<p><a href='api/get-gallery-items.php' target='_blank'>Test Gallery API</a></p>";
    echo "<p><a href='check_users.php' target='_blank'>Check Users</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Back to Home</a></p>";
?>