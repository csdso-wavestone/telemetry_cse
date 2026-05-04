<?php
require 'config.php';
dashboard_start_session();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $content = dashboard_read_historical_blob($file);
    if ($content === null) {
        http_response_code(404);
        echo json_encode(['error' => 'file not found']);
        exit;
    }
    echo $content;
    exit;
}

$files = dashboard_list_historical_files();
echo json_encode(['files' => $files]);
