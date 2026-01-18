<?php
/**
 * Check Gallery Table
 * This script checks if the gallery table exists and displays current status
 */

require_once 'config/dbconnection.php';

echo "<h1>Gallery Table Status Check</h1>";

try {
    // Check if gallery table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'gallery'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>✗ Gallery table does not exist.</p>";
        echo "<p>You need to run the database setup first.</p>";
        
        // Offer to create the table
        echo "<form method='post'>";
        echo "<button type='submit' name='create_table' class='btn'>Create Gallery Table</button>";
        echo "</form>";
        
        if (isset($_POST['create_table'])) {
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
        }
    } else {
        echo "<p style='color: green;'>✓ Gallery table exists.</p>";
        
        // Count total items in the table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery");
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE is_published = 1");
        $publishedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Total gallery items in database: <strong>$totalCount</strong></p>";
        echo "<p>Published gallery items: <strong>$publishedCount</strong></p>";
        
        // Show sample of items
        $stmt = $pdo->query("SELECT id, title, file_url, category, is_published FROM gallery LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($items) > 0) {
            echo "<h3>Sample Gallery Items:</h3>";
            echo "<ul>";
            foreach ($items as $item) {
                echo "<li>ID: {$item['id']} - {$item['title']} ({$item['category']}) - Published: " . ($item['is_published'] ? 'Yes' : 'No') . "</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Local Gallery Folder:</h2>";
    
    // Check local gallery folder
    $galleryDir = __DIR__ . '/Gallery';
    if (is_dir($galleryDir)) {
        $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        $imageCount = count($imageFiles);
        
        echo "<p>Images in local Gallery folder: <strong>$imageCount</strong></p>";
        
        if ($imageCount > 0) {
            echo "<p>Sample files: ";
            $sampleFiles = array_slice($imageFiles, 0, 5);
            foreach ($sampleFiles as $file) {
                echo basename($file) . ", ";
            }
            if ($imageCount > 5) echo "...";
            echo "</p>";
            
            // Check if local images are already in the database
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE file_url LIKE '../Gallery/%'");
            $localInDb = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>Local gallery images already in database: <strong>$localInDb</strong></p>";
            
            if ($localInDb == 0 && $imageCount > 0) {
                echo "<form method='post'>";
                echo "<button type='submit' name='add_local_images' class='btn'>Add Local Gallery Images to Database</button>";
                echo "</form>";
                
                if (isset($_POST['add_local_images'])) {
                    // Add local images to database
                    $categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
                    
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO gallery (title, description, file_url, thumbnail_url, category, is_published, uploaded_by) 
                         VALUES (?, ?, ?, ?, ?, 1, 1)"
                    );
                    
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
                        } catch (PDOException $e) {
                            echo "<p style='color: red;'>✗ Failed to add $filename: " . $e->getMessage() . "</p>";
                        }
                    }
                    
                    echo "<p style='color: green; font-weight: bold;'>Successfully added $addedCount images to the gallery database.</p>";
                }
            }
        } else {
            echo "<p style='color: orange;'>No images found in the Gallery folder.</p>";
        }
    } else {
        echo "<p style='color: red;'>Gallery folder does not exist.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='test_gallery.php'>Test Gallery Images</a> | ";
echo "<a href='pages/gallery.php'>View Gallery</a> | ";
echo "<a href='manage_gallery.php'>Manage Gallery</a> | ";
echo "<a href='index.php'>Home</a>";
?>