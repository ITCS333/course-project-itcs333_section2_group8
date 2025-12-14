<?php
/**
 * Discussion Board API
 * Handles CRUD operations for topics and replies.
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

// --- Helper Functions ------------------------------------------------------

function sendResponse(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}

function sanitizeInput(?string $value): string {
    return trim(filter_var($value ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function validateEmail(?string $email): ?string {
    if (!$email) {
        return null;
    }
    $filtered = filter_var($email, FILTER_VALIDATE_EMAIL);
    return $filtered ?: null;
}

function authenticateAdmin(PDO $db, array $data): bool {
    if (!empty($_SESSION['logged_in'])) {
        return true;
    }

    $emailValue = $data['admin_email'] ?? '';
    $password = $data['admin_password'] ?? '';

    $validatedEmail = validateEmail($emailValue);
    if (!$validatedEmail || !$password) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $validatedEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            return true;
        }
    } catch (PDOException $e) {
        error_log('Admin authentication failed: ' . $e->getMessage());
    }

    return false;
}

function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// --- Topic Queries ---------------------------------------------------------

function getAllTopics(PDO $db): void {
    try {
        $sql = 'SELECT topic_id, subject, message, author, DATE(created_at) AS created_at FROM topics';
        $params = [];

        if (!empty($_GET['search'])) {
            $sql .= ' WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search';
            $params[':search'] = '%' . sanitizeInput($_GET['search']) . '%';
        }

        $allowedSort = ['subject', 'author', 'created_at'];
        $sortField = in_array($_GET['sort'] ?? '', $allowedSort, true) ? $_GET['sort'] : 'created_at';
        $order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$sortField} {$order}";

        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(200, ['success' => true, 'data' => $topics]);
    } catch (PDOException $e) {
        error_log('Error fetching topics: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to fetch topics.']);
    }
}

function getTopicById(PDO $db, ?string $topicId): void {
    if (!$topicId) {
        sendResponse(400, ['success' => false, 'message' => 'Topic ID is required.']);
    }

    try {
        $stmt = $db->prepare('SELECT topic_id, subject, message, author, DATE(created_at) AS created_at FROM topics WHERE topic_id = :topic_id LIMIT 1');
        $stmt->execute([':topic_id' => sanitizeInput($topicId)]);
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($topic) {
            sendResponse(200, ['success' => true, 'data' => $topic]);
        }

        sendResponse(404, ['success' => false, 'message' => 'Topic not found.']);
    } catch (PDOException $e) {
        error_log('Error fetching topic: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to fetch the topic.']);
    }
}

function createTopic(PDO $db, array $data): void {
    $requiredFields = ['topic_id', 'subject', 'message', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(400, ['success' => false, 'message' => 'Missing required field: ' . $field]);
        }
    }

    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    $authorEmail = $data['author_email'] ?? null;
    if ($authorEmail && !validateEmail($authorEmail)) {
        sendResponse(400, ['success' => false, 'message' => 'Invalid author email.']);
    }

    try {
        $checkStmt = $db->prepare('SELECT id FROM topics WHERE topic_id = :topic_id');
        $checkStmt->execute([':topic_id' => $topicId]);
        if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
            sendResponse(409, ['success' => false, 'message' => 'Topic ID already exists.']);
        }

        $stmt = $db->prepare('INSERT INTO topics (topic_id, subject, message, author) VALUES (:topic_id, :subject, :message, :author)');
        $stmt->execute([
            ':topic_id' => $topicId,
            ':subject' => $subject,
            ':message' => $message,
            ':author' => $author
        ]);

        sendResponse(201, ['success' => true, 'topic_id' => $topicId]);
    } catch (PDOException $e) {
        error_log('Error creating topic: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to create topic.']);
    }
}

function updateTopic(PDO $db, array $data): void {
    $topicId = sanitizeInput($data['topic_id'] ?? '');
    if (!$topicId) {
        sendResponse(400, ['success' => false, 'message' => 'Topic ID is required.']);
    }

    try {
        $existsStmt = $db->prepare('SELECT id FROM topics WHERE topic_id = :topic_id');
        $existsStmt->execute([':topic_id' => $topicId]);
        if (!$existsStmt->fetch(PDO::FETCH_ASSOC)) {
            sendResponse(404, ['success' => false, 'message' => 'Topic not found.']);
        }

        $updates = [];
        $params = [':topic_id' => $topicId];

        if (!empty($data['subject'])) {
            $updates[] = 'subject = :subject';
            $params[':subject'] = sanitizeInput($data['subject']);
        }

        if (!empty($data['message'])) {
            $updates[] = 'message = :message';
            $params[':message'] = sanitizeInput($data['message']);
        }

        if (!empty($data['author'])) {
            $updates[] = 'author = :author';
            $params[':author'] = sanitizeInput($data['author']);
        }

        if (!$updates) {
            sendResponse(400, ['success' => false, 'message' => 'Nothing to update.']);
        }

        $sql = 'UPDATE topics SET ' . implode(', ', $updates) . ' WHERE topic_id = :topic_id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        sendResponse(200, ['success' => true, 'message' => 'Topic updated.']);
    } catch (PDOException $e) {
        error_log('Error updating topic: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to update topic.']);
    }
}

function deleteTopic(PDO $db, ?string $topicId): void {
    if (!$topicId) {
        sendResponse(400, ['success' => false, 'message' => 'Topic ID is required.']);
    }

    try {
        $stmt = $db->prepare('DELETE FROM topics WHERE topic_id = :topic_id');
        $stmt->execute([':topic_id' => sanitizeInput($topicId)]);

        if ($stmt->rowCount() === 0) {
            sendResponse(404, ['success' => false, 'message' => 'Topic not found.']);
        }

        sendResponse(200, ['success' => true, 'message' => 'Topic deleted.']);
    } catch (PDOException $e) {
        error_log('Error deleting topic: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to delete topic.']);
    }
}

// --- Reply Queries ---------------------------------------------------------

function getRepliesByTopic(PDO $db, ?string $topicId): void {
    if (!$topicId) {
        sendResponse(400, ['success' => false, 'message' => 'Topic ID is required.']);
    }

    try {
        $stmt = $db->prepare('SELECT reply_id, topic_id, text, author, DATE(created_at) AS created_at FROM replies WHERE topic_id = :topic_id ORDER BY created_at ASC');
        $stmt->execute([':topic_id' => sanitizeInput($topicId)]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse(200, ['success' => true, 'data' => $replies]);
    } catch (PDOException $e) {
        error_log('Error fetching replies: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to fetch replies.']);
    }
}

function createReply(PDO $db, array $data): void {
    $requiredFields = ['topic_id', 'reply_id', 'text', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(400, ['success' => false, 'message' => 'Missing required field: ' . $field]);
        }
    }

    $topicId = sanitizeInput($data['topic_id']);
    $replyId = sanitizeInput($data['reply_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    $email = $data['author_email'] ?? null;
    if ($email && !validateEmail($email)) {
        sendResponse(400, ['success' => false, 'message' => 'Invalid author email.']);
    }

    try {
        $stmt = $db->prepare('INSERT INTO replies (reply_id, topic_id, text, author) VALUES (:reply_id, :topic_id, :text, :author)');
        $stmt->execute([
            ':reply_id' => $replyId,
            ':topic_id' => $topicId,
            ':text' => $text,
            ':author' => $author
        ]);

        sendResponse(201, ['success' => true, 'reply_id' => $replyId]);
    } catch (PDOException $e) {
        error_log('Error creating reply: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to create reply.']);
    }
}

function deleteReply(PDO $db, ?string $replyId): void {
    if (!$replyId) {
        sendResponse(400, ['success' => false, 'message' => 'Reply ID is required.']);
    }

    try {
        $stmt = $db->prepare('DELETE FROM replies WHERE reply_id = :reply_id');
        $stmt->execute([':reply_id' => sanitizeInput($replyId)]);

        if ($stmt->rowCount() === 0) {
            sendResponse(404, ['success' => false, 'message' => 'Reply not found.']);
        }

        sendResponse(200, ['success' => true, 'message' => 'Reply deleted.']);
    } catch (PDOException $e) {
        error_log('Error deleting reply: ' . $e->getMessage());
        sendResponse(500, ['success' => false, 'message' => 'Unable to delete reply.']);
    }
}

// --- Main Request Handling -------------------------------------------------

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    sendResponse(500, ['success' => false, 'message' => 'Database connection failed.']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $_GET['resource'] ?? 'topics';
$bodyData = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? getRequestBody() : [];

switch ($resource) {
    case 'topics':
        if ($method === 'GET') {
            $topicId = $_GET['id'] ?? null;
            if ($topicId) {
                getTopicById($db, $topicId);
            }
            getAllTopics($db);
        }

        if ($method === 'POST') {
            if (!authenticateAdmin($db, $bodyData)) {
                sendResponse(401, ['success' => false, 'message' => 'Unauthorized']);
            }
            createTopic($db, $bodyData);
        }

        if ($method === 'PUT') {
            if (!authenticateAdmin($db, $bodyData)) {
                sendResponse(401, ['success' => false, 'message' => 'Unauthorized']);
            }
            updateTopic($db, $bodyData);
        }

        if ($method === 'DELETE') {
            if (!authenticateAdmin($db, $bodyData)) {
                sendResponse(401, ['success' => false, 'message' => 'Unauthorized']);
            }
            $topicId = $bodyData['topic_id'] ?? ($_GET['id'] ?? null);
            deleteTopic($db, $topicId);
        }

        sendResponse(405, ['success' => false, 'message' => 'Method not allowed for topics.']);
        break;

    case 'replies':
        if ($method === 'GET') {
            $topicId = $_GET['topic_id'] ?? null;
            getRepliesByTopic($db, $topicId);
        }

        if ($method === 'POST') {
            if (!authenticateAdmin($db, $bodyData)) {
                sendResponse(401, ['success' => false, 'message' => 'Unauthorized']);
            }
            createReply($db, $bodyData);
        }

        if ($method === 'DELETE') {
            if (!authenticateAdmin($db, $bodyData)) {
                sendResponse(401, ['success' => false, 'message' => 'Unauthorized']);
            }
            $replyId = $bodyData['reply_id'] ?? ($_GET['id'] ?? null);
            deleteReply($db, $replyId);
        }

        sendResponse(405, ['success' => false, 'message' => 'Method not allowed for replies.']);
        break;

    default:
        sendResponse(400, ['success' => false, 'message' => 'Unknown resource.']);
}
