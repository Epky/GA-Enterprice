-- ============================================================================
-- Supabase RLS Setup Script
-- Run this directly in Supabase SQL Editor to enable RLS on all tables
-- ============================================================================

-- Enable RLS on all tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE brands ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE product_images ENABLE ROW LEVEL SECURITY;
ALTER TABLE product_variants ENABLE ROW LEVEL SECURITY;
ALTER TABLE product_specifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory ENABLE ROW LEVEL SECURITY;
ALTER TABLE inventory_movements ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE carts ENABLE ROW LEVEL SECURITY;
ALTER TABLE cart_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE promotions ENABLE ROW LEVEL SECURITY;
ALTER TABLE coupons ENABLE ROW LEVEL SECURITY;
ALTER TABLE coupon_usage ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE activity_logs ENABLE ROW LEVEL SECURITY;

-- ============================================================================
-- Service Role Policies (Full Access for Backend)
-- ============================================================================

-- Drop existing service_role policies if they exist
DROP POLICY IF EXISTS service_role_all_access ON users;
DROP POLICY IF EXISTS service_role_all_access ON user_profiles;
DROP POLICY IF EXISTS service_role_all_access ON categories;
DROP POLICY IF EXISTS service_role_all_access ON brands;
DROP POLICY IF EXISTS service_role_all_access ON products;
DROP POLICY IF EXISTS service_role_all_access ON product_images;
DROP POLICY IF EXISTS service_role_all_access ON product_variants;
DROP POLICY IF EXISTS service_role_all_access ON product_specifications;
DROP POLICY IF EXISTS service_role_all_access ON inventory;
DROP POLICY IF EXISTS service_role_all_access ON inventory_movements;
DROP POLICY IF EXISTS service_role_all_access ON orders;
DROP POLICY IF EXISTS service_role_all_access ON order_items;
DROP POLICY IF EXISTS service_role_all_access ON payments;
DROP POLICY IF EXISTS service_role_all_access ON carts;
DROP POLICY IF EXISTS service_role_all_access ON cart_items;
DROP POLICY IF EXISTS service_role_all_access ON promotions;
DROP POLICY IF EXISTS service_role_all_access ON coupons;
DROP POLICY IF EXISTS service_role_all_access ON coupon_usage;
DROP POLICY IF EXISTS service_role_all_access ON audit_logs;
DROP POLICY IF EXISTS service_role_all_access ON activity_logs;

-- Create service_role policies (full access for Laravel backend)
CREATE POLICY service_role_all_access ON users FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON user_profiles FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON categories FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON brands FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON products FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON product_images FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON product_variants FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON product_specifications FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON inventory FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON inventory_movements FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON orders FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON order_items FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON payments FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON carts FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON cart_items FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON promotions FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON coupons FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON coupon_usage FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON audit_logs FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_access ON activity_logs FOR ALL USING (true) WITH CHECK (true);

-- ============================================================================
-- Public Read Policies (For Anonymous Users)
-- ============================================================================

-- Drop existing public read policies if they exist
DROP POLICY IF EXISTS categories_public_read ON categories;
DROP POLICY IF EXISTS brands_public_read ON brands;
DROP POLICY IF EXISTS products_public_read ON products;
DROP POLICY IF EXISTS product_images_public_read ON product_images;
DROP POLICY IF EXISTS product_specifications_public_read ON product_specifications;
DROP POLICY IF EXISTS product_variants_public_read ON product_variants;
DROP POLICY IF EXISTS inventory_public_read ON inventory;
DROP POLICY IF EXISTS promotions_public_read ON promotions;

-- Categories - Public can read active categories
CREATE POLICY categories_public_read 
ON categories 
FOR SELECT 
USING (is_active = true);

-- Brands - Public can read active brands
CREATE POLICY brands_public_read 
ON brands 
FOR SELECT 
USING (is_active = true);

-- Products - Public can read active products
CREATE POLICY products_public_read 
ON products 
FOR SELECT 
USING (status = 'active');

-- Product Images - Public can read all product images
CREATE POLICY product_images_public_read 
ON product_images 
FOR SELECT 
USING (true);

-- Product Specifications - Public can read all specifications
CREATE POLICY product_specifications_public_read 
ON product_specifications 
FOR SELECT 
USING (true);

-- Product Variants - Public can read active variants
CREATE POLICY product_variants_public_read 
ON product_variants 
FOR SELECT 
USING (is_active = true);

-- Inventory - Public can read inventory (for stock display)
CREATE POLICY inventory_public_read 
ON inventory 
FOR SELECT 
USING (true);

-- Promotions - Public can read active promotions
CREATE POLICY promotions_public_read 
ON promotions 
FOR SELECT 
USING (
    is_active = true 
    AND start_date <= CURRENT_TIMESTAMP 
    AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
);

-- ============================================================================
-- Done!
-- ============================================================================

SELECT 'RLS enabled and policies created successfully!' AS status;
