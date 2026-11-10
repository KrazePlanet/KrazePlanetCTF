-- ==========================================================
-- MySQL dump 10.19  Distrib 10.5.21-MariaDB, for Linux (x86_64)
-- Host: db01.internal.riverview.test    Database: riverview_admissions
-- Server version: 10.5.21-MariaDB-log
-- Dump date: 2025-09-18 03:42:11
-- ==========================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `cleartext_backup` varchar(64) DEFAULT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'staff',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `admin_users` WRITE;
INSERT INTO `admin_users` VALUES 
(1,'admin_root','admissions-admin@riverview.test','$2y$10$y582jKs1jWd3G6qQ8B1jZeO3iU2k8y9B.uXw6oJgA8gB1sM3q0tKy','Adm1n_Riverview#2025!','SuperAdmin','2025-09-17 18:22:10'),
(2,'reviewer_dean','d.jenkins@riverview.test','$2y$10$f381kLs9mZd4K1qW0A2jVeP4jV3l9z0C.vYx7pKfB9hC2tN4r1uLz','DeanJenkins_Vault99!','DeanReviewer','2025-09-17 14:10:45'),
(3,'service_sync','sync-bot@internal.riverview.test','$2y$10$p092mNs8kYc3L2rV9B3kWfQ5kW4m0a1D.wZy8qLgC0iD3uO5s2vMA','SyncBot_ApiPass2025$','ServiceAccount','2025-09-18 03:00:02');
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `application_id` varchar(20) NOT NULL,
  `applicant_name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `ssn` varchar(20) NOT NULL,
  `program` varchar(128) NOT NULL,
  `gpa` decimal(3,2) NOT NULL,
  `sat_score` int(11) DEFAULT NULL,
  `household_income` decimal(10,2) NOT NULL,
  `financial_aid_granted` decimal(10,2) DEFAULT 0.00,
  `application_status` varchar(32) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `applications` WRITE;
INSERT INTO `applications` VALUES 
('RV-2026-1049','Johnathan Vance','j.vance@inbox.test','+1 (555) 234-8901','219-45-8812','B.Sc. Computer Science & AI',3.85,1490,62400.00,18500.00,'Under Review','Dean recommendation attached. Priority candidate.','2025-09-10 11:30:00'),
('RV-2026-1052','Elena Rostova','elena.rostova@mailservice.test','+371 28 910 234','LV-948210385','M.Sc. Cybersecurity & Trust',3.92,NULL,30200.00,42000.00,'Approved','International visa F-1 expedited. Embassy clearance verified.','2025-09-11 15:45:12'),
('RV-2026-1088','Marcus Sterling','m.sterling@campusnet.test','+1 (555) 492-1084','184-90-3419','B.Eng. Mechanical Engineering',3.98,1560,195000.00,50000.00,'Approved','Presidential fellowship awardee.','2025-09-12 09:20:00'),
('RV-2026-1104','Sophia Morales','sophia.m@outlook.test','+1 (555) 782-9912','392-18-5041','B.A. Digital Media & Design',3.65,1380,48000.00,12000.00,'Pending Documents','Tax document follow-up requested.','2025-09-14 16:05:30');
UNLOCK TABLES;

--
-- Table structure for table `system_secrets`
--

DROP TABLE IF EXISTS `system_secrets`;
CREATE TABLE `system_secrets` (
  `secret_key` varchar(64) NOT NULL,
  `secret_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`secret_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `system_secrets` WRITE;
INSERT INTO `system_secrets` VALUES 
('AWS_S3_KEY','AKIA482910385RIVERVIEW','Cold storage S3 access key ID'),
('AWS_S3_SECRET','k9fA+7qW28zP10mN3rX5tL8vY4bC0dE1fG2hJ3kL','Cold storage S3 secret key'),
('SMTP_PASSWORD','RiverviewMail_2025!','Admissions relay password'),
('MASTER_DB_PASS','RiverviewTraining_DB_2025!','Root database master pass');
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Dump completed on 2025-09-18 03:42:15
