-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: KrazePlanet
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_name` varchar(100) DEFAULT 'Web Security',
  `assigned_users` varchar(255) DEFAULT 'All Trainees',
  `submission_date` date DEFAULT NULL,
  `labs_json` longtext DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'HTML Injection (HTMLI) Penetration Testing Assessment','Complete hands-on HTML injection exploit laboratories, document attack vectors, and construct security audit reports.','HTML Injection (HTMLI)','All Trainees','2026-08-26','[{\"badge\":\"LAB 1\",\"difficulty\":\"easy\",\"title\":\"HTML Injection - Reflected (GET Parameter)\",\"desc\":\"Inspect parameter reflections and execute arbitrary HTML element payload injection.\",\"url\":\"\\/htmli_reflected_get.php\",\"report_url\":\"HackerOneReport\\/1.md\"},{\"badge\":\"LAB 2\",\"difficulty\":\"easy\",\"title\":\"HTML Injection - Reflected (POST Parameter)\",\"desc\":\"Bypass client validations to submit payload through HTTP POST body.\",\"url\":\"\\/htmli_reflected_post.php\",\"report_url\":\"HackerOneReport\\/2.md\"}]','admin','2026-08-19 12:47:15'),(2,'Cross-Site Scripting (XSS) Core Challenge Track','Master client-side security testing across reflected, stored, and filter-evasion attack surfaces.','Cross-Site Scripting (XSS)','All Trainees','2026-09-02','[{\"badge\":\"LAB 1\",\"difficulty\":\"easy\",\"title\":\"Reflected XSS - Basic Script Injection\",\"desc\":\"Inject unencoded script elements into query string reflection sinks.\",\"url\":\"\\/reflected_xss_basic.php\",\"report_url\":\"HackerOneReport\\/1.md\"},{\"badge\":\"LAB 19\",\"difficulty\":\"hard\",\"title\":\"Reflected XSS - Multi-Parameter Filter Evasion\",\"desc\":\"Advanced filter evasion techniques across multiple interdependent reflection points.\",\"url\":\"\\/checkout\",\"report_url\":\"HackerOneReport\\/1.md\"}]','admin','2026-08-19 12:47:15');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_bookmarks`
--

DROP TABLE IF EXISTS `user_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_bookmark_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_bookmarks`
--

LOCK TABLES `user_bookmarks` WRITE;
/*!40000 ALTER TABLE `user_bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_lab_history`
--

DROP TABLE IF EXISTS `user_lab_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_lab_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `lab_title` varchar(255) DEFAULT NULL,
  `lab_badge` varchar(50) DEFAULT 'LAB',
  `lab_category` varchar(100) DEFAULT 'Web Security',
  `lab_url` varchar(500) DEFAULT NULL,
  `last_accessed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lab_hist_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_lab_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_lab_history`
--

LOCK TABLES `user_lab_history` WRITE;
/*!40000 ALTER TABLE `user_lab_history` DISABLE KEYS */;
INSERT INTO `user_lab_history` VALUES (1,1,'/tutorialrepublic','Reflected XSS - TutorialRepublic: Web Development Reference Search','LAB 1','Web Security','/tutorialrepublic','2026-08-19 13:21:50');
/*!40000 ALTER TABLE `user_lab_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT 'assignments.php',
  `icon` varchar(50) DEFAULT 'bi-bell-fill',
  `icon_bg` varchar(50) DEFAULT 'bg-info bg-opacity-10 text-info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_notifications`
--

LOCK TABLES `user_notifications` WRITE;
/*!40000 ALTER TABLE `user_notifications` DISABLE KEYS */;
INSERT INTO `user_notifications` VALUES (1,NULL,'New Assignment: HTML Injection','Complete the HTML Injection (HTMLI) penetration testing report assignment.','assignments.php','bi-journal-code','bg-info bg-opacity-10 text-info',0,'2026-08-19 09:54:05'),(2,NULL,'Platform Labs Updated','260+ interactive vulnerability training laboratories are active and ready.','index.php','bi-shield-check','bg-success bg-opacity-10 text-success',0,'2026-08-18 09:54:05'),(3,NULL,'Welcome to KrazePlanet','Track your solved labs, bookmarks, and certifications directly in your dashboard.','profile.php','bi-person-check-fill','bg-warning bg-opacity-10 text-warning',0,'2026-08-17 09:54:05'),(4,NULL,'CTF Leaderboard Active','Check the top security researchers and solve labs to climb the ranking.','leaderboard.php','bi-trophy-fill','bg-danger bg-opacity-10 text-danger',0,'2026-08-16 09:54:05'),(5,NULL,'WhatsApp Support Live','Direct WhatsApp mentoring and lab assistance is now connected.','contact.php','bi-whatsapp','bg-success bg-opacity-10 text-success',0,'2026-08-15 09:54:05');
/*!40000 ALTER TABLE `user_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_solved_labs`
--

DROP TABLE IF EXISTS `user_solved_labs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_solved_labs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lab_id` varchar(255) NOT NULL,
  `difficulty` varchar(20) DEFAULT 'easy',
  `points` int(11) DEFAULT 20,
  `solved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lab_unique` (`user_id`,`lab_id`),
  CONSTRAINT `user_solved_labs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_solved_labs`
--

LOCK TABLES `user_solved_labs` WRITE;
/*!40000 ALTER TABLE `user_solved_labs` DISABLE KEYS */;
INSERT INTO `user_solved_labs` VALUES (8,1,'/checkout','hard',100,'2026-08-19 12:40:25'),(11,1,'/tickets','medium',50,'2026-08-19 12:44:10'),(12,1,'/docs','easy',20,'2026-08-19 12:44:24');
/*!40000 ALTER TABLE `user_solved_labs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) DEFAULT '',
  `phone` varchar(30) DEFAULT '',
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `country` varchar(10) DEFAULT 'IN',
  `avatar` varchar(500) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'trainee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','System Administrator','+91 9876543210','admin@krazeplanet.com','$2y$10$c5VmO.FbPSL2bl8b4Dq9Ye9015OctPyRATU43IxaZIuZ5VP25Pt2G','IN','https://api.dicebear.com/7.x/adventurer/svg?seed=Admin&hair=short01','admin','2026-08-19 09:54:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-19 18:56:09
