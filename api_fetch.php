<?php
/*
 * api_fetch.php
 *
 * Purpose:
 *   Server-side proxy for fetching the live reactor JSON from the
 *   configured `api_base_url`. The endpoint returns the API payload
 *   to the caller (the dashboard JS) and also saves each successful
 *   response either locally under `dashboard/storage` or to an
 *   Azure Blob container when `settings.json` contains a SAS token
 *   and a blob base URL.
 *
 * Interconnections:
 *   - Reads `dashboard/settings.json` to determine `api_base_url`,
 *     `azure_blob_url` and `azure_sas_token`.
 *   - The dashboard UI calls this endpoint via fetch() to get live
 *     data and to persist each sample for history browsing.
 *
 * Notes:
 *   - The script requires a valid logged-in session. It returns a
 *     JSON error message if the API cannot be reached.
 */

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ./login.php');
    exit;
}

// Load settings and compute effective API/Storage configuration
$settingsPath = __DIR__ . '/settings.json';
$apiBaseUrl = 'https://api.entreprise-b.com';
$azureBlobUrl = '';
$azureSasToken = '';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true);
    if (!empty($settings['api_base_url'])) {
        // Ensure no trailing slash so we can append filenames or paths
        $apiBaseUrl = rtrim($settings['api_base_url'], '/');
    }
    if (!empty($settings['azure_blob_url'])) {
        $azureBlobUrl = rtrim($settings['azure_blob_url'], '/');
    }
    if (!empty($settings['azure_sas_token'])) {
        // Store SAS token without leading ? for consistent concatenation
        $azureSasToken = ltrim($settings['azure_sas_token'], '?');
    }
}

// Remove any legacy metadata fields that should not be stored or returned.
// This keeps persisted history and live fetch responses clean and focused
// on the actual reactor telemetry values.
function sanitize_payload(string $raw) : string {
    $payload = json_decode($raw, true);
    if (is_array($payload)) {
        unset($payload['source']);
        return json_encode($payload);
    }
    return $raw;
}

// Fetch the live API payload. The code uses file_get_contents() for
// simplicity; in production you might prefer curl with timeouts.
$response = @file_get_contents($apiBaseUrl);
if ($response === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'could not fetch API data']);
    exit;
}
$response = sanitize_payload($response);

$fileName = gmdate('Y-m-d_H-i-s') . '.json';

// If Azure config is present, attempt to upload as a BlockBlob via PUT.
// On failure (non-2xx), fallback to local storage.
if ($azureBlobUrl !== '' && $azureSasToken !== '') {
    $uploadUrl = $azureBlobUrl . '/' . rawurlencode($fileName) . '?' . $azureSasToken;
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-ms-blob-type: BlockBlob',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($response),
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // TEMPORARY FUNCTIONNALITY TO BE REMOVED LATER 
    if ($status < 200 || $status >= 300) {
        // Azure upload failed, save locally for debugging and history
        $storagePath = __DIR__ . '/storage';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        file_put_contents($storagePath . '/' . $fileName, $response);
    }
} else {
    // No Azure configured: persist locally under dashboard/storage
    $storagePath = __DIR__ . '/storage';
    if (!is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }
    file_put_contents($storagePath . '/' . $fileName, $response);
}

// Return the live API payload to the client
header('Content-Type: application/json');
echo $response;