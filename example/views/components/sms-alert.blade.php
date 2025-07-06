{{--
/**
 * SMS Alert Component
 * 
 * Comprehensive alert/notification component with various types and features
 * 
 * @param string $type - Alert type (success, error, warning, info, primary, secondary)
 * @param string $title - Alert title
 * @param string $message - Alert message
 * @param bool $dismissible - Whether alert can be dismissed
 * @param bool $autoHide - Whether alert should auto-hide
 * @param int $autoHideDelay - Auto-hide delay in milliseconds
 * @param string $icon - Custom icon SVG
 * @param bool $showIcon - Whether to show icon
 * @param string $size - Alert size (sm, md, lg)
 * @param string $position - Alert position (top, bottom, top-left, top-right, bottom-left, bottom-right)
 * @param bool $fixed - Whether alert should be fixed positioned
 * @param string $class - Additional CSS classes
 * @param string $id - Alert ID
 */
--}}

@props([
    'type' => 'info',
    'title' => '',
    'message' => '',
    'dismissible' => true,
    'autoHide' => false,
    'autoHideDelay' => 5000,
    'icon' => null,
    'showIcon' => true,
    'size' => 'md',
    'position' => null,
    'fixed' => false,
    'class' => '',
    'id' => 'sms-alert-' . uniqid()
])

@php
    $alertClasses = 'sms-alert sms-alert--' . $type . ' sms-alert--' . $size;
    
    if ($dismissible) {
        $alertClasses .= ' sms-alert--dismissible';
    }
    
    if ($fixed) {
        $alertClasses .= ' sms-alert--fixed';
    }
    
    if ($position) {
        $alertClasses .= ' sms-alert--' . $position;
    }
    
    $alertClasses .= ' ' . $class;
    
    // Default icons for each type
    $defaultIcons = [
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'primary' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'secondary' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
    ];
    
    $iconSvg = $icon ?: ($defaultIcons[$type] ?? $defaultIcons['info']);
@endphp

<div class="{{ $alertClasses }}" 
     id="{{ $id }}"
     role="alert"
     aria-live="polite"
     data-alert="{{ $id }}"
     @if($autoHide) data-auto-hide="{{ $autoHideDelay }}" @endif
     style="{{ $fixed && $position ? '' : 'position: relative;' }}">
    
    {{-- Alert Content --}}
    <div class="sms-alert__content">
        @if($showIcon)
        <div class="sms-alert__icon">
            <svg class="sms-alert__icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                {!! $iconSvg !!}
            </svg>
        </div>
        @endif
        
        <div class="sms-alert__body">
            @if($title)
            <div class="sms-alert__title">{{ $title }}</div>
            @endif
            
            <div class="sms-alert__message">
                @if($message)
                    {{ $message }}
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    </div>
    
    {{-- Dismiss Button --}}
    @if($dismissible)
    <button type="button" 
            class="sms-alert__dismiss" 
            aria-label="Dismiss alert"
            onclick="dismissAlert('{{ $id }}')">
        <svg class="sms-alert__dismiss-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
    @endif
    
    {{-- Progress Bar for Auto-hide --}}
    @if($autoHide)
    <div class="sms-alert__progress">
        <div class="sms-alert__progress-bar" data-alert-progress="{{ $id }}"></div>
    </div>
    @endif
</div>

{{-- Component Styles --}}
<style>
/* Alert Base */
.sms-alert {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius, 6px);
    font-size: 0.875rem;
    line-height: 1.5;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.sms-alert:last-child {
    margin-bottom: 0;
}

/* Alert Content */
.sms-alert__content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.sms-alert__icon {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.sms-alert__icon-svg {
    width: 1.25rem;
    height: 1.25rem;
}

.sms-alert__body {
    flex: 1;
    min-width: 0;
}

.sms-alert__title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: inherit;
}

.sms-alert__message {
    color: inherit;
    opacity: 0.9;
}

.sms-alert__message p {
    margin: 0;
}

.sms-alert__message p + p {
    margin-top: 0.5rem;
}

/* Dismiss Button */
.sms-alert__dismiss {
    flex-shrink: 0;
    background: none;
    border: none;
    padding: 0.25rem;
    margin: -0.25rem -0.25rem -0.25rem 0.5rem;
    cursor: pointer;
    border-radius: var(--border-radius-sm, 4px);
    transition: all 0.2s ease;
    opacity: 0.7;
}

.sms-alert__dismiss:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.1);
}

.sms-alert__dismiss:focus {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.sms-alert__dismiss-icon {
    width: 1rem;
    height: 1rem;
}

/* Progress Bar */
.sms-alert__progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.sms-alert__progress-bar {
    height: 100%;
    background: currentColor;
    width: 100%;
    transform: translateX(-100%);
    transition: transform linear;
}

/* Alert Types */
.sms-alert--success {
    background: var(--success-bg, #f0fdf4);
    border-color: var(--success-border, #bbf7d0);
    color: var(--success-text, #166534);
}

.sms-alert--error {
    background: var(--error-bg, #fef2f2);
    border-color: var(--error-border, #fecaca);
    color: var(--error-text, #991b1b);
}

.sms-alert--warning {
    background: var(--warning-bg, #fffbeb);
    border-color: var(--warning-border, #fed7aa);
    color: var(--warning-text, #92400e);
}

.sms-alert--info {
    background: var(--info-bg, #eff6ff);
    border-color: var(--info-border, #bfdbfe);
    color: var(--info-text, #1e40af);
}

.sms-alert--primary {
    background: var(--primary-bg, #eff6ff);
    border-color: var(--primary-border, #bfdbfe);
    color: var(--primary-text, #1e40af);
}

.sms-alert--secondary {
    background: var(--secondary-bg, #f9fafb);
    border-color: var(--secondary-border, #e5e7eb);
    color: var(--secondary-text, #374151);
}

/* Alert Sizes */
.sms-alert--sm {
    padding: 0.75rem;
    font-size: 0.75rem;
}

.sms-alert--sm .sms-alert__icon-svg {
    width: 1rem;
    height: 1rem;
}

.sms-alert--sm .sms-alert__content {
    gap: 0.5rem;
}

.sms-alert--lg {
    padding: 1.5rem;
    font-size: 1rem;
}

.sms-alert--lg .sms-alert__icon-svg {
    width: 1.5rem;
    height: 1.5rem;
}

.sms-alert--lg .sms-alert__content {
    gap: 1rem;
}

/* Fixed Positioning */
.sms-alert--fixed {
    position: fixed;
    z-index: 9999;
    max-width: 400px;
    margin: 0;
    box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05));
}

.sms-alert--top {
    top: 1rem;
    left: 50%;
    transform: translateX(-50%);
}

.sms-alert--bottom {
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
}

.sms-alert--top-left {
    top: 1rem;
    left: 1rem;
}

.sms-alert--top-right {
    top: 1rem;
    right: 1rem;
}

.sms-alert--bottom-left {
    bottom: 1rem;
    left: 1rem;
}

.sms-alert--bottom-right {
    bottom: 1rem;
    right: 1rem;
}

/* Animation Classes */
.sms-alert--entering {
    animation: smsAlertSlideIn 0.3s ease-out;
}

.sms-alert--leaving {
    animation: smsAlertSlideOut 0.3s ease-in;
}

@keyframes smsAlertSlideIn {
    from {
        opacity: 0;
        transform: translateY(-1rem) translateX(-50%);
    }
    to {
        opacity: 1;
        transform: translateY(0) translateX(-50%);
    }
}

@keyframes smsAlertSlideOut {
    from {
        opacity: 1;
        transform: translateY(0) translateX(-50%);
    }
    to {
        opacity: 0;
        transform: translateY(-1rem) translateX(-50%);
    }
}

.sms-alert--top-left.sms-alert--entering,
.sms-alert--bottom-left.sms-alert--entering {
    animation: smsAlertSlideInLeft 0.3s ease-out;
}

.sms-alert--top-left.sms-alert--leaving,
.sms-alert--bottom-left.sms-alert--leaving {
    animation: smsAlertSlideOutLeft 0.3s ease-in;
}

@keyframes smsAlertSlideInLeft {
    from {
        opacity: 0;
        transform: translateX(-100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes smsAlertSlideOutLeft {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(-100%);
    }
}

.sms-alert--top-right.sms-alert--entering,
.sms-alert--bottom-right.sms-alert--entering {
    animation: smsAlertSlideInRight 0.3s ease-out;
}

.sms-alert--top-right.sms-alert--leaving,
.sms-alert--bottom-right.sms-alert--leaving {
    animation: smsAlertSlideOutRight 0.3s ease-in;
}

@keyframes smsAlertSlideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes smsAlertSlideOutRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* Dark Mode */
[data-theme="dark"] .sms-alert--success {
    background: var(--dark-success-bg, #064e3b);
    border-color: var(--dark-success-border, #065f46);
    color: var(--dark-success-text, #a7f3d0);
}

[data-theme="dark"] .sms-alert--error {
    background: var(--dark-error-bg, #7f1d1d);
    border-color: var(--dark-error-border, #991b1b);
    color: var(--dark-error-text, #fca5a5);
}

[data-theme="dark"] .sms-alert--warning {
    background: var(--dark-warning-bg, #78350f);
    border-color: var(--dark-warning-border, #92400e);
    color: var(--dark-warning-text, #fcd34d);
}

[data-theme="dark"] .sms-alert--info {
    background: var(--dark-info-bg, #1e3a8a);
    border-color: var(--dark-info-border, #1e40af);
    color: var(--dark-info-text, #93c5fd);
}

[data-theme="dark"] .sms-alert--primary {
    background: var(--dark-primary-bg, #1e3a8a);
    border-color: var(--dark-primary-border, #1e40af);
    color: var(--dark-primary-text, #93c5fd);
}

[data-theme="dark"] .sms-alert--secondary {
    background: var(--dark-secondary-bg, #374151);
    border-color: var(--dark-secondary-border, #4b5563);
    color: var(--dark-secondary-text, #d1d5db);
}

[data-theme="dark"] .sms-alert__dismiss:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-alert--fixed {
        left: 1rem;
        right: 1rem;
        max-width: none;
    }
    
    .sms-alert--top {
        transform: none;
    }
    
    .sms-alert--bottom {
        transform: none;
    }
    
    @keyframes smsAlertSlideIn {
        from {
            opacity: 0;
            transform: translateY(-1rem);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes smsAlertSlideOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-1rem);
        }
    }
}

/* Alert Container for Multiple Alerts */
.sms-alert-container {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
}

.sms-alert-container .sms-alert {
    pointer-events: auto;
    margin-bottom: 0.5rem;
}

.sms-alert-container .sms-alert:last-child {
    margin-bottom: 0;
}

.sms-alert-container--top {
    top: 1rem;
    left: 50%;
    transform: translateX(-50%);
}

.sms-alert-container--bottom {
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
}

.sms-alert-container--top-left {
    top: 1rem;
    left: 1rem;
}

.sms-alert-container--top-right {
    top: 1rem;
    right: 1rem;
}

.sms-alert-container--bottom-left {
    bottom: 1rem;
    left: 1rem;
}

.sms-alert-container--bottom-right {
    bottom: 1rem;
    right: 1rem;
}
</style>

{{-- Alert JavaScript --}}
<script>
// Alert management class
class SmsAlertManager {
    constructor() {
        this.alerts = new Map();
        this.containers = new Map();
        this.init();
    }
    
    init() {
        // Initialize existing alerts
        document.querySelectorAll('[data-alert]').forEach(alert => {
            this.initAlert(alert);
        });
    }
    
    initAlert(alertElement) {
        const id = alertElement.dataset.alert;
        const autoHide = alertElement.dataset.autoHide;
        
        this.alerts.set(id, alertElement);
        
        // Add entering animation
        if (alertElement.classList.contains('sms-alert--fixed')) {
            alertElement.classList.add('sms-alert--entering');
            setTimeout(() => {
                alertElement.classList.remove('sms-alert--entering');
            }, 300);
        }
        
        // Setup auto-hide
        if (autoHide) {
            this.setupAutoHide(id, parseInt(autoHide));
        }
    }
    
    setupAutoHide(alertId, delay) {
        const alert = this.alerts.get(alertId);
        if (!alert) return;
        
        const progressBar = alert.querySelector('[data-alert-progress]');
        
        if (progressBar) {
            progressBar.style.transitionDuration = delay + 'ms';
            setTimeout(() => {
                progressBar.style.transform = 'translateX(0)';
            }, 100);
        }
        
        setTimeout(() => {
            this.dismiss(alertId);
        }, delay);
    }
    
    dismiss(alertId, animate = true) {
        const alert = this.alerts.get(alertId);
        if (!alert) return;
        
        if (animate && alert.classList.contains('sms-alert--fixed')) {
            alert.classList.add('sms-alert--leaving');
            setTimeout(() => {
                this.removeAlert(alertId);
            }, 300);
        } else {
            this.removeAlert(alertId);
        }
    }
    
    removeAlert(alertId) {
        const alert = this.alerts.get(alertId);
        if (alert) {
            alert.remove();
            this.alerts.delete(alertId);
        }
    }
    
    show(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            dismissible = true,
            autoHide = true,
            autoHideDelay = 5000,
            position = 'top-right',
            size = 'md',
            icon = null
        } = options;
        
        const alertId = 'sms-alert-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        const alertHtml = this.createAlertHtml({
            id: alertId,
            type,
            title,
            message,
            dismissible,
            autoHide,
            autoHideDelay,
            position,
            size,
            icon
        });
        
        const container = this.getOrCreateContainer(position);
        container.insertAdjacentHTML('beforeend', alertHtml);
        
        const alertElement = document.getElementById(alertId);
        this.initAlert(alertElement);
        
        return alertId;
    }
    
    createAlertHtml(options) {
        const {
            id, type, title, message, dismissible, autoHide, autoHideDelay, position, size, icon
        } = options;
        
        const defaultIcons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />',
            info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        };
        
        const iconSvg = icon || defaultIcons[type] || defaultIcons.info;
        
        return `
            <div class="sms-alert sms-alert--${type} sms-alert--${size} sms-alert--fixed sms-alert--${position} ${dismissible ? 'sms-alert--dismissible' : ''}" 
                 id="${id}"
                 role="alert"
                 aria-live="polite"
                 data-alert="${id}"
                 ${autoHide ? `data-auto-hide="${autoHideDelay}"` : ''}>
                
                <div class="sms-alert__content">
                    <div class="sms-alert__icon">
                        <svg class="sms-alert__icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            ${iconSvg}
                        </svg>
                    </div>
                    
                    <div class="sms-alert__body">
                        ${title ? `<div class="sms-alert__title">${title}</div>` : ''}
                        <div class="sms-alert__message">${message}</div>
                    </div>
                </div>
                
                ${dismissible ? `
                <button type="button" 
                        class="sms-alert__dismiss" 
                        aria-label="Dismiss alert"
                        onclick="dismissAlert('${id}')">
                    <svg class="sms-alert__dismiss-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                ` : ''}
                
                ${autoHide ? `
                <div class="sms-alert__progress">
                    <div class="sms-alert__progress-bar" data-alert-progress="${id}"></div>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    getOrCreateContainer(position) {
        if (this.containers.has(position)) {
            return this.containers.get(position);
        }
        
        const container = document.createElement('div');
        container.className = `sms-alert-container sms-alert-container--${position}`;
        container.id = `sms-alert-container-${position}`;
        
        document.body.appendChild(container);
        this.containers.set(position, container);
        
        return container;
    }
    
    // Public API
    success(message, options = {}) {
        return this.show({ ...options, type: 'success', message });
    }
    
    error(message, options = {}) {
        return this.show({ ...options, type: 'error', message, autoHide: false });
    }
    
    warning(message, options = {}) {
        return this.show({ ...options, type: 'warning', message });
    }
    
    info(message, options = {}) {
        return this.show({ ...options, type: 'info', message });
    }
    
    dismissAll() {
        this.alerts.forEach((alert, id) => {
            this.dismiss(id, false);
        });
    }
}

// Global alert manager instance
window.smsAlertManager = new SmsAlertManager();

// Global helper functions
window.dismissAlert = function(alertId) {
    window.smsAlertManager.dismiss(alertId);
};

window.showAlert = function(message, type = 'info', options = {}) {
    return window.smsAlertManager.show({ ...options, type, message });
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Re-initialize in case alerts were added after initial load
    window.smsAlertManager.init();
});

// Integration with SmsUtils if available
if (window.SmsUtils) {
    window.SmsUtils.showAlert = window.showAlert;
    window.SmsUtils.dismissAlert = window.dismissAlert;
}
</script>

{{-- Usage Examples:

<!-- Basic Alerts -->
<x-sms-alert type="success" message="Template saved successfully!" />
<x-sms-alert type="error" title="Validation Error" message="Please check the form fields." />
<x-sms-alert type="warning" message="This action cannot be undone." />
<x-sms-alert type="info" message="New features are available." />

<!-- Alert with Custom Content -->
<x-sms-alert type="success" title="Campaign Sent">
    <p>Your SMS campaign has been sent to <strong>1,234 recipients</strong>.</p>
    <p>You can track the delivery status in the analytics section.</p>
</x-sms-alert>

<!-- Non-dismissible Alert -->
<x-sms-alert type="error" message="System maintenance in progress." :dismissible="false" />

<!-- Auto-hiding Alert -->
<x-sms-alert 
    type="info" 
    message="Changes saved automatically." 
    :autoHide="true" 
    :autoHideDelay="3000" />

<!-- Fixed Position Alerts -->
<x-sms-alert 
    type="success" 
    message="Operation completed!" 
    :fixed="true" 
    position="top-right" />

<!-- Different Sizes -->
<x-sms-alert type="info" message="Small alert" size="sm" />
<x-sms-alert type="warning" message="Large alert" size="lg" />

<!-- Without Icon -->
<x-sms-alert type="secondary" message="Plain message" :showIcon="false" />

<!-- JavaScript Usage -->
<script>
// Show alerts programmatically
showAlert('Success message', 'success');
showAlert('Error message', 'error', { autoHide: false });
showAlert('Custom alert', 'info', { 
    title: 'Information', 
    position: 'bottom-left',
    autoHideDelay: 10000 
});

// Using the alert manager directly
window.smsAlertManager.success('Template created!');
window.smsAlertManager.error('Failed to save template');
window.smsAlertManager.warning('Quota limit reached');
window.smsAlertManager.info('System update available');

// Dismiss all alerts
window.smsAlertManager.dismissAll();
</script>

--}}