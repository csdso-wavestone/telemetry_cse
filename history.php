<?php
/*
 * history.php
 *
 * Purpose:
 *   Provides historical JSON file metadata and serves individual
 *   historical snapshots to the dashboard.
 *
 * Behavior:
 *   - When Azure Blob Storage is configured, the dashboard history
 *     is fetched from Azure rather than from local `storage`.
 *   - If Azure is not configured or if blob access fails, local storage
 *     is used as a fallback.
 *   - A modification in Azure Blob Storage is reflected automatically
 *     in the dashboard when the user loads history or views a file.
 *
 * Interconnections:
 *   - Reads `settings.json` to detect Azure blob config.
 *   - Uses Azure REST endpoints to list blobs and fetch blob content.
 *   - Falls back to local storage when Azure is unavailable.
 */

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'authentication required']);
    exit;
}

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

$storagePath = __DIR__ . '/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0755, true);
}

function azureRequest(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-ms-version: 2020-10-02',
        'Accept: application/xml',
    ]);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($result === false || $status < 200 || $status >= 300) {
        return null;
    }
    return $result;
}

function parseAzureBlobListXml(string $xml): array {
    $files = [];

    if (function_exists('simplexml_load_string')) {
        $contents = simplexml_load_string($xml);
        if ($contents !== false && isset($contents->Blobs->Blob)) {
            foreach ($contents->Blobs->Blob as $blob) {
                $name = (string) $blob->Name;
                if (substr($name, -5) !== '.json') {
                    continue;
                }
                $timestamp = (string) $blob->Properties->{'Last-Modified'};
                $files[] = [
                    'name' => $name,
                    'timestamp' => $timestamp ?: 'N/A',
                ];
            }
        }
        return $files;
    }

    if (class_exists('DOMDocument')) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        if ($doc->loadXML($xml)) {
            $nodes = $doc->getElementsByTagName('Blob');
            foreach ($nodes as $blob) {
                $nameNode = $blob->getElementsByTagName('Name')->item(0);
                $tsNode = $blob->getElementsByTagName('Last-Modified')->item(0);
                if (!$nameNode) {
                    continue;
                }
                $name = $nameNode->nodeValue;
                if (substr($name, -5) !== '.json') {
                    continue;
                }
                $files[] = [
                    'name' => $name,
                    'timestamp' => $tsNode ? $tsNode->nodeValue : 'N/A',
                ];
            }
        }
        libxml_clear_errors();
        return $files;
    }

    preg_match_all('/<Blob>(.*?)<\/Blob>/si', $xml, $matches);
    foreach ($matches[1] as $blobBlock) {
        if (!preg_match('/<Name>(.*?)<\/Name>/si', $blobBlock, $nameMatch)) {
            continue;
        }
        $name = trim($nameMatch[1]);
        if (substr($name, -5) !== '.json') {
            continue;
        }
        $timestamp = 'N/A';
        if (preg_match('/<Last-Modified>(.*?)<\/Last-Modified>/si', $blobBlock, $timeMatch)) {
            $timestamp = trim($timeMatch[1]) ?: 'N/A';
        }
        $files[] = [
            'name' => $name,
            'timestamp' => $timestamp,
        ];
    }

    return $files;
}

function listAzureBlobs(string $baseUrl, string $sasToken): ?array {
    $query = ltrim($sasToken, '?');
    $url = $baseUrl . '?restype=container&comp=list&' . $query;
    $xml = azureRequest($url);
    if ($xml === null) {
        return null;
    }

    $files = parseAzureBlobListXml($xml);
    usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
    return $files;
}

function getAzureBlobContent(string $baseUrl, string $sasToken, string $fileName): ?string {
    $query = ltrim($sasToken, '?');
    $url = $baseUrl . '/' . rawurlencode($fileName) . '?' . $query;
    return azureRequest($url);
}

function tryLocalFile(string $storagePath, string $fileName): ?string {
    $path = $storagePath . '/' . basename($fileName);
    if (!file_exists($path)) {
        return null;
    }
    return file_get_contents($path);
}

// Sanitize history payloads on read by removing legacy metadata fields.
// This prevents old files from exposing the `source` property in the UI.
function sanitizeHistoryContent(string $raw): string {
    $payload = json_decode($raw, true);
    if (is_array($payload)) {
        unset($payload['source']);
        return json_encode($payload);
    }
    return $raw;
}

$useAzure = $azureBlobUrl !== '' && $azureSasToken !== '';

// If a specific file was requested, attempt Azure first if configured
if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    if ($useAzure) {
        $content = getAzureBlobContent($azureBlobUrl, $azureSasToken, $file);
        if ($content !== null) {
            header('Content-Type: application/json');
            echo sanitizeHistoryContent($content);
            exit;
        }
    }

    $content = tryLocalFile($storagePath, $file);
    if ($content === null) {
        http_response_code(404);
        echo json_encode(['error' => 'file not found']);
        exit;
    }

    header('Content-Type: application/json');
    echo sanitizeHistoryContent($content);
    exit;
}

// Otherwise list history files from Azure when configured, else local storage
$files = null;
if ($useAzure) {
    $files = listAzureBlobs($azureBlobUrl, $azureSasToken);
}

if ($files === null) {
    $files = [];
    foreach (scandir($storagePath) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (substr($entry, -5) !== '.json') {
            continue;
        }
        $path = $storagePath . '/' . $entry;
        $files[] = [
            'name' => $entry,
            'timestamp' => date('c', filemtime($path)),
        ];
    }
    usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
}

header('Content-Type: application/json');
echo json_encode(['files' => $files]);
