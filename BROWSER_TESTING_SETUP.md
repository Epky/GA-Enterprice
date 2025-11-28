# Laravel Dusk Browser Testing Setup Guide

## Installation Steps

### 1. Install Laravel Dusk
```bash
composer require --dev laravel/dusk
```

### 2. Install Dusk in Your Application
```bash
php artisan dusk:install
```

This will create:
- `tests/Browser` directory
- `tests/DuskTestCase.php` base class
- `.env.dusk.local` environment file

### 3. Download ChromeDriver
```bash
php artisan dusk:chrome-driver
```

For specific Chrome version:
```bash
php artisan dusk:chrome-driver --detect
```

### 4. Configure Environment

Create `.env.dusk.local` file (if not exists):
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_DATABASE=your_test_database
```

### 5. Update .gitignore

Add these lines to `.gitignore`:
```
/tests/Browser/screenshots
/tests/Browser/console
/tests/Browser/downloads
```

## Running Browser Tests

### Run All Browser Tests
```bash
php artisan dusk
```

### Run Specific Test
```bash
php artisan dusk tests/Browser/ExampleTest.php
```

### Run with Specific Browser
```bash
php artisan dusk --browse
```

## Creating Your First Browser Test

### Example: Customer Dashboard Test

Create `tests/Browser/CustomerDashboardTest.php`:

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomerDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test customer can browse products
     */
    public function test_customer_can_browse_products()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/customer/dashboard')
                    ->assertSee('Products')
                    ->assertVisible('.product-card')
                    ->screenshot('customer-dashboard');
        });
    }

    /**
     * Test customer can filter products by category
     */
    public function test_customer_can_filter_by_category()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create([
            'name' => 'Lipstick',
            'is_active' => true
        ]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($user, $category) {
            $browser->loginAs($user)
                    ->visit('/customer/dashboard')
                    ->click('@category-' . $category->id)
                    ->waitForText('Lipstick')
                    ->assertSee('Lipstick')
                    ->screenshot('filtered-by-category');
        });
    }

    /**
     * Test customer can add product to cart
     */
    public function test_customer_can_add_to_cart()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'name' => 'Test Lipstick',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'price' => 299.99,
        ]);

        $this->browse(function (Browser $browser) use ($user, $product) {
            $browser->loginAs($user)
                    ->visit('/shop/' . $product->slug)
                    ->assertSee('Test Lipstick')
                    ->assertSee('₱299.99')
                    ->type('quantity', '2')
                    ->press('Add to Cart')
                    ->waitForText('Added to cart')
                    ->assertSee('Added to cart')
                    ->screenshot('product-added-to-cart');
        });
    }
}
```

### Example: Walk-in Transaction Test

Create `tests/Browser/WalkInTransactionTest.php`:

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WalkInTransactionTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test staff can create walk-in transaction
     */
    public function test_staff_can_create_walk_in_transaction()
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'price' => 199.99,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
            'location' => 'Store',
        ]);

        $this->browse(function (Browser $browser) use ($staff, $product) {
            $browser->loginAs($staff)
                    ->visit('/staff/walk-in-transaction/create')
                    ->assertSee('Walk-in Transaction')
                    ->type('search', 'TEST-001')
                    ->waitForText('Test Product')
                    ->click('@product-result-' . $product->id)
                    ->type('quantity', '2')
                    ->press('Add to Cart')
                    ->waitForText('Added to cart')
                    ->press('Complete Transaction')
                    ->waitForText('Transaction completed')
                    ->assertSee('Transaction completed')
                    ->screenshot('walk-in-transaction-completed');
        });
    }
}
```

### Example: Analytics Dashboard Test

Create `tests/Browser/AnalyticsDashboardTest.php`:

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AnalyticsDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test admin can view analytics dashboard
     */
    public function test_admin_can_view_analytics_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create some test data
        Order::factory()->count(10)->create([
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/dashboard')
                    ->assertSee('Analytics Dashboard')
                    ->assertVisible('#sales-chart')
                    ->assertVisible('#revenue-card')
                    ->assertVisible('#orders-card')
                    ->waitFor('#sales-chart canvas')
                    ->screenshot('analytics-dashboard');
        });
    }

    /**
     * Test admin can filter analytics by date range
     */
    public function test_admin_can_filter_by_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/dashboard')
                    ->select('date_range', 'last_30_days')
                    ->waitFor('#sales-chart canvas')
                    ->pause(1000) // Wait for chart to update
                    ->assertSee('Last 30 Days')
                    ->screenshot('analytics-filtered');
        });
    }
}
```

## Using Dusk Selectors

Add `dusk` attributes to your Blade templates for easier testing:

```blade
<!-- Product Card -->
<div class="product-card" dusk="product-{{ $product->id }}">
    <h3 dusk="product-name">{{ $product->name }}</h3>
    <p dusk="product-price">₱{{ number_format($product->price, 2) }}</p>
    <button dusk="add-to-cart-{{ $product->id }}">Add to Cart</button>
</div>

<!-- Category Filter -->
<button dusk="category-{{ $category->id }}">
    {{ $category->name }}
</button>

<!-- Search Input -->
<input type="text" dusk="product-search" placeholder="Search products...">
```

Then in tests, use `@` prefix:
```php
$browser->click('@add-to-cart-' . $product->id);
$browser->type('@product-search', 'lipstick');
```

## Common Dusk Assertions

```php
// Visibility
$browser->assertVisible('.element');
$browser->assertMissing('.element');

// Text Content
$browser->assertSee('Text');
$browser->assertDontSee('Text');
$browser->assertSeeIn('.selector', 'Text');

// Form Elements
$browser->assertInputValue('name', 'value');
$browser->assertChecked('checkbox');
$browser->assertSelected('select', 'value');

// URLs
$browser->assertPathIs('/expected/path');
$browser->assertRouteIs('route.name');

// JavaScript
$browser->assertScript('return true');
```

## Common Dusk Actions

```php
// Navigation
$browser->visit('/path');
$browser->clickLink('Link Text');
$browser->back();
$browser->forward();
$browser->refresh();

// Forms
$browser->type('field', 'value');
$browser->select('select', 'value');
$browser->check('checkbox');
$browser->uncheck('checkbox');
$browser->attach('file', '/path/to/file');
$browser->press('Button Text');

// Waiting
$browser->waitFor('.selector');
$browser->waitForText('Text');
$browser->waitUntilMissing('.selector');
$browser->pause(1000); // milliseconds

// Screenshots
$browser->screenshot('filename');
```

## Troubleshooting

### ChromeDriver Issues
```bash
# Update ChromeDriver
php artisan dusk:chrome-driver --detect

# Use specific version
php artisan dusk:chrome-driver 120
```

### Headless Mode
In `tests/DuskTestCase.php`:
```php
protected function driver()
{
    $options = (new ChromeOptions)->addArguments([
        '--disable-gpu',
        '--headless',
        '--window-size=1920,1080',
    ]);

    return RemoteWebDriver::create(
        'http://localhost:9515',
        DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        )
    );
}
```

### Database Issues
Use `DatabaseMigrations` trait:
```php
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MyTest extends DuskTestCase
{
    use DatabaseMigrations;
}
```

## Best Practices

1. **Use Dusk Selectors** - Add `dusk` attributes to important elements
2. **Wait for Elements** - Use `waitFor()` instead of `pause()`
3. **Take Screenshots** - Capture screenshots for debugging
4. **Clean Database** - Use `DatabaseMigrations` trait
5. **Test User Flows** - Focus on complete user journeys
6. **Keep Tests Fast** - Minimize unnecessary waits
7. **Use Page Objects** - For complex pages, create Page classes

## Next Steps

1. Run `composer require --dev laravel/dusk`
2. Run `php artisan dusk:install`
3. Download ChromeDriver: `php artisan dusk:chrome-driver --detect`
4. Create your first test
5. Run tests: `php artisan dusk`

## Resources

- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [Browser Test Guide](tests/Browser/CROSS_BROWSER_TEST_GUIDE.md)
- [Selenium WebDriver](https://www.selenium.dev/documentation/)
