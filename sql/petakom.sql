-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 01:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `petakom`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Bread'),
(2, 'Drinks'),
(3, 'Biscuits'),
(5, 'Chips'),
(6, 'Toiletries'),
(8, 'Ramen');

-- --------------------------------------------------------

--
-- Table structure for table `instore`
--

CREATE TABLE `instore` (
  `id` int(11) NOT NULL,
  `orderID` varchar(255) NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `amountPaid` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `payMethod` enum('cash','online') NOT NULL,
  `transactionDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instore`
--

INSERT INTO `instore` (`id`, `orderID`, `totalAmount`, `amountPaid`, `balance`, `payMethod`, `transactionDate`) VALUES
(1, 'ORD6832505e82ef9', 1.40, 2.00, 0.60, 'cash', '2025-05-25 07:03:58');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `itemID` int(11) NOT NULL,
  `itemName` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `availableStock` int(11) DEFAULT NULL,
  `sellingPrice` decimal(10,2) NOT NULL,
  `costPrice` decimal(10,2) NOT NULL,
  `expiryDate` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`itemID`, `itemName`, `image`, `availableStock`, `sellingPrice`, `costPrice`, `expiryDate`, `barcode`, `category_id`) VALUES
(1, 'Gardenia Coklat', 'uploads/1748120079_roti coklat.png', 7, 1.20, 1.00, '23/5/2027', '123456789', 1),
(2, 'Crysanthemum Tea', 'uploads/1748125601_crysanthemum.png', 3, 1.40, 1.20, '22/1/2026', '9556570501375', 2),
(7, 'Malkist Biscuits', 'uploads/1748125869_malkist.png', 6, 4.50, 5.00, '13/1/2026', '8996001302637', 3),
(8, 'Mr. Potato Tomato', 'uploads/1748127618_mr potato.png', 8, 4.50, 5.00, '25/5/2027', '7532159684', 5),
(10, 'Ramen Carbonara', 'uploads/1748127757_ramen carbo.png', 3, 7.00, 6.00, '7/5/2027', '78631459', 8);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `orderID` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `orderDetails` text NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `orderStatus` enum('pending','completed','cancelled') DEFAULT 'pending',
  `payStatus` enum('paid','unpaid') DEFAULT 'unpaid',
  `orderDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `orderID`, `email`, `name`, `orderDetails`, `totalAmount`, `orderStatus`, `payStatus`, `orderDate`) VALUES
(1, 'ORD6832505e82ef9', NULL, '', '[{\"name\":\"Crysanthemum Tea\",\"price\":1.4,\"quantity\":1}]', 1.40, 'completed', 'paid', '2025-05-25 01:03:58'),
(2, 'ORD683250aa55e11', 'dayana@gmail.com', 'Dayana', '[{\"id\":1,\"name\":\"Gardenia Coklat\",\"price\":1.2,\"quantity\":1,\"image\":\"1748120079_roti coklat.png\"},{\"id\":2,\"name\":\"Crysanthemum Tea\",\"price\":1.4,\"quantity\":1,\"image\":\"1748125601_crysanthemum.png\"}]', 2.60, 'completed', 'paid', '2025-05-25 01:05:14'),
(3, 'ORD6832513b005a8', 'dayana@gmail.com', 'Dayana', '[{\"id\":1,\"name\":\"Gardenia Coklat\",\"price\":1.2,\"quantity\":1,\"image\":\"1748120079_roti coklat.png\"}]', 1.20, 'completed', 'paid', '2025-05-25 01:07:39'),
(4, 'ORD6832517505881', 'dayana@gmail.com', 'Dayana', '[{\"id\":1,\"name\":\"Gardenia Coklat\",\"price\":1.2,\"quantity\":1,\"image\":\"1748120079_roti coklat.png\"}]', 1.20, 'completed', 'paid', '2025-05-25 01:08:37');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `email`, `amount`, `status`, `created_at`) VALUES
(1, 'dayana@gmail.com', 5.00, 'completed', '2025-05-25 05:24:19'),
(2, 'dayana@gmail.com', 5.00, 'completed', '2025-05-25 07:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','user') NOT NULL,
  `wallet_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `password`, `role`, `wallet_balance`) VALUES
(1, 'inasyada16@gmail.com', 'Amrina', '$2y$10$gy7ToxT.47Gk30UQ.ESUWuYobSquwMZznSQJBhiBhzyzoacbj7hrG', 'staff', 0.00),
(2, 'dayana@gmail.com', 'Dayana', '$2y$10$fnPJvY4C5qG9AUkaxN4z1e1pqhKx6mNyeLEeyF0V4AXU4C54cCbO2', 'user', 5.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instore`
--
ALTER TABLE `instore`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orderID` (`orderID`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`itemID`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orderID` (`orderID`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `instore`
--
ALTER TABLE `instore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `instore`
--
ALTER TABLE `instore`
  ADD CONSTRAINT `orderID` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `email` FOREIGN KEY (`email`) REFERENCES `users` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
