<?php
$pageTitle = 'Sales Charts';
$activePage = 'sales_charts';
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

        .period-toggle {
            display: flex;
            gap: 4px;
            background: var(--bg);
            padding: 4px;
            border-radius: 0;
            border: 1px solid var(--border);
        }

        .period-btn {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            background: none;
            color: var(--text-muted);
            font-family: var(--mono);
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .period-btn.active {
            background: var(--blue);
            color: var(--bg);
            font-weight: 600;
        }

        .period-btn:not(.active):hover {
            color: var(--text);
        }
    </style>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> sales <span>/</span> charts</div>
            <h2>Sales Charts</h2>
        </div>
    </div>

    <div class="content">
        <div class="charts-grid">

            <!-- Orders per Month -->
            <div class="chart-card full-width">
                <div class="chart-card-header">
                    <div>
                        <h3>Orders Over Time</h3>
                        <div class="sub">Order count by period</div>
                    </div>
                    <div class="period-toggle">
                        <button class="period-btn" data-period="monthly" onclick="loadOrders('monthly')">Monthly</button>
                        <button class="period-btn active" data-period="yearly" onclick="loadOrders('yearly')">Yearly</button>
                    </div>
                </div>
                <div class="chart-body" style="height:300px;position:relative;">
                    <div class="loading-indicator" id="loading-orders">Loading...</div>
                    <div id="orders-wrap" style="display:none;position:absolute;inset:24px;">
                        <canvas id="chart-orders"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue by Territory (bar) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Revenue by Territory</h3>
                        <div class="sub">Total sales YTD per region</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-territory">Loading...</div>
                    <canvas id="chart-territory" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Order Status Breakdown (doughnut) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Order Status Breakdown</h3>
                        <div class="sub">All orders by current status</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-status">Loading...</div>
                    <div style="width:280px;height:280px;display:none;flex-shrink:0;" id="status-wrap">
                        <canvas id="chart-status"></canvas>
                    </div>
                </div>
            </div>

            <!-- Online vs Offline Orders -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Online vs Offline Orders</h3>
                        <div class="sub">Order count by channel</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-channel">Loading...</div>
                    <div style="width:280px;height:280px;display:none;flex-shrink:0;" id="channel-wrap">
                        <canvas id="chart-channel"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top 10 Customers by Revenue -->
            <div class="chart-card full-width">
                <div class="chart-card-header">
                    <div>
                        <h3>Top 10 Customers by Revenue</h3>
                        <div class="sub">Total amount due per customer</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-customers">Loading...</div>
                    <canvas id="chart-customers" style="display:none;"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const BLUE       = '#4fa8ff';
        const BLUE_DIM   = '#2e7fd4';
        const ACCENT     = '#00e5a0';
        const MUTED      = '#7a9088';
        const SURFACE2   = '#1c211f';
        const BORDER     = '#2a3330';
        const TEXT       = '#e8ede9';
        const WARNING    = '#f5a623';
        const DANGER     = '#ff4f4f';
        const PURPLE     = '#c44dff';

        const PALETTE = [
            '#4fa8ff','#2e7fd4','#1a5fa8','#0d4080',
            '#00e5a0','#00b87f','#f5a623','#ff7f50',
            '#c44dff','#7c6ffd','#e85d75','#00c4c4',
        ];

        const STATUS_COLORS = {
            'Shipped':    ACCENT,
            'In Process': WARNING,
            'Approved':   BLUE,
            'Cancelled':  DANGER,
            'Rejected':   '#ff7070',
            'Backordered': MUTED,
        };

        function show(canvasId, loadingId) {
            document.getElementById(loadingId).style.display = 'none';
            document.getElementById(canvasId).style.display  = 'block';
        }

        function fetchData(type, params = {}) {
            const qs = new URLSearchParams({ type, ...params });
            return fetch('sales_charts_data.php?' + qs).then(r => r.json());
        }

        // --- Orders Over Time (line, switchable) ---
        let ordersChart = null;

        function loadOrders(period) {
            document.querySelectorAll('.period-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.period === period);
            });

            const wrap = document.getElementById('orders-wrap');
            document.getElementById('loading-orders').style.display = 'flex';
            wrap.style.display = 'none';

            if (ordersChart) { ordersChart.destroy(); ordersChart = null; }

            const oldCanvas = document.getElementById('chart-orders');
            const newCanvas = document.createElement('canvas');
            newCanvas.id = 'chart-orders';
            oldCanvas.replaceWith(newCanvas);

            fetchData('orders_over_time', { period }).then(data => {
                document.getElementById('loading-orders').style.display = 'none';
                wrap.style.display = 'block';

                ordersChart = new Chart(document.getElementById('chart-orders'), {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.label),
                        datasets: [{
                            label: 'Orders',
                            data: data.map(d => d.value),
                            borderColor: BLUE,
                            backgroundColor: 'rgba(79,168,255,0.08)',
                            borderWidth: 2,
                            pointBackgroundColor: BLUE,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.35,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y.toLocaleString() + ' orders' } }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: MUTED } },
                            y: { grid: { color: BORDER }, ticks: { color: MUTED }, beginAtZero: true }
                        }
                    }
                });
            });
        }

        // --- Revenue by Territory (horizontal bar) ---
        fetchData('revenue_by_territory').then(data => {
            show('chart-territory', 'loading-territory');
            new Chart(document.getElementById('chart-territory'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Revenue',
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

        // --- Order Status Breakdown (doughnut) ---
        fetchData('order_status_breakdown').then(data => {
            document.getElementById('loading-status').style.display = 'none';
            document.getElementById('status-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-status'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: data.map(d => STATUS_COLORS[d.label] || MUTED),
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
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString() + ' orders' } }
                    }
                }
            });
        });

        // --- Online vs Offline (doughnut) ---
        fetchData('online_vs_offline').then(data => {
            document.getElementById('loading-channel').style.display = 'none';
            document.getElementById('channel-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-channel'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: [BLUE, ACCENT],
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
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed.toLocaleString() + ' orders' } }
                    }
                }
            });
        });

        // --- Top 10 Customers by Revenue (bar) ---
        fetchData('top_customers').then(data => {
            show('chart-customers', 'loading-customers');
            new Chart(document.getElementById('chart-customers'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Revenue',
                        data: data.map(d => d.value),
                        backgroundColor: BLUE + 'bb',
                        borderColor: BLUE,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' $' + Number(ctx.parsed.y).toLocaleString(undefined, { maximumFractionDigits: 0 })
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: MUTED, font: { size: 11 } } },
                        y: {
                            grid: { color: BORDER },
                            ticks: {
                                color: MUTED,
                                callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : v >= 1000 ? (v/1000).toFixed(0) + 'K' : v)
                            }
                        }
                    }
                }
            });
        });

        loadOrders('yearly');
    </script>

<?php require 'footer.php'; ?>