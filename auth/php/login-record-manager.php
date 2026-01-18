<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../html/login.html');
    exit;
}

// Database connection
require_once '../../config/dbconnection.php';

// Handle form submissions
$message = '';
$error = '';

// Handle login record deletion
if (isset($_POST['delete_login_record'])) {
    $recordId = (int)$_POST['record_id'];
    
    try {
        // First get the record details for confirmation
        $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.role FROM users u WHERE u.id = ?");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            // Delete the user record (will cascade delete related records)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$recordId]);
            
            $message = "Login record for '" . htmlspecialchars($record['full_name']) . "' (" . htmlspecialchars($record['email']) . ") has been deleted successfully.";
        } else {
            $error = "Record not found.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle bulk deletion
if (isset($_POST['bulk_delete'])) {
    $recordIds = $_POST['selected_records'] ?? [];
    
    if (!empty($recordIds)) {
        try {
            $placeholders = str_repeat('?,', count($recordIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            $stmt->execute($recordIds);
            
            $message = count($recordIds) . " login record(s) deleted successfully.";
        } catch (PDOException $e) {
            $error = "Database error during bulk deletion: " . $e->getMessage();
        }
    } else {
        $error = "No records selected for deletion.";
    }
}

// Fetch all login records
try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.email,
            u.full_name,
            u.role,
            u.created_at,
            CASE 
                WHEN s.id IS NOT NULL THEN 'Yes'
                WHEN t.id IS NOT NULL THEN 'Yes'
                WHEN up.id IS NOT NULL AND u.role = 'parent' THEN 'Yes'
                ELSE 'No'
            END as has_profile
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN teachers t ON u.id = t.user_id  
        LEFT JOIN user_profiles up ON u.id = up.user_id AND u.role = 'parent'
        ORDER BY u.created_at DESC
    ");
    $loginRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $loginRecords = [];
}

// Get statistics
$totalRecords = count($loginRecords);
$adminCount = count(array_filter($loginRecords, fn($r) => $r['role'] === 'admin'));
$teacherCount = count(array_filter($loginRecords, fn($r) => $r['role'] === 'teacher'));
$studentCount = count(array_filter($loginRecords, fn($r) => $r['role'] === 'student'));
$parentCount = count(array_filter($loginRecords, fn($r) => $r['role'] === 'parent'));

$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Record Manager - Excellence School</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .manager-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .records-table th,
        .records-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .records-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }
        
        .records-table tr:last-child td {
            border-bottom: none;
        }
        
        .records-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .role-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-admin { background-color: #ffc107; color: #212529; }
        .badge-teacher { background-color: #17a2b8; color: white; }
        .badge-student { background-color: #28a745; color: white; }
        .badge-parent { background-color: #6f42c1; color: white; }
        
        .profile-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .profile-yes { background-color: #28a745; }
        .profile-no { background-color: #dc3545; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .bulk-actions {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .records-table {
                font-size: 0.8rem;
            }
            
            .records-table th,
            .records-table td {
                padding: 10px 8px;
            }
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
                    <a href="admin-dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
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
                </div>
            </div>
        </div>
    </header>

    <section class="admin-header">
        <div class="container">
            <h1><i class="fas fa-user-times"></i> Login Record Manager</h1>
            <p>Manage and delete user login records</p>
        </div>
    </section>

    <section class="admin-content">
        <div class="manager-container">
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
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalRecords; ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $adminCount; ?></div>
                    <div class="stat-label">Admin Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $teacherCount; ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $studentCount; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $parentCount; ?></div>
                    <div class="stat-label">Parents</div>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <div class="select-all-container">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                <form id="bulkDeleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="bulk_delete" value="1">
                    <button type="button" class="btn btn-primary" onclick="confirmBulkDelete()" style="background-color: #dc3545;">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </form>
                <a href="admin-access-editor.php" class="btn btn-outline">
                    <i class="fas fa-users"></i> Full User Management
                </a>
            </div>
            
            <!-- Records Table -->
            <table class="records-table">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll()">
                        </th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Profile Created</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loginRecords as $record): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_records[]" 
                                   value="<?php echo $record['id']; ?>" 
                                   class="record-checkbox"
                                   onchange="updateSelectAllState()">
                        </td>
                        <td><?php echo htmlspecialchars($record['id']); ?></td>
                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                        <td>
                            <span class="role-badge badge-<?php echo htmlspecialchars($record['role']); ?>">
                                <?php echo ucfirst(htmlspecialchars($record['role'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="profile-status profile-<?php echo strtolower($record['has_profile']); ?>" 
                                  title="<?php echo $record['has_profile'] === 'Yes' ? 'Profile exists' : 'No profile'; ?>">
                            </span>
                            <?php echo $record['has_profile']; ?>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($record['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-outline btn-small delete-record" 
                                        data-id="<?php echo $record['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($record['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($record['email']); ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($loginRecords)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; margin-top: 20px;">
                <i class="fas fa-user-slash" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                <h3>No Login Records Found</h3>
                <p>There are currently no user login records in the system.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the login record for:</p>
                <p><strong id="deleteUserInfo"></strong></p>
                <p style="color: #dc3545; font-weight: bold;">This action cannot be undone and will remove all associated data.</p>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" id="delete_record_id" name="record_id">
                <input type="hidden" name="delete_login_record" value="1">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline close-modal-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #dc3545;">
                        <i class="fas fa-trash"></i> Delete Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirm Bulk Deletion</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>You have selected <strong id="bulkDeleteCount">0</strong> record(s) for deletion.</p>
                <p style="color: #dc3545; font-weight: bold;">This action cannot be undone and will remove all associated data for all selected records.</p>
                <p>Are you sure you want to proceed?</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline close-modal-btn">Cancel</button>
                <button type="button" class="btn btn-primary" style="background-color: #dc3545;" onclick="performBulkDelete()">
                    <i class="fas fa-trash"></i> Delete Selected Records
                </button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>About Us</h3>
                    <p>Excellence School is committed to providing quality education and nurturing young minds for a successful future.</p>
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
                <p>&copy; 2025 Excellence School. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Get modal elements
            const deleteModal = document.getElementById('deleteModal');
            const bulkDeleteModal = document.getElementById('bulkDeleteModal');
            const closeModalButtons = document.querySelectorAll('.close-modal, .close-modal-btn');
            
            // Get delete buttons
            const deleteButtons = document.querySelectorAll('.delete-record');
            
            // Show delete confirmation modal
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const recordId = this.getAttribute('data-id');
                    const userName = this.getAttribute('data-name');
                    const userEmail = this.getAttribute('data-email');
                    
                    document.getElementById('delete_record_id').value = recordId;
                    document.getElementById('deleteUserInfo').textContent = `${userName} (${userEmail})`;
                    deleteModal.style.display = 'flex';
                });
            });
            
            // Close modals
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteModal.style.display = 'none';
                    bulkDeleteModal.style.display = 'none';
                });
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
                if (event.target === bulkDeleteModal) {
                    bulkDeleteModal.style.display = 'none';
                }
            });
        });
        
        // Select all functionality
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            const selectAllHeader = document.getElementById('selectAllHeader');
            
            const isChecked = selectAllCheckbox.checked;
            selectAllHeader.checked = isChecked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        }
        
        function updateSelectAllState() {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            const checkedBoxes = document.querySelectorAll('.record-checkbox:checked');
            const selectAllCheckbox = document.getElementById('selectAll');
            const selectAllHeader = document.getElementById('selectAllHeader');
            
            const allChecked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
            selectAllCheckbox.checked = allChecked;
            selectAllHeader.checked = allChecked;
        }
        
        // Bulk delete functionality
        function confirmBulkDelete() {
            const selectedCheckboxes = document.querySelectorAll('.record-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                alert('Please select at least one record to delete.');
                return;
            }
            
            document.getElementById('bulkDeleteCount').textContent = selectedCheckboxes.length;
            document.getElementById('bulkDeleteModal').style.display = 'flex';
        }
        
        function performBulkDelete() {
            // Move selected IDs to the form
            const selectedCheckboxes = document.querySelectorAll('.record-checkbox:checked');
            const form = document.getElementById('bulkDeleteForm');
            
            // Clear existing hidden inputs
            const existingInputs = form.querySelectorAll('input[name="selected_records[]"]');
            existingInputs.forEach(input => input.remove());
            
            // Add selected IDs
            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_records[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });
            
            form.submit();
        }
    </script>
</body>
</html>