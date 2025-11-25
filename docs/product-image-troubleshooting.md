# Product Image Troubleshooting Guide

## Common Issues and Solutions

### Issue 1: ERR_CONNECTION_REFUSED for Product Images

**Symptom:** Browser shows `ERR_CONNECTION_REFUSED` when trying to load product images.

**Root Cause:** The `APP_URL` in `.env` doesn't match the actual server address.

**Solution:**
1. Check where your Laravel server is running (e.g., `http://127.0.0.1:8000`)
2. Update `.env` file:
   ```env
   APP_URL=http://127.0.0.1:8000
   ```
3. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Issue 2: 403 Forbidden for Old/Restored Images

**Symptom:** Browser shows `403 Forbidden` for product images, especially after database restore.

**Root Cause:** Database contains image records pointing to files that don't exist on disk.

**Solution:**

#### Option A: Clean up orphaned records (Recommended)
```bash
# Dry run to see what would be deleted
php artisan products:clean-missing-images --dry-run

# Actually delete orphaned records
php artisan products:clean-missing-images
```

#### Option B: Restore missing image files
If you have backups of the images, copy them to the correct location:
```
storage/app/public/products/{product_id}/{filename}
```

---

### Issue 3: Storage Symlink Missing

**Symptom:** All product images return 404 or 403 errors.

**Root Cause:** The symbolic link from `public/storage` to `storage/app/public` doesn't exist.

**Solution:**
```bash
php artisan storage:link
```

---

### Issue 4: File Permissions

**Symptom:** Images upload successfully but can't be viewed (403 Forbidden).

**Root Cause:** Web server doesn't have read permissions on the files.

**Solution (Linux/Mac):**
```bash
chmod -R 755 storage/app/public
chmod -R 644 storage/app/public/**/*
```

**Solution (Windows):**
Usually not an issue, but ensure the files aren't marked as "Read-only" in properties.

---

## Prevention Best Practices

### 1. Always Use the Correct Image URL Method

**✅ Correct:**
```blade
<img src="{{ $product->primaryImage->full_url }}" alt="{{ $product->name }}">
```

**❌ Incorrect:**
```blade
<!-- Don't double-wrap with asset() -->
<img src="{{ asset($product->primaryImage->full_url) }}" alt="{{ $product->name }}">
```

### 2. Handle Missing Images Gracefully

The `ProductImage` model now includes a `fileExists()` method:

```php
@if($product->primaryImage && $product->primaryImage->fileExists())
    <img src="{{ $product->primaryImage->full_url }}" alt="{{ $product->name }}">
@else
    <!-- Show placeholder -->
@endif
```

### 3. Add Fallback with onerror

```blade
<img src="{{ $product->primaryImage->full_url }}" 
     alt="{{ $product->name }}"
     onerror="this.src='/images/placeholder.png';">
```

### 4. Regular Maintenance

Run the cleanup command periodically:
```bash
# Add to your deployment script or run monthly
php artisan products:clean-missing-images
```

---

## Database Restore Checklist

When restoring a database backup:

1. ✅ Restore database
2. ✅ Check if image files exist in `storage/app/public/products/`
3. ✅ If images are missing, either:
   - Restore image files from backup, OR
   - Run `php artisan products:clean-missing-images`
4. ✅ Verify `APP_URL` matches your server
5. ✅ Clear caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```
6. ✅ Test image loading in browser

---

## Technical Details

### How Image URLs Are Generated

1. Images are stored in: `storage/app/public/products/{product_id}/{filename}`
2. Symlink makes them accessible at: `public/storage/products/{product_id}/{filename}`
3. `ProductImage::getFullUrlAttribute()` generates: `http://127.0.0.1:8000/storage/products/{product_id}/{filename}`
4. This uses `APP_URL` from config, which comes from `.env`

### Why 403 Instead of 404?

When a file is missing but the symlink exists, Laravel's file serving returns 403 (Forbidden) instead of 404 (Not Found) because the directory exists but the specific file doesn't.

---

## Artisan Commands

### products:clean-missing-images

Removes product image database records that point to non-existent files.

**Usage:**
```bash
# Dry run (shows what would be deleted)
php artisan products:clean-missing-images --dry-run

# Actually delete orphaned records
php artisan products:clean-missing-images

# Non-interactive mode (for scripts)
php artisan products:clean-missing-images --no-interaction
```

**Options:**
- `--dry-run`: Show what would be deleted without actually deleting
- `--no-interaction`: Don't ask for confirmation (auto-confirms)

---

## Quick Diagnosis

Run this checklist to diagnose image issues:

```bash
# 1. Check if symlink exists
ls -la public/storage  # Linux/Mac
dir public\storage     # Windows

# 2. Check APP_URL
php artisan tinker --execute="echo config('app.url');"

# 3. Check for missing images
php artisan products:clean-missing-images --dry-run

# 4. Check file permissions (Linux/Mac only)
ls -la storage/app/public/products/

# 5. Test a specific image URL
curl -I http://127.0.0.1:8000/storage/products/3/20251125155804_lSfM9V9z.png
```

---

## Support

If you're still experiencing issues after following this guide:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server error logs
3. Verify database connection is working
4. Ensure storage disk is configured correctly in `config/filesystems.php`
