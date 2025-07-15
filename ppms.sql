-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2025 at 02:17 PM
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
-- Database: `ppms`
--

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notificationID` int(11) NOT NULL,
  `ICNumber` varchar(20) NOT NULL,
  `TrackingNumber` varchar(50) DEFAULT NULL,
  `notificationType` varchar(50) DEFAULT NULL,
  `messageContent` text DEFAULT NULL,
  `sentTimestamp` datetime DEFAULT NULL,
  `notificationStatus` varchar(50) DEFAULT NULL,
  `isRead` tinyint(1) DEFAULT 0,
  `deliveryMethod` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notificationID`, `ICNumber`, `TrackingNumber`, `notificationType`, `messageContent`, `sentTimestamp`, `notificationStatus`, `isRead`, `deliveryMethod`) VALUES
(8, '444444444444', 'GRT34534641', 'arrival', 'New parcel arrived! Your package (Tracking No: GRT34534641) is ready for pickup at TDI.', '2025-06-21 05:59:31', 'sent', 1, 'system'),
(9, '444444444444', 'TRR512566', 'arrival', 'New parcel arrived! Your package (Tracking No: TRR512566) is ready for pickup at HJH sukimah.', '2025-06-21 17:43:49', 'sent', 1, 'system'),
(10, '444444444444', 'TRR512566', 'pickup', 'Parcel collected! Your package (Tracking No: TRR512566) has been successfully retrieved. Thank you for using our service!', '2025-06-21 17:44:56', 'sent', 1, 'system'),
(11, '444444444444', 'MY098928221', 'arrival', 'New parcel arrived! Your package (Tracking No: MY098928221) is ready for pickup at T2-23A-01.', '2025-06-21 18:05:23', 'sent', 1, 'system'),
(18, '444444444444', 'MY098928221', 'pickup', 'Parcel collected! Your package (Tracking No: MY098928221) has been successfully retrieved. Thank you for using our service!', '2025-06-22 04:57:37', 'sent', 1, 'system'),
(19, '444444444444', 'GRT34534641', 'qr_email', 'QR verification code emailed to: iskandardzulqarnain0104@gmail.com', '2025-06-22 05:09:13', 'sent', 1, 'email'),
(20, '444444444444', 'GRT34534641', 'pickup', 'Parcel collected! Your package (Tracking No: GRT34534641) has been successfully retrieved. Thank you for using our service!', '2025-06-22 05:09:31', 'sent', 1, 'system'),
(25, '010401150099', 'JNT76572175', 'arrival', 'New parcel arrived! Your package (Tracking No: JNT76572175) is ready for pickup at Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim.', '2025-06-24 05:58:21', 'sent', 1, 'system'),
(26, '010401150099', 'JNT00987241', 'arrival', 'New parcel arrived! Your package (Tracking No: JNT00987241) is ready for pickup at Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim.', '2025-06-25 22:23:38', 'sent', 1, 'system'),
(27, '010401150099', 'SPX1234567', 'arrival', 'New parcel arrived! Your package (Tracking No: SPX1234567) is ready for pickup at Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim.', '2025-06-25 22:26:39', 'sent', 1, 'system'),
(28, '010401150099', 'SPX1234567', 'pickup', 'Parcel collected! Your package (Tracking No: SPX1234567) has been successfully retrieved. Thank you for using our service!', '2025-06-25 22:28:13', 'sent', 1, 'system');

-- --------------------------------------------------------

--
-- Table structure for table `parcel`
--

CREATE TABLE `parcel` (
  `TrackingNumber` varchar(50) NOT NULL,
  `ICNumber` varchar(20) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `deliveryLocation` varchar(100) DEFAULT NULL,
  `QR` text DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel`
--

INSERT INTO `parcel` (`TrackingNumber`, `ICNumber`, `date`, `time`, `status`, `name`, `deliveryLocation`, `QR`, `weight`) VALUES
('GRT34534641', '444444444444', '2025-06-21', '05:59:31', 'Retrieved', 'parcel a bit wet', 'TDI', NULL, 1.20),
('JNT00987241', '010401150099', '2025-06-25', '22:23:38', 'Pending', 'sila claim parcel anda segera...', 'Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim', NULL, 2.62),
('JNT133546', '444444444444', '2025-06-21', '05:13:30', 'Retrieved', 'ikan masin ke ni', 'TSN', NULL, 4.00),
('JNT76572175', '010401150099', '2025-06-24', '05:58:21', 'Pending', 'Package', 'Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim', NULL, 3.45),
('MY098928221', '444444444444', '2025-06-21', '18:05:23', 'Retrieved', 'Package', 'T2-23A-01', NULL, 12.04),
('MY123123', '444444444444', '2025-06-21', '04:24:22', 'Retrieved', 'parcel berat sikit', 'TDI', NULL, 6.40),
('SPX0987644', '444444444444', '2025-06-21', '05:26:48', 'Retrieved', 'ini bomb ke apa?', 'TSN', NULL, 51.02),
('SPX1234567', '010401150099', '2025-06-25', '22:26:39', 'Retrieved', 'sila claim parcel anda segera...', 'Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor Darul Ta\'zim', NULL, 4.60),
('TRR512566', '444444444444', '2025-06-21', '17:43:49', 'Retrieved', 'berat sikit hehe', 'HJH sukimah', NULL, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_type` enum('staff','receiver') NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_type`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 'receiver', '010401150099', '7a6797570e73a2b64cb92940a82d6665aea7a144c7f528ddf2e6f81d7c3f56f5', '2025-06-25 20:44:00', 0, '2025-06-25 17:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `receiver`
--

CREATE TABLE `receiver` (
  `ICNumber` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `notificationID` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receiver`
--

INSERT INTO `receiver` (`ICNumber`, `name`, `phoneNumber`, `password`, `notificationID`) VALUES
('010401150077', 'Isz', '+601115895851', '$2y$10$5SRZtXcmTaULpVcyiJWhSOiJ2Pcr7/1/X.Mlv.u16bGGiJU2xv.Qu', NULL),
('010401150099', 'Mikail', '+601115895859', '$2y$10$7fChdst60v9OCRZrhd1svu92fYjJYZpmPWchFvN9fg6jDt16RSCQC', NULL),
('444444444444', 'Madara', '0193122525', '$2y$10$m/IpHxiOJgpZUtVV.x1oIuVGxD78CHcOVM5bQE1Cit/LiStwd/IrO', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `retrievalrecord`
--

CREATE TABLE `retrievalrecord` (
  `RetrievalID` int(11) NOT NULL,
  `trackingNumber` varchar(50) NOT NULL,
  `ICNumber` varchar(20) NOT NULL,
  `staffID` varchar(20) DEFAULT NULL,
  `retrieveDate` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `retrieveTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retrievalrecord`
--

INSERT INTO `retrievalrecord` (`RetrievalID`, `trackingNumber`, `ICNumber`, `staffID`, `retrieveDate`, `status`, `retrieveTime`) VALUES
(1, 'MY123123', '444444444444', NULL, '2025-06-21', 'Retrieved', '05:20:34'),
(2, 'JNT133546', '444444444444', NULL, '2025-06-21', 'Retrieved', '05:20:31'),
(3, 'SPX0987644', '444444444444', NULL, '2025-06-21', 'Retrieved', '05:29:17'),
(5, 'TRR512566', '444444444444', NULL, '2025-06-21', 'Retrieved', '17:44:56'),
(6, 'MY098928221', '444444444444', NULL, '2025-06-22', 'Retrieved', '04:57:37'),
(7, 'GRT34534641', '444444444444', NULL, '2025-06-22', 'Retrieved', '05:09:31'),
(9, 'SPX1234567', '010401150099', NULL, '2025-06-25', 'Retrieved', '22:28:13');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffID` varchar(20) NOT NULL,
  `role` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffID`, `role`, `name`, `phoneNumber`, `password`) VALUES
('0105', 'Staff', 'Soloz Alfonso', '', '$2y$10$/ukobobdmfETZeru60hW3O9LvLho2RIGlge3eZP73lwvzJsjbigc6');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `ICNumber` (`ICNumber`),
  ADD KEY `TrackingNumber` (`TrackingNumber`);

--
-- Indexes for table `parcel`
--
ALTER TABLE `parcel`
  ADD PRIMARY KEY (`TrackingNumber`),
  ADD KEY `ICNumber` (`ICNumber`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_type_id` (`user_type`,`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `receiver`
--
ALTER TABLE `receiver`
  ADD PRIMARY KEY (`ICNumber`);

--
-- Indexes for table `retrievalrecord`
--
ALTER TABLE `retrievalrecord`
  ADD PRIMARY KEY (`RetrievalID`),
  ADD KEY `trackingNumber` (`trackingNumber`),
  ADD KEY `ICNumber` (`ICNumber`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `retrievalrecord`
--
ALTER TABLE `retrievalrecord`
  MODIFY `RetrievalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`ICNumber`) REFERENCES `receiver` (`ICNumber`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`TrackingNumber`) REFERENCES `parcel` (`TrackingNumber`);

--
-- Constraints for table `parcel`
--
ALTER TABLE `parcel`
  ADD CONSTRAINT `parcel_ibfk_1` FOREIGN KEY (`ICNumber`) REFERENCES `receiver` (`ICNumber`);

--
-- Constraints for table `retrievalrecord`
--
ALTER TABLE `retrievalrecord`
  ADD CONSTRAINT `retrievalrecord_ibfk_1` FOREIGN KEY (`trackingNumber`) REFERENCES `parcel` (`TrackingNumber`),
  ADD CONSTRAINT `retrievalrecord_ibfk_2` FOREIGN KEY (`ICNumber`) REFERENCES `receiver` (`ICNumber`),
  ADD CONSTRAINT `retrievalrecord_ibfk_3` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
