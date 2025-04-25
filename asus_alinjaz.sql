-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2023 at 12:37 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `asus_alinjaz`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

CREATE TABLE `about_us` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `about_us_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `locale` varchar(255) NOT NULL,
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `admin_type` varchar(255) NOT NULL DEFAULT '0',
  `lang` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_developer` int(11) NOT NULL DEFAULT 0,
  `sex_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 is male and 1 is female',
  `whatsapp` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `phone`, `email`, `email_verified_at`, `password`, `image`, `admin_type`, `lang`, `remember_token`, `deleted_at`, `created_at`, `updated_at`, `is_developer`, `sex_type`, `whatsapp`) VALUES
(1, 'مشرف', '01009428727', 'admin@admin.com', NULL, '$2y$10$W4ZCVuOIfb6GpdofyontB.UJbLCaYWjxEtxZ3wIeNKUi1udDcNlAS', NULL, 'super_admin', NULL, NULL, NULL, '2023-12-20 14:25:00', '2023-12-24 11:10:26', 0, 0, '+201098380656'),
(25, 'نورا محمد', '+1 (777) 522-9478', 'nour@admin.com', NULL, '$2y$10$wVu.t6HoZgP0bC24.aMQeOGABEGi0MFPrvHGCJ7bDZlgRMg1cdpQe', NULL, 'customer_service', NULL, NULL, NULL, '2023-12-27 10:43:59', '2023-12-27 10:43:59', 0, 1, '0545040659'),
(26, 'فهد ال عمران', '01098578878', 'fahd@admin.com', NULL, '$2y$10$PLGzOKSTV8DBy/CBz3FvpODfYzrIVOkXNdg40V/kMRSCSW9NOFmX6', NULL, 'customer_service', NULL, NULL, NULL, '2023-12-27 11:38:39', '2023-12-27 11:38:39', 0, 0, '+201098380656');

-- --------------------------------------------------------

--
-- Table structure for table `app_visits`
--

CREATE TABLE `app_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `android_count` int(11) NOT NULL DEFAULT 0,
  `ios_count` int(11) NOT NULL DEFAULT 0,
  `web_count` int(11) NOT NULL DEFAULT 0,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_visits`
--

INSERT INTO `app_visits` (`id`, `android_count`, `ios_count`, `web_count`, `date`, `created_at`, `updated_at`) VALUES
(1, 0, 0, 2, '2023-12-24', '2023-12-24 07:34:50', '2023-12-24 07:54:58'),
(2, 0, 0, 2, '2023-12-25', '2023-12-25 07:25:26', '2023-12-25 08:42:15'),
(3, 0, 0, 3, '2023-12-26', '2023-12-26 08:05:35', '2023-12-26 17:25:20'),
(4, 0, 0, 1, '2023-12-27', '2023-12-27 07:47:42', '2023-12-27 07:47:42'),
(5, 0, 0, 2, '2023-12-28', '2023-12-28 08:26:05', '2023-12-28 08:28:06');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `address` varchar(255) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `address`, `message`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 'شكوي من طلب', 'طلبي رقم #54 لم يتم الموافقة عليه حتي الان', 5, '2023-12-25 09:05:04', '2023-12-25 09:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `phone`, `email`, `subject`, `message`, `created_at`, `updated_at`) VALUES
(2, 'احمد', '010985478784', 'ahmed@gmail.com', 'شكوي', 'لا يم .. ني ك ك يس ص يصيصض', NULL, NULL),
(3, 'Fay Merritt', '100105487', 'gamasumuq@mailinator.com', 'Aut error voluptatum', 'Ipsum minim velit', '2023-12-26 20:36:38', '2023-12-26 20:36:38'),
(4, 'Galena Meyer', '0559887989', 'gesaxol@mailinator.com', 'Numquam odio ad ulla', 'Et voluptate ullamco', '2023-12-28 10:46:26', '2023-12-28 10:46:26'),
(5, 'Felicia Norman', '6454548', 'nuca@mailinator.com', 'Nulla dicta voluptat', 'Ratione et saepe lor', '2023-12-28 12:34:45', '2023-12-28 12:34:45');

-- --------------------------------------------------------

--
-- Table structure for table `cookies`
--

CREATE TABLE `cookies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `visits_count` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cookies`
--

INSERT INTO `cookies` (`id`, `name`, `value`, `visits_count`, `created_at`, `updated_at`) VALUES
(1, 'web_cookie', '0863dadf-818b-40ff-9f29-a6e2ae1e539b', 1, '2023-12-24 07:54:58', '2023-12-24 07:54:58'),
(2, 'web_cookie', 'a48aa3d2-19cd-41b9-876c-83a2e8eef759', 1, '2023-12-25 08:42:15', '2023-12-25 08:42:15'),
(3, 'web_cookie', 'eb444117-92b5-4fbb-bcfe-fcf130e8c621', 1, '2023-12-26 08:06:00', '2023-12-26 08:06:00'),
(4, 'web_cookie', '0689a679-4316-46a4-8a0e-0a762ce766a7', 1, '2023-12-26 17:25:20', '2023-12-26 17:25:20'),
(5, 'web_cookie', '5e02ffd1-09db-4ae5-8bac-8170eb61f8a3', 1, '2023-12-28 08:28:05', '2023-12-28 08:28:05');

-- --------------------------------------------------------

--
-- Table structure for table `develop_settings`
--

CREATE TABLE `develop_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `copyrights` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `portfolio_url` varchar(255) NOT NULL,
  `licences_url` varchar(255) NOT NULL,
  `support_url` varchar(255) NOT NULL,
  `faq_url` varchar(255) NOT NULL,
  `home_url` varchar(255) NOT NULL,
  `about_url` varchar(255) NOT NULL,
  `translation_show` tinyint(1) NOT NULL DEFAULT 0,
  `login_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

CREATE TABLE `job_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`id`, `image`, `created_at`, `updated_at`) VALUES
(21, 'images/JobTitle/7MHnEbHqcA1703149717.webp', '2023-12-21 10:08:37', '2023-12-21 10:08:37'),
(22, 'images/JobTitle/2UkAdMA2EO1703149775.webp', '2023-12-21 10:09:35', '2023-12-21 10:09:35'),
(26, 'images/JobTitle/KNihtCB7rV1703150641.webp', '2023-12-21 10:24:01', '2023-12-21 10:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `job_titles_translations`
--

CREATE TABLE `job_titles_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_title_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(100) NOT NULL DEFAULT '',
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `job_type_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_types_translations`
--

INSERT INTO `job_types_translations` (`id`, `job_type_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'طلب استقدام', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(2, 1, 'Recruitment request', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(3, 2, 'ايجار عمالة', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(4, 2, 'Labor hire', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(5, 3, 'استلام فوري', 'ar', '2023-12-24 07:25:54', '2023-12-24 07:25:54'),
(6, 3, 'Labor transfer', 'en', '2023-12-24 07:25:54', '2023-12-24 07:25:54');

-- --------------------------------------------------------

--
-- Table structure for table `maids`
--

CREATE TABLE `maids` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nationality_id` bigint(20) UNSIGNED NOT NULL,
  `passport_num` varchar(255) NOT NULL DEFAULT '',
  `price` double NOT NULL DEFAULT 0,
  `salary` double NOT NULL DEFAULT 0,
  `religion_id` bigint(20) UNSIGNED NOT NULL,
  `age` double(8,2) NOT NULL,
  `cv_file` varchar(255) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `available_at` date NOT NULL DEFAULT '2023-12-20',
  `is_show` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maids`
--

INSERT INTO `maids` (`id`, `nationality_id`, `passport_num`, `price`, `salary`, `religion_id`, `age`, `cv_file`, `available`, `available_at`, `is_show`, `created_at`, `updated_at`) VALUES
(6, 1, '#DSW5054', 100, 450, 3, 30.00, 'images/Maid/l0VNXjo3k91703426420.jpg', 0, '2024-02-24', 1, '2023-12-24 15:00:20', '2023-12-26 19:35:50'),
(7, 1, '#WS550', 300, 500, 1, 33.00, 'images/Maid/fICkxmoi321703486527.jpg', 1, '2023-12-25', 1, '2023-12-25 07:42:08', '2023-12-26 09:53:07'),
(12, 1, 'WES545', 148, 500, 1, 28.00, 'images/Maid/pGzZNvx0oE1703613353.jpg', 1, '2023-12-20', 1, '2023-12-26 18:55:53', '2023-12-26 18:56:51'),
(13, 1, 'Sint vel reprehender', 93, 0, 2, 62.00, 'images/Maid/1S8D1pwXG71703672669.jpg', 1, '2023-12-20', 1, '2023-12-27 11:24:29', '2023-12-27 11:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `maid_jobs`
--

CREATE TABLE `maid_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `maid_id` bigint(20) UNSIGNED NOT NULL,
  `job_type_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maid_jobs`
--

INSERT INTO `maid_jobs` (`id`, `maid_id`, `job_type_id`, `created_at`, `updated_at`) VALUES
(2, 6, 1, NULL, NULL),
(3, 7, 2, NULL, NULL),
(11, 12, 2, NULL, NULL),
(12, 12, 3, NULL, NULL),
(13, 12, 1, NULL, NULL),
(14, 13, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maid_job_titles`
--

CREATE TABLE `maid_job_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `maid_id` bigint(20) UNSIGNED NOT NULL,
  `job_title_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maid_job_titles`
--

INSERT INTO `maid_job_titles` (`id`, `maid_id`, `job_title_id`, `created_at`, `updated_at`) VALUES
(1, 6, 22, NULL, NULL),
(2, 7, 22, NULL, NULL),
(4, 7, 26, NULL, NULL),
(9, 12, 22, NULL, NULL),
(10, 13, 21, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
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
(44, '2023_12_20_103632_create_notifications_translations_table', 1),
(45, '2023_12_20_104027_create_about_us_table', 1),
(46, '2023_12_20_104128_create_about_us_translations_table', 1),
(47, '2023_12_20_105957_create_cookies_table', 1),
(48, '2023_12_20_130059_create_services_table', 1),
(49, '2023_12_20_130129_create_services_translations_table', 1),
(50, '2023_12_20_142911_create_statistics_table', 1),
(51, '2023_12_20_142943_create_statistics_translations_table', 1),
(52, '2023_12_21_164008_create_maid_job_titles_table', 2),
(53, '2023_12_24_133149_create_complaints_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `nationalities`
--

CREATE TABLE `nationalities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `logo` varchar(500) NOT NULL,
  `phone_code` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nationalities`
--

INSERT INTO `nationalities` (`id`, `logo`, `phone_code`, `created_at`, `updated_at`) VALUES
(1, 'images/Nationality/zuJCTTRPfX1703152612.png', '+966', '2023-12-21 10:56:52', '2023-12-21 10:56:52');

-- --------------------------------------------------------

--
-- Table structure for table `nationalities_translations`
--

CREATE TABLE `nationalities_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nationality_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `sub_title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nationalities_translations`
--

INSERT INTO `nationalities_translations` (`id`, `nationality_id`, `title`, `sub_title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'السعودية', 'سعودي', 'ar', '2023-12-21 10:56:52', '2023-12-21 11:04:01'),
(2, 1, 'Saudi Arabia', 'Saudi', 'en', '2023-12-21 10:56:52', '2023-12-21 10:56:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(1000) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `order_id`, `user_id`, `title`, `description`, `type`, `created_at`, `updated_at`) VALUES
(7, NULL, 1, '{\"ar\":\"\\u0647\\u0644\\u0627 \\u0628\\u0643\\u0645\",\"en\":\"Hello world\"}', '{\"ar\":\"\\u0627\\u0647\\u0644\\u0627 \\u0628\\u0643\\u0645 \\u0641\\u064a \\u0627\\u0644\\u062a\\u0637\\u0628\\u064a\\u0642 \\u0645\\u0639\\u0646\\u0627\",\"en\":\"welcome to our app\"}', 'general', '2023-12-26 17:23:48', '2023-12-26 17:23:48'),
(8, NULL, 6, '{\"ar\":\"\\u0627\\u0647\\u0644\\u0627 \\u0628\\u0643\\u0645\",\"en\":\"Hello\"}', '{\"ar\":\"\\u0645\\u0631\\u062d\\u0628\\u0627 \\u0628\\u0627\\u0644\\u062c\\u0645\\u064a\\u0639\",\"en\":\"Hello everyone\"}', 'general', '2023-12-26 18:09:19', '2023-12-26 18:09:19'),
(10, NULL, 6, '{\"ar\":\"Dolorem accusamus in\",\"en\":\"Fugit quam officiis\"}', '{\"ar\":\"Est magna maxime en\",\"en\":\"Nobis sit voluptate\"}', 'general', '2023-12-26 18:53:19', '2023-12-26 18:53:19'),
(23, 13, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"ingaz.notifications.There is a an update in your order status13\",\"ar\":\"ingaz.notifications.There is a an update in your order status13\"}', 'order_status', '2023-12-28 12:00:37', '2023-12-28 12:00:37'),
(24, 13, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"The status has been changed to 13\",\"ar\":\" \\u062a\\u0645 \\u062a\\u063a\\u064a\\u064a\\u0631 \\u0627\\u0644\\u062d\\u0627\\u0644\\u0629 \\u0627\\u0644\\u0649 13\"}', 'order_status', '2023-12-28 12:02:36', '2023-12-28 12:02:36'),
(25, 14, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"The status has been changed  14\",\"ar\":\" \\u062a\\u0645 \\u062a\\u063a\\u064a\\u064a\\u0631 \\u062d\\u0627\\u0644\\u0629 \\u0627\\u0644\\u0637\\u0644\\u0628 14\"}', 'order_status', '2023-12-28 12:03:36', '2023-12-28 12:03:36'),
(26, 16, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"There is a new order number 16\",\"ar\":\" \\u064a\\u0648\\u062c\\u062f \\u0637\\u0644\\u0628 \\u062c\\u062f\\u064a\\u062f \\u0631\\u0642\\u064516\"}', 'new_order', '2023-12-28 12:32:50', '2023-12-28 12:32:50'),
(27, 17, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"There is a new order number 17\",\"ar\":\" \\u064a\\u0648\\u062c\\u062f \\u0637\\u0644\\u0628 \\u062c\\u062f\\u064a\\u062f \\u0631\\u0642\\u064517\"}', 'new_order', '2023-12-28 12:32:55', '2023-12-28 12:32:55'),
(28, 18, 8, '{\"en\":\"Asus Al-Injaz\",\"ar\":\"\\u0623\\u0633\\u0633 \\u0627\\u0644\\u0627\\u0646\\u062c\\u0627\\u0632\"}', '{\"en\":\"There is a new order number 18\",\"ar\":\" \\u064a\\u0648\\u062c\\u062f \\u0637\\u0644\\u0628 \\u062c\\u062f\\u064a\\u062f \\u0631\\u0642\\u064518\"}', 'new_order', '2023-12-28 12:34:05', '2023-12-28 12:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `maid_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(255) NOT NULL,
  `job_type_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method` varchar(500) NOT NULL DEFAULT 'cash',
  `status` varchar(255) NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `maid_id`, `user_id`, `order_code`, `job_type_id`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES
(12, 6, 7, '99986', 1, 'cash', 'paid', '2023-12-27 11:50:24', '2023-12-28 11:10:24'),
(13, 6, 8, '70096', 1, 'cash', 'done', '2023-12-28 10:58:22', '2023-12-28 12:02:37'),
(14, 6, 8, '31841', 1, 'cash', 'paid', '2023-12-28 10:58:29', '2023-12-28 12:03:37'),
(15, 7, 8, '34047', 2, 'online', 'new', '2023-12-28 11:00:33', '2023-12-28 11:00:33'),
(16, 6, 8, '56753', 1, 'cash', 'new', '2023-12-28 12:32:50', '2023-12-28 12:32:50'),
(17, 6, 8, '84535', 1, 'cash', 'new', '2023-12-28 12:32:55', '2023-12-28 12:32:55'),
(18, 7, 8, '69187', 2, 'online', 'new', '2023-12-28 12:34:05', '2023-12-28 12:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `address_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `admin_id`, `package_id`, `address_id`, `created_at`, `updated_at`) VALUES
(11, 12, 26, NULL, NULL, '2023-12-27 11:50:24', '2023-12-27 11:50:24'),
(12, 13, 25, NULL, NULL, '2023-12-28 10:58:23', '2023-12-28 10:58:23'),
(13, 14, 25, NULL, NULL, '2023-12-28 10:58:29', '2023-12-28 10:58:29'),
(14, 15, NULL, 2, 2, '2023-12-28 11:00:33', '2023-12-28 11:00:33'),
(15, 16, 25, NULL, NULL, '2023-12-28 12:32:50', '2023-12-28 12:32:50'),
(16, 17, 25, NULL, NULL, '2023-12-28 12:32:55', '2023-12-28 12:32:55'),
(17, 18, NULL, 5, 2, '2023-12-28 12:34:05', '2023-12-28 12:34:05');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `original_price` double NOT NULL,
  `price_after_discount` double NOT NULL,
  `price_with_tax` double NOT NULL,
  `days_count` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `original_price`, `price_after_discount`, `price_with_tax`, `days_count`, `created_at`, `updated_at`) VALUES
(1, 600, 550, 590, 30, '2023-12-25 09:01:55', '2023-12-25 09:01:55'),
(2, 1000, 900, 990, 60, '2023-12-25 09:02:35', '2023-12-25 09:02:35'),
(3, 1400, 1320, 1466, 90, '2023-12-25 09:03:08', '2023-12-25 09:03:08'),
(5, 2900, 2500, 2750, 160, '2023-12-25 09:04:30', '2023-12-25 09:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `packages_translations`
--

CREATE TABLE `packages_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
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
(10, 5, 'Super premium package', 'en', '2023-12-25 09:04:30', '2023-12-25 09:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'users-create', 'Create Users', 'Create Users', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(2, 'users-read', 'Read Users', 'Read Users', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(3, 'users-update', 'Update Users', 'Update Users', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(4, 'users-delete', 'Delete Users', 'Delete Users', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(5, 'settings-create', 'Create Settings', 'Create Settings', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(6, 'settings-read', 'Read Settings', 'Read Settings', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(7, 'settings-update', 'Update Settings', 'Update Settings', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(8, 'settings-delete', 'Delete Settings', 'Delete Settings', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(9, 'job-titles-create', 'Create Job-titles', 'Create Job-titles', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(10, 'job-titles-read', 'Read Job-titles', 'Read Job-titles', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(11, 'job-titles-update', 'Update Job-titles', 'Update Job-titles', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(12, 'job-titles-delete', 'Delete Job-titles', 'Delete Job-titles', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(13, 'nationalities-create', 'Create Nationalities', 'Create Nationalities', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(14, 'nationalities-read', 'Read Nationalities', 'Read Nationalities', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(15, 'nationalities-update', 'Update Nationalities', 'Update Nationalities', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(16, 'nationalities-delete', 'Delete Nationalities', 'Delete Nationalities', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(17, 'sliders-create', 'Create Sliders', 'Create Sliders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(18, 'sliders-read', 'Read Sliders', 'Read Sliders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(19, 'sliders-update', 'Update Sliders', 'Update Sliders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(20, 'sliders-delete', 'Delete Sliders', 'Delete Sliders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(21, 'religions-create', 'Create Religions', 'Create Religions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(22, 'religions-read', 'Read Religions', 'Read Religions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(23, 'religions-update', 'Update Religions', 'Update Religions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(24, 'religions-delete', 'Delete Religions', 'Delete Religions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(25, 'questions-create', 'Create Questions', 'Create Questions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(26, 'questions-read', 'Read Questions', 'Read Questions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(27, 'questions-update', 'Update Questions', 'Update Questions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(28, 'questions-delete', 'Delete Questions', 'Delete Questions', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(29, 'contact-create', 'Create Contact', 'Create Contact', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(30, 'contact-read', 'Read Contact', 'Read Contact', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(31, 'contact-update', 'Update Contact', 'Update Contact', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(32, 'contact-delete', 'Delete Contact', 'Delete Contact', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(33, 'services-create', 'Create Services', 'Create Services', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(34, 'services-read', 'Read Services', 'Read Services', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(35, 'services-update', 'Update Services', 'Update Services', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(36, 'services-delete', 'Delete Services', 'Delete Services', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(37, 'about-us-create', 'Create About-us', 'Create About-us', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(38, 'about-us-read', 'Read About-us', 'Read About-us', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(39, 'about-us-update', 'Update About-us', 'Update About-us', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(40, 'about-us-delete', 'Delete About-us', 'Delete About-us', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(41, 'clients-create', 'Create Clients', 'Create Clients', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(42, 'clients-read', 'Read Clients', 'Read Clients', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(43, 'clients-update', 'Update Clients', 'Update Clients', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(44, 'clients-delete', 'Delete Clients', 'Delete Clients', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(45, 'maids-create', 'Create Maids', 'Create Maids', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(46, 'maids-read', 'Read Maids', 'Read Maids', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(47, 'maids-update', 'Update Maids', 'Update Maids', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(48, 'maids-delete', 'Delete Maids', 'Delete Maids', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(49, 'packages-create', 'Create Packages', 'Create Packages', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(50, 'packages-read', 'Read Packages', 'Read Packages', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(51, 'packages-update', 'Update Packages', 'Update Packages', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(52, 'packages-delete', 'Delete Packages', 'Delete Packages', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(53, 'complaints-create', 'Create Complaints', 'Create Complaints', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(54, 'complaints-read', 'Read Complaints', 'Read Complaints', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(55, 'complaints-update', 'Update Complaints', 'Update Complaints', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(56, 'complaints-delete', 'Delete Complaints', 'Delete Complaints', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(57, 'orders-create', 'Create Orders', 'Create Orders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(58, 'orders-read', 'Read Orders', 'Read Orders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(59, 'orders-update', 'Update Orders', 'Update Orders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(60, 'orders-delete', 'Delete Orders', 'Delete Orders', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(61, 'notifications-create', 'Create Notifications', 'Create Notifications', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(62, 'notifications-read', 'Read Notifications', 'Read Notifications', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(63, 'notifications-update', 'Update Notifications', 'Update Notifications', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(64, 'notifications-delete', 'Delete Notifications', 'Delete Notifications', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(65, 'statistics-create', 'Create Statistics', 'Create Statistics', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(66, 'statistics-read', 'Read Statistics', 'Read Statistics', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(67, 'statistics-update', 'Update Statistics', 'Update Statistics', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(68, 'statistics-delete', 'Delete Statistics', 'Delete Statistics', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(69, 'steps-create', 'Create Steps', 'Create Steps', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(70, 'steps-read', 'Read Steps', 'Read Steps', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(71, 'steps-update', 'Update Steps', 'Update Steps', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(72, 'steps-delete', 'Delete Steps', 'Delete Steps', '2023-12-28 10:04:26', '2023-12-28 10:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
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
(29, 3),
(30, 1),
(30, 3),
(31, 1),
(31, 3),
(32, 1),
(32, 3),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
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
(53, 3),
(54, 1),
(54, 3),
(55, 1),
(55, 3),
(56, 1),
(56, 3),
(57, 1),
(57, 3),
(58, 1),
(58, 3),
(59, 1),
(59, 3),
(60, 1),
(60, 3),
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
(72, 1);

-- --------------------------------------------------------

--
-- Table structure for table `permission_user`
--

CREATE TABLE `permission_user` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phone_verifications`
--

CREATE TABLE `phone_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone_code` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phone_verifications`
--

INSERT INTO `phone_verifications` (`id`, `phone_code`, `phone`, `code`, `created_at`, `updated_at`) VALUES
(1, '+966', '599888888', '61006', '2023-12-26 17:26:03', '2023-12-26 17:26:03'),
(2, '+966', '554979797', '82673', '2023-12-27 11:25:11', '2023-12-27 11:25:11'),
(3, '+966', '597878787', '50377', '2023-12-28 08:27:26', '2023-12-28 08:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `print_details_translations`
--

CREATE TABLE `print_details_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `print_details_id` bigint(20) UNSIGNED NOT NULL,
  `locale` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting`
--

CREATE TABLE `print_setting` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `logo` varchar(255) NOT NULL DEFAULT 'default.png',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting_details`
--

CREATE TABLE `print_setting_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `print_setting_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('first','second') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `print_setting_translations`
--

CREATE TABLE `print_setting_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `print_setting_id` bigint(20) UNSIGNED NOT NULL,
  `locale` varchar(255) NOT NULL,
  `first_title` varchar(255) NOT NULL,
  `second_title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `is_show`, `created_at`, `updated_at`) VALUES
(1, 1, '2023-12-21 13:56:46', '2023-12-21 14:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `questions_translations`
--

CREATE TABLE `questions_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions_translations`
--

INSERT INTO `questions_translations` (`id`, `question_id`, `title`, `description`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'ما هو دور \"شركة اسس الانجاز للاستقدام\" بالتحديد؟', '\"شركة اسس الانجاز للاستقدام\" هي شركة رائدة في مجال توفير خدمات استقدام العمالة الوافدة، تهدف إلى تلبية احتياجات الشركات والأفراد بتوفير الكفاءات البشرية المناسبة والمؤهلة.', 'ar', '2023-12-21 13:56:46', '2023-12-21 13:56:46'),
(2, 1, '? What is the role of \"Al-Injaz Establishment for Recruitment Company\" specifically', 'Al-Injaz Establishment for Recruitment Company\" is a leading company in the field of providing services for the recruitment of foreign labor. It aims to meet the needs of both companies and individuals by supplying suitable and qualified human resources', 'en', '2023-12-21 13:56:46', '2023-12-21 13:56:46');

-- --------------------------------------------------------

--
-- Table structure for table `religions`
--

CREATE TABLE `religions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `is_show` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `religions`
--

INSERT INTO `religions` (`id`, `is_show`, `created_at`, `updated_at`) VALUES
(1, 1, '2023-12-21 12:31:16', '2023-12-21 13:38:17'),
(2, 0, '2023-12-21 12:36:17', '2023-12-21 13:43:36'),
(3, 1, '2023-12-21 13:40:24', '2023-12-21 13:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `religions_translations`
--

CREATE TABLE `religions_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `religion_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `religions_translations`
--

INSERT INTO `religions_translations` (`id`, `religion_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'مسلم', 'ar', '2023-12-21 12:31:16', '2023-12-21 12:31:16'),
(2, 1, 'muslim', 'en', '2023-12-21 12:31:16', '2023-12-21 12:31:16'),
(3, 2, 'بوزي', 'ar', '2023-12-21 12:36:17', '2023-12-21 12:36:17'),
(4, 2, 'Bozi', 'en', '2023-12-21 12:36:17', '2023-12-21 12:36:17'),
(5, 3, 'مسيحي', 'ar', '2023-12-21 13:40:24', '2023-12-21 13:40:24'),
(6, 3, 'Cristian', 'en', '2023-12-21 13:40:24', '2023-12-21 13:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Admin', 'Super Admin', '2023-12-28 10:04:25', '2023-12-28 10:04:25'),
(2, 'admin', 'Admin', 'Admin', '2023-12-28 10:04:26', '2023-12-28 10:04:26'),
(3, 'customer_service', 'Customer Service', 'Customer Service', '2023-12-28 10:04:26', '2023-12-28 10:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`role_id`, `user_id`, `user_type`) VALUES
(1, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'images/Service/4AckSGtdAS1703165868.svg', '2023-12-21 14:37:48', '2023-12-21 14:37:48'),
(2, 'images/Service/5VOTrfctHV1703399851.svg', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(3, 'images/Service/VUbMxfl4f61703399908.svg', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(4, 'images/Service/1MxqSHLmow1703399945.svg', '2023-12-24 07:39:05', '2023-12-24 07:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `services_translations`
--

CREATE TABLE `services_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services_translations`
--

INSERT INTO `services_translations` (`id`, `service_id`, `title`, `description`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'استقدام الطهاة المحترفين', 'توفير طهاة محترفين لتحضير وجبات شهية ومتنوعة في منازل العملاء.', 'ar', '2023-12-21 14:37:48', '2023-12-21 14:37:48'),
(2, 1, 'Recruiting professional chefs.', 'Providing professional chefs to prepare delicious and diverse meals at clients\' homes', 'en', '2023-12-21 14:37:48', '2023-12-21 14:37:48'),
(3, 2, 'خدمات التنظيف والصيانة', 'توفير عمالة للقيام بأعمال التنظيف الشامل والصيانة الدورية للمنازل.', 'ar', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(4, 2, 'Cleaning and maintenance services', 'Providing manpower for comprehensive cleaning and regular maintenance services for homes.', 'en', '2023-12-24 07:37:31', '2023-12-24 07:37:31'),
(5, 3, 'رعاية الأطفال والمسنين', 'توفير جليسات الأطفال ورعاية وكبار السن.', 'ar', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(6, 3, 'Child and Elderly Care', 'Providing babysitting and elderly care services.', 'en', '2023-12-24 07:38:28', '2023-12-24 07:38:28'),
(7, 4, 'خدمات الضيافة والخدم', 'توفير موظفين لخدمة الضيوف في المناسبات والفعاليات.', 'ar', '2023-12-24 07:39:05', '2023-12-24 07:39:05'),
(8, 4, 'Hospitality and Services', 'Providing staff for guest service at events and gatherings.', 'en', '2023-12-24 07:39:05', '2023-12-24 07:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `logo_header` varchar(255) NOT NULL DEFAULT 'logo_header.png',
  `logo_footer` varchar(255) NOT NULL DEFAULT 'logo_footer.png',
  `favicon` varchar(255) NOT NULL DEFAULT 'favicon.png',
  `twitter` varchar(255) NOT NULL DEFAULT '#',
  `facebook` varchar(255) NOT NULL DEFAULT '#',
  `instagram` varchar(255) NOT NULL DEFAULT '#',
  `snapchat` varchar(255) NOT NULL DEFAULT '#',
  `linkedin` varchar(255) NOT NULL DEFAULT '#',
  `youtube` varchar(255) NOT NULL DEFAULT '#',
  `whatsapp` varchar(255) NOT NULL DEFAULT '#',
  `email` varchar(255) NOT NULL DEFAULT '#',
  `phone` varchar(255) NOT NULL DEFAULT '#',
  `about_image` varchar(255) NOT NULL,
  `other_phone` varchar(255) NOT NULL DEFAULT '#',
  `map_link` varchar(255) NOT NULL DEFAULT '#',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `logo_header`, `logo_footer`, `favicon`, `twitter`, `facebook`, `instagram`, `snapchat`, `linkedin`, `youtube`, `whatsapp`, `email`, `phone`, `about_image`, `other_phone`, `map_link`, `created_at`, `updated_at`) VALUES
(1, 'images/admins/settings/Zdpg85z4G71703405515.svg', 'images/admins/settings/YP1PNBxcVE1703405515.svg', 'images/admins/settings/BK5gaOUvvs1703609739.webp.svg', 'Laboris non nulla ea', 'Modi minus ex dicta', 'Explicabo Ipsam ven', 'Culpa doloribus exc', 'Harum autem nisi et', 'Ad cillum incidunt', 'Aliquam impedit ear', 'xytyf@mailinator.com', '+1 (565) 301-5646', 'images/settings/EWhfBBXVFR1703609784.webp', '+1 (999) 942-3547', 'Quae culpa beatae c', '2023-12-24 08:58:17', '2023-12-26 17:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `settings_translations`
--

CREATE TABLE `settings_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_id` bigint(20) UNSIGNED NOT NULL,
  `locale` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `privacy` varchar(1000) NOT NULL DEFAULT '',
  `rights_maids` varchar(10000) NOT NULL,
  `rights_users` varchar(1000) NOT NULL,
  `copyright` varchar(1000) NOT NULL DEFAULT '',
  `website_name` varchar(255) NOT NULL,
  `about_us_sub_title` varchar(255) NOT NULL DEFAULT '',
  `footer_text` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings_translations`
--

INSERT INTO `settings_translations` (`id`, `setting_id`, `locale`, `location`, `privacy`, `rights_maids`, `rights_users`, `copyright`, `website_name`, `about_us_sub_title`, `footer_text`, `created_at`, `updated_at`) VALUES
(1, 1, 'ar', 'اليرموك، الرياض 13251، المملكة العربية السعودية', '<h2 style=\"text-align: right;\">صفحة سياسة الخصوصية لموقع اسس الانجاز</h2>\r\n<p style=\"text-align: right;\">&nbsp;</p>\r\n<p style=\"text-align: right;\">**سياسة الخصوصية لموقع اسس الانجاز**</p>\r\n<p style=\"text-align: right;\">أخر تحديث:&nbsp;</p>\r\n<p style=\"text-align: right;\">نرحب بكم في موقعنا \"اسس الانجاز\". تلتزم الشركة بحماية خصوصيتكم ومعلوماتكم الشخصية. يرجى قراءة بيان الخصوصية التالي لفهم كيفية جمع واستخدام وحماية المعلومات التي تقدمونها لنا.</p>\r\n<p style=\"text-align: right;\">**1. المعلومات التي نقوم بجمعها:**</p>\r\n<p style=\"text-align: right;\">نقوم بجمع المعلومات التي تقدمونها لنا عند استخدام موقعنا، وذلك عندما تسجلون حسابًا، أو تقديم طلب، أو تتفاعلون مع المحتوى الخاص بنا. يمكن أن تشمل هذه المعلومات الاسم، والبريد الإلكتروني، ورقم الهاتف، ومعلومات أخرى ذات صلة.</p>\r\n<p style=\"text-align: right;\">**2. كيفية استخدام المعلومات:**</p>\r\n<p style=\"text-align: right;\">نستخدم المعلومات التي نقوم بجمعها لتقديم الخدمات والدعم للمستخدمين، ولتحسين تجربة المستخدم، ولضمان أمان المعلومات.</p>', '<p style=\"text-align: right;\">. <strong>حقوق صاحب العمل</strong></p>\r\n<p style=\"text-align: right;\">١. <strong>حق الراتب:</strong> الحق في استلام الراتب والأجور بشكل دوري وفقًا للاتفاق المتفق عليه.</p>\r\n<p style=\"text-align: right;\">٢. <strong>حق الإجازات:</strong> الحق في الاستفادة من إجازات مدفوعة الأجر وفقًا للقوانين العمالية.</p>\r\n<p style=\"text-align: right;\">٣. <strong>حق الحماية الصحية:</strong> الحق في التأمين الصحي والرعاية الطبية خلال فترة العمل.</p>\r\n<p style=\"text-align: right;\">٤. <strong>حق السلامة والأمان:</strong> الحق في بيئة عمل آمنة وخالية من المخاطر الصحية والسلامة.</p>\r\n<p style=\"text-align: right;\">٥. <strong>حق التمييز:</strong> الحق في عدم تعرضهم للتمييز أو المضايقة بناءً على الجنس أو العرق أو الدين أو اللون أو العمر.</p>\r\n<p style=\"text-align: right;\">٦. <strong>حق الاستراحة:</strong> الحق في فترات استراحة منتظمة خلال فترات العمل.</p>\r\n<p style=\"text-align: right;\"><strong>واجبات صاحب العمل</strong></p>\r\n<p style=\"text-align: right;\">١. <strong>واجب الالتزام بالعقد:</strong> الالتزام بجميع بنود وشروط العقد الذي يحدد العلاقة بين العامل وصاحب العمل.</p>\r\n<p style=\"text-align: right;\">٢. <strong>واجب الالتزام بالمواعيد:</strong> الالتزام بالحضور في مواعيد العمل المتفق عليها وتنبيه صاحب العمل في حالة التأخر.</p>\r\n<p style=\"text-align: right;\">٣. <strong>واجب الأداء الجيد:</strong> الالتزام بأداء العمل بشكل جيد وفعّال وفقًا لمتطلبات الوظيفة.</p>\r\n<p style=\"text-align: right;\">٤. <strong>واجب السرية:</strong> الالتزام بالسرية فيما يتعلق بالمعلومات التجارية أو السرية التي يتم الوصول إليها خلال العمل</p>', '<p style=\"text-align: right;\">. <strong>حقوق صاحب العمل</strong></p>\r\n<p style=\"text-align: right;\">١. <strong>حق الراتب:</strong> الحق في استلام الراتب والأجور بشكل دوري وفقًا للاتفاق المتفق عليه.</p>\r\n<p style=\"text-align: right;\">٢. <strong>حق الإجازات:</strong> الحق في الاستفادة من إجازات مدفوعة الأجر وفقًا للقوانين العمالية.</p>\r\n<p style=\"text-align: right;\">٣. <strong>حق الحماية الصحية:</strong> الحق في التأمين الصحي والرعاية الطبية خلال فترة العمل.</p>\r\n<p style=\"text-align: right;\">٤. <strong>حق السلامة والأمان:</strong> الحق في بيئة عمل آمنة وخالية من المخاطر الصحية والسلامة.</p>\r\n<p style=\"text-align: right;\">٥. <strong>حق التمييز:</strong> الحق في عدم تعرضهم للتمييز أو المضايقة بناءً على الجنس أو العرق أو الدين أو اللون أو العمر.</p>\r\n<p style=\"text-align: right;\">٦. <strong>حق الاستراحة:</strong> الحق في فترات استراحة منتظمة خلال فترات العمل.</p>\r\n<p style=\"text-align: right;\"><strong>واجبات صاحب العمل</strong></p>\r\n<p style=\"text-align: right;\">١. <strong>واجب الالتزام با', 'شركة اسس الانجاز للاستقدام ❤️', 'اسس الانجاز', 'يمكنك الأن استقدام عمالتك لفترات محدودة ماذا تنتظر!؟', 'مرحبًا بك في موقع شركة اسس الانجاز للاستقدام. نقدم خدمات الاستقدام بكفاءة عالية وتنوع. اكتشف خدماتنا وجعل حياتك المنزلية أسهل. للتواصل، اتصل بنا اليوم.', '2023-12-24 08:58:17', '2023-12-28 10:44:22'),
(2, 1, 'en', 'Al Yarmouk, Riyadh 13251, Kingdom of Saudi Arabia', '<p><strong>Privacy Policy for Achieve Recruitment Website</strong></p>\r\n<p>Last Updated: [1/1/2024]</p>\r\n<p>Welcome to our website \"Achieve Recruitment.\" The company is committed to protecting your privacy and personal information. Please read the following privacy policy to understand how we collect, use, and protect the information you provide to us.</p>\r\n<p><strong>1. Information We Collect:</strong></p>\r\n<p>We collect information you provide to us when using our website, such as when you register an account, submit an application, or interact with our content. This information may include names, email addresses, phone numbers, and other relevant details.</p>\r\n<p><strong>2. How We Use the Information:</strong></p>\r\n<p>We use the collected information to provide services and support to users, improve the user experience, and ensure the security of the information.</p>\r\n<p><strong>3. Information Sharing:</strong></p>\r\n<p>We do not share your personal information with th</p>', '<ol>\r\n<li>\r\n<p><strong>Employer\'s Rights:</strong></p>\r\n<ol>\r\n<li>\r\n<p><strong>Salary Right:</strong> The right to receive a salary and wages regularly according to the agreed-upon agreement.</p>\r\n</li>\r\n<li>\r\n<p><strong>Vacation Right:</strong> The right to benefit from paid leave in accordance with labor laws.</p>\r\n</li>\r\n<li>\r\n<p><strong>Health Protection Right:</strong> The right to health insurance and medical care during the employment period.</p>\r\n</li>\r\n<li>\r\n<p><strong>Safety and Security Right:</strong> The right to a safe working environment free from health and safety risks.</p>\r\n</li>\r\n<li>\r\n<p><strong>Non-Discrimination Right:</strong> The right not to be subjected to discrimination or harassment based on gender, race, religion, color, or age.</p>\r\n</li>\r\n<li>\r\n<p><strong>Rest Break Right:</strong> The right to regular rest breaks during working hours.</p>\r\n</li>\r\n</ol>\r\n<p><strong>Employer\'s Duties:</strong></p>\r\n<ol>\r\n<li>\r\n<p><strong>Contractual Commitment Duty:</strong> The commitment to all terms and conditions of the contract that defines the relationship between the worker and the employer.</p>\r\n</li>\r\n<li>\r\n<p><strong>Punctuality Duty:</strong> The commitment to attend work at agreed-upon times and notifying the employer in case of delay.</p>\r\n</li>\r\n<li>\r\n<p><strong>Good Performance Duty:</strong> The commitment to perform the job well and effectively according to job requirements.</p>\r\n</li>\r\n<li>\r\n<p><strong>Confidentiality Duty:</strong> The commitment to confidentiality regarding trade or confidential information accessed during work.</p>\r\n</li>\r\n</ol>\r\n</li>\r\n</ol>', '<ol>\r\n<li style=\"line-height: 1.2;\">\r\n<p style=\"line-height: 1.2;\"><strong>Employer\'s Rights:</strong></p>\r\n<ol>\r\n<li>\r\n<p><strong>Salary Right:</strong> The right to receive a salary and wages regularly according to the agreed-upon agreement.</p>\r\n</li>\r\n<li>\r\n<p><strong>Vacation Right:</strong> The right to benefit from paid leave in accordance with labor laws.</p>\r\n</li>\r\n<li>\r\n<p><strong>Health Protection Right:</strong> The right to health insurance and medical care during the employment period.</p>\r\n</li>\r\n<li>\r\n<p><strong>Safety and Security Right:</strong> The right to a safe working environment free from health and safety risks.</p>\r\n</li>\r\n<li>\r\n<p><strong>Non-Discrimination Right:</strong> The right not to be subjected to discrimination or harassment based on gender, race, religion, color, or age.</p>\r\n</li>\r\n<li>\r\n<p><strong>Rest Break Right:</strong> The right to regular rest breaks during working hours.</p>\r\n</li>\r\n</ol>\r\n<p style=\"line-height: 1.2;\"><strong>Employer\'s Du', '❤️ Asas Al-Injaaz', 'Asas Al-Injaaz', '\"You can now recruit your workforce for limited periods. What are you waiting for!?\"', 'Welcome to the Achieve Recruitment Company website. We offer efficient and diverse recruitment services. Explore our services and make your home life easier. For inquiries, contact us today.', '2023-12-24 08:58:17', '2023-12-28 10:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'images/Slider/xrHrTbWTZp1703156017.jpg', '2023-12-21 11:53:37', '2023-12-21 11:53:37'),
(2, 'images/Slider/xjYNQU1ufs1703156538.png', '2023-12-21 12:02:18', '2023-12-21 12:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `sliders_translations`
--

CREATE TABLE `sliders_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slider_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders_translations`
--

INSERT INTO `sliders_translations` (`id`, `slider_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'اعلان شركة الانشاءات', 'ar', '2023-12-21 12:01:56', '2023-12-21 12:01:56'),
(2, 1, 'Ad For Company', 'en', '2023-12-21 12:01:56', '2023-12-21 12:01:56');

-- --------------------------------------------------------

--
-- Table structure for table `statistics`
--

CREATE TABLE `statistics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `counter_num` varchar(255) NOT NULL,
  `counter_unit` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics`
--

INSERT INTO `statistics` (`id`, `image`, `counter_num`, `counter_unit`, `created_at`, `updated_at`) VALUES
(3, 'fa fa-child', '50', 'K', '2023-12-28 09:36:45', '2023-12-28 10:02:15'),
(4, 'fa fa-check', '30', 'K', '2023-12-28 09:42:46', '2023-12-28 09:58:19'),
(5, 'fa fa-bullhorn', '60', '', '2023-12-28 10:00:04', '2023-12-28 10:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `statistics_translations`
--

CREATE TABLE `statistics_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `statistic_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics_translations`
--

INSERT INTO `statistics_translations` (`id`, `statistic_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(5, 3, 'عميل راضي عن الخدمة', 'ar', '2023-12-28 09:36:45', '2023-12-28 09:36:45'),
(6, 3, 'Happy Clients', 'en', '2023-12-28 09:36:45', '2023-12-28 09:36:45'),
(7, 4, 'عقود استقدام', 'ar', '2023-12-28 09:42:46', '2023-12-28 09:56:47'),
(8, 4, 'completed tasks', 'en', '2023-12-28 09:42:46', '2023-12-28 09:56:47'),
(9, 5, 'طلبات', 'ar', '2023-12-28 10:00:04', '2023-12-28 10:00:04'),
(10, 5, 'orders', 'en', '2023-12-28 10:00:04', '2023-12-28 10:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `steps`
--

CREATE TABLE `steps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `icon` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `steps`
--

INSERT INTO `steps` (`id`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'images/Step/lObTeyeIeN1703754873.svg', '2023-12-28 10:14:33', '2023-12-28 10:14:33'),
(7, 'images/Step/kAPg5pjK8C1703755370.svg', '2023-12-28 10:22:50', '2023-12-28 10:22:50'),
(8, 'images/Step/tEC2aHkvcM1703755450.svg', '2023-12-28 10:24:10', '2023-12-28 10:24:10'),
(9, 'images/Step/vDSHpDmBig1703755514.svg', '2023-12-28 10:25:14', '2023-12-28 10:25:14'),
(10, 'images/Step/FV2cKnl5qY1703755615.svg', '2023-12-28 10:26:55', '2023-12-28 10:26:55'),
(11, 'images/Step/oZgItGIK281703755686.svg', '2023-12-28 10:28:06', '2023-12-28 10:28:06'),
(12, 'images/Step/K7XSAfqZkB1703755730.svg', '2023-12-28 10:28:50', '2023-12-28 10:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `steps_translations`
--

CREATE TABLE `steps_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `step_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `steps_translations`
--

INSERT INTO `steps_translations` (`id`, `step_id`, `title`, `locale`, `created_at`, `updated_at`) VALUES
(1, 1, 'سداد رسوم تاشيرة العمالة', 'ar', '2023-12-28 10:14:33', '2023-12-28 10:14:33'),
(2, 1, 'Paying the work visa fees', 'en', '2023-12-28 10:14:33', '2023-12-28 10:14:33'),
(13, 7, 'اختيار السيرة الذاتية للعمالة', 'ar', '2023-12-28 10:22:50', '2023-12-28 10:22:50'),
(14, 7, 'Selecting the resume for employment', 'en', '2023-12-28 10:22:50', '2023-12-28 10:22:50'),
(15, 8, 'متابعة حالة الطلب', 'ar', '2023-12-28 10:24:10', '2023-12-28 10:24:10'),
(16, 8, 'order track', 'en', '2023-12-28 10:24:10', '2023-12-28 10:24:10'),
(17, 9, 'ضمان 3 شهور', 'ar', '2023-12-28 10:25:14', '2023-12-28 10:25:14'),
(18, 9, '3-month warranty', 'en', '2023-12-28 10:25:14', '2023-12-28 10:25:14'),
(19, 10, 'طلب اصدار تاشيرة العمالة', 'ar', '2023-12-28 10:26:55', '2023-12-28 10:26:55'),
(20, 10, 'Request for the issuance of a work visa', 'en', '2023-12-28 10:26:55', '2023-12-28 10:26:55'),
(21, 11, 'دفع مبلغ وتوثيق الطلب', 'ar', '2023-12-28 10:28:06', '2023-12-28 10:28:06'),
(22, 11, 'Paying the amount and authenticating the request', 'en', '2023-12-28 10:28:07', '2023-12-28 10:28:07'),
(23, 12, 'تسليم العمالة الى العميل', 'ar', '2023-12-28 10:28:50', '2023-12-28 10:28:50'),
(24, 12, 'Handover of the workforce to the client', 'en', '2023-12-28 10:28:50', '2023-12-28 10:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone_code` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_blocked` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 is not blocked',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone_code`, `phone`, `password`, `is_blocked`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'محمود الكومي', '+966', '65587548', '$!asdasd8e883248237498wjdkaiu@0d9dfsdf', 0, NULL, '2023-12-24 11:35:48', '2023-12-24 12:53:07'),
(5, 'احمد مصطفي', '+20', '05487879', '#234eoojd73128flkjsdf', 1, NULL, '2023-12-24 11:51:40', '2023-12-24 12:53:52'),
(6, 'احمد طارق عباس يحيي', '+966', '599888888', '$2y$10$4qpfZ7UiF/uJDxkqMLzLlO5wHsN17PGLEbP0aDu7K2iJyOlYri7qa', 0, NULL, '2023-11-26 17:26:28', '2023-12-26 17:26:28'),
(7, 'هادي محمد خالد', '+966', '554979797', '$2y$10$FOoRA.P5PZGHFcrD3ZhuA.iQvoZ7JgQYdeJwH5bh7jfoMRdsuZihO', 0, NULL, '2023-12-27 11:27:14', '2023-12-27 11:27:14'),
(8, 'سليمان بن محمد', '+966', '597878787', '$2y$10$VUYg7P0Qam1njoWcJEu3JOEgko.iNSFE1NoJcrDXBMLEiHqDFUcXK', 0, NULL, '2023-12-28 08:28:03', '2023-12-28 08:28:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `address` varchar(500) NOT NULL,
  `title` varchar(500) NOT NULL,
  `phone` varchar(500) NOT NULL,
  `name` varchar(500) NOT NULL,
  `notes` varchar(1000) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address`, `title`, `phone`, `name`, `notes`, `created_at`, `updated_at`) VALUES
(1, 6, 'Sit id in ea obcaeca', 'Kellie Shelton', '+1 (296) 167-8264', 'Keely Bender', 'Ipsa et excepturi q', '2023-12-26 18:58:15', '2023-12-26 18:58:15'),
(2, 8, 'Aliquip excepturi se', 'Ronan Horn', '55989795', 'Hillary Paul', 'Molestiae consequatu', '2023-12-28 10:59:00', '2023-12-28 10:59:00'),
(3, 8, 'Dolorem aut ut quasi', 'Omar Reilly', '+1 (539) 741-7486', 'Orson Wiggins', 'Quasi voluptatibus u', '2023-12-28 12:33:17', '2023-12-28 12:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_firebase_tokens`
--

CREATE TABLE `user_firebase_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `software_type` varchar(255) NOT NULL,
  `phone_token` varchar(255) NOT NULL,
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
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaints_user_id_foreign` (`user_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cookies`
--
ALTER TABLE `cookies`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `maids_religion_id_foreign` (`religion_id`);

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
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `orders_job_type_id_foreign` (`job_type_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_details_order_id_foreign` (`order_id`),
  ADD KEY `order_details_admin_id_foreign` (`admin_id`),
  ADD KEY `order_details_package_id_foreign` (`package_id`),
  ADD KEY `order_details_address_id_foreign` (`address_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `permission_user`
--
ALTER TABLE `permission_user`
  ADD PRIMARY KEY (`user_id`,`permission_id`,`user_type`),
  ADD KEY `permission_user_permission_id_foreign` (`permission_id`);

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
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`user_id`,`role_id`,`user_type`),
  ADD KEY `role_user_role_id_foreign` (`role_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `about_us_translations`
--
ALTER TABLE `about_us_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `app_visits`
--
ALTER TABLE `app_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cookies`
--
ALTER TABLE `cookies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `develop_settings`
--
ALTER TABLE `develop_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_titles`
--
ALTER TABLE `job_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `job_titles_translations`
--
ALTER TABLE `job_titles_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `job_types`
--
ALTER TABLE `job_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_types_translations`
--
ALTER TABLE `job_types_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `maids`
--
ALTER TABLE `maids`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `maid_jobs`
--
ALTER TABLE `maid_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `maid_job_titles`
--
ALTER TABLE `maid_job_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `nationalities`
--
ALTER TABLE `nationalities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nationalities_translations`
--
ALTER TABLE `nationalities_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `packages_translations`
--
ALTER TABLE `packages_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `print_details_translations`
--
ALTER TABLE `print_details_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting`
--
ALTER TABLE `print_setting`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting_details`
--
ALTER TABLE `print_setting_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `print_setting_translations`
--
ALTER TABLE `print_setting_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `questions_translations`
--
ALTER TABLE `questions_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `religions`
--
ALTER TABLE `religions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `religions_translations`
--
ALTER TABLE `religions_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services_translations`
--
ALTER TABLE `services_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings_translations`
--
ALTER TABLE `settings_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sliders_translations`
--
ALTER TABLE `sliders_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `statistics_translations`
--
ALTER TABLE `statistics_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `steps`
--
ALTER TABLE `steps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `steps_translations`
--
ALTER TABLE `steps_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_firebase_tokens`
--
ALTER TABLE `user_firebase_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `complaints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `packages_translations`
--
ALTER TABLE `packages_translations`
  ADD CONSTRAINT `packages_translations_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permission_user`
--
ALTER TABLE `permission_user`
  ADD CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `religions_translations`
--
ALTER TABLE `religions_translations`
  ADD CONSTRAINT `religions_translations_religion_id_foreign` FOREIGN KEY (`religion_id`) REFERENCES `religions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `sliders_translations`
--
ALTER TABLE `sliders_translations`
  ADD CONSTRAINT `sliders_translations_slider_id_foreign` FOREIGN KEY (`slider_id`) REFERENCES `sliders` (`id`) ON DELETE CASCADE;

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
