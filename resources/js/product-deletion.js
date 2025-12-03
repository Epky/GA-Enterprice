/**
 * Product Deletion Modal Controller
 * 
 * Provides functions to control the product deletion confirmation modal.
 * The modal uses Alpine.js for state management and transitions.
 */

// Store focusable elements for focus trapping
let focusableElements = [];
let firstFocusableElement = null;
let lastFocusableElement = null;

/**
 * Shows the delete confirmation modal for a specific product
 * 
 * @param {number} productId - The ID of the product to delete
 * @param {string} productName - The name of the product (for display)
 * @param {number} stockQuantity - The total stock quantity across all locations
 */
export function showDeleteModal(productId, productName, stockQuantity) {
    // Store the element that triggered the modal for focus restoration
    window.deleteModalTrigger = document.activeElement;
    
    // Dispatch custom event to open the modal
    // Alpine.js listens for this event on the modal component
    window.dispatchEvent(new CustomEvent('open-delete-modal', {
        detail: {
            productId: productId,
            productName: productName,
            stockQuantity: stockQuantity
        }
    }));
    
    // Set up focus trap after a short delay to allow Alpine.js to render the modal
    setTimeout(() => {
        setupFocusTrap();
        focusFirstElement();
    }, 100);
}

/**
 * Hides the delete confirmation modal
 */
export function hideDeleteModal() {
    // Remove focus trap
    removeFocusTrap();
    
    // Dispatch custom event to close the modal
    window.dispatchEvent(new CustomEvent('close-delete-modal'));
    
    // Return focus to the element that triggered the modal
    if (window.deleteModalTrigger) {
        window.deleteModalTrigger.focus();
        window.deleteModalTrigger = null;
    }
}

/**
 * Confirms the deletion by submitting the delete form
 * This function is called when the user clicks the "Delete Product" button
 * 
 * @param {number} productId - The ID of the product to delete
 */
export function confirmDeletion(productId) {
    // Find the delete form for this specific product
    const form = document.querySelector(`form[action*="/products/${productId}"]`);
    
    if (form) {
        // Submit the form
        form.submit();
    } else {
        console.error(`Delete form not found for product ID: ${productId}`);
    }
}

/**
 * Set up focus trap within the modal
 * This ensures keyboard navigation stays within the modal when it's open
 */
function setupFocusTrap() {
    // Find the modal container
    const modal = document.querySelector('[x-data*="show"]');
    
    if (!modal) return;
    
    // Get all focusable elements within the modal
    const focusableSelectors = [
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        'a[href]',
        '[tabindex]:not([tabindex="-1"])'
    ].join(', ');
    
    focusableElements = Array.from(modal.querySelectorAll(focusableSelectors));
    
    if (focusableElements.length > 0) {
        firstFocusableElement = focusableElements[0];
        lastFocusableElement = focusableElements[focusableElements.length - 1];
        
        // Add keydown listener for tab key
        document.addEventListener('keydown', handleFocusTrap);
    }
}

/**
 * Remove focus trap when modal closes
 */
function removeFocusTrap() {
    document.removeEventListener('keydown', handleFocusTrap);
    focusableElements = [];
    firstFocusableElement = null;
    lastFocusableElement = null;
}

/**
 * Handle focus trap - keep focus within modal
 * 
 * @param {KeyboardEvent} event - The keyboard event
 */
function handleFocusTrap(event) {
    // Only trap Tab key
    if (event.key !== 'Tab') {
        return;
    }
    
    // Check if modal is visible
    const modal = document.querySelector('[x-data*="show"]');
    if (!modal || modal.style.display === 'none') {
        return;
    }
    
    // If Shift + Tab (going backwards)
    if (event.shiftKey) {
        if (document.activeElement === firstFocusableElement) {
            event.preventDefault();
            lastFocusableElement.focus();
        }
    } else {
        // Tab (going forwards)
        if (document.activeElement === lastFocusableElement) {
            event.preventDefault();
            firstFocusableElement.focus();
        }
    }
}

/**
 * Focus the first focusable element in the modal
 */
function focusFirstElement() {
    if (firstFocusableElement) {
        firstFocusableElement.focus();
    }
}

/**
 * Initialize event handlers for delete buttons
 * This function sets up click handlers on delete buttons to show the modal
 */
function initializeDeleteButtons() {
    // Find all delete buttons with specific class and data-product-* attributes
    // Using specific selector to avoid conflicts with other elements that may have data-product-id
    const deleteButtons = document.querySelectorAll('button.delete-product-btn[data-product-id]');
    
    if (deleteButtons.length === 0) {
        console.log('No delete product buttons found on this page');
        return;
    }
    
    console.log(`Initializing ${deleteButtons.length} delete product button(s)`);
    
    deleteButtons.forEach(button => {
        // Validate that this is actually a button element
        if (button.tagName !== 'BUTTON') {
            console.warn('Delete button selector matched non-button element:', button);
            return;
        }
        
        // Validate required data attributes
        if (!button.dataset.productId || !button.dataset.productName) {
            console.warn('Delete button missing required data attributes:', button);
            return;
        }
        
        // Remove any existing listeners to prevent duplicates
        button.removeEventListener('click', handleDeleteButtonClick);
        
        // Add click event listener
        button.addEventListener('click', handleDeleteButtonClick);
    });
}

/**
 * Handle delete button click event
 * 
 * @param {Event} event - The click event
 */
function handleDeleteButtonClick(event) {
    // Prevent default form submission or link navigation
    event.preventDefault();
    
    // Get product data from data attributes
    const button = event.currentTarget;
    const productId = parseInt(button.dataset.productId);
    const productName = button.dataset.productName || 'this product';
    const stockQuantity = parseInt(button.dataset.stockQuantity) || 0;
    
    // Show the modal
    showDeleteModal(productId, productName, stockQuantity);
}

/**
 * Handle escape key press to close modal
 * Note: Alpine.js already handles this, but we keep this for fallback
 */
function handleEscapeKey(event) {
    if (event.key === 'Escape') {
        hideDeleteModal();
    }
}

/**
 * Initialize all event handlers when DOM is ready
 */
function initialize() {
    // Initialize delete buttons
    initializeDeleteButtons();
    
    // Listen for escape key globally (fallback if Alpine.js fails)
    document.addEventListener('keydown', handleEscapeKey);
    
    // Re-initialize when content is dynamically loaded (e.g., pagination)
    document.addEventListener('DOMContentLoaded', initializeDeleteButtons);
}

// Initialize when the module loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    // DOM is already loaded
    initialize();
}

// Make functions available globally for inline event handlers
window.showDeleteModal = showDeleteModal;
window.hideDeleteModal = hideDeleteModal;
window.confirmDeletion = confirmDeletion;
window.initializeDeleteButtons = initializeDeleteButtons;
