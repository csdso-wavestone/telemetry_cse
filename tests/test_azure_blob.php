<?php
/**
 * Test Azure blob upload and retrieval when Azure is configured.
 *
 * This test is skipped if Azure settings are not present in dashboard/settings.json.
 */

function assertTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

function loadSettings()
{
    $settingsFile = __DIR__ . '/../settings.json';
    if (!file_exists($settingsFile)) {
        return [];
    }
    $contents = file_get_contents($settingsFile);
    return json_decode($contents, true) ?: [];
}

$settings = loadSettings();
$azureBlobUrl = trim($settings['azure_blob_url'] ?? '');
$azureSasToken = trim($settings['azure_sas_token'] ?? '');
if ($azureBlobUrl === '' || $azureSasToken === '') {
    echo "Skipping Azure blob test because settings.json has no Azure configuration.\n";
    exit(0);
}

$azureBlobUrl = rtrim($azureBlobUrl, '/');
$azureSasToken = ltrim($azureSasToken, '?');
$filename = 'test-blob-' . uniqid() . '.json';
$payload = json_encode(['test' => 'azure', 'created' => time()]);
$uploadUrl = sprintf('%s/%s?%s', $azureBlobUrl, rawurlencode($filename), $azureSasToken);

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-ms-blob-type: BlockBlob',
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload),
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
assertTrue($status >= 200 && $status < 300, "Azure upload failed with status $status, error: $curlError");

echo "Azure upload succeeded (status $status).\n";

$fetchUrl = $uploadUrl;
$ch = curl_init($fetchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$download = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assertTrue($status >= 200 && $status < 300, "Azure retrieval failed with status $status");
assertTrue($download === $payload, 'Downloaded blob content does not match uploaded data');

echo "Azure retrieval succeeded.\n";

echo "Done test_azure_blob.php\n";
