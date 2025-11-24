<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isStaff();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        
        return [
            // Basic product information
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('products', 'slug')->ignore($productId)
            ],
            'description' => 'required|string|max:5000',
            'short_description' => 'nullable|string|max:500',
            
            // Category and brand relationships
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = \App\Models\Category::find($value);
                    if ($category && !$category->is_active) {
                        $fail('Selected category is not active.');
                    }
                }
            ],
            'brand_id' => [
                'required',
                'exists:brands,id',
                function ($attribute, $value, $fail) {
                    $brand = \App\Models\Brand::find($value);
                    if ($brand && !$brand->is_active) {
                        $fail('Selected brand is not active.');
                    }
                }
            ],
            
            // Pricing information
            'base_price' => 'required|numeric|min:0|max:999999.99',
            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'lt:base_price'
            ],
            'cost_price' => 'nullable|numeric|min:0|max:999999.99',
            
            // Product flags
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_best_seller' => 'boolean',
            'status' => 'required|in:active,inactive,discontinued,out_of_stock',
            
            // SEO metadata
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
            
            // Product images (for new uploads)
            'new_images' => 'nullable|array|max:10',
            'new_images.*' => 'image|mimes:jpeg,png,webp|max:5120', // 5MB max
            'primary_image_id' => 'nullable|exists:product_images,id',
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => 'exists:product_images,id',
            
            // Product variants (for updates)
            'variants' => 'nullable|array|max:50',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.sku' => [
                'required_with:variants',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                'distinct',
                function ($attribute, $value, $fail) use ($productId) {
                    $variantId = $this->input(str_replace('.sku', '.id', $attribute));
                    $query = \App\Models\ProductVariant::where('sku', $value);
                    
                    if ($variantId) {
                        $query->where('id', '!=', $variantId);
                    }
                    
                    if ($query->exists()) {
                        $fail('Variant SKU must be unique.');
                    }
                }
            ],
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.variant_type' => 'required_with:variants|string|max:100',
            'variants.*.variant_value' => 'required_with:variants|string|max:100',
            'variants.*.price_adjustment' => 'nullable|numeric|min:-999999.99|max:999999.99',
            'variants.*.is_active' => 'boolean',
            'variants.*.delete' => 'boolean', // Flag to mark variant for deletion
            
            // Product specifications (for updates)
            'specifications' => 'nullable|array|max:20',
            'specifications.*.id' => 'nullable|exists:product_specifications,id',
            'specifications.*.name' => 'required_with:specifications|string|max:255',
            'specifications.*.value' => 'required_with:specifications|string|max:1000',
            'specifications.*.display_order' => 'nullable|integer|min:0',
            'specifications.*.delete' => 'boolean', // Flag to mark specification for deletion
            
            // Status change confirmation
            'confirm_status_change' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Product SKU is required.',
            'sku.unique' => 'A product with this SKU already exists.',
            'sku.regex' => 'SKU must contain only uppercase letters, numbers, hyphens, and underscores.',
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'slug.unique' => 'A product with this slug already exists.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'description.required' => 'Product description is required.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'short_description.max' => 'Short description cannot exceed 500 characters.',
            'category_id.required' => 'Product category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'brand_id.required' => 'Product brand is required.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'base_price.required' => 'Base price is required.',
            'base_price.numeric' => 'Base price must be a valid number.',
            'base_price.min' => 'Base price must be at least 0.',
            'base_price.max' => 'Base price cannot exceed 999,999.99.',
            'sale_price.numeric' => 'Sale price must be a valid number.',
            'sale_price.lt' => 'Sale price must be less than base price.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'status.required' => 'Product status is required.',
            'status.in' => 'Invalid product status selected.',
            'meta_title.max' => 'Meta title cannot exceed 255 characters.',
            'meta_description.max' => 'Meta description cannot exceed 500 characters.',
            'meta_keywords.max' => 'Meta keywords cannot exceed 1000 characters.',
            'new_images.max' => 'Cannot upload more than 10 new images.',
            'new_images.*.image' => 'All uploaded files must be images.',
            'new_images.*.mimes' => 'Images must be JPEG, PNG, or WebP format.',
            'new_images.*.max' => 'Each image must be smaller than 5MB.',
            'primary_image_id.exists' => 'Selected primary image does not exist.',
            'remove_image_ids.*.exists' => 'Image to remove does not exist.',
            'variants.max' => 'Cannot have more than 50 variants.',
            'variants.*.sku.required_with' => 'Variant SKU is required.',
            'variants.*.sku.regex' => 'Variant SKU must contain only uppercase letters, numbers, hyphens, and underscores.',
            'variants.*.sku.distinct' => 'Variant SKUs must be unique within this product.',
            'variants.*.name.required_with' => 'Variant name is required.',
            'variants.*.variant_type.required_with' => 'Variant type is required.',
            'variants.*.variant_value.required_with' => 'Variant value is required.',
            'specifications.max' => 'Cannot have more than 20 specifications.',
            'specifications.*.name.required_with' => 'Specification name is required.',
            'specifications.*.value.required_with' => 'Specification value is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'brand_id' => 'brand',
            'base_price' => 'base price',
            'sale_price' => 'sale price',
            'cost_price' => 'cost price',
            'is_featured' => 'featured status',
            'is_new_arrival' => 'new arrival status',
            'is_best_seller' => 'best seller status',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'meta_keywords' => 'meta keywords',
            'primary_image_id' => 'primary image',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from name if not provided or empty
        if (!$this->has('slug') || empty($this->slug)) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name)
            ]);
        }

        // Ensure boolean values are properly cast
        $this->merge([
            'is_featured' => $this->boolean('is_featured', false),
            'is_new_arrival' => $this->boolean('is_new_arrival', false),
            'is_best_seller' => $this->boolean('is_best_seller', false),
            'confirm_status_change' => $this->boolean('confirm_status_change', false),
        ]);

        // Ensure variants have proper boolean casting
        if ($this->has('variants') && is_array($this->variants)) {
            $variants = $this->variants;
            foreach ($variants as $index => $variant) {
                $variants[$index]['is_active'] = isset($variant['is_active']) 
                    ? filter_var($variant['is_active'], FILTER_VALIDATE_BOOLEAN) 
                    : true;
                $variants[$index]['delete'] = isset($variant['delete']) 
                    ? filter_var($variant['delete'], FILTER_VALIDATE_BOOLEAN) 
                    : false;
            }
            $this->merge(['variants' => $variants]);
        }

        // Ensure specifications have proper boolean casting
        if ($this->has('specifications') && is_array($this->specifications)) {
            $specifications = $this->specifications;
            foreach ($specifications as $index => $specification) {
                $specifications[$index]['delete'] = isset($specification['delete']) 
                    ? filter_var($specification['delete'], FILTER_VALIDATE_BOOLEAN) 
                    : false;
            }
            $this->merge(['specifications' => $specifications]);
        }
    }

    /**
     * Get validated data with additional processing.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Remove processing flags from validated data
        if (is_array($validated)) {
            unset($validated['confirm_status_change']);
            unset($validated['remove_image_ids']);
        }
        
        return $validated;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');
            
            // Validate primary image belongs to this product
            if ($this->has('primary_image_id') && $this->primary_image_id) {
                $image = \App\Models\ProductImage::find($this->primary_image_id);
                if ($image && $image->product_id !== $product->id) {
                    $validator->errors()->add('primary_image_id', 'Primary image must belong to this product.');
                }
            }

            // Validate remove image IDs belong to this product
            if ($this->has('remove_image_ids') && is_array($this->remove_image_ids)) {
                foreach ($this->remove_image_ids as $index => $imageId) {
                    $image = \App\Models\ProductImage::find($imageId);
                    if ($image && $image->product_id !== $product->id) {
                        $validator->errors()->add("remove_image_ids.{$index}", 'Image to remove must belong to this product.');
                    }
                }
            }

            // Validate that sale price is less than base price
            if ($this->has('sale_price') && $this->has('base_price')) {
                if ($this->sale_price && $this->sale_price >= $this->base_price) {
                    $validator->errors()->add('sale_price', 'Sale price must be less than base price.');
                }
            }

            // Validate variant SKUs don't conflict with main product SKU
            if ($this->has('variants') && $this->has('sku')) {
                $mainSku = $this->sku;
                foreach ($this->variants ?? [] as $index => $variant) {
                    if (isset($variant['sku']) && $variant['sku'] === $mainSku) {
                        $validator->errors()->add("variants.{$index}.sku", 'Variant SKU cannot be the same as product SKU.');
                    }
                }
            }

            // Validate variants belong to this product (for existing variants)
            if ($this->has('variants') && is_array($this->variants)) {
                foreach ($this->variants as $index => $variant) {
                    if (isset($variant['id']) && $variant['id']) {
                        $existingVariant = \App\Models\ProductVariant::find($variant['id']);
                        if ($existingVariant && $existingVariant->product_id !== $product->id) {
                            $validator->errors()->add("variants.{$index}.id", 'Variant must belong to this product.');
                        }
                    }
                }
            }

            // Validate specifications belong to this product (for existing specifications)
            if ($this->has('specifications') && is_array($this->specifications)) {
                foreach ($this->specifications as $index => $specification) {
                    if (isset($specification['id']) && $specification['id']) {
                        $existingSpec = \App\Models\ProductSpecification::find($specification['id']);
                        if ($existingSpec && $existingSpec->product_id !== $product->id) {
                            $validator->errors()->add("specifications.{$index}.id", 'Specification must belong to this product.');
                        }
                    }
                }
            }

            // Validate status change confirmation for critical status changes
            if ($this->has('status') && $product) {
                $newStatus = $this->status;
                $currentStatus = $product->status;
                
                $criticalChanges = [
                    'active' => ['discontinued', 'out_of_stock'],
                    'inactive' => ['discontinued'],
                ];
                
                if (isset($criticalChanges[$currentStatus]) && 
                    in_array($newStatus, $criticalChanges[$currentStatus]) && 
                    !$this->confirm_status_change) {
                    $validator->errors()->add('status', 'Please confirm this status change as it may affect product availability.');
                }
            }
        });
    }
}