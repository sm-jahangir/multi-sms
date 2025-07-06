@extends('layouts.app')

@section('title', isset($autoresponder) ? 'Edit Autoresponder' : 'Create Autoresponder')
@section('page-title', isset($autoresponder) ? 'Edit SMS Autoresponder' : 'Create SMS Autoresponder')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('sms.autoresponders.index') }}" class="btn btn-secondary btn-custom">
            <i class="fas fa-arrow-left"></i> Back to Autoresponders
        </a>
        @if(isset($autoresponder))
        <button type="button" class="btn btn-info btn-custom" onclick="previewAutoresponder()">
            <i class="fas fa-eye"></i> Preview
        </button>
        <button type="button" class="btn btn-warning btn-custom" onclick="testAutoresponder()">
            <i class="fas fa-paper-plane"></i> Test
        </button>
        @endif
        <button type="button" class="btn btn-success btn-custom" onclick="saveDraft()">
            <i class="fas fa-save"></i> Save Draft
        </button>
    </div>
@endsection

@section('content')
<form id="autoresponder-form" method="POST" action="{{ isset($autoresponder) ? route('sms.autoresponders.update', $autoresponder->id) : route('sms.autoresponders.store') }}">
    @csrf
    @if(isset($autoresponder))
        @method('PUT')
    @endif
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name">Autoresponder Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       value="{{ old('name', $autoresponder->name ?? '') }}" 
                                       placeholder="Enter autoresponder name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <select class="form-control @error('is_active') is-invalid @enderror" 
                                        id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', $autoresponder->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $autoresponder->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Brief description of this autoresponder">{{ old('description', $autoresponder->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            
            <!-- Triggers Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Triggers</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addTrigger()">
                            <i class="fas fa-plus"></i> Add Trigger
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="triggers-container">
                        @if(isset($autoresponder) && $autoresponder->triggers)
                            @foreach($autoresponder->triggers as $index => $trigger)
                                <div class="trigger-item" data-index="{{ $index }}">
                                    <!-- Trigger content will be populated by JavaScript -->
                                </div>
                            @endforeach
                        @else
                            <div class="trigger-item" data-index="0">
                                <!-- Default trigger will be added by JavaScript -->
                            </div>
                        @endif
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-info-circle"></i> Trigger Types:</h6>
                        <ul class="mb-0">
                            <li><strong>Keyword:</strong> Triggered when a specific keyword is received</li>
                            <li><strong>Schedule:</strong> Triggered at specific times or intervals</li>
                            <li><strong>Event:</strong> Triggered by system events (user registration, order, etc.)</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Response Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-reply"></i> Response Configuration</h5>
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
                                                {{ old('template_id', $autoresponder->template_id ?? '') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Select a template to auto-fill response message
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="response_delay">Response Delay (seconds)</label>
                                <input type="number" class="form-control" id="response_delay" name="response_delay" 
                                       value="{{ old('response_delay', $autoresponder->response_delay ?? 0) }}" 
                                       min="0" max="3600" placeholder="0">
                                <small class="form-text text-muted">
                                    Delay before sending response (0 = immediate)
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="response_message">Response Message <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('response_message') is-invalid @enderror" 
                                  id="response_message" name="response_message" rows="6" 
                                  placeholder="Enter the automatic response message..." required>{{ old('response_message', $autoresponder->response_message ?? '') }}</textarea>
                        @error('response_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text text-muted">
                                Use variables like {{ '{{name}}' }}, {{ '{{keyword}}' }}, {{ '{{time}}' }} for personalization
                            </small>
                            <small class="text-muted">
                                <span id="char-count">0</span>/160 characters
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="variables">Available Variables</label>
                        <input type="text" class="form-control" id="variables" name="variables" 
                               value="{{ old('variables', isset($autoresponder) && $autoresponder->variables ? implode(', ', $autoresponder->variables) : '') }}" 
                               placeholder="name, keyword, time" readonly>
                        <small class="form-text text-muted">
                            Variables detected in response message (auto-populated)
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Advanced Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_triggers_per_day">Max Triggers Per Day</label>
                                <input type="number" class="form-control" id="max_triggers_per_day" name="max_triggers_per_day" 
                                       value="{{ old('max_triggers_per_day', $autoresponder->max_triggers_per_day ?? 0) }}" 
                                       min="0" placeholder="0 = unlimited">
                                <small class="form-text text-muted">
                                    Maximum times this autoresponder can be triggered per day
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cooldown_minutes">Cooldown Period (minutes)</label>
                                <input type="number" class="form-control" id="cooldown_minutes" name="cooldown_minutes" 
                                       value="{{ old('cooldown_minutes', $autoresponder->cooldown_minutes ?? 0) }}" 
                                       min="0" placeholder="0 = no cooldown">
                                <small class="form-text text-muted">
                                    Minimum time between triggers for the same number
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                       value="{{ old('start_date', isset($autoresponder) && $autoresponder->start_date ? $autoresponder->start_date->format('Y-m-d\TH:i') : '') }}">
                                <small class="form-text text-muted">
                                    When this autoresponder becomes active
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                       value="{{ old('end_date', isset($autoresponder) && $autoresponder->end_date ? $autoresponder->end_date->format('Y-m-d\TH:i') : '') }}">
                                <small class="form-text text-muted">
                                    When this autoresponder expires (optional)
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="case_sensitive" name="case_sensitive" value="1" 
                               {{ old('case_sensitive', $autoresponder->case_sensitive ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="case_sensitive">
                            Case Sensitive Keywords
                        </label>
                        <small class="form-text text-muted">
                            Whether keyword matching should be case sensitive
                        </small>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exact_match" name="exact_match" value="1" 
                               {{ old('exact_match', $autoresponder->exact_match ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="exact_match">
                            Exact Keyword Match
                        </label>
                        <small class="form-text text-muted">
                            Keyword must match exactly (not as part of a larger word)
                        </small>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="log_responses" name="log_responses" value="1" 
                               {{ old('log_responses', $autoresponder->log_responses ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="log_responses">
                            Log All Responses
                        </label>
                        <small class="form-text text-muted">
                            Keep detailed logs of all autoresponder activities
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Response Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-eye"></i> Response Preview</h6>
                </div>
                <div class="card-body">
                    <div class="sms-preview">
                        <div class="sms-bubble">
                            <div id="preview-content">Enter response message to see preview...</div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Preview shows how the response will appear
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Trigger Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt"></i> Trigger Summary</h6>
                </div>
                <div class="card-body">
                    <div id="trigger-summary">
                        <p class="text-muted">Add triggers to see summary</p>
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
                                onclick="insertVariable('keyword')">
                            {{ '{{keyword}}' }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                                onclick="insertVariable('time')">
                            {{ '{{time}}' }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                                onclick="insertVariable('date')">
                            {{ '{{date}}' }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-1" 
                                onclick="insertVariable('phone')">
                            {{ '{{phone}}' }}
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
            
            <!-- Statistics (if editing) -->
            @if(isset($autoresponder))
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Total Triggers:</strong></td>
                            <td>{{ $autoresponder->triggers_count ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Responses:</strong></td>
                            <td>{{ $autoresponder->responses_count ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><strong>Success Rate:</strong></td>
                            <td>{{ $autoresponder->success_rate ?? 0 }}%</td>
                        </tr>
                        <tr>
                            <td><strong>Last Triggered:</strong></td>
                            <td>{{ $autoresponder->last_triggered_at ? $autoresponder->last_triggered_at->diffForHumans() : 'Never' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-lightning-bolt"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="previewAutoresponder()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="testAutoresponder()">
                            <i class="fas fa-paper-plane"></i> Test Response
                        </button>
                        @if(isset($autoresponder))
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="duplicateAutoresponder()">
                            <i class="fas fa-copy"></i> Duplicate
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewLogs()">
                            <i class="fas fa-history"></i> View Logs
                        </button>
                        @endif
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
                            <button type="button" class="btn btn-info" onclick="previewAutoresponder()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" name="action" value="save" class="btn btn-success">
                                <i class="fas fa-check"></i> 
                                {{ isset($autoresponder) ? 'Update Autoresponder' : 'Create Autoresponder' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Trigger Template -->
<div id="trigger-template" style="display: none;">
    <div class="trigger-item border rounded p-3 mb-3" data-index="">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Trigger <span class="trigger-number"></span></h6>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTrigger(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Trigger Type</label>
                    <select class="form-control trigger-type" name="triggers[][trigger_type]" onchange="updateTriggerFields(this)">
                        <option value="keyword">Keyword</option>
                        <option value="schedule">Schedule</option>
                        <option value="event">Event</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Priority</label>
                    <select class="form-control" name="triggers[][priority]">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Keyword Fields -->
        <div class="trigger-fields keyword-fields">
            <div class="form-group">
                <label>Keyword <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="triggers[][keyword]" 
                       placeholder="Enter keyword (e.g., HELP, INFO, STOP)">
                <small class="form-text text-muted">
                    The keyword that will trigger this autoresponder
                </small>
            </div>
        </div>
        
        <!-- Schedule Fields -->
        <div class="trigger-fields schedule-fields" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Schedule Type</label>
                        <select class="form-control" name="triggers[][schedule_type]">
                            <option value="once">One Time</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Schedule Time</label>
                        <input type="datetime-local" class="form-control" name="triggers[][schedule_time]">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Event Fields -->
        <div class="trigger-fields event-fields" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Event Type</label>
                        <select class="form-control" name="triggers[][event_type]">
                            <option value="user_registration">User Registration</option>
                            <option value="order_placed">Order Placed</option>
                            <option value="payment_received">Payment Received</option>
                            <option value="custom">Custom Event</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Event Value</label>
                        <input type="text" class="form-control" name="triggers[][event_value]" 
                               placeholder="Event specific value">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Conditions (Optional)</label>
            <textarea class="form-control" name="triggers[][conditions]" rows="2" 
                      placeholder="Additional conditions for this trigger (JSON format)"></textarea>
            <small class="form-text text-muted">
                Advanced conditions in JSON format (e.g., {"time_range": "09:00-17:00"})
            </small>
        </div>
    </div>
</div>

<!-- Test Autoresponder Modal -->
<div class="modal fade" id="testAutoresponderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Autoresponder</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="test-form">
                    <div class="form-group">
                        <label for="test-trigger-type">Test Trigger Type</label>
                        <select class="form-control" id="test-trigger-type" onchange="updateTestFields()">
                            <option value="keyword">Keyword Trigger</option>
                            <option value="schedule">Schedule Trigger</option>
                            <option value="event">Event Trigger</option>
                        </select>
                    </div>
                    
                    <!-- Keyword Test Fields -->
                    <div id="test-keyword-fields">
                        <div class="form-group">
                            <label for="test-keyword">Test Keyword</label>
                            <input type="text" class="form-control" id="test-keyword" 
                                   placeholder="Enter keyword to test">
                        </div>
                        <div class="form-group">
                            <label for="test-phone">From Phone Number</label>
                            <input type="tel" class="form-control" id="test-phone" 
                                   placeholder="+1234567890">
                        </div>
                        <div class="form-group">
                            <label for="test-message">Full Message</label>
                            <textarea class="form-control" id="test-message" rows="3" 
                                      placeholder="Full SMS message containing the keyword"></textarea>
                        </div>
                    </div>
                    
                    <!-- Schedule Test Fields -->
                    <div id="test-schedule-fields" style="display: none;">
                        <div class="form-group">
                            <label for="test-schedule-time">Test Schedule Time</label>
                            <input type="datetime-local" class="form-control" id="test-schedule-time">
                        </div>
                    </div>
                    
                    <!-- Event Test Fields -->
                    <div id="test-event-fields" style="display: none;">
                        <div class="form-group">
                            <label for="test-event-type">Event Type</label>
                            <input type="text" class="form-control" id="test-event-type" 
                                   placeholder="user_registration">
                        </div>
                        <div class="form-group">
                            <label for="test-event-data">Event Data (JSON)</label>
                            <textarea class="form-control" id="test-event-data" rows="3" 
                                      placeholder='{"user_id": 123, "email": "user@example.com"}'></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="test-variables">Variable Values (JSON)</label>
                        <textarea class="form-control" id="test-variables" rows="4" 
                                  placeholder='{"name": "John Doe", "keyword": "HELP"}'></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Response Preview</label>
                        <div class="alert alert-info" id="test-preview">
                            Response preview will appear here...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="runAutoresponderTest()">Run Test</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Autoresponder Form Styles */
.sms-preview {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
}

.sms-bubble {
    background: #28a745;
    color: white;
    padding: 12px 16px;
    border-radius: 18px;
    border-bottom-left-radius: 4px;
    max-width: 250px;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.4;
}

.variable-buttons .btn {
    margin-right: 5px;
}

.trigger-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6 !important;
}

.trigger-item:hover {
    background: #e9ecef;
}

.trigger-fields {
    margin-top: 15px;
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
    margin-bottom: 15px;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script>
// Autoresponder Form JavaScript

let triggerIndex = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form
    updateCharCount();
    updatePreview();
    initializeTriggers();
    
    // Event listeners
    $('#response_message').on('input', function() {
        updateCharCount();
        updatePreview();
        autoDetectVariables();
    });
    
    $('#test-variables').on('input', function() {
        updateTestPreview();
    });
    
    // Form validation
    $('#autoresponder-form').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
});

// Initialize Triggers
function initializeTriggers() {
    const container = $('#triggers-container');
    const existingTriggers = container.find('.trigger-item');
    
    if (existingTriggers.length === 0) {
        addTrigger();
    } else {
        existingTriggers.each(function(index) {
            const triggerItem = $(this);
            triggerItem.attr('data-index', index);
            triggerItem.find('.trigger-number').text(index + 1);
            
            // Initialize trigger fields based on existing data
            const triggerType = triggerItem.find('.trigger-type').val() || 'keyword';
            updateTriggerFields(triggerItem.find('.trigger-type')[0]);
        });
        triggerIndex = existingTriggers.length;
    }
    
    updateTriggerSummary();
}

// Add Trigger
function addTrigger() {
    const template = $('#trigger-template').html();
    const triggerHtml = template.replace(/data-index=""/g, `data-index="${triggerIndex}"`);
    const triggerElement = $(triggerHtml);
    
    triggerElement.find('.trigger-number').text(triggerIndex + 1);
    
    $('#triggers-container').append(triggerElement);
    
    // Initialize the new trigger
    updateTriggerFields(triggerElement.find('.trigger-type')[0]);
    
    triggerIndex++;
    updateTriggerSummary();
}

// Remove Trigger
function removeTrigger(button) {
    if ($('.trigger-item').length <= 1) {
        showAlert('warning', 'At least one trigger is required.');
        return;
    }
    
    $(button).closest('.trigger-item').remove();
    
    // Renumber triggers
    $('.trigger-item').each(function(index) {
        $(this).attr('data-index', index);
        $(this).find('.trigger-number').text(index + 1);
    });
    
    triggerIndex = $('.trigger-item').length;
    updateTriggerSummary();
}

// Update Trigger Fields
function updateTriggerFields(selectElement) {
    const triggerItem = $(selectElement).closest('.trigger-item');
    const triggerType = $(selectElement).val();
    
    // Hide all trigger fields
    triggerItem.find('.trigger-fields').hide();
    
    // Show relevant fields
    triggerItem.find(`.${triggerType}-fields`).show();
    
    updateTriggerSummary();
}

// Update Trigger Summary
function updateTriggerSummary() {
    const triggers = [];
    
    $('.trigger-item').each(function() {
        const triggerType = $(this).find('.trigger-type').val();
        let triggerText = '';
        
        if (triggerType === 'keyword') {
            const keyword = $(this).find('input[name*="[keyword]"]').val();
            triggerText = keyword ? `Keyword: "${keyword}"` : 'Keyword: (not set)';
        } else if (triggerType === 'schedule') {
            const scheduleType = $(this).find('select[name*="[schedule_type]"]').val();
            const scheduleTime = $(this).find('input[name*="[schedule_time]"]').val();
            triggerText = `Schedule: ${scheduleType}${scheduleTime ? ` at ${new Date(scheduleTime).toLocaleString()}` : ''}`;
        } else if (triggerType === 'event') {
            const eventType = $(this).find('select[name*="[event_type]"]').val();
            triggerText = `Event: ${eventType}`;
        }
        
        triggers.push(triggerText);
    });
    
    if (triggers.length > 0) {
        const summaryHtml = triggers.map((trigger, index) => 
            `<div class="badge badge-primary mr-1 mb-1">${index + 1}. ${trigger}</div>`
        ).join('');
        $('#trigger-summary').html(summaryHtml);
    } else {
        $('#trigger-summary').html('<p class="text-muted">No triggers configured</p>');
    }
}

// Update Character Count
function updateCharCount() {
    const content = $('#response_message').val();
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
    const content = $('#response_message').val() || 'Enter response message to see preview...';
    $('#preview-content').text(content);
}

// Auto-detect Variables
function autoDetectVariables() {
    const content = $('#response_message').val();
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

// Load Template
function loadTemplate() {
    const templateSelect = $('#template_id');
    const selectedOption = templateSelect.find('option:selected');
    
    if (selectedOption.val()) {
        const content = selectedOption.data('content');
        const variables = selectedOption.data('variables');
        
        $('#response_message').val(content);
        if (variables) {
            $('#variables').val(variables.replace(/,/g, ', '));
        }
        
        updateCharCount();
        updatePreview();
    }
}

// Insert Variable
function insertVariable(variable) {
    const textarea = document.getElementById('response_message');
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

// Preview Autoresponder
function previewAutoresponder() {
    const name = $('#name').val();
    const responseMessage = $('#response_message').val();
    
    if (!responseMessage) {
        showAlert('error', 'Please enter a response message first.');
        return;
    }
    
    // Collect trigger information
    const triggers = [];
    $('.trigger-item').each(function() {
        const triggerType = $(this).find('.trigger-type').val();
        let triggerInfo = { type: triggerType };
        
        if (triggerType === 'keyword') {
            triggerInfo.keyword = $(this).find('input[name*="[keyword]"]').val();
        } else if (triggerType === 'schedule') {
            triggerInfo.scheduleType = $(this).find('select[name*="[schedule_type]"]').val();
            triggerInfo.scheduleTime = $(this).find('input[name*="[schedule_time]"]').val();
        } else if (triggerType === 'event') {
            triggerInfo.eventType = $(this).find('select[name*="[event_type]"]').val();
        }
        
        triggers.push(triggerInfo);
    });
    
    // Open preview in new window
    const previewWindow = window.open('', '_blank', 'width=500,height=700');
    
    let triggersHtml = triggers.map((trigger, index) => {
        let triggerText = '';
        if (trigger.type === 'keyword') {
            triggerText = `Keyword: "${trigger.keyword || 'Not set'}"`;
        } else if (trigger.type === 'schedule') {
            triggerText = `Schedule: ${trigger.scheduleType}${trigger.scheduleTime ? ` at ${new Date(trigger.scheduleTime).toLocaleString()}` : ''}`;
        } else if (trigger.type === 'event') {
            triggerText = `Event: ${trigger.eventType}`;
        }
        return `<li>${triggerText}</li>`;
    }).join('');
    
    previewWindow.document.write(`
        <html>
            <head>
                <title>Autoresponder Preview</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .sms-bubble { background: #28a745; color: white; padding: 12px 16px; border-radius: 18px; border-bottom-left-radius: 4px; max-width: 300px; word-wrap: break-word; margin: 20px 0; }
                    .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                    ul { padding-left: 20px; }
                </style>
            </head>
            <body>
                <h3>Autoresponder Preview: ${name || 'Untitled'}</h3>
                
                <div class="info-box">
                    <h4>Triggers:</h4>
                    <ul>${triggersHtml}</ul>
                </div>
                
                <h4>Response Message:</h4>
                <div class="sms-bubble">${responseMessage}</div>
                
                <p><small>Character count: ${responseMessage.length}/160</small></p>
                
                <div class="info-box">
                    <h5>Settings:</h5>
                    <p><strong>Status:</strong> ${$('#is_active').val() == '1' ? 'Active' : 'Inactive'}</p>
                    <p><strong>Response Delay:</strong> ${$('#response_delay').val() || 0} seconds</p>
                    <p><strong>Max Triggers/Day:</strong> ${$('#max_triggers_per_day').val() || 'Unlimited'}</p>
                    <p><strong>Cooldown:</strong> ${$('#cooldown_minutes').val() || 0} minutes</p>
                </div>
            </body>
        </html>
    `);
}

// Test Autoresponder
function testAutoresponder() {
    const responseMessage = $('#response_message').val();
    if (!responseMessage) {
        showAlert('error', 'Please enter a response message first.');
        return;
    }
    
    // Pre-fill test form with autoresponder data
    const firstTrigger = $('.trigger-item').first();
    const triggerType = firstTrigger.find('.trigger-type').val();
    
    $('#test-trigger-type').val(triggerType);
    updateTestFields();
    
    if (triggerType === 'keyword') {
        const keyword = firstTrigger.find('input[name*="[keyword]"]').val();
        $('#test-keyword').val(keyword);
    }
    
    // Pre-fill variables
    const variables = $('#variables').val().split(',').map(v => v.trim()).filter(v => v);
    if (variables.length > 0) {
        const sampleVariables = {};
        variables.forEach(variable => {
            if (variable === 'keyword') {
                sampleVariables[variable] = $('#test-keyword').val() || 'HELP';
            } else if (variable === 'time') {
                sampleVariables[variable] = new Date().toLocaleTimeString();
            } else if (variable === 'date') {
                sampleVariables[variable] = new Date().toLocaleDateString();
            } else {
                sampleVariables[variable] = `[${variable}]`;
            }
        });
        $('#test-variables').val(JSON.stringify(sampleVariables, null, 2));
    }
    
    updateTestPreview();
    $('#testAutoresponderModal').modal('show');
}

// Update Test Fields
function updateTestFields() {
    const testType = $('#test-trigger-type').val();
    
    // Hide all test fields
    $('#test-keyword-fields, #test-schedule-fields, #test-event-fields').hide();
    
    // Show relevant fields
    $(`#test-${testType}-fields`).show();
}

// Update Test Preview
function updateTestPreview() {
    let content = $('#response_message').val();
    
    try {
        const variables = JSON.parse($('#test-variables').val() || '{}');
        
        Object.keys(variables).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            content = content.replace(regex, variables[key]);
        });
    } catch (e) {
        // Invalid JSON, show original content
    }
    
    $('#test-preview').text(content || 'Response preview will appear here...');
}

// Run Autoresponder Test
function runAutoresponderTest() {
    const testType = $('#test-trigger-type').val();
    let testData = {};
    
    if (testType === 'keyword') {
        const keyword = $('#test-keyword').val();
        const phone = $('#test-phone').val();
        const message = $('#test-message').val();
        
        if (!keyword) {
            showAlert('error', 'Please enter a keyword to test.');
            return;
        }
        
        testData = {
            type: 'keyword',
            keyword: keyword,
            from: phone,
            message: message || keyword
        };
    } else if (testType === 'schedule') {
        const scheduleTime = $('#test-schedule-time').val();
        
        if (!scheduleTime) {
            showAlert('error', 'Please enter a schedule time.');
            return;
        }
        
        testData = {
            type: 'schedule',
            schedule_time: scheduleTime
        };
    } else if (testType === 'event') {
        const eventType = $('#test-event-type').val();
        const eventData = $('#test-event-data').val();
        
        if (!eventType) {
            showAlert('error', 'Please enter an event type.');
            return;
        }
        
        testData = {
            type: 'event',
            event_type: eventType,
            event_data: eventData
        };
    }
    
    // Add variables
    try {
        testData.variables = JSON.parse($('#test-variables').val() || '{}');
    } catch (e) {
        testData.variables = {};
    }
    
    // Add response message
    testData.response_message = $('#response_message').val();
    
    showLoading('#test-preview');
    
    // Simulate test (in real implementation, this would call the backend)
    setTimeout(() => {
        let processedMessage = testData.response_message;
        
        // Process variables
        Object.keys(testData.variables).forEach(key => {
            const regex = new RegExp(`{{\\s*${key}\\s*}}`, 'g');
            processedMessage = processedMessage.replace(regex, testData.variables[key]);
        });
        
        $('#test-preview').html(`
            <div class="alert alert-success">
                <h6><i class="fas fa-check-circle"></i> Test Successful</h6>
                <p><strong>Processed Message:</strong></p>
                <div class="bg-light p-2 rounded">${processedMessage}</div>
                <p class="mt-2 mb-0"><small>Test completed successfully. The autoresponder would send this message.</small></p>
            </div>
        `);
        
        hideLoading('#test-preview');
    }, 1000);
}

// Duplicate Autoresponder
function duplicateAutoresponder() {
    if (!confirm('Duplicate this autoresponder?')) return;
    
    // In real implementation, this would submit to backend
    showAlert('info', 'Duplicate functionality will be implemented.');
}

// View Logs
function viewLogs() {
    // In real implementation, this would open logs modal
    showAlert('info', 'View logs functionality will be implemented.');
}

// Save Draft
function saveDraft() {
    const form = $('#autoresponder-form');
    
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
    
    $('#autoresponder-form')[0].reset();
    
    // Reset triggers
    $('#triggers-container').empty();
    triggerIndex = 0;
    addTrigger();
    
    updateCharCount();
    updatePreview();
    updateTriggerSummary();
    
    showAlert('info', 'Form reset successfully.');
}

// Validate Form
function validateForm() {
    const name = $('#name').val().trim();
    const responseMessage = $('#response_message').val().trim();
    
    if (!name) {
        showAlert('error', 'Autoresponder name is required.');
        $('#name').focus();
        return false;
    }
    
    if (!responseMessage) {
        showAlert('error', 'Response message is required.');
        $('#response_message').focus();
        return false;
    }
    
    // Validate triggers
    let hasValidTrigger = false;
    $('.trigger-item').each(function() {
        const triggerType = $(this).find('.trigger-type').val();
        
        if (triggerType === 'keyword') {
            const keyword = $(this).find('input[name*="[keyword]"]').val().trim();
            if (keyword) {
                hasValidTrigger = true;
            }
        } else if (triggerType === 'schedule') {
            const scheduleTime = $(this).find('input[name*="[schedule_time]"]').val();
            if (scheduleTime) {
                hasValidTrigger = true;
            }
        } else if (triggerType === 'event') {
            const eventType = $(this).find('select[name*="[event_type]"]').val();
            if (eventType) {
                hasValidTrigger = true;
            }
        }
    });
    
    if (!hasValidTrigger) {
        showAlert('error', 'At least one valid trigger is required.');
        return false;
    }
    
    return true;
}

// Event listeners for trigger updates
$(document).on('input change', '.trigger-item input, .trigger-item select', function() {
    updateTriggerSummary();
});
</script>
@endpush