import { InlineCreator } from './inline-creator.js';

// Ensure axios is properly configured
if (window.axios) {
    // Set default headers
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    window.axios.defaults.headers.common['Accept'] = 'application/json';
    
    // Get CSRF token from meta tag
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    } else {
        console.error('CSRF token not found in page');
    }
}

// Initialize inline creators when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize category creator if elements exist
    const categoryModal = document.getElementById('inline-category-modal');
    const categoryDropdown = document.getElementById('category_id');
    
    if (categoryModal && categoryDropdown) {
        window.categoryCreator = new InlineCreator(
            'inline-category-modal',
            'category_id',
            '/staff/categories/inline'
        );
        console.log('Category inline creator initialized');
    }
    
    // Initialize brand creator if elements exist
    const brandModal = document.getElementById('inline-brand-modal');
    const brandDropdown = document.getElementById('brand_id');
    
    if (brandModal && brandDropdown) {
        window.brandCreator = new InlineCreator(
            'inline-brand-modal',
            'brand_id',
            '/staff/brands/inline'
        );
        console.log('Brand inline creator initialized');
    }
});

export { InlineCreator };
