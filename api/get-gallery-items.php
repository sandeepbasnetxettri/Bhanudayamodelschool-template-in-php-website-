<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once '../config/dbconnection.php';

try {
    // Fetch published gallery items from the database
    $stmt = $pdo->query("
        SELECT id, title, description, file_url, thumbnail_url, category, tags, is_published, 
               COALESCE(DATE_FORMAT(upload_date, '%M %d, %Y'), DATE_FORMAT(created_at, '%M %d, %Y')) as date, created_at 
        FROM gallery 
        WHERE is_published = 1 
        ORDER BY upload_date DESC, created_at DESC
    ");
    $galleryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the gallery items to match the expected format
    $formattedItems = [];
    foreach ($galleryItems as $item) {
        $formattedItems[] = [
            'id' => $item['id'],
            'title' => $item['title'],
            'category' => $item['category'] ?: 'events', // default to 'events' if category is empty
            'date' => $item['date'] ?: date('M d, Y', strtotime($item['created_at'])),
            'image' => $item['file_url'] ?: $item['thumbnail_url'], // use file_url or fall back to thumbnail_url
            'description' => $item['description']
        ];
    }

    // If no items in database, try to get local gallery images from the Gallery folder
    if (empty($formattedItems)) {
        $galleryDir = __DIR__ . '/../Gallery';
        if (is_dir($galleryDir)) {
            $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
            
            // Sort files by name to ensure consistent ordering
            usort($imageFiles, function($a, $b) {
                return strnatcasecmp(basename($a), basename($b));
            });
            
            $categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            foreach ($imageFiles as $index => $imageFile) {
                $filename = basename($imageFile);
                $filepath = '../Gallery/' . $filename;
                
                // Extract number from filename if it's numeric
                preg_match('/^([0-9]+)[^0-9]?/', $filename, $matches);
                $number = isset($matches[1]) ? intval($matches[1]) : ($index + 1);
                
                $category = $categories[$number % count($categories)];
                $month = $months[($number + 2) % 12];
                $day = (($number * 3) % 28) + 1;
                
                // Check if file exists and get dimensions
                $imageInfo = getimagesize($imageFile);
                $width = $imageInfo[0] ?? 0;
                $height = $imageInfo[1] ?? 0;
                
                $formattedItems[] = [
                    'id' => $number,
                    'title' => "Gallery Image $number",
                    'category' => $category,
                    'date' => "$month $day, 2024",
                    'image' => $filepath,
                    'description' => "Local gallery image $number",
                    'width' => $width,
                    'height' => $height,
                    'size' => filesize($imageFile),
                    'source' => 'local'
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedItems,
        'total_count' => count($formattedItems)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error occurred',
        'message' => $e->getMessage()
    ]);
}
?>