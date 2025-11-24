<?php
// Start session if not already started
include '../sql/sql.php';
error_reporting(0);

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get the customer ID from the session
$customer_id = $_SESSION['customer_id'];

// Assuming the first name, middle name, and last name are stored in the session after login
$firstName = $_SESSION['customer_name'] ?? '';

// Create a full name variable
$fullName = trim("$firstName");
?>

<div class="iq-top-navbar">
    <div class="iq-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="iq-navbar-logo d-flex align-items-center justify-content-between">
                <i class="ri-menu-line wrapper-menu"></i>
                <a href="dashboard.php" class="header-logo">
                    <img src="../assets/images/gra.png" class="img-fluid rounded-normal" alt="logo">
                    <h5 class="logo-title ml-3">G.A. Ruiz Enterprise </h5>
                </a>
            </div>
            <div class="iq-search-bar device-search"></div>
            <div class="d-flex align-items-center">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-label="Toggle navigation">
                    <i class="ri-menu-3-line"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-list align-items-center">
                        <!-- Cart Icon -->
                        <li class="nav-item nav-icon">
                            <a href="cart.php" class="nav-link" id="cartIcon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-shopping-cart">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <span class="cart-count">
                                    <?php echo isset($cart_items) ? $cart_items->num_rows : '0'; ?>
                                </span>
                            </a>
                        </li>

                        <!-- Notification Icon -->
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="dropdown-toggle" id="dropdownMenuButton" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-bell">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span class="badge bg-primary">0</span> <!-- Replace 3 with your notification count variable -->
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <!-- Dropdown Content -->
                                <div class="card shadow-none m-0">
                                    <div class="card-body p-0">
                                        <div class="cust-title p-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h5 class="mb-0">Notifications</h5>
                                                <span class="badge badge-primary badge-card">0</span> <!-- Replace 3 with dynamic count -->
                                            </div>
                                        </div>
                                        <div class="px-3 pt-0 pb-0 sub-card">
                                            <!-- Dynamic Notification Items -->
                                            <a href="#" class="iq-sub-card">
                                                <div class="media align-items-center cust-card py-3 border-bottom">
                                                    <div class="media-body ml-3">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <h6 class="mb-0">Order Update</h6>
                                                            <small class="text-dark">5 min ago</small>
                                                        </div>
                                                        <small class="mb-0">Your order is now preparing.</small>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <!-- Link to Check Order Status -->
                                        <a href="order_status.php" class="btn btn-primary btn-block position-relative p-2">
                                            Check Order
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>


                        <li class="nav-item nav-icon dropdown caption-content">
                            <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton4" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="../assets/images/user/1.png" class="img-fluid rounded" alt="user">
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <div class="card shadow-none m-0">
                                    <div class="card-body p-0 text-center">
                                        <div class="media-body profile-detail text-center">
                                            <img src="../assets/images/gra.png" alt="profile-bg"
                                                class="rounded-top img-fluid mb-4">
                                            <img src="../assets/images/user/1.png" alt="profile-img"
                                                class="rounded profile-img img-fluid avatar-70">
                                        </div>
                                        <div class="p-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($firstName); ?></h5>
                                            <!--p class="mb-0">Since 10 March, 2020</p-->
                                            <div class="d-flex align-items-center justify-content-center mt-3">
                                                <a href="profile.php" class="btn border mr-2">Profile</a>
                                                <a href="../logout.php" class="btn border">Logout</a>
                                            </div>
                                        </div>





                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>