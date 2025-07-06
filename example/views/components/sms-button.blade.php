{{--
/**
 * SMS Button Component
 * 
 * Reusable button component with multiple variants and states
 * 
 * @param string $type - Button type (button, submit, reset)
 * @param string $variant - Button variant (primary, secondary, success, warning, danger, ghost, outline)
 * @param string $size - Button size (xs, sm, md, lg, xl)
 * @param string $href - Link URL (makes it an anchor tag)
 * @param string $target - Link target (_blank, _self, etc.)
 * @param bool $disabled - Whether button is disabled
 * @param bool $loading - Whether button is in loading state
 * @param string $icon - Icon class (optional)
 * @param string $iconPosition - Icon position (left, right)
 * @param string $onclick - JavaScript onclick handler
 * @param string $class - Additional CSS classes
 * @param string $id - Button ID
 * @param array $attributes - Additional HTML attributes
 */
--}}

@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => '',
    'target' => '_self',
    'disabled' => false,
    'loading' => false,
    'icon' => '',
    'iconPosition' => 'left',
    'onclick' => '',
    'class' => '',
    'id' => '',
    'attributes' => []
])

@php
    $baseClasses = 'sms-btn';
    $variantClass = 'sms-btn--' . $variant;
    $sizeClass = 'sms-btn--' . $size;
    $stateClasses = '';
    
    if ($disabled || $loading) {
        $stateClasses .= ' sms-btn--disabled';
    }
    
    if ($loading) {
        $stateClasses .= ' sms-btn--loading';
    }
    
    if ($icon && !$slot->isNotEmpty()) {
        $stateClasses .= ' sms-btn--icon-only';
    }
    
    $allClasses = trim($baseClasses . ' ' . $variantClass . ' ' . $sizeClass . ' ' . $stateClasses . ' ' . $class);
    
    $allAttributes = array_merge([
        'class' => $allClasses,
        'id' => $id,
        'onclick' => $onclick
    ], $attributes);
    
    if ($disabled || $loading) {
        $allAttributes['disabled'] = true;
    }
@endphp

@if($href)
    {{-- Render as link --}}
    <a href="{{ $href }}" 
       target="{{ $target }}"
       @foreach($allAttributes as $key => $value)
           @if($key !== 'type' && $key !== 'disabled')
               {{ $key }}="{{ $value }}"
           @endif
       @endforeach
       @if($disabled) aria-disabled="true" @endif>
        
        @include('components.sms-button-content')
    </a>
@else
    {{-- Render as button --}}
    <button type="{{ $type }}"
            @foreach($allAttributes as $key => $value)
                @if($value !== '' && $value !== null)
                    {{ $key }}="{{ $value }}"
                @endif
            @endforeach>
        
        @include('components.sms-button-content')
    </button>
@endif

{{-- Button Content Template --}}
@php
    // This will be included in both button and anchor tags
@endphp

{{-- We'll create the content inline since we can't include from the same component --}}
<span class="sms-btn__content">
    @if($loading)
        <span class="sms-btn__spinner">
            <svg class="sms-btn__spinner-icon" viewBox="0 0 24 24">
                <circle class="sms-btn__spinner-path" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
                </circle>
            </svg>
        </span>
    @endif
    
    @if($icon && $iconPosition === 'left' && !$loading)
        <i class="{{ $icon }} sms-btn__icon sms-btn__icon--left"></i>
    @endif
    
    @if($slot->isNotEmpty())
        <span class="sms-btn__text">{{ $slot }}</span>
    @endif
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <i class="{{ $icon }} sms-btn__icon sms-btn__icon--right"></i>
    @endif
</span>

{{-- Component Styles --}}
<style>
/* Base Button Styles */
.sms-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: inherit;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: var(--border-radius, 6px);
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
    user-select: none;
    vertical-align: middle;
    line-height: 1;
}

.sms-btn:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sms-btn__content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.sms-btn__text {
    display: inline-block;
}

.sms-btn__icon {
    display: inline-block;
    flex-shrink: 0;
}

/* Button Sizes */
.sms-btn--xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    min-height: 24px;
}

.sms-btn--sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    min-height: 32px;
}

.sms-btn--md {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    min-height: 40px;
}

.sms-btn--lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    min-height: 48px;
}

.sms-btn--xl {
    padding: 1rem 2rem;
    font-size: 1.125rem;
    min-height: 56px;
}

/* Icon Only Buttons */
.sms-btn--icon-only.sms-btn--xs {
    padding: 0.25rem;
    width: 24px;
}

.sms-btn--icon-only.sms-btn--sm {
    padding: 0.375rem;
    width: 32px;
}

.sms-btn--icon-only.sms-btn--md {
    padding: 0.5rem;
    width: 40px;
}

.sms-btn--icon-only.sms-btn--lg {
    padding: 0.75rem;
    width: 48px;
}

.sms-btn--icon-only.sms-btn--xl {
    padding: 1rem;
    width: 56px;
}

/* Button Variants */

/* Primary */
.sms-btn--primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

.sms-btn--primary:hover:not(.sms-btn--disabled) {
    background-color: #2563eb;
    border-color: #2563eb;
}

.sms-btn--primary:active:not(.sms-btn--disabled) {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
}

/* Secondary */
.sms-btn--secondary {
    background-color: #6b7280;
    border-color: #6b7280;
    color: #ffffff;
}

.sms-btn--secondary:hover:not(.sms-btn--disabled) {
    background-color: #4b5563;
    border-color: #4b5563;
}

/* Success */
.sms-btn--success {
    background-color: #10b981;
    border-color: #10b981;
    color: #ffffff;
}

.sms-btn--success:hover:not(.sms-btn--disabled) {
    background-color: #059669;
    border-color: #059669;
}

/* Warning */
.sms-btn--warning {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #ffffff;
}

.sms-btn--warning:hover:not(.sms-btn--disabled) {
    background-color: #d97706;
    border-color: #d97706;
}

/* Danger */
.sms-btn--danger {
    background-color: #ef4444;
    border-color: #ef4444;
    color: #ffffff;
}

.sms-btn--danger:hover:not(.sms-btn--disabled) {
    background-color: #dc2626;
    border-color: #dc2626;
}

/* Ghost */
.sms-btn--ghost {
    background-color: transparent;
    border-color: transparent;
    color: #6b7280;
}

.sms-btn--ghost:hover:not(.sms-btn--disabled) {
    background-color: #f3f4f6;
    color: #374151;
}

/* Outline Variants */
.sms-btn--outline {
    background-color: transparent;
    border-color: #d1d5db;
    color: #374151;
}

.sms-btn--outline:hover:not(.sms-btn--disabled) {
    background-color: #f9fafb;
    border-color: #9ca3af;
}

/* Button States */
.sms-btn--disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.sms-btn--loading {
    cursor: wait;
    pointer-events: none;
}

.sms-btn--loading .sms-btn__text {
    opacity: 0.7;
}

/* Loading Spinner */
.sms-btn__spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.sms-btn__spinner-icon {
    width: 1em;
    height: 1em;
    animation: spin 1s linear infinite;
}

.sms-btn__spinner-path {
    stroke: currentColor;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Dark Mode */
[data-theme="dark"] .sms-btn--ghost {
    color: #d1d5db;
}

[data-theme="dark"] .sms-btn--ghost:hover:not(.sms-btn--disabled) {
    background-color: #374151;
    color: #f9fafb;
}

[data-theme="dark"] .sms-btn--outline {
    border-color: #4b5563;
    color: #d1d5db;
}

[data-theme="dark"] .sms-btn--outline:hover:not(.sms-btn--disabled) {
    background-color: #374151;
    border-color: #6b7280;
}

/* Responsive */
@media (max-width: 768px) {
    .sms-btn--lg {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        min-height: 44px;
    }
    
    .sms-btn--xl {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        min-height: 48px;
    }
}

/* Button Groups */
.sms-btn-group {
    display: inline-flex;
    border-radius: var(--border-radius, 6px);
    overflow: hidden;
}

.sms-btn-group .sms-btn {
    border-radius: 0;
    border-right-width: 0;
}

.sms-btn-group .sms-btn:first-child {
    border-top-left-radius: var(--border-radius, 6px);
    border-bottom-left-radius: var(--border-radius, 6px);
}

.sms-btn-group .sms-btn:last-child {
    border-top-right-radius: var(--border-radius, 6px);
    border-bottom-right-radius: var(--border-radius, 6px);
    border-right-width: 1px;
}

.sms-btn-group .sms-btn:focus {
    z-index: 1;
}
</style>

{{-- Usage Examples:

<!-- Basic Buttons -->
<x-sms-button>Default Button</x-sms-button>
<x-sms-button variant="primary">Primary Button</x-sms-button>
<x-sms-button variant="success">Success Button</x-sms-button>
<x-sms-button variant="danger">Danger Button</x-sms-button>

<!-- Button Sizes -->
<x-sms-button size="xs">Extra Small</x-sms-button>
<x-sms-button size="sm">Small</x-sms-button>
<x-sms-button size="md">Medium</x-sms-button>
<x-sms-button size="lg">Large</x-sms-button>
<x-sms-button size="xl">Extra Large</x-sms-button>

<!-- Buttons with Icons -->
<x-sms-button icon="fas fa-paper-plane" variant="primary">Send SMS</x-sms-button>
<x-sms-button icon="fas fa-download" iconPosition="right">Download</x-sms-button>
<x-sms-button icon="fas fa-cog" variant="ghost" /> <!-- Icon only -->

<!-- Button States -->
<x-sms-button disabled>Disabled Button</x-sms-button>
<x-sms-button loading variant="primary">Loading...</x-sms-button>

<!-- Link Buttons -->
<x-sms-button href="{{ route('sms.templates.create') }}" variant="primary">
    Create Template
</x-sms-button>

<!-- Form Buttons -->
<x-sms-button type="submit" variant="success">Save Template</x-sms-button>
<x-sms-button type="reset" variant="secondary">Reset Form</x-sms-button>

<!-- Custom Attributes -->
<x-sms-button 
    variant="primary" 
    onclick="sendTestSms()" 
    :attributes="['data-template-id' => $template->id]">
    Test SMS
</x-sms-button>

<!-- Button Group -->
<div class="sms-btn-group">
    <x-sms-button variant="outline">Left</x-sms-button>
    <x-sms-button variant="outline">Middle</x-sms-button>
    <x-sms-button variant="outline">Right</x-sms-button>
</div>

--}}