<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// TODO: Get the PDO database connection
require_once 'Database.php';
$db = (new Database())->getConnection();
// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters for filtering and searching
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$student_id = "123";
$search = isset($_GET['search']) ? $_GET['search'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$action = isset($_GET['action']) ? $_GET['action'] : null;
/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    
    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    
    // TODO: Bind parameters if using search
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data
    global $search, $sort, $order;

    $allowedSort = ['name', 'student_id', 'email'];
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
    $sort = in_array($sort, $allowedSort) ? $sort : 'name';
    $sql = "SELECT student_id, name, email, created_at FROM students WHERE 1";
    if ($search) {
        $sql .= " AND (name LIKE :search OR student_id LIKE :search OR email LIKE :search)";}
    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);
    if ($search) {
        $stmt->bindValue(':search', "%$search%");}
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);   
    sendResponse(['success' => true, 'data' => $students]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    
    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch the result
    
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
       $stmt = $db->prepare("SELECT student_id, name, email, created_at FROM students WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        sendResponse(['success' => true, 'data' => $student]); }
         else {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);}
}

/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    
    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    
    // TODO: Prepare INSERT query
    
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    if (empty($data['student_id']) || empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse(['success' => false, 'message' => 'All fields are required'], 400);}
    $student_id = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    if (!validateEmail($email)) {
        sendResponse(['success' => false, 'message' => 'Invalid email'], 400);}
     $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :student_id OR email = :email");
    $stmt->execute([':student_id' => $student_id, ':email' => $email]);
    if ($stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student ID or Email already exists'], 409);}
     $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password) VALUES (:student_id, :name, :email, :password)");
    $success = $stmt->execute([
        ':student_id' => $student_id,
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);
   if ($success) {
        sendResponse(['success' => true, 'message' => 'Student created'], 201); } 
        else {
        sendResponse(['success' => false, 'message' => 'Failed to create student'], 500);}   
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    
    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
    
    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    
    // TODO: Execute the query
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
 if (empty($data['student_id'])) {
        sendResponse(['success' => false, 'message' => 'student_id is required'], 400);
    }

    $student_id = sanitizeInput($data['student_id']);
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    $fields = [];
    $params = [':student_id' => $student_id];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }
    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) {
            sendResponse(['success' => false, 'message' => 'Invalid email'], 400);
        }
    $stmt = $db->prepare("SELECT * FROM students WHERE email = :email AND student_id != :student_id");
        $stmt->execute([':email' => $data['email'], ':student_id' => $student_id]);
        if ($stmt->fetch()) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
        }
        $fields[] = "email = :email";
        $params[':email'] = sanitizeInput($data['email']);
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE student_id = :student_id";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Student updated']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update student'], 500);
    }       
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    // TODO: Prepare DELETE query
    
    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
 if (!$studentId) {
        sendResponse(['success' => false, 'message' => 'student_id is required'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    $stmt = $db->prepare("DELETE FROM students WHERE student_id = :student_id");
    $success = $stmt->execute([':student_id' => $studentId]);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Student deleted']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete student'], 500);
    }   
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    
    // TODO: Update password in database
    // Prepare UPDATE query
    
    // TODO: Bind parameters and execute
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
  if (empty($data['student_id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
    }

    $student_id = sanitizeInput($data['student_id']);
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !password_verify($data['current_password'], $student['password'])) {
        sendResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
    }

    $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE students SET password = :password WHERE student_id = :student_id");
    $success = $stmt->execute([':password' => $hashedPassword, ':student_id' => $student_id]);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to change password'], 500);
    }  
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
if ($studentId) {
            getStudentById($db, $studentId);
        } else {
            getStudents($db);
        }
        } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
              if ($action === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
                updateStudent($db, $input);
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        if (!$studentId && !empty($input['student_id'])) {
            $studentId = $input['student_id'];
        }
        deleteStudent($db, $studentId);

    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);

    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    sendResponse(['success' => false, 'message' => 'Database error: '.$e->getMessage()], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    sendResponse(['success' => false, 'message' => 'Server error: '.$e->getMessage()], 500);

}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    
    // TODO: Echo JSON encoded data
    
    // TODO: Exit to prevent further execution
     http_response_code($statusCode);
    echo json_encode($data);
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    return htmlspecialchars(strip_tags(trim($data)));

}

?>
