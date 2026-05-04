<?php
function dashboard_start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function dashboard_get_config_path(): string {
    return __DIR__ . '/settings.json';
}

function dashboard_load_config(): array {
    $path = dashboard_get_config_path();
    if (!file_exists($path)) {
        return [];
    }
    $data = file_get_contents($path);
    $config = json_decode($data, true);
    return is_array($config) ? $config : [];
}

function dashboard_save_config(array $config): void {
    $path = dashboard_get_config_path();
    file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function dashboard_get_setting(string $key, $default = null) {
    $envName = strtoupper($key);
    $envValue = getenv($envName);
    if ($envValue !== false && $envValue !== '') {
        return $envValue;
    }
    $config = dashboard_load_config();
    return $config[$key] ?? $default;
}

function dashboard_get_api_url(): string {
    return rtrim(dashboard_get_setting('api_base_url', 'https://api.entreprise-b.com'), '/');
}

function dashboard_get_poll_interval(): int {
    $interval = (int) dashboard_get_setting('poll_interval', 60);
    return max(10, $interval);
}

function dashboard_get_blob_config(): array {
    return [
        'account' => dashboard_get_setting('azure_storage_account', null),
        'container' => dashboard_get_setting('azure_storage_container', null),
        'sas' => dashboard_get_setting('azure_sas_token', null),
    ];
}

function dashboard_get_blob_local_root(): string {
    return dashboard_ensure_directory(dashboard_get_setting('blob_local_path', __DIR__ . '/storage'));
}

function dashboard_get_maintenance_path(): string {
    return __DIR__ . '/maintenance.json';
}

function dashboard_ensure_directory(string $path): string {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

function dashboard_resolve_blob_name(string $blobName): string {
    $blobName = str_replace('..', '', $blobName);
    return ltrim($blobName, '/');
}

function dashboard_http_request(string $url, string $method = 'GET', ?string $body = null, array $headers = []): array {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    return [
        'http_code' => $status,
        'body' => $response,
        'error' => $error,
    ];
}

function dashboard_upload_local_blob(string $blobName, string $content): bool {
    $root = dashboard_get_blob_local_root();
    $blobName = dashboard_resolve_blob_name($blobName);
    $path = $root . '/' . $blobName;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($path, $content) !== false;
}

function dashboard_build_sas_url(string $url, string $sasToken): string {
    if (strpos($sasToken, '?') !== 0 && strpos($sasToken, '?') !== false) {
        return $url . $sasToken;
    }
    return $url . '?' . ltrim($sasToken, '?');
}

function dashboard_upload_to_azure_blob(string $account, string $container, string $sasToken, string $blobName, string $content): bool {
    $blobName = dashboard_resolve_blob_name($blobName);
    $url = sprintf('https://%s.blob.core.windows.net/%s/%s', $account, $container, rawurlencode($blobName));
    $url = dashboard_build_sas_url($url, $sasToken);
    $headers = [
        'x-ms-blob-type: BlockBlob',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($content),
    ];
    $result = dashboard_http_request($url, 'PUT', $content, $headers);
    return $result['http_code'] >= 200 && $result['http_code'] < 300;
}

function dashboard_store_raw_data(string $json): bool {
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $filename = "reactor-data/{$timestamp}.json";
    $blobConfig = dashboard_get_blob_config();
    if (!empty($blobConfig['account']) && !empty($blobConfig['container']) && !empty($blobConfig['sas'])) {
        return dashboard_upload_to_azure_blob($blobConfig['account'], $blobConfig['container'], $blobConfig['sas'], $filename, $json);
    }
    return dashboard_upload_local_blob($filename, $json);
}

function dashboard_list_local_blobs(): array {
    $root = dashboard_get_blob_local_root();
    $files = [];
    $prefix = $root . '/reactor-data';
    if (!is_dir($prefix)) {
        return [];
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prefix, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'json') {
            $relative = str_replace($root . '/', '', $file->getPathname());
            $files[] = [
                'name' => $relative,
                'timestamp' => date('c', $file->getMTime()),
                'size' => $file->getSize(),
            ];
        }
    }
    usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
    return $files;
}

function dashboard_list_azure_blobs(string $account, string $container, string $sasToken): array {
    $url = sprintf('https://%s.blob.core.windows.net/%s?restype=container&comp=list&prefix=reactor-data/', $account, $container);
    $url = dashboard_build_sas_url($url, $sasToken);
    $result = dashboard_http_request($url, 'GET', null, ['Accept: application/xml']);
    if ($result['http_code'] !== 200 || empty($result['body'])) {
        return [];
    }
    $xml = simplexml_load_string($result['body']);
    if ($xml === false) {
        return [];
    }
    $files = [];
    foreach ($xml->Blob as $blob) {
        $name = (string) $blob->Name;
        $files[] = [
            'name' => $name,
            'timestamp' => (string) $blob->Properties->Last-Modified,
            'size' => (int) $blob->Properties->Content-Length,
        ];
    }
    usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
    return $files;
}

function dashboard_list_historical_files(): array {
    $blobConfig = dashboard_get_blob_config();
    if (!empty($blobConfig['account']) && !empty($blobConfig['container']) && !empty($blobConfig['sas'])) {
        return dashboard_list_azure_blobs($blobConfig['account'], $blobConfig['container'], $blobConfig['sas']);
    }
    return dashboard_list_local_blobs();
}

function dashboard_read_local_blob(string $blobName): ?string {
    $blobName = dashboard_resolve_blob_name($blobName);
    $path = dashboard_get_blob_local_root() . '/' . $blobName;
    if (!file_exists($path)) {
        return null;
    }
    return file_get_contents($path);
}

function dashboard_read_azure_blob(string $account, string $container, string $sasToken, string $blobName): ?string {
    $blobName = dashboard_resolve_blob_name($blobName);
    $url = sprintf('https://%s.blob.core.windows.net/%s/%s', $account, $container, rawurlencode($blobName));
    $url = dashboard_build_sas_url($url, $sasToken);
    $result = dashboard_http_request($url, 'GET', null, ['Accept: application/json']);
    if ($result['http_code'] !== 200) {
        return null;
    }
    return $result['body'];
}

function dashboard_read_historical_blob(string $blobName): ?string {
    $blobConfig = dashboard_get_blob_config();
    if (!empty($blobConfig['account']) && !empty($blobConfig['container']) && !empty($blobConfig['sas'])) {
        return dashboard_read_azure_blob($blobConfig['account'], $blobConfig['container'], $blobConfig['sas'], $blobName);
    }
    return dashboard_read_local_blob($blobName);
}

function dashboard_load_maintenance(): array {
    $path = dashboard_get_maintenance_path();
    if (!file_exists($path)) {
        return [];
    }
    $data = file_get_contents($path);
    $items = json_decode($data, true);
    return is_array($items) ? $items : [];
}

function dashboard_save_maintenance(array $items): bool {
    $path = dashboard_get_maintenance_path();
    return file_put_contents($path, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}
