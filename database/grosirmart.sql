-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 30, 2025 at 10:35 AM
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
-- Database: `grosirmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(30) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `parent_id`, `created_at`) VALUES
(1, 'Makanan', 'fas fa-utensils', NULL, '2025-11-29 05:47:49'),
(2, 'Minuman', 'fas fa-glass-whiskey', NULL, '2025-11-29 05:47:49'),
(3, 'Sembako', 'fas fa-shopping-basket', NULL, '2025-11-29 05:47:49'),
(4, 'Makanan Ringan', 'fas fa-cookie', 1, '2025-11-29 05:47:49'),
(5, 'Makanan Berat', 'fas fa-drumstick-bite', 1, '2025-11-29 05:47:49'),
(6, 'Non Coffee', 'fas fa-mug-hot', 2, '2025-11-29 05:47:49'),
(7, 'Coffee', 'fas fa-coffee', 2, '2025-11-29 05:47:49'),
(8, 'Bahan Sembako', 'fas fa-shopping-basket', 3, '2025-11-30 06:29:31');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `category_id`, `image`, `barcode`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Indomie Goreng', '', 3500.00, 49, 4, '692bc4320bd33.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 06:42:24'),
(2, 'Teh Botol', '', 5000.00, 30, 6, '692bf40e98f0f.jpg', '', 1, '2025-11-29 05:47:57', '2025-11-30 07:36:46'),
(3, 'Roti Tawar', '', 7000.00, 45, 4, '692bc458ddee6.png', '', 1, '2025-11-29 05:47:57', '2025-11-30 04:13:12'),
(4, 'Pilus Garuda', '', 1000.00, 16, 4, '692bc4517657c.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 09:00:02'),
(5, 'OREO', '', 7000.00, 91, 4, '692bf41e8be06.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 09:00:02'),
(6, 'Nasi Goreng Special', '', 25000.00, 10, 5, '692bc43dabb85.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 06:42:05'),
(7, 'Mie Ayam Bakso', '', 20000.00, 10, 5, '692bc437ee059.png', '', 1, '2025-11-29 05:47:57', '2025-11-30 04:12:39'),
(8, 'Es Teh Manis', '', 8000.00, 38, 6, '692bc41b655e7.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 09:00:02'),
(9, 'Ultra Milk', '', 7000.00, 25, 6, '692bc4706807b.png', '', 1, '2025-11-29 05:47:57', '2025-11-30 04:13:36'),
(10, 'Espresso', '', 20000.00, 10, 7, '692bc429e48c6.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 06:42:34'),
(11, 'Cappuccino', '', 25000.00, 11, 7, '692bc41565a75.png', '', 1, '2025-11-29 05:47:57', '2025-12-30 06:42:24'),
(12, 'Beras Pandan Wangi 5kg', '', 65000.00, 1, 8, '692bc305960b4.jpg', '', 1, '2025-11-29 05:47:57', '2025-12-30 06:42:24'),
(13, 'Mie Kremez Shorr', '', 1000.00, 20, 4, '692bdccc81d67.png', '', 0, '2025-11-30 05:57:32', '2025-11-30 07:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `rental_products`
--

CREATE TABLE `rental_products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_products`
--

INSERT INTO `rental_products` (`id`, `name`, `description`, `price_per_hour`, `price_per_day`, `stock`, `image`, `is_active`, `created_at`) VALUES
(1, 'PS5 Standard', 'PlayStation 5 dengan 1 controller', 15000.00, 100000.00, 5, 'ps5.png', 1, '2025-12-23 01:48:55'),
(2, 'PS5 Pro Package', 'PS5 dengan 2 controller + games', 20000.00, 120000.00, 3, 'ps5_pro.png', 1, '2025-12-23 01:48:55'),
(3, 'PS4 Slim', 'PlayStation 4 Slim dengan 1 controller', 10000.00, 70000.00, 4, 'ps4.png', 1, '2025-12-23 01:48:55'),
(4, 'Nintendo Switch', 'Switch dengan Joy-Con', 12000.00, 80000.00, 2, 'switch.png', 1, '2025-12-23 01:48:55');

-- --------------------------------------------------------

--
-- Table structure for table `rental_transactions`
--

CREATE TABLE `rental_transactions` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(20) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(15) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rental_type` enum('per_jam','per_hari') NOT NULL,
  `quantity` int(11) NOT NULL,
  `rental_hours` int(11) DEFAULT NULL,
  `rental_days` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'store_name', 'GrosirMart', 'Nama Toko'),
(2, 'store_address', 'Jl. Contoh No. 123', 'Alamat Toko'),
(3, 'store_phone', '08123456789', 'Telepon Toko'),
(4, 'bank_name', 'BCA', 'Nama Bank'),
(5, 'bank_account', '1234567890', 'Nomor Rekening'),
(6, 'bank_holder', 'Grosir Mart', 'Pemilik Rekening');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('tunai','debit','qris') NOT NULL,
  `cash_amount` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `customer_id`, `user_id`, `subtotal`, `discount`, `total`, `payment_method`, `cash_amount`, `change_amount`, `status`, `notes`, `created_at`) VALUES
(1, 'INV-123051', NULL, NULL, 8000.00, 0.00, 8000.00, 'tunai', 8000.00, 0.00, 'completed', NULL, '2025-11-30 08:08:43'),
(2, 'INV-124800', NULL, NULL, 8000.00, 0.00, 8000.00, 'tunai', 8000.00, 0.00, 'completed', NULL, '2025-11-30 08:08:44'),
(3, 'INV-149947', NULL, NULL, 25000.00, 0.00, 25000.00, 'tunai', 25000.00, 0.00, 'completed', NULL, '2025-11-30 08:09:09'),
(4, 'INV-209545', NULL, NULL, 25000.00, 0.00, 25000.00, 'tunai', 25000.00, 0.00, 'completed', NULL, '2025-11-30 08:10:09'),
(5, 'INV-389978', NULL, NULL, 45000.00, 0.00, 45000.00, 'tunai', 45000.00, 0.00, 'completed', NULL, '2025-11-30 08:13:10'),
(6, 'INV-407695', NULL, NULL, 45000.00, 0.00, 45000.00, 'tunai', 45000.00, 0.00, 'completed', NULL, '2025-11-30 08:13:27'),
(9, 'INV-761438', NULL, NULL, 1000.00, 0.00, 1000.00, 'tunai', 1000.00, 0.00, 'completed', NULL, '2025-11-30 08:19:21'),
(10, 'INV-815721', NULL, NULL, 98000.00, 0.00, 98000.00, 'tunai', 98000.00, 0.00, 'completed', NULL, '2025-11-30 08:20:15'),
(11, 'INV-114996', NULL, NULL, 65000.00, 0.00, 65000.00, 'tunai', 65000.00, 0.00, 'completed', NULL, '2025-11-30 08:25:15'),
(12, 'INV-901081', NULL, NULL, 20000.00, 0.00, 20000.00, 'debit', 0.00, 0.00, 'completed', NULL, '2025-11-30 08:38:21'),
(13, 'INV-312164', NULL, NULL, 65000.00, 0.00, 65000.00, 'tunai', 65000.00, 0.00, 'completed', NULL, '2025-12-23 01:45:12'),
(14, 'INV-925523', NULL, NULL, 154000.00, 15400.00, 138600.00, 'tunai', 138600.00, 0.00, 'completed', NULL, '2025-12-30 06:42:05'),
(15, 'INV-944364', NULL, NULL, 281500.00, 28150.00, 253350.00, 'tunai', 253350.00, 0.00, 'completed', NULL, '2025-12-30 06:42:24'),
(16, 'INV-954061', NULL, NULL, 140000.00, 14000.00, 126000.00, 'tunai', 150000.00, 24000.00, 'completed', NULL, '2025-12-30 06:42:34'),
(17, 'INV-202354', NULL, NULL, 46000.00, 0.00, 46000.00, 'tunai', 46000.00, 0.00, 'completed', NULL, '2025-12-30 09:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 9, 4, 1, 1000.00, 1000.00),
(2, 10, 12, 1, 65000.00, 65000.00),
(3, 10, 11, 1, 25000.00, 25000.00),
(4, 10, 8, 1, 8000.00, 8000.00),
(5, 11, 12, 1, 65000.00, 65000.00),
(6, 12, 10, 1, 20000.00, 20000.00),
(7, 13, 12, 1, 65000.00, 65000.00),
(8, 14, 6, 5, 25000.00, 125000.00),
(9, 14, 5, 4, 7000.00, 28000.00),
(10, 14, 4, 1, 1000.00, 1000.00),
(11, 15, 11, 4, 25000.00, 100000.00),
(12, 15, 12, 2, 65000.00, 130000.00),
(13, 15, 8, 1, 8000.00, 8000.00),
(14, 15, 10, 2, 20000.00, 40000.00),
(15, 15, 1, 1, 3500.00, 3500.00),
(16, 16, 10, 7, 20000.00, 140000.00),
(17, 17, 5, 5, 7000.00, 35000.00),
(18, 17, 4, 3, 1000.00, 3000.00),
(19, 17, 8, 1, 8000.00, 8000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(80) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','kasir') DEFAULT 'kasir',
  `is_active` tinyint(1) DEFAULT 1,
  `avatar` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `is_active`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'Sugiri Satrio Wicaksono', 'sugiriakun1@gmail.com', '081250784272', '$2y$10$IZLc8tRzplNn3rZCqr3lMevnPOkUJPue25uqI6YwqhQgNHR7WEZDO', 'kasir', 1, NULL, '2025-11-29 06:16:35', '2025-11-29 06:16:35'),
(2, 'Administrator', 'admin@grosirmart.com', NULL, '$2y$10$2uyrOhF9hTSRDuvweUdopehGwNJ.Yf6w/YN1xieZ2G16AYys5XK9m', 'admin', 1, NULL, '2025-11-29 06:30:28', '2025-11-29 06:30:28'),
(3, 'Idhar Krisna Yudha', 'idharyudha@gmail.com', '081267891059', '$2y$10$uLlPc95QKEilyfFDi4u2qucV5Q5FpQfwe78TN7ZJlVvE6h5N6/Qgy', 'admin', 1, NULL, '2025-11-30 08:40:37', '2025-11-30 08:40:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `rental_products`
--
ALTER TABLE `rental_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rental_transactions`
--
ALTER TABLE `rental_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rental_products`
--
ALTER TABLE `rental_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rental_transactions`
--
ALTER TABLE `rental_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `rental_transactions`
--
ALTER TABLE `rental_transactions`
  ADD CONSTRAINT `rental_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `rental_products` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
