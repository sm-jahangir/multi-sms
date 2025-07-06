{{--
/**
 * SMS Form Component
 * 
 * Comprehensive form component with validation, character counting, and SMS-specific features
 * 
 * @param string $action - Form action URL
 * @param string $method - Form method (GET, POST, PUT, PATCH, DELETE)
 * @param string $id - Form ID
 * @param string $class - Additional CSS classes
 * @param bool $multipart - Whether form accepts file uploads
 * @param bool $ajax - Whether form should be submitted via AJAX
 * @param string $successCallback - JavaScript function to call on success
 * @param string $errorCallback - JavaScript function to call on error
 * @param bool $autoSave - Whether to auto-save form data
 * @param string $autoSaveKey - Key for auto-save storage
 */
--}}

@props([
    'action' => '',
    'method' => 'POST',
    'id' => 'sms-form-' . uniqid(),
    'class' => '',
    'multipart' => false,
    'ajax' => false,
    'successCallback' => '',
    'errorCallback' => '',
    'autoSave' => false,
    'autoSaveKey' => ''
])

@php
    $formMethod = strtoupper($method);
    $actualMethod = in_array($formMethod, ['GET', 'POST']) ? $formMethod : 'POST';
    $needsMethodField = !in_array($formMethod, ['GET', 'POST']);
    $formClasses = 'sms-form ' . $class;
    
    if ($ajax) {
        $formClasses .= ' sms-form--ajax';
    }
    
    if ($autoSave) {
        $formClasses .= ' sms-form--autosave';
    }
@endphp

<form action="{{ $action }}"
      method="{{ $actualMethod }}"
      id="{{ $id }}"
      class="{{ $formClasses }}"
      @if($multipart) enctype="multipart/form-data" @endif
      @if($ajax) data-ajax="true" @endif
      @if($successCallback) data-success-callback="{{ $successCallback }}" @endif
      @if($errorCallback) data-error-callback="{{ $errorCallback }}" @endif
      @if($autoSave) data-autosave="true" data-autosave-key="{{ $autoSaveKey ?: $id }}" @endif
      novalidate>
    
    @if($actualMethod === 'POST')
        @csrf
    @endif
    
    @if($needsMethodField)
        @method($formMethod)
    @endif
    
    {{-- Form Loading Overlay --}}
    <div class="sms-form__loading" style="display: none;">
        <div class="sms-form__spinner">
            <svg class="sms-form__spinner-icon" viewBox="0 0 24 24">
                <circle class="sms-form__spinner-path" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
                </circle>
            </svg>
            <span>Processing...</span>
        </div>
    </div>
    
    {{-- Form Content --}}
    <div class="sms-form__content">
        {{ $slot }}
    </div>
</form>

{{-- Form Field Components --}}

{{-- Text Input Field --}}
@php
function renderTextField($name, $label, $value = '', $options = []) {
    $id = $options['id'] ?? 'field-' . $name;
    $type = $options['type'] ?? 'text';
    $placeholder = $options['placeholder'] ?? '';
    $required = $options['required'] ?? false;
    $disabled = $options['disabled'] ?? false;
    $readonly = $options['readonly'] ?? false;
    $maxlength = $options['maxlength'] ?? null;
    $pattern = $options['pattern'] ?? null;
    $autocomplete = $options['autocomplete'] ?? null;
    $help = $options['help'] ?? '';
    $error = $options['error'] ?? '';
    $class = $options['class'] ?? '';
    $attributes = $options['attributes'] ?? [];
    
    $fieldClasses = 'sms-form__field';
    $inputClasses = 'sms-form__input ' . $class;
    
    if ($error) {
        $fieldClasses .= ' sms-form__field--error';
        $inputClasses .= ' sms-form__input--error';
    }
    
    if ($required) {
        $fieldClasses .= ' sms-form__field--required';
    }
    
    echo '<div class="' . $fieldClasses . '">';
    
    if ($label) {
        echo '<label for="' . $id . '" class="sms-form__label">';
        echo htmlspecialchars($label);
        if ($required) echo '<span class="sms-form__required">*</span>';
        echo '</label>';
    }
    
    echo '<input type="' . $type . '"';
    echo ' id="' . $id . '"';
    echo ' name="' . $name . '"';
    echo ' class="' . $inputClasses . '"';
    echo ' value="' . htmlspecialchars($value) . '"';
    
    if ($placeholder) echo ' placeholder="' . htmlspecialchars($placeholder) . '"';
    if ($required) echo ' required';
    if ($disabled) echo ' disabled';
    if ($readonly) echo ' readonly';
    if ($maxlength) echo ' maxlength="' . $maxlength . '"';
    if ($pattern) echo ' pattern="' . htmlspecialchars($pattern) . '"';
    if ($autocomplete) echo ' autocomplete="' . $autocomplete . '"';
    
    foreach ($attributes as $attr => $val) {
        echo ' ' . $attr . '="' . htmlspecialchars($val) . '"';
    }
    
    echo '>';
    
    if ($help) {
        echo '<div class="sms-form__help">' . htmlspecialchars($help) . '</div>';
    }
    
    if ($error) {
        echo '<div class="sms-form__error">' . htmlspecialchars($error) . '</div>';
    }
    
    echo '</div>';
}
@endphp

{{-- Component Styles --}}
<style>
/* Form Base Styles */
.sms-form {
    position: relative;
    width: 100%;
}

.sms-form__content {
    position: relative;
    z-index: 1;
}

/* Form Loading */
.sms-form__loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: var(--border-radius, 6px);
}

.sms-form__spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--primary-color, #3b82f6);
}

.sms-form__spinner-icon {
    width: 2rem;
    height: 2rem;
    animation: spin 1s linear infinite;
}

.sms-form__spinner span {
    font-size: 0.875rem;
    font-weight: 500;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Form Fields */
.sms-form__field {
    margin-bottom: 1.5rem;
}

.sms-form__field:last-child {
    margin-bottom: 0;
}

.sms-form__label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary, #111827);
    line-height: 1.5;
}

.sms-form__required {
    color: var(--danger-color, #ef4444);
    margin-left: 0.25rem;
}

/* Form Inputs */
.sms-form__input,
.sms-form__textarea,
.sms-form__select {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--text-primary, #111827);
    background-color: var(--input-bg, #ffffff);
    background-image: none;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius, 6px);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.sms-form__input:focus,
.sms-form__textarea:focus,
.sms-form__select:focus {
    outline: 0;
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sms-form__input::placeholder,
.sms-form__textarea::placeholder {
    color: var(--text-muted, #9ca3af);
    opacity: 1;
}

.sms-form__input:disabled,
.sms-form__textarea:disabled,
.sms-form__select:disabled {
    background-color: var(--input-disabled-bg, #f9fafb);
    border-color: var(--input-disabled-border, #e5e7eb);
    color: var(--text-muted, #9ca3af);
    cursor: not-allowed;
}

/* Textarea Specific */
.sms-form__textarea {
    resize: vertical;
    min-height: 100px;
}

.sms-form__textarea--sms {
    font-family: 'Courier New', monospace;
    line-height: 1.4;
}

/* Select Specific */
.sms-form__select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    appearance: none;
}

/* Checkbox and Radio */
.sms-form__checkbox,
.sms-form__radio {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.sms-form__checkbox:last-child,
.sms-form__radio:last-child {
    margin-bottom: 0;
}

.sms-form__checkbox input,
.sms-form__radio input {
    width: 1rem;
    height: 1rem;
    margin: 0;
    accent-color: var(--primary-color, #3b82f6);
}

.sms-form__checkbox label,
.sms-form__radio label {
    margin: 0;
    font-weight: 400;
    cursor: pointer;
}

/* Error States */
.sms-form__field--error .sms-form__input,
.sms-form__field--error .sms-form__textarea,
.sms-form__field--error .sms-form__select {
    border-color: var(--danger-color, #ef4444);
}

.sms-form__field--error .sms-form__input:focus,
.sms-form__field--error .sms-form__textarea:focus,
.sms-form__field--error .sms-form__select:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.sms-form__error {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--danger-color, #ef4444);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.sms-form__error::before {
    content: '⚠';
    font-size: 0.875rem;
}

/* Help Text */
.sms-form__help {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
    line-height: 1.4;
}

/* Form Groups */
.sms-form__group {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}

.sms-form__group .sms-form__field {
    flex: 1;
    margin-bottom: 0;
}

/* Character Counter */
.sms-form__counter {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
}

.sms-form__counter--warning {
    color: var(--warning-color, #f59e0b);
}

.sms-form__counter--danger {
    color: var(--danger-color, #ef4444);
}

.sms-form__sms-count {
    font-weight: 500;
}

/* Variable Tags */
.sms-form__variables {
    margin-top: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.sms-form__variable {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background-color: var(--primary-color, #3b82f6);
    color: white;
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.sms-form__variable:hover {
    background-color: var(--primary-dark, #2563eb);
}

/* Form Actions */
.sms-form__actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
}

/* Dark Mode */
[data-theme="dark"] .sms-form__loading {
    background: rgba(55, 65, 81, 0.9);
}

[data-theme="dark"] .sms-form__label {
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-form__input,
[data-theme="dark"] .sms-form__textarea,
[data-theme="dark"] .sms-form__select {
    background-color: var(--dark-input-bg, #374151);
    border-color: var(--dark-input-border, #4b5563);
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-form__input:disabled,
[data-theme="dark"] .sms-form__textarea:disabled,
[data-theme="dark"] .sms-form__select:disabled {
    background-color: var(--dark-input-disabled-bg, #4b5563);
    border-color: var(--dark-input-disabled-border, #6b7280);
}

[data-theme="dark"] .sms-form__help {
    color: var(--dark-text-secondary, #d1d5db);
}

[data-theme="dark"] .sms-form__counter {
    color: var(--dark-text-secondary, #d1d5db);
}

[data-theme="dark"] .sms-form__actions {
    border-color: var(--dark-border-color, #4b5563);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-form__group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sms-form__group .sms-form__field {
        margin-bottom: 1.5rem;
    }
    
    .sms-form__group .sms-form__field:last-child {
        margin-bottom: 0;
    }
    
    .sms-form__actions {
        flex-direction: column;
    }
    
    .sms-form__actions .sms-btn {
        width: 100%;
    }
}
</style>

{{-- Form JavaScript --}}
<script>
class SmsFormComponent {
    constructor(formElement) {
        this.form = formElement;
        this.isAjax = this.form.dataset.ajax === 'true';
        this.autoSave = this.form.dataset.autosave === 'true';
        this.autoSaveKey = this.form.dataset.autosaveKey;
        this.successCallback = this.form.dataset.successCallback;
        this.errorCallback = this.form.dataset.errorCallback;
        
        this.init();
    }
    
    init() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Character counting for textareas
        this.initCharacterCounters();
        
        // Variable detection
        this.initVariableDetection();
        
        // Auto-save
        if (this.autoSave) {
            this.initAutoSave();
        }
        
        // Real-time validation
        this.initValidation();
    }
    
    handleSubmit(e) {
        if (this.isAjax) {
            e.preventDefault();
            this.submitAjax();
        }
    }
    
    async submitAjax() {
        const formData = new FormData(this.form);
        const loadingOverlay = this.form.querySelector('.sms-form__loading');
        
        try {
            this.showLoading();
            
            const response = await fetch(this.form.action, {
                method: this.form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                this.handleSuccess(result);
            } else {
                this.handleError(result);
            }
        } catch (error) {
            this.handleError({ message: 'Network error occurred' });
        } finally {
            this.hideLoading();
        }
    }
    
    handleSuccess(result) {
        // Clear form errors
        this.clearErrors();
        
        // Show success message
        if (result.message) {
            this.showAlert(result.message, 'success');
        }
        
        // Call custom callback
        if (this.successCallback && window[this.successCallback]) {
            window[this.successCallback](result);
        }
        
        // Clear auto-save data
        if (this.autoSave) {
            localStorage.removeItem(`sms_form_${this.autoSaveKey}`);
        }
    }
    
    handleError(result) {
        // Show validation errors
        if (result.errors) {
            this.showErrors(result.errors);
        }
        
        // Show general error message
        if (result.message) {
            this.showAlert(result.message, 'error');
        }
        
        // Call custom callback
        if (this.errorCallback && window[this.errorCallback]) {
            window[this.errorCallback](result);
        }
    }
    
    showErrors(errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const fieldContainer = field.closest('.sms-form__field');
                if (fieldContainer) {
                    fieldContainer.classList.add('sms-form__field--error');
                    
                    // Remove existing error message
                    const existingError = fieldContainer.querySelector('.sms-form__error');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Add new error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'sms-form__error';
                    errorDiv.textContent = errors[fieldName][0];
                    field.parentNode.appendChild(errorDiv);
                }
            }
        });
    }
    
    clearErrors() {
        const errorFields = this.form.querySelectorAll('.sms-form__field--error');
        errorFields.forEach(field => {
            field.classList.remove('sms-form__field--error');
            const errorMsg = field.querySelector('.sms-form__error');
            if (errorMsg) {
                errorMsg.remove();
            }
        });
    }
    
    initCharacterCounters() {
        const textareas = this.form.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            if (maxLength) {
                this.addCharacterCounter(textarea, parseInt(maxLength));
            }
        });
    }
    
    addCharacterCounter(textarea, maxLength) {
        const counter = document.createElement('div');
        counter.className = 'sms-form__counter';
        
        const updateCounter = () => {
            const length = textarea.value.length;
            const remaining = maxLength - length;
            const smsCount = Math.ceil(length / 160) || 1;
            
            counter.innerHTML = `
                <span>Characters: ${length}/${maxLength}</span>
                <span class="sms-form__sms-count">SMS: ${smsCount}</span>
            `;
            
            // Update counter color based on remaining characters
            counter.className = 'sms-form__counter';
            if (remaining < 50) {
                counter.classList.add('sms-form__counter--warning');
            }
            if (remaining < 20) {
                counter.classList.add('sms-form__counter--danger');
            }
        };
        
        textarea.addEventListener('input', updateCounter);
        textarea.parentNode.appendChild(counter);
        updateCounter();
    }
    
    initVariableDetection() {
        const textareas = this.form.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            const updateVariables = () => {
                const content = textarea.value;
                const variables = this.extractVariables(content);
                this.showVariables(textarea, variables);
            };
            
            textarea.addEventListener('input', updateVariables);
            updateVariables();
        });
    }
    
    extractVariables(content) {
        const regex = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/g;
        const variables = [];
        let match;
        
        while ((match = regex.exec(content)) !== null) {
            if (!variables.includes(match[1])) {
                variables.push(match[1]);
            }
        }
        
        return variables;
    }
    
    showVariables(textarea, variables) {
        let variablesContainer = textarea.parentNode.querySelector('.sms-form__variables');
        
        if (variables.length === 0) {
            if (variablesContainer) {
                variablesContainer.remove();
            }
            return;
        }
        
        if (!variablesContainer) {
            variablesContainer = document.createElement('div');
            variablesContainer.className = 'sms-form__variables';
            textarea.parentNode.appendChild(variablesContainer);
        }
        
        variablesContainer.innerHTML = variables.map(variable => 
            `<span class="sms-form__variable" onclick="insertVariable('${textarea.id}', '${variable}')">
                {{${variable}}}
            </span>`
        ).join('');
    }
    
    initAutoSave() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        const saveData = () => {
            const formData = new FormData(this.form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(`sms_form_${this.autoSaveKey}`, JSON.stringify(data));
        };
        
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(this.autoSaveTimeout);
                this.autoSaveTimeout = setTimeout(saveData, 1000);
            });
        });
        
        // Load saved data
        this.loadAutoSaveData();
    }
    
    loadAutoSaveData() {
        const savedData = localStorage.getItem(`sms_form_${this.autoSaveKey}`);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(name => {
                    const field = this.form.querySelector(`[name="${name}"]`);
                    if (field) {
                        field.value = data[name];
                        // Trigger input event to update counters
                        field.dispatchEvent(new Event('input'));
                    }
                });
            } catch (error) {
                console.error('Failed to load auto-save data:', error);
            }
        }
    }
    
    initValidation() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => {
                // Clear error state on input
                const fieldContainer = input.closest('.sms-form__field');
                if (fieldContainer && fieldContainer.classList.contains('sms-form__field--error')) {
                    fieldContainer.classList.remove('sms-form__field--error');
                    const errorMsg = fieldContainer.querySelector('.sms-form__error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        });
    }
    
    validateField(field) {
        const fieldContainer = field.closest('.sms-form__field');
        if (!fieldContainer) return;
        
        let isValid = true;
        let errorMessage = '';
        
        // Required validation
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        if (field.type === 'email' && field.value && !this.isValidEmail(field.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
        
        // Phone validation
        if (field.type === 'tel' && field.value && !this.isValidPhone(field.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
        
        // Update field state
        if (!isValid) {
            fieldContainer.classList.add('sms-form__field--error');
            
            let errorDiv = fieldContainer.querySelector('.sms-form__error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'sms-form__error';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = errorMessage;
        }
        
        return isValid;
    }
    
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    isValidPhone(phone) {
        const regex = /^[\+]?[1-9][\d\s\-\(\)]{7,}$/;
        return regex.test(phone.replace(/\s/g, ''));
    }
    
    showLoading() {
        const loading = this.form.querySelector('.sms-form__loading');
        if (loading) {
            loading.style.display = 'flex';
        }
    }
    
    hideLoading() {
        const loading = this.form.querySelector('.sms-form__loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }
    
    showAlert(message, type = 'info') {
        // This should integrate with your notification system
        if (window.SmsUtils && window.SmsUtils.showAlert) {
            window.SmsUtils.showAlert(message, type);
        } else {
            alert(message);
        }
    }
}

// Auto-initialize forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.sms-form');
    forms.forEach(form => {
        new SmsFormComponent(form);
    });
});

// Helper function for variable insertion
window.insertVariable = function(textareaId, variable) {
    const textarea = document.getElementById(textareaId);
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        
        textarea.value = before + `{{${variable}}}` + after;
        textarea.focus();
        textarea.setSelectionRange(start + variable.length + 4, start + variable.length + 4);
        
        // Trigger input event to update counters
        textarea.dispatchEvent(new Event('input'));
    }
};
</script>

{{-- Usage Examples:

<!-- Basic Form -->
<x-sms-form action="{{ route('sms.templates.store') }}" method="POST">
    <div class="sms-form__field">
        <label for="name" class="sms-form__label">Template Name <span class="sms-form__required">*</span></label>
        <input type="text" id="name" name="name" class="sms-form__input" required>
    </div>
    
    <div class="sms-form__field">
        <label for="content" class="sms-form__label">Message Content</label>
        <textarea id="content" name="content" class="sms-form__textarea sms-form__textarea--sms" maxlength="1600" required></textarea>
    </div>
    
    <div class="sms-form__actions">
        <x-sms-button variant="secondary" href="{{ route('sms.templates.index') }}">Cancel</x-sms-button>
        <x-sms-button type="submit" variant="primary">Save Template</x-sms-button>
    </div>
</x-sms-form>

<!-- AJAX Form with Auto-save -->
<x-sms-form 
    action="{{ route('sms.templates.store') }}" 
    method="POST" 
    ajax="true" 
    autoSave="true" 
    autoSaveKey="template-form"
    successCallback="handleTemplateSuccess">
    
    <!-- Form fields here -->
</x-sms-form>

<!-- Form with File Upload -->
<x-sms-form action="{{ route('sms.campaigns.import') }}" method="POST" multipart="true">
    <div class="sms-form__field">
        <label for="csv_file" class="sms-form__label">CSV File</label>
        <input type="file" id="csv_file" name="csv_file" class="sms-form__input" accept=".csv" required>
        <div class="sms-form__help">Upload a CSV file with recipient data</div>
    </div>
</x-sms-form>

--}}