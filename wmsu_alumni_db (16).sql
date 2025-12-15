-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 09:04 AM
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
-- Database: `wmsu_alumni_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_records`
--

CREATE TABLE `academic_records` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `year_graduated` year(4) DEFAULT NULL,
  `honors` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Degrees / program records for alumni';

--
-- Dumping data for table `academic_records`
--

INSERT INTO `academic_records` (`id`, `alumni_id`, `college_id`, `course_id`, `student_number`, `year_graduated`, `honors`, `created_at`) VALUES
(15, 1029, 2, 6, '0001-00001', '2025', NULL, '2025-12-14 09:22:22'),
(16, 1030, 2, 6, '0001-00002', '2025', NULL, '2025-12-14 09:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK to users.id (1:1)',
  `full_name` varchar(150) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Admin-specific details';

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `full_name`, `position`, `department`, `created_at`) VALUES
(3, 4, 'Mathew JG Santos Payopelin', 'Head Admin', 'IT', '2025-12-13 04:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Map admins to permissions';

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`id`, `admin_id`, `permission_id`, `granted_at`) VALUES
(31, 3, 1, '2025-12-14 05:36:19'),
(32, 3, 2, '2025-12-14 05:36:19'),
(33, 3, 3, '2025-12-14 05:36:19'),
(34, 3, 4, '2025-12-14 05:36:19'),
(35, 3, 5, '2025-12-14 05:36:19'),
(36, 3, 6, '2025-12-14 05:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

CREATE TABLE `alumni` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) NOT NULL COMMENT 'Natural key retained',
  `surname` varchar(100) NOT NULL,
  `given_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated') DEFAULT 'Single',
  `sex` enum('Male','Female') DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `preferred_contact_method` enum('phone','email','none') DEFAULT 'email',
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `barangay_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Alumni-specific details';

--
-- Dumping data for table `alumni`
--

INSERT INTO `alumni` (`id`, `user_id`, `student_id`, `surname`, `given_name`, `middle_name`, `birthday`, `blood_type`, `civil_status`, `sex`, `birth_place`, `address_street`, `preferred_contact_method`, `region`, `province`, `city`, `barangay`, `zip_code`, `created_at`, `updated_at`, `barangay_id`) VALUES
(1029, 11, '0001-00001', '1test', 'test1', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, '2025-12-14 09:22:22', '2025-12-14 09:22:48', NULL),
(1030, 12, '0001-00002', '2test', 'test2', NULL, NULL, NULL, 'Single', NULL, NULL, NULL, 'email', NULL, NULL, NULL, NULL, NULL, '2025-12-14 09:22:33', '2025-12-14 09:24:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL COMMENT 'FK to alumni: the person the application is about',
  `created_by_user_id` int(11) NOT NULL COMMENT 'FK to users: who created the application (self or admin)',
  `validated_by_admin_id` int(11) DEFAULT NULL COMMENT 'FK to admins: which admin validated',
  `application_type` enum('New','Renewal') DEFAULT 'New',
  `renewal_status` enum('none','pending','approved','rejected') DEFAULT 'none',
  `batch_name` varchar(100) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `status` enum('pending','active','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pending_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Applications with creator (user) and validator (admin)';

--
-- Triggers `applications`
--
DELIMITER $$
CREATE TRIGGER `trg_app_status_change` BEFORE UPDATE ON `applications` FOR EACH ROW BEGIN
  IF NEW.status <> OLD.status THEN
    IF NEW.status = 'pending' THEN
      SET NEW.pending_at = NOW();
    END IF;

    IF NEW.status = 'active' THEN
      SET NEW.approved_at = NOW();
    END IF;

    IF NEW.status = 'archived' THEN
      SET NEW.archived_at = NOW();
    END IF;

    
    IF NEW.renewal_status = 'rejected' THEN
      SET NEW.declined_at = NOW();
    END IF;

    
    IF OLD.status = 'archived' AND NEW.status = 'active' THEN
      SET NEW.restored_at = NOW();
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_applications_after_update` AFTER UPDATE ON `applications` FOR EACH ROW BEGIN
  IF NEW.renewal_status <> OLD.renewal_status THEN
    INSERT INTO notifications (user_id, type, message, created_at)
    VALUES (
      NEW.created_by_user_id,
      'renewal_request',
      CONCAT('Application #', NEW.id, ' status changed to ', NEW.renewal_status),
      NOW()
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_applications_before_update` BEFORE UPDATE ON `applications` FOR EACH ROW BEGIN
  
  IF NEW.renewal_status <> OLD.renewal_status THEN
    IF NEW.renewal_status = 'rejected' THEN
      SET NEW.declined_at = NOW();
    ELSEIF NEW.renewal_status = 'approved' THEN
      SET NEW.approved_at = NOW();
    ELSEIF NEW.renewal_status = 'pending' THEN
      SET NEW.pending_at = NOW();
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_applications_renewal_update` BEFORE UPDATE ON `applications` FOR EACH ROW BEGIN
    
    IF NEW.renewal_status = 'approved' AND OLD.renewal_status <> 'approved' THEN
        SET NEW.approved_at = NOW();
    END IF;

    
    IF NEW.renewal_status = 'rejected' AND OLD.renewal_status <> 'rejected' THEN
        SET NEW.declined_at = NOW();
    END IF;

    
    IF NEW.renewal_status = 'pending' AND OLD.renewal_status <> 'pending' THEN
        SET NEW.pending_at = NOW();
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_applications_status_update` BEFORE UPDATE ON `applications` FOR EACH ROW BEGIN
    
    IF NEW.status = 'active' AND OLD.status <> 'active' THEN
        SET NEW.approved_at = NOW();
    END IF;

    
    IF NEW.status = 'archived' AND OLD.status <> 'archived' THEN
        SET NEW.archived_at = NOW();
    END IF;

    
    IF NEW.status = 'pending' AND OLD.status = 'archived' THEN
        SET NEW.restored_at = NOW();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `type` enum('photo','document','other') NOT NULL,
  `path` varchar(512) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='File paths and attachments (photos, CVs, certificates)';

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'FK to users: actor who performed the action (admin/alumni/system)',
  `action` varchar(150) NOT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for actions by any user';

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Barangays lookup (optional)';

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`id`, `city_id`, `name`) VALUES
(1, 1, 'Baliwasan'),
(2, 1, 'Tetuan'),
(3, 1, 'Santa Maria'),
(4, 1, 'Pasonanca'),
(5, 1, 'Tumaga'),
(6, 1, 'Calarian'),
(7, 1, 'San Jose Cawa-Cawa');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Cities/municipalities lookup';

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `province_id`, `name`) VALUES
(1, 1, 'Zamboanga City'),
(2, 1, 'Pagadian City'),
(3, 2, 'Dipolog City'),
(4, 2, 'Dapitan City');

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Colleges (e.g., College of Computing Studies)';

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`, `code`) VALUES
(1, 'College of Nursing', 'CN'),
(2, 'College of Computing Studies', 'CCS');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `type` enum('phone','mobile','email','other') NOT NULL,
  `value` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `label` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phones and emails for alumni';

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `abbreviation` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Courses offered';

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `college_id`, `code`, `name`, `abbreviation`) VALUES
(5, 1, 'NURS', 'Bachelor of Science in Nursing', 'BSN'),
(6, 2, 'BSCS', 'Bachelor of Science in Computer Science', 'BSCS'),
(7, 2, 'BSIT', 'Bachelor of Science in Information Technology', 'BSIT'),
(8, 2, 'ACT', 'Associate in Computer Technology', 'ACT');

-- --------------------------------------------------------

--
-- Table structure for table `education_history`
--

CREATE TABLE `education_history` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `level` enum('elementary','junior_high','senior_high','tertiary','graduate') NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `year_completed` year(4) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Education history entries';

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`id`, `user_id`, `token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 11, '027529', '2025-12-14 11:55:58', NULL, '2025-12-14 10:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Emergency contacts for alumni';

-- --------------------------------------------------------

--
-- Table structure for table `employment_records`
--

CREATE TABLE `employment_records` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `position` varchar(150) DEFAULT NULL,
  `company_address` varchar(255) DEFAULT NULL,
  `company_contact` varchar(50) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Employment history for alumni';

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Receiver of the notification',
  `type` enum('new_application','renewal_request','pending_verification','system','other') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'new_application', 'New alumni application received from ID: 1022', 0, '2025-12-13 11:45:21');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL COMMENT 'e.g., applications.approve, users.manage',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Permission codes';

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'applications.view', 'View applications'),
(2, 'applications.approve', 'Approve applications'),
(3, 'applications.reject', 'Reject applications'),
(4, 'users.manage', 'Manage user accounts'),
(5, 'alumni.manage', 'Manage alumni records'),
(6, 'reports.view', 'View reports and analytics');

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Provinces lookup';

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`id`, `region_id`, `name`) VALUES
(1, 9, 'Zamboanga del Sur'),
(2, 9, 'Zamboanga del Norte'),
(3, 9, 'Zamboanga Sibugay');

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Regions lookup';

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `code`, `name`) VALUES
(9, 'IX', 'Region IX (Zamboanga Peninsula)');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`) VALUES
(1, 'admin', 'Administrator'),
(2, 'alumni', 'Alumni'),
(3, 'both', 'Admin + Alumni');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL COMMENT 'Login identifier; required',
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt / argon2 hash',
  `role` enum('admin','alumni','both') NOT NULL DEFAULT 'alumni',
  `status` enum('pending','approved','archived','declined') NOT NULL DEFAULT 'pending',
  `display_name` varchar(150) DEFAULT NULL COMMENT 'Cached display name (fname m_initial surname)',
  `pending_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When account became pending (creation time)',
  `approved_at` datetime DEFAULT NULL COMMENT 'Set when approved',
  `archived_at` datetime DEFAULT NULL COMMENT 'Set when archived',
  `declined_at` datetime DEFAULT NULL COMMENT 'Set when declined',
  `restored_at` datetime DEFAULT NULL COMMENT 'Set when restored from archived (updated each restore)',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `alumni_student_id` varchar(64) DEFAULT NULL,
  `verification_token` varchar(128) DEFAULT NULL,
  `password_reset_token` varchar(128) DEFAULT NULL,
  `password_reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Supertype table: central login credentials and lifecycle';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `display_name`, `pending_at`, `approved_at`, `archived_at`, `declined_at`, `restored_at`, `last_login`, `created_at`, `updated_at`, `is_verified`, `alumni_student_id`, `verification_token`, `password_reset_token`, `password_reset_expiry`) VALUES
(1, 'system@wmsu.local', '$2y$12$system-placeholder-hash', 'admin', 'approved', 'SYSTEM', '2025-11-15 15:11:00', NULL, NULL, NULL, NULL, NULL, '2025-11-15 07:11:00', '2025-11-15 07:11:00', 0, NULL, NULL, NULL, NULL),
(4, 'mathewpayopelin.payo.dev@gmail.com', '$2y$10$GPhEzUnN3xmRiah2yGjYYuL6pHb9BGIJwXpwkllau55LPrg./xtpa', 'both', 'approved', 'Mathew JG Santos Payopelin', '2025-12-13 12:56:51', NULL, NULL, NULL, NULL, '2025-12-14 22:15:48', '2025-12-13 04:56:51', '2025-12-14 14:15:48', 1, NULL, NULL, NULL, NULL),
(11, 'payomath@gmail.com', '$2y$10$ApOrCVFMJHymJcaeFtC.X.SDaGyLOlAy/UfdEj7qujM6ETntSlKzq', 'alumni', 'pending', 'test1 1test', '2025-12-14 17:22:48', NULL, NULL, NULL, NULL, '2025-12-14 18:45:44', '2025-12-14 09:22:48', '2025-12-14 10:45:44', 1, '0001-00001', NULL, NULL, NULL),
(12, 'gulaneithandeniel@gmail.com', '$2y$10$P4yXw0O9g6rnCcQGRH94WegFR0THeuA7EmfwKfRxpf8zknawqrEHO', 'alumni', 'pending', 'test2 2test', '2025-12-14 17:24:39', NULL, NULL, NULL, NULL, NULL, '2025-12-14 09:24:39', '2025-12-14 09:30:46', 1, '0001-00002', NULL, NULL, NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_status_change` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
  
  IF NEW.status <> OLD.status THEN
    
    IF NEW.status = 'pending' THEN
      SET NEW.pending_at = NOW();
    END IF;

    
    IF NEW.status = 'approved' THEN
      SET NEW.approved_at = NOW();
    END IF;

    
    IF NEW.status = 'archived' THEN
      SET NEW.archived_at = NOW();
    END IF;

    
    IF NEW.status = 'declined' THEN
      SET NEW.declined_at = NOW();
    END IF;

    
    IF OLD.status = 'archived' AND NEW.status = 'approved' THEN
      SET NEW.restored_at = NOW(); 
    END IF;
  END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_acad_alumni` (`alumni_id`),
  ADD KEY `fk_acad_college` (`college_id`),
  ADD KEY `fk_acad_course` (`course_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_perm` (`admin_id`,`permission_id`),
  ADD KEY `fk_admin_perm_permission` (`permission_id`);

--
-- Indexes for table `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_alumni_region` (`region`),
  ADD KEY `fk_alumni_province` (`province`),
  ADD KEY `fk_alumni_city` (`city`),
  ADD KEY `idx_alumni_student_surname` (`student_id`,`surname`),
  ADD KEY `fk_alumni_barangay` (`barangay_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_alumni` (`alumni_id`),
  ADD KEY `idx_app_created_by` (`created_by_user_id`),
  ADD KEY `fk_app_validated_by_admin` (`validated_by_admin_id`),
  ADD KEY `idx_app_status` (`status`,`application_type`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attach_alumni` (`alumni_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_barangay_city` (`city_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_city_province` (`province_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_college_name` (`name`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contacts_alumni` (`alumni_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `college_id` (`college_id`,`code`),
  ADD KEY `idx_course_college` (`college_id`);

--
-- Indexes for table `education_history`
--
ALTER TABLE `education_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_edu_alumni` (`alumni_id`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emer_alumni` (`alumni_id`);

--
-- Indexes for table `employment_records`
--
ALTER TABLE `employment_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_alumni` (`alumni_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_province_region` (`region_id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_region_name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `alumni_student_id` (`alumni_student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_records`
--
ALTER TABLE `academic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1031;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `education_history`
--
ALTER TABLE `education_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employment_records`
--
ALTER TABLE `employment_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD CONSTRAINT `fk_acad_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acad_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_acad_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `fk_admin_perm_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admin_perm_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `alumni`
--
ALTER TABLE `alumni`
  ADD CONSTRAINT `fk_alumni_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alumni_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_app_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_validated_by_admin` FOREIGN KEY (`validated_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `fk_attach_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `barangays`
--
ALTER TABLE `barangays`
  ADD CONSTRAINT `fk_barangay_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_city_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contacts_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `education_history`
--
ALTER TABLE `education_history`
  ADD CONSTRAINT `fk_edu_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD CONSTRAINT `email_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `fk_emer_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employment_records`
--
ALTER TABLE `employment_records`
  ADD CONSTRAINT `fk_emp_alumni` FOREIGN KEY (`alumni_id`) REFERENCES `alumni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `provinces`
--
ALTER TABLE `provinces`
  ADD CONSTRAINT `fk_province_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
