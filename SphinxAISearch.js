/**
 * Sphinx AI Search JavaScript
 * Enhanced functionality for the search interface
 */

class SphinxAISearch {
    constructor() {
        this.searchTimeout = null;
        this.currentRequest = null;
        this.cache = new Map();
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
        this.setupKeyboardShortcuts();
    }

    bindEvents() {
        const searchInput = document.getElementById('search_query');
        const searchForm = document.querySelector('.sphinx-search-form');
        
        if (searchInput) {
            searchInput.addEventListener('input', this.handleSearchInput.bind(this));
            searchInput.addEventListener('keydown', this.handleKeyDown.bind(this));
            searchInput.addEventListener('focus', this.handleFocus.bind(this));
            searchInput.addEventListener('blur', this.handleBlur.bind(this));
        }

        if (searchForm) {
            searchForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }

        // Bind admin functionality
        this.bindAdminEvents();
    }

    handleSearchInput(event) {
        const query = event.target.value.trim();
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Cancel previous request
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        if (query.length >= 2) {
            this.searchTimeout = setTimeout(() => {
                this.performLiveSearch(query);
            }, 300);
        } else {
            this.hideLiveResults();
        }
    }

    handleKeyDown(event) {
        const dropdown = document.querySelector('.live-search-results');
        
        if (!dropdown) return;

        const items = dropdown.querySelectorAll('.live-search-item');
        const activeItem = dropdown.querySelector('.live-search-item.active');
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.navigateResults(items, activeItem, 'next');
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.navigateResults(items, activeItem, 'prev');
                break;
            case 'Enter':
                event.preventDefault();
                if (activeItem) {
                    activeItem.click();
                }
                break;
            case 'Escape':
                this.hideLiveResults();
                break;
        }
    }

    handleFocus(event) {
        const query = event.target.value.trim();
        if (query.length >= 2 && this.cache.has(query)) {
            this.showLiveResults(this.cache.get(query));
        }
    }

    handleBlur(event) {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            this.hideLiveResults();
        }, 150);
    }

    handleFormSubmit(event) {
        const searchInput = document.getElementById('search_query');
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            event.preventDefault();
            this.showMessage('Please enter at least 2 characters', 'warning');
            return;
        }

        this.showLoading();
        
        // Add query to recent searches
        this.addToRecentSearches(query);
    }

    performLiveSearch(query) {
        // Check cache first
        if (this.cache.has(query)) {
            this.showLiveResults(this.cache.get(query));
            return;
        }

        const formData = new FormData();
        formData.append('api_action', 'search');
        formData.append('query', query);

        this.currentRequest = fetch(smf_scripturl + '?action=sphinxai_api', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.results) {
                this.cache.set(query, data.results);
                this.showLiveResults(data.results);
            } else if (data.error) {
                console.error('Search error:', data.error);
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
                console.error('Search request failed:', error);
            }
        });
    }

    showLiveResults(results) {
        this.hideLiveResults();
        
        if (results.length === 0) return;

        const searchContainer = document.querySelector('.search-container');
        const dropdown = document.createElement('div');
        dropdown.className = 'live-search-results';

        results.slice(0, 5).forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'live-search-item';
            if (index === 0) item.classList.add('active');

            const title = document.createElement('strong');
            title.textContent = result.subject || 'Untitled';

            const summary = document.createElement('div');
            summary.className = 'item-summary';
            summary.textContent = (result.summary || result.content || '').substring(0, 100) + '...';

            const meta = document.createElement('div');
            meta.className = 'item-meta';
            meta.innerHTML = `
                <span class="board-name">${result.board_name || 'Unknown Board'}</span>
                <span class="post-stats">${result.num_replies || 0} replies</span>
            `;

            item.appendChild(title);
            item.appendChild(summary);
            item.appendChild(meta);

            item.addEventListener('click', () => {
                window.location.href = `${smf_scripturl}?topic=${result.topic_id}.msg${result.post_id}#msg${result.post_id}`;
            });

            dropdown.appendChild(item);
        });

        searchContainer.appendChild(dropdown);
    }

    hideLiveResults() {
        const dropdown = document.querySelector('.live-search-results');
        if (dropdown) {
            dropdown.remove();
        }
    }

    navigateResults(items, activeItem, direction) {
        if (activeItem) {
            activeItem.classList.remove('active');
        }

        let newIndex;
        if (direction === 'next') {
            newIndex = activeItem ? 
                Array.from(items).indexOf(activeItem) + 1 : 0;
            if (newIndex >= items.length) newIndex = 0;
        } else {
            newIndex = activeItem ? 
                Array.from(items).indexOf(activeItem) - 1 : items.length - 1;
            if (newIndex < 0) newIndex = items.length - 1;
        }

        items[newIndex].classList.add('active');
        items[newIndex].scrollIntoView({ block: 'nearest' });
    }

    showLoading() {
        const searchButton = document.querySelector('.search-button');
        if (searchButton) {
            const originalText = searchButton.innerHTML;
            searchButton.innerHTML = '<div class="search-loading"></div> Searching...';
            searchButton.disabled = true;

            // Restore after timeout
            setTimeout(() => {
                searchButton.innerHTML = originalText;
                searchButton.disabled = false;
            }, 30000);
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'warning' ? '#fff3cd' : '#d4edda'};
            color: ${type === 'warning' ? '#856404' : '#155724'};
            padding: 12px 20px;
            border-radius: 4px;
            border: 1px solid ${type === 'warning' ? '#ffeaa7' : '#c3e6cb'};
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    addToRecentSearches(query) {
        let recentSearches = JSON.parse(localStorage.getItem('sphinx_recent_searches') || '[]');
        
        // Remove if already exists
        recentSearches = recentSearches.filter(search => search !== query);
        
        // Add to beginning
        recentSearches.unshift(query);
        
        // Keep only last 10
        recentSearches = recentSearches.slice(0, 10);
        
        localStorage.setItem('sphinx_recent_searches', JSON.stringify(recentSearches));
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Ctrl+K or Cmd+K to focus search
            if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                event.preventDefault();
                const searchInput = document.getElementById('search_query');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    }

    initializeTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    }

    showTooltip(event) {
        const element = event.target;
        const text = element.getAttribute('data-tooltip');
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip-popup';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            white-space: nowrap;
        `;

        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        element._tooltip = tooltip;
    }

    hideTooltip(event) {
        const tooltip = event.target._tooltip;
        if (tooltip) {
            tooltip.remove();
            delete event.target._tooltip;
        }
    }

    // Admin functionality
    bindAdminEvents() {
        const reindexButton = document.querySelector('[name="start_indexing"]');
        if (reindexButton) {
            reindexButton.addEventListener('click', this.handleReindex.bind(this));
        }

        const settingsForm = document.querySelector('form[action*="sa=settings"]');
        if (settingsForm) {
            settingsForm.addEventListener('submit', this.handleSettingsSave.bind(this));
        }
    }

    handleReindex(event) {
        event.preventDefault();
        
        if (!confirm('This will reindex all forum content. This may take a while. Continue?')) {
            return;
        }

        this.showMessage('Indexing started...', 'info');
        
        fetch(smf_scripturl + '?action=sphinxai_api', {
            method: 'POST',
            body: new FormData().append('api_action', 'reindex')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showMessage('Indexing completed successfully', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showMessage('Indexing failed: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            this.showMessage('Indexing failed: ' + error.message, 'error');
        });
    }

    handleSettingsSave(event) {
        const form = event.target;
        const formData = new FormData(form);
        
        // Validate model path
        const modelPath = formData.get('sphinx_ai_model_path');
        if (modelPath && !this.isValidPath(modelPath)) {
            event.preventDefault();
            this.showMessage('Please enter a valid model path', 'warning');
            return;
        }

        // Validate numeric fields
        const maxResults = parseInt(formData.get('sphinx_ai_max_results'));
        if (maxResults < 1 || maxResults > 100) {
            event.preventDefault();
            this.showMessage('Max results must be between 1 and 100', 'warning');
            return;
        }

        const summaryLength = parseInt(formData.get('sphinx_ai_summary_length'));
        if (summaryLength < 50 || summaryLength > 500) {
            event.preventDefault();
            this.showMessage('Summary length must be between 50 and 500', 'warning');
            return;
        }
    }

    isValidPath(path) {
        // Basic path validation
        return path.length > 0 && !path.includes('<') && !path.includes('>');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SphinxAISearch();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .live-search-item.active {
        background: #e3f2fd !important;
        border-left: 4px solid #4CAF50;
    }
    
    .message {
        animation: slideIn 0.3s ease-out;
    }
`;
document.head.appendChild(style);
