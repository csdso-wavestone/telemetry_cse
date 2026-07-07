<?php
if (isset($_SESSION ['username'])){
	$page_title = 'Reactor Dashboard';
}
else{
  $page_title = "Home";
}

include 'includes/header.php';

include 'includes/navbar.php';

if (isset($_SESSION ['username'])){
?>
<div class="container py-4">
    <div class="dashboard-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h2>Reactor Monitoring Dashboard</h2>
                <p class="mb-0">Live reactor metrics, historical snapshots, and operational context in one place.</p>
            </div>
            <a href="settings.php" class="btn btn-light text-primary mt-3">Settings</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Real-time data</span>
                    <button type="button" id="refreshBtn" class="btn btn-sm btn-primary">Refresh</button>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> <span id="reactorStatus">Unknown</span></p>
                    <p><strong>Temperature:</strong> <span id="reactorTemperature">N/A</span></p>
                    <p><strong>Pressure:</strong> <span id="reactorPressure">N/A</span></p>
                    <p><strong>Alerts:</strong> <span id="alerts" class="badge badge-secondary">No alerts</span></p>
                    <p class="text-muted small mb-3" id="apiStatus">Click refresh to load live data.</p>
                    <h5 class="mt-4">Sensor details</h5>
                    <table class="table table-sm table-bordered">
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

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">Maintenance planning</div>
                <div class="card-body">
                    <form id="maintenanceForm">
                        <div class="form-group">
                            <label for="maintenanceNote">New maintenance note</label>
                            <textarea id="maintenanceNote" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save note</button>
                    </form>
                    <hr>
                    <h5>Planned maintenance</h5>
                    <ul id="maintenanceList" class="list-group"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Summary table - Historical files</span>
                    <button type="button" id="refreshSummaryBtn" class="btn btn-sm btn-primary">Refresh</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 450px; overflow-x: auto; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.5rem;">
                        <table class="table table-sm summary-table mb-0">
                            <thead id="summaryTableHead">
                                <tr>
                                    <th>Filename</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody">
                                <tr><td colspan="100%" class="text-muted text-center py-3">No files loaded yet. Click refresh to load historical data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card dashboard-card">
                <div class="card-header">Historical data files</div>
                <div class="card-body">
                    <p class="text-muted">Each sensor reading is saved locally or in the cloud.</p>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Recent files</h6>
                            <ul id="historyList" class="list-group"></ul>
                        </div>
                        <div class="col-md-8">
                            <h6>Selected file contents</h6>
                            <pre id="historyDetails" class="code-preview"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/dashboard.js"></script>

<?php
}
else{
?>
    <!-- Hero Section -->
    <section id="hero" class="hero section">
        <div class="hero-bg">
          <img src="/assets/img/Picture1.jpg" alt="">
        </div>
        <div class="container text-center">
          <div style="margin-top: -250px;" class="d-flex flex-column justify-content-center align-items-center">
            <h1>Welcome to the <span>Reactor Dashboard</span></h1>
            <p>You are not authentified, please log in.<br></p>
            <div class="d-flex">
              <a href="/login.php" class="btn btn-primary btn-lg active" role="button">Log in</a>
            </div>
            <!-- <img src="assets/img/hero-services-img.webp" class="img-fluid hero-img" alt="">-->
          </div>
        </div>
  
      </section><!-- /Hero Section -->
<?php

}

include 'includes/footer.html';
?>