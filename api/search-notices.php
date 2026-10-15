<?php
// API endpoint to search notices from the database
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/dbconnection.php';

try {
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : $searchTerm;
    
    // Validate and sanitize search term
    if (empty($searchTerm)) {
        echo json_encode([
            'success' => true,
            'notices' => [],
            'count' => 0,
            'message' => 'No search term provided'
        ]);
        exit;
    }
    
    // Sanitize search term to prevent SQL injection in LIKE clauses
    $searchTerm = htmlspecialchars(strip_tags($searchTerm));
    
    // Limit search term length
    if (strlen($searchTerm) > 100) {
        echo json_encode([
            'success' => false,
            'error' => 'Search term too long'
        ]);
        exit;
    }
    
    $today = date('Y-m-d');
    
    // Split search term into words for better matching
    $searchWords = explode(' ', $searchTerm);
    $searchWords = array_filter($searchWords, function($word) {
        return !empty(trim($word));
    });
    
    if (empty($searchWords)) {
        echo json_encode([
            'success' => true,
            'notices' => [],
            'count' => 0,
            'search_term' => $searchTerm
        ]);
        exit;
    }
    
    // Build dynamic query for multiple word search
    $searchConditions = [];
    $params = [$today, $today]; // For the date conditions
    
    foreach ($searchWords as $word) {
        $cleanWord = trim($word);
        if (!empty($cleanWord)) {
            $searchConditions[] = "(title LIKE ? OR content LIKE ?)";
            $params[] = '%' . $cleanWord . '%';
            $params[] = '%' . $cleanWord . '%';
        }
    }
    
    // Build the query with search functionality - match ALL words in either title or content
    $query = "SELECT id, title, content, notice_type as type, image_url, created_at as date,
              (CASE 
                WHEN title LIKE ? THEN 2 
                WHEN content LIKE ? THEN 1 
                ELSE 0 
              END) as relevance_score
              FROM notices 
              WHERE is_published = 1 
              AND (start_date IS NULL OR start_date <= ?) 
              AND (end_date IS NULL OR end_date >= ?)
              AND (" . implode(' AND ', $searchConditions) . ")
              ORDER BY relevance_score DESC, created_at DESC";
    
    // Add the full search term to params for relevance scoring
    array_unshift($params, '%' . $searchTerm . '%', '%' . $searchTerm . '%');
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
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
            'image' => $notice['image_url'] ?: 'images/news-default.jpg' // Use image from DB or default
        ];
    }, $notices);
    
    echo json_encode([
        'success' => true,
        'notices' => $formattedNotices,
        'count' => count($formattedNotices),
        'search_term' => $searchTerm
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request: ' . $e->getMessage()
    ]);
}
?>