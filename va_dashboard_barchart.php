<?php
/**
 * VA Dashboard - Bar Chart Version
 * Shows monthly net change in vulnerabilities as a bar chart
 */

$pageTitle = 'VA Dashboard - Bar Chart View';
require_once 'includes/header.php';
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<style>
    .va-dashboard {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: calc(100vh - 200px);
        border-radius: 20px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
    }
    
    .dashboard-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .dashboard-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .dashboard-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 30px;
    }
    
    .config-section {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
    }
    
    .config-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-control, .btn {
        border-radius: 10px;
    }
    
    .btn-analyze {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-analyze:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    
    .visualization-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 40px;
        margin-top: 30px;
        color: #2d3748;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .chart-container {
        position: relative;
        width: 100%;
        height: 500px;
        background: #f7fafc;
        border-radius: 15px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    canvas {
        display: block;
        width: 100%;
        height: 100%;
    }
    
    .tooltip {
        position: absolute;
        background: rgba(26, 32, 44, 0.95);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 100;
        font-size: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        min-width: 200px;
    }
    
    .tooltip.active {
        opacity: 1;
    }
    
    .tooltip-month {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 8px;
        color: #63b3ed;
    }
    
    .tooltip-stat {
        display: flex;
        justify-content: space-between;
        margin: 5px 0;
    }
    
    .tooltip-label {
        color: #cbd5e0;
    }
    
    .tooltip-value {
        font-weight: 600;
        margin-left: 15px;
    }
    
    .new-value {
        color: #fc8181;
    }
    
    .closed-value {
        color: #68d391;
    }
    
    .net-positive {
        color: #fc8181;
    }
    
    .net-negative {
        color: #68d391;
    }
    
    .net-neutral {
        color: #a0aec0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-value {
        font-size: 2.5em;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9em;
        opacity: 0.9;
    }
    
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 40px;
    }
    
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
    
    .alert {
        border-radius: 10px;
        margin-top: 20px;
    }
    
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 20px;
        margin-top: 15px;
    }
    
    .status-growing {
        background: rgba(252, 129, 129, 0.2);
        color: #c53030;
    }
    
    .status-decreasing {
        background: rgba(104, 211, 145, 0.2);
        color: #2f855a;
    }
    
    .status-stable {
        background: rgba(160, 174, 192, 0.2);
        color: #4a5568;
    }

    .stat-card.vgi-card {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border-left: 4px solid #3b82f6;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .info-box-title {
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 8px;
    }

    .info-box-content {
        color: #1e3a8a;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .chart-legend {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #4a5568;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }
</style>

<div class="va-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-chart-bar"></i> VA Dashboard - Bar Chart View
        </h1>
        <p class="dashboard-subtitle">Monthly Net Change in Vulnerabilities</p>
    </div>
    
    <div class="config-section">
        <h3 class="config-title">
            <i class="fas fa-cog"></i> Configuration
        </h3>
        <form id="configForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="scHost" class="form-label">Tenable SC Host</label>
                    <input type="url" class="form-control" id="scHost" placeholder="https://your-sc-instance.com" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="monthsToAnalyze" class="form-label">Months to Analyze</label>
                    <select class="form-control" id="monthsToAnalyze">
                        <option value="6">6 Months</option>
                        <option value="12" selected>12 Months</option>
                        <option value="18">18 Months</option>
                        <option value="24">24 Months</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="accessKey" class="form-label">API Access Key</label>
                    <input type="password" class="form-control" id="accessKey" placeholder="Your API Access Key" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="secretKey" class="form-label">API Secret Key</label>
                    <input type="password" class="form-control" id="secretKey" placeholder="Your API Secret Key" required>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-analyze btn-lg">
                    <i class="fas fa-chart-line"></i> Analyze Vulnerabilities
                </button>
            </div>
        </form>
    </div>
</div>

<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-3">Analyzing vulnerability data...</p>
</div>

<div class="visualization-container" id="visualizationContainer" style="display: none;">
    <h2 class="text-center mb-4">Monthly Net Change in Vulnerabilities</h2>
    
    <div class="chart-container">
        <canvas id="barChart"></canvas>
        <div class="tooltip" id="tooltip">
            <div class="tooltip-month" id="tooltipMonth"></div>
            <div class="tooltip-stat">
                <span class="tooltip-label">New:</span>
                <span class="tooltip-value new-value" id="tooltipNew">0</span>
            </div>
            <div class="tooltip-stat">
                <span class="tooltip-label">Closed:</span>
                <span class="tooltip-value closed-value" id="tooltipClosed">0</span>
            </div>
            <div class="tooltip-stat">
                <span class="tooltip-label">Net Change:</span>
                <span class="tooltip-value" id="tooltipNet">0</span>
            </div>
            <div class="tooltip-stat">
                <span class="tooltip-label">VGI:</span>
                <span class="tooltip-value" id="tooltipVGI">0</span>
            </div>
        </div>
    </div>
    
    <div class="chart-legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #fc8181;"></div>
            <span>Positive Net Change (Vulnerabilities Increased)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #68d391;"></div>
            <span>Negative Net Change (Vulnerabilities Decreased)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #a0aec0;"></div>
            <span>No Change</span>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" id="totalNew">0</div>
            <div class="stat-label">Total New Vulnerabilities</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="totalClosed">0</div>
            <div class="stat-label">Total Closed Vulnerabilities</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="netChange">0</div>
            <div class="stat-label">Net Change</div>
        </div>
        <div class="stat-card vgi-card">
            <div class="stat-value" id="currentVGI">0</div>
            <div class="stat-label">Current VGI</div>
        </div>
        <div class="stat-card vgi-card">
            <div class="stat-value" id="avgVGIChange">0</div>
            <div class="stat-label">Avg VGI Change</div>
        </div>
    </div>
    
    <div class="info-box">
        <div class="info-box-title">📊 About This Chart</div>
        <div class="info-box-content">
            This bar chart displays the <strong>net change</strong> in vulnerabilities for each month. 
            Positive values (red bars) indicate an increase in vulnerabilities, while negative values (green bars) indicate a decrease. 
            Hover over each bar to see detailed information including new vulnerabilities, closed vulnerabilities, and VGI (Vulnerability Generic Index).
        </div>
    </div>
    
    <div class="text-center">
        <div class="status-indicator" id="statusIndicator">
            <i class="fas fa-info-circle"></i>
            <span id="statusText">Analysis complete</span>
        </div>
    </div>
</div>

<div id="alertContainer"></div>

<script>
let vulnerabilityData = [];
let canvas, ctx, tooltip;
let barData = [];

document.addEventListener('DOMContentLoaded', function() {
    canvas = document.getElementById('barChart');
    ctx = canvas.getContext('2d');
    tooltip = document.getElementById('tooltip');
    
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('configForm').addEventListener('submit', handleFormSubmit);
    
    if (canvas) {
        canvas.addEventListener('mousemove', handleCanvasMouseMove);
        canvas.addEventListener('mouseleave', handleCanvasMouseLeave);
        canvas.addEventListener('click', handleCanvasClick);
        window.addEventListener('resize', resizeCanvas);
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = {
        scHost: document.getElementById('scHost').value,
        accessKey: document.getElementById('accessKey').value,
        secretKey: document.getElementById('secretKey').value,
        monthsToAnalyze: parseInt(document.getElementById('monthsToAnalyze').value)
    };
    
    // Show loading spinner
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('visualizationContainer').style.display = 'none';
    clearAlerts();
    
    try {
        const response = await fetch('va_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            vulnerabilityData = result.data;
            displayResults();
        } else {
            showAlert('danger', 'Error: ' + result.message);
        }
    } catch (error) {
        showAlert('danger', 'Network error: ' + error.message);
    } finally {
        document.getElementById('loadingSpinner').style.display = 'none';
    }
}

function displayResults() {
    document.getElementById('visualizationContainer').style.display = 'block';
    resizeCanvas();
    updateStatistics();
    updateStatusIndicator();

    // Scroll to results
    document.getElementById('visualizationContainer').scrollIntoView({
        behavior: 'smooth'
    });
}

function resizeCanvas() {
    if (!canvas) return;
    
    const container = canvas.parentElement;
    canvas.width = container.clientWidth - 80;
    canvas.height = container.clientHeight - 80;
    drawBarChart();
}

function drawBarChart() {
    if (!ctx || vulnerabilityData.length === 0) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    barData = [];
    
    const padding = 60;
    const chartWidth = canvas.width - (padding * 2);
    const chartHeight = canvas.height - (padding * 2);
    
    // Find max absolute value for scaling
    const maxValue = Math.max(...vulnerabilityData.map(d => Math.abs(d.net)));
    // Use 80% of half the chart height for better visibility
    const scale = maxValue > 0 ? (chartHeight / 2) * 0.8 / maxValue : 1;
    
    const barWidth = chartWidth / vulnerabilityData.length;
    const barSpacing = barWidth * 0.2;
    const actualBarWidth = barWidth - barSpacing;
    
    const zeroY = padding + (chartHeight / 2);
    
    // Draw axes
    ctx.strokeStyle = '#2d3748';
    ctx.lineWidth = 2;
    
    // Y-axis
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, padding + chartHeight);
    ctx.stroke();
    
    // X-axis (zero line)
    ctx.beginPath();
    ctx.moveTo(padding, zeroY);
    ctx.lineTo(padding + chartWidth, zeroY);
    ctx.stroke();
    
    // Draw grid lines and labels
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    ctx.font = '12px Arial';
    ctx.fillStyle = '#4a5568';
    ctx.textAlign = 'right';
    
    // Y-axis labels
    const steps = 5;
    for (let i = 0; i <= steps; i++) {
        const value = maxValue * 1.2 * (1 - (i / (steps / 2)));
        const y = padding + (chartHeight * i / steps);
        
        // Grid line
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(padding + chartWidth, y);
        ctx.stroke();
        
        // Label
        ctx.fillText(Math.round(value), padding - 10, y + 4);
    }
    
    // Draw bars
    vulnerabilityData.forEach((data, index) => {
        const x = padding + (index * barWidth) + (barSpacing / 2);
        const netValue = data.net;
        const barHeight = Math.abs(netValue) * scale;
        
        let y, color;
        if (netValue > 0) {
            // Positive (bad) - bar goes up from zero line
            y = zeroY - barHeight;
            color = '#fc8181';
        } else if (netValue < 0) {
            // Negative (good) - bar goes down from zero line
            y = zeroY;
            color = '#68d391';
        } else {
            // Zero
            y = zeroY;
            color = '#a0aec0';
        }
        
        // Store bar data for hover detection
        barData.push({
            x: x,
            y: y,
            width: actualBarWidth,
            height: Math.abs(barHeight),
            month: data.month,
            new: data.new,
            closed: data.closed,
            net: data.net,
            vgi: data.vgi || 0,
            vgiChange: data.vgiChange || 0,
            color: color
        });
        
        // Draw bar
        ctx.fillStyle = color;
        ctx.fillRect(x, y, actualBarWidth, Math.abs(barHeight) || 2);
        
        // Draw month label
        ctx.save();
        ctx.translate(x + actualBarWidth / 2, padding + chartHeight + 15);
        ctx.rotate(-Math.PI / 4);
        ctx.textAlign = 'right';
        ctx.fillStyle = '#2d3748';
        ctx.font = '11px Arial';
        ctx.fillText(data.month, 0, 0);
        ctx.restore();
    });
    
    // Draw axis labels
    ctx.fillStyle = '#2d3748';
    ctx.font = 'bold 14px Arial';
    ctx.textAlign = 'center';
    
    // Y-axis label
    ctx.save();
    ctx.translate(20, padding + chartHeight / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.fillText('Net Change in Vulnerabilities', 0, 0);
    ctx.restore();
    
    // X-axis label
    ctx.fillText('Month', padding + chartWidth / 2, canvas.height - 10);
}

function isPointInBar(x, y, bar) {
    return x >= bar.x && x <= bar.x + bar.width &&
           y >= bar.y && y <= bar.y + bar.height;
}

function handleCanvasMouseMove(e) {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    let found = false;
    
    for (let i = 0; i < barData.length; i++) {
        const bar = barData[i];
        if (isPointInBar(x, y, bar)) {
            // Update tooltip content
            document.getElementById('tooltipMonth').textContent = bar.month;
            document.getElementById('tooltipNew').textContent = bar.new;
            document.getElementById('tooltipClosed').textContent = bar.closed;
            
            const netElement = document.getElementById('tooltipNet');
            netElement.textContent = bar.net > 0 ? `+${bar.net}` : bar.net;
            netElement.className = 'tooltip-value ' + 
                (bar.net > 0 ? 'net-positive' : bar.net < 0 ? 'net-negative' : 'net-neutral');
            
            document.getElementById('tooltipVGI').textContent = bar.vgi;
            
            // Position tooltip
            tooltip.style.left = e.clientX - rect.left + 20 + 'px';
            tooltip.style.top = e.clientY - rect.top - 100 + 'px';
            tooltip.classList.add('active');
            
            canvas.style.cursor = 'pointer';
            found = true;
            break;
        }
    }
    
    if (!found) {
        tooltip.classList.remove('active');
        canvas.style.cursor = 'default';
    }
}

function handleCanvasMouseLeave() {
    tooltip.classList.remove('active');
    canvas.style.cursor = 'default';
}

function handleCanvasClick(e) {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    for (let i = 0; i < barData.length; i++) {
        const bar = barData[i];
        if (isPointInBar(x, y, bar)) {
            showAlert('info', `<strong>${bar.month}</strong><br>
                New: ${bar.new} | Closed: ${bar.closed} | Net: ${bar.net > 0 ? '+' + bar.net : bar.net} | VGI: ${bar.vgi}`);
            break;
        }
    }
}

function updateStatistics() {
    if (vulnerabilityData.length === 0) return;
    
    let totalNew = 0;
    let totalClosed = 0;
    let totalVGIChange = 0;
    
    vulnerabilityData.forEach(data => {
        totalNew += data.new;
        totalClosed += data.closed;
        totalVGIChange += data.vgiChange || 0;
    });
    
    const netChange = totalNew - totalClosed;
    const currentVGI = vulnerabilityData[vulnerabilityData.length - 1].vgi || 0;
    const avgVGIChange = (totalVGIChange / vulnerabilityData.length).toFixed(2);
    
    document.getElementById('totalNew').textContent = totalNew;
    document.getElementById('totalClosed').textContent = totalClosed;
    document.getElementById('netChange').textContent = netChange > 0 ? `+${netChange}` : netChange;
    document.getElementById('currentVGI').textContent = currentVGI;
    document.getElementById('avgVGIChange').textContent = avgVGIChange > 0 ? `+${avgVGIChange}` : avgVGIChange;
}

function updateStatusIndicator() {
    if (vulnerabilityData.length === 0) return;
    
    const totalNew = vulnerabilityData.reduce((sum, data) => sum + data.new, 0);
    const totalClosed = vulnerabilityData.reduce((sum, data) => sum + data.closed, 0);
    const netChange = totalNew - totalClosed;
    
    const indicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    
    if (netChange > 0) {
        indicator.className = 'status-indicator status-growing';
        statusText.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Vulnerability backlog is growing';
    } else if (netChange < 0) {
        indicator.className = 'status-indicator status-decreasing';
        statusText.innerHTML = '<i class="fas fa-check-circle"></i> Vulnerability backlog is decreasing';
    } else {
        indicator.className = 'status-indicator status-stable';
        statusText.innerHTML = '<i class="fas fa-minus-circle"></i> Vulnerability backlog is stable';
    }
}

function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function clearAlerts() {
    document.getElementById('alertContainer').innerHTML = '';
}
</script>

<?php require_once 'includes/footer.php'; ?>