<?php
// api.php (V2) - IR Blaster API Handler

// Enable full error reporting and display errors on the page for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send an API request using cURL
function sendApiRequest($url, $payload) {
    // Initialize a cURL session
    $ch = curl_init();

    // Set the URL to which the request will be sent
    curl_setopt($ch, CURLOPT_URL, $url);

    // Ensure the response is returned as a string instead of being directly outputted
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Specify that this request will use the POST method
    curl_setopt($ch, CURLOPT_POST, true);

    // Attach the payload (data) to be sent in the request body
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set custom HTTP headers, including the content type and user agent
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain', // Indicate that the payload is plain text
        'User-Agent: JustOS API Tester' // Custom user agent for the request
    ]);

    // Execute the cURL request and store the response
    $response = curl_exec($ch);

    // Retrieve the HTTP status code from the response
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Capture any error that occurred during the cURL request
    $error = curl_error($ch);

    // Close the cURL session to free up resources
    curl_close($ch);

    // Return an array containing the response, HTTP status code, and any error message
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

// Function to load IR command payloads from a text file
function loadPayloads($filename) {
    $payloads = []; // Initialize an empty array to store the payloads

    // Read the file line by line, ignoring empty lines
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Loop through each line in the file
    foreach ($lines as $line) {
        // Split each line into an action and an IR code using the '=' delimiter
        list($action, $irCode) = explode('=', $line, 2);

        // Trim whitespace and store the action and corresponding IR code in the array
        $payloads[trim($action)] = trim($irCode);
    }

    // Return the array of payloads
    return $payloads;
}

// Load the payloads from the 'payloads.txt' file into an array
$payloads = loadPayloads('payloads.txt');

// Function to get the appropriate payload based on the provided action
function getPayload($action, $payloads) {
    // Check if the action exists in the payloads array
    if (isset($payloads[$action])) {
        // Construct and return the full command to be sent, wrapping the IR code in the necessary shell command
        return 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
    }

    // Return an empty string if the action is not found
    return '';
}

// Initialize an empty string to store any error message
$errorMessage = '';

// Check if the request method is POST, indicating a form submission or API request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize the device URL from the POST request, removing any trailing slash
    $deviceUrl = rtrim($_POST['device_url'], '/');

    // Retrieve the action parameter from the POST request
    $action = $_POST['action'];

    // Construct the full URL for the API request by appending the command endpoint
    $url = $deviceUrl . "/cgi-bin/api/command/cli";

    // Get the payload (IR command) corresponding to the requested action
    $payload = getPayload($action, $payloads);
    
    // Check if a valid payload was found for the action
    if ($payload) {
        // Send the API request with the constructed URL and payload
        $result = sendApiRequest($url, $payload);

        // Check if there was an error during the request or if the HTTP status code indicates a failure
        if ($result['error'] || $result['httpCode'] >= 400) {
            // Construct an error message based on the error or HTTP status code
            $errorMessage = "Error: " . ($result['error'] ?: "HTTP Code " . $result['httpCode']);
        }
    } else {
        // If no valid payload was found, set an error message indicating an invalid action
        $errorMessage = "Invalid action: " . htmlspecialchars($action);
    }

    // Set the Content-Type header to JSON and return the error message as a JSON response
    header('Content-Type: application/json');
    echo json_encode(['error' => $errorMessage]);
    exit; // End the script execution after sending the response
}
?>
