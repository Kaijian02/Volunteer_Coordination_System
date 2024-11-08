-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2024 at 10:23 AM
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
-- Database: `volunteer_coordination_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('Present','Absent') DEFAULT 'Absent',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `event_id`, `attendance_date`, `status`, `timestamp`) VALUES
(622, 44, 72, '2024-11-01', 'Present', '2024-11-08 09:22:14'),
(623, 45, 72, '2024-11-01', 'Present', '2024-11-08 09:22:14'),
(624, 39, 72, '2024-11-01', 'Present', '2024-11-08 09:22:14'),
(625, 44, 72, '2024-11-02', 'Present', '2024-11-08 09:22:18'),
(626, 45, 72, '2024-11-02', 'Present', '2024-11-08 09:22:18'),
(627, 39, 72, '2024-11-02', 'Absent', '2024-11-08 09:22:18');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `donation_amount` decimal(10,2) NOT NULL,
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `user_id`, `event_id`, `donation_amount`, `donation_date`) VALUES
(94, 44, 71, 200.00, '2024-11-08 09:19:42');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_poster` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `volunteers_needed` int(11) NOT NULL,
  `current_volunteers` int(11) DEFAULT 0,
  `close_event` enum('Yes','No') NOT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `min_age` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `donation` enum('Yes','No') NOT NULL,
  `goal` float(10,2) DEFAULT 0.00,
  `raised` float(10,2) DEFAULT 0.00,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Public','Closed','Cancelled','Passed') DEFAULT NULL,
  `date_edited` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_poster`, `start_date`, `end_date`, `start_time`, `end_time`, `venue`, `state`, `city`, `volunteers_needed`, `current_volunteers`, `close_event`, `skills`, `min_age`, `comments`, `donation`, `goal`, `raised`, `date_created`, `status`, `date_edited`, `user_id`) VALUES
(52, 'Beach Cleanup Drives', 'I’m passionate about giving back to the community and creating positive change through volunteering. Over the years, I\'ve had the opportunity to work on various initiatives that support [specific causes or general community support]. I believe that even small efforts can lead to big impacts when we work together. I\'m excited to be part of this community and look forward to contributing in any way I can!', 'uploads/30/poster/52/27b9dd813a6d733eea1aa38b1af4ef6f.jpg', '2024-11-13', '2024-11-16', '09:10:00', '12:10:00', 'Batu Ferringhi', 'Penang', 'Batu Feringgi', 1, 0, 'Yes', '', 20, 'None', 'Yes', 8000.00, 0.00, '2024-10-06 12:43:27', 'Cancelled', '2024-11-08 07:04:26', 30),
(71, 'Food Bank Packing Dayasd', 'Volunteer with us to pack food for families in need. Your time will make a direct impact on reducing hunger in our community.', 'uploads/30/poster/71/dbe2568ca604e6e83ba759b5e02a94e8.jpg', '2024-12-19', NULL, '10:00:00', '15:00:00', 'Taman Mahsuri, Jalan Mahsuri 5', 'Kelantan', 'Pasir Mas', 10, 1, 'No', '', 13, 'None', 'Yes', 1000.00, 200.00, '2024-11-08 04:46:59', 'Public', '2024-11-08 09:22:46', 30),
(72, 'Senior Center Tech Help Day', 'Spend an afternoon assisting seniors with their tech devices. Your patience and tech skills will help them stay connected with family and friends.', 'uploads/30/poster/72/9327c54d4b346b7f9996e76a12d5d53c.jpg', '2024-11-01', '2024-11-02', '10:00:00', '16:00:00', 'Tanjung Tuition Centre', 'Penang', 'Bukit Mertajam', 5, 5, 'Yes', 'Tutoring and Mentoring Programs,Adult Education Classes,Fitness and Wellness Programs,Housing Assistance,Support Groups', 18, 'Can communicate well in English and Bahasa Melayu', 'No', 0.00, 0.00, '2024-11-08 06:17:51', 'Closed', '2024-11-08 09:22:09', 30);

-- --------------------------------------------------------

--
-- Table structure for table `event_applications`
--

CREATE TABLE `event_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('Applying','Approved','Cancelled','Participated','Rejected','Pending Cancellation','Cancellation Approved') DEFAULT NULL,
  `applied_date` datetime NOT NULL DEFAULT current_timestamp(),
  `cancelled_date` datetime DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `rejected_date` datetime DEFAULT NULL,
  `pending_cancelled_date` datetime DEFAULT NULL,
  `reason` varchar(150) DEFAULT NULL,
  `evidence` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_applications`
--

INSERT INTO `event_applications` (`id`, `user_id`, `event_id`, `status`, `applied_date`, `cancelled_date`, `approval_date`, `rejected_date`, `pending_cancelled_date`, `reason`, `evidence`) VALUES
(117, 44, 71, 'Approved', '2024-11-08 17:19:21', NULL, '2024-11-08 17:22:49', NULL, NULL, NULL, NULL),
(118, 44, 72, 'Participated', '2024-11-08 17:21:07', NULL, '2024-11-08 17:21:55', NULL, NULL, NULL, NULL),
(119, 39, 72, 'Participated', '2024-11-08 17:21:17', NULL, '2024-11-08 17:22:04', NULL, NULL, NULL, NULL),
(120, 45, 72, 'Participated', '2024-11-08 17:21:32', NULL, '2024-11-08 17:22:00', NULL, NULL, NULL, NULL),
(121, 45, 71, 'Applying', '2024-11-08 17:22:55', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `volunteer_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `replied_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `volunteer_id`, `event_id`, `organizer_id`, `review_text`, `replied_text`, `created_at`, `updated_at`, `replied_at`, `replied_updated_at`) VALUES
(28, 44, 72, 30, 'dasdadasdad', 'Thanks hope to see you again!!asdasdads', '2024-11-08 06:20:26', '2024-11-08 09:02:04', '2024-11-08 06:21:34', '2024-11-08 09:02:31'),
(29, 45, 72, 30, 'Thank you for your participation', NULL, '2024-11-08 06:39:15', '2024-11-08 06:48:08', NULL, NULL),
(30, 39, 72, 30, 'asdad', NULL, '2024-11-08 09:02:11', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `state` varchar(30) NOT NULL,
  `city` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(100) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `date_joined` varchar(255) NOT NULL,
  `event_joined` int(11) DEFAULT NULL,
  `event_created` int(11) DEFAULT NULL,
  `self_introduction` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `credit` int(11) NOT NULL,
  `day_absent` int(10) DEFAULT NULL,
  `day_attended` int(10) DEFAULT NULL,
  `verified_by_admin` tinyint(1) DEFAULT 0,
  `failed_attempts` int(11) DEFAULT 0,
  `last_failed_attempt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `dob`, `state`, `city`, `password`, `token`, `is_verified`, `reset_token`, `reset_token_expiry`, `profile_image`, `date_joined`, `event_joined`, `event_created`, `self_introduction`, `skills`, `credit`, `day_absent`, `day_attended`, `verified_by_admin`, `failed_attempts`, `last_failed_attempt`) VALUES
(30, 'user', 'Law Kai Jian', 'lawkaijian@gmail.com', '2002-11-09', 'Kelantan', 'Pasir Mas', '$2y$10$v3utuwhreUkibCHecaMAmuyNdk.gUahJfF6jgg0mnNOw31Yes2jwS', '69f1cf5e5727d9585ca7408dd1c5171cf5fd9ecc6c919d2f85042f1f4ef5d8fdc5c25b4316304471e2d18f7336e33f66df84', 1, 'ec9ce6f28ab99715b625a6368b30f9b633e63d0359c1bb66b352e82cd691e61ece93d57fcc57dd1db1b0445dcbf45d3f9a6f', '2024-10-24 16:35:01', 'uploads/30/profile/e1d5511b2ec8e2566cbbf18524a6e541.jpg', '6 August 2024', 0, 3, 'Hi, my name is Law Kai Jian, 22 years old. I am passionate to organize volunteer event. Welcome everyone apply for my event. Together bring benefit to society!', 'Health Education Workshops,Community Clean-ups,Tree Planting and Gardening,Food Drives and Pantries,Housing Assistance,Cultural Festivals and Events', 100, NULL, NULL, 1, 0, NULL),
(39, 'user', 'Jerry Law', 'jerrylaw02@gmail.com', '2002-09-06', 'Penang', 'Bukit Mertajam', '$2y$10$DQbcrOd7xXf0P8wJNJp3pe62K0F95fy6I4BR8p3cXGApWrlsc.Bs.', '1ac4c4994b0fc74d06193936466c510e8293c1b487d3c65209d6df4984afae6cdea1188ff93c2b0e8ee324550b8ab9c93acc', 1, NULL, NULL, 'uploads/39/profile/931dda640f552319601730a9b8d7d8e6.jpg', '6 September 2024', 21, 0, 'I’m passionate about giving back to the community and creating positive change through volunteering. Over the years, I\'ve had the opportunity to work on various initiatives that support [specific causes or general community support]. I believe that even small efforts can lead to big impacts when we work together. I\'m excited to be part of this community and look forward to contributing in any way I can!', 'Community Clean-ups,Food Drives and Pantries,Clothing Drives,Cultural Festivals and Events,Recreational Programs', 98, 1, 10, 1, 0, NULL),
(44, 'user', 'Alan', 'p20012812@student.newinti.edu.my', '2005-09-07', 'Kuala Lumpur', 'Taman Tun Dr Ismail', '$2y$10$wAxdRcwJx99kDsWeRGLW2ObbzWd.17aKce2IyCyo8S1jnFifrs5TW', 'ed53c20c084ab8c74102dc454296a9d2cf26c1a1fb2322b396de71d0f2b2f9806f312428265fde85f0283e2bd3353670a09f', 1, NULL, NULL, 'uploads/44/profile/51a47ad1959c5e5f0cff2f175f4548de.jpg', '7 September 2024', 11, 1, 'I’m passionate about giving back to the community and creating positive change through volunteering. Over the years, I\'ve had the opportunity to work on various initiatives that support [specific causes or general community support. I believe that even small efforts can lead to big impacts when we work together. I\'m excited to be part of this community and look forward to contributing in any way I can!  ', 'Community Clean-ups,Tree Planting and Gardening,Recycling Drives,Housing Assistance,Public Libraries and Community Centers', 92, NULL, NULL, 1, 0, NULL),
(45, 'user', 'Chis', 'lawchris815@gmail.com', '2002-10-11', 'Penang', 'Perai', '$2y$10$OZ4u8aZYCDGepaRV.yu4g.ubgSvmLVw.SgkPFWklPCnfzpeapXSZm', 'd99d615b73616daddd16ce7e8cb9a715ee9240e075ea2df9edcd729b684c3c9b295d434e55c2c5e934d953dcdb88929cf81b', 1, NULL, NULL, 'uploads/45/profile/8b1bcdd903ffbbbc461448f0fd5a4130.jpg', '10 October 2024', 2, 0, 'asdasd', 'Recycling Drives,Housing Assistance', 100, NULL, NULL, 0, 0, NULL),
(47, 'user', 'asd asdf', 'lawkaijian123@gmail.com', '2024-10-23', 'Kuala Lumpur', 'Sri Petaling', '$2y$10$2u5oEmr3rlUPwzZhuYAa5.3QbWsni.Hn56fI8Z2vAqzWiejImZ2bK', 'ab875d07548437f2a2425af84adb82a68981e855c759f58da482a8f8759f798e35e914beefd1539f4cba41dd6e1b612a96fc', 0, NULL, NULL, NULL, '23 October 2024', 0, 0, NULL, NULL, 100, NULL, NULL, 0, 0, NULL),
(49, 'user', 'Chris', 'chrisng021109@gmail.com', '2004-06-30', 'Penang', 'Sungai Dua', '$2y$10$faUJiWSly32E.J/Mx9eu2uI/G9BKeNdQu3.IF5lDhFVZooa6NsHTa', 'd2f32040876dcd6424e389112eaecc395c8e06dee4c08414543145e15999a4b47233fd8674f1261d5966d98e3dbc08c4ce37', 1, NULL, NULL, NULL, '29 October 2024', 0, 0, NULL, 'Community Clean-ups', 100, NULL, NULL, 0, 0, NULL),
(51, 'admin', 'Admin', 'admin@gmail.com', '2015-11-05', 'Putrajaya', 'Putrajaya', '$2y$10$FFbsDbwTRt/EcTQnBEqC.OwXM80SIU6jFBHK8FaNcWoYjXVTGkTQe', '915a7a5d89031c627ed9269c456e5d9b2521cce65e08041dc9bbfec10f79370cb585f98c9caeac22080c0e6355e4aa754dd4', 1, NULL, NULL, NULL, '5 November 2024', 0, 0, NULL, NULL, 100, NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_certificates`
--

CREATE TABLE `user_certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_certificates`
--

INSERT INTO `user_certificates` (`id`, `user_id`, `file_name`, `uploaded_at`) VALUES
(19, 30, 'Law Kai Jian (Resume).pdf', '2024-11-08 09:17:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`event_id`,`attendance_date`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `event_applications`
--
ALTER TABLE `event_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_certificates`
--
ALTER TABLE `user_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=628;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `event_applications`
--
ALTER TABLE `event_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `user_certificates`
--
ALTER TABLE `user_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_applications`
--
ALTER TABLE `event_applications`
  ADD CONSTRAINT `event_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `event_applications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_certificates`
--
ALTER TABLE `user_certificates`
  ADD CONSTRAINT `user_certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
