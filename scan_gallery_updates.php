<?php
/**
 * Scan Gallery Updates
 * This script scans the Gallery folder for new images and adds them to the database if they're not already there
 */

require_once 'config/dbconnection.php';

echo "<h1>Scanning Gallery Folder for Updates</h1>";

try {
    // Check if gallery table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'gallery'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>✗ Gallery table does not exist. Please create it first.</p>";
        exit;
    }

    // Get all image files from the Gallery folder
    $galleryDir = __DIR__ . '/Gallery';
    if (!is_dir($galleryDir)) {
        echo "<p style='color: red;'>✗ Gallery folder does not exist at: $galleryDir</p>";
        exit;
    }

    $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
    $imageCount = count($imageFiles);

    echo "<p>Found <strong>$imageCount</strong> images in the Gallery folder.</p>";

    if ($imageCount == 0) {
        echo "<p>No images found in the Gallery folder.</p>";
        exit;
    }

    // Sort files by name to ensure consistent ordering
    usort($imageFiles, function($a, $b) {
        return strnatcasecmp(basename($a), basename($b));
    });

    // Get existing file URLs from the database
    $stmt = $pdo->query("SELECT file_url FROM gallery WHERE file_url LIKE '../Gallery/%'");
    $existingUrls = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existingPaths = array_map(function($url) {
        return basename(parse_url($url, PHP_URL_PATH));
    }, $existingUrls);

    // Identify new files that aren't in the database yet
    $newFiles = [];
    foreach ($imageFiles as $imageFile) {
        $filename = basename($imageFile);
        if (!in_array($filename, $existingPaths)) {
            $newFiles[] = $imageFile;
        }
    }

    $newCount = count($newFiles);
    echo "<p>Found <strong>$newCount</strong> new images not yet in the database.</p>";

    if ($newCount > 0) {
        // Prepare statement for inserting gallery items
        $insertStmt = $pdo->prepare(
            "INSERT INTO gallery (title, description, file_url, thumbnail_url, category, is_published, uploaded_by) 
             VALUES (?, ?, ?, ?, ?, 1, 1)"
        );

        $categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
        $addedCount = 0;

        foreach ($newFiles as $index => $imageFile) {
            $filename = basename($imageFile);
            $fileInfo = pathinfo($filename);
            $imageNumber = intval(preg_replace('/[^0-9]/', '', $fileInfo['filename'])) ?: ($index + 1);

            // Generate title based on file name
            $title = !empty($fileInfo['filename']) ? 
                htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $fileInfo['filename']))) : 
                "Gallery Image " . $imageNumber;

            $description = "Added from local gallery folder: " . $filename;

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
                echo "<p style='color: green;'>✓ Added: $filename</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Failed to add $filename: " . $e->getMessage() . "</p>";
            }
        }

        echo "<p style='font-weight: bold; color: blue;'>Successfully added $addedCount new images to the gallery database.</p>";
    } else {
        echo "<p>All images in the Gallery folder are already in the database.</p>";
    }

    // Also check for files in database that might no longer exist in the folder
    $missingFiles = [];
    foreach ($existingPaths as $existingFile) {
        $fullPath = $galleryDir . '/' . $existingFile;
        if (!file_exists($fullPath)) {
            $missingFiles[] = $existingFile;
        }
    }

    if (!empty($missingFiles)) {
        echo "<h3>Potentially Missing Files:</h3>";
        echo "<ul>";
        foreach ($missingFiles as $missingFile) {
            echo "<li style='color: orange;'>⚠ $missingFile - File not found in Gallery folder</li>";
        }
        echo "</ul>";
        echo "<p>These files exist in the database but not in the Gallery folder. Consider updating their status or removing them.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<a href='check_gallery_table.php'>Check Gallery Table</a> | ";
echo "<a href='pages/gallery.php'>View Gallery</a> | ";
echo "<a href='manage_gallery.php'>Manage Gallery</a> | ";
echo "<a href='index.php'>Home</a>";
?>