<?php
require 'config.php';
dashboard_start_session();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

$apiUrl = dashboard_get_api_url();
if ($apiUrl === '') {
    http_response_code(500);
    echo json_encode(['error' => 'API base URL is not configured']);
    exit;
}

$result = dashboard_http_request($apiUrl, 'GET', null, ['Accept: application/json']);
if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
    http_response_code(502);
    echo json_encode([
        'error' => 'unable to fetch remote API',
        'status' => $result['http_code'],
        'curl_error' => $result['error'],
    ]);
    exit;
}

$payload = $result['body'] ?? '';
if ($payload !== '') {
    dashboard_store_raw_data($payload);
}

echo $payload;