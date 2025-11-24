# Help Tooltip Usage Guide

## Overview

The help tooltip system provides inline contextual help for form fields throughout the staff product management interface. This guide explains how to implement and use help tooltips in your views.

## Components

### 1. Help Tooltip Component

Location: `resources/views/components/help-tooltip.blade.php`

This is a reusable Blade component that displays a tooltip with help text when users hover over a question mark icon.

### 2. Help Configuration

Location: `config/help.php`

Contains all help text organized by feature area (product, pricing, inventory, etc.).

### 3. Help Text Helper

Location: `app/Helpers/HelpTextHelper.php`

Provides convenient methods to access help text in views and controllers.

## Usage Examples

### Basic Usage in Blade Templates

```blade
<!-- Product Name Field with Tooltip -->
<div>
    <label for="name" class="flex items-center text-sm font-medium text-gray-700 mb-1">
        Product Name *
        <x-help-tooltip position="top">
            <x-slot name="content">
                {{ config('help.product.name') }}
            </x-slot>
        </x-help-tooltip>
    </label>
    <input type="text" name="name" id="name" class="w-full rounded-md border-gray-300">
</div>
```

### Using Helper Class

```blade
@php
use App\Helpers\HelpTextHelper;
@endphp

<!-- SKU Field with Tooltip -->
<div>
    <label for="sku" class="flex items-center text-sm font-medium text-gray-700 mb-1">
        SKU *
        @if(HelpTextHelper::has('product', 'sku'))
            <x-help-tooltip position="top">
                <x-slot name="content">
                    {{ HelpTextHelper::get('product', 'sku') }}
                </x-slot>
            </x-help-tooltip>
        @endif
    </label>
    <input type="text" name="sku" id="sku" class="w-full rounded-md border-gray-300">
</div>
```

### Different Tooltip Positions

```blade
<!-- Top Position (default) -->
<x-help-tooltip position="top">
    <x-slot name="content">Help text appears above</x-slot>
</x-help-tooltip>

<!-- Bottom Position -->
<x-help-tooltip position="bottom">
    <x-slot name="content">Help text appears below</x-slot>
</x-help-tooltip>

<!-- Left Position -->
<x-help-tooltip position="left">
    <x-slot name="content">Help text appears to the left</x-slot>
</x-help-tooltip>

<!-- Right Position -->
<x-help-tooltip position="right">
    <x-slot name="content">Help text appears to the right</x-slot>
</x-help-tooltip>
```

### Complete Form Field Example

```blade
<div class="space-y-6">
    <!-- Product Name -->
    <div>
        <label for="name" class="flex items-center text-sm font-medium text-gray-700 mb-1">
            Product Name *
            <x-help-tooltip position="top">
                <x-slot name="content">
                    {{ config('help.product.name') }}
                </x-slot>
            </x-help-tooltip>
        </label>
        <input type="text" 
               name="name" 
               id="name"
               value="{{ old('name') }}"
               required
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
               placeholder="Enter product name">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Price -->
    <div>
        <label for="price" class="flex items-center text-sm font-medium text-gray-700 mb-1">
            Price *
            <x-help-tooltip position="top">
                <x-slot name="content">
                    {{ config('help.pricing.price') }}
                </x-slot>
            </x-help-tooltip>
        </label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
            <input type="number" 
                   name="price" 
                   id="price"
                   value="{{ old('price') }}"
                   step="0.01"
                   min="0"
                   required
                   class="w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="0.00">
        </div>
        @error('price')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Category -->
    <div>
        <label for="category_id" class="flex items-center text-sm font-medium text-gray-700 mb-1">
            Category *
            <x-help-tooltip position="top">
                <x-slot name="content">
                    {{ config('help.product.category') }}
                </x-slot>
            </x-help-tooltip>
        </label>
        <select name="category_id" 
                id="category_id"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select a category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
```

## Adding New Help Text

### Step 1: Add to Configuration

Edit `config/help.php` and add your help text:

```php
'product' => [
    'name' => 'Enter a clear, descriptive product name...',
    'sku' => 'Unique product identifier...',
    // Add new field help text
    'new_field' => 'Help text for new field...',
],
```

### Step 2: Use in View

```blade
<div>
    <label for="new_field" class="flex items-center text-sm font-medium text-gray-700 mb-1">
        New Field
        <x-help-tooltip position="top">
            <x-slot name="content">
                {{ config('help.product.new_field') }}
            </x-slot>
        </x-help-tooltip>
    </label>
    <input type="text" name="new_field" id="new_field" class="w-full rounded-md border-gray-300">
</div>
```

## Best Practices

### 1. Tooltip Placement

- Use `position="top"` for fields in the middle or bottom of forms
- Use `position="bottom"` for fields near the top of forms
- Use `position="left"` or `position="right"` for fields in tight spaces

### 2. Help Text Content

- Keep help text concise (1-3 sentences)
- Focus on what the field does and how to use it
- Include examples when helpful
- Mention validation requirements
- Avoid technical jargon

### 3. When to Use Tooltips

✅ Use tooltips for:
- Complex or technical fields
- Fields with specific format requirements
- Fields that affect other parts of the system
- Fields with validation rules that aren't obvious

❌ Don't use tooltips for:
- Self-explanatory fields (e.g., "Email")
- Fields with clear labels and placeholders
- Every single field (causes clutter)

### 4. Accessibility

The tooltip component includes:
- Proper ARIA attributes
- Keyboard navigation support
- Focus indicators
- Screen reader compatibility

### 5. Styling Consistency

Always use the component as-is to maintain consistent styling across the application. If you need custom styling, extend the component rather than creating inline styles.

## Troubleshooting

### Tooltip Not Appearing

1. Check that the help text exists in `config/help.php`
2. Verify the component is properly included: `<x-help-tooltip>`
3. Clear config cache: `php artisan config:clear`
4. Check browser console for JavaScript errors

### Tooltip Position Wrong

1. Try different position values: top, bottom, left, right
2. Check if parent container has `overflow: hidden`
3. Ensure adequate space around the field

### Help Text Not Loading

1. Clear config cache: `php artisan config:clear`
2. Check file permissions on `config/help.php`
3. Verify syntax in config file (proper PHP array format)

## Integration with Existing Forms

To add tooltips to existing forms:

1. Identify fields that need help text
2. Add help text to `config/help.php`
3. Update form labels to include tooltip component
4. Test tooltip positioning and content
5. Clear config cache

## Example: Complete Product Form with Tooltips

See `resources/views/staff/products/create.blade.php` for a complete implementation example with tooltips on all major fields.

## Additional Resources

- User Guide: `docs/STAFF_PRODUCT_MANAGEMENT_GUIDE.md`
- Troubleshooting: `docs/TROUBLESHOOTING_GUIDE.md`
- Quick Reference: Available at `/staff/help/quick-reference`

## Support

For questions or issues with the help system:
- Check the troubleshooting guide
- Review existing implementations
- Contact the development team
