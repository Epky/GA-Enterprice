/**
 * Avatar Preview Module
 * 
 * Handles client-side avatar preview functionality including:
 * - File selection and validation
 * - Instant preview display
 * - Upload/cancel button state management
 * - Preview reset functionality
 */

// Constants for validation
const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB in bytes

// Store original avatar URL for cancel functionality
let originalAvatarUrl = null;

/**
 * Initialize avatar preview functionality
 */
export function initializeAvatarPreview() {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');
    const uploadButton = document.getElementById('upload-button');
    const cancelButton = document.getElementById('cancel-button');

    // Exit if elements don't exist on the page
    if (!avatarInput || !avatarPreview || !uploadButton || !cancelButton) {
        return;
    }

    // Store the original avatar URL
    originalAvatarUrl = avatarPreview.src;

    // Add event listener for file input change
    avatarInput.addEventListener('change', handleFileSelect);

    // Add event listener for cancel button
    cancelButton.addEventListener('click', handleCancel);
}

/**
 * Handle file selection event
 * @param {Event} event - The file input change event
 */
function handleFileSelect(event) {
    const file = event.target.files[0];

    // If no file selected, reset
    if (!file) {
        resetPreview();
        return;
    }

    // Validate file type
    if (!validateFileType(file)) {
        showError('Please select a valid image file (JPG, JPEG, PNG, GIF, or WEBP).');
        resetPreview();
        return;
    }

    // Validate file size
    if (!validateFileSize(file)) {
        showError('File size must not exceed 2MB.');
        resetPreview();
        return;
    }

    // Display preview
    displayPreview(file);

    // Show upload and cancel buttons
    showActionButtons();
}

/**
 * Validate file type
 * @param {File} file - The selected file
 * @returns {boolean} - True if valid, false otherwise
 */
function validateFileType(file) {
    return ALLOWED_TYPES.includes(file.type);
}

/**
 * Validate file size
 * @param {File} file - The selected file
 * @returns {boolean} - True if valid, false otherwise
 */
function validateFileSize(file) {
    return file.size <= MAX_FILE_SIZE;
}

/**
 * Display preview of selected image
 * @param {File} file - The selected file
 */
function displayPreview(file) {
    const avatarPreview = document.getElementById('avatar-preview');
    const reader = new FileReader();

    reader.onload = function(e) {
        avatarPreview.src = e.target.result;
    };

    reader.readAsDataURL(file);
}

/**
 * Show upload and cancel buttons
 */
function showActionButtons() {
    const uploadButton = document.getElementById('upload-button');
    const cancelButton = document.getElementById('cancel-button');

    if (uploadButton) {
        uploadButton.classList.remove('hidden');
    }

    if (cancelButton) {
        cancelButton.classList.remove('hidden');
    }
}

/**
 * Hide upload and cancel buttons
 */
function hideActionButtons() {
    const uploadButton = document.getElementById('upload-button');
    const cancelButton = document.getElementById('cancel-button');

    if (uploadButton) {
        uploadButton.classList.add('hidden');
    }

    if (cancelButton) {
        cancelButton.classList.add('hidden');
    }
}

/**
 * Handle cancel button click
 */
function handleCancel() {
    resetPreview();
}

/**
 * Reset preview to original state
 */
function resetPreview() {
    const avatarInput = document.getElementById('avatar-input');
    const avatarPreview = document.getElementById('avatar-preview');

    // Reset file input
    if (avatarInput) {
        avatarInput.value = '';
    }

    // Restore original avatar
    if (avatarPreview && originalAvatarUrl) {
        avatarPreview.src = originalAvatarUrl;
    }

    // Hide action buttons
    hideActionButtons();

    // Clear any error messages
    clearErrors();
}

/**
 * Show error message
 * @param {string} message - The error message to display
 */
function showError(message) {
    const avatarInput = document.getElementById('avatar-input');
    
    if (!avatarInput) {
        return;
    }

    // Remove any existing error messages
    clearErrors();

    // Create error element
    const errorElement = document.createElement('p');
    errorElement.className = 'mt-2 text-sm text-red-600 avatar-preview-error';
    errorElement.textContent = message;

    // Insert after the file input
    avatarInput.parentNode.insertBefore(errorElement, avatarInput.nextSibling);
}

/**
 * Clear error messages
 */
function clearErrors() {
    const errorElements = document.querySelectorAll('.avatar-preview-error');
    errorElements.forEach(element => element.remove());
}

// Initialize on DOM content loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAvatarPreview);
} else {
    initializeAvatarPreview();
}
