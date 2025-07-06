{{--
/**
 * SMS Table Component
 * 
 * Comprehensive table component with sorting, filtering, pagination, and bulk actions
 * 
 * @param array $columns - Table column definitions
 * @param array $data - Table data
 * @param array $actions - Row action buttons
 * @param array $bulkActions - Bulk action options
 * @param bool $sortable - Enable sorting
 * @param bool $filterable - Enable filtering
 * @param bool $searchable - Enable search
 * @param bool $selectable - Enable row selection
 * @param string $emptyMessage - Message when no data
 * @param string $loadingMessage - Loading message
 * @param array $pagination - Pagination data
 * @param string $id - Table ID
 * @param string $class - Additional CSS classes
 */
--}}

@props([
    'columns' => [],
    'data' => [],
    'actions' => [],
    'bulkActions' => [],
    'sortable' => true,
    'filterable' => false,
    'searchable' => true,
    'selectable' => false,
    'emptyMessage' => 'No data available',
    'loadingMessage' => 'Loading...',
    'pagination' => null,
    'id' => 'sms-table-' . uniqid(),
    'class' => ''
])

@php
    $tableClasses = 'sms-table-wrapper ' . $class;
    $hasData = !empty($data);
    $totalColumns = count($columns) + ($selectable ? 1 : 0) + (!empty($actions) ? 1 : 0);
@endphp

<div class="{{ $tableClasses }}" id="{{ $id }}">
    {{-- Table Header with Search and Filters --}}
    @if($searchable || $filterable || !empty($bulkActions))
    <div class="sms-table__header">
        <div class="sms-table__header-left">
            @if($searchable)
            <div class="sms-table__search">
                <div class="sms-table__search-input">
                    <svg class="sms-table__search-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    <input type="text" 
                           class="sms-table__search-field" 
                           placeholder="Search..." 
                           data-table-search="{{ $id }}">
                </div>
            </div>
            @endif
            
            @if($filterable)
            <div class="sms-table__filters">
                <button type="button" class="sms-table__filter-toggle" data-table-filter-toggle="{{ $id }}">
                    <svg class="sms-table__filter-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                    </svg>
                    Filters
                </button>
            </div>
            @endif
        </div>
        
        <div class="sms-table__header-right">
            @if(!empty($bulkActions) && $selectable)
            <div class="sms-table__bulk-actions" style="display: none;">
                <select class="sms-table__bulk-select" data-table-bulk="{{ $id }}">
                    <option value="">Bulk Actions</option>
                    @foreach($bulkActions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" class="sms-table__bulk-apply" data-table-bulk-apply="{{ $id }}">
                    Apply
                </button>
            </div>
            @endif
            
            <div class="sms-table__actions">
                {{ $headerActions ?? '' }}
            </div>
        </div>
    </div>
    @endif
    
    {{-- Filter Panel --}}
    @if($filterable)
    <div class="sms-table__filter-panel" data-table-filters="{{ $id }}" style="display: none;">
        <div class="sms-table__filter-content">
            {{ $filters ?? '' }}
        </div>
        <div class="sms-table__filter-actions">
            <button type="button" class="sms-table__filter-clear" data-table-filter-clear="{{ $id }}">
                Clear Filters
            </button>
            <button type="button" class="sms-table__filter-apply" data-table-filter-apply="{{ $id }}">
                Apply Filters
            </button>
        </div>
    </div>
    @endif
    
    {{-- Table Container --}}
    <div class="sms-table__container">
        {{-- Loading Overlay --}}
        <div class="sms-table__loading" data-table-loading="{{ $id }}" style="display: none;">
            <div class="sms-table__spinner">
                <svg class="sms-table__spinner-icon" viewBox="0 0 24 24">
                    <circle class="sms-table__spinner-path" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                        <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                        <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
                    </circle>
                </svg>
                <span>{{ $loadingMessage }}</span>
            </div>
        </div>
        
        {{-- Table --}}
        <table class="sms-table" data-table="{{ $id }}">
            <thead class="sms-table__head">
                <tr class="sms-table__row sms-table__row--header">
                    @if($selectable)
                    <th class="sms-table__cell sms-table__cell--select">
                        <label class="sms-table__checkbox">
                            <input type="checkbox" 
                                   class="sms-table__checkbox-input" 
                                   data-table-select-all="{{ $id }}">
                            <span class="sms-table__checkbox-mark"></span>
                        </label>
                    </th>
                    @endif
                    
                    @foreach($columns as $key => $column)
                    @php
                        $columnConfig = is_array($column) ? $column : ['label' => $column];
                        $label = $columnConfig['label'] ?? $key;
                        $sortKey = $columnConfig['sort'] ?? $key;
                        $isSortable = $sortable && ($columnConfig['sortable'] ?? true);
                        $width = $columnConfig['width'] ?? null;
                        $align = $columnConfig['align'] ?? 'left';
                        $class = $columnConfig['class'] ?? '';
                    @endphp
                    
                    <th class="sms-table__cell sms-table__cell--header sms-table__cell--{{ $align }} {{ $class }}"
                        @if($width) style="width: {{ $width }}" @endif
                        @if($isSortable) data-table-sort="{{ $sortKey }}" @endif>
                        <div class="sms-table__header-content">
                            <span class="sms-table__header-label">{{ $label }}</span>
                            @if($isSortable)
                            <div class="sms-table__sort-icons">
                                <svg class="sms-table__sort-icon sms-table__sort-icon--asc" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>
                                <svg class="sms-table__sort-icon sms-table__sort-icon--desc" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            @endif
                        </div>
                    </th>
                    @endforeach
                    
                    @if(!empty($actions))
                    <th class="sms-table__cell sms-table__cell--header sms-table__cell--actions">
                        Actions
                    </th>
                    @endif
                </tr>
            </thead>
            
            <tbody class="sms-table__body">
                @if($hasData)
                    @foreach($data as $index => $row)
                    <tr class="sms-table__row sms-table__row--data" data-table-row="{{ $index }}">
                        @if($selectable)
                        <td class="sms-table__cell sms-table__cell--select">
                            <label class="sms-table__checkbox">
                                <input type="checkbox" 
                                       class="sms-table__checkbox-input" 
                                       data-table-select-row="{{ $index }}"
                                       value="{{ $row['id'] ?? $index }}">
                                <span class="sms-table__checkbox-mark"></span>
                            </label>
                        </td>
                        @endif
                        
                        @foreach($columns as $key => $column)
                        @php
                            $columnConfig = is_array($column) ? $column : ['label' => $column];
                            $align = $columnConfig['align'] ?? 'left';
                            $class = $columnConfig['class'] ?? '';
                            $format = $columnConfig['format'] ?? null;
                            $value = data_get($row, $key, '');
                            
                            // Format value based on type
                            if ($format === 'date' && $value) {
                                $value = \Carbon\Carbon::parse($value)->format('M d, Y');
                            } elseif ($format === 'datetime' && $value) {
                                $value = \Carbon\Carbon::parse($value)->format('M d, Y H:i');
                            } elseif ($format === 'number' && is_numeric($value)) {
                                $value = number_format($value);
                            } elseif ($format === 'currency' && is_numeric($value)) {
                                $value = '$' . number_format($value, 2);
                            } elseif ($format === 'badge' && $value) {
                                $badgeClass = $columnConfig['badgeClass'] ?? 'primary';
                                $value = '<span class="sms-badge sms-badge--' . $badgeClass . '">' . $value . '</span>';
                            }
                        @endphp
                        
                        <td class="sms-table__cell sms-table__cell--{{ $align }} {{ $class }}">
                            @if($format === 'badge')
                                {!! $value !!}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                        @endforeach
                        
                        @if(!empty($actions))
                        <td class="sms-table__cell sms-table__cell--actions">
                            <div class="sms-table__row-actions">
                                @foreach($actions as $action)
                                @php
                                    $actionConfig = is_array($action) ? $action : ['label' => $action];
                                    $label = $actionConfig['label'] ?? 'Action';
                                    $url = $actionConfig['url'] ?? '#';
                                    $method = $actionConfig['method'] ?? 'GET';
                                    $class = $actionConfig['class'] ?? 'sms-btn sms-btn--sm sms-btn--ghost';
                                    $icon = $actionConfig['icon'] ?? null;
                                    $confirm = $actionConfig['confirm'] ?? null;
                                    $condition = $actionConfig['condition'] ?? true;
                                    
                                    // Replace placeholders in URL
                                    if (is_string($url)) {
                                        foreach ($row as $rowKey => $rowValue) {
                                            $url = str_replace('{' . $rowKey . '}', $rowValue, $url);
                                        }
                                    }
                                @endphp
                                
                                @if($condition)
                                    @if($method === 'GET')
                                    <a href="{{ $url }}" 
                                       class="{{ $class }}"
                                       @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif>
                                        @if($icon)
                                        <svg class="sms-btn__icon" viewBox="0 0 20 20" fill="currentColor">
                                            {!! $icon !!}
                                        </svg>
                                        @endif
                                        {{ $label }}
                                    </a>
                                    @else
                                    <form method="POST" action="{{ $url }}" style="display: inline;">
                                        @csrf
                                        @if($method !== 'POST')
                                            @method($method)
                                        @endif
                                        <button type="submit" 
                                                class="{{ $class }}"
                                                @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif>
                                            @if($icon)
                                            <svg class="sms-btn__icon" viewBox="0 0 20 20" fill="currentColor">
                                                {!! $icon !!}
                                            </svg>
                                            @endif
                                            {{ $label }}
                                        </button>
                                    </form>
                                    @endif
                                @endif
                                @endforeach
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                @else
                    <tr class="sms-table__row sms-table__row--empty">
                        <td class="sms-table__cell sms-table__cell--empty" colspan="{{ $totalColumns }}">
                            <div class="sms-table__empty">
                                <svg class="sms-table__empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="m9 9 6 6"/>
                                    <path d="m15 9-6 6"/>
                                </svg>
                                <p class="sms-table__empty-message">{{ $emptyMessage }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    {{-- Pagination --}}
    @if($pagination && $hasData)
    <div class="sms-table__pagination">
        <div class="sms-table__pagination-info">
            Showing {{ $pagination['from'] ?? 1 }} to {{ $pagination['to'] ?? count($data) }} 
            of {{ $pagination['total'] ?? count($data) }} results
        </div>
        
        <div class="sms-table__pagination-controls">
            @if(isset($pagination['prev_page_url']) && $pagination['prev_page_url'])
            <a href="{{ $pagination['prev_page_url'] }}" class="sms-table__pagination-btn sms-table__pagination-btn--prev">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Previous
            </a>
            @endif
            
            @if(isset($pagination['links']))
                @foreach($pagination['links'] as $link)
                    @if($link['url'])
                    <a href="{{ $link['url'] }}" 
                       class="sms-table__pagination-btn {{ $link['active'] ? 'sms-table__pagination-btn--active' : '' }}">
                        {{ $link['label'] }}
                    </a>
                    @else
                    <span class="sms-table__pagination-btn sms-table__pagination-btn--disabled">
                        {{ $link['label'] }}
                    </span>
                    @endif
                @endforeach
            @endif
            
            @if(isset($pagination['next_page_url']) && $pagination['next_page_url'])
            <a href="{{ $pagination['next_page_url'] }}" class="sms-table__pagination-btn sms-table__pagination-btn--next">
                Next
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Component Styles --}}
<style>
/* Table Wrapper */
.sms-table-wrapper {
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: var(--border-radius, 8px);
    overflow: hidden;
    box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
}

/* Table Header */
.sms-table__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--table-header-bg, #f9fafb);
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    gap: 1rem;
}

.sms-table__header-left,
.sms-table__header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Search */
.sms-table__search-input {
    position: relative;
    display: flex;
    align-items: center;
}

.sms-table__search-icon {
    position: absolute;
    left: 0.75rem;
    width: 1rem;
    height: 1rem;
    color: var(--text-muted, #9ca3af);
    z-index: 1;
}

.sms-table__search-field {
    padding: 0.5rem 0.75rem 0.5rem 2.5rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.875rem;
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    min-width: 250px;
    transition: border-color 0.2s ease;
}

.sms-table__search-field:focus {
    outline: 0;
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Filters */
.sms-table__filter-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sms-table__filter-toggle:hover {
    background: var(--hover-bg, #f3f4f6);
}

.sms-table__filter-icon {
    width: 1rem;
    height: 1rem;
}

.sms-table__filter-panel {
    padding: 1rem 1.5rem;
    background: var(--filter-panel-bg, #f9fafb);
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.sms-table__filter-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.sms-table__filter-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.sms-table__filter-clear,
.sms-table__filter-apply {
    padding: 0.5rem 1rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sms-table__filter-clear {
    background: var(--input-bg, #ffffff);
    color: var(--text-secondary, #6b7280);
}

.sms-table__filter-apply {
    background: var(--primary-color, #3b82f6);
    color: white;
    border-color: var(--primary-color, #3b82f6);
}

/* Bulk Actions */
.sms-table__bulk-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sms-table__bulk-select {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.875rem;
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
}

.sms-table__bulk-apply {
    padding: 0.5rem 1rem;
    background: var(--primary-color, #3b82f6);
    color: white;
    border: 1px solid var(--primary-color, #3b82f6);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 0.875rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.sms-table__bulk-apply:hover {
    background: var(--primary-dark, #2563eb);
}

/* Table Container */
.sms-table__container {
    position: relative;
    overflow-x: auto;
}

/* Loading */
.sms-table__loading {
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
}

.sms-table__spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--primary-color, #3b82f6);
}

.sms-table__spinner-icon {
    width: 2rem;
    height: 2rem;
    animation: spin 1s linear infinite;
}

/* Table */
.sms-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.sms-table__head {
    background: var(--table-header-bg, #f9fafb);
}

.sms-table__row {
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.sms-table__row--data:hover {
    background: var(--table-row-hover, #f9fafb);
}

.sms-table__row--empty {
    background: transparent;
}

.sms-table__cell {
    padding: 0.75rem 1rem;
    text-align: left;
    vertical-align: middle;
    color: var(--text-primary, #111827);
}

.sms-table__cell--header {
    font-weight: 600;
    color: var(--text-secondary, #6b7280);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.sms-table__cell--center {
    text-align: center;
}

.sms-table__cell--right {
    text-align: right;
}

.sms-table__cell--select {
    width: 3rem;
    text-align: center;
}

.sms-table__cell--actions {
    width: auto;
    white-space: nowrap;
}

.sms-table__cell--empty {
    text-align: center;
    padding: 3rem 1rem;
}

/* Header Content */
.sms-table__header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
}

.sms-table__header-label {
    flex: 1;
}

.sms-table__sort-icons {
    display: flex;
    flex-direction: column;
    margin-left: 0.5rem;
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.sms-table__header-content:hover .sms-table__sort-icons {
    opacity: 1;
}

.sms-table__sort-icon {
    width: 0.75rem;
    height: 0.75rem;
    color: var(--text-muted, #9ca3af);
}

.sms-table__sort-icon--active {
    color: var(--primary-color, #3b82f6);
}

/* Checkbox */
.sms-table__checkbox {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.sms-table__checkbox-input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.sms-table__checkbox-mark {
    width: 1rem;
    height: 1rem;
    border: 1px solid var(--input-border, #d1d5db);
    border-radius: var(--border-radius-sm, 3px);
    background: var(--input-bg, #ffffff);
    position: relative;
    transition: all 0.2s ease;
}

.sms-table__checkbox-input:checked + .sms-table__checkbox-mark {
    background: var(--primary-color, #3b82f6);
    border-color: var(--primary-color, #3b82f6);
}

.sms-table__checkbox-input:checked + .sms-table__checkbox-mark::after {
    content: '';
    position: absolute;
    left: 0.25rem;
    top: 0.125rem;
    width: 0.25rem;
    height: 0.5rem;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

/* Row Actions */
.sms-table__row-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

/* Empty State */
.sms-table__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--text-muted, #9ca3af);
}

.sms-table__empty-icon {
    width: 3rem;
    height: 3rem;
    opacity: 0.5;
}

.sms-table__empty-message {
    font-size: 1rem;
    font-weight: 500;
    margin: 0;
}

/* Pagination */
.sms-table__pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--table-footer-bg, #f9fafb);
    border-top: 1px solid var(--border-color, #e5e7eb);
}

.sms-table__pagination-info {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

.sms-table__pagination-controls {
    display: flex;
    gap: 0.25rem;
    align-items: center;
}

.sms-table__pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--input-border, #d1d5db);
    background: var(--input-bg, #ffffff);
    color: var(--text-primary, #111827);
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: var(--border-radius-sm, 4px);
    transition: all 0.2s ease;
}

.sms-table__pagination-btn:hover {
    background: var(--hover-bg, #f3f4f6);
}

.sms-table__pagination-btn--active {
    background: var(--primary-color, #3b82f6);
    color: white;
    border-color: var(--primary-color, #3b82f6);
}

.sms-table__pagination-btn--disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.sms-table__pagination-btn svg {
    width: 1rem;
    height: 1rem;
}

/* Badge */
.sms-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: var(--border-radius-full, 9999px);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.sms-badge--primary {
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary-color, #3b82f6);
}

.sms-badge--success {
    background: rgba(34, 197, 94, 0.1);
    color: var(--success-color, #22c55e);
}

.sms-badge--warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color, #f59e0b);
}

.sms-badge--danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color, #ef4444);
}

.sms-badge--secondary {
    background: rgba(107, 114, 128, 0.1);
    color: var(--text-secondary, #6b7280);
}

/* Dark Mode */
[data-theme="dark"] .sms-table-wrapper {
    background: var(--dark-card-bg, #1f2937);
    border-color: var(--dark-border-color, #374151);
}

[data-theme="dark"] .sms-table__header,
[data-theme="dark"] .sms-table__head,
[data-theme="dark"] .sms-table__pagination {
    background: var(--dark-table-header-bg, #374151);
    border-color: var(--dark-border-color, #4b5563);
}

[data-theme="dark"] .sms-table__row {
    border-color: var(--dark-border-color, #374151);
}

[data-theme="dark"] .sms-table__row--data:hover {
    background: var(--dark-table-row-hover, #374151);
}

[data-theme="dark"] .sms-table__cell {
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-table__cell--header {
    color: var(--dark-text-secondary, #d1d5db);
}

[data-theme="dark"] .sms-table__search-field,
[data-theme="dark"] .sms-table__filter-toggle,
[data-theme="dark"] .sms-table__bulk-select {
    background: var(--dark-input-bg, #374151);
    border-color: var(--dark-input-border, #4b5563);
    color: var(--dark-text-primary, #f9fafb);
}

[data-theme="dark"] .sms-table__loading {
    background: rgba(31, 41, 55, 0.9);
}

/* Responsive */
@media (max-width: 768px) {
    .sms-table__header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .sms-table__header-left,
    .sms-table__header-right {
        justify-content: space-between;
    }
    
    .sms-table__search-field {
        min-width: auto;
        width: 100%;
    }
    
    .sms-table__pagination {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .sms-table__pagination-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .sms-table__row-actions {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .sms-table__cell--actions {
        white-space: normal;
    }
}

@media (max-width: 640px) {
    .sms-table__container {
        overflow-x: scroll;
    }
    
    .sms-table {
        min-width: 600px;
    }
}
</style>

{{-- Table JavaScript --}}
<script>
class SmsTableComponent {
    constructor(tableWrapper) {
        this.wrapper = tableWrapper;
        this.table = tableWrapper.querySelector('.sms-table');
        this.id = tableWrapper.id;
        this.currentSort = { column: null, direction: 'asc' };
        this.selectedRows = new Set();
        this.searchTimeout = null;
        
        this.init();
    }
    
    init() {
        this.initSorting();
        this.initSelection();
        this.initSearch();
        this.initFilters();
        this.initBulkActions();
    }
    
    initSorting() {
        const sortableHeaders = this.wrapper.querySelectorAll('[data-table-sort]');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', (e) => {
                const column = header.dataset.tableSort;
                this.sort(column);
            });
        });
    }
    
    sort(column) {
        let direction = 'asc';
        
        if (this.currentSort.column === column) {
            direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        }
        
        this.currentSort = { column, direction };
        
        // Update UI
        this.updateSortIcons(column, direction);
        
        // Trigger sort event
        this.triggerEvent('sort', { column, direction });
        
        // If no custom handler, sort locally
        if (!this.hasCustomHandler('sort')) {
            this.sortLocally(column, direction);
        }
    }
    
    updateSortIcons(activeColumn, direction) {
        const headers = this.wrapper.querySelectorAll('[data-table-sort]');
        headers.forEach(header => {
            const icons = header.querySelectorAll('.sms-table__sort-icon');
            icons.forEach(icon => icon.classList.remove('sms-table__sort-icon--active'));
            
            if (header.dataset.tableSort === activeColumn) {
                const activeIcon = header.querySelector(
                    `.sms-table__sort-icon--${direction}`
                );
                if (activeIcon) {
                    activeIcon.classList.add('sms-table__sort-icon--active');
                }
            }
        });
    }
    
    sortLocally(column, direction) {
        const tbody = this.table.querySelector('.sms-table__body');
        const rows = Array.from(tbody.querySelectorAll('.sms-table__row--data'));
        
        rows.sort((a, b) => {
            const aValue = this.getCellValue(a, column);
            const bValue = this.getCellValue(b, column);
            
            let comparison = 0;
            if (aValue > bValue) comparison = 1;
            if (aValue < bValue) comparison = -1;
            
            return direction === 'desc' ? -comparison : comparison;
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }
    
    getCellValue(row, column) {
        const columnIndex = this.getColumnIndex(column);
        const cell = row.children[columnIndex];
        return cell ? cell.textContent.trim() : '';
    }
    
    getColumnIndex(column) {
        const headers = this.wrapper.querySelectorAll('[data-table-sort]');
        for (let i = 0; i < headers.length; i++) {
            if (headers[i].dataset.tableSort === column) {
                return i + (this.hasSelection() ? 1 : 0);
            }
        }
        return 0;
    }
    
    initSelection() {
        const selectAll = this.wrapper.querySelector('[data-table-select-all]');
        const selectRows = this.wrapper.querySelectorAll('[data-table-select-row]');
        
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                this.selectAll(e.target.checked);
            });
        }
        
        selectRows.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.selectRow(e.target.value, e.target.checked);
            });
        });
    }
    
    selectAll(checked) {
        const selectRows = this.wrapper.querySelectorAll('[data-table-select-row]');
        selectRows.forEach(checkbox => {
            checkbox.checked = checked;
            this.selectRow(checkbox.value, checked);
        });
    }
    
    selectRow(value, checked) {
        if (checked) {
            this.selectedRows.add(value);
        } else {
            this.selectedRows.delete(value);
        }
        
        this.updateSelectionUI();
        this.triggerEvent('selectionChange', {
            selected: Array.from(this.selectedRows),
            count: this.selectedRows.size
        });
    }
    
    updateSelectionUI() {
        const selectAll = this.wrapper.querySelector('[data-table-select-all]');
        const bulkActions = this.wrapper.querySelector('.sms-table__bulk-actions');
        const totalRows = this.wrapper.querySelectorAll('[data-table-select-row]').length;
        
        if (selectAll) {
            selectAll.checked = this.selectedRows.size === totalRows && totalRows > 0;
            selectAll.indeterminate = this.selectedRows.size > 0 && this.selectedRows.size < totalRows;
        }
        
        if (bulkActions) {
            bulkActions.style.display = this.selectedRows.size > 0 ? 'flex' : 'none';
        }
    }
    
    initSearch() {
        const searchField = this.wrapper.querySelector('[data-table-search]');
        if (searchField) {
            searchField.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.search(e.target.value);
                }, 300);
            });
        }
    }
    
    search(query) {
        this.triggerEvent('search', { query });
        
        if (!this.hasCustomHandler('search')) {
            this.searchLocally(query);
        }
    }
    
    searchLocally(query) {
        const rows = this.wrapper.querySelectorAll('.sms-table__row--data');
        const searchTerm = query.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm);
            row.style.display = matches ? '' : 'none';
        });
    }
    
    initFilters() {
        const filterToggle = this.wrapper.querySelector('[data-table-filter-toggle]');
        const filterPanel = this.wrapper.querySelector('[data-table-filters]');
        const filterApply = this.wrapper.querySelector('[data-table-filter-apply]');
        const filterClear = this.wrapper.querySelector('[data-table-filter-clear]');
        
        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', () => {
                const isVisible = filterPanel.style.display !== 'none';
                filterPanel.style.display = isVisible ? 'none' : 'block';
            });
        }
        
        if (filterApply) {
            filterApply.addEventListener('click', () => {
                this.applyFilters();
            });
        }
        
        if (filterClear) {
            filterClear.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }
    
    applyFilters() {
        const filterPanel = this.wrapper.querySelector('[data-table-filters]');
        if (!filterPanel) return;
        
        const filters = {};
        const inputs = filterPanel.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.value) {
                filters[input.name] = input.value;
            }
        });
        
        this.triggerEvent('filter', { filters });
    }
    
    clearFilters() {
        const filterPanel = this.wrapper.querySelector('[data-table-filters]');
        if (!filterPanel) return;
        
        const inputs = filterPanel.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.value = '';
        });
        
        this.triggerEvent('filter', { filters: {} });
    }
    
    initBulkActions() {
        const bulkApply = this.wrapper.querySelector('[data-table-bulk-apply]');
        if (bulkApply) {
            bulkApply.addEventListener('click', () => {
                this.applyBulkAction();
            });
        }
    }
    
    applyBulkAction() {
        const bulkSelect = this.wrapper.querySelector('[data-table-bulk]');
        if (!bulkSelect || !bulkSelect.value) return;
        
        const action = bulkSelect.value;
        const selected = Array.from(this.selectedRows);
        
        this.triggerEvent('bulkAction', { action, selected });
        
        // Reset selection
        bulkSelect.value = '';
    }
    
    showLoading() {
        const loading = this.wrapper.querySelector('[data-table-loading]');
        if (loading) {
            loading.style.display = 'flex';
        }
    }
    
    hideLoading() {
        const loading = this.wrapper.querySelector('[data-table-loading]');
        if (loading) {
            loading.style.display = 'none';
        }
    }
    
    triggerEvent(eventName, data) {
        const event = new CustomEvent(`smsTable:${eventName}`, {
            detail: { tableId: this.id, ...data }
        });
        this.wrapper.dispatchEvent(event);
    }
    
    hasCustomHandler(eventName) {
        return this.wrapper.hasAttribute(`data-table-${eventName}-handler`);
    }
    
    hasSelection() {
        return this.wrapper.querySelector('[data-table-select-all]') !== null;
    }
    
    // Public API
    refresh() {
        this.triggerEvent('refresh', {});
    }
    
    getSelected() {
        return Array.from(this.selectedRows);
    }
    
    clearSelection() {
        this.selectedRows.clear();
        const checkboxes = this.wrapper.querySelectorAll('[data-table-select-row], [data-table-select-all]');
        checkboxes.forEach(cb => cb.checked = false);
        this.updateSelectionUI();
    }
}

// Auto-initialize tables
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.sms-table-wrapper');
    tables.forEach(table => {
        new SmsTableComponent(table);
    });
});

// Global table utilities
window.SmsTable = {
    getInstance: function(id) {
        const wrapper = document.getElementById(id);
        return wrapper ? wrapper.smsTableInstance : null;
    },
    
    refresh: function(id) {
        const instance = this.getInstance(id);
        if (instance) instance.refresh();
    },
    
    getSelected: function(id) {
        const instance = this.getInstance(id);
        return instance ? instance.getSelected() : [];
    }
};
</script>

{{-- Usage Examples:

<!-- Basic Table -->
<x-sms-table 
    :columns="[
        'name' => 'Template Name',
        'status' => ['label' => 'Status', 'format' => 'badge', 'badgeClass' => 'success'],
        'created_at' => ['label' => 'Created', 'format' => 'date']
    ]"
    :data="$templates"
    :actions="[
        ['label' => 'Edit', 'url' => '/templates/{id}/edit', 'class' => 'sms-btn sms-btn--sm sms-btn--primary'],
        ['label' => 'Delete', 'url' => '/templates/{id}', 'method' => 'DELETE', 'confirm' => 'Are you sure?', 'class' => 'sms-btn sms-btn--sm sms-btn--danger']
    ]"
    searchable="true"
    sortable="true" />

<!-- Advanced Table with Bulk Actions -->
<x-sms-table 
    :columns="[
        'name' => 'Campaign Name',
        'recipients' => ['label' => 'Recipients', 'format' => 'number', 'align' => 'center'],
        'status' => ['label' => 'Status', 'format' => 'badge'],
        'scheduled_at' => ['label' => 'Scheduled', 'format' => 'datetime']
    ]"
    :data="$campaigns"
    :bulkActions="[
        'activate' => 'Activate Selected',
        'deactivate' => 'Deactivate Selected',
        'delete' => 'Delete Selected'
    ]"
    selectable="true"
    filterable="true"
    :pagination="$campaigns->toArray()">
    
    <x-slot name="headerActions">
        <x-sms-button href="{{ route('campaigns.create') }}" variant="primary">
            New Campaign
        </x-sms-button>
    </x-slot>
    
    <x-slot name="filters">
        <div class="sms-form__field">
            <label class="sms-form__label">Status</label>
            <select name="status" class="sms-form__select">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        
        <div class="sms-form__field">
            <label class="sms-form__label">Date Range</label>
            <input type="date" name="date_from" class="sms-form__input">
        </div>
    </x-slot>
</x-sms-table>

<!-- Event Handling -->
<script>
document.addEventListener('smsTable:sort', function(e) {
    console.log('Sort:', e.detail);
    // Handle server-side sorting
});

document.addEventListener('smsTable:search', function(e) {
    console.log('Search:', e.detail);
    // Handle server-side search
});

document.addEventListener('smsTable:bulkAction', function(e) {
    console.log('Bulk Action:', e.detail);
    // Handle bulk actions
});
</script>

--}}