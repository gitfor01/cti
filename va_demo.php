<?php
/**
 * VA Dashboard Demo - Sample Data Version
 * Demonstrates the VA Dashboard with sample vulnerability data
 */

$pageTitle = 'VA Dashboard Demo - Vulnerability Analysis';
require_once 'includes/header.php';
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate sample data for demonstration with severity breakdown
$sampleData = [
    [
        'month' => '2024-01', 'new' => 45, 'closed' => 32, 'net' => 13,
        'severity' => [
            'new' => ['critical' => 8, 'high' => 15, 'medium' => 18, 'low' => 4],
            'closed' => ['critical' => 5, 'high' => 12, 'medium' => 13, 'low' => 2]
        ]
    ],
    [
        'month' => '2024-02', 'new' => 38, 'closed' => 41, 'net' => -3,
        'severity' => [
            'new' => ['critical' => 6, 'high' => 12, 'medium' => 16, 'low' => 4],
            'closed' => ['critical' => 7, 'high' => 14, 'medium' => 17, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-03', 'new' => 52, 'closed' => 28, 'net' => 24,
        'severity' => [
            'new' => ['critical' => 12, 'high' => 18, 'medium' => 19, 'low' => 3],
            'closed' => ['critical' => 4, 'high' => 9, 'medium' => 12, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-04', 'new' => 41, 'closed' => 45, 'net' => -4,
        'severity' => [
            'new' => ['critical' => 7, 'high' => 13, 'medium' => 17, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 16, 'medium' => 18, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-05', 'new' => 35, 'closed' => 38, 'net' => -3,
        'severity' => [
            'new' => ['critical' => 5, 'high' => 11, 'medium' => 15, 'low' => 4],
            'closed' => ['critical' => 6, 'high' => 13, 'medium' => 16, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-06', 'new' => 48, 'closed' => 35, 'net' => 13,
        'severity' => [
            'new' => ['critical' => 9, 'high' => 16, 'medium' => 19, 'low' => 4],
            'closed' => ['critical' => 5, 'high' => 11, 'medium' => 16, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-07', 'new' => 29, 'closed' => 52, 'net' => -23,
        'severity' => [
            'new' => ['critical' => 4, 'high' => 8, 'medium' => 14, 'low' => 3],
            'closed' => ['critical' => 11, 'high' => 18, 'medium' => 20, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-08', 'new' => 43, 'closed' => 39, 'net' => 4,
        'severity' => [
            'new' => ['critical' => 8, 'high' => 14, 'medium' => 17, 'low' => 4],
            'closed' => ['critical' => 7, 'high' => 13, 'medium' => 16, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-09', 'new' => 37, 'closed' => 44, 'net' => -7,
        'severity' => [
            'new' => ['critical' => 6, 'high' => 12, 'medium' => 15, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 15, 'medium' => 18, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-10', 'new' => 50, 'closed' => 31, 'net' => 19,
        'severity' => [
            'new' => ['critical' => 11, 'high' => 17, 'medium' => 18, 'low' => 4],
            'closed' => ['critical' => 4, 'high' => 10, 'medium' => 14, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-11', 'new' => 33, 'closed' => 47, 'net' => -14,
        'severity' => [
            'new' => ['critical' => 5, 'high' => 10, 'medium' => 14, 'low' => 4],
            'closed' => ['critical' => 9, 'high' => 16, 'medium' => 19, 'low' => 3]
        ]
    ],
    [
        'month' => '2024-12', 'new' => 40, 'closed' => 42, 'net' => -2,
        'severity' => [
            'new' => ['critical' => 7, 'high' => 13, 'medium' => 16, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 14, 'medium' => 17, 'low' => 3]
        ]
    ]
];

$totalNew = array_sum(array_column($sampleData, 'new'));
$totalClosed = array_sum(array_column($sampleData, 'closed'));
$netChange = $totalNew - $totalClosed;
$avgPerMonth = round($totalNew / count($sampleData));
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
    
    .demo-notice {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
        text-align: center;
    }
    
    .demo-notice h4 {
        margin-bottom: 10px;
        color: #ffd700;
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
        padding: 20px;
        border-radius: 12px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 100;
        font-size: 13px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        min-width: 280px;
        backdrop-filter: blur(10px);
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
    
    .tooltip-section {
        margin-bottom: 15px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .tooltip-section:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .tooltip-section-title {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 8px;
        color: #63b3ed;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .severity-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 6px;
    }
    
    .severity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    
    .severity-label {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .severity-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .severity-critical .severity-dot { background: #e53e3e; }
    .severity-high .severity-dot { background: #ed8936; }
    .severity-medium .severity-dot { background: #d69e2e; }
    .severity-low .severity-dot { background: #38a169; }
    
    .severity-value {
        font-weight: 600;
        min-width: 20px;
        text-align: right;
    }
    
    .total-row {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: 600;
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
    
    .btn-live {
        background: linear-gradient(45deg, #48bb78, #38b2ac);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-live:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        color: white;
        text-decoration: none;
    }
</style>

<div class="va-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-shield-virus"></i> VA Dashboard Demo
        </h1>
        <p class="dashboard-subtitle">Tenable Security Center - Monthly Vulnerability Analysis</p>
    </div>
    
    <div class="demo-notice">
        <h4><i class="fas fa-info-circle"></i> Demo Mode</h4>
        <p>This is a demonstration using sample vulnerability data. The visualization shows how the VA Dashboard would look with real Tenable Security Center data.</p>
        <a href="va_dashboard.php" class="btn-live">
            <i class="fas fa-plug"></i> Connect to Live Tenable SC
        </a>
    </div>
</div>

<div class="visualization-container">
    <h2 class="text-center mb-4">Vulnerability Analysis Results - 2024</h2>
    
    <div class="canvas-container">
        <canvas id="vulnerabilityCanvas"></canvas>
        <div class="tooltip" id="tooltip">
            <div class="tooltip-month" id="tooltipMonth"></div>
            
            <div class="tooltip-section">
                <div class="tooltip-section-title">
                    <i class="fas fa-arrow-up"></i> New Vulnerabilities
                </div>
                <div class="severity-grid">
                    <div class="severity-item severity-critical">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Critical
                        </div>
                        <div class="severity-value" id="newCritical">0</div>
                    </div>
                    <div class="severity-item severity-high">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            High
                        </div>
                        <div class="severity-value" id="newHigh">0</div>
                    </div>
                    <div class="severity-item severity-medium">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Medium
                        </div>
                        <div class="severity-value" id="newMedium">0</div>
                    </div>
                    <div class="severity-item severity-low">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Low
                        </div>
                        <div class="severity-value" id="newLow">0</div>
                    </div>
                </div>
                <div class="tooltip-stat total-row">
                    <span class="tooltip-label">Total New:</span>
                    <span class="tooltip-value new-value" id="tooltipNew">0</span>
                </div>
            </div>
            
            <div class="tooltip-section">
                <div class="tooltip-section-title">
                    <i class="fas fa-arrow-down"></i> Closed Vulnerabilities
                </div>
                <div class="severity-grid">
                    <div class="severity-item severity-critical">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Critical
                        </div>
                        <div class="severity-value" id="closedCritical">0</div>
                    </div>
                    <div class="severity-item severity-high">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            High
                        </div>
                        <div class="severity-value" id="closedHigh">0</div>
                    </div>
                    <div class="severity-item severity-medium">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Medium
                        </div>
                        <div class="severity-value" id="closedMedium">0</div>
                    </div>
                    <div class="severity-item severity-low">
                        <div class="severity-label">
                            <div class="severity-dot"></div>
                            Low
                        </div>
                        <div class="severity-value" id="closedLow">0</div>
                    </div>
                </div>
                <div class="tooltip-stat total-row">
                    <span class="tooltip-label">Total Closed:</span>
                    <span class="tooltip-value closed-value" id="tooltipClosed">0</span>
                </div>
            </div>
            
            <div class="tooltip-section">
                <div class="tooltip-stat">
                    <span class="tooltip-label">Net Change:</span>
                    <span class="tooltip-value" id="tooltipNet">0</span>
                </div>
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
            <div class="stat-value"><?php echo $totalNew; ?></div>
            <div class="stat-label">Total New Vulnerabilities</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalClosed; ?></div>
            <div class="stat-label">Total Closed Vulnerabilities</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $netChange > 0 ? "+$netChange" : $netChange; ?></div>
            <div class="stat-label">Net Change</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $avgPerMonth; ?></div>
            <div class="stat-label">Avg New per Month</div>
        </div>
    </div>
    
    <div class="text-center">
        <div class="status-indicator <?php echo $netChange > 0 ? 'status-growing' : ($netChange < 0 ? 'status-decreasing' : 'status-stable'); ?>">
            <i class="fas fa-<?php echo $netChange > 0 ? 'exclamation-triangle' : ($netChange < 0 ? 'check-circle' : 'minus-circle'); ?>"></i>
            <span>
                <?php 
                if ($netChange > 0) {
                    echo "Vulnerability backlog is growing";
                } elseif ($netChange < 0) {
                    echo "Vulnerability backlog is decreasing";
                } else {
                    echo "Vulnerability backlog is stable";
                }
                ?>
            </span>
        </div>
    </div>
</div>

<script>
// Sample vulnerability data
const vulnerabilityData = <?php echo json_encode($sampleData); ?>;

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
    resizeCanvas();
});

function setupEventListeners() {
    if (canvas) {
        canvas.addEventListener('mousemove', handleCanvasMouseMove);
        canvas.addEventListener('mouseleave', handleCanvasMouseLeave);
        window.addEventListener('resize', resizeCanvas);
    }
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
            
            // Update totals
            document.getElementById('tooltipNew').textContent = arc.new;
            document.getElementById('tooltipClosed').textContent = arc.closed;
            
            // Update severity breakdown for new vulnerabilities
            if (arc.severity && arc.severity.new) {
                document.getElementById('newCritical').textContent = arc.severity.new.critical || 0;
                document.getElementById('newHigh').textContent = arc.severity.new.high || 0;
                document.getElementById('newMedium').textContent = arc.severity.new.medium || 0;
                document.getElementById('newLow').textContent = arc.severity.new.low || 0;
            }
            
            // Update severity breakdown for closed vulnerabilities
            if (arc.severity && arc.severity.closed) {
                document.getElementById('closedCritical').textContent = arc.severity.closed.critical || 0;
                document.getElementById('closedHigh').textContent = arc.severity.closed.high || 0;
                document.getElementById('closedMedium').textContent = arc.severity.closed.medium || 0;
                document.getElementById('closedLow').textContent = arc.severity.closed.low || 0;
            }
            
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
</script>

<?php require_once 'includes/footer.php'; ?>