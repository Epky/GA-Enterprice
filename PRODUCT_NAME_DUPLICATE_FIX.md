# Product Name Duplicate Fix

## Problem
Ang sistema ay nag-error kapag may duplicate product name, kahit na ang product name ay dapat pwedeng mag-duplicate. Ang SKU lang ang dapat unique.

## Root Cause
Ang problema ay hindi sa product name validation, kundi sa **slug generation**:

1. Kapag nag-create ng product, ang slug ay auto-generated from product name
2. Ang slug ay may **unique constraint** sa database
3. Kung may existing product na "Lipstick Red" (slug: "lipstick-red"), at mag-create ng another "Lipstick Red", mag-error sa slug uniqueness
4. Ang `generateUniqueSlug()` method ay dapat mag-append ng number (e.g., "lipstick-red-1", "lipstick-red-2"), pero hindi ito properly na-trigger

## Solution Applied

### 1. ProductService.php - createProduct() method
**Before:**
```php
// Generate slug if not provided
if (empty($data['slug'])) {
    $data['slug'] = $this->generateUniqueSlug($data['name']);
}
```

**After:**
```php
// ALWAYS generate a unique slug from the name to prevent conflicts
// Even if slug is provided, we ensure it's unique
$data['slug'] = $this->generateUniqueSlug($data['name']);
```

**Why:** Ensures na lagi may unique slug generation, hindi lang pag empty.

### 2. ProductStoreRequest.php - prepareForValidation()
**Before:**
```php
// Generate slug from name if not provided
if (!$this->has('slug') || empty($this->slug)) {
    $this->merge([
        'slug' => \Illuminate\Support\Str::slug($this->name)
    ]);
}
```

**After:**
```php
// Note: Slug will be auto-generated in ProductService to ensure uniqueness
// We don't generate it here to avoid duplicate slug validation errors
// The ProductService will handle slug generation with proper uniqueness checks
```

**Why:** Inalis ang slug generation sa request level para hindi mag-conflict sa validation.

### 3. ProductStoreRequest.php - slug validation rules
**Before:**
```php
'slug' => [
    'nullable',
    'string',
    'max:255',
    'regex:/^[a-z0-9\-]+$/',
    Rule::unique('products', 'slug')
],
```

**After:**
```php
// Slug is auto-generated in ProductService with uniqueness handling
// No validation needed here as it will be created automatically
'slug' => 'nullable|string|max:255|regex:/^[a-z0-9\-]+$/',
```

**Why:** Inalis ang unique validation sa request level kasi ang ProductService na ang bahala.

## How It Works Now

1. User enters product name: "Lipstick Red"
2. User enters SKU: "LIP-RED-001" (must be unique)
3. System calls `ProductService::createProduct()`
4. Service generates unique slug:
   - First product: "lipstick-red"
   - Second product with same name: "lipstick-red-1"
   - Third product with same name: "lipstick-red-2"
5. Product is created successfully

## Validation Rules Summary

| Field | Unique? | Notes |
|-------|---------|-------|
| **SKU** | ✅ YES | Must be unique across all products |
| **Product Name** | ❌ NO | Can have duplicates (e.g., multiple "Lipstick Red" variants) |
| **Slug** | ✅ YES | Auto-generated with uniqueness (e.g., "lipstick-red-1") |

## Testing
Test by creating multiple products with the same name but different SKUs:

1. Product 1: Name="Lipstick Red", SKU="LIP-RED-001" → slug="lipstick-red"
2. Product 2: Name="Lipstick Red", SKU="LIP-RED-002" → slug="lipstick-red-1"
3. Product 3: Name="Lipstick Red", SKU="LIP-RED-003" → slug="lipstick-red-2"

All should create successfully! ✅

## Files Modified
- `app/Services/ProductService.php`
- `app/Http/Requests/ProductStoreRequest.php`
