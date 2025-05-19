<?php
// Enable detailed error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

// Start session for user auth
session_start();

// Debug logging
$debug_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'get_params' => $_GET,
    'server_software' => $_SERVER['SERVER_SOFTWARE']
];

// User authentication check
function getUserId() {
    // First check session
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    // Then check for token authorization
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (!$auth_header) {
        return null;
    }
    
    // Remove 'Bearer ' prefix if present
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
    } else {
        $token = $auth_header;
    }
    
    // Validate token with database
    $db = Database::getInstance();
    $stmt = $db->query("SELECT user_id FROM user_sessions WHERE token = ? AND expires_at > NOW()", [$token]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['user_id'];
}

// Get username from user_id
function getUsername($userId) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT username FROM users WHERE id = ?", [$userId]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['username'];
}

// Generate a unique ID for files
function generateFileId() {
    return uniqid('file_', true);
}

// Store file record in database
function storeFileRecord($userId, $filename, $originalFilename, $filePath, $fileSize, $mimeType, $description = null) {
    $db = Database::getInstance();
    $fileId = generateFileId();
    
    $sql = "INSERT INTO user_files (id, user_id, filename, original_filename, file_path, file_size, mime_type, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $db->query($sql, [
        $fileId,
        $userId,
        $filename,
        $originalFilename,
        $filePath,
        $fileSize,
        $mimeType,
        $description
    ]);
    
    return $fileId;
}

// Get file records for user
function getUserFiles($userId) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM user_files WHERE user_id = ? AND is_deleted = 0 ORDER BY upload_date DESC";
    $stmt = $db->query($sql, [$userId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mark file as deleted in database
function markFileDeleted($fileId, $userId) {
    $db = Database::getInstance();
    $sql = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
    $stmt = $db->query($sql, [$fileId, $userId]);
    
    return $stmt->rowCount() > 0;
}

// Get file by name for a specific user
function getFileByName($userId, $filename) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM user_files WHERE user_id = ? AND filename = ? AND is_deleted = 0";
    $stmt = $db->query($sql, [$userId, $filename]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get file by ID for a specific user
function getFileById($userId, $fileId) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM user_files WHERE user_id = ? AND id = ? AND is_deleted = 0";
    $stmt = $db->query($sql, [$userId, $fileId]);
    
    if ($stmt->rowCount() === 0) {
        return null;
    }
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Create user upload directory if it doesn't exist
function ensureUserDirectory($username) {
    // Ensure uploads directory exists first
    $uploadsDir = __DIR__ . '/../uploads';
    
    // Check if uploads directory exists
    if (!file_exists($uploadsDir)) {
        // Try to create with more permissive permissions for production environment
        if (!mkdir($uploadsDir, 0777, true)) {
            $error = error_get_last();
            throw new Exception("Failed to create uploads directory: " . ($error ? $error['message'] : 'Unknown error'));
        }
        // After creation, explicitly set permissions to ensure writability
        chmod($uploadsDir, 0777);
    } else {
        // Make sure existing directory is writable
        if (!is_writable($uploadsDir)) {
            // Try to update permissions
            chmod($uploadsDir, 0777);
            if (!is_writable($uploadsDir)) {
                throw new Exception("Uploads directory exists but is not writable");
            }
        }
    }
    
    // Sanitize username for directory name
    $safeUsername = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $username);
    
    // Create user-specific directory using proper path separator
    $userDir = $uploadsDir . DIRECTORY_SEPARATOR . $safeUsername;
    
    if (!file_exists($userDir)) {
        // Try to create with more permissive permissions
        if (!mkdir($userDir, 0777, true)) {
            $error = error_get_last();
            throw new Exception("Failed to create user directory: " . ($error ? $error['message'] : 'Unknown error'));
        }
        // After creation, explicitly set permissions
        chmod($userDir, 0777);
    } else {
        // Make sure existing directory is writable
        if (!is_writable($userDir)) {
            // Try to update permissions
            chmod($userDir, 0777);
            if (!is_writable($userDir)) {
                throw new Exception("User directory exists but is not writable");
            }
        }
    }
    
    return $userDir;
}

try {
    // Log all inputs for debugging
    $debug_log['headers'] = getallheaders();
    $debug_log['post_data'] = $_POST;
    $debug_log['files_data'] = $_FILES;
    $debug_log['session_data'] = $_SESSION;
    
    // Authenticate user
    $userId = getUserId();
    if (!$userId) {
        throw new Exception('Authentication required');
    }
    
    $username = getUsername($userId);
    if (!$username) {
        throw new Exception('User not found');
    }
    
    $debug_log['user_id'] = $userId;
    $debug_log['username'] = $username;
    
    // Ensure user directory exists
    $userDir = ensureUserDirectory($username);
    $debug_log['user_dir'] = $userDir;
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Handle different actions based on request method
    switch ($method) {
        case 'GET':
            // List files action
            if (!isset($_GET['action']) || $_GET['action'] === 'list') {
                $debug_log['action'] = 'list_files';
                
                // Get files from database instead of filesystem
                $files = getUserFiles($userId);
                $formattedFiles = [];
                
                foreach ($files as $file) {
                    $formattedFiles[] = [
                        'id' => $file['id'],
                        'name' => $file['original_filename'],
                        'size' => $file['file_size'],
                        'type' => $file['mime_type'],
                        'description' => $file['description'],
                        'uploaded' => $file['upload_date'],
                        'path' => $file['file_path']
                    ];
                }
                
                $response = [
                    'success' => true,
                    'files' => $formattedFiles
                ];
                $debug_log['response'] = $response;
            }
            // Get specific file info
            else if (isset($_GET['action']) && $_GET['action'] === 'info' && isset($_GET['file'])) {
                $debug_log['action'] = 'file_info';
                
                $filename = basename($_GET['file']);
                $filePath = $userDir . DIRECTORY_SEPARATOR . $filename;
                
                if (!file_exists($filePath)) {
                    throw new Exception('File not found');
                }
                
                $fileInfo = [
                    'name' => $filename,
                    'size' => filesize($filePath),
                    'type' => mime_content_type($filePath),
                    'uploaded' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'path' => 'uploads/' . $username . '/' . $filename
                ];
                
                $response = [
                    'success' => true,
                    'file' => $fileInfo
                ];
                $debug_log['response'] = $response;
            }
            else {
                throw new Exception('Invalid action');
            }
            break;
            
        case 'POST':
            // Upload file
            if (!isset($_GET['action']) || $_GET['action'] === 'upload') {
                $debug_log['action'] = 'upload_file';
                
                // Check if file was uploaded
                if (!isset($_FILES['file'])) {
                    throw new Exception('No file uploaded');
                }
                
                $file = $_FILES['file'];
                $debug_log['uploaded_file_data'] = $file;
                
                // Check for upload errors
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('File upload error: ' . $file['error']);
                }
                
                // Validate file size (max 10MB)
                if ($file['size'] > 10 * 1024 * 1024) {
                    throw new Exception('File too large (max 10MB)');
                }
                
                // Get optional description
                $description = isset($_POST['description']) ? $_POST['description'] : null;
                $debug_log['description'] = $description;
                
                // Generate a safe filename
                $originalFilename = $file['name'];
                $filename = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $originalFilename);
                
                // Check if file already exists
                $targetPath = $userDir . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($targetPath)) {
                    // Append timestamp to make filename unique
                    $filenameParts = pathinfo($filename);
                    $filename = $filenameParts['filename'] . '_' . time() . '.' . $filenameParts['extension'];
                    $targetPath = $userDir . DIRECTORY_SEPARATOR . $filename;
                }
                
                $debug_log['target_path'] = $targetPath;
                $debug_log['tmp_name'] = $file['tmp_name'];
                $debug_log['tmp_file_exists'] = file_exists($file['tmp_name']);
                
                // Check that temp file exists
                if (!file_exists($file['tmp_name'])) {
                    throw new Exception('Temporary uploaded file not found: ' . $file['tmp_name']);
                }
                
                // Check target directory is writable
                $targetDir = dirname($targetPath);
                if (!is_writable($targetDir)) {
                    // Try to fix permissions
                    chmod($targetDir, 0777);
                    if (!is_writable($targetDir)) {
                        throw new Exception('Target directory is not writable: ' . $targetDir);
                    }
                }
                
                // Move uploaded file to user directory with explicit error handling
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $moveError = error_get_last();
                    $debug_log['move_error'] = $moveError;
                    
                    // Try a fallback approach with copy+unlink
                    if (copy($file['tmp_name'], $targetPath)) {
                        unlink($file['tmp_name']);
                        // Successfully copied
                        $debug_log['fallback_copy'] = true;
                    } else {
                        $copyError = error_get_last();
                        $debug_log['copy_error'] = $copyError;
                        throw new Exception('Failed to save uploaded file. PHP Error: ' . ($moveError ? $moveError['message'] : 'Unknown error'));
                    }
                }
                
                // Set proper permissions on the file
                chmod($targetPath, 0666);
                
                // Store file record in database
                $webPath = 'uploads/' . $username . '/' . $filename;
                $fileId = storeFileRecord(
                    $userId,
                    $filename,
                    $originalFilename,
                    $webPath,
                    $file['size'],
                    mime_content_type($targetPath),
                    $description
                );
                
                // Get file info
                $fileInfo = [
                    'id' => $fileId,
                    'name' => $originalFilename,
                    'size' => $file['size'],
                    'type' => mime_content_type($targetPath),
                    'description' => $description,
                    'uploaded' => date('Y-m-d H:i:s'),
                    'path' => $webPath
                ];
                
                $response = [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file' => $fileInfo
                ];
                $debug_log['response'] = $response;
            }
            else {
                throw new Exception('Invalid action');
            }
            break;
            
        case 'DELETE':
            // Delete file
            if (isset($_GET['action']) && $_GET['action'] === 'delete') {
                $debug_log['action'] = 'delete_file';
                
                // Check if we have the file ID
                if (!isset($_GET['id'])) {
                    throw new Exception('File ID is required');
                }
                
                $fileId = $_GET['id'];
                $debug_log['file_id'] = $fileId;
                
                // Get file record from database by ID
                $fileRecord = getFileById($userId, $fileId);
                
                if (!$fileRecord) {
                    throw new Exception('File not found in database');
                }
                
                $debug_log['file_record'] = $fileRecord;
                
                // Get the filename from the record
                $filename = $fileRecord['filename'];
                $filePath = $userDir . DIRECTORY_SEPARATOR . $filename;
                
                $debug_log['file_path'] = $filePath;
                
                if (!file_exists($filePath)) {
                    // If file doesn't exist on disk but is in database, still mark as deleted
                    $debug_log['file_exists_on_disk'] = false;
                    markFileDeleted($fileId, $userId);
                    
                    $response = [
                        'success' => true,
                        'message' => 'File record deleted successfully (file not found on disk)'
                    ];
                } else {
                    $debug_log['file_exists_on_disk'] = true;
                    
                    // Delete file from disk
                    if (!unlink($filePath)) {
                        $deleteError = error_get_last();
                        $debug_log['delete_error'] = $deleteError;
                        throw new Exception('Failed to delete file: ' . ($deleteError ? $deleteError['message'] : 'Unknown error'));
                    }
                    
                    // Mark file as deleted in database
                    markFileDeleted($fileId, $userId);
                    
                    $response = [
                        'success' => true,
                        'message' => 'File deleted successfully'
                    ];
                }
                
                $debug_log['response'] = $response;
            }
            else {
                throw new Exception('Invalid action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
    // Add debug info in development mode
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        $response['_debug'] = $debug_log;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Include debug info on errors
    $error_response['_debug'] = $debug_log;
    $error_response['_debug']['exception'] = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    // Log errors to a file
    $log_message = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    $log_message .= "Debug data: " . json_encode($debug_log, JSON_PRETTY_PRINT) . "\n\n";
    file_put_contents(__DIR__ . '/../logs/upload_errors.log', $log_message, FILE_APPEND);
    
    echo json_encode($error_response);
} 