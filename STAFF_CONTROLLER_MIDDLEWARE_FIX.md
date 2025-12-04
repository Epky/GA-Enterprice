# Staff Controller Middleware Fix

## Problem
Both `StaffCategoryController` and `StaffBrandController` were throwing the error:
```
Call to undefined method App\Http\Controllers\Staff\StaffCategoryController::middleware()
```

This occurred when accessing `/staff/categories` or `/staff/brands` routes.

## Root Cause
In Laravel 12, the `middleware()` method was removed from the base `Controller` class. The controllers were trying to use `$this->middleware()` in their constructors, which is no longer supported.

## Solution
Removed the constructor middleware from both controllers:
- `app/Http/Controllers/Staff/StaffCategoryController.php`
- `app/Http/Controllers/Staff/StaffBrandController.php`

The middleware protection is already properly configured in `routes/staff.php`:
```php
Route::middleware(['auth', 'role.redirect', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    // All staff routes are protected here
});
```

## Changes Made
1. Removed the `__construct()` method from `StaffCategoryController`
2. Removed the `__construct()` method from `StaffBrandController`

## Testing
After this fix:
- ✅ `/staff/categories` should load without errors
- ✅ `/staff/brands` should load without errors
- ✅ Authentication and authorization still work via route middleware
- ✅ Both staff and admin users can access these routes

## Date
December 4, 2025
