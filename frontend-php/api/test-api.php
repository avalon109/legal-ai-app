<?php
/**
 * Test script to verify API connectivity
 */

header('Content-Type: text/plain');

echo "Testing API connectivity...\n\n";

// Define the target API endpoint
$apiEndpoint = 'http://ec2-16-16-78-219.eu-north-1.compute.amazonaws.com:8000/legal-advice';

// The test question
$testData = json_encode([
    'question' => 'What are the basic tenant rights in the Netherlands?'
]);

echo "API Endpoint: " . $apiEndpoint . "\n";
echo "Test Data: " . $testData . "\n\n";

// Initialize cURL session
$ch = curl_init($apiEndpoint);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);  
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($testData)
]);

// For debugging, enable verbose output
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "Sending request...\n";

// Execute cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status Code: " . $httpCode . "\n\n";

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose cURL log:\n" . $verboseLog . "\n\n";

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "Raw Response:\n" . $response . "\n\n";
    
    // Try to parse as JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "Parsed Response:\n";
        print_r($data);
    }
}

// Close cURL session
curl_close($ch);
?> 