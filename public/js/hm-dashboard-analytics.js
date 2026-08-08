(function () {
    if (typeof Chart === 'undefined' || !window.hmDashCharts) {
        return;
    }

    var data = window.hmDashCharts;
    var isRtl = data.locale === 'ar';

    var css = getComputedStyle(document.documentElement);
    function token(name, fallback) {
        var value = css.getPropertyValue(name).trim();
        return value !== '' ? value : fallback;
    }

    var palette = {
        primary: token('--bd-primary', '#2d60ff'),
        primaryDeep: token('--bd-primary-deep', '#1814f3'),
        teal: token('--bd-teal', '#16dbcc'),
        success: token('--bd-success', '#41d4a8'),
        warning: token('--bd-warning', '#fcaa0b'),
        danger: token('--bd-danger', '#fe5c73'),
        muted: token('--bd-muted', '#718ebf'),
        title: token('--bd-title', '#343c6a'),
        line: token('--bd-line', '#e6eff5'),
        series: ['#2d60ff', '#16dbcc', '#fcaa0b', '#fe5c73', '#41d4a8', '#1814f3', '#718ebf', '#343c6a', '#9899ee', '#f3b7ff', '#7f9cf5', '#f6a5c0'],
    };

    var fontFamily = token('--hm-font-family', 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif');
    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = palette.muted;

    var sharedTooltip = {
        rtl: isRtl,
        textDirection: isRtl ? 'rtl' : 'ltr',
        backgroundColor: palette.title,
        titleFont: { size: 13, weight: '700' },
        bodyFont: { size: 12 },
        padding: 12,
        cornerRadius: 10,
        displayColors: false,
    };

    function gridScales(showY) {
        return {
            x: {
                reverse: isRtl,
                grid: { display: false },
                ticks: { maxTicksLimit: 10, maxRotation: 0 },
            },
            y: {
                position: isRtl ? 'right' : 'left',
                beginAtZero: true,
                border: { display: false },
                grid: { color: palette.line, drawTicks: false },
                ticks: { precision: 0, display: showY !== false },
            },
        };
    }

    // Activity over the last 30 days (line)
    var trendCanvas = document.getElementById('hmDashTrend');
    if (trendCanvas && data.trend) {
        var ctx = trendCanvas.getContext('2d');
        var fill = ctx.createLinearGradient(0, 0, 0, trendCanvas.parentNode.clientHeight || 300);
        fill.addColorStop(0, 'rgba(45, 96, 255, 0.28)');
        fill.addColorStop(1, 'rgba(45, 96, 255, 0)');

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: data.trend.labels,
                datasets: [{
                    label: data.labels.total,
                    data: data.trend.total,
                    borderColor: palette.primaryDeep,
                    backgroundColor: fill,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.45,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: palette.primaryDeep,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: sharedTooltip },
                scales: gridScales(true),
            },
        });
    }

    // Records per module (doughnut)
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
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: isRtl,
                        labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 12 },
                    },
                    tooltip: sharedTooltip,
                },
            },
        });
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
                        backgroundColor: palette.warning,
                        borderRadius: 8,
                        maxBarThickness: 22,
                    },
                    {
                        label: data.labels.completed,
                        data: data.status.map(function (m) { return m.completed; }),
                        backgroundColor: palette.teal,
                        borderRadius: 8,
                        maxBarThickness: 22,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', rtl: isRtl, labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                    tooltip: sharedTooltip,
                },
                scales: gridScales(true),
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
                        backgroundColor: palette.primary,
                        borderRadius: 8,
                        maxBarThickness: 22,
                    },
                    {
                        label: data.labels.inquiries,
                        data: data.branches.inquiries,
                        backgroundColor: palette.teal,
                        borderRadius: 8,
                        maxBarThickness: 22,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', rtl: isRtl, labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                    tooltip: sharedTooltip,
                },
                scales: gridScales(true),
            },
        });
    }
})();
