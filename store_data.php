<?php
/*
 * store_data.php
 *
 * Purpose:
 *   Generic endpoint that accepts POSTed JSON payloads and persists
 *   them either to local storage (`storage`) or to Azure
 *   Blob storage when configured via `settings.json`.
 *
 * Interconnections:
 *   - Used when an external client or the UI wants to push a JSON
 *     snapshot into the same history pipeline used by `api_fetch.php`.
 *   - Reads `settings.json` to determine whether to try an
 *     Azure upload (SAS token) or fallback to local filesystem.
 */

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

// Only allow POST to create data
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

// Strip any metadata fields from POSTed payloads before persisting.
// This ensures the history files only contain actual sensor telemetry.
function sanitize_payload(string $raw): string {
    $payload = json_decode($raw, true);
    if (is_array($payload)) {
        unset($payload['source']);
        return json_encode($payload);
    }
    return $raw;
}

$body = sanitize_payload($body);

// Load optional Azure settings
$settingsPath = __DIR__ . '/settings.json';
$azureBlobUrl = '';
$azureSasToken = '';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true);
    if (!empty($settings['azure_blob_url'])) {
        $azureBlobUrl = rtrim($settings['azure_blob_url'], '/');
    }
    if (!empty($settings['azure_sas_token'])) {
        $azureSasToken = ltrim($settings['azure_sas_token'], '?');
    }
}

$fileName = gmdate('Y-m-d_H-i-s') . '.json';

// Try Azure upload first when configured, otherwise persist locally
if ($azureBlobUrl !== '' && $azureSasToken !== '') {
    $uploadUrl = $azureBlobUrl . '/' . rawurlencode($fileName) . '?' . $azureSasToken;
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-ms-blob-type: BlockBlob',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        // On any non-2xx result, fallback to local storage
        $storagePath = __DIR__ . '/storage';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        file_put_contents($storagePath . '/' . $fileName, $body);
    }
} else {
    $storagePath = __DIR__ . '/storage';
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }
    file_put_contents($storagePath . '/' . $fileName, $body);
}

echo json_encode(['success' => true]);
