-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2024 at 06:30 PM
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
-- Database: `tour`
--

-- --------------------------------------------------------

--
-- Table structure for table `tour`
--

CREATE TABLE `tour` (
  `sl` int(4) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(3) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `email` varchar(25) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `other` varchar(150) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tour`
--

INSERT INTO `tour` (`sl`, `name`, `age`, `gender`, `email`, `phone`, `other`, `date`) VALUES
(1, 'DS', 24, 'male', 'ds@ds.com', '45614435463', 'No', '2024-11-03 21:26:35'),
(2, 'Abdullah Shishir', 23, 'male', 'abdullah.shishir19', '01124324904', 'dfg ', '2024-11-03 23:58:49'),
(3, 'Abdullah Shishir', 23, 'male', 'abdullah.shishir19', '01124324904', 'dfg ', '2024-11-04 00:00:44'),
(4, 'Abdullah Shishir', 23, 'male', 'abdullah.shishir19', '01124324904', 'dfg ', '2024-11-04 00:10:36'),
(5, 'js', 15, 'male', 'hgfhf', '01000000', 'yo yo', '2024-11-04 00:11:14'),
(6, 'js', 15, 'male', 'hgfhf', '01000000', 'yo yo', '2024-11-04 00:11:32'),
(7, 'js', 15, 'male', 'hgfhf', '01000000', 'yo yo', '2024-11-04 00:11:35'),
(8, '', 0, '', '', '', '', '2024-12-14 23:21:13'),
(9, 'Abdullah Shishir', 23, 'male', 'akou873@outlook.com', '01124324904', 'Yo', '2024-12-14 23:25:43'),
(10, 'Abdullah Shishir', 23, 'male', 'akou873@outlook.com', '01124324904', 'Yo', '2024-12-14 23:27:40'),
(11, 'Abdullah Shishir', 23, 'male', 'akou873@outlook.com', '01124324904', 'Yo', '2024-12-14 23:28:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tour`
--
ALTER TABLE `tour`
  ADD PRIMARY KEY (`sl`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tour`
--
ALTER TABLE `tour`
  MODIFY `sl` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
