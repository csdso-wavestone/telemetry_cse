<?php
require 'config.php';
dashboard_start_session();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $note = trim($payload['note'] ?? '');
    if ($note === '') {
        http_response_code(400);
        echo json_encode(['error' => 'note is required']);
        exit;
    }
    $items = dashboard_load_maintenance();
    $items[] = [
        'created' => date('c'),
        'note' => $note,
    ];
    dashboard_save_maintenance($items);
    echo json_encode(['success' => true]);
    exit;
}

$items = dashboard_load_maintenance();
echo json_encode($items);
