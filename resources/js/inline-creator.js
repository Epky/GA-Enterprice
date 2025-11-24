/**
 * InlineCreator - Handles inline creation of categories and brands
 * 
 * This module manages modal interactions, AJAX form submissions,
 * dropdown updates, and user feedback for inline creation workflows.
 */
class InlineCreator {
    /**
     * Initialize the inline creator
     * 
     * @param {string} modalId - The ID of the modal element
     * @param {string} dropdownId - The ID of the dropdown to update
     * @param {string} endpoint - The API endpoint for creation
     */
    constructor(modalId, dropdownId, endpoint) {
        this.modalId = modalId;
        this.dropdownId = dropdownId;
        this.endpoint = endpoint;
        this.modal = document.getElementById(modalId);
        this.dropdown = document.getElementById(dropdownId);
        this.form = document.getElementById(`${modalId}-form`);
        this.submitButton = document.getElementById(`${modalId}-submit`);
        this.submitText = document.getElementById(`${modalId}-submit-text`);
        this.spinner = document.getElementById(`${modalId}-spinner`);
        this.triggerButton = null; // Will store the button that opened the modal
        
        if (!this.modal || !this.dropdown || !this.form) {
            console.error('InlineCreator: Required elements not found', {
                modalId,
                dropdownId,
                modal: !!this.modal,
                dropdown: !!this.dropdown,
                form: !!this.form
            });
            return;
        }
        
        this.initializeEventListeners();
    }

    /**
     * Initialize all event listeners
     */
    initializeEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });

        // Store trigger button reference when modal opens
        document.addEventListener('click', (e) => {
            const button = e.target.closest('[data-modal-target]');
            if (button && button.dataset.modalTarget === this.modalId) {
                this.triggerButton = button;
            }
        });

        // Clear errors and validate when user types
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                this.clearFieldError(input.name);
                this.validateFieldOnInput(input);
            });
            
            // Also validate on blur for better UX
            input.addEventListener('blur', () => {
                this.validateFieldOnBlur(input);
            });
        });
    }

    /**
     * Open the modal
     * Shows modal, dims background, and traps focus
     */
    openModal() {
        // Dispatch Alpine.js event to open modal
        window.dispatchEvent(new CustomEvent('open-modal', {
            detail: this.modalId
        }));
        
        // Reset form
        this.resetForm();
    }

    /**
     * Close the modal
     * Hides modal, removes backdrop, and restores focus
     */
    closeModal() {
        // Dispatch Alpine.js event to close modal
        window.dispatchEvent(new CustomEvent('close-modal', {
            detail: this.modalId
        }));
        
        // Restore focus to trigger button
        if (this.triggerButton) {
            setTimeout(() => {
                this.triggerButton.focus();
            }, 100);
        }
        
        // Reset form
        this.resetForm();
    }

    /**
     * Submit the form via AJAX
     * Collects form data, sends POST request with CSRF token,
     * handles loading state, and processes response
     * 
     * @param {number} retryCount - Number of retry attempts (internal use)
     */
    async submitForm(retryCount = 0) {
        // Client-side validation
        if (!this.validateForm()) {
            return;
        }

        // Collect form data
        const formData = new FormData(this.form);
        
        // Show loading state
        this.setLoadingState(true);
        
        // Clear previous errors
        this.clearAllErrors();

        try {
            // Send AJAX request with timeout
            const response = await window.axios.post(this.endpoint, formData, {
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json',
                },
                timeout: 30000 // 30 second timeout
            });

            // Handle success
            if (response.data.success) {
                this.handleSuccess(response.data);
            } else {
                this.showError('An unexpected error occurred. Please try again.');
            }
        } catch (error) {
            // Check if this is a network error and we haven't exceeded retry limit
            const isNetworkError = !error.response && error.request;
            const maxRetries = 2;
            
            if (isNetworkError && retryCount < maxRetries) {
                // Show retry message
                this.showError(`Network error. Retrying... (Attempt ${retryCount + 1} of ${maxRetries})`);
                
                // Wait before retrying (exponential backoff)
                await new Promise(resolve => setTimeout(resolve, 1000 * (retryCount + 1)));
                
                // Reset loading state and retry
                this.setLoadingState(false);
                return this.submitForm(retryCount + 1);
            }
            
            // Handle error normally if not retrying
            this.handleError(error);
        } finally {
            this.setLoadingState(false);
        }
    }

    /**
     * Validate form before submission
     * 
     * @returns {boolean} True if form is valid
     */
    validateForm() {
        let isValid = true;
        
        // Clear all previous errors
        this.clearAllErrors();
        
        // Validate name field (required)
        const nameInput = this.form.querySelector('[name="name"]');
        if (!nameInput || !nameInput.value.trim()) {
            this.showFieldError('name', 'Name is required');
            isValid = false;
        } else if (nameInput.value.trim().length < 2) {
            this.showFieldError('name', 'Name must be at least 2 characters');
            isValid = false;
        } else if (nameInput.value.trim().length > 100) {
            this.showFieldError('name', 'Name must not exceed 100 characters');
            isValid = false;
        }
        
        // Validate description length if provided
        const descriptionInput = this.form.querySelector('[name="description"]');
        if (descriptionInput && descriptionInput.value.trim().length > 500) {
            this.showFieldError('description', 'Description must not exceed 500 characters');
            isValid = false;
        }
        
        // If validation failed, show general error message
        if (!isValid) {
            this.showError('Please correct the errors below before submitting');
            
            // Focus on first invalid field
            const firstErrorField = this.form.querySelector('.border-red-500');
            if (firstErrorField) {
                firstErrorField.focus();
            }
        }

        return isValid;
    }

    /**
     * Validate field as user types (real-time validation)
     * Only shows errors after user has started typing
     * 
     * @param {HTMLElement} input - The input element to validate
     */
    validateFieldOnInput(input) {
        // Only validate if field has been touched and has content
        if (!input.value) {
            return;
        }

        const fieldName = input.name;
        const value = input.value.trim();

        // Validate based on field name
        switch (fieldName) {
            case 'name':
                if (value.length > 0 && value.length < 2) {
                    this.showFieldError(fieldName, 'Name must be at least 2 characters');
                } else if (value.length > 100) {
                    this.showFieldError(fieldName, 'Name must not exceed 100 characters');
                }
                break;
                
            case 'description':
                if (value.length > 500) {
                    this.showFieldError(fieldName, 'Description must not exceed 500 characters');
                }
                break;
        }
    }

    /**
     * Validate field when user leaves it (blur event)
     * Shows required field errors
     * 
     * @param {HTMLElement} input - The input element to validate
     */
    validateFieldOnBlur(input) {
        const fieldName = input.name;
        const value = input.value.trim();

        // Validate required fields
        if (fieldName === 'name' && !value) {
            this.showFieldError(fieldName, 'Name is required');
        }
    }

    /**
     * Handle successful creation
     * Updates dropdown, shows success message, and closes modal
     * 
     * @param {Object} data - Response data from server
     */
    handleSuccess(data) {
        // Update dropdown with new item
        this.updateDropdown(data.data);
        
        // Determine item type for message
        const itemType = this.modalId.includes('category') ? 'Category' : 'Brand';
        const itemName = data.data.name;
        
        // Show success message with item name
        const successMessage = data.message || `${itemType} "${itemName}" created successfully`;
        this.showSuccess(successMessage);
        
        // Log success for debugging
        console.log('Inline creation successful:', data.data);
        
        // Close modal after delay to allow user to see success message
        setTimeout(() => {
            this.closeModal();
            
            // Show a toast notification if available
            this.showToastNotification(successMessage, 'success');
        }, 1500);
    }

    /**
     * Handle error response
     * Provides specific error messages for different error types
     * 
     * @param {Error} error - Error object from axios
     */
    handleError(error) {
        console.error('Inline creation error:', error);
        
        if (error.response) {
            // Server responded with error
            const status = error.response.status;
            const data = error.response.data;
            
            if (status === 422) {
                // Validation errors
                const errors = data.errors || {};
                this.showValidationErrors(errors);
                
                // Check for duplicate name error specifically
                if (errors.name && errors.name.some(msg => msg.toLowerCase().includes('already') || msg.toLowerCase().includes('taken'))) {
                    this.showError('A ' + (this.modalId.includes('category') ? 'category' : 'brand') + ' with this name already exists. Please choose a different name.');
                }
            } else if (status === 400) {
                // Bad request
                this.showError(data.message || 'Invalid request. Please check your input and try again.');
            } else if (status === 401) {
                // Unauthorized
                this.showError('Your session has expired. Please refresh the page and log in again.');
            } else if (status === 403) {
                // Forbidden
                this.showError('You do not have permission to perform this action.');
            } else if (status === 404) {
                // Not found
                this.showError('The requested resource was not found. Please refresh the page and try again.');
            } else if (status === 419) {
                // CSRF token mismatch
                this.showError('Your session has expired. Please refresh the page and try again.');
            } else if (status === 429) {
                // Too many requests
                this.showError('Too many requests. Please wait a moment and try again.');
            } else if (status >= 500) {
                // Server error
                this.showError('A server error occurred. Please try again later or contact support if the problem persists.');
            } else if (data.message) {
                // Other server errors with message
                this.showError(data.message);
            } else {
                this.showError('An error occurred. Please try again.');
            }
        } else if (error.request) {
            // Network error - no response received
            this.showError('Network error. Please check your internet connection and try again.');
        } else if (error.code === 'ECONNABORTED') {
            // Request timeout
            this.showError('Request timed out. Please check your connection and try again.');
        } else {
            // Other errors
            this.showError('An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Update dropdown with new item
     * Adds new option to select element, sets it as selected,
     * and maintains sort order
     * 
     * @param {Object} item - The new item data (id, name, etc.)
     */
    updateDropdown(item) {
        if (!this.dropdown) {
            console.error('Dropdown not found:', this.dropdownId);
            return;
        }

        // Create new option
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        option.selected = true;

        // Find correct position to insert (alphabetically)
        const options = Array.from(this.dropdown.options);
        let insertIndex = options.length;

        // Skip first option if it's a placeholder (empty value)
        const startIndex = options[0] && !options[0].value ? 1 : 0;

        for (let i = startIndex; i < options.length; i++) {
            if (options[i].textContent.toLowerCase() > item.name.toLowerCase()) {
                insertIndex = i;
                break;
            }
        }

        // Insert option at correct position
        if (insertIndex >= options.length) {
            this.dropdown.appendChild(option);
        } else {
            this.dropdown.insertBefore(option, options[insertIndex]);
        }

        // Trigger change event
        this.dropdown.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Highlight the dropdown briefly
        this.highlightDropdown();
    }

    /**
     * Highlight dropdown to show it was updated
     */
    highlightDropdown() {
        const originalClass = this.dropdown.className;
        this.dropdown.classList.add('ring-2', 'ring-green-500', 'ring-offset-2');
        
        setTimeout(() => {
            this.dropdown.className = originalClass;
        }, 2000);
    }

    /**
     * Show validation errors inline in modal
     * Displays field-specific errors and a general error summary
     * 
     * @param {Object} errors - Object with field names as keys and error arrays as values
     */
    showValidationErrors(errors) {
        let hasDuplicateError = false;
        
        Object.keys(errors).forEach(field => {
            const messages = errors[field];
            if (messages && messages.length > 0) {
                const errorMessage = messages[0];
                this.showFieldError(field, errorMessage);
                
                // Check if this is a duplicate name error
                if (field === 'name' && (errorMessage.toLowerCase().includes('already') || 
                    errorMessage.toLowerCase().includes('taken') || 
                    errorMessage.toLowerCase().includes('exists'))) {
                    hasDuplicateError = true;
                }
            }
        });

        // Show appropriate general error message
        const errorCount = Object.keys(errors).length;
        if (hasDuplicateError) {
            const type = this.modalId.includes('category') ? 'category' : 'brand';
            this.showError(`A ${type} with this name already exists. Please choose a different name.`);
        } else if (errorCount === 1) {
            this.showError('Please correct the error below.');
        } else {
            this.showError(`Please correct ${errorCount} errors below.`);
        }
    }

    /**
     * Show error for specific field
     * 
     * @param {string} fieldName - Name of the field
     * @param {string} message - Error message
     */
    showFieldError(fieldName, message) {
        const input = this.form.querySelector(`[name="${fieldName}"]`);
        const errorElement = document.getElementById(`${this.modalId}-${fieldName}-error`);

        if (input) {
            input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            input.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            
            // Update ARIA attributes for accessibility
            input.setAttribute('aria-invalid', 'true');
        }

        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    }

    /**
     * Clear error for specific field
     * 
     * @param {string} fieldName - Name of the field
     */
    clearFieldError(fieldName) {
        const input = this.form.querySelector(`[name="${fieldName}"]`);
        const errorElement = document.getElementById(`${this.modalId}-${fieldName}-error`);

        if (input) {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            input.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            
            // Update ARIA attributes for accessibility
            input.setAttribute('aria-invalid', 'false');
        }

        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
    }

    /**
     * Clear all field errors
     */
    clearAllErrors() {
        // Clear field-specific errors
        const errorElements = this.form.querySelectorAll('[id$="-error"]');
        errorElements.forEach(element => {
            element.textContent = '';
            element.classList.add('hidden');
        });

        // Reset input styles and ARIA attributes
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            input.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            
            // Update ARIA attributes for accessibility
            input.setAttribute('aria-invalid', 'false');
        });

        // Hide error container
        this.hideError();
    }

    /**
     * Show general error message
     * 
     * @param {string} message - Error message to display
     */
    showError(message) {
        const errorContainer = document.getElementById(`${this.modalId}-error-container`);
        const errorMessage = document.getElementById(`${this.modalId}-error-message`);

        if (errorContainer && errorMessage) {
            errorMessage.textContent = message;
            errorContainer.classList.remove('hidden');
            
            // The error container already has role="alert" and aria-live="assertive"
            // so screen readers will automatically announce it
        }
    }

    /**
     * Hide error message
     */
    hideError() {
        const errorContainer = document.getElementById(`${this.modalId}-error-container`);
        if (errorContainer) {
            errorContainer.classList.add('hidden');
        }
    }

    /**
     * Show success notification and close modal
     * 
     * @param {string} message - Success message to display
     */
    showSuccess(message) {
        const successContainer = document.getElementById(`${this.modalId}-success-container`);
        const successMessage = document.getElementById(`${this.modalId}-success-message`);

        if (successContainer && successMessage) {
            successMessage.textContent = message;
            successContainer.classList.remove('hidden');
            
            // The success container already has role="status" and aria-live="polite"
            // so screen readers will automatically announce it
            
            // Hide error if visible
            this.hideError();
        }
    }

    /**
     * Hide success message
     */
    hideSuccess() {
        const successContainer = document.getElementById(`${this.modalId}-success-container`);
        if (successContainer) {
            successContainer.classList.add('hidden');
        }
    }

    /**
     * Set loading state for submit button
     * Disables button and shows loading spinner to prevent double submission
     * 
     * @param {boolean} isLoading - Whether form is submitting
     */
    setLoadingState(isLoading) {
        if (!this.submitButton || !this.submitText || !this.spinner) {
            console.warn('Loading state elements not found');
            return;
        }

        if (isLoading) {
            // Disable submit button
            this.submitButton.disabled = true;
            this.submitButton.setAttribute('aria-busy', 'true');
            this.submitButton.classList.add('opacity-75', 'cursor-not-allowed');
            
            // Update button text
            this.submitText.textContent = 'Creating...';
            
            // Show spinner
            this.spinner.classList.remove('hidden');
            
            // Also disable cancel button to prevent closing during submission
            const cancelButton = this.form.querySelector('button[type="button"]');
            if (cancelButton) {
                cancelButton.disabled = true;
                cancelButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            // Disable all form inputs
            const inputs = this.form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.disabled = true;
            });
        } else {
            // Enable submit button
            this.submitButton.disabled = false;
            this.submitButton.removeAttribute('aria-busy');
            this.submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
            
            // Reset button text
            this.submitText.textContent = 'Create';
            
            // Hide spinner
            this.spinner.classList.add('hidden');
            
            // Re-enable cancel button
            const cancelButton = this.form.querySelector('button[type="button"]');
            if (cancelButton) {
                cancelButton.disabled = false;
                cancelButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            // Re-enable all form inputs
            const inputs = this.form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.disabled = false;
            });
        }
    }

    /**
     * Reset form to initial state
     */
    resetForm() {
        this.form.reset();
        this.clearAllErrors();
        this.hideSuccess();
        this.setLoadingState(false);
    }

    /**
     * Announce message to screen readers using a dedicated live region
     * This is used for toast notifications that appear outside the modal
     * 
     * @param {string} message - Message to announce
     * @param {string} priority - 'polite' or 'assertive'
     */
    announceToScreenReader(message, priority = 'polite') {
        // Create or get ARIA live region for toast notifications
        let liveRegion = document.getElementById('inline-creator-toast-announcer');
        
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'inline-creator-toast-announcer';
            liveRegion.className = 'sr-only';
            liveRegion.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
            liveRegion.setAttribute('aria-live', priority);
            liveRegion.setAttribute('aria-atomic', 'true');
            document.body.appendChild(liveRegion);
        } else {
            liveRegion.setAttribute('aria-live', priority);
            liveRegion.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
        }

        // Clear and set message
        liveRegion.textContent = '';
        setTimeout(() => {
            liveRegion.textContent = message;
        }, 100);
    }

    /**
     * Show toast notification (if notification system exists)
     * Falls back gracefully if no notification system is available
     * 
     * @param {string} message - Message to display
     * @param {string} type - Notification type ('success', 'error', 'warning', 'info')
     */
    showToastNotification(message, type = 'info') {
        // Check for Alpine.js event-based notification
        if (window.Alpine) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
            return;
        }
        
        // Fallback: Create a simple toast notification
        const toast = document.createElement('div');
        const toastId = `toast-${Date.now()}`;
        toast.id = toastId;
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        toast.textContent = message;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toast.setAttribute('aria-atomic', 'true');
        
        document.body.appendChild(toast);
        
        // Announce to screen readers using dedicated announcer
        this.announceToScreenReader(message, type === 'error' ? 'assertive' : 'polite');
        
        // Fade out and remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                const toastElement = document.getElementById(toastId);
                if (toastElement && toastElement.parentNode) {
                    document.body.removeChild(toastElement);
                }
            }, 300);
        }, 3000);
    }
}

/**
 * Initialize inline creators for a page
 * 
 * @param {Object} config - Configuration object with modal/dropdown/endpoint mappings
 */
function initializeInlineCreators(config) {
    const creators = {};

    Object.keys(config).forEach(key => {
        const { modalId, dropdownId, endpoint } = config[key];
        creators[key] = new InlineCreator(modalId, dropdownId, endpoint);
    });

    return creators;
}

// Export for use in other modules
export { InlineCreator, initializeInlineCreators };
