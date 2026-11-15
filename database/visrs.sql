-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 09:42 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

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

--
-- Dumping data for table `accidents`
--

INSERT INTO `accidents` (`AccidentID`, `VehicleID`, `AccidentDate`, `Location`, `Description`, `DamageLevel`, `ReportNumber`, `CreatedAt`) VALUES
(2, 6, '2026-09-16', 'Grand Anse, St. George', 'Vehicle 1 was traveling northbound at approximately 25 mph through an intersection on a green light when Vehicle 2, traveling southbound, attempted a sudden left turn without yielding the right-of-way. Despite Vehicle 1 immediately applying the brakes, a broadside collision occurred, causing severe front-end damage to Vehicle 1 and extensive passenger-side damage to Vehicle 2. The impact deployed airbags in both vehicles, requiring both to be towed from the scene, while the driver of Vehicle 1 was transported to the hospital for evaluation of leg pain.', 'Moderate', '1102356', '2026-10-12 20:26:43'),
(3, 4, '2026-09-28', 'St. George\'s, near the Carenage', 'Vehicle reported stolen; later recovered with front-end damage and broken driver-side window.', 'Major', '1102401', '2026-10-14 13:10:00'),
(4, 5, '2026-10-02', 'Grand Anse Main Road', 'Minor fender-bender at low speed while reversing out of a parking space.', 'Minor', '1102415', '2026-10-14 13:20:00'),
(5, 7, '2026-10-03', 'St. Paul\'s, St. George', 'Side-swipe collision at a roundabout; both vehicles sustained scratched panels.', 'Minor', '1102422', '2026-10-14 13:30:00'),
(6, 8, '2026-10-04', 'Grenville, St. Andrew', 'Pickup truck rear-ended a stopped car at a pedestrian crossing; no injuries.', 'Moderate', '1102431', '2026-10-14 13:40:00'),
(7, 9, '2026-10-06', 'L\'Anse aux Epines, St. George', 'Vehicle hit a pothole and lost control, striking a utility pole.', 'Moderate', '1102440', '2026-10-14 13:50:00'),
(8, 10, '2026-10-08', 'Morne Rouge, St. George', 'Rear-end collision at traffic lights; bumper and trunk damage.', 'Minor', '1102455', '2026-10-14 14:00:00'),
(9, 11, '2026-10-09', 'Sauteurs, St. Patrick', 'Head-on collision on a blind curve; airbags deployed, both vehicles towed.', 'Severe', '1102466', '2026-10-14 14:10:00'),
(10, 13, '2026-10-10', 'True Blue, St. George', 'Vehicle struck a stray animal at night; significant front-end damage.', 'Major', '1102477', '2026-10-14 14:20:00'),
(11, 16, '2026-10-13', 'St. George\'s, Kirani James Boulevard', 'Motorcycle skidded on wet road and fell; rider treated for minor injuries.', 'Moderate', '1102488', '2026-10-14 14:30:00'),
(12, 17, '2026-10-01', 'St. George\'s, Maurice Bishop Highway', 'Rear-end collision at a roundabout during heavy rain; bumper and tail-light damage.', 'Minor', '1102501', '2026-10-14 15:00:00'),
(13, 18, '2026-10-02', 'Grand Anse, St. George', 'Vehicle clipped a parked car while reversing out of a driveway.', 'Minor', '1102512', '2026-10-14 15:10:00'),
(14, 19, '2026-10-04', 'St. Paul\'s, St. George', 'Sideswipe during lane change on a two-lane road.', 'Minor', '1102523', '2026-10-14 15:20:00'),
(15, 20, '2026-10-05', 'Grenville, St. Andrew', 'Brake failure on a downhill stretch; vehicle struck a low wall.', 'Moderate', '1102534', '2026-10-14 15:30:00'),
(16, 21, '2026-10-07', 'True Blue, St. George', 'T-bone collision at an uncontrolled intersection; driver-side door caved in.', 'Moderate', '1102545', '2026-10-14 15:40:00'),
(17, 22, '2026-10-08', 'St. David\'s, St. George', 'Pickup rolled onto its side after swerving to avoid a pothole.', 'Major', '1102556', '2026-10-14 15:50:00'),
(18, 23, '2026-10-09', 'Calliste, St. George', 'Rear-ended a bus stopped at a bus stop; significant front-end damage.', 'Major', '1102567', '2026-10-14 16:00:00'),
(19, 24, '2026-10-10', 'Morne Rouge, St. George', 'Multi-vehicle pile-up during fog; SUV sandwiched between two cars.', 'Severe', '1102578', '2026-10-14 16:10:00'),
(20, 25, '2026-10-11', 'St. George\'s, The Carenage', 'Vehicle reported stolen; later recovered abandoned with broken windows.', 'Moderate', '1102589', '2026-10-14 16:20:00'),
(21, 26, '2026-10-12', 'Woburn, St. George', 'SUV struck a utility pole after avoiding a stray dog; front-end crushed.', 'Severe', '1102590', '2026-10-14 16:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `accident_photos`
--

CREATE TABLE `accident_photos` (
  `PhotoID` int(10) UNSIGNED NOT NULL,
  `AccidentID` int(10) UNSIGNED NOT NULL,
  `UploadedBy` int(10) UNSIGNED NOT NULL,
  `OriginalName` varchar(255) NOT NULL,
  `StoredName` varchar(255) NOT NULL,
  `MimeType` varchar(100) NOT NULL,
  `FileSize` int(10) UNSIGNED NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(87, NULL, 'Reset password via email link', 'users', 1, '::1', '2026-10-09 20:40:02'),
(88, 1, 'Updated vehicle', 'vehicles', 5, '::1', '2026-10-10 23:56:10'),
(89, 1, 'Updated vehicle', 'vehicles', 4, '::1', '2026-10-10 23:56:43'),
(90, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-10 23:58:28'),
(91, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-10 23:59:01'),
(92, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-10 23:59:41'),
(93, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-10 23:59:45'),
(94, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-11 00:00:41'),
(95, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:13:22'),
(96, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:13:28'),
(97, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:13:31'),
(98, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:13:51'),
(99, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:14:06'),
(100, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:15:56'),
(101, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:16:09'),
(102, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:16:38'),
(103, 1, 'Updated own profile information', 'users', 1, '::1', '2026-10-11 00:20:26'),
(104, 1, 'Enabled two-factor authentication', 'users', 1, '::1', '2026-10-11 01:12:12'),
(105, 1, 'Regenerated 2FA recovery codes', 'users', 1, '::1', '2026-10-11 01:22:38'),
(106, 1, 'Disabled two-factor authentication', 'users', 1, '::1', '2026-10-11 01:23:37'),
(107, 1, 'Updated vehicle', 'vehicles', 4, '::1', '2026-10-11 17:50:04'),
(108, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-12 19:58:50'),
(109, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-12 19:59:35'),
(110, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-12 19:59:39'),
(111, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-12 19:59:44'),
(112, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-12 19:59:48'),
(113, 1, 'Added accident record for vehicle GND-4827', 'accidents', 2, '::1', '2026-10-12 20:26:43'),
(114, 1, 'Added current ownership record for vehicle GND-4827', 'ownership_history', 10, '::1', '2026-10-12 20:29:16'),
(115, 1, 'Updated accident record #2 for vehicle GND-4827', 'accidents', 2, '::1', '2026-10-12 20:31:49'),
(116, 1, 'Exported accident records to PDF (1 rows)', 'accidents', 0, '::1', '2026-10-12 20:42:01'),
(117, 1, 'Exported accident records to CSV (1 rows)', 'accidents', 0, '::1', '2026-10-12 20:42:55'),
(118, 1, 'Exported accident records to CSV (1 rows)', 'accidents', 0, '::1', '2026-10-12 20:43:01'),
(119, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-15 01:00:55'),
(120, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-15 01:00:58'),
(121, 1, 'Enabled two-factor authentication', 'users', 1, '::1', '2026-10-18 02:06:08'),
(122, 1, 'Updated profile picture', 'users', 1, '::1', '2026-10-18 02:43:59'),
(123, 1, 'Removed profile picture', 'users', 1, '::1', '2026-10-18 03:06:26'),
(124, 1, 'Disabled two-factor authentication', 'users', 1, '::1', '2026-10-21 15:53:39'),
(125, 1, 'Exported accident records to PDF (1 rows)', 'accidents', 0, '::1', '2026-10-21 15:55:06'),
(126, 1, 'Exported accident records to PDF (1 rows)', 'accidents', 0, '::1', '2026-10-21 15:55:20'),
(127, 1, 'Exported accident records to PDF (1 rows)', 'accidents', 0, '::1', '2026-10-21 15:55:31'),
(128, 1, 'Added owner', 'owners', 6, '::1', '2026-10-01 13:12:30'),
(129, 1, 'Added owner', 'owners', 7, '::1', '2026-10-02 14:45:30'),
(130, 1, 'Added owner', 'owners', 8, '::1', '2026-10-03 18:20:30'),
(131, 1, 'Added owner', 'owners', 9, '::1', '2026-10-05 12:30:30'),
(132, 1, 'Added owner', 'owners', 10, '::1', '2026-10-07 20:05:30'),
(133, 1, 'Added owner', 'owners', 11, '::1', '2026-10-09 15:40:30'),
(134, 1, 'Added owner', 'owners', 12, '::1', '2026-09-20 13:00:30'),
(135, 1, 'Added owner', 'owners', 13, '::1', '2026-09-22 14:15:30'),
(136, 1, 'Added owner', 'owners', 14, '::1', '2026-09-24 15:30:30'),
(137, 1, 'Added owner', 'owners', 15, '::1', '2026-09-26 17:45:30'),
(138, 1, 'Added owner', 'owners', 16, '::1', '2026-09-28 19:00:30'),
(139, 1, 'Added owner', 'owners', 17, '::1', '2026-09-30 20:30:30'),
(140, 1, 'Added owner', 'owners', 18, '::1', '2026-10-02 12:45:30'),
(141, 1, 'Added owner', 'owners', 19, '::1', '2026-10-04 14:00:30'),
(142, 1, 'Added owner', 'owners', 20, '::1', '2026-10-06 15:15:30'),
(143, 1, 'Added vehicle', 'vehicles', 7, '::1', '2026-10-01 13:17:30'),
(144, 1, 'Added vehicle', 'vehicles', 8, '::1', '2026-10-02 14:52:30'),
(145, 1, 'Added vehicle', 'vehicles', 9, '::1', '2026-10-03 18:27:30'),
(146, 1, 'Added vehicle', 'vehicles', 10, '::1', '2026-10-05 12:37:30'),
(147, 1, 'Added vehicle', 'vehicles', 11, '::1', '2026-10-07 20:12:30'),
(148, 1, 'Added vehicle', 'vehicles', 12, '::1', '2026-10-09 15:47:30'),
(149, 1, 'Added vehicle', 'vehicles', 13, '::1', '2026-10-10 12:02:30'),
(150, 1, 'Added vehicle', 'vehicles', 14, '::1', '2026-10-11 13:22:30'),
(151, 1, 'Added vehicle', 'vehicles', 15, '::1', '2026-10-12 19:57:30'),
(152, 1, 'Added vehicle', 'vehicles', 16, '::1', '2026-10-13 17:07:30'),
(153, 1, 'Added vehicle', 'vehicles', 17, '::1', '2026-09-20 13:11:30'),
(154, 1, 'Added vehicle', 'vehicles', 18, '::1', '2026-09-22 14:21:30'),
(155, 1, 'Added vehicle', 'vehicles', 19, '::1', '2026-09-24 15:36:30'),
(156, 1, 'Added vehicle', 'vehicles', 20, '::1', '2026-09-26 17:51:30'),
(157, 1, 'Added vehicle', 'vehicles', 21, '::1', '2026-09-28 19:06:30'),
(158, 1, 'Added vehicle', 'vehicles', 22, '::1', '2026-09-30 20:36:30'),
(159, 1, 'Added vehicle', 'vehicles', 23, '::1', '2026-10-02 12:51:30'),
(160, 1, 'Added vehicle', 'vehicles', 24, '::1', '2026-10-04 14:06:30'),
(161, 1, 'Added vehicle', 'vehicles', 25, '::1', '2026-10-06 15:21:30'),
(162, 1, 'Added vehicle', 'vehicles', 26, '::1', '2026-10-08 17:31:30'),
(163, 1, 'Added insurance policy for vehicle GND-1130', 'insurance', 4, '::1', '2026-10-05 12:16:00'),
(164, 1, 'Added insurance policy for vehicle GND-2277', 'insurance', 5, '::1', '2026-10-06 16:01:00'),
(165, 1, 'Added insurance policy for vehicle GND-3045', 'insurance', 6, '::1', '2026-10-07 13:46:00'),
(166, 1, 'Added insurance policy for vehicle GND-4412', 'insurance', 7, '::1', '2026-10-08 18:11:00'),
(167, 1, 'Added insurance policy for vehicle GND-5560', 'insurance', 8, '::1', '2026-10-09 14:21:00'),
(168, 1, 'Added insurance policy for vehicle GND-6621', 'insurance', 9, '::1', '2026-10-10 15:06:00'),
(169, 1, 'Added insurance policy for vehicle GND-8844', 'insurance', 10, '::1', '2026-10-11 13:31:00'),
(170, 1, 'Added insurance policy for vehicle GND-9951', 'insurance', 11, '::1', '2026-10-12 20:01:00'),
(171, 1, 'Added insurance policy for vehicle GND-1012', 'insurance', 12, '::1', '2026-10-13 17:16:00'),
(172, 1, 'Added insurance policy for vehicle GND-1131', 'insurance', 14, '::1', '2026-09-20 13:16:00'),
(173, 1, 'Added insurance policy for vehicle GND-2288', 'insurance', 15, '::1', '2026-09-22 14:26:00'),
(174, 1, 'Added insurance policy for vehicle GND-3399', 'insurance', 16, '::1', '2026-09-24 15:41:00'),
(175, 1, 'Added insurance policy for vehicle GND-4455', 'insurance', 17, '::1', '2026-09-26 17:56:00'),
(176, 1, 'Added insurance policy for vehicle GND-5511', 'insurance', 18, '::1', '2026-09-28 19:11:00'),
(177, 1, 'Added insurance policy for vehicle GND-6677', 'insurance', 19, '::1', '2026-09-30 20:41:00'),
(178, 1, 'Added insurance policy for vehicle GND-7788', 'insurance', 20, '::1', '2026-10-02 12:56:00'),
(179, 1, 'Added insurance policy for vehicle GND-8899', 'insurance', 21, '::1', '2026-10-04 14:11:00'),
(180, 1, 'Added insurance policy for vehicle GND-9911', 'insurance', 22, '::1', '2026-10-06 15:26:00'),
(181, 1, 'Added insurance policy for vehicle GND-1013', 'insurance', 23, '::1', '2026-10-08 17:36:00'),
(182, 1, 'Added accident record for vehicle GND-4827', 'accidents', 2, '::1', '2026-10-13 00:27:00'),
(183, 1, 'Added accident record for vehicle GND-1131', 'accidents', 12, '::1', '2026-10-14 15:00:30'),
(184, 1, 'Added accident record for vehicle GND-2288', 'accidents', 13, '::1', '2026-10-14 15:10:30'),
(185, 1, 'Added accident record for vehicle GND-3399', 'accidents', 14, '::1', '2026-10-14 15:20:30'),
(186, 1, 'Added accident record for vehicle GND-4455', 'accidents', 15, '::1', '2026-10-14 15:30:30'),
(187, 1, 'Added accident record for vehicle GND-5511', 'accidents', 16, '::1', '2026-10-14 15:40:30'),
(188, 1, 'Added accident record for vehicle GND-6677', 'accidents', 17, '::1', '2026-10-14 15:50:30'),
(189, 1, 'Added accident record for vehicle GND-7788', 'accidents', 18, '::1', '2026-10-14 16:00:30'),
(190, 1, 'Added accident record for vehicle GND-8899', 'accidents', 19, '::1', '2026-10-14 16:10:30'),
(191, 1, 'Added accident record for vehicle GND-9911', 'accidents', 20, '::1', '2026-10-14 16:20:30'),
(192, 1, 'Added accident record for vehicle GND-1013', 'accidents', 21, '::1', '2026-10-14 16:30:30'),
(193, 1, 'Removed vehicle listing', 'vehicle_listings', 1, '::1', '2026-11-15 18:38:13'),
(194, 1, 'Uploaded accident photo', 'accident_photos', 1, '::1', '2026-11-15 20:03:09'),
(195, 1, 'Deleted accident photo', 'accident_photos', 1, '::1', '2026-11-15 20:03:19'),
(196, 1, 'Updated profile picture', 'users', 1, '::1', '2026-11-15 20:03:33'),
(197, 1, 'Removed profile picture', 'users', 1, '::1', '2026-11-15 20:03:37'),
(198, 1, 'Uploaded accident photo', 'accident_photos', 2, '::1', '2026-11-15 20:07:03'),
(199, 1, 'Deleted accident photo', 'accident_photos', 2, '::1', '2026-11-15 20:07:07');

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
(1, 4, 'Grenada Insurance Services Ltd.', 'GIS-2025-004821', 'Comprehensive', '2025-10-22', '2026-09-14', 'Active', '2026-09-14 15:21:58'),
(2, 5, 'Grenada Insurance Services Ltd.', 'GIS-2026-005122', 'Comprehensive', '2026-03-01', '2027-02-28', 'Active', '2026-10-02 13:00:00'),
(3, 6, 'Grenada Insurance Services Ltd.', 'GIS-2026-005345', 'Third-Party, Fire & Theft', '2026-04-15', '2027-04-14', 'Active', '2026-10-04 14:30:00'),
(4, 7, 'Nationwide Insurance Agency', 'NIA-2026-110078', 'Comprehensive', '2026-01-10', '2027-01-09', 'Active', '2026-10-05 12:15:00'),
(5, 8, 'Nationwide Insurance Agency', 'NIA-2025-098442', 'Third-Party Only', '2025-05-20', '2026-05-19', 'Expired', '2026-10-06 16:00:00'),
(6, 9, 'Grenada Insurance Services Ltd.', 'GIS-2026-006001', 'Comprehensive', '2026-02-22', '2027-02-21', 'Active', '2026-10-07 13:45:00'),
(7, 10, 'Caribbean Alliance Insurance', 'CAI-2026-220045', 'Comprehensive', '2026-11-04', '2027-11-03', 'Active', '2026-10-08 18:10:00'),
(8, 11, 'Nationwide Insurance Agency', 'NIA-2026-112233', 'Third-Party, Fire & Theft', '2026-09-12', '2027-09-11', 'Active', '2026-10-09 14:20:00'),
(9, 12, 'Caribbean Alliance Insurance', 'CAI-2026-221190', 'Comprehensive', '2026-06-19', '2027-06-18', 'Active', '2026-10-10 15:05:00'),
(10, 14, 'Grenada Insurance Services Ltd.', 'GIS-2026-006777', 'Comprehensive', '2026-08-15', '2027-08-14', 'Active', '2026-10-11 13:30:00'),
(11, 15, 'Nationwide Insurance Agency', 'NIA-2025-077551', 'Third-Party Only', '2025-04-03', '2026-04-02', 'Expired', '2026-10-12 20:00:00'),
(12, 16, 'Caribbean Alliance Insurance', 'CAI-2026-222881', 'Third-Party Only', '2026-12-11', '2027-12-10', 'Active', '2026-10-13 17:15:00'),
(13, 8, 'Grenada Insurance Services Ltd.', 'GIS-2024-001902', 'Third-Party Only', '2024-05-20', '2025-05-19', 'Cancelled', '2026-10-14 12:50:00'),
(14, 17, 'Caribbean Alliance Insurance', 'CAI-2026-300117', 'Comprehensive', '2026-06-14', '2027-06-13', 'Active', '2026-09-20 13:15:00'),
(15, 18, 'Nationwide Insurance Agency', 'NIA-2026-300218', 'Third-Party, Fire & Theft', '2026-08-22', '2027-08-21', 'Active', '2026-09-22 14:25:00'),
(16, 19, 'Grenada Insurance Services Ltd.', 'GIS-2026-300319', 'Comprehensive', '2026-05-18', '2027-05-17', 'Active', '2026-09-24 15:40:00'),
(17, 20, 'Nationwide Insurance Agency', 'NIA-2025-300420', 'Third-Party Only', '2025-11-04', '2026-11-03', 'Active', '2026-09-26 17:55:00'),
(18, 21, 'Caribbean Alliance Insurance', 'CAI-2026-300521', 'Comprehensive', '2026-03-30', '2027-03-29', 'Active', '2026-09-28 19:10:00'),
(19, 22, 'Grenada Insurance Services Ltd.', 'GIS-2026-300622', 'Comprehensive', '2026-02-14', '2027-02-13', 'Active', '2026-09-30 20:40:00'),
(20, 23, 'Nationwide Insurance Agency', 'NIA-2026-300723', 'Third-Party, Fire & Theft', '2026-07-09', '2027-07-08', 'Active', '2026-10-02 12:55:00'),
(21, 24, 'Caribbean Alliance Insurance', 'CAI-2026-300824', 'Comprehensive', '2026-10-21', '2027-10-20', 'Active', '2026-10-04 14:10:00'),
(22, 25, 'Grenada Insurance Services Ltd.', 'GIS-2026-300925', 'Third-Party Only', '2026-09-12', '2027-09-11', 'Active', '2026-10-06 15:25:00'),
(23, 26, 'Nationwide Insurance Agency', 'NIA-2026-301026', 'Third-Party, Fire & Theft', '2026-12-02', '2027-12-01', 'Active', '2026-10-08 17:35:00'),
(24, 7, 'Caribbean Alliance Insurance', 'CAI-2024-200078', 'Third-Party Only', '2024-01-10', '2025-01-09', 'Expired', '2026-10-10 13:30:00'),
(25, 17, 'Grenada Insurance Services Ltd.', 'GIS-2024-100117', 'Third-Party Only', '2024-06-14', '2025-06-13', 'Cancelled', '2026-10-11 14:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `AttemptID` int(10) UNSIGNED NOT NULL,
  `Email` varchar(150) NOT NULL,
  `IPAddress` varchar(45) NOT NULL,
  `AttemptedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`AttemptID`, `Email`, `IPAddress`, `AttemptedAt`) VALUES
(1, 'andre.baptiste@example.com', '::1', '2026-11-15 11:03:09');

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
  `UserID` int(11) DEFAULT NULL,
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

INSERT INTO `owners` (`OwnerID`, `UserID`, `FirstName`, `LastName`, `Address`, `Phone`, `Email`, `CreatedAt`) VALUES
(4, 21, 'Quintel', 'Charles', 'Dunfermline, St. Andrew', '+ 1 (473) 456-0954', 'quintelcharles@ccagrenada.com', '2026-09-14 14:53:55'),
(5, 22, 'Nathaniel', 'Grey', 'River Road, St.George', '+1 (473) 440-3252', 'ngrey@sgu.edu', '2026-09-14 14:57:02'),
(6, 6, 'Alicia', 'Thomas', 'Calliste, St. George', '+1 (473) 415-7723', 'alicia.thomas@example.com', '2026-10-01 13:12:00'),
(7, 7, 'Devon', 'Mitchell', 'Grenville, St. Andrew', '+1 (473) 420-8814', 'devon.mitchell@example.com', '2026-10-02 14:45:00'),
(8, 8, 'Kerry-Ann', 'Joseph', 'Gouyave, St. John', '+1 (473) 435-3390', 'kerryann.joseph@example.com', '2026-10-03 18:20:00'),
(9, 9, 'Marcus', 'Roberts', 'Sauteurs, St. Patrick', '+1 (473) 442-5518', 'marcus.roberts@example.com', '2026-10-05 12:30:00'),
(10, 10, 'Simone', 'Francis', 'Hillsborough, Carriacou', '+1 (473) 443-9902', 'simone.francis@example.com', '2026-10-07 20:05:00'),
(11, 11, 'Andre', 'Baptiste', 'Victoria, St. Mark', '+1 (473) 444-2276', 'andre.baptiste@example.com', '2026-10-09 15:40:00'),
(12, 12, 'Patricia', 'Lewis', 'St. George\'s, Lucas Street', '+1 (473) 410-6601', 'patricia.lewis@example.com', '2026-09-20 13:00:00'),
(13, 13, 'Jamal', 'Charles', 'Grand Anse, St. George', '+1 (473) 418-4422', 'jamal.charles@example.com', '2026-09-22 14:15:00'),
(14, 14, 'Tessa', 'Williams', 'Beaulieu, St. George', '+1 (473) 421-9933', 'tessa.williams@example.com', '2026-09-24 15:30:00'),
(15, 15, 'Keron', 'Alexander', 'Tempe, St. George', '+1 (473) 429-7711', 'keron.alexander@example.com', '2026-09-26 17:45:00'),
(16, 16, 'Monique', 'Richardson', 'Woburn, St. George', '+1 (473) 433-8822', 'monique.richardson@example.com', '2026-09-28 19:00:00'),
(17, 17, 'Dwight', 'Edwards', 'Red Mud, St. Patrick', '+1 (473) 439-5544', 'dwight.edwards@example.com', '2026-09-30 20:30:00'),
(18, 18, 'Cherise', 'Moore', 'Victoria, St. Mark', '+1 (473) 446-6611', 'cherise.moore@example.com', '2026-10-02 12:45:00'),
(19, 19, 'Owen', 'Pierre', 'Hillsborough, Carriacou', '+1 (473) 449-3377', 'owen.pierre@example.com', '2026-10-04 14:00:00'),
(20, 20, 'Nadia', 'Joseph', 'Birchgrove, St. George', '+1 (473) 452-2288', 'nadia.joseph@example.com', '2026-10-06 15:15:00');

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
(9, 4, 5, '2025-10-21', NULL, 'Bought', '2026-09-14 15:05:14'),
(10, 6, 4, '2026-09-05', NULL, 'Bought', '2026-10-12 20:29:16'),
(11, 7, 6, '2020-07-10', NULL, 'Original purchase', '2026-10-01 13:16:00'),
(12, 8, 7, '2019-05-18', NULL, 'Original purchase', '2026-10-02 14:51:00'),
(13, 9, 8, '2021-02-22', '2024-11-30', 'Sale of vehicle', '2026-10-03 18:26:00'),
(14, 9, 6, '2024-12-01', NULL, 'Bought from previous owner', '2026-10-03 18:27:00'),
(15, 10, 9, '2022-11-04', NULL, 'Original purchase', '2026-10-05 12:36:00'),
(16, 11, 10, '2018-09-12', NULL, 'Original purchase', '2026-10-07 20:11:00'),
(17, 12, 11, '2021-06-19', NULL, 'Original purchase', '2026-10-09 15:46:00'),
(18, 13, 6, '2020-03-27', '2026-10-09', 'Sale of vehicle', '2026-10-10 12:01:00'),
(19, 15, 8, '2017-04-03', NULL, 'Original purchase', '2026-10-12 19:56:00'),
(20, 16, 9, '2019-12-11', NULL, 'Original purchase', '2026-10-13 17:06:00'),
(21, 17, 12, '2019-06-14', NULL, 'Original purchase', '2026-09-20 13:12:00'),
(22, 18, 12, '2018-08-22', '2023-10-01', 'Sale of vehicle', '2026-09-22 14:22:00'),
(23, 18, 13, '2023-10-02', NULL, 'Bought from previous owner', '2026-09-22 14:23:00'),
(24, 19, 14, '2020-05-18', NULL, 'Original purchase', '2026-09-24 15:37:00'),
(25, 20, 15, '2017-11-04', NULL, 'Original purchase', '2026-09-26 17:52:00'),
(26, 21, 16, '2019-03-30', NULL, 'Original purchase', '2026-09-28 19:07:00'),
(27, 22, 17, '2020-02-14', NULL, 'Original purchase', '2026-09-30 20:37:00'),
(28, 23, 18, '2017-07-09', NULL, 'Original purchase', '2026-10-02 12:52:00'),
(29, 24, 19, '2018-10-21', NULL, 'Original purchase', '2026-10-04 14:07:00'),
(30, 25, 20, '2016-09-12', NULL, 'Original purchase', '2026-10-06 15:22:00'),
(31, 26, 12, '2016-12-02', NULL, 'Original purchase', '2026-10-08 17:32:00'),
(32, 18, 13, '2023-10-02', '2026-10-01', 'Kept as second vehicle', '2026-10-09 13:00:00'),
(33, 18, 12, '2026-10-02', NULL, 'Bought back from family', '2026-10-09 13:01:00'),
(34, 23, 18, '2017-07-09', '2025-01-15', 'Temporary transfer', '2026-10-10 14:00:00');

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
-- Table structure for table `recovery_codes`
--

CREATE TABLE `recovery_codes` (
  `CodeID` int(10) UNSIGNED NOT NULL,
  `UserID` int(10) UNSIGNED NOT NULL,
  `CodeHash` char(64) NOT NULL,
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `TokenID` int(10) UNSIGNED NOT NULL,
  `UserID` int(11) NOT NULL,
  `TokenHash` char(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`TokenID`, `UserID`, `TokenHash`, `ExpiresAt`, `CreatedAt`) VALUES
(35, 1, '4e7cca29a677924257ee8dc0547070db08924d795f382d5a2ab847f811425c01', '2026-11-27 02:49:17', '2026-10-27 21:49:17'),
(41, 1, '4c77ecc100247bab665b4168841bd03a63429c85c83589d75c65a9f7a45f3762', '2026-12-09 15:30:34', '2026-11-09 10:30:34'),
(45, 1, '9ea3a8d81aa096a789f0a661f15dc5487547b034fd52850684cf4f0371abc217', '2026-12-15 21:16:06', '2026-11-15 16:16:06');

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
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `TwoFactorSecret` varchar(64) DEFAULT NULL,
  `TwoFactorEnabled` tinyint(1) NOT NULL DEFAULT 0,
  `TwoFactorConfirmedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Email`, `ProfileImage`, `Password`, `Role`, `CreatedAt`, `TwoFactorSecret`, `TwoFactorEnabled`, `TwoFactorConfirmedAt`) VALUES
(1, 'System', 'Administrator', 'admin@visrs.local', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Admin', '2026-09-13 18:40:07', NULL, 0, NULL),
(2, 'Police', 'Officer', 'police@visrs.local', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Police', '2026-09-13 18:49:06', NULL, 0, NULL),
(3, 'Vehicle', 'Seller', 'seller@visrs.local', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Seller', '2026-09-13 18:49:06', NULL, 0, NULL),
(5, 'Michael', 'Brown', 'michael.brown@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-15 00:37:34', NULL, 0, NULL),
(6, 'Alicia', 'Thomas', 'alicia.thomas@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-10-01 13:12:00', NULL, 0, NULL),
(7, 'Devon', 'Mitchell', 'devon.mitchell@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Seller', '2026-10-02 14:45:00', NULL, 0, NULL),
(8, 'Kerry-Ann', 'Joseph', 'kerryann.joseph@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Police', '2026-10-03 18:20:00', NULL, 0, NULL),
(9, 'Marcus', 'Roberts', 'marcus.roberts@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Police', '2026-10-05 12:30:00', NULL, 0, NULL),
(10, 'Simone', 'Francis', 'simone.francis@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Seller', '2026-10-07 20:05:00', NULL, 0, NULL),
(11, 'Andre', 'Baptiste', 'andre.baptiste@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-10-09 15:40:00', NULL, 0, NULL),
(12, 'Patricia', 'Lewis', 'patricia.lewis@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'Admin', '2026-09-20 13:00:00', NULL, 0, NULL),
(13, 'Jamal', 'Charles', 'jamal.charles@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-22 14:15:00', NULL, 0, NULL),
(14, 'Tessa', 'Williams', 'tessa.williams@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-24 15:30:00', NULL, 0, NULL),
(15, 'Keron', 'Alexander', 'keron.alexander@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-26 17:45:00', NULL, 0, NULL),
(16, 'Monique', 'Richardson', 'monique.richardson@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-28 19:00:00', NULL, 0, NULL),
(17, 'Dwight', 'Edwards', 'dwight.edwards@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-30 20:30:00', NULL, 0, NULL),
(18, 'Cherise', 'Moore', 'cherise.moore@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-10-02 12:45:00', NULL, 0, NULL),
(19, 'Owen', 'Pierre', 'owen.pierre@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-10-04 14:00:00', NULL, 0, NULL),
(20, 'Nadia', 'Joseph', 'nadia.joseph@example.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-10-06 15:15:00', NULL, 0, NULL),
(21, 'Quintel', 'Charles', 'quintelcharles@ccagrenada.com', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-14 18:53:55', NULL, 0, NULL),
(22, 'Nathaniel', 'Grey', 'ngrey@sgu.edu', NULL, '$2y$10$0o33QDbG6ZCkNzurkTRxGuCGuJLpns9LWAD51Z6PX/ueT6qi7wlvy', 'User', '2026-09-14 18:57:02', NULL, 0, NULL);

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
(4, '7XYZ890', '4T1B11HK5LU123456', 5, NULL, 'Toyota', 'Camry', '2022', NULL, NULL, NULL, NULL, NULL, 'White', 'Car', 'ENG2021TOY45821', '2023-10-14', 'Stolen', '2026-09-14 14:54:48'),
(5, 'P8492', '1NXBU4EE2HZ12749', 4, 3, 'Toyota', 'Corolla', '2026', 24679, '2025-06-27', 5000, '2026-06-08', 15500, 'Midnight Black Metallic', 'Car', 'M20A-FKS-094832', '2026-02-18', 'Sold', '2026-09-15 16:10:35'),
(6, 'GND-4827', '1HGCM82633A004352', 4, 3, 'Mitsubishi', 'Outlander', '2021', 25000, '2025-02-08', 7000, '2026-09-08', 20000, 'Silver', 'SUV', '2ZRFE123456', '2021-03-15', 'Active', '2026-09-15 22:25:13'),
(7, 'GND-1130', 'JTDBR32E720123401', 6, NULL, 'Honda', 'Civic', '2020', 41200, '2026-08-15', 5000, '2026-09-01', 40000, 'Blue', 'Car', 'R18A1-112233', '2020-07-10', 'Active', '2026-10-01 13:15:00'),
(8, 'GND-2277', '1FTFW1ET5DFC12345', 7, NULL, 'Ford', 'F-150', '2019', 78500, '2026-07-22', 8000, '2026-09-12', 77000, 'Black', 'Pickup', 'ECO-3.5-V6-4451', '2019-05-18', 'Active', '2026-10-02 14:50:00'),
(9, 'GND-3045', 'JN1AZ4EH7FM730112', 8, 3, 'Nissan', 'Altima', '2021', 33210, '2026-09-05', 6000, '2026-09-05', 33000, 'Grey', 'Car', 'QR25DE-778812', '2021-02-22', 'Active', '2026-10-03 18:25:00'),
(10, 'GND-4412', '5YJSA1E26HF000337', 9, NULL, 'Tesla', 'Model S', '2022', 18900, '2026-09-20', 20000, '2026-09-20', 18000, 'White', 'Car', 'EV-MOTOR-99321', '2022-11-04', 'Active', '2026-10-05 12:35:00'),
(11, 'GND-5560', '1C4RJFAG0CC123998', 10, 3, 'Jeep', 'Grand Cherokee', '2018', 95400, '2026-06-30', 7000, '2026-08-18', 94000, 'Red', 'SUV', 'PENTASTAR-5521', '2018-09-12', 'Active', '2026-10-07 20:10:00'),
(12, 'GND-6621', '3VW2K7AJ9FM123882', 11, NULL, 'Volkswagen', 'Jetta', '2021', 27800, '2026-08-08', 6000, '2026-09-28', 27000, 'Silver', 'Car', 'EA888-883421', '2021-06-19', 'Active', '2026-10-09 15:45:00'),
(13, 'GND-7733', '1HGCV1F34LA123004', 6, 3, 'Honda', 'Accord', '2020', 52100, '2026-07-11', 6000, '2026-09-14', 51000, 'Dark Blue', 'Car', 'L15B7-220145', '2020-03-27', 'Sold', '2026-10-10 12:00:00'),
(14, 'GND-8844', '4T1G11AK8MU123551', 7, NULL, 'Toyota', 'Camry', '2021', 36500, '2026-09-01', 5000, '2026-09-01', 36000, 'Pearl White', 'Car', 'A25A-FKS-33412', '2021-08-15', 'Active', '2026-10-11 13:20:00'),
(15, 'GND-9951', '1FADP3F29HL123907', 8, NULL, 'Ford', 'Focus', '2017', 118700, '2026-05-19', 7000, '2026-09-09', 117000, 'Blue', 'Car', '2.0-Duratec-7712', '2017-04-03', 'Inactive', '2026-10-12 19:55:00'),
(16, 'GND-1012', 'JYARN23E9HA001277', 9, NULL, 'Yamaha', 'YZF-R3', '2019', 14300, '2026-08-25', 4000, '2026-09-22', 14000, 'Matte Black', 'Motorcycle', 'YZF-R3-320-8811', '2019-12-11', 'Active', '2026-10-13 17:05:00'),
(17, 'GND-1131', 'WBA8E9G50GNT12301', 12, NULL, 'BMW', '328i', '2019', 62400, '2026-08-10', 7000, '2026-09-18', 61000, 'Black Sapphire', 'Car', 'N20B20-441002', '2019-06-14', 'Active', '2026-09-20 13:10:00'),
(18, 'GND-2288', '1G1ZD5ST8JF123402', 13, 3, 'Chevrolet', 'Malibu', '2018', 88200, '2026-07-05', 6000, '2026-09-25', 87000, 'Grey', 'Car', 'LFV-2.0-115733', '2018-08-22', 'Active', '2026-09-22 14:20:00'),
(19, 'GND-3399', '4S3BNAN64H3123403', 14, NULL, 'Subaru', 'Legacy', '2020', 47500, '2026-09-12', 6000, '2026-09-12', 47000, 'White', 'Car', 'FB25-330041', '2020-05-18', 'Active', '2026-09-24 15:35:00'),
(20, 'GND-4455', '5NPE24AF5FH123404', 15, NULL, 'Hyundai', 'Sonata', '2017', 132000, '2026-04-08', 7000, '2026-08-30', 131000, 'Silver', 'Car', 'G4KJ-2.4-22741', '2017-11-04', 'Inactive', '2026-09-26 17:50:00'),
(21, 'GND-5511', 'JM1BM1U72G4123405', 16, 3, 'Mazda', 'Mazda3', '2019', 70300, '2026-08-19', 6000, '2026-09-22', 69000, 'Soul Red', 'Car', 'PE-VPS-991245', '2019-03-30', 'Active', '2026-09-28 19:05:00'),
(22, 'GND-6677', '1FTEW1EP2KFA12306', 17, NULL, 'Ford', 'F-150', '2020', 61000, '2026-09-01', 8000, '2026-09-01', 60000, 'White', 'Pickup', 'ECO-2.7-V6-33211', '2020-02-14', 'Active', '2026-09-30 20:35:00'),
(23, 'GND-7788', '3CZRU6H50HM123407', 18, 3, 'Honda', 'HR-V', '2017', 99300, '2026-06-22', 6000, '2026-09-08', 98000, 'Blue', 'SUV', 'R18Z9-772341', '2017-07-09', 'Active', '2026-10-02 12:50:00'),
(24, 'GND-8899', '2T3RFREV5JW123408', 19, NULL, 'Toyota', 'RAV4', '2018', 85600, '2026-08-05', 7000, '2026-09-19', 84000, 'Grey', 'SUV', '2AR-FE-119342', '2018-10-21', 'Active', '2026-10-04 14:05:00'),
(25, 'GND-9911', 'KNAFK4A66G5123409', 20, NULL, 'Kia', 'Forte', '2016', 145000, '2026-03-11', 7000, '2026-09-30', 144000, 'Black', 'Car', 'G4KD-2.0-33412', '2016-09-12', 'Stolen', '2026-10-06 15:20:00'),
(26, 'GND-1013', '1FM5K8GT0GG123410', 12, 3, 'Ford', 'Explorer', '2016', 118000, '2026-05-29', 8000, '2026-09-15', 117000, 'Bronze', 'SUV', '3.5-V6-882417', '2016-12-02', 'Active', '2026-10-08 17:30:00');

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

--
-- Dumping data for table `vehicle_documents`
--

INSERT INTO `vehicle_documents` (`DocumentID`, `VehicleID`, `UploadedBy`, `DocType`, `OriginalName`, `FileName`, `FilePath`, `FileSize`, `MimeType`, `CreatedAt`) VALUES
(1, 17, 1, 'Registration', 'BMW_328i_Registration.pdf', 'veh17_reg.pdf', 'marketplace/docs/veh17_reg.pdf', 204800, 'application/pdf', '2026-10-01 09:25:00'),
(2, 18, 1, 'Registration', 'Chevy_Malibu_Registration.pdf', 'veh18_reg.pdf', 'marketplace/docs/veh18_reg.pdf', 198400, 'application/pdf', '2026-10-02 10:58:00'),
(3, 19, 1, 'Insurance', 'Subaru_Legacy_Policy.pdf', 'veh19_ins.pdf', 'marketplace/docs/veh19_ins.pdf', 153600, 'application/pdf', '2026-10-03 14:35:00'),
(4, 20, 1, 'Registration', 'Hyundai_Sonata_Registration.pdf', 'veh20_reg.pdf', 'marketplace/docs/veh20_reg.pdf', 211200, 'application/pdf', '2026-10-05 08:45:00'),
(5, 21, 1, 'Insurance', 'Mazda3_Policy.pdf', 'veh21_ins.pdf', 'marketplace/docs/veh21_ins.pdf', 148800, 'application/pdf', '2026-10-07 16:20:00'),
(6, 22, 1, 'Registration', 'Ford_F150_Registration.pdf', 'veh22_reg.pdf', 'marketplace/docs/veh22_reg.pdf', 220000, 'application/pdf', '2026-10-09 11:55:00'),
(7, 23, 1, 'Insurance', 'Honda_HRV_Policy.pdf', 'veh23_ins.pdf', 'marketplace/docs/veh23_ins.pdf', 139200, 'application/pdf', '2026-10-10 08:10:00'),
(8, 24, 1, 'Registration', 'Toyota_RAV4_Registration.pdf', 'veh24_reg.pdf', 'marketplace/docs/veh24_reg.pdf', 205600, 'application/pdf', '2026-10-11 09:30:00'),
(9, 25, 1, 'Insurance', 'Kia_Forte_Policy.pdf', 'veh25_ins.pdf', 'marketplace/docs/veh25_ins.pdf', 144000, 'application/pdf', '2026-10-12 16:05:00'),
(10, 26, 1, 'Registration', 'Ford_Explorer_Registration.pdf', 'veh26_reg.pdf', 'marketplace/docs/veh26_reg.pdf', 214400, 'application/pdf', '2026-10-13 13:15:00'),
(11, 7, 1, 'Insurance', 'Honda_Civic_Policy.pdf', 'veh7_ins.pdf', 'marketplace/docs/veh7_ins.pdf', 145600, 'application/pdf', '2026-10-14 09:00:00'),
(12, 8, 1, 'Registration', 'Ford_F150_Registration_2.pdf', 'veh8_reg.pdf', 'marketplace/docs/veh8_reg.pdf', 219200, 'application/pdf', '2026-10-14 09:05:00');

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
(6, 5, 'marketplace/uploads/vehicle_5_0e04599bf1354bfbc82b8824625061f5.jpg', 0, '2026-09-15 16:37:33'),
(7, 7, 'marketplace/uploads/vehicle_7_placeholder.jpg', 1, '2026-10-01 13:20:00'),
(8, 8, 'marketplace/uploads/vehicle_8_placeholder.jpg', 1, '2026-10-02 14:55:00'),
(9, 9, 'marketplace/uploads/vehicle_9_placeholder.jpg', 1, '2026-10-03 18:30:00'),
(10, 10, 'marketplace/uploads/vehicle_10_placeholder.jpg', 1, '2026-10-05 12:40:00'),
(11, 11, 'marketplace/uploads/vehicle_11_placeholder.jpg', 1, '2026-10-07 20:15:00'),
(12, 12, 'marketplace/uploads/vehicle_12_placeholder.jpg', 1, '2026-10-09 15:50:00'),
(13, 13, 'marketplace/uploads/vehicle_13_placeholder.jpg', 1, '2026-10-10 12:05:00'),
(14, 14, 'marketplace/uploads/vehicle_14_placeholder.jpg', 1, '2026-10-11 13:25:00'),
(15, 15, 'marketplace/uploads/vehicle_15_placeholder.jpg', 1, '2026-10-12 20:00:00'),
(16, 16, 'marketplace/uploads/vehicle_16_placeholder.jpg', 1, '2026-10-13 17:10:00'),
(17, 17, 'marketplace/uploads/vehicle_17_placeholder.jpg', 1, '2026-09-20 13:18:00'),
(18, 18, 'marketplace/uploads/vehicle_18_placeholder.jpg', 1, '2026-09-22 14:28:00'),
(19, 19, 'marketplace/uploads/vehicle_19_placeholder.jpg', 1, '2026-09-24 15:42:00'),
(20, 20, 'marketplace/uploads/vehicle_20_placeholder.jpg', 1, '2026-09-26 17:58:00'),
(21, 21, 'marketplace/uploads/vehicle_21_placeholder.jpg', 1, '2026-09-28 19:12:00'),
(22, 22, 'marketplace/uploads/vehicle_22_placeholder.jpg', 1, '2026-09-30 20:42:00'),
(23, 23, 'marketplace/uploads/vehicle_23_placeholder.jpg', 1, '2026-10-02 12:58:00'),
(24, 24, 'marketplace/uploads/vehicle_24_placeholder.jpg', 1, '2026-10-04 14:12:00'),
(25, 25, 'marketplace/uploads/vehicle_25_placeholder.jpg', 1, '2026-10-06 15:28:00'),
(26, 26, 'marketplace/uploads/vehicle_26_placeholder.jpg', 1, '2026-10-08 17:38:00');

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
-- Indexes for table `accident_photos`
--
ALTER TABLE `accident_photos`
  ADD PRIMARY KEY (`PhotoID`),
  ADD KEY `idx_accident` (`AccidentID`),
  ADD KEY `idx_uploader` (`UploadedBy`);

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
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`AttemptID`),
  ADD KEY `idx_email_time` (`Email`,`AttemptedAt`),
  ADD KEY `idx_ip_time` (`IPAddress`,`AttemptedAt`),
  ADD KEY `idx_time` (`AttemptedAt`);

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
  ADD PRIMARY KEY (`OwnerID`),
  ADD KEY `idx_owner_user` (`UserID`);

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
-- Indexes for table `recovery_codes`
--
ALTER TABLE `recovery_codes`
  ADD PRIMARY KEY (`CodeID`),
  ADD KEY `idx_user` (`UserID`),
  ADD KEY `idx_user_unused` (`UserID`,`UsedAt`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`TokenID`),
  ADD KEY `idx_token_hash` (`TokenHash`),
  ADD KEY `idx_user_id` (`UserID`);

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
  MODIFY `AccidentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `accident_photos`
--
ALTER TABLE `accident_photos`
  MODIFY `PhotoID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `insurance`
--
ALTER TABLE `insurance`
  MODIFY `InsuranceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `AttemptID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `OwnerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `ownership_history`
--
ALTER TABLE `ownership_history`
  MODIFY `OwnershipID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `ResetID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recovery_codes`
--
ALTER TABLE `recovery_codes`
  MODIFY `CodeID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `TokenID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `VehicleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `DocumentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `fk_owner_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_remember_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

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
