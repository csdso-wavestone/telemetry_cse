<?php
/*
 * maintenance.php
 *
 * Purpose:
 *   Simple JSON-backed storage for maintenance notes created from
 *   the dashboard UI. Notes are persisted to `dashboard/maintenance.json`.
 *
 * Interconnections:
 *   - The dashboard client in `assets/js/dashboard.js` POSTs new notes to this
 *     endpoint and reads the saved list to render the maintenance plan.
 *   - This is intentionally simple and file-backed for ease of local
 *     development; in production you would likely store notes in a DB.
 */

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

$maintenancePath = __DIR__ . '/maintenance.json';

// Handle creating a new maintenance note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $note = trim($payload['note'] ?? '');
    if ($note === '') {
        http_response_code(400);
        echo json_encode(['error' => 'note is required']);
        exit;
    }

    $items = [];
    if (file_exists($maintenancePath)) {
        $items = json_decode(file_get_contents($maintenancePath), true);
        if (!is_array($items)) {
            $items = [];
        }
    }

    // Append note with ISO8601 timestamp
    $items[] = [
        'created' => date('c'),
        'note' => $note,
    ];
    file_put_contents($maintenancePath, json_encode($items, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true]);
    exit;
}

// Otherwise return the list of maintenance items
$items = [];
if (file_exists($maintenancePath)) {
    $items = json_decode(file_get_contents($maintenancePath), true);
    if (!is_array($items)) {
        $items = [];
    }
}

echo json_encode($items);
