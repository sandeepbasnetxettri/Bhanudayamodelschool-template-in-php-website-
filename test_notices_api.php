<?php
// Test script to verify the notices API endpoint
echo "<h2>Testing Notices API Endpoint</h2>";

// Make a request to the API endpoint
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/get-notices.php';
echo "<p>Requesting: $url</p>";

$response = file_get_contents($url);

if ($response === FALSE) {
    echo "<p>Error: Could not reach the API endpoint</p>";
} else {
    echo "<h3>Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode the JSON response
    $data = json_decode($response, true);
    
    if ($data && isset($data['success'])) {
        echo "<h3>Parsed Response:</h3>";
        echo "<p>Success: " . ($data['success'] ? 'Yes' : 'No') . "</p>";
        
        if (isset($data['notices'])) {
            echo "<p>Number of notices: " . count($data['notices']) . "</p>";
            
            foreach ($data['notices'] as $index => $notice) {
                echo "<details>";
                echo "<summary>Notice " . ($index + 1) . ": " . htmlspecialchars($notice['title']) . "</summary>";
                echo "<ul>";
                echo "<li>ID: " . htmlspecialchars($notice['id']) . "</li>";
                echo "<li>Type: " . htmlspecialchars($notice['type']) . "</li>";
                echo "<li>Date: " . htmlspecialchars($notice['date']) . "</li>";
                echo "<li>Description: " . htmlspecialchars(substr($notice['description'], 0, 100)) . "...</li>";
                echo "</ul>";
                echo "</details>";
            }
        }
    } else {
        echo "<p>Could not parse response as JSON</p>";
    }
}
?>