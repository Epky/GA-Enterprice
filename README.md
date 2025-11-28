# Beauty Store E-Commerce System

A comprehensive e-commerce platform for beauty product retail, built with Laravel. This system supports both online shopping and walk-in transactions, with powerful analytics and inventory management capabilities.

## Features

### Customer Features
- Browse products by category and brand
- Product search and filtering
- Shopping cart management
- Online order placement
- Order history and tracking

### Staff Features
- Product management (CRUD operations)
- Inventory management with location tracking
- Walk-in transaction processing
- Inventory movement tracking
- Low stock alerts
- Brand and category management

### Admin Features
- **Comprehensive Analytics Dashboard** (see below)
- User management (customers, staff, admins)
- System health monitoring
- Data export capabilities

## Analytics Dashboard

The admin analytics dashboard provides comprehensive business intelligence with the following metrics:

### Available Metrics

#### Revenue Analytics
- **Total Revenue**: Sum of all completed and paid orders
- **Revenue Trends**: Daily/monthly revenue visualization
- **Revenue by Location**: Breakdown of sales by inventory location
- **Period Comparison**: Compare current period with previous period
- **Percentage Change**: Track revenue growth or decline

#### Order Analytics
- **Total Orders**: Count of all non-cancelled orders
- **Order Status Breakdown**: Completed, pending, and processing orders
- **Average Order Value (AOV)**: Revenue divided by completed orders
- **Order Type Distribution**: Walk-in vs online orders
- **Channel Comparison**: Revenue and order count by channel

#### Product Analytics
- **Top Selling Products**: Top 10 products by quantity sold
- **Sales by Category**: Revenue breakdown by product category
- **Sales by Brand**: Revenue and units sold by brand
- **Product Performance**: Quantity sold and revenue per product

#### Payment Analytics
- **Payment Method Distribution**: Orders and revenue by payment method
- **Payment Preferences**: Percentage breakdown of payment methods
- **Payment Trends**: Track popular payment methods over time

#### Profit Analytics
- **Gross Profit**: Total revenue minus total cost
- **Profit Margin**: Percentage of revenue retained as profit
- **Cost Analysis**: Total cost of goods sold

#### Customer Analytics
- **Total Customers**: Count of all registered customers
- **New Customers**: Customer acquisition by period
- **Growth Rate**: Customer growth percentage
- **Customer Trends**: Track customer acquisition over time

#### Inventory Analytics
- **Low Stock Alerts**: Products at or below reorder level
- **Out of Stock Count**: Products with zero inventory
- **Stock Severity**: Prioritized by lowest stock percentage
- **Inventory Health**: Overall inventory status

### Time Period Options

The analytics dashboard supports multiple time period filters:

- **Today**: Current day from midnight to now
- **This Week**: Current week from Monday to Sunday
- **This Month**: Current calendar month
- **This Year**: Current calendar year (January to December)
- **Custom Range**: User-defined start and end dates

All metrics automatically adjust to the selected time period, and comparisons are made with the equivalent previous period.

### Data Visualization

- **Line Charts**: Revenue and order trends over time
- **Pie Charts**: Payment method and channel distribution
- **Bar Charts**: Category and brand performance
- **Tables**: Top products, detailed breakdowns
- **Cards**: Key metrics with change indicators

### Export Functionality

Export comprehensive analytics data to CSV format:

1. Navigate to the admin dashboard
2. Select your desired time period
3. Click the "Export Analytics" button
4. CSV file downloads automatically with filename format: `analytics_YYYY-MM-DD_to_YYYY-MM-DD.csv`

**Export Contents:**
- Revenue metrics (total, previous period, change percentage)
- Order metrics (total, completed, walk-in, online, AOV)
- Profit metrics (gross profit, margin, cost)
- Customer metrics (total, new, growth rate)
- Top selling products (name, quantity, revenue)
- Sales by category (name, revenue, percentage)
- Sales by brand (name, revenue, units sold)
- Payment method distribution (method, count, revenue, percentage)
- Channel comparison (walk-in vs online)
- Revenue by location (location, revenue, order count)

### Performance Optimization

The analytics system includes several performance optimizations:

- **Caching**: Frequently accessed metrics are cached
  - Current period data: 15-minute cache
  - Historical data: 24-hour cache
- **Database Indexes**: Optimized queries with proper indexing
- **Query Optimization**: Efficient aggregation using database functions
- **Lazy Loading**: Charts and widgets load progressively

### Accessing Analytics

**URL**: `/admin/dashboard`

**Requirements**: Admin role required

**Usage Examples**:
```
# View current month analytics (default)
/admin/dashboard

# View this week's analytics
/admin/dashboard?period=week

# View custom date range
/admin/dashboard?period=custom&start_date=2024-01-01&end_date=2024-01-31

# Export analytics
/admin/analytics/export?period=month
```

## Technology Stack

- **Framework**: Laravel 11.x
- **Database**: PostgreSQL (Supabase)
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **Charts**: Chart.js
- **Authentication**: Laravel Breeze

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js and NPM
- PostgreSQL database (or Supabase account)

### Setup Steps

1. Clone the repository
```bash
git clone <repository-url>
cd beauty-store
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

5. Update `.env` with your database credentials
```env
DB_CONNECTION=pgsql
DB_HOST=your-database-host
DB_PORT=5432
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

6. Run migrations
```bash
php artisan migrate
```

7. Seed the database (optional)
```bash
php artisan db:seed
```

8. Build frontend assets
```bash
npm run build
```

9. Start the development server
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

### Default Admin Account

After seeding, you can log in with:
- **Email**: admin@example.com
- **Password**: password

## Testing

The application includes comprehensive test coverage:

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Categories

- **Unit Tests**: Service layer logic, calculations, data transformations
- **Feature Tests**: HTTP endpoints, workflows, integrations
- **Property-Based Tests**: Correctness properties validated across random inputs
- **Performance Tests**: Analytics query performance and cache effectiveness

## Documentation

Additional documentation is available in the `/docs` directory:

- [Analytics Performance Optimization](docs/ANALYTICS_PERFORMANCE_OPTIMIZATION_SUMMARY.md)
- [Analytics Performance Benchmark](docs/ANALYTICS_PERFORMANCE_BENCHMARK.md)
- [Inventory Location Fix](docs/inventory-location-fix.md)
- [Walk-in Transaction Inventory Reservation](docs/walk-in-transaction-inventory-reservation.md)
- [Product Image Troubleshooting](docs/product-image-troubleshooting.md)
- [Supabase Connection Setup](SUPABASE_CONNECTION_SETUP.md)

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # Admin controllers
│   ├── Staff/          # Staff controllers
│   ├── Customer/       # Customer controllers
│   └── Shop/           # Public shop controllers
├── Models/             # Eloquent models
├── Services/           # Business logic services
│   ├── AnalyticsService.php
│   ├── InventoryService.php
│   ├── ProductService.php
│   └── WalkInTransactionService.php
└── View/Components/    # Blade components

resources/
├── views/
│   ├── admin/          # Admin views
│   ├── staff/          # Staff views
│   ├── customer/       # Customer views
│   └── components/     # Reusable components
└── js/
    └── analytics-charts.js  # Chart.js initialization

tests/
├── Unit/               # Unit tests
├── Feature/            # Feature tests
└── Performance/        # Performance tests
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation as needed
- Ensure all tests pass before submitting PR

## Security

If you discover a security vulnerability, please email security@example.com. All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
