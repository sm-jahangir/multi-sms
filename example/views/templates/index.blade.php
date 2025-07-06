@extends('layouts.app')

@section('title', 'SMS Templates')
@section('page-title', 'SMS Templates')

@section('page-actions')
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary btn-custom" onclick="refreshTemplates()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <a href="{{ route('sms.templates.create') }}" class="btn btn-success btn-custom">
            <i class="fas fa-plus"></i> New Template
        </a>
        <button type="button" class="btn btn-info btn-custom" onclick="importTemplates()">
            <i class="fas fa-download"></i> Import
        </button>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search-templates">Search Templates</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search-templates" 
                                       placeholder="Search by name or content...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="searchTemplates()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-type">Filter by Type</label>
                            <select class="form-control" id="filter-type" onchange="filterTemplates()">
                                <option value="">All Types</option>
                                <option value="welcome">Welcome</option>
                                <option value="notification">Notification</option>
                                <option value="marketing">Marketing</option>
                                <option value="reminder">Reminder</option>
                                <option value="verification">Verification</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-status">Filter by Status</label>
                            <select class="form-control" id="filter-status" onchange="filterTemplates()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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

        <!-- Templates Grid -->
        <div class="row" id="templates-container">
            <!-- Templates will be loaded here via AJAX -->
        </div>

        <!-- Loading Placeholder -->
        <div id="templates-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading templates...</p>
        </div>

        <!-- Empty State -->
        <div id="templates-empty" class="text-center py-5" style="display: none;">
            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Templates Found</h4>
            <p class="text-muted">Create your first SMS template to get started.</p>
            <a href="{{ route('sms.templates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Template
            </a>
        </div>
    </div>
</div>

<!-- Template Card Template (Hidden) -->
<div id="template-card-template" style="display: none;">
    <div class="col-lg-4 col-md-6 mb-4 template-card" data-template-id="{id}" data-type="{type}" data-status="{status}">
        <div class="card h-100 template-item">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold">{name}</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" onclick="editTemplate({id})">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a class="dropdown-item" href="#" onclick="duplicateTemplate({id})">
                            <i class="fas fa-copy"></i> Duplicate
                        </a>
                        <a class="dropdown-item" href="#" onclick="testTemplate({id})">
                            <i class="fas fa-paper-plane"></i> Test Send
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="deleteTemplate({id})">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge badge-{type_color} mr-1">{type}</span>
                    <span class="badge badge-{status_color}">{status}</span>
                </div>
                <p class="card-text text-muted small">{content_preview}</p>
                <div class="template-variables mb-2">
                    <small class="text-muted">Variables: {variables}</small>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted">Used</small><br>
                        <strong>{usage_count}</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Success Rate</small><br>
                        <strong>{success_rate}%</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted">Updated</small><br>
                        <strong>{updated_at}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Template Modal -->
<div class="modal fade" id="testTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test SMS Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="test-template-form">
                    <div class="form-group">
                        <label for="test-phone">Phone Number</label>
                        <input type="tel" class="form-control" id="test-phone" 
                               placeholder="+1234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="test-variables">Template Variables (JSON)</label>
                        <textarea class="form-control" id="test-variables" rows="4" 
                                  placeholder='{"name": "John Doe", "amount": "$100"}'></textarea>
                        <small class="form-text text-muted">
                            Provide values for template variables in JSON format
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Preview</label>
                        <div class="alert alert-info" id="template-preview">
                            Template preview will appear here...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestTemplate()">Send Test SMS</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Templates Management JavaScript

let currentTemplates = [];
let currentTemplateId = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadTemplates();
    
    // Setup search on enter key
    $('#search-templates').on('keypress', function(e) {
        if (e.which === 13) {
            searchTemplates();
        }
    });
    
    // Setup template variables preview
    $('#test-variables').on('input', function() {
        updateTemplatePreview();
    });
});

// Load Templates from API
function loadTemplates() {
    $('#templates-loading').show();
    $('#templates-container').empty();
    $('#templates-empty').hide();
    
    $.get('/sms-test/templates')
        .done(function(response) {
            if (response.status === 'success' && response.templates) {
                currentTemplates = response.templates;
                displayTemplates(currentTemplates);
            } else {
                showEmptyState();
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to load templates.');
            showEmptyState();
        })
        .always(function() {
            $('#templates-loading').hide();
        });
}

// Display Templates in Grid
function displayTemplates(templates) {
    const container = $('#templates-container');
    container.empty();
    
    if (templates.length === 0) {
        showEmptyState();
        return;
    }
    
    templates.forEach(template => {
        const templateCard = createTemplateCard(template);
        container.append(templateCard);
    });
    
    $('#templates-empty').hide();
}

// Create Template Card HTML
function createTemplateCard(template) {
    let cardTemplate = $('#template-card-template').html();
    
    // Replace placeholders with actual data
    cardTemplate = cardTemplate
        .replace(/{id}/g, template.id)
        .replace(/{name}/g, template.name)
        .replace(/{type}/g, template.type || 'general')
        .replace(/{status}/g, template.is_active ? 'active' : 'inactive')
        .replace(/{type_color}/g, getTypeColor(template.type))
        .replace(/{status_color}/g, template.is_active ? 'success' : 'secondary')
        .replace(/{content_preview}/g, truncateText(template.content, 100))
        .replace(/{variables}/g, template.variables ? template.variables.join(', ') : 'None')
        .replace(/{usage_count}/g, template.usage_count || 0)
        .replace(/{success_rate}/g, template.success_rate || 0)
        .replace(/{updated_at}/g, formatDate(template.updated_at));
    
    return cardTemplate;
}

// Get Badge Color for Template Type
function getTypeColor(type) {
    const colors = {
        'welcome': 'primary',
        'notification': 'info',
        'marketing': 'warning',
        'reminder': 'secondary',
        'verification': 'success'
    };
    return colors[type] || 'light';
}

// Search Templates
function searchTemplates() {
    const searchTerm = $('#search-templates').val().toLowerCase();
    
    if (!searchTerm) {
        displayTemplates(currentTemplates);
        return;
    }
    
    const filteredTemplates = currentTemplates.filter(template => 
        template.name.toLowerCase().includes(searchTerm) ||
        template.content.toLowerCase().includes(searchTerm)
    );
    
    displayTemplates(filteredTemplates);
}

// Filter Templates
function filterTemplates() {
    const typeFilter = $('#filter-type').val();
    const statusFilter = $('#filter-status').val();
    
    let filteredTemplates = currentTemplates;
    
    if (typeFilter) {
        filteredTemplates = filteredTemplates.filter(template => 
            template.type === typeFilter
        );
    }
    
    if (statusFilter) {
        const isActive = statusFilter === 'active';
        filteredTemplates = filteredTemplates.filter(template => 
            template.is_active === isActive
        );
    }
    
    displayTemplates(filteredTemplates);
}

// Clear All Filters
function clearFilters() {
    $('#search-templates').val('');
    $('#filter-type').val('');
    $('#filter-status').val('');
    displayTemplates(currentTemplates);
}

// Refresh Templates
function refreshTemplates() {
    loadTemplates();
    showAlert('info', 'Templates refreshed.');
}

// Edit Template
function editTemplate(templateId) {
    window.location.href = `/sms/templates/${templateId}/edit`;
}

// Duplicate Template
function duplicateTemplate(templateId) {
    if (!confirm('Create a copy of this template?')) return;
    
    showLoading('body');
    
    $.post('/sms/templates/duplicate', {
        template_id: templateId,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Template duplicated successfully!');
            loadTemplates();
        } else {
            showAlert('error', 'Failed to duplicate template.');
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to duplicate template.');
    })
    .always(function() {
        hideLoading('body');
    });
}

// Test Template
function testTemplate(templateId) {
    currentTemplateId = templateId;
    const template = currentTemplates.find(t => t.id === templateId);
    
    if (template) {
        // Pre-fill variables if available
        if (template.variables && template.variables.length > 0) {
            const sampleVariables = {};
            template.variables.forEach(variable => {
                sampleVariables[variable] = `[${variable}]`;
            });
            $('#test-variables').val(JSON.stringify(sampleVariables, null, 2));
        }
        
        updateTemplatePreview();
    }
    
    $('#testTemplateModal').modal('show');
}

// Update Template Preview
function updateTemplatePreview() {
    if (!currentTemplateId) return;
    
    const template = currentTemplates.find(t => t.id === currentTemplateId);
    if (!template) return;
    
    let preview = template.content;
    
    try {
        const variables = JSON.parse($('#test-variables').val() || '{}');
        
        // Replace variables in preview
        Object.keys(variables).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            preview = preview.replace(regex, variables[key]);
        });
    } catch (e) {
        // Invalid JSON, show original template
    }
    
    $('#template-preview').text(preview);
}

// Send Test Template
function sendTestTemplate() {
    const phoneNumber = $('#test-phone').val();
    const variables = $('#test-variables').val();
    
    if (!phoneNumber) {
        showAlert('error', 'Please enter a phone number.');
        return;
    }
    
    showLoading('#testTemplateModal .modal-content');
    
    $.post('/sms-test/templates/send', {
        template_id: currentTemplateId,
        phone: phoneNumber,
        variables: variables,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Test SMS sent successfully!');
            $('#testTemplateModal').modal('hide');
        } else {
            showAlert('error', 'Failed to send test SMS: ' + response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to send test SMS.');
    })
    .always(function() {
        hideLoading('#testTemplateModal .modal-content');
    });
}

// Delete Template
function deleteTemplate(templateId) {
    const template = currentTemplates.find(t => t.id === templateId);
    if (!template) return;
    
    if (!confirm(`Are you sure you want to delete the template "${template.name}"?`)) return;
    
    showLoading('body');
    
    $.ajax({
        url: `/sms/templates/${templateId}`,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Template deleted successfully!');
            loadTemplates();
        } else {
            showAlert('error', 'Failed to delete template.');
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to delete template.');
    })
    .always(function() {
        hideLoading('body');
    });
}

// Import Templates
function importTemplates() {
    // This would typically open a file upload modal
    showAlert('info', 'Template import feature coming soon!');
}

// Show Empty State
function showEmptyState() {
    $('#templates-container').empty();
    $('#templates-empty').show();
}

// Utility Functions
function truncateText(text, length) {
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}
</script>
@endpush