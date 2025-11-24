/**
 * Product Visibility Management JavaScript
 * Handles quick visibility toggles and status updates
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize visibility management if on the visibility page
    if (document.getElementById('bulkVisibilityForm')) {
        initializeVisibilityManagement();
    }
});

function initializeVisibilityManagement() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkForm = document.getElementById('bulkVisibilityForm');

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    // Update selected count
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
        if (selectedCountSpan) {
            selectedCountSpan.textContent = `${selectedCount} products selected`;
        }
        if (bulkActionBtn) {
            bulkActionBtn.disabled = selectedCount === 0;
        }
    }

    // Add selected product IDs to form on submit
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                e.preventDefault();
                alert('Please select at least one product');
                return;
            }

            // Remove existing product inputs
            bulkForm.querySelectorAll('input[name="products[]"]').forEach(input => input.remove());

            // Add selected product IDs
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'products[]';
                input.value = id;
                bulkForm.appendChild(input);
            });
        });
    }

    // Quick status change
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const newStatus = this.value;
            
            quickUpdateVisibility(productId, 'status', newStatus);
        });
    });

    // Toggle marketing flags
    document.querySelectorAll('.toggle-flag').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const flag = this.dataset.flag;
            const currentValue = this.classList.contains('bg-blue-500') || 
                               this.classList.contains('bg-purple-500') || 
                               this.classList.contains('bg-yellow-500');
            
            quickUpdateVisibility(productId, flag, !currentValue, this);
        });
    });
}

/**
 * Quick update product visibility via AJAX
 */
function quickUpdateVisibility(productId, field, value, buttonElement = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }

    fetch(`/staff/products/${productId}/quick-toggle-visibility`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.content
        },
        body: JSON.stringify({
            field: field,
            value: value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Updated successfully');
            
            // Update button appearance if it's a flag toggle
            if (buttonElement && field !== 'status') {
                updateFlagButtonAppearance(buttonElement, field, value);
            }
        } else {
            showErrorMessage(data.message || 'Failed to update');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to update visibility');
    });
}

/**
 * Update flag button appearance after toggle
 */
function updateFlagButtonAppearance(button, flag, isActive) {
    if (isActive) {
        button.classList.remove('bg-gray-200', 'text-gray-700');
        if (flag === 'is_featured') {
            button.classList.add('bg-blue-500', 'text-white');
        } else if (flag === 'is_new_arrival') {
            button.classList.add('bg-purple-500', 'text-white');
        } else if (flag === 'is_best_seller') {
            button.classList.add('bg-yellow-500', 'text-white');
        }
    } else {
        button.classList.remove('bg-blue-500', 'bg-purple-500', 'bg-yellow-500', 'text-white');
        button.classList.add('bg-gray-200', 'text-gray-700');
    }
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    showToast(message, 'success');
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    showToast(message, 'error');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Fade in
    setTimeout(() => {
        toast.style.opacity = '1';
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        quickUpdateVisibility,
        showSuccessMessage,
        showErrorMessage
    };
}
