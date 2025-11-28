# Admin Analytics Dashboard User Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Accessing the Dashboard](#accessing-the-dashboard)
3. [Understanding the Dashboard Layout](#understanding-the-dashboard-layout)
4. [Time Period Filters](#time-period-filters)
5. [Metrics Explained](#metrics-explained)
6. [Interpreting Charts and Visualizations](#interpreting-charts-and-visualizations)
7. [Exporting Analytics Data](#exporting-analytics-data)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Introduction

The Admin Analytics Dashboard is your central hub for business intelligence and performance monitoring. It provides real-time insights into sales, revenue, inventory, customers, and overall business health. This guide will help you understand and effectively use all the analytics features available.

### What You Can Do
- Monitor revenue and sales performance
- Track top-selling products and categories
- Analyze customer behavior and growth
- Monitor inventory levels and alerts
- Compare walk-in vs online sales
- Export data for further analysis
- Make data-driven business decisions

---

## Accessing the Dashboard

### Requirements
- **Role**: Admin access required
- **URL**: Navigate to `/admin/dashboard` or click "Dashboard" in the admin navigation menu

### Login Steps
1. Go to the login page
2. Enter your admin credentials
3. Click "Login"
4. You'll be automatically redirected to the admin dashboard

---

## Understanding the Dashboard Layout

The dashboard is organized into several sections:

### 1. Header Section
- **Time Period Filter**: Dropdown to select date range
- **Export Button**: Download analytics data as CSV
- **Refresh Indicator**: Shows when data is loading

### 2. Key Metrics Cards (Top Row)
Quick overview cards showing:
- Total Revenue (with percentage change)
- Total Orders (with breakdown)
- Average Order Value
- New Customers
- Gross Profit
- Low Stock Alerts

Each card displays:
- **Current Value**: The metric for the selected period
- **Change Indicator**: Green (↑) for increase, Red (↓) for decrease
- **Percentage Change**: Compared to the previous equivalent period

### 3. Charts Section
Visual representations of data:
- **Sales Trend Chart**: Line chart showing revenue and orders over time
- **Payment Methods Chart**: Pie chart showing payment distribution
- **Category Performance**: Bar chart of sales by category

### 4. Tables Section
Detailed data tables:
- **Top Selling Products**: Products ranked by quantity sold
- **Sales by Category**: Revenue breakdown by category
- **Sales by Brand**: Revenue breakdown by brand
- **Revenue by Location**: Sales performance by store location

### 5. Additional Metrics
- **Channel Comparison**: Walk-in vs Online performance
- **Customer Metrics**: Total and new customer counts
- **Inventory Alerts**: Low stock warnings

---

## Time Period Filters

### Available Periods

#### Today
- **Range**: Current day from midnight to now
- **Use Case**: Monitor today's performance in real-time
- **Comparison**: Compares with yesterday

#### This Week
- **Range**: Monday to Sunday of current week
- **Use Case**: Track weekly performance and trends
- **Comparison**: Compares with previous week

#### This Month (Default)
- **Range**: First day to last day of current month
- **Use Case**: Monthly performance review and planning
- **Comparison**: Compares with previous month

#### This Year
- **Range**: January 1 to December 31 of current year
- **Use Case**: Annual performance review
- **Comparison**: Compares with previous year
- **Note**: Shows monthly data points instead of daily

#### Custom Range
- **Range**: User-defined start and end dates
- **Use Case**: Analyze specific periods (e.g., holiday season, promotional period)
- **Comparison**: Compares with equivalent previous period

### How to Change Time Period

1. Locate the time period dropdown at the top of the dashboard
2. Click to open the dropdown menu
3. Select your desired period:
   - Click "Today", "This Week", "This Month", or "This Year" for preset periods
   - Click "Custom Range" to specify exact dates
4. If using Custom Range:
   - Select start date from the calendar
   - Select end date from the calendar
   - Click "Apply" or "Update"
5. The dashboard will automatically refresh with new data

**Note**: All metrics, charts, and tables update simultaneously when you change the time period.

---

## Metrics Explained

### Revenue Metrics

#### Total Revenue
- **What it shows**: Sum of all completed and paid orders
- **Calculation**: Only includes orders with status "completed" and payment status "paid"
- **Why it matters**: Primary indicator of business performance
- **How to interpret**:
  - Green indicator (↑): Revenue is growing compared to previous period
  - Red indicator (↓): Revenue is declining compared to previous period
  - Percentage shows the rate of change

**Example**: 
```
Total Revenue: ₱125,450.00 ↑ 15.3%
```
This means you earned ₱125,450 in the selected period, which is 15.3% more than the previous equivalent period.

#### Revenue by Location
- **What it shows**: Revenue breakdown by inventory location
- **Use Case**: Identify which locations are performing best
- **Action Items**: 
  - Allocate more inventory to high-performing locations
  - Investigate underperforming locations
  - Plan location-specific promotions

### Order Metrics

#### Total Orders
- **What it shows**: Count of all non-cancelled orders
- **Includes**: Completed, pending, and processing orders
- **Excludes**: Cancelled orders
- **Why it matters**: Indicates transaction volume and customer activity

#### Order Type Breakdown
- **Walk-in Orders**: Orders created by staff for in-store customers
- **Online Orders**: Orders placed through the e-commerce website
- **Use Case**: Understand which channel drives more transactions
- **Action Items**:
  - If walk-in is higher: Focus on in-store experience
  - If online is higher: Invest in digital marketing
  - Balance resources between channels

#### Average Order Value (AOV)
- **What it shows**: Average amount spent per order
- **Calculation**: Total Revenue ÷ Number of Completed Orders
- **Why it matters**: Indicates customer spending behavior
- **How to improve**:
  - Implement upselling strategies
  - Create product bundles
  - Offer volume discounts
  - Suggest complementary products

**Example**:
```
AOV: ₱1,250.00
```
This means on average, each customer spends ₱1,250 per order.

**Strategies to increase AOV**:
- "Customers who bought this also bought..."
- "Buy 2, Get 10% off"
- Free shipping on orders over ₱2,000

### Product Analytics

#### Top Selling Products
- **What it shows**: Products ranked by quantity sold
- **Displays**: Product name, quantity sold, revenue generated
- **Default**: Top 10 products
- **Use Case**: 
  - Identify bestsellers for restocking
  - Plan promotions around popular items
  - Understand customer preferences

**How to use this data**:
1. **Ensure Stock Availability**: Never run out of top sellers
2. **Feature Prominently**: Display bestsellers on homepage
3. **Create Bundles**: Combine top sellers with slower-moving items
4. **Analyze Trends**: Track if top products change over time

#### Sales by Category
- **What it shows**: Revenue breakdown by product category
- **Displays**: Category name, revenue, percentage of total
- **Sorted by**: Revenue (highest to lowest)
- **Use Case**:
  - Identify most profitable categories
  - Allocate marketing budget by category performance
  - Plan inventory purchases

**Example**:
```
Skincare: ₱45,000 (36%)
Makeup: ₱38,000 (30%)
Haircare: ₱25,000 (20%)
Fragrance: ₱17,450 (14%)
```

**Interpretation**: Skincare is your strongest category, generating 36% of total revenue. Consider expanding skincare product lines.

#### Sales by Brand
- **What it shows**: Revenue and units sold by brand
- **Use Case**:
  - Identify top-performing brands
  - Negotiate better terms with popular brands
  - Decide which brands to promote

### Payment Analytics

#### Payment Method Distribution
- **What it shows**: How customers prefer to pay
- **Displays**: Payment method, order count, revenue, percentage
- **Common Methods**: Cash, Credit Card, Debit Card, E-Wallet, Bank Transfer
- **Use Case**:
  - Ensure popular payment methods are always available
  - Consider adding new payment options if needed
  - Optimize checkout process for popular methods

**Example**:
```
E-Wallet: 45% of orders, ₱56,250 revenue
Credit Card: 30% of orders, ₱37,500 revenue
Cash: 25% of orders, ₱31,250 revenue
```

**Action Items**:
- If e-wallet is popular, ensure QR codes are visible
- If cash is high, ensure adequate change is available
- If credit card is low, check if processing fees are too high

### Profit Analytics

#### Gross Profit
- **What it shows**: Revenue minus cost of goods sold
- **Calculation**: (Sale Price - Cost Price) × Quantity for all sold items
- **Note**: Only includes products with cost price data
- **Why it matters**: Shows actual profitability, not just revenue

#### Profit Margin
- **What it shows**: Percentage of revenue retained as profit
- **Calculation**: (Gross Profit ÷ Total Revenue) × 100
- **Healthy Range**: Typically 30-50% for retail
- **Use Case**: 
  - Evaluate pricing strategy
  - Identify low-margin products
  - Make decisions about discounts and promotions

**Example**:
```
Gross Profit: ₱45,000
Profit Margin: 36%
```

**Interpretation**: For every ₱100 in sales, you keep ₱36 as profit after paying for the products.

**Warning Signs**:
- Margin below 20%: Review pricing or find cheaper suppliers
- Margin declining: Costs may be rising or discounts too frequent
- Margin varies by category: Some categories may be unprofitable

### Customer Analytics

#### Total Customers
- **What it shows**: Count of all registered customer accounts
- **Use Case**: Track customer base growth over time

#### New Customers
- **What it shows**: Customers who registered during the selected period
- **Why it matters**: Indicates customer acquisition effectiveness
- **Growth Rate**: Percentage change compared to previous period

**Example**:
```
Total Customers: 1,250
New Customers: 85 ↑ 12%
```

**Interpretation**: You gained 85 new customers this period, which is 12% more than the previous period.

**Action Items**:
- If growth is positive: Continue current marketing strategies
- If growth is negative: Review marketing campaigns, improve customer experience
- If growth is stagnant: Launch customer referral program

### Inventory Analytics

#### Low Stock Alerts
- **What it shows**: Products at or below reorder level
- **Displays**: Product name, current quantity, reorder level, stock percentage
- **Sorted by**: Severity (lowest stock percentage first)
- **Use Case**: Prevent stockouts of popular items

**How to use**:
1. **Daily Check**: Review low stock alerts every morning
2. **Prioritize**: Focus on items with lowest stock percentage
3. **Reorder**: Place orders with suppliers immediately
4. **Track**: Monitor if items frequently appear in alerts

**Example**:
```
Product: Moisturizing Cream
Current Stock: 5 units
Reorder Level: 20 units
Stock Percentage: 25%
Status: LOW STOCK
```

**Action**: Order more Moisturizing Cream immediately to avoid stockout.

---

## Interpreting Charts and Visualizations

### Sales Trend Chart (Line Chart)

**What it shows**: Revenue and order count over time

**How to read it**:
- **X-axis**: Time (dates or months)
- **Y-axis (Left)**: Revenue amount
- **Y-axis (Right)**: Order count
- **Blue Line**: Revenue trend
- **Orange Line**: Order count trend

**Patterns to look for**:

1. **Upward Trend**: Business is growing
   - Action: Maintain current strategies, prepare for increased demand

2. **Downward Trend**: Business is declining
   - Action: Investigate causes, launch promotions, improve marketing

3. **Spikes**: Sudden increases on specific days
   - Action: Identify what caused the spike (promotion, holiday, event)
   - Replicate successful strategies

4. **Dips**: Sudden decreases on specific days
   - Action: Identify causes (system issues, competitor promotions)
   - Plan to prevent future dips

5. **Seasonal Patterns**: Regular ups and downs
   - Action: Plan inventory and staffing for busy periods
   - Launch promotions during slow periods

**Example Interpretation**:
```
If you see revenue increasing but order count staying flat:
→ AOV is increasing (customers spending more per order)
→ Upselling strategies are working

If you see order count increasing but revenue staying flat:
→ AOV is decreasing (customers spending less per order)
→ May need to review pricing or product mix
```

### Payment Methods Chart (Pie Chart)

**What it shows**: Distribution of payment methods used

**How to read it**:
- Each slice represents a payment method
- Size of slice = percentage of total orders
- Hover over slice to see exact numbers

**What to look for**:
- **Dominant Method**: Largest slice shows most popular payment
- **Balanced Distribution**: Multiple similar-sized slices indicate diverse payment preferences
- **Tiny Slices**: Payment methods rarely used (consider removing if maintenance is costly)

**Action Items**:
- Ensure most popular methods are always functional
- Train staff on popular payment methods
- Consider adding new methods if customer requests are frequent

### Category Performance Chart (Bar Chart)

**What it shows**: Revenue by product category

**How to read it**:
- **X-axis**: Category names
- **Y-axis**: Revenue amount
- **Bar Height**: Higher bar = more revenue

**What to look for**:
- **Tallest Bars**: Your strongest categories
- **Shortest Bars**: Underperforming categories
- **Gaps**: Large differences between categories

**Action Items**:
1. **For Top Categories**:
   - Expand product selection
   - Feature prominently on website
   - Allocate more inventory budget

2. **For Weak Categories**:
   - Investigate why sales are low
   - Consider promotions or discounts
   - Evaluate if category should be discontinued

---

## Exporting Analytics Data

### Why Export Data?

- Create custom reports for stakeholders
- Perform deeper analysis in Excel or other tools
- Archive historical data for record-keeping
- Share insights with team members
- Create presentations for business meetings

### How to Export

1. **Select Time Period**: Choose the period you want to export
2. **Click Export Button**: Located at the top right of the dashboard
3. **Wait for Download**: File generates and downloads automatically
4. **Open File**: CSV file opens in Excel, Google Sheets, or any spreadsheet application

### Export File Format

**Filename**: `analytics_YYYY-MM-DD_to_YYYY-MM-DD.csv`

**Example**: `analytics_2024-01-01_to_2024-01-31.csv`

### What's Included in the Export

The CSV file contains all analytics data organized in sections:

1. **Summary Section**
   - Period selected
   - Date range

2. **Revenue Metrics**
   - Total revenue
   - Previous period revenue
   - Change percentage

3. **Order Metrics**
   - Total orders
   - Completed orders
   - Walk-in orders
   - Online orders
   - Average order value

4. **Profit Metrics**
   - Gross profit
   - Profit margin
   - Total cost

5. **Customer Metrics**
   - Total customers
   - New customers
   - Growth rate

6. **Top Selling Products**
   - Product name
   - Quantity sold
   - Revenue

7. **Sales by Category**
   - Category name
   - Revenue
   - Percentage

8. **Sales by Brand**
   - Brand name
   - Revenue
   - Units sold

9. **Payment Method Distribution**
   - Payment method
   - Order count
   - Revenue
   - Percentage

10. **Channel Comparison**
    - Walk-in vs Online
    - Revenue and order count for each

11. **Revenue by Location**
    - Location name
    - Revenue
    - Order count

### Using Exported Data

**In Excel/Google Sheets**:
1. Open the CSV file
2. Use pivot tables for custom analysis
3. Create additional charts and visualizations
4. Calculate custom metrics
5. Compare multiple time periods side-by-side

**For Presentations**:
1. Copy key metrics into PowerPoint/Google Slides
2. Create charts from the data
3. Highlight trends and insights
4. Add context and recommendations

**For Record Keeping**:
1. Save exports in organized folders by month/quarter
2. Create a historical database
3. Track long-term trends
4. Reference for year-over-year comparisons

---

## Best Practices

### Daily Routine

**Morning (9:00 AM)**:
1. Check "Today" metrics to see overnight online orders
2. Review low stock alerts
3. Check if any products need immediate reordering
4. Note any unusual spikes or dips

**Midday (1:00 PM)**:
1. Quick check of today's revenue vs. yesterday
2. Monitor if any payment methods are having issues
3. Check if top products are in stock

**End of Day (6:00 PM)**:
1. Review full day's performance
2. Compare with same day last week
3. Note any patterns or anomalies
4. Plan for tomorrow based on insights

### Weekly Routine

**Monday Morning**:
1. Review "This Week" metrics
2. Compare with previous week
3. Set weekly goals based on trends
4. Plan promotions if needed

**Friday Afternoon**:
1. Review week's performance
2. Identify top-performing products
3. Check inventory levels for weekend
4. Export weekly data for records

### Monthly Routine

**First Day of Month**:
1. Review previous month's performance
2. Export previous month's data
3. Set monthly goals
4. Plan promotions and campaigns

**Mid-Month**:
1. Check progress toward monthly goals
2. Adjust strategies if needed
3. Review inventory turnover

**End of Month**:
1. Comprehensive review of all metrics
2. Export monthly data
3. Create summary report for stakeholders
4. Plan for next month

### Quarterly Routine

**End of Quarter**:
1. Review 3-month trends
2. Analyze seasonal patterns
3. Evaluate product category performance
4. Review profit margins
5. Plan for next quarter
6. Update business strategy based on insights

### Making Data-Driven Decisions

**When Revenue is Down**:
1. Check if order count is also down (fewer customers) or just AOV (customers spending less)
2. Review top products - are bestsellers out of stock?
3. Check payment methods - are any not working?
4. Look at channel comparison - is one channel underperforming?
5. Review customer metrics - are you losing customers?

**When Revenue is Up**:
1. Identify what's driving growth (more orders or higher AOV?)
2. Check which products/categories are performing well
3. Ensure adequate inventory of top sellers
4. Consider expanding successful strategies
5. Document what's working for future reference

**When Profit Margin is Low**:
1. Review cost prices - have supplier costs increased?
2. Check if discounts are too frequent or too deep
3. Identify low-margin products
4. Consider adjusting prices
5. Negotiate better terms with suppliers

**When Inventory Alerts are High**:
1. Review reorder levels - are they set correctly?
2. Check if top sellers are frequently low
3. Improve forecasting based on sales trends
4. Consider increasing safety stock for bestsellers
5. Evaluate supplier lead times

---

## Troubleshooting

### Dashboard Not Loading

**Symptoms**: Blank page, loading spinner doesn't stop, error message

**Solutions**:
1. **Refresh the page**: Press F5 or click refresh button
2. **Clear browser cache**: 
   - Chrome: Ctrl+Shift+Delete
   - Select "Cached images and files"
   - Click "Clear data"
3. **Try different browser**: Use Chrome, Firefox, or Edge
4. **Check internet connection**: Ensure you're connected
5. **Contact IT support**: If issue persists

### Data Seems Incorrect

**Symptoms**: Numbers don't match expectations, metrics seem too high/low

**Checks**:
1. **Verify time period**: Ensure correct period is selected
2. **Check date range**: For custom ranges, verify start and end dates
3. **Review order statuses**: Remember only completed/paid orders count for revenue
4. **Consider timezone**: Data is based on server timezone
5. **Compare with previous exports**: Check if historical data matches

**If data is truly incorrect**:
1. Note the specific metric that's wrong
2. Note the time period selected
3. Take a screenshot
4. Contact technical support with details

### Export Not Downloading

**Symptoms**: Click export button but file doesn't download

**Solutions**:
1. **Check browser downloads**: File may have downloaded to default folder
2. **Allow pop-ups**: Browser may be blocking download
   - Look for blocked pop-up icon in address bar
   - Click and allow pop-ups from this site
3. **Disable ad blocker**: May be interfering with download
4. **Try different browser**: Some browsers handle downloads differently
5. **Check disk space**: Ensure enough space for file

### Charts Not Displaying

**Symptoms**: Empty chart areas, broken chart icons

**Solutions**:
1. **Refresh page**: Charts may not have loaded
2. **Check JavaScript**: Ensure JavaScript is enabled in browser
3. **Disable browser extensions**: Ad blockers may block Chart.js
4. **Clear cache**: Old cached files may be causing issues
5. **Update browser**: Ensure using latest browser version

### Slow Performance

**Symptoms**: Dashboard takes long time to load, charts lag

**Reasons**:
1. **Large date range**: Yearly or custom ranges with lots of data
2. **Many concurrent users**: Multiple admins accessing simultaneously
3. **Server load**: High traffic on the website
4. **Network speed**: Slow internet connection

**Solutions**:
1. **Use shorter periods**: Start with "This Month" instead of "This Year"
2. **Wait for off-peak hours**: Access during less busy times
3. **Close other tabs**: Free up browser resources
4. **Check internet speed**: Ensure stable connection
5. **Contact IT**: May need server optimization

### Missing Data

**Symptoms**: Some products, categories, or metrics show zero or are missing

**Possible Causes**:
1. **No data for period**: No sales in selected time range
2. **Filters applied**: Some data may be filtered out
3. **Deleted records**: Products/categories may have been deleted
4. **Data not synced**: Recent orders may not be processed yet

**Solutions**:
1. **Try different period**: Check if data exists in other periods
2. **Verify in database**: Check if orders actually exist
3. **Wait and refresh**: Recent data may need time to process
4. **Check order statuses**: Ensure orders are marked as completed

---

## Tips for Success

### Understanding Your Business

1. **Know Your Baseline**: Track metrics over time to understand what's "normal" for your business
2. **Identify Patterns**: Look for daily, weekly, and seasonal patterns
3. **Set Realistic Goals**: Base goals on historical performance and growth trends
4. **Focus on Trends**: Don't panic over single-day fluctuations; look at longer trends

### Using Analytics Effectively

1. **Regular Monitoring**: Check dashboard daily, even if just for a few minutes
2. **Compare Periods**: Always look at change percentages, not just absolute numbers
3. **Dig Deeper**: If a metric is unusual, investigate the underlying data
4. **Take Action**: Analytics are only useful if you act on insights
5. **Document Decisions**: Note why you made changes based on data

### Improving Metrics

**To Increase Revenue**:
- Promote top-selling products
- Create product bundles
- Launch targeted marketing campaigns
- Improve product descriptions and images
- Offer limited-time promotions

**To Increase AOV**:
- Suggest complementary products
- Offer volume discounts
- Create minimum order for free shipping
- Upsell premium versions
- Bundle slow-moving items with bestsellers

**To Improve Profit Margin**:
- Negotiate better supplier prices
- Reduce unnecessary discounts
- Focus on high-margin products
- Optimize shipping costs
- Reduce operational waste

**To Grow Customer Base**:
- Launch referral program
- Improve customer service
- Enhance website user experience
- Invest in digital marketing
- Engage on social media

### Common Mistakes to Avoid

1. **Ignoring Context**: A 50% increase sounds great, but if it's from ₱100 to ₱150, it's not significant
2. **Overreacting to Single Days**: One bad day doesn't mean disaster; look at weekly trends
3. **Focusing Only on Revenue**: Profit matters more than revenue
4. **Neglecting Inventory**: Running out of bestsellers loses sales
5. **Not Exporting Data**: Regular exports create valuable historical records
6. **Making Changes Too Quickly**: Give strategies time to work before changing
7. **Ignoring Customer Metrics**: Revenue without customer growth is unsustainable

---

## Glossary

**AOV (Average Order Value)**: Average amount spent per order

**Completed Order**: Order that has been fulfilled and delivered

**Gross Profit**: Revenue minus cost of goods sold

**Growth Rate**: Percentage change compared to previous period

**Low Stock**: Inventory at or below reorder level

**Order Status**: Current state of order (pending, processing, completed, cancelled)

**Payment Status**: Whether payment has been received (paid, pending, failed)

**Period Comparison**: Comparing current period with equivalent previous period

**Profit Margin**: Percentage of revenue retained as profit

**Reorder Level**: Minimum stock quantity before reordering is needed

**Revenue**: Total monetary value from completed and paid orders

**Stock Percentage**: Current quantity as percentage of reorder level

**Walk-in Order**: Order created by staff for in-store customer

**Online Order**: Order placed through e-commerce website

---

## Support

If you need help or have questions:

1. **Check this guide**: Most common questions are answered here
2. **Review documentation**: Check `/docs` folder for technical details
3. **Contact IT support**: Email support@example.com
4. **Request training**: Ask for one-on-one analytics training session

---

## Feedback

We're constantly improving the analytics dashboard. If you have suggestions:

- What metrics would you like to see?
- What features would make your job easier?
- What's confusing or unclear?
- What additional reports do you need?

Send feedback to: analytics-feedback@example.com

---

**Last Updated**: November 2024  
**Version**: 1.0  
**For**: Admin Analytics Dashboard v1.0
