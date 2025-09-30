<?php
/**
 * VA Dashboard - Tenable Security Center Vulnerability Analysis
 * Beautiful frontend for monthly vulnerability tracking
 */

$pageTitle = 'VA Dashboard - Vulnerability Analysis';
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
    
    .canvas-container {
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
        min-width: 280px;
        max-width: 350px;
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
    
    .legend {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
        margin-top: 20px;
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
    
    .tooltip-section {
        margin: 10px 0;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .tooltip-section:last-child {
        border-bottom: none;
    }
    
    .tooltip-section-title {
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 8px;
        color: #63b3ed;
    }
    
    .tooltip-severity-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px 8px;
        margin-bottom: 8px;
    }
    
    .tooltip-severity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    
    .severity-label {
        font-weight: 600;
    }
    
    .severity-label.critical {
        color: #fc8181;
    }
    
    .severity-label.high {
        color: #f6ad55;
    }
    
    .severity-label.medium {
        color: #fbb6ce;
    }
    
    .severity-label.low {
        color: #9ae6b4;
    }
    
    .severity-value {
        font-weight: 700;
        min-width: 20px;
        text-align: right;
    }
    
    .tooltip-total {
        display: flex;
        justify-content: space-between;
        font-weight: 600;
        padding-top: 5px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 5px;
    }
</style>

<div class="va-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-shield-virus"></i> VA Dashboard
        </h1>
        <p class="dashboard-subtitle">Tenable Security Center - Monthly Vulnerability Analysis</p>
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
    <h2 class="text-center mb-4">Vulnerability Analysis Results</h2>
    
    <div class="canvas-container">
        <canvas id="vulnerabilityCanvas"></canvas>
        <div class="tooltip" id="tooltip">
            <div class="tooltip-month" id="tooltipMonth"></div>
            <div class="tooltip-section">
                <div class="tooltip-section-title">📈 New Vulnerabilities</div>
                <div class="tooltip-severity-grid">
                    <div class="tooltip-severity-item">
                        <span class="severity-label critical">Critical:</span>
                        <span class="severity-value" id="tooltipNewCritical">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label high">High:</span>
                        <span class="severity-value" id="tooltipNewHigh">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label medium">Medium:</span>
                        <span class="severity-value" id="tooltipNewMedium">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label low">Low:</span>
                        <span class="severity-value" id="tooltipNewLow">0</span>
                    </div>
                </div>
                <div class="tooltip-total">
                    <span class="tooltip-label">Total New:</span>
                    <span class="tooltip-value new-value" id="tooltipNew">0</span>
                </div>
            </div>
            <div class="tooltip-section">
                <div class="tooltip-section-title">📉 Closed Vulnerabilities</div>
                <div class="tooltip-severity-grid">
                    <div class="tooltip-severity-item">
                        <span class="severity-label critical">Critical:</span>
                        <span class="severity-value" id="tooltipClosedCritical">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label high">High:</span>
                        <span class="severity-value" id="tooltipClosedHigh">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label medium">Medium:</span>
                        <span class="severity-value" id="tooltipClosedMedium">0</span>
                    </div>
                    <div class="tooltip-severity-item">
                        <span class="severity-label low">Low:</span>
                        <span class="severity-value" id="tooltipClosedLow">0</span>
                    </div>
                </div>
                <div class="tooltip-total">
                    <span class="tooltip-label">Total Closed:</span>
                    <span class="tooltip-value closed-value" id="tooltipClosed">0</span>
                </div>
            </div>
            <div class="tooltip-stat">
                <span class="tooltip-label">Net Change:</span>
                <span class="tooltip-value" id="tooltipNet">0</span>
            </div>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #e53e3e;"></div>
            <span>Q1 (Jan-Mar)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #ed8936;"></div>
            <span>Q2 (Apr-Jun)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #48bb78;"></div>
            <span>Q3 (Jul-Sep)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #805ad5;"></div>
            <span>Q4 (Oct-Dec)</span>
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
        <div class="stat-card">
            <div class="stat-value" id="avgPerMonth">0</div>
            <div class="stat-label">Avg New per Month</div>
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
let arcData = [];

// Colors for the arcs (quarterly colors)
const colors = [
    '#e53e3e', '#e74c3c', '#dd6b20', 
    '#ed8936', '#f6ad55', '#ecc94b',
    '#48bb78', '#38b2ac', '#4299e1',
    '#805ad5', '#9f7aea', '#d53f8c'
];

document.addEventListener('DOMContentLoaded', function() {
    canvas = document.getElementById('vulnerabilityCanvas');
    ctx = canvas.getContext('2d');
    tooltip = document.getElementById('tooltip');
    
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('configForm').addEventListener('submit', handleFormSubmit);
    
    if (canvas) {
        canvas.addEventListener('mousemove', handleCanvasMouseMove);
        canvas.addEventListener('mouseleave', handleCanvasMouseLeave);
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
    drawVisualization();
}

function drawVisualization() {
    if (!ctx || vulnerabilityData.length === 0) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    arcData = [];
    
    const centerX = canvas.width / 2;
    const baseY = canvas.height - 20;
    const maxRadius = Math.min(canvas.width, canvas.height * 1.8) / 2;
    const arcWidth = 25;
    const arcSpacing = 8;
    
    vulnerabilityData.forEach((data, index) => {
        const radius = maxRadius - (index * (arcWidth + arcSpacing));
        const arcInfo = {
            month: data.month,
            new: data.new,
            closed: data.closed,
            net: data.net,
            severity: data.severity || { new: {}, closed: {} },
            centerX,
            centerY: baseY,
            radius,
            arcWidth,
            color: colors[index % colors.length]
        };
        arcData.push(arcInfo);
        
        // Draw arc
        ctx.beginPath();
        ctx.arc(centerX, baseY, radius, Math.PI, 0, false);
        ctx.lineWidth = arcWidth;
        ctx.strokeStyle = colors[index % colors.length];
        ctx.lineCap = 'round';
        ctx.stroke();
        
        // Draw circles at ends
        const leftX = centerX - radius;
        const rightX = centerX + radius;
        
        ctx.beginPath();
        ctx.arc(leftX, baseY, arcWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
        
        ctx.beginPath();
        ctx.arc(rightX, baseY, arcWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
    });
    
    // Draw base line
    ctx.beginPath();
    ctx.moveTo(0, baseY);
    ctx.lineTo(canvas.width, baseY);
    ctx.lineWidth = 3;
    ctx.strokeStyle = '#2d3748';
    ctx.stroke();
}

function isPointInArc(x, y, arc) {
    const dx = x - arc.centerX;
    const dy = y - arc.centerY;
    const distance = Math.sqrt(dx * dx + dy * dy);
    
    if (dy > 0) return false;
    
    const minRadius = arc.radius - arc.arcWidth / 2;
    const maxRadius = arc.radius + arc.arcWidth / 2;
    
    return distance >= minRadius && distance <= maxRadius;
}

function handleCanvasMouseMove(e) {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left - 40;
    const y = e.clientY - rect.top - 40;
    
    let found = false;
    
    for (let i = arcData.length - 1; i >= 0; i--) {
        const arc = arcData[i];
        if (isPointInArc(x, y, arc)) {
            // Update month
            document.getElementById('tooltipMonth').textContent = arc.month;
            
            // Update new vulnerabilities by severity
            document.getElementById('tooltipNewCritical').textContent = arc.severity.new.critical || 0;
            document.getElementById('tooltipNewHigh').textContent = arc.severity.new.high || 0;
            document.getElementById('tooltipNewMedium').textContent = arc.severity.new.medium || 0;
            document.getElementById('tooltipNewLow').textContent = arc.severity.new.low || 0;
            document.getElementById('tooltipNew').textContent = arc.new;
            
            // Update closed vulnerabilities by severity
            document.getElementById('tooltipClosedCritical').textContent = arc.severity.closed.critical || 0;
            document.getElementById('tooltipClosedHigh').textContent = arc.severity.closed.high || 0;
            document.getElementById('tooltipClosedMedium').textContent = arc.severity.closed.medium || 0;
            document.getElementById('tooltipClosedLow').textContent = arc.severity.closed.low || 0;
            document.getElementById('tooltipClosed').textContent = arc.closed;
            
            // Update net change
            const netElement = document.getElementById('tooltipNet');
            netElement.textContent = arc.net > 0 ? `+${arc.net}` : arc.net;
            netElement.className = 'tooltip-value ' + 
                (arc.net > 0 ? 'net-positive' : arc.net < 0 ? 'net-negative' : 'net-neutral');
            
            // Position tooltip
            tooltip.style.left = e.clientX - rect.left + 20 + 'px';
            tooltip.style.top = e.clientY - rect.top - 120 + 'px';
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

function updateStatistics() {
    if (vulnerabilityData.length === 0) return;
    
    let totalNew = 0;
    let totalClosed = 0;
    
    vulnerabilityData.forEach(data => {
        totalNew += data.new;
        totalClosed += data.closed;
    });
    
    const netChange = totalNew - totalClosed;
    const avgPerMonth = Math.round(totalNew / vulnerabilityData.length);
    
    document.getElementById('totalNew').textContent = totalNew;
    document.getElementById('totalClosed').textContent = totalClosed;
    document.getElementById('netChange').textContent = netChange > 0 ? `+${netChange}` : netChange;
    document.getElementById('avgPerMonth').textContent = avgPerMonth;
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
}

function clearAlerts() {
    document.getElementById('alertContainer').innerHTML = '';
}
</script>

<?php require_once 'includes/footer.php'; ?>