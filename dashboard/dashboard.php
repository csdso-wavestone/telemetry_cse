<?php

$page_title = 'Reactor Dashboard';
include '../includes/header.html';
include '../includes/navbar.html';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Reactor Monitoring Dashboard</h2>
            <p class="text-muted">Live reactor status, historical blob data, and maintenance planning.</p>
        </div>
        <a href="settings.php" class="btn btn-outline-secondary">Dashboard settings</a>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    Real-time data <span id="apiStatus" class="badge badge-info ml-2">Loading...</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong> <span id="reactorStatus">Unknown</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <div class="card bg-light p-3 mb-2">
                                <strong>Temperature</strong>
                                <div id="reactorTemperature">N/A</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card bg-light p-3 mb-2">
                                <strong>Pressure</strong>
                                <div id="reactorPressure">N/A</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <strong>Alerts</strong>
                        <div id="alerts" class="badge badge-secondary">No alerts</div>
                    </div>
                    <div class="mt-4">
                        <h5>Detailed sensor values</h5>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody id="realtimeDetails"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Maintenance planning</strong></div>
                <div class="card-body">
                    <form id="maintenanceForm">
                        <div class="form-group">
                            <label for="maintenanceNote">New maintenance note</label>
                            <textarea id="maintenanceNote" class="form-control" rows="3" placeholder="Add a note for planned maintenance"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save plan</button>
                    </form>
                    <hr>
                    <h6>Upcoming maintenance items</h6>
                    <ul id="maintenanceList" class="list-group"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header"><strong>Historical blob data</strong></div>
                <div class="card-body">
                    <p class="text-muted">Historical JSON files are stored in Azure Blob Storage or local JSON storage when Azure is not configured.</p>
                    <div class="row">
                        <div class="col-md-5">
                            <h6>Recent files</h6>
                            <ul id="historyList" class="list-group"></ul>
                        </div>
                        <div class="col-md-7">
                            <h6>Selected file contents</h6>
                            <pre id="historyDetails" class="border rounded p-3" style="background:#f8f9fa;max-height:360px;overflow:auto;white-space:pre-wrap;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>

<?php include '../includes/footer.html'; ?>