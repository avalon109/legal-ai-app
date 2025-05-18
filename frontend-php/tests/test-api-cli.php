<?php
/**
 * Command-line test script for API connectivity
 * 
 * Run from terminal: php test-api-cli.php
 */

// Only run from command line
if (php_sapi_name() !== 'cli') {
    die("This script is meant to be run from the command line only.");
}

echo "\033[1;36m=====================================================\033[0m\n";
echo "\033[1;36m   TESTING API CONNECTIVITY - COMMAND LINE VERSION   \033[0m\n";
echo "\033[1;36m=====================================================\033[0m\n\n";

// Define the target API endpoint
$apiEndpoint = 'http://ec2-16-16-78-219.eu-north-1.compute.amazonaws.com:8000/legal-advice';

// The specific test question
$testQuestion = "I think my landlord is charging me too much";
$testData = json_encode([
    'question' => $testQuestion
]);

echo "\033[1;33mAPI Endpoint:\033[0m " . $apiEndpoint . "\n";
echo "\033[1;33mTest Question:\033[0m " . $testQuestion . "\n";
echo "\033[1;33mRequest Body:\033[0m " . $testData . "\n\n";

// Initialize cURL session
$ch = curl_init($apiEndpoint);

if (!$ch) {
    echo "\033[1;31mERROR: Failed to initialize cURL\033[0m\n";
    exit(1);
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

echo "\033[1;32mSending POST request to API...\033[0m\n";
$startTime = microtime(true);

// Execute cURL request
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$timeTaken = round($endTime - $startTime, 2);

echo "Request completed in \033[1;32m{$timeTaken} seconds\033[0m\n";
echo "HTTP Status Code: " . ($httpCode >= 200 && $httpCode < 300 ? "\033[1;32m" : "\033[1;31m") . $httpCode . "\033[0m\n\n";

// Get connection information
$connectionInfo = curl_getinfo($ch);
echo "\033[1;33mConnection Details:\033[0m\n";
echo "  - Primary IP: " . $connectionInfo['primary_ip'] . "\n";
echo "  - Primary Port: " . $connectionInfo['primary_port'] . "\n";
echo "  - Content Type: " . ($connectionInfo['content_type'] ?? 'N/A') . "\n";
echo "  - Request Size: " . $connectionInfo['request_size'] . " bytes\n";
echo "  - Response Size: " . $connectionInfo['size_download'] . " bytes\n";
echo "\n";

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "\033[1;33mVerbose cURL log:\033[0m\n";
echo "\033[0;90m-----------------------------------------------------\033[0m\n";
echo $verboseLog;
echo "\033[0;90m-----------------------------------------------------\033[0m\n\n";

// Check for cURL errors
if (curl_errno($ch)) {
    echo "\033[1;31mcURL ERROR: " . curl_error($ch) . "\033[0m\n";
    echo "\n\033[1;33mTROUBLESHOOTING TIPS:\033[0m\n";
    echo "1. Check if the server has outbound access to the API endpoint\n";
    echo "2. Verify the API endpoint is correct and running\n";
    echo "3. Check firewall settings that might block outbound connections\n";
    echo "4. Try running this test from a different server/network\n";
    
    // Close cURL session
    curl_close($ch);
    exit(1);
}

echo "\033[1;33mAPI RESPONSE:\033[0m\n";
echo "\033[0;90m-----------------------------------------------------\033[0m\n";
echo $response . "\n";
echo "\033[0;90m-----------------------------------------------------\033[0m\n\n";

// Try to parse as JSON
$data = json_decode($response, true);
if ($data !== null) {
    echo "\033[1;33mPARSED RESPONSE:\033[0m\n";
    echo "\033[0;90m-----------------------------------------------------\033[0m\n";
    print_r($data);
    echo "\033[0;90m-----------------------------------------------------\033[0m\n";
    
    if (isset($data['response'])) {
        echo "\n\033[1;33mAI ANSWER:\033[0m\n";
        echo "\033[0;90m-----------------------------------------------------\033[0m\n";
        echo $data['response'] . "\n";
        echo "\033[0;90m-----------------------------------------------------\033[0m\n";
    } else {
        echo "\n\033[1;31mWARNING: Response does not contain 'response' field\033[0m\n";
    }
} else {
    echo "\033[1;31mWARNING: Could not parse response as JSON. Error: " . json_last_error_msg() . "\033[0m\n";
}

// Close cURL session
curl_close($ch);

echo "\n\033[1;36m=====================================================\033[0m\n";
echo "\033[1;36m                   TEST COMPLETED                     \033[0m\n";
echo "\033[1;36m=====================================================\033[0m\n";
?> 