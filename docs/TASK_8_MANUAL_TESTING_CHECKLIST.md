# Task 8: Manual Testing Checklist

## Cache Management and Data Consistency Verification

Use this checklist to manually verify that cache management and data consistency are working correctly in the inline category and brand creation feature.

---

## Test 1: Category Cache Clearing

### Steps:
1. [ ] Log in as a staff user
2. [ ] Open browser developer tools (F12)
3. [ ] Navigate to Network tab
4. [ ] Go to product create page (`/staff/products/create`)
5. [ ] Click "Add New Category" button
6. [ ] Fill in category form:
   - Name: "Test Category [timestamp]"
   - Description: "Testing cache clearing"
   - Active: Checked
7. [ ] Click "Create" button
8. [ ] Verify success message appears
9. [ ] Verify dropdown updates with new category
10. [ ] Open new tab and navigate to `/staff/categories`
11. [ ] Verify new category appears in the list immediately

### Expected Results:
- ✅ Category created successfully
- ✅ Dropdown refreshed with new category
- ✅ New category appears in categories index page
- ✅ No delay or cache staleness

---

## Test 2: Brand Cache Clearing

### Steps:
1. [ ] Log in as a staff user
2. [ ] Open browser developer tools (F12)
3. [ ] Navigate to Network tab
4. [ ] Go to product create page (`/staff/products/create`)
5. [ ] Click "Add New Brand" button
6. [ ] Fill in brand form:
   - Name: "Test Brand [timestamp]"
   - Description: "Testing cache clearing"
   - Active: Checked
7. [ ] Click "Create" button
8. [ ] Verify success message appears
9. [ ] Verify dropdown updates with new brand
10. [ ] Open new tab and navigate to `/staff/brands`
11. [ ] Verify new brand appears in the list immediately

### Expected Results:
- ✅ Brand created successfully
- ✅ Dropdown refreshed with new brand
- ✅ New brand appears in brands index page
- ✅ No delay or cache staleness

---

## Test 3: Cache Performance (Categories)

### Steps:
1. [ ] Clear application cache: `php artisan cache:clear`
2. [ ] Open browser developer tools (F12)
3. [ ] Navigate to Network tab
4. [ ] Make first API call to `/staff/categories/active`
5. [ ] Note the response time (should query database)
6. [ ] Make second API call to `/staff/categories/active`
7. [ ] Note the response time (should be faster - from cache)
8. [ ] Create a new category via inline modal
9. [ ] Make third API call to `/staff/categories/active`
10. [ ] Note the response time (should query database again)
11. [ ] Verify new category is in the response
12. [ ] Make fourth API call to `/staff/categories/active`
13. [ ] Note the response time (should be fast again - from cache)

### Expected Results:
- ✅ First call: ~100ms (database query)
- ✅ Second call: ~10ms (cached)
- ✅ Third call (after creation): ~100ms (database query with new item)
- ✅ Fourth call: ~10ms (cached with new item)
- ✅ New category included in all responses after creation

---

## Test 4: Cache Performance (Brands)

### Steps:
1. [ ] Clear application cache: `php artisan cache:clear`
2. [ ] Open browser developer tools (F12)
3. [ ] Navigate to Network tab
4. [ ] Make first API call to `/staff/brands/active`
5. [ ] Note the response time (should query database)
6. [ ] Make second API call to `/staff/brands/active`
7. [ ] Note the response time (should be faster - from cache)
8. [ ] Create a new brand via inline modal
9. [ ] Make third API call to `/staff/brands/active`
10. [ ] Note the response time (should query database again)
11. [ ] Verify new brand is in the response
12. [ ] Make fourth API call to `/staff/brands/active`
13. [ ] Note the response time (should be fast again - from cache)

### Expected Results:
- ✅ First call: ~100ms (database query)
- ✅ Second call: ~10ms (cached)
- ✅ Third call (after creation): ~100ms (database query with new item)
- ✅ Fourth call: ~10ms (cached with new item)
- ✅ New brand included in all responses after creation

---

## Test 5: Multiple Inline Creations

### Steps:
1. [ ] Go to product create page
2. [ ] Create first category: "Category A"
3. [ ] Verify it appears in dropdown
4. [ ] Create second category: "Category B"
5. [ ] Verify both categories appear in dropdown
6. [ ] Create third category: "Category C"
7. [ ] Verify all three categories appear in dropdown
8. [ ] Navigate to `/staff/categories`
9. [ ] Verify all three categories appear in the list
10. [ ] Repeat steps 1-9 for brands

### Expected Results:
- ✅ All categories created successfully
- ✅ Dropdown updates after each creation
- ✅ All categories appear in index page
- ✅ Same behavior for brands
- ✅ No data inconsistencies

---

## Test 6: Inactive Items Not in Dropdown

### Steps:
1. [ ] Go to `/staff/categories`
2. [ ] Create a new category with "Active" unchecked
3. [ ] Go to product create page
4. [ ] Open category dropdown
5. [ ] Verify inactive category does NOT appear
6. [ ] Go to `/staff/brands`
7. [ ] Create a new brand with "Active" unchecked
8. [ ] Go to product create page
9. [ ] Open brand dropdown
10. [ ] Verify inactive brand does NOT appear

### Expected Results:
- ✅ Inactive categories not in dropdown
- ✅ Inactive brands not in dropdown
- ✅ Only active items available for selection
- ✅ Cache respects active status

---

## Test 7: Cache Clearing on Update

### Steps:
1. [ ] Create a category via inline modal: "Original Name"
2. [ ] Note the category ID
3. [ ] Navigate to `/staff/categories`
4. [ ] Edit the category, change name to "Updated Name"
5. [ ] Save changes
6. [ ] Go to product create page
7. [ ] Open category dropdown
8. [ ] Verify category shows "Updated Name"
9. [ ] Repeat for brands

### Expected Results:
- ✅ Category name updated in database
- ✅ Cache cleared on update
- ✅ Dropdown shows updated name
- ✅ Same behavior for brands

---

## Test 8: Transaction Rollback on Error

### Steps:
1. [ ] Open browser developer tools
2. [ ] Go to Network tab
3. [ ] Go to product create page
4. [ ] Click "Add New Category"
5. [ ] Fill in form with duplicate name (existing category)
6. [ ] Click "Create"
7. [ ] Verify error message appears
8. [ ] Check database: `SELECT * FROM categories WHERE name = 'duplicate name'`
9. [ ] Verify only one record exists (no duplicate created)
10. [ ] Verify cache was NOT cleared (check by making API call)

### Expected Results:
- ✅ Validation error displayed
- ✅ No duplicate record created
- ✅ Transaction rolled back
- ✅ Cache not cleared on error
- ✅ Data consistency maintained

---

## Test 9: Concurrent Creations

### Steps:
1. [ ] Open two browser tabs
2. [ ] In both tabs, go to product create page
3. [ ] In tab 1, click "Add New Category"
4. [ ] In tab 2, click "Add New Category"
5. [ ] In tab 1, create "Category X"
6. [ ] In tab 2, create "Category Y"
7. [ ] Verify both categories created successfully
8. [ ] Refresh both tabs
9. [ ] Verify both categories appear in dropdowns
10. [ ] Navigate to `/staff/categories`
11. [ ] Verify both categories appear in list

### Expected Results:
- ✅ Both categories created successfully
- ✅ No race conditions
- ✅ Both appear in dropdowns
- ✅ Both appear in index page
- ✅ Data consistency maintained

---

## Test 10: Cache Expiry

### Steps:
1. [ ] Clear application cache
2. [ ] Make API call to `/staff/categories/active`
3. [ ] Note the timestamp
4. [ ] Wait 1 hour (or modify cache duration in code for testing)
5. [ ] Make API call to `/staff/categories/active` again
6. [ ] Verify database query is executed (check logs or query time)
7. [ ] Make another API call immediately
8. [ ] Verify it's served from cache (faster response)

### Expected Results:
- ✅ Cache expires after 1 hour
- ✅ Fresh data fetched from database
- ✅ Cache repopulated automatically
- ✅ Subsequent calls served from cache

---

## Database Verification Queries

### Check Categories Cache Status
```sql
-- Check if category exists
SELECT * FROM categories WHERE name = 'Test Category';

-- Check active categories
SELECT id, name, slug, is_active FROM categories WHERE is_active = true ORDER BY display_order;

-- Check category count
SELECT COUNT(*) FROM categories;
```

### Check Brands Cache Status
```sql
-- Check if brand exists
SELECT * FROM brands WHERE name = 'Test Brand';

-- Check active brands
SELECT id, name, slug, is_active FROM brands WHERE is_active = true ORDER BY name;

-- Check brand count
SELECT COUNT(*) FROM brands;
```

### Check Cache Keys (Laravel Tinker)
```php
// In terminal: php artisan tinker

// Check if cache keys exist
Cache::has('categories.active');
Cache::has('categories.root');
Cache::has('brands.active');
Cache::has('brands.stats');

// Get cache values
Cache::get('categories.active');
Cache::get('brands.active');

// Clear specific cache
Cache::forget('categories.active');
Cache::forget('brands.active');

// Clear all cache
Cache::flush();
```

---

## Performance Benchmarks

### Expected Response Times

| Endpoint | Cache Status | Expected Time | Acceptable Range |
|----------|--------------|---------------|------------------|
| `/staff/categories/active` | Miss | ~100ms | 50-200ms |
| `/staff/categories/active` | Hit | ~10ms | 5-20ms |
| `/staff/brands/active` | Miss | ~100ms | 50-200ms |
| `/staff/brands/active` | Hit | ~10ms | 5-20ms |
| `/staff/categories/inline` | N/A | ~150ms | 100-300ms |
| `/staff/brands/inline` | N/A | ~150ms | 100-300ms |

### Cache Hit Ratio Target
- **Target:** >95% cache hit ratio under normal operation
- **Measurement:** Monitor over 1 hour period
- **Acceptable:** >90% cache hit ratio

---

## Troubleshooting

### Issue: Cache Not Clearing
**Symptoms:** Old data appears in dropdown after creation

**Solutions:**
1. Check if `clearCategoryCaches()` or `clearBrandCaches()` is called
2. Verify cache driver is configured correctly in `.env`
3. Check if cache tags are supported by cache driver
4. Try clearing cache manually: `php artisan cache:clear`

### Issue: Slow Response Times
**Symptoms:** API calls take longer than expected

**Solutions:**
1. Check database connection
2. Verify indexes exist on `is_active` and `display_order` columns
3. Check cache driver performance (Redis > Memcached > File)
4. Monitor database query logs

### Issue: Duplicate Slugs
**Symptoms:** Error when creating category/brand with similar name

**Solutions:**
1. Verify slug generation logic includes counter
2. Check database for existing slugs
3. Ensure unique constraint on slug column

---

## Sign-Off

### Tester Information
- **Name:** ___________________________
- **Date:** ___________________________
- **Environment:** ___________________________

### Test Results
- [ ] All tests passed
- [ ] Some tests failed (see notes below)
- [ ] Tests could not be completed (see notes below)

### Notes:
```
[Add any observations, issues, or recommendations here]
```

### Approval
- [ ] Cache management verified
- [ ] Data consistency verified
- [ ] Performance acceptable
- [ ] Ready for production

**Signature:** ___________________________  
**Date:** ___________________________
