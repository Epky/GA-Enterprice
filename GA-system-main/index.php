<?php
// Start session if not already started
session_start();
include 'sql/sql.php';

// Check if connection is established
if (!$conn || $conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Retrieve form data
  $name = $_POST['name'];
  $email = $_POST['email'];
  $number = $_POST['number'];
  $message = $_POST['message'];

  // Prepare SQL to insert contact message
  $sql = "INSERT INTO tbl_contact_messages (name, email, number, message) VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);

  if ($stmt) {
    $stmt->bind_param("ssss", $name, $email, $number, $message);
    if ($stmt->execute()) {
      // SweetAlert for success
      echo "<script>
                setTimeout(function() {
                    swal({
                        title: 'Success!',
                        text: 'Message sent successfully!',
                        type: 'success'
                    }, function() {
                        window.location = 'index.php'; // Redirect after message
                    });
                }, 500);
            </script>";
    } else {
      // SweetAlert for error
      echo "<script>
                setTimeout(function() {
                    swal({
                        title: 'Error!',
                        text: 'Error: {$stmt->error}',
                        type: 'error'
                    });
                }, 500);
            </script>";
    }
    $stmt->close();
  } else {
    // SweetAlert for SQL prepare error
    echo "<script>
            setTimeout(function() {
                swal({
                    title: 'Error!',
                    text: 'Error preparing statement: {$conn->error}',
                    type: 'error'
                });
            }, 500);
        </script>";
  }
}

// Function to fetch the trending product based on the most ordered product from tbl_orders
function fetchTrendingProduct()
{
  global $conn;

  if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT p.product_name, p.product_price, p.product_image_1, COUNT(o.product_id) as order_count
            FROM tbl_orders o
            JOIN tbl_product p ON o.product_id = p.product_id
            GROUP BY o.product_id
            ORDER BY order_count DESC
            LIMIT 1";

  $result = $conn->query($sql);

  if (!$result) {
    // Output error if the query fails
    die("Error in SQL query: " . $conn->error . " - Query: " . $sql);
  }

  if ($result->num_rows > 0) {
    return $result->fetch_assoc();
  } else {
    return null;
  }
}

// Fetch all product images from tbl_product
function fetchAllProductImages()
{
  global $conn;

  if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT product_image_1, product_image_2, product_image_3 FROM tbl_product";
  $result = $conn->query($sql);

  $images = [];
  while ($row = $result->fetch_assoc()) {
    if (!empty($row['product_image_1'])) {
      $images[] = $row['product_image_1'];
    }
    if (!empty($row['product_image_2'])) {
      $images[] = $row['product_image_2'];
    }
    if (!empty($row['product_image_3'])) {
      $images[] = $row['product_image_3'];
    }
  }
  return $images;
}

// Function to fetch the most ordered product
function fetchBestSellingProduct()
{
  global $conn;

  if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
  }

  // Query to fetch all products ordered with their count
  $sql = "SELECT o.product_id, p.product_name, p.product_price, p.product_image_1, COUNT(o.product_id) as order_count
            FROM tbl_orders o
            JOIN tbl_product p ON o.product_id = p.product_id
            GROUP BY o.product_id
            ORDER BY order_count DESC";

  $result = $conn->query($sql);

  if (!$result) {
    // Output error if the query fails
    die("Error in SQL query: " . $conn->error . " - Query: " . $sql);
  }

  // Check if all product IDs are the same
  $productData = [];
  while ($row = $result->fetch_assoc()) {
    $productData[] = $row;
  }

  // If there is only one unique product_id, return that product
  if (count($productData) > 0) {
    $firstProductId = $productData[0]['product_id'];
    $allSame = true;

    foreach ($productData as $product) {
      if ($product['product_id'] !== $firstProductId) {
        $allSame = false;
        break;
      }
    }

    if ($allSame) {
      // All products have the same product_id, return the first product
      return $productData[0];
    }
  }

  return null; // Return null if no best-selling product found or products are different
}

function fetchLatestProducts($limit = 3)
{
  global $conn;

  if (!$conn || $conn->connect_error) {
    die("Database connection error: " . $conn->connect_error);
  }

  $sql = "SELECT product_id, product_name, product_description, product_image_1 
            FROM tbl_product 
            ORDER BY date_added DESC 
            LIMIT ?";

  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
      $products[] = $row;
    }

    $stmt->close();
    return $products;
  } else {
    die("Error preparing statement: " . $conn->error);
  }
}

// Fetch the latest products (you can set a limit to how many products you want to show)
$latestProducts = fetchLatestProducts(3);

$bestSellingProduct = fetchBestSellingProduct();

$productImages = fetchAllProductImages();

$trendingProduct = fetchTrendingProduct();

// Close connection at the end of the script
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>G.A. Ruiz Enterprise </title>
  <link rel="icon" href="assets/images/gra.png" type="assets/images/gra.png">
  <link rel="stylesheet" href="landing/vendors/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="landing/vendors/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="landing/vendors/themify-icons/themify-icons.css">
  <link rel="stylesheet" href="landing/vendors/nice-select/nice-select.css">
  <link rel="stylesheet" href="landing/vendors/owl-carousel/owl.theme.default.min.css">
  <link rel="stylesheet" href="landing/vendors/owl-carousel/owl.carousel.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
  <link rel="stylesheet" href="landing/css/style.css">
  <style>
    .logo_h img {
      width: 100px;
      /* Adjust width to your preference */
      height: auto;
      /* Maintain aspect ratio */
    }

    #heroProductSlider .item img {
      width: 100%;
      /* Full width */
      height: auto;
      /* Maintain aspect ratio */
      max-height: 500px;
      /* Set maximum height */
      object-fit: cover;
      /* Ensure the image covers the container */
    }

    .hero-banner__img {
      max-height: 500px;
      /* Set a maximum height for the container */
      overflow: hidden;
      /* Hide overflow to prevent image overflow */
    }
  </style>
</head>

<body>
  <header class="header_area">
    <div class="main_menu">
      <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
          <a class="navbar-brand logo_h">
            <img src="assets/images/gra.png" alt="G.A. Ruiz Enterprise ">
          </a>
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
            <ul class="nav navbar-nav menu_nav ml-5px mr-auto">
              <li class="nav-item active">
                <a class="nav-link"><b>Home</b></a>
              </li>


              <!-- <li class="nav-item submenu dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Blog</a>
    <ul class="dropdown-menu">
        <li class="nav-item"><a class="nav-link" href="blog.html">All Blog Posts</a></li>
        <li class="nav-item"><a class="nav-link" href="blog-category.html">Blog Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="latest-news.html">Latest News</a></li>
    </ul>
</li> 

              </li-->
              <li class="nav-item submenu dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                  <b>Pages</b>
                </a>
                <ul class="dropdown-menu">
                  <li class="nav-item"><a class="nav-link" href="login.php">Login As Customer</a></li>
                  <li class="nav-item"><a class="nav-link" href="login-admin.php">Login As Admin</a></li>
                  <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                </ul>
              </li>

              <!-- <li class="nav-item">
    <a class="nav-link" href="contact.html">
        <i class="fa fa-phone"></i> Contact Us
    </a>
</li> -->

            </ul>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <main class="site-main">
    <div class="container">
      <div class="row no-gutters align-items-center grat-60px">
        <div class="col-12">
          <div class="hero-banner__img owl-carousel owl-theme" id="heroProductSlider">
            <?php foreach ($productImages as $image): ?>
              <div class="item">
                <img class="img-fluid" src="uploads/<?php echo htmlspecialchars($image); ?>" alt="Product Image">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-sm-7 col-lg-6 offset-lg-1 pl-4 pl-md-5 pl-lg-0">
          <div class="hero-banner__content">
          </div>
        </div>
      </div>
    </div>
    <section class="section-margin calc-60px">
      <div class="container">
        <div class="section-intro pb-60px">
          <h2>Popular Item in the <span class="section-intro__style">Market</span></h2>
        </div>
        <div class="row">
          <?php if ($trendingProduct) { ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
              <div class="card text-center card-product">
                <div class="card-product__img">
                  <img class="card-img" src="uploads/<?php echo htmlspecialchars($trendingProduct['product_image_1']); ?>" alt="Trending Product">
                  <ul class="card-product__imgOverlay">
                    <li><button><i class="ti-shopping-cart"></i></button></li>
                  </ul>
                </div>
                <div class="card-body">
                  <p>Trending Product</p>
                  <h4 class="card-product__title"><a href="single-product.html"><?php echo htmlspecialchars($trendingProduct['product_name']); ?></a></h4>
                  <p class="card-product__price">₱<?php echo number_format($trendingProduct['product_price'], 2); ?></p>
                </div>
              </div>
            </div>
          <?php } else { ?>
            <div class="col-12">
              <p>No trending products available at the moment.</p>
            </div>
          <?php } ?>
        </div>
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-12">
              <!-- Product Slider Start -->
              <div class="owl-carousel owl-theme" id="productSlider">
                <?php if (!empty($productImages)) { ?>
                  <?php foreach ($productImages as $image) { ?>
                    <div class="item">
                      <img src="uploads/<?php echo htmlspecialchars($image); ?>" alt="Product Image" class="img-fluid product-slider-img">
                    </div>
                  <?php } ?>
                <?php } else { ?>
                  <p>No products available at the moment.</p>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
        <section class="section-margin calc-60px">
          <div class="container">
            <div class="section-intro pb-60px">
              <p>Popular Item in the market</p>
              <h2>Best <span class="section-intro__style">Sellers</span></h2>
            </div>
            <div class="owl-carousel owl-theme" id="bestSellerCarousel">
              <?php if (!empty($bestSellingProduct)) { ?>
                <!-- Display the Best Selling Product -->
                <div class="card text-center card-product">
                  <div class="card-product__img">
                    <img class="img-fluid" src="uploads/<?php echo htmlspecialchars($bestSellingProduct['product_image_1']); ?>" alt="<?php echo htmlspecialchars($bestSellingProduct['product_name']); ?>">
                  </div>
                  <div class="card-body">
                    <p>Category</p> <!-- Replace with actual category if available -->
                    <h4 class="card-product__title"><a href="single-product.php?product_id=<?php echo $bestSellingProduct['product_id']; ?>"><?php echo htmlspecialchars($bestSellingProduct['product_name']); ?></a></h4>
                    <p class="card-product__price">₱<?php echo htmlspecialchars(number_format($bestSellingProduct['product_price'], 2)); ?></p>
                  </div>
                </div>
              <?php } else { ?>
                <p>No best-selling products available at the moment.</p>
              <?php } ?>
            </div>
          </div>
        </section>
        <section class="latest-products section-margin calc-60px">
          <div class="container">
            <div class="section-intro pb-60px text-center">
              <h2>Latest <span class="section-intro__style">Products</span></h2>
            </div>

            <div class="row">
              <?php if (!empty($latestProducts)) { ?>
                <?php foreach ($latestProducts as $product) { ?>
                  <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card card-product card-product--latest">
                      <div class="card-product__img">
                        <img class="img-fluid rounded" src="uploads/<?php echo htmlspecialchars($product['product_image_1']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                      </div>
                      <div class="card-body">
                        <h4 class="card-product__title"><?php echo htmlspecialchars($product['product_name']); ?></a></h4>
                        <p class="card-product__description"><?php echo htmlspecialchars(substr($product['product_description'], 0, 80)) . '...'; ?></p>
                      </div>
                    </div>
                  </div>
                <?php } ?>
              <?php } else { ?>
                <div class="col-12 text-center">
                  <p>No latest products available at the moment.</p>
                </div>
              <?php } ?>
            </div>
          </div>
        </section>
        <section class="subscribe-position">
          <div class="container">
            <div class="subscribe text-center">
              <h3 class="subscribe__title">Get In Touch.</h3>
              <p>Bearing Void gathering light light his eavening unto don't be afraid</p>
              <div id="contact_form">
                <form action="index.php" method="post" class="contact-form mt-5 pt-1">
                  <div class="form-group">
                    <input class="form-control mb-3" type="text" name="name" placeholder="Enter your name" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control mb-3" type="email" name="email" placeholder="Enter your email" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control mb-3" type="text" name="number" placeholder="Enter your number" required>
                  </div>
                  <div class="form-group">
                    <textarea class="form-control mb-3" name="message" placeholder="Enter your message" rows="4" required></textarea>
                  </div>
                  <button class="btn btn-primary" type="submit">Send Message</button>
                </form>
              </div>
            </div>
          </div>
        </section>
  </main>
  <footer class="footer">
    <div class="footer-area">
      <div class="container">
        <div class="row section_gap">
          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="single-footer-widget tp_widgets">
              <h4 class="footer_title large_title">Our Mission</h4>
              <p>
                To be able to offer excellent dry goods, school supplies, and cosmetics to the clients in Digos City
                and its other areas by guaranteeing world-class service delivery, with excellent and sustained relations
                with suppliers and adopting modern technologies in its endeavor towards a culture of innovation and continuous
                improvement.
              </p>
              <p>
                Our goal is to inspire positive change through impactful initiatives and services.
              </p>
            </div>
          </div>

          <div class="offset-lg-1 col-lg-2 col-md-6 col-sm-6">
            <div class="single-footer-widget tp_widgets">
              <h4 class="footer_title">Quick Links</h4>
              <ul class="list">
                <li><a href="#">Home</a></li>
                <li><a href="#">Shop</a></li>
                <li><a href="#">Blog</a></li>
                <li><a href="#">Products</a></li>
                <li><a href="#">Brands</a></li>
                <li><a href="#">Contact</a></li>
              </ul>
            </div>
          </div>

          <div class="col-lg-2 col-md-6 col-sm-6">
            <div class="single-footer-widget instafeed">
              <h4 class="footer_title">Gallery</h4>
              <ul class="list instafeed d-flex flex-wrap">
                <li><img src="img/gallery/photo1.jpg" alt="Gallery Image 1"></li>
                <li><img src="img/gallery/photo2.jpg" alt="Gallery Image 2"></li>
                <li><img src="img/gallery/photo3.jpg" alt="Gallery Image 3"></li>
                <li><img src="img/gallery/photo4.jpg" alt="Gallery Image 4"></li>
                <li><img src="img/gallery/photo5.jpg" alt="Gallery Image 5"></li>
                <li><img src="img/gallery/photo6.jpg" alt="Gallery Image 6"></li>
              </ul>
            </div>
          </div>

          <div class="offset-lg-1 col-lg-3 col-md-6 col-sm-6">
            <div class="single-footer-widget tp_widgets">
              <h4 class="footer_title">Contact Us</h4>
              <div class="ml-40">
                <p class="sm-head">
                  <span class="fa fa-location-arrow"></span>
                  Head Office
                </p>
                <p>Business Center, Digos City</p>

                <p class="sm-head">
                  <span class="fa fa-phone"></span>
                  Phone Number
                </p>
                <p>
                  +09913615463 <br>
                  +09516075501
                </p>

                <p class="sm-head">
                  <span class="fa fa-envelope"></span>
                  Email
                </p>
                <p>
                  hadogchess@gmail.com <br>
                  payanedsel@gmail.com
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
    </div>
    </div>
    <script src="landing/vendors/jquery/jquery-3.2.1.min.js"></script>
    <script src="landing/vendors/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="landing/vendors/skrollr.min.js"></script>
    <script src="landing/vendors/owl-carousel/owl.carousel.min.js"></script>
    <script src="landing/vendors/nice-select/jquery.nice-select.min.js"></script>
    <script src="landing/vendors/jquery.ajaxchimp.min.js"></script>
    <script src="landing/vendors/mail-script.js"></script>
    <script src="landing/js/main.js"></script>
    <script>
      $(document).ready(function() {
        // Initialize the product slider using Owl Carousel
        $('#productSlider').owlCarousel({
          loop: true,
          margin: 10,
          nav: true,
          responsive: {
            0: {
              items: 1
            },
            600: {
              items: 2
            },
            1000: {
              items: 4
            }
          }
        });
        $("#productSlider").owlCarousel({
          items: 1, // Show only one image at a time
          loop: true, // Enable looping
          margin: 10, // Margin between items
          autoplay: true, // Enable autoplay
          autoplayTimeout: 3000, // Duration for each image (3 seconds)
          autoplaySpeed: 1000, // Speed of transition effect (1 second)
          autoplayHoverPause: false, // Continue autoplay even when hovered
          nav: true, // Display navigation arrows
          dots: true // Show pagination dots
        });
        $("#bestSellerCarousel").owlCarousel({
          items: 1, // Only display one item at a time
          loop: true, // Enable infinite loop
          margin: 30, // Margin between items
          autoplay: true, // Enable automatic slide transition
          autoplayTimeout: 3000, // Time interval for auto slide transition (in ms)
          autoplaySpeed: 1000, // Speed of slide transition (in ms)
          autoplayHoverPause: true, // Pause the slide when hovered
          nav: true, // Show navigation arrows
          dots: true // Show pagination dots
        });

        $("#heroProductSlider").owlCarousel({
          items: 1, // Show one image at a time
          loop: true, // Enable infinite looping
          autoplay: true, // Enable automatic slide transition
          autoplayTimeout: 3000, // 3 seconds per slide
          autoplaySpeed: 1000, // Transition speed between slides
          nav: true, // Show navigation arrows
          dots: true // Show pagination dots
        });
      });
    </script>
</body>

</html>