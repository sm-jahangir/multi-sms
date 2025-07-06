/**
 * SMS Dashboard JavaScript
 * 
 * এই JavaScript file এ সব SMS functionality এর জন্য common functions, utilities এবং components আছে।
 * সব views এ reusable functions এবং proper error handling included।
 */

// ==================== Global Configuration ====================
const SmsConfig = {
    // API endpoints
    endpoints: {
        sendTest: '/sms/api/send-test',
        getTemplates: '/sms/api/templates',
        getTemplate: '/sms/api/templates/{id}',
        previewTemplate: '/sms/api/templates/{id}/preview',
        getCampaigns: '/sms/api/campaigns',
        getCampaignStatus: '/sms/api/campaigns/{id}/status',
        getAutoresponders: '/sms/api/autoresponders',
        testTrigger: '/sms/api/autoresponders/test-trigger',
        getDashboardData: '/sms/api/dashboard',
        getChartData: '/sms/api/charts/{type}',
        getDrivers: '/sms/api/drivers',
        getSystemStatus: '/sms/api/system/status',
        validatePhone: '/sms/api/validate-phone',
        detectVariables: '/sms/api/detect-variables'
    },
    
    // Default settings
    defaults: {
        pagination: {
            perPage: 15,
            maxPages: 10
        },
        refresh: {
            interval: 30000, // 30 seconds
            charts: 60000    // 1 minute
        },
        validation: {
            phoneRegex: /^\+?[1-9]\d{1,14}$/,
            maxSmsLength: 1600,
            smsSegmentLength: 160
        },
        animation: {
            duration: 300,
            easing: 'ease-in-out'
        }
    },
    
    // Current state
    state: {
        currentPage: 'dashboard',
        filters: {},
        selectedItems: [],
        isLoading: false,
        refreshIntervals: {}
    }
};

// ==================== Utility Functions ====================

/**
 * SMS Utilities Class
 * Common utility functions যা সব জায়গায় ব্যবহার হয়
 */
class SmsUtils {
    /**
     * Make AJAX request with proper error handling
     */
    static async makeRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }
    
    /**
     * Show loading indicator
     */
    static showLoading(element, text = 'Loading...') {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.innerHTML = `
                <div class="sms-loading">
                    <div class="sms-spinner"></div>
                    <span>${text}</span>
                </div>
            `;
        }
    }
    
    /**
     * Hide loading indicator
     */
    static hideLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            const loading = element.querySelector('.sms-loading');
            if (loading) {
                loading.remove();
            }
        }
    }
    
    /**
     * Show alert message
     */
    static showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('sms-alerts') || document.body;
        
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="sms-alert sms-alert-${type}" style="margin-bottom: 1rem;">
                <div class="sms-alert-icon">
                    ${this.getAlertIcon(type)}
                </div>
                <div class="sms-alert-content">
                    <div class="sms-alert-message">${message}</div>
                </div>
                <button type="button" class="sms-alert-close" onclick="SmsUtils.closeAlert('${alertId}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                this.closeAlert(alertId);
            }, duration);
        }
        
        return alertId;
    }
    
    /**
     * Close alert
     */
    static closeAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    }
    
    /**
     * Get alert icon based on type
     */
    static getAlertIcon(type) {
        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"></polyline></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            danger: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };
        
        return icons[type] || icons.info;
    }
    
    /**
     * Format phone number
     */
    static formatPhoneNumber(phone) {
        if (!phone) return '';
        
        // Remove all non-digit characters except +
        let cleaned = phone.replace(/[^+\d]/g, '');
        
        // Add + if not present
        if (!cleaned.startsWith('+')) {
            cleaned = '+' + cleaned;
        }
        
        return cleaned;
    }
    
    /**
     * Validate phone number
     */
    static validatePhoneNumber(phone) {
        const cleaned = this.formatPhoneNumber(phone);
        return SmsConfig.defaults.validation.phoneRegex.test(cleaned);
    }
    
    /**
     * Calculate SMS count based on character length
     */
    static calculateSmsCount(text) {
        if (!text) return 0;
        
        const length = text.length;
        const segmentLength = SmsConfig.defaults.validation.smsSegmentLength;
        
        if (length <= segmentLength) {
            return 1;
        } else if (length <= 306) {
            return 2;
        } else {
            return Math.ceil(length / 153); // Multipart SMS segments are 153 chars
        }
    }
    
    /**
     * Extract variables from text
     */
    static extractVariables(text) {
        if (!text) return [];
        
        const regex = /{{\s*(\w+)\s*}}/g;
        const variables = [];
        let match;
        
        while ((match = regex.exec(text)) !== null) {
            if (!variables.includes(match[1])) {
                variables.push(match[1]);
            }
        }
        
        return variables;
    }
    
    /**
     * Process variables in text
     */
    static processVariables(text, variables = {}) {
        if (!text) return '';
        
        let processed = text;
        
        Object.keys(variables).forEach(key => {
            const value = variables[key] || '';
            processed = processed.replace(new RegExp(`{{\\s*${key}\\s*}}`, 'g'), value);
        });
        
        return processed;
    }
    
    /**
     * Format date
     */
    static formatDate(date, format = 'Y-m-d H:i:s') {
        if (!date) return '';
        
        const d = new Date(date);
        
        const formats = {
            'Y': d.getFullYear(),
            'm': String(d.getMonth() + 1).padStart(2, '0'),
            'd': String(d.getDate()).padStart(2, '0'),
            'H': String(d.getHours()).padStart(2, '0'),
            'i': String(d.getMinutes()).padStart(2, '0'),
            's': String(d.getSeconds()).padStart(2, '0')
        };
        
        return format.replace(/[Ymdis]/g, match => formats[match]);
    }
    
    /**
     * Format relative time (time ago)
     */
    static timeAgo(date) {
        if (!date) return '';
        
        const now = new Date();
        const past = new Date(date);
        const diffInSeconds = Math.floor((now - past) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, seconds] of Object.entries(intervals)) {
            const interval = Math.floor(diffInSeconds / seconds);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    }
    
    /**
     * Format number with commas
     */
    static formatNumber(number) {
        if (number === null || number === undefined) return '0';
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Format percentage
     */
    static formatPercentage(value, decimals = 1) {
        if (value === null || value === undefined) return '0%';
        return `${parseFloat(value).toFixed(decimals)}%`;
    }
    
    /**
     * Debounce function
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle function
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Copy text to clipboard
     */
    static async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showAlert('Copied to clipboard!', 'success', 2000);
            return true;
        } catch (err) {
            console.error('Failed to copy text: ', err);
            this.showAlert('Failed to copy to clipboard', 'danger', 3000);
            return false;
        }
    }
    
    /**
     * Download data as file
     */
    static downloadFile(data, filename, type = 'text/plain') {
        const blob = new Blob([data], { type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
    
    /**
     * Parse CSV data
     */
    static parseCSV(csvText) {
        const lines = csvText.split('\n');
        const result = [];
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line) {
                const values = line.split(',').map(value => value.trim().replace(/^"|"$/g, ''));
                result.push(values);
            }
        }
        
        return result;
    }
    
    /**
     * Generate CSV from array data
     */
    static generateCSV(data, headers = []) {
        let csv = '';
        
        // Add headers if provided
        if (headers.length > 0) {
            csv += headers.join(',') + '\n';
        }
        
        // Add data rows
        data.forEach(row => {
            const values = Array.isArray(row) ? row : Object.values(row);
            csv += values.map(value => `"${value}"`).join(',') + '\n';
        });
        
        return csv;
    }
    
    /**
     * Validate email address
     */
    static validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Generate random ID
     */
    static generateId(prefix = 'sms') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }
    
    /**
     * Get URL parameter
     */
    static getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    
    /**
     * Set URL parameter
     */
    static setUrlParameter(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    }
    
    /**
     * Remove URL parameter
     */
    static removeUrlParameter(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.pushState({}, '', url);
    }
}

// ==================== SMS API Class ====================

/**
 * SMS API Class
 * API calls এর জন্য dedicated class
 */
class SmsApi {
    /**
     * Send test SMS
     */
    static async sendTest(data) {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.sendTest, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Get templates list
     */
    static async getTemplates(params = {}) {
        const url = new URL(SmsConfig.endpoints.getTemplates, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        
        return await SmsUtils.makeRequest(url.toString());
    }
    
    /**
     * Get single template
     */
    static async getTemplate(templateId) {
        const url = SmsConfig.endpoints.getTemplate.replace('{id}', templateId);
        return await SmsUtils.makeRequest(url);
    }
    
    /**
     * Preview template with variables
     */
    static async previewTemplate(templateId, variables = {}) {
        const url = SmsConfig.endpoints.previewTemplate.replace('{id}', templateId);
        return await SmsUtils.makeRequest(url, {
            method: 'POST',
            body: JSON.stringify({ variables })
        });
    }
    
    /**
     * Get campaigns list
     */
    static async getCampaigns(params = {}) {
        const url = new URL(SmsConfig.endpoints.getCampaigns, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        
        return await SmsUtils.makeRequest(url.toString());
    }
    
    /**
     * Get campaign status
     */
    static async getCampaignStatus(campaignId) {
        const url = SmsConfig.endpoints.getCampaignStatus.replace('{id}', campaignId);
        return await SmsUtils.makeRequest(url);
    }
    
    /**
     * Get autoresponders list
     */
    static async getAutoresponders(params = {}) {
        const url = new URL(SmsConfig.endpoints.getAutoresponders, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        
        return await SmsUtils.makeRequest(url.toString());
    }
    
    /**
     * Test autoresponder trigger
     */
    static async testTrigger(data) {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.testTrigger, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Get dashboard data
     */
    static async getDashboardData(period = '7d') {
        const url = new URL(SmsConfig.endpoints.getDashboardData, window.location.origin);
        url.searchParams.append('period', period);
        
        return await SmsUtils.makeRequest(url.toString());
    }
    
    /**
     * Get chart data
     */
    static async getChartData(type, period = '7d') {
        const url = SmsConfig.endpoints.getChartData.replace('{type}', type);
        const fullUrl = new URL(url, window.location.origin);
        fullUrl.searchParams.append('period', period);
        
        return await SmsUtils.makeRequest(fullUrl.toString());
    }
    
    /**
     * Get drivers status
     */
    static async getDrivers() {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.getDrivers);
    }
    
    /**
     * Get system status
     */
    static async getSystemStatus() {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.getSystemStatus);
    }
    
    /**
     * Validate phone number
     */
    static async validatePhone(phone) {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.validatePhone, {
            method: 'POST',
            body: JSON.stringify({ phone })
        });
    }
    
    /**
     * Detect variables in content
     */
    static async detectVariables(content) {
        return await SmsUtils.makeRequest(SmsConfig.endpoints.detectVariables, {
            method: 'POST',
            body: JSON.stringify({ content })
        });
    }
}

// ==================== SMS Components ====================

/**
 * SMS Modal Component
 * Reusable modal component
 */
class SmsModal {
    constructor(options = {}) {
        this.options = {
            id: SmsUtils.generateId('modal'),
            title: 'Modal',
            size: 'md', // sm, md, lg, xl
            backdrop: true,
            keyboard: true,
            ...options
        };
        
        this.element = null;
        this.backdrop = null;
        this.isVisible = false;
        
        this.create();
    }
    
    create() {
        // Create backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'sms-modal-backdrop';
        this.backdrop.style.display = 'none';
        
        // Create modal
        this.element = document.createElement('div');
        this.element.className = 'sms-modal';
        this.element.id = this.options.id;
        this.element.style.display = 'none';
        
        const sizeClass = this.options.size !== 'md' ? ` sms-modal-${this.options.size}` : '';
        
        this.element.innerHTML = `
            <div class="sms-modal-dialog${sizeClass}">
                <div class="sms-modal-header">
                    <h5 class="sms-modal-title">${this.options.title}</h5>
                    <button type="button" class="sms-modal-close" data-dismiss="modal">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="sms-modal-body">
                    <!-- Content will be inserted here -->
                </div>
                <div class="sms-modal-footer" style="display: none;">
                    <!-- Footer content will be inserted here -->
                </div>
            </div>
        `;
        
        // Add event listeners
        this.addEventListeners();
        
        // Append to body
        document.body.appendChild(this.backdrop);
        document.body.appendChild(this.element);
    }
    
    addEventListeners() {
        // Close button
        const closeBtn = this.element.querySelector('.sms-modal-close');
        closeBtn.addEventListener('click', () => this.hide());
        
        // Backdrop click
        if (this.options.backdrop) {
            this.backdrop.addEventListener('click', () => this.hide());
        }
        
        // Keyboard events
        if (this.options.keyboard) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible) {
                    this.hide();
                }
            });
        }
    }
    
    setTitle(title) {
        const titleElement = this.element.querySelector('.sms-modal-title');
        titleElement.textContent = title;
        return this;
    }
    
    setBody(content) {
        const bodyElement = this.element.querySelector('.sms-modal-body');
        if (typeof content === 'string') {
            bodyElement.innerHTML = content;
        } else {
            bodyElement.innerHTML = '';
            bodyElement.appendChild(content);
        }
        return this;
    }
    
    setFooter(content) {
        const footerElement = this.element.querySelector('.sms-modal-footer');
        if (content) {
            if (typeof content === 'string') {
                footerElement.innerHTML = content;
            } else {
                footerElement.innerHTML = '';
                footerElement.appendChild(content);
            }
            footerElement.style.display = 'flex';
        } else {
            footerElement.style.display = 'none';
        }
        return this;
    }
    
    show() {
        this.backdrop.style.display = 'block';
        this.element.style.display = 'flex';
        
        // Trigger reflow
        this.backdrop.offsetHeight;
        this.element.offsetHeight;
        
        // Add show classes
        this.backdrop.classList.add('show');
        this.element.classList.add('show');
        
        this.isVisible = true;
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        return this;
    }
    
    hide() {
        this.backdrop.classList.remove('show');
        this.element.classList.remove('show');
        
        setTimeout(() => {
            this.backdrop.style.display = 'none';
            this.element.style.display = 'none';
            this.isVisible = false;
            
            // Restore body scroll
            document.body.style.overflow = '';
        }, 150);
        
        return this;
    }
    
    destroy() {
        this.hide();
        setTimeout(() => {
            if (this.backdrop && this.backdrop.parentNode) {
                this.backdrop.parentNode.removeChild(this.backdrop);
            }
            if (this.element && this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
        }, 150);
    }
}

/**
 * SMS Pagination Component
 * Reusable pagination component
 */
class SmsPagination {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            currentPage: 1,
            totalPages: 1,
            maxVisible: 5,
            showFirst: true,
            showLast: true,
            showPrevNext: true,
            onPageChange: null,
            ...options
        };
        
        this.render();
    }
    
    render() {
        if (!this.container) return;
        
        const { currentPage, totalPages, maxVisible, showFirst, showLast, showPrevNext } = this.options;
        
        if (totalPages <= 1) {
            this.container.innerHTML = '';
            return;
        }
        
        let html = '<nav class="sms-pagination"><ul class="pagination">';
        
        // First page
        if (showFirst && currentPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">First</a></li>`;
        }
        
        // Previous page
        if (showPrevNext && currentPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        const endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? ' active' : '';
            html += `<li class="page-item${activeClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        // Next page
        if (showPrevNext && currentPage < totalPages) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`;
        }
        
        // Last page
        if (showLast && currentPage < totalPages) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">Last</a></li>`;
        }
        
        html += '</ul></nav>';
        
        this.container.innerHTML = html;
        
        // Add event listeners
        this.container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                if (page !== currentPage && this.options.onPageChange) {
                    this.options.onPageChange(page);
                }
            });
        });
    }
    
    update(currentPage, totalPages) {
        this.options.currentPage = currentPage;
        this.options.totalPages = totalPages;
        this.render();
    }
}

// ==================== Global Event Handlers ====================

/**
 * Initialize SMS Dashboard
 */
function initSmsDashboard() {
    // Set up CSRF token for all AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        // Set default headers for fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options.headers = {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            };
            return originalFetch(url, options);
        };
    }
    
    // Initialize sidebar toggle
    initSidebarToggle();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize auto-refresh
    initAutoRefresh();
    
    console.log('SMS Dashboard initialized');
}

/**
 * Initialize sidebar toggle functionality
 */
function initSidebarToggle() {
    const toggleBtn = document.querySelector('[data-toggle="sidebar"]');
    const sidebar = document.querySelector('.sms-sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sms-sidebar-collapsed', isCollapsed);
        });
        
        // Restore state from localStorage
        const isCollapsed = localStorage.getItem('sms-sidebar-collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    // Simple tooltip implementation
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (!text) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'sms-tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        pointer-events: none;
        white-space: nowrap;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    e.target._tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.remove();
        delete e.target._tooltip;
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', validateForm);
    });
}

function validateForm(e) {
    const form = e.target;
    let isValid = true;
    
    // Clear previous errors
    form.querySelectorAll('.sms-form-error').forEach(error => error.remove());
    form.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
    
    // Validate required fields
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        }
    });
    
    // Validate phone numbers
    form.querySelectorAll('input[type="tel"], input[data-type="phone"]').forEach(input => {
        if (input.value && !SmsUtils.validatePhoneNumber(input.value)) {
            showFieldError(input, 'Please enter a valid phone number');
            isValid = false;
        }
    });
    
    // Validate emails
    form.querySelectorAll('input[type="email"]').forEach(input => {
        if (input.value && !SmsUtils.validateEmail(input.value)) {
            showFieldError(input, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
    }
}

function showFieldError(input, message) {
    input.classList.add('is-invalid');
    
    const error = document.createElement('div');
    error.className = 'sms-form-error';
    error.textContent = message;
    
    input.parentNode.appendChild(error);
}

/**
 * Initialize auto-refresh functionality
 */
function initAutoRefresh() {
    // Auto-refresh dashboard data
    if (document.querySelector('[data-auto-refresh]')) {
        const interval = parseInt(document.querySelector('[data-auto-refresh]').getAttribute('data-auto-refresh')) || 30000;
        
        SmsConfig.state.refreshIntervals.dashboard = setInterval(() => {
            if (typeof refreshDashboard === 'function') {
                refreshDashboard();
            }
        }, interval);
    }
}

// ==================== Initialize on DOM Ready ====================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSmsDashboard);
} else {
    initSmsDashboard();
}

// ==================== Export for Global Use ====================

// Make classes and utilities available globally
window.SmsUtils = SmsUtils;
window.SmsApi = SmsApi;
window.SmsModal = SmsModal;
window.SmsPagination = SmsPagination;
window.SmsConfig = SmsConfig;