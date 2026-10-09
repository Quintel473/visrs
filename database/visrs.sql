-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 12:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `visrs`
--

-- --------------------------------------------------------

--
-- Table structure for table `accidents`
--

CREATE TABLE `accidents` (
  `AccidentID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `AccidentDate` date NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `DamageLevel` enum('Minor','Moderate','Major','Severe') NOT NULL DEFAULT 'Minor',
  `ReportNumber` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(255) NOT NULL,
  `TableAffected` varchar(100) DEFAULT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`LogID`, `UserID`, `Action`, `TableAffected`, `RecordID`, `IPAddress`, `CreatedAt`) VALUES
(22, 1, 'Deleted vehicle', 'vehicles', 1, '::1', '2026-09-14 14:41:42'),
(23, 1, 'Added vehicle', 'vehicles', 2, '::1', '2026-09-14 14:42:54'),
(24, 1, 'Added historical ownership record for vehicle P 1234', 'ownership_history', 6, '::1', '2026-09-14 14:43:40'),
(25, 1, 'Added vehicle', 'vehicles', 3, '::1', '2026-09-14 14:49:38'),
(26, 1, 'Added historical ownership record for vehicle 7XYZ890', 'ownership_history', 7, '::1', '2026-09-14 14:51:00'),
(27, 1, 'Deleted ownership record for vehicle 7XYZ890', 'ownership_history', 7, '::1', '2026-09-14 14:52:23'),
(28, 1, 'Deleted ownership record for vehicle P 1234', 'ownership_history', 6, '::1', '2026-09-14 14:52:27'),
(29, 1, 'Deleted vehicle', 'vehicles', 3, '::1', '2026-09-14 14:52:41'),
(30, 1, 'Deleted vehicle', 'vehicles', 2, '::1', '2026-09-14 14:52:45'),
(31, 1, 'Deleted owner', 'owners', 3, '::1', '2026-09-14 14:52:58'),
(32, 1, 'Deleted owner', 'owners', 1, '::1', '2026-09-14 14:53:01'),
(33, 1, 'Added owner', 'owners', 4, '::1', '2026-09-14 14:53:55'),
(34, 1, 'Added vehicle', 'vehicles', 4, '::1', '2026-09-14 14:54:48'),
(35, 1, 'Added historical ownership record for vehicle 7XYZ890', 'ownership_history', 8, '::1', '2026-09-14 14:55:36'),
(36, 1, 'Added owner', 'owners', 5, '::1', '2026-09-14 14:57:02'),
(37, 1, 'Updated ownership record for vehicle 7XYZ890', 'ownership_history', 8, '::1', '2026-09-14 14:59:42'),
(38, 1, 'Updated ownership record for vehicle 7XYZ890', 'ownership_history', 8, '::1', '2026-09-14 15:01:19'),
(39, 1, 'Updated ownership record for vehicle 7XYZ890', 'ownership_history', 8, '::1', '2026-09-14 15:02:32'),
(40, 1, 'Added current ownership record for vehicle 7XYZ890', 'ownership_history', 9, '::1', '2026-09-14 15:05:14'),
(41, 1, 'Added insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:21:58'),
(42, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:41:36'),
(43, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:42:24'),
(44, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:54:31'),
(45, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:54:35'),
(46, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:55:03'),
(47, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:56:00'),
(48, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 15:56:24'),
(49, 1, 'Updated insurance policy GIS-2025-004821 for vehicle 7XYZ890', 'insurance', 1, '::1', '2026-09-14 16:01:03'),
(50, 1, 'Added accident record for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:20:52'),
(51, 1, 'Updated accident record #1 for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:25:06'),
(52, 1, 'Updated accident record #1 for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:25:58'),
(53, 1, 'Updated accident record #1 for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:26:58'),
(54, 2, 'Updated accident record #1 for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:28:14'),
(55, 2, 'Deleted accident record #1 for vehicle 7XYZ890', 'accidents', 1, '::1', '2026-09-14 22:41:33'),
(56, 3, 'Created vehicle marketplace listing', 'vehicle_listings', 1, '::1', '2026-09-15 03:24:19'),
(57, 3, 'Updated vehicle', 'vehicles', 4, '::1', '2026-09-15 03:30:42'),
(58, 5, 'Submitted vehicle interest request', 'vehicle_interests', 1, '::1', '2026-09-15 15:13:12'),
(59, 3, 'Added vehicle', 'vehicles', 5, '::1', '2026-09-15 16:10:35'),
(60, 3, 'Created vehicle marketplace listing', 'vehicle_listings', 2, '::1', '2026-09-15 16:37:33'),
(61, 3, 'Changed inquiry status from Pending to Contacted', 'vehicle_interests', 1, '::1', '2026-09-15 17:06:00'),
(62, 3, 'Added vehicle', 'vehicles', 6, '::1', '2026-09-15 22:25:13'),
(63, 1, 'Changed inquiry status from Contacted to Accepted', 'vehicle_interests', 1, '::1', '2026-09-18 00:04:57'),
(64, 1, 'Changed inquiry status from Accepted to Pending', 'vehicle_interests', 1, '::1', '2026-09-18 00:05:01'),
(65, 1, 'Changed inquiry status from Pending to Declined', 'vehicle_interests', 1, '::1', '2026-09-18 00:05:05'),
(66, 1, 'Changed inquiry status from Declined to Closed', 'vehicle_interests', 1, '::1', '2026-09-18 00:05:08'),
(67, 1, 'Changed inquiry status from Closed to Pending', 'vehicle_interests', 1, '::1', '2026-09-18 00:05:11'),
(68, 1, 'Updated vehicle', 'vehicles', 5, '::1', '2026-09-27 17:36:04'),
(69, 1, 'Updated vehicle', 'vehicles', 6, '::1', '2026-10-08 23:21:54'),
(70, 1, 'Updated vehicle', 'vehicles', 6, '::1', '2026-10-08 23:23:33'),
(71, 1, 'Updated vehicle', 'vehicles', 6, '::1', '2026-10-08 23:24:29'),
(72, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:09:55'),
(73, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:10:06'),
(74, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:10:26'),
(75, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:14:08'),
(76, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:14:54'),
(77, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:14:55'),
(78, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:07'),
(79, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:13'),
(80, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:24'),
(81, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:33'),
(82, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:39'),
(83, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:15:49'),
(84, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:18:52'),
(85, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-09 18:19:52'),
(86, NULL, 'Requested password reset', 'users', 1, '::1', '2026-10-09 20:38:39'),
(87, NULL, 'Reset password via email link', 'users', 1, '::1', '2026-10-09 20:40:02');

-- --------------------------------------------------------

--
-- Table structure for table `insurance`
--

CREATE TABLE `insurance` (
  `InsuranceID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `ProviderName` varchar(150) NOT NULL,
  `PolicyNumber` varchar(100) NOT NULL,
  `CoverageType` varchar(100) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `ExpiryDate` date DEFAULT NULL,
  `Status` enum('Active','Expired','Cancelled') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance`
--

INSERT INTO `insurance` (`InsuranceID`, `VehicleID`, `ProviderName`, `PolicyNumber`, `CoverageType`, `StartDate`, `ExpiryDate`, `Status`, `CreatedAt`) VALUES
(1, 4, 'Grenada Insurance Services Ltd.', 'GIS-2025-004821', 'Comprehensive', '2025-10-22', '2026-09-14', 'Active', '2026-09-14 15:21:58');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Message` text NOT NULL,
  `Link` varchar(255) DEFAULT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `ReadAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `OwnerID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Phone` varchar(30) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`OwnerID`, `FirstName`, `LastName`, `Address`, `Phone`, `Email`, `CreatedAt`) VALUES
(4, 'Quintel', 'Charles', 'Dunfermline, St. Andrew', '+ 1 (473) 456-0954', 'quintelcharles@ccagrenada.com', '2026-09-14 14:53:55'),
(5, 'Nathaniel', 'Grey', 'River Road, St.George', '+1 (473) 440-3252', 'ngrey@sgu.edu', '2026-09-14 14:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `ownership_history`
--

CREATE TABLE `ownership_history` (
  `OwnershipID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `OwnerID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `TransferReason` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ownership_history`
--

INSERT INTO `ownership_history` (`OwnershipID`, `VehicleID`, `OwnerID`, `StartDate`, `EndDate`, `TransferReason`, `CreatedAt`) VALUES
(8, 4, 4, '2023-10-14', '2025-10-14', 'Sale of vehicle', '2026-09-14 14:55:36'),
(9, 4, 5, '2025-10-21', NULL, 'Bought', '2026-09-14 15:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `ResetID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TokenHash` varchar(255) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IPAddress` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`ResetID`, `UserID`, `TokenHash`, `ExpiresAt`, `UsedAt`, `CreatedAt`, `IPAddress`) VALUES
(1, 1, '08cbf5d2fb8bf740f44665143bb2ca35c86f05ff448e4ad822af8e863cb3486f', '2026-10-09 23:38:37', '2026-10-09 16:40:02', '2026-10-09 16:38:37', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Police','Seller','User') NOT NULL DEFAULT 'User',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `ProfileImage`, `Password`, `Role`, `CreatedAt`) VALUES
(1, 'System', 'Administrator', 'admin@visrs.local', 'users/uploads/avatars/avatar_1_3c754ad53eb46bb711c8c3ce499586bd.jpg', '$2y$10$Imtpoor58ONPuyhVuupt5.BwPeXwSitfhzqU26S.mxvr.q5gJkwcS', 'Admin', '2026-09-13 18:40:07'),
(2, 'Police', 'Officer', 'police@visrs.local', NULL, '$2y$10$PXbiSCo8AINeXkdmlJGVZOaN/c0GhMnBZFlTWUrooI1U0vdlyhbvq', 'Police', '2026-09-13 18:49:06'),
(3, 'Vehicle', 'Seller', 'seller@visrs.local', NULL, '$2y$10$FiccudFMePKxu0Zl4fDbcuDb76h0OW3sCuz2pGEHRBWwUC2kMRgrm', 'Seller', '2026-09-13 18:49:06'),
(5, 'Michael', 'Brown', 'michael.brown@example.com', NULL, '$2y$10$FOyZgcOrSI5qZCEWUuHoBuDBCFh9mnhTjeGkoZDX9ek2fac7Dg4mS', 'User', '2026-09-15 00:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `VehicleID` int(11) NOT NULL,
  `PlateNumber` varchar(20) NOT NULL,
  `VIN` varchar(50) NOT NULL,
  `OwnerID` int(11) NOT NULL,
  `SellerID` int(11) DEFAULT NULL,
  `Make` varchar(100) NOT NULL,
  `Model` varchar(100) NOT NULL,
  `VehicleYear` year(4) NOT NULL,
  `Mileage` int(10) UNSIGNED DEFAULT NULL,
  `LastServiceDate` date DEFAULT NULL,
  `OilChangeIntervalKm` int(10) UNSIGNED DEFAULT NULL,
  `LastOilChangeDate` date DEFAULT NULL,
  `LastOilChangeMileage` int(10) UNSIGNED DEFAULT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `VehicleType` varchar(50) DEFAULT NULL,
  `EngineNumber` varchar(100) DEFAULT NULL,
  `RegistrationDate` date DEFAULT NULL,
  `Status` enum('Active','Inactive','Stolen','Sold') NOT NULL DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`VehicleID`, `PlateNumber`, `VIN`, `OwnerID`, `SellerID`, `Make`, `Model`, `VehicleYear`, `Mileage`, `LastServiceDate`, `OilChangeIntervalKm`, `LastOilChangeDate`, `LastOilChangeMileage`, `Color`, `VehicleType`, `EngineNumber`, `RegistrationDate`, `Status`, `CreatedAt`) VALUES
(4, '7XYZ890', '4T1B11HK5LU123456', 5, NULL, 'Toyota', 'Camry', '2022', NULL, NULL, NULL, NULL, NULL, 'White', 'Car', 'ENG2021TOY45821', '2023-10-14', 'Active', '2026-09-14 14:54:48'),
(5, 'P8492', '1NXBU4EE2HZ12749', 4, 3, 'Toyota', 'Corolla', '2026', 24679, '2025-06-27', 5000, '2026-06-08', 15500, 'Midnight Black Metallic', 'Car', 'M20A-FKS-094832', '2026-02-18', 'Active', '2026-09-15 16:10:35'),
(6, 'GND-4827', '1HGCM82633A004352', 5, 3, 'Mitsubishi', 'Outlander', '2021', 25000, '2025-02-08', 7000, '2026-09-08', 20000, 'Silver', 'SUV', '2ZRFE123456', '2021-03-15', 'Active', '2026-09-15 22:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `DocumentID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `UploadedBy` int(11) DEFAULT NULL,
  `DocType` varchar(50) NOT NULL,
  `OriginalName` varchar(255) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `FilePath` varchar(255) NOT NULL,
  `FileSize` int(11) NOT NULL,
  `MimeType` varchar(100) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_images`
--

CREATE TABLE `vehicle_images` (
  `ImageID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `ImagePath` varchar(255) NOT NULL,
  `IsPrimary` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_images`
--

INSERT INTO `vehicle_images` (`ImageID`, `VehicleID`, `ImagePath`, `IsPrimary`, `CreatedAt`) VALUES
(1, 4, 'marketplace/uploads/vehicle_4_22d26f90d926ae4be349e0c6f23b416a.jpg', 1, '2026-09-15 03:24:19'),
(2, 4, 'marketplace/uploads/vehicle_4_5221249d9b576d333b946aa257a885ec.jpg', 0, '2026-09-15 03:24:19'),
(3, 4, 'marketplace/uploads/vehicle_4_a238e5e4d0a8488660c5f5ab9e0be4ba.jpg', 0, '2026-09-15 03:24:19'),
(4, 5, 'marketplace/uploads/vehicle_5_12c50b98ab33ded70ed1ac922ff02eb9.jpg', 1, '2026-09-15 16:37:33'),
(5, 5, 'marketplace/uploads/vehicle_5_668efca1d6975c6a6ce4124ff8445a9b.jpg', 0, '2026-09-15 16:37:33'),
(6, 5, 'marketplace/uploads/vehicle_5_0e04599bf1354bfbc82b8824625061f5.jpg', 0, '2026-09-15 16:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_interests`
--

CREATE TABLE `vehicle_interests` (
  `InterestID` int(11) NOT NULL,
  `ListingID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `BuyerID` int(11) NOT NULL,
  `SellerID` int(11) NOT NULL,
  `Message` text DEFAULT NULL,
  `Status` enum('Pending','Contacted','Accepted','Declined','Closed') NOT NULL DEFAULT 'Pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_interests`
--

INSERT INTO `vehicle_interests` (`InterestID`, `ListingID`, `VehicleID`, `BuyerID`, `SellerID`, `Message`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 1, 4, 5, 3, 'Hello, I would like to know if this vehicle is still available for sale', 'Pending', '2026-09-15 15:13:12', '2026-09-18 00:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_listings`
--

CREATE TABLE `vehicle_listings` (
  `ListingID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `SellerID` int(11) NOT NULL,
  `Price` decimal(12,2) NOT NULL,
  `Description` text DEFAULT NULL,
  `Status` enum('Available','Reserved','Sold','Inactive') NOT NULL DEFAULT 'Available',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_listings`
--

INSERT INTO `vehicle_listings` (`ListingID`, `VehicleID`, `SellerID`, `Price`, `Description`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 4, 3, 25000.00, NULL, 'Available', '2026-09-15 03:24:19', '2026-09-15 03:24:19'),
(2, 5, 3, 21999.99, NULL, 'Available', '2026-09-15 16:37:33', '2026-09-15 16:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_messages`
--

CREATE TABLE `vehicle_messages` (
  `MessageID` int(11) NOT NULL,
  `InterestID` int(11) NOT NULL,
  `ListingID` int(11) NOT NULL,
  `VehicleID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  `RecipientID` int(11) NOT NULL,
  `MessageText` text NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReadAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accidents`
--
ALTER TABLE `accidents`
  ADD PRIMARY KEY (`AccidentID`),
  ADD KEY `VehicleID` (`VehicleID`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `insurance`
--
ALTER TABLE `insurance`
  ADD PRIMARY KEY (`InsuranceID`),
  ADD KEY `VehicleID` (`VehicleID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `idx_user_unread` (`UserID`,`IsRead`),
  ADD KEY `idx_created` (`CreatedAt`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`OwnerID`);

--
-- Indexes for table `ownership_history`
--
ALTER TABLE `ownership_history`
  ADD PRIMARY KEY (`OwnershipID`),
  ADD KEY `VehicleID` (`VehicleID`),
  ADD KEY `OwnerID` (`OwnerID`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`ResetID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_token` (`TokenHash`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`VehicleID`),
  ADD UNIQUE KEY `PlateNumber` (`PlateNumber`),
  ADD UNIQUE KEY `VIN` (`VIN`),
  ADD KEY `OwnerID` (`OwnerID`);

--
-- Indexes for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD PRIMARY KEY (`DocumentID`),
  ADD KEY `UploadedBy` (`UploadedBy`),
  ADD KEY `idx_vehicle` (`VehicleID`),
  ADD KEY `idx_type` (`DocType`);

--
-- Indexes for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `idx_image_vehicle` (`VehicleID`),
  ADD KEY `idx_image_primary` (`IsPrimary`);

--
-- Indexes for table `vehicle_interests`
--
ALTER TABLE `vehicle_interests`
  ADD PRIMARY KEY (`InterestID`),
  ADD UNIQUE KEY `unique_buyer_listing` (`ListingID`,`BuyerID`),
  ADD KEY `idx_interest_listing` (`ListingID`),
  ADD KEY `idx_interest_vehicle` (`VehicleID`),
  ADD KEY `idx_interest_buyer` (`BuyerID`),
  ADD KEY `idx_interest_seller` (`SellerID`),
  ADD KEY `idx_interest_status` (`Status`);

--
-- Indexes for table `vehicle_listings`
--
ALTER TABLE `vehicle_listings`
  ADD PRIMARY KEY (`ListingID`),
  ADD UNIQUE KEY `unique_vehicle_listing` (`VehicleID`),
  ADD KEY `idx_listing_seller` (`SellerID`),
  ADD KEY `idx_listing_status` (`Status`),
  ADD KEY `idx_listing_price` (`Price`);

--
-- Indexes for table `vehicle_messages`
--
ALTER TABLE `vehicle_messages`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `idx_message_interest` (`InterestID`),
  ADD KEY `idx_message_listing` (`ListingID`),
  ADD KEY `idx_message_vehicle` (`VehicleID`),
  ADD KEY `idx_message_sender` (`SenderID`),
  ADD KEY `idx_message_recipient` (`RecipientID`),
  ADD KEY `idx_message_created` (`CreatedAt`),
  ADD KEY `idx_message_unread` (`RecipientID`,`IsRead`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accidents`
--
ALTER TABLE `accidents`
  MODIFY `AccidentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `insurance`
--
ALTER TABLE `insurance`
  MODIFY `InsuranceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `OwnerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ownership_history`
--
ALTER TABLE `ownership_history`
  MODIFY `OwnershipID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `VehicleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `DocumentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicle_interests`
--
ALTER TABLE `vehicle_interests`
  MODIFY `InterestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_listings`
--
ALTER TABLE `vehicle_listings`
  MODIFY `ListingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vehicle_messages`
--
ALTER TABLE `vehicle_messages`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accidents`
--
ALTER TABLE `accidents`
  ADD CONSTRAINT `accidents_ibfk_1` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `insurance`
--
ALTER TABLE `insurance`
  ADD CONSTRAINT `insurance_ibfk_1` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `ownership_history`
--
ALTER TABLE `ownership_history`
  ADD CONSTRAINT `ownership_history_ibfk_1` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`),
  ADD CONSTRAINT `ownership_history_ibfk_2` FOREIGN KEY (`OwnerID`) REFERENCES `owners` (`OwnerID`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`OwnerID`) REFERENCES `owners` (`OwnerID`);

--
-- Constraints for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `vehicle_documents_ibfk_1` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_documents_ibfk_2` FOREIGN KEY (`UploadedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD CONSTRAINT `fk_image_vehicle` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_interests`
--
ALTER TABLE `vehicle_interests`
  ADD CONSTRAINT `fk_interest_buyer` FOREIGN KEY (`BuyerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_interest_listing` FOREIGN KEY (`ListingID`) REFERENCES `vehicle_listings` (`ListingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_interest_seller` FOREIGN KEY (`SellerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_interest_vehicle` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_listings`
--
ALTER TABLE `vehicle_listings`
  ADD CONSTRAINT `fk_listing_seller` FOREIGN KEY (`SellerID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_listing_vehicle` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_messages`
--
ALTER TABLE `vehicle_messages`
  ADD CONSTRAINT `fk_message_interest` FOREIGN KEY (`InterestID`) REFERENCES `vehicle_interests` (`InterestID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_listing` FOREIGN KEY (`ListingID`) REFERENCES `vehicle_listings` (`ListingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_recipient` FOREIGN KEY (`RecipientID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`SenderID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_vehicle` FOREIGN KEY (`VehicleID`) REFERENCES `vehicles` (`VehicleID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
