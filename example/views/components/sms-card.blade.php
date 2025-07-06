{{--
/**
 * SMS Card Component
 * 
 * Reusable card component for SMS dashboard
 * 
 * @param string $title - Card title
 * @param string $value - Main value to display
 * @param string $icon - Icon class (optional)
 * @param string $color - Color theme (primary, success, warning, danger)
 * @param string $subtitle - Subtitle text (optional)
 * @param string $trend - Trend indicator (up, down, neutral) (optional)
 * @param string $trendValue - Trend percentage (optional)
 * @param string $link - Link URL (optional)
 * @param string $size - Card size (sm, md, lg)
 */
--}}

@props([
    'title' => '',
    'value' => '',
    'icon' => '',
    'color' => 'primary',
    'subtitle' => '',
    'trend' => '',
    'trendValue' => '',
    'link' => '',
    'size' => 'md'
])

<div class="sms-card sms-card--{{ $color }} sms-card--{{ $size }} {{ $link ? 'sms-card--clickable' : '' }}" 
     @if($link) onclick="window.location.href='{{ $link }}'" @endif>
    
    {{-- Card Header --}}
    <div class="sms-card__header">
        @if($icon)
            <div class="sms-card__icon sms-card__icon--{{ $color }}">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
        
        <div class="sms-card__title-section">
            <h3 class="sms-card__title">{{ $title }}</h3>
            @if($subtitle)
                <p class="sms-card__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if($trend && $trendValue)
            <div class="sms-card__trend sms-card__trend--{{ $trend }}">
                <i class="fas fa-arrow-{{ $trend === 'up' ? 'up' : 'down' }}"></i>
                <span>{{ $trendValue }}%</span>
            </div>
        @endif
    </div>
    
    {{-- Card Body --}}
    <div class="sms-card__body">
        <div class="sms-card__value">{{ $value }}</div>
        
        {{-- Custom Content Slot --}}
        @if($slot->isNotEmpty())
            <div class="sms-card__content">
                {{ $slot }}
            </div>
        @endif
    </div>
    
    {{-- Card Footer (if link provided) --}}
    @if($link)
        <div class="sms-card__footer">
            <span class="sms-card__link-text">View Details</span>
            <i class="fas fa-arrow-right"></i>
        </div>
    @endif
</div>

{{-- Component Styles --}}
<style>
.sms-card {
    background: var(--card-bg, #ffffff);
    border-radius: var(--border-radius, 8px);
    box-shadow: var(--box-shadow, 0 2px 4px rgba(0, 0, 0, 0.1));
    padding: 1.5rem;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color, #e5e7eb);
}

.sms-card--clickable {
    cursor: pointer;
}

.sms-card--clickable:hover {
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-hover, 0 4px 8px rgba(0, 0, 0, 0.15));
}

/* Card Sizes */
.sms-card--sm {
    padding: 1rem;
}

.sms-card--md {
    padding: 1.5rem;
}

.sms-card--lg {
    padding: 2rem;
}

/* Card Header */
.sms-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.sms-card__icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}

.sms-card__icon--primary {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.sms-card__icon--success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.sms-card__icon--warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.sms-card__icon--danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.sms-card__title-section {
    flex: 1;
}

.sms-card__title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary, #6b7280);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.sms-card__subtitle {
    font-size: 0.75rem;
    color: var(--text-muted, #9ca3af);
    margin: 0.25rem 0 0 0;
}

.sms-card__trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.sms-card__trend--up {
    color: #10b981;
}

.sms-card__trend--down {
    color: #ef4444;
}

.sms-card__trend--neutral {
    color: #6b7280;
}

/* Card Body */
.sms-card__body {
    margin-bottom: 1rem;
}

.sms-card__value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary, #111827);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.sms-card--sm .sms-card__value {
    font-size: 1.5rem;
}

.sms-card--lg .sms-card__value {
    font-size: 2.5rem;
}

.sms-card__content {
    color: var(--text-secondary, #6b7280);
    font-size: 0.875rem;
}

/* Card Footer */
.sms-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    margin-top: 1rem;
}

.sms-card__link-text {
    font-size: 0.875rem;
    color: var(--primary-color, #3b82f6);
    font-weight: 500;
}

/* Color Variants */
.sms-card--primary {
    border-left: 4px solid #3b82f6;
}

.sms-card--success {
    border-left: 4px solid #10b981;
}

.sms-card--warning {
    border-left: 4px solid #f59e0b;
}

.sms-card--danger {
    border-left: 4px solid #ef4444;
}

/* Dark Mode */
[data-theme="dark"] .sms-card {
    background: var(--dark-card-bg, #374151);
    border-color: var(--dark-border-color, #4b5563);
}

[data-theme="dark"] .sms-card__title {
    color: var(--dark-text-secondary, #d1d5db);
}

[data-theme="dark"] .sms-card__value {
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-card__footer {
    border-color: var(--dark-border-color, #4b5563);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-card {
        padding: 1rem;
    }
    
    .sms-card__header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .sms-card__icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .sms-card__value {
        font-size: 1.5rem;
    }
}
</style>

{{-- Usage Examples:

<!-- Basic Card -->
<x-sms-card 
    title="Total SMS Sent" 
    value="1,234" 
    icon="fas fa-paper-plane" 
    color="primary" />

<!-- Card with Trend -->
<x-sms-card 
    title="Delivery Rate" 
    value="94.5%" 
    icon="fas fa-check-circle" 
    color="success" 
    trend="up" 
    trendValue="2.3" />

<!-- Clickable Card -->
<x-sms-card 
    title="Failed SMS" 
    value="23" 
    icon="fas fa-exclamation-triangle" 
    color="danger" 
    link="{{ route('sms.logs', ['status' => 'failed']) }}" />

<!-- Card with Custom Content -->
<x-sms-card title="Campaign Performance" color="primary">
    <div class="progress-bar">
        <div class="progress-fill" style="width: 75%"></div>
    </div>
    <p class="mt-2 text-sm">75% Complete</p>
</x-sms-card>

--}}