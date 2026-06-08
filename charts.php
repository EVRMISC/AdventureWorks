<?php
$pageTitle = 'Charts';
$activePage = 'charts';
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
            border-radius: 6px;
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
            background: var(--accent);
            color: var(--bg);
            font-weight: 600;
        }

        .period-btn:not(.active):hover {
            color: var(--text);
        }
    </style>

    <div class="topbar">
        <div>
            <div class="breadcrumb"><span>/</span> charts</div>
            <h2>Charts</h2>
        </div>
    </div>

    <div class="content">

        <div class="charts-grid">

            <!-- Employees per Department -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Employees per Department</h3>
                        <div class="sub">Current assignments only</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-dept">Loading...</div>
                    <canvas id="chart-dept" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Gender Breakdown -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Gender Breakdown</h3>
                        <div class="sub">Active employees</div>
                    </div>
                </div>
                <div class="chart-body" style="display:flex;align-items:center;justify-content:center;min-height:280px;">
                    <div class="loading-indicator" id="loading-gender">Loading...</div>
                    <div style="width:260px;height:260px;display:none;flex-shrink:0;" id="gender-wrap">
                        <canvas id="chart-gender"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pay Rate Distribution -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Pay Rate Distribution</h3>
                        <div class="sub">Latest rate per employee</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-pay">Loading...</div>
                    <canvas id="chart-pay" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Hires per Year -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3>Hires per Year</h3>
                        <div class="sub">All employees including inactive</div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="loading-indicator" id="loading-hires">Loading...</div>
                    <canvas id="chart-hires" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Total Salary Sent - Full Width -->
            <div class="chart-card full-width">
                <div class="chart-card-header">
                    <div>
                        <h3>Total Salary Sent</h3>
                        <div class="sub">Estimated from pay rate records</div>
                    </div>
                    <div class="period-toggle">
                        <button class="period-btn" data-period="daily"   onclick="loadSalary('daily')">Daily</button>
                        <button class="period-btn" data-period="weekly"  onclick="loadSalary('weekly')">Weekly</button>
                        <button class="period-btn active" data-period="yearly" onclick="loadSalary('yearly')">Yearly</button>
                    </div>
                </div>
                <div class="chart-body" style="height:320px;position:relative;">
                    <div class="loading-indicator" id="loading-salary">Loading...</div>
                    <div id="salary-wrap" style="display:none;position:absolute;inset:24px;">
                        <canvas id="chart-salary"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const ACCENT      = '#00e5a0';
        const ACCENT_DIM  = '#00b87f';
        const MUTED       = '#7a9088';
        const SURFACE2    = '#1c211f';
        const BORDER      = '#2a3330';
        const TEXT        = '#e8ede9';

        const PALETTE = [
            '#00e5a0','#00b87f','#009e6e','#007a55','#005c40',
            '#4fffca','#a0ffe0','#00c4c4','#0096b4','#006a8a',
            '#f5a623','#ff7f50','#e85d75','#c44dff','#7c6ffd',
        ];

        Chart.defaults.color           = MUTED;
        Chart.defaults.borderColor     = BORDER;
        Chart.defaults.font.family     = "'IBM Plex Mono', monospace";
        Chart.defaults.font.size       = 11;

        let salaryChart = null;

        function show(canvasId, loadingId) {
            document.getElementById(loadingId).style.display = 'none';
            document.getElementById(canvasId).style.display  = 'block';
        }

        function fetchData(type, params = {}) {
            const qs = new URLSearchParams({ type, ...params });
            return fetch('charts_data.php?' + qs).then(r => r.json());
        }

        // --- Employees per Department (horizontal bar) ---
        fetchData('employees_per_department').then(data => {
            show('chart-dept', 'loading-dept');
            new Chart(document.getElementById('chart-dept'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Employees',
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
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + ' employees' } }
                    },
                    scales: {
                        x: { grid: { color: BORDER }, ticks: { color: MUTED } },
                        y: { grid: { display: false }, ticks: { color: TEXT, font: { size: 11 } } }
                    }
                }
            });
        });

        // --- Gender Breakdown (doughnut) ---
        fetchData('gender_breakdown').then(data => {
            document.getElementById('loading-gender').style.display = 'none';
            document.getElementById('gender-wrap').style.display    = 'block';
            new Chart(document.getElementById('chart-gender'), {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        data: data.map(d => d.value),
                        backgroundColor: [ACCENT, '#4fffca'],
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
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' employees' } }
                    }
                }
            });
        });

        // --- Pay Rate Distribution (bar) ---
        fetchData('pay_rate_distribution').then(data => {
            show('chart-pay', 'loading-pay');
            new Chart(document.getElementById('chart-pay'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Employees',
                        data: data.map(d => d.value),
                        backgroundColor: ACCENT + 'bb',
                        borderColor: ACCENT,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' employees' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: TEXT } },
                        y: { grid: { color: BORDER }, ticks: { color: MUTED } }
                    }
                }
            });
        });

        // --- Hires per Year (line) ---
        fetchData('hires_per_year').then(data => {
            show('chart-hires', 'loading-hires');
            new Chart(document.getElementById('chart-hires'), {
                type: 'line',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [{
                        label: 'Hires',
                        data: data.map(d => d.value),
                        borderColor: ACCENT,
                        backgroundColor: 'rgba(0,229,160,0.08)',
                        borderWidth: 2,
                        pointBackgroundColor: ACCENT,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' hires' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: MUTED } },
                        y: { grid: { color: BORDER }, ticks: { color: MUTED }, beginAtZero: true }
                    }
                }
            });
        });

        // --- Total Salary (line, switchable) ---
        function loadSalary(period) {
            document.querySelectorAll('.period-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.period === period);
            });

            const wrap = document.getElementById('salary-wrap');
            document.getElementById('loading-salary').style.display = 'flex';
            wrap.style.display = 'none';

            // Destroy old chart and replace canvas to avoid warping
            if (salaryChart) {
                salaryChart.destroy();
                salaryChart = null;
            }

            const oldCanvas = document.getElementById('chart-salary');
            const newCanvas = document.createElement('canvas');
            newCanvas.id = 'chart-salary';
            oldCanvas.replaceWith(newCanvas);

            fetchData('total_salary', { period }).then(data => {
                document.getElementById('loading-salary').style.display = 'none';
                wrap.style.display = 'block';

                const labels = data.map(d => d.label);
                const values = data.map(d => d.value);

                salaryChart = new Chart(document.getElementById('chart-salary'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Total Salary ($)',
                            data: values,
                            borderColor: '#f5a623',
                            backgroundColor: 'rgba(245,166,35,0.08)',
                            borderWidth: 2,
                            pointBackgroundColor: '#f5a623',
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
                            tooltip: {
                                callbacks: {
                                    label: ctx => ' $' + Number(ctx.parsed.y).toLocaleString(undefined, { maximumFractionDigits: 0 })
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: MUTED } },
                            y: {
                                grid: { color: BORDER },
                                ticks: {
                                    color: MUTED,
                                    callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : v >= 1000 ? (v/1000).toFixed(0) + 'K' : v)
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        }

        loadSalary('yearly');
    </script>

<?php require 'footer.php'; ?>