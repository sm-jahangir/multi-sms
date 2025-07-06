@extends('layouts.app')

@section('title', isset($template) ? 'Edit Template' : 'Create Template')
@section('page-title', isset($template) ? 'Edit SMS Template' : 'Create SMS Template')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('sms.templates.index') }}" class="btn btn-secondary btn-custom">
            <i class="fas fa-arrow-left"></i> Back to Templates
        </a>
        @if(isset($template))
        <button type="button" class="btn btn-info btn-custom" onclick="previewTemplate()">
            <i class="fas fa-eye"></i> Preview
        </button>
        <button type="button" class="btn btn-warning btn-custom" onclick="testTemplate()">
            <i class="fas fa-paper-plane"></i> Test Send
        </button>
        @endif
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Template Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt"></i> 
                    {{ isset($template) ? 'Edit Template' : 'Template Details' }}
                </h5>
            </div>
            <div class="card-body">
                <form id="template-form" method="POST" action="{{ isset($template) ? route('sms.templates.update', $template->id) : route('sms.templates.store') }}">
                    @csrf
                    @if(isset($template))
                        @method('PUT')
                    @endif
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       value="{{ old('name', $template->name ?? '') }}" 
                                       placeholder="Enter template name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Choose a descriptive name for your template
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="type">Template Type</label>
                                <select class="form-control @error('type') is-invalid @enderror" 
                                        id="type" name="type">
                                    <option value="general" {{ old('type', $template->type ?? '') === 'general' ? 'selected' : '' }}>General</option>
                                    <option value="welcome" {{ old('type', $template->type ?? '') === 'welcome' ? 'selected' : '' }}>Welcome</option>
                                    <option value="notification" {{ old('type', $template->type ?? '') === 'notification' ? 'selected' : '' }}>Notification</option>
                                    <option value="marketing" {{ old('type', $template->type ?? '') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                                    <option value="reminder" {{ old('type', $template->type ?? '') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                                    <option value="verification" {{ old('type', $template->type ?? '') === 'verification' ? 'selected' : '' }}>Verification</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Message Content <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" name="content" rows="6" 
                                  placeholder="Enter your SMS message content..." required>{{ old('content', $template->content ?? '') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text text-muted">
                                Use variables like {{ '{{name}}' }}, {{ '{{amount}}' }}, etc. for dynamic content
                            </small>
                            <small class="text-muted">
                                <span id="char-count">0</span>/160 characters
                            </small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="variables">Template Variables</label>
                                <input type="text" class="form-control" id="variables" name="variables" 
                                       value="{{ old('variables', isset($template) && $template->variables ? implode(', ', $template->variables) : '') }}" 
                                       placeholder="name, amount, date">
                                <small class="form-text text-muted">
                                    Comma-separated list of variables (auto-detected from content)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       value="{{ old('category', $template->category ?? '') }}" 
                                       placeholder="e.g., Orders, Promotions">
                                <small class="form-text text-muted">
                                    Optional category for organization
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Brief description of when to use this template">{{ old('description', $template->description ?? '') }}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Template
                                </label>
                                <small class="form-text text-muted d-block">
                                    Only active templates can be used in campaigns
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_detect_variables" 
                                       name="auto_detect_variables" value="1" checked>
                                <label class="form-check-label" for="auto_detect_variables">
                                    Auto-detect Variables
                                </label>
                                <small class="form-text text-muted d-block">
                                    Automatically find variables in content
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-save"></i> 
                            {{ isset($template) ? 'Update Template' : 'Create Template' }}
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        @if(isset($template))
                        <button type="button" class="btn btn-info" onclick="duplicateTemplate()">
                            <i class="fas fa-copy"></i> Duplicate
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Template Preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-eye"></i> Live Preview</h6>
            </div>
            <div class="card-body">
                <div class="sms-preview">
                    <div class="sms-bubble">
                        <div id="preview-content">Enter content to see preview...</div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Preview shows how the SMS will appear
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Variable Helper -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-code"></i> Variable Helper</h6>
            </div>
            <div class="card-body">
                <div class="variable-buttons">
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('name')">
                        {{ '{{name}}' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('amount')">
                        {{ '{{amount}}' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('date')">
                        {{ '{{date}}' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('time')">
                        {{ '{{time}}' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('company')">
                        {{ '{{company}}' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                            onclick="insertVariable('phone')">
                        {{ '{{phone}}' }}
                    </button>
                </div>
                <hr>
                <div class="form-group mb-2">
                    <input type="text" class="form-control form-control-sm" 
                           id="custom-variable" placeholder="Custom variable name">
                </div>
                <button type="button" class="btn btn-sm btn-success btn-block" 
                        onclick="insertCustomVariable()">
                    <i class="fas fa-plus"></i> Add Custom Variable
                </button>
            </div>
        </div>
        
        <!-- Template Tips -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Tips</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Keep messages under 160 characters for single SMS
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Use clear, actionable language
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Include your company name for identification
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Test templates before using in campaigns
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success"></i>
                        Use variables for personalization
                    </li>
                </ul>
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
                <form id="test-form">
                    <div class="form-group">
                        <label for="test-phone">Phone Number</label>
                        <input type="tel" class="form-control" id="test-phone" 
                               placeholder="+1234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="test-variables">Variable Values (JSON)</label>
                        <textarea class="form-control" id="test-variables" rows="4" 
                                  placeholder='{"name": "John Doe", "amount": "$100"}'></textarea>
                    </div>
                    <div class="form-group">
                        <label>Preview</label>
                        <div class="alert alert-info" id="test-preview">
                            Template preview will appear here...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestSMS()">Send Test SMS</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* SMS Preview Styles */
.sms-preview {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
}

.sms-bubble {
    background: #007bff;
    color: white;
    padding: 12px 16px;
    border-radius: 18px;
    border-bottom-right-radius: 4px;
    max-width: 250px;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.4;
}

.variable-buttons .btn {
    margin-right: 5px;
}

#char-count {
    font-weight: bold;
}

#char-count.warning {
    color: #ffc107;
}

#char-count.danger {
    color: #dc3545;
}
</style>
@endpush

@push('scripts')
<script>
// Template Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize character counter
    updateCharCount();
    updatePreview();
    
    // Auto-detect variables when content changes
    $('#content').on('input', function() {
        updateCharCount();
        updatePreview();
        
        if ($('#auto_detect_variables').is(':checked')) {
            autoDetectVariables();
        }
    });
    
    // Update preview when variables change
    $('#test-variables').on('input', function() {
        updateTestPreview();
    });
    
    // Form validation
    $('#template-form').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
});

// Update Character Count
function updateCharCount() {
    const content = $('#content').val();
    const count = content.length;
    const counter = $('#char-count');
    
    counter.text(count);
    
    // Update color based on length
    counter.removeClass('warning danger');
    if (count > 160) {
        counter.addClass('danger');
    } else if (count > 140) {
        counter.addClass('warning');
    }
}

// Update Live Preview
function updatePreview() {
    const content = $('#content').val() || 'Enter content to see preview...';
    $('#preview-content').text(content);
}

// Auto-detect Variables in Content
function autoDetectVariables() {
    const content = $('#content').val();
    const variableRegex = /{{\s*(\w+)\s*}}/g;
    const variables = [];
    let match;
    
    while ((match = variableRegex.exec(content)) !== null) {
        if (!variables.includes(match[1])) {
            variables.push(match[1]);
        }
    }
    
    $('#variables').val(variables.join(', '));
}

// Insert Variable at Cursor Position
function insertVariable(variable) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);
    
    const variableText = `{{${variable}}}`;
    textarea.value = before + variableText + after;
    textarea.selectionStart = textarea.selectionEnd = start + variableText.length;
    
    // Trigger events
    $(textarea).trigger('input');
    textarea.focus();
}

// Insert Custom Variable
function insertCustomVariable() {
    const customVar = $('#custom-variable').val().trim();
    if (!customVar) {
        showAlert('error', 'Please enter a variable name.');
        return;
    }
    
    // Validate variable name (alphanumeric and underscore only)
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(customVar)) {
        showAlert('error', 'Variable name can only contain letters, numbers, and underscores.');
        return;
    }
    
    insertVariable(customVar);
    $('#custom-variable').val('');
}

// Preview Template
function previewTemplate() {
    const content = $('#content').val();
    if (!content) {
        showAlert('error', 'Please enter template content first.');
        return;
    }
    
    // Show preview in modal or new window
    const previewWindow = window.open('', '_blank', 'width=400,height=300');
    previewWindow.document.write(`
        <html>
            <head><title>Template Preview</title></head>
            <body style="font-family: Arial, sans-serif; padding: 20px;">
                <h3>SMS Template Preview</h3>
                <div style="background: #007bff; color: white; padding: 12px 16px; border-radius: 18px; border-bottom-right-radius: 4px; max-width: 250px; word-wrap: break-word;">
                    ${content}
                </div>
                <p><small>Character count: ${content.length}/160</small></p>
            </body>
        </html>
    `);
}

// Test Template
function testTemplate() {
    const content = $('#content').val();
    if (!content) {
        showAlert('error', 'Please enter template content first.');
        return;
    }
    
    // Pre-fill test variables
    const variables = $('#variables').val().split(',').map(v => v.trim()).filter(v => v);
    if (variables.length > 0) {
        const sampleVariables = {};
        variables.forEach(variable => {
            sampleVariables[variable] = `[${variable}]`;
        });
        $('#test-variables').val(JSON.stringify(sampleVariables, null, 2));
    }
    
    updateTestPreview();
    $('#testTemplateModal').modal('show');
}

// Update Test Preview
function updateTestPreview() {
    let content = $('#content').val();
    
    try {
        const variables = JSON.parse($('#test-variables').val() || '{}');
        
        // Replace variables in preview
        Object.keys(variables).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            content = content.replace(regex, variables[key]);
        });
    } catch (e) {
        // Invalid JSON, show original content
    }
    
    $('#test-preview').text(content || 'Template preview will appear here...');
}

// Send Test SMS
function sendTestSMS() {
    const phoneNumber = $('#test-phone').val();
    const variables = $('#test-variables').val();
    const content = $('#content').val();
    
    if (!phoneNumber) {
        showAlert('error', 'Please enter a phone number.');
        return;
    }
    
    if (!content) {
        showAlert('error', 'Please enter template content.');
        return;
    }
    
    showLoading('#testTemplateModal .modal-content');
    
    // Process content with variables
    let processedContent = content;
    try {
        const vars = JSON.parse(variables || '{}');
        Object.keys(vars).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            processedContent = processedContent.replace(regex, vars[key]);
        });
    } catch (e) {
        // Use original content if variables are invalid
    }
    
    // Send via simple SMS API
    $.get('/test-sms', {
        to: phoneNumber,
        message: processedContent
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

// Duplicate Template
function duplicateTemplate() {
    if (!confirm('Create a copy of this template?')) return;
    
    // Clear the form action and method to create new template
    const form = $('#template-form');
    form.attr('action', '{{ route("sms.templates.store") }}');
    form.find('input[name="_method"]').remove();
    
    // Update form title and button
    $('h1').text('Create SMS Template (Copy)');
    $('#name').val($('#name').val() + ' (Copy)');
    
    showAlert('info', 'Template ready for duplication. Modify as needed and save.');
}

// Reset Form
function resetForm() {
    if (!confirm('Reset all changes?')) return;
    
    $('#template-form')[0].reset();
    updateCharCount();
    updatePreview();
    showAlert('info', 'Form reset successfully.');
}

// Validate Form
function validateForm() {
    const name = $('#name').val().trim();
    const content = $('#content').val().trim();
    
    if (!name) {
        showAlert('error', 'Template name is required.');
        $('#name').focus();
        return false;
    }
    
    if (!content) {
        showAlert('error', 'Template content is required.');
        $('#content').focus();
        return false;
    }
    
    if (content.length > 1000) {
        showAlert('error', 'Template content is too long (max 1000 characters).');
        $('#content').focus();
        return false;
    }
    
    return true;
}
</script>
@endpush