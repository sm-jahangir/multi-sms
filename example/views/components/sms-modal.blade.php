{{--
/**
 * SMS Modal Component
 * 
 * Reusable modal component with customizable content and actions
 * 
 * @param string $id - Modal ID (required)
 * @param string $title - Modal title
 * @param string $size - Modal size (xs, sm, md, lg, xl, full)
 * @param bool $closable - Whether modal can be closed
 * @param bool $backdrop - Whether to show backdrop
 * @param bool $keyboard - Whether to close on ESC key
 * @param string $headerClass - Additional header classes
 * @param string $bodyClass - Additional body classes
 * @param string $footerClass - Additional footer classes
 * @param bool $scrollable - Whether modal body is scrollable
 * @param bool $centered - Whether to center modal vertically
 */
--}}

@props([
    'id' => 'sms-modal-' . uniqid(),
    'title' => '',
    'size' => 'md',
    'closable' => true,
    'backdrop' => true,
    'keyboard' => true,
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'scrollable' => false,
    'centered' => false
])

@php
    $modalClasses = 'sms-modal';
    $dialogClasses = 'sms-modal__dialog sms-modal__dialog--' . $size;
    
    if ($centered) {
        $dialogClasses .= ' sms-modal__dialog--centered';
    }
    
    if ($scrollable) {
        $dialogClasses .= ' sms-modal__dialog--scrollable';
    }
@endphp

{{-- Modal Backdrop --}}
<div id="{{ $id }}" 
     class="{{ $modalClasses }}" 
     data-backdrop="{{ $backdrop ? 'true' : 'false' }}" 
     data-keyboard="{{ $keyboard ? 'true' : 'false' }}"
     style="display: none;"
     role="dialog" 
     aria-labelledby="{{ $id }}-title" 
     aria-hidden="true">
    
    {{-- Modal Dialog --}}
    <div class="{{ $dialogClasses }}" role="document">
        <div class="sms-modal__content">
            
            {{-- Modal Header --}}
            @if($title || $closable || isset($header))
                <div class="sms-modal__header {{ $headerClass }}">
                    @if(isset($header))
                        {{ $header }}
                    @else
                        @if($title)
                            <h5 class="sms-modal__title" id="{{ $id }}-title">{{ $title }}</h5>
                        @endif
                        
                        @if($closable)
                            <button type="button" 
                                    class="sms-modal__close" 
                                    data-dismiss="modal" 
                                    aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        @endif
                    @endif
                </div>
            @endif
            
            {{-- Modal Body --}}
            <div class="sms-modal__body {{ $bodyClass }}">
                @if(isset($body))
                    {{ $body }}
                @else
                    {{ $slot }}
                @endif
            </div>
            
            {{-- Modal Footer --}}
            @if(isset($footer))
                <div class="sms-modal__footer {{ $footerClass }}">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Component Styles --}}
<style>
/* Modal Base Styles */
.sms-modal {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
    overflow-y: auto;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    transition: opacity 0.3s ease;
}

.sms-modal.show {
    display: flex !important;
    align-items: center;
    justify-content: center;
    opacity: 1;
}

.sms-modal.fade {
    opacity: 0;
}

.sms-modal.fade.show {
    opacity: 1;
}

/* Modal Dialog */
.sms-modal__dialog {
    position: relative;
    width: auto;
    margin: 1rem;
    pointer-events: none;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.sms-modal.show .sms-modal__dialog {
    transform: scale(1);
    pointer-events: auto;
}

.sms-modal__dialog--centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 2rem);
}

.sms-modal__dialog--scrollable {
    max-height: calc(100vh - 2rem);
    overflow: hidden;
}

.sms-modal__dialog--scrollable .sms-modal__content {
    max-height: 100%;
    overflow: hidden;
}

.sms-modal__dialog--scrollable .sms-modal__body {
    overflow-y: auto;
}

/* Modal Sizes */
.sms-modal__dialog--xs {
    max-width: 300px;
}

.sms-modal__dialog--sm {
    max-width: 400px;
}

.sms-modal__dialog--md {
    max-width: 500px;
}

.sms-modal__dialog--lg {
    max-width: 800px;
}

.sms-modal__dialog--xl {
    max-width: 1140px;
}

.sms-modal__dialog--full {
    max-width: calc(100vw - 2rem);
    max-height: calc(100vh - 2rem);
}

/* Modal Content */
.sms-modal__content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    background-color: var(--modal-bg, #ffffff);
    background-clip: padding-box;
    border: 1px solid var(--modal-border, rgba(0, 0, 0, 0.2));
    border-radius: var(--border-radius-lg, 12px);
    box-shadow: var(--modal-shadow, 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04));
    pointer-events: auto;
    outline: 0;
}

/* Modal Header */
.sms-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid var(--modal-border, #e5e7eb);
}

.sms-modal__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
    line-height: 1.5;
}

.sms-modal__close {
    padding: 0.5rem;
    margin: -0.5rem -0.5rem -0.5rem auto;
    background: transparent;
    border: 0;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    border-radius: var(--border-radius, 6px);
    transition: all 0.2s ease;
}

.sms-modal__close:hover {
    color: var(--text-primary, #111827);
    background-color: var(--hover-bg, #f3f4f6);
}

.sms-modal__close:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modal Body */
.sms-modal__body {
    position: relative;
    flex: 1 1 auto;
    padding: 1.5rem;
    color: var(--text-primary, #111827);
    line-height: 1.6;
}

/* Modal Footer */
.sms-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem 1.5rem 1.5rem;
    border-top: 1px solid var(--modal-border, #e5e7eb);
}

/* Dark Mode */
[data-theme="dark"] .sms-modal__content {
    background-color: var(--dark-modal-bg, #374151);
    border-color: var(--dark-modal-border, #4b5563);
}

[data-theme="dark"] .sms-modal__header {
    border-color: var(--dark-modal-border, #4b5563);
}

[data-theme="dark"] .sms-modal__title {
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-modal__close {
    color: var(--dark-text-secondary, #d1d5db);
}

[data-theme="dark"] .sms-modal__close:hover {
    color: var(--dark-text-primary, #f9fafb);
    background-color: var(--dark-hover-bg, #4b5563);
}

[data-theme="dark"] .sms-modal__body {
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-modal__footer {
    border-color: var(--dark-modal-border, #4b5563);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-modal__dialog {
        margin: 0.5rem;
        max-width: calc(100vw - 1rem);
    }
    
    .sms-modal__dialog--full {
        max-width: calc(100vw - 1rem);
        max-height: calc(100vh - 1rem);
    }
    
    .sms-modal__header {
        padding: 1rem 1rem 0.75rem 1rem;
    }
    
    .sms-modal__body {
        padding: 1rem;
    }
    
    .sms-modal__footer {
        padding: 0.75rem 1rem 1rem 1rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .sms-modal__footer .sms-btn {
        width: 100%;
    }
}

/* Animation Classes */
.sms-modal-enter {
    opacity: 0;
    transform: scale(0.9);
}

.sms-modal-enter-active {
    opacity: 1;
    transform: scale(1);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.sms-modal-exit {
    opacity: 1;
    transform: scale(1);
}

.sms-modal-exit-active {
    opacity: 0;
    transform: scale(0.9);
    transition: opacity 0.3s ease, transform 0.3s ease;
}
</style>

{{-- Modal JavaScript --}}
<script>
// Modal functionality
class SmsModalComponent {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.dialog = this.modal?.querySelector('.sms-modal__dialog');
        this.backdrop = this.modal?.dataset.backdrop !== 'false';
        this.keyboard = this.modal?.dataset.keyboard !== 'false';
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        if (!this.modal) return;
        
        // Close button event
        const closeBtn = this.modal.querySelector('[data-dismiss="modal"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        
        // Backdrop click event
        if (this.backdrop) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.hide();
                }
            });
        }
        
        // Keyboard event
        if (this.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.hide();
                }
            });
        }
    }
    
    show() {
        if (this.isOpen) return;
        
        this.modal.style.display = 'flex';
        this.modal.classList.add('fade');
        
        // Force reflow
        this.modal.offsetHeight;
        
        this.modal.classList.add('show');
        this.modal.setAttribute('aria-hidden', 'false');
        
        // Focus management
        const focusableElement = this.modal.querySelector('input, button, textarea, select, [tabindex]:not([tabindex="-1"])');
        if (focusableElement) {
            focusableElement.focus();
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        this.isOpen = true;
        
        // Dispatch custom event
        this.modal.dispatchEvent(new CustomEvent('sms-modal:show'));
    }
    
    hide() {
        if (!this.isOpen) return;
        
        this.modal.classList.remove('show');
        this.modal.setAttribute('aria-hidden', 'true');
        
        // Wait for animation to complete
        setTimeout(() => {
            this.modal.style.display = 'none';
            this.modal.classList.remove('fade');
            
            // Restore body scroll
            document.body.style.overflow = '';
            
            this.isOpen = false;
            
            // Dispatch custom event
            this.modal.dispatchEvent(new CustomEvent('sms-modal:hide'));
        }, 300);
    }
    
    toggle() {
        if (this.isOpen) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    setTitle(title) {
        const titleElement = this.modal.querySelector('.sms-modal__title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
    
    setBody(content) {
        const bodyElement = this.modal.querySelector('.sms-modal__body');
        if (bodyElement) {
            if (typeof content === 'string') {
                bodyElement.innerHTML = content;
            } else {
                bodyElement.innerHTML = '';
                bodyElement.appendChild(content);
            }
        }
    }
    
    setFooter(content) {
        let footerElement = this.modal.querySelector('.sms-modal__footer');
        
        if (!footerElement) {
            footerElement = document.createElement('div');
            footerElement.className = 'sms-modal__footer';
            this.modal.querySelector('.sms-modal__content').appendChild(footerElement);
        }
        
        if (typeof content === 'string') {
            footerElement.innerHTML = content;
        } else {
            footerElement.innerHTML = '';
            footerElement.appendChild(content);
        }
    }
}

// Auto-initialize modals
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.sms-modal');
    modals.forEach(modal => {
        window[`smsModal_${modal.id}`] = new SmsModalComponent(modal.id);
    });
});

// Global modal functions
window.showSmsModal = function(modalId) {
    const modalInstance = window[`smsModal_${modalId}`];
    if (modalInstance) {
        modalInstance.show();
    }
};

window.hideSmsModal = function(modalId) {
    const modalInstance = window[`smsModal_${modalId}`];
    if (modalInstance) {
        modalInstance.hide();
    }
};
</script>

{{-- Usage Examples:

<!-- Basic Modal -->
<x-sms-modal id="basic-modal" title="Basic Modal">
    <p>This is a basic modal with some content.</p>
</x-sms-modal>

<!-- Modal with Custom Header and Footer -->
<x-sms-modal id="custom-modal" size="lg">
    <x-slot name="header">
        <h5 class="sms-modal__title">Custom Header</h5>
        <span class="badge badge-primary">New</span>
        <button type="button" class="sms-modal__close" data-dismiss="modal">&times;</button>
    </x-slot>
    
    <x-slot name="body">
        <p>Modal content goes here...</p>
    </x-slot>
    
    <x-slot name="footer">
        <x-sms-button variant="secondary" onclick="hideSmsModal('custom-modal')">Cancel</x-sms-button>
        <x-sms-button variant="primary">Save Changes</x-sms-button>
    </x-slot>
</x-sms-modal>

<!-- Form Modal -->
<x-sms-modal id="form-modal" title="Create Template" size="md">
    <form id="template-form">
        <div class="form-group">
            <label for="template-name">Template Name</label>
            <input type="text" id="template-name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="template-content">Content</label>
            <textarea id="template-content" class="form-control" rows="4" required></textarea>
        </div>
    </form>
    
    <x-slot name="footer">
        <x-sms-button variant="secondary" onclick="hideSmsModal('form-modal')">Cancel</x-sms-button>
        <x-sms-button variant="primary" type="submit" form="template-form">Create Template</x-sms-button>
    </x-slot>
</x-sms-modal>

<!-- Confirmation Modal -->
<x-sms-modal id="confirm-modal" title="Confirm Action" size="sm" centered>
    <p>Are you sure you want to delete this template? This action cannot be undone.</p>
    
    <x-slot name="footer">
        <x-sms-button variant="secondary" onclick="hideSmsModal('confirm-modal')">Cancel</x-sms-button>
        <x-sms-button variant="danger">Delete</x-sms-button>
    </x-slot>
</x-sms-modal>

<!-- JavaScript Usage -->
<script>
// Show modal
showSmsModal('basic-modal');

// Hide modal
hideSmsModal('basic-modal');

// Access modal instance
const modal = window.smsModal_basic_modal;
modal.show();
modal.hide();
modal.setTitle('New Title');
modal.setBody('<p>New content</p>');

// Listen to modal events
document.getElementById('basic-modal').addEventListener('sms-modal:show', function() {
    console.log('Modal shown');
});

document.getElementById('basic-modal').addEventListener('sms-modal:hide', function() {
    console.log('Modal hidden');
});
</script>

--}}