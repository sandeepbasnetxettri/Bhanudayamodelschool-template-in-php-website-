<?php
session_start();
include_once '../../config/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'parent') {
    header('Location: parent-login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch parent data from both users and user_profiles tables
try {
    $stmt = $pdo->prepare("SELECT u.*, up.phone, up.date_of_birth, up.gender, up.address, up.occupation, up.relationship FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If columns don't exist yet, fall back to basic query
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add default empty values for missing profile fields
        $parent['phone'] = '';
        $parent['date_of_birth'] = '';
        $parent['gender'] = '';
        $parent['address'] = '';
        $parent['occupation'] = '';
        $parent['relationship'] = '';
        
        // Show a message indicating the database needs to be updated
        echo "<div class='alert alert-warning' style='margin: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;'>";
        echo "<strong>Database Update Required:</strong> Please run the database update script to enable full profile functionality. ";
        echo "<a href='../../run_manual_update.php' style='color: #007bff; text-decoration: underline;'>Click here to update</a>";
        echo "</div>";
    } else {
        // Re-throw the exception if it's a different error
        throw $e;
    }
}

// Split full_name into first and last name for display purposes
$name_parts = explode(' ', $parent['full_name'], 2);
$parent['first_name'] = $name_parts[0];
$parent['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';


// Fetch children data
try {
    // First try to fetch using parent_id column
    $stmt = $pdo->prepare("SELECT * FROM students WHERE parent_id = ?");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If parent_id column doesn't exist, fetch all students (temporary workaround)
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM students");
        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Show a message indicating the database needs to be updated
        echo "<div class='alert alert-warning' style='margin: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;'>";
        echo "<strong>Database Update Required:</strong> Please run the database update script to properly link parents with their children. ";
        echo "<a href='../../run_manual_update.php' style='color: #007bff; text-decoration: underline;'>Click here to update</a>";
        echo "</div>";
    } else {
        // Re-throw the exception if it's a different error
        throw $e;
    }
}

// Function to calculate average grade for a student
function getAverageGrade($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare("SELECT AVG(percentage) as avg_percentage FROM results WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avg_percentage'] ? round($result['avg_percentage'], 2) : 0;
    } catch (PDOException $e) {
        return 0; // Return 0 if results table doesn't exist yet
    }
}

// Function to calculate attendance rate for a student
function getAttendanceRate($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_days FROM attendance WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $totalDays = $stmt->fetch(PDO::FETCH_ASSOC)['total_days'];
        
        if ($totalDays == 0) return 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as present_days FROM attendance WHERE student_id = ? AND status = 'present'");
        $stmt->execute([$studentId]);
        $presentDays = $stmt->fetch(PDO::FETCH_ASSOC)['present_days'];
        
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    } catch (PDOException $e) {
        return 0; // Return 0 if attendance table doesn't exist yet
    }
}

// Function to get student's rank in class
function getStudentRank($pdo, $studentId, $gradeLevel) {
    try {
        // Get all students in the same grade with their average scores
        $stmt = $pdo->prepare(
            "SELECT s.id, AVG(r.percentage) as avg_grade 
            FROM students s 
            LEFT JOIN results r ON s.id = r.student_id 
            WHERE s.grade_level = ? 
            GROUP BY s.id 
            ORDER BY avg_grade DESC"
        );
        $stmt->execute([$gradeLevel]);
        $studentsGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find the rank of the current student
        $rank = 0;
        $totalInGrade = count($studentsGrades);
        foreach ($studentsGrades as $index => $studentData) {
            if ($studentData['id'] == $studentId) {
                $rank = $index + 1;
                break;
            }
        }
        
        return [$rank, $totalInGrade];
    } catch (PDOException $e) {
        return [0, 0]; // Return 0 if results table doesn't exist yet
    }
}

// Function to get subject-wise performance for a student
function getSubjectPerformance($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare(
            "SELECT r.exam_name, r.marks_obtained, r.total_marks, r.grade, r.percentage, 
            s.subject_name, t.full_name as teacher_name 
            FROM results r 
            LEFT JOIN subjects s ON r.subject_id = s.id 
            LEFT JOIN teachers t ON s.id % 3 + 1 = t.id -- Simplified teacher assignment for demo
            WHERE r.student_id = ? 
            ORDER BY r.exam_date DESC, s.subject_name"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If results table doesn't exist, return empty array
        return [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Profile - Excellence School</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
                    <a href="parent-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
        
        <div class="main-header">
            <div class="container">
                <div class="header-content">
                    <div class="logo-section">
                        <img src="../../images/school-logo.png" alt="School Logo" class="logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%234CAF50%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22white%22%3ES%3C/text%3E%3C/svg%3E'">
                        <div class="school-info">
                            <h1>Bhanudaya Secondary School</h1>
                            <p class="tagline"></p>
                        </div>
                    </div>
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        
    </header>

    <section class="profile-header">
        <div class="container">
            <h1><i class="fas fa-user-circle"></i> Parent Profile</h1>
        </div>
    </section>

    <section class="profile-content">
        <div class="container">
            <div class="profile-grid">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="avatar-container">
                            <img src="<?php echo !empty($parent['avatar']) ? '../../uploads/avatars/' . htmlspecialchars($parent['avatar']) : '../../images/default-avatar.png'; ?>" alt="Profile Picture" class="avatar" id="profileAvatar">
                            <button class="change-avatar-btn" id="changeAvatarBtn">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <h3 id="parentName"><?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></h3>
                        <p id="parentRole">Parent</p>
                        <div class="profile-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo count($children); ?></span>
                                <span class="stat-label">Children</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">0</span>
                                <span class="stat-label">Pending Fees</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-nav">
                        <ul>
                            <li class="active"><a href="#overview"><i class="fas fa-tachometer-alt"></i> Overview</a></li>
                            <li><a href="#personal-info"><i class="fas fa-user"></i> Personal Info</a></li>
                            <li><a href="#academic-details"><i class="fas fa-graduation-cap"></i> Academic Details</a></li>
                            <li><a href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="profile-main">
                    <!-- Overview Section -->
                    <div class="profile-section active" id="overview">
                        <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
                        <div class="dashboard-widgets">
                            <div class="widget">
                                <div class="widget-header">
                                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Events</h3>
                                </div>
                                <div class="widget-content">
                                    <ul class="events-list">
                                        <li>
                                            <span class="event-date">Dec 20</span>
                                            <span class="event-title">Final Exams</span>
                                        </li>
                                        <li>
                                            <span class="event-date">Dec 25</span>
                                            <span class="event-title">Christmas Holiday</span>
                                        </li>
                                        <li>
                                            <span class="event-date">Jan 01</span>
                                            <span class="event-title">New Year Celebration</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="widget">
                                <div class="widget-header">
                                    <h3><i class="fas fa-bell"></i> Recent Notices</h3>
                                </div>
                                <div class="widget-content">
                                    <ul class="notices-list">
                                        <li>
                                            <span class="notice-title">Annual Sports Day</span>
                                            <span class="notice-date">Posted 2 days ago</span>
                                        </li>
                                        <li>
                                            <span class="notice-title">Library Timing Changes</span>
                                            <span class="notice-date">Posted 1 week ago</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information Section -->
                    <div class="profile-section" id="personal-info">
                        <h2><i class="fas fa-user-edit"></i> Personal Information</h2>
                        
                        <!-- Avatar Upload Form -->
                        <div class="avatar-upload-section">
                            <h3><i class="fas fa-image"></i> Profile Picture</h3>
                            <div class="avatar-preview">
                                <img src="<?php echo !empty($parent['avatar']) ? '../../uploads/avatars/' . htmlspecialchars($parent['avatar']) : '../../images/default-avatar.png'; ?>" alt="Profile Picture" class="avatar-preview-img">
                                <button class="btn btn-primary" onclick="document.getElementById('avatarUploadModal').style.display='block'">
                                    <i class="fas fa-camera"></i> Change Avatar
                                </button>
                            </div>
                        </div>
                        
                        <form id="personalInfoForm" method="POST" action="update-parent-profile.php">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($parent['first_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($parent['last_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($parent['email']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($parent['phone']); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="dateOfBirth">Date of Birth</label>
                                    <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($parent['date_of_birth']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender">
                                        <option value="male" <?php echo ($parent['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($parent['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($parent['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($parent['address']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($parent['occupation']); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="childrenCount">Number of Children</label>
                                    <input type="number" id="childrenCount" name="childrenCount" value="<?php echo count($children); ?>" min="1" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="relationship">Relationship</label>
                                    <select id="relationship" name="relationship">
                                        <option value="father" <?php echo ($parent['relationship'] === 'father') ? 'selected' : ''; ?>>Father</option>
                                        <option value="mother" <?php echo ($parent['relationship'] === 'mother') ? 'selected' : ''; ?>>Mother</option>
                                        <option value="guardian" <?php echo ($parent['relationship'] === 'guardian') ? 'selected' : ''; ?>>Guardian</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                    
                    <!-- Academic Details Section -->
                    <div class="profile-section" id="academic-details">
                        <h2><i class="fas fa-graduation-cap"></i> Children's Academic Details</h2>
                        
                        <div class="children-accordion">
                            <?php foreach ($children as $index => $child): ?>
                            <?php 
                                // Get actual academic data for this child
                                $averageGrade = getAverageGrade($pdo, $child['id']);
                                $attendanceRate = getAttendanceRate($pdo, $child['id']);
                                list($rank, $totalInGrade) = getStudentRank($pdo, $child['id'], $child['grade_level']);
                                $subjectPerformance = getSubjectPerformance($pdo, $child['id']);
                                
                                // Calculate additional metrics for enhanced display
                                $totalSubjects = count($subjectPerformance);
                                $passedSubjects = 0;
                                foreach ($subjectPerformance as $perf) {
                                    if (isset($perf['grade']) && in_array(strtoupper($perf['grade']), ['A', 'B', 'C'])) {
                                        $passedSubjects++;
                                    }
                                }
                                $passRate = $totalSubjects > 0 ? round(($passedSubjects / $totalSubjects) * 100, 1) : 0;
                            ?>
                            <div class="child-section" style="perspective: 1500px; transform-style: preserve-3d;">
                                <div class="child-header" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 15px 20px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; margin-bottom: 15px; transition: all 0.4s ease; transform-style: preserve-3d; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                    <div style="display: flex; align-items: center; gap: 15px; transform: translateZ(10px);">
                                        <div style="font-size: 28px; color: #4CAF50; transform: translateZ(15px);">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div>
                                            <h3 style="margin: 0; color: #333; font-size: 1.4em; text-shadow: 0 1px 2px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($child['full_name']); ?></h3>
                                            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.95em;">Grade <?php echo htmlspecialchars($child['grade_level']); ?> | Roll No: <?php echo htmlspecialchars($child['roll_number'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <button class="expand-btn" style="background: #4CAF50; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; transform: translateZ(20px); box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                                        <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                                    </button>
                                </div>
                                <div class="child-content" style="padding: 0 20px 20px 20px; display: none; animation: contentReveal 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;">
                                    <!-- Academic Summary Cards -->
                                    <div class="academic-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px; perspective: 1200px; transform-style: preserve-3d;">
                                        <div class="summary-card" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border-radius: 15px; padding: 20px; text-align: center; color: white; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3); transform: translateZ(0) rotateX(0deg) rotateY(0deg); transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; perspective: 1200px; transform-style: preserve-3d; overflow: hidden; animation: summaryCardEntrance 0.5s ease-out; transform-origin: center;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: rgba(255,255,255,0.3);"></div>
                                            <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; transform: translateZ(10px);">Overall Average</h4>
                                            <p class="grade" style="margin: 0; font-size: 28px; font-weight: bold; transform: translateZ(15px); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);"><?php echo $averageGrade; ?>%</p>
                                            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;">Excellent</div>
                                        </div>
                                        
                                        <div class="summary-card" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); border-radius: 15px; padding: 20px; text-align: center; color: white; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3); transform: translateZ(0) rotateX(0deg) rotateY(0deg); transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; perspective: 1200px; transform-style: preserve-3d; overflow: hidden; animation: summaryCardEntrance 0.5s ease-out; animation-delay: 0.1s; transform-origin: center;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: rgba(255,255,255,0.3);"></div>
                                            <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; transform: translateZ(10px);">Attendance</h4>
                                            <p class="attendance" style="margin: 0; font-size: 28px; font-weight: bold; transform: translateZ(15px); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);"><?php echo $attendanceRate; ?>%</p>
                                            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;"><?php echo $attendanceRate >= 75 ? 'Good' : ($attendanceRate >= 60 ? 'Avg' : 'Low'); ?></div>
                                        </div>
                                        
                                        <div class="summary-card" style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); border-radius: 15px; padding: 20px; text-align: center; color: white; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3); transform: translateZ(0) rotateX(0deg) rotateY(0deg); transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; perspective: 1200px; transform-style: preserve-3d; overflow: hidden; animation: summaryCardEntrance 0.5s ease-out; animation-delay: 0.2s; transform-origin: center;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: rgba(255,255,255,0.3);"></div>
                                            <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; transform: translateZ(10px);">Class Rank</h4>
                                            <p class="rank" style="margin: 0; font-size: 28px; font-weight: bold; transform: translateZ(15px); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);"><?php echo $rank > 0 ? $rank . 'th' : 'N/A'; ?></p>
                                            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;">of <?php echo $totalInGrade; ?></div>
                                        </div>
                                        
                                        <div class="summary-card" style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%); border-radius: 15px; padding: 20px; text-align: center; color: white; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3); transform: translateZ(0) rotateX(0deg) rotateY(0deg); transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; perspective: 1200px; transform-style: preserve-3d; overflow: hidden; animation: summaryCardEntrance 0.5s ease-out; animation-delay: 0.3s; transform-origin: center;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: rgba(255,255,255,0.3);"></div>
                                            <h4 style="margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; transform: translateZ(10px);">Pass Rate</h4>
                                            <p class="pass-rate" style="margin: 0; font-size: 28px; font-weight: bold; transform: translateZ(15px); text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);"><?php echo $passRate; ?>%</p>
                                            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;"><?php echo $passRate >= 80 ? 'Excellent' : ($passRate >= 60 ? 'Good' : 'Needs Improvement'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Subject Performance Table with 3D Effects -->
                                    <div class="subject-performance" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius: 15px; padding: 25px; box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2); margin-bottom: 25px; transform: translateZ(0) rotateX(0deg); transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; perspective: 1200px; transform-style: preserve-3d; overflow: hidden; transform-origin: center;">
                                        <h4 style="margin: 0 0 20px 0; color: #333; font-size: 1.3em; display: flex; align-items: center; gap: 10px; transform: translateZ(10px);"><i class="fas fa-chart-bar" style="color: #4CAF50;"></i> Subject-wise Performance</h4>
                                        <div style="overflow-x: auto;">
                                            <table class="performance-table" style="width: 100%; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); border-radius: 8px; overflow: hidden;">
                                                <thead style="background: #f8f9fa;">
                                                    <tr style="transform-style: preserve-3d;">
                                                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; position: relative; transform-style: preserve-3d; background: #f1f3f5;">Subject</th>
                                                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; position: relative; transform-style: preserve-3d; background: #f1f3f5;">Teacher</th>
                                                        <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; position: relative; transform-style: preserve-3d; background: #f1f3f5;">Grade</th>
                                                        <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; position: relative; transform-style: preserve-3d; background: #f1f3f5;">Percentage</th>
                                                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6; font-weight: 600; color: #555; position: relative; transform-style: preserve-3d; background: #f1f3f5;">Exam</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($subjectPerformance)): ?>
                                                        <?php foreach ($subjectPerformance as $index => $perf): 
                                                            $rowStyle = ($index % 2 == 0) ? 'background: #f8f9fa;' : 'background: white;';
                                                        ?>
                                                        <tr style="<?php echo $rowStyle; ?> transition: all 0.2s ease; transform-style: preserve-3d; transform-origin: center;" class="subject-row">
                                                            <td style="padding: 15px; border-bottom: 1px solid #eee; transform: translateZ(2px);"><?php echo htmlspecialchars($perf['subject_name'] ?? 'N/A'); ?></td>
                                                            <td style="padding: 15px; border-bottom: 1px solid #eee; transform: translateZ(2px);"><?php echo htmlspecialchars($perf['teacher_name'] ?? 'N/A'); ?></td>
                                                            <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center; transform: translateZ(2px);">
                                                                <span class="grade-<?php echo strtolower(substr($perf['grade'], 0, 1)); ?>" style="display: inline-block; padding: 5px 12px; border-radius: 20px; font-weight: bold; background: <?php 
                                                                    switch(strtoupper($perf['grade'])) {
                                                                        case 'A': echo '#4CAF50'; break;
                                                                        case 'B': echo '#2196F3'; break;
                                                                        case 'C': echo '#FF9800'; break;
                                                                        case 'D': echo '#FF5722'; break;
                                                                        case 'F': echo '#f44336'; break;
                                                                        default: echo '#9e9e9e';
                                                                    } 
                                                                ?>; color: white; min-width: 40px;"><?php echo htmlspecialchars($perf['grade'] ?? 'N/A'); ?></span>
                                                            </td>
                                                            <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center; transform: translateZ(2px);">
                                                                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                                                    <span style="font-weight: bold; color: #333;"><?php echo $perf['percentage'] ? number_format($perf['percentage'], 2) . '%' : 'N/A'; ?></span>
                                                                    <?php if ($perf['percentage']): ?>
                                                                    <div class="progress-bar" style="width: 80px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; transform: translateZ(0); transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);">
                                                                        <div class="progress-fill" style="height: 100%; width: <?php echo min($perf['percentage'], 100); ?>%; background: <?php 
                                                                            if ($perf['percentage'] >= 80) echo '#4CAF50';
                                                                            elseif ($perf['percentage'] >= 60) echo '#2196F3';
                                                                            elseif ($perf['percentage'] >= 40) echo '#FF9800';
                                                                            else echo '#f44336';
                                                                        ?>; border-radius: 4px; transition: width 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);"></div>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td style="padding: 15px; border-bottom: 1px solid #eee; transform: translateZ(2px);"><?php echo htmlspecialchars($perf['exam_name'] ?? 'N/A'); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5" style="padding: 30px; text-align: center; color: #999; font-style: italic;">No academic data available yet</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons with 3D Effects -->
                                    <div class="action-buttons" style="display: flex; gap: 15px; flex-wrap: wrap; perspective: 1200px; transform-style: preserve-3d; margin-top: 20px;">
                                        <button class="btn btn-3d" onclick="downloadReport('<?php echo $child['id']; ?>')" style="flex: 1; min-width: 200px; padding: 15px 20px; border-radius: 12px; font-weight: 600; text-align: center; transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); border: none; cursor: pointer; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25); transform: translateZ(0) rotateX(0deg) rotateY(0deg); position: relative; transform-style: preserve-3d; overflow: hidden; perspective: 1200px; background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                            <i class="fas fa-download"></i> Download Report
                                        </button>
                                        <button class="btn btn-3d" onclick="contactTeacher('<?php echo $child['id']; ?>')" style="flex: 1; min-width: 200px; padding: 15px 20px; border-radius: 12px; font-weight: 600; text-align: center; transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); border: none; cursor: pointer; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25); transform: translateZ(0) rotateX(0deg) rotateY(0deg); position: relative; transform-style: preserve-3d; overflow: hidden; perspective: 1200px; background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); color: white; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                            <i class="fas fa-comments"></i> Contact Teacher
                                        </button>
                                        <button class="btn btn-3d" onclick="viewProgressGraph('<?php echo $child['id']; ?>')" style="flex: 1; min-width: 200px; padding: 15px 20px; border-radius: 12px; font-weight: 600; text-align: center; transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); border: none; cursor: pointer; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25); transform: translateZ(0) rotateX(0deg) rotateY(0deg); position: relative; transform-style: preserve-3d; overflow: hidden; perspective: 1200px; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                            <i class="fas fa-chart-line"></i> View Progress
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Settings Section -->
                    <div class="profile-section" id="settings">
                        <h2><i class="fas fa-cog"></i> Account Settings</h2>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                            <div class="setting-options">
                                <div class="setting-option">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Email Notifications</span>
                                </div>
                                
                                <div class="setting-option">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span>SMS Alerts</span>
                                </div>
                                
                                <div class="setting-option">
                                    <label class="switch">
                                        <input type="checkbox">
                                        <span class="slider"></span>
                                    </label>
                                    <span>Push Notifications</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-lock"></i> Security Settings</h3>
                            <div class="setting-options">
                                <div class="setting-option">
                                    <button class="btn btn-outline" onclick="changePassword()">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                                
                                <div class="setting-option">
                                    <button class="btn btn-outline" onclick="enable2FA()">
                                        <i class="fas fa-shield-alt"></i> Two-Factor Authentication
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-user-circle"></i> Profile Visibility</h3>
                            <div class="setting-options">
                                <div class="setting-option">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Allow teachers to view my contact information</span>
                                </div>
                                
                                <div class="setting-option">
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                    <span>Show my profile to other parents</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3><i class="fas fa-trash-alt"></i> Account Management</h3>
                            <div class="setting-options">
                                <div class="setting-option">
                                    <button class="btn btn-danger" onclick="downloadData()">
                                        <i class="fas fa-download"></i> Download My Data
                                    </button>
                                </div>
                                
                                <div class="setting-option">
                                    <button class="btn btn-danger" onclick="deactivateAccount()">
                                        <i class="fas fa-user-slash"></i> Deactivate Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>About Us</h3>
                    <p>Excellence School is committed to providing quality education and nurturing young minds for a successful future.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=61571984150477"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.tiktok.com/@kazidon10"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.instagram.com/codebykazi/"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/@KaziGamingKG"><i class="fab fa-youtube"></i></a>
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
                <p>&copy; 2025 Excellence School. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../js/parent-profile.js"></script>
    <script>
        function downloadReport(childId) {
            alert('Downloading report for child ID: ' + childId);
            // In a real implementation, this would trigger a report download
        }
        
        function contactTeacher(childId) {
            alert('Contacting teacher for child ID: ' + childId);
            // In a real implementation, this would open a messaging interface
        }
        
        function viewProgressGraph(childId) {
            alert('Viewing progress graph for child ID: ' + childId);
            // In a real implementation, this would open a chart showing the student's progress over time
        }
        
        function changePassword() {
            alert('Change password functionality would open here');
            // In a real implementation, this would open a password change form
        }
        
        function enable2FA() {
            alert('Two-factor authentication setup would open here');
            // In a real implementation, this would open 2FA setup
        }
        
        function downloadData() {
            alert('Preparing data download...');
            // In a real implementation, this would prepare user data for download
        }
        
        function deactivateAccount() {
            if (confirm('Are you sure you want to deactivate your account? This action cannot be undone.')) {
                alert('Account deactivation initiated');
                // In a real implementation, this would send a request to deactivate the account
            }
        }
    </script>

</body>
</html>
