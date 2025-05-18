<?php
/**
 * Proxy script to forward requests to the Legal AI API
 * This avoids CORS and mixed content issues
 * 
 * DEBUG MODE - Extensive logging enabled
 */

// Create a debug log file specific to this request
$debug_id = uniqid('ai_debug_');
$debug_log = __DIR__ . "/../logs/{$debug_id}.log";

// Enhanced debug logging function
function debug_log($message, $data = null) {
    global $debug_log, $debug_id;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_entry .= "\n" . print_r($data, true);
        } else {
            $log_entry .= "\n" . $data;
        }
    }
    
    // Save to dedicated debug log
    file_put_contents($debug_log, $log_entry . "\n\n", FILE_APPEND);
    
    // Also log to general error log
    error_log("[{$debug_id}] {$message}");
}

// Start debugging
debug_log("=== NEW API REQUEST STARTED ===");
debug_log("Request time: " . date('Y-m-d H:i:s'));
debug_log("Debug ID: {$debug_id}");

// Disable output buffering to prevent timeouts
ob_end_clean();
ob_implicit_flush(true);

// Allow CORS from our domain
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Log request details to help diagnose issues
debug_log("METHOD: " . $_SERVER['REQUEST_METHOD']);
debug_log("REMOTE IP: " . $_SERVER['REMOTE_ADDR']);
debug_log("USER AGENT: " . $_SERVER['HTTP_USER_AGENT']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    debug_log("Handled OPTIONS request");
    exit(0);
}

// Handle only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("ERROR: Invalid method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Define the target API endpoint
$apiEndpoint = 'http://ec2-16-16-78-219.eu-north-1.compute.amazonaws.com:8000/legal-advice';
debug_log("API endpoint: {$apiEndpoint}");

// Get the request body
$requestBody = file_get_contents('php://input');
if (empty($requestBody)) {
    debug_log("ERROR: Empty request body");
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

debug_log("REQUEST BODY:", $requestBody);

// Try to parse as JSON
$jsonData = json_decode($requestBody, true);
if ($jsonData === null) {
    debug_log("ERROR: Invalid JSON: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

debug_log("PARSED REQUEST:", $jsonData);

// Make sure we have a question
if (!isset($jsonData['question'])) {
    debug_log("ERROR: Missing 'question' parameter");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter: question']);
    exit;
}

// Prepare data for API - only send the question parameter
$apiData = json_encode(['question' => $jsonData['question']]);
debug_log("FORWARDING DATA:", $apiData);

// Set up cURL
$ch = curl_init($apiEndpoint);
if (!$ch) {
    debug_log("ERROR: Failed to initialize cURL");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to initialize request']);
    exit;
}

// Configure cURL with verbose debugging
$verbose = fopen('php://temp', 'w+');

// Configure cURL
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $apiData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($apiData)
    ],
    CURLOPT_TIMEOUT => 120,       // 2 minutes timeout
    CURLOPT_CONNECTTIMEOUT => 15, // 15 seconds to establish connection
    CURLOPT_NOSIGNAL => 1,        // Ignore signals that might terminate the request
    CURLOPT_FAILONERROR => false, // Don't fail on HTTP error codes
    CURLOPT_VERBOSE => true,      // Enable verbose output
    CURLOPT_STDERR => $verbose    // Capture verbose output
]);

// Log the start time to track how long the API call takes
$startTime = microtime(true);
debug_log("Starting API request at: " . date('Y-m-d H:i:s'));

// Execute the request
debug_log("Sending request to API");
$response = curl_exec($ch);
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// Get info about the request
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

debug_log("API request completed in {$executionTime} seconds");
debug_log("Response HTTP code: {$httpCode}");
debug_log("Response size: {$size} bytes");
debug_log("Content type: {$contentType}");

// Get verbose curl output
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
debug_log("CURL VERBOSE LOG:", $verboseLog);

// Check for errors
if (curl_errno($ch)) {
    $errorMessage = curl_error($ch);
    $errorCode = curl_errno($ch);
    debug_log("ERROR: cURL error {$errorCode}: {$errorMessage}");
    
    // Special handling for timeout errors
    if ($errorCode == CURLE_OPERATION_TIMEDOUT) {
        debug_log("REQUEST TIMED OUT after {$executionTime} seconds");
        http_response_code(504); // Gateway Timeout
        echo json_encode([
            'error' => 'API request timed out',
            'message' => 'The AI service took too long to respond. Please try a shorter or simpler question.'
        ]);
    } else {
        http_response_code(502); // Bad Gateway
        echo json_encode([
            'error' => 'API connection error',
            'code' => $errorCode,
            'message' => $errorMessage
        ]);
    }
    curl_close($ch);
    exit;
}

// Close cURL
curl_close($ch);

// Check response status
if ($httpCode >= 400) {
    debug_log("ERROR: API returned error status: " . $httpCode);
    debug_log("ERROR RESPONSE:", $response);
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'API error', 
        'status' => $httpCode, 
        'details' => $response
    ]);
    exit;
}

// Log the raw response
debug_log("RAW API RESPONSE:", $response);

// Try to validate JSON response
$responseData = json_decode($response, true);
if ($responseData === null) {
    $jsonError = json_last_error_msg();
    debug_log("WARNING: API returned non-JSON response. Error: " . $jsonError);
    debug_log("INVALID JSON RESPONSE:", $response);
    
    // Handle non-JSON response by wrapping it
    $wrappedResponse = json_encode([
        'response' => 'The AI service returned an invalid response. Please try again.',
        'debug_id' => $debug_id,
        'raw_data' => substr($response, 0, 1000) // Include part of the raw response for debugging
    ]);
    
    debug_log("SENDING WRAPPED RESPONSE:", $wrappedResponse);
    echo $wrappedResponse;
} else {
    // Log the parsed response structure
    debug_log("PARSED JSON RESPONSE:", $responseData);
    
    // Check if the expected 'response' field exists
    if (!isset($responseData['response'])) {
        debug_log("WARNING: Response doesn't contain 'response' field");
        debug_log("Response structure:", array_keys($responseData));
        
        // API appears to use 'result' field instead of 'response'
        if (isset($responseData['result']) && isset($responseData['status'])) {
            debug_log("Found standard structure with 'result' field - converting to expected format");
            
            // Create a properly formatted response
            $fixedResponse = json_encode([
                'response' => $responseData['result'],
                'status' => $responseData['status'],
                'formatted' => true
            ]);
            
            debug_log("SENDING FIXED RESPONSE:", $fixedResponse);
            echo $fixedResponse;
        }
        // Handle other possible field names
        else {
            // Create a fixed structure
            $fixedResponse = json_encode([
                'response' => isset($responseData['answer']) ? $responseData['answer'] : 
                             (isset($responseData['text']) ? $responseData['text'] : 
                             (isset($responseData['content']) ? $responseData['content'] : 
                             (is_string($responseData) ? $responseData : 
                              json_encode($responseData)))),
                'debug_id' => $debug_id,
                'original_structure' => array_keys($responseData)
            ]);
            
            debug_log("SENDING FIXED RESPONSE:", $fixedResponse);
            echo $fixedResponse;
        }
    } else {
        // Pass through the JSON response as-is
        debug_log("API response successful, contains expected 'response' field");
        debug_log("SENDING ORIGINAL RESPONSE");
        echo $response;
    }
}

debug_log("Proxy completed successfully");
debug_log("=== END OF REQUEST ===");
?> 