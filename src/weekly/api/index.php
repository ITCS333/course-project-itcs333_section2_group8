<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json; charset=utf-8');
// In development allow all origins; in production restrict this to your domain(s)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');



// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    // no body needed for preflight
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
$databaseIncludePath = __DIR__ . '/config/Database.php';
if (file_exists($databaseIncludePath)) {
    require_once $databaseIncludePath;
} else {
    // Try a common alternative path (adjust as needed)
    if (file_exists(__DIR__ . '/../config/Database.php')) {
        require_once __DIR__ . '/../config/Database.php';
    } else {
        // We will still continue; later code will handle missing $db gracefully.
        // For now, do not fatal error because user requested incremental changes.
    }
}


// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$db = null;
if (class_exists('Database')) {
    try {
        $database = new Database();
        // Expect Database::getConnection() to return a PDO instance
        if (method_exists($database, 'getConnection')) {
            $db = $database->getConnection();
        } else {
            // getConnection method missing; we don't fatal here to keep incremental work possible
            // but set $db = null and allow later code to return helpful error if DB ops are attempted.
            $db = null;
        }
    } catch (Exception $e) {
        // Can't use helper sendError yet (defined later), so return minimal JSON and exit
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
        exit;
    }
}


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawBody = file_get_contents('php://input');
$body = null;
if ($rawBody) {
    // Try decode; if decode fails, $body will be null and we can handle it later
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $body = $decoded;
    } else {
        // Not JSON: keep raw string in case caller sent form-encoded or other content
        $body = $rawBody;
    }
}
$requestData = is_array($body) ? $body : [];


// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? trim($_GET['resource']) : (isset($_GET['r']) ? trim($_GET['r']) : 'weeks');
$resource = strtolower($resource);

$GLOBALS['db'] = $db;
$GLOBALS['request_method'] = $method;
$GLOBALS['request_body'] = $body;
$GLOBALS['resource'] = $resource;
$GLOBALS['request_data'] = $requestData;

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'start_date';
    $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
        $query = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
     $params = [];
    if ($search) {
        $query .= " WHERE title LIKE ? OR description LIKE ?";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'start_date';
    }
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
        if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    // TODO: Add ORDER BY clause to the query
        $query .= " ORDER BY $sort $order";

    // TODO: Prepare the SQL query using PDO
        $stmt = $db->prepare($query);

    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
     foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }
    
    // TODO: Execute the query
        $stmt->execute();

    // TODO: Fetch all results as an associative array
        $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($weeks as &$week) {
        $decodedLinks = json_decode($week['links'], true);
        $week['links'] = is_array($decodedLinks) ? $decodedLinks : [];
    }
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
      sendResponse([
        'success' => true,
        'data' => $weeks
    ]);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        return sendError("week_id is required", 400);
    }
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
     $sql = "SELECT week_id, title, start_date, description, links, created_at 
            FROM weeks WHERE week_id = ?";
    // TODO: Bind the week_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $weekId);
    // TODO: Execute the query
        $stmt->execute();

    // TODO: Fetch the result
        $week = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
     if ($week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
        return sendResponse([
            "success" => true,
            "data" => $week
        ], 200);
    } else {
        return sendError("Week not found", 404);
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['week_id']) ||
        empty($data['title']) ||
        empty($data['start_date']) ||
        empty($data['description'])
    ) {
        return sendError("Required fields: week_id, title, start_date, description", 400);
    }

    
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
     $weekId = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
     if (!validateDate($data['start_date'])) {
        return sendError("Invalid date format. Use YYYY-MM-DD", 400);
    }
    $startDate = $data['start_date'];

    
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
     $check = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if ($check->fetch()) {
        return sendError("week_id already exists", 409);
    }
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
      $links = isset($data['links']) && is_array($data['links'])
        ? json_encode($data['links'])
        : json_encode([]);
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
     $sql = "INSERT INTO weeks (week_id, title, start_date, description, links)
            VALUES (?, ?, ?, ?, ?)";
    // TODO: Bind parameters
        $stmt = $db->prepare($sql);

    // TODO: Execute the query
        $success = $stmt->execute([$weekId, $title, $startDate, $description, $links]);

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if ($success) {
        return sendResponse([
            "success" => true,
            "message" => "Week created successfully",
            "data" => [
                "week_id" => $weekId,
                "title" => $title,
                "start_date" => $startDate,
                "description" => $description,
                "links" => json_decode($links, true)
            ]
        ], 201);
    } else {
        return sendError("Failed to create week", 500);
    }
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!isset($data["week_id"])) {
        return sendError("week_id is required", 400);
    }
    $week_id = $data["week_id"];
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $check = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
    $check->execute([$week_id]);
    if (!$check->fetch()) {
        return sendError("Week not found", 404);
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $updateFields = [];
    $values = [];
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
    // title
    if (isset($data["title"])) {
        $updateFields[] = "title = ?";
        $values[] = trim($data["title"]);
    }

    // start_date
    if (isset($data["start_date"])) {
        $date = DateTime::createFromFormat("Y-m-d", $data["start_date"]);
        if (!$date) {
            return sendError("Invalid start_date format", 400);
        }
        $updateFields[] = "start_date = ?";
        $values[] = $data["start_date"];
    }

    // description
    if (isset($data["description"])) {
        $updateFields[] = "description = ?";
        $values[] = trim($data["description"]);
    }

    // links
    if (isset($data["links"])) {
        if (!is_array($data["links"])) {
            return sendError("links must be an array", 400);
        }
        $updateFields[] = "links = ?";
        $values[] = json_encode($data["links"]);
    }

    
    // TODO: If no fields to update, return error response with 400 status
    if (empty($updateFields)) {
        return sendError("No fields provided to update", 400);
    }
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";

    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
        $sql = "UPDATE weeks SET " . implode(", ", $updateFields) . " WHERE week_id = ?";

    // TODO: Prepare the query
        $stmt = $db->prepare($sql);

    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
        $values[] = $week_id;

    // TODO: Execute the query
        $success = $stmt->execute($values);

    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if ($success) {
        return getWeekById($db, $week_id);
    }

    return sendError("Failed to update week", 500);
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        return jsonResponse(false, "week_id is required", 400);
    }
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $check = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if ($check->rowCount() === 0) {
        return jsonResponse(false, "Week not found", 404);
    }
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
        $deleteComments = $db->prepare("DELETE FROM comments WHERE week_id = ?");

    // TODO: Execute comment deletion query
        $deleteComments->execute([$weekId]);

    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
        $deleteWeek = $db->prepare("DELETE FROM weeks WHERE week_id = ?");

    // TODO: Bind the week_id parameter
        // ><(Already bound in execute below)

    
    // TODO: Execute the query
        $deleteWeek->execute([$weekId]);

    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if ($deleteWeek->rowCount() > 0) {
        return jsonResponse(true, "Week and related comments deleted successfully");
    } else {
        return jsonResponse(false, "Failed to delete week", 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!$weekId) {
        return jsonResponse(false, "week_id is required", 400);
    }
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
        $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC");

    // TODO: Bind the week_id parameter
        $stmt->execute([$weekId]);

    // TODO: Execute the query
        // ><(Execution done above)

    // TODO: Fetch all results as an associative array
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
        return jsonResponse(true, $comments);

}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (!isset($data["week_id"], $data["author"], $data["text"])) {
        return jsonResponse(false, "week_id, author, and text are required", 400);
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = trim($data["week_id"]);
    $author = trim($data["author"]);
    $text = trim($data["text"]);
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if ($text === "") {
        return jsonResponse(false, "Comment text cannot be empty", 400);
    }
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $checkWeek = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $checkWeek->execute([$weekId]);
    if ($checkWeek->rowCount() === 0) {
        return jsonResponse(false, "Week not found", 404);
    }
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
        $insert = $db->prepare("INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)");

    // TODO: Bind parameters
        //  >< Done inside execute()

    // TODO: Execute the query
        $success = $insert->execute([$weekId, $author, $text]);

    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    if ($success) {
        $id = $db->lastInsertId();
        return jsonResponse(true, [
            "id" => $id,
            "week_id" => $weekId,
            "author" => $author,
            "text" => $text
        ], 201);
    } else {
        return jsonResponse(false, "Failed to create comment", 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (!$commentId) {
        return jsonResponse(false, "Comment id is required", 400);
    }
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $check = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $check->execute([$commentId]);
    if ($check->rowCount() === 0) {
        return jsonResponse(false, "Comment not found", 404);
    }
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
        $delete = $db->prepare("DELETE FROM comments WHERE id = ?");

    // TODO: Bind the id parameter
        // <> Done in execute()

    // TODO: Execute the query
        $delete->execute([$commentId]);

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($delete->rowCount() > 0) {
        return jsonResponse(true, "Comment deleted successfully");
    } else {
        return jsonResponse(false, "Failed to delete comment", 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
        $resource = $GLOBALS['resource'] ?? 'weeks';

    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if (isset($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
                        createWeek($db, $requestData);

        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
                       updateWeek($db, $requestData);

        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $weekId = $_GET['week_id'] ?? ($requestData['week_id'] ?? null);
            deleteWeek($db, $weekId);
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
                        sendError("Method not allowed for resource 'weeks'.", 405);

        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
                        createComment($db, $requestData);

        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = $_GET['id'] ?? ($requestData['id'] ?? null);
            deleteComment($db, $commentId);
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
                        sendError("Method not allowed for resource 'comments'.", 405);

        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
                sendError("Invalid resource. Use 'weeks' or 'comments'.", 400);

    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
        sendError("Database error occurred", 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
        sendError("An unexpected error occurred", 500);

}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
        http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    // Use json_encode($data)
        echo json_encode($data);

    // TODO: Exit to prevent further execution
        exit;

}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $response = [
        'success' => false,
        'error' => $message
    ];
    
    // TODO: Call sendResponse() with the error array and status code
        sendResponse($response, $statusCode);

}

/**
 * Helper function to send success/error payloads with consistent shape
 *
 * @param bool $success
 * @param mixed $payload
 * @param int $statusCode
 */
function jsonResponse($success, $payload = null, $statusCode = 200) {
    $response = [
        'success' => $success
    ];

    if ($payload !== null) {
        if ($success) {
            if (is_string($payload)) {
                $response['message'] = $payload;
            } else {
                $response['data'] = $payload;
            }
        } else {
            $response['error'] = $payload;
        }
    }

    sendResponse($response, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
        $data = trim($data);

    // TODO: Strip HTML tags using strip_tags()
        $data = strip_tags($data);

    // TODO: Convert special characters using htmlspecialchars()
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // TODO: Return sanitized data
        return $data;

}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
        return in_array($field, $allowedFields);

}

?>
