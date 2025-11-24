<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Management Help Text
    |--------------------------------------------------------------------------
    |
    | This file contains all help text and tooltips for the staff product
    | management system. Organized by feature area for easy maintenance.
    |
    */

    'product' => [
        'name' => 'Enter a clear, descriptive product name that customers will see. Use proper capitalization and avoid abbreviations.',
        'sku' => 'Unique product identifier (Stock Keeping Unit). Leave blank to auto-generate. Use letters, numbers, and hyphens only.',
        'slug' => 'URL-friendly version of product name. Auto-generated from product name. Used in product page URLs.',
        'description' => 'Detailed product description including features, benefits, ingredients, and usage instructions. Supports basic HTML formatting.',
        'short_description' => 'Brief product summary (2-3 sentences) shown in product listings and previews.',
        'category' => 'Select the most appropriate category for this product. Helps customers find products through navigation.',
        'brand' => 'Select the product manufacturer or brand. Create new brands in Brand Management if needed.',
        'status' => 'Active: Visible to customers | Inactive: Hidden from store | Out of Stock: Visible but not purchasable | Discontinued: Archived',
        'is_featured' => 'Featured products appear in special sections on homepage and category pages. Use for promotions or bestsellers.',
        'is_new_arrival' => 'Mark as new arrival to display in "New Products" section. Automatically expires after 30 days.',
        'is_best_seller' => 'Highlight popular products. Manually set or auto-updated based on sales data.',
        'tags' => 'Keywords for search optimization. Separate with commas. Example: moisturizer, anti-aging, organic',
    ],

    'pricing' => [
        'price' => 'Regular selling price. Enter numbers only without currency symbols. Example: 29.99',
        'sale_price' => 'Discounted price during promotions. Must be less than regular price. Leave blank if no sale.',
        'cost_price' => 'Your cost for this product. Used for profit margin calculations. Not visible to customers.',
        'compare_at_price' => 'Original price before discount. Shows "was $X, now $Y" to customers. Must be higher than sale price.',
    ],

    'inventory' => [
        'quantity' => 'Current stock available for sale. Enter whole numbers only. Updates automatically with orders.',
        'reserved_quantity' => 'Stock allocated to pending orders. Automatically managed by the system.',
        'reorder_level' => 'Minimum stock level before low stock alert. Set based on typical sales velocity and reorder time.',
        'reorder_quantity' => 'Suggested quantity to order when stock is low. Based on typical order size and lead time.',
        'track_inventory' => 'Enable to track stock levels. Disable for digital products or services that don\'t require inventory tracking.',
        'allow_backorder' => 'Allow customers to order when out of stock. Product will be marked as "Available on backorder".',
        'location' => 'Physical storage location or warehouse. Useful for multi-location inventory management.',
    ],

    'variant' => [
        'name' => 'Variant name describing the option. Example: "Red", "Large", "50ml". Keep consistent across products.',
        'sku' => 'Unique SKU for this variant. Must be different from base product and other variants.',
        'price_adjustment' => 'Price difference from base product. Use positive for increase, negative for decrease. Example: +5.00 or -2.50',
        'price' => 'Absolute price for this variant. Overrides base product price if set.',
        'quantity' => 'Stock quantity for this specific variant. Tracked separately from other variants.',
        'is_active' => 'Enable to make variant available for purchase. Disable to hide without deleting.',
    ],

    'image' => [
        'upload' => 'Upload product images. Supported formats: JPEG, PNG, WebP. Maximum size: 5MB per image. Recommended: 1200x1200px.',
        'primary' => 'Primary image appears first in galleries and listings. Click star icon to set as primary.',
        'alt_text' => 'Descriptive text for accessibility and SEO. Describe what\'s in the image. Example: "Blue moisturizer bottle on white background".',
        'order' => 'Drag and drop images to reorder. Order affects how images appear in customer gallery.',
        'optimization' => 'Images are automatically resized and optimized for web. Original files are preserved in storage.',
    ],

    'category' => [
        'name' => 'Category name shown to customers. Use clear, descriptive names. Example: "Face Moisturizers".',
        'slug' => 'URL-friendly identifier. Auto-generated from name. Used in category page URLs.',
        'parent' => 'Select parent category to create subcategory. Leave blank for top-level category. Maximum 4 levels deep.',
        'description' => 'Category description for SEO and category pages. Explain what products belong in this category.',
        'image' => 'Category banner image. Displayed on category pages. Recommended size: 1920x400px.',
        'is_active' => 'Active categories appear in navigation. Inactive categories are hidden but products remain assigned.',
        'order' => 'Display order in navigation. Lower numbers appear first. Drag and drop to reorder.',
    ],

    'brand' => [
        'name' => 'Brand or manufacturer name. Example: "L\'Oréal", "Neutrogena".',
        'slug' => 'URL-friendly identifier. Auto-generated from name. Used in brand page URLs.',
        'description' => 'Brand story and information. Displayed on brand pages and product details.',
        'logo' => 'Brand logo image. Displayed on product pages and brand directory. Recommended: 400x400px PNG with transparency.',
        'website' => 'Brand\'s official website URL. Include https://. Shown as link on brand pages.',
        'is_active' => 'Active brands appear in filters and brand directory. Inactive brands are hidden but products remain assigned.',
    ],

    'seo' => [
        'meta_title' => 'Page title for search engines. 50-60 characters recommended. Include main keywords.',
        'meta_description' => 'Page description for search results. 150-160 characters. Compelling summary with keywords.',
        'meta_keywords' => 'Comma-separated keywords. Example: moisturizer, anti-aging, organic skincare. Used for internal search.',
        'og_title' => 'Title when shared on social media. Defaults to meta title if blank.',
        'og_description' => 'Description when shared on social media. Defaults to meta description if blank.',
        'og_image' => 'Image when shared on social media. Uses primary product image if blank. Recommended: 1200x630px.',
    ],

    'shipping' => [
        'weight' => 'Product weight in grams. Used for shipping calculations. Include packaging weight.',
        'length' => 'Package length in centimeters. Used for shipping calculations and carrier selection.',
        'width' => 'Package width in centimeters. Used for shipping calculations and carrier selection.',
        'height' => 'Package height in centimeters. Used for shipping calculations and carrier selection.',
        'requires_shipping' => 'Enable for physical products. Disable for digital products or services.',
    ],

    'promotion' => [
        'name' => 'Internal promotion name. Not visible to customers. Example: "Summer Sale 2025".',
        'description' => 'Promotion details and terms. Visible to customers on product pages.',
        'discount_type' => 'Percentage: Discount as % of price | Fixed: Discount as fixed amount | Buy X Get Y: Quantity-based discount.',
        'discount_value' => 'Discount amount. For percentage: enter 10 for 10%. For fixed: enter amount like 5.00.',
        'start_date' => 'Promotion start date and time. Promotion activates automatically at this time.',
        'end_date' => 'Promotion end date and time. Promotion deactivates automatically at this time.',
        'applicable_to' => 'All Products: Site-wide | Selected Products: Choose specific products | Categories: Apply to entire categories.',
        'minimum_quantity' => 'Minimum quantity required for promotion. Leave blank for no minimum.',
        'maximum_uses' => 'Maximum number of times promotion can be used. Leave blank for unlimited.',
    ],

    'bulk_operations' => [
        'select_all' => 'Select all products on current page. Use filters to narrow selection before bulk operations.',
        'status_update' => 'Change status for all selected products. Useful for seasonal products or bulk deactivation.',
        'price_update' => 'Update prices for selected products. Choose increase/decrease by percentage or fixed amount.',
        'category_update' => 'Move selected products to different category. Original category assignment is replaced.',
        'export' => 'Export selected products to CSV file. Includes all product data for backup or analysis.',
    ],

    'search_filter' => [
        'search' => 'Search by product name, SKU, or description. Partial matches supported. Case-insensitive.',
        'category_filter' => 'Filter products by category. Includes products in subcategories.',
        'brand_filter' => 'Filter products by brand. Select multiple brands to see all matching products.',
        'status_filter' => 'Filter by product status. Useful for finding inactive or out-of-stock products.',
        'stock_filter' => 'Filter by stock level. Low Stock: Below reorder level | Out of Stock: Zero quantity.',
        'featured_filter' => 'Show only featured products. Useful for managing homepage and promotional displays.',
        'date_filter' => 'Filter by creation or update date. Useful for finding recently added or modified products.',
    ],

    'inventory_movement' => [
        'type' => 'Purchase: New stock received | Sale: Sold to customer | Adjustment: Manual correction | Return: Customer return | Damage: Damaged/lost stock.',
        'quantity' => 'Quantity to add (positive) or remove (negative). Enter whole numbers only.',
        'reference' => 'Reference number for this movement. Example: PO number, invoice number, or order ID.',
        'notes' => 'Additional details about this movement. Required for adjustments and damage. Example: "Damaged in shipping".',
        'location' => 'Storage location for this inventory. Useful for multi-warehouse operations.',
    ],
];
