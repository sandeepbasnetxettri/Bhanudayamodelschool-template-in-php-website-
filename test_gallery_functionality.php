<?php
/**
 * Test Gallery Functionality
 * This script tests the gallery system with various local image formats and sizes
 */

session_start();

// Check if user is logged in (any role)
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/php/login.php');
    exit;
}

// Database connection
require_once 'config/dbconnection.php';

// Get all image files from the Gallery folder
$galleryDir = __DIR__ . '/Gallery';
$imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);

// Sort files by name to ensure consistent ordering
usort($imageFiles, function($a, $b) {
    return strnatcasecmp(basename($a), basename($b));
});

$totalImages = count($imageFiles);

// Test the gallery API
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/get-gallery-items.php';
$apiResponse = null;
$apiSuccess = false;

// Attempt to fetch from API
if (ini_get('allow_url_fopen')) {
    $apiResponse = @file_get_contents($apiUrl);
    if ($apiResponse !== false) {
        $decodedResponse = json_decode($apiResponse, true);
        $apiSuccess = ($decodedResponse && isset($decodedResponse['success']) && $decodedResponse['success'] === true);
    }
} else {
    // Alternative using curl if allow_url_fopen is disabled
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $apiResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($apiResponse !== false && $httpCode === 200) {
            $decodedResponse = json_decode($apiResponse, true);
            $apiSuccess = ($decodedResponse && isset($decodedResponse['success']) && $decodedResponse['success'] === true);
        }
    }
}

// Check database connection and gallery table
$dbConnectionOk = true;
$tableExists = false;
$itemCount = 0;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'gallery'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery");
        $itemCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
} catch (Exception $e) {
    $dbConnectionOk = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Gallery Functionality - Bhanudaya Secondary School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-test-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .gallery-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        
        .test-result {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .test-pass {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .test-fail {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .test-warn {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .image-preview {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            text-align: center;
        }
        
        .image-preview img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .image-info {
            padding: 10px;
            font-size: 0.9em;
            background: #f9f9f9;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .btn-group a {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <div class="top-info">
                    <span><i class="fas fa-phone"></i> +977-1-4567890</span>
                    <span><i class="fas fa-envelope"></i> bhanudayahss071@gmail.com</span>
                </div>
                <div class="top-links">
                    <a href="auth/php/admin-dashboard.php"><i class="fas fa-user-shield"></i> Admin Dashboard</a>
                    <a href="auth/php/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
        
        <div class="main-header">
            <div class="container">
                <div class="header-content">
                    <div class="logo-section">
                        <img src="images/school im.png" alt="School Logo" class="logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%234CAF50%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22white%22%3ES%3C/text%3E%3C/svg%3E'">
                        <div class="school-info">
                            <h1>Bhanudaya Secondary School</h1>
                            <p class="tagline">Creating Tomorrow's Leaders Today</p>
                        </div>
                    </div>
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="admin-header">
        <div class="container">
            <h1><i class="fas fa-vial"></i> Test Gallery Functionality</h1>
            <p>Testing local gallery system with various image formats and sizes</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="gallery-test-container">
            <div class="gallery-stats">
                <h3>Gallery System Status</h3>
                <p><i class="fas fa-image"></i> Total images in Gallery folder: <strong><?php echo $totalImages; ?></strong></p>
                <p><i class="fas fa-database"></i> Gallery items in database: <strong><?php echo $itemCount; ?></strong></p>
                <p><i class="fas fa-plug"></i> Database connection: <strong><?php echo $dbConnectionOk ? 'OK' : 'FAILED'; ?></strong></p>
                <p><i class="fas fa-table"></i> Gallery table exists: <strong><?php echo $tableExists ? 'YES' : 'NO'; ?></strong></p>
            </div>
            
            <h2>System Tests</h2>
            
            <div class="test-result <?php echo $dbConnectionOk ? 'test-pass' : 'test-fail'; ?>">
                <i class="fas <?php echo $dbConnectionOk ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <strong>Database Connection:</strong> 
                <?php echo $dbConnectionOk ? 'Connected successfully' : 'Failed to connect to database'; ?>
            </div>
            
            <div class="test-result <?php echo $tableExists ? 'test-pass' : 'test-fail'; ?>">
                <i class="fas <?php echo $tableExists ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <strong>Gallery Table:</strong> 
                <?php echo $tableExists ? 'Gallery table exists in database' : 'Gallery table does not exist'; ?>
            </div>
            
            <div class="test-result <?php echo $apiSuccess ? 'test-pass' : 'test-fail'; ?>">
                <i class="fas <?php echo $apiSuccess ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <strong>API Endpoint:</strong> 
                <?php if ($apiSuccess && $apiResponse): ?>
                    API is working, returned <?php echo count(json_decode($apiResponse, true)['data']); ?> items
                <?php elseif ($apiResponse === false): ?>
                    Could not access API endpoint
                <?php else: ?>
                    API returned error: <?php echo htmlspecialchars(json_decode($apiResponse, true)['error'] ?? 'Unknown error'); ?>
                <?php endif; ?>
            </div>
            
            <div class="test-result <?php echo $totalImages > 0 ? 'test-pass' : 'test-warn'; ?>">
                <i class="fas <?php echo $totalImages > 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <strong>Local Images:</strong> 
                <?php echo $totalImages > 0 ? "Found $totalImages images in Gallery folder" : 'No images found in Gallery folder'; ?>
            </div>
            
            <h2>Image Format Testing</h2>
            <?php if ($totalImages > 0): ?>
                <div class="image-preview-grid">
                    <?php foreach ($imageFiles as $index => $imageFile): 
                        $filename = basename($imageFile);
                        $fileInfo = pathinfo($filename);
                        $fileSize = filesize($imageFile);
                        $fileSizeFormatted = round($fileSize / 1024, 2) . ' KB';
                        
                        // Get image dimensions
                        $imageInfo = getimagesize($imageFile);
                        $width = $imageInfo[0] ?? 0;
                        $height = $imageInfo[1] ?? 0;
                        
                        // Determine if image is accessible via web
                        $webPath = './Gallery/' . $filename;
                    ?>
                        <div class="image-preview">
                            <img src="<?php echo $webPath; ?>" alt="<?php echo htmlspecialchars($filename); ?>" 
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect fill=%22%23ffcccc%22 width=%22200%22 height=%22150%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2214%22 fill=%22%23666%22%3E<?php echo urlencode(substr($filename, 0, 15)); ?>%3C/text%3E%3C/svg%3E'">
                            <div class="image-info">
                                <div><strong><?php echo htmlspecialchars($fileInfo['filename']); ?></strong></div>
                                <div><?php echo strtoupper($fileInfo['extension']); ?> | <?php echo $width; ?>x<?php echo $height; ?></div>
                                <div><?php echo $fileSizeFormatted; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <h3>Format Distribution</h3>
                <?php 
                $formatCounts = [];
                foreach ($imageFiles as $imageFile) {
                    $extension = strtolower(pathinfo($imageFile, PATHINFO_EXTENSION));
                    $formatCounts[$extension] = ($formatCounts[$extension] ?? 0) + 1;
                }
                ?>
                <div class="test-result test-pass">
                    <i class="fas fa-chart-pie"></i>
                    <strong>Supported Formats:</strong> 
                    <?php foreach ($formatCounts as $format => $count): ?>
                        <?php echo strtoupper($format); ?> (<?php echo $count; ?>) 
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="test-result test-warn">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>No images found:</strong> The Gallery folder is empty or inaccessible
                </div>
            <?php endif; ?>
            
            <h2>Gallery System Tools</h2>
            <div class="btn-group">
                <a href="pages/gallery.php" class="btn btn-primary"><i class="fas fa-eye"></i> View Gallery</a>
                <a href="api/get-gallery-items.php" class="btn btn-info"><i class="fas fa-code"></i> Test API</a>
                <a href="manage_gallery.php" class="btn btn-secondary"><i class="fas fa-cog"></i> Manage Gallery</a>
                <a href="scan_gallery_updates.php" class="btn btn-warning"><i class="fas fa-sync"></i> Scan for Updates</a>
                <a href="test_gallery.php" class="btn"><i class="fas fa-images"></i> Test Local Images</a>
                <a href="check_gallery_table.php" class="btn"><i class="fas fa-database"></i> Check DB</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>About Us</h3>
                    <p>Bhanudaya Secondary School is committed to providing quality education and nurturing young minds for a successful future.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> Binayi Triveni Rural Municipality, Dumkibas, Nawalaparasi, Nepal</li>
                        <li><i class="fas fa-phone"></i> +977-1-4567890</li>
                        <li><i class="fas fa-envelope"></i> bhanudayahss071@gmail.com</li>
                        <li><i class="fas fa-clock"></i> Sun-Fri: 9:00 AM - 4:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Bhanudaya Secondary School. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
</body>
</html>