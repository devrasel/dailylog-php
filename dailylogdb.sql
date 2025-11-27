-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 27, 2025 at 05:30 AM
-- Server version: 9.4.0
-- PHP Version: 8.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dailylogdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `Expense`
--

CREATE TABLE `Expense` (
  `id` varchar(191) NOT NULL,
  `date` datetime NOT NULL,
  `amount` double NOT NULL,
  `category` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text,
  `paymentMethod` varchar(191) DEFAULT 'Cash',
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Expense`
--

INSERT INTO `Expense` (`id`, `date`, `amount`, `category`, `title`, `description`, `paymentMethod`, `userId`, `createdAt`, `updatedAt`) VALUES
('exp_69269ec7eceff5.50790540', '2025-11-26 06:31:00', 275, 'Other', 'Tree pot', 'For small trees insid ehouse', 'Cash', 'user_692695840ea532.14410380', '2025-11-26 12:31:35', '2025-11-26 12:31:35'),
('exp_6926a124efa905.32313953', '2025-11-26 06:41:00', 1450, 'Food', 'Rice ', '28 Rice', 'Cash', 'user_692695840ea532.14410380', '2025-11-26 12:41:40', '2025-11-26 12:41:40'),
('exp_6926ce54e94d40.47773050', '2025-11-26 09:54:00', 35000, 'Shopping', 'Cloths', '', 'Cash', 'user_692695840ea532.14410380', '2025-11-26 15:54:28', '2025-11-26 15:54:28');

-- --------------------------------------------------------

--
-- Table structure for table `FuelEntry`
--

CREATE TABLE `FuelEntry` (
  `id` varchar(191) NOT NULL,
  `date` datetime NOT NULL,
  `odometer` double NOT NULL,
  `liters` double NOT NULL,
  `costPerLiter` double NOT NULL,
  `totalCost` double NOT NULL,
  `fuelType` varchar(191) DEFAULT 'FULL',
  `location` varchar(191) DEFAULT NULL,
  `notes` text,
  `parentEntry` varchar(191) DEFAULT NULL,
  `odometerExtraKm` double DEFAULT '0',
  `vehicleId` varchar(191) NOT NULL,
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `FuelEntry`
--

INSERT INTO `FuelEntry` (`id`, `date`, `odometer`, `liters`, `costPerLiter`, `totalCost`, `fuelType`, `location`, `notes`, `parentEntry`, `odometerExtraKm`, `vehicleId`, `userId`, `createdAt`, `updatedAt`) VALUES
('fuel_692696212290c8.13158982', '2025-11-24 05:54:00', 2650, 4, 122.96, 491.84, 'FULL', '', '', NULL, 0, 'veh_692695ac88ee26.20952277', 'user_692695840ea532.14410380', '2025-11-26 11:54:41', '2025-11-26 15:51:08'),
('fuel_6926cd3a997082.81291949', '2025-11-26 09:49:00', 2850, 4.07, 122.96, 500, 'FULL', 'Shapla', '', NULL, 0, 'veh_692695ac88ee26.20952277', 'user_692695840ea532.14410380', '2025-11-26 15:49:46', '2025-11-26 15:49:46'),
('fuel_6926cd6b0e49c7.94895554', '2025-11-26 09:50:00', 2860, 1.22, 122.96, 150, 'PARTIAL', 'shapla', '', NULL, 0, 'veh_692695ac88ee26.20952277', 'user_692695840ea532.14410380', '2025-11-26 15:50:35', '2025-11-26 15:50:35');

-- --------------------------------------------------------

--
-- Table structure for table `MaintenanceCost`
--

CREATE TABLE `MaintenanceCost` (
  `id` varchar(191) NOT NULL,
  `date` datetime NOT NULL,
  `description` varchar(191) NOT NULL,
  `cost` double NOT NULL,
  `category` varchar(191) NOT NULL,
  `odometer` double DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `notes` text,
  `vehicleId` varchar(191) NOT NULL,
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `MaintenanceCost`
--

INSERT INTO `MaintenanceCost` (`id`, `date`, `description`, `cost`, `category`, `odometer`, `location`, `notes`, `vehicleId`, `userId`, `createdAt`, `updatedAt`) VALUES
('maint_6926a2e7bc6cb5.20565743', '2025-11-26 06:48:00', 'OIl change', 650, 'Oil Change', 2450, NULL, 'New oil change on MBC honda showroom', 'veh_692695f20cd431.07276010', 'user_692695840ea532.14410380', '2025-11-26 12:49:11', '2025-11-26 12:49:11'),
('maint_6926ce92866993.29581991', '2025-11-26 09:55:00', 'Oil change', 760, 'Oil Change', 2850, NULL, '', 'veh_692695ac88ee26.20952277', 'user_692695840ea532.14410380', '2025-11-26 15:55:30', '2025-11-26 15:55:30');

-- --------------------------------------------------------

--
-- Table structure for table `SecurityQuestion`
--

CREATE TABLE `SecurityQuestion` (
  `id` varchar(191) NOT NULL,
  `question` varchar(191) NOT NULL,
  `answerHash` varchar(191) NOT NULL,
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `SecurityQuestion`
--

INSERT INTO `SecurityQuestion` (`id`, `question`, `answerHash`, `userId`, `createdAt`, `updatedAt`) VALUES
('security_6927d458d56b64.17281804', 'What city were you born in?', '$2y$12$52xF5FjtrLYnmgElb0BjiuILA38uknK1V2p7GwpQ1sYMSpZj6I1Dm', 'user_692695840ea532.14410380', '2025-11-27 10:32:24', '2025-11-27 10:32:24'),
('security_6927d4ca0d2296.59379898', 'What was your first car?', '$2y$12$oWJumccUAnBNYdQCts.wXu4N99Elt3TXtWgtXpOLEV1s8Nb4bUHg.', 'user_6927d4ca0cabf7.24100885', '2025-11-27 10:34:18', '2025-11-27 10:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `Settings`
--

CREATE TABLE `Settings` (
  `id` varchar(191) NOT NULL,
  `currency` varchar(191) DEFAULT 'BDT',
  `dateFormat` varchar(191) DEFAULT 'MM/DD/YYYY',
  `distanceUnit` varchar(191) DEFAULT 'km',
  `volumeUnit` varchar(191) DEFAULT 'L',
  `entriesPerPage` int DEFAULT '10',
  `timezone` varchar(191) DEFAULT 'Asia/Dhaka',
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Settings`
--

INSERT INTO `Settings` (`id`, `currency`, `dateFormat`, `distanceUnit`, `volumeUnit`, `entriesPerPage`, `timezone`, `userId`, `createdAt`, `updatedAt`) VALUES
('settings_692695840f9b91.83780261', 'BDT', 'MM/DD/YYYY', 'km', 'L', 20, 'Asia/Dhaka', 'user_692695840ea532.14410380', '2025-11-26 11:52:04', '2025-11-27 09:59:13'),
('settings_6927d27e959392.68796551', 'BDT', 'MM/DD/YYYY', 'km', 'L', 10, 'Asia/Dhaka', 'user_6927d27e950132.12470520', '2025-11-27 10:24:30', '2025-11-27 10:24:30'),
('settings_6927d4ca0d5858.53194069', 'BDT', 'MM/DD/YYYY', 'km', 'L', 10, 'Asia/Dhaka', 'user_6927d4ca0cabf7.24100885', '2025-11-27 10:34:18', '2025-11-27 10:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `id` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `passwordHash` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `User`
--

INSERT INTO `User` (`id`, `email`, `name`, `passwordHash`, `createdAt`, `updatedAt`) VALUES
('user_692695840ea532.14410380', 'rasel@gmail.com', 'Rasel', '$2y$12$RMRc17v8YBh/TukJPUOAu.gThvpd8/2aK1fo3gG/sKLgal8bTYS0y', '2025-11-26 11:52:04', '2025-11-27 10:32:59'),
('user_6927d27e950132.12470520', 'test@ff.dd', 'test', '$2y$12$Hws.FYDOJg1J9C115DHvSudzZM3HmmaGjSApQuHIdv7gvqBJAufj6', '2025-11-27 10:24:30', '2025-11-27 10:24:30'),
('user_6927d4ca0cabf7.24100885', 'asd@sdd.dd', 'asdsa', '$2y$12$ezB0IuDF8CemgMrqOX5ubube33/Zsh7uxVZcVN8tffjf7VqgXlkrW', '2025-11-27 10:34:18', '2025-11-27 10:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `Vehicle`
--

CREATE TABLE `Vehicle` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `make` varchar(191) DEFAULT NULL,
  `model` varchar(191) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `licensePlate` varchar(191) DEFAULT NULL,
  `chassisNumber` varchar(191) DEFAULT NULL,
  `engineCC` varchar(191) DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT '1',
  `displayOrder` int DEFAULT '0',
  `userId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Vehicle`
--

INSERT INTO `Vehicle` (`id`, `name`, `make`, `model`, `year`, `licensePlate`, `chassisNumber`, `engineCC`, `color`, `isActive`, `displayOrder`, `userId`, `createdAt`, `updatedAt`) VALUES
('veh_692695ac88ee26.20952277', 'Hornet 2.0', NULL, NULL, NULL, 'RANGPUR METRO LA-11-8428', 'PS0MC5960SH004673', '184.5', 'Black', 1, 0, 'user_692695840ea532.14410380', '2025-11-26 11:52:44', '2025-11-27 10:08:12'),
('veh_692695f20cd431.07276010', 'Other bike', NULL, NULL, NULL, 'test', 'Chassis test', '1223', 'red', 1, 0, 'user_692695840ea532.14410380', '2025-11-26 11:53:54', '2025-11-27 10:40:33');

-- --------------------------------------------------------

--
-- Table structure for table `VehicleDocument`
--

CREATE TABLE `VehicleDocument` (
  `id` varchar(191) NOT NULL,
  `filename` varchar(191) NOT NULL,
  `filetype` varchar(191) NOT NULL,
  `size` int NOT NULL,
  `url` varchar(191) NOT NULL,
  `vehicleId` varchar(191) NOT NULL,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `VehicleDocument`
--

INSERT INTO `VehicleDocument` (`id`, `filename`, `filetype`, `size`, `url`, `vehicleId`, `createdAt`, `updatedAt`) VALUES
('doc_6926a2b63d03f9.61969581', 'rasel passport file.pdf', 'application/pdf', 527375, 'static/uploads/1764139702-6926a2b63cdc0.pdf', 'veh_692695f20cd431.07276010', '2025-11-26 12:48:22', '2025-11-26 12:48:22'),
('doc_6926a2ffac9815.49594961', 'raselpic.jpg', 'image/jpeg', 94450, 'static/uploads/1764139775-6926a2ffac740.jpg', 'veh_692695f20cd431.07276010', '2025-11-26 12:49:35', '2025-11-26 12:49:35'),
('doc_6926cdcd9dae20.30933134', 'rasel passport file.pdf', 'application/pdf', 527375, 'static/uploads/1764150733-6926cdcd9d6a5.pdf', 'veh_692695ac88ee26.20952277', '2025-11-26 15:52:13', '2025-11-26 15:52:13'),
('doc_6927c013a489c2.75026634', 'WhatsApp Image 2025-01-02 at 16.23.38.jpeg', 'image/jpeg', 362885, 'static/uploads/1764212755-6927c013a46f0.jpeg', 'veh_692695ac88ee26.20952277', '2025-11-27 09:05:55', '2025-11-27 09:05:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Expense`
--
ALTER TABLE `Expense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`userId`,`date`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `FuelEntry`
--
ALTER TABLE `FuelEntry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicleId` (`vehicleId`),
  ADD KEY `userId` (`userId`),
  ADD KEY `parentEntry` (`parentEntry`);

--
-- Indexes for table `MaintenanceCost`
--
ALTER TABLE `MaintenanceCost`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicleId` (`vehicleId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `SecurityQuestion`
--
ALTER TABLE `SecurityQuestion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_question` (`userId`,`question`);

--
-- Indexes for table `Settings`
--
ALTER TABLE `Settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userId` (`userId`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `Vehicle`
--
ALTER TABLE `Vehicle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `VehicleDocument`
--
ALTER TABLE `VehicleDocument`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicleId` (`vehicleId`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Expense`
--
ALTER TABLE `Expense`
  ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `FuelEntry`
--
ALTER TABLE `FuelEntry`
  ADD CONSTRAINT `fuelentry_ibfk_1` FOREIGN KEY (`vehicleId`) REFERENCES `Vehicle` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fuelentry_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fuelentry_ibfk_3` FOREIGN KEY (`parentEntry`) REFERENCES `FuelEntry` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `MaintenanceCost`
--
ALTER TABLE `MaintenanceCost`
  ADD CONSTRAINT `maintenancecost_ibfk_1` FOREIGN KEY (`vehicleId`) REFERENCES `Vehicle` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenancecost_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `SecurityQuestion`
--
ALTER TABLE `SecurityQuestion`
  ADD CONSTRAINT `securityquestion_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Settings`
--
ALTER TABLE `Settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Vehicle`
--
ALTER TABLE `Vehicle`
  ADD CONSTRAINT `vehicle_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `VehicleDocument`
--
ALTER TABLE `VehicleDocument`
  ADD CONSTRAINT `vehicledocument_ibfk_1` FOREIGN KEY (`vehicleId`) REFERENCES `Vehicle` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
