@extends('layouts.app')

@section('title', 'SMS Autoresponders')
@section('page-title', 'SMS Autoresponders')

@section('page-actions')
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-secondary btn-custom" onclick="refreshAutoresponders()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <a href="{{ route('sms.autoresponders.create') }}" class="btn btn-primary btn-custom">
            <i class="fas fa-plus"></i> New Autoresponder
        </a>
        <button type="button" class="btn btn-info btn-custom" onclick="testKeyword()">
            <i class="fas fa-paper-plane"></i> Test Keyword
        </button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle btn-custom" 
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-download"></i> Export
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#" onclick="exportAutoresponders('csv')">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
                <a class="dropdown-item" href="#" onclick="exportAutoresponders('json')">
                    <i class="fas fa-file-code"></i> Export as JSON
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0" id="total-autoresponders">0</h4>
                        <p class="mb-0">Total Autoresponders</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-robot fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0" id="active-autoresponders">0</h4>
                        <p class="mb-0">Active</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0" id="total-triggers">0</h4>
                        <p class="mb-0">Total Triggers</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-bolt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0" id="total-responses">0</h4>
                        <p class="mb-0">Total Responses</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-reply fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="search">Search Autoresponders</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" 
                               placeholder="Search by name, keyword, or message...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchAutoresponders()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="status-filter">Status</label>
                    <select class="form-control" id="status-filter" onchange="filterAutoresponders()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="trigger-filter">Trigger Type</label>
                    <select class="form-control" id="trigger-filter" onchange="filterAutoresponders()">
                        <option value="">All Types</option>
                        <option value="keyword">Keyword</option>
                        <option value="schedule">Schedule</option>
                        <option value="event">Event</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="sort-by">Sort By</label>
                    <select class="form-control" id="sort-by" onchange="sortAutoresponders()">
                        <option value="created_at">Created Date</option>
                        <option value="name">Name</option>
                        <option value="trigger_count">Trigger Count</option>
                        <option value="response_count">Response Count</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="per-page">Per Page</label>
                    <select class="form-control" id="per-page" onchange="changePerPage()">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkActivate()">
                        <i class="fas fa-play"></i> Activate Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkDeactivate()">
                        <i class="fas fa-pause"></i> Deactivate Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>
                <div class="float-right">
                    <small class="text-muted">
                        <span id="selected-count">0</span> selected
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Autoresponders List -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Autoresponders</h5>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="select-all" onchange="toggleSelectAll()">
                <label class="form-check-label" for="select-all">
                    Select All
                </label>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Loading State -->
        <div id="loading-state" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading autoresponders...</p>
        </div>
        
        <!-- Autoresponders Grid -->
        <div id="autoresponders-grid" class="row">
            <!-- Autoresponders will be loaded here -->
        </div>
        
        <!-- Empty State -->
        <div id="empty-state" class="text-center py-5" style="display: none;">
            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Autoresponders Found</h5>
            <p class="text-muted">Create your first autoresponder to get started with automated SMS responses.</p>
            <a href="{{ route('sms.autoresponders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Autoresponder
            </a>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted" id="pagination-info">
                    Showing 0 to 0 of 0 results
                </small>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination-links">
                    <!-- Pagination links will be loaded here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Hidden Autoresponder Card Template -->
<div id="autoresponder-card-template" style="display: none;">
    <div class="col-lg-6 col-xl-4 mb-4 autoresponder-card">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input autoresponder-checkbox" type="checkbox" data-id="">
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                            type="button" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" onclick="editAutoresponder(this)">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a class="dropdown-item" href="#" onclick="duplicateAutoresponder(this)">
                            <i class="fas fa-copy"></i> Duplicate
                        </a>
                        <a class="dropdown-item" href="#" onclick="testAutoresponder(this)">
                            <i class="fas fa-paper-plane"></i> Test
                        </a>
                        <a class="dropdown-item" href="#" onclick="viewLogs(this)">
                            <i class="fas fa-history"></i> View Logs
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-success" href="#" onclick="toggleStatus(this, 'activate')">
                            <i class="fas fa-play"></i> Activate
                        </a>
                        <a class="dropdown-item text-warning" href="#" onclick="toggleStatus(this, 'deactivate')">
                            <i class="fas fa-pause"></i> Deactivate
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="deleteAutoresponder(this)">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0 autoresponder-name"></h6>
                    <span class="badge autoresponder-status"></span>
                </div>
                <p class="card-text text-muted small autoresponder-description"></p>
                
                <!-- Trigger Info -->
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-bolt text-warning mr-2"></i>
                        <strong class="small">Triggers:</strong>
                    </div>
                    <div class="trigger-list small text-muted">
                        <!-- Triggers will be populated here -->
                    </div>
                </div>
                
                <!-- Response Preview -->
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-reply text-info mr-2"></i>
                        <strong class="small">Response:</strong>
                    </div>
                    <div class="response-preview small text-muted">
                        <!-- Response preview will be populated here -->
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="row text-center">
                    <div class="col-4">
                        <div class="small">
                            <div class="font-weight-bold trigger-count">0</div>
                            <div class="text-muted">Triggers</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="small">
                            <div class="font-weight-bold response-count">0</div>
                            <div class="text-muted">Responses</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="small">
                            <div class="font-weight-bold success-rate">0%</div>
                            <div class="text-muted">Success</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted created-date"></small>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="editAutoresponder(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="testAutoresponder(this)">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteAutoresponder(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Keyword Modal -->
<div class="modal fade" id="testKeywordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Keyword Trigger</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="test-keyword-form">
                    <div class="form-group">
                        <label for="test-keyword">Keyword</label>
                        <input type="text" class="form-control" id="test-keyword" 
                               placeholder="Enter keyword to test" required>
                        <small class="form-text text-muted">
                            Enter a keyword to test which autoresponder will be triggered
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="test-phone">From Phone Number</label>
                        <input type="tel" class="form-control" id="test-phone" 
                               placeholder="+1234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="test-message">Full Message (Optional)</label>
                        <textarea class="form-control" id="test-message" rows="3" 
                                  placeholder="Full SMS message containing the keyword"></textarea>
                    </div>
                </form>
                
                <!-- Test Result -->
                <div id="test-result" style="display: none;">
                    <hr>
                    <h6>Test Result:</h6>
                    <div id="test-result-content"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="runKeywordTest()">Test Keyword</button>
            </div>
        </div>
    </div>
</div>

<!-- Autoresponder Details Modal -->
<div class="modal fade" id="autoresponderDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Autoresponder Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="autoresponder-details-content">
                    <!-- Details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="edit-from-modal">Edit</button>
            </div>
        </div>
    </div>
</div>

<!-- Automation Logs Modal -->
<div class="modal fade" id="automationLogsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Automation Logs</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="automation-logs-content">
                    <!-- Logs will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary" onclick="exportLogs()">
                    <i class="fas fa-download"></i> Export Logs
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Autoresponder Card Styles */
.autoresponder-card .card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #dee2e6;
}

.autoresponder-card .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.autoresponder-card .card-title {
    font-size: 1rem;
    font-weight: 600;
}

.autoresponder-card .badge {
    font-size: 0.75rem;
}

.trigger-list {
    max-height: 60px;
    overflow-y: auto;
}

.response-preview {
    max-height: 40px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Status Badges */
.badge.badge-active {
    background-color: #28a745;
}

.badge.badge-inactive {
    background-color: #6c757d;
}

/* Loading Animation */
.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Search and Filter Styles */
.form-group label {
    font-weight: 600;
    font-size: 0.875rem;
}

/* Modal Styles */
.modal-lg {
    max-width: 900px;
}

.modal-xl {
    max-width: 1200px;
}

/* Test Result Styles */
#test-result-content .alert {
    margin-bottom: 0;
}

/* Statistics Cards */
.card.bg-primary,
.card.bg-success,
.card.bg-info,
.card.bg-warning {
    border: none;
}

.card.bg-primary .card-body,
.card.bg-success .card-body,
.card.bg-info .card-body,
.card.bg-warning .card-body {
    padding: 1.5rem;
}
</style>
@endpush

@push('scripts')
<script>
// Autoresponders Management JavaScript

let autoresponders = [];
let filteredAutoresponders = [];
let currentPage = 1;
let perPage = 25;
let totalPages = 1;
let selectedAutoresponders = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load autoresponders on page load
    loadAutoresponders();
    
    // Setup search functionality
    $('#search').on('input', debounce(searchAutoresponders, 300));
    
    // Setup keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'f':
                    e.preventDefault();
                    $('#search').focus();
                    break;
                case 'n':
                    e.preventDefault();
                    window.location.href = '{{ route("sms.autoresponders.create") }}';
                    break;
            }
        }
    });
});

// Load Autoresponders
function loadAutoresponders() {
    showLoading();
    
    $.get('/sms-test/autoresponders')
        .done(function(response) {
            if (response.status === 'success') {
                autoresponders = response.data.autoresponders || [];
                updateStatistics(response.data.statistics || {});
                filterAutoresponders();
            } else {
                showAlert('error', 'Failed to load autoresponders: ' + response.message);
                showEmptyState();
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to load autoresponders.');
            showEmptyState();
        })
        .always(function() {
            hideLoading();
        });
}

// Update Statistics
function updateStatistics(stats) {
    $('#total-autoresponders').text(stats.total || 0);
    $('#active-autoresponders').text(stats.active || 0);
    $('#total-triggers').text(stats.total_triggers || 0);
    $('#total-responses').text(stats.total_responses || 0);
}

// Filter Autoresponders
function filterAutoresponders() {
    const search = $('#search').val().toLowerCase();
    const statusFilter = $('#status-filter').val();
    const triggerFilter = $('#trigger-filter').val();
    
    filteredAutoresponders = autoresponders.filter(autoresponder => {
        // Search filter
        const matchesSearch = !search || 
            autoresponder.name.toLowerCase().includes(search) ||
            autoresponder.description.toLowerCase().includes(search) ||
            (autoresponder.triggers && autoresponder.triggers.some(trigger => 
                trigger.keyword && trigger.keyword.toLowerCase().includes(search)
            )) ||
            autoresponder.response_message.toLowerCase().includes(search);
        
        // Status filter
        const matchesStatus = !statusFilter || 
            (statusFilter === 'active' && autoresponder.is_active) ||
            (statusFilter === 'inactive' && !autoresponder.is_active);
        
        // Trigger type filter
        const matchesTrigger = !triggerFilter || 
            (autoresponder.triggers && autoresponder.triggers.some(trigger => 
                trigger.trigger_type === triggerFilter
            ));
        
        return matchesSearch && matchesStatus && matchesTrigger;
    });
    
    sortAutoresponders();
}

// Sort Autoresponders
function sortAutoresponders() {
    const sortBy = $('#sort-by').val();
    
    filteredAutoresponders.sort((a, b) => {
        switch(sortBy) {
            case 'name':
                return a.name.localeCompare(b.name);
            case 'trigger_count':
                return (b.triggers?.length || 0) - (a.triggers?.length || 0);
            case 'response_count':
                return (b.automation_logs?.length || 0) - (a.automation_logs?.length || 0);
            case 'created_at':
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });
    
    paginateAutoresponders();
}

// Paginate Autoresponders
function paginateAutoresponders() {
    perPage = parseInt($('#per-page').val());
    totalPages = Math.ceil(filteredAutoresponders.length / perPage);
    
    if (currentPage > totalPages) {
        currentPage = 1;
    }
    
    displayAutoresponders();
    updatePagination();
}

// Display Autoresponders
function displayAutoresponders() {
    const container = $('#autoresponders-grid');
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    const pageAutoresponders = filteredAutoresponders.slice(start, end);
    
    container.empty();
    
    if (pageAutoresponders.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    pageAutoresponders.forEach(autoresponder => {
        const card = createAutoresponderCard(autoresponder);
        container.append(card);
    });
    
    // Update pagination info
    const showing = pageAutoresponders.length;
    const total = filteredAutoresponders.length;
    $('#pagination-info').text(`Showing ${start + 1} to ${start + showing} of ${total} results`);
}

// Create Autoresponder Card
function createAutoresponderCard(autoresponder) {
    const template = $('#autoresponder-card-template').html();
    const card = $(template);
    
    // Set basic info
    card.find('.autoresponder-checkbox').attr('data-id', autoresponder.id);
    card.find('.autoresponder-name').text(autoresponder.name);
    card.find('.autoresponder-description').text(autoresponder.description || 'No description');
    
    // Set status badge
    const statusBadge = card.find('.autoresponder-status');
    if (autoresponder.is_active) {
        statusBadge.addClass('badge-active').text('Active');
    } else {
        statusBadge.addClass('badge-inactive').text('Inactive');
    }
    
    // Set triggers
    const triggerList = card.find('.trigger-list');
    if (autoresponder.triggers && autoresponder.triggers.length > 0) {
        const triggerItems = autoresponder.triggers.map(trigger => {
            let triggerText = '';
            if (trigger.trigger_type === 'keyword') {
                triggerText = `Keyword: ${trigger.keyword}`;
            } else if (trigger.trigger_type === 'schedule') {
                triggerText = `Schedule: ${trigger.schedule_time}`;
            } else {
                triggerText = `${trigger.trigger_type}: ${trigger.trigger_value || 'N/A'}`;
            }
            return `<div class="badge badge-light mr-1 mb-1">${triggerText}</div>`;
        }).join('');
        triggerList.html(triggerItems);
    } else {
        triggerList.html('<span class="text-muted">No triggers</span>');
    }
    
    // Set response preview
    const responsePreview = card.find('.response-preview');
    const message = autoresponder.response_message || 'No response message';
    responsePreview.text(message.length > 100 ? message.substring(0, 100) + '...' : message);
    
    // Set statistics
    card.find('.trigger-count').text(autoresponder.triggers?.length || 0);
    card.find('.response-count').text(autoresponder.automation_logs?.length || 0);
    
    // Calculate success rate
    const totalLogs = autoresponder.automation_logs?.length || 0;
    const successfulLogs = autoresponder.automation_logs?.filter(log => log.status === 'success').length || 0;
    const successRate = totalLogs > 0 ? Math.round((successfulLogs / totalLogs) * 100) : 0;
    card.find('.success-rate').text(successRate + '%');
    
    // Set created date
    const createdDate = new Date(autoresponder.created_at).toLocaleDateString();
    card.find('.created-date').text(`Created: ${createdDate}`);
    
    // Set data attributes for actions
    card.find('[onclick*="editAutoresponder"]').attr('data-id', autoresponder.id);
    card.find('[onclick*="duplicateAutoresponder"]').attr('data-id', autoresponder.id);
    card.find('[onclick*="testAutoresponder"]').attr('data-id', autoresponder.id);
    card.find('[onclick*="viewLogs"]').attr('data-id', autoresponder.id);
    card.find('[onclick*="toggleStatus"]').attr('data-id', autoresponder.id);
    card.find('[onclick*="deleteAutoresponder"]').attr('data-id', autoresponder.id);
    
    return card;
}

// Search Autoresponders
function searchAutoresponders() {
    currentPage = 1;
    filterAutoresponders();
}

// Refresh Autoresponders
function refreshAutoresponders() {
    selectedAutoresponders = [];
    updateSelectedCount();
    loadAutoresponders();
}

// Change Per Page
function changePerPage() {
    currentPage = 1;
    paginateAutoresponders();
}

// Update Pagination
function updatePagination() {
    const paginationContainer = $('#pagination-links');
    paginationContainer.empty();
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationContainer.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(1)">1</a>
            </li>
        `);
        if (startPage > 2) {
            paginationContainer.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        paginationContainer.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationContainer.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        paginationContainer.append(`
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a>
            </li>
        `);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    paginationContainer.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

// Change Page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    displayAutoresponders();
    updatePagination();
}

// Toggle Select All
function toggleSelectAll() {
    const selectAll = $('#select-all').is(':checked');
    $('.autoresponder-checkbox').prop('checked', selectAll);
    
    if (selectAll) {
        selectedAutoresponders = filteredAutoresponders.map(a => a.id);
    } else {
        selectedAutoresponders = [];
    }
    
    updateSelectedCount();
}

// Update Selected Count
function updateSelectedCount() {
    $('#selected-count').text(selectedAutoresponders.length);
    
    // Update select all checkbox
    const totalVisible = $('.autoresponder-checkbox').length;
    const totalSelected = $('.autoresponder-checkbox:checked').length;
    
    if (totalSelected === 0) {
        $('#select-all').prop('indeterminate', false).prop('checked', false);
    } else if (totalSelected === totalVisible) {
        $('#select-all').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#select-all').prop('indeterminate', true);
    }
}

// Handle individual checkbox change
$(document).on('change', '.autoresponder-checkbox', function() {
    const id = parseInt($(this).data('id'));
    
    if ($(this).is(':checked')) {
        if (!selectedAutoresponders.includes(id)) {
            selectedAutoresponders.push(id);
        }
    } else {
        selectedAutoresponders = selectedAutoresponders.filter(selectedId => selectedId !== id);
    }
    
    updateSelectedCount();
});

// Edit Autoresponder
function editAutoresponder(element) {
    const id = $(element).data('id');
    window.location.href = `/sms/autoresponders/${id}/edit`;
}

// Duplicate Autoresponder
function duplicateAutoresponder(element) {
    const id = $(element).data('id');
    
    if (!confirm('Duplicate this autoresponder?')) return;
    
    showLoading();
    
    $.post(`/sms/autoresponders/${id}/duplicate`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Autoresponder duplicated successfully!');
            loadAutoresponders();
        } else {
            showAlert('error', 'Failed to duplicate autoresponder: ' + response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to duplicate autoresponder.');
    })
    .always(function() {
        hideLoading();
    });
}

// Test Autoresponder
function testAutoresponder(element) {
    const id = $(element).data('id');
    const autoresponder = autoresponders.find(a => a.id === id);
    
    if (!autoresponder) {
        showAlert('error', 'Autoresponder not found.');
        return;
    }
    
    // Pre-fill test form with autoresponder data
    if (autoresponder.triggers && autoresponder.triggers.length > 0) {
        const firstTrigger = autoresponder.triggers[0];
        if (firstTrigger.trigger_type === 'keyword') {
            $('#test-keyword').val(firstTrigger.keyword);
        }
    }
    
    $('#testKeywordModal').modal('show');
}

// View Logs
function viewLogs(element) {
    const id = $(element).data('id');
    
    showLoading('#automation-logs-content');
    $('#automationLogsModal').modal('show');
    
    $.get(`/sms-test/autoresponders/${id}/logs`)
        .done(function(response) {
            if (response.status === 'success') {
                displayAutomationLogs(response.data.logs || []);
            } else {
                $('#automation-logs-content').html(`
                    <div class="alert alert-danger">
                        Failed to load logs: ${response.message}
                    </div>
                `);
            }
        })
        .fail(function() {
            $('#automation-logs-content').html(`
                <div class="alert alert-danger">
                    Failed to load automation logs.
                </div>
            `);
        })
        .always(function() {
            hideLoading('#automation-logs-content');
        });
}

// Display Automation Logs
function displayAutomationLogs(logs) {
    if (logs.length === 0) {
        $('#automation-logs-content').html(`
            <div class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Logs Found</h5>
                <p class="text-muted">No automation logs available for this autoresponder.</p>
            </div>
        `);
        return;
    }
    
    let logsHtml = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Trigger</th>
                        <th>From</th>
                        <th>Response</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    logs.forEach(log => {
        const date = new Date(log.created_at).toLocaleString();
        const status = log.status === 'success' ? 
            '<span class="badge badge-success">Success</span>' : 
            '<span class="badge badge-danger">Failed</span>';
        
        logsHtml += `
            <tr>
                <td>${date}</td>
                <td>${log.trigger_data || 'N/A'}</td>
                <td>${log.from_number || 'N/A'}</td>
                <td>${log.response_message ? (log.response_message.length > 50 ? log.response_message.substring(0, 50) + '...' : log.response_message) : 'N/A'}</td>
                <td>${status}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="viewLogDetails(${log.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    logsHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#automation-logs-content').html(logsHtml);
}

// Toggle Status
function toggleStatus(element, action) {
    const id = $(element).data('id');
    const actionText = action === 'activate' ? 'activate' : 'deactivate';
    
    if (!confirm(`${actionText.charAt(0).toUpperCase() + actionText.slice(1)} this autoresponder?`)) return;
    
    showLoading();
    
    $.post(`/sms/autoresponders/${id}/${action}`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', `Autoresponder ${actionText}d successfully!`);
            loadAutoresponders();
        } else {
            showAlert('error', `Failed to ${actionText} autoresponder: ` + response.message);
        }
    })
    .fail(function() {
        showAlert('error', `Failed to ${actionText} autoresponder.`);
    })
    .always(function() {
        hideLoading();
    });
}

// Delete Autoresponder
function deleteAutoresponder(element) {
    const id = $(element).data('id');
    
    if (!confirm('Delete this autoresponder? This action cannot be undone.')) return;
    
    showLoading();
    
    $.ajax({
        url: `/sms/autoresponders/${id}`,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Autoresponder deleted successfully!');
            loadAutoresponders();
        } else {
            showAlert('error', 'Failed to delete autoresponder: ' + response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to delete autoresponder.');
    })
    .always(function() {
        hideLoading();
    });
}

// Bulk Actions
function bulkActivate() {
    if (selectedAutoresponders.length === 0) {
        showAlert('warning', 'Please select autoresponders to activate.');
        return;
    }
    
    if (!confirm(`Activate ${selectedAutoresponders.length} selected autoresponders?`)) return;
    
    bulkAction('activate');
}

function bulkDeactivate() {
    if (selectedAutoresponders.length === 0) {
        showAlert('warning', 'Please select autoresponders to deactivate.');
        return;
    }
    
    if (!confirm(`Deactivate ${selectedAutoresponders.length} selected autoresponders?`)) return;
    
    bulkAction('deactivate');
}

function bulkDelete() {
    if (selectedAutoresponders.length === 0) {
        showAlert('warning', 'Please select autoresponders to delete.');
        return;
    }
    
    if (!confirm(`Delete ${selectedAutoresponders.length} selected autoresponders? This action cannot be undone.`)) return;
    
    bulkAction('delete');
}

function bulkAction(action) {
    showLoading();
    
    $.post('/sms/autoresponders/bulk-action', {
        action: action,
        ids: selectedAutoresponders,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', `Autoresponders ${action}d successfully!`);
            selectedAutoresponders = [];
            updateSelectedCount();
            loadAutoresponders();
        } else {
            showAlert('error', `Failed to ${action} autoresponders: ` + response.message);
        }
    })
    .fail(function() {
        showAlert('error', `Failed to ${action} autoresponders.`);
    })
    .always(function() {
        hideLoading();
    });
}

// Test Keyword
function testKeyword() {
    $('#test-result').hide();
    $('#testKeywordModal').modal('show');
}

// Run Keyword Test
function runKeywordTest() {
    const keyword = $('#test-keyword').val().trim();
    const phone = $('#test-phone').val().trim();
    const message = $('#test-message').val().trim();
    
    if (!keyword) {
        showAlert('error', 'Please enter a keyword to test.');
        return;
    }
    
    if (!phone) {
        showAlert('error', 'Please enter a phone number.');
        return;
    }
    
    showLoading('#test-result-content');
    $('#test-result').show();
    
    $.get('/sms-test/trigger-autoresponder', {
        keyword: keyword,
        from: phone,
        message: message || keyword
    })
    .done(function(response) {
        if (response.status === 'success') {
            $('#test-result-content').html(`
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> Test Successful</h6>
                    <p><strong>Triggered Autoresponder:</strong> ${response.data.autoresponder_name}</p>
                    <p><strong>Response Message:</strong> ${response.data.response_message}</p>
                    <p><strong>Message ID:</strong> ${response.data.message_id}</p>
                </div>
            `);
        } else {
            $('#test-result-content').html(`
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> No Match Found</h6>
                    <p>${response.message}</p>
                </div>
            `);
        }
    })
    .fail(function() {
        $('#test-result-content').html(`
            <div class="alert alert-danger">
                <h6><i class="fas fa-times-circle"></i> Test Failed</h6>
                <p>Failed to test keyword trigger.</p>
            </div>
        `);
    })
    .always(function() {
        hideLoading('#test-result-content');
    });
}

// Export Autoresponders
function exportAutoresponders(format) {
    const params = new URLSearchParams({
        format: format,
        search: $('#search').val(),
        status: $('#status-filter').val(),
        trigger_type: $('#trigger-filter').val()
    });
    
    window.open(`/sms/autoresponders/export?${params.toString()}`, '_blank');
}

// Export Logs
function exportLogs() {
    // Implementation for exporting logs
    showAlert('info', 'Export functionality will be implemented.');
}

// Show/Hide Loading
function showLoading(target = null) {
    if (target) {
        $(target).html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `);
    } else {
        $('#loading-state').show();
        $('#autoresponders-grid').hide();
    }
}

function hideLoading(target = null) {
    if (!target) {
        $('#loading-state').hide();
        $('#autoresponders-grid').show();
    }
}

// Show/Hide Empty State
function showEmptyState() {
    $('#empty-state').show();
    $('#autoresponders-grid').hide();
}

function hideEmptyState() {
    $('#empty-state').hide();
    $('#autoresponders-grid').show();
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endpush