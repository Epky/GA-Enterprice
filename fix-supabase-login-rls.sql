-- ============================================================================
-- Fix Supabase Login "Tenant or user not found" Error
-- ============================================================================
-- This script fixes the RLS issue that prevents Laravel from authenticating users
-- when using Supabase's connection pooler.
--
-- Run this in your Supabase SQL Editor
-- ============================================================================

-- Solution 1: Grant BYPASSRLS to your postgres user
-- Replace 'postgres.hgmdtzpsbzwanjuhiemf' with your actual username
ALTER USER "postgres.hgmdtzpsbzwanjuhiemf" WITH BYPASSRLS;

-- Solution 2: Create permissive RLS policies for the users table
-- This allows authentication to work properly

-- First, drop any existing restrictive policies
DROP POLICY IF EXISTS users_select_policy ON users;
DROP POLICY IF EXISTS users_insert_policy ON users;
DROP POLICY IF EXISTS users_update_policy ON users;
DROP POLICY IF EXISTS users_delete_policy ON users;

-- Create permissive SELECT policy (allows reading users for authentication)
CREATE POLICY users_select_policy ON users
FOR SELECT
TO public
USING (true);

-- Allow inserting new users (for registration)
CREATE POLICY users_insert_policy ON users
FOR INSERT
TO public
WITH CHECK (true);

-- Allow updating users
CREATE POLICY users_update_policy ON users
FOR UPDATE
TO public
USING (true)
WITH CHECK (true);

-- Allow deleting users
CREATE POLICY users_delete_policy ON users
FOR DELETE
TO public
USING (true);

-- Verify the changes
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd
FROM pg_policies
WHERE tablename = 'users'
ORDER BY policyname;

-- Check if BYPASSRLS was granted
SELECT rolname, rolbypassrls
FROM pg_roles
WHERE rolname LIKE 'postgres%';
