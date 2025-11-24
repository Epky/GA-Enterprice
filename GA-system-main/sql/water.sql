-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2024 at 03:49 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `water`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin_account`
--

CREATE TABLE `tbl_admin_account` (
  `admin_id` int(255) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_mname` varchar(255) NOT NULL,
  `admin_lname` varchar(255) NOT NULL,
  `admin_username` varchar(255) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `date_register` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_admin_account`
--

INSERT INTO `tbl_admin_account` (`admin_id`, `admin_name`, `admin_mname`, `admin_lname`, `admin_username`, `admin_password`, `date_register`) VALUES
(1, 'a', 'a', 'a', 'asx', 'asx', '2024-10-08 01:21:54'),
(2, 'sdc', 'sdc', 'sdc', 'sdc', 'sdc', '2024-10-06 02:26:36');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contact_messages`
--

CREATE TABLE `tbl_contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `number` varchar(15) NOT NULL,
  `message` text NOT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_contact_messages`
--

INSERT INTO `tbl_contact_messages` (`id`, `name`, `email`, `number`, `message`, `date_submitted`, `is_read`) VALUES
(1, 'vdffvddfv', 'fvddfvdfv@gmail.com', 'sdc', 'cds', '2024-10-06 04:14:01', 1),
(2, 'gbf', 'fgb@gmail.com', 'fgb', 'fgb', '2024-10-06 04:17:14', 1),
(3, 'gbffgbgb', 'ffgbgb@gmail.com', 'dfv', 'fgb', '2024-10-06 04:18:19', 1),
(4, 'sdc', 'csddcssdc@gmail.com', 'bffgb', 'bgffgb', '2024-10-06 04:19:52', 1),
(5, 'vdf', 'vdfvdfdfv@gmail.com', 'vfddfv', 'vfdfvd', '2024-10-06 04:22:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_account`
--

CREATE TABLE `tbl_customer_account` (
  `customerid` int(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_number` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_address_1` varchar(255) NOT NULL,
  `customer_address_2` varchar(255) NOT NULL,
  `customer_city` varchar(255) NOT NULL,
  `customer_municipality` varchar(255) NOT NULL,
  `customer_zipcode` varchar(255) NOT NULL,
  `customer_password` varchar(255) NOT NULL,
  `online_offline_status` varchar(255) NOT NULL,
  `date_register` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_customer_account`
--

INSERT INTO `tbl_customer_account` (`customerid`, `customer_name`, `customer_number`, `customer_email`, `customer_address_1`, `customer_address_2`, `customer_city`, `customer_municipality`, `customer_zipcode`, `customer_password`, `online_offline_status`, `date_register`) VALUES
(1, 'final test', '3', 'a@gmail.com', 'a', 'a', 'scdsdc', 'a', '123', 'a', '1', '2024-10-08 00:30:37'),
(2, 'dfvv', '12', 'qqq@gmail.com', 'qqq@gmail.com', 'qqq@gmail.com', 'qqq@gmail.com', 'qqq@gmail.com', '23', 'sss', '', '2024-10-08 01:48:35');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order`
--

CREATE TABLE `tbl_order` (
  `order_id` int(255) NOT NULL,
  `customerid` int(255) NOT NULL,
  `item_quantity` varchar(255) NOT NULL,
  `order_status` varchar(255) NOT NULL DEFAULT 'Wait for Confirmation',
  `product_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
--
-- Table structure for table `inventory_log`
--

CREATE TABLE IF NOT EXISTS `inventory_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `change_type` ENUM('add', 'remove') NOT NULL,
  `quantity` INT(11) NOT NULL,
  `date_added` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
);

--
-- Table structure for table `tbl_orders`
--

CREATE TABLE `tbl_orders` (
  `orders_id` int(255) NOT NULL,
  `customerid` int(255) NOT NULL,
  `orders_date` datetime NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `orders_status` varchar(255) NOT NULL,
  `delivery_id` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_id` varchar(255) NOT NULL,
  `item_quantity` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_orders`
--

INSERT INTO `tbl_orders` (`orders_id`, `customerid`, `orders_date`, `shipping_address`, `total_amount`, `orders_status`, `delivery_id`, `payment_status`, `tracking_number`, `created_at`, `updated_at`, `order_id`, `item_quantity`, `product_id`) VALUES
(24, 1, '2024-10-05 13:32:31', 'csdcsdcdsdcs, scdsdc, scdsdc, scdsdc, 3434', '344.00', 'Delivered', 'Walk in', 'Paid', 'QW7RE8Q6US', '2024-10-06 01:11:09', '2024-10-06 01:11:09', '22', '1', '4'),
(25, 1, '2024-10-06 09:11:33', 'a, a, scdsdc, a, 3434', '344.00', 'Delivered', 'Cash On Delivery', 'Paid', 'N2RPAJUUZL', '2024-10-06 01:13:33', '2024-10-06 01:13:33', '23', '1', '4'),
(26, 1, '2024-10-06 09:15:51', 'a, a, scdsdc, a, 3434', '344.00', 'Delivered', 'Walk in', 'Paid', '62ZGW9V73T', '2024-10-06 01:16:20', '2024-10-06 01:16:20', '24', '1', '4'),
(27, 1, '2024-10-06 09:18:18', 'a, a, scdsdc, a, 3434', '1032.00', 'Delivered', 'Cash On Delivery', 'Paid', 'OB2NI8NWL9', '2024-10-06 01:34:12', '2024-10-06 01:34:12', '25', '1', '4'),
(28, 1, '2024-10-06 09:18:18', 'a, a, scdsdc, a, 3434', '1032.00', 'Delivered', 'Cash On Delivery', 'Paid', 'OB2NI8NWL9', '2024-10-06 01:34:18', '2024-10-06 01:34:18', '26', '2', '4'),
(29, 1, '2024-10-06 09:20:39', 'a, a, scdsdc, a, 3434', '2408.00', 'Delivered', 'Cash On Delivery', 'Paid', 'TEWLQSYBGL', '2024-10-06 01:31:57', '2024-10-06 01:31:57', '27', '2', '4'),
(30, 1, '2024-10-06 09:20:39', 'a, a, scdsdc, a, 3434', '2408.00', 'Delivered', 'Cash On Delivery', 'Paid', 'TEWLQSYBGL', '2024-10-06 01:32:14', '2024-10-06 01:32:14', '28', '5', '4'),
(31, 1, '2024-10-06 09:25:42', 'a, a, scdsdc, a, 3434', '1032.00', 'Delivered', 'Cash On Delivery', 'Paid', 'OK2YU91TTT', '2024-10-06 01:31:36', '2024-10-06 01:31:36', '29', '3', '4'),
(32, 1, '2024-10-06 09:25:42', 'a, a, scdsdc, a, 3434', '688.00', 'Delivered', 'Cash On Delivery', 'Paid', 'OK2YU91TTT', '2024-10-06 01:31:44', '2024-10-06 01:31:44', '30', '2', '4'),
(33, 1, '2024-10-08 08:28:06', 'a, a, scdsdc, a, 123', '344.00', 'Delivered', 'Cash On Delivery', 'Paid', 'TAN9C9M9ZU', '2024-10-08 00:29:19', '2024-10-08 00:29:19', '31', '1', '4');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `product_id` int(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` varchar(255) NOT NULL,
  `product_description` varchar(255) NOT NULL,
  `product_stocks` varchar(255) NOT NULL,
  `product_image_1` varchar(255) NOT NULL,
  `product_image_2` varchar(255) NOT NULL,
  `product_image_3` varchar(255) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`product_id`, `product_name`, `product_price`, `product_description`, `product_stocks`, `product_image_1`, `product_image_2`, `product_image_3`, `date_added`) VALUES
(4, 'dfv', '344', 'fvdvdf', '7', '457224954_1077651690732077_980592246847182442_n.jpg', '458484490_1027516842034784_2399830904221486150_n.jpg', '458230276_531635972715753_7233608857506093000_n.jpg', '2024-10-08 00:27:27'),
(5, 'tubig', '100', 'cds', '0', 'qr_2024-00855 (1).png', '436724324_396608316133140_8946683016816256863_n.jpg', 'd6e26112-b1fa-461e-88e7-3b62322a23c3-removebg-preview.png', '2024-10-05 04:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_type_delivery`
--

CREATE TABLE `tbl_type_delivery` (
  `delivery_id` int(255) NOT NULL,
  `delivery_type` varchar(255) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_type_delivery`
--

INSERT INTO `tbl_type_delivery` (`delivery_id`, `delivery_type`, `date_added`) VALUES
(1, 'Cash On Delivery', '2024-10-05 01:07:28'),
(2, 'Walk in', '2024-10-05 01:07:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_admin_account`
--
ALTER TABLE `tbl_admin_account`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `tbl_contact_messages`
--
ALTER TABLE `tbl_contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_customer_account`
--
ALTER TABLE `tbl_customer_account`
  ADD PRIMARY KEY (`customerid`);

--
-- Indexes for table `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD PRIMARY KEY (`orders_id`),
  ADD KEY `customer_id` (`customerid`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `tbl_type_delivery`
--
ALTER TABLE `tbl_type_delivery`
  ADD PRIMARY KEY (`delivery_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_admin_account`
--
ALTER TABLE `tbl_admin_account`
  MODIFY `admin_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_contact_messages`
--
ALTER TABLE `tbl_contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_customer_account`
--
ALTER TABLE `tbl_customer_account`
  MODIFY `customerid` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_order`
--
ALTER TABLE `tbl_order`
  MODIFY `order_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  MODIFY `orders_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `product_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_type_delivery`
--
ALTER TABLE `tbl_type_delivery`
  MODIFY `delivery_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`customerid`) REFERENCES `tbl_customer_account` (`customerid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
