<?php
/**
 * VA Dashboard Demo - Bar Chart Version
 * Demonstrates the VA Dashboard with bar chart visualization
 */

$pageTitle = 'VA Dashboard Demo - Bar Chart View';
require_once 'includes/header.php';
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate sample data for demonstration with severity breakdown and VGI
$sampleData = [
    [
        'month' => 'Jan 2024', 'new' => 45, 'closed' => 32, 'net' => 13,
        'severity' => [
            'new' => ['critical' => 8, 'high' => 15, 'medium' => 18, 'low' => 4],
            'closed' => ['critical' => 5, 'high' => 12, 'medium' => 13, 'low' => 2]
        ],
        'current' => [
            'critical' => ['count' => 25, 'assets' => 15],
            'high' => ['count' => 48, 'assets' => 28],
            'medium' => ['count' => 72, 'assets' => 35],
            'low' => ['count' => 35, 'assets' => 18]
        ],
        'vgi' => 2.47,
        'vgiChange' => 0
    ],
    [
        'month' => 'Feb 2024', 'new' => 38, 'closed' => 41, 'net' => -3,
        'severity' => [
            'new' => ['critical' => 6, 'high' => 12, 'medium' => 16, 'low' => 4],
            'closed' => ['critical' => 7, 'high' => 14, 'medium' => 17, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 24, 'assets' => 14],
            'high' => ['count' => 46, 'assets' => 26],
            'medium' => ['count' => 71, 'assets' => 34],
            'low' => ['count' => 36, 'assets' => 19]
        ],
        'vgi' => 2.38,
        'vgiChange' => -0.09
    ],
    [
        'month' => 'Mar 2024', 'new' => 52, 'closed' => 28, 'net' => 24,
        'severity' => [
            'new' => ['critical' => 12, 'high' => 18, 'medium' => 19, 'low' => 3],
            'closed' => ['critical' => 4, 'high' => 9, 'medium' => 12, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 32, 'assets' => 18],
            'high' => ['count' => 55, 'assets' => 32],
            'medium' => ['count' => 78, 'assets' => 38],
            'low' => ['count' => 36, 'assets' => 19]
        ],
        'vgi' => 2.86,
        'vgiChange' => 0.48
    ],
    [
        'month' => 'Apr 2024', 'new' => 41, 'closed' => 45, 'net' => -4,
        'severity' => [
            'new' => ['critical' => 7, 'high' => 13, 'medium' => 17, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 16, 'medium' => 18, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 31, 'assets' => 17],
            'high' => ['count' => 52, 'assets' => 29],
            'medium' => ['count' => 77, 'assets' => 37],
            'low' => ['count' => 37, 'assets' => 20]
        ],
        'vgi' => 2.75,
        'vgiChange' => -0.11
    ],
    [
        'month' => 'May 2024', 'new' => 35, 'closed' => 38, 'net' => -3,
        'severity' => [
            'new' => ['critical' => 5, 'high' => 11, 'medium' => 15, 'low' => 4],
            'closed' => ['critical' => 6, 'high' => 13, 'medium' => 16, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 30, 'assets' => 16],
            'high' => ['count' => 50, 'assets' => 27],
            'medium' => ['count' => 76, 'assets' => 36],
            'low' => ['count' => 38, 'assets' => 21]
        ],
        'vgi' => 2.68,
        'vgiChange' => -0.07
    ],
    [
        'month' => 'Jun 2024', 'new' => 48, 'closed' => 35, 'net' => 13,
        'severity' => [
            'new' => ['critical' => 9, 'high' => 16, 'medium' => 19, 'low' => 4],
            'closed' => ['critical' => 5, 'high' => 11, 'medium' => 16, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 34, 'assets' => 19],
            'high' => ['count' => 55, 'assets' => 31],
            'medium' => ['count' => 79, 'assets' => 38],
            'low' => ['count' => 39, 'assets' => 22]
        ],
        'vgi' => 2.91,
        'vgiChange' => 0.23
    ],
    [
        'month' => 'Jul 2024', 'new' => 29, 'closed' => 52, 'net' => -23,
        'severity' => [
            'new' => ['critical' => 4, 'high' => 8, 'medium' => 14, 'low' => 3],
            'closed' => ['critical' => 11, 'high' => 18, 'medium' => 20, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 27, 'assets' => 14],
            'high' => ['count' => 45, 'assets' => 24],
            'medium' => ['count' => 73, 'assets' => 34],
            'low' => ['count' => 39, 'assets' => 22]
        ],
        'vgi' => 2.42,
        'vgiChange' => -0.49
    ],
    [
        'month' => 'Aug 2024', 'new' => 43, 'closed' => 39, 'net' => 4,
        'severity' => [
            'new' => ['critical' => 8, 'high' => 14, 'medium' => 17, 'low' => 4],
            'closed' => ['critical' => 7, 'high' => 13, 'medium' => 16, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 28, 'assets' => 15],
            'high' => ['count' => 46, 'assets' => 25],
            'medium' => ['count' => 74, 'assets' => 35],
            'low' => ['count' => 40, 'assets' => 23]
        ],
        'vgi' => 2.48,
        'vgiChange' => 0.06
    ],
    [
        'month' => 'Sep 2024', 'new' => 37, 'closed' => 44, 'net' => -7,
        'severity' => [
            'new' => ['critical' => 6, 'high' => 12, 'medium' => 15, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 15, 'medium' => 18, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 26, 'assets' => 14],
            'high' => ['count' => 43, 'assets' => 23],
            'medium' => ['count' => 71, 'assets' => 33],
            'low' => ['count' => 41, 'assets' => 24]
        ],
        'vgi' => 2.35,
        'vgiChange' => -0.13
    ],
    [
        'month' => 'Oct 2024', 'new' => 50, 'closed' => 31, 'net' => 19,
        'severity' => [
            'new' => ['critical' => 11, 'high' => 17, 'medium' => 18, 'low' => 4],
            'closed' => ['critical' => 4, 'high' => 10, 'medium' => 14, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 33, 'assets' => 18],
            'high' => ['count' => 50, 'assets' => 28],
            'medium' => ['count' => 75, 'assets' => 36],
            'low' => ['count' => 42, 'assets' => 25]
        ],
        'vgi' => 2.69,
        'vgiChange' => 0.34
    ],
    [
        'month' => 'Nov 2024', 'new' => 33, 'closed' => 47, 'net' => -14,
        'severity' => [
            'new' => ['critical' => 5, 'high' => 10, 'medium' => 14, 'low' => 4],
            'closed' => ['critical' => 9, 'high' => 16, 'medium' => 19, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 29, 'assets' => 15],
            'high' => ['count' => 44, 'assets' => 24],
            'medium' => ['count' => 70, 'assets' => 33],
            'low' => ['count' => 43, 'assets' => 26]
        ],
        'vgi' => 2.44,
        'vgiChange' => -0.25
    ],
    [
        'month' => 'Dec 2024', 'new' => 40, 'closed' => 42, 'net' => -2,
        'severity' => [
            'new' => ['critical' => 7, 'high' => 13, 'medium' => 16, 'low' => 4],
            'closed' => ['critical' => 8, 'high' => 14, 'medium' => 17, 'low' => 3]
        ],
        'current' => [
            'critical' => ['count' => 28, 'assets' => 15],
            'high' => ['count' => 43, 'assets' => 23],
            'medium' => ['count' => 69, 'assets' => 32],
            'low' => ['count' => 44, 'assets' => 27]
        ],
        'vgi' => 2.38,
        'vgiChange' => -0.06
    ]
];

$totalNew = array_sum(array_column($sampleData, 'new'));
$totalClosed = array_sum(array_column($sampleData, 'closed'));
$netChange = $totalNew - $totalClosed;
$currentVGI = end($sampleData)['vgi'];
$totalVGIChange = array_sum(array_column($sampleData, 'vgiChange'));
$avgVGIChange = round($totalVGIChange / count($sampleData), 2);
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

    .month-filter-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        color: #2d3748;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .month-filter-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        text-align: center;
        color: #2d3748;
    }

    .month-buttons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .month-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .month-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .month-button.active {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
    }

    .month-details-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-top: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .month-details-card.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .month-details-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 3px solid #e2e8f0;
    }

    .month-details-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
    }

    .month-details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-top: 20px;
    }

    .severity-breakdown {
        background: #f7fafc;
        border-radius: 12px;
        padding: 20px;
    }

    .severity-breakdown-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .severity-item-detail {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        margin: 8px 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .severity-item-label {
        font-weight: 600;
        font-size: 1rem;
    }

    .severity-item-label.critical {
        color: #e53e3e;
    }

    .severity-item-label.high {
        color: #dd6b20;
    }

    .severity-item-label.medium {
        color: #d69e2e;
    }

    .severity-item-label.low {
        color: #38a169;
    }

    .severity-item-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
    }

    .severity-total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        font-weight: 700;
        font-size: 1.1rem;
        color: #2d3748;
    }

    .net-change-display {
        text-align: center;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        margin-top: 20px;
    }

    .net-change-label {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .net-change-value {
        font-size: 3rem;
        font-weight: 700;
    }

    .clear-filter-btn {
        background: #718096;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        margin: 20px auto 0;
    }

    .clear-filter-btn:hover {
        background: #4a5568;
        transform: translateY(-2px);
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
    
    .stat-card.vgi-card {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    
    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border-left: 4px solid #3b82f6;
        border-radius: 10px;
        padding: 20px;
        margin: 30px 0;
    }
    
    .info-box-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #1e40af;
    }
    
    .info-box-content {
        font-size: 0.95rem;
        line-height: 1.6;
        color: #374151;
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
            <i class="fas fa-chart-bar"></i> VA Dashboard Demo - Bar Chart
        </h1>
        <p class="dashboard-subtitle">Monthly Net Change in Vulnerabilities</p>
    </div>
    
    <div class="demo-notice">
        <h4><i class="fas fa-info-circle"></i> Demo Mode - Bar Chart View</h4>
        <p>This is a demonstration using sample vulnerability data. The bar chart shows the net change in vulnerabilities for each month.</p>
        <a href="va_dashboard_barchart.php" class="btn-live">
            <i class="fas fa-plug"></i> Connect to Live Tenable SC
        </a>
    </div>
</div>

<div class="visualization-container">
    <h2 class="text-center mb-4">Monthly Net Change in Vulnerabilities - 2024</h2>
    
    <div class="chart-container">
        <canvas id="barChart"></canvas>
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
        <div class="stat-card vgi-card">
            <div class="stat-value"><?php echo $currentVGI; ?></div>
            <div class="stat-label">Current VGI</div>
        </div>
    </div>
    
    <div class="info-box">
        <div class="info-box-title">📊 About VGI (Vulnerability Generic Index)</div>
        <div class="info-box-content">
            The VGI is calculated using the formula: <strong>((Critical×4×Assets) + (High×3×Assets) + (Medium×2×Assets) + (Low×1×Assets)) ÷ 100</strong>
            <br>This index provides a weighted score that considers both the severity of vulnerabilities and the number of affected assets, giving you a comprehensive view of your security posture.
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

    <div class="month-filter-section">
        <h3 class="month-filter-title">
            <i class="fas fa-calendar-alt"></i> Monthly Details
        </h3>
        <div class="month-buttons-grid" id="monthButtonsGrid">
            <!-- Month buttons will be populated dynamically -->
        </div>
        <div class="month-details-card" id="monthDetailsCard">
            <div class="month-details-header">
                <div class="month-details-title" id="selectedMonthTitle">Select a month</div>
            </div>
            <div class="month-details-grid">
                <div class="severity-breakdown">
                    <div class="severity-breakdown-title">
                        📈 New Vulnerabilities
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label critical">Critical</span>
                        <span class="severity-item-value" id="detailNewCritical">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label high">High</span>
                        <span class="severity-item-value" id="detailNewHigh">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label medium">Medium</span>
                        <span class="severity-item-value" id="detailNewMedium">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label low">Low</span>
                        <span class="severity-item-value" id="detailNewLow">0</span>
                    </div>
                    <div class="severity-total">
                        <span>Total New:</span>
                        <span id="detailTotalNew">0</span>
                    </div>
                </div>
                <div class="severity-breakdown">
                    <div class="severity-breakdown-title">
                        📉 Closed Vulnerabilities
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label critical">Critical</span>
                        <span class="severity-item-value" id="detailClosedCritical">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label high">High</span>
                        <span class="severity-item-value" id="detailClosedHigh">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label medium">Medium</span>
                        <span class="severity-item-value" id="detailClosedMedium">0</span>
                    </div>
                    <div class="severity-item-detail">
                        <span class="severity-item-label low">Low</span>
                        <span class="severity-item-value" id="detailClosedLow">0</span>
                    </div>
                    <div class="severity-total">
                        <span>Total Closed:</span>
                        <span id="detailTotalClosed">0</span>
                    </div>
                </div>
            </div>
            <div class="net-change-display">
                <div class="net-change-label">Net Change</div>
                <div class="net-change-value" id="detailNetChange">0</div>
            </div>
            <button class="clear-filter-btn" onclick="clearMonthFilter()">
                <i class="fas fa-times"></i> Clear Selection
            </button>
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
    canvas = document.getElementById('barChart');
    ctx = canvas.getContext('2d');
    tooltip = document.getElementById('tooltip');

    setupEventListeners();
    resizeCanvas();
    populateMonthButtons();
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
    const steps = 4;
    for (let i = 0; i <= steps; i++) {
        // Calculate value from top (positive) to bottom (negative)
        const value = maxValue - (i * (maxValue * 2 / steps));
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
        arcData.push({
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
            severity: data.severity || { new: {}, closed: {} },
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

function populateMonthButtons() {
    const grid = document.getElementById('monthButtonsGrid');
    grid.innerHTML = '';

    vulnerabilityData.forEach((data, index) => {
        const button = document.createElement('button');
        button.className = 'month-button';
        button.textContent = data.month;
        button.onclick = () => selectMonth(index);
        grid.appendChild(button);
    });
}

function selectMonth(index) {
    const data = vulnerabilityData[index];

    // Update active button state
    const buttons = document.querySelectorAll('.month-button');
    buttons.forEach((btn, i) => {
        if (i === index) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Show details card
    const detailsCard = document.getElementById('monthDetailsCard');
    detailsCard.classList.add('active');

    // Update month title
    document.getElementById('selectedMonthTitle').textContent = data.month;

    // Update new vulnerabilities
    document.getElementById('detailNewCritical').textContent = data.severity.new.critical || 0;
    document.getElementById('detailNewHigh').textContent = data.severity.new.high || 0;
    document.getElementById('detailNewMedium').textContent = data.severity.new.medium || 0;
    document.getElementById('detailNewLow').textContent = data.severity.new.low || 0;
    document.getElementById('detailTotalNew').textContent = data.new;

    // Update closed vulnerabilities
    document.getElementById('detailClosedCritical').textContent = data.severity.closed.critical || 0;
    document.getElementById('detailClosedHigh').textContent = data.severity.closed.high || 0;
    document.getElementById('detailClosedMedium').textContent = data.severity.closed.medium || 0;
    document.getElementById('detailClosedLow').textContent = data.severity.closed.low || 0;
    document.getElementById('detailTotalClosed').textContent = data.closed;

    // Update net change
    const netChange = data.net;
    const netElement = document.getElementById('detailNetChange');
    netElement.textContent = netChange > 0 ? `+${netChange}` : netChange;

    // Scroll to details
    detailsCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearMonthFilter() {
    // Remove active state from all buttons
    const buttons = document.querySelectorAll('.month-button');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Hide details card
    document.getElementById('monthDetailsCard').classList.remove('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>