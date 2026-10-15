<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit;
}

// Database connection
require_once '../../config/dbconnection.php';

// Get user info from session
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Get student ID from session user_id
$userId = $_SESSION['user_id'];

// Get student details
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // If student not found by user_id, try by email
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$userEmail]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            header('Location: login.php');
            exit;
        }
    }
} catch (PDOException $e) {
    // If students table doesn't exist, redirect to login
    header('Location: login.php');
    exit;
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

// Function to get upcoming assignments for a student
function getUpcomingAssignments($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare(
            "SELECT a.title as assignment_title, a.due_date, 
            s.subject_name, c.class_name 
            FROM assignments a 
            LEFT JOIN subjects s ON a.subject_id = s.id 
            LEFT JOIN classes c ON a.class_id = c.id 
            LEFT JOIN enrollments e ON e.class_id = c.id 
            WHERE e.student_id = ? AND a.due_date >= CURDATE() 
            ORDER BY a.due_date ASC LIMIT 5"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return []; // Return empty array if assignments table doesn't exist yet
    }
}

// Get actual student data
$studentId = $student['id'];
$averageGrade = getAverageGrade($pdo, $studentId);
$attendanceRate = getAttendanceRate($pdo, $studentId);
$upcomingAssignments = getUpcomingAssignments($pdo, $studentId);
$coursesEnrolled = 0; // This would need to be calculated based on enrollments

// Calculate number of courses enrolled
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM enrollments WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $coursesEnrolled = $stmt->fetch(PDO::FETCH_ASSOC)['course_count'];
} catch (PDOException $e) {
    $coursesEnrolled = 0;
}

// Count pending assignments
$pendingAssignments = count($upcomingAssignments);

// Get today's schedule
function getTodaysSchedule($pdo, $studentId) {
    try {
        $stmt = $pdo->prepare(
            "SELECT s.subject_name, c.schedule, c.room_number 
            FROM enrollments e 
            JOIN classes c ON e.class_id = c.id 
            JOIN subjects s ON c.subject_id = s.id 
            WHERE e.student_id = ? AND DAYOFWEEK(CURDATE()) = SUBSTRING_INDEX(c.schedule, ' ', -1) + 1 
            ORDER BY c.schedule"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return []; // Return empty array if schedule table doesn't exist yet
    }
}

$todaysSchedule = getTodaysSchedule($pdo, $studentId);

// Get recent notices for students
function getRecentNotices($pdo) {
    try {
        $stmt = $pdo->prepare(
            "SELECT title, created_at FROM notices 
            WHERE (target_audience = 'all' OR target_audience LIKE '%students%' OR target_audience LIKE '%all%') 
            AND is_published = 1 
            ORDER BY created_at DESC LIMIT 5"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return []; // Return empty array if notices table doesn't exist yet
    }
}

$recentNotices = getRecentNotices($pdo);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Excellence School</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../css/portal.css">
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
                    <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
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
                            <p class="tagline">Building Tomorrow's Leaders Today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="portal-header">
        <div class="container">
            <h1><i class="fas fa-graduation-cap"></i> Student Portal</h1>
            <p>Welcome back, <span id="studentName"><?php echo htmlspecialchars($userName); ?></span>!</p>
        </div>
    </section>

    <section class="portal-content">
        <div class="container">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-book"></i>
                    <h3>Courses Enrolled</h3>
                    <p><?php echo $coursesEnrolled; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-tasks"></i>
                    <h3>Pending Assignments</h3>
                    <p><?php echo $pendingAssignments; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Average Grade</h3>
                    <p><?php echo $averageGrade; ?>%</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Attendance Rate</h3>
                    <p><?php echo $attendanceRate; ?>%</p>
                </div>
            </div>

            <div class="portal-grid">
                <div class="portal-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-calendar-alt"></i> Today's Schedule</h3>
                    </div>
                    <div class="widget-content">
                        <ul class="schedule-list">
                            <?php if (!empty($todaysSchedule)): ?>
                                <?php foreach ($todaysSchedule as $class): ?>
                                <li>
                                    <span class="time"><?php echo htmlspecialchars($class['schedule'] ?? 'TBD'); ?></span>
                                    <span class="subject"><?php echo htmlspecialchars($class['subject_name'] ?? 'N/A'); ?></span>
                                    <span class="room"><?php echo htmlspecialchars($class['room_number'] ?? 'TBD'); ?></span>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <span class="time">-</span>
                                    <span class="subject">No classes scheduled for today</span>
                                    <span class="room">-</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="portal-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-tasks"></i> Pending Assignments</h3>
                    </div>
                    <div class="widget-content">
                        <ul class="assignments-list">
                            <?php if (!empty($upcomingAssignments)): ?>
                                <?php foreach ($upcomingAssignments as $assignment): ?>
                                <li>
                                    <span class="assignment-title"><?php echo htmlspecialchars($assignment['assignment_title'] ?? 'N/A'); ?></span>
                                    <span class="due-date">Due: <?php echo date('M j', strtotime($assignment['due_date'])); ?></span>
                                    <span class="priority <?php echo (strtotime($assignment['due_date']) - time() < 86400 * 3) ? 'high' : ((strtotime($assignment['due_date']) - time() < 86400 * 7) ? 'medium' : 'low'); ?>">
                                        <?php echo (strtotime($assignment['due_date']) - time() < 86400 * 3) ? 'Urgent' : ((strtotime($assignment['due_date']) - time() < 86400 * 7) ? 'Soon' : 'Normal'); ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <span class="assignment-title">No pending assignments</span>
                                    <span class="due-date">-</span>
                                    <span class="priority low">None</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="portal-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-bell"></i> Recent Notices</h3>
                    </div>
                    <div class="widget-content">
                        <ul class="notices-list">
                            <?php if (!empty($recentNotices)): ?>
                                <?php foreach ($recentNotices as $notice): ?>
                                <li>
                                    <span class="notice-title"><?php echo htmlspecialchars($notice['title'] ?? 'Untitled Notice'); ?></span>
                                    <span class="notice-date">Posted <?php echo date('M j', strtotime($notice['created_at'])); ?></span>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <span class="notice-title">No recent notices</span>
                                    <span class="notice-date">-</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="portal-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-chart-bar"></i> Performance Overview</h3>
                    </div>
                    <div class="widget-content">
                        <div class="performance-chart">
                            <canvas id="performanceChart" height="200"></canvas>
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

    <script src="../js/student-portal.js"></script>
    <script>
        // Enhanced student portal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                // Add delay for staggered animation
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animated-card');
                
                // Add hover effect enhancement
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Add animation to portal widgets
            const widgets = document.querySelectorAll('.portal-widget');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            widgets.forEach(widget => {
                widget.style.opacity = '0';
                widget.style.transform = 'translateY(20px)';
                widget.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(widget);
            });
            
            // Add click effects to list items
            const listItems = document.querySelectorAll('.schedule-list li, .assignments-list li, .notices-list li');
            listItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.style.backgroundColor = '#f0f8ff';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 300);
                });
            });
            
            // Add performance chart initialization
            const performanceChart = document.getElementById('performanceChart');
            if (performanceChart) {
                // This would typically be replaced with actual charting library initialization
                performanceChart.style.backgroundColor = '#f8f9fa';
                performanceChart.style.borderRadius = '8px';
                performanceChart.style.display = 'flex';
                performanceChart.style.alignItems = 'center';
                performanceChart.style.justifyContent = 'center';
                performanceChart.style.color = '#666';
                performanceChart.textContent = 'Performance Chart Visualization';
                performanceChart.style.fontWeight = 'bold';
            }
        });
    </script>
</body>
</html>
