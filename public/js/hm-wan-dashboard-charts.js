(function () {
    if (typeof Chart === 'undefined' || !window.hmWanCharts) {
        return;
    }

    var charts = window.hmWanCharts;
    var labels = window.hmWanChartLabels || {};
    var isRtl = window.hmWanChartLocale === 'ar';

    var palette = {
        primary: '#4f46e5',
        primarySoft: 'rgba(79, 70, 229, 0.10)',
        pending: '#f6821f',
        actionTaken: '#3b82f6',
        activated: '#22a55b',
        type: ['#4f46e5', '#22a55b', '#f6821f', '#3b82f6', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'],
    };

    var fontFamily = getComputedStyle(document.documentElement).getPropertyValue('--hm-font-family').trim()
        || 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = '#8a92a6';

    var sharedTooltip = {
        rtl: isRtl,
        backgroundColor: '#0f2545',
        titleFont: { size: 13, weight: '700' },
        bodyFont: { size: 12 },
        padding: 12,
        cornerRadius: 10,
        displayColors: false,
    };

    function renderLegend(el, items) {
        if (!el) {
            return;
        }

        var total = items.reduce(function (sum, item) { return sum + item.value; }, 0);

        el.innerHTML = items.map(function (item) {
            var percent = total > 0 ? Math.round((item.value / total) * 100) : 0;
            return '<li class="wan-legend__item">'
                + '<span class="wan-legend__dot" style="background:' + item.color + '"></span>'
                + '<span class="wan-legend__label" title="' + item.label + '">' + item.label + '</span>'
                + '<span class="wan-legend__value">' + item.value + ' (' + percent + '%)</span>'
                + '</li>';
        }).join('');
    }

    if (charts.trend && charts.trend.has_data) {
        var trendCanvas = document.getElementById('hmWanTrendChart');

        if (trendCanvas) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: charts.trend.labels,
                    datasets: [{
                        label: labels.notifications || 'Notifications',
                        data: charts.trend.values,
                        borderColor: palette.actionTaken,
                        backgroundColor: 'rgba(59, 130, 246, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: palette.actionTaken,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, sharedTooltip, {
                            callbacks: {
                                label: function (context) {
                                    return (labels.notifications || 'Notifications') + ' ' + context.parsed.y;
                                },
                            },
                        }),
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6, font: { size: 11 } },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { size: 11 } },
                            grid: { color: 'rgba(226, 232, 240, 0.7)', drawBorder: false },
                        },
                    },
                },
            });
        }
    }

    if (charts.type_distribution && charts.type_distribution.has_data) {
        var typeCanvas = document.getElementById('hmWanTypeChart');

        if (typeCanvas) {
            var typeColors = charts.type_distribution.labels.map(function (_, index) {
                return palette.type[index % palette.type.length];
            });

            new Chart(typeCanvas, {
                type: 'doughnut',
                data: {
                    labels: charts.type_distribution.labels,
                    datasets: [{
                        data: charts.type_distribution.values,
                        backgroundColor: typeColors,
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, sharedTooltip, {
                            callbacks: {
                                label: function (context) {
                                    var total = context.dataset.data.reduce(function (s, i) { return s + i; }, 0);
                                    var percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percent + '%)';
                                },
                            },
                        }),
                    },
                },
            });

            renderLegend(document.getElementById('hmWanTypeLegend'), charts.type_distribution.labels.map(function (label, index) {
                return { label: label, value: charts.type_distribution.values[index], color: typeColors[index] };
            }));
        }
    }

    if (charts.workflow_distribution && charts.workflow_distribution.has_data) {
        var workflowCanvas = document.getElementById('hmWanWorkflowChart');

        if (workflowCanvas) {
            var workflowColors = (charts.workflow_distribution.keys || []).map(function (key) {
                if (key === 'pending') { return palette.pending; }
                if (key === 'action_taken') { return palette.actionTaken; }
                return palette.activated;
            });

            new Chart(workflowCanvas, {
                type: 'pie',
                data: {
                    labels: charts.workflow_distribution.labels,
                    datasets: [{
                        data: charts.workflow_distribution.values,
                        backgroundColor: workflowColors,
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: Object.assign({}, sharedTooltip, {
                            callbacks: {
                                label: function (context) {
                                    var total = context.dataset.data.reduce(function (s, i) { return s + i; }, 0);
                                    var percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                    return context.label + ': ' + context.parsed + ' (' + percent + '%)';
                                },
                            },
                        }),
                    },
                },
            });

            renderLegend(document.getElementById('hmWanWorkflowLegend'), charts.workflow_distribution.labels.map(function (label, index) {
                return { label: label, value: charts.workflow_distribution.values[index], color: workflowColors[index] };
            }));
        }
    }
})();
