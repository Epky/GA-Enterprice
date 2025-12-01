# Requirements Document

## Introduction

Ang landing page ng customer dashboard ay kasalukuyang may "Shop by Category" section. Ang feature na ito ay magbabago ng landing page para direktang magpakita ng mga products na may pictures, na mas engaging at user-friendly para sa mga customers.

## Glossary

- **Landing Page**: Ang unang page na makikita ng customer pagkapasok sa customer dashboard
- **Product Grid**: Ang layout ng mga products na naka-arrange sa grid format
- **Featured Products**: Mga produktong specially highlighted sa landing page
- **Product Card**: Ang individual display component ng bawat product na may image, name, price, at details

## Requirements

### Requirement 1

**User Story:** Bilang customer, gusto kong makita agad ang mga available products na may pictures sa landing page, para mas madali akong makapili ng produkto.

#### Acceptance Criteria

1. WHEN a customer visits the landing page THEN the system SHALL display a grid of products with product images
2. WHEN displaying products THEN the system SHALL show product name, price, category, and primary image for each product
3. WHEN a product has no image THEN the system SHALL display a placeholder image
4. WHEN displaying the product grid THEN the system SHALL show at least 12 products per page with pagination
5. WHEN a customer clicks on a product card THEN the system SHALL navigate to the product detail page

### Requirement 2

**User Story:** Bilang customer, gusto kong makita ang featured products sa landing page, para makita ko agad ang mga special o recommended products.

#### Acceptance Criteria

1. WHEN the landing page loads THEN the system SHALL display featured products section above the main product grid
2. WHEN displaying featured products THEN the system SHALL show a maximum of 4 featured products
3. WHEN a product is marked as featured THEN the system SHALL display a featured badge on the product card
4. WHEN there are no featured products THEN the system SHALL hide the featured products section
5. WHEN displaying featured products THEN the system SHALL show the same information as regular product cards

### Requirement 3

**User Story:** Bilang customer, gusto kong may search at filter functionality sa landing page, para mas madali kong mahanap ang hinahanap kong produkto.

#### Acceptance Criteria

1. WHEN a customer uses the search bar THEN the system SHALL filter products based on product name, description, or SKU
2. WHEN a customer selects a category filter THEN the system SHALL display only products from that category
3. WHEN a customer selects a brand filter THEN the system SHALL display only products from that brand
4. WHEN a customer applies price range filters THEN the system SHALL display only products within the specified price range
5. WHEN filters are applied THEN the system SHALL preserve filter selections during pagination and sorting

### Requirement 4

**User Story:** Bilang customer, gusto kong may sorting options sa product grid, para ma-organize ko ang products based sa aking preference.

#### Acceptance Criteria

1. WHEN a customer selects "Newest First" sort option THEN the system SHALL display products ordered by creation date descending
2. WHEN a customer selects "Price: Low to High" sort option THEN the system SHALL display products ordered by price ascending
3. WHEN a customer selects "Price: High to Low" sort option THEN the system SHALL display products ordered by price descending
4. WHEN a customer selects "Name: A-Z" sort option THEN the system SHALL display products ordered alphabetically by name
5. WHEN sorting is applied THEN the system SHALL preserve the sort selection during pagination

### Requirement 5

**User Story:** Bilang customer, gusto kong makita kung out of stock ang isang product, para hindi ako mag-expect na available ito.

#### Acceptance Criteria

1. WHEN a product has zero available stock THEN the system SHALL display an "OUT OF STOCK" badge overlay on the product image
2. WHEN a product is out of stock THEN the system SHALL still display the product in the grid
3. WHEN displaying stock status THEN the system SHALL calculate total available stock across all inventory locations
4. WHEN a product becomes out of stock THEN the system SHALL update the display immediately upon page refresh
5. WHEN a product is out of stock THEN the system SHALL maintain the same card layout and information display
