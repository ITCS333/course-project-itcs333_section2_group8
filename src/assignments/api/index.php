<?php
session_start();
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header("Content-Type: application/json");



// TODO: Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}



// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once '../../config/database.php';


// TODO: Create database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (PDOException $e) {
    sendResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
}

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);




// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
$input = json_decode(file_get_contents('php://input'), true);
if ($method === 'POST' || $method === 'PUT') {
    $data = is_array($input) ? $input : [];
} else {
    $data = [];
}   


// TODO: Parse query parameters
$resource = isset($_GET['resource']) ? $_GET['resource'] : null;
$id = $_GET['id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;



// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments where 1=1";
    $params = [];
    
    
    // TODO: Check if 'search' query parameter exists in $_GET
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = '%' . trim($_GET['search']) . '%';
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = $search;
    }
    
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $allowedSortFields = ['title', 'due_date', 'created_at'];
    $allowedOrderValues = ['asc', 'desc'];  

    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'asc';
    if (in_array($sort, $allowedSortFields) && in_array(strtolower($order), $allowedOrderValues)) {
        $sql .= " ORDER BY $sort " . strtoupper($order);
    } else {
        $sql .= " ORDER BY created_at ASC";
    }
    
    
    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind parameters if search is used
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    
    // TODO: Execute the prepared statement
    $stmt->execute();
    
    
    // TODO: Fetch all results as associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($assignments as &$assignment) {
        $assignment['files'] = json_decode($assignment['files'], true) ?: [];
    }
    
    
    // TODO: Return JSON response
    sendResponse($assignments);
    
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
    }
    
    
    // TODO: Prepare SQL query to select assignment by id
    $sql = "SELECT * FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind the :id parameter
    $stmt->bindParam(':id', $assignmentId);
    
    
    // TODO: Execute the statement
    $stmt->execute();
    
    
    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    // TODO: Check if assignment was found
    if (!$assignment) {
        sendResponse(['error' => 'Assignment not found'], 404);
   return;
    }


    
    
    // TODO: Decode the 'files' field from JSON to array
    if ($assignment['files']) {
        $assignment['files'] = json_decode($assignment['files'], true) ?: [];
    } else {
        $assignment['files'] = [];
    }
   
    
    // TODO: Return success response with assignment data
    sendResponse($assignment);
    
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    $requiredFields = ['title', 'description', 'due_date'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(['error' => "$field is required"], 400);
        return;
        }
    }
    
    
    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $due_date = ($data['due_date']);

    
    
    // TODO: Validate due_date format
    if (!validateDate($due_date)) {
        sendResponse(['error' => 'Invalid due_date format. Expected YYYY-MM-DD'], 400);
        return;
    }
    
    
    // TODO: Generate a unique assignment ID
    $assignment_id = 'ASG-' . time() . '-' . rand(1000, 9999);
    
    
    // TODO: Handle the 'files' field
    $files = isset($data['files']) && is_array($data['files']) ? $data['files'] : [];
    $filesJson = json_encode($files);
    
    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind all parameters
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $due_date);
    $stmt->bindValue(':files', $filesJson);
    
    // TODO: Execute the statement
    try {
        $stmt->execute();
    
        // TODO: Check if insert was successful
        if ($stmt->rowCount() > 0) {
            $newAssignment = [
                'id' => $db->lastInsertId(),
                'title' => $title,
                'description' => $description,
                'due_date' => $due_date,
                'files' => $files,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            sendResponse($newAssignment, 201);
        } else {
            sendResponse(['error' => 'Failed to create assignment'], 500);
        }
    } catch (PDOException $e) {
        sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }   

    
    
    // TODO: If insert failed, return 500 error
 
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
if (empty($data['id'])) {
        sendResponse(['error' => 'Assignment ID is required for update'], 400);
        return;
    }
    
    
    // TODO: Store assignment ID in variable
    $assignmentId = $data['id'];
    
    
    // TODO: Check if assignment exists
    $checkstmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkstmt->bindValue(':id', $assignmentId);
    $checkstmt->execute();
    if ($checkstmt->rowCount() === 0) {
        sendResponse(['error' => 'Assignment not found'], 404);
        return;
    }


    
    // TODO: Build UPDATE query dynamically based on provided fields
    $sql = "UPDATE assignments SET ";
    $updates = [];
    $params = [':id' => $assignmentId];
    
    
    // TODO: Check which fields are provided and add to SET clause
    $allowedSortFields = ['title', 'description', 'due_date', 'files'];
    $hasUpdates = false;

    foreach ($allowedSortFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'files' && is_array($data[$field])) {
                $filesJson = json_encode($data[$field]);
                $updates[] = "$field = :$field";
                $params[":$field"] = $filesJson;
            } else {
                $updates[] = "$field = :$field";
                $params[":$field"] = sanitizeInput($data[$field]);
            }
            $hasUpdates = true;
        }
    }

    
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (!$hasUpdates) {
        sendResponse(['error' => 'No fields to update'], 400);
        return;
    }
    
    
    // TODO: Complete the UPDATE query
    $updates[] = "updated_at = NOW()";
    $sql .= implode(", ", $updates) . " WHERE id = :id";
    
    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    
    // TODO: Execute the statement
    try {
        $stmt->execute();
    
        // TODO: Check if update was successful
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Assignment updated successfully']);
        } else {
            sendResponse(['message' => 'No changes made to the assignment']);
        }
    } catch (PDOException $e) {
        sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
    
    
    // TODO: If no rows affected, return appropriate message
    
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    
    
    // TODO: Check if assignment exists
    $checkstmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkstmt->bindValue(':id', $assignmentId);
    $checkstmt->execute();
    if ($checkstmt->rowCount() === 0) {
        sendResponse(['error' => 'Assignment not found'], 404);
        return;
    }
    
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments WHERE assignment_id = :assignment_id");
    $deleteCommentsStmt->bindValue(':assignment_id', $assignmentId);
    $deleteCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for assignment
    $sql = "DELETE FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind the :id parameter
    $stmt->bindParam(':id', $assignmentId);
    
    
    // TODO: Execute the statement
    try {
        $stmt->execute();
    
        // TODO: Check if delete was successful
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Assignment deleted successfully']);
        } else {
            sendResponse(['error' => 'Failed to delete assignment'], 500);
        }
    } catch (PDOException $e) {
        sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
    
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    
    
    // TODO: Prepare SQL query to select all comments for the assignment
    $sql = "SELECT * FROM comments WHERE assignment_id = :assignment_id";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the :assignment_id parameter
    $stmt->bindValue(':assignment_id', $assignmentId);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return success response with comments data
    sendResponse(['comments' => $comments]);
    
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    $requiredFields = ['assignment_id', 'author', 'text'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(['error' => "$field is required"], 400);
            return;
        }
    }   
    
    
    // TODO: Sanitize input data
    $assignment_id = sanitizeInput($data['assignment_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    
    // TODO: Validate that text is not empty after trimming
    if (empty(trim($text))) {
        sendResponse(['error' => 'Comment text cannot be empty'], 400);
        return;
    }   
    
    
    // TODO: Verify that the assignment exists
    $checkstmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkstmt->bindValue(':id', $assignment_id);
    $checkstmt->execute();
    if ($checkstmt->rowCount() === 0) {
        sendResponse(['error' => 'Assignment not found'], 404);
        return;
    }
    
    
    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments (assignment_id, author, text, created_at) 
            VALUES (:assignment_id, :author, :text, NOW())";
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind all parameters
    $stmt->bindValue(':assignment_id', $assignment_id);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);
    
    
    // TODO: Execute the statement
    try {
        $stmt->execute();
    
    
    // TODO: Get the ID of the inserted comment
    $commentId = $db->lastInsertId();
    
    // TODO: Return success response with created comment data
    $newComment = [
        'id' => $commentId,
        'assignment_id' => $assignment_id,
        'author' => $author,
        'text' => $text,
        'created_at' => date('Y-m-d H:i:s')
    ];
    sendResponse(['comment' => $newComment], 201);
    }
    catch (PDOException $e) {
        sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        sendResponse(['error' => 'Comment ID is required'], 400);
        return;
    }
    
    
    // TODO: Check if comment exists
    $checkstmt = $db->prepare("SELECT * FROM comments WHERE id = :id");
    $checkstmt->bindValue(':id', $commentId);
    $checkstmt->execute();
    if ($checkstmt->rowCount() === 0) {
        sendResponse(['error' => 'Comment not found'], 404);
        return;
    }
    
    
    // TODO: Prepare DELETE query
    $sql = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($sql);
    
    
    // TODO: Bind the :id parameter
    $stmt->bindValue(':id', $commentId);
    
    // TODO: Execute the statement
    try {
        $stmt->execute();
    
        // TODO: Check if delete was successful
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Comment deleted successfully']);
        } else {
            sendResponse(['error' => 'Failed to delete comment'], 500);
        }
    } catch (PDOException $e) {
        sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
    
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    if (!$resource) {
        sendResponse(['error' => 'Resource parameter is required'], 400);
    exit;
    }
    
    
    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if ($id) {
                getAssignmentById($db, $id);
            } else {
                getAllAssignments($db);
            }
            
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            
            if ($assignment_id) {
                getCommentsByAssignment($db, $assignment_id);
            } else {
                sendResponse(['error' => 'assignment_id parameter is required for comments'], 400);
            }
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource'], 400);
            
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            
            createComment($db, $data);
        } else {
            // TODO: Invalid resource, return 400 error
            
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            
            updateAssignment($db, $data);
        } else {
            // TODO: PUT not supported for other resources
            
            sendResponse(['error' => 'PUT method not supported for this resource'], 405);
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            
            $deleteId = !empty($id) ? $id : ($data['id'] ?? null);
            deleteAssignment($db, $deleteId);

        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            
            deleteComment($db, $id);
        } else {
            // TODO: Invalid resource, return 400 error
            
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } else {
        // TODO: Method not supported
        
        sendResponse(['error' => 'HTTP method not supported'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    sendResponse(['error' => 'General error: ' . $e->getMessage()], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    
    // TODO: Ensure data is an array
    
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit();
    
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
    
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    
    return in_array($value, $allowedValues);
    
    
    // TODO: Return the result
    
}

?>
