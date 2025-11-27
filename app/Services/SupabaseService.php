<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SupabaseService
{
    protected $url;
    protected $anonKey;
    
    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        $this->anonKey = env('SUPABASE_ANON_KEY');
    }
    
    /**
     * Get categories with active products count
     * This bypasses the database connection issue by using Supabase REST API
     */
    public function getActiveCategories()
    {
        try {
            // Use Supabase REST API with anon key
            $response = Http::withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer ' . $this->anonKey,
            ])->get("{$this->url}/rest/v1/categories", [
                'is_active' => 'eq.true',
                'parent_id' => 'is.null',
                'order' => 'display_order.asc,name.asc',
                'select' => '*'
            ]);
            
            if ($response->successful()) {
                // Convert arrays to objects so they work with blade templates
                $categories = collect($response->json())->map(function ($category) {
                    $categoryObj = (object) $category;
                    // Set default count to 0 (will be updated once database connection is fixed)
                    $categoryObj->active_products_count = 0;
                    return $categoryObj;
                });
                
                return $categories;
            }
            
            return collect([]);
        } catch (\Exception $e) {
            \Log::error('Supabase API Error: ' . $e->getMessage());
            return collect([]);
        }
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer ' . $this->anonKey,
            ])->get("{$this->url}/rest/v1/products", [
                'category_id' => "eq.{$categoryId}",
                'status' => 'eq.active',
                'select' => '*'
            ]);
            
            if ($response->successful()) {
                return collect($response->json());
            }
            
            return collect([]);
        } catch (\Exception $e) {
            \Log::error('Supabase API Error: ' . $e->getMessage());
            return collect([]);
        }
    }
}
