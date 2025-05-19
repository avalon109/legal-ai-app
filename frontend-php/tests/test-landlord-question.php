<?php
/**
 * Test script to verify API connectivity with a specific landlord question
 */

header('Content-Type: text/plain');
echo "=====================================================\n";
echo "   TESTING API CONNECTIVITY - LANDLORD QUESTION     \n";
echo "=====================================================\n\n";

// Define the target API endpoint
$apiEndpoint = 'http://ec2-16-16-78-219.eu-north-1.compute.amazonaws.com:8000/legal-advice';

// The specific test question
$testQuestion = "I think my landlord is charging me too much";
$testData = json_encode([
    'question' => $testQuestion
]);

echo "API Endpoint: " . $apiEndpoint . "\n";
echo "Test Question: " . $testQuestion . "\n";
echo "Request Body: " . $testData . "\n\n";

// Initialize cURL session
$ch = curl_init($apiEndpoint);

if (!$ch) {
    echo "ERROR: Failed to initialize cURL\n";
    exit;
}

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true); // Explicitly using POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($testData)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for API response
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// For debugging, enable verbose output
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "Sending POST request to API...\n";
$startTime = microtime(true);

// Execute cURL request
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$timeTaken = round($endTime - $startTime, 2);

echo "Request completed in {$timeTaken} seconds\n";
echo "HTTP Status Code: " . $httpCode . "\n\n";

// Get connection information
$connectionInfo = curl_getinfo($ch);
echo "Connection Details:\n";
echo "  - Primary IP: " . $connectionInfo['primary_ip'] . "\n";
echo "  - Primary Port: " . $connectionInfo['primary_port'] . "\n";
echo "  - Local IP: " . ($connectionInfo['local_ip'] ?? 'N/A') . "\n";
echo "  - Local Port: " . ($connectionInfo['local_port'] ?? 'N/A') . "\n";
echo "  - Content Type: " . ($connectionInfo['content_type'] ?? 'N/A') . "\n";
echo "  - Request Size: " . $connectionInfo['request_size'] . " bytes\n";
echo "  - Response Size: " . $connectionInfo['size_download'] . " bytes\n";
echo "\n";

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose cURL log:\n";
echo "-----------------------------------------------------\n";
echo $verboseLog;
echo "-----------------------------------------------------\n\n";

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL ERROR: " . curl_error($ch) . "\n";
    echo "\nTROUBLESHOOTING TIPS:\n";
    echo "1. Check if the server has outbound access to the API endpoint\n";
    echo "2. Verify the API endpoint is correct and running\n";
    echo "3. Check firewall settings that might block outbound connections\n";
    echo "4. Try running this test from a different server/network\n";
} else {
    echo "API RESPONSE:\n";
    echo "-----------------------------------------------------\n";
    echo $response . "\n";
    echo "-----------------------------------------------------\n\n";
    
    // Try to parse as JSON
    $data = json_decode($response, true);
    if ($data !== null) {
        echo "PARSED RESPONSE:\n";
        echo "-----------------------------------------------------\n";
        print_r($data);
        echo "-----------------------------------------------------\n";
        
        if (isset($data['response'])) {
            echo "\nAI ANSWER:\n";
            echo "-----------------------------------------------------\n";
            echo $data['response'] . "\n";
            echo "-----------------------------------------------------\n";
        }
    } else {
        echo "WARNING: Could not parse response as JSON. Error: " . json_last_error_msg() . "\n";
    }
}

// Close cURL session
curl_close($ch);

echo "\n=====================================================\n";
echo "                   TEST COMPLETED                     \n";
echo "=====================================================\n";
?> 