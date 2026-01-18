<?php
/**
 * Test Gallery Images
 * This script tests the local gallery images in the Gallery folder
 */
session_start();

// Check if user is logged in (any role)
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/php/login.php');
    exit;
}

// Get all image files from the Gallery folder
$galleryDir = __DIR__ . '/Gallery';
$imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);

// Sort files by name to ensure consistent ordering
usort($imageFiles, function($a, $b) {
    return strnatcasecmp(basename($a), basename($b));
});

$totalImages = count($imageFiles);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Gallery - Bhanudaya Secondary School</title>
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
        
        .gallery-grid-test {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item-test {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            height: 200px;
        }
        
        .gallery-item-test img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .file-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            font-size: 12px;
            text-align: center;
        }
        
        .no-images {
            text-align: center;
            padding: 40px;
            color: #666;
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
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="auth/php/admin-dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
                    <?php endif; ?>
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
            <h1><i class="fas fa-images"></i> Test Gallery Images</h1>
            <p>Testing local gallery images from the Gallery folder</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="gallery-test-container">
            <div class="gallery-stats">
                <h3>Gallery Folder Status</h3>
                <p><i class="fas fa-image"></i> Total images found: <strong><?php echo $totalImages; ?></strong></p>
                <p><i class="fas fa-folder"></i> Location: <code>/Gallery</code></p>
            </div>
            
            <?php if ($totalImages > 0): ?>
                <h2>Gallery Images Preview</h2>
                <div class="gallery-grid-test">
                    <?php foreach ($imageFiles as $index => $imageFile): ?>
                        <div class="gallery-item-test">
                            <img src="Gallery/<?php echo basename($imageFile); ?>" 
                                 alt="Gallery Image <?php echo ($index + 1); ?>"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22250%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2216%22 fill=%22%23999%22%3EImage Missing%3C/text%3E%3C/svg%3E'">
                            <div class="file-name"><?php echo basename($imageFile); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 30px; text-align: center;">
                    <a href="pages/gallery.php" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Main Gallery
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="manage_gallery.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> Manage Gallery
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-images">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f44336; margin-bottom: 15px;"></i>
                    <h3>No Images Found</h3>
                    <p>The Gallery folder does not contain any image files.</p>
                    <p>Please add images to the <code>c:/xampp/htdocs/Bhanudayamodelschool/Gallery</code> folder.</p>
                </div>
            <?php endif; ?>
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
</body>
</html>