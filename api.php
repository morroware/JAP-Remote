<?php
// api.php (V2) - IR Blaster API Handler

// Enable error reporting and display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send an API request using cURL
function sendApiRequest($url, $payload) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain',
        'User-Agent: JustOS API Tester'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

// Function to load payloads from a text file
function loadPayloads($filename) {
    $payloads = [];
    // Read the file line by line, ignoring empty lines
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Split each line into action and IR code
        list($action, $irCode) = explode('=', $line, 2);
        $payloads[trim($action)] = trim($irCode);
    }
    return $payloads;
}

// Load payloads from the text file
$payloads = loadPayloads('payloads.txt');

// Function to get the payload based on the action
function getPayload($action, $payloads) {
    if (isset($payloads[$action])) {
        // Construct the full command by wrapping the IR code
        return 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
    }
    return '';
}

// Initialize an empty error message variable
$errorMessage = '';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceUrl = rtrim($_POST['device_url'], '/');
    $action = $_POST['action'];
    $url = $deviceUrl . "/cgi-bin/api/command/cli";
    $payload = getPayload($action, $payloads);
    
    if ($payload) {
        $result = sendApiRequest($url, $payload);
        if ($result['error'] || $result['httpCode'] >= 400) {
            $errorMessage = "Error: " . ($result['error'] ?: "HTTP Code " . $result['httpCode']);
        }
    } else {
        $errorMessage = "Invalid action: " . htmlspecialchars($action);
    }

    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $errorMessage]);
    exit;
}
?>