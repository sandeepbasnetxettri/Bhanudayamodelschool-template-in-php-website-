<?php
/**
 * Gallery Management Interface
 * This script allows administrators to manage gallery items
 */
session_start();

// Database connection
require_once 'config/dbconnection.php';

// Check if user is admin (security measure)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth/php/login.php');
    exit;
}

// Process form submissions
$message = '';
$error = '';

// Handle gallery item addition from local files
if (isset($_POST['add_local_gallery'])) {
    try {
        // Get all image files from the Gallery folder
        $galleryDir = __DIR__ . '/Gallery';
        $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        
        if (empty($imageFiles)) {
            $error = "No image files found in the Gallery folder.";
        } else {
            // Check how many local images are already in the database
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE file_url LIKE '../Gallery/%'");
            $existingLocalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($existingLocalCount > 0) {
                $error = "Local gallery images are already in the database. Cannot add duplicates.";
            } else {
                // Prepare statement for inserting gallery items
                $insertStmt = $pdo->prepare(
                    "INSERT INTO gallery (title, description, file_url, thumbnail_url, category, is_published, uploaded_by) 
                     VALUES (?, ?, ?, ?, ?, 1, ?)"
                );
                
                $categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
                $addedCount = 0;
                
                foreach ($imageFiles as $index => $imageFile) {
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
                            $category,
                            $_SESSION['user_id'] // uploaded by current admin
                        ]);
                        $addedCount++;
                    } catch (PDOException $e) {
                        $error .= "Failed to add $filename: " . $e->getMessage() . "<br>";
                    }
                }
                
                if ($addedCount > 0) {
                    $message = "Successfully added $addedCount images to the gallery database.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "Error processing gallery images: " . $e->getMessage();
    }
}

// Handle scanning for new local gallery images
if (isset($_POST['scan_new_local_gallery'])) {
    try {
        $galleryDir = __DIR__ . '/Gallery';
        $imageFiles = glob($galleryDir . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        
        if (empty($imageFiles)) {
            $error = "No image files found in the Gallery folder.";
        } else {
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

            if (empty($newFiles)) {
                $message = "No new images found. All images in the Gallery folder are already in the database.";
            } else {
                // Prepare statement for inserting gallery items
                $insertStmt = $pdo->prepare(
                    "INSERT INTO gallery (title, description, file_url, thumbnail_url, category, is_published, uploaded_by) 
                     VALUES (?, ?, ?, ?, ?, 1, ?)"
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
                            $category,
                            $_SESSION['user_id'] // uploaded by current admin
                        ]);
                        $addedCount++;
                    } catch (PDOException $e) {
                        $error .= "Failed to add $filename: " . $e->getMessage() . "<br>";
                    }
                }
                
                if ($addedCount > 0) {
                    $message = "Successfully scanned and added $addedCount new images to the gallery database.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "Error scanning for new gallery images: " . $e->getMessage();
    }
}

// Handle gallery item deletion
if (isset($_POST['delete_gallery_item'])) {
    $itemId = (int)$_POST['item_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$itemId]);
        $message = "Gallery item deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting gallery item: " . $e->getMessage();
    }
}

// Fetch all gallery items
try {
    $stmt = $pdo->query("
        SELECT id, title, description, file_url, category, is_published, created_at 
        FROM gallery 
        ORDER BY created_at DESC
    ");
    $galleryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $galleryItems = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Bhanudaya Secondary School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="auth/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-management-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .gallery-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .gallery-table th,
        .gallery-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .gallery-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }
        
        .gallery-table tr:last-child td {
            border-bottom: none;
        }
        
        .gallery-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .preview-image {
            max-width: 80px;
            max-height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-published { background-color: #4CAF50; color: white; }
        .status-draft { background-color: #f44336; color: white; }
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
            <h1><i class="fas fa-images"></i> Gallery Management</h1>
            <p>Manage school gallery images and albums</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="gallery-management-container">
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="add_local_gallery" class="btn btn-primary" 
                            onclick="return confirm('This will add all local images from the Gallery folder to the database. Continue?')">
                        <i class="fas fa-plus"></i> Add Local Gallery Images
                    </button>
                </form>
                
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="scan_new_local_gallery" class="btn btn-info" 
                            onclick="return confirm('This will scan for new images in the Gallery folder that are not yet in the database. Continue?')">
                        <i class="fas fa-search"></i> Scan for New Images
                    </button>
                </form>
                
                <a href="pages/gallery.php" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> View Gallery
                </a>
                
                <a href="scan_gallery_updates.php" class="btn btn-warning">
                    <i class="fas fa-sync"></i> Detailed Scan
                </a>
            </div>
            
            <h2>Gallery Items</h2>
            <table class="gallery-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($galleryItems)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No gallery items found in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($galleryItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                            <td>
                                <?php if ($item['file_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['file_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="preview-image"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2260%22%3E%3Crect fill=%22%23ddd%22 width=%2280%22 height=%2260%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2212%22 fill=%22%23999%22%3EImage%3C/text%3E%3C/svg%3E'">
                                <?php else: ?>
                                    No Preview
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $item['is_published'] ? 'published' : 'draft'; ?>">
                                    <?php echo $item['is_published'] ? 'Published' : 'Draft'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this gallery item?')">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_gallery_item" class="btn btn-danger btn-small">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; text-align: center; color: #666;">
                <p>Total gallery items in the system: <strong><?php echo count($galleryItems); ?></strong></p>
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
</body>
</html>