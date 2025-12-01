/**
 * SearchableSelect Component
 * 
 * A custom dropdown component with search and inline delete functionality.
 * Provides an enhanced select experience for managing categories and brands.
 */
class SearchableSelect {
    /**
     * Create a SearchableSelect instance
     * @param {HTMLElement} element - The wrapper element containing the component
     * @param {Object} options - Configuration options
     * @param {string} options.csrfToken - CSRF token for AJAX requests
     */
    constructor(element, options = {}) {
        this.element = element;
        this.options = options;
        
        // Component state
        this.isOpen = false;
        this.selectedId = null;
        this.items = [];
        
        // Get DOM elements
        this.hiddenInput = element.querySelector('input[type="hidden"]');
        this.trigger = element.querySelector('.searchable-select-trigger');
        this.dropdown = element.querySelector('.searchable-select-dropdown');
        this.searchInput = element.querySelector('.search-input');
        this.itemsList = element.querySelector('.items-list');
        this.noResults = element.querySelector('.no-results');
        this.selectedText = element.querySelector('.selected-text');
        
        // Get routes from data attributes
        this.deleteRoutePattern = this.hiddenInput.dataset.deleteRoutePattern;
        this.refreshRoute = this.hiddenInput.dataset.refreshRoute;
        
        // Initialize selected value
        this.selectedId = this.hiddenInput.value || null;
        
        // Store items data
        this.cacheItems();
        
        // Set up event listeners
        this.initEventListeners();
    }
    
    /**
     * Cache items data from DOM for filtering
     */
    cacheItems() {
        const itemElements = this.itemsList.querySelectorAll('.item');
        this.items = Array.from(itemElements).map(el => ({
            id: el.dataset.id,
            name: el.dataset.name,
            element: el
        }));
    }
    
    /**
     * Initialize all event listeners
     */
    initEventListeners() {
        // Trigger button click
        this.trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });
        
        // Search input with debouncing
        let searchTimeout;
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.search(e.target.value);
            }, 300);
        });
        
        // Item selection
        this.itemsList.addEventListener('click', (e) => {
            const item = e.target.closest('.item');
            if (item && !e.target.closest('.delete-btn')) {
                const id = item.dataset.id;
                const name = item.dataset.name;
                this.selectItem(id, name);
            }
        });
        
        // Delete buttons
        this.itemsList.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                e.stopPropagation();
                const id = deleteBtn.dataset.id;
                const name = deleteBtn.dataset.name;
                this.deleteItem(id, name);
            }
        });
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.element.contains(e.target)) {
                this.close();
            }
        });
        
        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    /**
     * Toggle dropdown open/close
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    /**
     * Open the dropdown
     */
    open() {
        this.isOpen = true;
        this.dropdown.classList.remove('hidden');
        this.trigger.setAttribute('aria-expanded', 'true');
        this.searchInput.focus();
    }
    
    /**
     * Close the dropdown
     */
    close() {
        this.isOpen = false;
        this.dropdown.classList.add('hidden');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.searchInput.value = '';
        this.search(''); // Reset search filter
    }
    
    /**
     * Filter items based on search query
     * @param {string} query - Search query string
     */
    search(query) {
        const lowerQuery = query.toLowerCase().trim();
        let hasVisibleItems = false;
        
        this.items.forEach(item => {
            const matches = item.name.toLowerCase().includes(lowerQuery);
            
            if (matches) {
                item.element.classList.remove('hidden');
                hasVisibleItems = true;
            } else {
                item.element.classList.add('hidden');
            }
        });
        
        // Show/hide "no results" message
        if (hasVisibleItems) {
            this.noResults.classList.add('hidden');
            this.itemsList.classList.remove('hidden');
        } else {
            this.noResults.classList.remove('hidden');
            this.itemsList.classList.add('hidden');
        }
    }
    
    /**
     * Select an item
     * @param {string} id - Item ID
     * @param {string} name - Item name
     */
    selectItem(id, name) {
        this.selectedId = id;
        this.hiddenInput.value = id;
        this.selectedText.textContent = name;
        this.selectedText.classList.remove('text-gray-500');
        this.selectedText.classList.add('text-gray-900');
        
        // Update aria-selected attributes
        this.items.forEach(item => {
            if (item.id === id) {
                item.element.setAttribute('aria-selected', 'true');
                item.element.classList.add('bg-blue-50');
            } else {
                item.element.setAttribute('aria-selected', 'false');
                item.element.classList.remove('bg-blue-50');
            }
        });
        
        this.close();
    }
    
    /**
     * Delete an item with confirmation
     * @param {string} id - Item ID to delete
     * @param {string} name - Item name for confirmation
     */
    deleteItem(id, name) {
        // Show confirmation dialog
        const confirmed = confirm(`Are you sure you want to delete "${name}"?`);
        
        if (!confirmed) {
            return;
        }
        
        // Show loading state
        this.showLoading();
        
        // Build delete URL from route pattern
        const baseUrl = window.location.origin;
        const routePattern = this.deleteRoutePattern;
        
        // Map route names to URL patterns
        const routeMap = {
            'staff.categories.delete-inline': `/staff/categories/${id}/inline`,
            'staff.brands.delete-inline': `/staff/brands/${id}/inline`
        };
        
        const deleteUrl = baseUrl + (routeMap[routePattern] || `/staff/${routePattern}/${id}/inline`);
        
        console.log('Deleting item:', { id, name, routePattern, deleteUrl });
        
        // Make AJAX DELETE request using axios for consistency
        window.axios.delete(deleteUrl, {
            headers: {
                'X-CSRF-TOKEN': this.options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Delete response:', response);
            this.hideLoading();
            
            const data = response.data;
            if (data.success) {
                this.showMessage('success', data.message || 'Item deleted successfully');
                
                // If deleted item was selected, clear selection
                if (this.selectedId === id) {
                    this.selectedId = null;
                    this.hiddenInput.value = '';
                    this.selectedText.textContent = this.hiddenInput.closest('.searchable-select-wrapper').querySelector('.searchable-select-trigger .selected-text').dataset.placeholder || 'Select...';
                    this.selectedText.classList.remove('text-gray-900');
                    this.selectedText.classList.add('text-gray-500');
                }
                
                // Refresh dropdown items
                this.refreshItems();
            } else {
                this.showMessage('error', data.message || 'Failed to delete item');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            this.hideLoading();
            this.handleError(error);
        });
    }
    
    /**
     * Refresh dropdown items from server
     */
    refreshItems() {
        fetch(this.refreshRoute, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to refresh items');
            }
            return response.json();
        })
        .then(response => {
            // Extract the data array from the response
            const items = response.success && response.data ? response.data : response;
            // Update items list
            this.updateItemsList(items);
        })
        .catch(error => {
            console.error('Error refreshing items:', error);
            this.showMessage('error', 'Failed to refresh list');
        });
    }
    
    /**
     * Update the items list with new data
     * @param {Array} items - Array of item objects with id and name
     */
    updateItemsList(items) {
        // Clear current items
        this.itemsList.innerHTML = '';
        
        if (items.length === 0) {
            this.itemsList.innerHTML = '<li class="px-3 py-2 text-sm text-gray-500 text-center">No items available</li>';
            this.items = [];
            return;
        }
        
        // Add new items
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = `item flex items-center justify-between px-3 py-2 hover:bg-gray-100 cursor-pointer transition-colors duration-150 ${this.selectedId == item.id ? 'bg-blue-50' : ''}`;
            li.dataset.id = item.id;
            li.dataset.name = item.name;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', this.selectedId == item.id ? 'true' : 'false');
            
            li.innerHTML = `
                <span class="item-name text-sm text-gray-900 flex-1">${this.escapeHtml(item.name)}</span>
                <button 
                    type="button" 
                    class="delete-btn ml-2 p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-red-500"
                    data-id="${item.id}"
                    data-name="${this.escapeHtml(item.name)}"
                    aria-label="Delete ${this.escapeHtml(item.name)}"
                    title="Delete ${this.escapeHtml(item.name)}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            `;
            
            this.itemsList.appendChild(li);
        });
        
        // Re-cache items
        this.cacheItems();
    }
    
    /**
     * Show loading state
     */
    showLoading() {
        this.trigger.disabled = true;
        this.trigger.classList.add('opacity-50', 'cursor-not-allowed');
    }
    
    /**
     * Hide loading state
     */
    hideLoading() {
        this.trigger.disabled = false;
        this.trigger.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    /**
     * Show a message to the user
     * @param {string} type - Message type ('success' or 'error')
     * @param {string} text - Message text
     */
    showMessage(type, text) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-md shadow-lg transition-opacity duration-300 ${
            type === 'success' 
                ? 'bg-green-500 text-white' 
                : 'bg-red-500 text-white'
        }`;
        toast.textContent = text;
        
        document.body.appendChild(toast);
        
        // Fade out and remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    /**
     * Handle errors from AJAX requests
     * @param {Object} error - Error object
     */
    handleError(error) {
        console.error('SearchableSelect error:', error);
        
        let message = 'An error occurred. Please try again.';
        
        if (error.status === 422 && error.data) {
            // Validation error
            if (error.data.message) {
                message = error.data.message;
            } else if (error.data.errors) {
                const firstError = Object.values(error.data.errors)[0];
                message = Array.isArray(firstError) ? firstError[0] : firstError;
            }
        } else if (error.status === 500) {
            message = 'Server error. Please try again later.';
        } else if (error.status === 404) {
            message = 'Item not found.';
        } else if (!navigator.onLine) {
            message = 'Network error. Please check your connection.';
        }
        
        this.showMessage('error', message);
    }
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize all searchable select components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    document.querySelectorAll('[data-component="searchable-select"]').forEach(element => {
        new SearchableSelect(element, { csrfToken });
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchableSelect;
}
