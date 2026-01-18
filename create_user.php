<?php
/**
 * Create User Script for Bhanudayamodelschool
 * This script allows secure creation of new users in the system
 */
session_start();

// Database connection
require_once 'config/dbconnection.php';

// Check if user is admin (security measure)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: auth/php/login.php');
    exit;
}

// Process form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $parentName = trim($_POST['parentName'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($role === 'student' && empty($parentName)) {
        $error = "Please enter the parent/guardian name for student accounts.";
    } elseif ($role === 'admin') {
        $error = "Cannot create admin accounts through this interface.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                $error = "Email already registered. Please use a different email.";
            } else {
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user into database
                $fullName = $firstName . ' ' . $lastName;
                $stmt = $pdo->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $fullName, $passwordHash, $role]);
                
                // Get the inserted user ID
                $userId = $pdo->lastInsertId();
                
                // Ensure user_profiles entry exists for all users
                $profileStmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $profileStmt->execute([$userId]);
                
                // Update phone number in user_profiles if provided
                if (!empty($phone)) {
                    $phoneStmt = $pdo->prepare("UPDATE user_profiles SET phone = ? WHERE user_id = ?");
                    $phoneStmt->execute([$phone, $userId]);
                }
                
                // Handle role-specific data
                switch ($role) {
                    case 'student':
                        // Generate a unique student ID
                        $studentId = 'STU' . str_pad($userId, 6, '0', STR_PAD_LEFT);
                        
                        // Find parent ID by name
                        $parentId = null;
                        if (!empty($parentName)) {
                            $parentStmt = $pdo->prepare("SELECT u.id FROM users u WHERE u.full_name = ? AND u.role = 'parent'");
                            $parentStmt->execute([$parentName]);
                            $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
                            if ($parent) {
                                $parentId = $parent['id'];
                            }
                        }
                        
                        // Insert into students table with parent_id
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, full_name, phone, email, parent_guardian_name, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$userId, $studentId, $fullName, $phone, $email, $parentName, $parentId]);
                        break;
                    case 'teacher':
                        // Generate a unique employee ID
                        $employeeId = 'EMP' . str_pad($userId, 6, '0', STR_PAD_LEFT);
                        // Insert into teachers table
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, employee_id, full_name, phone, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$userId, $employeeId, $fullName, $phone, $email]);
                        break;
                    case 'parent':
                        // Parent information is stored in user_profiles and can be updated later
                        break;
                }
                
                $message = "User '$fullName' has been created successfully with role: $role";
                
                // Clear form data after successful creation
                $firstName = $lastName = $email = $phone = $parentName = '';
            }
        } catch (PDOException $e) {
            $error = "User creation failed. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User - Bhanudaya Secondary School</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="auth/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .create-user-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        #parentNameGroup {
            display: none;
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
            <h1><i class="fas fa-user-plus"></i> Create New User</h1>
            <p>Add a new user account to the system</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="create-user-container">
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" class="form-control" 
                                   value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" class="form-control" 
                                   value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                <option value="parent" <?php echo (isset($_POST['role']) && $_POST['role'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" id="parentNameGroup">
                    <label for="parentName">Parent/Guardian Name *</label>
                    <input type="text" id="parentName" name="parentName" class="form-control" 
                           value="<?php echo htmlspecialchars($parentName ?? ''); ?>" 
                           placeholder="Enter parent/guardian full name">
                    <small class="form-hint">Required for student accounts</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </form>
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

    <script>
        // Show/hide parent name field based on role selection
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const parentNameGroup = document.getElementById('parentNameGroup');
            
            roleSelect.addEventListener('change', function() {
                if (this.value === 'student') {
                    parentNameGroup.style.display = 'block';
                } else {
                    parentNameGroup.style.display = 'none';
                }
            });
            
            // Trigger change event on page load to handle pre-selected values
            if (roleSelect.value === 'student') {
                parentNameGroup.style.display = 'block';
            }
        });
    </script>
</body>
</html>