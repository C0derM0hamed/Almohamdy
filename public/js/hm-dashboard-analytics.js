(function () {
    if (typeof Chart === 'undefined' || !window.hmDashCharts) {
        return;
    }

    var data = window.hmDashCharts;
    var isRtl = data.locale === 'ar';

    // Figma dashboard palette (file GSqeiriywPn3cbzHr5ivtz, frame 7:2)
    var palette = {
        primary: '#6366f1',
        primaryLight: '#818cf8',
        dark: '#302a58',
        amber: '#f59e0b',
        muted: '#64748b',
        ink: '#0f172a',
        line: '#e8ecf4',
        series: ['#6366f1', '#302a58', '#8b5cf6', '#a5b4fc', '#f59e0b', '#c7d2fe', '#22c55e', '#818cf8', '#e879f9', '#94a3b8', '#fbbf24', '#34d399'],
    };

    var css = getComputedStyle(document.documentElement);
    var fontFamily = css.getPropertyValue('--hm-font-family').trim()
        || 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = palette.muted;

    var sharedTooltip = {
        rtl: isRtl,
        textDirection: isRtl ? 'rtl' : 'ltr',
        backgroundColor: palette.dark,
        titleFont: { size: 13, weight: '700' },
        bodyFont: { size: 12 },
        padding: 12,
        cornerRadius: 10,
        displayColors: false,
    };

    function gridScales() {
        return {
            x: {
                reverse: isRtl,
                grid: { display: false },
                ticks: { maxTicksLimit: 8, maxRotation: 0 },
            },
            y: {
                position: isRtl ? 'right' : 'left',
                beginAtZero: true,
                border: { display: false },
                grid: { color: palette.line, drawTicks: false },
                ticks: { precision: 0, maxTicksLimit: 5 },
            },
        };
    }

    function legendBottom() {
        return {
            position: 'bottom',
            rtl: isRtl,
            labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true, pointStyle: 'rectRounded', padding: 14 },
        };
    }

    // Activity trend line with 7/30/90-day period switcher
    var trendCanvas = document.getElementById('hmDashTrend');
    if (trendCanvas && data.trend) {
        var trendChart = new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: data.trend.labels.slice(-30),
                datasets: [{
                    label: data.labels.total,
                    data: data.trend.total.slice(-30),
                    borderColor: palette.primary,
                    borderWidth: 3,
                    fill: false,
                    tension: 0.45,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: palette.primary,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: sharedTooltip },
                scales: gridScales(),
            },
        });

        document.querySelectorAll('.hm-fd-period').forEach(function (button) {
            button.addEventListener('click', function () {
                var days = parseInt(button.getAttribute('data-period'), 10) || 30;
                document.querySelectorAll('.hm-fd-period').forEach(function (b) {
                    b.classList.toggle('is-active', b === button);
                });
                trendChart.data.labels = data.trend.labels.slice(-days);
                trendChart.data.datasets[0].data = data.trend.total.slice(-days);
                trendChart.update();
            });
        });
    }

    // Records per module (doughnut) with custom two-column legend
    var modulesCanvas = document.getElementById('hmDashModules');
    if (modulesCanvas && data.modules && data.modules.length) {
        new Chart(modulesCanvas, {
            type: 'doughnut',
            data: {
                labels: data.modules.map(function (m) { return m.label; }),
                datasets: [{
                    data: data.modules.map(function (m) { return m.total; }),
                    backgroundColor: palette.series,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: { legend: { display: false }, tooltip: sharedTooltip },
            },
        });

        var legendHost = document.getElementById('hmFdDonutLegend');
        if (legendHost) {
            data.modules.forEach(function (m, i) {
                var item = document.createElement('div');
                item.className = 'hm-fd-legend-item';
                var dot = document.createElement('span');
                dot.className = 'dot';
                dot.style.background = palette.series[i % palette.series.length];
                var label = document.createElement('span');
                label.className = 'label';
                label.textContent = m.label;
                item.appendChild(dot);
                item.appendChild(label);
                legendHost.appendChild(item);
            });
        }
    }

    // Pending vs completed per module (bar)
    var statusCanvas = document.getElementById('hmDashStatus');
    if (statusCanvas && data.status && data.status.length) {
        new Chart(statusCanvas, {
            type: 'bar',
            data: {
                labels: data.status.map(function (m) { return m.label; }),
                datasets: [
                    {
                        label: data.labels.pending,
                        data: data.status.map(function (m) { return m.pending; }),
                        backgroundColor: palette.amber,
                        borderRadius: 6,
                        maxBarThickness: 34,
                    },
                    {
                        label: data.labels.completed,
                        data: data.status.map(function (m) { return m.completed; }),
                        backgroundColor: palette.primary,
                        borderRadius: 6,
                        maxBarThickness: 34,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: legendBottom(), tooltip: sharedTooltip },
                scales: gridScales(),
            },
        });
    }

    // Complaints / inquiries per branch (administrators only)
    var branchesCanvas = document.getElementById('hmDashBranches');
    if (branchesCanvas && data.branches) {
        new Chart(branchesCanvas, {
            type: 'bar',
            data: {
                labels: data.branches.labels,
                datasets: [
                    {
                        label: data.labels.complaints,
                        data: data.branches.complaints,
                        backgroundColor: palette.primaryLight,
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: data.labels.inquiries,
                        data: data.branches.inquiries,
                        backgroundColor: palette.dark,
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: legendBottom(), tooltip: sharedTooltip },
                scales: gridScales(),
            },
        });
    }
})();
