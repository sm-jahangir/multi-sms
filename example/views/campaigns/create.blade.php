@extends('layouts.app')

@section('title', isset($campaign) ? 'Edit Campaign' : 'Create Campaign')
@section('page-title', isset($campaign) ? 'Edit SMS Campaign' : 'Create SMS Campaign')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('sms.campaigns.index') }}" class="btn btn-secondary btn-custom">
            <i class="fas fa-arrow-left"></i> Back to Campaigns
        </a>
        @if(isset($campaign))
        <button type="button" class="btn btn-info btn-custom" onclick="previewCampaign()">
            <i class="fas fa-eye"></i> Preview
        </button>
        <button type="button" class="btn btn-warning btn-custom" onclick="testCampaign()">
            <i class="fas fa-paper-plane"></i> Test Send
        </button>
        @endif
        <button type="button" class="btn btn-success btn-custom" onclick="saveDraft()">
            <i class="fas fa-save"></i> Save Draft
        </button>
    </div>
@endsection

@section('content')
<form id="campaign-form" method="POST" action="{{ isset($campaign) ? route('sms.campaigns.update', $campaign->id) : route('sms.campaigns.store') }}">
    @csrf
    @if(isset($campaign))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Campaign Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Campaign Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name">Campaign Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       value="{{ old('name', $campaign->name ?? '') }}" 
                                       placeholder="Enter campaign name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="type">Campaign Type</label>
                                <select class="form-control @error('type') is-invalid @enderror" 
                                        id="type" name="type" onchange="toggleScheduleOptions()">
                                    <option value="immediate" {{ old('type', $campaign->type ?? '') === 'immediate' ? 'selected' : '' }}>Send Immediately</option>
                                    <option value="scheduled" {{ old('type', $campaign->type ?? '') === 'scheduled' ? 'selected' : '' }}>Schedule for Later</option>
                                    <option value="recurring" {{ old('type', $campaign->type ?? '') === 'recurring' ? 'selected' : '' }}>Recurring Campaign</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Brief description of this campaign">{{ old('description', $campaign->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Message Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comment-alt"></i> Message Content</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="template_id">Use Template</label>
                                <select class="form-control" id="template_id" name="template_id" onchange="loadTemplate()">
                                    <option value="">Select a template (optional)</option>
                                    @foreach($templates ?? [] as $template)
                                        <option value="{{ $template->id }}" 
                                                data-content="{{ $template->content }}"
                                                data-variables="{{ implode(',', $template->variables ?? []) }}"
                                                {{ old('template_id', $campaign->template_id ?? '') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Select a template to auto-fill message content
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sender_id">Sender ID</label>
                                <input type="text" class="form-control" id="sender_id" name="sender_id" 
                                       value="{{ old('sender_id', $campaign->sender_id ?? config('multi-sms.default_sender')) }}" 
                                       placeholder="Sender name or number">
                                <small class="form-text text-muted">
                                    Leave empty to use default sender
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message_content">Message Content <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message_content') is-invalid @enderror" 
                                  id="message_content" name="message_content" rows="6" 
                                  placeholder="Enter your SMS message content..." required>{{ old('message_content', $campaign->message_content ?? '') }}</textarea>
                        @error('message_content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text text-muted">
                                Use variables like {{ '{{name}}' }}, {{ '{{amount}}' }} for personalization
                            </small>
                            <small class="text-muted">
                                <span id="char-count">0</span>/160 characters
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="variables">Message Variables</label>
                        <input type="text" class="form-control" id="variables" name="variables" 
                               value="{{ old('variables', isset($campaign) && $campaign->variables ? implode(', ', $campaign->variables) : '') }}" 
                               placeholder="name, amount, date" readonly>
                        <small class="form-text text-muted">
                            Variables detected in message content (auto-populated)
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Recipients -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Recipients</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Recipient Method</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_method" 
                                   id="method_manual" value="manual" 
                                   {{ old('recipient_method', $campaign->recipient_method ?? 'manual') === 'manual' ? 'checked' : '' }}
                                   onchange="toggleRecipientMethod()">
                            <label class="form-check-label" for="method_manual">
                                Manual Entry
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_method" 
                                   id="method_upload" value="upload" 
                                   {{ old('recipient_method', $campaign->recipient_method ?? '') === 'upload' ? 'checked' : '' }}
                                   onchange="toggleRecipientMethod()">
                            <label class="form-check-label" for="method_upload">
                                Upload CSV File
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_method" 
                                   id="method_database" value="database" 
                                   {{ old('recipient_method', $campaign->recipient_method ?? '') === 'database' ? 'checked' : '' }}
                                   onchange="toggleRecipientMethod()">
                            <label class="form-check-label" for="method_database">
                                From Database Query
                            </label>
                        </div>
                    </div>
                    
                    <!-- Manual Entry -->
                    <div id="manual-recipients" class="recipient-method">
                        <div class="form-group">
                            <label for="recipients_manual">Phone Numbers</label>
                            <textarea class="form-control @error('recipients_manual') is-invalid @enderror" 
                                      id="recipients_manual" name="recipients_manual" rows="6" 
                                      placeholder="Enter phone numbers (one per line or comma-separated)&#10;+1234567890&#10;+0987654321&#10;or&#10;+1234567890, +0987654321">{{ old('recipients_manual', isset($campaign) && is_array($campaign->recipients) ? implode("\n", $campaign->recipients) : '') }}</textarea>
                            @error('recipients_manual')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Enter phone numbers with country code. Total: <span id="manual-count">0</span> numbers
                            </small>
                        </div>
                    </div>
                    
                    <!-- CSV Upload -->
                    <div id="upload-recipients" class="recipient-method" style="display: none;">
                        <div class="form-group">
                            <label for="recipients_file">CSV File</label>
                            <input type="file" class="form-control-file @error('recipients_file') is-invalid @enderror" 
                                   id="recipients_file" name="recipients_file" accept=".csv">
                            @error('recipients_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Upload a CSV file with phone numbers. First column should contain phone numbers.
                                <a href="#" onclick="downloadSampleCSV()">Download sample CSV</a>
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="csv_phone_column">Phone Number Column</label>
                            <input type="text" class="form-control" id="csv_phone_column" name="csv_phone_column" 
                                   value="{{ old('csv_phone_column', 'phone') }}" placeholder="phone">
                            <small class="form-text text-muted">
                                Column name containing phone numbers
                            </small>
                        </div>
                    </div>
                    
                    <!-- Database Query -->
                    <div id="database-recipients" class="recipient-method" style="display: none;">
                        <div class="form-group">
                            <label for="database_query">Database Query</label>
                            <textarea class="form-control @error('database_query') is-invalid @enderror" 
                                      id="database_query" name="database_query" rows="4" 
                                      placeholder="SELECT phone FROM users WHERE active = 1">{{ old('database_query', $campaign->database_query ?? '') }}</textarea>
                            @error('database_query')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                SQL query to fetch phone numbers from database
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="testDatabaseQuery()">
                            <i class="fas fa-play"></i> Test Query
                        </button>
                        <div id="query-result" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <!-- Scheduling Options -->
            <div class="card mb-4" id="schedule-options" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Scheduling Options</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scheduled_at">Schedule Date & Time</label>
                                <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       id="scheduled_at" name="scheduled_at" 
                                       value="{{ old('scheduled_at', isset($campaign) && $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="timezone">Timezone</label>
                                <select class="form-control" id="timezone" name="timezone">
                                    <option value="UTC" {{ old('timezone', $campaign->timezone ?? 'UTC') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="America/New_York" {{ old('timezone', $campaign->timezone ?? '') === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                    <option value="America/Chicago" {{ old('timezone', $campaign->timezone ?? '') === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                    <option value="America/Denver" {{ old('timezone', $campaign->timezone ?? '') === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                    <option value="America/Los_Angeles" {{ old('timezone', $campaign->timezone ?? '') === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                    <option value="Asia/Dhaka" {{ old('timezone', $campaign->timezone ?? '') === 'Asia/Dhaka' ? 'selected' : '' }}>Bangladesh Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recurring Options -->
                    <div id="recurring-options" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="recurring_frequency">Frequency</label>
                                    <select class="form-control" id="recurring_frequency" name="recurring_frequency">
                                        <option value="daily" {{ old('recurring_frequency', $campaign->recurring_frequency ?? '') === 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ old('recurring_frequency', $campaign->recurring_frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ old('recurring_frequency', $campaign->recurring_frequency ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="recurring_interval">Interval</label>
                                    <input type="number" class="form-control" id="recurring_interval" name="recurring_interval" 
                                           value="{{ old('recurring_interval', $campaign->recurring_interval ?? 1) }}" min="1">
                                    <small class="form-text text-muted">Every X days/weeks/months</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="recurring_end_date">End Date</label>
                                    <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date" 
                                           value="{{ old('recurring_end_date', isset($campaign) && $campaign->recurring_end_date ? $campaign->recurring_end_date->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Campaign Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-eye"></i> Campaign Preview</h6>
                </div>
                <div class="card-body">
                    <div class="sms-preview">
                        <div class="sms-bubble">
                            <div id="preview-content">Enter message content to see preview...</div>
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
            
            <!-- Campaign Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Campaign Summary</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Recipients:</strong></td>
                            <td><span id="total-recipients">0</span></td>
                        </tr>
                        <tr>
                            <td><strong>Message Length:</strong></td>
                            <td><span id="message-length">0</span> chars</td>
                        </tr>
                        <tr>
                            <td><strong>SMS Parts:</strong></td>
                            <td><span id="sms-parts">1</span></td>
                        </tr>
                        <tr>
                            <td><strong>Estimated Cost:</strong></td>
                            <td>$<span id="estimated-cost">0.00</span></td>
                        </tr>
                        <tr>
                            <td><strong>Send Time:</strong></td>
                            <td><span id="send-time">Immediate</span></td>
                        </tr>
                    </table>
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
                                onclick="insertVariable('company')">
                            {{ '{{company}}' }}
                        </button>
                    </div>
                    <hr>
                    <div class="form-group mb-2">
                        <input type="text" class="form-control form-control-sm" 
                               id="custom-variable" placeholder="Custom variable">
                    </div>
                    <button type="button" class="btn btn-sm btn-success btn-block" 
                            onclick="insertCustomVariable()">
                        <i class="fas fa-plus"></i> Add Variable
                    </button>
                </div>
            </div>
            
            <!-- Campaign Settings -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-cog"></i> Settings</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="normal" {{ old('priority', $campaign->priority ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority', $campaign->priority ?? '') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="low" {{ old('priority', $campaign->priority ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rate_limit">Rate Limit (per minute)</label>
                        <input type="number" class="form-control" id="rate_limit" name="rate_limit" 
                               value="{{ old('rate_limit', $campaign->rate_limit ?? 60) }}" min="1" max="1000">
                        <small class="form-text text-muted">Messages per minute</small>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="track_clicks" name="track_clicks" value="1" 
                               {{ old('track_clicks', $campaign->track_clicks ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="track_clicks">
                            Track Link Clicks
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="send_confirmation" name="send_confirmation" value="1" 
                               {{ old('send_confirmation', $campaign->send_confirmation ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="send_confirmation">
                            Send Completion Email
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="button" class="btn btn-info" onclick="previewCampaign()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" name="action" value="schedule" class="btn btn-warning" id="schedule-btn" style="display: none;">
                                <i class="fas fa-clock"></i> Schedule Campaign
                            </button>
                            <button type="submit" name="action" value="send" class="btn btn-success" id="send-btn">
                                <i class="fas fa-paper-plane"></i> 
                                {{ isset($campaign) ? 'Update & Send' : 'Send Campaign' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Test Campaign Modal -->
<div class="modal fade" id="testCampaignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Campaign</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="test-form">
                    <div class="form-group">
                        <label for="test-phone">Test Phone Number</label>
                        <input type="tel" class="form-control" id="test-phone" 
                               placeholder="+1234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="test-variables">Variable Values (JSON)</label>
                        <textarea class="form-control" id="test-variables" rows="4" 
                                  placeholder='{"name": "John Doe", "amount": "$100"}'></textarea>
                    </div>
                    <div class="form-group">
                        <label>Test Message Preview</label>
                        <div class="alert alert-info" id="test-preview">
                            Message preview will appear here...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestCampaign()">Send Test</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Campaign Form Styles */
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

.recipient-method {
    border-left: 3px solid #007bff;
    padding-left: 15px;
    margin-left: 10px;
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

.form-check {
    margin-bottom: 10px;
}
</style>
@endpush

@push('scripts')
<script>
// Campaign Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form
    updateCharCount();
    updatePreview();
    updateSummary();
    toggleScheduleOptions();
    toggleRecipientMethod();
    
    // Event listeners
    $('#message_content').on('input', function() {
        updateCharCount();
        updatePreview();
        autoDetectVariables();
        updateSummary();
    });
    
    $('#recipients_manual').on('input', function() {
        updateManualCount();
        updateSummary();
    });
    
    $('#test-variables').on('input', function() {
        updateTestPreview();
    });
    
    // Form validation
    $('#campaign-form').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
    
    // Initialize counts
    updateManualCount();
});

// Update Character Count
function updateCharCount() {
    const content = $('#message_content').val();
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

// Update Preview
function updatePreview() {
    const content = $('#message_content').val() || 'Enter message content to see preview...';
    $('#preview-content').text(content);
}

// Auto-detect Variables
function autoDetectVariables() {
    const content = $('#message_content').val();
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

// Update Campaign Summary
function updateSummary() {
    const content = $('#message_content').val();
    const recipients = getRecipientCount();
    
    // Update summary
    $('#total-recipients').text(recipients);
    $('#message-length').text(content.length);
    
    // Calculate SMS parts
    const parts = Math.ceil(content.length / 160) || 1;
    $('#sms-parts').text(parts);
    
    // Estimate cost (assuming $0.01 per SMS part)
    const cost = (recipients * parts * 0.01).toFixed(2);
    $('#estimated-cost').text(cost);
    
    // Update send time
    const type = $('#type').val();
    const scheduledAt = $('#scheduled_at').val();
    let sendTime = 'Immediate';
    
    if (type === 'scheduled' && scheduledAt) {
        sendTime = new Date(scheduledAt).toLocaleString();
    } else if (type === 'recurring') {
        sendTime = 'Recurring';
    }
    
    $('#send-time').text(sendTime);
}

// Get Recipient Count
function getRecipientCount() {
    const method = $('input[name="recipient_method"]:checked').val();
    
    if (method === 'manual') {
        const manual = $('#recipients_manual').val().trim();
        if (!manual) return 0;
        
        // Count lines and comma-separated values
        const lines = manual.split('\n').filter(line => line.trim());
        let count = 0;
        lines.forEach(line => {
            count += line.split(',').filter(phone => phone.trim()).length;
        });
        return count;
    }
    
    return 0; // For upload and database methods, count would be determined server-side
}

// Update Manual Count
function updateManualCount() {
    const count = getRecipientCount();
    $('#manual-count').text(count);
}

// Toggle Schedule Options
function toggleScheduleOptions() {
    const type = $('#type').val();
    const scheduleOptions = $('#schedule-options');
    const recurringOptions = $('#recurring-options');
    const scheduleBtn = $('#schedule-btn');
    const sendBtn = $('#send-btn');
    
    if (type === 'scheduled' || type === 'recurring') {
        scheduleOptions.show();
        scheduleBtn.show();
        sendBtn.text(sendBtn.text().replace('Send', 'Schedule'));
    } else {
        scheduleOptions.hide();
        scheduleBtn.hide();
        sendBtn.text(sendBtn.text().replace('Schedule', 'Send'));
    }
    
    if (type === 'recurring') {
        recurringOptions.show();
    } else {
        recurringOptions.hide();
    }
}

// Toggle Recipient Method
function toggleRecipientMethod() {
    const method = $('input[name="recipient_method"]:checked').val();
    
    $('.recipient-method').hide();
    $(`#${method}-recipients`).show();
    
    updateSummary();
}

// Load Template
function loadTemplate() {
    const templateSelect = $('#template_id');
    const selectedOption = templateSelect.find('option:selected');
    
    if (selectedOption.val()) {
        const content = selectedOption.data('content');
        const variables = selectedOption.data('variables');
        
        $('#message_content').val(content);
        if (variables) {
            $('#variables').val(variables.replace(/,/g, ', '));
        }
        
        updateCharCount();
        updatePreview();
        updateSummary();
    }
}

// Insert Variable
function insertVariable(variable) {
    const textarea = document.getElementById('message_content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);
    
    const variableText = `{{${variable}}}`;
    textarea.value = before + variableText + after;
    textarea.selectionStart = textarea.selectionEnd = start + variableText.length;
    
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
    
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(customVar)) {
        showAlert('error', 'Variable name can only contain letters, numbers, and underscores.');
        return;
    }
    
    insertVariable(customVar);
    $('#custom-variable').val('');
}

// Test Database Query
function testDatabaseQuery() {
    const query = $('#database_query').val().trim();
    if (!query) {
        showAlert('error', 'Please enter a database query.');
        return;
    }
    
    showLoading('#query-result');
    
    $.post('/sms/campaigns/test-query', {
        query: query,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status === 'success') {
            $('#query-result').html(`
                <div class="alert alert-success">
                    <strong>Query successful!</strong><br>
                    Found ${response.count} recipients.
                </div>
            `);
        } else {
            $('#query-result').html(`
                <div class="alert alert-danger">
                    <strong>Query failed:</strong><br>
                    ${response.message}
                </div>
            `);
        }
    })
    .fail(function() {
        $('#query-result').html(`
            <div class="alert alert-danger">
                <strong>Error:</strong> Failed to test query.
            </div>
        `);
    })
    .always(function() {
        hideLoading('#query-result');
    });
}

// Preview Campaign
function previewCampaign() {
    const content = $('#message_content').val();
    if (!content) {
        showAlert('error', 'Please enter message content first.');
        return;
    }
    
    // Open preview in new window
    const previewWindow = window.open('', '_blank', 'width=400,height=600');
    const summary = {
        name: $('#name').val(),
        content: content,
        recipients: getRecipientCount(),
        type: $('#type').val(),
        scheduledAt: $('#scheduled_at').val()
    };
    
    previewWindow.document.write(`
        <html>
            <head>
                <title>Campaign Preview</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .sms-bubble { background: #007bff; color: white; padding: 12px 16px; border-radius: 18px; border-bottom-right-radius: 4px; max-width: 250px; word-wrap: break-word; margin: 20px 0; }
                    .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; }
                </style>
            </head>
            <body>
                <h3>Campaign Preview: ${summary.name}</h3>
                <div class="summary">
                    <p><strong>Recipients:</strong> ${summary.recipients}</p>
                    <p><strong>Type:</strong> ${summary.type}</p>
                    ${summary.scheduledAt ? `<p><strong>Scheduled:</strong> ${new Date(summary.scheduledAt).toLocaleString()}</p>` : ''}
                </div>
                <h4>Message Preview:</h4>
                <div class="sms-bubble">${summary.content}</div>
                <p><small>Character count: ${summary.content.length}/160</small></p>
            </body>
        </html>
    `);
}

// Test Campaign
function testCampaign() {
    const content = $('#message_content').val();
    if (!content) {
        showAlert('error', 'Please enter message content first.');
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
    $('#testCampaignModal').modal('show');
}

// Update Test Preview
function updateTestPreview() {
    let content = $('#message_content').val();
    
    try {
        const variables = JSON.parse($('#test-variables').val() || '{}');
        
        Object.keys(variables).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            content = content.replace(regex, variables[key]);
        });
    } catch (e) {
        // Invalid JSON, show original content
    }
    
    $('#test-preview').text(content || 'Message preview will appear here...');
}

// Send Test Campaign
function sendTestCampaign() {
    const phoneNumber = $('#test-phone').val();
    const variables = $('#test-variables').val();
    const content = $('#message_content').val();
    
    if (!phoneNumber) {
        showAlert('error', 'Please enter a phone number.');
        return;
    }
    
    showLoading('#testCampaignModal .modal-content');
    
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
    
    $.get('/test-sms', {
        to: phoneNumber,
        message: processedContent
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAlert('success', 'Test SMS sent successfully!');
            $('#testCampaignModal').modal('hide');
        } else {
            showAlert('error', 'Failed to send test SMS: ' + response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to send test SMS.');
    })
    .always(function() {
        hideLoading('#testCampaignModal .modal-content');
    });
}

// Save Draft
function saveDraft() {
    const form = $('#campaign-form');
    const originalAction = form.attr('action');
    
    // Add draft parameter
    $('<input>').attr({
        type: 'hidden',
        name: 'action',
        value: 'draft'
    }).appendTo(form);
    
    form.submit();
}

// Reset Form
function resetForm() {
    if (!confirm('Reset all changes?')) return;
    
    $('#campaign-form')[0].reset();
    updateCharCount();
    updatePreview();
    updateSummary();
    toggleScheduleOptions();
    toggleRecipientMethod();
    showAlert('info', 'Form reset successfully.');
}

// Validate Form
function validateForm() {
    const name = $('#name').val().trim();
    const content = $('#message_content').val().trim();
    const type = $('#type').val();
    const method = $('input[name="recipient_method"]:checked').val();
    
    if (!name) {
        showAlert('error', 'Campaign name is required.');
        $('#name').focus();
        return false;
    }
    
    if (!content) {
        showAlert('error', 'Message content is required.');
        $('#message_content').focus();
        return false;
    }
    
    if (method === 'manual') {
        const recipients = $('#recipients_manual').val().trim();
        if (!recipients) {
            showAlert('error', 'Please enter recipient phone numbers.');
            $('#recipients_manual').focus();
            return false;
        }
    }
    
    if (type === 'scheduled') {
        const scheduledAt = $('#scheduled_at').val();
        if (!scheduledAt) {
            showAlert('error', 'Please select a schedule date and time.');
            $('#scheduled_at').focus();
            return false;
        }
        
        if (new Date(scheduledAt) <= new Date()) {
            showAlert('error', 'Schedule date must be in the future.');
            $('#scheduled_at').focus();
            return false;
        }
    }
    
    return true;
}

// Download Sample CSV
function downloadSampleCSV() {
    const csvContent = "phone,name,email\n+1234567890,John Doe,john@example.com\n+0987654321,Jane Smith,jane@example.com";
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sample_recipients.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush