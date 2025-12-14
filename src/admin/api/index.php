<?php
// Start session at the beginning - TASK1601
session_start();

// TODO: Set headers for JSON response and CORS - TASK1602, TASK1603
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// TODO: Database connection using credentials from instructions
$host = 'localhost';
$db   = 'course';
$user = 'admin';
$pass = 'password123';
$charset = 'utf8mb4';

// TODO: Get PDO database connection using sample connection code from instructions
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// TODO: Get HTTP request method - TASK1604, TASK1605
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get request body for POST and PUT requests - TASK1606, TASK1607, TASK1608
$input = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters for filtering and searching
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Helper function to send JSON response
function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// TODO: Helper function to sanitize input
function sanitize($data) {
    // Trim whitespace
    $data = trim($data);
    // Strip HTML tags
    $data = strip_tags($data);
    // Convert special characters
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// TODO: Validate email - TASK1609, TASK1610
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Main API logic
try {
    switch ($action) {
        case 'login':
            // Get email and password from request
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                sendResponse(false, null, 'Email and password are required', 400);
            }
            
            // TODO: Prepare SQL query using PDO - TASK1611
            $stmt = $pdo->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = :email");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            
            // TODO: Execute the query - TASK1612
            $stmt->execute();
            
            // TODO: Fetch the result - TASK1613
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse(false, null, 'Invalid email or password', 401);
            }
            
            // TODO: Verify password - TASK1614
            if (!password_verify($password, $user['password'])) {
                sendResponse(false, null, 'Invalid email or password', 401);
            }
            
            // TODO: Store user data in session - TASK1615
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Remove password from response
            unset($user['password']);
            
            sendResponse(true, $user, 'Login successful');
            break;
            
        case 'logout':
            session_destroy();
            sendResponse(true, null, 'Logged out successfully');
            break;
            
        case 'get_students':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Check if search parameter exists
            $search = $_GET['search'] ?? '';

            // TODO: Check if sort and order parameters exist
            $sort = $_GET['sort'] ?? 'name';
            $order = $_GET['order'] ?? 'asc';

            // Validate sort field to prevent SQL injection
            $allowedSort = ['name', 'email', 'created_at'];
            $sort = in_array($sort, $allowedSort) ? $sort : 'name';
            $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

            // Build SQL query
            $sql = "SELECT id, name, email, created_at FROM users WHERE is_admin = 0";
            $params = [];

            if ($search) {
                $sql .= " AND (name LIKE :search OR email LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $sql .= " ORDER BY $sort $order";

            // TODO: Prepare SQL query using PDO - TASK1611
            $stmt = $pdo->prepare($sql);

            // TODO: Bind parameters if using search
            if ($search) {
                $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            }

            // TODO: Execute the query - TASK1612
            $stmt->execute();

            // TODO: Fetch all results as associative array - TASK1613
            $students = $stmt->fetchAll();

            // TODO: Return JSON response - TASK1616
            sendResponse(true, $students, 'Students retrieved successfully');
            break;

        case 'get_student_by_id':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Get student_id from query parameters
            $studentId = $_GET['id'] ?? '';

            if (!$studentId) {
                sendResponse(false, null, 'Student ID is required', 400);
            }

            // TODO: Prepare SQL query to select student by id - TASK1611
            $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = :id AND is_admin = 0");
            $stmt->bindValue(':id', $studentId, PDO::PARAM_INT);
            
            // TODO: Execute the query - TASK1612
            $stmt->execute();

            // TODO: Fetch the result - TASK1613
            $student = $stmt->fetch();

            // TODO: Check if student exists
            if ($student) {
                sendResponse(true, $student, 'Student retrieved successfully');
            } else {
                sendResponse(false, null, 'Student not found', 404);
            }
            break;

        case 'add_student':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Validate required fields
            $name = $_POST['name'] ?? '';
            $studentId = $_POST['student_id'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? 'password';

            if (empty($name) || empty($studentId) || empty($email) || empty($password)) {
                sendResponse(false, null, 'All fields are required', 400);
            }

            // TODO: Sanitize input data
            $name = sanitize($name);
            $studentId = sanitize($studentId);
            $email = sanitize($email);
            $password = sanitize($password);

            // TODO: Validate email format - TASK1609, TASK1610
            if (!validateEmail($email)) {
                sendResponse(false, null, 'Invalid email format', 400);
            }

            // TODO: Check if student_id or email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                sendResponse(false, null, 'Email already exists', 409);
            }

            // TODO: Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // TODO: Prepare INSERT query - TASK1611
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, is_admin) 
                VALUES (:name, :email, :password, 0)
            ");

            // TODO: Bind parameters
            $insertStmt->bindValue(':name', $name, PDO::PARAM_STR);
            $insertStmt->bindValue(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);

            // TODO: Check if insert was successful
            if ($insertStmt->execute()) {
                $newId = $pdo->lastInsertId();
                $newStudent = $pdo->query("SELECT id, name, email, created_at FROM users WHERE id = $newId")->fetch();
                sendResponse(true, $newStudent, 'Student created successfully', 201);
            } else {
                sendResponse(false, null, 'Failed to create student', 500);
            }
            break;

        case 'update_student':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Get data from request body
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';

            if (!$id) {
                sendResponse(false, null, 'Student ID is required', 400);
            }

            // TODO: Check if student exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND is_admin = 0");
            $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();

            if (!$checkStmt->fetch()) {
                sendResponse(false, null, 'Student not found', 404);
            }

            // TODO: Build UPDATE query dynamically
            $fields = [];
            $params = [':id' => $id];

            if (!empty($name)) {
                $fields[] = 'name = :name';
                $params[':name'] = sanitize($name);
            }

            if (!empty($email)) {
                // Validate new email - TASK1609, TASK1610
                if (!validateEmail($email)) {
                    sendResponse(false, null, 'Invalid email format', 400);
                }

                // TODO: If email is being updated, check if new email already exists
                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $emailCheck->bindValue(':email', $email, PDO::PARAM_STR);
                $emailCheck->bindValue(':id', $id, PDO::PARAM_INT);
                $emailCheck->execute();

                if ($emailCheck->fetch()) {
                    sendResponse(false, null, 'Email already exists', 409);
                }

                $fields[] = 'email = :email';
                $params[':email'] = sanitize($email);
            }

            if (empty($fields)) {
                sendResponse(false, null, 'No fields to update', 400);
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id AND is_admin = 0";
            
            // TODO: Prepare SQL query - TASK1611
            $stmt = $pdo->prepare($sql);

            // TODO: Bind parameters dynamically
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            // TODO: Execute the query - TASK1612
            if ($stmt->execute()) {
                sendResponse(true, null, 'Student updated successfully');
            } else {
                sendResponse(false, null, 'Failed to update student', 500);
            }
            break;

        case 'delete_student':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Validate that student_id is provided
            $id = $_POST['id'] ?? 0;

            if (!$id) {
                sendResponse(false, null, 'Student ID is required', 400);
            }

            // TODO: Check if student exists
            $checkStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND is_admin = 0");
            $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();

            $student = $checkStmt->fetch();

            if (!$student) {
                sendResponse(false, null, 'Student not found', 404);
            }

            // TODO: Prepare DELETE query - TASK1611
            $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND is_admin = 0");
            $deleteStmt->bindValue(':id', $id, PDO::PARAM_INT);

            // TODO: Execute the query - TASK1612
            if ($deleteStmt->execute()) {
                sendResponse(true, ['deleted_student' => $student], 'Student deleted successfully');
            } else {
                sendResponse(false, null, 'Failed to delete student', 500);
            }
            break;

        case 'change_password':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // TODO: Validate required fields
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                sendResponse(false, null, 'All password fields are required', 400);
            }

            // TODO: Validate new password strength
            if (strlen($newPassword) < 8) {
                sendResponse(false, null, 'New password must be at least 8 characters', 400);
            }

            if ($newPassword !== $confirmPassword) {
                sendResponse(false, null, 'New passwords do not match', 400);
            }

            // TODO: Retrieve current password hash from database (for the logged-in user)
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user) {
                sendResponse(false, null, 'User not found', 404);
            }

            // TODO: Verify current password - TASK1614
            if (!password_verify($currentPassword, $user['password'])) {
                sendResponse(false, null, 'Current password is incorrect', 401);
            }

            // TODO: Hash the new password
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // TODO: Update password in database
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $updateStmt->bindValue(':password', $newHashedPassword, PDO::PARAM_STR);
            $updateStmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);

            // TODO: Check if update was successful
            if ($updateStmt->execute()) {
                sendResponse(true, null, 'Password updated successfully');
            } else {
                sendResponse(false, null, 'Failed to update password', 500);
            }
            break;

        case 'get_assignments':
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // Get assignments from database
            $stmt = $pdo->query("SELECT id, title, description, due_date, created_at FROM assignments ORDER BY due_date ASC");
            $assignments = $stmt->fetchAll();
            sendResponse(true, $assignments, 'Assignments retrieved successfully');
            break;

        case 'get_admin_info':
            // Check if user is logged in and is admin
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                sendResponse(false, null, 'Unauthorized', 401);
            }
            
            // Get admin user info
            $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = :id AND is_admin = 1");
            $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $admin = $stmt->fetch();
            sendResponse(true, $admin, 'Admin info retrieved');
            break;

        case 'register':
            // Registration logic
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($name) || empty($email) || empty($password)) {
                sendResponse(false, null, 'All fields are required', 400);
            }
            
            // Validate email - TASK1609, TASK1610
            if (!validateEmail($email)) {
                sendResponse(false, null, 'Invalid email format', 400);
            }
            
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $checkStmt->bindValue(':email', $email, PDO::PARAM_STR);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                sendResponse(false, null, 'Email already exists', 409);
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, is_admin) 
                VALUES (:name, :email, :password, 0)
            ");
            $insertStmt->bindValue(':name', sanitize($name), PDO::PARAM_STR);
            $insertStmt->bindValue(':email', sanitize($email), PDO::PARAM_STR);
            $insertStmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
            
            if ($insertStmt->execute()) {
                sendResponse(true, null, 'Registration successful', 201);
            } else {
                sendResponse(false, null, 'Registration failed', 500);
            }
            break;

        default:
            sendResponse(false, null, 'Invalid action specified', 400);
            break;
    }

// TODO: Error handling - TASK1617, TASK1618
} catch (PDOException $e) {
    // TODO: Handle database errors - TASK1618
    error_log("Database error: " . $e->getMessage());
    sendResponse(false, null, 'Database error occurred', 500);
} catch (Exception $e) {
    // TODO: Handle general errors - TASK1617
    error_log("General error: " . $e->getMessage());
    sendResponse(false, null, 'An error occurred', 500);
}