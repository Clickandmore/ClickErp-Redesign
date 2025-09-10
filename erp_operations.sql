-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Sep 07, 2025 at 11:12 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ussusalenjaz`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

CREATE TABLE `about_us` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_us`
--

INSERT INTO `about_us` (`id`, `created_at`, `updated_at`) VALUES
(1, '2023-12-24 08:07:58', '2023-12-24 08:07:58'),
(2, '2023-12-24 08:18:09', '2023-12-24 08:18:09'),
(3, '2023-12-24 08:22:13', '2023-12-24 08:22:13');

-- --------------------------------------------------------

--
-- Table structure for table `about_us_translations`
--

CREATE TABLE `about_us_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `about_us_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_us_translations`
--

INSERT INTO `about_us_translations` (`id`, `about_us_id`, `title`, `description`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'رحلتنا نحو الاستقدام: الإبداع في الخدمة', 'في شركة اسس الانجاز للاستقدام، نحن نؤمن بأن البيت هو المكان الذي ينعكس فيه الدفء والرعاية. نقدم خدمات الاستقدام بروح إبداعية، حيث نسعى جاهدين لتحويل البيوت إلى أماكن تنبض بالحياة والسعادة.', 'ar', '2023-12-24 08:07:58', '2023-12-24 08:07:58'),
(2, 1, 'Our Journey Towards Recruitment: Innovation in Service', 'At Achieve Recruitment Company, we believe that home is where warmth and care are reflected. We offer recruitment services with a creative spirit, striving to transform houses into vibrant and happy spaces.', 'en', '2023-12-24 08:07:58', '2023-12-24 08:07:58'),
(3, 2, 'لماذا نحن؟', 'في رحلتنا نحو تقديم أفضل خدمات الاستقدام، نجمع بين الاحترافية والإبداع. نحن هنا لتلبية احتياجات منازلكم بفريق من العمالة المدربة بعناية ومهنية، سعينا لتحقيق الاستقدام بمستوى جديد من الفخامة والابتكار.', 'ar', '2023-12-24 08:18:09', '2023-12-24 08:18:09'),
(4, 2, 'Why us ?', '\"In our journey towards providing the best recruitment services, we combine professionalism with creativity. We are here to meet the needs of your homes with a carefully trained and professional team, aiming to achieve recruitment at a new level of luxury', 'en', '2023-12-24 08:18:09', '2023-12-24 08:18:09'),
(5, 3, 'قيمنا:', '<p style=\"text-align: right; line-height: 1;\"><strong>الاحترافية:</strong></p>\r\n<p style=\"text-align: right; line-height: 1;\">نلتزم بتقديم خدمات استقدام عالية الجودة بمستوى عالٍ من الاحترافية</p>\r\n<p style=\"text-align: right; line-height: 1;\"><strong>الإبداع:</strong></p>\r\n<p style=\"text-align: right; line-height: 1;\">نسعى دومًا لتقديم حلاً مبتكرًا ومميزًا لاحتياجاتكم المنزلية</p>\r\n<p style=\"text-align: right; line-height: 1;\"><strong>الجودة:</strong></p>\r\n<p style=\"text-align: right; line-height: 1;\">نفهم أهمية جودة الخدمة في خلق تجربة مرضية ومستدامة</p>', 'ar', '2023-12-24 08:22:13', '2023-12-26 17:59:02'),
(6, 3, 'Values:', '<p style=\"line-height: 1;\"><strong>Professionalism</strong>:</p>\r\n<p style=\"line-height: 1;\">We are committed to providing high-quality recruitment services with a high level of professionalism.</p>\r\n<p style=\"line-height: 1;\"><strong>Creativity</strong>:</p>\r\n<p style=\"line-height: 1;\">We always strive to offer innovative and distinctive solutions for your household needs.</p>\r\n<p style=\"line-height: 1;\"><strong>Quality</strong>:</p>\r\n<p style=\"line-height: 1;\">We understand the importance of service quality in creating a satisfying and sustainable experience</p>', 'en', '2023-12-24 08:22:13', '2023-12-26 17:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sort` int DEFAULT '999',
  `role_id` int DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_developer` int NOT NULL DEFAULT '0',
  `sex_type` tinyint NOT NULL DEFAULT '0' COMMENT '0 is male and 1 is female',
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `phone`, `email`, `email_verified_at`, `password`, `image`, `admin_type`, `sort`, `role_id`, `lang`, `remember_token`, `deleted_at`, `created_at`, `updated_at`, `is_developer`, `sex_type`, `whatsapp`) VALUES
(1, 'مشرف', '0500680188', 'admin@admin.com', NULL, '$2y$12$6XEnhzshdqpLN4doo4zNEuZXT2Bo2opqXBL7HZumLzXBYCKhXBnb2', 'auth/profile/EbRzkvf1Kk1745397625.svg', 'super_admin', 999, NULL, NULL, NULL, NULL, '2023-12-20 14:25:00', '2025-07-03 18:33:17', 0, 0, '+201098380656');

-- --------------------------------------------------------

--
-- Table structure for table `app_visits`
--

CREATE TABLE `app_visits` (
  `id` bigint UNSIGNED NOT NULL,
  `android_count` int NOT NULL DEFAULT '0',
  `ios_count` int NOT NULL DEFAULT '0',
  `web_count` int NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cancel_contract_requests`
--

CREATE TABLE `cancel_contract_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cancel_contract_requests`
--

INSERT INTO `cancel_contract_requests` (`id`, `user_id`, `contract_id`, `notes`, `deleted_at`, `created_at`, `updated_at`) VALUES
(9, 17, 9, 'rtyryrty', NULL, '2024-07-29 08:07:30', '2024-07-29 08:07:30');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` bigint UNSIGNED NOT NULL,
  `complaint_type_id` bigint UNSIGNED DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci COMMENT 'تفاصيل الشكوى',
  `response` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','resolved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT 'أولوية الشكوى',
  `assigned_to` bigint UNSIGNED DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'المرفقات المتعددة',
  `user_id` bigint UNSIGNED NOT NULL,
  `complainant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم مقدم الشكوى',
  `complainant_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'رقم جوال مقدم الشكوى',
  `maid_passport` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'رقم جواز العاملة',
  `maid_nationality_id` bigint UNSIGNED DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'موضوع الشكوى',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ حل الشكوى',
  `resolved_by` bigint UNSIGNED DEFAULT NULL
) ;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `complaint_type_id`, `address`, `message`, `details`, `response`, `status`, `priority`, `assigned_to`, `file`, `attachments`, `user_id`, `complainant_name`, `complainant_phone`, `maid_passport`, `maid_nationality_id`, `subject`, `created_at`, `updated_at`, `is_read`, `resolved_at`, `resolved_by`) VALUES
(21, NULL, 'ارقام تواصل  وموقعكم لو مكتبكم حقيقي مو وهمي', 'كل وسائل التواصل لاتخدمنا  نحتاج ارقام تعمل للتواصل', NULL, NULL, 'resolved', 'medium', NULL, NULL, NULL, 810, NULL, NULL, NULL, NULL, NULL, '2025-02-27 14:16:31', '2025-05-17 20:14:12', 0, NULL, NULL),
(22, NULL, 'ssss', 'vbdxb', NULL, NULL, 'resolved', 'medium', NULL, NULL, NULL, 729, NULL, NULL, NULL, NULL, NULL, '2025-04-06 20:25:34', '2025-05-17 20:14:21', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `complaint_feedbacks`
--

CREATE TABLE `complaint_feedbacks` (
  `id` bigint UNSIGNED NOT NULL,
  `complaint_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `overall_rating` enum('very_satisfied','satisfied','neutral','dissatisfied','very_dissatisfied') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_speed` int DEFAULT NULL COMMENT 'سرعة الاستجابة 1-5',
  `solution_quality` int DEFAULT NULL COMMENT 'جودة الحل 1-5',
  `employee_professionalism` int DEFAULT NULL COMMENT 'احترافية الموظف 1-5',
  `communication_quality` int DEFAULT NULL COMMENT 'جودة التواصل 1-5',
  `would_recommend` enum('yes','maybe','no') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaint_types`
--

CREATE TABLE `complaint_types` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaint_types`
--

INSERT INTO `complaint_types` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'شكوى علي عقد', '2025-05-13 23:37:19', '2025-08-03 19:47:16'),
(2, 'شكوى علي عاملة', '2025-05-13 23:37:31', '2025-08-03 19:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` bigint UNSIGNED NOT NULL,
  `previous_contract_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'مسار ملف التوقيع',
  `warranty_status` enum('active','expired','not_applicable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_applicable',
  `warranty_start_date` timestamp NULL DEFAULT NULL,
  `warranty_end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `marketer_id` bigint UNSIGNED DEFAULT NULL,
  `maid_id` bigint UNSIGNED DEFAULT NULL,
  `required_maid_nationality_id` bigint UNSIGNED DEFAULT NULL,
  `required_maid_religion_id` bigint UNSIGNED DEFAULT NULL COMMENT 'الدين المطلوب للعاملة',
  `required_maid_age_min` int DEFAULT NULL COMMENT 'الحد الأدنى للعمر',
  `required_maid_age_max` int DEFAULT NULL COMMENT 'الحد الأقصى للعمر',
  `required_maid_experience_min` int DEFAULT NULL COMMENT 'الحد الأدنى للخبرة',
  `required_maid_job_title_id` bigint UNSIGNED DEFAULT NULL COMMENT 'المهنة المطلوبة',
  `required_maid_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات إضافية',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'maid',
  `contract_date` date DEFAULT NULL,
  `visa_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `msaned_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_infos`
--

CREATE TABLE `contract_infos` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `visa_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'رقم التأشيرة',
  `visa_hijry_date` date DEFAULT NULL COMMENT 'تاريخ التأشيرة الهجري',
  `visa_milad_date` date DEFAULT NULL COMMENT 'تاريخ التأشيرة الميلادي',
  `working_hours_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'نوع string علشان لو هيدخل مثلا 3 ساعات',
  `sponsorship_price` double DEFAULT NULL COMMENT 'مبلغ نقل الكفالة',
  `salary_after_sponsorship` double DEFAULT NULL COMMENT 'الراتب بعد الكفالة',
  `monthly_cost` double DEFAULT NULL COMMENT 'التكلفة الشهرية',
  `contract_duration` int DEFAULT NULL COMMENT 'مدة العقد',
  `contract_value` double DEFAULT NULL COMMENT 'قيمة العقد',
  `contract_date` date DEFAULT NULL COMMENT 'تاريخ العقد',
  `ingaz_from` date DEFAULT NULL COMMENT 'الانجاز من',
  `ingaz_to` date DEFAULT NULL COMMENT 'الانجاز الي',
  `previous_sponsorship` bigint UNSIGNED DEFAULT NULL COMMENT 'الكفيل السابق',
  `msaned_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'رقم مساند',
  `tafwed_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'رقم التفويض',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `maid_date_back` date DEFAULT NULL COMMENT 'تاريخ ارجاع العاملة',
  `salary_final_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'الحالة النهائية للراتب',
  `duration_from_enter` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'المدة منذ دخول المملكة',
  `back_reasons` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسباب ارجاع العاملة',
  `shelter_id` bigint UNSIGNED DEFAULT NULL,
  `receipt_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'مصدر الاستلام',
  `wants_to_work` enum('yes','no','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown' COMMENT 'رغبة العمل في عقود داخل الإيواء: نعم، لا، غير محدد',
  `arrival_date` date DEFAULT NULL COMMENT 'تاريخ الوصول',
  `is_available_for_instant` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'متاحة للاستلام الفوري على الموقع'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_payments`
--

CREATE TABLE `contract_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_date` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_return_requests`
--

CREATE TABLE `contract_return_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_status_logs`
--

CREATE TABLE `contract_status_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cookies`
--

CREATE TABLE `cookies` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visits_count` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cookies`
--

INSERT INTO `cookies` (`id`, `name`, `value`, `visits_count`, `created_at`, `updated_at`) VALUES
(1, 'web_cookie', '01901fd3-b933-40cc-96c9-2385534e2783', 1, '2025-08-19 18:15:30', '2025-08-19 18:15:30'),
(2, 'web_cookie', '867ecc29-fb82-4d51-81d1-5a29e23142a4', 1, '2025-08-20 08:52:13', '2025-08-20 08:52:13'),
(3, 'web_cookie', '526d4f75-f1ee-438a-bc52-d2e7c818fa4b', 1, '2025-08-21 12:28:12', '2025-08-21 12:28:12'),
(4, 'web_cookie', '0f6f3459-d0fd-4026-8b44-a7a5e21809ff', 1, '2025-08-23 19:43:24', '2025-08-23 19:43:24'),
(5, 'web_cookie', 'ef9bd9bf-69d2-4ce6-929a-5fcd8cd856af', 1, '2025-08-28 06:10:19', '2025-08-28 06:10:19'),
(6, 'web_cookie', '112f44d7-e344-451c-9609-56528ce7f606', 1, '2025-08-28 06:34:38', '2025-08-28 06:34:38'),
(7, 'web_cookie', '679d3d40-e53c-49b4-940f-3b330162dd53', 1, '2025-08-30 07:09:47', '2025-08-30 07:09:47'),
(8, 'web_cookie', '8197c2d2-f6c7-4b89-9ed3-e082dce15cb6', 1, '2025-08-30 07:16:19', '2025-08-30 07:16:19'),
(9, 'web_cookie', 'ce8569dc-e96e-4675-873f-8f5fb2229ed4', 1, '2025-08-30 07:22:38', '2025-08-30 07:22:38'),
(10, 'web_cookie', '64ffdc0a-4d01-4d86-87be-69c2ca324fe9', 1, '2025-08-30 07:25:57', '2025-08-30 07:25:57'),
(11, 'web_cookie', '601ac12b-445c-41e8-aa59-cef64516eb4a', 1, '2025-08-30 07:26:09', '2025-08-30 07:26:09'),
(12, 'web_cookie', '4ed36348-ddcc-4408-b388-544b29c1c96e', 1, '2025-08-30 07:28:37', '2025-08-30 07:28:37'),
(13, 'web_cookie', 'bf56e970-e469-4f95-b861-1fa1e7dca4eb', 1, '2025-08-30 07:36:56', '2025-08-30 07:36:56'),
(14, 'web_cookie', 'db990666-0791-4abe-9e6c-65d708a13e52', 1, '2025-08-30 07:53:14', '2025-08-30 07:53:14'),
(15, 'web_cookie', '9f8db2ff-5e72-47c9-b19d-a183e4713a6c', 1, '2025-08-30 08:03:43', '2025-08-30 08:03:43'),
(16, 'web_cookie', '803ca94e-7eb6-4426-bc82-c899a98b2e2b', 1, '2025-08-30 08:55:17', '2025-08-30 08:55:17'),
(17, 'web_cookie', '7db6deab-a302-409e-9592-0c9989e3a546', 1, '2025-08-30 11:31:41', '2025-08-30 11:31:41'),
(18, 'web_cookie', 'f60d935a-91eb-49f2-8dce-1f98203a63e9', 1, '2025-08-30 11:59:41', '2025-08-30 11:59:41'),
(19, 'web_cookie', '14745ac4-8177-4964-8d68-f8a2e39246f3', 1, '2025-08-30 20:17:39', '2025-08-30 20:17:39'),
(20, 'web_cookie', '249c3d8a-4041-4c0b-abe0-d51a68c7c9c8', 1, '2025-08-31 14:45:23', '2025-08-31 14:45:23'),
(21, 'web_cookie', '3ad03bfb-de81-4088-ac3b-b4a2b3d4764b', 1, '2025-09-01 21:36:50', '2025-09-01 21:36:50'),
(22, 'web_cookie', '4205290c-6c5c-4356-9d8d-233873f4ce85', 1, '2025-09-02 03:19:00', '2025-09-02 03:19:00'),
(23, 'web_cookie', 'e2c48ef5-0bdb-4742-b5b5-b29250d117b8', 1, '2025-09-02 09:55:27', '2025-09-02 09:55:27'),
(24, 'web_cookie', '8f3a2d86-cc48-46d9-abd8-bd9b2252b262', 1, '2025-09-02 14:16:20', '2025-09-02 14:16:20'),
(25, 'web_cookie', '27103499-a329-40b3-aed6-210f04a5ce44', 1, '2025-09-02 14:42:46', '2025-09-02 14:42:46'),
(26, 'web_cookie', '08e42d01-a22e-4abf-b96b-a7fa8085e7da', 1, '2025-09-02 14:45:59', '2025-09-02 14:45:59'),
(27, 'web_cookie', '399570cc-4b19-4952-b655-28d9eff0ead0', 1, '2025-09-02 14:55:28', '2025-09-02 14:55:28'),
(28, 'web_cookie', '7df9d05f-f1cb-48f6-bbcb-6154e6d35f61', 1, '2025-09-02 18:09:35', '2025-09-02 18:09:35'),
(29, 'web_cookie', '8c3bcce4-b3ec-41ce-9b84-45fcaaab0144', 1, '2025-09-02 18:51:21', '2025-09-02 18:51:21'),
(30, 'web_cookie', '63655a80-b94f-4e61-bd60-c389931466bf', 1, '2025-09-03 15:27:20', '2025-09-03 15:27:20'),
(31, 'web_cookie', '39c045e5-2cf8-468f-9b25-48174c573b98', 1, '2025-09-03 16:37:55', '2025-09-03 16:37:55'),
(32, 'web_cookie', '33bf4998-4921-42cb-8678-3fc0de660a90', 1, '2025-09-03 17:48:22', '2025-09-03 17:48:22'),
(33, 'web_cookie', '25fbcea5-0e9b-43ca-a392-b4b21b3f7a19', 1, '2025-09-06 09:37:55', '2025-09-06 09:37:55'),
(34, 'web_cookie', 'd49ee265-2ff1-4093-a497-947a42c1e630', 1, '2025-09-06 14:07:52', '2025-09-06 14:07:52'),
(35, 'web_cookie', '980c616d-0f90-418d-a5da-fa1e57155c8f', 1, '2025-09-07 02:26:04', '2025-09-07 02:26:04'),
(36, 'web_cookie', 'fa54d310-9209-49d2-9d18-eb52775da7e6', 1, '2025-09-07 03:07:07', '2025-09-07 03:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint UNSIGNED NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_symbol` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name_ar`, `name_en`, `code`, `phone_code`, `currency`, `currency_symbol`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'المملكة العربية السعودية', 'Saudi Arabia', 'SA', '966', 'SAR', '﷼', 1, 1, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(2, 'مصر', 'Egypt', 'EG', '20', 'EGP', 'ج.م', 1, 2, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(3, 'الإمارات العربية المتحدة', 'United Arab Emirates', 'AE', '971', 'AED', 'د.إ', 1, 3, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(4, 'الكويت', 'Kuwait', 'KW', '965', 'KWD', 'د.ك', 1, 4, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(5, 'قطر', 'Qatar', 'QA', '974', 'QAR', 'ر.ق', 1, 5, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(6, 'البحرين', 'Bahrain', 'BH', '973', 'BHD', 'د.ب', 1, 6, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(7, 'عمان', 'Oman', 'OM', '968', 'OMR', 'ر.ع', 1, 7, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(8, 'الأردن', 'Jordan', 'JO', '962', 'JOD', 'د.ا', 1, 8, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(9, 'المغرب', 'Morocco', 'MA', '212', 'MAD', 'د.م.', 1, 9, '2025-05-27 00:30:03', '2025-05-27 00:30:03'),
(10, 'تونس', 'Tunisia', 'TN', '216', 'TND', 'د.ت', 1, 10, '2025-05-27 00:30:03', '2025-05-27 00:30:03');

-- --------------------------------------------------------

--
-- Table structure for table `deportation_procedures`
--

CREATE TABLE `deportation_procedures` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `trip_id` bigint UNSIGNED DEFAULT NULL,
  `trip_scheduled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تم جدولة رحلة السفر',
  `salary_received` tinyint(1) DEFAULT NULL COMMENT 'هل تم استلام الراتب',
  `salary_received_date` date DEFAULT NULL COMMENT 'تاريخ استلام الراتب',
  `has_passport` tinyint(1) DEFAULT NULL COMMENT 'هل معها جواز السفر',
  `passport_date` date DEFAULT NULL COMMENT 'تاريخ استلام الجواز',
  `has_deportation_letter` tinyint(1) DEFAULT NULL COMMENT 'خطاب الترحيل',
  `deportation_letter_date` date DEFAULT NULL COMMENT 'تاريخ خطاب الترحيل',
  `has_settlement_session` tinyint(1) DEFAULT NULL COMMENT 'جلسة التسوية',
  `settlement_date` date DEFAULT NULL COMMENT 'تاريخ جلسة التسوية',
  `has_final_exit` tinyint(1) DEFAULT NULL COMMENT 'خروج نهائي',
  `final_exit_date` date DEFAULT NULL COMMENT 'تاريخ الخروج النهائي',
  `attachments` text COLLATE utf8mb4_unicode_ci COMMENT 'المرفقات - JSON',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات',
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'حالة الإجراءات',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ بدء الإجراءات',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ اكتمال الإجراءات',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `completed_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `procedures_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deportation_procedure_logs`
--

CREATE TABLE `deportation_procedure_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `deportation_procedure_id` bigint UNSIGNED NOT NULL,
  `admin_id` bigint UNSIGNED NOT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `develop_settings`
--

CREATE TABLE `develop_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `copyrights` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `portfolio_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `licences_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `support_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `faq_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `home_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `about_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation_show` tinyint(1) NOT NULL DEFAULT '0',
  `login_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `istiqdam_contracts`
--

CREATE TABLE `istiqdam_contracts` (
  `id` bigint UNSIGNED NOT NULL,
  `istiqdam_contract_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_id` bigint UNSIGNED DEFAULT NULL,
  `package_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `beneficiary_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beneficiary_birthdate` date NOT NULL,
  `beneficiary_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at_istiqdam` datetime DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled','expired','pending_approval','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `contact_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `address_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(10,2) DEFAULT NULL,
  `commitments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `work_permit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_maid_id` bigint UNSIGNED DEFAULT NULL,
  `maid_assigned_at` datetime DEFAULT NULL,
  `special_specification` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `last_api_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `last_synced_at` datetime DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

CREATE TABLE `job_titles` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`id`, `image`, `created_at`, `updated_at`) VALUES
(21, 'images/JobTitle/7MHnEbHqcA1703149717.webp', '2023-12-21 10:08:37', '2023-12-21 10:08:37'),
(22, 'images/JobTitle/wPUVPXhO8S1752674343.webp', '2023-12-21 10:09:35', '2025-07-16 16:59:03'),
(26, 'images/JobTitle/KNihtCB7rV1703150641.webp', '2023-12-21 10:24:01', '2023-12-21 10:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `job_titles_translations`
--

CREATE TABLE `job_titles_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `job_title_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles_translations`
--

INSERT INTO `job_titles_translations` (`id`, `job_title_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(41, 21, 'عامله منزلية', 'ar', '2023-12-21 10:08:37', '2023-12-21 10:08:37'),
(42, 21, 'Housemaid', 'en', '2023-12-21 10:08:37', '2023-12-21 10:08:37'),
(43, 22, 'سائق خاص', 'ar', '2023-12-21 10:09:35', '2023-12-21 10:09:35'),
(44, 22, 'Driver', 'en', '2023-12-21 10:09:35', '2023-12-21 10:09:35'),
(51, 26, 'مربية اطفال', 'ar', '2023-12-21 10:24:01', '2023-12-21 10:24:01'),
(52, 26, 'Babysitter', 'en', '2023-12-21 10:24:01', '2023-12-21 10:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `job_types`
--

CREATE TABLE `job_types` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_types`
--

INSERT INTO `job_types` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'images/image.png', '2023-12-24 07:25:53', '2023-12-24 07:25:53'),
(2, 'images/image.png', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(3, 'images/image.png', '2023-12-24 07:25:54', '2023-12-24 07:25:54');

-- --------------------------------------------------------

--
-- Table structure for table `job_types_translations`
--

CREATE TABLE `job_types_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `job_type_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_types_translations`
--

INSERT INTO `job_types_translations` (`id`, `job_type_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'استقدام', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(2, 1, 'Recruitment request', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(3, 2, 'ايجار عمالة', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(4, 2, 'Labor hire', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(5, 3, 'إستلام فورى', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(6, 3, 'Labor transfer', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54');

-- --------------------------------------------------------

--
-- Table structure for table `maids`
--

CREATE TABLE `maids` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality_id` bigint UNSIGNED NOT NULL,
  `passport_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `price` double NOT NULL DEFAULT '0',
  `salary` double NOT NULL DEFAULT '0',
  `religion_id` bigint UNSIGNED NOT NULL,
  `age` double(8,2) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `cv_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `available_at` date NOT NULL DEFAULT '2024-01-18',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `experience_years` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `experience_status` enum('has_experience','no_experience') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no_experience' COMMENT 'حالة الخبرة: سبق لها العمل أم لا',
  `cv_preview` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_id` bigint UNSIGNED DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_expiry_date` date DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ;

--
-- Dumping data for table `maids`
--

INSERT INTO `maids` (`id`, `name`, `nationality_id`, `passport_num`, `price`, `salary`, `religion_id`, `age`, `birth_date`, `cv_file`, `available`, `is_pinned`, `available_at`, `status`, `is_show`, `created_at`, `updated_at`, `experience_years`, `experience_status`, `cv_preview`, `office_id`, `image`, `height`, `weight`, `id_number`, `id_expiry_date`, `skills`, `languages`) VALUES
(1323, 'MST SALINA BHAGUM', 5, 'A02628285', 0, 900, 3, 37.00, '1988-04-02', '', 0, 0, '2025-07-02', 'completed', 1, '2025-07-02 19:02:09', '2025-08-04 03:03:21', '2', 'has_experience', NULL, 4, 'images/Maid/xv4XhZIBWO1751472129.jpg', '152', '62', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1324, 'LOVLY BEGUM', 5, 'B00826521', 0, 1100, 3, 37.00, '1987-09-06', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:12:27', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/K9xjc1G47v1751472747.jpg', '161', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1325, 'LAKE AKTER', 5, 'A03861121', 0, 1100, 3, 30.00, '1994-10-02', '', 0, 0, '2025-07-02', 'completed', 1, '2025-07-02 19:19:19', '2025-07-23 20:37:31', '2', 'has_experience', NULL, 4, 'images/Maid/Lu5dTsMVhU1751473159.jpg', '158', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1326, 'MUSHAMMAD YASMIN', 5, 'EL0045150', 0, 1100, 3, 34.00, '1990-10-20', '', 0, 0, '2025-07-02', 'completed', 0, '2025-07-02 19:23:43', '2025-08-04 03:29:07', '4', 'has_experience', NULL, 4, 'images/Maid/RQaLMCWtT71751473423.jpg', '158', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1327, 'PARVIN BEGUM', 5, 'EK0199541', 0, 1100, 3, 35.00, '1990-01-01', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:27:03', '2025-07-30 17:42:19', '2', 'has_experience', NULL, 4, 'images/Maid/WYpCebp9s51751473623.jpg', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1328, 'RIYA AKTHAR', 5, 'A16251895', 0, 1100, 3, 35.00, '1990-02-06', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 19:32:14', '2025-07-26 09:07:16', '3', 'has_experience', NULL, 4, 'images/Maid/9COriD8Y5h1751473934.png', '152', '55', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1329, 'DIPA', 5, 'B00513277', 0, 1100, 3, 33.00, '1992-01-01', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:34:33', '2025-08-03 10:54:31', '4', 'has_experience', NULL, 4, 'images/Maid/AFRLMNrLBI1751474073.png', '155', '55', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1330, 'MST RUBI BEGUM', 5, 'A03376204', 0, 1100, 3, 30.00, '1995-03-10', '', 1, 0, '2025-07-02', 'available', 0, '2025-07-02 19:37:05', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/1xsJ3Mu2Gp1751474225.png', '152', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1331, 'MST SABINA KHATUN', 5, 'A04971720', 0, 1100, 3, 29.00, '1995-10-05', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 19:39:40', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/q7pkJOXicd1751474380.png', '158', '75', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1332, 'SAMIMA', 5, 'EJ0702058', 0, 1100, 3, 39.00, '1986-02-03', '', 0, 0, '2025-07-02', 'Reserved', 1, '2025-07-02 19:43:53', '2025-08-10 02:32:27', '8', 'has_experience', NULL, 4, 'images/Maid/MWMhJRtK281751474633.png', '152', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1333, 'SALMA BEGUM', 5, 'A03212437', 0, 1100, 4, 34.00, '1990-10-25', '', 1, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:46:00', '2025-07-14 18:04:55', '2', 'has_experience', NULL, 4, 'images/Maid/WCIcuAl4nM1751474760.png', '158', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1334, 'MST MULLIKA KHATUN', 5, 'A14915906', 0, 1100, 3, 33.00, '1992-05-01', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:48:14', '2025-08-17 17:18:58', '2', 'has_experience', NULL, 4, 'images/Maid/J61SLmv1lM1751474894.png', '158', '74', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1335, 'MST FULBANU BEGUM', 5, 'A02617426', 0, 1100, 3, 42.00, '1983-01-01', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 19:50:06', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/0wSVXAE9Mh1751475006.png', '158', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1336, 'ASMA BEGUM', 5, 'A01242894', 0, 1100, 3, 39.00, '1985-09-09', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 19:52:03', '2025-08-14 19:20:58', '2', 'has_experience', NULL, 4, 'images/Maid/FdTKuoeHLS1751475123.png', '152', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1337, 'MST AKLIMA BEGUM', 5, 'A00815966', 0, 1100, 4, 37.00, '1987-11-02', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 19:54:03', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/a8pEMQzgL91751475243.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1338, 'MST ALTAMAN NESA', 5, 'A09005097', 0, 1100, 3, 40.00, '1985-04-26', '', 0, 0, '2025-07-02', 'Reserved', 1, '2025-07-02 19:56:41', '2025-08-02 02:40:08', '3', 'has_experience', NULL, 4, 'images/Maid/CV5dzASKPz1751475401.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1339, 'MST MOMENA AKTER', 5, 'A03285322', 0, 1100, 3, 37.00, '1988-06-01', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 19:58:46', '2025-07-26 09:07:16', '1', 'has_experience', NULL, 4, 'images/Maid/E2kcPQ8St61751475526.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1340, 'FARJANA KHATUN', 5, 'A03403405', 0, 1100, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:01:05', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/no163CfKVB1751475665.png', '152', '52', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1341, 'SUFIA BEGUM', 5, 'A02721623', 0, 1100, 3, 37.00, '1988-01-18', '', 1, 0, '2025-07-02', 'completed', 1, '2025-07-02 20:03:19', '2025-08-04 09:21:01', '2', 'has_experience', NULL, 4, 'images/Maid/ahLDK9zd961751475799.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1342, 'MST RINA AKTER', 5, 'A14037430', 0, 900, 3, 30.00, '1994-12-01', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 20:06:21', '2025-07-29 18:16:20', '0', 'no_experience', NULL, 4, 'images/Maid/t3TfGxm8FY1751475981.png', '155', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1343, 'SHIRINA BEGAM', 5, 'A15354481', 0, 900, 3, 29.00, '1996-06-20', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 20:08:09', '2025-08-11 19:33:35', '0', 'no_experience', NULL, 4, 'images/Maid/3Us65zVX5b1751476089.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1344, 'MST ROJINA KHATUN', 5, 'A06516206', 0, 900, 3, 33.00, '1992-06-05', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:10:05', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/7efU76d0ys1751476205.png', '161', '65', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1345, 'MISS MOMADA KHATUN', 5, 'A16196499', 0, 900, 3, 32.00, '1993-01-03', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:12:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/WKsF3Dy9YJ1751476328.png', '158', '63', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1346, 'MST ARJINA KHATUN', 5, 'A08774819', 0, 900, 3, 36.00, '1989-03-03', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:14:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/HUPui777Xd1751476496.png', '152', '59', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1347, 'JAHANARA BEGUM', 5, 'A17323718', 0, 900, 3, 31.00, '1994-01-01', '', 0, 0, '2025-07-02', 'Reserved', 1, '2025-07-02 20:16:43', '2025-08-15 18:46:25', '0', 'no_experience', NULL, 4, 'images/Maid/CDUdpPeAFs1751476603.png', '158', '56', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1348, 'MST BEAUTY BEGUM', 5, 'A17542268', 0, 900, 3, 35.00, '1990-01-01', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:18:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/RyeWC6kECL1751476702.png', '158', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1349, 'AHIMA KHATUN', 5, 'A17859921', 0, 900, 3, 27.00, '1998-02-12', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:20:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/LIU17VFKOk1751476822.png', '155', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1350, 'MST RUMA AKTER', 5, 'A15148087', 0, 900, 3, 37.00, '1987-08-25', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:21:59', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/7lgiUgifPU1751476919.png', '155', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1351, 'MARZINA', 5, 'A00348083', 0, 900, 3, 31.00, '1994-03-10', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:24:31', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/qEXAp1xXsM1751477071.png', '155', '56', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1352, 'MST BULBULI', 5, 'A17734133', 0, 900, 3, 37.00, '1987-11-25', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:26:00', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/kwPBO8KoBQ1751477160.png', '152', '50', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1353, 'MST MAJEDA BEGUM', 5, 'A17749621', 0, 900, 3, 39.00, '1986-03-03', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:27:32', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/UjMdIrrPyU1751477252.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1354, 'MISS AKLIMA KHATUN', 5, 'A08866243', 0, 900, 3, 34.00, '1990-07-25', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:28:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/s8llih6F0e1751477333.png', '155', '54', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1355, 'SAYERA BEGUM', 5, 'A17388646', 0, 900, 3, 37.00, '1988-01-06', '', 0, 0, '2025-07-02', 'reserved', 1, '2025-07-02 20:31:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/6GAuMtqHBD1751477474.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1356, 'AYASHA BIBI', 5, 'A17196854', 0, 900, 3, 31.00, '1994-01-01', '', 1, 0, '2025-07-02', 'available', 1, '2025-07-02 20:32:30', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/SE9451MFHK1751477550.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1357, 'MARY NAFULA NYONGESA', 3, 'BK899365', 0, 900, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:24:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/l6zgyfPEoQ1751549069.png', '170', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1358, 'ALICENT JEPCHIRCHIR TARUS', 3, 'BK838713', 0, 900, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:26:25', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/d9eieRhgUW1751549185.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1359, 'ANN MUTHONI', 3, 'BK767860', 0, 900, 1, 41.00, '1984-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:28:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/Uj5Zati7g41751549327.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1360, 'MELVINE ANYANJE LIBOYI', 3, 'BK962268', 0, 900, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:31:30', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/ar1k8BMjtn1751549490.png', '170', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1361, 'MAUREEN CHEBET', 3, 'AK1816194', 0, 900, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:33:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/JGEckdrD6e1751549629.png', '152', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1362, 'CAROLYNE NELIMA SHITANDA', 3, 'BK436868', 0, 900, 1, 39.00, '1986-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:37:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/3pvLcUH6Aa1751549823.png', '170', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1363, 'LILIAN NELIMA', 3, 'BK980804', 0, 900, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:39:27', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/T4T8Wo49ua1751549967.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1364, 'ASKAH NYANSIABOKA ONWOYO', 3, 'BK991830', 0, 900, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:41:27', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/VYY0H34YIj1751550087.png', '170', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1365, 'LINET KERUBO OCHARO', 3, 'AK0597822', 0, 900, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:43:59', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/HSb163CqA01751550239.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1366, 'HILDAH NAFULA MASINDE', 3, 'BK375444', 0, 900, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:46:09', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/CdI4VgOBdj1751550369.png', '155', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1367, 'CENTRINE NEKESA SIENYA', 3, 'BK988630', 0, 900, 1, 35.00, '1990-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 16:50:21', '2025-08-18 17:56:24', '0', 'no_experience', NULL, 8, 'images/Maid/VbUPx8gYcK1751550621.png', '170', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1368, 'PURITY KANYIVU KASAVI', 3, 'BK778941', 0, 900, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:52:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/cqjy3lxFpl1751550769.png', '158', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1369, 'AGNES CHENGO KAHINDI', 3, 'BK942469', 0, 900, 1, 35.00, '1990-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 16:54:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/lePRCAOP9l1751550868.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1370, 'FAITH KEMUNCHE GUTWA', 3, 'AK0614632', 0, 1000, 1, 24.00, '2001-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 17:05:58', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/ABdVf2f0JX1751551558.png', '167', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1371, 'JANE KANGU OTSULA', 3, 'AK234753', 0, 1000, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:08:01', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/wW3mhbXtmC1751551681.png', '167', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1372, 'DOLIS BOSIBORI NYANGAU', 3, 'AK0567439', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:10:42', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/62TbNv5B0u1751551842.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1373, 'ZIPPORAH BONARERI NYABERI', 3, 'AK0899717', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:12:28', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/FLwnqJJCff1751551948.png', '167', '65', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1374, 'SOFIA RIMA MBAABU', 3, 'AK0806133', 0, 1000, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:17:26', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/cqerB5S5x91751552246.png', '164', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1375, 'RISPER NYAMBEKI ANGASA', 3, 'AK0670031', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:21:16', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/ZCkGRJAO6h1751552476.png', '164', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1376, 'PURITY SAYO GWONEKI', 3, 'BK236820', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-03', 'available', 0, '2025-07-03 17:23:31', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/SuAZSQVolr1751552611.png', '170', '68', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1377, 'GEOGINAH MUENI MARTIN', 3, 'AK1103546', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:27:46', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/TmQcb9w4Ee1751552866.png', '167', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1378, 'LUCIANA MALEMBA MWAFUSI', 3, 'AK0789799', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:30:01', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/q8zVTaA45E1751553001.png', '164', '64', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1379, 'EUNICE NYABOKE OBUYA', 3, 'CK111868', 0, 1000, 1, 23.00, '2002-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 17:32:06', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/BJ8Zn4Q8VH1751553126.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1380, 'EUCABETH OSEBE MOSE', 3, 'BK166542', 0, 1000, 1, 38.00, '1987-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:33:57', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/sm1a1kL8zR1751553237.png', '152', '73', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1381, 'GLADYS KEMUNTO TOEL', 3, 'BK391181', 0, 1000, 1, 39.00, '1986-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 17:35:57', '2025-07-29 17:54:55', '2', 'has_experience', NULL, 8, 'images/Maid/wEzqHQaobO1751553357.png', '164', '63', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1382, 'LYDIAH NYABOKE OMOTE', 3, 'BK242859', 0, 1000, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 17:38:02', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/q5nAUldvQK1751553482.png', '164', '63', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1383, NULL, 8, '', 0, 1000, 1, 36.00, '1989-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 18:12:46', '2025-07-31 17:40:27', '3', 'has_experience', NULL, 11, 'images/Maid/VCp5fNHI9E1751555566.png', '167', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1384, 'DOMESTIC HELPEP', 8, 'OP0270724', 0, 1000, 3, 37.00, '1988-01-03', '', 0, 0, '2025-07-03', 'Reserved', 1, '2025-07-03 18:17:17', '2025-07-27 23:12:56', '2', 'has_experience', NULL, 11, 'images/Maid/r9fQ6ITtcC1751555837.png', '167', '62', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1385, 'KWIZERA RANDY DIANE', 8, 'P00155219', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:21:12', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 11, 'images/Maid/WTZZHcjz8m1751556072.png', '164', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1386, 'NIYONZIMA ELIANE', 8, 'OP0236367', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:24:01', '2025-07-26 09:07:16', '3', 'has_experience', NULL, 11, 'images/Maid/tbfkTtCZ451751556241.png', '164', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1387, 'NIZIGIYIMANA EVELYNE', 8, 'P00116304', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:26:22', '2025-07-26 09:07:16', '4', 'has_experience', NULL, 11, 'images/Maid/XFlo8AIhF71751556382.png', '167', '53', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1388, 'BIKORIMANA FLORIDE', 8, 'P00071522', 0, 1000, 1, 25.00, '2000-01-01', '', 0, 0, '2025-07-03', 'Reserved', 1, '2025-07-03 18:28:28', '2025-08-19 17:40:48', '2', 'has_experience', NULL, 11, 'images/Maid/RLAzzcP9AX1751556508.png', '164', '53', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1389, 'IRAKOZE BELYSE', 8, 'P00132816', 0, 1000, 3, 21.00, '2003-12-31', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:31:01', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 11, 'images/Maid/dO6hpNoCBt1751556661.png', '167', '53', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1390, 'NDAYIKUNDA JEANNETTE', 8, 'P00104420', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-03', 'reserved', 1, '2025-07-03 18:33:37', '2025-07-14 17:19:03', '2', 'has_experience', NULL, 11, 'images/Maid/g0jKEGsRg91751556817.png', '164', '56', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1392, 'ZEYNEB SIRAJI MOHAMED', 6, 'EP8458765', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:49:34', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/pikzMcaOsz1751557774.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1393, 'SENBETE KEDIRO BAJA', 6, 'EQ1256447', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:55:52', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NtJRUjamlH1751558152.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1394, 'HAJITU HIRPHATO GARICHO', 6, 'EQ1849716', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:57:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/o1htNfgwTz1751558228.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1395, 'REHIMA MUHAMED JABO', 6, 'EP8342031', 0, 1000, 3, 33.00, '1992-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 18:59:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/yBV9ueqLHN1751558342.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1396, 'MOMINAMUHAMMEDESSA', 6, 'EQ1138594', 0, 1000, 3, 29.00, '1996-01-01', '', 1, 0, '2025-07-03', 'available', 0, '2025-07-03 19:00:31', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/PVl5dUHKgU1751558431.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1397, 'FATUMA SEYIDA ADEM', 6, 'EP8288987', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:02:06', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/fCKAMmOukB1751558526.png', '162', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1398, 'MEREMMUHAMEDTESEMA', 6, 'EQ1270714', 0, 1000, 4, 28.00, '1997-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:03:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/S0lUVvypbO1751558619.png', '167', '57', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1399, 'ALFIYA ABABIYA ABASANBI', 6, 'EP8744839', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:04:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/lBFROz2Yc91751558690.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1400, 'ASHYA WORESO CHURA', 6, 'EP7169491', 0, 1000, 4, 27.00, '1998-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:06:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/j0lTxPlBdq1751558776.png', '158', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1401, 'TEYIBA DEKENA KIMO', 6, 'EQ1288094', 0, 1000, 3, 27.00, '1998-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:07:42', '2025-07-26 16:37:58', '0', 'no_experience', NULL, 6, 'images/Maid/4LbIHJVmmX1751558862.png', '159', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1402, 'REHIMA BENCHA KUMBI', 6, 'EP8944548', 0, 1000, 4, 27.00, '1998-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:08:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/omDF3pwCo81751558936.png', '163', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1403, 'SEMIRA TESHITE BATI', 6, 'EQ1395274', 0, 1000, 4, 26.00, '1999-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:10:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UXEKBWxxns1751559017.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1404, 'SEMIRA ABDU ASSEN', 6, 'EQ2543633', 0, 1000, 3, 26.00, '1999-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:11:31', '2025-08-10 18:26:46', '0', 'no_experience', NULL, 6, 'images/Maid/L5znLoaFWI1751559091.png', '160', '54', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1405, 'ZEBURA ALI ABAYE', 6, 'EQ2536746', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:12:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/BMfeTUITJ01751559165.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1406, 'KEDIJA OLI GURMU', 6, 'EP8868646', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:14:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VwxbqHvYk11751559285.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1407, 'AJIBA ABABIYA ABAGERO', 6, 'EQ2046282', 0, 1000, 3, 30.00, '1995-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:16:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Y9XH2Vhmg71751559368.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1408, 'KAMERU KEMBELO KOLBO', 6, 'EP9237564', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:17:27', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DGbBhCWwxl1751559447.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1409, 'KAWUNI ABAOLI ABAFITA', 6, 'EP9393626', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:28:37', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/78BCsFnWbc1751560117.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1410, 'FOZIYA NASIR JEMAL', 6, 'EQ2435096', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-03', 'available', 0, '2025-07-03 19:29:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/zQIL8bmzDk1751560194.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1411, 'HAYAT HAILE MEHAMMED', 6, 'EQ2818123', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:31:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/GgZ1mEWa3C1751560296.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1412, NULL, 6, '', 0, 1000, 3, 22.00, '2003-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:32:41', '2025-08-16 18:12:28', '0', 'no_experience', NULL, 6, 'images/Maid/QMhlZ6KS2W1751560361.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1413, 'REHIMA RAYA ABASAMBI', 6, 'EQ2829857', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:33:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/eMHqeUzQgO1751560434.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1414, 'MEDINA AHAMED MEHAMEDNUR', 6, 'EQ2763313', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-03', 'available', 0, '2025-07-03 19:34:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/viL3icKj5O1751560494.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1415, 'AYISHA BULTUM ABABULGU', 6, 'EQ1155569', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:36:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/1DUWk0BW3C1751560561.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1416, 'SHAMSIYA HIDO BULO', 6, 'EQ2042141', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:37:32', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/JpxESfUS2I1751560652.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1417, 'IMAN SULEMAN JEMAL', 6, 'EQ1838690', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-03', 'available', 1, '2025-07-03 19:39:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/B4XZtwmKeN1751560742.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1418, 'LOMI RABU KABETO', 6, 'EQ1251464', 0, 1000, 3, 26.00, '1999-01-01', '', 0, 0, '2025-07-03', 'reserved', 1, '2025-07-03 19:40:00', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/XhmHgepSV71751560800.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1419, 'ELIZABETH NDILA MUSYOKA', 3, 'CK87611', 0, 900, 1, 30.00, '1995-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 16:35:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/BOoLV9vDsU1751722546.png', '173', '65', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1420, 'PHYLES MORAGWA KERIGA', 3, 'AK1890183', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 16:37:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/1vc0zIXsw11751722659.png', '164', '55', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1421, NULL, 3, '', 0, 900, 1, 21.00, '2004-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 16:39:47', '2025-08-18 19:36:01', '0', 'no_experience', NULL, 8, 'images/Maid/bf5gLHpYxe1751722787.png', '167', '56', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1422, 'TERESIA WANJIRU MWANGI', 3, 'BK821073', 0, 900, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 16:45:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/WgqKyCsCuf1751723102.png', '152', '63', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1423, 'JANE KEMUNTO BIAGE', 3, 'BK612087', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 16:47:38', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/z6YquRtSVH1751723258.png', '152', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1424, 'ROSE WAIRIMU KAMAU', 3, 'BK929024', 0, 900, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 16:49:48', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/BgjAGFiKEd1751723388.png', '164', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1425, 'VENIC N’YANSIABOKA ATIKA', 3, 'BK980592', 0, 900, 1, 21.00, '2004-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 16:51:32', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/5LY1P93ecb1751723491.png', '158', '55', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1426, 'NANCY ADHIAMBO OTIENO', 3, 'BK971150', 0, 900, 4, 23.00, '2002-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 16:53:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/59kGIDLkAx1751723601.png', '167', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1427, 'JULIANA WAWIRA NJENGA', 3, 'BK1022310', 0, 900, 1, 38.00, '1987-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 16:55:48', '2025-08-09 19:03:13', '0', 'no_experience', NULL, 8, 'images/Maid/EDmvF1ApNc1751723748.png', '167', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1428, 'CHRISTINE CHELIMA NGOBIRI', 3, 'BK829804', 0, 900, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 16:58:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/ksUd1cmtm01751723909.png', '161', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1429, 'EUNICE IVERIA OLUM', 3, 'BK604589', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:00:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/z8h1hNWXsg1751724047.png', '161', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1430, 'FAITH PERESIAN', 3, 'BK512030', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 17:02:42', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/pE77ST9euY1751724162.png', '170', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1431, 'HYLINE BOCHERE ARUTA', 3, 'BK888388', 0, 900, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:06:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/FKsgvGWEvo1751724409.png', '167', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1432, 'JOAN CHERONO', 3, 'AK1724460', 0, 900, 3, 29.00, '1996-01-01', '', 0, 0, '2025-07-05', 'Reserved', 1, '2025-07-05 17:10:47', '2025-08-13 01:58:43', '0', 'no_experience', NULL, 8, 'images/Maid/ESsezrrqMF1751724647.png', '161', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\",\\\"mother\\\"]\"'),
(1433, 'LYDIAH KASYOKA NZOMO', 3, 'AK1190226', 0, 900, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:12:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/ZJp4wzKrlv1751724753.png', '160', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1434, 'NANCY NJOKI KARIUKI', 3, 'AK1484195', 0, 900, 4, 42.00, '1983-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:14:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/2kR03PgYci1751724868.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1435, 'STELLA JERONO', 3, 'BK375185', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:16:12', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/KtN1BYjHRJ1751724972.png', '160', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1436, 'SUSAN JERUTO', 3, 'AK1724738', 0, 900, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:17:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/4mLTbER4Ts1751725076.png', '160', '54', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1437, 'ZIPPORAH NJOKI MBUGUA', 3, 'AK1852211', 0, 900, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 17:23:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/KwjFOXjEI01751725390.png', '165', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1438, 'FLORA NJERI MWENDA', 3, 'BK840664', 0, 900, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 17:25:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/5UpcxUmuMy1751725544.png', '165', '61', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1439, 'DAMARIS NJOKI NJIRU', 3, 'BK838023', 0, 900, 1, 40.00, '1985-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:28:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/BDRfQknoqF1751725696.png', '150', '57', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1440, 'IRENE NANJALA WAFULA', 3, 'AK1770931', 0, 900, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:30:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/M9GY2eU5ZB1751725846.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1441, 'LUCY WANGUI MURATHA', 3, 'BK837531', 0, 900, 1, 26.00, '1998-11-19', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:32:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/V6VtGu4BiT1751725973.png', '152', '57', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1442, 'LYDIA MUTHONI MWANGI', 3, 'BK1003765', 0, 900, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:34:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/yvndUbRY3x1751726066.png', '161', '59', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1443, 'MOUREEN BONYO NYAKEKE', 3, 'AK16744494', 0, 900, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:36:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/OZU3XSlFMR1751726201.png', '160', '61', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1444, 'HYLINE NYAKANGI AGWATAI', 3, 'BK998232', 0, 900, 4, 21.00, '2004-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 17:38:01', '2025-07-30 18:07:37', '0', 'no_experience', NULL, 8, 'images/Maid/zBIgQiSTFb1751726281.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1446, 'ZIPPORAH MURUGI MBURU', 3, 'AK0731853', 0, 1000, 1, 44.00, '1981-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:46:48', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/1HzV3KHWA71751726808.png', '160', '64', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1449, 'WINNIE ADHIAMBO AYOO', 3, 'AK1134099', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:50:56', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/6boYqJxh5y1751727056.png', '160', '59', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1450, 'SOPHIA WANGARI MUTHAMA', 3, 'BK263233', 0, 1000, 1, 48.00, '1977-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:54:50', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/opPr3gwTNO1751727290.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1451, 'MARTHA WANJIRA  MUNYI', 3, 'AK0863806', 0, 1000, 1, 44.00, '1981-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 17:58:58', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/kniJkCiSaZ1751727538.png', '164', '68', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1452, 'MILDRED OUMA OLANG.', 3, 'AK0331949', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:02:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/nYSpEdg1331751727737.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1453, 'ZIPPORAH NYAMBEKI ABUYA', 3, 'BK168248', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:04:15', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/eCY654SaJu1751727855.png', '162', '63', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1454, 'ZIPPORAH WANJIRU', 3, 'AK1276513', 0, 1000, 1, 33.00, '1992-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 18:07:08', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/lT86TniwML1751728028.png', '160', '63', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1455, 'TABITHA WANJIRU THUO', 3, 'BK179110', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:09:44', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/TyDt2RZAse1751728184.png', '170', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1456, 'SHARON JERUTO CHIRCHIR', 3, 'AK0994723', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:12:06', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/bbl6IvVELu1751728326.png', '175', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1457, 'ROSELINE ALUOCH OTIENO', 3, 'AK1076493', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:14:02', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/KwnnphxBMG1751728441.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1458, 'MERCY JEPKEMOI', 3, 'BK274020', 0, 1000, 3, 29.00, '1996-01-01', '', 0, 0, '2025-07-05', 'Reserved', 1, '2025-07-05 18:16:24', '2025-08-16 14:16:46', '2', 'has_experience', NULL, 8, 'images/Maid/gcMi5juWuQ1751728584.png', '161', '59', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1460, 'MARY NJERI KARUA', 3, 'AK0625305', 0, 1000, 1, 41.00, '1984-01-09', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:19:04', '2025-07-26 09:07:16', '1', 'has_experience', NULL, 8, 'images/Maid/FaN49o1XYq1751728744.png', '167', '54', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1461, 'MARGARET CHRISTABEL KISACH', 3, 'AK1123554', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:20:32', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/pQ2dRDleFL1751728832.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1462, 'LYNN JERROP LIMO', 3, 'AK1178447', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:21:57', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/457Jen4n311751728917.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1463, 'JANE WANJIRU NJUGUNA', 3, 'AK1050778', 0, 1000, 1, 38.00, '1987-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:23:52', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/uutIAnGEPM1751729032.png', '152', '56', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1464, 'GRACE GESARE GEORGE', 3, 'AK0852733', 0, 1000, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:26:06', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/8rSF10pgYM1751729166.png', '155', '59', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1465, 'ANN NJERI JOHN', 3, 'AK0968816', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 18:27:43', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/I4v7ywYEOg1751729263.png', '160', '65', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1466, 'ASHA KHAMISI RACHUO', 3, 'AK0473384', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:23:39', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/vCMALrsqtu1751732619.png', '175', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1467, 'PATIENCE FURAHA NGOWA', 3, 'CK52620', 0, 1000, 3, 41.00, '1984-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:25:23', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/g4nImWP7sy1751732723.png', '160', '41', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1468, 'SELINA SIDI WANJE', 3, 'AK1332161', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:37:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 8, 'images/Maid/GB5P8ljotM1751733422.png', '167', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1469, 'MWANAIDI CHAKA NDORO', 3, 'CK72271', 0, 1000, 3, 35.00, '1990-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:39:09', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/0QMtzNTGMl1751733549.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1470, 'LINET KADENGE MANGI', 3, 'BK382724', 0, 1000, 3, 35.00, '1990-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:41:09', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/k2QsT2zIOh1751733669.png', '167', '74', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1472, 'ANNA PAUL MULEI', 3, 'AK1106066', 0, 1000, 3, 37.00, '1988-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:43:49', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/0qCN4TkJUS1751733829.png', '167', '61', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1473, 'ALIMA OMITO NYANGWESO', 3, 'AK0178480', 0, 1000, 3, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:45:14', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 8, 'images/Maid/CjlO4CRlzs1751733914.png', '155', '64', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1478, 'NAIRUBA WINIEFRED', 4, 'B00550260', 0, 900, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:56:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/EAe0oVhveS1751734607.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1479, 'KISAKYE PROSSY', 4, 'B00583722', 0, 900, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:57:48', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/4PcBmWJ5vU1751734668.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1480, 'NAMUKWAYA ALLEN', 4, 'B00506088', 0, 900, 1, 21.00, '2004-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 19:59:05', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/2ZTbmLOdhk1751734745.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1481, 'MUKOYA JULIET', 4, 'B00533439', 0, 900, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:00:31', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/Om4xt4E5lG1751734831.png', '164', '111', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1482, 'KABASYABA JOVANIS', 4, 'B00488570', 0, 900, 1, 38.00, '1987-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:01:33', '2025-08-11 17:25:31', '0', 'no_experience', NULL, 14, 'images/Maid/pTSQm3KtE01751734893.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1483, 'CHEBET RISPER', 4, 'B00447531', 0, 900, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:02:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/CXvYTx0UvG1751734959.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1484, 'SIRIKIRE PROSSY', 4, 'B00620568', 0, 900, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:03:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/D6530JhcWF1751735034.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1485, 'NABIRYERECHEAL', 4, 'B00123285', 0, 900, 1, 21.00, '2004-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 20:05:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/xAVKnoUsQP1751735103.png', '166', '72', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1486, 'NAMULONDO ANGELLA', 4, 'B00068292', 0, 900, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:06:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/iQl3QOL1vz1751735173.png', '162', '81', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1487, 'MAKUNE MARGRET', 4, 'A00550850', 0, 900, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 20:07:15', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/73tW5n5AFT1751735235.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1488, 'AMULEN ROSE', 4, 'B00560283', 0, 900, 1, 29.00, '1996-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:08:30', '2025-07-27 16:45:30', '0', 'no_experience', NULL, 14, 'images/Maid/nDcAeBqDfi1751735310.png', '157', '50', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1489, 'NADEMUN MATRIDA', 4, 'B00583970', 0, 900, 1, 40.00, '1985-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:09:30', '2025-07-28 18:41:51', '0', 'no_experience', NULL, 14, 'images/Maid/EGOWLLoUdS1751735370.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1490, 'NAMULI REBECCA', 4, 'B00371223', 0, 1200, 1, 45.00, '1980-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:11:12', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/4B3TunWDN91751735472.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1491, NULL, 4, '', 0, 1200, 1, 39.00, '1986-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:12:38', '2025-07-27 17:01:28', '2', 'has_experience', NULL, 14, 'images/Maid/eQG9DUB44d1751735558.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"mother\\\"]\"'),
(1492, 'NASSOLO MARIAM', 4, 'B00621361', 0, 900, 3, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:14:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/zcOxMIThVt1751735641.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"mother\\\"]\"'),
(1493, 'NAMBIRO SUMAYA', 4, 'B00641544', 0, 900, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-05', 'available', 0, '2025-07-05 20:15:06', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/weqhi6Yohr1751735706.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\"]\"'),
(1494, 'MUGIDE NURIA', 4, 'A01016011', 0, 900, 3, 23.00, '2002-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:16:19', '2025-08-18 17:48:15', '0', 'no_experience', NULL, 14, 'images/Maid/jbfLyzO2g81751735779.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1495, 'NAMUGGA SARAH', 4, 'B00470417', 0, 900, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:17:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/1LmHUBvGvQ1751735853.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"');
INSERT INTO `maids` (`id`, `name`, `nationality_id`, `passport_num`, `price`, `salary`, `religion_id`, `age`, `birth_date`, `cv_file`, `available`, `is_pinned`, `available_at`, `status`, `is_show`, `created_at`, `updated_at`, `experience_years`, `experience_status`, `cv_preview`, `office_id`, `image`, `height`, `weight`, `id_number`, `id_expiry_date`, `skills`, `languages`) VALUES
(1496, 'MUTESI HAWA', 4, 'B00270590', 0, 900, 3, 40.00, '1985-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:19:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/gWas9RR4er1751735966.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1497, 'ATEGEKA GRACE', 4, 'B00637754', 0, 900, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:20:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/KmshGY8fuD1751736056.png', '164', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1498, 'KENGONZI MONICA', 4, 'B00637752', 0, 900, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:22:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/yJnRKr4KK91751736134.png', '167', '70', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1499, 'ATEGEKA  MANGADALENA ABWOOLI', 4, 'B00452518', 0, 900, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:24:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/ViZhMGGXVr1751736244.png', '167', '55', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1500, 'NYANGOMA GRACE', 4, 'B00309099', 0, 900, 1, 24.00, '2001-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:25:25', '2025-08-10 17:34:23', '0', 'no_experience', NULL, 14, 'images/Maid/eXYQw1wL131751736325.png', '155', '54', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1501, 'ABIGABA GEORGIA', 4, 'B00289172', 0, 900, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:27:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/l794YOxc011751736437.png', '161', '68', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1502, 'TUMUSIIME ESTHER', 4, 'B00308708', 0, 900, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:30:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/kWJwCrJIBa1751736616.png', '149', '54', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1503, 'AINEMBABAZI EMMILLY  FLORENCE', 4, 'B00308704', 0, 900, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:31:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/5iE3PvcARM1751736707.png', '161', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1504, 'KYOSIMIRE AGNES', 4, 'A01056460', 0, 900, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:32:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/EDVme8kmqM1751736778.png', '146', '52', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1505, 'KYOMUHANGI HOPE', 4, 'B00283104', 0, 900, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:34:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/10voGiKK0W1751736854.png', '155', '50', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1506, 'KYOMUHANGI  DINAVENCE', 4, 'B00452443', 0, 900, 1, 34.00, '1991-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:35:28', '2025-08-04 18:54:05', '0', 'no_experience', NULL, 14, 'images/Maid/pAvglB1HLM1751736928.png', '152', '57', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1507, 'NAIGA AGNES', 4, 'B00364462', 0, 900, 1, 30.00, '1995-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:36:35', '2025-08-06 16:47:37', '0', 'no_experience', NULL, 14, 'images/Maid/T4Wqz2pbq41751736995.png', '152', '50', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1508, 'NYAMAIZI MOREEN', 4, 'A01042967', 0, 900, 1, 32.00, '1993-01-01', '', 0, 0, '2025-07-05', 'reserved', 1, '2025-07-05 20:37:45', '2025-08-10 16:18:23', '0', 'no_experience', NULL, 14, 'images/Maid/1sKM0b7bpp1751737065.png', '152', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1509, 'NANKUUMBI TEDDY', 4, 'A00508077', 0, 900, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:38:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/Uo8VvDtMwU1751737137.png', '152', '78', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1510, 'KUNIHIRA FLORANCE', 4, 'B00465429', 0, 900, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:40:09', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/0qPD2dCDdx1751737209.png', '152', '50', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1511, 'KYEGENEYA JETRIDA', 4, 'B00531659', 0, 900, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:41:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/LiZA0PoO3r1751737286.png', '161', '60', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1512, 'NALUMU BETTY', 4, 'B00308025', 0, 900, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-05', 'available', 1, '2025-07-05 20:43:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/PjwuXEoQrB1751737390.png', '155', '64', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1513, 'NAMUKOSE MAUREEN', 4, 'B00014244', 0, 1200, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:15:40', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/Jf072L2CGe1751807740.png', '162', '75', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1514, 'NAKIRYOWA ESTHER', 4, 'A00570457', 0, 1200, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:17:08', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/Em0psyJO9j1751807828.png', '159', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\",\\\"mother\\\"]\"'),
(1515, 'NAIGAGA DIANAH', 4, 'A00759697', 0, 900, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:19:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/Thkb0B9pSK1751807964.png', '163', '173', '', NULL, '\"[null]\"', '\"[\\\"english\\\",\\\"mother\\\"]\"'),
(1516, 'TULISIKLA TRUST', 4, 'A00874545', 0, 1200, 1, 25.00, '2000-01-09', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:21:39', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/cEh10JZvCs1751808099.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1517, 'MUSOKI PHONA', 4, 'A00685588', 0, 1200, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:23:31', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/rz3txadtNt1751808211.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1518, 'KOBUSINGYE HILDA', 4, 'A00064872', 0, 1200, 1, 25.00, '2000-01-01', '', 0, 0, '2025-07-06', 'reserved', 1, '2025-07-06 16:25:59', '2025-07-28 18:37:40', '2', 'has_experience', NULL, 14, 'images/Maid/Y3a4xGAYR11751808359.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1519, 'NAMWASE BRENDA', 4, 'A00667520', 0, 1200, 4, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:27:37', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/D0iPNb8PiH1751808457.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1520, 'AKURATA RECHEAL', 4, 'A00656073', 0, 1200, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:29:57', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/Irquhxx98J1751808597.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1521, 'NAMULI SARAH', 4, 'A00349532', 0, 1200, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:31:28', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/6AqLVe7tIo1751808688.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1522, 'NABBOSA AMINAH NALONGO', 4, 'A00009302', 0, 1200, 3, 39.00, '1986-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:34:11', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/kvF6ftkGJZ1751808851.png', '165', '70', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1523, 'NAMAYANJA HASIFAH', 4, 'B00248850', 0, 1200, 3, 38.00, '1987-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:35:19', '2025-07-26 09:07:16', '1', 'has_experience', NULL, 14, 'images/Maid/mjWYVVgD8a1751808919.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1524, 'NANTABO AISHA', 4, 'A00892708', 0, 1200, 3, 31.00, '1994-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:36:49', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/503spoMhlY1751809009.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1525, 'NAKANWAGI AISHA', 4, 'A00435938', 0, 1200, 1, 27.00, '1998-01-01', '', 0, 0, '2025-07-06', 'reserved', 1, '2025-07-06 16:38:18', '2025-08-17 17:11:44', '2', 'has_experience', NULL, 14, 'images/Maid/hpTZyLZ5eV1751809098.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1526, 'NINSIIMA SHAMIM', 4, 'A00231391', 0, 1200, 3, 38.00, '1987-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:39:20', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/y3cTeDIEAd1751809160.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"english\\\"]\"'),
(1527, 'SADIYA AHMED HAMDO', 6, 'EP8621162', 0, 1200, 3, 29.00, '1996-01-01', '', 0, 0, '2025-07-06', 'reserved', 1, '2025-07-06 16:45:32', '2025-07-26 09:07:16', '4', 'has_experience', NULL, 6, 'images/Maid/51OfEXB4311751809532.png', '157', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1528, 'NURITU JEBISA KUBSA', 6, 'EQ2168651', 0, 1200, 3, 37.00, '1988-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:48:12', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/cCtBaIzs3t1751809692.png', '156', '50', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1529, 'SEADA MUSTEFA HAMICHA', 6, 'EP8870730', 0, 1200, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:50:22', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/k7IeeYA0pr1751809822.png', '157', '51', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1530, 'GENO ABDULETIF', 6, 'EP7063399', 0, 1200, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'reserved', 1, '2025-07-06 16:55:03', '2025-07-14 18:09:58', '2', 'has_experience', NULL, 6, 'images/Maid/C8VIr5kluf1751810103.png', '157', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1531, 'ZEKIYA ABDELA AHMED', 6, 'EQ2165687', 0, 1200, 3, 36.00, '1989-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 16:57:31', '2025-07-26 09:07:16', '6', 'has_experience', NULL, 6, 'images/Maid/34d5WnuAHE1751810251.png', '167', '59', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1532, 'MARTA EWNETU BAYIBGN', 6, 'EQ1969680', 0, 900, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 16:59:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UVWtjkyMnj1751810376.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1533, 'YESHIHAREG YIMAM TEGEGN', 6, 'EQ2571375', 0, 1200, 1, 45.00, '1980-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:00:58', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/zYWICMYs9A1751810458.png', '161', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1534, 'YECHALE GIZAWE GOREFU', 6, 'EP7026099', 0, 1200, 1, 25.00, '2000-02-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:04:25', '2025-07-26 09:07:16', '3', 'has_experience', NULL, 6, 'images/Maid/TW6RI3i4nf1751810665.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1535, 'MESKEREM ABERA KASSIE', 6, 'EP7624093', 0, 1200, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:05:53', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/jWIfRw0uE61751810753.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1536, 'MELSHIW MELESE ADMASU', 6, 'EP7291998', 0, 1200, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:07:09', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/56gyNTzkfP1751810829.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1537, 'FITSUM ASEFA ENGDA', 6, 'EP8556832', 0, 1200, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:09:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/JIuf5JXGdH1751810997.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1538, 'GENET SISAY MARYE', 6, 'EQ2857802', 0, 1200, 1, 38.00, '1987-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:11:15', '2025-07-26 09:07:16', '3', 'has_experience', NULL, 6, 'images/Maid/tdA8ScR0rM1751811075.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1539, 'BOGALECH SOLOMON ERGENO', 6, 'EP8255491', 0, 1200, 1, 35.00, '1990-01-01', '', 0, 0, '2025-07-06', 'reserved', 1, '2025-07-06 17:12:24', '2025-07-29 17:45:48', '3', 'has_experience', NULL, 6, 'images/Maid/oFqDbQoPux1751811144.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1540, 'ABEBECH ASEFA ANAGAW', 6, 'EP6947817', 0, 1200, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 17:13:34', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/5zPwrphOtG1751811214.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1541, 'MEKDES YITBAREK MARIYE', 6, 'EP8146547', 0, 1200, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:18:11', '2025-07-26 09:07:16', '3', 'has_experience', NULL, 6, 'images/Maid/TqSpjesGdT1751811491.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1542, 'MESERET WORKNEH HABTAMU', 6, 'EQ2003310', 0, 1200, 1, 42.00, '1983-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:20:53', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/mm4CCZw99t1751811653.png', '157', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1543, 'HABTIYE MESFEN HABTAMU', 6, 'EQ2853622', 0, 1200, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:22:42', '2025-07-26 09:07:16', '8', 'has_experience', NULL, 6, 'images/Maid/OQ3JyFpW7D1751811762.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1544, NULL, 6, '', 0, 1200, 1, 38.00, '1987-01-01', '', 0, 0, '2025-07-06', 'reserved', 1, '2025-07-06 17:25:31', '2025-07-28 16:41:39', '2', 'has_experience', NULL, 6, 'images/Maid/GN6Nm6mLdY1751811931.png', '157', '54', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1545, 'DEMOZ ALEMAYEHU KEBEDE', 6, 'EQ1169776', 0, 1200, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 17:26:54', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/OIjXrpYCqE1751812014.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"arabic\\\",\\\"amharic\\\"]\"'),
(1546, 'WERKNESH MUCHEYE GELAW', 6, 'EQ1143973', 0, 1000, 1, 26.00, NULL, '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 17:31:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/ihoOxnoMMY1751812278.png', '160', '57', NULL, NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1547, 'ALIMA KETEMA GEMEDA', 6, 'EP8881211', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:32:42', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/OntSaI9NgW1751812362.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1548, 'ABEBO ASEFA DIRIBA', 6, 'EQ1852254', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:34:52', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DHoV8H2Xjx1751812492.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1549, 'MURSHIDA ABEBE GUTE', 6, 'EP9228163', 0, 1000, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:36:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UE1ITXcvGj1751812577.png', '167', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1550, 'ZEWDITU ADMASU ABERA', 6, 'EQ2030552', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:37:27', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/1eJAtnQXVG1751812647.png', '161', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1551, 'TAGESECH SHEMEBO WOLAMO', 6, 'EP9309111', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:38:43', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/abdI3FBhzR1751812723.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1552, 'TARIKE TAMIRE DINKAGO', 6, 'EQ2074007', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:40:11', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/P3WAL7ExgL1751812811.png', '161', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1553, 'ASNEKECH MEKESE BERATA', 6, 'EP9235789', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:42:19', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VLYM76UFb81751812939.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1554, 'HABTAMNESH MARKOS ULISO', 6, 'EP8313212', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:43:35', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/gC7VUmrLI41751813015.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1555, 'MEKDES WECHAMO KELBORE', 6, 'EQ2173070', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:44:51', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3OrG1vM7bk1751813091.png', '160', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1556, 'MEKDES BADE TUNBELO', 6, 'EP9190881', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:46:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/wASCpEi7Zt1751813184.png', '167', '60', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1557, 'FENET KABEBA GUTA', 6, 'EQ1258606', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:48:32', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/ccNZhpmxnn1751813312.png', '162', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1558, 'GETINESH EREGANA ERESULO', 6, 'EQ1939324', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:50:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/wlY5VyjrqU1751813453.png', '165', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1559, 'KONGIT ESAYAS ATUMO', 6, 'EQ2482117', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:52:34', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/roDO6iEKe81751813554.png', '161', '59', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1560, 'LALISE BANTI AYALE', 6, 'EP6874402', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:54:25', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/xh7rUZpHZO1751813665.png', '161', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1561, 'SHIBRE TAYE BAHRU', 6, 'EQ1369409', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:55:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/eUP6YzSFqQ1751813755.png', '164', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1562, 'FANAYE REGASA BARI', 6, 'EP8588828', 0, 1000, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:57:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/pEHhtuTNDb1751813836.png', '165', '58', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1563, 'GENO TEMAM ROBA', 6, 'EP9378110', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 17:58:19', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/qLNRdbsubu1751813899.png', '161', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1564, 'DEGEFECH YOHANIS DINIKGO', 6, 'EQ1853707', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 17:59:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/wDQHCCcxqa1751813966.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1565, 'MESERET RAMETO LERAGO', 6, 'EP9302149', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 18:02:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/0ToIGvtXg01751814149.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1577, 'YESYIT TARKE ENDALAMEW', 6, 'EP9287130', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:14:52', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/zgMCNp7Pd21751814892.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1578, 'ABEBAYEHU GADISA GEBRE', 6, 'EQ2183220', 0, 1000, 1, 30.00, '1995-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:15:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/h2XlBH42eu1751814956.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1579, 'SENAYT GALECHO MANA', 6, 'EQ2189140', 0, 1000, 1, 25.00, '2000-02-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:16:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/ACaBA3Fte31751815010.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1580, 'MULU GIRMA TESHOME', 6, 'EP9084104', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:20:23', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/OLKP2wKilS1751815223.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1581, 'BEREKET TESFAYE ACHAMO', 6, 'EQ1402542', 0, 1000, 1, 21.00, '2004-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:21:43', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/HUmsZBmE0I1751815303.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1582, 'BIRTUKAN DEBERO ABIYO', 6, 'EQ1937971', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:22:48', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/qL6qxloi5B1751815368.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1583, 'FIREHIWOT NIGUSE TEKLEHAYMANOT', 6, 'EP8906877', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:23:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Ov8vm38WQW1751815421.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1584, 'MESELECH MISHAMO ERGANO', 6, 'EP9242025', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:24:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/jN0vq1JMQ51751815481.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1585, 'TESFANESH ASHURO HANKO', 6, 'EQ1957472', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:25:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/qrcVfxv6xR1751815540.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1586, 'ZENEBECH ZEGEYE AMAN', 6, 'EQ1277629', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:26:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/RrVm1V9hDL1751815596.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1587, 'DESTA KEBETA BONTI', 6, 'EP8396891', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:27:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/WiqxUx2l4n1751815649.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1588, 'EP9217441', 6, 'EP9217441', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:30:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FhrR2KCTC11751815828.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1589, 'BUSKE BADHO HUMNESA', 6, 'EQ2047759', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:31:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/KFysn5a7mK1751815893.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1590, 'EMNET MISHAMO ERGANO', 6, 'EP9301603', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:32:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/RtQDjSWDGI1751815956.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1591, 'DULE BEGNA FITE', 6, 'EP9328313', 0, 1000, 1, 40.00, '1985-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:33:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tH05Vf1mCZ1751816016.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1592, 'ENATNESH ASNAKEW ASTATKIE', 6, 'EP9178558', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:34:35', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/ZQKhvsY9l31751816075.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1593, 'FENTAYE FESEHA GELAW', 6, 'EQ2032663', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:35:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/cSrgZbfN9T1751816121.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1594, 'CHALTU NAGASA BAKALA', 6, 'EQ1851132', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:36:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NJlp0IMbVd1751816184.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1595, 'TURIST TESHOME TAMIRU', 6, 'EP9062821', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:37:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/XcbBLE7Olj1751816242.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1596, 'ALEMITU ASSEFA SORI', 6, 'EP8301417', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:38:15', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4xZOMTt3iA1751816295.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1597, 'MEKDES SISAY ABERA', 6, 'EQ2199994', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:39:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/SpOFqXRYDH1751816357.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1598, 'TESFANESH HAILU WOLE', 6, 'EQ2034863', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:40:15', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mrtOegBrXK1751816415.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1599, 'ABEBECH TILAYE GIZAW', 6, 'EQ1171570', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:41:37', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/hgGRCRdKNa1751816497.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1600, 'AYANTU BEKELE KENENI', 6, 'EP8363646', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:42:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/rdmuCcvlJO1751816560.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1601, 'BILEN AYALEW AMBAW', 6, 'EQ1248055', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:43:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vm8Fsn0z7U1751816619.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1602, 'GENZEB EWNU GEDAMU', 6, 'EQ1416068', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 18:44:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/82hxafB8fh1751816695.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1603, 'MAHLET WORKU DEMSIE', 6, 'EQ1404987', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:45:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/wIi6TwdTMt1751816757.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1604, 'KEBEBUSH KETEMA GUNTA', 6, 'EP8384800', 0, 1000, 1, 30.00, '1995-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:47:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/S7pAzezJRW1751816822.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1605, 'AMARECH SHARA TOLA', 6, 'EQ2080636', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:50:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/23ieBXtT7i1751817004.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1606, 'KALKIDAN FENTAW KASIE', 6, 'EQ1843016', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:51:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/sBf0CJozGp1751817077.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1607, 'CHALTU KABITO FAYERA', 6, 'EP8310446', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:52:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VDYCw8hKCF1751817134.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1608, 'AYU CHALCHISA WAMI', 6, 'EP8203110', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:53:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/KCls1cOWqo1751817193.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1609, 'GENET MARKOS HADERO', 6, 'EP8788299', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:54:11', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Tw9qTIci7l1751817251.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1610, 'BELENESH HAILEMIKAEL WACHAMO', 6, 'EQ2025511', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:55:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/sQvdsuWS7S1751817317.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1611, 'MULU NEGESSE MEGERSA', 6, 'EQ1955543', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:56:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vo2S9bPe4P1751817376.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1612, 'ELSABET GANTA GASHO', 6, 'EP9041983', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:57:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/g934fBC0fw1751817434.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1613, 'KIBNESH AMENO HANKEBO', 6, 'EP9389488', 0, 100, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:58:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/cnkqZdXlsM1751817484.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1614, 'MASHO TESHOME BEZU', 6, 'EP8950704', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 18:59:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NSaocpZqok1751817558.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1615, 'ADANU BIRU SOBOKA', 6, 'EP8867033', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:02:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NHQTI00lyt1751817741.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1616, 'FEYISE ZEWUDE HEYI', 6, 'EP8883594', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-06', 'available', 0, '2025-07-06 19:08:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/J7IUm3CI1y1751818120.png', '160', '55', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1617, 'BEZAWIT MISGAN TEGEGN', 6, 'EQ1015188', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:09:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/riLnOw3HW21751818189.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1618, 'DIRBE BIRHANU DESALGNE', 6, 'EQ2061244', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:10:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/YdQvFJnC5C1751818254.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1619, 'LIDIYA ALEMAYEHU AMBO', 6, 'EQ1357323', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:12:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/xqtYOE6wzw1751818341.png', '160', '60', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1620, 'TINSAYE KEBEDE AMBO', 6, 'EQ1357327', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:13:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/f4M3aVb6kq1751818430.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1621, 'DINKNESH KEBEDE KALETEMO', 6, 'EP9272872', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:14:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vfgq65GTRH1751818494.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1622, 'TARIKUWA BEYENE WELDEMARYAM', 6, 'EQ1928091', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:15:59', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FodbWCCwgd1751818558.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1623, 'BESA GENA BUDE', 6, 'EQ2036939', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:20:31', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vY6aAq3zJm1751818831.png', '160', '57', '', NULL, '\"[null]\"', '\"[\\\"amharic\\\"]\"'),
(1624, 'ETENESH TELORE UMEYE', 6, 'EP6665938', 0, 1000, 1, 34.00, '1991-01-01', '', 1, 0, '2025-07-06', 'available', 1, '2025-07-06 19:25:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/v6WRjQcS3u1751819124.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1625, 'DEBELTU CHALA MEGERSA', 6, 'EQ1229740', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:16:30', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/9FmEwnyEoK1751894190.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1626, 'DESSE GEBISA SEBOKA', 6, 'EP9076433', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:17:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/l1oXvXmyNe1751894278.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1627, 'FANTU TEKALEGN AZEZE', 6, 'EQ1238547', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:18:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3tonTgxNhq1751894333.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1628, 'DEMEKU FIKADU FIRISA', 6, 'EQ2065744', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:20:15', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/p4iJ5swVjc1751894415.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1629, 'NETSANET MOKONIN HIBISO', 6, 'EP8131448', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:21:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/sHNgChDjZH1751894486.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1630, 'ALMAZ GOMOSO GOA', 6, 'EP9134333', 0, 1000, 1, 37.00, '1988-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:25:07', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/2UXWjxm4dw1751894707.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1631, 'AYELU ASEFA GEMADA', 6, 'EP7937477', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:26:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FAE1oJzH251751894782.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1632, 'HABTAM ALEMYE WODAJNEW', 6, 'EP9302804', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:27:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/n9HhVgGVEA1751894849.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1633, 'TAMENECH ABRIHAM AYMALO', 6, 'EP8362300', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:28:51', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/HYkyyRCjeO1751894931.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1634, 'NETSANET TAMIRAT BALCHA', 6, 'EQ1493456', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:30:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/m8pGMqcjFz1751895045.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1635, 'BIRO GIRMA JIMA', 6, 'EP8487097', 0, 1000, 1, 30.00, '1995-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:31:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/CgQ1qT0xeJ1751895110.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1636, 'SIRIAT ASFAW ALEMU', 6, 'EP9300533', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:33:05', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7l607TkaDl1751895185.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1637, 'TOFA GETAW DESALEK', 6, 'EQ2178246', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:34:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/KAqIVAgmRX1751895244.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1638, 'METALEM TAMERE LIBABO', 6, 'EQ1850981', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:35:30', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VJ3V7OzqIb1751895330.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1639, 'BILCHA  ALEMU  GODEBO', 6, 'EP8378452', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:37:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Vh2sMvi3BC1751895430.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1640, 'LENSA TIZAZU ADUSHA', 6, 'EQ1968796', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:38:12', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/15kuiJ47yk1751895491.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1641, 'KURE TASAMA TULU', 6, 'EP6697299', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:39:06', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Yy71uO0Nkz1751895546.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1642, 'SIMEGN DEMISE DESTA', 6, 'EQ1357328', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:39:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/l9wHAMwOAL1751895595.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1643, 'BESHATU ABRAHAM GUDINA', 6, 'EQ1129950', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:41:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/wpBCbKV0oc1751895662.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1644, 'ABEBECH MULUGETA AMDISA', 6, 'EQ1134125', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:41:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Ne8wNaYQOZ1751895716.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1645, 'BELAYNESH SHISHIGO LALOGO', 6, 'EP9247662', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:43:23', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/BUoxPGyHWo1751895803.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1646, 'TADELECH ADEM EGETO', 6, 'EP8043925', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:47:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/6kb49QRQ9l1751896033.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1647, 'GOBENE DUBE GAMADA', 6, 'EP8699206', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:48:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/LggPZQVTE01751896119.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1648, 'BIRHANE ESHETU ADINO', 6, 'EP8874968', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:49:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UVLp01mj9h1751896181.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1649, 'BIFTU AYALE GAMTA', 6, 'EP8140559', 0, 1000, 1, 21.00, '2004-01-01', '', 0, 0, '2025-07-07', 'reserved', 1, '2025-07-07 16:50:43', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vi19Ycoez21751896243.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1650, 'LIDIYA ABEBE MESKELO', 6, 'EP8360942', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:51:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/rAEfbx2JGa1751896314.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1651, 'ADANECH MEGISO ABU', 6, 'EP9222110', 0, 1200, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:53:28', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 6, 'images/Maid/h5bE1qWxBU1751896408.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1652, 'ALEMNESH ASARU DEDGEBA', 6, 'EP8880016', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:54:31', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/hIYuuElIdS1751896471.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1653, 'ANDASHA TIGIST DELESA', 6, 'EQ1954327', 0, 1000, 1, 26.00, '1999-01-01', '', 0, 0, '2025-07-07', 'reserved', 1, '2025-07-07 16:55:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tihHBnfCEe1751896536.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1654, 'TSEHAYNESH  TIRORE LORISO', 6, 'EP8126369', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:56:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/XMXTi9r2WF1751896606.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1655, 'DESALECH CHEMISO  LORISO', 6, 'EP8126370', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:57:43', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/bz0Oclheye1751896663.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1656, 'KEBEBUSH YOHANNES ABA', 6, 'EP8407883', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 16:58:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/aO02DXVa7g1751896730.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1657, 'MEREBE SANKIKO BERSO', 6, 'EQ1277026', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:00:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7ONoydWkmm1751896815.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1658, 'AMARECH ABEBE NECHA', 6, 'EQ1051559', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:01:19', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/BEDQoAA3kf1751896879.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1659, 'ABEBECH KABETO BUKURA', 6, 'EP7819265', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:03:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/elzSaWnO6C1751896996.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1660, 'JITU AWOL ABAFITA', 6, 'EQ1123778', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:04:20', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/0uvbKhY2IE1751897060.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1661, 'TAMENECH LELISHO MERORE', 6, 'EP8449908', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:05:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/nAa5gAQCxK1751897136.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1662, 'FIRENESH DANIEL ORDOLO', 6, 'EQ1254227', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:06:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3gFdEts8Sk1751897196.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1663, 'ASNAKECH KUMALO KOYERA', 6, 'EP6996007', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:07:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4QpuSLoUOU1751897260.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1664, 'ALMAZ KUCHE SANA', 6, 'EQ1290179', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:08:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/8tTLlBmJVB1751897330.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1665, 'MESKEREM ZEFINE BALIGO', 6, 'EP7389653', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:09:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/R1AT0nlgPM1751897394.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1666, 'KIDEST WENDIYE KIFLE', 6, 'EQ196OO29', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:12:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mcFZeYqJkg1751897544.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1667, 'EYBEZ ASCHENAKI DERESSE', 6, 'EP8684422', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:14:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/1QhBBrmVkd1751897650.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1668, 'TSEGANESH DEGELE WIRKA   I', 6, 'EP8255227', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:17:42', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/qaQOmyRiks1751897862.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1669, 'TEJI ALEMU BEKELE  I', 6, 'EP8234542', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:18:42', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tFAVL5Vk061751897922.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1670, 'ADDSIE ABEBAW SETIE', 6, 'EP8710533', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:19:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/LPdfEpWV261751897996.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1671, 'WEGENE BEYERA MECHESA', 6, 'EP8956309', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:20:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/BQANPLeaaQ1751898047.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1672, 'JITU FITUMA KUMISA  I', 6, 'EQ1927670', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:21:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/2KkruGdw6e1751898114.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1673, 'TSEGANESH DANIEAL  DEKU', 6, 'EP8410446', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:23:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NBYtu59yj01751898188.png', '160', '55', '', NULL, '\"[null]\"', NULL);
INSERT INTO `maids` (`id`, `name`, `nationality_id`, `passport_num`, `price`, `salary`, `religion_id`, `age`, `birth_date`, `cv_file`, `available`, `is_pinned`, `available_at`, `status`, `is_show`, `created_at`, `updated_at`, `experience_years`, `experience_status`, `cv_preview`, `office_id`, `image`, `height`, `weight`, `id_number`, `id_expiry_date`, `skills`, `languages`) VALUES
(1674, 'SHEWAYE KBRET  BANTERGAI', 6, 'EP9079099', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:24:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/6Fy8RH7vHS1751898243.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1675, 'SHURUBE DANEL HALESO', 6, 'EP9140879', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:24:52', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/U19o5Y36iM1751898292.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1676, 'ADANECH ABERA  KANASHYEWUI', 6, 'EP9313593', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:25:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/T34Gse4Eue1751898355.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1677, 'FEYRUZA ENDRIS MUSAI', 6, 'EQ1127700', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:26:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/9zzU1h2Aqk1751898406.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1678, 'ZENA ABERA KENDO', 6, 'EQ1020936', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:27:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/j8JchKkzfA1751898470.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1679, 'MEKDES TESHOME WOLE', 6, 'EP9320798', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:29:12', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FyFgte4jWf1751898552.png', '160', '51', '', NULL, '\"[null]\"', NULL),
(1680, 'BERKET MELESE ANULO', 6, 'EQ1039592', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:31:42', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/nSKoKpTf6t1751898702.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1681, 'WEDENESHE AREBA MANDIDA', 6, 'EQ2041584', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:32:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vTkI94DJXT1751898765.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1682, 'SIFEN TESHOME HURISA', 6, 'EP9240832', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:33:52', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/a3YkqtDGZO1751898832.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1683, 'BURTUKAN TESHOME HURISA', 6, 'EP9244125', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:34:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/d2OKbhQsnU1751898884.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1684, 'ANSHA ENDRIS AWELU', 6, 'EQ1396083', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:35:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/oULJBuwUQX1751898949.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1685, 'ALIMA ABDULKADIR KEDIR', 6, 'EQ1009804', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:36:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/zkjEZ9Z6Kd1751899004.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1686, 'MOMINA GETASEW  ASEN', 6, 'EP9071661', 0, 1000, 3, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:38:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vIsnUqZHyu1751899098.png', '165', '55', '', NULL, '\"[null]\"', NULL),
(1687, 'MERARTU ENDALU WEYESA', 6, 'EQ1931465', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:39:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/70aQYIr8kQ1751899173.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1688, 'EKRAM JEMAL AHMED', 6, 'EP8151673', 0, 1000, 3, 21.00, '2004-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 17:41:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/zygZmdekQ31751899273.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1689, 'NEJAT NURU HUSSEN', 6, 'EQ2501232', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 17:42:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/A4ZbR73Ukw1751899336.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1690, 'HAYAT GELETA DEJEN', 6, 'EP9151350', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:43:20', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/K0rSQlmRWR1751899400.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1691, 'RAHIMET ALI MEKTEBA', 6, 'EP9122990', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:44:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/OxZpVt1X4i1751899457.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1692, 'FATUMA FENTAW SEID', 6, 'EQ2088795', 0, 1000, 3, 21.00, '2004-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 17:49:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/6duu17zzhE1751899795.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1693, 'SEADA ABDU MOHAMMED', 6, 'EQ1014913', 0, 1000, 3, 30.00, '1995-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:52:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/W5tzHgPipG1751899937.png', '164', '55', '', NULL, '\"[null]\"', NULL),
(1694, 'RABIA JEMAL MUHE', 6, 'EP8566268', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:53:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/SOV2w0Lxbi1751900037.png', '166', '58', '', NULL, '\"[null]\"', NULL),
(1695, 'TAMIMA FITESA WAKO', 6, 'EP8980791', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 17:54:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/8JQLERch2J1751900097.png', '169', '58', '', NULL, '\"[null]\"', NULL),
(1696, 'ASYA HUSSEN AYALEW', 6, 'EQ2059138', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:55:50', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/IbNVjvxGXJ1751900150.png', '168', '58', '', NULL, '\"[null]\"', NULL),
(1697, 'BERITE SIRGAGA MOHAMMED', 6, 'EQ1147990', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:56:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/rMvVDLm3fr1751900195.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1698, 'AJIBA HUSSEN MOHAMMED', 6, 'EQ2141307', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:57:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/KVp8RQiE131751900248.png', '169', '58', '', NULL, '\"[null]\"', NULL),
(1699, 'ALEYKA SULTAN AHMED', 6, 'EP8074751', 0, 1000, 3, 21.00, '2004-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 17:58:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3pKu5PkyjN1751900321.png', '168', '58', '', NULL, '\"[null]\"', NULL),
(1700, 'KEYIREYA HUSSEN MOHAMMED', 6, 'EQ2141772', 0, 1000, 3, 30.00, '1995-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 17:59:47', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/y26xRazc8s1751900387.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1701, 'ARABE KUSHIYE GEMEDA', 6, 'EP8918854', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:00:35', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FSLiyzE4Ma1751900435.png', '166', '58', '', NULL, '\"[null]\"', NULL),
(1702, 'CHALTU UMAR TUKE', 6, 'EQ2553067', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:01:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/91KQSexpdX1751900486.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1703, 'ZABURA AHMED EBRE', 6, 'EQ1498650', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:02:23', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4q6o44q4No1751900543.png', '166', '58', '', NULL, '\"[null]\"', NULL),
(1704, 'SEADA MOHAMMED KASSA', 6, 'EQ1958119', 0, 1000, 3, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:03:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/QzDKkBYplH1751900598.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1705, 'SEADA MOHAMMED YIMER', 6, 'EQ2048030', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:04:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/KwBmuamg8u1751900673.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1706, 'ZEYNEBA GOBENA ROBA', 6, 'EP8473460', 0, 1000, 3, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 18:05:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/e6t1rm2SrB1751900753.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1707, 'JEMILA CHEKISO MISHA', 6, 'EQ1126035', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:06:38', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Ojs0JCnsUo1751900798.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1708, 'AMOYE ENDRIS TOYB', 6, 'EQ2767377', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:07:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/XtAqkQI2qO1751900856.png', '167', '58', '', NULL, '\"[null]\"', NULL),
(1709, 'FATUMA ENDRIS MUHE', 6, 'EQ1382594', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:08:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4b0q52wvfN1751900901.png', '166', '58', '', NULL, '\"[null]\"', NULL),
(1710, 'HABIBA MASHO CHAKO', 6, 'EQ1292615', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:09:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/iXZxyoC4Oi1751900964.png', '166', '58', '', NULL, '\"[null]\"', NULL),
(1711, 'ZEBO HUSEN MUHAMMED', 6, 'EQ2455267', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 18:10:16', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tz5EHFkcYy1751901016.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1712, 'LUBABA YESUF MOHAMMED', 6, 'EQ2462404', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:11:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3HZRxuuhC01751901063.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1713, 'TIEMR ABEBAW HAILE', 6, 'EQ2254046', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:12:00', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mSjRSn8H2f1751901120.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1714, 'RADIYA KABETO WORASA', 6, 'EP9260947', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:12:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/gsHHxqI5XX1751901173.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1715, 'SAMIRA ABAJIHAD ABABULGU', 6, 'EQ1054342', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:13:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/lgq6T8zuJu1751901224.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1716, 'SEMIRA ALI SEID', 6, 'EQ2160037', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:14:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/5HVqBmAjZ61751901284.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1717, 'MABE JEBEL SANBU', 6, 'EQ1923844', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:15:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/nKh2ym0qzv1751901341.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1718, 'NEIMA ENDRIS ABDU', 6, 'EP9273687', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 18:16:34', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/atnXHBpRPa1751901394.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1719, 'HAMZIYA JUNDI ABDUREHMAN', 6, 'EQ1012882', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:17:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/TDI5Pj9GJ51751901448.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1720, 'KEDIJA SHAWNTA WANE', 6, 'EP8911493', 0, 1000, 3, 35.00, '1990-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:18:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/26cYDnl0d01751901509.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1721, 'GOBANE AYANO DUBE', 6, 'EQ1027763', 0, 1000, 3, 27.00, '1997-09-19', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:19:23', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Y1YMrEXgOl1751901563.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1722, 'TEMRA SETEGN AMINU', 6, 'EQ1134693', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:20:11', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/HlA4C2bitW1751901611.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1723, 'REYHANA NESRU KEDIR', 6, 'EQ2140944', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:21:06', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/u3bfmvAjMu1751901665.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1724, 'AREBE ADEM  UMER', 6, 'EQ1835916', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:22:05', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/9LyPRbfJe91751901725.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1725, 'MARIYA TAMAM BAME', 6, 'EP9052918', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:23:00', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/kQoN87gek21751901780.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1726, 'SEFINA KEDIR JILO', 6, 'EP8926067', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:24:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/aNuvilDn0d1751901841.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1727, 'WAKEYO REHIMA HUSSEN', 6, 'EQ1055321', 0, 1000, 3, 27.00, '1998-01-01', '', 0, 0, '2025-07-07', 'reserved', 1, '2025-07-07 18:24:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/MOYPoCYnLB1751901897.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1728, 'FATIYA MOHAMMED SIRAJ', 6, 'EQ2238230', 0, 1000, 3, 33.00, '1992-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:25:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/BJfVcZXpYR1751901949.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1729, 'HAJERA BEREKA BULCHA', 6, 'EQ1905021', 0, 1000, 3, 30.00, '1995-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:29:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/n0omzJSGRG1751902150.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1730, 'MUNEWARA MAMA HUSSEN', 6, 'EP8854100', 0, 1000, 3, 33.00, '1992-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:30:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/TbcRNog4XL1751902203.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1731, 'WESILA GEGTIRO SULE', 6, 'EQ1261935', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:31:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tcKCsNrxjT1751902261.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1732, 'MUFARIHA HAMIDE ABAGERO', 6, 'EP8771823', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:32:11', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/86cxURyb9A1751902331.png', '160', '50', '', NULL, '\"[null]\"', NULL),
(1733, 'JEMLA TIGCHO HISABO', 6, 'EP9282140', 0, 1000, 3, 40.00, '1985-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:33:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/n51EqLE8gu1751902393.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1734, 'AYISHA HASEN SEMAGN', 6, 'EQ2256320', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:34:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DhGu71Q7ac1751902443.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1735, 'AMINA SHAMIRU ADEM', 6, 'EP8726303', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:35:07', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/01zJAI0pvW1751902507.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1736, 'SUDI MAMED ABABULGU', 6, 'EQ1905042', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:35:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/C7nvQgb5SZ1751902558.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1737, 'KEDIJA YESUF ABRIE', 6, 'EP8192934', 0, 1000, 3, 29.00, '1996-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:36:59', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/MqKxPOh2WI1751902619.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1738, 'KARIMA MUHAMED FATO', 6, 'EP9161206', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:38:19', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DakVk6OSxX1751902699.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1739, 'AMINA ABEDUREMAN ABAGERO', 6, 'EQ1496522', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:39:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/JnWW0HCDdo1751902773.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1740, 'ALEM MUHAMMED HAMZA', 6, 'EP8961189', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:40:23', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7PEfsE5fZS1751902823.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1741, 'NEJAT ABDO ABGIDDI', 6, 'EP8768739', 0, 1000, 3, 21.00, '2004-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:41:35', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mxIv5FesAX1751902895.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1742, 'HAMZIYA AMINO HAJI', 6, 'EQ1362055', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:42:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/bXZIH3gA8C1751902965.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1743, 'TEYIBA ABAYE LEMA', 6, 'EP7854356', 0, 1000, 3, 22.00, '2003-01-01', '', 0, 0, '2025-07-07', 'reserved', 1, '2025-07-07 18:43:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/9h8QUawyzt1751903020.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1744, 'HAJO HUSEN ABDA', 6, 'EQ2081859', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:45:00', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/ngjD8SrsGb1751903100.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1745, 'ANSHA ENDRIS YASSIN', 6, 'EP9076995', 0, 1000, 3, 41.00, '1984-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:45:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/fJ0kXPeP5E1751903158.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1746, 'SOFEYA GIRMA MENGISTA', 6, 'EP9005308', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 0, '2025-07-07 18:47:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Eng6k6h3Rq1751903266.png', '160', '59', '', NULL, '\"[null]\"', NULL),
(1747, 'ASHA NEZIMU ABDULKADIR', 6, 'EP8768887', 0, 1000, 3, 22.00, NULL, '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:48:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/PvA5uYtnzk1751903320.png', '160', '55', NULL, NULL, '\"[null]\"', NULL),
(1748, 'MERII HUMNESA SARBESA', 6, 'EP9239615', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:50:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/dJnN9YGHXq1751903422.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1749, 'KEMILA ISA HUSSEN', 6, 'EP8381327', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:52:12', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/rKSYSaWO0F1751903532.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1750, 'MEKIYA ABDU MOHAMMED', 6, 'EQ1369233', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'completed', 1, '2025-07-07 18:53:01', '2025-08-11 14:34:46', '0', 'no_experience', NULL, 6, 'images/Maid/D0c97ZUjlS1751903581.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1751, 'SHEMISYA ABDLIBAR HUSSEN', 6, 'EP9180239', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:54:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/cSgPEwFiTO1751903643.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1752, 'MEDINA JUNDI KEDIR', 6, 'EQ1045871', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:55:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/090rNH9Sn61751903702.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1753, 'ROMA AHMED GEMECHU', 6, 'EP9229008', 0, 1000, 3, 24.00, '2001-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:55:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4Z5KDfMqei1751903756.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1754, 'NEJAT MEHAMED HACHALU', 6, 'EP9305953', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 18:57:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/bsOGMZYz6x1751903833.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1755, 'BESA BURI TOLA', 6, 'EP8642657', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:00:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Jbs0ezoKTR1751904017.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1756, 'SADIYA SULTAN GAMBU', 6, 'EP9120265', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:01:21', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UD2vGwbxZk1751904081.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1757, 'HAMZIYA ADEM DUBE', 6, 'EQ1026426', 0, 1000, 3, 22.00, '2003-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:02:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3aXv2wSgKG1751904177.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1758, 'HAYAT USSEN NESREDIN', 6, 'EQ1398441', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:03:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/dioQIgb2bx1751904235.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1759, 'ALWIYE JEMAL WAN', 6, 'EP8477021', 0, 1000, 3, 37.00, '1988-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:05:07', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/gdHLBYYKkD1751904307.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1760, 'TIGIST MESAY ALENE', 6, 'EP8836168', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:18:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/I2cBkxvNPu1751905088.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1761, 'BEYISU AMAN EDEO  I', 6, 'EP9019441', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:19:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/fcB4XCXqZ31751905173.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1762, 'KAMSO HASO TULA', 6, 'EP8556633', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:20:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Kt08yFG8sw1751905236.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1763, 'ATIMA ASINAKE MUHAMMEDI', 6, 'EP9020956', 0, 1000, 3, 32.00, '1993-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:21:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/L8NJFBbWub1751905289.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1764, 'BELAYNESH SEID JENORO', 6, 'EQ1167693', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:36:25', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/uNywEr5PJz1751906185.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1765, 'KERIMA AREBAYU MANZA', 6, 'EP9070176', 0, 1000, 3, 25.00, '2000-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:38:07', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/LfELycm9qD1751906287.png', '162', '53', '', NULL, '\"[null]\"', NULL),
(1766, 'ALIYA ADEN TIBESO', 6, 'EP8451377', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:39:25', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/SrZHqQK1cS1751906365.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1767, 'HABIBA ABE DEYBESO', 6, 'EP9163023', 0, 1000, 3, 26.00, '1999-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:41:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/xwi0lYjuJe1751906478.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1768, 'AYSHA MOHAMMED  LIGIBCHO', 6, 'EP8584739', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:42:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4ni3ju5zdW1751906537.png', '160', '55', '', NULL, '\"[null]\"', NULL),
(1769, 'ALIYA BUTA BARJE', 6, 'EQ1247509', 0, 1000, 3, 23.00, '2002-01-01', '', 1, 0, '2025-07-07', 'available', 1, '2025-07-07 19:44:38', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/GMLwH9IdX71751906678.png', '160', '57', '', NULL, '\"[null]\"', NULL),
(1772, 'MUKYAALA RAJIBAH', 4, 'A00819939', 0, 1200, 3, 27.00, '1998-06-06', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 16:29:26', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 14, 'images/Maid/34nOD1J40f1751981366.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1773, 'TASHLIMA AKTER', 5, 'A02368911', 0, 1100, 3, 38.00, '1987-03-05', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:12:52', '2025-07-26 09:07:16', '2', 'has_experience', NULL, 4, 'images/Maid/0AZ8b7mHha1751983972.png', '155', '60', '', NULL, '\"[null]\"', NULL),
(1774, 'REHANA BEGUM', 5, 'EL0578136', 0, 1100, 3, 42.00, '1983-05-04', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:15:31', '2025-07-26 09:07:16', '4', 'has_experience', NULL, 4, 'images/Maid/abYA4txhu21751984131.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1775, 'NARGIS', 5, 'A16387769', 0, 900, 3, 35.00, '1989-07-10', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 17:17:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/jIAcgXQumO1751984261.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1776, 'MST HAMIDA BEGUM', 5, 'A17824350', 0, 900, 3, 36.00, '1989-02-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:19:09', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/KbBgB2uJNr1751984349.png', '152', '62', '', NULL, '\"[null]\"', NULL),
(1777, 'MST MUSHIDA BANU', 5, 'A18070387', 0, 900, 3, 33.00, '1992-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:20:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/FkEb1GkZ5a1751984414.png', '157', '57', '', NULL, '\"[null]\"', NULL),
(1778, 'LIPI BEGUM', 5, 'A18478743', 0, 900, 3, 31.00, '1994-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:21:28', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/H3P91gXoMe1751984488.png', '152', '55', '', NULL, '\"[null]\"', NULL),
(1779, 'MST JOSNA KHATUN', 5, 'A18447554', 0, 900, 3, 39.00, '1986-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 17:25:22', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 4, 'images/Maid/XgTBkJiNT31751984722.png', '152', '57', '', NULL, '\"[null]\"', NULL),
(1780, 'MITU TADESSE AWASH', 6, 'EP9336366', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 19:56:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4AAmMIvskX1751993768.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1781, 'MESERET BEKELE BEDAN', 6, 'EP9099426', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 19:57:20', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Z4qQXI0Koi1751993840.png', '159', '63', '', NULL, '\"[null]\"', NULL),
(1782, 'BAYESH BERIHUN TSEGA', 6, 'EQ2233992', 0, 1000, 1, 31.00, '1994-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 19:58:14', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/hAQxruqYer1751993894.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1783, 'WELELA MELKAMU GEBEY', 6, 'EP9254017', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 19:59:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7Pz3AAZ0yi1751993941.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1784, 'MEKDES NEGA MESFIN', 6, 'EQ1157440', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 19:59:46', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/oktqcrLE1V1751993986.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1785, 'ADENEK ASEGIE CHEKOLE', 6, 'EQ1410545', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:00:51', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VGzQ4te4wr1751994051.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1786, 'TIRUEDL TEGEGNE TEBEJE', 6, 'EQ2421981', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:01:33', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/XCNTlqoJu21751994093.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1787, NULL, 6, '', 0, 1000, 1, 22.00, '2003-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:02:15', '2025-07-27 19:13:48', '0', 'no_experience', NULL, 6, 'images/Maid/usGndOeK111751994135.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1788, 'MULUKEN DEJEN ENGIDAW', 6, 'EP6374446', 0, 1000, 1, 32.00, '1993-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:02:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/PvSZcvVnEV1751994178.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1789, 'ATALEL MELESE KASSA', 6, 'EP9166652', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:03:44', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/FxLNuvmlWF1751994224.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1790, 'YEZINA TEWACHEW GETNET', 6, 'EQ1123382', 0, 1000, 1, 33.00, '1992-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:04:24', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/OCjhdxNQQb1751994264.png', '159', '60', '', NULL, '\"[null]\"', NULL),
(1791, 'WUBALEM ASSEFA DEBALKIE', 6, 'EP7153928', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:05:09', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DALetW9gDD1751994309.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1792, 'FIRE TEKLETSION TEKA', 6, 'EQ1821946', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:05:48', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/VuNzRsTikF1751994348.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1793, 'EMEBET BIHONEGN SHEGAW', 6, 'EQ2086588', 0, 1000, 1, 23.00, '2002-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:06:27', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/6MRFf7NilM1751994387.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1794, 'TIGIST AMARE ESHETU', 6, 'EP9384087', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:07:11', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/nJVvPfWWy81751994431.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1795, 'HANAN NIGATE AYELE', 6, 'EP8377543', 0, 1000, 1, 21.00, '2004-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:08:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/gnjmy62BsC1751994488.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1796, 'HAWI BELAY TULU', 6, 'EQ2153890', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:08:53', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/Q1S5Z3UDf71751994533.png', '158', '62', '', NULL, '\"[null]\"', NULL),
(1797, 'ASTER ASRAT AMAMO', 6, 'EQ1119042', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:09:36', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/N1TEMn60Vi1751994576.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1798, 'MEDANIT NUGUSE KEBEDE', 6, 'EQ2511081', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:10:15', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/UwNNGO99gY1751994615.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1799, 'MARITU YIBELTAL ADARG', 6, 'EP9042342', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:10:56', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/aUOlttExjP1751994656.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1800, 'ABINET MULUGETA WERKNEH', 6, 'EP7424770', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:11:55', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/IhEaAYySoS1751994715.png', '160', '53', '', NULL, '\"[null]\"', NULL),
(1801, 'GUZGUZ FANTAW ADANE', 6, 'EQ1477685', 0, 1000, 1, 22.00, '2003-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:12:50', '2025-08-07 10:03:54', '0', 'no_experience', NULL, 6, 'images/Maid/fBRgdtp9HC1751994770.png', '160', '53', '', NULL, '\"[null]\"', NULL),
(1802, NULL, 6, '', 0, 1000, 1, 29.00, '1996-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:13:44', '2025-08-18 19:37:23', '0', 'no_experience', NULL, 6, 'images/Maid/oGUlrEmfmO1751994824.png', '160', '53', '', NULL, '\"[null]\"', NULL),
(1803, 'YESHI MISAW TSEGAW', 6, 'EP8185052', 0, 1000, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:15:10', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/dEQq4fA8yq1751994910.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1804, 'MESERET DIRIBA ABERA', 6, 'EP7912612', 0, 1000, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:16:08', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/IY6tsDqD3q1751994968.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1805, 'GADISE WORKU TULU', 6, 'EP8273834', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:16:57', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mx9rqMuZba1751995017.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1806, 'FIRIYOT DEBEBE GEDA', 6, 'EP9348273', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:17:43', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/NM02ctPoiD1751995063.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1807, 'AMRIYA KASSA ASHENAFI', 6, 'EP9299422', 0, 1000, 1, 24.00, '2001-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:18:27', '2025-08-19 20:40:02', '0', 'no_experience', NULL, 6, 'images/Maid/QecWNMmMTl1751995107.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1808, 'ALEMNESH SITOTAW SISAY', 6, 'EQ1821355', 0, 1000, 1, 29.00, '1996-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:19:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/MUgdv28ljh1751995153.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1809, 'HAYMANOT MABRE WUNETU', 6, 'EQ1179504', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:20:03', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/RWTl1QFzbL1751995203.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1810, 'MASANTU TEKILE DIRESO', 6, 'EP9048561', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:20:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/mE5e2F8U0I1751995258.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1811, 'ABERASH KIFILE KIA', 6, 'EP7605995', 0, 1000, 1, 23.00, '2002-02-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:21:49', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/twAJWqPXpm1751995309.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1812, NULL, 6, '', 0, 1000, 1, 30.00, '1995-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:22:42', '2025-08-07 10:31:57', '0', 'no_experience', NULL, 6, 'images/Maid/7cb2X3PdeS1751995362.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1813, 'BURTUKE GAMESA ASFEA', 6, 'EQ1852460', 0, 1000, 1, 27.00, '1998-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:24:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/pi5K2nmP5g1751995444.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1814, 'MEKDES AREDA TADESE', 6, 'BURTUKE GAMESA ASFEA', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:25:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7Uc69aOuaV1751995502.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1815, 'GENET DEJENE HAYLU', 6, 'EP6881254', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:26:04', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/cY93MaB2541751995564.png', '160', '62', '', NULL, '\"[null]\"', NULL),
(1816, 'BIRI EJERA DEBELE', 6, 'EP8923746', 0, 1000, 1, 28.00, '1996-12-01', '', 1, 0, '2025-07-08', 'available', 0, '2025-07-08 20:32:07', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3cgW24yVj61751995927.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1817, 'ALEMNESH ASCHALEW', 6, 'EQ2168382', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:33:01', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3zbqt1x9f81751995981.png', '158', '62', '', NULL, '\"[null]\"', NULL),
(1818, 'WORKI MEGERSA LETA', 6, 'EP8612584', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:33:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/h5kA77PfIf1751996025.png', '160', '62', '', NULL, '\"[null]\"', NULL),
(1819, 'CHALTU REGASA DIBAB', 6, 'EP9078976', 0, 1000, 3, 27.00, '1998-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:35:39', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/bqQAMS0mWS1751996139.png', '160', '62', '', NULL, '\"[null]\"', NULL),
(1820, 'BURTUKAN GELETA  JIDA', 6, 'EP8804262', 0, 1000, 3, 34.00, '1991-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:36:51', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/tDcD148Oie1751996211.png', '160', '62', '', NULL, '\"[null]\"', NULL),
(1821, 'ZENEBECH MEKONIN MECHA', 6, 'EP6531342', 0, 1000, 1, 36.00, '1989-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:37:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/MgJyAdlwtk1751996265.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1822, 'ABEBA GETACHEW BEDADA', 6, 'EP8802941', 0, 1000, 1, 30.00, '1995-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:38:32', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/7q2DMyweTP1751996312.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1823, 'SHITA MARDASA TOLOSA', 6, 'EP8430236', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:39:29', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/e8sTKmAhsW1751996369.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1824, 'RUFAEL TESFAYE KEBEDE', 6, 'EP8767879', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:40:13', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/AxeER5V1Z51751996413.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1825, 'ALEMNESH ASIRES HAILU', 6, 'EP9142693', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:40:58', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/jVLBokawKh1751996458.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1826, 'MEKIDES DAE TAWA', 6, 'EP8499798', 0, 1000, 1, 25.00, '2000-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:41:45', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/vGD9XOLi4y1751996504.png', '160', '63', '', NULL, '\"[null]\"', NULL),
(1827, 'KALKIDAN ASRATIE BELETE', 6, 'EP8097647', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:42:26', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/6D7bjk8zsT1751996546.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1828, 'SHITO TESFAYE TOLOSA', 6, 'EP8174790', 0, 1000, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:43:41', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3McSFUWiEe1751996621.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1829, 'ASTER ESHETU KEBEDE', 6, 'EP9284507', 0, 1000, 1, 26.00, '1999-01-01', '', 1, 0, '2025-07-08', 'available', 1, '2025-07-08 20:44:54', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/4xqAp9Ik8W1751996694.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1830, 'GADISE NAGESO DEKO', 6, 'EQ2445473', 0, 1000, 1, 25.00, '2000-01-01', '', 0, 0, '2025-07-08', 'reserved', 1, '2025-07-08 20:46:17', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/DA6EJfRJAo1751996777.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1831, 'AHIMBISIBWE SESERIA', 4, 'B00631809', 0, 900, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-09', 'available', 0, '2025-07-09 16:24:18', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 14, 'images/Maid/9BmsO7s7as1752067458.png', '161', '60', '', NULL, '\"[null]\"', NULL),
(1832, 'MUHINDO PRECIOUS', 4, 'B00309097', 0, 900, 1, 22.00, '2003-01-01', '', 1, 0, '2025-07-09', 'reserved', 1, '2025-07-09 16:25:30', '2025-07-10 16:15:09', '0', 'no_experience', NULL, 14, 'images/Maid/dX6nZCVqkF1752067530.png', '155', '50', '', NULL, '\"[null]\"', NULL),
(1833, 'ASIIMWE MACKLINE', 4, 'B00589589', 0, 900, 1, 21.00, '2004-01-01', '', 0, 0, '2025-07-09', 'reserved', 1, '2025-07-09 16:27:02', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 13, 'images/Maid/Xyhn2vAvHP1752067622.png', '155', '50', '', NULL, '\"[null]\"', NULL),
(1834, 'TEYIBA FANAGA TESHITE', 6, 'EP6797960', 0, 1000, 3, 28.00, '1997-01-01', '', 1, 0, '2025-07-12', 'available', 1, '2025-07-12 17:45:34', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/g5GpB0LGbt1752331534.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1835, 'MEKDES ALEBACHEW GEZAGN', 6, 'EP7715913', 0, 1200, 1, 35.00, '1990-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 17:47:15', '2025-07-14 16:28:21', '6', 'has_experience', NULL, 6, 'images/Maid/FzgwFTz5Pv1752331635.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1836, 'TIGIST DIRE GURMU', 6, 'EP7395053', 0, 1200, 1, 31.00, '1994-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 17:48:29', '2025-07-13 19:02:08', '4', 'has_experience', NULL, 6, 'images/Maid/4muFp3c9AR1752331709.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1837, 'HABTIYE MESFEN HABTAMU', 6, 'EQ2853662', 0, 1200, 1, 37.00, '1987-10-09', '', 1, 0, '2025-07-12', 'available', 1, '2025-07-12 17:53:48', '2025-07-26 09:07:16', '8', 'has_experience', NULL, 6, 'images/Maid/x0dztpIJcX1752332028.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1838, 'FEREWET ADISU MULNEH', 6, 'EQ1047810', 0, 1200, 1, 46.00, '1979-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 17:55:15', '2025-07-13 17:28:08', '5', 'has_experience', NULL, 6, 'images/Maid/7TkOXgIPac1752332115.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1840, 'KAFANE ABERA FEYISA', 6, 'EQ2519293', 0, 1200, 1, 28.00, '1997-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 17:57:40', '2025-07-13 17:51:04', '9', 'has_experience', NULL, 6, 'images/Maid/V76Ea2UBES1752332260.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1841, NULL, 6, '', 0, 1200, 1, 33.00, '1992-01-01', '', 0, 0, '2025-07-12', 'reserved', 1, '2025-07-12 17:59:19', '2025-07-28 17:54:45', '6', 'has_experience', NULL, 6, 'images/Maid/o5yhXQaJZN1752332359.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1842, 'ZULUFA KEDIR SHEDAGA', 6, 'EQ2189531', 0, 1200, 3, 40.00, '1985-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 18:00:21', '2025-07-13 17:30:46', '3', 'has_experience', NULL, 6, 'images/Maid/mhK83zsCvq1752332421.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1844, 'ZEYNEBA SEID ASFAW', 6, 'EQ1911759', 0, 1200, 3, 39.00, '1986-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 18:07:31', '2025-07-13 17:24:45', '0', 'no_experience', NULL, 6, 'images/Maid/8DB3Cv3xus1752332851.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1845, 'MEDINA ADEM EBRAHIM', 6, 'EP9147547', 0, 1200, 3, 38.00, '1987-01-01', '', 1, 0, '2025-07-12', 'reserved', 1, '2025-07-12 18:16:18', '2025-07-13 17:24:07', '5', 'has_experience', NULL, 6, 'images/Maid/pONlM6sPiC1752333378.png', '160', '60', '', NULL, '\"[null]\"', NULL),
(1846, 'BETELHEM BIRHANU ASSEFA', 6, 'EP8580695', 0, 1000, 1, 24.00, '2001-01-01', '', 1, 0, '2025-07-12', 'available', 1, '2025-07-12 18:19:40', '2025-07-26 09:07:16', '0', 'no_experience', NULL, 6, 'images/Maid/3bkuqGEnwg1752333580.png', '160', '58', '', NULL, '\"[null]\"', NULL),
(1847, 'NEEMA MOHMMED ASSEN', 6, 'EP8217637', 0, 1200, 3, 38.00, '1987-01-01', '', 0, 0, '2025-07-14', 'reserved', 1, '2025-07-14 16:48:48', '2025-07-28 16:27:37', '1', 'has_experience', NULL, 6, 'images/Maid/RD80WxQstq1752500928.png', '158', '54', '', NULL, '\"[null]\"', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maid_jobs`
--

CREATE TABLE `maid_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `job_type_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maid_jobs`
--

INSERT INTO `maid_jobs` (`id`, `maid_id`, `job_type_id`, `created_at`, `updated_at`) VALUES
(2389, 1323, 2, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2390, 1325, 2, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2391, 1328, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2392, 1331, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2393, 1335, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2394, 1337, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2395, 1339, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2396, 1340, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2397, 1341, 2, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2398, 1344, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2399, 1345, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2400, 1346, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2401, 1348, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2402, 1349, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2403, 1350, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2404, 1351, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2405, 1352, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2406, 1353, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2407, 1354, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2408, 1356, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2409, 1357, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2410, 1358, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2411, 1359, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2412, 1360, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2413, 1361, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2414, 1362, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2415, 1363, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2416, 1364, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2417, 1365, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2418, 1366, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2419, 1368, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2420, 1369, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2421, 1371, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2422, 1372, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2423, 1373, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2424, 1374, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2425, 1375, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2426, 1377, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2427, 1378, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2428, 1380, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2429, 1382, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2430, 1385, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2431, 1386, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2432, 1387, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2433, 1389, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2434, 1392, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2435, 1393, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2436, 1394, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2437, 1395, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2438, 1397, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2439, 1399, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2440, 1400, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2441, 1402, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2442, 1403, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2443, 1405, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2444, 1406, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2445, 1408, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2446, 1409, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2447, 1411, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2448, 1413, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2449, 1415, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2450, 1416, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2451, 1417, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2452, 1419, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2453, 1420, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2454, 1423, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2455, 1425, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2456, 1428, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2457, 1429, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2458, 1431, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2459, 1433, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2460, 1434, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2461, 1435, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2462, 1436, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2463, 1439, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2464, 1440, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2465, 1441, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2466, 1442, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2467, 1443, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2468, 1446, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2469, 1449, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2470, 1450, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2471, 1451, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2472, 1452, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2473, 1453, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2474, 1455, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2475, 1456, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2476, 1457, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2477, 1460, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2478, 1461, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2479, 1462, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2480, 1463, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2481, 1464, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2482, 1465, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2483, 1466, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2484, 1467, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2485, 1468, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2486, 1469, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2487, 1470, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2488, 1472, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2489, 1473, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2490, 1478, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2491, 1479, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2492, 1480, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2493, 1481, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2494, 1483, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2495, 1484, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2496, 1486, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2497, 1490, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2498, 1492, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2499, 1495, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2500, 1496, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2501, 1497, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2502, 1498, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2503, 1499, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2504, 1501, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2505, 1502, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2506, 1503, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2507, 1504, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2508, 1505, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2509, 1509, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2510, 1510, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2511, 1511, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2512, 1512, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2513, 1513, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2514, 1514, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2515, 1515, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2516, 1516, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2517, 1517, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2518, 1519, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2519, 1520, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2520, 1521, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2521, 1522, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2522, 1523, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2523, 1524, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2524, 1526, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2525, 1528, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2526, 1529, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2527, 1532, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2528, 1533, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2529, 1534, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2530, 1535, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2531, 1536, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2532, 1537, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2533, 1538, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2534, 1541, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2535, 1542, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2536, 1543, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2537, 1547, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2538, 1548, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2539, 1549, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2540, 1550, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2541, 1551, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2542, 1552, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2543, 1553, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2544, 1554, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2545, 1555, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2546, 1556, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2547, 1557, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2548, 1558, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2549, 1559, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2550, 1560, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2551, 1561, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2552, 1562, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2553, 1564, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2554, 1577, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2555, 1578, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2556, 1579, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2557, 1580, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2558, 1581, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2559, 1582, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2560, 1583, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2561, 1584, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2562, 1585, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2563, 1586, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2564, 1587, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2565, 1588, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2566, 1589, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2567, 1590, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2568, 1591, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2569, 1592, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2570, 1593, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2571, 1594, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2572, 1595, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2573, 1596, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2574, 1597, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2575, 1598, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2576, 1599, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2577, 1600, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2578, 1601, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2579, 1603, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2580, 1604, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2581, 1605, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2582, 1606, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2583, 1607, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2584, 1608, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2585, 1609, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2586, 1610, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2587, 1611, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2588, 1612, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2589, 1613, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2590, 1614, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2591, 1615, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2592, 1617, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2593, 1618, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2594, 1619, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2595, 1620, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2596, 1621, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2597, 1622, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2598, 1623, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2599, 1624, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2600, 1625, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2601, 1626, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2602, 1627, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2603, 1628, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2604, 1629, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2605, 1630, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2606, 1631, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2607, 1632, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2608, 1633, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2609, 1634, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2610, 1635, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2611, 1636, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2612, 1637, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2613, 1638, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2614, 1639, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2615, 1640, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2616, 1641, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2617, 1642, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2618, 1643, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2619, 1644, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2620, 1645, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2621, 1646, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2622, 1647, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2623, 1648, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2624, 1650, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2625, 1651, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2626, 1652, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2627, 1654, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2628, 1655, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2629, 1656, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2630, 1657, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2631, 1658, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2632, 1659, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2633, 1660, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2634, 1661, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2635, 1662, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2636, 1663, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2637, 1664, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2638, 1665, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2639, 1666, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2640, 1667, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2641, 1668, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2642, 1669, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2643, 1670, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2644, 1671, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2645, 1672, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2646, 1673, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2647, 1674, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2648, 1675, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2649, 1676, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2650, 1677, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2651, 1678, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2652, 1679, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2653, 1680, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2654, 1681, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2655, 1682, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2656, 1683, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2657, 1684, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2658, 1685, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2659, 1686, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2660, 1687, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2661, 1690, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2662, 1691, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2663, 1693, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2664, 1694, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2665, 1696, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2666, 1697, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2667, 1698, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2668, 1699, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2669, 1701, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2670, 1702, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2671, 1703, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2672, 1704, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2673, 1705, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2674, 1707, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2675, 1708, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2676, 1709, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2677, 1710, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2678, 1712, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2679, 1713, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2680, 1714, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2681, 1715, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2682, 1716, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2683, 1717, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2684, 1719, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2685, 1720, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2686, 1721, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2687, 1722, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2688, 1723, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2689, 1724, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2690, 1725, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2691, 1726, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2692, 1728, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2693, 1729, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2694, 1730, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2695, 1731, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2696, 1732, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2697, 1733, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2698, 1734, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2699, 1735, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2700, 1736, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2701, 1737, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2702, 1738, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2703, 1739, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2704, 1740, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2705, 1741, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2706, 1742, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2707, 1744, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2708, 1745, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2709, 1747, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2710, 1748, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2711, 1749, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2712, 1750, 2, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2713, 1751, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2714, 1752, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2715, 1753, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2716, 1754, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2717, 1755, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2718, 1756, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2719, 1757, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2720, 1758, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2721, 1759, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2722, 1760, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2723, 1761, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2724, 1762, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2725, 1763, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2726, 1764, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2727, 1765, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2728, 1766, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2729, 1767, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2730, 1768, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2731, 1769, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2732, 1772, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2733, 1773, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2734, 1774, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2735, 1776, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2736, 1777, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2737, 1778, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2738, 1779, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2739, 1780, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2740, 1781, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2741, 1783, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2742, 1784, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2743, 1785, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2744, 1786, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2745, 1788, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2746, 1789, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2747, 1790, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2748, 1791, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2749, 1792, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2750, 1793, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2751, 1794, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2752, 1795, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2753, 1796, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2754, 1797, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2755, 1798, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2756, 1799, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2757, 1800, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2758, 1803, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2759, 1804, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2760, 1805, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2761, 1806, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2762, 1808, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2763, 1809, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2764, 1810, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2765, 1811, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2766, 1813, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2767, 1814, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2768, 1815, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2769, 1817, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2770, 1818, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2771, 1819, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2772, 1820, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2773, 1821, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2774, 1822, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2775, 1823, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2776, 1824, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2777, 1825, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2778, 1826, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2779, 1827, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2780, 1828, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2781, 1829, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2782, 1834, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2783, 1837, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21'),
(2784, 1846, 1, '2025-09-02 18:17:21', '2025-09-02 18:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `maid_job_titles`
--

CREATE TABLE `maid_job_titles` (
  `id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `job_title_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maid_job_titles`
--

INSERT INTO `maid_job_titles` (`id`, `maid_id`, `job_title_id`, `created_at`, `updated_at`) VALUES
(1273, 1323, 21, NULL, NULL),
(1274, 1324, 21, NULL, NULL),
(1275, 1325, 21, NULL, NULL),
(1276, 1326, 21, NULL, NULL),
(1277, 1327, 21, NULL, NULL),
(1278, 1328, 21, NULL, NULL),
(1279, 1329, 21, NULL, NULL),
(1280, 1330, 21, NULL, NULL),
(1281, 1331, 21, NULL, NULL),
(1282, 1332, 21, NULL, NULL),
(1283, 1333, 21, NULL, NULL),
(1284, 1334, 21, NULL, NULL),
(1285, 1335, 21, NULL, NULL),
(1286, 1336, 21, NULL, NULL),
(1287, 1337, 21, NULL, NULL),
(1288, 1338, 21, NULL, NULL),
(1289, 1339, 21, NULL, NULL),
(1290, 1340, 21, NULL, NULL),
(1291, 1341, 21, NULL, NULL),
(1292, 1342, 21, NULL, NULL),
(1293, 1343, 21, NULL, NULL),
(1294, 1344, 21, NULL, NULL),
(1295, 1345, 21, NULL, NULL),
(1296, 1346, 21, NULL, NULL),
(1297, 1347, 21, NULL, NULL),
(1298, 1348, 21, NULL, NULL),
(1299, 1349, 21, NULL, NULL),
(1300, 1350, 21, NULL, NULL),
(1301, 1351, 21, NULL, NULL),
(1302, 1352, 21, NULL, NULL),
(1303, 1353, 21, NULL, NULL),
(1304, 1354, 21, NULL, NULL),
(1305, 1355, 21, NULL, NULL),
(1306, 1356, 21, NULL, NULL),
(1307, 1357, 21, NULL, NULL),
(1308, 1358, 21, NULL, NULL),
(1309, 1359, 21, NULL, NULL),
(1310, 1360, 21, NULL, NULL),
(1311, 1361, 21, NULL, NULL),
(1312, 1362, 21, NULL, NULL),
(1313, 1363, 21, NULL, NULL),
(1314, 1364, 21, NULL, NULL),
(1315, 1365, 21, NULL, NULL),
(1316, 1366, 21, NULL, NULL),
(1317, 1367, 21, NULL, NULL),
(1318, 1368, 21, NULL, NULL),
(1319, 1369, 21, NULL, NULL),
(1320, 1370, 21, NULL, NULL),
(1321, 1371, 21, NULL, NULL),
(1322, 1372, 21, NULL, NULL),
(1323, 1373, 21, NULL, NULL),
(1324, 1374, 21, NULL, NULL),
(1325, 1375, 21, NULL, NULL),
(1326, 1376, 21, NULL, NULL),
(1327, 1377, 21, NULL, NULL),
(1328, 1378, 21, NULL, NULL),
(1329, 1379, 21, NULL, NULL),
(1330, 1380, 21, NULL, NULL),
(1331, 1381, 21, NULL, NULL),
(1332, 1382, 21, NULL, NULL),
(1333, 1383, 21, NULL, NULL),
(1334, 1384, 21, NULL, NULL),
(1335, 1385, 21, NULL, NULL),
(1336, 1386, 21, NULL, NULL),
(1337, 1387, 21, NULL, NULL),
(1338, 1388, 21, NULL, NULL),
(1339, 1389, 21, NULL, NULL),
(1340, 1390, 21, NULL, NULL),
(1342, 1392, 21, NULL, NULL),
(1343, 1393, 21, NULL, NULL),
(1344, 1394, 21, NULL, NULL),
(1345, 1395, 21, NULL, NULL),
(1346, 1396, 21, NULL, NULL),
(1347, 1397, 21, NULL, NULL),
(1348, 1398, 21, NULL, NULL),
(1349, 1399, 21, NULL, NULL),
(1350, 1400, 21, NULL, NULL),
(1351, 1401, 21, NULL, NULL),
(1352, 1402, 21, NULL, NULL),
(1353, 1403, 21, NULL, NULL),
(1354, 1404, 21, NULL, NULL),
(1355, 1405, 21, NULL, NULL),
(1356, 1406, 21, NULL, NULL),
(1357, 1407, 21, NULL, NULL),
(1358, 1408, 21, NULL, NULL),
(1359, 1409, 21, NULL, NULL),
(1360, 1410, 21, NULL, NULL),
(1361, 1411, 21, NULL, NULL),
(1362, 1412, 21, NULL, NULL),
(1363, 1413, 21, NULL, NULL),
(1364, 1414, 21, NULL, NULL),
(1365, 1415, 21, NULL, NULL),
(1366, 1416, 21, NULL, NULL),
(1367, 1417, 21, NULL, NULL),
(1368, 1418, 21, NULL, NULL),
(1369, 1419, 21, NULL, NULL),
(1370, 1420, 21, NULL, NULL),
(1371, 1421, 21, NULL, NULL),
(1372, 1422, 21, NULL, NULL),
(1373, 1423, 21, NULL, NULL),
(1374, 1424, 21, NULL, NULL),
(1375, 1425, 21, NULL, NULL),
(1376, 1426, 21, NULL, NULL),
(1377, 1427, 21, NULL, NULL),
(1378, 1428, 21, NULL, NULL),
(1379, 1429, 21, NULL, NULL),
(1380, 1430, 21, NULL, NULL),
(1381, 1431, 21, NULL, NULL),
(1382, 1432, 21, NULL, NULL),
(1383, 1433, 21, NULL, NULL),
(1384, 1434, 21, NULL, NULL),
(1385, 1435, 21, NULL, NULL),
(1386, 1436, 21, NULL, NULL),
(1387, 1437, 21, NULL, NULL),
(1388, 1438, 21, NULL, NULL),
(1389, 1439, 21, NULL, NULL),
(1390, 1440, 21, NULL, NULL),
(1391, 1441, 21, NULL, NULL),
(1392, 1442, 21, NULL, NULL),
(1393, 1443, 21, NULL, NULL),
(1394, 1444, 21, NULL, NULL),
(1396, 1446, 21, NULL, NULL),
(1399, 1449, 21, NULL, NULL),
(1400, 1450, 21, NULL, NULL),
(1401, 1451, 21, NULL, NULL),
(1402, 1452, 21, NULL, NULL),
(1403, 1453, 21, NULL, NULL),
(1404, 1454, 21, NULL, NULL),
(1405, 1455, 21, NULL, NULL),
(1406, 1456, 21, NULL, NULL),
(1407, 1457, 21, NULL, NULL),
(1408, 1458, 21, NULL, NULL),
(1410, 1460, 21, NULL, NULL),
(1411, 1461, 21, NULL, NULL),
(1412, 1462, 21, NULL, NULL),
(1413, 1463, 21, NULL, NULL),
(1414, 1464, 21, NULL, NULL),
(1415, 1465, 21, NULL, NULL),
(1416, 1466, 21, NULL, NULL),
(1417, 1467, 21, NULL, NULL),
(1418, 1468, 21, NULL, NULL),
(1419, 1469, 21, NULL, NULL),
(1420, 1470, 21, NULL, NULL),
(1422, 1472, 21, NULL, NULL),
(1423, 1473, 21, NULL, NULL),
(1428, 1478, 21, NULL, NULL),
(1429, 1479, 21, NULL, NULL),
(1430, 1480, 21, NULL, NULL),
(1431, 1481, 21, NULL, NULL),
(1432, 1482, 21, NULL, NULL),
(1433, 1483, 21, NULL, NULL),
(1434, 1484, 21, NULL, NULL),
(1435, 1485, 21, NULL, NULL),
(1436, 1486, 21, NULL, NULL),
(1437, 1487, 21, NULL, NULL),
(1438, 1488, 21, NULL, NULL),
(1439, 1489, 21, NULL, NULL),
(1440, 1490, 21, NULL, NULL),
(1441, 1491, 21, NULL, NULL),
(1442, 1492, 21, NULL, NULL),
(1443, 1493, 21, NULL, NULL),
(1444, 1494, 21, NULL, NULL),
(1445, 1495, 21, NULL, NULL),
(1446, 1496, 21, NULL, NULL),
(1447, 1497, 21, NULL, NULL),
(1448, 1498, 21, NULL, NULL),
(1449, 1499, 21, NULL, NULL),
(1450, 1500, 21, NULL, NULL),
(1451, 1501, 21, NULL, NULL),
(1452, 1502, 21, NULL, NULL),
(1453, 1503, 21, NULL, NULL),
(1454, 1504, 21, NULL, NULL),
(1455, 1505, 21, NULL, NULL),
(1456, 1506, 21, NULL, NULL),
(1457, 1507, 21, NULL, NULL),
(1458, 1508, 21, NULL, NULL),
(1459, 1509, 21, NULL, NULL),
(1460, 1510, 21, NULL, NULL),
(1461, 1511, 21, NULL, NULL),
(1462, 1512, 21, NULL, NULL),
(1463, 1513, 21, NULL, NULL),
(1464, 1514, 21, NULL, NULL),
(1465, 1515, 21, NULL, NULL),
(1466, 1516, 21, NULL, NULL),
(1467, 1517, 21, NULL, NULL),
(1468, 1518, 21, NULL, NULL),
(1469, 1519, 21, NULL, NULL),
(1470, 1520, 21, NULL, NULL),
(1471, 1521, 21, NULL, NULL),
(1472, 1522, 21, NULL, NULL),
(1473, 1523, 21, NULL, NULL),
(1474, 1524, 21, NULL, NULL),
(1475, 1525, 21, NULL, NULL),
(1476, 1526, 21, NULL, NULL),
(1477, 1527, 21, NULL, NULL),
(1478, 1528, 21, NULL, NULL),
(1479, 1529, 21, NULL, NULL),
(1480, 1530, 21, NULL, NULL),
(1481, 1531, 21, NULL, NULL),
(1482, 1532, 21, NULL, NULL),
(1483, 1533, 21, NULL, NULL),
(1484, 1534, 21, NULL, NULL),
(1485, 1535, 21, NULL, NULL),
(1486, 1536, 21, NULL, NULL),
(1487, 1537, 21, NULL, NULL),
(1488, 1538, 21, NULL, NULL),
(1489, 1539, 21, NULL, NULL),
(1490, 1540, 21, NULL, NULL),
(1491, 1541, 21, NULL, NULL),
(1492, 1542, 21, NULL, NULL),
(1493, 1543, 21, NULL, NULL),
(1494, 1544, 21, NULL, NULL),
(1495, 1545, 21, NULL, NULL),
(1496, 1546, 21, NULL, NULL),
(1497, 1547, 21, NULL, NULL),
(1498, 1548, 21, NULL, NULL),
(1499, 1549, 21, NULL, NULL),
(1500, 1550, 21, NULL, NULL),
(1501, 1551, 21, NULL, NULL),
(1502, 1552, 21, NULL, NULL),
(1503, 1553, 21, NULL, NULL),
(1504, 1554, 21, NULL, NULL),
(1505, 1555, 21, NULL, NULL),
(1506, 1556, 21, NULL, NULL),
(1507, 1557, 21, NULL, NULL),
(1508, 1558, 21, NULL, NULL),
(1509, 1559, 21, NULL, NULL),
(1510, 1560, 21, NULL, NULL),
(1511, 1561, 21, NULL, NULL),
(1512, 1562, 21, NULL, NULL),
(1513, 1563, 21, NULL, NULL),
(1514, 1564, 21, NULL, NULL),
(1515, 1565, 21, NULL, NULL),
(1527, 1577, 21, NULL, NULL),
(1528, 1578, 21, NULL, NULL),
(1529, 1579, 21, NULL, NULL),
(1530, 1580, 21, NULL, NULL),
(1531, 1581, 21, NULL, NULL),
(1532, 1582, 21, NULL, NULL),
(1533, 1583, 21, NULL, NULL),
(1534, 1584, 21, NULL, NULL),
(1535, 1585, 21, NULL, NULL),
(1536, 1586, 21, NULL, NULL),
(1537, 1587, 21, NULL, NULL),
(1538, 1588, 21, NULL, NULL),
(1539, 1589, 21, NULL, NULL),
(1540, 1590, 21, NULL, NULL),
(1541, 1591, 21, NULL, NULL),
(1542, 1592, 21, NULL, NULL),
(1543, 1593, 21, NULL, NULL),
(1544, 1594, 21, NULL, NULL),
(1545, 1595, 21, NULL, NULL),
(1546, 1596, 21, NULL, NULL),
(1547, 1597, 21, NULL, NULL),
(1548, 1598, 21, NULL, NULL),
(1549, 1599, 21, NULL, NULL),
(1550, 1600, 21, NULL, NULL),
(1551, 1601, 21, NULL, NULL),
(1552, 1602, 21, NULL, NULL),
(1553, 1603, 21, NULL, NULL),
(1554, 1604, 21, NULL, NULL),
(1555, 1605, 21, NULL, NULL),
(1556, 1606, 21, NULL, NULL),
(1557, 1607, 21, NULL, NULL),
(1558, 1608, 21, NULL, NULL),
(1559, 1609, 21, NULL, NULL),
(1560, 1610, 21, NULL, NULL),
(1561, 1611, 21, NULL, NULL),
(1562, 1612, 21, NULL, NULL),
(1563, 1613, 21, NULL, NULL),
(1564, 1614, 21, NULL, NULL),
(1565, 1615, 21, NULL, NULL),
(1566, 1616, 21, NULL, NULL),
(1567, 1617, 21, NULL, NULL),
(1568, 1618, 21, NULL, NULL),
(1569, 1619, 21, NULL, NULL),
(1570, 1620, 21, NULL, NULL),
(1571, 1621, 21, NULL, NULL),
(1572, 1622, 21, NULL, NULL),
(1573, 1623, 21, NULL, NULL),
(1574, 1624, 21, NULL, NULL),
(1575, 1625, 21, NULL, NULL),
(1576, 1626, 21, NULL, NULL),
(1577, 1627, 21, NULL, NULL),
(1578, 1628, 21, NULL, NULL),
(1579, 1629, 21, NULL, NULL),
(1580, 1630, 21, NULL, NULL),
(1581, 1631, 21, NULL, NULL),
(1582, 1632, 21, NULL, NULL),
(1583, 1633, 21, NULL, NULL),
(1584, 1634, 21, NULL, NULL),
(1585, 1635, 21, NULL, NULL),
(1586, 1636, 21, NULL, NULL),
(1587, 1637, 21, NULL, NULL),
(1588, 1638, 21, NULL, NULL),
(1589, 1639, 21, NULL, NULL),
(1590, 1640, 21, NULL, NULL),
(1591, 1641, 21, NULL, NULL),
(1592, 1642, 21, NULL, NULL),
(1593, 1643, 21, NULL, NULL),
(1594, 1644, 21, NULL, NULL),
(1595, 1645, 21, NULL, NULL),
(1596, 1646, 21, NULL, NULL),
(1597, 1647, 21, NULL, NULL),
(1598, 1648, 21, NULL, NULL),
(1599, 1649, 21, NULL, NULL),
(1600, 1650, 21, NULL, NULL),
(1601, 1651, 21, NULL, NULL),
(1602, 1652, 21, NULL, NULL),
(1603, 1653, 21, NULL, NULL),
(1604, 1654, 21, NULL, NULL),
(1605, 1655, 21, NULL, NULL),
(1606, 1656, 21, NULL, NULL),
(1607, 1657, 21, NULL, NULL),
(1608, 1658, 21, NULL, NULL),
(1609, 1659, 21, NULL, NULL),
(1610, 1660, 21, NULL, NULL),
(1611, 1661, 21, NULL, NULL),
(1612, 1662, 21, NULL, NULL),
(1613, 1663, 21, NULL, NULL),
(1614, 1664, 21, NULL, NULL),
(1615, 1665, 21, NULL, NULL),
(1616, 1666, 21, NULL, NULL),
(1617, 1667, 21, NULL, NULL),
(1618, 1668, 21, NULL, NULL),
(1619, 1669, 21, NULL, NULL),
(1620, 1670, 21, NULL, NULL),
(1621, 1671, 21, NULL, NULL),
(1622, 1672, 21, NULL, NULL),
(1623, 1673, 21, NULL, NULL),
(1624, 1674, 21, NULL, NULL),
(1625, 1675, 21, NULL, NULL),
(1626, 1676, 21, NULL, NULL),
(1627, 1677, 21, NULL, NULL),
(1628, 1678, 21, NULL, NULL),
(1629, 1679, 21, NULL, NULL),
(1630, 1680, 21, NULL, NULL),
(1631, 1681, 21, NULL, NULL),
(1632, 1682, 21, NULL, NULL),
(1633, 1683, 21, NULL, NULL),
(1634, 1684, 21, NULL, NULL),
(1635, 1685, 21, NULL, NULL),
(1636, 1686, 21, NULL, NULL),
(1637, 1687, 21, NULL, NULL),
(1638, 1688, 21, NULL, NULL),
(1639, 1689, 21, NULL, NULL),
(1640, 1690, 21, NULL, NULL),
(1641, 1691, 21, NULL, NULL),
(1642, 1692, 21, NULL, NULL),
(1643, 1693, 21, NULL, NULL),
(1644, 1694, 21, NULL, NULL),
(1645, 1695, 21, NULL, NULL),
(1646, 1696, 21, NULL, NULL),
(1647, 1697, 21, NULL, NULL),
(1648, 1698, 21, NULL, NULL),
(1649, 1699, 21, NULL, NULL),
(1650, 1700, 21, NULL, NULL),
(1651, 1701, 21, NULL, NULL),
(1652, 1702, 21, NULL, NULL),
(1653, 1703, 21, NULL, NULL),
(1654, 1704, 21, NULL, NULL),
(1655, 1705, 21, NULL, NULL),
(1656, 1706, 21, NULL, NULL),
(1657, 1707, 21, NULL, NULL),
(1658, 1708, 21, NULL, NULL),
(1659, 1709, 21, NULL, NULL),
(1660, 1710, 21, NULL, NULL),
(1661, 1711, 21, NULL, NULL),
(1662, 1712, 21, NULL, NULL),
(1663, 1713, 21, NULL, NULL),
(1664, 1714, 21, NULL, NULL),
(1665, 1715, 21, NULL, NULL),
(1666, 1716, 21, NULL, NULL),
(1667, 1717, 21, NULL, NULL),
(1668, 1718, 21, NULL, NULL),
(1669, 1719, 21, NULL, NULL),
(1670, 1720, 21, NULL, NULL),
(1671, 1721, 21, NULL, NULL),
(1672, 1722, 21, NULL, NULL),
(1673, 1723, 21, NULL, NULL),
(1674, 1724, 21, NULL, NULL),
(1675, 1725, 21, NULL, NULL),
(1676, 1726, 21, NULL, NULL),
(1677, 1727, 21, NULL, NULL),
(1678, 1728, 21, NULL, NULL),
(1679, 1729, 21, NULL, NULL),
(1680, 1730, 21, NULL, NULL),
(1681, 1731, 21, NULL, NULL),
(1682, 1732, 21, NULL, NULL),
(1683, 1733, 21, NULL, NULL),
(1684, 1734, 21, NULL, NULL),
(1685, 1735, 21, NULL, NULL),
(1686, 1736, 21, NULL, NULL),
(1687, 1737, 21, NULL, NULL),
(1688, 1738, 21, NULL, NULL),
(1689, 1739, 21, NULL, NULL),
(1690, 1740, 21, NULL, NULL),
(1691, 1741, 21, NULL, NULL),
(1692, 1742, 21, NULL, NULL),
(1693, 1743, 21, NULL, NULL),
(1694, 1744, 21, NULL, NULL),
(1695, 1745, 21, NULL, NULL),
(1696, 1746, 21, NULL, NULL),
(1697, 1747, 21, NULL, NULL),
(1698, 1748, 21, NULL, NULL),
(1699, 1749, 21, NULL, NULL),
(1700, 1750, 21, NULL, NULL),
(1701, 1751, 21, NULL, NULL),
(1702, 1752, 21, NULL, NULL),
(1703, 1753, 21, NULL, NULL),
(1704, 1754, 21, NULL, NULL),
(1705, 1755, 21, NULL, NULL),
(1706, 1756, 21, NULL, NULL),
(1707, 1757, 21, NULL, NULL),
(1708, 1758, 21, NULL, NULL),
(1709, 1759, 21, NULL, NULL),
(1710, 1760, 21, NULL, NULL),
(1711, 1761, 21, NULL, NULL),
(1712, 1762, 21, NULL, NULL),
(1713, 1763, 21, NULL, NULL),
(1714, 1764, 21, NULL, NULL),
(1715, 1765, 21, NULL, NULL),
(1716, 1766, 21, NULL, NULL),
(1717, 1767, 21, NULL, NULL),
(1718, 1768, 21, NULL, NULL),
(1719, 1769, 21, NULL, NULL),
(1720, 1772, 21, NULL, NULL),
(1723, 1773, 21, NULL, NULL),
(1724, 1774, 21, NULL, NULL),
(1725, 1775, 21, NULL, NULL),
(1726, 1776, 21, NULL, NULL),
(1727, 1777, 21, NULL, NULL),
(1728, 1778, 21, NULL, NULL),
(1729, 1779, 21, NULL, NULL),
(1730, 1780, 21, NULL, NULL),
(1731, 1781, 21, NULL, NULL),
(1732, 1782, 21, NULL, NULL),
(1733, 1783, 21, NULL, NULL),
(1734, 1784, 21, NULL, NULL),
(1735, 1785, 21, NULL, NULL),
(1736, 1786, 21, NULL, NULL),
(1737, 1787, 21, NULL, NULL),
(1738, 1788, 21, NULL, NULL),
(1739, 1789, 21, NULL, NULL),
(1740, 1790, 21, NULL, NULL),
(1741, 1791, 21, NULL, NULL),
(1742, 1792, 21, NULL, NULL),
(1743, 1793, 21, NULL, NULL),
(1744, 1794, 21, NULL, NULL),
(1745, 1795, 21, NULL, NULL),
(1746, 1796, 21, NULL, NULL),
(1747, 1797, 21, NULL, NULL),
(1748, 1798, 21, NULL, NULL),
(1749, 1799, 21, NULL, NULL),
(1750, 1800, 21, NULL, NULL),
(1751, 1801, 21, NULL, NULL),
(1752, 1802, 21, NULL, NULL),
(1753, 1803, 21, NULL, NULL),
(1754, 1804, 21, NULL, NULL),
(1755, 1805, 21, NULL, NULL),
(1756, 1806, 21, NULL, NULL),
(1757, 1807, 21, NULL, NULL),
(1758, 1808, 21, NULL, NULL),
(1759, 1809, 21, NULL, NULL),
(1760, 1810, 21, NULL, NULL),
(1761, 1811, 21, NULL, NULL),
(1762, 1812, 21, NULL, NULL),
(1763, 1813, 21, NULL, NULL),
(1764, 1814, 21, NULL, NULL),
(1765, 1815, 21, NULL, NULL),
(1766, 1816, 21, NULL, NULL),
(1767, 1817, 21, NULL, NULL),
(1768, 1818, 21, NULL, NULL),
(1769, 1819, 21, NULL, NULL),
(1770, 1820, 21, NULL, NULL),
(1771, 1821, 21, NULL, NULL),
(1772, 1822, 21, NULL, NULL),
(1773, 1823, 21, NULL, NULL),
(1774, 1824, 21, NULL, NULL),
(1775, 1825, 21, NULL, NULL),
(1776, 1826, 21, NULL, NULL),
(1777, 1827, 21, NULL, NULL),
(1778, 1828, 21, NULL, NULL),
(1779, 1829, 21, NULL, NULL),
(1780, 1830, 21, NULL, NULL),
(1781, 1831, 21, NULL, NULL),
(1782, 1832, 21, NULL, NULL),
(1783, 1833, 21, NULL, NULL),
(1784, 1834, 21, NULL, NULL),
(1785, 1835, 21, NULL, NULL),
(1786, 1836, 21, NULL, NULL),
(1787, 1837, 21, NULL, NULL),
(1788, 1838, 21, NULL, NULL),
(1790, 1840, 21, NULL, NULL),
(1791, 1841, 21, NULL, NULL),
(1792, 1842, 21, NULL, NULL),
(1794, 1844, 21, NULL, NULL),
(1795, 1845, 21, NULL, NULL),
(1796, 1846, 21, NULL, NULL),
(1797, 1847, 21, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maid_translations`
--

CREATE TABLE `maid_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `languages` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `marital_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maid_translations`
--

INSERT INTO `maid_translations` (`id`, `maid_id`, `locale`, `languages`, `marital_status`, `created_at`, `updated_at`) VALUES
(2254, 1624, 'ar', '[\"amharic\"]', '', '2025-07-06 19:25:24', '2025-07-06 19:25:24'),
(2255, 1546, 'ar', 'amharic', 'عازبة', '2025-07-06 19:34:46', '2025-07-06 19:34:46'),
(2256, 1625, 'ar', '[\"amharic\"]', '', '2025-07-07 16:16:30', '2025-07-07 16:16:30'),
(2257, 1626, 'ar', '[\"amharic\"]', '', '2025-07-07 16:17:58', '2025-07-07 16:17:58'),
(2258, 1627, 'ar', '[\"amharic\"]', '', '2025-07-07 16:18:53', '2025-07-07 16:18:53'),
(2259, 1628, 'ar', '[\"amharic\"]', '', '2025-07-07 16:20:15', '2025-07-07 16:20:15'),
(2260, 1629, 'ar', '[\"amharic\"]', '', '2025-07-07 16:21:26', '2025-07-07 16:21:26'),
(2261, 1630, 'ar', '[\"amharic\"]', '', '2025-07-07 16:25:07', '2025-07-07 16:25:07'),
(2262, 1631, 'ar', '[\"amharic\"]', '', '2025-07-07 16:26:22', '2025-07-07 16:26:22'),
(2263, 1632, 'ar', '[\"amharic\"]', '', '2025-07-07 16:27:29', '2025-07-07 16:27:29'),
(2264, 1633, 'ar', '[\"amharic\"]', '', '2025-07-07 16:28:51', '2025-07-07 16:28:51'),
(2265, 1634, 'ar', '[\"amharic\"]', '', '2025-07-07 16:30:45', '2025-07-07 16:30:45'),
(2266, 1635, 'ar', '[\"amharic\"]', '', '2025-07-07 16:31:51', '2025-07-07 16:31:51'),
(2267, 1636, 'ar', '[\"amharic\"]', '', '2025-07-07 16:33:05', '2025-07-07 16:33:05'),
(2268, 1637, 'ar', '[\"amharic\"]', '', '2025-07-07 16:34:04', '2025-07-07 16:34:04'),
(2269, 1638, 'ar', '[\"amharic\"]', '', '2025-07-07 16:35:30', '2025-07-07 16:35:30'),
(2270, 1639, 'ar', '[\"amharic\"]', '', '2025-07-07 16:37:10', '2025-07-07 16:37:10'),
(2271, 1640, 'ar', '[\"amharic\"]', '', '2025-07-07 16:38:12', '2025-07-07 16:38:12'),
(2272, 1641, 'ar', '[\"amharic\"]', '', '2025-07-07 16:39:06', '2025-07-07 16:39:06'),
(2273, 1642, 'ar', '[\"amharic\"]', '', '2025-07-07 16:39:55', '2025-07-07 16:39:55'),
(2274, 1643, 'ar', '[\"amharic\"]', '', '2025-07-07 16:41:02', '2025-07-07 16:41:02'),
(2275, 1644, 'ar', '[\"amharic\"]', '', '2025-07-07 16:41:56', '2025-07-07 16:41:56'),
(2276, 1645, 'ar', '[\"amharic\"]', '', '2025-07-07 16:43:23', '2025-07-07 16:43:23'),
(2277, 1646, 'ar', '[\"amharic\"]', '', '2025-07-07 16:47:13', '2025-07-07 16:47:13'),
(2278, 1647, 'ar', '[\"amharic\"]', '', '2025-07-07 16:48:39', '2025-07-07 16:48:39'),
(2279, 1648, 'ar', '[\"amharic\"]', '', '2025-07-07 16:49:41', '2025-07-07 16:49:41'),
(2280, 1649, 'ar', '[\"amharic\"]', '', '2025-07-07 16:50:43', '2025-07-07 16:50:43'),
(2281, 1650, 'ar', '[\"amharic\"]', '', '2025-07-07 16:51:54', '2025-07-07 16:51:54'),
(2282, 1651, 'ar', '[\"amharic\"]', '', '2025-07-07 16:53:28', '2025-07-07 16:53:28'),
(2283, 1652, 'ar', '[\"amharic\"]', '', '2025-07-07 16:54:31', '2025-07-07 16:54:31'),
(2284, 1653, 'ar', '[\"amharic\"]', '', '2025-07-07 16:55:36', '2025-07-07 16:55:36'),
(2285, 1654, 'ar', '[\"amharic\"]', '', '2025-07-07 16:56:47', '2025-07-07 16:56:47'),
(2286, 1655, 'ar', '[\"amharic\"]', '', '2025-07-07 16:57:43', '2025-07-07 16:57:43'),
(2287, 1656, 'ar', '[\"amharic\"]', '', '2025-07-07 16:58:50', '2025-07-07 16:58:50'),
(2288, 1657, 'ar', '[\"amharic\"]', '', '2025-07-07 17:00:16', '2025-07-07 17:00:16'),
(2289, 1658, 'ar', '[\"amharic\"]', '', '2025-07-07 17:01:20', '2025-07-07 17:01:20'),
(2290, 1659, 'ar', '[\"amharic\"]', '', '2025-07-07 17:03:16', '2025-07-07 17:03:16'),
(2291, 1660, 'ar', '[\"amharic\"]', '', '2025-07-07 17:04:20', '2025-07-07 17:04:20'),
(2292, 1661, 'ar', '[\"amharic\"]', '', '2025-07-07 17:05:36', '2025-07-07 17:05:36'),
(2293, 1662, 'ar', '[\"amharic\"]', '', '2025-07-07 17:06:36', '2025-07-07 17:06:36'),
(2294, 1663, 'ar', '[\"amharic\"]', '', '2025-07-07 17:07:40', '2025-07-07 17:07:40'),
(2295, 1664, 'ar', '[\"amharic\"]', '', '2025-07-07 17:08:50', '2025-07-07 17:08:50'),
(2296, 1665, 'ar', '[\"amharic\"]', '', '2025-07-07 17:09:55', '2025-07-07 17:09:55'),
(2297, 1666, 'ar', '[\"amharic\"]', '', '2025-07-07 17:12:24', '2025-07-07 17:12:24'),
(2298, 1667, 'ar', '[\"amharic\"]', '', '2025-07-07 17:14:10', '2025-07-07 17:14:10'),
(2299, 1668, 'ar', '[\"amharic\"]', '', '2025-07-07 17:17:42', '2025-07-07 17:17:42'),
(2300, 1669, 'ar', '[\"amharic\"]', '', '2025-07-07 17:18:42', '2025-07-07 17:18:42'),
(2301, 1670, 'ar', '[\"amharic\"]', '', '2025-07-07 17:19:56', '2025-07-07 17:19:56'),
(2302, 1671, 'ar', '[\"amharic\"]', '', '2025-07-07 17:20:47', '2025-07-07 17:20:47'),
(2303, 1672, 'ar', '[\"amharic\"]', '', '2025-07-07 17:21:54', '2025-07-07 17:21:54'),
(2304, 1673, 'ar', '[\"amharic\"]', '', '2025-07-07 17:23:08', '2025-07-07 17:23:08'),
(2305, 1674, 'ar', '[\"amharic\"]', '', '2025-07-07 17:24:03', '2025-07-07 17:24:03'),
(2306, 1675, 'ar', '[\"amharic\"]', '', '2025-07-07 17:24:52', '2025-07-07 17:24:52'),
(2307, 1676, 'ar', '[\"amharic\"]', '', '2025-07-07 17:25:55', '2025-07-07 17:25:55'),
(2308, 1677, 'ar', '[\"amharic\"]', '', '2025-07-07 17:26:46', '2025-07-07 17:26:46'),
(2309, 1678, 'ar', '[\"amharic\"]', '', '2025-07-07 17:27:50', '2025-07-07 17:27:50'),
(2310, 1679, 'ar', '[\"amharic\"]', '', '2025-07-07 17:29:12', '2025-07-07 17:29:12'),
(2311, 1680, 'ar', '[\"amharic\"]', '', '2025-07-07 17:31:42', '2025-07-07 17:31:42'),
(2312, 1681, 'ar', '[\"amharic\"]', '', '2025-07-07 17:32:45', '2025-07-07 17:32:45'),
(2313, 1682, 'ar', '[\"amharic\"]', '', '2025-07-07 17:33:52', '2025-07-07 17:33:52'),
(2314, 1683, 'ar', '[\"amharic\"]', '', '2025-07-07 17:34:44', '2025-07-07 17:34:44'),
(2315, 1684, 'ar', '[\"amharic\"]', '', '2025-07-07 17:35:49', '2025-07-07 17:35:49'),
(2316, 1685, 'ar', '[\"amharic\"]', '', '2025-07-07 17:36:44', '2025-07-07 17:36:44'),
(2317, 1686, 'ar', '[\"amharic\"]', '', '2025-07-07 17:38:18', '2025-07-07 17:38:18'),
(2318, 1687, 'ar', '[\"amharic\"]', '', '2025-07-07 17:39:33', '2025-07-07 17:39:33'),
(2319, 1688, 'ar', '[\"amharic\"]', '', '2025-07-07 17:41:13', '2025-07-07 17:41:13'),
(2320, 1689, 'ar', '[\"amharic\"]', '', '2025-07-07 17:42:16', '2025-07-07 17:42:16'),
(2321, 1690, 'ar', '[\"amharic\"]', '', '2025-07-07 17:43:20', '2025-07-07 17:43:20'),
(2322, 1691, 'ar', '[\"amharic\"]', '', '2025-07-07 17:44:17', '2025-07-07 17:44:17'),
(2323, 1692, 'ar', '[\"amharic\"]', '', '2025-07-07 17:49:55', '2025-07-07 17:49:55'),
(2324, 1693, 'ar', '[\"amharic\"]', '', '2025-07-07 17:52:17', '2025-07-07 17:52:17'),
(2325, 1694, 'ar', '[\"amharic\"]', '', '2025-07-07 17:53:57', '2025-07-07 17:53:57'),
(2326, 1695, 'ar', '[\"amharic\"]', '', '2025-07-07 17:54:57', '2025-07-07 17:54:57'),
(2327, 1696, 'ar', '[\"amharic\"]', '', '2025-07-07 17:55:50', '2025-07-07 17:55:50'),
(2328, 1697, 'ar', '[\"amharic\"]', '', '2025-07-07 17:56:36', '2025-07-07 17:56:36'),
(2329, 1698, 'ar', '[\"amharic\"]', '', '2025-07-07 17:57:28', '2025-07-07 17:57:28'),
(2330, 1699, 'ar', '[\"amharic\"]', '', '2025-07-07 17:58:41', '2025-07-07 17:58:41'),
(2331, 1700, 'ar', '[\"amharic\"]', '', '2025-07-07 17:59:47', '2025-07-07 17:59:47'),
(2332, 1701, 'ar', '[\"amharic\"]', '', '2025-07-07 18:00:35', '2025-07-07 18:00:35'),
(2333, 1702, 'ar', '[\"amharic\"]', '', '2025-07-07 18:01:26', '2025-07-07 18:01:26'),
(2334, 1703, 'ar', '[\"amharic\"]', '', '2025-07-07 18:02:23', '2025-07-07 18:02:23'),
(2335, 1704, 'ar', '[\"amharic\"]', '', '2025-07-07 18:03:18', '2025-07-07 18:03:18'),
(2336, 1705, 'ar', '[\"amharic\"]', '', '2025-07-07 18:04:33', '2025-07-07 18:04:33'),
(2337, 1706, 'ar', '[\"amharic\"]', '', '2025-07-07 18:05:53', '2025-07-07 18:05:53'),
(2338, 1707, 'ar', '[\"amharic\"]', '', '2025-07-07 18:06:38', '2025-07-07 18:06:38'),
(2339, 1708, 'ar', '[\"amharic\"]', '', '2025-07-07 18:07:36', '2025-07-07 18:07:36'),
(2340, 1709, 'ar', '[\"amharic\"]', '', '2025-07-07 18:08:21', '2025-07-07 18:08:21'),
(2341, 1710, 'ar', '[\"amharic\"]', '', '2025-07-07 18:09:24', '2025-07-07 18:09:24'),
(2342, 1711, 'ar', '[\"amharic\"]', '', '2025-07-07 18:10:16', '2025-07-07 18:10:16'),
(2343, 1712, 'ar', '[\"amharic\"]', '', '2025-07-07 18:11:03', '2025-07-07 18:11:03'),
(2344, 1713, 'ar', '[\"amharic\"]', '', '2025-07-07 18:12:00', '2025-07-07 18:12:00'),
(2345, 1714, 'ar', '[\"amharic\"]', '', '2025-07-07 18:12:53', '2025-07-07 18:12:53'),
(2346, 1715, 'ar', '[\"amharic\"]', '', '2025-07-07 18:13:44', '2025-07-07 18:13:44'),
(2347, 1716, 'ar', '[\"amharic\"]', '', '2025-07-07 18:14:44', '2025-07-07 18:14:44'),
(2348, 1717, 'ar', '[\"amharic\"]', '', '2025-07-07 18:15:41', '2025-07-07 18:15:41'),
(2349, 1718, 'ar', '[\"amharic\"]', '', '2025-07-07 18:16:34', '2025-07-07 18:16:34'),
(2350, 1719, 'ar', '[\"amharic\"]', '', '2025-07-07 18:17:28', '2025-07-07 18:17:28'),
(2351, 1720, 'ar', '[\"amharic\"]', '', '2025-07-07 18:18:29', '2025-07-07 18:18:29'),
(2352, 1721, 'ar', '[\"amharic\"]', '', '2025-07-07 18:19:23', '2025-07-07 18:19:23'),
(2353, 1722, 'ar', '[\"amharic\"]', '', '2025-07-07 18:20:11', '2025-07-07 18:20:11'),
(2354, 1723, 'ar', '[\"amharic\"]', '', '2025-07-07 18:21:06', '2025-07-07 18:21:06'),
(2355, 1724, 'ar', '[\"amharic\"]', '', '2025-07-07 18:22:05', '2025-07-07 18:22:05'),
(2356, 1725, 'ar', '[\"amharic\"]', '', '2025-07-07 18:23:00', '2025-07-07 18:23:00'),
(2357, 1726, 'ar', '[\"amharic\"]', '', '2025-07-07 18:24:01', '2025-07-07 18:24:01'),
(2358, 1727, 'ar', '[\"amharic\"]', '', '2025-07-07 18:24:57', '2025-07-07 18:24:57'),
(2359, 1728, 'ar', '[\"amharic\"]', '', '2025-07-07 18:25:49', '2025-07-07 18:25:49'),
(2360, 1729, 'ar', '[\"amharic\"]', '', '2025-07-07 18:29:10', '2025-07-07 18:29:10'),
(2361, 1730, 'ar', '[\"amharic\"]', '', '2025-07-07 18:30:03', '2025-07-07 18:30:03'),
(2362, 1731, 'ar', '[\"amharic\"]', '', '2025-07-07 18:31:01', '2025-07-07 18:31:01'),
(2363, 1732, 'ar', '[\"amharic\"]', '', '2025-07-07 18:32:11', '2025-07-07 18:32:11'),
(2364, 1733, 'ar', '[\"amharic\"]', '', '2025-07-07 18:33:13', '2025-07-07 18:33:13'),
(2365, 1734, 'ar', '[\"amharic\"]', '', '2025-07-07 18:34:04', '2025-07-07 18:34:04'),
(2366, 1735, 'ar', '[\"amharic\"]', '', '2025-07-07 18:35:07', '2025-07-07 18:35:07'),
(2367, 1736, 'ar', '[\"amharic\"]', '', '2025-07-07 18:35:58', '2025-07-07 18:35:58'),
(2368, 1737, 'ar', '[\"amharic\"]', '', '2025-07-07 18:36:59', '2025-07-07 18:36:59'),
(2369, 1738, 'ar', '[\"amharic\"]', '', '2025-07-07 18:38:19', '2025-07-07 18:38:19'),
(2370, 1739, 'ar', '[\"amharic\"]', '', '2025-07-07 18:39:33', '2025-07-07 18:39:33'),
(2371, 1740, 'ar', '[\"amharic\"]', '', '2025-07-07 18:40:23', '2025-07-07 18:40:23'),
(2372, 1741, 'ar', '[\"amharic\"]', '', '2025-07-07 18:41:35', '2025-07-07 18:41:35'),
(2373, 1742, 'ar', '[\"amharic\"]', '', '2025-07-07 18:42:45', '2025-07-07 18:42:45'),
(2374, 1743, 'ar', '[\"amharic\"]', '', '2025-07-07 18:43:40', '2025-07-07 18:43:40'),
(2375, 1744, 'ar', '[\"amharic\"]', '', '2025-07-07 18:45:00', '2025-07-07 18:45:00'),
(2376, 1745, 'ar', '[\"amharic\"]', '', '2025-07-07 18:45:58', '2025-07-07 18:45:58'),
(2377, 1746, 'ar', '[\"amharic\"]', '', '2025-07-07 18:47:46', '2025-07-07 18:47:46'),
(2378, 1747, 'ar', 'amharic', 'عازبة', '2025-07-07 18:48:40', '2025-07-07 18:48:56'),
(2379, 1748, 'ar', '[\"amharic\"]', '', '2025-07-07 18:50:22', '2025-07-07 18:50:22'),
(2380, 1749, 'ar', '[\"amharic\"]', '', '2025-07-07 18:52:12', '2025-07-07 18:52:12'),
(2381, 1750, 'ar', '[\"amharic\"]', '', '2025-07-07 18:53:01', '2025-07-07 18:53:01'),
(2382, 1751, 'ar', '[\"amharic\"]', '', '2025-07-07 18:54:03', '2025-07-07 18:54:03'),
(2383, 1752, 'ar', '[\"amharic\"]', '', '2025-07-07 18:55:02', '2025-07-07 18:55:02'),
(2384, 1753, 'ar', '[\"amharic\"]', '', '2025-07-07 18:55:56', '2025-07-07 18:55:56'),
(2385, 1754, 'ar', '[\"amharic\"]', '', '2025-07-07 18:57:13', '2025-07-07 18:57:13'),
(2386, 1755, 'ar', '[\"amharic\"]', '', '2025-07-07 19:00:17', '2025-07-07 19:00:17'),
(2387, 1756, 'ar', '[\"amharic\"]', '', '2025-07-07 19:01:21', '2025-07-07 19:01:21'),
(2388, 1757, 'ar', '[\"amharic\"]', '', '2025-07-07 19:02:57', '2025-07-07 19:02:57'),
(2389, 1758, 'ar', '[\"amharic\"]', '', '2025-07-07 19:03:55', '2025-07-07 19:03:55'),
(2390, 1759, 'ar', '[\"amharic\"]', '', '2025-07-07 19:05:07', '2025-07-07 19:05:07'),
(2391, 1760, 'ar', '[\"amharic\"]', '', '2025-07-07 19:18:08', '2025-07-07 19:18:08'),
(2392, 1761, 'ar', '[\"amharic\"]', '', '2025-07-07 19:19:33', '2025-07-07 19:19:33'),
(2393, 1762, 'ar', '[\"amharic\"]', '', '2025-07-07 19:20:37', '2025-07-07 19:20:37'),
(2394, 1763, 'ar', '[\"amharic\"]', '', '2025-07-07 19:21:29', '2025-07-07 19:21:29'),
(2395, 1764, 'ar', '[\"amharic\"]', '', '2025-07-07 19:36:25', '2025-07-07 19:36:25'),
(2396, 1765, 'ar', '[\"amharic\"]', '', '2025-07-07 19:38:07', '2025-07-07 19:38:07'),
(2397, 1766, 'ar', '[\"amharic\"]', '', '2025-07-07 19:39:25', '2025-07-07 19:39:25'),
(2398, 1767, 'ar', '[\"amharic\"]', '', '2025-07-07 19:41:18', '2025-07-07 19:41:18'),
(2399, 1768, 'ar', '[\"amharic\"]', '', '2025-07-07 19:42:17', '2025-07-07 19:42:17'),
(2400, 1769, 'ar', '[\"amharic\"]', '', '2025-07-07 19:44:38', '2025-07-07 19:44:38'),
(2402, 1772, 'ar', '[\"arabic\",\"mother\"]', '', '2025-07-08 16:29:26', '2025-07-08 16:29:26'),
(2404, 1773, 'ar', '[\"arabic\",\"mother\"]', '', '2025-07-08 17:12:52', '2025-07-08 17:12:52'),
(2405, 1774, 'ar', '[\"arabic\",\"mother\"]', '', '2025-07-08 17:15:31', '2025-07-08 17:15:31'),
(2406, 1775, 'ar', '[\"mother\"]', '', '2025-07-08 17:17:41', '2025-07-08 17:17:41'),
(2407, 1776, 'ar', '[\"mother\"]', '', '2025-07-08 17:19:09', '2025-07-08 17:19:09'),
(2408, 1777, 'ar', '[\"mother\"]', '', '2025-07-08 17:20:14', '2025-07-08 17:20:14'),
(2409, 1778, 'ar', '[\"mother\"]', '', '2025-07-08 17:21:28', '2025-07-08 17:21:28'),
(2410, 1779, 'ar', '[\"mother\"]', '', '2025-07-08 17:25:22', '2025-07-08 17:25:22'),
(2411, 1780, 'ar', '[\"amharic\"]', '', '2025-07-08 19:56:08', '2025-07-08 19:56:08'),
(2412, 1781, 'ar', '[\"amharic\"]', '', '2025-07-08 19:57:20', '2025-07-08 19:57:20'),
(2413, 1782, 'ar', '[\"amharic\"]', '', '2025-07-08 19:58:14', '2025-07-08 19:58:14'),
(2414, 1783, 'ar', '[\"amharic\"]', '', '2025-07-08 19:59:01', '2025-07-08 19:59:01'),
(2415, 1784, 'ar', '[\"amharic\"]', '', '2025-07-08 19:59:46', '2025-07-08 19:59:46'),
(2416, 1785, 'ar', '[\"amharic\"]', '', '2025-07-08 20:00:51', '2025-07-08 20:00:51'),
(2417, 1786, 'ar', '[\"amharic\"]', '', '2025-07-08 20:01:33', '2025-07-08 20:01:33'),
(2418, 1787, 'ar', '[\"amharic\"]', '', '2025-07-08 20:02:15', '2025-07-08 20:02:15'),
(2419, 1788, 'ar', '[\"amharic\"]', '', '2025-07-08 20:02:58', '2025-07-08 20:02:58'),
(2420, 1789, 'ar', '[\"amharic\"]', '', '2025-07-08 20:03:44', '2025-07-08 20:03:44'),
(2421, 1790, 'ar', '[\"amharic\"]', '', '2025-07-08 20:04:24', '2025-07-08 20:04:24'),
(2422, 1791, 'ar', '[\"amharic\"]', '', '2025-07-08 20:05:09', '2025-07-08 20:05:09'),
(2423, 1792, 'ar', '[\"amharic\"]', '', '2025-07-08 20:05:48', '2025-07-08 20:05:48'),
(2424, 1793, 'ar', '[\"amharic\"]', '', '2025-07-08 20:06:27', '2025-07-08 20:06:27'),
(2425, 1794, 'ar', '[\"amharic\"]', '', '2025-07-08 20:07:11', '2025-07-08 20:07:11'),
(2426, 1795, 'ar', '[\"amharic\"]', '', '2025-07-08 20:08:08', '2025-07-08 20:08:08'),
(2427, 1796, 'ar', '[\"amharic\"]', '', '2025-07-08 20:08:53', '2025-07-08 20:08:53'),
(2428, 1797, 'ar', '[\"amharic\"]', '', '2025-07-08 20:09:36', '2025-07-08 20:09:36'),
(2429, 1798, 'ar', '[\"amharic\"]', '', '2025-07-08 20:10:15', '2025-07-08 20:10:15'),
(2430, 1799, 'ar', '[\"amharic\"]', '', '2025-07-08 20:10:56', '2025-07-08 20:10:56'),
(2431, 1800, 'ar', '[\"amharic\"]', '', '2025-07-08 20:11:55', '2025-07-08 20:11:55'),
(2432, 1801, 'ar', '[\"amharic\"]', '', '2025-07-08 20:12:50', '2025-07-08 20:12:50'),
(2433, 1802, 'ar', '[\"amharic\"]', '', '2025-07-08 20:13:44', '2025-07-08 20:13:44'),
(2434, 1803, 'ar', '[\"amharic\"]', '', '2025-07-08 20:15:10', '2025-07-08 20:15:10'),
(2435, 1804, 'ar', '[\"amharic\"]', '', '2025-07-08 20:16:08', '2025-07-08 20:16:08'),
(2436, 1805, 'ar', '[\"amharic\"]', '', '2025-07-08 20:16:57', '2025-07-08 20:16:57'),
(2437, 1806, 'ar', '[\"amharic\"]', '', '2025-07-08 20:17:43', '2025-07-08 20:17:43'),
(2438, 1807, 'ar', '[\"amharic\"]', '', '2025-07-08 20:18:27', '2025-07-08 20:18:27'),
(2439, 1808, 'ar', '[\"amharic\"]', '', '2025-07-08 20:19:13', '2025-07-08 20:19:13'),
(2440, 1809, 'ar', '[\"amharic\"]', '', '2025-07-08 20:20:03', '2025-07-08 20:20:03'),
(2441, 1810, 'ar', '[\"amharic\"]', '', '2025-07-08 20:20:58', '2025-07-08 20:20:58'),
(2442, 1811, 'ar', '[\"amharic\"]', '', '2025-07-08 20:21:49', '2025-07-08 20:21:49'),
(2443, 1812, 'ar', '[\"amharic\"]', '', '2025-07-08 20:22:42', '2025-07-08 20:22:42'),
(2444, 1813, 'ar', '[\"amharic\"]', '', '2025-07-08 20:24:04', '2025-07-08 20:24:04'),
(2445, 1814, 'ar', '[\"amharic\"]', '', '2025-07-08 20:25:02', '2025-07-08 20:25:02'),
(2446, 1815, 'ar', '[\"amharic\"]', '', '2025-07-08 20:26:04', '2025-07-08 20:26:04'),
(2447, 1816, 'ar', '[\"amharic\"]', '', '2025-07-08 20:32:07', '2025-07-08 20:32:07'),
(2448, 1817, 'ar', '[\"amharic\"]', '', '2025-07-08 20:33:01', '2025-07-08 20:33:01'),
(2449, 1818, 'ar', '[\"amharic\"]', '', '2025-07-08 20:33:45', '2025-07-08 20:33:45'),
(2450, 1819, 'ar', '[\"amharic\"]', '', '2025-07-08 20:35:39', '2025-07-08 20:35:39'),
(2451, 1820, 'ar', '[\"amharic\"]', '', '2025-07-08 20:36:51', '2025-07-08 20:36:51'),
(2452, 1821, 'ar', '[\"amharic\"]', '', '2025-07-08 20:37:45', '2025-07-08 20:37:45'),
(2453, 1822, 'ar', '[\"amharic\"]', '', '2025-07-08 20:38:32', '2025-07-08 20:38:32'),
(2454, 1823, 'ar', '[\"amharic\"]', '', '2025-07-08 20:39:29', '2025-07-08 20:39:29'),
(2455, 1824, 'ar', '[\"amharic\"]', '', '2025-07-08 20:40:13', '2025-07-08 20:40:13'),
(2456, 1825, 'ar', '[\"amharic\"]', '', '2025-07-08 20:40:58', '2025-07-08 20:40:58'),
(2457, 1826, 'ar', '[\"amharic\"]', '', '2025-07-08 20:41:45', '2025-07-08 20:41:45'),
(2458, 1827, 'ar', '[\"amharic\"]', '', '2025-07-08 20:42:26', '2025-07-08 20:42:26'),
(2459, 1828, 'ar', '[\"amharic\"]', '', '2025-07-08 20:43:41', '2025-07-08 20:43:41'),
(2460, 1829, 'ar', '[\"amharic\"]', '', '2025-07-08 20:44:54', '2025-07-08 20:44:54'),
(2461, 1830, 'ar', '[\"amharic\"]', '', '2025-07-08 20:46:17', '2025-07-08 20:46:17'),
(2462, 1831, 'ar', '[\"english\"]', '', '2025-07-09 16:24:18', '2025-07-09 16:24:18'),
(2463, 1832, 'ar', '[\"english\"]', '', '2025-07-09 16:25:30', '2025-07-09 16:25:30'),
(2464, 1833, 'ar', '[\"english\"]', '', '2025-07-09 16:27:02', '2025-07-09 16:27:02'),
(2465, 1834, 'ar', '[\"amharic\"]', '', '2025-07-12 17:45:34', '2025-07-12 17:45:34'),
(2466, 1835, 'ar', '[\"amharic\"]', '', '2025-07-12 17:47:15', '2025-07-12 17:47:15'),
(2467, 1836, 'ar', '[\"amharic\"]', '', '2025-07-12 17:48:29', '2025-07-12 17:48:29'),
(2468, 1837, 'ar', '[\"amharic\"]', '', '2025-07-12 17:53:48', '2025-07-12 17:53:48'),
(2469, 1838, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 17:55:15', '2025-07-12 17:55:15'),
(2471, 1840, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 17:57:40', '2025-07-12 17:57:40'),
(2472, 1841, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 17:59:19', '2025-07-12 17:59:19'),
(2473, 1842, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 18:00:21', '2025-07-12 18:00:21'),
(2475, 1844, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 18:07:31', '2025-07-12 18:07:31'),
(2476, 1845, 'ar', '[\"arabic\",\"amharic\"]', '', '2025-07-12 18:16:18', '2025-07-12 18:16:18'),
(2477, 1846, 'ar', '[\"amharic\"]', '', '2025-07-12 18:19:40', '2025-07-12 18:19:40'),
(2478, 1847, 'ar', '[\"arabic\"]', '', '2025-07-14 16:48:48', '2025-07-14 16:48:48');

-- --------------------------------------------------------

--
-- Table structure for table `maid_unavailable_records`
--

CREATE TABLE `maid_unavailable_records` (
  `id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `contract_id` bigint UNSIGNED DEFAULT NULL,
  `admin_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2023_02_19_091453_create_admins_table', 1),
(6, '2023_05_01_014505_laratrust_setup_tables', 1),
(7, '2023_05_02_000115_create_settings_table', 1),
(8, '2023_05_02_000217_create_settings_translations_table', 1),
(9, '2023_05_29_124858_add_type_to_admins_table', 1),
(10, '2023_05_30_154546_create_print_setting_table', 1),
(11, '2023_05_30_154551_create_print_setting_translations_table', 1),
(12, '2023_05_30_154613_create_print_setting_details_table', 1),
(13, '2023_05_30_154626_create_print_setting_details_translations_table', 1),
(14, '2023_07_02_160044_add_is_developer_to_admins_table', 1),
(15, '2023_09_19_164442_create_develop_settings_table', 1),
(16, '2023_12_03_164136_create_contacts_table', 1),
(17, '2023_12_14_094633_create_app_visits_table', 1),
(18, '2023_12_19_152321_create_job_titles_table', 1),
(19, '2023_12_19_152729_create_job_titles_translations_table', 1),
(20, '2023_12_19_153143_create_nationalities_table', 1),
(21, '2023_12_19_153238_create_nationalities_translations_table', 1),
(22, '2023_12_19_153638_create_packages_table', 1),
(23, '2023_12_19_153718_create_packages_translations_table', 1),
(24, '2023_12_19_155206_create_sliders_table', 1),
(25, '2023_12_19_155317_create_sliders_translations_table', 1),
(26, '2023_12_19_155618_create_phone_verifications_table', 1),
(27, '2023_12_19_160101_create_steps_table', 1),
(28, '2023_12_19_160201_create_steps_translations_table', 1),
(29, '2023_12_19_160851_create_questions_table', 1),
(30, '2023_12_19_160945_create_questions_translations_table', 1),
(31, '2023_12_19_161703_create_job_types_table', 1),
(32, '2023_12_19_161713_create_religions_table', 1),
(33, '2023_12_19_161803_create_job_types_translations_table', 1),
(34, '2023_12_19_161826_create_religions_translations_table', 1),
(35, '2023_12_19_161903_create_maids_table', 1),
(36, '2023_12_19_162314_create_maid_jobs_table', 1),
(37, '2023_12_19_163416_add_sex_type_to_admins_table', 1),
(38, '2023_12_19_163553_add_phone_and_whatsapp_to_admins_table', 1),
(39, '2023_12_19_164543_create_orders_table', 1),
(40, '2023_12_19_164746_create_user_addresses_table', 1),
(41, '2023_12_19_204640_create_user_firebase_tokens_table', 1),
(42, '2023_12_20_103034_create_order_details_table', 1),
(43, '2023_12_20_103439_create_notifications_table', 1),
(44, '2023_12_20_104027_create_about_us_table', 1),
(45, '2023_12_20_104128_create_about_us_translations_table', 1),
(46, '2023_12_20_105957_create_cookies_table', 1),
(47, '2023_12_20_130059_create_services_table', 1),
(48, '2023_12_20_130129_create_services_translations_table', 1),
(49, '2023_12_20_142911_create_statistics_table', 1),
(50, '2023_12_20_142943_create_statistics_translations_table', 1),
(51, '2023_12_21_164008_create_maid_job_titles_table', 1),
(52, '2023_12_24_133149_create_complaints_table', 1),
(53, '2024_01_21_161117_add_experince_years_to_maids_table', 2),
(54, '2024_01_21_163420_add_nationality_id_to_packages_table', 2),
(56, '2024_01_22_094219_create_return_requests_table', 3),
(57, '2024_01_28_153726_add_cv_preview_to_maids_table', 4),
(58, '2024_02_05_121106_create_maid_translations_table', 5),
(59, '2024_02_05_163656_add_file_to_return_requests_table', 6),
(60, '2024_02_06_164041_create_recruitment_offices_table', 7),
(61, '2024_02_06_164149_create_recruitment_office_translations_table', 7),
(62, '2024_02_07_102258_add_office_id_to_maids_table', 7),
(63, '2024_02_14_125906_add_image_to_maids_table', 8),
(64, '2024_03_31_123621_add_feild_to_nationalities_table', 9),
(65, '2024_04_25_152017_create_shelter_places_table', 10),
(66, '2024_04_25_152124_create_shelter_place_translations_table', 10),
(67, '2024_04_28_091329_add_feilds_to_users_table', 10),
(68, '2024_04_30_110659_create_contracts_table', 10),
(69, '2024_04_30_110759_create_contract_infos_table', 10),
(70, '2024_04_30_112908_create_contract_status_logs_table', 10),
(71, '2024_04_30_113435_add_order_id_to_contracts_table', 10),
(72, '2024_04_30_123256_add_user_id_to_contracts_table', 10),
(73, '2024_05_01_132725_add_feilds_to_contract_infos_table', 10),
(74, '2024_07_14_160047_add_2_new_columns_to_maids_table', 11),
(75, '2024_07_15_162555_create_order_transfers_table', 12),
(77, '2024_07_23_144737_add_deliver_maid_to_order_details_table', 13),
(78, '2024_07_28_230347_create_cancel_contract_requests_table', 14),
(79, '2024_08_01_221804_create_shelter_workers_table', 15),
(82, '2024_08_04_100255_create_travel_histories_table', 16),
(83, '2024_08_04_100547_create_travel_history_files_table', 16),
(84, '2024_08_04_230409_create_new_settings_table', 17),
(85, '2024_08_29_122656_change_msaned_number_to_nullable_in_contract_infos_table', 18),
(86, '2024_08_29_142102_change_received_by_other_to_null_in_shelter_workers_table', 18),
(87, '2024_08_29_192147_add_columns_to_shelter_workers_table', 18),
(88, '2024_09_01_124142_add_uploads_to_shelter_workers_table', 19),
(89, '2024_09_01_155200_add_type_to_contracts_table', 19),
(94, '2024_09_02_134931_create_permission_tables', 20),
(95, '2024_09_02_140846_create_activity_log_table', 20),
(96, '2024_09_02_140847_add_event_column_to_activity_log_table', 20),
(97, '2024_09_02_140848_add_batch_uuid_column_to_activity_log_table', 20),
(98, '2024_09_04_144338_add_extra_to_travel_histories_table', 21),
(99, '2024_09_04_204929_add_transfer_type_to_order_transfers_table', 22),
(100, '2024_03_21_000000_create_new_settings_table', 23),
(101, '2024_04_28_210000_add_status_to_users_table', 24),
(102, '2025_05_03_235646_create_complaint_types_table', 25),
(103, '2024_04_30_120000_add_expiration_time_to_orders_table', 26),
(104, '2024_04_30_130000_add_columns_to_settings_table', 27),
(105, '2024_03_21_create_countries_table', 28),
(106, '2025_05_26_220329_update_contract_infos_table', 29),
(107, '2025_05_26_222353_add_beds_num_to_users_table', 30),
(108, '2025_05_26_232141_add_notes_to_contract_status_logs_table', 31),
(109, '2025_05_31_050425_add_is_pinned_to_maids_table', 32),
(110, '2024_03_21_create_recruitment_requests_table', 33),
(111, '2025_06_25_125432_add_receipt_source_to_contract_infos_table', 34),
(112, '2025_06_25_114805_add_wants_to_work_to_contract_infos_table', 35),
(113, '2025_06_12_160214_add_status_to_maids_table', 36),
(114, '2025_06_12_131318_add_name_to_maids_table', 37),
(115, '2025_07_02_165342_add_birth_date_to_maids_table', 38),
(116, '2025_07_02_175832_add_languages_to_maids_table', 39),
(117, '2025_07_13_053832_update_contracts_type_to_recruitment', 40),
(118, '2025_04_28_200644_add_is_temp_to_users_table', 41),
(119, '2025_05_12_002510_add_name_fields_to_users_table', 41),
(120, '2025_07_20_135121_update_contract_statuses_to_completed', 42),
(121, '2025_07_20_115252_add_contract_fields_to_contracts_table', 43),
(122, '2025_07_20_075140_add_arrival_date_to_contract_infos_table', 44),
(123, '2025_07_19_133310_add_deleted_at_to_orders_table', 45),
(124, '2025_07_19_133800_add_timer_columns_to_orders_table', 46),
(125, '2025_07_19_134315_update_existing_orders_status_changed_at', 47),
(126, '2025_07_22_180850_add_marketer_id_to_contracts_table', 48),
(127, '2025_07_26_002321_create_maid_unavailable_records_table', 49),
(128, '2025_07_27_145256_add_indexes_to_improve_contract_filters', 50),
(129, '2025_07_27_180258_add_warranty_status_to_contracts_table', 51),
(130, '2025_07_27_180326_update_completed_contracts_status', 52),
(131, '2025_07_27_181131_remove_warranty_contracts_sidebar', 53),
(132, '2025_07_28_104306_create_sponsorship_transfers_table', 54),
(133, '2025_07_28_152208_update_sponsorship_transfers_status_enum', 54),
(134, '2025_07_29_162540_create_deportation_procedures_table', 54),
(135, '2025_07_30_145617_add_required_maid_nationality_to_contracts_table', 55),
(136, '2025_07_29_181903_update_deportation_procedures_boolean_defaults', 56),
(137, '2025_01_02_000000_create_rental_contract_terms_table', 57),
(138, '2025_07_30_153014_create_rental_contracts_table', 58),
(139, '2025_07_30_153108_add_rental_fields_to_users_table', 59),
(140, '2025_08_03_115151_add_missing_fields_to_rental_contract_infos_table', 60),
(141, '2025_01_02_optimize_orders_table_indexes', 61),
(142, '2025_07_29_175000_add_trip_id_to_deportation_procedures_table', 61),
(143, '2025_07_30_153055_create_rental_contract_infos_table', 62),
(144, '2025_07_29_180833_add_procedures_confirmed_to_deportation_procedures_table', 63),
(145, '2025_08_10_000000_add_date_fields_to_deportation_procedures', 64),
(146, '2025_08_10_000001_add_trip_scheduled_to_deportation_procedures', 65),
(147, '2025_08_10_000002_ensure_all_deportation_procedures_fields', 65),
(148, '2025_08_10_000003_enhance_complaints_table', 66),
(149, '2025_01_15_create_complaint_feedbacks_table', 67),
(150, '2025_08_14_152505_create_sms_templates_table', 67),
(151, '2025_08_16_114012_create_contract_payments_table', 67),
(152, '2025_08_16_114419_add_previous_contract_id_to_contracts_table', 67),
(153, '2025_08_16_162040_add_closure_type_to_rental_contracts_table', 67),
(154, '2025_08_16_171005_add_confirmation_fields_to_travel_history_table', 67),
(155, '2025_08_16_195750_create_istiqdam_contracts_table', 67),
(156, '2025_08_18_225211_create_contract_return_requests_table', 68),
(157, '2025_08_19_183740_add_is_read_to_complaints_table', 68),
(158, '2025_08_23_123559_add_signature_to_contracts_table', 69),
(159, '2025_08_23_125439_add_signature_to_rental_contracts_table', 70),
(160, '2025_08_23_192643_add_required_maid_fields_to_contracts_table', 71),
(161, '2025_08_30_092237_add_experience_status_to_maids_table', 72),
(163, '2025_08_30_103126_update_existing_maids_experience_status', 73),
(164, '2025_08_30_225939_create_deportation_procedure_logs_table', 74),
(165, '2025_08_30_231109_add_attachments_to_deportation_procedures_table', 74),
(166, '2025_09_02_030450_add_is_available_for_instant_to_contract_infos_table', 75),
(167, '2025_09_02_211332_add_job_types_to_existing_maids', 76);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_permissions`
--

INSERT INTO `model_has_permissions` (`permission_id`, `model_type`, `model_id`) VALUES
(56, 'App\\Models\\auth\\Admin', 1),
(57, 'App\\Models\\auth\\Admin', 1),
(403, 'App\\Models\\auth\\Admin', 1),
(404, 'App\\Models\\auth\\Admin', 1),
(405, 'App\\Models\\auth\\Admin', 1),
(406, 'App\\Models\\auth\\Admin', 1),
(407, 'App\\Models\\auth\\Admin', 1),
(413, 'App\\Models\\auth\\Admin', 1),
(414, 'App\\Models\\auth\\Admin', 1),
(415, 'App\\Models\\auth\\Admin', 1),
(56, 'App\\Models\\auth\\Admin', 36),
(57, 'App\\Models\\auth\\Admin', 36),
(403, 'App\\Models\\auth\\Admin', 36),
(404, 'App\\Models\\auth\\Admin', 36),
(405, 'App\\Models\\auth\\Admin', 36),
(406, 'App\\Models\\auth\\Admin', 36),
(407, 'App\\Models\\auth\\Admin', 36),
(413, 'App\\Models\\auth\\Admin', 36),
(414, 'App\\Models\\auth\\Admin', 36),
(415, 'App\\Models\\auth\\Admin', 36),
(56, 'App\\Models\\auth\\Admin', 42),
(57, 'App\\Models\\auth\\Admin', 42),
(403, 'App\\Models\\auth\\Admin', 42),
(404, 'App\\Models\\auth\\Admin', 42),
(405, 'App\\Models\\auth\\Admin', 42),
(406, 'App\\Models\\auth\\Admin', 42),
(407, 'App\\Models\\auth\\Admin', 42),
(413, 'App\\Models\\auth\\Admin', 42),
(414, 'App\\Models\\auth\\Admin', 42),
(415, 'App\\Models\\auth\\Admin', 42),
(56, 'App\\Models\\auth\\Admin', 46),
(57, 'App\\Models\\auth\\Admin', 46),
(403, 'App\\Models\\auth\\Admin', 46),
(404, 'App\\Models\\auth\\Admin', 46),
(405, 'App\\Models\\auth\\Admin', 46),
(406, 'App\\Models\\auth\\Admin', 46),
(407, 'App\\Models\\auth\\Admin', 46),
(413, 'App\\Models\\auth\\Admin', 46),
(414, 'App\\Models\\auth\\Admin', 46),
(415, 'App\\Models\\auth\\Admin', 46),
(56, 'App\\Models\\auth\\Admin', 48),
(57, 'App\\Models\\auth\\Admin', 48),
(65, 'App\\Models\\auth\\Admin', 48),
(66, 'App\\Models\\auth\\Admin', 48),
(89, 'App\\Models\\auth\\Admin', 48),
(90, 'App\\Models\\auth\\Admin', 48),
(91, 'App\\Models\\auth\\Admin', 48),
(92, 'App\\Models\\auth\\Admin', 48),
(403, 'App\\Models\\auth\\Admin', 48),
(404, 'App\\Models\\auth\\Admin', 48),
(405, 'App\\Models\\auth\\Admin', 48),
(406, 'App\\Models\\auth\\Admin', 48),
(407, 'App\\Models\\auth\\Admin', 48),
(413, 'App\\Models\\auth\\Admin', 48),
(414, 'App\\Models\\auth\\Admin', 48),
(415, 'App\\Models\\auth\\Admin', 48),
(56, 'App\\Models\\auth\\Admin', 49),
(57, 'App\\Models\\auth\\Admin', 49),
(65, 'App\\Models\\auth\\Admin', 49),
(66, 'App\\Models\\auth\\Admin', 49),
(89, 'App\\Models\\auth\\Admin', 49),
(90, 'App\\Models\\auth\\Admin', 49),
(91, 'App\\Models\\auth\\Admin', 49),
(92, 'App\\Models\\auth\\Admin', 49),
(403, 'App\\Models\\auth\\Admin', 49),
(404, 'App\\Models\\auth\\Admin', 49),
(405, 'App\\Models\\auth\\Admin', 49),
(406, 'App\\Models\\auth\\Admin', 49),
(407, 'App\\Models\\auth\\Admin', 49),
(413, 'App\\Models\\auth\\Admin', 49),
(414, 'App\\Models\\auth\\Admin', 49),
(415, 'App\\Models\\auth\\Admin', 49),
(56, 'App\\Models\\auth\\Admin', 50),
(57, 'App\\Models\\auth\\Admin', 50),
(65, 'App\\Models\\auth\\Admin', 50),
(66, 'App\\Models\\auth\\Admin', 50),
(89, 'App\\Models\\auth\\Admin', 50),
(90, 'App\\Models\\auth\\Admin', 50),
(91, 'App\\Models\\auth\\Admin', 50),
(92, 'App\\Models\\auth\\Admin', 50),
(403, 'App\\Models\\auth\\Admin', 50),
(404, 'App\\Models\\auth\\Admin', 50),
(405, 'App\\Models\\auth\\Admin', 50),
(406, 'App\\Models\\auth\\Admin', 50),
(407, 'App\\Models\\auth\\Admin', 50),
(413, 'App\\Models\\auth\\Admin', 50),
(414, 'App\\Models\\auth\\Admin', 50),
(415, 'App\\Models\\auth\\Admin', 50),
(39, 'App\\Models\\auth\\Admin', 52),
(40, 'App\\Models\\auth\\Admin', 52),
(41, 'App\\Models\\auth\\Admin', 52),
(56, 'App\\Models\\auth\\Admin', 52),
(57, 'App\\Models\\auth\\Admin', 52),
(65, 'App\\Models\\auth\\Admin', 52),
(66, 'App\\Models\\auth\\Admin', 52),
(89, 'App\\Models\\auth\\Admin', 52),
(90, 'App\\Models\\auth\\Admin', 52),
(91, 'App\\Models\\auth\\Admin', 52),
(92, 'App\\Models\\auth\\Admin', 52),
(403, 'App\\Models\\auth\\Admin', 52),
(404, 'App\\Models\\auth\\Admin', 52),
(405, 'App\\Models\\auth\\Admin', 52),
(406, 'App\\Models\\auth\\Admin', 52),
(407, 'App\\Models\\auth\\Admin', 52),
(413, 'App\\Models\\auth\\Admin', 52),
(414, 'App\\Models\\auth\\Admin', 52),
(415, 'App\\Models\\auth\\Admin', 52),
(56, 'App\\Models\\auth\\Admin', 53),
(57, 'App\\Models\\auth\\Admin', 53),
(65, 'App\\Models\\auth\\Admin', 53),
(66, 'App\\Models\\auth\\Admin', 53),
(89, 'App\\Models\\auth\\Admin', 53),
(90, 'App\\Models\\auth\\Admin', 53),
(91, 'App\\Models\\auth\\Admin', 53),
(92, 'App\\Models\\auth\\Admin', 53),
(403, 'App\\Models\\auth\\Admin', 53),
(404, 'App\\Models\\auth\\Admin', 53),
(405, 'App\\Models\\auth\\Admin', 53),
(406, 'App\\Models\\auth\\Admin', 53),
(407, 'App\\Models\\auth\\Admin', 53),
(413, 'App\\Models\\auth\\Admin', 53),
(414, 'App\\Models\\auth\\Admin', 53),
(415, 'App\\Models\\auth\\Admin', 53),
(39, 'App\\Models\\auth\\Admin', 54),
(40, 'App\\Models\\auth\\Admin', 54),
(41, 'App\\Models\\auth\\Admin', 54),
(56, 'App\\Models\\auth\\Admin', 54),
(57, 'App\\Models\\auth\\Admin', 54),
(65, 'App\\Models\\auth\\Admin', 54),
(66, 'App\\Models\\auth\\Admin', 54),
(89, 'App\\Models\\auth\\Admin', 54),
(90, 'App\\Models\\auth\\Admin', 54),
(91, 'App\\Models\\auth\\Admin', 54),
(92, 'App\\Models\\auth\\Admin', 54),
(403, 'App\\Models\\auth\\Admin', 54),
(404, 'App\\Models\\auth\\Admin', 54),
(405, 'App\\Models\\auth\\Admin', 54),
(406, 'App\\Models\\auth\\Admin', 54),
(407, 'App\\Models\\auth\\Admin', 54),
(413, 'App\\Models\\auth\\Admin', 54),
(414, 'App\\Models\\auth\\Admin', 54),
(415, 'App\\Models\\auth\\Admin', 54),
(56, 'App\\Models\\auth\\Admin', 55),
(57, 'App\\Models\\auth\\Admin', 55),
(65, 'App\\Models\\auth\\Admin', 55),
(66, 'App\\Models\\auth\\Admin', 55),
(89, 'App\\Models\\auth\\Admin', 55),
(90, 'App\\Models\\auth\\Admin', 55),
(91, 'App\\Models\\auth\\Admin', 55),
(92, 'App\\Models\\auth\\Admin', 55),
(403, 'App\\Models\\auth\\Admin', 55),
(404, 'App\\Models\\auth\\Admin', 55),
(405, 'App\\Models\\auth\\Admin', 55),
(406, 'App\\Models\\auth\\Admin', 55),
(407, 'App\\Models\\auth\\Admin', 55),
(413, 'App\\Models\\auth\\Admin', 55),
(414, 'App\\Models\\auth\\Admin', 55),
(415, 'App\\Models\\auth\\Admin', 55),
(56, 'App\\Models\\auth\\Admin', 56),
(57, 'App\\Models\\auth\\Admin', 56),
(403, 'App\\Models\\auth\\Admin', 56),
(404, 'App\\Models\\auth\\Admin', 56),
(405, 'App\\Models\\auth\\Admin', 56),
(406, 'App\\Models\\auth\\Admin', 56),
(407, 'App\\Models\\auth\\Admin', 56),
(413, 'App\\Models\\auth\\Admin', 56),
(414, 'App\\Models\\auth\\Admin', 56),
(415, 'App\\Models\\auth\\Admin', 56),
(56, 'App\\Models\\auth\\Admin', 57),
(57, 'App\\Models\\auth\\Admin', 57),
(403, 'App\\Models\\auth\\Admin', 57),
(404, 'App\\Models\\auth\\Admin', 57),
(405, 'App\\Models\\auth\\Admin', 57),
(406, 'App\\Models\\auth\\Admin', 57),
(407, 'App\\Models\\auth\\Admin', 57),
(413, 'App\\Models\\auth\\Admin', 57),
(414, 'App\\Models\\auth\\Admin', 57),
(415, 'App\\Models\\auth\\Admin', 57),
(39, 'App\\Models\\auth\\Admin', 58),
(40, 'App\\Models\\auth\\Admin', 58),
(41, 'App\\Models\\auth\\Admin', 58),
(56, 'App\\Models\\auth\\Admin', 58),
(57, 'App\\Models\\auth\\Admin', 58),
(65, 'App\\Models\\auth\\Admin', 58),
(66, 'App\\Models\\auth\\Admin', 58),
(89, 'App\\Models\\auth\\Admin', 58),
(90, 'App\\Models\\auth\\Admin', 58),
(91, 'App\\Models\\auth\\Admin', 58),
(92, 'App\\Models\\auth\\Admin', 58),
(403, 'App\\Models\\auth\\Admin', 58),
(404, 'App\\Models\\auth\\Admin', 58),
(405, 'App\\Models\\auth\\Admin', 58),
(406, 'App\\Models\\auth\\Admin', 58),
(407, 'App\\Models\\auth\\Admin', 58),
(413, 'App\\Models\\auth\\Admin', 58),
(414, 'App\\Models\\auth\\Admin', 58),
(415, 'App\\Models\\auth\\Admin', 58),
(39, 'App\\Models\\auth\\Admin', 59),
(40, 'App\\Models\\auth\\Admin', 59),
(41, 'App\\Models\\auth\\Admin', 59),
(56, 'App\\Models\\auth\\Admin', 59),
(57, 'App\\Models\\auth\\Admin', 59),
(403, 'App\\Models\\auth\\Admin', 59),
(404, 'App\\Models\\auth\\Admin', 59),
(405, 'App\\Models\\auth\\Admin', 59),
(406, 'App\\Models\\auth\\Admin', 59),
(407, 'App\\Models\\auth\\Admin', 59),
(413, 'App\\Models\\auth\\Admin', 59),
(414, 'App\\Models\\auth\\Admin', 59),
(415, 'App\\Models\\auth\\Admin', 59),
(39, 'App\\Models\\auth\\Admin', 60),
(40, 'App\\Models\\auth\\Admin', 60),
(41, 'App\\Models\\auth\\Admin', 60),
(56, 'App\\Models\\auth\\Admin', 60),
(57, 'App\\Models\\auth\\Admin', 60),
(65, 'App\\Models\\auth\\Admin', 60),
(66, 'App\\Models\\auth\\Admin', 60),
(89, 'App\\Models\\auth\\Admin', 60),
(90, 'App\\Models\\auth\\Admin', 60),
(91, 'App\\Models\\auth\\Admin', 60),
(92, 'App\\Models\\auth\\Admin', 60),
(403, 'App\\Models\\auth\\Admin', 60),
(404, 'App\\Models\\auth\\Admin', 60),
(405, 'App\\Models\\auth\\Admin', 60),
(406, 'App\\Models\\auth\\Admin', 60),
(407, 'App\\Models\\auth\\Admin', 60),
(413, 'App\\Models\\auth\\Admin', 60),
(414, 'App\\Models\\auth\\Admin', 60),
(415, 'App\\Models\\auth\\Admin', 60);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\auth\\Admin', 1),
(1, 'App\\Models\\auth\\Admin', 36),
(1, 'App\\Models\\auth\\Admin', 42),
(1, 'App\\Models\\auth\\Admin', 46),
(3, 'App\\Models\\auth\\Admin', 48),
(3, 'App\\Models\\auth\\Admin', 49),
(3, 'App\\Models\\auth\\Admin', 50),
(7, 'App\\Models\\auth\\Admin', 52),
(3, 'App\\Models\\auth\\Admin', 53),
(5, 'App\\Models\\auth\\Admin', 54),
(3, 'App\\Models\\auth\\Admin', 55),
(1, 'App\\Models\\auth\\Admin', 56),
(1, 'App\\Models\\auth\\Admin', 57),
(5, 'App\\Models\\auth\\Admin', 58),
(6, 'App\\Models\\auth\\Admin', 59),
(5, 'App\\Models\\auth\\Admin', 60);

-- --------------------------------------------------------

--
-- Table structure for table `nationalities`
--

CREATE TABLE `nationalities` (
  `id` bigint UNSIGNED NOT NULL,
  `logo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price` double DEFAULT NULL,
  `service_transportation_cost` decimal(10,2) DEFAULT NULL COMMENT 'تكلفة نقل الخدمات',
  `rental_cost` decimal(10,2) DEFAULT NULL COMMENT 'تكلفة التأجير',
  `monthly_salary` decimal(10,2) DEFAULT NULL COMMENT 'الراتب الشهري'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nationalities`
--

INSERT INTO `nationalities` (`id`, `logo`, `phone_code`, `created_at`, `updated_at`, `price`, `service_transportation_cost`, `rental_cost`, `monthly_salary`) VALUES
(2, 'images/Nationality/ArqpVBLcWB1704115577.png', 'Ph', '2024-01-01 16:26:17', '2025-04-09 16:41:08', 14153, NULL, NULL, NULL),
(3, 'images/Nationality/Z9osZOxOnJ1704816026.webp', 'KN', '2024-01-09 07:15:40', '2025-07-02 16:23:26', 5698, NULL, NULL, NULL),
(4, 'images/Nationality/rVJkrMEjST1704816102.png', 'Ug', '2024-01-09 19:01:42', '2025-07-02 16:22:49', 4599, NULL, NULL, NULL),
(5, 'images/Nationality/NgV2FHinsT1704816271.png', 'Bn', '2024-01-09 19:04:31', '2025-07-02 16:21:03', 7965, NULL, NULL, 900.11),
(6, 'images/Nationality/GVYnU3aURD1704816323.png', 'Et', '2024-01-09 19:05:23', '2025-01-05 18:27:06', 4285, NULL, NULL, NULL),
(7, 'images/Nationality/jJyxUelzSg1704816385.png', 'Sr', '2024-01-09 19:06:25', '2024-05-06 11:54:01', 14724, NULL, NULL, NULL),
(8, 'images/Nationality/nibu5tVmYB1704816577.png', 'Bu', '2024-01-09 19:09:37', '2025-07-02 16:23:12', 5898, NULL, NULL, NULL),
(9, 'images/Nationality/FZcmcyWNOg1704816731.png', 'Th', '2024-01-09 19:12:11', '2024-05-06 11:52:27', 9785, NULL, NULL, NULL),
(10, 'images/Nationality/of70DIGwa71704816835.png', 'Pa', '2024-01-09 19:13:55', '2024-05-06 11:54:27', 9240, NULL, NULL, NULL),
(11, 'images/Nationality/jpMqktfPbN1704816871.png', 'In', '2024-01-09 19:14:31', '2025-04-09 16:42:30', 2300, NULL, NULL, NULL),
(12, 'images/Nationality/nOQE8BD0eI1751548219.webp', 'tnz', '2025-07-03 16:09:49', '2025-07-03 16:10:19', 5495, NULL, NULL, 900.00),
(13, 'images/Nationality/QYB0y7hWHY1752935356.jpg', 'EGY', '2025-07-19 17:29:16', '2025-07-19 17:29:16', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nationalities_translations`
--

CREATE TABLE `nationalities_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `nationality_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nationalities_translations`
--

INSERT INTO `nationalities_translations` (`id`, `nationality_id`, `title`, `sub_title`, `locale`, `created_at`, `updated_at`) VALUES
(3, 2, 'الفلبين', 'الفلبين', 'ar', '2024-01-01 16:26:17', '2024-02-05 20:28:33'),
(4, 2, 'Philippines', 'Philippines', 'en', '2024-01-01 16:26:17', '2024-01-01 16:26:17'),
(5, 3, 'كينيا', 'كينيا', 'ar', '2024-01-09 07:15:40', '2024-02-05 20:28:27'),
(6, 3, 'Kenya', 'Kenyan', 'en', '2024-01-09 07:15:40', '2024-01-09 19:02:00'),
(7, 4, 'اوغندة', 'اوغندة', 'ar', '2024-01-09 19:01:42', '2024-02-05 20:28:21'),
(8, 4, 'Uganda', 'Ugandan', 'en', '2024-01-09 19:01:42', '2024-01-09 19:01:42'),
(9, 5, 'بنجلاديش', 'بنجلاديش', 'ar', '2024-01-09 19:04:31', '2024-02-05 20:28:14'),
(10, 5, 'Bangladesh', 'Bangali', 'en', '2024-01-09 19:04:31', '2024-01-09 19:04:31'),
(11, 6, 'اثيوبيا', 'اثيوبيا', 'ar', '2024-01-09 19:05:23', '2024-02-05 20:28:07'),
(12, 6, 'Ethiopia', 'Ethiopian', 'en', '2024-01-09 19:05:23', '2024-01-09 19:05:23'),
(13, 7, 'سري لنكا', 'سري لنكا', 'ar', '2024-01-09 19:06:25', '2024-02-05 20:27:59'),
(14, 7, 'Sri Lanka', 'Sri Lankan', 'en', '2024-01-09 19:06:25', '2024-01-09 19:06:25'),
(15, 8, 'بورندي', 'بورندي', 'ar', '2024-01-09 19:09:37', '2024-02-05 20:22:48'),
(16, 8, 'Burundi', 'Burundian', 'en', '2024-01-09 19:09:37', '2024-01-09 19:09:37'),
(17, 9, 'تايلند', 'تايلند', 'ar', '2024-01-09 19:12:11', '2024-02-05 20:22:40'),
(18, 9, 'Thailand', 'Thai', 'en', '2024-01-09 19:12:11', '2024-01-09 19:12:11'),
(19, 10, 'باكستان', 'باكستان', 'ar', '2024-01-09 19:13:55', '2024-02-05 20:22:33'),
(20, 10, 'Pakistan', 'Pakistani', 'en', '2024-01-09 19:13:55', '2024-01-09 19:13:55'),
(21, 11, 'الهند', 'الهند', 'ar', '2024-01-09 19:14:31', '2024-02-05 20:22:26'),
(22, 11, 'India', 'Indian', 'en', '2024-01-09 19:14:31', '2024-01-09 19:14:31'),
(23, 12, '', 'تنزانيا', 'ar', '2025-07-03 16:09:49', '2025-07-03 16:09:49'),
(24, 12, '', 'Tanzania', 'en', '2025-07-03 16:09:49', '2025-07-03 16:09:49'),
(25, 13, '', 'مصر', 'ar', '2025-07-19 17:29:16', '2025-07-19 17:29:16'),
(26, 13, '', 'Egypt', 'en', '2025-07-19 17:29:16', '2025-07-19 17:29:16');

-- --------------------------------------------------------

--
-- Table structure for table `new_settings`
--

CREATE TABLE `new_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `new_settings`
--

INSERT INTO `new_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'contract_template', '<h3>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; عقد</h3><p>&nbsp;</p><p>الحمد لله وحده وبعد ففي يوم الموافق @todays_date تم الاتفاق بمدينة الرياض بين كل من:</p><p>&nbsp;</p><p>&nbsp; &nbsp; 1- @app_name</p><p>&nbsp; &nbsp; لاستقدام العمالة المنزلية ترخيص وزارة العمل رقم / 4001772 وتاريخ : 2022/10/13 م سجل تجاري رقم / 1010601958 صادر من الرياض</p><p>&nbsp; &nbsp; وعنوانه الرياض صندوق بريد: الرمز البريدي: هاتف: جوال: البريد الالكتروني</p><p>&nbsp; &nbsp; ويمثله في التوقيع عن هذا العقد المدير بصفته المالك ويشار اليه فيما بعد بـ (الطرف الاول).</p><p>&nbsp;</p><p>&nbsp; &nbsp; 2- @client_name</p><p>&nbsp; &nbsp; الجنسية @client_nationality - بموجب هوية وطنية رقم @client_national_number وعنوانه @client_address الرمز البريدي هاتف جوال @client_phone_code @client_phone_number &nbsp;فاكس -------------- ويشار اليه فيما بعد بـ (الطرف الثاني).</p><p>&nbsp;</p><p>تمهيد:</p><p>حيث أن الطرف الأول مرخص ويسمح له بتقديم خدمات العمالة المنزلية ,ولديه الخبرات والامكانيات اللازمة لتوفير خدمات العمالة المنزلية، وذلك وفق الأنظمة واللوائح والتعليمات المعمول بها في المملكة العربية السعودية وبما لا يتعارض مع أحكام الشريعة الإسلامية، ولحاجه الطرف الثاني لخدمات بعض العمالة المنزلية، فقد اتفق الطرفان وهما بكامل أهليتهما المعتبرة شرعا ونظاما على ما يلي:</p><p>&nbsp;</p><p>أولا: موضوع العقد</p><p>اتفق الطرفان على أن يقوم الطرف الأول بتوفير عامله منزليه وتقديم خدماته للطرف الثاني: وفقا لما يكلفه به في حدود مجال عمله بمهنة عاملة تأجير, وبيانات العامل هي :</p><p>&nbsp;</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; الجنسية | @maid_nationality</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; رقم جواز السفر | @maid_passport_num</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; المؤهلات والخبرات | @maid_experience_years</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ساعات العمل الفعلية اليومية | @contract_working_hours_num</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; مدة الخدمة المطلوبة | @contract_duration</p><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; التكلفة الشهرية | @contract_monthly_cost</p><p>&nbsp;</p><p>ثانيا: مدة العقد</p><p>مدة العقد @contract_duration يوما علي ان تبدأ من @contract_date, تنتهي @contract_expire_date قابلة للتجديد لمدة او مدد مماثلة بموجب أشعار مكتوب من الطرف الثاني للطرف الأول قبل نهاية</p><p>&nbsp;</p><p>ثالثا: قيمة العقد</p><p>1- يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العاملة المنزلية: وفقا للتكلفة الشهرية (@contract_monthly_cost) المعلنة – في تاريخ التعاقد: وفقا لما يلي :</p><p>3- في حال تجديد هذا العقد، وموافقة العامل على الاستمرار في العمل لدى الطرف الثاني ,فيدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العامل المنزلي للمدة الجديدة تعادل التكلفة السابقة.</p><p>&nbsp;</p><p>رابعا: التزامات الطرف الأول</p><p>1- تقديم خدمات العامل المذكور أعلاه للطرف الثاني.</p><p>4- الحصول على موافقة خطية من العامل للعمل لدى الطرف الثاني ,ونقل خدماته ألية ,وذلك قبل نقل خدماته للطرف الثاني.</p><p>5- أذا كان العامل بمهنة (سائق) فيجب أن يحمل رخصة قيادة سعودية سارية المفعول.<br>&nbsp;</p><p>خامسا: التزامات الطرف الثاني</p><p>1- سداد التكلفة المتفق عليها بالفقرة (1) من البند (ثالثا) من هذا العقد</p><p>2- أن يعامل العامل بالاحترام اللائق ,وأن يمتنع عن كل قول ,أو فعل يمس كرامته ,ودينه</p><p>3- تمكين العامل من الوصول ألي أقرب فرع بنك أو جهاز صراف ألي لاستلام مرتبه الشهري ومساعدته لتحويل اية مبالغ لذوية.</p><p>4- أن يكون مسؤولا عن أي تجاوزات تلحق أضرار بالعامل في مواجهة الطرف الأول والعامل.</p><p>5- أشعار الطرف الأول بأية مخالفات, أو أخطاء يرتكبها العامل لاتخاذ ما يلزم من إجراءات بحقة.</p><p>6- عدم تشغيل العامل في اعمال تختلف عن المهنة المرخص له بها سواء بطريقه مباشره. او غير مباشرة.</p><p>7- يشترط أذا كانت المهنة العامل (سائق) أن تكون السيارة المسلمة له مؤمن عليها تأمين ضد الغير على الأقل.</p><p>8- توفير السكن المناسب للعامل.</p><p>9- ان يؤمن الطرف الثاني للعامل ثلاث وجبات غذاء يوميا.</p><p>10- أبلاغ الطرف الأول كتابيا في حال تغيب العامل عن العمل أو رفضه العمل خلال (24) ساعة من وقت التغيب أو الرفض العمالة مع تسليم جميع متعلقات العامل للطرف الأول، وكذلك في حال عودة العامل يشعر الطرف الثاني الطرف الأول كتابيا بتاريخ العودة محددا ساعة وتاريخ عودة العامل.</p><p>11- أعادة العامل عند انتهاء مده الاختبار او التقديم خدماته للطرف الأول, وفي حال عدم التزام الطرف الثاني بذلك ,فأنه يعوض الطرف الأول بما يعادل (150) مائه وخمسون ریال سعودي عن كل يوم تأخير مع قيمه التكلفة اليومية للعامل المقدمة خدماته.</p><p>12- في حالة إعادة العاملة خلال مدة العقد يتم احتساب معادلة الخصم (150) مائه وخمسون ریال سعودي عن كل يوم عمل به لدى الطرف الثاني ويتم إعادة المبلغ المتبقي.</p><p>&nbsp;</p><p>سادسا: أحكام عامه</p><p>1- لايجوز للطرف الثاني القيام بإعادة تقديم خدمات العامل لأي طرف أخر سواء بطريقة مباشره أوغير مباشرة</p><p>2- يرتبط العامل مع الطرف الاول بعقد عمل منتهي بنقل الكفالة للطرف الأول بعد انتهاء مدة الاختبار المتفق عليها في العقد. ويعمل تحت أشرافه وأدارته وفقا لنظام العمل.</p><p>3- يخضع العامل لتعليمات الطرف الثاني، وعليه الحفاظ على أسرار أعماله وأسرار أسرته ,ويكون للطرف الثاني حق توجيهه حسب مقتضيات العمل لدية ,ووفقا للأنظمة السارية.</p><p>4- أي شروط أضافيه تتعارض مع نظام العمل أو لائحته أو القرارات الصادرة تنفيذا له تعتبر باطله</p><p>&nbsp;</p><p>سابعا: الخلافات والمنازعات</p><p>في حاله حدوث خلاف بين الطرفين يتم اللجوء للمكتب المتعاقد فيه المختص لمحاوله التوصل لحل ودي بين الطرفين قبل النظر فيه من المحكمة المختصة.</p><p>&nbsp;</p><p>ثامنا: الأخطارات والإشعارات</p><p>تتم الاخطارات والإشعارات بين الطرفين كتابه عن طريق البريد المسجل أو البريد الممتاز أو البريد الالكتروني او عن طريق الرسائل النصية عن طريق الجوال او الواتساب. وتوجه الأخطارات على معلومات التواصل الموضح لكل من الطرفين في صدر هذا العقد ويلتزم كل طرف بأشعار الطرف الأخر خطيا في حال تغييره لمعلومات التواصل الخاص به والا اعتبرت معلومات التواصل المدون أعلاه هو المعمول به نظاما دون غيره.</p><p>&nbsp;</p><p>&nbsp; &nbsp; أسم الطرف الأول: شركة أسس الإنجاز للاستقدام</p><p>&nbsp; &nbsp; أسم الطرف الثاني: @client_name</p><p>&nbsp; &nbsp; توقيع الطرف الأول ..............................................................…</p><p>&nbsp; &nbsp; توقيع الطرف الثاني ...........................................................….</p><p>&nbsp; &nbsp; الختم ..............................................................…………………….<br>&nbsp;</p>', '2024-08-04 21:04:27', '2024-08-04 22:11:32'),
(2, 'bank_transfer_data', '<p>يجب التحويل على حساب: TEST451254125125120<br>ملاحظات اخرى هنا</p>', '2024-09-04 22:36:12', '2024-09-04 22:36:12'),
(3, 'sadad_transfer_data', '<p>يجب التحويل على حساب: TEST451234343125120<br>ملاحظات اخرى هنا</p>', '2024-09-04 22:36:12', '2024-09-04 22:36:12'),
(4, 'show_popup', '0', '2025-04-28 20:11:42', '2025-04-28 20:43:47'),
(5, 'popup_image', 'images/settings/7CzQKE88elaTqzDH2LTOiPTVDeZZV2oXfNVo78KN.png', '2025-04-28 20:11:42', '2025-04-28 20:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `description`, `order_id`, `user_id`, `type`, `created_at`, `updated_at`) VALUES
(202, '{\"ar\":\"\\u0639\\u0631\\u0636\",\"en\":null}', '{\"ar\":\"\\u0646\\u062d\\u0646 \\u0646\\u0642\\u062f\\u0645 \\u0623\\u0641\\u0636\\u0644 \\u062e\\u062f\\u0645\\u0627\\u062a \\u0627\\u0644\\u0627\\u0633\\u062a\\u0642\\u062f\\u0627\\u0645\\u060c \\u0646\\u062c\\u0645\\u0639 \\u0628\\u064a\\u0646 \\u0627\\u0644\\u0627\\u062d\\u062a\\u0631\\u0627\\u0641\\u064a\\u0629 \\u0648\\u0627\\u0644\\u0625\\u0628\\u062f\\u0627\\u0639. \\u0646\\u062d\\u0646 \\u0647\\u0646\\u0627 \\u0644\\u062a\\u0644\\u0628\\u064a\\u0629 \\u0627\\u062d\\u062a\\u064a\\u0627\\u062c\\u0627\\u062a \\u0645\\u0646\\u0627\\u0632\\u0644\\u0643\\u0645 \\u0628\\u06', NULL, NULL, 'general', '2024-02-07 19:45:57', '2024-02-07 19:45:57'),
(213, '{\"ar\":\"\\u0639\\u0631\\u0636\",\"en\":\"\\u00d7Offer\"}', '{\"ar\":\"\\u0627\\u0633\\u062a\\u0642\\u062f\\u0645 \\u0627\\u0644\\u0627\\u0646 \\u0648\\u0644\\u062d\\u0642 \\u0639\\u0631\\u0648\\u0636 \\u064a\\u0648\\u0645 \\u0627\\u0644\\u062a\\u0623\\u0633\\u064a\\u0633\",\"en\":null}', NULL, NULL, 'general', '2024-02-15 19:05:05', '2024-02-15 19:05:05'),
(444, '{\"en\":null}', '{\"en\":null}', NULL, NULL, 'general', '2024-06-23 16:35:00', '2024-06-23 16:35:00'),
(445, '{\"en\":null}', '{\"en\":null}', NULL, NULL, 'general', '2024-06-23 16:35:08', '2024-06-23 16:35:08'),
(446, '{\"ar\":\"\\u0646\\u062a\\u0645\\u0646\\u064a \\u0644\\u0643\\u0645 \\u064a\\u0648\\u0645\\u0627 \\u0633\\u0639\\u064a\\u062f\\u0627 \\u2764\",\"en\":\"\\u0646\\u062a\\u0645\\u0646\\u064a \\u0644\\u0643\\u0645 \\u064a\\u0648\\u0645\\u0627 \\u0633\\u0639\\u064a\\u062f\\u0627 \\u2764\"}', '{\"ar\":\"\\u0646\\u062a\\u0645\\u0646\\u064a \\u0644\\u0643\\u0645 \\u064a\\u0648\\u0645\\u0627 \\u0633\\u0639\\u064a\\u062f\\u0627 \\u2764\",\"en\":\"\\u0646\\u062a\\u0645\\u0646\\u064a \\u0644\\u0643\\u0645 \\u064a\\u0648\\u0645\\u0627 \\u0633\\u0639\\u064a\\u062f\\u0627 \\u2764\"}', NULL, NULL, 'general', '2024-06-23 16:35:54', '2024-06-23 16:35:54'),
(447, '{\"en\":null}', '{\"en\":null}', NULL, NULL, 'general', '2024-06-23 16:40:48', '2024-06-23 16:40:48'),
(448, '{\"ar\":\"1\",\"en\":\"2\"}', '{\"ar\":\"3\",\"en\":\"4\"}', NULL, NULL, 'general', '2024-06-23 16:41:09', '2024-06-23 16:41:09');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `payment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type_id` bigint UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `deadline_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expiration_time` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `admin_id` bigint UNSIGNED DEFAULT NULL,
  `package_id` bigint UNSIGNED DEFAULT NULL,
  `address_id` bigint UNSIGNED DEFAULT NULL,
  `deliver_maid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_transfers`
--

CREATE TABLE `order_transfers` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `file` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `transfer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint UNSIGNED NOT NULL,
  `original_price` double NOT NULL,
  `price_after_discount` double NOT NULL,
  `price_with_tax` double NOT NULL,
  `days_count` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nationality_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `original_price`, `price_after_discount`, `price_with_tax`, `days_count`, `created_at`, `updated_at`, `nationality_id`) VALUES
(1, 3400, 2800, 2800, 30, '2023-12-25 09:01:55', '2024-02-17 16:17:49', 3),
(2, 6800, 5500, 5500, 60, '2023-12-25 09:02:35', '2024-02-17 16:18:26', 3),
(3, 1400, 1320, 1466, 90, '2023-12-25 09:03:08', '2023-12-25 09:03:08', NULL),
(5, 2900, 2500, 2750, 160, '2023-12-25 09:04:30', '2025-09-03 08:59:42', 11),
(6, 2400, 1900, 1900, 30, '2024-05-23 20:08:46', '2024-05-23 20:08:46', 4),
(7, 2000, 1500, 1800, 30, '2024-07-18 11:44:40', '2024-07-18 11:44:40', 6);

-- --------------------------------------------------------

--
-- Table structure for table `packages_translations`
--

CREATE TABLE `packages_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `package_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages_translations`
--

INSERT INTO `packages_translations` (`id`, `package_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'باقة 30 يوم', 'ar', '2023-12-25 09:01:55', '2023-12-25 09:01:55'),
(2, 1, '30 Days Package', 'en', '2023-12-25 09:01:55', '2023-12-25 09:01:55'),
(3, 2, 'باقة 60 يوم', 'ar', '2023-12-25 09:02:35', '2023-12-25 09:02:35'),
(4, 2, '60 Days Packge', 'en', '2023-12-25 09:02:35', '2023-12-25 09:02:35'),
(5, 3, 'باقة 90 يوم', 'ar', '2023-12-25 09:03:08', '2023-12-25 09:03:08'),
(6, 3, '90 Days Package', 'en', '2023-12-25 09:03:08', '2023-12-25 09:03:08'),
(9, 5, 'باقة سوبر بريمير', 'ar', '2023-12-25 09:04:30', '2023-12-25 09:04:30'),
(10, 5, 'Super premium package', 'en', '2023-12-25 09:04:30', '2023-12-25 09:04:30'),
(11, 6, 'بريميوم', 'ar', '2024-05-23 20:08:46', '2024-05-23 20:08:46'),
(12, 6, 'Premium', 'en', '2024-05-23 20:08:46', '2024-05-23 20:08:46'),
(13, 7, 'باقة اثيوبيا', 'ar', '2024-07-18 11:44:40', '2024-07-18 11:44:40'),
(14, 7, 'باقة اثيوبيا en', 'en', '2024-07-18 11:44:40', '2024-07-18 11:44:40');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `group_name`, `section_name`, `created_at`, `updated_at`, `group`, `section`, `display_name`, `description`) VALUES
(1, 'dashboard_view', 'admin', 'dashboard', 'dashboard', NULL, '2025-06-30 11:25:30', NULL, NULL, 'عرض لوحة التحكم', 'إمكانية عرض لوحة التحكم الرئيسية'),
(3, 'dashboard_view_statistics', 'admin', 'dashboard', 'dashboard', NULL, '2025-06-30 11:25:30', NULL, NULL, 'عرض إحصائيات لوحة التحكم', 'إمكانية عرض إحصائيات لوحة التحكم'),
(4, 'dashboard_clients_count', 'admin', 'dashboard', 'dashboard', NULL, '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد العملاء', 'إمكانية عرض عدد العملاء في لوحة التحكم'),
(5, 'settings_view', 'admin', 'settings', 'settings', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'settings_control', 'admin', 'settings', 'settings', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'roles_view', 'admin', 'permissions', 'permissions', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'roles_control', 'admin', 'permissions', 'permissions', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'set_perms', 'admin', 'permissions', 'permissions', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'developers_perms', 'admin', 'permissions', 'permissions', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'full_contract', 'admin', 'contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'job_titles_view', 'admin', 'job_titles', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'job_titles_control', 'admin', 'job_titles', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'job_titles_delete', 'admin', 'job_titles', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'packages_view', 'admin', 'packages', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'packages_control', 'admin', 'packages', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'packages_delete', 'admin', 'packages', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'nationalities_view', 'admin', 'nationalities', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'nationalities_control', 'admin', 'nationalities', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'nationalities_delete', 'admin', 'nationalities', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'relegions_view', 'admin', 'religions', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'relegions_control', 'admin', 'religions', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'relegions_delete', 'admin', 'religions', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'orders_view', 'admin', 'orders', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'عرض الطلبات', NULL),
(30, 'orders_control', 'admin', 'orders', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'التحكم في الطلبات', NULL),
(31, 'orders_delete', 'admin', 'orders', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'حذف الطلبات', NULL),
(32, 'return_request_view', 'admin', 'return_requests', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'عرض طلبات الاسترجاع', NULL),
(33, 'return_request_control', 'admin', 'return_requests', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'التحكم في طلبات الاسترجاع', NULL),
(34, 'return_request_delete', 'admin', 'return_requests', 'order_management', NULL, '2025-06-30 14:26:10', NULL, NULL, 'حذف طلبات الاسترجاع', NULL),
(38, 'cancel_contract_view', 'admin', 'cancel_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'rent_contract_view', 'admin', 'rent_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'rent_contract_control', 'admin', 'rent_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'rent_contract_delete', 'admin', 'rent_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'contract_settings', 'admin', 'contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(43, 'shelter_contract_view', 'admin', 'shelter_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'shelter_contract_control', 'admin', 'shelter_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'shelter_contract_delete', 'admin', 'shelter_contracts', 'contract_management', NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'shelter_workers_view', 'admin', 'shelter_workers', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(47, 'shelter_workers_control', 'admin', 'shelter_workers', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'travel_view', 'admin', 'travel', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'travel_control', 'admin', 'travel', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(50, 'rec_offices_view', 'admin', 'rec_offices', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(51, 'rec_offices_control', 'admin', 'rec_offices', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(52, 'rec_offices_delete', 'admin', 'rec_offices', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(53, 'shelter_places_view', 'admin', 'shelter_places', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(54, 'shelter_places_control', 'admin', 'shelter_places', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(55, 'shelter_places_delete', 'admin', 'shelter_places', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(56, 'sponsor_transfer_view', 'admin', 'sponsor_transfer', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(57, 'sponsor_transfer_control', 'admin', 'sponsor_transfer', 'organization_management', NULL, NULL, NULL, NULL, NULL, NULL),
(58, 'notifications_control', 'admin', 'notifications', 'communications', NULL, NULL, NULL, NULL, NULL, NULL),
(59, 'sliders_view', 'admin', 'sliders', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(60, 'sliders_control', 'admin', 'sliders', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(61, 'sliders_delete', 'admin', 'sliders', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(62, 'faq_view', 'admin', 'faq', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(63, 'faq_control', 'admin', 'faq', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(64, 'faq_delete', 'admin', 'faq', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(65, 'complaint_view', 'admin', 'complaints', 'customer_service', NULL, NULL, NULL, NULL, NULL, NULL),
(66, 'complaint_delete', 'admin', 'complaints', 'customer_service', NULL, NULL, NULL, NULL, NULL, NULL),
(67, 'about_us_view', 'admin', 'about_us', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(68, 'about_us_control', 'admin', 'about_us', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(69, 'about_us_delete', 'admin', 'about_us', 'content_management', NULL, NULL, NULL, NULL, NULL, NULL),
(70, 'contact_view', 'admin', 'contacts', 'customer_service', NULL, NULL, NULL, NULL, NULL, NULL),
(71, 'contact_delete', 'admin', 'contacts', 'customer_service', NULL, NULL, NULL, NULL, NULL, NULL),
(72, 'stat_view', 'admin', 'statistics', 'reporting', NULL, NULL, NULL, NULL, NULL, NULL),
(73, 'stat_control', 'admin', 'statistics', 'reporting', NULL, NULL, NULL, NULL, NULL, NULL),
(74, 'stat_delete', 'admin', 'statistics', 'reporting', NULL, NULL, NULL, NULL, NULL, NULL),
(75, 'steps_view', 'admin', 'steps', 'process_management', NULL, NULL, NULL, NULL, NULL, NULL),
(76, 'steps_control', 'admin', 'steps', 'process_management', NULL, NULL, NULL, NULL, NULL, NULL),
(77, 'steps_delete', 'admin', 'steps', 'process_management', NULL, NULL, NULL, NULL, NULL, NULL),
(78, 'services_view', 'admin', 'services', 'service_management', NULL, NULL, NULL, NULL, NULL, NULL),
(79, 'services_control', 'admin', 'services', 'service_management', NULL, NULL, NULL, NULL, NULL, NULL),
(80, 'services_delete', 'admin', 'services', 'service_management', NULL, NULL, NULL, NULL, NULL, NULL),
(81, 'skills_view', 'admin', 'skills', 'organization_management', '2025-05-13 20:30:18', '2025-05-13 20:30:18', NULL, NULL, NULL, NULL),
(82, 'skills_control', 'admin', 'skills', 'organization_management', '2025-05-13 20:30:18', '2025-05-13 20:30:18', NULL, NULL, NULL, NULL),
(83, 'skills_delete', 'admin', 'skills', 'organization_management', '2025-05-13 20:30:18', '2025-05-13 20:30:18', NULL, NULL, NULL, NULL),
(84, 'complaints-read', 'admin', '', '', '2025-05-13 20:31:06', '2025-05-13 20:31:06', NULL, NULL, NULL, NULL),
(85, 'complaint_view', 'web', '', '', '2025-05-13 23:32:27', '2025-05-13 23:32:27', NULL, NULL, NULL, NULL),
(86, 'complaint_create', 'web', '', '', '2025-05-13 23:32:27', '2025-05-13 23:32:27', NULL, NULL, NULL, NULL),
(87, 'complaint_edit', 'web', '', '', '2025-05-13 23:32:27', '2025-05-13 23:32:27', NULL, NULL, NULL, NULL),
(88, 'complaint_delete', 'web', '', '', '2025-05-13 23:32:27', '2025-05-13 23:32:27', NULL, NULL, NULL, NULL),
(89, 'complaint_type_view', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(90, 'complaint_type_create', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(91, 'complaint_type_edit', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(92, 'complaint_type_delete', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(93, 'recruitment_offices_view', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(94, 'recruitment_offices_create', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(95, 'recruitment_offices_edit', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(96, 'recruitment_offices_delete', 'admin', '', '', '2025-06-30 10:44:27', '2025-06-30 10:44:27', NULL, NULL, NULL, NULL),
(110, 'cv_view', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(111, 'cv_create', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(112, 'cv_edit', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(113, 'cv_delete', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(114, 'cv_pin', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(115, 'cv_unpin', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(116, 'cv_approve', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(117, 'cv_reject', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(118, 'cv_export', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(119, 'cv_import', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(120, 'cv_manage', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(121, 'cv_download', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(122, 'cv_upload', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(123, 'cv_convert', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(124, 'cv_analytics', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(125, 'cv_reports', 'admin', 'cv', 'cv_management', '2025-06-30 10:44:42', '2025-06-30 10:44:42', NULL, NULL, NULL, NULL),
(126, 'dashboard_contacts_count', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد رسائل التواصل', 'إمكانية عرض عدد رسائل التواصل في لوحة التحكم'),
(127, 'dashboard_current_orders_count', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد الطلبات الحالية', 'إمكانية عرض عدد الطلبات الحالية في لوحة التحكم'),
(128, 'dashboard_cv_count', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد السير الذاتية', 'إمكانية عرض عدد السير الذاتية في لوحة التحكم'),
(129, 'dashboard_employees_count', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد الموظفين', 'إمكانية عرض عدد الموظفين في لوحة التحكم'),
(130, 'dashboard_completed_cv_count', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض عدد السير الذاتية المكتملة', 'إمكانية عرض عدد السير الذاتية المكتملة في لوحة التحكم'),
(131, 'dashboard_orders_statistics', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض إحصائيات الطلبات', 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم'),
(132, 'dashboard_top_customer_services', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض أفضل خدمة عملاء', 'إمكانية عرض أفضل خدمة عملاء في لوحة التحكم'),
(133, 'dashboard_orders_types', 'admin', 'dashboard', 'dashboard', '2025-06-30 11:13:53', '2025-06-30 11:25:30', NULL, NULL, 'عرض أنواع الطلبات', 'إمكانية عرض أنواع الطلبات في لوحة التحكم'),
(154, 'clients_export_pdf', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'تصدير العملاء بصيغة PDF', 'إمكانية تصدير بيانات العملاء بصيغة PDF'),
(155, 'clients_export_excel', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'تصدير العملاء بصيغة Excel', 'إمكانية تصدير بيانات العملاء بصيغة Excel'),
(156, 'clients_toggle_status', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'تغيير حالة العميل', 'إمكانية تفعيل أو إلغاء تفعيل العميل'),
(157, 'clients_show', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'عرض تفاصيل العميل', 'إمكانية عرض تفاصيل العميل الكاملة'),
(158, 'clients_manage', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'إدارة العملاء', 'إمكانية إدارة جميع عمليات العملاء'),
(159, 'clients_import', 'admin', '', '', '2025-06-30 11:52:31', '2025-06-30 11:52:31', NULL, NULL, 'استيراد بيانات العملاء', 'إمكانية استيراد بيانات العملاء من ملفات خارجية'),
(175, 'clients_view', 'admin', '', '', '2025-06-30 12:07:02', '2025-06-30 12:30:09', NULL, NULL, 'عرض العملاء', NULL),
(176, 'clients_create', 'admin', '', '', '2025-06-30 12:07:02', '2025-06-30 12:30:09', NULL, NULL, 'إضافة عميل', NULL),
(177, 'clients_edit', 'admin', '', '', '2025-06-30 12:07:02', '2025-06-30 12:30:09', NULL, NULL, 'تعديل العملاء', NULL),
(178, 'clients_delete', 'admin', '', '', '2025-06-30 12:07:02', '2025-06-30 12:30:09', NULL, NULL, 'حذف العملاء', NULL),
(179, 'clients_export', 'admin', '', '', '2025-06-30 12:07:02', '2025-06-30 12:30:09', NULL, NULL, 'تصدير بيانات العملاء', NULL),
(180, 'clients_create_recruitment', 'admin', '', '', '2025-06-30 13:49:00', '2025-06-30 13:49:00', NULL, NULL, 'إنشاء طلب استقدام', 'إمكانية إنشاء طلب استقدام للعميل'),
(181, 'maids_export_pdf', 'admin', '', '', '2025-06-30 13:57:19', '2025-06-30 13:57:19', NULL, NULL, 'تصدير السير الذاتية بصيغة PDF', 'إمكانية تصدير بيانات السير الذاتية بصيغة PDF'),
(182, 'maids_export_excel', 'admin', '', '', '2025-06-30 13:57:19', '2025-06-30 13:57:19', NULL, NULL, 'تصدير السير الذاتية بصيغة Excel', 'إمكانية تصدير بيانات السير الذاتية بصيغة Excel'),
(183, 'maids_show', 'admin', '', '', '2025-06-30 13:57:19', '2025-06-30 13:57:19', NULL, NULL, 'عرض تفاصيل السيرة الذاتية', 'إمكانية عرض تفاصيل السيرة الذاتية الكاملة'),
(184, 'maids_toggle_status', 'admin', '', '', '2025-06-30 13:57:19', '2025-06-30 13:57:19', NULL, NULL, 'تغيير حالة الظهور', 'إمكانية إظهار أو إخفاء السيرة الذاتية'),
(185, 'maids_toggle_pin', 'admin', '', '', '2025-06-30 13:57:19', '2025-06-30 13:57:19', NULL, NULL, 'تثبيت/إلغاء تثبيت', 'إمكانية تثبيت أو إلغاء تثبيت السيرة الذاتية'),
(186, 'maids_view', 'admin', '', '', '2025-06-30 13:59:33', '2025-06-30 13:59:33', NULL, NULL, 'عرض السير الذاتية', 'إمكانية عرض قائمة السير الذاتية'),
(187, 'maids_create', 'admin', '', '', '2025-06-30 13:59:33', '2025-06-30 13:59:33', NULL, NULL, 'إضافة سيرة ذاتية', 'إمكانية إضافة سيرة ذاتية جديدة'),
(188, 'maids_edit', 'admin', '', '', '2025-06-30 13:59:33', '2025-06-30 13:59:33', NULL, NULL, 'تعديل السير الذاتية', 'إمكانية تعديل بيانات السير الذاتية'),
(189, 'maids_delete', 'admin', '', '', '2025-06-30 13:59:33', '2025-06-30 13:59:33', NULL, NULL, 'حذف السير الذاتية', 'إمكانية حذف السير الذاتية'),
(190, 'maids_export', 'admin', '', '', '2025-06-30 13:59:33', '2025-06-30 13:59:33', NULL, NULL, 'تصدير السير الذاتية', 'إمكانية تصدير بيانات السير الذاتية'),
(191, 'orders_create', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'إنشاء طلب', 'إمكانية إنشاء طلب جديد'),
(192, 'orders_edit', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'تعديل الطلبات', 'إمكانية تعديل بيانات الطلبات'),
(193, 'orders_export', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'تصدير الطلبات', 'إمكانية تصدير بيانات الطلبات'),
(198, 'return_request_create', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'إنشاء طلب استرجاع', 'إمكانية إنشاء طلب استرجاع جديد'),
(199, 'return_request_edit', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'تعديل طلبات الاسترجاع', 'إمكانية تعديل بيانات طلبات الاسترجاع'),
(200, 'return_request_approve', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'الموافقة على طلبات الاسترجاع', 'إمكانية الموافقة على طلبات الاسترجاع'),
(201, 'return_request_reject', 'admin', '', '', '2025-06-30 14:25:13', '2025-06-30 14:25:13', NULL, NULL, 'رفض طلبات الاسترجاع', 'إمكانية رفض طلبات الاسترجاع'),
(207, 'orders_change_status', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تغيير حالة الطلب', 'إمكانية تغيير حالة الطلب (جديد، تحت الإجراء، مكتمل، ملغي)'),
(208, 'orders_mark_in_progress', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تحديد الطلب تحت الإجراء', 'إمكانية تغيير حالة الطلب إلى تحت الإجراء'),
(209, 'orders_mark_cancelled', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'إلغاء الطلب', 'إمكانية إلغاء الطلب'),
(210, 'orders_mark_completed', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'إكمال الطلب', 'إمكانية تحديد الطلب كمكتمل'),
(211, 'orders_add_contract', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'إضافة عقد للطلب', 'إمكانية إضافة عقد للطلب'),
(212, 'orders_view_contract', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'عرض عقد الطلب', 'إمكانية عرض عقد الطلب'),
(213, 'orders_reports', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تقارير الطلبات', 'إمكانية عرض تقارير الطلبات'),
(214, 'orders_analytics', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تحليلات الطلبات', 'إمكانية عرض تحليلات الطلبات'),
(215, 'return_request_export', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تصدير طلبات الاسترجاع', 'إمكانية تصدير بيانات طلبات الاسترجاع'),
(216, 'return_request_change_status', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تغيير حالة طلب الاسترجاع', 'إمكانية تغيير حالة طلب الاسترجاع'),
(217, 'return_request_reports', 'admin', '', '', '2025-06-30 14:36:40', '2025-06-30 14:36:40', NULL, NULL, 'تقارير طلبات الاسترجاع', 'إمكانية عرض تقارير طلبات الاسترجاع'),
(343, 'rec_contracts_view', 'admin', '', '', '2025-07-01 08:41:42', '2025-07-01 08:41:42', NULL, NULL, 'عرض عقود الاستقدام', 'إمكانية عرض قائمة عقود الاستقدام'),
(344, 'rec_contracts_create', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إنشاء عقد استقدام جديد', 'إمكانية إنشاء عقد استقدام جديد'),
(345, 'rec_contracts_edit', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تعديل عقد الاستقدام', 'إمكانية تعديل بيانات عقد الاستقدام'),
(346, 'rec_contracts_delete', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'حذف عقد الاستقدام', 'إمكانية حذف عقد الاستقدام'),
(347, 'rec_contracts_export', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تصدير عقود الاستقدام', 'إمكانية تصدير بيانات عقود الاستقدام'),
(348, 'rec_contracts_print', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'طباعة عقد الاستقدام', 'إمكانية طباعة عقد الاستقدام'),
(349, 'rec_contracts_change_to_msaned', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تغيير إلى عقود مساند', 'تغيير حالة العقد من بانتظار الاعتماد إلى عقود مساند'),
(350, 'rec_contracts_change_to_tafwed', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تغيير إلى التفويض', 'تغيير حالة العقد من عقود مساند إلى التفويض'),
(351, 'rec_contracts_change_to_taffez', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تغيير إلى التفييز', 'تغيير حالة العقد من التفويض إلى التفييز'),
(352, 'rec_contracts_change_to_tazker', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تغيير إلى التذكرة', 'تغيير حالة العقد من التفييز إلى التذكرة'),
(353, 'rec_contracts_add_travel', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إضافة رحلة للعقد', 'إمكانية إضافة رحلة جديدة للعقد'),
(354, 'rec_contracts_edit_travel', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تعديل رحلة العقد', 'إمكانية تعديل بيانات رحلة العقد'),
(355, 'rec_contracts_delete_travel', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'حذف رحلة العقد', 'إمكانية حذف رحلة من العقد'),
(356, 'rec_contracts_complete_travel', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تحديد اكتمال الرحلة', 'إمكانية تحديد اكتمال الرحلة'),
(357, 'rec_contracts_view_travel', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'عرض تفاصيل الرحلة', 'إمكانية عرض تفاصيل رحلة العقد'),
(358, 'rec_contracts_warranty_view', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'عرض العقود تحت الضمان', 'إمكانية عرض العقود التي تحت الضمان'),
(359, 'rec_contracts_warranty_manage', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إدارة فترة الضمان', 'إمكانية إدارة فترة الضمان للعقود'),
(360, 'rec_contracts_close_after_warranty', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إغلاق العقد بعد انتهاء الضمان', 'إمكانية إغلاق العقد بعد انتهاء فترة الضمان'),
(361, 'rec_contracts_cancel_view', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'عرض طلبات إلغاء العقود', 'إمكانية عرض طلبات إلغاء العقود'),
(362, 'rec_contracts_cancel_create', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إنشاء طلب إلغاء عقد', 'إمكانية إنشاء طلب إلغاء عقد جديد'),
(363, 'rec_contracts_cancel_edit', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تعديل طلب إلغاء العقد', 'إمكانية تعديل طلب إلغاء العقد'),
(364, 'rec_contracts_cancel_delete', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'حذف طلب إلغاء العقد', 'إمكانية حذف طلب إلغاء العقد'),
(365, 'rec_contracts_cancel_approve', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'الموافقة على إلغاء العقد', 'إمكانية الموافقة على طلب إلغاء العقد'),
(366, 'rec_contracts_cancel_reject', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'رفض إلغاء العقد', 'إمكانية رفض طلب إلغاء العقد'),
(367, 'rec_contracts_return', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إرجاع عقد الاستقدام', 'إمكانية إرجاع عقد الاستقدام'),
(368, 'rec_contracts_return_view', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'عرض العقود المرتجعة', 'إمكانية عرض العقود المرتجعة'),
(369, 'rec_contracts_return_manage', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إدارة المرتجعات', 'إمكانية إدارة عمليات الإرجاع'),
(370, 'rec_contracts_manage', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إدارة عقود الاستقدام', 'إمكانية إدارة جميع جوانب عقود الاستقدام'),
(371, 'rec_contracts_reports', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'تقارير عقود الاستقدام', 'إمكانية عرض وتوليد تقارير عقود الاستقدام'),
(372, 'rec_contracts_analytics', 'admin', '', '', '2025-07-01 08:41:43', '2025-07-01 08:41:43', NULL, NULL, 'إحصائيات عقود الاستقدام', 'إمكانية عرض إحصائيات وتحليلات عقود الاستقدام'),
(373, 'shelter_contract_create', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(374, 'shelter_contract_edit', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(375, 'shelter_workers_create', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(376, 'shelter_workers_edit', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(377, 'shelter_workers_delete', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(378, 'shelter_places_create', 'admin', '', '', '2025-07-01 09:10:43', '2025-07-01 09:10:43', NULL, NULL, NULL, NULL),
(379, 'shelter_places_edit', 'admin', '', '', '2025-07-01 09:10:44', '2025-07-01 09:10:44', NULL, NULL, NULL, NULL),
(380, 'travel_create', 'admin', '', '', '2025-07-01 09:11:23', '2025-07-01 09:11:23', NULL, NULL, NULL, NULL),
(381, 'travel_edit', 'admin', '', '', '2025-07-01 09:11:23', '2025-07-01 09:11:23', NULL, NULL, NULL, NULL),
(382, 'travel_delete', 'admin', '', '', '2025-07-01 09:11:23', '2025-07-01 09:11:23', NULL, NULL, NULL, NULL),
(383, 'religions_view', 'admin', '', '', '2025-07-01 17:13:12', '2025-07-01 17:13:12', NULL, NULL, NULL, NULL),
(384, 'users_view', 'admin', '', '', '2025-07-01 17:13:25', '2025-07-01 17:13:25', NULL, NULL, NULL, NULL),
(385, 'statistics_view', 'admin', '', '', '2025-07-01 17:13:36', '2025-07-01 17:13:36', NULL, NULL, NULL, NULL),
(386, 'permissions_view', 'admin', '', '', '2025-07-01 17:48:16', '2025-07-01 17:48:16', NULL, NULL, 'عرض الصلاحيات', NULL),
(387, 'permissions_assign', 'admin', '', '', '2025-07-01 17:48:16', '2025-07-01 17:48:16', NULL, NULL, 'تعيين الصلاحيات', NULL),
(388, 'permissions_create', 'admin', '', '', '2025-07-01 17:48:16', '2025-07-01 17:48:16', NULL, NULL, 'إنشاء صلاحيات', NULL),
(389, 'permissions_delete', 'admin', '', '', '2025-07-01 17:48:16', '2025-07-01 17:48:16', NULL, NULL, 'حذف صلاحيات', NULL),
(391, 'users_create', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'إضافة مستخدم', NULL),
(392, 'users_edit', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'تعديل المستخدمين', NULL),
(393, 'users_delete', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'حذف المستخدمين', NULL),
(394, 'users_control', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'التحكم في المستخدمين', NULL),
(395, 'users_export', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'تصدير المستخدمين', NULL),
(396, 'users_import', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'استيراد المستخدمين', NULL),
(397, 'users_manage', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'إدارة المستخدمين', NULL),
(398, 'users_activate', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'تفعيل المستخدمين', NULL),
(399, 'users_deactivate', 'admin', '', '', '2025-07-01 17:54:55', '2025-07-01 17:54:55', NULL, NULL, 'إلغاء تفعيل المستخدمين', NULL),
(400, 'rec_contracts_update', 'admin', '', '', '2025-07-26 09:04:57', '2025-07-26 09:04:57', NULL, NULL, NULL, NULL),
(401, 'orders_change_maid', 'admin', '', '', '2025-07-30 16:15:43', '2025-07-30 16:15:43', NULL, NULL, 'تغيير السيرة الذاتية للطلب', 'إمكانية تغيير السيرة الذاتية المرتبطة بالطلب'),
(402, 'orders_change_responsible', 'admin', '', '', '2025-07-30 16:15:43', '2025-07-30 16:15:43', NULL, NULL, 'تغيير مسئول الطلب', 'إمكانية تغيير الموظف المسؤول عن الطلب'),
(403, 'sponsor_transfer_create', 'admin', '', '', '2025-07-30 16:15:59', '2025-07-30 16:15:59', NULL, NULL, NULL, NULL),
(404, 'sponsor_transfer_edit', 'admin', '', '', '2025-07-30 16:15:59', '2025-07-30 16:15:59', NULL, NULL, NULL, NULL),
(405, 'sponsor_transfer_delete', 'admin', '', '', '2025-07-30 16:15:59', '2025-07-30 16:15:59', NULL, NULL, NULL, NULL),
(406, 'rent_contract_create', 'admin', '', '', '2025-07-31 13:42:37', NULL, NULL, NULL, 'اضافة عقد تاجير', NULL),
(407, 'rent_contract_edit', 'admin', '', '', '2025-07-31 13:42:44', NULL, NULL, NULL, 'تعديل عقد تاجير', NULL),
(408, 'rent_contract_view', 'web', '', '', '2025-08-03 15:42:11', '2025-08-03 15:42:11', NULL, NULL, NULL, NULL),
(409, 'rent_contract_create', 'web', '', '', '2025-08-03 15:42:11', '2025-08-03 15:42:11', NULL, NULL, NULL, NULL),
(410, 'rent_contract_edit', 'web', '', '', '2025-08-03 15:42:11', '2025-08-03 15:42:11', NULL, NULL, NULL, NULL),
(411, 'rent_contract_delete', 'web', '', '', '2025-08-03 15:42:11', '2025-08-03 15:42:11', NULL, NULL, NULL, NULL),
(412, 'rent_contract_control', 'web', '', '', '2025-08-03 15:42:11', '2025-08-03 15:42:11', NULL, NULL, NULL, NULL),
(413, 'complaint_create', 'admin', 'complaints', 'customer_service', '2025-08-11 12:55:30', '2025-08-11 12:55:30', NULL, NULL, 'إضافة شكوى', NULL),
(414, 'complaint_edit', 'admin', 'complaints', 'customer_service', '2025-08-11 12:55:30', '2025-08-11 12:55:30', NULL, NULL, 'تعديل الشكاوى', NULL),
(415, 'complaint_response', 'admin', 'complaints', 'customer_service', '2025-08-11 12:55:30', '2025-08-11 12:55:30', NULL, NULL, 'الرد على الشكاوى', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phone_verifications`
--

CREATE TABLE `phone_verifications` (
  `id` bigint UNSIGNED NOT NULL,
  `phone_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_details_translations`
--

CREATE TABLE `print_details_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `print_details_id` bigint UNSIGNED NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting`
--

CREATE TABLE `print_setting` (
  `id` bigint UNSIGNED NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default.png',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting_details`
--

CREATE TABLE `print_setting_details` (
  `id` bigint UNSIGNED NOT NULL,
  `print_setting_id` bigint UNSIGNED NOT NULL,
  `type` enum('first','second') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting_translations`
--

CREATE TABLE `print_setting_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `print_setting_id` bigint UNSIGNED NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` bigint UNSIGNED NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `is_show`, `created_at`, `updated_at`) VALUES
(1, 1, '2023-12-21 13:56:46', '2023-12-21 14:01:22'),
(3, 1, '2025-04-13 18:16:31', '2025-04-13 18:16:31'),
(4, 1, '2025-04-14 19:45:15', '2025-04-14 19:45:15');

-- --------------------------------------------------------

--
-- Table structure for table `questions_translations`
--

CREATE TABLE `questions_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `question_id` bigint UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions_translations`
--

INSERT INTO `questions_translations` (`id`, `question_id`, `title`, `description`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'ما هو دور \"شركة اسس الانجاز للاستقدام\" بالتحديد؟', '\"شركة اسس الانجاز للاستقدام\" هي شركة رائدة في مجال توفير خدمات استقدام العمالة الوافدة، تهدف إلى تلبية احتياجات الشركات والأفراد بتوفير الكفاءات البشرية المناسبة والمؤهلة.', 'ar', '2023-12-21 13:56:46', '2023-12-21 13:56:46'),
(2, 1, '? What is the role of \"Al-Injaz Establishment for Recruitment Company\" specifically', 'Al-Injaz Establishment for Recruitment Company\" is a leading company in the field of providing services for the recruitment of foreign labor. It aims to meet the needs of both companies and individuals by supplying suitable and qualified human resources', 'en', '2023-12-21 13:56:46', '2023-12-21 13:56:46'),
(5, 3, 'شروط استخراج تاشيرة سائق خاص', 'شروط استخراج تاشيرة سائق خاص تعد مهنة السائق الخاص إحدى أهم المهن في المملكة العربية السعودية، والتي تشهد إقبال كبير لما لها من تأثير فعال على الحياة اليومية للأفراد والشركات، وعليه، فقد عملت وزارة الموارد البشرية والتنمية الاجتماعية في المملكة بالتسهيل على المواطنين، وذلك من خلال استخراج تأشيرة سائق خاص بشكل قانوني إلكترونياً.   تعرف معنا في هذا المقال على شروط استخراج تاشيرة سائق خاص وأهم الإجراءات اللازمة، وكيفية الاستخراج.   أهم شروط استخراج تاشيرة السائق الخاص   تساهم عملية استخراج تأشيرة سائق خاص في ضمان الحصول على سائق موثوق، وهي تعد خطوة أساسية في تيسير التنقل اليومي، وإليك أهم شروط استخراج تاشيرة سائق خاص:   •	لا بد من خضوع السائق إلى الفحص الطبي للتأكد من سلامته من جميع الأمراض، والتي قد تؤثر على أدائه. •	يشترط خلو سجل السائق من الشبهات الجنائية. •	يفضل أن يمتلك خبرة سابقة في القيادة. •	يجب أن يحصل على رخصة صادرة من جهة رسمية معتمدة. •	لا يقل عمر السائق عن 25 عام. •	تشترط حصول السائق الخاص في الظام الجديد على رخصة قيادة سارية المفعول من موطنه الأصلي. •	وأيضا، لا بد من حصوله ع', 'ar', '2025-04-13 18:16:31', '2025-04-13 18:16:31'),
(6, 3, 'شروط استخراج تاشيرة سائق خاص', 'شروط استخراج تاشيرة سائق خاص تعد مهنة السائق الخاص إحدى أهم المهن في المملكة العربية السعودية، والتي تشهد إقبال كبير لما لها من تأثير فعال على الحياة اليومية للأفراد والشركات، وعليه، فقد عملت وزارة الموارد البشرية والتنمية الاجتماعية في المملكة بالتسهيل على المواطنين، وذلك من خلال استخراج تأشيرة سائق خاص بشكل قانوني إلكترونياً.   تعرف معنا في هذا المقال على شروط استخراج تاشيرة سائق خاص وأهم الإجراءات اللازمة، وكيفية الاستخراج.   أهم شروط استخراج تاشيرة السائق الخاص   تساهم عملية استخراج تأشيرة سائق خاص في ضمان الحصول على سائق موثوق، وهي تعد خطوة أساسية في تيسير التنقل اليومي، وإليك أهم شروط استخراج تاشيرة سائق خاص:   •	لا بد من خضوع السائق إلى الفحص الطبي للتأكد من سلامته من جميع الأمراض، والتي قد تؤثر على أدائه. •	يشترط خلو سجل السائق من الشبهات الجنائية. •	يفضل أن يمتلك خبرة سابقة في القيادة. •	يجب أن يحصل على رخصة صادرة من جهة رسمية معتمدة. •	لا يقل عمر السائق عن 25 عام. •	تشترط حصول السائق الخاص في الظام الجديد على رخصة قيادة سارية المفعول من موطنه الأصلي. •	وأيضا، لا بد من حصوله ع', 'en', '2025-04-13 18:16:31', '2025-04-13 18:16:31'),
(7, 4, 'كم رسوم تفويض تأشيرة ؟', 'الاليه الجديده للتفاويض الالكترونيه تتطلب تصديق مكاتب الاستقدام المحليه علي التاشيرات الفرديه كالعماله المنزليه والسائق الخاص , لذا نقدم لك في شركة أسس الإنجاز أرخص الأسعار - ارخص تفويض الكتروني سائق خاص , تواصل مباشرة مع مسئول التفاويض بشركة أسس الإنجاز للاستقدام , بالإتصال على 0533346330', 'ar', '2025-04-14 19:45:15', '2025-04-14 19:45:15'),
(8, 4, 'كم رسوم تفويض تأشيرة ؟', 'الاليه الجديده للتفاويض الالكترونيه تتطلب تصديق مكاتب الاستقدام المحليه علي التاشيرات الفرديه كالعماله المنزليه والسائق الخاص , لذا نقدم لك في شركة أسس الإنجاز أرخص الأسعار - ارخص تفويض الكتروني سائق خاص , تواصل مباشرة مع مسئول التفاويض بشركة أسس الإنجاز للاستقدام , بالإتصال على 0533346330', 'en', '2025-04-14 19:45:15', '2025-04-14 19:45:15');

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_offices`
--

CREATE TABLE `recruitment_offices` (
  `id` bigint UNSIGNED NOT NULL,
  `nationality_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recruitment_offices`
--

INSERT INTO `recruitment_offices` (`id`, `nationality_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2024-02-07 16:19:53', '2024-02-07 16:19:53'),
(2, 2, '2024-02-07 16:20:04', '2024-02-07 16:20:04'),
(3, 2, '2024-02-07 16:20:21', '2024-02-07 16:20:21'),
(4, 5, '2024-02-07 16:20:58', '2024-02-07 16:20:58'),
(5, 6, '2024-02-07 16:21:35', '2024-02-07 16:21:35'),
(6, 6, '2024-02-07 16:22:00', '2024-02-07 16:22:00'),
(7, 3, '2024-02-07 16:22:25', '2024-02-07 16:22:25'),
(8, 3, '2024-02-07 16:24:44', '2024-02-07 16:24:44'),
(9, 3, '2024-02-07 16:26:01', '2024-02-07 16:26:01'),
(10, 3, '2024-02-07 16:26:12', '2024-02-07 16:26:12'),
(11, 8, '2024-02-07 16:26:32', '2024-02-07 16:26:32'),
(12, 4, '2024-02-07 16:26:57', '2024-02-07 16:26:57'),
(13, 4, '2024-02-07 16:27:09', '2024-02-07 16:27:09'),
(14, 4, '2024-02-07 16:27:21', '2024-02-07 16:27:21'),
(15, 11, '2024-02-07 16:27:40', '2024-02-07 16:27:40'),
(16, 11, '2024-02-07 16:27:51', '2024-02-07 16:27:51'),
(17, 11, '2024-02-07 16:28:00', '2024-02-07 16:28:00'),
(18, 5, '2024-02-07 16:28:09', '2024-03-28 22:20:35'),
(19, 11, '2024-02-07 16:28:20', '2024-02-07 16:28:20'),
(20, 7, '2024-02-07 16:30:46', '2024-02-07 16:30:46'),
(21, 7, '2024-02-07 16:30:56', '2024-02-07 16:30:56'),
(22, 7, '2024-02-07 16:31:06', '2024-02-07 16:31:06'),
(23, 9, '2024-02-07 16:31:22', '2024-02-07 16:31:22'),
(24, 10, '2024-02-07 16:32:18', '2024-02-07 16:32:18'),
(25, 10, '2024-02-07 16:32:28', '2024-02-07 16:32:28'),
(26, 2, '2024-06-06 10:58:04', '2024-06-06 10:58:04'),
(28, 12, '2025-07-08 17:06:00', '2025-07-08 17:06:00'),
(29, 13, '2025-07-19 18:44:11', '2025-07-19 18:44:11'),
(30, 6, '2025-07-28 16:52:47', '2025-07-28 16:52:47');

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_office_translations`
--

CREATE TABLE `recruitment_office_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `rec_office_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recruitment_office_translations`
--

INSERT INTO `recruitment_office_translations` (`id`, `rec_office_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'Best Migrant Workers International Manpower S', 'ar', '2024-02-07 16:19:53', '2024-02-07 16:19:53'),
(2, 1, 'Best Migrant Workers International Manpower S', 'en', '2024-02-07 16:19:53', '2024-02-07 16:19:53'),
(3, 2, 'G & M Phils INC', 'ar', '2024-02-07 16:20:04', '2024-02-07 16:20:04'),
(4, 2, 'G & M Phils INC', 'en', '2024-02-07 16:20:04', '2024-02-07 16:20:04'),
(5, 3, 'Sama International Recruitment Agency Co', 'ar', '2024-02-07 16:20:21', '2024-02-07 16:20:21'),
(6, 3, 'Sama International Recruitment Agency Co', 'en', '2024-02-07 16:20:21', '2024-02-07 16:20:21'),
(7, 4, 'M/S. Salman Overseas', 'ar', '2024-02-07 16:20:58', '2024-02-07 16:20:58'),
(8, 4, 'M/S. Salman Overseas', 'en', '2024-02-07 16:20:58', '2024-02-07 16:20:58'),
(9, 5, 'TOFAMENFAUHA FOREIGN EMPLOYMENT AGENT PLC', 'ar', '2024-02-07 16:21:35', '2024-02-07 16:21:35'),
(10, 5, 'TOFAMENFAUHA FOREIGN EMPLOYMENT AGENT PLC', 'en', '2024-02-07 16:21:35', '2024-02-07 16:21:35'),
(11, 6, 'KAMZ FOREIGN EMPLOYMENT AGENT P.L.C', 'ar', '2024-02-07 16:22:00', '2025-07-28 16:49:46'),
(12, 6, 'KAMZ FOREIGN EMPLOYMENT AGENT P.L.C', 'en', '2024-02-07 16:22:00', '2025-07-28 16:49:46'),
(13, 7, 'MULINYA MANPOWER AGENCY LIMITED', 'ar', '2024-02-07 16:22:25', '2024-02-07 16:22:25'),
(14, 7, 'MULINYA MANPOWER AGENCY LIMITED', 'en', '2024-02-07 16:22:25', '2024-02-07 16:22:25'),
(15, 8, 'Lewis Davey Resource Consultants Limited', 'ar', '2024-02-07 16:24:44', '2024-02-07 16:24:44'),
(16, 8, 'Lewis Davey Resource Consultants Limited', 'en', '2024-02-07 16:24:44', '2024-02-07 16:24:44'),
(17, 9, 'ALKAFI LIMITED', 'ar', '2024-02-07 16:26:01', '2024-02-07 16:26:01'),
(18, 9, 'ALKAFI LIMITED', 'en', '2024-02-07 16:26:01', '2024-02-07 16:26:01'),
(19, 10, 'KAZI WORLD COMPANY LIMITED', 'ar', '2024-02-07 16:26:12', '2024-02-07 16:26:12'),
(20, 10, 'KAZI WORLD COMPANY LIMITED', 'en', '2024-02-07 16:26:12', '2024-02-07 16:26:12'),
(21, 11, 'Trust Manpower Agency', 'ar', '2024-02-07 16:26:32', '2024-02-07 16:26:32'),
(22, 11, 'Trust Manpower Agency', 'en', '2024-02-07 16:26:32', '2024-02-07 16:26:32'),
(23, 12, 'Jaro Holdings International Limited', 'ar', '2024-02-07 16:26:57', '2024-02-07 16:26:57'),
(24, 12, 'Jaro Holdings International Limited', 'en', '2024-02-07 16:26:57', '2024-02-07 16:26:57'),
(25, 13, 'Kagadi Recruitment Agency Limited', 'ar', '2024-02-07 16:27:09', '2024-02-07 16:27:09'),
(26, 13, 'Kagadi Recruitment Agency Limited', 'en', '2024-02-07 16:27:09', '2024-02-07 16:27:09'),
(27, 14, 'WASEL RECRUITMENT', 'ar', '2024-02-07 16:27:21', '2024-02-07 16:27:21'),
(28, 14, 'WASEL RECRUITMENT', 'en', '2024-02-07 16:27:21', '2024-02-07 16:27:21'),
(29, 15, 'EXPO INDIA', 'ar', '2024-02-07 16:27:40', '2024-02-07 16:27:40'),
(30, 15, 'EXPO INDIA', 'en', '2024-02-07 16:27:40', '2024-02-07 16:27:40'),
(31, 16, 'ARAB WINGS INTERNATIONAL TRAVELS', 'ar', '2024-02-07 16:27:51', '2024-02-07 16:27:51'),
(32, 16, 'ARAB WINGS INTERNATIONAL TRAVELS', 'en', '2024-02-07 16:27:51', '2024-02-07 16:27:51'),
(33, 17, 'AL Mansoor Labour Supply Services', 'ar', '2024-02-07 16:28:00', '2024-02-07 16:28:00'),
(34, 17, 'AL Mansoor Labour Supply Services', 'en', '2024-02-07 16:28:00', '2024-02-07 16:28:00'),
(35, 18, 'REMAL OVERSEAS', 'ar', '2024-02-07 16:28:09', '2024-03-28 22:20:35'),
(36, 18, 'REMAL OVERSEAS', 'en', '2024-02-07 16:28:09', '2024-03-28 22:20:35'),
(37, 19, 'ALSALEH ENTERPRISES', 'ar', '2024-02-07 16:28:20', '2024-02-07 16:28:20'),
(38, 19, 'ALSALEH ENTERPRISES', 'en', '2024-02-07 16:28:20', '2024-02-07 16:28:20'),
(39, 20, 'AL AMNA FOREIGN EMPLOYMENT AGENCY (PVT) LTD', 'ar', '2024-02-07 16:30:46', '2024-02-07 16:30:46'),
(40, 20, 'AL AMNA FOREIGN EMPLOYMENT AGENCY (PVT) LTD', 'en', '2024-02-07 16:30:46', '2024-02-07 16:30:46'),
(41, 21, 'AL HAMDAN TRAVELS', 'ar', '2024-02-07 16:30:56', '2024-02-07 16:30:56'),
(42, 21, 'AL HAMDAN TRAVELS', 'en', '2024-02-07 16:30:56', '2024-02-07 16:30:56'),
(43, 22, 'WORLD AIR TRAVELS', 'ar', '2024-02-07 16:31:06', '2024-02-07 16:31:06'),
(44, 22, 'WORLD AIR TRAVELS', 'en', '2024-02-07 16:31:06', '2024-02-07 16:31:06'),
(45, 23, 'Malindo Recruiting Agency Co LTD', 'ar', '2024-02-07 16:31:22', '2024-02-07 16:31:22'),
(46, 23, 'Malindo Recruiting Agency Co LTD', 'en', '2024-02-07 16:31:22', '2024-02-07 16:31:22'),
(47, 24, 'White Gloves International', 'ar', '2024-02-07 16:32:18', '2024-02-07 16:32:18'),
(48, 24, 'White Gloves International', 'en', '2024-02-07 16:32:18', '2024-02-07 16:32:18'),
(49, 25, 'Khawaza Khela Manpower Services', 'ar', '2024-02-07 16:32:28', '2024-02-07 16:32:28'),
(50, 25, 'Khawaza Khela Manpower Services', 'en', '2024-02-07 16:32:28', '2024-02-07 16:32:28'),
(51, 26, 'LEADWELL INTERNATIONAL MANPOWER SERVICES INC', 'ar', '2024-06-06 10:58:04', '2024-06-06 10:58:04'),
(52, 26, 'LEADWELL INTERNATIONAL MANPOWER SERVICES INC', 'en', '2024-06-06 10:58:04', '2024-06-06 10:58:04'),
(55, 28, 'M.A.A,F.S FUTURE RECRUNMENT AGENCIES LIMITED', 'ar', '2025-07-08 17:06:00', '2025-07-08 17:06:00'),
(56, 28, 'M.A.A,F.S FUTURE RECRUNMENT AGENCIES LIMITED', 'en', '2025-07-08 17:06:00', '2025-07-08 17:06:00'),
(57, 29, 'مكتب تجربه مصر', 'ar', '2025-07-19 18:44:11', '2025-07-19 18:44:11'),
(58, 29, 'Test office Egypt+', 'en', '2025-07-19 18:44:11', '2025-07-19 18:44:11'),
(59, 30, 'M and R foreign employment agent plc', 'ar', '2025-07-28 16:52:47', '2025-07-28 16:52:47'),
(60, 30, 'M and R foreign employment agent plc', 'en', '2025-07-28 16:52:47', '2025-07-28 16:52:47');

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_requests`
--

CREATE TABLE `recruitment_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `client_id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED DEFAULT NULL,
  `admin_id` bigint UNSIGNED DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `experience_years` int NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `request_type` enum('admin','employee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `religions`
--

CREATE TABLE `religions` (
  `id` bigint UNSIGNED NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `religions`
--

INSERT INTO `religions` (`id`, `is_show`, `created_at`, `updated_at`) VALUES
(1, 1, '2023-12-21 12:31:16', '2023-12-21 13:38:17'),
(3, 1, '2023-12-21 13:40:24', '2023-12-21 13:40:24'),
(4, 1, '2023-12-21 15:40:24', '2023-12-21 15:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `religions_translations`
--

CREATE TABLE `religions_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `religion_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `religions_translations`
--

INSERT INTO `religions_translations` (`id`, `religion_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'غير مسلم', 'ar', '2023-12-21 12:31:16', '2024-01-09 07:19:18'),
(2, 1, 'not muslim', 'en', '2023-12-21 12:31:16', '2024-01-09 07:19:18'),
(5, 3, 'مسلم', 'ar', '2023-12-21 13:40:24', '2024-01-09 07:19:32'),
(6, 3, 'muslim', 'en', '2023-12-21 13:40:24', '2024-01-09 07:19:32'),
(8, 4, 'not selected', 'en', '2023-12-21 15:40:24', '2024-01-09 09:19:32'),
(9, 4, 'غير محدد', 'ar', '2023-12-21 15:40:24', '2024-01-09 09:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `rental_contracts`
--

CREATE TABLE `rental_contracts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL COMMENT 'معرف العميل',
  `maid_id` bigint UNSIGNED DEFAULT NULL COMMENT 'معرف العاملة',
  `marketer_id` bigint UNSIGNED DEFAULT NULL COMMENT 'معرف المسوق',
  `order_id` bigint UNSIGNED DEFAULT NULL COMMENT 'معرف الطلب المرتبط',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new' COMMENT 'حالة العقد',
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'مسار ملف التوقيع',
  `closure_type` enum('available','transferred') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `transferred_to_contract_id` bigint UNSIGNED DEFAULT NULL,
  `contract_date` date DEFAULT NULL COMMENT 'تاريخ العقد',
  `contract_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'رقم العقد',
  `warranty_status` enum('not_applicable','active','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_applicable' COMMENT 'حالة الضمان',
  `warranty_start_date` date DEFAULT NULL COMMENT 'تاريخ بداية الضمان',
  `warranty_end_date` date DEFAULT NULL COMMENT 'تاريخ انتهاء الضمان',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_contract_infos`
--

CREATE TABLE `rental_contract_infos` (
  `id` bigint UNSIGNED NOT NULL,
  `rental_contract_id` bigint UNSIGNED NOT NULL COMMENT 'معرف عقد التأجير',
  `working_hours_per_day` int DEFAULT NULL COMMENT 'عدد ساعات العمل يومياً',
  `hourly_rate` decimal(8,2) DEFAULT NULL COMMENT 'أجرة الساعة',
  `monthly_salary` decimal(10,2) DEFAULT NULL COMMENT 'الراتب الشهري',
  `sponsorship_transfer_amount` decimal(10,2) DEFAULT NULL COMMENT 'مبلغ نقل الكفالة',
  `salary_after_sponsorship` decimal(10,2) DEFAULT NULL COMMENT 'الراتب بعد نقل الكفالة',
  `monthly_cost` decimal(10,2) DEFAULT NULL COMMENT 'التكلفة الشهرية',
  `contract_duration_months` int DEFAULT NULL COMMENT 'مدة العقد بالشهور',
  `contract_duration_days` int DEFAULT NULL COMMENT 'مدة العقد بالأيام',
  `contract_value` decimal(12,2) DEFAULT NULL COMMENT 'قيمة العقد المحسوبة',
  `ingaz_from` date DEFAULT NULL COMMENT 'الإنجاز من',
  `ingaz_to` date DEFAULT NULL COMMENT 'الإنجاز إلى',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات',
  `additional_terms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'شروط إضافية',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sponsorship_transfer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'نقل الكفالة (yes/no)',
  `rental_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'نوع التأجير'
) ;

--
-- Dumping data for table `rental_contract_infos`
--

INSERT INTO `rental_contract_infos` (`id`, `rental_contract_id`, `working_hours_per_day`, `hourly_rate`, `monthly_salary`, `sponsorship_transfer_amount`, `salary_after_sponsorship`, `monthly_cost`, `contract_duration_months`, `contract_duration_days`, `contract_value`, `ingaz_from`, `ingaz_to`, `notes`, `additional_terms`, `created_at`, `updated_at`, `sponsorship_transfer`, `rental_type`) VALUES
(1, 1, 8, NULL, NULL, NULL, NULL, 2600.00, 3, 90, 7800.00, '2025-08-09', '2025-11-07', NULL, NULL, '2025-08-09 18:22:13', '2025-08-09 18:22:13', NULL, NULL),
(2, 2, 8, NULL, NULL, NULL, NULL, 3000.00, 2, 60, 6000.00, '2025-08-23', '2025-10-22', NULL, NULL, '2025-08-23 09:03:39', '2025-08-23 09:03:39', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rental_contract_terms`
--

CREATE TABLE `rental_contract_terms` (
  `id` bigint UNSIGNED NOT NULL,
  `section_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_order` int NOT NULL DEFAULT '1',
  `term_number` int DEFAULT NULL,
  `term_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `term_order` int NOT NULL DEFAULT '1',
  `page_number` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_contract_terms`
--

INSERT INTO `rental_contract_terms` (`id`, `section_title`, `section_order`, `term_number`, `term_content`, `term_order`, `page_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'أولاً: موضوع العقد', 1, NULL, 'يقر الطرف الأول بأنه يزاول نشاط تقديم خدمات العمالة المنزلية بموجب ترخيص رقم -------------- ويقدم الطرف الأول خدمات العمالة المنزلية للطرف الثاني بناء على طلبه وفقا للشروط والأحكام الواردة في هذا العقد.', 1, 1, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(2, 'ثانياً: مدة العقد', 2, NULL, 'مدة العقد [مدة_العقد] يوماً على أن تبدأ من [تاريخ_البداية] قابلة للتجديد لمدة أو مدد مماثلة بموجب إشعار مكتوب من الطرف الثاني للطرف الأول قبل نهاية العقد', 1, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(3, 'ثالثاً: قيمة العقد', 3, 1, 'يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العاملة المنزلية: وفقا للتكلفة الشهرية ([التكلفة_الشهرية] ريال) المعلنة في تاريخ التعاقد', 1, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(4, 'ثالثاً: قيمة العقد', 3, 2, 'إجمالي قيمة العقد: [إجمالي_التكلفة] ريال سعودي', 2, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(5, 'ثالثاً: قيمة العقد', 3, 3, 'في حال تجديد هذا العقد، ومرافقة العامل على الاستمرار في العمل لدى الطرف الثاني، يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العامل المنزلي للمدة الجديدة تعادل التكلفة السابقة.', 3, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(6, 'رابعا: التزامات الطرف الأول', 4, 1, 'أن يقدم للطرف الثاني عاملة منزلية ([اسم_العاملة]) من جنسية ([جنسية_العاملة]) ذات كفاءة وخبرة بمهنة خدمة المنازل.', 1, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(7, 'رابعا: التزامات الطرف الأول', 4, 2, 'إعداد العامل وتهيئته للعمل، والتأكد من جاهزيته لتقديم الخدمة.', 2, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(8, 'رابعا: التزامات الطرف الأول', 4, 3, 'تسليم العامل للطرف الثاني في الوقت والمكان المتفق عليه.', 3, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(9, 'رابعا: التزامات الطرف الأول', 4, 4, 'استبدال العامل في حال عدم كفاءته، أو رغبة الطرف الثاني في استبداله خلال (24) ساعة.', 4, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(10, 'خامسا: التزامات الطرف الثاني', 5, 1, 'دفع المبالغ المستحقة عليه للطرف الأول في المواعيد المحددة.', 1, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(11, 'خامسا: التزامات الطرف الثاني', 5, 2, 'توفير مكان مناسب لإقامة العامل.', 2, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(12, 'خامسا: التزامات الطرف الثاني', 5, 3, 'توفير الغذاء والكساء المناسب للعامل.', 3, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(13, 'خامسا: التزامات الطرف الثاني', 5, 4, 'توفير العلاج الطبي اللازم للعامل في حالة المرض أو الإصابة.', 4, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(14, 'خامسا: التزامات الطرف الثاني', 5, 5, 'معاملة العامل معاملة حسنة وعدم تكليفه بأعمال غير متفق عليها.', 5, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(15, 'خامسا: التزامات الطرف الثاني', 5, 6, 'عدم السماح للعامل بالعمل لدى الغير.', 6, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(16, 'خامسا: التزامات الطرف الثاني', 5, 7, 'تسليم العامل جواز سفره ووثائقه الشخصية.', 7, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(17, 'خامسا: التزامات الطرف الثاني', 5, 8, 'إعادة العامل للطرف الأول عند انتهاء مدة العقد أو عند فسخه.', 8, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(18, 'خامسا: التزامات الطرف الثاني', 5, 9, 'تحمل كافة المسؤوليات القانونية الناتجة عن مخالفة الأنظمة والقوانين.', 9, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(19, 'خامسا: التزامات الطرف الثاني', 5, 10, 'إبلاغ الطرف الأول كتابيا في حال تغيب العامل عن العمل أو رفضه العمل خلال (24) ساعة من وقت التغيب أو الرفض.', 10, 2, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(20, 'خامسا: التزامات الطرف الثاني', 5, 11, 'إعادة العامل عند انتهاء مدة الاختبار أو التقييم خدماته للطرف الأول، وفي حال عدم التزام الطرف الثاني بذلك فأنه يعوض الطرف الأول بما يعادل (150) مائة وخمسون ريال سعودي عن كل يوم تأخير.', 11, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(21, 'خامسا: التزامات الطرف الثاني', 5, 12, 'في حالة إعادة العاملة خلال مدة العقد يتم احتساب معادلة الخصم (150) مائة وخمسون ريال سعودي عن كل يوم عمل به لدى الطرف الثاني ويتم إعادة المبلغ المتبقي.', 12, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(22, 'سادسا: أحكام عامه', 6, 1, 'لايجوز للطرف الثاني القيام بإعادة تقديم خدمات العامل لأي طرف آخر سواء بطريقة مباشره أو غير مباشرة', 1, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(23, 'سادسا: أحكام عامه', 6, 2, 'يرتبط العامل مع الطرف الأول بعقد عمل منتهي، ينقل الكفالة للطرف الأول بعد انتهاء مدة الاختبار المتفق عليها في العقد، ويعمل تحت إشرافه وإدارته وفقا لنظام العمل.', 2, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(24, 'سادسا: أحكام عامه', 6, 3, 'يخضع العامل لتعليمات الطرف الثاني، وعليه الحفاظ على أسرار أعماله وأسرار أسرته ويكون للطرف الثاني حق توجيهه حسب مقتضيات العمل أدبية، ووفقا للأنظمة السارية.', 3, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(25, 'سادسا: أحكام عامه', 6, 4, 'يحق للطرف الثاني إنهاء هذا العقد في أي وقت يشاء بشرط أن يشعر الطرف الأول كتابيا برغبته في إنهاء العقد قبل موعد الإنهاء بمدة لا تقل عن (30) يوم.', 4, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(26, 'سابعا', 7, NULL, 'يخضع هذا العقد للأنظمة والقوانين المعمول بها في المملكة العربية السعودية، وفي حال حدوث أي خلاف أو نزاع بين الطرفين بشأن تفسير أو تنفيذ هذا العقد، يتم حله بالطرق الودية، وإذا تعذر ذلك يحال النزاع إلى الجهات المختصة في المملكة العربية السعودية.', 1, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23'),
(27, 'ثامنا', 8, NULL, 'حرر هذا العقد من نسختين أصليتين باللغة العربية، وتم التوقيع عليه من قبل الطرفين، وتسلم كل طرف نسخة للعمل بموجبها.', 1, 3, 1, '2025-08-09 10:08:23', '2025-08-09 10:08:23');

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', '2024-09-03 03:03:21', '2024-09-03 03:03:21'),
(2, 'admin', 'web', '2025-05-13 23:32:27', '2025-05-13 23:32:27'),
(3, 'خدمة العملاء', 'admin', '2025-05-17 20:16:24', '2025-05-17 20:16:24'),
(4, 'قسم المبيعات', 'admin', '2025-06-26 19:35:13', '2025-06-26 19:35:13'),
(5, 'قسم الخارجية', 'admin', '2025-06-26 19:35:25', '2025-06-26 19:35:25'),
(6, 'متابعة ما بعد البيع  والسكن', 'admin', '2025-06-26 19:35:37', '2025-06-26 19:35:37'),
(7, 'قسم التسويق', 'admin', '2025-06-26 19:35:56', '2025-06-26 19:35:56'),
(8, 'التأجير', 'admin', '2025-06-26 19:43:56', '2025-06-26 19:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(13, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(66, 1),
(67, 1),
(68, 1),
(69, 1),
(70, 1),
(71, 1),
(72, 1),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(89, 1),
(90, 1),
(91, 1),
(92, 1),
(93, 1),
(94, 1),
(95, 1),
(96, 1),
(110, 1),
(111, 1),
(112, 1),
(113, 1),
(114, 1),
(115, 1),
(116, 1),
(117, 1),
(118, 1),
(119, 1),
(120, 1),
(121, 1),
(122, 1),
(123, 1),
(124, 1),
(125, 1),
(126, 1),
(127, 1),
(128, 1),
(129, 1),
(130, 1),
(131, 1),
(132, 1),
(133, 1),
(154, 1),
(155, 1),
(156, 1),
(157, 1),
(158, 1),
(159, 1),
(175, 1),
(176, 1),
(177, 1),
(178, 1),
(179, 1),
(180, 1),
(181, 1),
(182, 1),
(183, 1),
(184, 1),
(185, 1),
(186, 1),
(187, 1),
(188, 1),
(189, 1),
(190, 1),
(191, 1),
(192, 1),
(193, 1),
(198, 1),
(199, 1),
(200, 1),
(201, 1),
(207, 1),
(208, 1),
(209, 1),
(210, 1),
(211, 1),
(212, 1),
(213, 1),
(214, 1),
(215, 1),
(216, 1),
(217, 1),
(343, 1),
(344, 1),
(345, 1),
(346, 1),
(347, 1),
(348, 1),
(349, 1),
(350, 1),
(351, 1),
(352, 1),
(353, 1),
(354, 1),
(355, 1),
(356, 1),
(357, 1),
(358, 1),
(359, 1),
(360, 1),
(361, 1),
(362, 1),
(363, 1),
(364, 1),
(365, 1),
(366, 1),
(367, 1),
(368, 1),
(369, 1),
(370, 1),
(371, 1),
(372, 1),
(373, 1),
(374, 1),
(375, 1),
(376, 1),
(377, 1),
(378, 1),
(379, 1),
(380, 1),
(381, 1),
(382, 1),
(383, 1),
(384, 1),
(385, 1),
(386, 1),
(387, 1),
(388, 1),
(389, 1),
(391, 1),
(392, 1),
(393, 1),
(394, 1),
(395, 1),
(396, 1),
(397, 1),
(398, 1),
(399, 1),
(400, 1),
(401, 1),
(402, 1),
(1, 3),
(3, 3),
(17, 3),
(18, 3),
(19, 3),
(29, 3),
(30, 3),
(38, 3),
(39, 3),
(40, 3),
(41, 3),
(48, 3),
(49, 3),
(154, 3),
(155, 3),
(156, 3),
(157, 3),
(158, 3),
(159, 3),
(175, 3),
(176, 3),
(177, 3),
(179, 3),
(180, 3),
(186, 3),
(187, 3),
(191, 3),
(207, 3),
(208, 3),
(211, 3),
(212, 3),
(213, 3),
(343, 3),
(344, 3),
(345, 3),
(347, 3),
(348, 3),
(349, 3),
(350, 3),
(351, 3),
(352, 3),
(353, 3),
(354, 3),
(355, 3),
(356, 3),
(357, 3),
(358, 3),
(359, 3),
(360, 3),
(361, 3),
(362, 3),
(363, 3),
(364, 3),
(365, 3),
(366, 3),
(367, 3),
(368, 3),
(369, 3),
(370, 3),
(371, 3),
(372, 3),
(380, 3),
(386, 3),
(387, 3),
(388, 3),
(389, 3),
(400, 3),
(401, 3),
(402, 3),
(386, 4),
(387, 4),
(388, 4),
(389, 4),
(401, 4),
(402, 4),
(29, 5),
(30, 5),
(31, 5),
(38, 5),
(48, 5),
(49, 5),
(154, 5),
(155, 5),
(156, 5),
(157, 5),
(158, 5),
(159, 5),
(175, 5),
(176, 5),
(177, 5),
(178, 5),
(179, 5),
(180, 5),
(181, 5),
(182, 5),
(183, 5),
(184, 5),
(185, 5),
(186, 5),
(187, 5),
(188, 5),
(189, 5),
(190, 5),
(191, 5),
(192, 5),
(193, 5),
(207, 5),
(208, 5),
(209, 5),
(210, 5),
(211, 5),
(212, 5),
(213, 5),
(214, 5),
(343, 5),
(344, 5),
(345, 5),
(346, 5),
(347, 5),
(348, 5),
(349, 5),
(350, 5),
(351, 5),
(352, 5),
(353, 5),
(354, 5),
(355, 5),
(356, 5),
(357, 5),
(358, 5),
(359, 5),
(360, 5),
(361, 5),
(362, 5),
(363, 5),
(364, 5),
(365, 5),
(366, 5),
(367, 5),
(368, 5),
(369, 5),
(370, 5),
(371, 5),
(372, 5),
(380, 5),
(381, 5),
(382, 5),
(386, 5),
(387, 5),
(388, 5),
(389, 5),
(400, 5),
(401, 5),
(402, 5),
(43, 6),
(44, 6),
(45, 6),
(46, 6),
(47, 6),
(53, 6),
(54, 6),
(55, 6),
(65, 6),
(66, 6),
(89, 6),
(90, 6),
(91, 6),
(92, 6),
(154, 6),
(155, 6),
(156, 6),
(157, 6),
(158, 6),
(159, 6),
(175, 6),
(176, 6),
(177, 6),
(178, 6),
(179, 6),
(180, 6),
(181, 6),
(182, 6),
(183, 6),
(184, 6),
(185, 6),
(186, 6),
(187, 6),
(188, 6),
(189, 6),
(190, 6),
(191, 6),
(344, 6),
(373, 6),
(374, 6),
(375, 6),
(376, 6),
(377, 6),
(378, 6),
(379, 6),
(386, 6),
(387, 6),
(388, 6),
(389, 6),
(400, 6),
(401, 6),
(402, 6),
(1, 7),
(3, 7),
(4, 7),
(17, 7),
(18, 7),
(19, 7),
(20, 7),
(21, 7),
(22, 7),
(23, 7),
(24, 7),
(25, 7),
(38, 7),
(50, 7),
(51, 7),
(52, 7),
(81, 7),
(82, 7),
(83, 7),
(126, 7),
(127, 7),
(128, 7),
(129, 7),
(130, 7),
(131, 7),
(132, 7),
(133, 7),
(154, 7),
(155, 7),
(156, 7),
(157, 7),
(158, 7),
(159, 7),
(175, 7),
(176, 7),
(177, 7),
(178, 7),
(179, 7),
(180, 7),
(181, 7),
(182, 7),
(183, 7),
(184, 7),
(185, 7),
(186, 7),
(187, 7),
(188, 7),
(189, 7),
(190, 7),
(343, 7),
(344, 7),
(345, 7),
(346, 7),
(347, 7),
(348, 7),
(349, 7),
(350, 7),
(351, 7),
(352, 7),
(353, 7),
(354, 7),
(355, 7),
(356, 7),
(357, 7),
(358, 7),
(359, 7),
(360, 7),
(361, 7),
(362, 7),
(363, 7),
(364, 7),
(365, 7),
(366, 7),
(367, 7),
(368, 7),
(369, 7),
(370, 7),
(371, 7),
(372, 7),
(383, 7),
(386, 7),
(387, 7),
(388, 7),
(389, 7),
(400, 7),
(401, 7),
(402, 7),
(386, 8),
(387, 8),
(388, 8),
(389, 8),
(401, 8),
(402, 8);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'images/Service/1MxqSHLmow1703399945.svg', NULL, NULL),
(2, 'images/Service/5VOTrfctHV1703399851.svg', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(3, 'images/Service/VUbMxfl4f61703399908.svg', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(4, 'images/Service/1MxqSHLmow1703399945.svg', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(6, 'images/Service/4AckSGtdAS1703165868.svg', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services_translations`
--

CREATE TABLE `services_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `service_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services_translations`
--

INSERT INTO `services_translations` (`id`, `service_id`, `title`, `description`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'إستقدام أمهر العمالة المنزلية', 'إستقدام أمهر العمالة المنزلية بخبرة وكفاءة لضمان راحتكم وسعادتكم.', 'ar', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(2, 1, 'Recruiting skilled domestic workers', 'Providing staff for guest service at events and gatherings.', 'en', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(3, 2, 'خدمات التنظيف والصيانة', 'توفير عمالة للقيام بأعمال التنظيف الشامل والصيانة الدورية للمنازل.', 'ar', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(4, 2, 'Cleaning and maintenance services', 'Providing manpower for comprehensive cleaning and regular maintenance services for homes.', 'en', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(5, 3, 'رعاية الأطفال والمسنين', 'توفير جليسات الأطفال ورعاية وكبار السن.', 'ar', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(6, 3, 'Child and Elderly Care', 'Providing babysitting and elderly care services.', 'en', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(7, 4, 'خدمات الضيافة والمناسبات', 'توفير موظفين لخدمة الضيوف في المناسبات والفعاليات.', 'ar', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(8, 4, 'Hospitality and Services', 'Providing staff for guest service at events and gatherings.', 'en', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(12, 6, 'استقدام الطهاة المحترفين', 'توفير طهاة محترفين لتحضير وجبات شهية ومتنوعة في منازل العملاء.', 'ar', '2023-12-21 14:37:48', '2023-12-21 14:37:48'),
(13, 6, 'Recruiting professional chefs.', 'Providing professional chefs to prepare delicious and diverse meals at clients\' homes', 'en', '2023-12-21 14:37:48', '2023-12-21 14:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `logo_header` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'logo_header.png',
  `logo_footer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'logo_footer.png',
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'favicon.png',
  `twitter` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `facebook` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `instagram` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `snapchat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `youtube` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `about_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `map_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `logo_header`, `logo_footer`, `favicon`, `twitter`, `facebook`, `instagram`, `snapchat`, `linkedin`, `youtube`, `whatsapp`, `email`, `phone`, `about_image`, `other_phone`, `map_link`, `created_at`, `updated_at`) VALUES
(1, '', '', 'general', 'images/admins/settings/Zdpg85z4G71703405515.svg', 'images/admins/settings/YP1PNBxcVE1703405515.svg', 'images/admins/settings/BK5gaOUvvs1703609739.webp.svg', 'https://twitter.com/usalenjaz', 'Modi minus ex dicta', 'https://www.instagram.com/usalenjaz?igsh=MWNxY3RpbGk3eWEyOA%3D%3D&utm_source=qr', 'https://snapchat.com/t/aKjwLGeD', 'Harum autem nisi et', 'Ad cillum incidunt', '+966556395333', 'assusalengaz@gmail.com', '+966556395333', 'images/settings/EWhfBBXVFR1703609784.webp', '+ 966533346330', 'https://maps.app.goo.gl/2Ez1pZxJXzuubnTk7', '2023-12-24 08:58:17', '2025-07-26 19:35:23'),
(2, '', '', 'general', 'logo_header.png', 'logo_footer.png', 'favicon.png', '#', '#', '#', '#', '#', '#', '#', '#', '#', '', '#', '#', '2024-08-04 21:01:21', '2024-08-04 21:01:21'),
(3, '', '', 'general', 'logo_header.png', 'logo_footer.png', 'favicon.png', '#', '#', '#', '#', '#', '#', '#', '#', '#', '', '#', '#', '2024-08-04 21:02:47', '2024-08-04 21:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `settings_translations`
--

CREATE TABLE `settings_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `setting_id` bigint UNSIGNED NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `privacy` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `terms_conditions` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `return_conditions` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_times` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rights_maids` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rights_users` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `copyright` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `website_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `about_us_sub_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `about_us_app` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `footer_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings_translations`
--

INSERT INTO `settings_translations` (`id`, `setting_id`, `locale`, `location`, `privacy`, `terms_conditions`, `return_conditions`, `work_times`, `rights_maids`, `rights_users`, `copyright`, `website_name`, `about_us_sub_title`, `about_us_app`, `footer_text`, `created_at`, `updated_at`) VALUES
(1, 1, 'ar', 'اليرموك، الرياض 13251، المملكة العربية السعودية', '<h2 style=\"text-align: right;\">صفحة سياسة الخصوصية لموقع اسس الانجاز</h2>\r\n<p style=\"text-align: right;\">&nbsp;</p>\r\n<p style=\"text-align: right;\">**سياسة الخصوصية لموقع اسس الانجاز**</p>\r\n<p style=\"text-align: right;\">أخر تحديث:&nbsp;</p>\r\n<p style=\"text-align: right;\">نرحب بكم في موقعنا \"اسس الانجاز\". تلتزم الشركة بحماية خصوصيتكم ومعلوماتكم الشخصية. يرجى قراءة بيان الخصوصية التالي لفهم كيفية جمع واستخدام وحماية المعلومات التي تقدمونها لنا.</p>\r\n<p style=\"text-align: right;\">**1. المعلومات التي نقوم بجمعها:**</p>\r\n<p style=\"text-align: right;\">نقوم بجمع المعلومات التي تقدمونها لنا عند استخدام موقعنا، وذلك عندما تسجلون حسابًا، أو تقديم طلب، أو تتفاعلون مع المحتوى الخاص بنا. يمكن أن تشمل هذه المعلومات الاسم، والبريد الإلكتروني، ورقم الهاتف، ومعلومات أخرى ذات صلة.</p>\r\n<p style=\"text-align: right;\">**2. كيفية استخدام المعلومات:**</p>\r\n<p style=\"text-align: right;\">نستخدم المعلومات التي نقوم بجمعها لتقديم الخدمات والدعم للمستخدمين، ولتحسين تجربة المستخدم، ولضمان أمان المعلومات.</p>', '<h2 style=\"text-align: right;\">صفحة سياسة الخصوصية لموقع اسس الانجاز</h2>\r\n<p style=\"text-align: right;\">&nbsp;</p>\r\n<p style=\"text-align: right;\">**سياسة الخصوصية لموقع اسس الانجاز**</p>\r\n<p style=\"text-align: right;\">أخر تحديث:&nbsp;</p>\r\n<p style=\"text-align: right;\">نرحب بكم في موقعنا \"اسس الانجاز\". تلتزم الشركة بحماية خصوصيتكم ومعلوماتكم الشخصية. يرجى قراءة بيان الخصوصية التالي لفهم كيفية جمع واستخدام وحماية المعلومات التي تقدمونها لنا.</p>\r\n<p style=\"text-align: right;\">**1. المعلومات التي نقوم بجمعها:**</p>\r\n<p style=\"text-align: right;\">نقوم بجمع المعلومات التي تقدمونها لنا عند استخدام موقعنا، وذلك عندما تسجلون حسابًا، أو تقديم طلب، أو تتفاعلون مع المحتوى الخاص بنا. يمكن أن تشمل هذه المعلومات الاسم، والبريد الإلكتروني، ورقم الهاتف، ومعلومات أخرى ذات صلة.</p>\r\n<p style=\"text-align: right;\">**2. كيفية استخدام المعلومات:**</p>\r\n<p style=\"text-align: right;\">نستخدم المعلومات التي نقوم بجمعها لتقديم الخدمات والدعم للمستخدمين، ولتحسين تجربة المستخدم، ولضمان أمان المعلومات.</p>', '<p>شروط استرجاع العاملة خلال فترة التجربة ٩٠ يوم من تاريخ وصول العاملة<br>امتناع العاملة عن العمل&nbsp;<br>فشلها بالفحص الطبي بتقرير من مركز معتمد<br>عدم توفر المواصفات الاساسيه بالعقد ( العمر - الديانة - الخبرة )</p>', '', '<p style=\"text-align: right;\"><strong>حق الراتب</strong></p>\r\n<p style=\"text-align: right;\">الحق في استلام الراتب والأجور بشكل دوري وفقًا للاتفاق المتفق عليه</p>\r\n<p style=\"text-align: right;\"><strong>حق الإجازات</strong></p>\r\n<p style=\"text-align: right;\">الحق في الاستفادة من إجازات مدفوعة الأجر وفقًا للقوانين العمالية</p>\r\n<p style=\"text-align: right;\"><strong>حق الحماية الصحية</strong></p>\r\n<p style=\"text-align: right;\">الحق في التأمين الصحي والرعاية الطبية خلال فترة العمل</p>\r\n<p style=\"text-align: right;\"><strong>حق السلامة والأمان</strong></p>\r\n<p style=\"text-align: right;\">الحق في بيئة عمل آمنة وخالية من المخاطر الصحية والسلامة</p>\r\n<p style=\"text-align: right;\"><strong>&nbsp;حق التمييز</strong></p>\r\n<p style=\"text-align: right;\">الحق في عدم تعرضهم للتمييز أو المضايقة بناءً على الجنس أو العرق أو الدين أو اللون أو العمر</p>\r\n<p style=\"text-align: right;\"><strong>&nbsp;حق الاستراحة</strong></p>\r\n<p style=\"text-align: right;\">الحق في فترات استراحة منتظمة خلال فترات العمل</p>\r\n<p>&a', '<p style=\"text-align: right;\"><strong>حق الراتب</strong></p>\r\n<p style=\"text-align: right;\">الحق في استلام الراتب والأجور بشكل دوري وفقًا للاتفاق المتفق عليه</p>\r\n<p style=\"text-align: right;\"><strong>حق الإجازات</strong></p>\r\n<p style=\"text-align: right;\">الحق في الاستفادة من إجازات مدفوعة الأجر وفقًا للقوانين العمالية</p>\r\n<p style=\"text-align: right;\"><strong>حق الحماية الصحية</strong></p>\r\n<p style=\"text-align: right;\">الحق في التأمين الصحي والرعاية الطبية خلال فترة العمل</p>\r\n<p style=\"text-align: right;\"><strong>حق السلامة والأمان</strong></p>\r\n<p style=\"text-align: right;\">الحق في بيئة عمل آمنة وخالية من المخاطر الصحية والسلامة</p>\r\n<p style=\"text-align: right;\"><strong>&nbsp;حق التمييز</strong></p>\r\n<p style=\"text-align: right;\">الحق في عدم تعرضهم للتمييز أو المضايقة بناءً على الجنس أو العرق أو الدين أو اللون أو العمر</p>\r\n<p style=\"text-align: right;\"><strong>&nbsp;حق الاستراحة</strong></p>\r\n<p style=\"text-align: right;\">الحق في فترات استراحة منتظمة خلال فترات العمل</p>\r\n<p>&a', 'شركة اسس الانجاز للاستقدام ❤️', 'اسس الانجاز', 'يمكنك الأن استقدام عمالتك لفترات محدودة ماذا تنتظر!؟', '<h2 style=\"text-align: right;\">عن موقع اسس الانجاز</h2>\r\n<p style=\"text-align: right;\">&nbsp;</p>\r\n<p style=\"text-align: right;\">*عن موقع اسس الانجاز**</p>\r\n<p style=\"text-align: right;\">أخر تحديث:&nbsp;</p>\r\n<p style=\"text-align: right;\">نرحب بكم في موقعنا \"اسس الانجاز\". تلتزم الشركة بحماية خصوصيتكم ومعلوماتكم الشخصية. يرجى قراءة بيان الخصوصية التالي لفهم كيفية جمع واستخدام وحماية المعلومات التي تقدمونها لنا.</p>\r\n<p style=\"text-align: right;\">**1. المعلومات التي نقوم بجمعها:**</p>\r\n<p style=\"text-align: right;\">نقوم بجمع المعلومات التي تقدمونها لنا عند استخدام موقعنا، وذلك عندما تسجلون حسابًا، أو تقديم طلب، أو تتفاعلون مع المحتوى الخاص بنا. يمكن أن تشمل هذه المعلومات الاسم، والبريد الإلكتروني، ورقم الهاتف، ومعلومات أخرى ذات صلة.</p>\r\n<p style=\"text-align: right;\">**2. كيفية استخدام المعلومات:**</p>\r\n<p style=\"text-align: right;\">نستخدم المعلومات التي نقوم بجمعها لتقديم الخدمات والدعم للمستخدمين، ولتحسين تجربة المستخدم، ولضمان أمان المعلومات.</p>', 'مرحبًا بك في موقع شركة اسس الانجاز للاستقدام. نقدم خدمات الاستقدام بكفاءة عالية وتنوع. اكتشف خدماتنا وجعل حياتك المنزلية أسهل. للتواصل، اتصل بنا اليوم.', '2023-12-24 08:58:17', '2025-07-26 19:35:23'),
(2, 1, 'en', 'Al Yarmouk, Riyadh 13251, Kingdom of Saudi Arabia', '<p><strong>Privacy Policy for Achieve Recruitment Website</strong></p>\r\n<p>Last Updated: [Insert Last Update Date]</p>\r\n<p>Welcome to our website \"Achieve Recruitment.\" The company is committed to protecting your privacy and personal information. Please read the following privacy policy to understand how we collect, use, and protect the information you provide to us.</p>\r\n<p><strong>1. Information We Collect:</strong></p>\r\n<p>We collect information you provide to us when using our website, such as when you register an account, submit an application, or interact with our content. This information may include names, email addresses, phone numbers, and other relevant details.</p>\r\n<p><strong>2. How We Use the Information:</strong></p>\r\n<p>We use the collected information to provide services and support to users, improve the user experience, and ensure the security of the information.</p>\r\n<p><strong>3. Information Sharing:</strong></p>\r\n<p>We do not share your personal information with th', '<p><strong>Privacy Policy for Achieve Recruitment Website</strong></p>\r\n<p>Last Updated: [Insert Last Update Date]</p>\r\n<p>Welcome to our website \"Achieve Recruitment.\" The company is committed to protecting your privacy and personal information. Please read the following privacy policy to understand how we collect, use, and protect the information you provide to us.</p>\r\n<p><strong>1. Information We Collect:</strong></p>\r\n<p>We collect information you provide to us when using our website, such as when you register an account, submit an application, or interact with our content. This information may include names, email addresses, phone numbers, and other relevant details.</p>\r\n<p><strong>2. How We Use the Information:</strong></p>\r\n<p>We use the collected information to provide services and support to users, improve the user experience, and ensure the security of the information.</p>\r\n<p><strong>3. Information Sharing:</strong></p>\r\n<p>We do not share your personal information with th', '<p>Return conditions of the worker during the trial period of 90 days from the arrival date of the worker:<br>- The worker\'s refusal to work<br>- Failure of the medical examination with a report from an accredited center<br>- The basic specifications in the contract are not met (age, religion, experience)</p>', '', '<ol>\r\n<li>\r\n<p><strong>Salary Right:</strong></p>\r\n<ul>\r\n<li>The right to receive a salary and wages regularly according to the agreed-upon terms.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Leave Right:</strong></p>\r\n<ul>\r\n<li>The right to benefit from paid leave in accordance with labor laws.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Health Protection Right:</strong></p>\r\n<ul>\r\n<li>The right to health insurance and medical care during the period of employment.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Safety and Security Right:</strong></p>\r\n<ul>\r\n<li>The right to a safe work environment free from health and safety risks.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Non-discrimination Right:</strong></p>\r\n<ul>\r\n<li>The right not to be subjected to discrimination or harassment based on gender, ethnicity, religion, color, or age.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Rest Break Right:</strong></p>\r\n<ul>\r\n<li>The right to regular rest breaks during working hours.</li>\r\n</ul>\r\n</li>\r\n</ol>', '<ol>\r\n<li>\r\n<p><strong>Salary Right:</strong></p>\r\n<ul>\r\n<li>The right to receive a salary and wages regularly according to the agreed-upon terms.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Leave Right:</strong></p>\r\n<ul>\r\n<li>The right to benefit from paid leave in accordance with labor laws.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Health Protection Right:</strong></p>\r\n<ul>\r\n<li>The right to health insurance and medical care during the period of employment.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Safety and Security Right:</strong></p>\r\n<ul>\r\n<li>The right to a safe work environment free from health and safety risks.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Non-discrimination Right:</strong></p>\r\n<ul>\r\n<li>The right not to be subjected to discrimination or harassment based on gender, ethnicity, religion, color, or age.</li>\r\n</ul>\r\n</li>\r\n<li>\r\n<p><strong>Rest Break Right:</strong></p>\r\n<ul>\r\n<li>The right to regular rest breaks during working hours.</li>\r\n</ul>\r\n</li>\r\n</ol>', '❤️ Asas Al-Injaaz', 'Ussus Alenjaz', '\"You can now recruit your workforce for limited periods. What are you waiting for!?\"', '<p><strong>About Achieve Recruitment Website</strong></p>\r\n<p>Last Updated: [Insert Last Update Date]</p>\r\n<p>Welcome to our website \"Achieve Recruitment.\" The company is committed to protecting your privacy and personal information. Please read the following privacy policy to understand how we collect, use, and protect the information you provide to us.</p>\r\n<p><strong>1. Information We Collect:</strong></p>\r\n<p>We collect information you provide to us when using our website, such as when you register an account, submit an application, or interact with our content. This information may include names, email addresses, phone numbers, and other relevant details.</p>\r\n<p><strong>2. How We Use the Information:</strong></p>\r\n<p>We use the collected information to provide services and support to users, improve the user experience, and ensure the security of the information.</p>\r\n<p><strong>3. Information Sharing:</strong></p>\r\n<p>We do not share your personal information with th</p>', 'Welcome to the Achieve Recruitment Company website. We offer efficient and diverse recruitment services. Explore our services and make your home life easier. For inquiries, contact us today.', '2023-12-24 08:58:17', '2025-07-26 19:35:23');

-- --------------------------------------------------------

--
-- Table structure for table `shelter_places`
--

CREATE TABLE `shelter_places` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelter_places`
--

INSERT INTO `shelter_places` (`id`, `created_at`, `updated_at`) VALUES
(1, '2024-05-02 17:10:28', '2024-05-02 17:10:28');

-- --------------------------------------------------------

--
-- Table structure for table `shelter_place_translations`
--

CREATE TABLE `shelter_place_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `shelter_place_id` bigint UNSIGNED NOT NULL,
  `title` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelter_place_translations`
--

INSERT INTO `shelter_place_translations` (`id`, `shelter_place_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'إيواء حي الرمال', 'ar', '2024-05-02 17:10:28', '2024-05-02 17:10:28'),
(2, 1, 'Rimal Accomodation', 'en', '2024-05-02 17:10:28', '2024-05-02 17:10:28');

-- --------------------------------------------------------

--
-- Table structure for table `shelter_workers`
--

CREATE TABLE `shelter_workers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `residence_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passport_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationality_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shelter_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_by_other` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `worker_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `travel_doc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `travel_doc_notes` text COLLATE utf8mb4_unicode_ci,
  `settlement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_notes` text COLLATE utf8mb4_unicode_ci,
  `graduation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_notes` text COLLATE utf8mb4_unicode_ci,
  `reserve` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reserve_date` date DEFAULT NULL,
  `reserve_notes` text COLLATE utf8mb4_unicode_ci,
  `travel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `travel_notes` text COLLATE utf8mb4_unicode_ci,
  `contract_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelter_workers`
--

INSERT INTO `shelter_workers` (`id`, `name`, `city`, `worker_name`, `residence_num`, `passport_num`, `nationality_id`, `shelter_status`, `received_by`, `received_by_other`, `status`, `deleted_at`, `created_at`, `updated_at`, `worker_type`, `travel_doc`, `travel_doc_notes`, `settlement`, `settlement_notes`, `graduation`, `graduation_notes`, `reserve`, `reserve_date`, `reserve_notes`, `travel`, `travel_notes`, `contract_id`) VALUES
(1, 'ايواء محلى الرياض', 'الرياض', 'عامل تست', '54456544', 'PD54544445', '2', 'absent', 'other', 'آخر آخر', 'awaiting_service_transfer', '2024-08-01 20:23:30', '2024-08-01 20:18:57', '2024-08-01 20:23:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'ايواء محلى', 'الرياض', 'عامل', '54456544', 'PD54544445', '4', 'good', 'other', 'آخر آخر', 'rent', '2025-07-02 19:37:27', '2024-08-01 20:19:09', '2025-07-02 19:37:27', 'instant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-02-12', NULL, NULL, NULL, NULL),
(3, 'عامله', 'مدينه', 'تست', '222333', '444555333', '3', 'absent', 'embassy', NULL, 'deportation', NULL, '2024-08-29 22:14:01', '2025-05-17 20:39:46', 'deportation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17', NULL, NULL, NULL, NULL),
(4, 'ثثثثثثثثثثثثث', 'الرياض', 'ثثثثث', 'لا يوجد', 'ِِA222222222', '7', 'absent', 'company', NULL, 'deportation', NULL, '2025-01-10 22:44:17', '2025-01-10 22:44:17', 'deportation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'eeee', 'eee', 'eeee', '2222222222', 'rr22222222', '3', 'absent', 'client', NULL, 'deportation', NULL, '2025-04-06 20:56:10', '2025-04-06 20:57:51', 'deportation', 'uploads/travel_docs/y1T3CFaskkjdM7SWCMlqrcBqUM6GKL4jENtTdlJu.pdf', NULL, NULL, NULL, NULL, NULL, 'uploads/reserves/GNk2pT1l2i384tiOAk0KjqRfda8oijQ0koJ9zMT7.pdf', '2025-04-08', NULL, NULL, NULL, NULL),
(6, 'sa', 'aa', 'qq', '33', 'ww', '2', 'good', 'client', NULL, 'service_transfer', NULL, '2025-04-10 19:52:42', '2025-04-10 19:52:42', 'instant', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'aaf', 'rrh', 'hrhr', 'h33', 'eee', '3', 'good', 'client', NULL, 'rent', NULL, '2025-04-10 19:53:19', '2025-04-10 19:53:19', 'rent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` bigint UNSIGNED NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `is_show`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-05-13 23:33:07', '2025-05-13 23:33:07'),
(2, 1, '2025-05-13 23:33:13', '2025-05-13 23:33:13'),
(3, 1, '2025-05-13 23:33:20', '2025-05-13 23:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `skills_translations`
--

CREATE TABLE `skills_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `skill_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills_translations`
--

INSERT INTO `skills_translations` (`id`, `skill_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'الطبخ', 'ar', '2025-05-13 23:33:07', '2025-05-13 23:33:07'),
(2, 1, 'cooking', 'en', '2025-05-13 23:33:07', '2025-05-13 23:33:07'),
(3, 2, 'التنظيف', 'ar', '2025-05-13 23:33:13', '2025-05-13 23:33:13'),
(4, 2, 'cleaninig', 'en', '2025-05-13 23:33:13', '2025-05-13 23:33:13'),
(5, 3, 'رعاية الاطفال', 'ar', '2025-05-13 23:33:20', '2025-05-13 23:33:20'),
(6, 3, 'رعاية الاطفال', 'en', '2025-05-13 23:33:20', '2025-05-13 23:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'images/Slider/Q23JrMmx6j1708024545.webp', '2023-12-21 11:53:37', '2024-02-15 22:15:45'),
(2, 'images/Slider/BKj4B68TqC1709196105.webp', '2023-12-21 12:02:18', '2024-02-29 11:41:45'),
(3, 'images/Slider/1u3jXE00k61744648379.jpg', '2025-04-14 19:32:59', '2025-04-14 19:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `sliders_translations`
--

CREATE TABLE `sliders_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `slider_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders_translations`
--

INSERT INTO `sliders_translations` (`id`, `slider_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'اعلان شركة الانشاءات', 'ar', '2023-12-21 12:01:56', '2023-12-21 12:01:56'),
(2, 1, 'Ad For Company', 'en', '2023-12-21 12:01:56', '2023-12-21 12:01:56'),
(3, 2, 'Ussus', 'ar', '2024-02-29 11:41:45', '2024-02-29 11:41:45'),
(4, 2, 'Ussus', 'en', '2024-02-29 11:41:45', '2024-02-29 11:41:45'),
(5, 3, 'لا تشيلي هم شغل البيت', 'ar', '2025-04-14 19:32:59', '2025-04-14 19:32:59'),
(6, 3, 'لا تشيلي هم شغل البيت', 'en', '2025-04-14 19:32:59', '2025-04-14 19:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sponsorship_transfers`
--

CREATE TABLE `sponsorship_transfers` (
  `id` bigint UNSIGNED NOT NULL,
  `contract_id` bigint UNSIGNED NOT NULL,
  `old_sponsor_id` bigint UNSIGNED NOT NULL,
  `new_sponsor_id` bigint UNSIGNED NOT NULL,
  `trial_days` int NOT NULL,
  `trial_start_date` date NOT NULL,
  `contract_value` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `remaining_amount` decimal(10,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','trial','transferred','failed','rejected','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `trial_started_at` timestamp NULL DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `transferred_by` bigint UNSIGNED DEFAULT NULL,
  `failed_by` bigint UNSIGNED DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `statistics`
--

CREATE TABLE `statistics` (
  `id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics`
--

INSERT INTO `statistics` (`id`, `image`, `counter_num`, `counter_unit`, `created_at`, `updated_at`) VALUES
(8, 'fa fa-hourglass', '9875', '', '2024-02-08 10:21:06', '2025-01-12 17:29:44'),
(9, 'fa fa-check', '9467', '', '2024-02-08 10:24:12', '2025-01-12 17:29:10'),
(10, 'fa fa-users', '9373', '', '2024-02-08 10:30:15', '2025-01-12 17:29:24');

-- --------------------------------------------------------

--
-- Table structure for table `statistics_translations`
--

CREATE TABLE `statistics_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `statistic_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics_translations`
--

INSERT INTO `statistics_translations` (`id`, `statistic_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(15, 8, 'طلبات الاستقدام', 'ar', '2024-02-08 10:21:06', '2024-02-08 10:21:06'),
(16, 8, 'Recruitment Orders', 'en', '2024-02-08 10:21:06', '2024-02-08 10:21:06'),
(17, 9, 'عقود تمت بنجاح', 'ar', '2024-02-08 10:24:12', '2024-02-08 10:24:12'),
(18, 9, 'Contracts', 'en', '2024-02-08 10:24:12', '2024-02-08 10:24:12'),
(19, 10, 'عميل راضي عن الخدمة', 'ar', '2024-02-08 10:30:15', '2024-02-08 10:30:15'),
(20, 10, 'Satisfy', 'en', '2024-02-08 10:30:15', '2024-02-08 10:30:15');

-- --------------------------------------------------------

--
-- Table structure for table `steps`
--

CREATE TABLE `steps` (
  `id` bigint UNSIGNED NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `steps_translations`
--

CREATE TABLE `steps_translations` (
  `id` bigint UNSIGNED NOT NULL,
  `step_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_histories`
--

CREATE TABLE `travel_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `maid_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `travel_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `travel_with` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint UNSIGNED DEFAULT NULL,
  `arrived_at` timestamp NULL DEFAULT NULL,
  `arrival_confirmed_by` bigint UNSIGNED DEFAULT NULL,
  `arrival_airport` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `travel_histories`
--

INSERT INTO `travel_histories` (`id`, `user_id`, `maid_id`, `order_id`, `travel_type`, `travel_with`, `notes`, `status`, `deleted_at`, `created_at`, `updated_at`, `confirmed_at`, `confirmed_by`, `arrived_at`, `arrival_confirmed_by`, `arrival_airport`, `arrival_date`, `arrival_time`, `flight_company`, `flight_number`) VALUES
(1, 20, 241, NULL, 'outside', 'airport', 'y', 'pending', '2025-07-14 18:23:30', '2024-09-01 18:29:44', '2025-07-14 18:23:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 668, 471, 36, 'outside', 'airport', NULL, 'pending', '2025-01-09 18:19:23', '2025-01-09 18:18:42', '2025-01-09 18:19:23', NULL, NULL, NULL, NULL, 'الرياض', '2025-01-09', '12:03', 'السعودية', 'SV865'),
(3, 707, 435, 35, 'inside', 'airport', NULL, 'pending', '2025-07-14 18:23:24', '2025-01-09 18:55:33', '2025-07-14 18:23:24', NULL, NULL, NULL, NULL, 'الرياض', '2025-01-09', '13:03', 'الفلبينيه', 'PR650'),
(4, 712, 434, 38, 'outside', 'airport', NULL, 'pending', '2025-07-14 18:23:18', '2025-01-10 22:18:41', '2025-07-14 18:23:18', NULL, NULL, NULL, NULL, 'الرياض', '2025-01-12', '11:19', 'السعو', 'SV862'),
(5, 18, 562, NULL, 'outside', 'airport', NULL, 'pending', '2025-02-11 16:20:38', '2025-02-11 16:20:29', '2025-02-11 16:20:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 775, 763, 40, 'outside', 'airport', NULL, 'pending', '2025-07-14 18:23:12', '2025-02-16 18:50:14', '2025-07-14 18:23:12', NULL, NULL, NULL, NULL, 'gggg', '2025-02-17', '00:01', 'ss', 'aa'),
(7, 729, 568, 41, 'outside', 'airport', 'fife', 'pending', '2025-07-14 18:23:05', '2025-04-06 20:30:02', '2025-07-14 18:23:05', NULL, NULL, NULL, NULL, 'rugs', '2025-04-06', '23:30', 'def', 'fefe'),
(8, 729, 568, 41, 'inside', 'other', NULL, 'pending', '2025-07-14 18:22:53', '2025-04-06 20:31:20', '2025-07-14 18:22:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 729, 568, 41, 'inside', 'other', NULL, 'pending', '2025-07-14 18:22:45', '2025-04-06 20:33:09', '2025-07-14 18:22:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 729, 573, 42, 'outside', 'airport', NULL, 'pending', '2025-07-14 18:22:35', '2025-04-06 20:47:44', '2025-07-14 18:22:35', NULL, NULL, NULL, NULL, 'zas', '2025-04-06', '20:48', 'r', NULL),
(11, 729, 573, 42, 'inside', 'other', NULL, 'pending', '2025-07-14 18:22:27', '2025-04-06 20:50:29', '2025-07-14 18:22:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 729, 573, 42, 'inside', 'other', NULL, 'pending', '2025-07-14 18:23:38', '2025-04-06 20:58:08', '2025-07-14 18:23:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 729, 573, 42, 'inside', 'other', NULL, 'pending', '2025-07-14 18:23:46', '2025-04-15 09:30:09', '2025-07-14 18:23:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 729, 573, 42, 'inside', 'other', NULL, 'pending', '2025-07-14 18:23:58', '2025-04-15 09:31:29', '2025-07-14 18:23:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 1024, 763, 44, 'outside', 'airport', NULL, 'pending', '2025-07-14 18:24:22', '2025-05-17 19:49:32', '2025-07-14 18:24:22', NULL, NULL, NULL, NULL, 'Riyadh', '2025-05-16', '23:30', 'Saudi Airlines', 'SV1805'),
(16, 1024, 763, 44, 'inside', 'other', NULL, 'pending', '2025-07-14 18:24:28', '2025-05-17 19:50:11', '2025-07-14 18:24:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 742, 563, 45, 'inside', 'other', NULL, 'completed', '2025-07-14 18:24:51', '2025-05-27 00:35:33', '2025-07-14 18:24:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 1024, 763, 44, 'outside', 'other', NULL, 'pending', '2025-07-14 18:24:11', '2025-05-27 09:13:54', '2025-07-14 18:24:11', NULL, NULL, NULL, NULL, NULL, '2025-02-01', '23:30', NULL, NULL),
(19, 1024, 763, 44, 'inside', 'other', NULL, 'pending', '2025-07-14 18:24:33', '2025-05-27 09:14:20', '2025-07-14 18:24:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 746, 565, 46, 'inside', 'other', NULL, 'completed', '2025-07-14 18:24:46', '2025-06-01 17:41:38', '2025-07-14 18:24:46', NULL, NULL, NULL, NULL, '0', '2026-06-01', '13:00', NULL, NULL),
(21, 723, 562, 48, 'inside', 'other', NULL, 'completed', '2025-07-14 18:24:38', '2025-06-04 18:38:29', '2025-07-14 18:24:38', NULL, NULL, NULL, NULL, NULL, '2025-06-04', '18:38', NULL, NULL),
(22, 1071, 1265, 51, 'inside', 'other', NULL, 'completed', '2025-07-14 18:25:11', '2025-06-26 18:41:19', '2025-07-14 18:25:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 1077, 1258, 52, 'outside', 'airport', NULL, 'completed', '2025-07-14 18:24:55', '2025-06-26 19:57:53', '2025-07-14 18:24:55', NULL, NULL, NULL, NULL, 'Riyadh', '2025-06-24', '16:30', 'Saudi AIrlines', 'SV680'),
(24, 1066, 1255, 53, 'inside', 'other', NULL, 'completed', '2025-07-14 18:25:00', '2025-06-26 20:39:33', '2025-07-14 18:25:00', NULL, NULL, NULL, NULL, NULL, '2025-06-16', '16:00', NULL, NULL),
(25, 1093, 1245, 55, 'outside', 'airport', NULL, 'completed', '2025-07-14 18:25:24', '2025-06-29 18:36:10', '2025-07-14 18:25:24', NULL, NULL, NULL, NULL, 'Riyadh', '2025-06-23', '17:00', 'Saudi AIrlines', 'SV680'),
(26, 20, 1503, 117, 'inside', 'other', NULL, 'approved', '2025-07-29 10:57:53', '2025-07-20 16:40:54', '2025-07-29 10:57:53', NULL, NULL, NULL, NULL, 'Riyadh', '2025-07-17', '15:00', 'Saudi AIrlines', 'SV680'),
(27, 1193, 1981, 84, 'international', 'airport', NULL, 'completed', NULL, '2025-07-28 18:35:53', '2025-08-03 11:29:55', NULL, NULL, NULL, NULL, 'JED', '2025-08-01', '22:25', 'AKASA AIR', 'QP 561'),
(28, 20, 1743, 117, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-10 10:50:25', '2025-08-10 17:44:39', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-11', '13:15', 'Saudi AIrlines', NULL),
(29, 1508, 2297, 341, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-10 10:53:37', '2025-08-10 10:54:39', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-08', '00:55', 'Flynas', 'XY347'),
(30, 1490, 2283, 324, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-10 11:06:50', '2025-08-18 17:37:29', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-18', '11:06', NULL, NULL),
(31, 1490, 2283, 324, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-10 11:12:47', '2025-08-18 17:37:41', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-18', '11:06', NULL, NULL),
(32, 1491, 2284, 325, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-10 17:44:18', '2025-08-11 15:16:21', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-09', '13:00', 'Flynas', NULL),
(33, 1503, 2292, 337, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-11 14:56:57', '2025-08-11 15:16:10', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-09', '17:00', 'Flynas', NULL),
(34, 1506, 2294, 339, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-11 15:14:36', '2025-08-18 23:59:43', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-18', '17:00', NULL, NULL),
(35, 1485, 2279, 317, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-11 15:22:26', '2025-08-11 20:22:34', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-11', '21:15', NULL, NULL),
(36, 1134, 1527, 61, 'international', 'airport', NULL, 'completed', NULL, '2025-08-11 17:18:27', '2025-08-11 17:18:52', NULL, NULL, NULL, NULL, 'RUH', '2025-08-09', '16:15', 'Ethiopian airlines', 'ET412'),
(37, 1156, 1935, 65, 'international', 'airport', NULL, 'completed', NULL, '2025-08-11 17:20:49', '2025-08-11 17:21:18', NULL, NULL, NULL, NULL, 'RUH', '2025-08-06', '13:22', 'Flynas', 'XY347'),
(38, 1274, 2100, 138, 'international', 'airport', NULL, 'completed', NULL, '2025-08-11 17:38:15', '2025-08-11 18:25:24', NULL, NULL, NULL, NULL, 'RUH', '2025-08-11', '15:15', 'OMAR AIR', 'WY-683'),
(39, 1182, 2156, 78, 'international', 'airport', NULL, 'completed', NULL, '2025-08-12 10:26:59', '2025-08-13 18:13:08', NULL, NULL, NULL, NULL, NULL, '2025-08-03', '02:12', 'AKASA AIR', 'QP 561'),
(40, 1259, 2093, 127, 'international', 'airport', NULL, 'completed', NULL, '2025-08-13 18:16:25', '2025-08-16 16:51:35', NULL, NULL, NULL, NULL, 'RUH', '2025-08-11', '14:11', 'OMAR AIR', 'WY-683'),
(41, 0, 0, 409, '', '', NULL, 'active', '2025-08-13 18:53:10', '2025-08-13 18:51:28', '2025-08-13 18:53:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 218, 2312, 409, 'international', 'airport', NULL, 'completed', NULL, '2025-08-13 18:53:59', '2025-08-13 18:56:37', NULL, NULL, NULL, NULL, 'RUH', '2025-08-13', '13:15', 'Saudi AIrlines', 'SV680'),
(43, 0, 0, 217, '', '', NULL, 'active', '2025-08-14 11:35:29', '2025-08-14 11:32:55', '2025-08-14 11:35:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 0, 0, 110, '', '', NULL, 'active', '2025-08-14 11:35:21', '2025-08-14 11:34:11', '2025-08-14 11:35:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 1442, 2260, 288, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:46:10', '2025-08-18 17:02:23', NULL, NULL, NULL, NULL, 'RUH', '2025-08-18', '07:35', 'SAUDI AIRLINE', 'SV-741'),
(46, 1403, 2236, 257, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:47:10', '2025-08-18 17:02:38', NULL, NULL, NULL, NULL, 'RUH', '2025-08-18', '07:35', 'SAUDI AIRLINE', 'SV-741'),
(47, 1296, 2112, 168, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:49:12', '2025-08-16 16:50:40', NULL, NULL, NULL, NULL, 'RUH', '2025-08-12', '15:15', 'OMAR AIR', 'QP 561'),
(48, 1229, 2048, 110, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:51:30', '2025-08-16 16:50:28', NULL, NULL, NULL, NULL, 'RUH', '2025-08-10', '15:01', 'OMAR AIR', 'QP 561'),
(49, 1271, 2099, 137, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:53:15', '2025-08-16 16:50:11', NULL, NULL, NULL, NULL, NULL, '2025-08-12', '07:35', 'SAUDI AIRLINE', 'SV-741'),
(50, 1276, 2102, 140, 'international', 'airport', NULL, 'completed', NULL, '2025-08-14 19:55:29', '2025-08-16 16:50:00', NULL, NULL, NULL, NULL, NULL, '2025-08-09', '02:30', 'AKASA AIR', 'QP 561'),
(51, 1155, 1454, 64, 'international', 'airport', NULL, 'pending', NULL, '2025-08-16 17:35:47', '2025-08-16 17:35:47', NULL, NULL, NULL, NULL, 'RUH', '2025-08-25', '15:50', 'Saudi AIrlines', 'SV1034'),
(52, 1176, 2074, 74, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:23:03', '2025-08-17 17:23:03', NULL, NULL, NULL, NULL, 'RUH', '2025-08-19', '11:45', 'Bader Airlines', 'J4670'),
(53, 1185, 1649, 80, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:24:19', '2025-08-17 17:24:19', NULL, NULL, NULL, NULL, 'RUH', '2025-08-21', '11:45', 'Bader Airlines', 'J4670'),
(54, 1204, 1653, 91, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:26:20', '2025-08-17 17:26:20', NULL, NULL, NULL, NULL, 'RUH', '2025-08-19', '11:45', 'Bader Airlines', 'J4670'),
(55, 1215, 1830, 98, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:27:28', '2025-08-17 17:27:28', NULL, NULL, NULL, NULL, 'RUH', '2025-08-19', '11:45', 'Bader Airlines', 'J4670'),
(56, 1216, 2038, 99, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:28:05', '2025-08-17 17:28:05', NULL, NULL, NULL, NULL, 'RUH', '2025-08-19', '11:45', 'Bader Airlines', 'J4670'),
(57, 1258, 2063, 126, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:32:15', '2025-08-17 17:32:15', NULL, NULL, NULL, NULL, 'RUH', '2025-08-19', '11:45', 'Bader Airlines', 'J4670'),
(58, 1178, 2154, 75, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 17:38:14', '2025-08-17 17:38:14', NULL, NULL, NULL, NULL, 'JED', '2025-08-21', '11:15', 'OMAR AIR', 'WY-266'),
(59, 1214, 2037, 97, 'international', 'airport', NULL, 'completed', NULL, '2025-08-17 17:41:25', '2025-08-18 19:28:03', NULL, NULL, '2025-08-18 19:28:03', NULL, 'RUH', '2025-08-09', '02:10', 'الخطوط الاثيوبية', 'ET412'),
(60, 1189, 1775, 82, 'international', 'airport', NULL, 'pending', NULL, '2025-08-17 18:27:35', '2025-08-17 18:27:35', NULL, NULL, NULL, NULL, 'RUH', '2025-09-04', '21:50', 'Saudi AIrlines', 'SV1046'),
(61, 1355, 2404, 445, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-18 17:37:11', '2025-08-18 17:38:04', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-15', '05:37', 'Flynas', NULL),
(62, 1307, 2120, 172, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 18:14:59', '2025-08-18 18:14:59', NULL, NULL, NULL, NULL, 'RUH', '2025-08-27', '02:10', 'الخطوط الاثيوبية', 'ET412'),
(63, 1345, 2189, 205, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 18:16:59', '2025-08-18 18:16:59', NULL, NULL, NULL, NULL, 'RUH', '2025-09-13', '02:10', 'الخطوط الاثيوبية', 'ET412'),
(64, 1356, 2198, 214, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:18:27', '2025-08-18 19:18:27', NULL, NULL, NULL, NULL, 'RUH', '2028-09-14', '02:10', 'الخطوط الاثيوبية', 'ET412'),
(65, 1285, 1901, 149, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:19:53', '2025-08-18 19:19:53', NULL, NULL, NULL, NULL, 'RUH', '2025-09-14', '02:10', 'الخطوط الاثيوبية', 'ET412'),
(66, 1434, 2254, 282, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:21:36', '2025-08-18 19:22:10', NULL, NULL, NULL, NULL, 'RUH', '2025-09-16', '10:15', 'الخطوط القطرية', 'QR1428'),
(67, 1157, 1847, 66, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:23:55', '2025-08-18 19:23:55', NULL, NULL, NULL, NULL, 'RUH', '2025-09-18', '10:15', 'الخطوط القطرية', 'QR1428'),
(68, 1379, 2228, 236, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:25:23', '2025-08-18 19:25:23', NULL, NULL, NULL, NULL, 'RUH', '2025-09-18', '10:15', 'الخطوط القطرية', 'QR1428'),
(69, 1430, 2251, 278, 'international', 'airport', NULL, 'pending', NULL, '2025-08-18 19:26:44', '2025-08-18 19:26:44', NULL, NULL, NULL, NULL, 'RUH', '2025-09-18', '10:15', 'الخطوط القطرية', 'QR1428'),
(70, 1652, 2410, 460, 'deportation', 'مطار', NULL, 'completed', NULL, '2025-08-18 23:59:19', '2025-08-19 00:00:19', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-12', '17:00', 'Flynas', NULL),
(71, 1400, 2235, 254, 'deportation', 'مطار', NULL, 'scheduled', NULL, '2025-08-19 00:26:24', '2025-08-19 00:26:24', NULL, NULL, NULL, NULL, 'مطار الملك خالد الدولي - الرياض', '2025-08-20', '22:10', 'Flynas', NULL),
(72, 1311, 2123, 176, 'international', 'airport', NULL, 'completed', NULL, '2025-08-19 18:48:21', '2025-08-20 13:21:37', NULL, NULL, '2025-08-20 13:21:37', 1, 'RUH', '2025-08-28', '16:25', 'AIR INDIA', 'AI-925'),
(73, 0, 0, 327, '', '', NULL, 'active', '2025-08-19 18:52:05', '2025-08-19 18:49:48', '2025-08-19 18:52:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(74, 0, 0, 217, '', '', NULL, 'active', '2025-08-19 20:29:03', '2025-08-19 18:50:45', '2025-08-19 20:29:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 0, 0, 469, '', '', NULL, 'active', NULL, '2025-08-23 15:57:58', '2025-08-23 15:57:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, 1336, 2161, 197, 'international', 'airport', NULL, 'pending', NULL, '2025-08-28 06:14:42', '2025-08-28 06:14:42', NULL, NULL, NULL, NULL, 'RUH', '2025-08-28', '09:14', 'Saudi AIrlines', '32239'),
(77, 0, 0, 418, '', '', NULL, 'active', NULL, '2025-08-31 14:40:08', '2025-08-31 14:40:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(78, 0, 0, 327, '', '', NULL, 'active', NULL, '2025-08-31 14:40:34', '2025-08-31 14:40:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(79, 0, 0, 221, '', '', NULL, 'active', NULL, '2025-08-31 14:40:53', '2025-08-31 14:40:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `travel_history_files`
--

CREATE TABLE `travel_history_files` (
  `id` bigint UNSIGNED NOT NULL,
  `travel_id` bigint UNSIGNED NOT NULL,
  `file` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `travel_history_files`
--

INSERT INTO `travel_history_files` (`id`, `travel_id`, `file`, `created_at`, `updated_at`) VALUES
(1, 7, 'file-17228119250.png', '2024-08-04 22:52:05', '2024-08-04 22:52:05'),
(2, 8, 'file-17228122960.png', '2024-08-04 22:58:16', '2024-08-04 22:58:16'),
(4, 17, 'file-17228140480.png', '2024-08-04 23:27:28', '2024-08-04 23:27:28'),
(5, 18, 'file-17228340650.png', '2024-08-05 05:01:05', '2024-08-05 05:01:05'),
(6, 1, '‏لقطة الشاشة 2024-09-17 في 5.44.31 م-17265941650.png', '2024-09-17 20:29:25', '2024-09-17 20:29:25'),
(7, 3, 'WhatsApp Image 2025-01-07 at 7.21.08 PM-17364381330.jpg', '2025-01-09 18:55:33', '2025-01-09 18:55:33'),
(8, 7, '_0000_67ec2cc058d93-17439606020.png', '2025-04-06 20:30:02', '2025-04-06 20:30:02'),
(9, 8, '_0000_67ec2cc058d93-17439606800.png', '2025-04-06 20:31:20', '2025-04-06 20:31:20'),
(10, 27, 'WhatsApp Image 2025-07-28 at 18.29.54-17537169530.jpg', '2025-07-28 18:35:53', '2025-07-28 18:35:53'),
(11, 27, 'WhatsApp Image 2025-07-28 at 18.29.54-17537169540.jpg', '2025-07-28 18:35:54', '2025-07-28 18:35:54'),
(12, 38, '1000211013-17549230950.pdf', '2025-08-11 17:38:15', '2025-08-11 17:38:15'),
(13, 39, 'WhatsApp Image 2024-02-25 at 11.09.58-17549836190.jpg', '2025-08-12 10:26:59', '2025-08-12 10:26:59'),
(14, 40, 'WhatsApp Image 2024-02-25 at 11.09.58-17550981850.jpg', '2025-08-13 18:16:25', '2025-08-13 18:16:25'),
(15, 45, 'WhatsApp Image 2025-08-13 at 13.45.28-17551899700.jpg', '2025-08-14 19:46:10', '2025-08-14 19:46:10'),
(16, 46, 'WhatsApp Image 2025-08-13 at 13.45.28-17551900300.jpg', '2025-08-14 19:47:10', '2025-08-14 19:47:10'),
(17, 47, 'WhatsApp Image 2024-02-25 at 11.09.58-17551901520.jpg', '2025-08-14 19:49:12', '2025-08-14 19:49:12'),
(18, 47, 'WhatsApp Image 2024-02-25 at 11.09.58-17551901520.jpg', '2025-08-14 19:49:12', '2025-08-14 19:49:12'),
(19, 48, 'WhatsApp Image 2024-02-25 at 11.09.58-17551902900.jpg', '2025-08-14 19:51:30', '2025-08-14 19:51:30'),
(20, 49, 'WhatsApp Image 2024-02-25 at 11.09.58-17551903950.jpg', '2025-08-14 19:53:15', '2025-08-14 19:53:15'),
(21, 50, 'WhatsApp Image 2024-02-25 at 11.09.58-17551905290.jpg', '2025-08-14 19:55:29', '2025-08-14 19:55:29'),
(22, 58, 'MOHAMMAD AZAD-17554414940.pdf', '2025-08-17 17:38:14', '2025-08-17 17:38:14'),
(23, 59, 'SEBAH ABDEY TKTT (1)-17554416850.pdf', '2025-08-17 17:41:25', '2025-08-17 17:41:25'),
(24, 62, '27-08-2025 توفا-17555300990.pdf', '2025-08-18 18:14:59', '2025-08-18 18:14:59'),
(25, 63, '13-09-2025 توفا-17555302190.pdf', '2025-08-18 18:16:59', '2025-08-18 18:16:59'),
(26, 72, 'WhatsApp Image 2025-08-19 at 11.00.28-17556185010.jpg', '2025-08-19 18:48:21', '2025-08-19 18:48:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `is_blocked` tinyint NOT NULL DEFAULT '0' COMMENT '0 is not blocked',
  `status` tinyint NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nationality_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `national_num` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rooms_num` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `family_num` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `children` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profession` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'المهنة',
  `monthly_income` decimal(10,2) DEFAULT NULL COMMENT 'الدخل الشهري',
  `beds_num` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `phone_code`, `phone`, `password`, `register_from`, `is_blocked`, `status`, `remember_token`, `created_at`, `updated_at`, `nationality_id`, `email`, `birthdate`, `national_num`, `phone2`, `address`, `city`, `rooms_num`, `family_num`, `children`, `job`, `profession`, `monthly_income`, `beds_num`) VALUES
(613, 'Mutasim2468', NULL, NULL, '+966', '552468101', '$2y$10$x5KPiX3SDKG46CUXOTaIeuCbAZFtYDdP1qc7Ynza9W89sLJNNVpFu', 'web', 0, 1, NULL, '2024-09-13 22:12:25', '2024-09-13 22:12:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(614, 'ماجد حمدان حسين القشمري', NULL, NULL, '+966', '508606406', '$2y$10$bJ2JOBhL8nFtBQeaHEgr1.1QWymnEO4inwXL7dGpPJ3EXovOkQh6W', 'web', 0, 1, NULL, '2024-09-14 00:10:47', '2024-09-14 00:10:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(615, 'مشعل محسن عوض الظفيري', NULL, NULL, '+966', '554618754', '$2y$10$5O1z4hbYS5mkU2U1AGUN3OxoymYTtbSmMwibCYAEGREKETpExEYEK', 'web', 0, 1, NULL, '2024-09-14 17:15:17', '2024-09-14 17:15:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(616, 'Shatha yasser', NULL, NULL, '+966', '556839840', '$2y$10$2./em82hfw1FtaT.JSN3veeE1ZxAJ6p1aVQtt/BmK7GtZlURJQ8NW', 'web', 0, 1, NULL, '2024-09-14 19:26:46', '2024-09-14 19:26:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(617, 'Tahany32', NULL, NULL, '+966', '508810434', '$2y$10$rs80lsKS7x4QXcpXKcqN5.ChSd4LQjRm26B3gUsos8iLDcpQwEY/u', 'web', 0, 1, NULL, '2024-09-15 15:20:12', '2024-09-15 15:20:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(618, 'رزان الزهراني', NULL, NULL, '+966', '557249268', '$2y$10$SAYRykrHLSdLXb5fjEpViePEqRxMBkeBjsKUztQNXIdQd7VAE7Bjq', 'web', 0, 1, NULL, '2024-09-15 18:13:10', '2024-09-15 18:13:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(619, 'زايد سعد العنزي', NULL, NULL, '+966', '546818006', '$2y$10$gEzYJ46uovz7IRncnWFYC.d0TSGq/4x28M31qAPoC.5ZKRg7Vt6AO', 'web', 0, 1, NULL, '2024-09-16 04:01:00', '2024-09-16 04:01:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(620, 'معتز محمد مصطفي محمد', NULL, NULL, '+966', '509429977', '$2y$10$EmcWKuxJEoOGaNiT7AUsyOLMD2WLssUGRwgoFUSLapmUE9fjUiY0i', 'web', 0, 1, NULL, '2024-09-16 09:02:08', '2024-09-16 09:02:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(621, 'نورة ناصر العبدالله', NULL, NULL, '+966', '501177133', '$2y$10$YB1yG6xj6.2g9XbCnpQLrODVA0ax0LQzcLyYYbTULfintuw/AJX4S', 'web', 0, 1, NULL, '2024-09-16 13:03:11', '2024-09-16 13:03:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(622, 'بشاير محمد مطلق المطيري', NULL, NULL, '+966', '500954950', '$2y$10$RlF9.sqxapfX/Z7fvFt6YOUSfksqm3k6iDJ6zzJBIAIJiuO44P66.', 'web', 0, 1, NULL, '2024-09-19 17:52:54', '2024-09-19 17:52:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(623, 'شيخه مدهش تركي النتيفات', NULL, NULL, '+966', '569111745', '$2y$10$d1XxnUz//7KMbZ/hw3cCae7BMXa3Oks2iENjCerbFwk03Z6MolX4q', 'web', 0, 1, NULL, '2024-09-20 13:37:25', '2024-09-20 13:37:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(624, 'Shaimaa', NULL, NULL, '+966', '558955377', '$2y$10$MAG0WmFUl6I6dFeUB1Frr.61ZutEzFrMd2pw0BMJ40MsrMIh59U5G', 'web', 0, 1, NULL, '2024-09-20 23:50:24', '2024-09-20 23:50:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(625, 'محمد هلال عبدالله سعد للقرماني', NULL, NULL, '+966', '553180863', '$2y$10$N3HDJLjZfA7.s7kqx32VPOAK8dR01yAMFYQvPA0KuvrCSRG9VihIy', 'web', 0, 1, NULL, '2024-09-22 07:21:38', '2024-09-22 07:21:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(626, 'أماني يحيى أحمد', NULL, NULL, '+966', '551723327', '$2y$10$inGxAmyjRI0.tpNJTdcwuOhvqRc3tN5aMpkoPBMFUS1RMiI.Yr.bK', 'web', 0, 1, NULL, '2024-09-22 16:44:34', '2024-09-22 16:44:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(627, 'سلطانه الطويل', NULL, NULL, '+966', '560537359', '$2y$10$mGgcUuSoA5K/boAwZpO66edrEGNzFQsdMUduGfuUIHWaek167iU1y', 'web', 0, 1, NULL, '2024-09-24 13:38:29', '2024-09-24 13:38:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(628, 'Hadeel saad', NULL, NULL, '+966', '501239249', '$2y$10$iNUfMaPHAfbDkHs1wEpyOuvRwhcqcapuOzjrz.g/41phhPJ4NH0KK', 'web', 0, 1, NULL, '2024-09-25 16:36:09', '2024-09-25 16:36:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(629, 'العنود', NULL, NULL, '+966', '559203301', '$2y$10$v9Cm5H3sNUB4xic8XPgXiOO5zaUAzH7fVAhJunz4jof.xC5xSK/7S', 'web', 0, 1, NULL, '2024-09-26 10:10:29', '2024-09-26 10:10:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(630, 'محمدعوض', NULL, NULL, '+966', '596999953', '$2y$10$A0B/mu/XT1Frr089PmP38eOnsHNYoUBu.0QkInamE8HM4cFvorrPq', 'web', 0, 1, NULL, '2024-09-27 11:13:16', '2024-09-27 11:13:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(631, 'أحمد بره', NULL, NULL, '+966', '542089267', '$2y$10$3oMJUJzZuAzVzJQGstsvm.ZFoxjmt4fAL4qlcezOXJthJPEWxZRRq', 'web', 0, 1, NULL, '2024-09-27 15:51:42', '2024-09-27 15:51:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(632, 'دحمان عمردحمان عبدالله', NULL, NULL, '+966', '564288088', '$2y$10$LvhqyIQrsCk23l8yep9fquJErW0Tg6tQdHJpZKOrF9AXFgbLkY0Ku', 'web', 0, 1, NULL, '2024-10-17 03:46:51', '2024-10-17 03:46:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(633, '', NULL, NULL, '+966', '507777671', '$2y$10$vf8nWsazL2X57KIFjlrlLOLMdtOyyvTyTQvW09bSUg1rta7jPs7Pm', 'web', 0, 1, NULL, '2024-10-17 07:11:19', '2024-10-17 07:11:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(634, 'Hasan Ahmaid Alzhrani', NULL, NULL, '+966', '504714748', '$2y$10$43atKXjzaapl/tOnGONWFOzw32NgZQkCyfPbD/t6yqKHIvscqAFi6', 'web', 0, 1, NULL, '2024-10-17 23:56:43', '2024-10-17 23:56:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(639, '1077286407', NULL, NULL, '+966', '504694056', '$2y$10$RW4qgOw6JRGo6kXkyTbRhuUgauUJv7zo/xSfDrr8aclS2h5tcul0.', 'web', 0, 1, NULL, '2024-10-18 11:07:32', '2024-10-18 11:07:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(640, 'لين الدوسري', NULL, NULL, '+966', '503465765', '$2y$10$VwvU1Ah9Nc8Gt1K/rYPBU.LxywRBwQoTbamN2aKsf7irCu79gOozC', 'web', 0, 1, NULL, '2024-10-18 15:43:36', '2024-10-18 15:43:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(641, '+966509061867', NULL, NULL, '+966', '509061867', '$2y$10$ViKzNZPqbZvrDxKp6zNx6OeEN.LshgSKfcUR9fgxpA6eSKvi2Dc3W', 'web', 0, 1, NULL, '2024-10-18 21:37:48', '2024-10-18 21:37:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(642, 'وديان منيف الدوسري', NULL, NULL, '+966', '505857446', '$2y$10$1R4RWhavhX9TXlG33llGz.eRkiAHqLqpF0hhlHGpkgedzY9M16zWC', 'web', 0, 1, NULL, '2024-10-20 10:45:16', '2024-10-20 10:45:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(643, 'فهد خليل الحربي', NULL, NULL, '+966', '541481488', '', 'web', 0, 1, NULL, '2024-10-21 20:39:59', '2024-10-21 23:31:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(644, 'Mohammed Alharbi', NULL, NULL, '+966', '505889789', '$2y$10$Nsn49GwR0U1c05kE..FHVOhWqsSZdTr0aLckiKphoEByMkvwL18a.', 'web', 0, 1, NULL, '2024-10-22 18:04:23', '2024-10-22 18:04:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(645, 'Rawabi mayal', NULL, NULL, '+966', '560653118', '$2y$10$j6Bsswifss85PiT9VlKeYOpTw8G54mq.I9JO4I8tStUrNp5Uw/FMW', 'web', 0, 1, NULL, '2024-10-24 00:29:01', '2024-10-24 00:29:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(647, 'حمد سعد الرشيدي', NULL, NULL, '+966', '538974727', '$2y$10$c3KOQ.flazvAaTmSqXJm5eeI5Z9eQlx9bWpxN0j/PozLU3Z6rJhma', 'web', 0, 1, NULL, '2024-10-27 13:11:37', '2024-10-27 13:11:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(653, 'نوف ابراهيم القلعي', NULL, NULL, '+966', '506260647', '$2y$10$zqAu9Zch/8Zk7PfLqk5MAO24C.9XFfxVAAgzsDRDlJP5cb42wrooW', 'web', 0, 1, NULL, '2024-10-31 11:19:21', '2024-10-31 11:19:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(659, 'ريم سعد', NULL, NULL, '+966', '550614750', '$2y$10$Grq3yA8ZCkyBA8zha3ANwu0VRAcBvOrg6jck48Q.iUCwZp2cZMg4.', 'web', 0, 1, NULL, '2024-11-01 23:24:19', '2024-11-01 23:24:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(660, 'اسراء العنزي', NULL, NULL, '+966', '542929665', '$2y$10$j9XUQhlKg/u2EYwbTPWg4OvXv.IRHeQ3zEuWSsexmYd6F6UsxavTK', 'web', 0, 1, NULL, '2024-11-03 17:16:13', '2024-11-03 17:16:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(661, 'رفيعه البيشي', NULL, NULL, '+966', '537568721', '$2y$10$lblnlEJGErd/PnFFsw3/G.NkY.4A/OcqwujXG0G0ciScnAZK1xG3S', 'web', 0, 1, NULL, '2024-11-05 14:22:12', '2024-11-05 14:22:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(662, 'احمد عبدالله', NULL, NULL, '+966', '556262808', '$2y$10$A0cnfzcMYASCgJAKcHY6ZOw9Nq.7A2CoXrbom.ug81PnWo3XvES/m', 'web', 0, 1, NULL, '2024-11-11 17:17:40', '2024-11-11 17:17:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(663, 'وجدان سعيد الزهراني', NULL, NULL, '+966', '508750995', '$2y$10$HJw8uPj9YDnBNN3nZZSh1uFgxCGqU84YzcRj.he0POTmJLVEYA8Y6', 'web', 0, 1, NULL, '2024-11-14 14:35:56', '2024-11-14 14:35:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(664, 'ايمان', NULL, NULL, '+966', '557559031', '$2y$10$YG/l2enXt7FqPAggaeuhT.ZzdR/vgPkTnNYit5M6tyZlwO9PFZ6oW', 'web', 0, 1, NULL, '2024-11-14 20:39:23', '2024-11-14 20:39:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(665, 'حسن احمد السيد حسن عطا', NULL, NULL, '+966', '591641466', '$2y$10$9H9gpOP9OLP.ZUf4w5pNKuyISiQLI8l19qH5nsKeDe7IIXf2Qax6G', 'web', 0, 1, NULL, '2024-11-19 16:22:45', '2024-11-19 16:22:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(666, 'فاطمة شوعي بجوي', NULL, NULL, '+966', '559625194', '$2y$10$LXYIXRP2CaZepQjWhf5Rb.ZSJk8dnDjliWvXGlBVHY.nl/XZroqC6', 'web', 0, 1, NULL, '2024-11-25 19:44:05', '2024-11-25 19:44:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(667, 'Ihab Gaballa', NULL, NULL, '+966', '542828893', '$2y$10$/gUxA7rt8yMcrwHn9/jdFuCL1wF1QBTktD4zNzFACN81048r12mQW', 'web', 0, 1, NULL, '2024-11-30 12:50:36', '2024-11-30 12:50:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(668, 'فايزة عبدالله', NULL, NULL, '+966', '504716301', '$2y$10$4dTueRjglZ5YhzOIfOu3TOjTx/NUhsXC/URIwi4wyd7i6VwRgYs8q', 'web', 0, 1, NULL, '2024-12-02 16:09:19', '2024-12-02 16:09:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(669, 'عبدالله المالكي', NULL, NULL, '+966', '503419559', '$2y$10$EyzK0mvljIAN4BYXCs52We1kCvreV/LRFtTk9CY.IOq8qmek7mcPm', 'web', 0, 1, NULL, '2024-12-02 16:23:16', '2024-12-02 16:23:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(670, '', NULL, NULL, '+966', '555379969', '$2y$10$jPmDGRVxgMwJJeNVUkRq0ezEbFgZfchSCO3whRQPi2pWWsjBRKfLi', 'web', 0, 1, NULL, '2024-12-16 23:56:44', '2024-12-16 23:56:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(671, 'لطيفة', NULL, NULL, '+966', '533937871', '$2y$10$3jaDLIJ87igDd765Y0rBnOoEzPYKMMIPh22/RQCEvsNvEZK98WI5y', 'web', 0, 1, NULL, '2024-12-17 23:20:50', '2024-12-17 23:20:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(672, 'صالح عبدالله ملفي الحربي', NULL, NULL, '+966', '560025551', '$2y$10$5UusldNvus56dGglwPQxcOWWo8i8gd5JxNISXHPBBw4YKy4QxXWR.', 'web', 0, 1, NULL, '2024-12-19 22:36:20', '2024-12-19 22:36:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(673, 'Ali', NULL, NULL, '+966', '504984634', '$2y$10$M5MX4w4Yn9hkhXIpyeg3zuLeUqGpRP/jD4i3/rFzWoko/Avb61hiO', 'web', 0, 1, NULL, '2024-12-21 23:32:52', '2024-12-21 23:32:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(674, 'علي مطر علي العنزي', NULL, NULL, '+966', '538966337', '$2y$10$GaQnfMUXRJU1.rcy9fOQHeJ8qB/LQfYUVtnhFuHCOwyc0leHcwVWC', 'web', 0, 1, NULL, '2024-12-22 00:55:05', '2024-12-22 00:55:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(676, 'Hashir Qamar Fasihi', NULL, NULL, '+966', '564366925', '$2y$10$LqSP.U/8ucdGKvejatnc.er5olfiLr2sqytlPKtA1VmbywI1cXG46', 'web', 0, 1, NULL, '2024-12-22 14:08:32', '2024-12-22 14:08:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(677, 'Mohammeds59', NULL, NULL, '+966', '502238021', '$2y$10$1L7jp.7mRF3n9LUUQmCSN.ikLJbPKmIUDzmZmvalpY3Fib5QC8ST6', 'web', 0, 1, NULL, '2024-12-22 19:33:18', '2024-12-22 19:33:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(678, 'احلام محمد الشهري', NULL, NULL, '+966', '531120819', '$2y$10$LpxKxSviFQVZe.6sNoLXSejCM1kdQTtaJHxPld5ycBaHvWmWIm8r.', 'web', 0, 1, NULL, '2024-12-24 01:56:13', '2024-12-24 01:56:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(679, 'Moudi', NULL, NULL, '+966', '555683818', '$2y$10$S/R9isLJS1BxnFffIYcKxuS3A87GJEZSFoTzbQL0DpCab3GXaak4C', 'web', 0, 1, NULL, '2024-12-24 08:35:39', '2024-12-24 08:35:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(680, 'احمد محمد عبد المنعم', NULL, NULL, '+966', '575433872', '$2y$10$1S3ghqaeMUSnElySRmqKfOeMqrdvygXD6Kqzjoys5ipXVY8bht3fC', 'web', 0, 1, NULL, '2024-12-26 20:02:37', '2024-12-26 20:02:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(681, 'Mazen mohammad', NULL, NULL, '+966', '502659579', '$2y$10$HOYI.6T9gjjJFZGnJgcfx.iSgrcqfmcwrmb3NQT7G4JzMami6n36i', 'web', 0, 1, NULL, '2024-12-28 03:03:45', '2024-12-28 03:03:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(682, 'محمد ماجد مانع الدوسرب', NULL, NULL, '+966', '570053252', '$2y$10$50G45TOBtbaTpZho9irJ8.5kN7DcAlMvhQuVdBTVS5fDO5/p0AMJm', 'web', 0, 1, NULL, '2024-12-29 02:17:41', '2024-12-29 02:17:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(683, 'TOFEEG', NULL, NULL, '+966', '505383469', '$2y$10$.s6DM4WtHU0/ehsR3WsVbe/JqqiE9aaiocOSswgFHh0nWu6cZjuC6', 'web', 0, 1, NULL, '2024-12-31 12:04:14', '2024-12-31 12:04:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(684, 'maha', NULL, NULL, '+966', '554108070', '$2y$10$p0d9dlSVEtfvNlktsgxBQeRyYfFROxBAazKYPiUZmphh4ZAPB8oYu', 'web', 0, 1, NULL, '2024-12-31 13:41:47', '2024-12-31 13:41:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(685, 'فاطمه العتيبي', NULL, NULL, '+966', '504612077', '', 'web', 0, 1, NULL, '2025-01-04 21:45:31', '2025-01-04 21:47:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(689, 'عميل تجربة', NULL, NULL, '+966', '512345678', '', 'web', 0, 1, NULL, '2025-01-06 00:46:09', '2025-01-06 00:46:09', NULL, 'sa@site.app', NULL, '1', '1', NULL, '3', '4', '5', '2', 'نجار', NULL, NULL, NULL),
(709, 'سامية حمود الثبيتي', NULL, NULL, '+966', '503320940', '$2y$10$7kSHLtKwUdegKyDDI/E/o.chlbEGDJQ01DOvBz.Zap3SVrV2PsaPG', 'web', 0, 1, NULL, '2025-01-10 14:39:51', '2025-01-10 14:39:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(710, 'حسن منصور العبدالله', NULL, NULL, '+966', '545781155', '$2y$10$l.MRJw52I.56F8PUo1w75uMsXj7T3J.zopxBYXEBlUtX9DBnyE/.m', 'web', 0, 1, NULL, '2025-01-10 15:59:00', '2025-01-10 15:59:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(711, 'عععcom', NULL, NULL, '+966', '540631915', '$2y$10$BlEyZRO9kUMToBlkVXtBUe2FO9RTp5lhaqqkj5ji1nXr7cHYjdYSa', 'web', 0, 1, NULL, '2025-01-10 21:50:25', '2025-01-10 21:50:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(713, 'Omar', NULL, NULL, '+966', '544524452', '$2y$10$g93YnmX1JRoIEXlMjgI0nOOUMHbt4z7YO9iNBR/KRZTktySxiXHKS', 'web', 0, 1, NULL, '2025-01-11 18:58:01', '2025-01-11 18:58:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(714, 'بدور عمران', NULL, NULL, '+966', '552381856', '$2y$10$VV3Bj4PPHcxAJLKyB5dD9OPOpYib9mSZric2jft3S6HKG8cVjKE9K', 'web', 0, 1, NULL, '2025-01-11 21:54:27', '2025-01-11 21:54:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(719, 'سالم فرحان', NULL, NULL, '+966', '539857280', '$2y$10$5B2AIeUinat.UUvUFft/rOnkymysEgkmjdV.PMZ0NFlGWG.WQqhHu', 'web', 0, 1, NULL, '2025-01-12 10:49:52', '2025-01-12 10:49:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(721, 'احمد خليفه الاحمد', NULL, NULL, '+966', '546111539', '$2y$10$wn1oC.B88vVtDVLRKnPs/.3g8SNoPkb.MztI9jI6EhSUeAt25zSrq', 'web', 0, 1, NULL, '2025-01-13 23:28:24', '2025-01-13 23:28:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(722, 'عادل مطر', NULL, NULL, '+966', '555974186', '$2y$10$Rkz5EUhjzHXUPPTTA2OuIucLM4TorAbsWwZbFmM6iQMtrJWG64bI6', 'web', 0, 1, NULL, '2025-01-14 09:57:49', '2025-01-14 09:57:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(723, 'نوال احمد', NULL, NULL, '+966', '554574575', '$2y$10$wLjA.fXop9XKUvajD6TaKOy2Yi9Bxf6d/cm2gxsLf.EUIYDw4ry92', 'web', 0, 1, NULL, '2025-01-14 14:16:03', '2025-01-14 14:16:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(724, 'علي سراج', NULL, NULL, '+966', '503001526', '$2y$10$dx6EzwzZ1wRo4jFq0nOHWOhIe0/uha5uw19r9yZ3y0wtSxTzLrRci', 'web', 0, 1, NULL, '2025-01-16 21:08:24', '2025-01-16 21:08:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(725, 'aljwhara alsharief', NULL, NULL, '+966', '558857731', '$2y$10$25VfehFxXIgcMLK8aurAc.WLhB2pk/whTEW89scWcJlZvo9UavARO', 'web', 0, 1, NULL, '2025-01-17 10:19:24', '2025-01-17 10:19:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(726, 'فاطمه', NULL, NULL, '+966', '538735981', '$2y$10$rgS91tWD10EJG5qPILX9W.qh/UQMKuNNVnltsJUTNC9m6wToe6kQ2', 'web', 0, 1, NULL, '2025-01-17 12:25:42', '2025-01-17 12:25:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(727, 'Mohammed', NULL, NULL, '+966', '509465600', '$2y$10$DwtCrqypUpyC51wz6G196O7TTV2R8M0G5NL3zsmDPKNQGI43Nvu46', 'web', 0, 1, NULL, '2025-01-18 01:14:18', '2025-01-18 01:14:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(728, 'وائل حلمي محمود الدلاش', NULL, NULL, '+966', '542952048', '$2y$10$pBkEjnTmtBNN77CUjhhM1.IFIagfjf/hkK0B8D9bQIbNcLNeSknty', 'web', 0, 1, NULL, '2025-01-19 02:58:25', '2025-01-19 02:58:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(729, 'qqq', NULL, NULL, '+966', '566100236', '$2y$10$66WOuupaG/FW5WwYuQF2XO5lPr5N7LIrfqnK7Tqh.I.LJbmjvruy6', 'web', 0, 1, NULL, '2025-01-19 18:15:23', '2025-01-19 18:15:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(730, '', NULL, NULL, '+966', '505110675', '$2y$10$Khk30EmgnT7foPj6frKdp.dR0B2KfltN7rrvYVq88oFsy3MDWEPeW', 'web', 0, 1, NULL, '2025-01-23 06:41:15', '2025-01-23 06:41:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(731, 'Amjad', NULL, NULL, '+966', '554749853', '$2y$10$Ba9jYvjou0zcH.9H7b8sjub4pPY6ZPVVCMIn/fygJLStpKBXMwlC.', 'web', 0, 1, NULL, '2025-01-24 12:26:59', '2025-01-24 12:26:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(732, 'اميره عبدالله الزهراني', NULL, NULL, '+966', '557613933', '$2y$10$86gLjkvgnEtVHKOkmujb0uufmA8sb1cYH8.MTlmTVfZyOeBhRsbZ2', 'web', 0, 1, NULL, '2025-01-25 01:28:19', '2025-01-25 01:28:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(733, 'احمد مجمل على العروسي', NULL, NULL, '+966', '538274055', '$2y$10$A03y0qSP1AtmtIBn1nqwpuMrObfAvt9ByvGFwErzID6T4oMPVb3Xy', 'web', 0, 1, NULL, '2025-01-25 12:35:28', '2025-01-25 12:35:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(734, 'عبدالله ثروت', NULL, NULL, '+966', '505313932', '$2y$10$IEkIbKf38qV/O9R9d9lKx.XjLJg2DThNXCFoXLJ560aVBtwXDs.wO', 'web', 0, 1, NULL, '2025-01-26 21:16:43', '2025-01-26 21:19:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(735, 'عمر الغامدي', NULL, NULL, '+966', '563441156', '$2y$10$mkK/ukOA97fQ62k6e9C3z.TeDCdK1JiShUQDfe6sb2VPE5DG/gU2O', 'web', 0, 1, NULL, '2025-01-26 23:53:11', '2025-01-26 23:53:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(736, 'عيس يحي محمد الريثي', NULL, NULL, '+966', '559461739', '$2y$10$fDPFteCJmbQrq6yRwjt.JOPee5ggEXpMRO2c0hfGYmCeDQF3gvk6W', 'web', 0, 1, NULL, '2025-01-27 21:12:03', '2025-01-27 21:12:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(737, 'رزان الشهراني', NULL, NULL, '+966', '502650601', '$2y$10$nw5tJfc0WxvWft7ZpMVxte4DMRmIP1d7GPZ7ipqQblo.CpmZpBVfm', 'web', 0, 1, NULL, '2025-01-28 17:08:54', '2025-01-28 17:08:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(738, 'اسامه محمد احمد رزق', NULL, NULL, '+966', '564600583', '$2y$10$KQ/08fH7MMsV79nieXODl.qnAwSVmdntlcgLKxqNrY68Ia6VMvf4G', 'web', 0, 1, NULL, '2025-01-29 15:56:38', '2025-01-29 15:56:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(741, 'عبدالعزيز الشمري', NULL, NULL, '+966', '540045321', '$2y$10$6ig9dnm4jbbNLHTdTOjNtuegIqscg.kwe61EM3dLITvKcrUvwYYCu', 'web', 0, 1, NULL, '2025-01-30 14:02:33', '2025-01-30 14:02:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(742, 'تجربة', NULL, NULL, '+966', '555666777', '$2y$10$7jGaovHnVPz4/iY8wlbMV.tU8xVGYucpsnem1E5zUeR2G7t0adEAO', 'web', 0, 1, NULL, '2025-01-30 14:02:33', '2025-01-30 14:02:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(743, 'محمد', NULL, NULL, '+966', '538880362', '$2y$10$aVKfBJqiDclOPozjgJoyOuiGpVKRwbTQqbXlDSWVykpTNZu2LKWzK', 'web', 0, 1, NULL, '2025-01-31 14:17:46', '2025-01-31 14:17:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(744, 'محمد رمضان ارضا', NULL, NULL, '+966', '543350151', '$2y$10$PlpebCdJDcRfvXE7Ev53UefvxKcpUKOVj.QzkFAKWX8qqLSENBl1u', 'web', 0, 1, NULL, '2025-02-01 22:35:37', '2025-02-01 22:35:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(745, 'Ziyad Alturki', NULL, NULL, '+966', '506811033', '$2y$10$dtC1QS.8WV6MUTlhghRG1elVrUMjnFUfy5PXFTvqDfXIT5M..Y1uu', 'web', 0, 1, NULL, '2025-02-02 15:14:35', '2025-02-02 15:14:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(746, 'حنين', NULL, NULL, '+966', '548859114', '$2y$10$Qo1Fv2cU6fexxhzBYaIf2Okjiv96JVCRVyuiN9Nqj1gQ94GRKKAOW', 'web', 0, 1, NULL, '2025-02-02 20:36:24', '2025-02-02 20:40:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(747, 'الجوهره ناصر', NULL, NULL, '+966', '547199292', '$2y$10$hibWwSdq6tel07i3V6yxuOMPWbTHElJ5Tvxmo7mogQUUpCsIw063e', 'web', 0, 1, NULL, '2025-02-02 22:40:12', '2025-02-02 22:40:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(749, 'البندري العنزي', NULL, NULL, '+966', '582596719', '$2y$10$8HuL8fRFYzrpgVD9p6gDlOj/en3YaEAvwwyQlpsXHY6mo4QR3lApa', 'web', 0, 1, NULL, '2025-02-03 18:14:05', '2025-02-03 18:14:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(750, 'ندى الحربي', NULL, NULL, '+966', '505396085', '$2y$10$ebZX4i9EkubLuhuY720dluLMbCJD165rE2.SgcPtXRz5JLTM0Ic8C', 'web', 0, 1, NULL, '2025-02-03 18:39:55', '2025-02-03 18:39:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(751, 'سعد علي', NULL, NULL, '+966', '555112447', '$2y$10$MMYCT84Z43F.pPt/CWKCl.xVBx1kv/3cP15EEr2ALdjwZR9F25CaK', 'web', 0, 1, NULL, '2025-02-03 19:47:30', '2025-02-03 19:47:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(752, 'محمد عبدالسلام محمد العنزي', NULL, NULL, '+966', '598111602', '$2y$10$njsibVCL2b4VsvEiBKop2.Bwyd1yJwTBgTBA3VeMF5BEqB9nB8JTi', 'web', 0, 1, NULL, '2025-02-03 21:46:55', '2025-02-03 21:46:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(753, 'روان عمر', NULL, NULL, '+966', '550780632', '$2y$10$ift2rKJLWh6Hx98zrx4JpezMy2PWuW5TfRp2GsFe6UX7sesA7s9ja', 'web', 0, 1, NULL, '2025-02-04 23:58:28', '2025-02-04 23:58:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(754, '2566071409', NULL, NULL, '+966', '556644215', '$2y$10$d53F6D7oA2HgwJFfRx/FROFkcHR0mf/J.81Jvlv6E4WZ7FaTPeUrG', 'web', 0, 1, NULL, '2025-02-06 01:50:50', '2025-02-06 01:50:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(755, 'زياد تركي', NULL, NULL, '+966', '595660559', '$2y$10$gQLxnH23noZeWrj/TMVGOOqZzJ/pUXzoXIUusNcqXzKZUVefGa/oW', 'web', 0, 1, NULL, '2025-02-06 14:51:47', '2025-02-06 14:51:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(756, 'يزيد عبدالرحمن حمود العطوي', NULL, NULL, '+966', '590506543', '$2y$10$4L7IiPB5cjvDiEpACfbwseU7tgNS3ECYMioTA1XI7DKGz72Ny3k4u', 'web', 0, 1, NULL, '2025-02-06 21:11:20', '2025-02-06 21:11:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(757, 'حصة علي الراشد', NULL, NULL, '+966', '565171616', '$2y$10$iRU.VBqS/aVZHJyu23trH.McraWjG6Ail0TEXwnc9Z2SxOX2MGgFa', 'web', 0, 1, NULL, '2025-02-07 16:08:49', '2025-02-07 16:08:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(758, 'Fai', NULL, NULL, '+966', '535736469', '$2y$10$rkZEh1MPmrGH4vpL0fwkeeptW3c7ZGif4c0OgEic9NS8aAticuvlW', 'web', 0, 1, NULL, '2025-02-09 20:04:59', '2025-02-09 20:04:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(759, 'بدر الشمري', NULL, NULL, '+966', '555951512', '$2y$10$z7xTft1yGQRXwHOq2dN/lOvpcrDR3jEoPV/OLkFICc2/4V2x4x6.C', 'web', 0, 1, NULL, '2025-02-09 23:32:03', '2025-02-09 23:32:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(760, 'محمد فيصل محمد المطيري', NULL, NULL, '+966', '568507305', '$2y$10$e9fQnU0x2IvhElpOTGMmFuvgimzUo22bZUOmiMl07sbJsrT3/aD/K', 'web', 0, 1, NULL, '2025-02-11 21:20:32', '2025-02-11 21:20:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(761, 'نوف السبيعي', NULL, NULL, '+966', '570503566', '$2y$10$h5p8dGSJuLVbQ0tTtt.u9.lPPpN5Gd5RRlGJ7Hqrr7z7kRXdg8ob6', 'web', 0, 1, NULL, '2025-02-12 01:38:07', '2025-02-12 01:38:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(762, 'نوره الحربي', NULL, NULL, '+966', '557702145', '$2y$10$pihusKtU/F/WIa5Bs6L5kupNgwxXvNxOQDU/lSdMmhRcKGaD1xnMm', 'web', 0, 1, NULL, '2025-02-12 04:26:40', '2025-02-12 04:26:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(763, 'Taif', NULL, NULL, '+966', '557575592', '$2y$10$vZidrb3.o9lQYWkrigCDw..1O/iJKNz6UJTEf5GarZHxv.S8z.h62', 'web', 0, 1, NULL, '2025-02-12 18:41:05', '2025-02-12 18:41:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(764, 'سلطان الشمري', NULL, NULL, '+966', '567424000', '$2y$10$IqRtK7QUmBlh9TDZIAPfOOcoJNliGLOPh.mzlXKlfMO65CPp.pT8.', 'web', 0, 1, NULL, '2025-02-12 21:47:40', '2025-02-12 21:47:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(765, 'شعاع عبيد ثاني الضبيب', NULL, NULL, '+966', '506494983', '$2y$10$m4z4fyvwpcmjsUSxXw1vPuo8zN8whOyj7kypWqCqboDZ8ofD.nDAO', 'web', 0, 1, NULL, '2025-02-13 03:49:48', '2025-02-13 03:49:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(766, 'بيان فهد', NULL, NULL, '+966', '500487818', '$2y$10$X7oJ9a4ak98RHqm7/7sZteItQfREqd1zPgj7wOY8/t3B2DT1PhIf2', 'web', 0, 1, NULL, '2025-02-13 20:39:15', '2025-02-13 20:39:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(767, 'خالد ناشي السعدي', NULL, NULL, '+966', '535623714', '$2y$10$DQeDpnSW3AFCSMNYQihgxOSt7M7GbrZtcMT8QybSEoP.Z/ZwCcOQG', 'web', 0, 1, NULL, '2025-02-14 09:31:58', '2025-02-14 09:31:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(768, 'hadeel', NULL, NULL, '+966', '559079326', '$2y$10$pWTG0vQ3ONt6JT6NvjI8UuVUqMCj8/INzMr.Vq/LdtJMdUzcWPRKK', 'web', 0, 1, NULL, '2025-02-14 19:33:10', '2025-02-14 19:33:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(769, 'Ali Almuqati', NULL, NULL, '+966', '581427037', '$2y$10$m45OIHaPiV4jZcYwg9ehXupXCvCXNdrbjRWcaYHAEP.JzzUDHbWxq', 'web', 0, 1, NULL, '2025-02-14 22:24:11', '2025-02-14 22:24:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(770, 'رشا', NULL, NULL, '+966', '543804468', '$2y$10$v02CSqbk3rDYRwCMBfoXuOaBKthp4C0r5LWf5jJDUX83B2DBs0zbO', 'web', 0, 1, NULL, '2025-02-14 23:54:12', '2025-02-14 23:54:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(771, 'عبدالله الغامدي', NULL, NULL, '+966', '564228778', '$2y$10$lzgHujWddhsBGBArJwTsv.o.cd4zq97j2v40OSU5DdV0smm5Qy0x.', 'web', 0, 1, NULL, '2025-02-15 00:02:44', '2025-02-15 00:02:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(772, 'Amralz', NULL, NULL, '+966', '568683020', '$2y$10$saxGG74R4Kmu5YH.oYWhb.yyrkGtXMRf3Zj9k95DAjreiTTuQbV3C', 'web', 0, 1, NULL, '2025-02-15 23:58:59', '2025-02-15 23:58:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(773, 'ريه ناصر البيشي', NULL, NULL, '+966', '509630127', '$2y$10$9bO8Iqxxn62blQo/O218c.P8JGR6CDjJjEamgi6XqyyLZpyR9Vys6', 'web', 0, 1, NULL, '2025-02-16 01:05:38', '2025-02-16 01:05:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(774, 'ام بتال', NULL, NULL, '+966', '556429146', '$2y$10$KuP7gAgQLpmAqGLBDbNqleHxphlCQrmuaEakcCSFjBIBqM3iP68GG', 'web', 0, 1, NULL, '2025-02-16 06:10:06', '2025-02-16 06:10:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(775, 'sssss', NULL, NULL, '+966', '0555555555', '', 'web', 0, 1, NULL, '2025-02-16 18:47:31', '2025-02-16 18:47:31', NULL, 'ssssss@gmail.com', NULL, '1111111111', '0555555555', NULL, 'ss', '5', '6', '2', '4', NULL, NULL, NULL),
(776, '', NULL, NULL, '+966', '530822632', '$2y$10$XZ10lrRtNkFdN0AAIrzsvefBtUiDrOaQAklfe4qUfAsLyxJOaXMbm', 'web', 0, 1, NULL, '2025-02-17 16:46:10', '2025-02-17 16:46:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(777, 'وفاء نفاع السلمي', NULL, NULL, '+966', '563299513', '$2y$10$RURmWYBd.CtJNIL3.PpIT.HnElkOMbQU0qG2QlZ4fMHxvyYCKqMT2', 'web', 0, 1, NULL, '2025-02-18 00:03:04', '2025-02-18 00:03:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(778, 'محمد عبدالرحمن الغضيه', NULL, NULL, '+966', '533282591', '$2y$10$9HHac0R13hdfobsn6m.IWe8HEcd46fbpRU8vdML0bx9/x6HNmpemC', 'web', 0, 1, NULL, '2025-02-18 12:41:24', '2025-02-18 12:46:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(779, 'محمد عبدالكريم السهيمي', NULL, NULL, '+966', '503558300', '$2y$10$bHYZ6574YgdCg5BYwBLPKuZRUGv.qVRIa.uJr9jvrGNMaa7RIm7yq', 'web', 0, 1, NULL, '2025-02-18 18:39:22', '2025-02-18 18:39:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(780, '', NULL, NULL, '+966', '599477423', '$2y$10$9uMrUTfPLwpVoTfPIdxQGuQn.DdDDLiUUB2MpBX7yQYLv8xrX7eTi', 'web', 0, 1, NULL, '2025-02-18 19:45:50', '2025-02-18 19:45:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(782, 'Wessam Ahmed', NULL, NULL, '+966', '555058472', '$2y$10$OO1jZBqOWjYM9tZ4RxfJq.fRGt/ED7m7km4.hkJ3f5EkINW4.1.Oi', 'web', 0, 1, NULL, '2025-02-18 23:33:04', '2025-02-18 23:33:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(783, 'محمد سليمان العنزي', NULL, NULL, '+966', '568611666', '$2y$10$HbkVqyeuhjuIEj5oxMKVoePKhA.uqUHrNJW19J4q3MqJjklHyrLLe', 'web', 0, 1, NULL, '2025-02-19 00:12:48', '2025-02-19 00:12:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(784, 'متعب عبدالله الرشيدي', NULL, NULL, '+966', '531335961', '$2y$10$L3cP1GwES4E2oN/JUq.Njup7bAfr.Uwh69sX8Hn9iVg1xHJ5.Nx0q', 'web', 0, 1, NULL, '2025-02-19 22:36:56', '2025-02-19 22:36:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(785, 'رهف الحويطي', NULL, NULL, '+966', '538638401', '$2y$10$5Su30Q7d7IVGMTykCUlw1.jK0zHQebF66zyOBECNypBxo8A/6PAg.', 'web', 0, 1, NULL, '2025-02-19 23:22:02', '2025-02-19 23:22:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(786, 'Rami', NULL, NULL, '+966', '544651976', '$2y$10$xnJIhpKFltGdFDlyvG0LvO0E0wrhawHBUtjiH0PHDb8ZQ4vBzKYFS', 'web', 0, 1, NULL, '2025-02-22 17:51:54', '2025-02-22 17:51:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(787, 'شيخه حسن محمد الشريف', NULL, NULL, '+966', '532342448', '$2y$10$x21gPqxxFB05tKK7jlXoAOdoaNxWV9GLyy7SMu/ArL6K50Tl0gkHe', 'web', 0, 1, NULL, '2025-02-22 21:33:32', '2025-02-22 21:33:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(788, 'سعيد علي البلوي', NULL, NULL, '+966', '548327073', '$2y$10$gQf7/m3731S9VqBmzupuUuf28OxJYgkHg9FDxDQuzqnc3CCBR6OwG', 'web', 0, 1, NULL, '2025-02-23 01:25:17', '2025-02-23 01:25:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(789, 'صلاح جميل احمد عبدالله', NULL, NULL, '+966', '559440672', '$2y$10$uZ3kdq1K53VZAGVH0igH7..GVNNqEexjPYWhxNC0tzGdpk8ctjZdm', 'web', 0, 1, NULL, '2025-02-23 03:07:53', '2025-02-23 03:07:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(790, 'زياد متعب خشان العنزي', NULL, NULL, '+966', '544143142', '$2y$10$.6WNnAyZs4s3NRFoVkoz8elXSHhi1gwkeozMu2eUdvHxF2A2nqFei', 'web', 0, 1, NULL, '2025-02-24 15:33:44', '2025-02-24 15:33:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(791, 'عياد فنخان عمر العنزي', NULL, NULL, '+966', '508099173', '$2y$10$pP/mlrzz9JYcVP5MbGM11uAI2LJIFTRNgiivva0ZohXyfGYJGr7fW', 'web', 0, 1, NULL, '2025-02-24 19:45:13', '2025-02-24 19:45:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(792, 'غنام حزام غنام عوض المطيري', NULL, NULL, '+966', '552966034', '$2y$10$fVi38XUaJDqLX91IEqYiGe9esi8lAexhhHyc6l5dMO4O2n9JMzU1y', 'web', 0, 1, NULL, '2025-02-24 22:07:19', '2025-02-24 22:07:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(793, 'سمر هلال سلمي الحربي', NULL, NULL, '+966', '591099779', '$2y$10$07HVRn642TAwj6g2fHDSluxkDN3CUMIfhr3D2MnSdKOoJ4SnqCfrW', 'web', 0, 1, NULL, '2025-02-24 22:09:11', '2025-02-24 22:09:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(794, 'Ayedh Alqahtani', NULL, NULL, '+966', '505860509', '$2y$10$fKlmpYePk/LKhGlOatLIr./FcTs33NR5n9GiRwnNjDrCqqelTDlgi', 'web', 0, 1, NULL, '2025-02-24 22:47:23', '2025-02-24 22:47:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(795, 'بدريه عطا العسيري', NULL, NULL, '+966', '554601846', '$2y$10$eiylr13kxkeiqC18kplJVe/CqgLD4WdB.v.yUS45jEixcdIUXGWgy', 'web', 0, 1, NULL, '2025-02-25 05:40:10', '2025-02-25 05:40:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(796, 'معالي سعود الشمري', NULL, NULL, '+966', '558381855', '$2y$10$QLGDL8u0QyTtSUJqPyY9Mult5pWU.CgFFBTJ55h1IQPFpX4QKhGMG', 'web', 0, 1, NULL, '2025-02-25 09:26:32', '2025-02-25 09:26:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(797, 'حسين جاسم عبداللهً القصير', NULL, NULL, '+966', '569909477', '$2y$10$5aduLp3z9ZrDFNPf61Avh.uzkdtyJb76yijeQWhR0LHhEkWmr76Ra', 'web', 0, 1, NULL, '2025-02-25 20:10:23', '2025-02-25 20:10:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(798, 'رهيب ناصر محمد منصور', NULL, NULL, '+966', '556531827', '$2y$10$syLjvOTSDs/s3zXNwB4YMeUmVcED2Emh57xPfGDEkKHuVr36jjRzK', 'web', 0, 1, NULL, '2025-02-25 20:26:22', '2025-02-25 20:26:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(799, 'أشرف الحربي', NULL, NULL, '+966', '555345464', '$2y$10$QzO4nZNO0T5lPG624Q7mU.a53guW/0Byvhd52VY29Vkr/EwygG6R2', 'web', 0, 1, NULL, '2025-02-25 23:13:06', '2025-02-25 23:13:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(801, 'موسى علي معيض القرني', NULL, NULL, '+966', '505556514', '$2y$10$UwksV0P0F8NS.OAD2nJ7OOPibfAa.GCdbyIZDJ5Wo5CNcZghOU3ay', 'web', 0, 1, NULL, '2025-02-26 12:07:38', '2025-02-26 12:07:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(803, 'صباح حسين العنزي', NULL, NULL, '+966', '559714588', '$2y$10$11KvnZVRHtdaMtINXiq/MOllh2zW0SjDExtgwlYNEs.FqVzQDZtoS', 'web', 0, 1, NULL, '2025-02-26 18:41:15', '2025-02-26 18:41:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(804, 'جواهر', NULL, NULL, '+966', '531196654', '$2y$10$hhpyO6/l/RdeLvxTvUGI3OymLYoC1VO4GtJqGIWHvMwg38jPgZBH6', 'web', 0, 1, NULL, '2025-02-26 19:24:15', '2025-02-26 19:24:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(805, '', NULL, NULL, '+966', '535133793', '$2y$10$DCkjEQTYJjsyljBQusNSfugRBoTxnnxObZLQLmDX/HMrFvSjMI5ZO', 'web', 0, 1, NULL, '2025-02-26 20:32:15', '2025-02-26 20:32:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(806, 'JAVED SHAIKH', NULL, NULL, '+966', '564425177', '$2y$10$Td9iT3JaYB8TyreRS/DF8uwFjQRX33P9u4w9iKgwaUGrEUEbnEvXa', 'web', 0, 1, NULL, '2025-02-26 22:04:21', '2025-02-26 22:04:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(809, 'علي ابوخير عسيري الزهراني', NULL, NULL, '+966', '558188449', '$2y$10$heKI4tUry/rvkH.boUTG8.aVN61RmmhtnWHnpLwhpv0rNWaxLGSjG', 'web', 0, 1, NULL, '2025-02-26 22:22:59', '2025-02-26 22:22:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(810, 'سارة', NULL, NULL, '+966', '548636588', '$2y$10$IJhSQ9LIaCPBFetH8BV/U.jX7VdCLm/gjUGrEqkqx42eJyKUzFUP6', 'web', 0, 1, NULL, '2025-02-26 23:31:04', '2025-02-26 23:31:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(811, 'عبدالله حمود الحصيني', NULL, NULL, '+966', '546292207', '$2y$10$VMM0jW2.fCVBtPmdGtWwaOkX7G7jtvjVmiO7qf5lAEaMGdvkp6bQe', 'web', 0, 1, NULL, '2025-02-27 01:20:52', '2025-02-27 01:20:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(812, 'عبدالله جميل عبدالله القرني', NULL, NULL, '+966', '599994530', '$2y$10$2fXaLTZihMFjkDqsOhN6BOJsUbUX2pb83do0SX4ULeTt5k7RBfXTW', 'web', 0, 1, NULL, '2025-02-27 05:38:52', '2025-02-27 05:38:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(813, 'عامر سلمان العنزي', NULL, NULL, '+966', '543246080', '$2y$10$merGFkX29ozVU7raJZ2vleVrfNUYgQ/jl8eWFdr..7w/LGyuGHUXq', 'web', 0, 1, NULL, '2025-02-27 11:15:45', '2025-02-27 11:15:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(816, 'Demah', NULL, NULL, '+966', '504511822', '$2y$10$0MXehkxLZesD4nA3C1opVumSGfDDt0fQVH6zZokjBtxE3ldPSNdta', 'web', 0, 1, NULL, '2025-02-27 14:47:35', '2025-02-27 14:47:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(817, 'مروى يوسف الشمري', NULL, NULL, '+966', '534528612', '$2y$10$BsbZyNi8fxoB1fqIkY/yae.uMFPZcVOMOsJ5iGwqeSPaxo.SxYQRO', 'web', 0, 1, NULL, '2025-02-27 18:55:48', '2025-02-27 18:55:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(818, 'منال حسن الغانم', NULL, NULL, '+966', '503018022', '$2y$10$q0NniWJclQ9i5FJ2NgSmY.2rmHloglNuyZuTcYaCiiQVfjV5sSKSK', 'web', 0, 1, NULL, '2025-02-27 21:25:18', '2025-02-27 21:25:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(819, 'Ola', NULL, NULL, '+966', '532028280', '$2y$10$5jUrh0s8f80MH/.Bjb.GOOIENDO606Nc96RtDj4b5TFwb.5rWFZkG', 'web', 0, 1, NULL, '2025-02-28 00:14:43', '2025-02-28 00:14:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(820, '', NULL, NULL, '+966', '570897354', '$2y$10$iwAavC22pgnxRiTdjnPp8u5BHMclW4jUCoS0iF7IRqiD59n6ESP8y', 'web', 0, 1, NULL, '2025-02-28 02:51:57', '2025-02-28 02:51:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(821, 'Muna Alshehri', NULL, NULL, '+966', '504813089', '$2y$10$kivgekItSgKIAJAeKzPKUuaVmVJ9HryEUgPKkBQRz/EMMrOph3P8u', 'web', 0, 1, NULL, '2025-02-28 13:01:57', '2025-02-28 13:01:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(822, 'تركي العنزي', NULL, NULL, '+966', '557535321', '$2y$10$wyqIt8SdzD5XYWr0.vEhb.93KkM7PLWcztsqKlB8v.nmITOfkEz1G', 'web', 0, 1, NULL, '2025-02-28 13:26:03', '2025-02-28 13:26:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ' ',
  `phone` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ' ',
  `name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ' ',
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ' ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address`, `title`, `phone`, `name`, `notes`, `created_at`, `updated_at`) VALUES
(43, 689, '2', ' ', ' ', ' ', ' ', '2025-01-06 00:46:09', '2025-01-06 00:46:09'),
(47, 741, 'الرياض حي النهضة شارع الحكم المنتصر بالله', 'عمارة', '+966540045321', 'عبدالعزيز الشمري', ' ', '2025-01-30 14:04:57', '2025-01-30 14:04:57'),
(48, 761, 'الاحساء،محاسن أرامكو', 'محاسن', '+966570503566', 'نوف السبيعي', ' ', '2025-02-12 01:39:14', '2025-02-12 01:39:14'),
(49, 762, 'الرياض', 'منزل تركي', '0557702145', 'تركي نشمي الحربي', ' ', '2025-02-12 04:29:05', '2025-02-12 04:29:05'),
(50, 764, 'حايل', 'حي مكه', '+966567424000', 'سلطان فراج الشمري', 'شارع عبدالله المغزالي', '2025-02-12 21:50:27', '2025-02-12 21:50:27'),
(51, 769, 'المزاحمية', 'المنزل', '581427037', 'علي المقاطي', ' ', '2025-02-14 22:28:09', '2025-02-14 22:28:09'),
(52, 770, 'الباحه بلجرشي', 'منطقة الباحه', '٥٤٣٨٠٤٤٦٨', 'رشا راشد جمعان', ' ', '2025-02-14 23:58:53', '2025-02-14 23:58:53'),
(53, 775, 'ss', ' ', ' ', ' ', ' ', '2025-02-16 18:47:31', '2025-02-16 18:47:31'),
(55, 729, 'qd', 'ty', '555555', 'e', 'd', '2025-04-10 20:11:10', '2025-04-10 20:11:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_firebase_tokens`
--

CREATE TABLE `user_firebase_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `software_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_us`
--
ALTER TABLE `about_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `about_us_translations`
--
ALTER TABLE `about_us_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `about_us_translations_about_us_id_locale_unique` (`about_us_id`,`locale`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_visits`
--
ALTER TABLE `app_visits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cancel_contract_requests`
--
ALTER TABLE `cancel_contract_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaints_user_id_foreign` (`user_id`),
  ADD KEY `complaints_complaint_type_id_foreign` (`complaint_type_id`),
  ADD KEY `complaints_maid_nationality_id_foreign` (`maid_nationality_id`),
  ADD KEY `complaints_resolved_by_foreign` (`resolved_by`),
  ADD KEY `complaints_complainant_phone_index` (`complainant_phone`),
  ADD KEY `complaints_maid_passport_index` (`maid_passport`),
  ADD KEY `complaints_priority_index` (`priority`),
  ADD KEY `complaints_assigned_to_index` (`assigned_to`);

--
-- Indexes for table `complaint_feedbacks`
--
ALTER TABLE `complaint_feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaint_feedbacks_token_unique` (`token`),
  ADD KEY `complaint_feedbacks_user_id_foreign` (`user_id`),
  ADD KEY `complaint_feedbacks_complaint_id_submitted_at_index` (`complaint_id`,`submitted_at`),
  ADD KEY `complaint_feedbacks_token_index` (`token`);

--
-- Indexes for table `complaint_types`
--
ALTER TABLE `complaint_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contracts_order_id_foreign` (`order_id`),
  ADD KEY `contracts_user_id_foreign` (`user_id`),
  ADD KEY `contracts_maid_id_foreign` (`maid_id`),
  ADD KEY `contracts_marketer_id_foreign` (`marketer_id`),
  ADD KEY `contracts_status_index` (`status`),
  ADD KEY `contracts_type_index` (`type`),
  ADD KEY `contracts_created_at_index` (`created_at`),
  ADD KEY `contracts_required_maid_nationality_id_index` (`required_maid_nationality_id`),
  ADD KEY `contracts_previous_contract_id_foreign` (`previous_contract_id`),
  ADD KEY `contracts_required_maid_religion_id_foreign` (`required_maid_religion_id`),
  ADD KEY `contracts_required_maid_job_title_id_foreign` (`required_maid_job_title_id`);

--
-- Indexes for table `contract_infos`
--
ALTER TABLE `contract_infos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_infos_contract_id_foreign` (`contract_id`),
  ADD KEY `contract_infos_previous_sponsorship_foreign` (`previous_sponsorship`),
  ADD KEY `contract_infos_shelter_id_foreign` (`shelter_id`);

--
-- Indexes for table `contract_payments`
--
ALTER TABLE `contract_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_payments_contract_id_foreign` (`contract_id`);

--
-- Indexes for table `contract_return_requests`
--
ALTER TABLE `contract_return_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_return_requests_processed_by_foreign` (`processed_by`),
  ADD KEY `contract_return_requests_contract_id_status_index` (`contract_id`,`status`),
  ADD KEY `contract_return_requests_user_id_status_index` (`user_id`,`status`);

--
-- Indexes for table `contract_status_logs`
--
ALTER TABLE `contract_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_status_logs_contract_id_foreign` (`contract_id`);

--
-- Indexes for table `cookies`
--
ALTER TABLE `cookies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_code_unique` (`code`);

--
-- Indexes for table `deportation_procedures`
--
ALTER TABLE `deportation_procedures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deportation_procedures_created_by_foreign` (`created_by`),
  ADD KEY `deportation_procedures_completed_by_foreign` (`completed_by`),
  ADD KEY `deportation_procedures_status_index` (`status`),
  ADD KEY `deportation_procedures_contract_id_index` (`contract_id`),
  ADD KEY `deportation_procedures_maid_id_index` (`maid_id`),
  ADD KEY `deportation_procedures_trip_id_foreign` (`trip_id`);

--
-- Indexes for table `deportation_procedure_logs`
--
ALTER TABLE `deportation_procedure_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deportation_procedure_logs_admin_id_foreign` (`admin_id`),
  ADD KEY `deportation_logs_procedure_created_idx` (`deportation_procedure_id`,`created_at`),
  ADD KEY `deportation_logs_action_idx` (`action_type`);

--
-- Indexes for table `develop_settings`
--
ALTER TABLE `develop_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `istiqdam_contracts`
--
ALTER TABLE `istiqdam_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `istiqdam_contracts_istiqdam_contract_id_unique` (`istiqdam_contract_id`),
  ADD KEY `istiqdam_contracts_created_by_foreign` (`created_by`),
  ADD KEY `istiqdam_contracts_updated_by_foreign` (`updated_by`),
  ADD KEY `istiqdam_contracts_status_created_at_index` (`status`,`created_at`),
  ADD KEY `istiqdam_contracts_beneficiary_id_index` (`beneficiary_id`),
  ADD KEY `istiqdam_contracts_package_id_index` (`package_id`),
  ADD KEY `istiqdam_contracts_assigned_maid_id_index` (`assigned_maid_id`),
  ADD KEY `istiqdam_contracts_last_synced_at_index` (`last_synced_at`);

--
-- Indexes for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_titles_translations`
--
ALTER TABLE `job_titles_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_titles_translations_job_title_id_locale_unique` (`job_title_id`,`locale`);

--
-- Indexes for table `job_types`
--
ALTER TABLE `job_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_types_translations`
--
ALTER TABLE `job_types_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_types_translations_job_type_id_locale_unique` (`job_type_id`,`locale`);

--
-- Indexes for table `maids`
--
ALTER TABLE `maids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maids_nationality_id_foreign` (`nationality_id`),
  ADD KEY `maids_religion_id_foreign` (`religion_id`),
  ADD KEY `maids_office_id_foreign` (`office_id`),
  ADD KEY `maids_nationality_id_index` (`nationality_id`),
  ADD KEY `maids_religion_id_index` (`religion_id`),
  ADD KEY `maids_office_id_index` (`office_id`),
  ADD KEY `maids_status_index` (`status`);

--
-- Indexes for table `maid_jobs`
--
ALTER TABLE `maid_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maid_jobs_maid_id_foreign` (`maid_id`),
  ADD KEY `maid_jobs_job_type_id_foreign` (`job_type_id`);

--
-- Indexes for table `maid_job_titles`
--
ALTER TABLE `maid_job_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maid_job_titles_maid_id_foreign` (`maid_id`),
  ADD KEY `maid_job_titles_job_title_id_foreign` (`job_title_id`);

--
-- Indexes for table `maid_translations`
--
ALTER TABLE `maid_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maid_translations_maid_id_locale_unique` (`maid_id`,`locale`),
  ADD KEY `maid_translations_locale_index` (`locale`);

--
-- Indexes for table `maid_unavailable_records`
--
ALTER TABLE `maid_unavailable_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maid_unavailable_records_maid_id_index` (`maid_id`),
  ADD KEY `maid_unavailable_records_contract_id_index` (`contract_id`),
  ADD KEY `maid_unavailable_records_admin_id_index` (`admin_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `nationalities`
--
ALTER TABLE `nationalities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nationalities_translations`
--
ALTER TABLE `nationalities_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nationalities_translations_nationality_id_locale_unique` (`nationality_id`,`locale`);

--
-- Indexes for table `new_settings`
--
ALTER TABLE `new_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_order_id_foreign` (`order_id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_maid_id_foreign` (`maid_id`),
  ADD KEY `orders_user_id_foreign` (`user_id`),
  ADD KEY `orders_job_type_id_foreign` (`job_type_id`),
  ADD KEY `orders_status_index` (`status`),
  ADD KEY `orders_created_at_index` (`created_at`),
  ADD KEY `orders_user_id_index` (`user_id`),
  ADD KEY `orders_maid_id_index` (`maid_id`),
  ADD KEY `orders_job_type_id_index` (`job_type_id`),
  ADD KEY `orders_deadline_at_index` (`deadline_at`),
  ADD KEY `orders_order_code_index` (`order_code`),
  ADD KEY `orders_status_created_at_index` (`status`,`created_at`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_details_order_id_foreign` (`order_id`),
  ADD KEY `order_details_admin_id_foreign` (`admin_id`),
  ADD KEY `order_details_package_id_foreign` (`package_id`),
  ADD KEY `order_details_address_id_foreign` (`address_id`),
  ADD KEY `order_details_order_id_index` (`order_id`),
  ADD KEY `order_details_admin_id_index` (`admin_id`);

--
-- Indexes for table `order_transfers`
--
ALTER TABLE `order_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_transfers_order_id_foreign` (`order_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `packages_nationality_id_foreign` (`nationality_id`);

--
-- Indexes for table `packages_translations`
--
ALTER TABLE `packages_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `packages_translations_package_id_locale_unique` (`package_id`,`locale`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `print_details_translations`
--
ALTER TABLE `print_details_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `print_details_translations_print_details_id_locale_unique` (`print_details_id`,`locale`),
  ADD KEY `print_details_translations_locale_index` (`locale`);

--
-- Indexes for table `print_setting`
--
ALTER TABLE `print_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `print_setting_details`
--
ALTER TABLE `print_setting_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `print_setting_details_print_setting_id_foreign` (`print_setting_id`);

--
-- Indexes for table `print_setting_translations`
--
ALTER TABLE `print_setting_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `print_setting_translations_print_setting_id_locale_unique` (`print_setting_id`,`locale`),
  ADD KEY `print_setting_translations_locale_index` (`locale`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions_translations`
--
ALTER TABLE `questions_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `questions_translations_question_id_locale_unique` (`question_id`,`locale`);

--
-- Indexes for table `recruitment_offices`
--
ALTER TABLE `recruitment_offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recruitment_offices_nationality_id_foreign` (`nationality_id`);

--
-- Indexes for table `recruitment_office_translations`
--
ALTER TABLE `recruitment_office_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `recruitment_office_translations_rec_office_id_locale_unique` (`rec_office_id`,`locale`);

--
-- Indexes for table `recruitment_requests`
--
ALTER TABLE `recruitment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recruitment_requests_client_id_foreign` (`client_id`),
  ADD KEY `recruitment_requests_maid_id_foreign` (`maid_id`),
  ADD KEY `recruitment_requests_admin_id_foreign` (`admin_id`);

--
-- Indexes for table `religions`
--
ALTER TABLE `religions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `religions_translations`
--
ALTER TABLE `religions_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `religions_translations_religion_id_locale_unique` (`religion_id`,`locale`);

--
-- Indexes for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rental_contracts_contract_number_unique` (`contract_number`),
  ADD KEY `rental_contracts_user_id_foreign` (`user_id`),
  ADD KEY `rental_contracts_maid_id_foreign` (`maid_id`),
  ADD KEY `rental_contracts_marketer_id_foreign` (`marketer_id`),
  ADD KEY `rental_contracts_order_id_foreign` (`order_id`),
  ADD KEY `rental_contracts_status_closure_type_index` (`status`,`closure_type`);

--
-- Indexes for table `rental_contract_infos`
--
ALTER TABLE `rental_contract_infos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rental_contract_terms`
--
ALTER TABLE `rental_contract_terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_requests_maid_id_foreign` (`maid_id`),
  ADD KEY `return_requests_order_id_foreign` (`order_id`),
  ADD KEY `return_requests_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services_translations`
--
ALTER TABLE `services_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `services_translations_service_id_locale_unique` (`service_id`,`locale`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings_translations`
--
ALTER TABLE `settings_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_translations_setting_id_locale_unique` (`setting_id`,`locale`),
  ADD KEY `settings_translations_locale_index` (`locale`);

--
-- Indexes for table `shelter_places`
--
ALTER TABLE `shelter_places`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shelter_place_translations`
--
ALTER TABLE `shelter_place_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shelter_place_translations_shelter_place_id_locale_unique` (`shelter_place_id`,`locale`);

--
-- Indexes for table `shelter_workers`
--
ALTER TABLE `shelter_workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skills_translations`
--
ALTER TABLE `skills_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skill_locale_unique` (`skill_id`,`locale`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders_translations`
--
ALTER TABLE `sliders_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sliders_translations_slider_id_locale_unique` (`slider_id`,`locale`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sms_templates_name_unique` (`name`);

--
-- Indexes for table `sponsorship_transfers`
--
ALTER TABLE `sponsorship_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sponsorship_transfers_approved_by_foreign` (`approved_by`),
  ADD KEY `sponsorship_transfers_contract_id_status_index` (`contract_id`,`status`),
  ADD KEY `sponsorship_transfers_old_sponsor_id_index` (`old_sponsor_id`),
  ADD KEY `sponsorship_transfers_new_sponsor_id_index` (`new_sponsor_id`),
  ADD KEY `sponsorship_transfers_status_index` (`status`),
  ADD KEY `sponsorship_transfers_transferred_by_foreign` (`transferred_by`),
  ADD KEY `sponsorship_transfers_failed_by_foreign` (`failed_by`),
  ADD KEY `sponsorship_transfers_trial_started_at_index` (`trial_started_at`),
  ADD KEY `sponsorship_transfers_transferred_at_index` (`transferred_at`),
  ADD KEY `sponsorship_transfers_failed_at_index` (`failed_at`);

--
-- Indexes for table `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `statistics_translations`
--
ALTER TABLE `statistics_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `statistics_translations_statistic_id_locale_unique` (`statistic_id`,`locale`);

--
-- Indexes for table `steps`
--
ALTER TABLE `steps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `steps_translations`
--
ALTER TABLE `steps_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `steps_translations_step_id_locale_unique` (`step_id`,`locale`);

--
-- Indexes for table `travel_histories`
--
ALTER TABLE `travel_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `travel_histories_confirmed_by_foreign` (`confirmed_by`),
  ADD KEY `travel_histories_arrival_confirmed_by_foreign` (`arrival_confirmed_by`),
  ADD KEY `travel_histories_status_confirmed_at_index` (`status`,`confirmed_at`),
  ADD KEY `travel_histories_status_arrived_at_index` (`status`,`arrived_at`);

--
-- Indexes for table `travel_history_files`
--
ALTER TABLE `travel_history_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD KEY `users_nationality_id_foreign` (`nationality_id`),
  ADD KEY `users_city_index` (`city`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_addresses_user_id_foreign` (`user_id`);

--
-- Indexes for table `user_firebase_tokens`
--
ALTER TABLE `user_firebase_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_firebase_tokens_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_us`
--
ALTER TABLE `about_us`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `about_us_translations`
--
ALTER TABLE `about_us_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `app_visits`
--
ALTER TABLE `app_visits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cancel_contract_requests`
--
ALTER TABLE `cancel_contract_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complaint_feedbacks`
--
ALTER TABLE `complaint_feedbacks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complaint_types`
--
ALTER TABLE `complaint_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=477;

--
-- AUTO_INCREMENT for table `contract_infos`
--
ALTER TABLE `contract_infos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=463;

--
-- AUTO_INCREMENT for table `contract_payments`
--
ALTER TABLE `contract_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contract_return_requests`
--
ALTER TABLE `contract_return_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contract_status_logs`
--
ALTER TABLE `contract_status_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1823;

--
-- AUTO_INCREMENT for table `cookies`
--
ALTER TABLE `cookies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `deportation_procedures`
--
ALTER TABLE `deportation_procedures`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `deportation_procedure_logs`
--
ALTER TABLE `deportation_procedure_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `develop_settings`
--
ALTER TABLE `develop_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `istiqdam_contracts`
--
ALTER TABLE `istiqdam_contracts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_titles`
--
ALTER TABLE `job_titles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `job_titles_translations`
--
ALTER TABLE `job_titles_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `job_types`
--
ALTER TABLE `job_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_types_translations`
--
ALTER TABLE `job_types_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `maids`
--
ALTER TABLE `maids`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maid_jobs`
--
ALTER TABLE `maid_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3052;

--
-- AUTO_INCREMENT for table `maid_job_titles`
--
ALTER TABLE `maid_job_titles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2382;

--
-- AUTO_INCREMENT for table `maid_translations`
--
ALTER TABLE `maid_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3061;

--
-- AUTO_INCREMENT for table `maid_unavailable_records`
--
ALTER TABLE `maid_unavailable_records`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `nationalities`
--
ALTER TABLE `nationalities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `nationalities_translations`
--
ALTER TABLE `nationalities_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `new_settings`
--
ALTER TABLE `new_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=850;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=487;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=469;

--
-- AUTO_INCREMENT for table `order_transfers`
--
ALTER TABLE `order_transfers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `packages_translations`
--
ALTER TABLE `packages_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_details_translations`
--
ALTER TABLE `print_details_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting`
--
ALTER TABLE `print_setting`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting_details`
--
ALTER TABLE `print_setting_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting_translations`
--
ALTER TABLE `print_setting_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `questions_translations`
--
ALTER TABLE `questions_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `recruitment_offices`
--
ALTER TABLE `recruitment_offices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `recruitment_office_translations`
--
ALTER TABLE `recruitment_office_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `recruitment_requests`
--
ALTER TABLE `recruitment_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `religions`
--
ALTER TABLE `religions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `religions_translations`
--
ALTER TABLE `religions_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rental_contract_infos`
--
ALTER TABLE `rental_contract_infos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_contract_terms`
--
ALTER TABLE `rental_contract_terms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services_translations`
--
ALTER TABLE `services_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings_translations`
--
ALTER TABLE `settings_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shelter_places`
--
ALTER TABLE `shelter_places`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shelter_place_translations`
--
ALTER TABLE `shelter_place_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shelter_workers`
--
ALTER TABLE `shelter_workers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `skills_translations`
--
ALTER TABLE `skills_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sliders_translations`
--
ALTER TABLE `sliders_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsorship_transfers`
--
ALTER TABLE `sponsorship_transfers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `statistics_translations`
--
ALTER TABLE `statistics_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `steps`
--
ALTER TABLE `steps`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `steps_translations`
--
ALTER TABLE `steps_translations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_histories`
--
ALTER TABLE `travel_histories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `travel_history_files`
--
ALTER TABLE `travel_history_files`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1672;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `user_firebase_tokens`
--
ALTER TABLE `user_firebase_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_us_translations`
--
ALTER TABLE `about_us_translations`
  ADD CONSTRAINT `about_us_translations_about_us_id_foreign` FOREIGN KEY (`about_us_id`) REFERENCES `about_us` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `complaints_complaint_type_id_foreign` FOREIGN KEY (`complaint_type_id`) REFERENCES `complaint_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `complaints_maid_nationality_id_foreign` FOREIGN KEY (`maid_nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `complaints_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `complaints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaint_feedbacks`
--
ALTER TABLE `complaint_feedbacks`
  ADD CONSTRAINT `complaint_feedbacks_complaint_id_foreign` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_marketer_id_foreign` FOREIGN KEY (`marketer_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_previous_contract_id_foreign` FOREIGN KEY (`previous_contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_required_maid_job_title_id_foreign` FOREIGN KEY (`required_maid_job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_required_maid_nationality_id_foreign` FOREIGN KEY (`required_maid_nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_required_maid_religion_id_foreign` FOREIGN KEY (`required_maid_religion_id`) REFERENCES `religions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_infos`
--
ALTER TABLE `contract_infos`
  ADD CONSTRAINT `contract_infos_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_infos_previous_sponsorship_foreign` FOREIGN KEY (`previous_sponsorship`) REFERENCES `recruitment_offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contract_infos_shelter_id_foreign` FOREIGN KEY (`shelter_id`) REFERENCES `shelter_places` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_payments`
--
ALTER TABLE `contract_payments`
  ADD CONSTRAINT `contract_payments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_return_requests`
--
ALTER TABLE `contract_return_requests`
  ADD CONSTRAINT `contract_return_requests_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_return_requests_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contract_return_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contract_status_logs`
--
ALTER TABLE `contract_status_logs`
  ADD CONSTRAINT `contract_status_logs_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deportation_procedures`
--
ALTER TABLE `deportation_procedures`
  ADD CONSTRAINT `deportation_procedures_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deportation_procedures_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deportation_procedures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deportation_procedures_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deportation_procedures_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `travel_histories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deportation_procedure_logs`
--
ALTER TABLE `deportation_procedure_logs`
  ADD CONSTRAINT `deportation_procedure_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deportation_procedure_logs_deportation_procedure_id_foreign` FOREIGN KEY (`deportation_procedure_id`) REFERENCES `deportation_procedures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `istiqdam_contracts`
--
ALTER TABLE `istiqdam_contracts`
  ADD CONSTRAINT `istiqdam_contracts_assigned_maid_id_foreign` FOREIGN KEY (`assigned_maid_id`) REFERENCES `maids` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `istiqdam_contracts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `istiqdam_contracts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `job_titles_translations`
--
ALTER TABLE `job_titles_translations`
  ADD CONSTRAINT `job_titles_translations_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_types_translations`
--
ALTER TABLE `job_types_translations`
  ADD CONSTRAINT `job_types_translations_job_type_id_foreign` FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maids`
--
ALTER TABLE `maids`
  ADD CONSTRAINT `maids_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maids_office_id_foreign` FOREIGN KEY (`office_id`) REFERENCES `recruitment_offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `maids_religion_id_foreign` FOREIGN KEY (`religion_id`) REFERENCES `religions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maid_jobs`
--
ALTER TABLE `maid_jobs`
  ADD CONSTRAINT `maid_jobs_job_type_id_foreign` FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maid_jobs_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maid_job_titles`
--
ALTER TABLE `maid_job_titles`
  ADD CONSTRAINT `maid_job_titles_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maid_job_titles_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maid_translations`
--
ALTER TABLE `maid_translations`
  ADD CONSTRAINT `maid_translations_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maid_unavailable_records`
--
ALTER TABLE `maid_unavailable_records`
  ADD CONSTRAINT `maid_unavailable_records_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maid_unavailable_records_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maid_unavailable_records_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nationalities_translations`
--
ALTER TABLE `nationalities_translations`
  ADD CONSTRAINT `nationalities_translations_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_job_type_id_foreign` FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_transfers`
--
ALTER TABLE `order_transfers`
  ADD CONSTRAINT `order_transfers_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `nationalities` (`id`);

--
-- Constraints for table `packages_translations`
--
ALTER TABLE `packages_translations`
  ADD CONSTRAINT `packages_translations_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `print_details_translations`
--
ALTER TABLE `print_details_translations`
  ADD CONSTRAINT `print_details_translations_print_details_id_foreign` FOREIGN KEY (`print_details_id`) REFERENCES `print_setting_details` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `print_setting_details`
--
ALTER TABLE `print_setting_details`
  ADD CONSTRAINT `print_setting_details_print_setting_id_foreign` FOREIGN KEY (`print_setting_id`) REFERENCES `print_setting` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `print_setting_translations`
--
ALTER TABLE `print_setting_translations`
  ADD CONSTRAINT `print_setting_translations_print_setting_id_foreign` FOREIGN KEY (`print_setting_id`) REFERENCES `print_setting` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions_translations`
--
ALTER TABLE `questions_translations`
  ADD CONSTRAINT `questions_translations_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_offices`
--
ALTER TABLE `recruitment_offices`
  ADD CONSTRAINT `recruitment_offices_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_office_translations`
--
ALTER TABLE `recruitment_office_translations`
  ADD CONSTRAINT `recruitment_office_translations_rec_office_id_foreign` FOREIGN KEY (`rec_office_id`) REFERENCES `recruitment_offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_requests`
--
ALTER TABLE `recruitment_requests`
  ADD CONSTRAINT `recruitment_requests_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `recruitment_requests_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recruitment_requests_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `religions_translations`
--
ALTER TABLE `religions_translations`
  ADD CONSTRAINT `religions_translations_religion_id_foreign` FOREIGN KEY (`religion_id`) REFERENCES `religions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_contracts`
--
ALTER TABLE `rental_contracts`
  ADD CONSTRAINT `rental_contracts_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rental_contracts_marketer_id_foreign` FOREIGN KEY (`marketer_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rental_contracts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rental_contracts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `return_requests_maid_id_foreign` FOREIGN KEY (`maid_id`) REFERENCES `maids` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `return_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `return_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services_translations`
--
ALTER TABLE `services_translations`
  ADD CONSTRAINT `services_translations_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings_translations`
--
ALTER TABLE `settings_translations`
  ADD CONSTRAINT `settings_translations_setting_id_foreign` FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shelter_place_translations`
--
ALTER TABLE `shelter_place_translations`
  ADD CONSTRAINT `shelter_place_translations_shelter_place_id_foreign` FOREIGN KEY (`shelter_place_id`) REFERENCES `shelter_places` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skills_translations`
--
ALTER TABLE `skills_translations`
  ADD CONSTRAINT `skills_translations_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sliders_translations`
--
ALTER TABLE `sliders_translations`
  ADD CONSTRAINT `sliders_translations_slider_id_foreign` FOREIGN KEY (`slider_id`) REFERENCES `sliders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sponsorship_transfers`
--
ALTER TABLE `sponsorship_transfers`
  ADD CONSTRAINT `sponsorship_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sponsorship_transfers_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sponsorship_transfers_failed_by_foreign` FOREIGN KEY (`failed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sponsorship_transfers_new_sponsor_id_foreign` FOREIGN KEY (`new_sponsor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sponsorship_transfers_old_sponsor_id_foreign` FOREIGN KEY (`old_sponsor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sponsorship_transfers_transferred_by_foreign` FOREIGN KEY (`transferred_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `statistics_translations`
--
ALTER TABLE `statistics_translations`
  ADD CONSTRAINT `statistics_translations_statistic_id_foreign` FOREIGN KEY (`statistic_id`) REFERENCES `statistics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `steps_translations`
--
ALTER TABLE `steps_translations`
  ADD CONSTRAINT `steps_translations_step_id_foreign` FOREIGN KEY (`step_id`) REFERENCES `steps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_histories`
--
ALTER TABLE `travel_histories`
  ADD CONSTRAINT `travel_histories_arrival_confirmed_by_foreign` FOREIGN KEY (`arrival_confirmed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `travel_histories_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_firebase_tokens`
--
ALTER TABLE `user_firebase_tokens`
  ADD CONSTRAINT `user_firebase_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
