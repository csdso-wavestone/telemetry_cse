/**
 * js/dashboard.js
 *
 * Purpose:
 *   Client-side behavior for the Reactor Monitoring dashboard. This
 *   script is responsible for:
 *     - polling `api_fetch.php` for live reactor JSON
 *     - rendering realtime values into the dashboard DOM
 *     - loading the list of historical files from `history.php`
 *     - loading and saving maintenance notes via `maintenance.php`
 *
 * Interconnections:
 *   - `api_fetch.php` returns live JSON and persists snapshots to
 *     `dashboard/storage` (or to Azure when configured).
 *   - `history.php` provides file listings and file contents.
 *   - `maintenance.php` handles GET/POST of maintenance notes.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Default poll interval used in this client; can be tuned via settings.php
    const realtimeInterval = 60000;

    document.getElementById('maintenanceForm').addEventListener('submit', async event => {
        event.preventDefault();
        const note = document.getElementById('maintenanceNote').value.trim();
        if (!note) {
            return;
        }
        await saveMaintenance(note);
        document.getElementById('maintenanceNote').value = '';
        await loadMaintenance();
    });

    // Fetch live data from the server; server also persists the JSON
    // snapshot for history browsing. Errors are surfaced in a small
    // status element on the page so the operator can see failures.
    async function fetchRealtime() {
        const output = document.getElementById('apiStatus');
        try {
            const response = await fetch('api_fetch.php');
            if (!response.ok) {
                output.textContent = `Error ${response.status}`;
                return;
            }
            const data = await response.json();
            renderRealtime(data);
            output.textContent = 'Live reactor data loaded';
        } catch (error) {
            output.textContent = 'Unable to fetch realtime data';
        }
    }

    // Load the list of recent saved JSON files and populate the left column
    async function fetchHistory() {
        const historyList = document.getElementById('historyList');
        try {
            const response = await fetch('history.php');
            if (!response.ok) {
                historyList.innerHTML = '<li class="list-group-item text-danger">Unable to load history list</li>';
                return;
            }
            const data = await response.json();
            historyList.innerHTML = '';
            if (!Array.isArray(data.files) || data.files.length === 0) {
                historyList.innerHTML = '<li class="list-group-item">No historical file available</li>';
                return;
            }
            data.files.slice(0, 10).forEach(file => {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                item.innerHTML = `<span>${file.name}</span><span class="badge badge-secondary badge-pill">${file.timestamp}</span>`;
                item.addEventListener('click', () => loadHistoryFile(file.name));
                historyList.appendChild(item);
            });
        } catch (error) {
            historyList.innerHTML = '<li class="list-group-item text-danger">Unable to fetch history</li>';
        }
    }

    // Request a particular historical JSON file and show its contents
    async function loadHistoryFile(name) {
        const details = document.getElementById('historyDetails');
        try {
            const response = await fetch(`history.php?file=${encodeURIComponent(name)}`);
            if (!response.ok) {
                details.textContent = 'Unable to load file contents';
                return;
            }
            const data = await response.json();
            details.textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            details.textContent = 'Unable to load file contents';
        }
    }

    // Load maintenance notes and render into the maintenance list
    async function loadMaintenance() {
        const list = document.getElementById('maintenanceList');
        try {
            const response = await fetch('maintenance.php');
            if (!response.ok) {
                list.innerHTML = '<li class="list-group-item text-danger">Could not load maintenance plan</li>';
                return;
            }
            const items = await response.json();
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<li class="list-group-item">No maintenance items planned yet.</li>';
                return;
            }
            items.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `<strong>${item.created}</strong><br>${item.note}`;
                list.appendChild(li);
            });
        } catch (error) {
            list.innerHTML = '<li class="list-group-item text-danger">Could not load maintenance plan</li>';
        }
    }

    // POST a maintenance note to the server
    async function saveMaintenance(note) {
        try {
            await fetch('maintenance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ note }),
            });
        } catch (error) {
            console.error('Failed to save maintenance note', error);
        }
    }

    // Given the live API JSON, update UI elements (status, metrics,
    // alerts) and populate the details table.
    function renderRealtime(data) {
        const status = document.getElementById('reactorStatus');
        const temperature = document.getElementById('reactorTemperature');
        const pressure = document.getElementById('reactorPressure');
        const alertBadge = document.getElementById('alerts');
        status.textContent = data.status || 'Unknown';
        temperature.textContent = data.temperature !== undefined ? `${data.temperature} °C` : 'N/A';
        pressure.textContent = data.pressure !== undefined ? `${data.pressure} bar` : 'N/A';
        const statusClass = (data.status || '').toLowerCase();
        alertBadge.textContent = data.alerts || 'No alerts';
        alertBadge.className = 'badge ' + (statusClass === 'critical' ? 'badge-danger' : statusClass === 'warning' ? 'badge-warning' : 'badge-success');

        const detailsBody = document.getElementById('realtimeDetails');
        detailsBody.innerHTML = '';
        if (data.details && typeof data.details === 'object') {
            Object.entries(data.details).forEach(([key, value]) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${key}</td><td>${value}</td>`;
                detailsBody.appendChild(row);
            });
        }
    }

    // Initial population on page load
    fetchRealtime();
    fetchHistory();
    loadMaintenance();

    // Attach click handler to Refresh button for manual data updates
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', fetchRealtime);
    }
});
