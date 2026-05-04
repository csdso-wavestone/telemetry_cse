<?php
require 'config.php';
dashboard_start_session();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

$body = file_get_contents('php://input');
if (trim($body) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'empty payload']);
    exit;
}

$stored = dashboard_store_raw_data($body);
if ($stored) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'unable to store data']);
}
