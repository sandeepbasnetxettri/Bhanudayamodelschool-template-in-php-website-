<?php
// Simple script to check gallery data in the database
require_once 'config/dbconnection.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Gallery Data Check</h1>";

try {
    // Check if gallery table exists and get count
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'gallery'
    ");
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($tableExists == 0) {
        echo "<p style='color: red;'>Gallery table does not exist!</p>";
        exit;
    }

    // Count total gallery items
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM gallery');
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total gallery items in database: <strong>$totalCount</strong></p>";

    // Count published gallery items
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM gallery WHERE is_published = 1');
    $publishedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Published gallery items: <strong>$publishedCount</strong></p>";

    if ($publishedCount > 0) {
        // Get sample of published items
        $stmt = $pdo->query('SELECT id, title, file_url, category, upload_date, is_published FROM gallery WHERE is_published = 1 ORDER BY upload_date DESC LIMIT 10');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample of published gallery items:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Date</th><th>Published</th><th>Image URL</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['id']) . "</td>";
            echo "<td>" . htmlspecialchars($item['title']) . "</td>";
            echo "<td>" . htmlspecialchars($item['category']) . "</td>";
            echo "<td>" . htmlspecialchars($item['upload_date']) . "</td>";
            echo "<td>" . ($item['is_published'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . (strlen($item['file_url']) > 50 ? substr(htmlspecialchars($item['file_url']), 0, 50) . '...' : htmlspecialchars($item['file_url'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No published gallery items found. Items need to have is_published = 1 to appear on the website.</p>";
        
        // Show unpublished items if any
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM gallery WHERE is_published = 0');
        $unpublishedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($unpublishedCount > 0) {
            echo "<p>Found $unpublishedCount unpublished items in the database.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<br><h2>Test the Gallery API</h2>";
echo "<p><a href='./api/get-gallery-items.php' target='_blank'>Click here to test the gallery API endpoint directly</a></p>";

echo "<br><h2>Visit the Gallery Page</h2>";
echo "<p><a href='./pages/gallery.php' target='_blank'>Click here to visit the gallery page</a></p>";
?>
</file>