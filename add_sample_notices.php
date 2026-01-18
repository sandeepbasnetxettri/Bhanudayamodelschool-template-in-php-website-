<?php
// Script to add sample notices to the database
require_once 'config/dbconnection.php';

// Sample notices data
$sampleNotices = [
    [
        'title' => 'School Annual Day Celebration',
        'content' => 'We are pleased to announce our annual day celebration will be held on March 15th, 2024. All students, parents, and staff are invited to attend this memorable event filled with performances, awards, and entertainment.',
        'type' => 'event',
        'is_published' => 1
    ],
    [
        'title' => 'New Academic Session Registration',
        'content' => 'Registration for the new academic session 2024-25 is now open. Parents of existing students and new admissions are requested to visit the school office or register online through the portal.',
        'type' => 'academic',
        'is_published' => 1
    ],
    [
        'title' => 'Teacher Training Workshop',
        'content' => 'A professional development workshop for teachers will be conducted from February 20-22. This workshop aims to enhance teaching methodologies and introduce new educational technologies.',
        'type' => 'administrative',
        'is_published' => 1
    ],
    [
        'title' => 'Republic Day Holiday',
        'content' => 'The school will remain closed on Republic Day (February 15th) in observance of the national holiday. Classes will resume on February 16th.',
        'type' => 'holiday',
        'is_published' => 1
    ],
    [
        'title' => 'Science Exhibition Results',
        'content' => 'Congratulations to all participants of the annual science exhibition. The winners are announced and prizes will be distributed during the assembly on Friday.',
        'type' => 'achievement',
        'is_published' => 1
    ]
];

try {
    $insertQuery = "INSERT INTO notices (title, content, notice_type, is_published, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($insertQuery);
    
    $addedCount = 0;
    foreach ($sampleNotices as $notice) {
        $result = $stmt->execute([
            $notice['title'],
            $notice['content'],
            $notice['type'],
            $notice['is_published']
        ]);
        
        if ($result) {
            $addedCount++;
        }
    }
    
    echo "Successfully added $addedCount sample notices to the database.\n";
    
    // Verify the notices were added
    $verifyQuery = "SELECT COUNT(*) as total FROM notices WHERE is_published = 1";
    $verifyResult = $pdo->query($verifyQuery);
    $verifyData = $verifyResult->fetch(PDO::FETCH_ASSOC);
    
    echo "Total published notices in database now: " . $verifyData['total'] . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>