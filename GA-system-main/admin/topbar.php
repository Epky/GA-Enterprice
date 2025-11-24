<?php
include '../sql/sql.php';  // Ensure your database connection setup is included

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit();
}

// Get the admin ID from the session
$admin_id = $_SESSION['admin_id'];

// Fetch admin details
$qryAdmin = $conn->prepare("SELECT admin_name, admin_mname, admin_lname FROM tbl_admin_account WHERE admin_id = ?");
$qryAdmin->bind_param("i", $admin_id);
$qryAdmin->execute();
$adminData = $qryAdmin->get_result()->fetch_assoc();

// Query to fetch unread messages from tbl_contact_messages
$query = "SELECT * FROM tbl_contact_messages WHERE is_read = 0 ORDER BY date_submitted DESC";
$result = $conn->query($query);

// Query to get unread message count
$count_query = "SELECT COUNT(*) AS unread_count FROM tbl_contact_messages WHERE is_read = 0";
$count_result = $conn->query($count_query);
$unread_count = $count_result->fetch_assoc()['unread_count'];

// If "View All" is clicked, mark all messages as read
if (isset($_GET['view_all'])) {
    $update_query = "UPDATE tbl_contact_messages SET is_read = 1 WHERE is_read = 0";
    $conn->query($update_query);
    header("Location: list-messages.php");  // Redirect to list-messages.php page after marking as read
    exit;
}
?>

<div class="iq-top-navbar">
    <div class="iq-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="iq-navbar-logo d-flex align-items-center justify-content-between">
                <i class="ri-menu-line wrapper-menu"></i>
                <a href="../backend/index.html" class="header-logo">
                    <img src="../assets/images/gra.png" class="img-fluid rounded-normal" alt="logo">
                    <h5 class="logo-title ml-3"> G.A. Ruiz Enterprise </h5>
                </a>
            </div>
            <div class="iq-search-bar device-search">
            </div>
            <div class="d-flex align-items-center">
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-label="Toggle navigation">
                    <i class="ri-menu-3-line"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-list align-items-center">
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton2"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-mail">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span class="badge bg-primary"><?php echo $unread_count; ?></span>
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                <div class="card shadow-none m-0">
                                    <div class="card-body p-0">
                                        <div class="cust-title p-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h5 class="mb-0">All Messages</h5>
                                                <span class="badge badge-primary badge-card"><?php echo $unread_count; ?></span>
                                            </div>
                                        </div>
                                        <div class="px-3 pt-0 pb-0 sub-card">
                                            <?php while ($row = $result->fetch_assoc()) { ?>
                                                <a href="#" class="iq-sub-card">
                                                    <div class="media align-items-center cust-card py-3 border-bottom">
                                                        <div class="media-body ml-3">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <h6 class="mb-0"><?php echo $row['name']; ?></h6>
                                                                <small class="text-dark">
                                                                    <b><?php echo date("h:i A", strtotime($row['date_submitted'])); ?></b>
                                                                </small>
                                                            </div>
                                                            <small class="mb-0">
                                                                Email: <?php echo $row['email']; ?><br>
                                                                Number: <?php echo $row['number']; ?><br>
                                                                Message: <?php echo $row['message']; ?><br>
                                                                Date: <?php echo date("Y-m-d h:i A", strtotime($row['date_submitted'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php } ?>
                                        </div>
                                        <a class="right-ic btn btn-primary btn-block position-relative p-2" href="?view_all=1" role="button">
                                            View All
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item nav-icon dropdown caption-content">
                            <a href="#" class="search-toggle dropdown-toggle" id="dropdownMenuButton4"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="../assets/images/user/10.jpg" class="img-fluid rounded" alt="user">
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <div class="card shadow-none m-0">
                                    <div class="card-body p-0 text-center">
                                        <div class="media-body profile-detail text-center">
                                            <img src="../assets/images/gra.png" alt="profile-bg"
                                                class="rounded-top img-fluid mb-4">
                                            <img src="../assets/images/user/10.jpg" alt="profile-img"
                                                class="rounded profile-img img-fluid avatar-70">
                                        </div>
                                        <div class="p-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($adminData['admin_name'] . ' ' . $adminData['admin_mname'] . ' ' . $adminData['admin_lname']); ?></h5>
                                            <p class="mb-0">Admin</p>
                                            <div class="d-flex align-items-center justify-content-center mt-3">
                                                <a href="profile.php" class="btn border mr-2">Profile</a>
                                                <a href="../login-admin.php" class="btn border">Sign Out</a>
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