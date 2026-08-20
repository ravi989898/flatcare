/**
 * Initializes the Super Admin dashboard's Chart.js widgets.
 *
 * Loaded as an external file (not an inline <script> block) because the
 * app's CSP is `script-src 'self' cdn.jsdelivr.net code.jquery.com` with no
 * 'unsafe-inline' - inline scripts and event handlers are silently blocked.
 * Chart data reaches this file via <script type="application/json"> data
 * islands in the page, which CSP does not restrict (their MIME type isn't
 * executable, so the browser never treats them as script).
 */
(function () {
    function readJson(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return null;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const revenue = readJson('revenue-chart-data');
        const revenueCanvas = document.getElementById('revenueChart');

        if (revenue && revenueCanvas && typeof Chart !== 'undefined') {
            new Chart(revenueCanvas, {
                type: 'bar',
                data: {
                    labels: revenue.map(function (row) { return row.label; }),
                    datasets: [{
                        label: 'Revenue',
                        data: revenue.map(function (row) { return row.total; }),
                        backgroundColor: '#007bff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        }

        const societyStatus = readJson('society-status-chart-data');
        const societyStatusCanvas = document.getElementById('societyStatusChart');

        if (societyStatus && societyStatusCanvas && typeof Chart !== 'undefined') {
            new Chart(societyStatusCanvas, {
                type: 'pie',
                data: {
                    labels: ['Active', 'Inactive', 'Expired'],
                    datasets: [{
                        data: [societyStatus.active, societyStatus.inactive, societyStatus.expired],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
        }
    });
})();
