-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 15, 2025 at 10:39 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `baby_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `birth_date` date NOT NULL,
  `age` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` float NOT NULL,
  `height` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `user_id`, `name`, `birth_date`, `age`, `weight`, `height`, `created_at`, `is_archived`) VALUES
(6, 5, 'ayla said', '2025-06-01', '4 أشهر و 23 يوم', 7.1, 64.5, '2025-06-03 14:50:41', 0),
(7, 5, 'youssef mohammed', '2025-03-10', '7 أشهر و 14 يوم', 9.5, 71, '2025-06-03 15:00:00', 0),
(8, 8, 'laila ahmad', '2024-12-01', '10 أشهر و 23 يوم', 10.2, 75.2, '2025-09-01 10:00:00', 0),
(9, 8, 'ali ahmad', '2023-01-15', 'سنة و 9 أشهر', 11.5, 80, '2025-09-01 10:01:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `child_vaccines`
--

CREATE TABLE `child_vaccines` (
  `id` int NOT NULL,
  `child_id` int NOT NULL,
  `vaccine_id` int NOT NULL,
  `due_date` date NOT NULL,
  `administered_date` date DEFAULT NULL,
  `status` enum('due','administered','missed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'due',
  `nurse_note` text COLLATE utf8mb4_general_ci,
  `certificate_filename` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nurse_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `child_vaccines`
--

INSERT INTO `child_vaccines` (`id`, `child_id`, `vaccine_id`, `due_date`, `administered_date`, `status`, `nurse_note`, `certificate_filename`, `nurse_id`, `created_at`) VALUES
(1, 6, 1, '2025-08-01', '2025-08-05', 'administered', 'تم إعطاء الجرعة الأولى من شلل الأطفال في الموعد', 'cert_65373a21b3d2b.pdf', 7, '2025-08-05 09:00:00'),
(2, 6, 2, '2025-08-01', '2025-08-05', 'administered', 'تم إعطاء الجرعة الأولى من الثلاثي البكتيري', 'cert_65373a21b3d2b.pdf', 7, '2025-08-05 09:00:00'),
(3, 7, 1, '2025-05-10', NULL, 'missed', 'لم يحضر الأهل في الموعد المحدد', NULL, NULL, '2025-04-01 08:00:00'),
(4, 6, 4, '2025-10-01', NULL, 'missed', 'فات موعد الجرعة الثانية من الروتا', NULL, NULL, '2025-09-01 09:00:00'),
(5, 8, 3, '2025-12-01', NULL, 'due', 'مستحق في نهاية العام الأول', NULL, NULL, '2025-10-24 09:00:00'),
(6, 6, 4, '2024-02-24', '2024-02-26', 'administered', 'qq', 'cert_68fb6e3fc6f6b.png', 7, '2025-10-24 12:17:03');

-- --------------------------------------------------------

--
-- Table structure for table `daily_activities`
--

CREATE TABLE `daily_activities` (
  `id` int NOT NULL,
  `child_id` int NOT NULL,
  `date` date NOT NULL,
  `activity_type` enum('breast_feed','formula_feed','nap','night_sleep','growth_record') COLLATE utf8mb4_general_ci NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `quantity` float DEFAULT NULL,
  `details` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `illness` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `medicine_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `medicine_dose` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `medicine_time` time DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_activities`
--

INSERT INTO `daily_activities` (`id`, `child_id`, `date`, `activity_type`, `start_time`, `end_time`, `duration`, `quantity`, `details`, `weight`, `height`, `temperature`, `illness`, `medicine_name`, `medicine_dose`, `medicine_time`, `note`, `created_at`) VALUES
(1, 6, '2025-10-20', 'growth_record', NULL, NULL, NULL, NULL, NULL, 6.8, 63.5, 36.8, NULL, NULL, NULL, NULL, 'كانت بصحة جيدة', '2025-10-20 10:00:00'),
(2, 6, '2025-10-23', 'growth_record', NULL, NULL, NULL, NULL, NULL, 7.1, 64.5, 38.5, 'سعال خفيف', 'مسكن', '2.5 مل', '12:00:00', 'الحرارة مرتفعة قليلاً، تم إعطاؤها مسكن', '2025-10-23 11:30:00'),
(3, 6, '2025-10-23', 'night_sleep', '22:00:00', '05:30:00', 7.5, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'نوم متقطع بسبب السعال', '2025-10-23 05:35:00'),
(4, 6, '2025-10-24', 'breast_feed', '08:00:00', NULL, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'رضاعة جيدة من الجهة اليمنى', '2025-10-24 08:15:00'),
(5, 7, '2025-10-15', 'growth_record', NULL, NULL, NULL, NULL, NULL, 9, 70, 36.6, NULL, NULL, NULL, NULL, 'فحص شهري', '2025-10-15 10:00:00'),
(6, 7, '2025-10-24', 'formula_feed', '10:00:00', NULL, NULL, 120, 'سيميلاك', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'شرب الكمية كاملة', '2025-10-24 10:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `professional_notes`
--

CREATE TABLE `professional_notes` (
  `id` int NOT NULL,
  `child_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_type` enum('doctor','nurse') COLLATE utf8mb4_general_ci NOT NULL,
  `note_content` text COLLATE utf8mb4_general_ci NOT NULL,
  `note_type` enum('general','sleep_advice') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professional_notes`
--

INSERT INTO `professional_notes` (`id`, `child_id`, `user_id`, `user_type`, `note_content`, `note_type`, `created_at`) VALUES
(1, 6, 7, 'nurse', 'الطفلة آيلا تحتاج لمتابعة يومية لدرجة حرارتها بسبب السعال الخفيف، ومراجعة الطبيب إذا استمر ارتفاع الحرارة.', 'general', '2025-10-23 12:00:00'),
(2, 7, 7, 'nurse', 'لتشجيع الطفل على النوم لفترة أطول ليلاً، يجب تحديد طقوس نوم ثابتة وتجنب التحفيز الشديد قبل النوم بساعة.', 'sleep_advice', '2025-10-20 15:30:00'),
(3, 6, 6, 'doctor', 'تم فحص الطفل، الحالة مستقرة. يرجى التركيز على الرضاعة الطبيعية.', 'general', '2025-10-24 07:00:00'),
(5, 6, 7, 'nurse', 'لتشجيع الطفل على النوم لفترة أطول ليلاً، يجب تحديد طقوس نوم ثابتة وتجنب التحفيز الشديد قبل النوم بساعة.', 'sleep_advice', '2025-10-24 12:11:57'),
(6, 6, 7, 'nurse', 'تم تسجيل/تعديل حالة تطعيم: الروتا (Rota) - جرعة 2. تم الإعطاء في 2024-02-26. . ملاحظة الممرض: qq', 'general', '2025-10-24 12:17:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `security_question` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `security_answer` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_type` enum('parent','doctor','nurse') COLLATE utf8mb4_general_ci DEFAULT 'parent',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `security_question`, `security_answer`, `user_type`, `created_at`) VALUES
(5, 'rama alhoshan (Parent)', 'rama@gmail.com', '09663258741', '$2y$10$cFNuXFuqjfbduG86CdYPJOATStvrLplfKGwVaGCnWhvx21zNEVl3u', 'اسم أول مدرسة التحقت بها؟', 'awaael', 'parent', '2025-06-03 14:49:56'),
(6, 'Dr. Ahmad (Pediatrician)', 'dr.ahmad@clinic.com', '0944658535', '', 'ما هو اسم صديق طفولتك المفضل؟', 'ali', 'doctor', '2025-10-23 06:00:00'),
(7, 'Nurse Huda', 'huda@clinic.com', '0988854455', '$2y$10$cFNuXFuqjfbduG86CdYPJOATStvrLplfKGwVaGCnWhvx21zNEVl3u', 'ما هو اسم والدتك قبل الزواج؟', 'fatima', 'nurse', '2025-10-23 06:01:00'),
(8, 'Abeer Salah (Parent)', 'abeer@gmail.com', '0501234567', '$2y$10$cFNuXFuqjfbduG86CdYPJOATStvrLplfKGwVaGCnWhvx21zNEVl3u', 'ما هو اسم صديق طفولتك المفضل؟', 'sara', 'parent', '2025-09-01 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `target_age` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `target_age`, `description`, `created_at`) VALUES
(1, 'شلل الأطفال الفموي (OPV) - جرعة 1', 'شهرين', 'الجرعة الأولى للقاح شلل الأطفال', '2025-10-23 06:05:00'),
(2, 'الثلاثي البكتيري (DTP) - جرعة 1', 'شهرين', 'الجرعة الأولى للقاح الخناق والكزاز والسعال الديكي', '2025-10-23 06:05:00'),
(3, 'لقاح الحصبة والنكاف والحصبة الألمانية (MMR)', 'سنة واحدة', 'الجرعة الأولى', '2025-10-23 06:05:00'),
(4, 'الروتا (Rota) - جرعة 2', '4 أشهر', 'الجرعة الثانية للقاح الروتا', '2025-10-24 06:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `child_vaccines`
--
ALTER TABLE `child_vaccines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `nurse_id` (`nurse_id`);

--
-- Indexes for table `daily_activities`
--
ALTER TABLE `daily_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `professional_notes`
--
ALTER TABLE `professional_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `child_vaccines`
--
ALTER TABLE `child_vaccines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `daily_activities`
--
ALTER TABLE `daily_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `professional_notes`
--
ALTER TABLE `professional_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `child_vaccines`
--
ALTER TABLE `child_vaccines`
  ADD CONSTRAINT `child_vaccines_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `child_vaccines_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `child_vaccines_ibfk_3` FOREIGN KEY (`nurse_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_activities`
--
ALTER TABLE `daily_activities`
  ADD CONSTRAINT `daily_activities_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professional_notes`
--
ALTER TABLE `professional_notes`
  ADD CONSTRAINT `professional_notes_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professional_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
