<?php
$pageTitle = 'Vendor Charts';
$activePage = 'vendors_charts';
require 'header.php';
?>

    <style>
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0;
            overflow: hidden;
        }

        .chart-card.full-width {
            grid-column: 1 / -1;
        }

        .chart-card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-card-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .chart-card-header .sub {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .chart-body {
            padding: 24px;
            position: relative;
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-body canvas {
            width: 100% !important;
        }

        .loading-indicator {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--mono);
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> purchasing <span>/</span> vendor charts</div>
            <h2>Vendor Charts</h2>
        </div>
    </div>

    <div class="content">
        <div class="charts-grid">

            <!-- Top 10 Vendors by Spend -->
            <div class="chart-card full-width">
                <div class="chart-card-header">
                    <div>
                        <h3>Top 10 Vendors by Total Spend</h3>
                        <div class="sub">Cumulative purchase order value per vendor</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-top">Loading...</div>
                    <canvas id="chart-top" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Vendors by Credit Rating (doughnut) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Vendors by Credit Rating</h3>
                        <div class="sub">Count of vendors per rating tier</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-credit">Loading...</div>
                    <div style="width:280px;height:280px;display:none;flex-shrink:0;" id="credit-wrap">
                        <canvas id="chart-credit"></canvas>
                    </div>
                </div>
            </div>

            <!-- Active vs Inactive (doughnut) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Active vs Inactive Vendors</h3>
                        <div class="sub">Current vendor status split</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-active">Loading...</div>
                    <div style="width:280px;height:280px;display:none;flex-shrink:0;" id="active-wrap">
                        <canvas id="chart-active"></canvas>
                    </div>
                </div>
            </div>

            <!-- Orders per Vendor (bar distribution) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Order Volume Distribution</h3>
                        <div class="sub">Vendors grouped by number of orders placed</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-orders">Loading...</div>
                    <canvas id="chart-orders" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Preferred vs Non-Preferred Spend -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Preferred vs Non-Preferred Spend</h3>
                        <div class="sub">Total spend split by preferred vendor flag</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-preferred">Loading...</div>
                    <div style="width:280px;height:280px;display:none;flex-shrink:0;" id="preferred-wrap">
                        <canvas id="chart-preferred"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const PURPLE     = '#c44dff';
        const PURPLE_DIM = '#9b38d4';
        const ACCENT     = '#00e5a0';
        const MUTED      = '#7a9088';
        const BORDER     = '#2a3330';
        const TEXT       = '#e8ede9';
        const WARNING    = '#f5a623';
        const DANGER     = '#ff4f4f';
        const BLUE       = '#4fa8ff';

        const PALETTE = [
            '#c44dff','#9b38d4','#7c22ab','#5e1280',
            '#00e5a0','#00b87f','#4fa8ff','#f5a623',
            '#ff7f50','#e85d75','#7c6ffd','#00c4c4',
        ];

        const CREDIT_COLORS = {
            'Superior':  ACCENT,
            'Excellent': '#4fffca',
            'Above Avg': BLUE,
            'Average':   WARNING,
            'Below Avg': DANGER,
        };

        function show(canvasId, loadingId) {
            document.getElementById(loadingId).style.display = 'none';
            document.getElementById(canvasId).style.display  = 'block';
        }

        function fetchData(type) {
            return fetch('vendors_charts_data.php?type=' + type).then(r => r.json());
        }

        // --- Top 10 Vendors by Spend (horizontal bar) ---
        fetchData('top_vendors_by_spend').then(data => {
            show('chart-top', 'loading-top');
            new Chart(document.getElementById('chart-top'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Total Spend',
                        data: data.map(d => d.value),
                        backgroundColor: data.map((_, i) => PALETTE[i % PALETTE.length]),
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' $' + Number(ctx.parsed.x).toLocaleString(undefined, { maximumFractionDigits: 0 })
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: BORDER },
                            ticks: {
                                color: MUTED,
                                callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : v >= 1000 ? (v/1000).toFixed(0) + 'K' : v)
                            }
                        },
                        y: { grid: { display: false }, ticks: { color: TEXT, font: { size: 11 } } }
                    }
                }
            });
        });

        // --- Vendors by Credit Rating (doughnut) ---
        fetchData('vendors_by_credit_rating').then(data => {
            document.getElementById('loading-credit').style.display = 'none';
            document.getElementById('credit-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-credit'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: data.map(d => CREDIT_COLORS[d.label] || MUTED),
                        borderColor: '#151918',
                        borderWidth: 3,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: TEXT, padding: 12, usePointStyle: true, pointStyleWidth: 10, font: { size: 11 } }
                        },
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' vendors' } }
                    }
                }
            });
        });

        // --- Active vs Inactive (doughnut) ---
        fetchData('active_vs_inactive').then(data => {
            document.getElementById('loading-active').style.display = 'none';
            document.getElementById('active-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-active'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: [ACCENT, DANGER],
                        borderColor: '#151918',
                        borderWidth: 3,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: TEXT, padding: 16, usePointStyle: true, pointStyleWidth: 10 }
                        },
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' vendors' } }
                    }
                }
            });
        });

        // --- Order Volume Distribution (bar) ---
        fetchData('order_volume_distribution').then(data => {
            show('chart-orders', 'loading-orders');
            new Chart(document.getElementById('chart-orders'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Vendors',
                        data: data.map(d => d.value),
                        backgroundColor: PURPLE + 'bb',
                        borderColor: PURPLE,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' vendors' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: MUTED, font: { size: 11 } } },
                        y: { grid: { color: BORDER }, ticks: { color: MUTED }, beginAtZero: true }
                    }
                }
            });
        });

        // --- Preferred vs Non-Preferred Spend (doughnut) ---
        fetchData('preferred_vs_nonpreferred_spend').then(data => {
            document.getElementById('loading-preferred').style.display = 'none';
            document.getElementById('preferred-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-preferred'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: [PURPLE, MUTED],
                        borderColor: '#151918',
                        borderWidth: 3,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: TEXT, padding: 16, usePointStyle: true, pointStyleWidth: 10 }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.label + ': $' + Number(ctx.parsed).toLocaleString(undefined, { maximumFractionDigits: 0 })
                            }
                        }
                    }
                }
            });
        });
    </script>

<?php require 'footer.php'; ?>