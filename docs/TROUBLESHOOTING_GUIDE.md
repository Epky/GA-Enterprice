# Staff Product Management Troubleshooting Guide

## Table of Contents

1. [Common Issues](#common-issues)
2. [Product Upload Problems](#product-upload-problems)
3. [Image Upload Issues](#image-upload-issues)
4. [Inventory Problems](#inventory-problems)
5. [Search and Filter Issues](#search-and-filter-issues)
6. [Performance Issues](#performance-issues)
7. [Error Messages](#error-messages)
8. [Browser Compatibility](#browser-compatibility)

## Common Issues

### Cannot Access Product Management

**Symptoms:**
- Redirected to login page
- "Access Denied" message
- Menu items not visible

**Solutions:**

1. **Check Your Role**
   - Ensure you have 'staff' role assigned
   - Contact administrator if role is incorrect
   - Log out and log back in

2. **Clear Browser Cache**
   ```
   Chrome: Ctrl + Shift + Delete
   Firefox: Ctrl + Shift + Delete
   Safari: Cmd + Option + E
   ```

3. **Check Session**
   - Session may have expired
   - Log out completely
   - Clear cookies
   - Log back in

### Changes Not Saving

**Symptoms:**
- Form submits but changes don't appear
- Success message shows but data unchanged
- Page refreshes without saving

**Solutions:**

1. **Check Required Fields**
   - All required fields must be filled
   - Look for red asterisks (*)
   - Scroll through entire form
   - Check for validation errors

2. **Check Internet Connection**
   - Ensure stable connection
   - Try refreshing page
   - Check if other sites load

3. **Browser Issues**
   - Disable browser extensions
   - Try incognito/private mode
   - Clear browser cache
   - Try different browser

4. **Form Validation**
   - Check for error messages
   - Ensure data formats are correct
   - Verify SKU is unique
   - Check price format (numbers only)

### Page Loading Slowly

**Symptoms:**
- Long wait times
- Spinning loader
- Timeout errors

**Solutions:**

1. **Reduce Filters**
   - Remove unnecessary filters
   - Narrow date ranges
   - Limit search scope

2. **Pagination**
   - Use smaller page sizes
   - Navigate page by page
   - Avoid loading all products

3. **Clear Cache**
   - Clear browser cache
   - Refresh page (Ctrl + F5)

4. **Check Network**
   - Test internet speed
   - Close unnecessary tabs
   - Restart browser

## Product Upload Problems

### Product Creation Fails

**Error: "SKU already exists"**

**Solution:**
- SKU must be unique across all products
- Check existing products for duplicate
- Modify SKU to make it unique
- Leave SKU blank for auto-generation

**Error: "Category is required"**

**Solution:**
- Select a category from dropdown
- If no categories exist, create one first
- Refresh page if categories don't load
- Contact admin if category list is empty

**Error: "Price must be a positive number"**

**Solution:**
- Enter numbers only (no currency symbols)
- Use decimal point for cents (e.g., 19.99)
- Ensure price is greater than 0
- Check for extra spaces or characters

### Variant Creation Issues

**Variants Not Saving**

**Solutions:**
1. Fill all variant required fields
2. Ensure variant SKU is unique
3. Set variant price or price adjustment
4. Save product before adding variants
5. Refresh page and try again

**Variant Prices Not Displaying**

**Solutions:**
1. Check if price adjustment is set
2. Verify base product has price
3. Ensure variant is active
4. Clear cache and refresh

### Product Not Appearing in List

**Possible Causes:**

1. **Status is Inactive**
   - Edit product
   - Change status to "Active"
   - Save changes

2. **Filtered Out**
   - Check active filters
   - Clear all filters
   - Search by SKU directly

3. **Pagination**
   - Product may be on different page
   - Use search to find quickly
   - Check total product count

## Image Upload Issues

### Image Upload Fails

**Error: "File too large"**

**Solution:**
- Maximum file size is 5MB
- Compress image before upload
- Use online tools: TinyPNG, Compressor.io
- Reduce image dimensions
- Convert to WebP format

**Error: "Invalid file type"**

**Solution:**
- Only JPEG, PNG, WebP allowed
- Check file extension
- Convert image to supported format
- Ensure file isn't corrupted

**Error: "Upload failed"**

**Solutions:**
1. Check internet connection
2. Try smaller file size
3. Refresh page and retry
4. Try different browser
5. Clear browser cache

### Image Not Displaying

**Symptoms:**
- Broken image icon
- Blank space where image should be
- Image shows in upload but not on product

**Solutions:**

1. **Wait for Processing**
   - Images need time to process
   - Refresh page after 30 seconds
   - Check if upload completed

2. **Check Image URL**
   - View page source
   - Check if image URL is valid
   - Verify image exists in storage

3. **Clear Cache**
   - Hard refresh (Ctrl + F5)
   - Clear browser cache
   - Try incognito mode

4. **Re-upload Image**
   - Delete problematic image
   - Upload again
   - Use different image file

### Cannot Delete Image

**Solutions:**
1. Ensure you have permission
2. Check if image is primary (set different primary first)
3. Refresh page and try again
4. Contact administrator if persists

### Image Order Not Saving

**Solutions:**
1. Drag and drop to reorder
2. Wait for save confirmation
3. Refresh page to verify
4. Try using different browser

## Inventory Problems

### Stock Update Not Working

**Error: "Invalid quantity"**

**Solution:**
- Enter whole numbers only
- No negative numbers
- No decimal points for quantity
- Remove any extra characters

**Stock Shows Incorrect Amount**

**Solutions:**
1. Check reserved stock
2. Review recent movements
3. Verify pending orders
4. Perform stock audit
5. Contact administrator

### Low Stock Alerts Not Showing

**Solutions:**
1. Check reorder level is set
2. Verify stock is below reorder level
3. Refresh dashboard
4. Check alert settings
5. Clear browser cache

### Inventory Movement Not Recorded

**Solutions:**
1. Ensure movement type is selected
2. Add notes for reference
3. Check if quantity changed
4. Verify save confirmation
5. Review movement history

### Bulk Stock Update Fails

**Solutions:**
1. Check CSV format
2. Verify all SKUs exist
3. Ensure quantities are valid
4. Check for special characters
5. Try smaller batches

## Search and Filter Issues

### Search Returns No Results

**Solutions:**
1. Check spelling
2. Try partial search terms
3. Remove filters
4. Search by SKU instead
5. Verify product exists

### Filters Not Working

**Solutions:**
1. Clear all filters and reapply
2. Refresh page
3. Check if products match criteria
4. Try different filter combinations
5. Clear browser cache

### Export Not Working

**Solutions:**
1. Check if products are selected
2. Verify export format
3. Disable popup blockers
4. Try different browser
5. Check download folder

## Performance Issues

### Slow Page Loading

**Immediate Solutions:**
1. Reduce number of displayed items
2. Clear browser cache
3. Close unnecessary tabs
4. Disable browser extensions
5. Use wired internet connection

**Long-term Solutions:**
1. Archive old products
2. Optimize images before upload
3. Use filters to narrow results
4. Request system optimization

### Form Submission Timeout

**Solutions:**
1. Reduce image sizes
2. Upload fewer images at once
3. Save product first, then add images
4. Check internet connection
5. Try during off-peak hours

### Browser Freezing

**Solutions:**
1. Close other applications
2. Restart browser
3. Clear browser cache
4. Update browser to latest version
5. Try different browser

## Error Messages

### "Session Expired"

**Solution:**
- Log out completely
- Clear cookies
- Log back in
- Resume work

### "Unauthorized Access"

**Solution:**
- Verify your role permissions
- Contact administrator
- Log out and log back in
- Check if account is active

### "Database Error"

**Solution:**
- Refresh page
- Try again in a few minutes
- Contact technical support
- Report error details

### "Validation Failed"

**Solution:**
- Read error message carefully
- Check highlighted fields
- Correct invalid data
- Ensure all required fields filled

### "File Upload Error"

**Solution:**
- Check file size (max 5MB)
- Verify file type (JPEG, PNG, WebP)
- Try different file
- Check internet connection

### "Duplicate Entry"

**Solution:**
- SKU must be unique
- Check existing products
- Modify SKU
- Use auto-generated SKU

## Browser Compatibility

### Recommended Browsers

✅ **Fully Supported:**
- Google Chrome (latest version)
- Mozilla Firefox (latest version)
- Microsoft Edge (latest version)
- Safari (latest version)

⚠️ **Limited Support:**
- Internet Explorer 11 (not recommended)
- Older browser versions

### Browser-Specific Issues

**Chrome Issues:**
- Clear cache: `chrome://settings/clearBrowserData`
- Disable extensions in incognito mode
- Reset settings if needed

**Firefox Issues:**
- Clear cache: Options > Privacy & Security
- Disable add-ons temporarily
- Refresh Firefox if needed

**Safari Issues:**
- Clear cache: Safari > Clear History
- Check privacy settings
- Disable content blockers

**Edge Issues:**
- Clear cache: Settings > Privacy
- Reset browser if needed
- Try Chrome as alternative

### Mobile Browser Issues

**Note:** Product management is optimized for desktop browsers.

**Mobile Solutions:**
1. Use desktop mode in browser
2. Rotate to landscape orientation
3. Use tablet for better experience
4. Access from desktop when possible

## Data Issues

### Product Data Missing

**Solutions:**
1. Check if product was deleted
2. Verify filters aren't hiding it
3. Search by SKU
4. Check with administrator
5. Review audit logs

### Images Disappeared

**Solutions:**
1. Check if images were deleted
2. Verify storage quota
3. Check image URLs
4. Contact administrator
5. Re-upload if necessary

### Inventory Discrepancy

**Solutions:**
1. Review movement history
2. Check pending orders
3. Verify reserved stock
4. Perform physical count
5. Adjust with proper documentation

## Getting Additional Help

### Before Contacting Support

Gather this information:
- What you were trying to do
- Exact error message
- Steps to reproduce issue
- Browser and version
- Screenshots if possible
- Your username (not password)

### Contact Methods

**Email Support:**
- support@beautystore.com
- Include all gathered information
- Attach screenshots
- Response within 24 hours

**Phone Support:**
- 1-800-BEAUTY-HELP
- Available: Mon-Fri, 9 AM - 5 PM
- Have account information ready

**Live Chat:**
- Available during business hours
- Click chat icon in dashboard
- Fastest response time

### Emergency Issues

For critical issues affecting operations:
- Call emergency hotline: 1-800-URGENT-HELP
- Email: emergency@beautystore.com
- Mark as "URGENT" in subject

## Preventive Measures

### Best Practices to Avoid Issues

1. **Regular Backups**
   - Export product data weekly
   - Save important information
   - Keep offline copies

2. **Browser Maintenance**
   - Clear cache weekly
   - Update browser regularly
   - Disable unnecessary extensions

3. **Data Validation**
   - Double-check before saving
   - Verify SKUs are unique
   - Test with small batches first

4. **Image Preparation**
   - Optimize before upload
   - Use consistent dimensions
   - Keep file sizes reasonable

5. **Regular Training**
   - Review user guide monthly
   - Stay updated on new features
   - Practice with test products

## Frequently Asked Questions

**Q: Why can't I delete a product?**
A: Products with order history cannot be deleted. Set status to "Discontinued" instead.

**Q: How do I recover a deleted product?**
A: Deleted products cannot be recovered. Contact administrator immediately.

**Q: Why are my changes not visible to customers?**
A: Check product status is "Active" and visibility is set to "Public".

**Q: Can I undo bulk operations?**
A: No automatic undo. Always preview before confirming bulk operations.

**Q: How long do images take to process?**
A: Usually 10-30 seconds depending on file size and server load.

**Q: Why can't I upload more images?**
A: Check maximum images per product limit (usually 10 images).

**Q: How do I handle products with multiple sizes?**
A: Use product variants for different sizes with individual SKUs and stock.

**Q: Can I import products from Excel?**
A: Yes, use CSV format. See bulk import documentation.

---

*Last Updated: November 2025*
*Version: 1.0*

**Need more help?** Contact support@beautystore.com
