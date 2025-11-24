<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
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
        return [
            // Basic product information
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('products', 'sku')
            ],
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('products', 'slug')
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
            
            // Product images
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,webp|max:5120', // 5MB max
            'primary_image_index' => 'nullable|integer|min:0',
            
            // Product variants
            'variants' => 'nullable|array|max:50',
            'variants.*.sku' => [
                'required_with:variants',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                'distinct',
                Rule::unique('product_variants', 'sku')
            ],
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.variant_type' => 'required_with:variants|string|max:100',
            'variants.*.variant_value' => 'required_with:variants|string|max:100',
            'variants.*.price_adjustment' => 'nullable|numeric|min:-999999.99|max:999999.99',
            'variants.*.is_active' => 'boolean',
            
            // Product specifications
            'specifications' => 'nullable|array|max:20',
            'specifications.*.name' => 'required_with:specifications|string|max:255',
            'specifications.*.value' => 'required_with:specifications|string|max:1000',
            'specifications.*.display_order' => 'nullable|integer|min:0',
            
            // Initial inventory (optional)
            'initial_stock' => 'nullable|integer|min:0|max:999999',
            'reorder_level' => 'nullable|integer|min:0|max:999999',
            'reorder_quantity' => 'nullable|integer|min:1|max:999999',
            'location' => 'nullable|string|max:100',
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
            'images.max' => 'Cannot upload more than 10 images.',
            'images.*.image' => 'All uploaded files must be images.',
            'images.*.mimes' => 'Images must be JPEG, PNG, or WebP format.',
            'images.*.max' => 'Each image must be smaller than 5MB.',
            'variants.max' => 'Cannot create more than 50 variants.',
            'variants.*.sku.required_with' => 'Variant SKU is required.',
            'variants.*.sku.unique' => 'Variant SKU must be unique.',
            'variants.*.sku.regex' => 'Variant SKU must contain only uppercase letters, numbers, hyphens, and underscores.',
            'variants.*.sku.distinct' => 'Variant SKUs must be unique within this product.',
            'variants.*.name.required_with' => 'Variant name is required.',
            'variants.*.variant_type.required_with' => 'Variant type is required.',
            'variants.*.variant_value.required_with' => 'Variant value is required.',
            'specifications.max' => 'Cannot add more than 20 specifications.',
            'specifications.*.name.required_with' => 'Specification name is required.',
            'specifications.*.value.required_with' => 'Specification value is required.',
            'initial_stock.integer' => 'Initial stock must be a whole number.',
            'initial_stock.min' => 'Initial stock cannot be negative.',
            'reorder_level.integer' => 'Reorder level must be a whole number.',
            'reorder_quantity.integer' => 'Reorder quantity must be a whole number.',
            'reorder_quantity.min' => 'Reorder quantity must be at least 1.',
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
            'initial_stock' => 'initial stock',
            'reorder_level' => 'reorder level',
            'reorder_quantity' => 'reorder quantity',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from name if not provided
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
        ]);

        // Set default location if not provided
        if (!$this->has('location') || empty($this->location)) {
            $this->merge([
                'location' => 'main_warehouse'
            ]);
        }

        // Ensure variants have proper boolean casting
        if ($this->has('variants') && is_array($this->variants)) {
            $variants = $this->variants;
            foreach ($variants as $index => $variant) {
                $variants[$index]['is_active'] = isset($variant['is_active']) 
                    ? filter_var($variant['is_active'], FILTER_VALIDATE_BOOLEAN) 
                    : true;
            }
            $this->merge(['variants' => $variants]);
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
        
        // Remove primary_image_index from validated data as it's processed separately
        if (is_array($validated) && isset($validated['primary_image_index'])) {
            unset($validated['primary_image_index']);
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
            // Validate primary image index
            if ($this->has('primary_image_index') && $this->has('images')) {
                $primaryIndex = $this->primary_image_index;
                $imageCount = count($this->images ?? []);
                
                if ($primaryIndex >= $imageCount) {
                    $validator->errors()->add('primary_image_index', 'Primary image index is invalid.');
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
        });
    }
}