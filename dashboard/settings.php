<?php
require 'config.php';
dashboard_start_session();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Dashboard Settings';
include '../includes/header.html';
include '../includes/navbar.html';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = dashboard_load_config();
    $config['api_base_url'] = trim($_POST['api_base_url'] ?? '');
    $config['poll_interval'] = (int) ($_POST['poll_interval'] ?? 60);
    $config['azure_storage_account'] = trim($_POST['azure_storage_account'] ?? '');
    $config['azure_storage_container'] = trim($_POST['azure_storage_container'] ?? '');
    $config['azure_sas_token'] = trim($_POST['azure_sas_token'] ?? '');
    $config['blob_local_path'] = trim($_POST['blob_local_path'] ?? __DIR__ . '/storage');
    dashboard_save_config($config);
    $success = true;
}

$config = dashboard_load_config();
$apiUrl = dashboard_get_setting('api_base_url', 'https://api.entreprise-b.com');
$pollInterval = dashboard_get_setting('poll_interval', 60);
$azureAccount = dashboard_get_setting('azure_storage_account', '');
$azureContainer = dashboard_get_setting('azure_storage_container', '');
$azureSas = dashboard_get_setting('azure_sas_token', '');
$blobLocalPath = dashboard_get_setting('blob_local_path', __DIR__ . '/storage');
?>

<div class="container py-4">
    <h2>Dashboard Settings</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">Settings saved.</div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <div class="form-group">
            <label for="api_base_url">API Base URL</label>
            <input type="text" id="api_base_url" name="api_base_url" class="form-control" value="<?php echo htmlspecialchars($apiUrl); ?>" placeholder="https://api.entreprise-b.com">
            <small class="form-text text-muted">This URL is used for real-time reactor metrics.</small>
        </div>
        <div class="form-group">
            <label for="poll_interval">Polling interval (seconds)</label>
            <input type="number" id="poll_interval" name="poll_interval" class="form-control" value="<?php echo htmlspecialchars($pollInterval); ?>" min="10">
        </div>
        <hr>
        <h5>Azure Blob Storage</h5>
        <div class="form-group">
            <label for="azure_storage_account">Azure Storage account</label>
            <input type="text" id="azure_storage_account" name="azure_storage_account" class="form-control" value="<?php echo htmlspecialchars($azureAccount); ?>">
        </div>
        <div class="form-group">
            <label for="azure_storage_container">Container name</label>
            <input type="text" id="azure_storage_container" name="azure_storage_container" class="form-control" value="<?php echo htmlspecialchars($azureContainer); ?>">
        </div>
        <div class="form-group">
            <label for="azure_sas_token">SAS token</label>
            <textarea id="azure_sas_token" name="azure_sas_token" class="form-control" rows="2"><?php echo htmlspecialchars($azureSas); ?></textarea>
            <small class="form-text text-muted">Example: ?sv=2024-01-01&ss=b&srt=sco&sp=rw&... (leave blank to use local storage)</small>
        </div>
        <div class="form-group">
            <label for="blob_local_path">Local storage path</label>
            <input type="text" id="blob_local_path" name="blob_local_path" class="form-control" value="<?php echo htmlspecialchars($blobLocalPath); ?>">
            <small class="form-text text-muted">If Azure is not configured, the dashboard saves historical files here.</small>
        </div>
        <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
</div>

<?php include '../includes/footer.html'; ?>
