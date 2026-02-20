<?php
// Test de l'API Gemini
$response = @file_get_contents('http://127.0.0.1:8000/admin/ai/analyze/8');
if ($response === false) {
    echo "ERREUR: Impossible de se connecter à l'API\n";
    echo "HTTP response headers:\n";
    print_r($http_response_header ?? []);
} else {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "SUCCESS: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['error'])) {
            echo "ERROR: " . $data['error'] . "\n";
        }
        if (isset($data['student']['name'])) {
            echo "Student: " . $data['student']['name'] . "\n";
        }
        if (isset($data['analysis'])) {
            echo "Analysis received!\n";
        }
    } else {
        echo "Not JSON - HTML Error page probably\n";
        echo substr($response, 0, 500) . "\n";
    }
}
