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

    .severity-item {
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

    .stat-card.vgi-card {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .tooltip-vgi {
        background: rgba(251, 191, 36, 0.2);
        padding: 8px;
        border-radius: 8px;
        margin-top: 8px;
    }

    .vgi-value {
        color: #fbbf24;
    }

    .vgi-display {
        text-align: center;
        padding: 20px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border-radius: 12px;
        margin-top: 20px;
    }

    .vgi-label {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .vgi-value-large {
        font-size: 3rem;
        font-weight: 700;
    }

    .vgi-explanation {
        font-size: 0.85rem;
        opacity: 0.85;
        margin-top: 8px;
        font-style: italic;
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
            <div class="tooltip-vgi">
                <div class="tooltip-stat">
                    <span class="tooltip-label">VGI (Vulnerability Generic Index):</span>
                    <span class="tooltip-value vgi-value" id="tooltipVGI">0</span>
                </div>
                <div class="tooltip-stat">
                    <span class="tooltip-label">VGI Change:</span>
                    <span class="tooltip-value vgi-value" id="tooltipVGIChange">0</span>
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
        <div class="info-box-title">📊 About VGI (Vulnerability Generic Index)</div>
        <div class="info-box-content">
            The VGI is calculated using the formula: <strong>((Critical×4×Assets) + (High×3×Assets) + (Medium×2×Assets) + (Low×1×Assets)) ÷ 100</strong>
            <br>This index provides a weighted score that considers both the severity of vulnerabilities and the number of affected assets, giving you a comprehensive view of your security posture.
        </div>
    </div>
    
    <div class="text-center">
        <div class="status-indicator" id="statusIndicator">
            <i class="fas fa-info-circle"></i>
            <span id="statusText">Analysis complete</span>
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
                    <div class="severity-item">
                        <span class="severity-item-label critical">Critical</span>
                        <span class="severity-item-value" id="detailNewCritical">0</span>
                    </div>
                    <div class="severity-item">
                        <span class="severity-item-label high">High</span>
                        <span class="severity-item-value" id="detailNewHigh">0</span>
                    </div>
                    <div class="severity-item">
                        <span class="severity-item-label medium">Medium</span>
                        <span class="severity-item-value" id="detailNewMedium">0</span>
                    </div>
                    <div class="severity-item">
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
                    <div class="severity-item">
                        <span class="severity-item-label critical">Critical</span>
                        <span class="severity-item-value" id="detailClosedCritical">0</span>
                    </div>
                    <div class="severity-item">
                        <span class="severity-item-label high">High</span>
                        <span class="severity-item-value" id="detailClosedHigh">0</span>
                    </div>
                    <div class="severity-item">
                        <span class="severity-item-label medium">Medium</span>
                        <span class="severity-item-value" id="detailClosedMedium">0</span>
                    </div>
                    <div class="severity-item">
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
            <div class="vgi-display">
                <div class="vgi-label">VGI (Vulnerability Generic Index)</div>
                <div class="vgi-value-large" id="detailVGI">0</div>
                <div class="vgi-explanation">
                    Change from previous month: <span id="detailVGIChange">0</span>
                </div>
            </div>
            <button class="clear-filter-btn" onclick="clearMonthFilter()">
                <i class="fas fa-times"></i> Clear Selection
            </button>
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
    populateMonthButtons();

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
            vgi: data.vgi || 0,
            vgiChange: data.vgiChange || 0,
            severity: data.severity || { new: {}, closed: {} },
            current: data.current || {},
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
            
            // Update VGI
            document.getElementById('tooltipVGI').textContent = arc.vgi;
            const vgiChangeElement = document.getElementById('tooltipVGIChange');
            vgiChangeElement.textContent = arc.vgiChange > 0 ? `+${arc.vgiChange}` : arc.vgiChange;
            
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
}

function clearAlerts() {
    document.getElementById('alertContainer').innerHTML = '';
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

    // Update VGI
    document.getElementById('detailVGI').textContent = data.vgi || 0;
    const vgiChange = data.vgiChange || 0;
    document.getElementById('detailVGIChange').textContent = vgiChange > 0 ? `+${vgiChange}` : vgiChange;

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