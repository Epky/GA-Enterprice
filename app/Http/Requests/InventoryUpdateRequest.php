<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryUpdateRequest extends FormRequest
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
            // Product and variant identification
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            
            // Location information
            'location' => 'required|string|max:100',
            
            // Quantity updates
            'quantity_available' => 'nullable|integer|min:0|max:999999',
            'quantity_reserved' => 'nullable|integer|min:0|max:999999',
            'quantity_sold' => 'nullable|integer|min:0|max:999999',
            
            // Reorder management
            'reorder_level' => 'nullable|integer|min:0|max:999999',
            'reorder_quantity' => 'nullable|integer|min:1|max:999999',
            
            // Bulk operations
            'bulk_updates' => 'nullable|array|max:100',
            'bulk_updates.*.inventory_id' => 'required_with:bulk_updates|exists:inventory,id',
            'bulk_updates.*.quantity_available' => 'nullable|integer|min:0|max:999999',
            'bulk_updates.*.reorder_level' => 'nullable|integer|min:0|max:999999',
            'bulk_updates.*.reorder_quantity' => 'nullable|integer|min:1|max:999999',
            
            // Stock adjustment
            'adjustment_type' => 'nullable|in:increase,decrease,set',
            'adjustment_quantity' => 'nullable|integer|min:1|max:999999',
            'adjustment_reason' => 'nullable|string|max:500',
            'movement_type' => [
                'nullable',
                Rule::in([
                    'purchase', 'sale', 'return', 'adjustment', 'transfer', 
                    'damage', 'expired', 'theft', 'recount', 'promotion'
                ])
            ],
            
            // Transfer operations
            'transfer_to_location' => 'nullable|string|max:100',
            'transfer_quantity' => 'nullable|integer|min:1|max:999999',
            'transfer_notes' => 'nullable|string|max:1000',
            
            // Reservation operations
            'reserve_quantity' => 'nullable|integer|min:1|max:999999',
            'release_quantity' => 'nullable|integer|min:1|max:999999',
            'fulfill_quantity' => 'nullable|integer|min:1|max:999999',
            'reservation_reference' => 'nullable|string|max:255',
            
            // Audit and tracking
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
            'supplier_reference' => 'nullable|string|max:255',
            
            // Confirmation flags
            'confirm_negative_adjustment' => 'nullable|boolean',
            'confirm_bulk_update' => 'nullable|boolean',
            'force_update' => 'nullable|boolean',
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
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'variant_id.exists' => 'Selected product variant does not exist.',
            'location.required' => 'Location is required.',
            'location.max' => 'Location name cannot exceed 100 characters.',
            'quantity_available.integer' => 'Available quantity must be a whole number.',
            'quantity_available.min' => 'Available quantity cannot be negative.',
            'quantity_available.max' => 'Available quantity cannot exceed 999,999.',
            'quantity_reserved.integer' => 'Reserved quantity must be a whole number.',
            'quantity_reserved.min' => 'Reserved quantity cannot be negative.',
            'quantity_reserved.max' => 'Reserved quantity cannot exceed 999,999.',
            'quantity_sold.integer' => 'Sold quantity must be a whole number.',
            'quantity_sold.min' => 'Sold quantity cannot be negative.',
            'quantity_sold.max' => 'Sold quantity cannot exceed 999,999.',
            'reorder_level.integer' => 'Reorder level must be a whole number.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'reorder_level.max' => 'Reorder level cannot exceed 999,999.',
            'reorder_quantity.integer' => 'Reorder quantity must be a whole number.',
            'reorder_quantity.min' => 'Reorder quantity must be at least 1.',
            'reorder_quantity.max' => 'Reorder quantity cannot exceed 999,999.',
            'bulk_updates.max' => 'Cannot update more than 100 inventory records at once.',
            'bulk_updates.*.inventory_id.required_with' => 'Inventory ID is required for bulk updates.',
            'bulk_updates.*.inventory_id.exists' => 'Inventory record does not exist.',
            'adjustment_type.in' => 'Invalid adjustment type selected.',
            'adjustment_quantity.integer' => 'Adjustment quantity must be a whole number.',
            'adjustment_quantity.min' => 'Adjustment quantity must be at least 1.',
            'adjustment_quantity.max' => 'Adjustment quantity cannot exceed 999,999.',
            'adjustment_reason.max' => 'Adjustment reason cannot exceed 500 characters.',
            'movement_type.in' => 'Invalid movement type selected.',
            'transfer_to_location.max' => 'Transfer location cannot exceed 100 characters.',
            'transfer_quantity.integer' => 'Transfer quantity must be a whole number.',
            'transfer_quantity.min' => 'Transfer quantity must be at least 1.',
            'transfer_quantity.max' => 'Transfer quantity cannot exceed 999,999.',
            'transfer_notes.max' => 'Transfer notes cannot exceed 1000 characters.',
            'reserve_quantity.integer' => 'Reserve quantity must be a whole number.',
            'reserve_quantity.min' => 'Reserve quantity must be at least 1.',
            'reserve_quantity.max' => 'Reserve quantity cannot exceed 999,999.',
            'release_quantity.integer' => 'Release quantity must be a whole number.',
            'release_quantity.min' => 'Release quantity must be at least 1.',
            'release_quantity.max' => 'Release quantity cannot exceed 999,999.',
            'fulfill_quantity.integer' => 'Fulfill quantity must be a whole number.',
            'fulfill_quantity.min' => 'Fulfill quantity must be at least 1.',
            'fulfill_quantity.max' => 'Fulfill quantity cannot exceed 999,999.',
            'reservation_reference.max' => 'Reservation reference cannot exceed 255 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'reference_number.max' => 'Reference number cannot exceed 255 characters.',
            'supplier_reference.max' => 'Supplier reference cannot exceed 255 characters.',
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
            'product_id' => 'product',
            'variant_id' => 'product variant',
            'quantity_available' => 'available quantity',
            'quantity_reserved' => 'reserved quantity',
            'quantity_sold' => 'sold quantity',
            'reorder_level' => 'reorder level',
            'reorder_quantity' => 'reorder quantity',
            'adjustment_type' => 'adjustment type',
            'adjustment_quantity' => 'adjustment quantity',
            'adjustment_reason' => 'adjustment reason',
            'movement_type' => 'movement type',
            'transfer_to_location' => 'transfer location',
            'transfer_quantity' => 'transfer quantity',
            'transfer_notes' => 'transfer notes',
            'reserve_quantity' => 'reserve quantity',
            'release_quantity' => 'release quantity',
            'fulfill_quantity' => 'fulfill quantity',
            'reservation_reference' => 'reservation reference',
            'reference_number' => 'reference number',
            'supplier_reference' => 'supplier reference',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default location if not provided
        if (!$this->has('location') || empty($this->location)) {
            $this->merge([
                'location' => 'main_warehouse'
            ]);
        }

        // Ensure boolean values are properly cast
        $this->merge([
            'confirm_negative_adjustment' => $this->boolean('confirm_negative_adjustment', false),
            'confirm_bulk_update' => $this->boolean('confirm_bulk_update', false),
            'force_update' => $this->boolean('force_update', false),
        ]);

        // Set default movement type based on adjustment type
        if ($this->has('adjustment_type') && !$this->has('movement_type')) {
            $movementType = match($this->adjustment_type) {
                'increase' => 'purchase',
                'decrease' => 'adjustment',
                'set' => 'recount',
                default => 'adjustment'
            };
            $this->merge(['movement_type' => $movementType]);
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
        
        // Remove confirmation flags from validated data
        if (is_array($validated)) {
            unset($validated['confirm_negative_adjustment']);
            unset($validated['confirm_bulk_update']);
            unset($validated['force_update']);
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
            // Validate variant belongs to product
            if ($this->has('product_id') && $this->has('variant_id') && $this->variant_id) {
                $variant = \App\Models\ProductVariant::find($this->variant_id);
                if ($variant && $variant->product_id != $this->product_id) {
                    $validator->errors()->add('variant_id', 'Selected variant does not belong to the specified product.');
                }
            }

            // Validate adjustment operations
            if ($this->has('adjustment_type') && $this->has('adjustment_quantity')) {
                $adjustmentType = $this->adjustment_type;
                $adjustmentQuantity = $this->adjustment_quantity;
                
                // For decrease operations, check if sufficient stock exists
                if ($adjustmentType === 'decrease' && $this->has('product_id')) {
                    $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
                        ->where('variant_id', $this->variant_id)
                        ->where('location', $this->location ?? 'main_warehouse')
                        ->first();
                    
                    if ($inventory && $inventory->quantity_available < $adjustmentQuantity) {
                        if (!$this->confirm_negative_adjustment) {
                            $validator->errors()->add('adjustment_quantity', 
                                'Insufficient stock for this adjustment. Current stock: ' . $inventory->quantity_available);
                        }
                    }
                }
            }

            // Validate transfer operations
            if ($this->has('transfer_quantity') && $this->has('transfer_to_location')) {
                if ($this->location === $this->transfer_to_location) {
                    $validator->errors()->add('transfer_to_location', 'Transfer destination must be different from source location.');
                }

                // Check if sufficient stock exists for transfer
                if ($this->has('product_id')) {
                    $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
                        ->where('variant_id', $this->variant_id)
                        ->where('location', $this->location)
                        ->first();
                    
                    if ($inventory && $inventory->quantity_available < $this->transfer_quantity) {
                        $validator->errors()->add('transfer_quantity', 
                            'Insufficient stock for transfer. Available: ' . $inventory->quantity_available);
                    }
                }
            }

            // Validate reservation operations
            if ($this->has('reserve_quantity') && $this->has('product_id')) {
                $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
                    ->where('variant_id', $this->variant_id)
                    ->where('location', $this->location ?? 'main_warehouse')
                    ->first();
                
                if ($inventory && $inventory->quantity_available < $this->reserve_quantity) {
                    $validator->errors()->add('reserve_quantity', 
                        'Insufficient available stock for reservation. Available: ' . $inventory->quantity_available);
                }
            }

            if ($this->has('release_quantity') && $this->has('product_id')) {
                $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
                    ->where('variant_id', $this->variant_id)
                    ->where('location', $this->location ?? 'main_warehouse')
                    ->first();
                
                if ($inventory && $inventory->quantity_reserved < $this->release_quantity) {
                    $validator->errors()->add('release_quantity', 
                        'Insufficient reserved stock to release. Reserved: ' . $inventory->quantity_reserved);
                }
            }

            if ($this->has('fulfill_quantity') && $this->has('product_id')) {
                $inventory = \App\Models\Inventory::where('product_id', $this->product_id)
                    ->where('variant_id', $this->variant_id)
                    ->where('location', $this->location ?? 'main_warehouse')
                    ->first();
                
                if ($inventory && $inventory->quantity_reserved < $this->fulfill_quantity) {
                    $validator->errors()->add('fulfill_quantity', 
                        'Insufficient reserved stock to fulfill. Reserved: ' . $inventory->quantity_reserved);
                }
            }

            // Validate bulk operations
            if ($this->has('bulk_updates') && is_array($this->bulk_updates)) {
                if (count($this->bulk_updates) > 10 && !$this->confirm_bulk_update) {
                    $validator->errors()->add('bulk_updates', 
                        'Please confirm bulk update of ' . count($this->bulk_updates) . ' inventory records.');
                }

                // Validate each bulk update record
                foreach ($this->bulk_updates as $index => $update) {
                    if (isset($update['inventory_id'])) {
                        $inventory = \App\Models\Inventory::find($update['inventory_id']);
                        if (!$inventory) {
                            $validator->errors()->add("bulk_updates.{$index}.inventory_id", 
                                'Inventory record does not exist.');
                        }
                    }
                }
            }

            // Validate that at least one operation is specified
            $operations = [
                'quantity_available', 'quantity_reserved', 'quantity_sold',
                'reorder_level', 'reorder_quantity', 'adjustment_quantity',
                'transfer_quantity', 'reserve_quantity', 'release_quantity',
                'fulfill_quantity', 'bulk_updates'
            ];

            $hasOperation = false;
            foreach ($operations as $operation) {
                if ($this->has($operation) && !empty($this->$operation)) {
                    $hasOperation = true;
                    break;
                }
            }

            if (!$hasOperation) {
                $validator->errors()->add('operation', 'At least one inventory operation must be specified.');
            }
        });
    }

    /**
     * Get the operation type based on the request data.
     *
     * @return string|null
     */
    public function getOperationType(): ?string
    {
        if ($this->has('adjustment_quantity')) {
            return 'adjustment';
        }
        
        if ($this->has('transfer_quantity')) {
            return 'transfer';
        }
        
        if ($this->has('reserve_quantity')) {
            return 'reservation';
        }
        
        if ($this->has('release_quantity')) {
            return 'release';
        }
        
        if ($this->has('fulfill_quantity')) {
            return 'fulfillment';
        }
        
        if ($this->has('bulk_updates')) {
            return 'bulk_update';
        }
        
        return 'direct_update';
    }

    /**
     * Check if the operation requires confirmation.
     *
     * @return bool
     */
    public function requiresConfirmation(): bool
    {
        // Large bulk operations
        if ($this->has('bulk_updates') && count($this->bulk_updates) > 10) {
            return !$this->confirm_bulk_update;
        }

        // Negative adjustments
        if ($this->has('adjustment_type') && $this->adjustment_type === 'decrease') {
            return !$this->confirm_negative_adjustment;
        }

        return false;
    }
}