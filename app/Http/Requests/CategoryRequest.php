<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For inline creation, always authorize if user is authenticated
        // The middleware already checks for staff/admin role
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;
        
        // Simplified rules for inline creation
        if ($this->isInlineCreation()) {
            return [
                'name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    Rule::unique('categories', 'name')->ignore($categoryId)
                ],
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:categories,id',
                'is_active' => 'boolean',
            ];
        }
        
        // Full rules for regular creation/update
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId)
            ],
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if (!$value) return;
                    
                    try {
                        // Prevent self-reference
                        if ($value == $categoryId) {
                            $fail('Category cannot be its own parent.');
                            return;
                        }
                        
                        // Prevent circular reference
                        if ($categoryId) {
                            $category = \App\Models\Category::find($categoryId);
                            if ($category) {
                                $descendants = $category->getAllDescendants();
                                if ($descendants->contains('id', $value)) {
                                    $fail('Cannot move category under its own descendant.');
                                    return;
                                }
                            }
                        }
                        
                        // Check if parent is active
                        $parent = \App\Models\Category::find($value);
                        if ($parent && !$parent->is_active) {
                            $fail('Cannot assign inactive parent category.');
                        }
                    } catch (\Exception $e) {
                        Log::error('Parent validation error: ' . $e->getMessage());
                        // Don't fail validation on database errors
                    }
                }
            ],
            'image_url' => 'nullable|url|max:500',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'confirm_deactivation' => 'nullable|boolean'
        ];
    }

    /**
     * Check if this is an inline creation request
     */
    protected function isInlineCreation(): bool
    {
        return $this->ajax() && 
               $this->isMethod('POST') && 
               str_contains($this->path(), '/inline');
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.min' => 'Category name must be at least 2 characters.',
            'name.unique' => 'A category with this name already exists.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'parent_id.exists' => 'Selected parent category does not exist.',
            'image_url.url' => 'Image URL must be a valid URL.',
            'image_url.max' => 'Image URL cannot exceed 500 characters.',
            'display_order.integer' => 'Display order must be a number.',
            'display_order.min' => 'Display order must be at least 0.',
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
            'parent_id' => 'parent category',
            'image_url' => 'image URL',
            'display_order' => 'display order',
            'is_active' => 'active status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure boolean values are properly cast
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active', true)
            ]);
        }

        // For inline creation, always set is_active to true if not provided
        if ($this->isInlineCreation() && !$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if ($this->has('confirm_deactivation')) {
            $this->merge([
                'confirm_deactivation' => $this->boolean('confirm_deactivation', false)
            ]);
        }

        // Set default display order if not provided
        if (!$this->has('display_order') || $this->display_order === null) {
            try {
                $parentId = $this->input('parent_id');
                $maxOrder = \App\Models\Category::where('parent_id', $parentId)
                    ->max('display_order') ?? 0;
                $this->merge(['display_order' => $maxOrder + 1]);
            } catch (\Exception $e) {
                Log::error('Failed to get max display_order: ' . $e->getMessage());
                $this->merge(['display_order' => 0]);
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Add custom logic for handling validation failures if needed
        parent::failedValidation($validator);
    }
}