@extends('layouts.app')

@section('title', 'SMS Analytics')
@section('page-title', 'SMS Analytics & Reports')

@section('page-actions')
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-primary btn-custom" onclick="refreshAnalytics()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-info btn-custom" onclick="exportReport()">
            <i class="fas fa-download"></i> Export Report
        </button>
        <button type="button" class="btn btn-outline-success btn-custom" onclick="scheduleReport()">
            <i class="fas fa-calendar"></i> Schedule Report
        </button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-filter"></i> Date Range
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#" onclick="setDateRange('today')">Today</a>
                <a class="dropdown-item" href="#" onclick="setDateRange('yesterday')">Yesterday</a>
                <a class="dropdown-item" href="#" onclick="setDateRange('week')">This Week</a>
                <a class="dropdown-item" href="#" onclick="setDateRange('month')">This Month</a>
                <a class="dropdown-item" href="#" onclick="setDateRange('quarter')">This Quarter</a>
                <a class="dropdown-item" href="#" onclick="setDateRange('year')">This Year</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showCustomDateRange()">Custom Range</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label class="mb-0 mr-3"><strong>Date Range:</strong></label>
                            <input type="date" class="form-control form-control-sm mr-2" id="start-date" style="width: auto;">
                            <span class="mx-2">to</span>
                            <input type="date" class="form-control form-control-sm mr-3" id="end-date" style="width: auto;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="applyDateFilter()">
                                <i class="fas fa-search"></i> Apply
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="badge badge-info" id="current-range">Last 30 days</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="resetDateFilter()">
                            <i class="fas fa-times"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overview Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1" id="total-sent">0</h3>
                        <p class="mb-0">Total Sent</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="stat-change" id="sent-change">+0% from last period</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1" id="delivered-count">0</h3>
                        <p class="mb-0">Delivered</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="stat-change" id="delivered-change">+0% from last period</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1" id="failed-count">0</h3>
                        <p class="mb-0">Failed</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="stat-change" id="failed-change">+0% from last period</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1" id="success-rate">0%</h3>
                        <p class="mb-0">Success Rate</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="stat-change" id="rate-change">+0% from last period</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- SMS Trends Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> SMS Trends</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="changeTrendView('daily')" id="trend-daily">Daily</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeTrendView('weekly')" id="trend-weekly">Weekly</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeTrendView('monthly')" id="trend-monthly">Monthly</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Driver Performance -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Driver Performance</h5>
            </div>
            <div class="card-body">
                <canvas id="driverChart" height="200"></canvas>
                <div class="mt-3" id="driver-stats">
                    <!-- Driver statistics will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Analytics -->
<div class="row mb-4">
    <!-- Campaign Performance -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Campaign Performance</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAllCampaigns()">
                        View All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Sent</th>
                                <th>Delivered</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody id="campaign-performance">
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Template Usage -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Template Usage</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAllTemplates()">
                        View All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Template</th>
                                <th>Usage</th>
                                <th>Success Rate</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody id="template-usage">
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Analysis & Autoresponder Stats -->
<div class="row mb-4">
    <!-- Error Analysis -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Error Analysis</h5>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="viewErrorDetails()">
                        View Details
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="errorChart" height="150"></canvas>
                <div class="mt-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <h6 class="text-danger" id="network-errors">0</h6>
                            <small class="text-muted">Network Errors</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-warning" id="invalid-numbers">0</h6>
                            <small class="text-muted">Invalid Numbers</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-info" id="rate-limits">0</h6>
                            <small class="text-muted">Rate Limits</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Autoresponder Stats -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-robot"></i> Autoresponder Stats</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAutoresponderDetails()">
                        View Details
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary" id="total-triggers">0</h4>
                            <small class="text-muted">Total Triggers</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success" id="auto-responses">0</h4>
                            <small class="text-muted">Auto Responses</small>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>Triggers</th>
                                <th>Responses</th>
                            </tr>
                        </thead>
                        <tbody id="autoresponder-stats">
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    <div class="py-2">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cost Analysis & Geographic Distribution -->
<div class="row mb-4">
    <!-- Cost Analysis -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> Cost Analysis</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success" id="total-cost">$0.00</h4>
                            <small class="text-muted">Total Cost</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info" id="avg-cost-per-sms">$0.00</h4>
                            <small class="text-muted">Avg Cost/SMS</small>
                        </div>
                    </div>
                </div>
                
                <canvas id="costChart" height="150"></canvas>
                
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Cost by Driver:</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCostBreakdown()">
                            Breakdown
                        </button>
                    </div>
                    <div id="cost-by-driver" class="mt-2">
                        <!-- Cost breakdown will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Geographic Distribution -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-globe"></i> Geographic Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th>Messages</th>
                                <th>Success Rate</th>
                                <th>Avg Cost</th>
                            </tr>
                        </thead>
                        <tbody id="geographic-stats">
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <canvas id="geoChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent SMS Activity</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="filterActivity('all')" id="filter-all">All</button>
                        <button type="button" class="btn btn-outline-success" onclick="filterActivity('delivered')" id="filter-delivered">Delivered</button>
                        <button type="button" class="btn btn-outline-danger" onclick="filterActivity('failed')" id="filter-failed">Failed</button>
                        <button type="button" class="btn btn-outline-warning" onclick="filterActivity('pending')" id="filter-pending">Pending</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Recipient</th>
                                <th>Message</th>
                                <th>Driver</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity">
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-spinner fa-spin"></i> Loading recent activity...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <small class="text-muted">Showing <span id="activity-count">0</span> of <span id="total-activity">0</span> messages</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadMoreActivity()" id="load-more-btn">
                            <i class="fas fa-chevron-down"></i> Load More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Report Modal -->
<div class="modal fade" id="exportReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Analytics Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="export-form">
                    <div class="form-group">
                        <label for="export-format">Export Format</label>
                        <select class="form-control" id="export-format">
                            <option value="pdf">PDF Report</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV Data</option>
                            <option value="json">JSON Data</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="export-date-range">Date Range</label>
                        <select class="form-control" id="export-date-range" onchange="toggleCustomExportRange()">
                            <option value="current">Current Filter Range</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div id="custom-export-range" style="display: none;">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="export-start-date">Start Date</label>
                                    <input type="date" class="form-control" id="export-start-date">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="export-end-date">End Date</label>
                                    <input type="date" class="form-control" id="export-end-date">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Include Sections</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-overview" checked>
                            <label class="form-check-label" for="include-overview">Overview Statistics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-trends" checked>
                            <label class="form-check-label" for="include-trends">Trends & Charts</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-campaigns" checked>
                            <label class="form-check-label" for="include-campaigns">Campaign Performance</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-errors">
                            <label class="form-check-label" for="include-errors">Error Analysis</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-costs">
                            <label class="form-check-label" for="include-costs">Cost Analysis</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include-activity">
                            <label class="form-check-label" for="include-activity">Recent Activity</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateReport()">
                    <i class="fas fa-download"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div class="modal fade" id="scheduleReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Analytics Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="schedule-form">
                    <div class="form-group">
                        <label for="schedule-frequency">Frequency</label>
                        <select class="form-control" id="schedule-frequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-time">Delivery Time</label>
                        <input type="time" class="form-control" id="schedule-time" value="09:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-email">Email Recipients</label>
                        <textarea class="form-control" id="schedule-email" rows="3" 
                                  placeholder="Enter email addresses separated by commas"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-format">Report Format</label>
                        <select class="form-control" id="schedule-format">
                            <option value="pdf">PDF Report</option>
                            <option value="excel">Excel Spreadsheet</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="scheduleReportSubmit()">
                    <i class="fas fa-calendar-plus"></i> Schedule Report
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Analytics Styles */
.stat-card {
    border: none;
    border-radius: 10px;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-change {
    font-size: 0.8rem;
    opacity: 0.9;
}

.chart-container {
    position: relative;
    height: 300px;
}

.table-responsive {
    max-height: 400px;
}

.activity-status {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-delivered {
    background-color: #d4edda;
    color: #155724;
}

.status-failed {
    background-color: #f8d7da;
    color: #721c24;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.driver-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.driver-stat:last-child {
    border-bottom: none;
}

.cost-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.trend-indicator {
    font-size: 0.8rem;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

.trend-neutral {
    color: #6c757d;
}

.btn-group-sm .btn {
    font-size: 0.8rem;
}

.card-header h5 {
    font-size: 1rem;
    font-weight: 600;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
</style>
@endpush

@push('scripts')
<script>
// Analytics JavaScript

let trendsChart, driverChart, errorChart, costChart, geoChart;
let currentDateRange = 'month';
let activityFilter = 'all';
let activityPage = 1;
let activityLoading = false;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range
    setDefaultDateRange();
    
    // Load initial data
    loadAnalyticsData();
    
    // Initialize charts
    initializeCharts();
    
    // Set up auto-refresh
    setInterval(refreshAnalytics, 300000); // Refresh every 5 minutes
});

// Set Default Date Range
function setDefaultDateRange() {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    
    $('#start-date').val(formatDate(startDate));
    $('#end-date').val(formatDate(endDate));
    $('#current-range').text('Last 30 days');
}

// Format Date for Input
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

// Set Date Range
function setDateRange(range) {
    const endDate = new Date();
    let startDate = new Date();
    let rangeText = '';
    
    switch(range) {
        case 'today':
            startDate = new Date();
            rangeText = 'Today';
            break;
        case 'yesterday':
            startDate.setDate(startDate.getDate() - 1);
            endDate.setDate(endDate.getDate() - 1);
            rangeText = 'Yesterday';
            break;
        case 'week':
            startDate.setDate(startDate.getDate() - 7);
            rangeText = 'Last 7 days';
            break;
        case 'month':
            startDate.setDate(startDate.getDate() - 30);
            rangeText = 'Last 30 days';
            break;
        case 'quarter':
            startDate.setDate(startDate.getDate() - 90);
            rangeText = 'Last 90 days';
            break;
        case 'year':
            startDate.setFullYear(startDate.getFullYear() - 1);
            rangeText = 'Last year';
            break;
    }
    
    $('#start-date').val(formatDate(startDate));
    $('#end-date').val(formatDate(endDate));
    $('#current-range').text(rangeText);
    
    currentDateRange = range;
    loadAnalyticsData();
}

// Show Custom Date Range
function showCustomDateRange() {
    // Focus on start date input
    $('#start-date').focus();
}

// Apply Date Filter
function applyDateFilter() {
    const startDate = $('#start-date').val();
    const endDate = $('#end-date').val();
    
    if (!startDate || !endDate) {
        showAlert('error', 'Please select both start and end dates.');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        showAlert('error', 'Start date cannot be after end date.');
        return;
    }
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    $('#current-range').text(`${diffDays} days (${start.toLocaleDateString()} - ${end.toLocaleDateString()})`);
    
    currentDateRange = 'custom';
    loadAnalyticsData();
}

// Reset Date Filter
function resetDateFilter() {
    setDateRange('month');
}

// Load Analytics Data
function loadAnalyticsData() {
    showLoading('.stat-card');
    
    // Simulate API call
    setTimeout(() => {
        loadOverviewStats();
        loadCampaignPerformance();
        loadTemplateUsage();
        loadAutoresponderStats();
        loadGeographicStats();
        loadRecentActivity();
        updateCharts();
        
        hideLoading('.stat-card');
    }, 1000);
}

// Load Overview Statistics
function loadOverviewStats() {
    // Simulate data
    const stats = {
        totalSent: 15420,
        delivered: 14891,
        failed: 529,
        successRate: 96.6,
        changes: {
            sent: 12.5,
            delivered: 13.2,
            failed: -8.3,
            rate: 1.1
        }
    };
    
    $('#total-sent').text(formatNumber(stats.totalSent));
    $('#delivered-count').text(formatNumber(stats.delivered));
    $('#failed-count').text(formatNumber(stats.failed));
    $('#success-rate').text(stats.successRate + '%');
    
    // Update change indicators
    updateChangeIndicator('#sent-change', stats.changes.sent);
    updateChangeIndicator('#delivered-change', stats.changes.delivered);
    updateChangeIndicator('#failed-change', stats.changes.failed);
    updateChangeIndicator('#rate-change', stats.changes.rate);
}

// Update Change Indicator
function updateChangeIndicator(selector, change) {
    const element = $(selector);
    const isPositive = change > 0;
    const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
    const sign = isPositive ? '+' : '';
    
    element.html(`<i class="fas ${icon}"></i> ${sign}${change}% from last period`);
}

// Format Number
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
}

// Load Campaign Performance
function loadCampaignPerformance() {
    const campaigns = [
        { name: 'Welcome Series', sent: 2450, delivered: 2389, rate: 97.5 },
        { name: 'Flash Sale Alert', sent: 5200, delivered: 4998, rate: 96.1 },
        { name: 'Order Confirmation', sent: 3800, delivered: 3762, rate: 99.0 },
        { name: 'Abandoned Cart', sent: 1850, delivered: 1742, rate: 94.2 },
        { name: 'Birthday Wishes', sent: 920, delivered: 895, rate: 97.3 }
    ];
    
    let html = '';
    campaigns.forEach(campaign => {
        const rateClass = campaign.rate >= 95 ? 'text-success' : campaign.rate >= 90 ? 'text-warning' : 'text-danger';
        html += `
            <tr>
                <td>
                    <strong>${campaign.name}</strong>
                </td>
                <td>${formatNumber(campaign.sent)}</td>
                <td>${formatNumber(campaign.delivered)}</td>
                <td class="${rateClass}">${campaign.rate}%</td>
            </tr>
        `;
    });
    
    $('#campaign-performance').html(html);
}

// Load Template Usage
function loadTemplateUsage() {
    const templates = [
        { name: 'Order Confirmation', usage: 3800, rate: 99.0, trend: 'up' },
        { name: 'Welcome Message', usage: 2450, rate: 97.5, trend: 'up' },
        { name: 'Flash Sale', usage: 2100, rate: 96.1, trend: 'down' },
        { name: 'Shipping Update', usage: 1950, rate: 98.2, trend: 'up' },
        { name: 'Payment Reminder', usage: 1200, rate: 94.8, trend: 'neutral' }
    ];
    
    let html = '';
    templates.forEach(template => {
        const rateClass = template.rate >= 95 ? 'text-success' : template.rate >= 90 ? 'text-warning' : 'text-danger';
        const trendIcon = template.trend === 'up' ? 'fa-arrow-up text-success' : 
                         template.trend === 'down' ? 'fa-arrow-down text-danger' : 
                         'fa-minus text-muted';
        
        html += `
            <tr>
                <td>
                    <strong>${template.name}</strong>
                </td>
                <td>${formatNumber(template.usage)}</td>
                <td class="${rateClass}">${template.rate}%</td>
                <td><i class="fas ${trendIcon}"></i></td>
            </tr>
        `;
    });
    
    $('#template-usage').html(html);
}

// Load Autoresponder Stats
function loadAutoresponderStats() {
    $('#total-triggers').text('1,247');
    $('#auto-responses').text('1,198');
    
    const autoresponders = [
        { keyword: 'HELP', triggers: 450, responses: 445 },
        { keyword: 'INFO', triggers: 320, responses: 318 },
        { keyword: 'STOP', triggers: 280, responses: 280 },
        { keyword: 'START', triggers: 197, responses: 155 }
    ];
    
    let html = '';
    autoresponders.forEach(auto => {
        html += `
            <tr>
                <td><strong>${auto.keyword}</strong></td>
                <td>${auto.triggers}</td>
                <td>${auto.responses}</td>
            </tr>
        `;
    });
    
    $('#autoresponder-stats').html(html);
}

// Load Geographic Stats
function loadGeographicStats() {
    const countries = [
        { name: 'United States', messages: 8450, rate: 97.2, cost: '$0.0075' },
        { name: 'Canada', messages: 2100, rate: 96.8, cost: '$0.0080' },
        { name: 'United Kingdom', messages: 1850, rate: 95.5, cost: '$0.0065' },
        { name: 'Australia', messages: 1200, rate: 98.1, cost: '$0.0090' },
        { name: 'Germany', messages: 980, rate: 94.2, cost: '$0.0070' }
    ];
    
    let html = '';
    countries.forEach(country => {
        const rateClass = country.rate >= 95 ? 'text-success' : 'text-warning';
        html += `
            <tr>
                <td><strong>${country.name}</strong></td>
                <td>${formatNumber(country.messages)}</td>
                <td class="${rateClass}">${country.rate}%</td>
                <td>${country.cost}</td>
            </tr>
        `;
    });
    
    $('#geographic-stats').html(html);
}

// Load Recent Activity
function loadRecentActivity() {
    if (activityLoading) return;
    
    activityLoading = true;
    
    // Simulate API call
    setTimeout(() => {
        const activities = [
            {
                time: '2 minutes ago',
                recipient: '+1234567890',
                message: 'Your order #12345 has been confirmed...',
                driver: 'Twilio',
                status: 'delivered',
                cost: '$0.0075'
            },
            {
                time: '5 minutes ago',
                recipient: '+1987654321',
                message: 'Welcome to our service! Use code...',
                driver: 'Vonage',
                status: 'delivered',
                cost: '$0.0080'
            },
            {
                time: '8 minutes ago',
                recipient: '+1122334455',
                message: 'Flash sale: 50% off everything...',
                driver: 'Twilio',
                status: 'failed',
                cost: '$0.0000'
            },
            {
                time: '12 minutes ago',
                recipient: '+1555666777',
                message: 'Your payment of $99.99 has been...',
                driver: 'Plivo',
                status: 'pending',
                cost: '$0.0065'
            },
            {
                time: '15 minutes ago',
                recipient: '+1888999000',
                message: 'Thank you for your purchase...',
                driver: 'Twilio',
                status: 'delivered',
                cost: '$0.0075'
            }
        ];
        
        let html = '';
        activities.forEach(activity => {
            if (activityFilter !== 'all' && activity.status !== activityFilter) {
                return;
            }
            
            const statusClass = `status-${activity.status}`;
            const statusText = activity.status.charAt(0).toUpperCase() + activity.status.slice(1);
            
            html += `
                <tr>
                    <td><small class="text-muted">${activity.time}</small></td>
                    <td><code>${activity.recipient}</code></td>
                    <td>
                        <div class="text-truncate" style="max-width: 200px;" title="${activity.message}">
                            ${activity.message}
                        </div>
                    </td>
                    <td><span class="badge badge-secondary">${activity.driver}</span></td>
                    <td><span class="activity-status ${statusClass}">${statusText}</span></td>
                    <td>${activity.cost}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewMessageDetails('${activity.recipient}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        if (activityPage === 1) {
            $('#recent-activity').html(html);
        } else {
            $('#recent-activity').append(html);
        }
        
        $('#activity-count').text(activities.length * activityPage);
        $('#total-activity').text('1,247');
        
        activityLoading = false;
    }, 500);
}

// Initialize Charts
function initializeCharts() {
    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    trendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Sent',
                    data: [],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Delivered',
                    data: [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Failed',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    
    // Driver Chart
    const driverCtx = document.getElementById('driverChart').getContext('2d');
    driverChart = new Chart(driverCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Error Chart
    const errorCtx = document.getElementById('errorChart').getContext('2d');
    errorChart = new Chart(errorCtx, {
        type: 'bar',
        data: {
            labels: ['Network', 'Invalid Numbers', 'Rate Limits', 'Other'],
            datasets: [{
                label: 'Error Count',
                data: [45, 23, 12, 8],
                backgroundColor: ['#dc3545', '#ffc107', '#fd7e14', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Cost Chart
    const costCtx = document.getElementById('costChart').getContext('2d');
    costChart = new Chart(costCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Daily Cost',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Geographic Chart
    const geoCtx = document.getElementById('geoChart').getContext('2d');
    geoChart = new Chart(geoCtx, {
        type: 'bar',
        data: {
            labels: ['US', 'CA', 'UK', 'AU', 'DE'],
            datasets: [{
                label: 'Messages',
                data: [8450, 2100, 1850, 1200, 980],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Update Charts
function updateCharts() {
    updateTrendsChart();
    updateDriverChart();
    updateCostAnalysis();
}

// Update Trends Chart
function updateTrendsChart() {
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const sentData = [1200, 1450, 1380, 1620, 1890, 2100, 1950];
    const deliveredData = [1165, 1398, 1342, 1567, 1823, 2021, 1881];
    const failedData = [35, 52, 38, 53, 67, 79, 69];
    
    trendsChart.data.labels = labels;
    trendsChart.data.datasets[0].data = sentData;
    trendsChart.data.datasets[1].data = deliveredData;
    trendsChart.data.datasets[2].data = failedData;
    trendsChart.update();
}

// Update Driver Chart
function updateDriverChart() {
    const drivers = ['Twilio', 'Vonage', 'Plivo', 'Infobip', 'MessageBird'];
    const data = [45.2, 28.7, 15.3, 7.8, 3.0];
    
    driverChart.data.labels = drivers;
    driverChart.data.datasets[0].data = data;
    driverChart.update();
    
    // Update driver stats
    let statsHtml = '';
    drivers.forEach((driver, index) => {
        statsHtml += `
            <div class="driver-stat">
                <span>${driver}</span>
                <span class="font-weight-bold">${data[index]}%</span>
            </div>
        `;
    });
    $('#driver-stats').html(statsHtml);
}

// Update Cost Analysis
function updateCostAnalysis() {
    $('#total-cost').text('$115.67');
    $('#avg-cost-per-sms').text('$0.0075');
    
    // Update cost chart
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const costData = [9.50, 11.25, 10.80, 12.75, 14.85, 16.50, 15.25];
    
    costChart.data.labels = labels;
    costChart.data.datasets[0].data = costData;
    costChart.update();
    
    // Update cost by driver
    const costByDriver = [
        { driver: 'Twilio', cost: '$52.30' },
        { driver: 'Vonage', cost: '$33.15' },
        { driver: 'Plivo', cost: '$17.70' },
        { driver: 'Infobip', cost: '$9.02' },
        { driver: 'MessageBird', cost: '$3.50' }
    ];
    
    let costHtml = '';
    costByDriver.forEach(item => {
        costHtml += `
            <div class="cost-item">
                <span>${item.driver}</span>
                <span class="font-weight-bold">${item.cost}</span>
            </div>
        `;
    });
    $('#cost-by-driver').html(costHtml);
    
    // Update error stats
    $('#network-errors').text('45');
    $('#invalid-numbers').text('23');
    $('#rate-limits').text('12');
}

// Change Trend View
function changeTrendView(view) {
    $('.btn-group .btn').removeClass('active');
    $(`#trend-${view}`).addClass('active');
    
    // Update chart based on view
    updateTrendsChart();
}

// Filter Activity
function filterActivity(status) {
    $('.btn-group .btn').removeClass('active');
    $(`#filter-${status}`).addClass('active');
    
    activityFilter = status;
    activityPage = 1;
    loadRecentActivity();
}

// Load More Activity
function loadMoreActivity() {
    activityPage++;
    loadRecentActivity();
}

// View Message Details
function viewMessageDetails(recipient) {
    showAlert('info', `Message details for ${recipient} will be implemented.`);
}

// Refresh Analytics
function refreshAnalytics() {
    loadAnalyticsData();
    showAlert('success', 'Analytics data refreshed.');
}

// Export Report
function exportReport() {
    $('#exportReportModal').modal('show');
}

// Toggle Custom Export Range
function toggleCustomExportRange() {
    const range = $('#export-date-range').val();
    if (range === 'custom') {
        $('#custom-export-range').show();
    } else {
        $('#custom-export-range').hide();
    }
}

// Generate Report
function generateReport() {
    const format = $('#export-format').val();
    const dateRange = $('#export-date-range').val();
    
    showAlert('info', `Generating ${format.toUpperCase()} report for ${dateRange} range...`);
    
    // Simulate report generation
    setTimeout(() => {
        showAlert('success', 'Report generated successfully! Download will start shortly.');
        $('#exportReportModal').modal('hide');
    }, 2000);
}

// Schedule Report
function scheduleReport() {
    $('#scheduleReportModal').modal('show');
}

// Schedule Report Submit
function scheduleReportSubmit() {
    const frequency = $('#schedule-frequency').val();
    const time = $('#schedule-time').val();
    const emails = $('#schedule-email').val();
    
    if (!emails.trim()) {
        showAlert('error', 'Please enter at least one email address.');
        return;
    }
    
    showAlert('success', `${frequency.charAt(0).toUpperCase() + frequency.slice(1)} report scheduled successfully!`);
    $('#scheduleReportModal').modal('hide');
}

// View Functions
function viewAllCampaigns() {
    window.location.href = '/sms/campaigns';
}

function viewAllTemplates() {
    window.location.href = '/sms/templates';
}

function viewErrorDetails() {
    showAlert('info', 'Error details view will be implemented.');
}

function viewAutoresponderDetails() {
    window.location.href = '/sms/autoresponders';
}

function viewCostBreakdown() {
    showAlert('info', 'Cost breakdown view will be implemented.');
}
</script>
@endpush