@extends('layouts.app')

@section('title', 'SMS Campaigns')
@section('page-title', 'SMS Campaigns')

@section('page-actions')
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary btn-custom" onclick="refreshCampaigns()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <a href="{{ route('sms.campaigns.create') }}" class="btn btn-success btn-custom">
            <i class="fas fa-plus"></i> New Campaign
        </a>
        <button type="button" class="btn btn-info btn-custom" onclick="showBulkActions()">
            <i class="fas fa-tasks"></i> Bulk Actions
        </button>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Campaign Statistics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Campaigns</div>
                                <div class="h5 mb-0 font-weight-bold" id="total-campaigns">{{ $statistics['total'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bullhorn fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Active Campaigns</div>
                                <div class="h5 mb-0 font-weight-bold" id="active-campaigns">{{ $statistics['active'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-play-circle fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Scheduled</div>
                                <div class="h5 mb-0 font-weight-bold" id="scheduled-campaigns">{{ $statistics['scheduled'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Avg Success Rate</div>
                                <div class="h5 mb-0 font-weight-bold" id="avg-success-rate">{{ $statistics['avg_success_rate'] ?? 0 }}%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-percentage fa-2x text-white-50"></i>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search-campaigns">Search Campaigns</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search-campaigns" 
                                       placeholder="Search by name...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="searchCampaigns()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filter-status">Status</label>
                            <select class="form-control" id="filter-status" onchange="filterCampaigns()">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="running">Running</option>
                                <option value="completed">Completed</option>
                                <option value="paused">Paused</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filter-type">Type</label>
                            <select class="form-control" id="filter-type" onchange="filterCampaigns()">
                                <option value="">All Types</option>
                                <option value="immediate">Immediate</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="recurring">Recurring</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-date">Date Range</label>
                            <select class="form-control" id="filter-date" onchange="filterCampaigns()">
                                <option value="">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-secondary btn-block" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Campaigns Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list"></i> Campaign List</h6>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleView('table')" id="table-view-btn">
                        <i class="fas fa-table"></i> Table
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleView('grid')" id="grid-view-btn">
                        <i class="fas fa-th"></i> Grid
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Table View -->
                <div id="table-view">
                    <div class="table-responsive">
                        <table class="table table-hover" id="campaigns-table">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Campaign Name</th>
                                    <th>Status</th>
                                    <th>Recipients</th>
                                    <th>Sent/Total</th>
                                    <th>Success Rate</th>
                                    <th>Scheduled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="campaigns-tbody">
                                <!-- Campaigns will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Grid View -->
                <div id="grid-view" style="display: none;">
                    <div class="row" id="campaigns-grid">
                        <!-- Campaign cards will be loaded here -->
                    </div>
                </div>
                
                <!-- Loading State -->
                <div id="campaigns-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading campaigns...</p>
                </div>
                
                <!-- Empty State -->
                <div id="campaigns-empty" class="text-center py-5" style="display: none;">
                    <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Campaigns Found</h4>
                    <p class="text-muted">Create your first SMS campaign to get started.</p>
                    <a href="{{ route('sms.campaigns.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Campaign
                    </a>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Showing <span id="showing-from">0</span> to <span id="showing-to">0</span> of <span id="total-records">0</span> campaigns</small>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination">
                            <!-- Pagination will be generated here -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campaign Details Modal -->
<div class="modal fade" id="campaignDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Campaign Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="campaign-details-content">
                <!-- Campaign details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="edit-campaign-btn" onclick="editCampaign()">Edit Campaign</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Actions</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk-action">Select Action</label>
                    <select class="form-control" id="bulk-action">
                        <option value="">Choose an action...</option>
                        <option value="pause">Pause Campaigns</option>
                        <option value="resume">Resume Campaigns</option>
                        <option value="delete">Delete Campaigns</option>
                        <option value="duplicate">Duplicate Campaigns</option>
                    </select>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="selected-count">0</span> campaigns selected
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Execute Action</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Campaigns Management JavaScript

let currentCampaigns = [];
let selectedCampaigns = [];
let currentView = 'table';
let currentPage = 1;
let totalPages = 1;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCampaigns();
    
    // Setup search on enter key
    $('#search-campaigns').on('keypress', function(e) {
        if (e.which === 13) {
            searchCampaigns();
        }
    });
    
    // Auto-refresh every 30 seconds for running campaigns
    setInterval(function() {
        if (hasRunningCampaigns()) {
            loadCampaigns(false); // Silent refresh
        }
    }, 30000);
});

// Load Campaigns from API
function loadCampaigns(showLoading = true) {
    if (showLoading) {
        $('#campaigns-loading').show();
        $('#table-view').hide();
        $('#grid-view').hide();
        $('#campaigns-empty').hide();
    }
    
    const params = {
        page: currentPage,
        search: $('#search-campaigns').val(),
        status: $('#filter-status').val(),
        type: $('#filter-type').val(),
        date_range: $('#filter-date').val()
    };
    
    $.get('/sms-test/campaigns', params)
        .done(function(response) {
            if (response.status === 'success' && response.campaigns) {
                currentCampaigns = response.campaigns.data || response.campaigns;
                totalPages = response.campaigns.last_page || 1;
                
                displayCampaigns(currentCampaigns);
                updatePagination(response.campaigns);
                updateStatistics(response.statistics);
            } else {
                showEmptyState();
            }
        })
        .fail(function() {
            if (showLoading) {
                showAlert('error', 'Failed to load campaigns.');
                showEmptyState();
            }
        })
        .always(function() {
            if (showLoading) {
                $('#campaigns-loading').hide();
            }
        });
}

// Display Campaigns
function displayCampaigns(campaigns) {
    if (campaigns.length === 0) {
        showEmptyState();
        return;
    }
    
    if (currentView === 'table') {
        displayTableView(campaigns);
        $('#table-view').show();
        $('#grid-view').hide();
    } else {
        displayGridView(campaigns);
        $('#table-view').hide();
        $('#grid-view').show();
    }
    
    $('#campaigns-empty').hide();
}

// Display Table View
function displayTableView(campaigns) {
    const tbody = $('#campaigns-tbody');
    tbody.empty();
    
    campaigns.forEach(campaign => {
        const row = createTableRow(campaign);
        tbody.append(row);
    });
}

// Create Table Row
function createTableRow(campaign) {
    const statusBadge = getStatusBadge(campaign.status);
    const progressBar = createProgressBar(campaign.sent_count, campaign.total_recipients);
    const successRate = campaign.success_rate || 0;
    
    return `
        <tr data-campaign-id="${campaign.id}">
            <td>
                <input type="checkbox" class="campaign-checkbox" value="${campaign.id}" onchange="updateSelectedCampaigns()">
            </td>
            <td>
                <div>
                    <strong>${campaign.name}</strong>
                    <br><small class="text-muted">${campaign.template_name || 'No template'}</small>
                </div>
            </td>
            <td>${statusBadge}</td>
            <td>${campaign.total_recipients || 0}</td>
            <td>
                ${progressBar}
                <small class="text-muted">${campaign.sent_count || 0}/${campaign.total_recipients || 0}</small>
            </td>
            <td>
                <span class="badge ${successRate >= 90 ? 'bg-success' : successRate >= 70 ? 'bg-warning' : 'bg-danger'}">
                    ${successRate}%
                </span>
            </td>
            <td>
                <small>${campaign.scheduled_at ? formatDateTime(campaign.scheduled_at) : 'Not scheduled'}</small>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="viewCampaign(${campaign.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="editCampaign(${campaign.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            ${getCampaignActions(campaign)}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

// Display Grid View
function displayGridView(campaigns) {
    const grid = $('#campaigns-grid');
    grid.empty();
    
    campaigns.forEach(campaign => {
        const card = createCampaignCard(campaign);
        grid.append(card);
    });
}

// Create Campaign Card
function createCampaignCard(campaign) {
    const statusBadge = getStatusBadge(campaign.status);
    const progressBar = createProgressBar(campaign.sent_count, campaign.total_recipients);
    const successRate = campaign.success_rate || 0;
    
    return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 campaign-card" data-campaign-id="${campaign.id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="campaign-checkbox mr-2" value="${campaign.id}" onchange="updateSelectedCampaigns()">
                        <strong>${campaign.name}</strong>
                    </div>
                    ${statusBadge}
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">
                        <i class="fas fa-file-alt"></i> ${campaign.template_name || 'No template'}
                    </p>
                    <div class="mb-2">
                        <small class="text-muted">Progress</small>
                        ${progressBar}
                        <div class="d-flex justify-content-between">
                            <small>${campaign.sent_count || 0}/${campaign.total_recipients || 0} sent</small>
                            <small>${successRate}% success</small>
                        </div>
                    </div>
                    ${campaign.scheduled_at ? `<p class="card-text"><small class="text-muted"><i class="fas fa-clock"></i> ${formatDateTime(campaign.scheduled_at)}</small></p>` : ''}
                </div>
                <div class="card-footer bg-transparent">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="viewCampaign(${campaign.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="editCampaign(${campaign.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                ${getCampaignActions(campaign)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Get Status Badge
function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'scheduled': '<span class="badge bg-warning">Scheduled</span>',
        'running': '<span class="badge bg-primary">Running</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'paused': '<span class="badge bg-info">Paused</span>',
        'failed': '<span class="badge bg-danger">Failed</span>'
    };
    return badges[status] || '<span class="badge bg-light">Unknown</span>';
}

// Create Progress Bar
function createProgressBar(sent, total) {
    if (!total || total === 0) return '<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>';
    
    const percentage = Math.round((sent / total) * 100);
    const colorClass = percentage === 100 ? 'bg-success' : percentage > 0 ? 'bg-primary' : 'bg-light';
    
    return `
        <div class="progress mb-1" style="height: 8px;">
            <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${percentage}%" 
                 aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    `;
}

// Get Campaign Actions
function getCampaignActions(campaign) {
    let actions = [];
    
    if (campaign.status === 'draft') {
        actions.push('<a class="dropdown-item" href="#" onclick="startCampaign(' + campaign.id + ')"><i class="fas fa-play"></i> Start Campaign</a>');
    }
    
    if (campaign.status === 'running') {
        actions.push('<a class="dropdown-item" href="#" onclick="pauseCampaign(' + campaign.id + ')"><i class="fas fa-pause"></i> Pause Campaign</a>');
    }
    
    if (campaign.status === 'paused') {
        actions.push('<a class="dropdown-item" href="#" onclick="resumeCampaign(' + campaign.id + ')"><i class="fas fa-play"></i> Resume Campaign</a>');
    }
    
    actions.push('<a class="dropdown-item" href="#" onclick="duplicateCampaign(' + campaign.id + ')"><i class="fas fa-copy"></i> Duplicate</a>');
    actions.push('<a class="dropdown-item" href="#" onclick="exportCampaign(' + campaign.id + ')"><i class="fas fa-download"></i> Export</a>');
    actions.push('<div class="dropdown-divider"></div>');
    actions.push('<a class="dropdown-item text-danger" href="#" onclick="deleteCampaign(' + campaign.id + ')"><i class="fas fa-trash"></i> Delete</a>');
    
    return actions.join('');
}

// Toggle View
function toggleView(view) {
    currentView = view;
    
    $('#table-view-btn, #grid-view-btn').removeClass('active');
    $(`#${view}-view-btn`).addClass('active');
    
    displayCampaigns(currentCampaigns);
}

// Search Campaigns
function searchCampaigns() {
    currentPage = 1;
    loadCampaigns();
}

// Filter Campaigns
function filterCampaigns() {
    currentPage = 1;
    loadCampaigns();
}

// Clear Filters
function clearFilters() {
    $('#search-campaigns').val('');
    $('#filter-status').val('');
    $('#filter-type').val('');
    $('#filter-date').val('');
    currentPage = 1;
    loadCampaigns();
}

// Refresh Campaigns
function refreshCampaigns() {
    loadCampaigns();
    showAlert('info', 'Campaigns refreshed.');
}

// View Campaign Details
function viewCampaign(campaignId) {
    showLoading('#campaignDetailsModal .modal-content');
    $('#campaignDetailsModal').modal('show');
    
    $.get(`/sms-test/campaigns/${campaignId}`)
        .done(function(response) {
            if (response.status === 'success') {
                displayCampaignDetails(response.campaign);
            } else {
                $('#campaign-details-content').html('<div class="alert alert-danger">Failed to load campaign details.</div>');
            }
        })
        .fail(function() {
            $('#campaign-details-content').html('<div class="alert alert-danger">Failed to load campaign details.</div>');
        })
        .always(function() {
            hideLoading('#campaignDetailsModal .modal-content');
        });
}

// Display Campaign Details
function displayCampaignDetails(campaign) {
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Campaign Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${campaign.name}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${getStatusBadge(campaign.status)}</td></tr>
                    <tr><td><strong>Type:</strong></td><td>${campaign.type || 'Immediate'}</td></tr>
                    <tr><td><strong>Template:</strong></td><td>${campaign.template_name || 'N/A'}</td></tr>
                    <tr><td><strong>Recipients:</strong></td><td>${campaign.total_recipients || 0}</td></tr>
                    <tr><td><strong>Created:</strong></td><td>${formatDateTime(campaign.created_at)}</td></tr>
                    ${campaign.scheduled_at ? `<tr><td><strong>Scheduled:</strong></td><td>${formatDateTime(campaign.scheduled_at)}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-6">
                <h6>Performance Metrics</h6>
                <table class="table table-sm">
                    <tr><td><strong>Messages Sent:</strong></td><td>${campaign.sent_count || 0}</td></tr>
                    <tr><td><strong>Successful:</strong></td><td>${campaign.success_count || 0}</td></tr>
                    <tr><td><strong>Failed:</strong></td><td>${campaign.failed_count || 0}</td></tr>
                    <tr><td><strong>Success Rate:</strong></td><td>${campaign.success_rate || 0}%</td></tr>
                    <tr><td><strong>Total Cost:</strong></td><td>$${campaign.total_cost || '0.00'}</td></tr>
                </table>
            </div>
        </div>
        ${campaign.message_content ? `
        <div class="mt-3">
            <h6>Message Content</h6>
            <div class="alert alert-light">${campaign.message_content}</div>
        </div>
        ` : ''}
    `;
    
    $('#campaign-details-content').html(html);
    $('#edit-campaign-btn').attr('onclick', `editCampaign(${campaign.id})`);
}

// Campaign Actions
function editCampaign(campaignId) {
    window.location.href = `/sms/campaigns/${campaignId}/edit`;
}

function startCampaign(campaignId) {
    if (!confirm('Start this campaign now?')) return;
    
    executeCampaignAction(campaignId, 'start');
}

function pauseCampaign(campaignId) {
    if (!confirm('Pause this campaign?')) return;
    
    executeCampaignAction(campaignId, 'pause');
}

function resumeCampaign(campaignId) {
    if (!confirm('Resume this campaign?')) return;
    
    executeCampaignAction(campaignId, 'resume');
}

function duplicateCampaign(campaignId) {
    if (!confirm('Create a copy of this campaign?')) return;
    
    executeCampaignAction(campaignId, 'duplicate');
}

function deleteCampaign(campaignId) {
    const campaign = currentCampaigns.find(c => c.id === campaignId);
    if (!campaign) return;
    
    if (!confirm(`Are you sure you want to delete the campaign "${campaign.name}"?`)) return;
    
    executeCampaignAction(campaignId, 'delete');
}

// Execute Campaign Action
function executeCampaignAction(campaignId, action) {
    showLoading('body');
    
    $.post(`/sms/campaigns/${campaignId}/${action}`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', response.message || `Campaign ${action} successful!`);
            loadCampaigns();
        } else {
            showAlert('error', response.message || `Failed to ${action} campaign.`);
        }
    })
    .fail(function() {
        showAlert('error', `Failed to ${action} campaign.`);
    })
    .always(function() {
        hideLoading('body');
    });
}

// Bulk Actions
function showBulkActions() {
    updateSelectedCount();
    $('#bulkActionsModal').modal('show');
}

function toggleSelectAll() {
    const selectAll = $('#select-all').is(':checked');
    $('.campaign-checkbox').prop('checked', selectAll);
    updateSelectedCampaigns();
}

function updateSelectedCampaigns() {
    selectedCampaigns = $('.campaign-checkbox:checked').map(function() {
        return parseInt($(this).val());
    }).get();
    
    updateSelectedCount();
}

function updateSelectedCount() {
    $('#selected-count').text(selectedCampaigns.length);
}

function executeBulkAction() {
    const action = $('#bulk-action').val();
    if (!action) {
        showAlert('error', 'Please select an action.');
        return;
    }
    
    if (selectedCampaigns.length === 0) {
        showAlert('error', 'Please select campaigns.');
        return;
    }
    
    if (!confirm(`Execute ${action} on ${selectedCampaigns.length} selected campaigns?`)) return;
    
    showLoading('#bulkActionsModal .modal-content');
    
    $.post('/sms/campaigns/bulk-action', {
        action: action,
        campaign_ids: selectedCampaigns,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', response.message || 'Bulk action completed successfully!');
            $('#bulkActionsModal').modal('hide');
            loadCampaigns();
            selectedCampaigns = [];
            $('.campaign-checkbox').prop('checked', false);
            $('#select-all').prop('checked', false);
        } else {
            showAlert('error', response.message || 'Bulk action failed.');
        }
    })
    .fail(function() {
        showAlert('error', 'Bulk action failed.');
    })
    .always(function() {
        hideLoading('#bulkActionsModal .modal-content');
    });
}

// Update Statistics
function updateStatistics(statistics) {
    if (statistics) {
        $('#total-campaigns').text(statistics.total || 0);
        $('#active-campaigns').text(statistics.active || 0);
        $('#scheduled-campaigns').text(statistics.scheduled || 0);
        $('#avg-success-rate').text((statistics.avg_success_rate || 0) + '%');
    }
}

// Update Pagination
function updatePagination(paginationData) {
    const pagination = $('#pagination');
    pagination.empty();
    
    if (!paginationData || paginationData.last_page <= 1) return;
    
    // Previous button
    if (paginationData.current_page > 1) {
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="changePage(${paginationData.current_page - 1})">Previous</a></li>`);
    }
    
    // Page numbers
    for (let i = Math.max(1, paginationData.current_page - 2); i <= Math.min(paginationData.last_page, paginationData.current_page + 2); i++) {
        const activeClass = i === paginationData.current_page ? 'active' : '';
        pagination.append(`<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`);
    }
    
    // Next button
    if (paginationData.current_page < paginationData.last_page) {
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="changePage(${paginationData.current_page + 1})">Next</a></li>`);
    }
    
    // Update showing info
    $('#showing-from').text(paginationData.from || 0);
    $('#showing-to').text(paginationData.to || 0);
    $('#total-records').text(paginationData.total || 0);
}

// Change Page
function changePage(page) {
    currentPage = page;
    loadCampaigns();
}

// Utility Functions
function showEmptyState() {
    $('#table-view').hide();
    $('#grid-view').hide();
    $('#campaigns-empty').show();
}

function hasRunningCampaigns() {
    return currentCampaigns.some(campaign => campaign.status === 'running');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function exportCampaign(campaignId) {
    window.open(`/sms/campaigns/${campaignId}/export`, '_blank');
}
</script>
@endpush