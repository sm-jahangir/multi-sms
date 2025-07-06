{{--
/**
 * SMS Pagination Component
 * 
 * Comprehensive pagination component with customizable styling and behavior
 * 
 * @param object $paginator - Laravel paginator instance
 * @param string $view - Pagination view type (simple, full, compact)
 * @param bool $showInfo - Show pagination info text
 * @param bool $showJumper - Show page jumper input
 * @param bool $showSizeSelector - Show page size selector
 * @param array $pageSizes - Available page sizes
 * @param string $infoText - Custom info text template
 * @param string $class - Additional CSS classes
 * @param string $id - Component ID
 */
--}}

@props([
    'paginator' => null,
    'view' => 'full', // simple, full, compact
    'showInfo' => true,
    'showJumper' => false,
    'showSizeSelector' => false,
    'pageSizes' => [10, 25, 50, 100],
    'infoText' => 'Showing {from} to {to} of {total} results',
    'class' => '',
    'id' => 'sms-pagination-' . uniqid()
])

@if($paginator && $paginator->hasPages())
@php
    $paginationClasses = 'sms-pagination sms-pagination--' . $view . ' ' . $class;
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $from = $paginator->firstItem() ?? 0;
    $to = $paginator->lastItem() ?? 0;
    $total = $paginator->total();
    $perPage = $paginator->perPage();
    
    // Generate page links
    $links = [];
    $onEachSide = 2; // Number of pages to show on each side of current page
    
    // Always show first page
    if ($currentPage > $onEachSide + 2) {
        $links[] = ['page' => 1, 'url' => $paginator->url(1), 'active' => false, 'disabled' => false];
        if ($currentPage > $onEachSide + 3) {
            $links[] = ['page' => '...', 'url' => null, 'active' => false, 'disabled' => true];
        }
    }
    
    // Pages around current page
    $start = max(1, $currentPage - $onEachSide);
    $end = min($lastPage, $currentPage + $onEachSide);
    
    for ($page = $start; $page <= $end; $page++) {
        $links[] = [
            'page' => $page,
            'url' => $paginator->url($page),
            'active' => $page === $currentPage,
            'disabled' => false
        ];
    }
    
    // Always show last page
    if ($currentPage < $lastPage - $onEachSide - 1) {
        if ($currentPage < $lastPage - $onEachSide - 2) {
            $links[] = ['page' => '...', 'url' => null, 'active' => false, 'disabled' => true];
        }
        $links[] = ['page' => $lastPage, 'url' => $paginator->url($lastPage), 'active' => false, 'disabled' => false];
    }
    
    // Format info text
    $formattedInfoText = str_replace(
        ['{from}', '{to}', '{total}', '{page}', '{pages}'],
        [$from, $to, $total, $currentPage, $lastPage],
        $infoText
    );
@endphp

<div class="{{ $paginationClasses }}" id="{{ $id }}" data-pagination="{{ $id }}">
    {{-- Pagination Info --}}
    @if($showInfo && $view !== 'simple')
    <div class="sms-pagination__info">
        <span class="sms-pagination__info-text">{{ $formattedInfoText }}</span>
        
        @if($showSizeSelector)
        <div class="sms-pagination__size-selector">
            <label for="{{ $id }}-page-size" class="sms-pagination__size-label">Show:</label>
            <select id="{{ $id }}-page-size" 
                    class="sms-pagination__size-select" 
                    data-pagination-size="{{ $id }}"
                    onchange="changePaginationSize('{{ $id }}', this.value)">
                @foreach($pageSizes as $size)
                <option value="{{ $size }}" {{ $size == $perPage ? 'selected' : '' }}>
                    {{ $size }}
                </option>
                @endforeach
            </select>
            <span class="sms-pagination__size-suffix">per page</span>
        </div>
        @endif
    </div>
    @endif
    
    {{-- Pagination Controls --}}
    <div class="sms-pagination__controls">
        {{-- Previous Button --}}
        @if($paginator->onFirstPage())
        <span class="sms-pagination__btn sms-pagination__btn--prev sms-pagination__btn--disabled">
            @if($view === 'compact')
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            @else
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="sms-pagination__text">Previous</span>
            @endif
        </span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}" 
           class="sms-pagination__btn sms-pagination__btn--prev"
           rel="prev"
           aria-label="Previous page">
            @if($view === 'compact')
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            @else
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="sms-pagination__text">Previous</span>
            @endif
        </a>
        @endif
        
        {{-- Page Numbers (only for full view) --}}
        @if($view === 'full')
        <div class="sms-pagination__pages">
            @foreach($links as $link)
                @if($link['disabled'])
                <span class="sms-pagination__btn sms-pagination__btn--page sms-pagination__btn--disabled">
                    {{ $link['page'] }}
                </span>
                @elseif($link['active'])
                <span class="sms-pagination__btn sms-pagination__btn--page sms-pagination__btn--active" 
                      aria-current="page">
                    {{ $link['page'] }}
                </span>
                @else
                <a href="{{ $link['url'] }}" 
                   class="sms-pagination__btn sms-pagination__btn--page"
                   aria-label="Go to page {{ $link['page'] }}">
                    {{ $link['page'] }}
                </a>
                @endif
            @endforeach
        </div>
        @endif
        
        {{-- Page Info (for simple and compact views) --}}
        @if($view === 'simple' || $view === 'compact')
        <div class="sms-pagination__current">
            <span class="sms-pagination__current-text">
                Page {{ $currentPage }} of {{ $lastPage }}
            </span>
            
            @if($showJumper)
            <div class="sms-pagination__jumper">
                <label for="{{ $id }}-page-jump" class="sms-pagination__jumper-label">Go to:</label>
                <input type="number" 
                       id="{{ $id }}-page-jump"
                       class="sms-pagination__jumper-input" 
                       min="1" 
                       max="{{ $lastPage }}"
                       value="{{ $currentPage }}"
                       data-pagination-jump="{{ $id }}"
                       onkeypress="handlePaginationJump(event, '{{ $id }}', {{ $lastPage }})">
            </div>
            @endif
        </div>
        @endif
        
        {{-- Next Button --}}
        @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" 
           class="sms-pagination__btn sms-pagination__btn--next"
           rel="next"
           aria-label="Next page">
            @if($view === 'compact')
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            @else
            <span class="sms-pagination__text">Next</span>
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            @endif
        </a>
        @else
        <span class="sms-pagination__btn sms-pagination__btn--next sms-pagination__btn--disabled">
            @if($view === 'compact')
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            @else
            <span class="sms-pagination__text">Next</span>
            <svg class="sms-pagination__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            @endif
        </span>
        @endif
    </div>
    
    {{-- Advanced Controls --}}
    @if($showJumper && $view === 'full')
    <div class="sms-pagination__advanced">
        <div class="sms-pagination__jumper">
            <label for="{{ $id }}-page-jump" class="sms-pagination__jumper-label">Go to page:</label>
            <input type="number" 
                   id="{{ $id }}-page-jump"
                   class="sms-pagination__jumper-input" 
                   min="1" 
                   max="{{ $lastPage }}"
                   value="{{ $currentPage }}"
                   data-pagination-jump="{{ $id }}"
                   onkeypress="handlePaginationJump(event, '{{ $id }}', {{ $lastPage }})">
            <button type="button" 
                    class="sms-pagination__jumper-btn"
                    onclick="jumpToPage('{{ $id }}', {{ $lastPage }})">
                Go
            </button>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Component Styles --}}
<style>
/* Pagination Base */
.sms-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
    font-size: 0.875rem;
}

.sms-pagination--simple,
.sms-pagination--compact {
    justify-content: center;
}

.sms-pagination--compact {
    gap: 0.5rem;
}

/* Pagination Info */
.sms-pagination__info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-secondary, #6b7280);
}

.sms-pagination__info-text {
    font-weight: 500;
}

/* Size Selector */
.sms-pagination__size-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.sms-pagination__size-label,
.sms-pagination__size-suffix {
    color: var(--text-secondary, #6b7280);
    font-weight: 400;
}

.sms-pagination__size-select {
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    cursor: pointer;
}

.sms-pagination__size-select:focus {
    outline: 0;
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Pagination Controls */
.sms-pagination__controls {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.sms-pagination--compact .sms-pagination__controls {
    gap: 0.5rem;
}

/* Pagination Buttons */
.sms-pagination__btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--input-border, #d1d5db);
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: var(--border-radius-sm, 4px);
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
}

.sms-pagination__btn:hover:not(.sms-pagination__btn--disabled):not(.sms-pagination__btn--active) {
    background: var(--hover-bg, #f3f4f6);
    border-color: var(--hover-border, #9ca3af);
}

.sms-pagination__btn--active {
    background: var(--primary-color, #3b82f6);
    border-color: var(--primary-color, #3b82f6);
    color: white;
}

.sms-pagination__btn--disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.sms-pagination__btn--page {
    min-width: 2.5rem;
    justify-content: center;
    padding: 0.5rem;
}

.sms-pagination__btn--prev,
.sms-pagination__btn--next {
    font-weight: 500;
}

/* Pagination Icons */
.sms-pagination__icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

/* Page Numbers */
.sms-pagination__pages {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Current Page Info */
.sms-pagination__current {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-secondary, #6b7280);
}

.sms-pagination__current-text {
    font-weight: 500;
    white-space: nowrap;
}

/* Page Jumper */
.sms-pagination__jumper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sms-pagination__jumper-label {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    white-space: nowrap;
}

.sms-pagination__jumper-input {
    width: 4rem;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    text-align: center;
}

.sms-pagination__jumper-input:focus {
    outline: 0;
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sms-pagination__jumper-btn {
    padding: 0.25rem 0.75rem;
    background: var(--primary-color, #3b82f6);
    color: white;
    border: 1px solid var(--primary-color, #3b82f6);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.875rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.sms-pagination__jumper-btn:hover {
    background: var(--primary-dark, #2563eb);
}

/* Advanced Controls */
.sms-pagination__advanced {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Compact View Adjustments */
.sms-pagination--compact .sms-pagination__btn {
    padding: 0.375rem;
    min-width: auto;
}

.sms-pagination--compact .sms-pagination__btn--page {
    min-width: 2rem;
}

.sms-pagination--compact .sms-pagination__text {
    display: none;
}

/* Simple View Adjustments */
.sms-pagination--simple .sms-pagination__controls {
    gap: 1rem;
}

/* Dark Mode */
[data-theme="dark"] .sms-pagination__btn {
    background: var(--dark-input-bg, #374151);
    border-color: var(--dark-input-border, #4b5563);
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-pagination__btn:hover:not(.sms-pagination__btn--disabled):not(.sms-pagination__btn--active) {
    background: var(--dark-hover-bg, #4b5563);
    border-color: var(--dark-hover-border, #6b7280);
}

[data-theme="dark"] .sms-pagination__size-select,
[data-theme="dark"] .sms-pagination__jumper-input {
    background: var(--dark-input-bg, #374151);
    border-color: var(--dark-input-border, #4b5563);
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-pagination__info,
[data-theme="dark"] .sms-pagination__current,
[data-theme="dark"] .sms-pagination__size-label,
[data-theme="dark"] .sms-pagination__size-suffix,
[data-theme="dark"] .sms-pagination__jumper-label {
    color: var(--dark-text-secondary, #d1d5db);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-pagination {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .sms-pagination__info {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .sms-pagination__controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .sms-pagination__advanced {
        justify-content: center;
    }
    
    .sms-pagination--full .sms-pagination__pages {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .sms-pagination__btn--prev .sms-pagination__text,
    .sms-pagination__btn--next .sms-pagination__text {
        display: none;
    }
    
    .sms-pagination__btn--prev,
    .sms-pagination__btn--next {
        padding: 0.5rem;
        min-width: auto;
    }
    
    .sms-pagination__size-selector {
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }
    
    .sms-pagination__current {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}
</style>

{{-- Pagination JavaScript --}}
<script>
// Page size change handler
function changePaginationSize(paginationId, newSize) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('per_page', newSize);
    currentUrl.searchParams.set('page', 1); // Reset to first page
    window.location.href = currentUrl.toString();
}

// Page jump handler
function handlePaginationJump(event, paginationId, maxPage) {
    if (event.key === 'Enter') {
        event.preventDefault();
        jumpToPage(paginationId, maxPage);
    }
}

function jumpToPage(paginationId, maxPage) {
    const input = document.querySelector(`[data-pagination-jump="${paginationId}"]`);
    if (!input) return;
    
    const page = parseInt(input.value);
    
    if (isNaN(page) || page < 1 || page > maxPage) {
        input.value = '';
        input.focus();
        
        // Show error message
        if (window.SmsUtils && window.SmsUtils.showAlert) {
            window.SmsUtils.showAlert(`Please enter a valid page number between 1 and ${maxPage}`, 'error');
        } else {
            alert(`Please enter a valid page number between 1 and ${maxPage}`);
        }
        return;
    }
    
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// Pagination component class
class SmsPaginationComponent {
    constructor(element) {
        this.element = element;
        this.id = element.dataset.pagination;
        
        this.init();
    }
    
    init() {
        this.initKeyboardNavigation();
        this.initAccessibility();
    }
    
    initKeyboardNavigation() {
        this.element.addEventListener('keydown', (e) => {
            if (e.target.matches('.sms-pagination__btn')) {
                this.handleKeyNavigation(e);
            }
        });
    }
    
    handleKeyNavigation(e) {
        const buttons = Array.from(this.element.querySelectorAll('.sms-pagination__btn:not(.sms-pagination__btn--disabled)'));
        const currentIndex = buttons.indexOf(e.target);
        
        let targetIndex = currentIndex;
        
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                targetIndex = Math.max(0, currentIndex - 1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                targetIndex = Math.min(buttons.length - 1, currentIndex + 1);
                break;
            case 'Home':
                e.preventDefault();
                targetIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                targetIndex = buttons.length - 1;
                break;
        }
        
        if (targetIndex !== currentIndex && buttons[targetIndex]) {
            buttons[targetIndex].focus();
        }
    }
    
    initAccessibility() {
        // Add ARIA labels and roles
        const nav = this.element.querySelector('.sms-pagination__controls');
        if (nav) {
            nav.setAttribute('role', 'navigation');
            nav.setAttribute('aria-label', 'Pagination Navigation');
        }
        
        // Add page information for screen readers
        const activeBtn = this.element.querySelector('.sms-pagination__btn--active');
        if (activeBtn) {
            activeBtn.setAttribute('aria-current', 'page');
        }
    }
    
    // Public API
    goToPage(page) {
        const maxPage = this.getMaxPage();
        if (page >= 1 && page <= maxPage) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('page', page);
            window.location.href = currentUrl.toString();
        }
    }
    
    getMaxPage() {
        const lastPageBtn = this.element.querySelector('.sms-pagination__btn--page:last-of-type');
        return lastPageBtn ? parseInt(lastPageBtn.textContent) : 1;
    }
    
    getCurrentPage() {
        const activeBtn = this.element.querySelector('.sms-pagination__btn--active');
        return activeBtn ? parseInt(activeBtn.textContent) : 1;
    }
}

// Auto-initialize pagination components
document.addEventListener('DOMContentLoaded', function() {
    const paginations = document.querySelectorAll('[data-pagination]');
    paginations.forEach(pagination => {
        new SmsPaginationComponent(pagination);
    });
});

// Global pagination utilities
window.SmsPagination = {
    getInstance: function(id) {
        const element = document.querySelector(`[data-pagination="${id}"]`);
        return element ? element.smsPaginationInstance : null;
    },
    
    goToPage: function(id, page) {
        const instance = this.getInstance(id);
        if (instance) instance.goToPage(page);
    },
    
    getCurrentPage: function(id) {
        const instance = this.getInstance(id);
        return instance ? instance.getCurrentPage() : 1;
    }
};
</script>

{{-- Usage Examples:

<!-- Basic Pagination -->
<x-sms-pagination :paginator="$templates" />

<!-- Simple Pagination -->
<x-sms-pagination :paginator="$campaigns" view="simple" />

<!-- Compact Pagination -->
<x-sms-pagination :paginator="$logs" view="compact" />

<!-- Advanced Pagination with All Features -->
<x-sms-pagination 
    :paginator="$templates"
    view="full"
    :showInfo="true"
    :showJumper="true"
    :showSizeSelector="true"
    :pageSizes="[10, 25, 50, 100, 200]"
    infoText="Displaying {from}-{to} of {total} templates"
    class="my-custom-pagination" />

<!-- Pagination without Info -->
<x-sms-pagination 
    :paginator="$autoresponders"
    :showInfo="false" />

<!-- Custom Info Text -->
<x-sms-pagination 
    :paginator="$analytics"
    infoText="Page {page} of {pages} ({total} total records)" />

--}}