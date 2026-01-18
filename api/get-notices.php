<?php
// API endpoint to fetch published notices from the database
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/dbconnection.php';

try {
    // Fetch only published notices that are currently active
    // Include notices where:
    // 1. is_published = 1 (published)
    // 2. If start_date is set, it should be <= today
    // 3. If end_date is set, it should be >= today (or end_date is null)
    $today = date('Y-m-d');
    
    $query = "SELECT id, title, content, notice_type as type, created_at as date 
              FROM notices 
              WHERE is_published = 1 
              AND (start_date IS NULL OR start_date <= ?) 
              AND (end_date IS NULL OR end_date >= ?)
              ORDER BY created_at DESC";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$today, $today]);
    
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform the data to match what the frontend expects
    $formattedNotices = array_map(function($notice) {
        // Ensure date is in the right format
        $date = $notice['date'];
        if ($date && !strtotime($date)) {  // If date is not in a valid format
            $date = date('Y-m-d H:i:s'); // Use current timestamp as fallback
        }
        
        return [
            'id' => $notice['id'],
            'title' => $notice['title'],
            'description' => $notice['content'],
            'date' => $date,
            'type' => $notice['type'], // Raw type for proper mapping in frontend
            'image' => 'images/news-default.jpg' // Default image for notices
        ];
    }, $notices);
    
    echo json_encode([
        'success' => true,
        'notices' => $formattedNotices
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>