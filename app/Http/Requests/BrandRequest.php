<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        $brandId = $this->route('brand')?->id;
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brandId)
            ],
            'description' => 'nullable|string|max:1000',
            'logo_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'confirm_deactivation' => 'nullable|boolean'
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
            'name.required' => 'Brand name is required.',
            'name.unique' => 'A brand with this name already exists.',
            'name.max' => 'Brand name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'logo_url.url' => 'Logo URL must be a valid URL.',
            'logo_url.max' => 'Logo URL cannot exceed 500 characters.',
            'website_url.url' => 'Website URL must be a valid URL.',
            'website_url.max' => 'Website URL cannot exceed 500 characters.',
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
            'logo_url' => 'logo URL',
            'website_url' => 'website URL',
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

        if ($this->has('confirm_deactivation')) {
            $this->merge([
                'confirm_deactivation' => $this->boolean('confirm_deactivation', false)
            ]);
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
        
        // Remove confirm_deactivation from validated data as it's not a model attribute
        if (is_array($validated) && isset($validated['confirm_deactivation'])) {
            unset($validated['confirm_deactivation']);
        }
        
        return $validated;
    }
}