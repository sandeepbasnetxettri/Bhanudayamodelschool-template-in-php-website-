<?php
/**
 * Check Users Script for Bhanudayamodelschool
 * This script displays all users in the system for verification
 */
session_start();

// Database connection
require_once 'config/dbconnection.php';

// Check if user is admin (security measure)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth/php/login.php');
    exit;
}

// Fetch all users
try {
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.full_name, u.role, u.created_at, 
               up.phone as profile_phone,
               s.student_id,
               t.employee_id
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List - Bhanudaya Secondary School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="auth/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .role-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .role-admin { background-color: #f44336; color: white; }
        .role-student { background-color: #2196F3; color: white; }
        .role-teacher { background-color: #FF9800; color: white; }
        .role-parent { background-color: #9C27B0; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.9rem;
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
                    <a href="auth/php/admin-dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
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
            <h1><i class="fas fa-users"></i> User Management</h1>
            <p>View and manage all user accounts in the system</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="users-container">
            <div style="margin-bottom: 20px;">
                <a href="create_user.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create New User
                </a>
            </div>
            
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Identifier</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">No users found in the system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['profile_phone'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($user['student_id']): ?>
                                    Student ID: <?php echo htmlspecialchars($user['student_id']); ?>
                                <?php elseif ($user['employee_id']): ?>
                                    Emp ID: <?php echo htmlspecialchars($user['employee_id']); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; text-align: center; color: #666;">
                <p>Total users in the system: <strong><?php echo count($users); ?></strong></p>
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