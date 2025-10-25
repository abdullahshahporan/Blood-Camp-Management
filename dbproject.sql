-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 07:03 AM
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
-- Database: `dbproject`
--

-- --------------------------------------------------------

--
-- Table structure for table `allocation`
--

CREATE TABLE `allocation` (
  `allocation_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `unit_id` char(14) DEFAULT NULL,
  `allocated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocation`
--

INSERT INTO `allocation` (`allocation_id`, `request_id`, `unit_id`, `allocated_at`) VALUES
(1, 1, 'BU001', '2025-08-06 10:00:00'),
(2, 2, 'BU002', '2025-08-07 09:30:00'),
(3, 3, 'BU003', '2025-08-08 11:15:00'),
(4, 4, 'BU004', '2025-08-09 12:00:00'),
(5, 5, 'BU005', '2025-08-10 09:45:00'),
(6, 6, 'BU006', '2025-08-11 10:30:00'),
(7, 7, 'BU007', '2025-08-12 09:20:00'),
(8, 8, 'BU008', '2025-08-13 10:40:00'),
(9, 9, 'BU009', '2025-08-14 11:10:00'),
(10, 10, 'BU010', '2025-08-15 10:50:00'),
(11, 11, 'BU011', '2025-08-16 09:30:00'),
(12, 12, 'BU012', '2025-08-17 10:00:00'),
(13, 13, 'BU013', '2025-08-18 09:15:00'),
(14, 14, 'BU014', '2025-08-19 11:00:00'),
(15, 15, 'BU015', '2025-08-20 10:45:00'),
(16, 16, 'BU016', '2025-08-21 09:40:00'),
(17, 17, 'BU017', '2025-08-22 10:20:00'),
(18, 18, 'BU018', '2025-08-23 11:25:00'),
(19, 19, 'BU019', '2025-08-24 10:10:00'),
(21, 11, 'BU012', '2025-10-25 06:52:54');

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `camp_id` int(11) DEFAULT NULL,
  `appointment_time` datetime DEFAULT NULL,
  `STATUS` enum('Booked','Completed','NoShow') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`appointment_id`, `donor_id`, `camp_id`, `appointment_time`, `STATUS`, `created_at`) VALUES
(1, 1, 1, '2025-08-05 09:30:00', 'Completed', '2025-10-22 01:33:04'),
(2, 2, 2, '2025-07-18 10:00:00', 'Booked', '2025-10-22 01:33:04'),
(3, 3, 3, '2025-06-25 11:00:00', 'Completed', '2025-10-22 01:33:04'),
(4, 4, 4, '2025-09-02 12:00:00', 'NoShow', '2025-10-22 01:33:04'),
(5, 5, 5, '2025-05-12 09:00:00', 'Completed', '2025-10-22 01:33:04'),
(6, 6, 6, '2025-04-20 10:30:00', 'Completed', '2025-10-22 01:33:04'),
(7, 7, 7, '2025-10-12 11:45:00', 'Booked', '2025-10-22 01:33:04'),
(8, 8, 8, '2025-07-01 09:15:00', 'Completed', '2025-10-22 01:33:04'),
(9, 9, 9, '2025-09-14 10:45:00', 'Booked', '2025-10-22 01:33:04'),
(10, 10, 10, '2025-06-30 11:30:00', 'Completed', '2025-10-22 01:33:04'),
(11, 11, 11, '2025-05-24 12:15:00', 'Completed', '2025-10-22 01:33:04'),
(12, 12, 12, '2025-10-15 09:00:00', 'Booked', '2025-10-22 01:33:04'),
(13, 13, 13, '2025-07-22 10:00:00', 'NoShow', '2025-10-22 01:33:04'),
(14, 14, 14, '2025-08-10 09:30:00', 'Completed', '2025-10-22 01:33:04'),
(15, 15, 15, '2025-06-15 11:00:00', 'Booked', '2025-10-22 01:33:04'),
(16, 16, 16, '2025-09-25 10:45:00', 'Completed', '2025-10-22 01:33:04'),
(17, 17, 17, '2025-04-18 11:30:00', 'Completed', '2025-10-22 01:33:04'),
(18, 18, 18, '2025-07-09 12:00:00', 'Booked', '2025-10-22 01:33:04'),
(19, 19, 19, '2025-05-01 09:15:00', 'Completed', '2025-10-22 01:33:04'),
(20, 20, 20, '2025-08-20 10:30:00', 'Completed', '2025-10-22 01:33:04'),
(21, 21, 1, '2025-10-23 16:00:00', 'Booked', '2025-10-22 09:07:47'),
(24, 9, 9, '2025-10-23 16:23:00', 'NoShow', '2025-10-22 10:23:23'),
(25, 19, 4, '2025-10-25 09:00:00', 'Booked', '2025-10-25 00:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `blood_request`
--

CREATE TABLE `blood_request` (
  `request_id` int(11) NOT NULL,
  `hospital_name` varchar(150) DEFAULT NULL,
  `blood_group` enum('A+','B+','O+','AB+','A-','B-','O-','AB-') DEFAULT NULL,
  `units_requested` int(11) DEFAULT NULL,
  `urgency` enum('Normal','Urgent') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_request`
--

INSERT INTO `blood_request` (`request_id`, `hospital_name`, `blood_group`, `units_requested`, `urgency`, `created_at`) VALUES
(1, 'Khulna Medical Center', 'A+', 2, 'Normal', '2025-10-22 01:33:04'),
(2, 'City Blood Bank', 'O+', 3, 'Urgent', '2025-10-22 01:33:04'),
(3, 'Sadar Hospital', 'B+', 1, 'Normal', '2025-10-22 01:33:04'),
(4, 'Red Cross Unit', 'AB+', 2, 'Normal', '2025-10-22 01:33:04'),
(5, 'Metro Diagnostic Center', 'A-', 4, 'Normal', '2025-10-22 01:33:04'),
(6, 'HealthPoint Hospital', 'B-', 1, 'Normal', '2025-10-22 01:33:04'),
(7, 'GreenLife Clinic', 'O-', 2, 'Normal', '2025-10-22 01:33:04'),
(8, 'BloodCare Foundation', 'AB-', 3, 'Normal', '2025-10-22 01:33:04'),
(9, 'Delta Hospital', 'A+', 1, 'Normal', '2025-10-22 01:33:04'),
(10, 'Medinova Blood Center', 'O+', 2, 'Normal', '2025-10-22 01:33:04'),
(11, 'Sheikh Abu Naser Hospital', 'B+', 2, 'Urgent', '2025-10-22 01:33:04'),
(12, 'Hope Medical Center', 'A-', 3, 'Normal', '2025-10-22 01:33:04'),
(13, 'Nova Hospital', 'O-', 1, 'Urgent', '2025-10-22 01:33:04'),
(14, 'National Blood Bank', 'AB-', 2, 'Normal', '2025-10-22 01:33:04'),
(15, 'Satkhira Blood Camp', 'A+', 2, 'Urgent', '2025-10-22 01:33:04'),
(16, 'Medicare Diagnostic', 'B+', 4, 'Normal', '2025-10-22 01:33:04'),
(17, 'Al-Baraka Blood Point', 'O+', 3, 'Normal', '2025-10-22 01:33:04'),
(18, 'Apex Medical Center', 'A-', 1, 'Urgent', '2025-10-22 01:33:04'),
(19, 'South Bengal Hospital', 'B-', 2, 'Normal', '2025-10-22 01:33:04'),
(20, 'Khulna New Market Hospital', 'O-', 2, 'Urgent', '2025-10-22 01:33:04'),
(21, 'Khulna Medical Center', 'A+', 5, 'Urgent', '2025-10-24 03:45:25'),
(22, 'BloodCare Foundation', 'A+', 3, 'Normal', '2025-10-24 09:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `blood_unit`
--

CREATE TABLE `blood_unit` (
  `unit_id` char(14) NOT NULL,
  `donation_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `STATUS` enum('Available','Reserved','Expired') DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_unit`
--

INSERT INTO `blood_unit` (`unit_id`, `donation_id`, `location_id`, `STATUS`, `expiry_date`, `created_at`) VALUES
('BU001', 1, 1, 'Available', '2025-12-05', '2025-10-22 01:33:04'),
('BU002', 2, 3, 'Available', '2025-11-10', '2025-10-22 01:33:04'),
('BU003', 3, 5, 'Reserved', '2025-10-20', '2025-10-22 01:33:04'),
('BU004', 4, 6, 'Expired', '2025-09-15', '2025-10-22 01:33:04'),
('BU005', 5, 8, 'Available', '2025-12-01', '2025-10-22 01:33:04'),
('BU006', 6, 9, 'Reserved', '2025-10-25', '2025-10-22 01:33:04'),
('BU007', 7, 10, 'Available', '2025-11-15', '2025-10-22 01:33:04'),
('BU008', 8, 12, 'Available', '2025-12-25', '2025-10-22 01:33:04'),
('BU009', 9, 13, 'Expired', '2025-09-05', '2025-10-22 01:33:04'),
('BU010', 10, 15, 'Reserved', '2025-10-30', '2025-10-22 01:33:04'),
('BU011', 11, 16, 'Available', '2025-11-12', '2025-10-22 01:33:04'),
('BU012', 12, 17, 'Reserved', '2025-12-20', '2025-10-22 01:33:04'),
('BU013', 13, 18, 'Reserved', '2025-09-25', '2025-10-22 01:33:04'),
('BU014', 14, 19, 'Available', '2025-10-18', '2025-10-22 01:33:04'),
('BU015', 15, 20, 'Available', '2025-11-07', '2025-10-22 01:33:04'),
('BU016', 16, 11, 'Expired', '2025-09-10', '2025-10-22 01:33:04'),
('BU017', 17, 14, 'Available', '2025-12-02', '2025-10-22 01:33:04'),
('BU018', 18, 7, 'Available', '2025-10-22', '2025-10-22 01:33:04'),
('BU019', 19, 2, 'Reserved', '2025-12-08', '2025-10-22 01:33:04'),
('BU020', 20, 4, 'Available', '2025-11-25', '2025-10-22 01:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `camp`
--

CREATE TABLE `camp` (
  `camp_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `camp_address` varchar(100) NOT NULL,
  `camp_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camp`
--

INSERT INTO `camp` (`camp_id`, `title`, `location_id`, `camp_address`, `camp_date`, `created_at`) VALUES
(1, 'Khulna City Blood Donation Camp', 1, 'Khulna Medical College Auditorium, Boyra, Khulna', '2025-08-05', '2025-10-21 19:33:04'),
(2, 'Red Cross Mega Camp', 4, 'Bangladesh Red Crescent Society Hall, Agrabad, Chittagong', '2025-07-18', '2025-10-21 19:33:04'),
(3, 'University Blood Drive', 2, 'Dhaka University TSC, Shahbagh, Dhaka', '2025-06-25', '2025-10-21 19:33:04'),
(4, 'Community Health Camp', 3, 'Rajshahi Medical University Ground, Laxmipur, Rajshahi', '2025-09-02', '2025-10-21 19:33:04'),
(5, 'Save Life Initiative', 5, 'Sylhet District Stadium, Kazirbazar, Sylhet', '2025-05-12', '2025-10-21 19:33:04'),
(6, 'Dhaka Central Blood Drive', 6, 'National Blood Transfusion Centre, Dhanmondi, Dhaka', '2025-04-20', '2025-10-21 19:33:04'),
(7, 'Hope Donation Day', 7, 'Rangpur Zilla Parishad Bhaban, Jahaj Company Moor, Rangpur', '2025-10-12', '2025-10-21 19:33:04'),
(8, 'Rajshahi Blood Festival', 8, 'Rajshahi University Central Field, Motihar, Rajshahi', '2025-07-01', '2025-10-21 19:33:04'),
(9, 'Corporate Donor Event', 9, 'Gazipur Industrial Park Community Hall, Tongi, Gazipur', '2025-09-14', '2025-10-21 19:33:04'),
(10, 'Jashore Donor Meet', 17, 'Jashore Municipality Auditorium, Daratana, Jashore', '2025-06-30', '2025-10-21 19:33:04'),
(11, 'Nova Health Camp', 14, 'Pabna Science & Technology Institute, Pabna Sadar, Pabna', '2025-05-24', '2025-10-21 19:33:04'),
(12, 'South Bengal Donation', 20, 'Madaripur Govt. College Field, Madaripur', '2025-10-29', '2025-10-21 19:33:04'),
(13, 'Kushtia Camp', 10, 'Kushtia General Hospital Campus, Kushtia', '2025-07-22', '2025-10-21 19:33:04'),
(14, 'Satkhira Volunteer Drive', 16, 'Satkhira Sadar Hospital, Satkhira', '2025-08-10', '2025-10-21 19:33:04'),
(15, 'Khulna Mega Donation', 11, 'Khulna Public Hall, Sonadanga, Khulna', '2025-06-15', '2025-10-21 19:33:04'),
(16, 'BloodCare Annual Camp', 8, 'Rajshahi City Blood Centre, Shaheb Bazar, Rajshahi', '2025-09-25', '2025-10-21 19:33:04'),
(17, 'Youth Donor Program', 18, 'Feni Govt. College Ground, Trunk Road, Feni', '2025-04-18', '2025-10-21 19:33:04'),
(18, 'Medical Awareness Camp', 13, 'Jessore District Stadium, Jashore', '2025-07-09', '2025-10-21 19:33:04'),
(19, 'Save Humanity Event', 15, 'Tangail Municipality Hall, Tangail', '2025-11-01', '2025-10-21 19:33:04'),
(20, 'Emergency Blood Drive', 12, 'Bogura Medical College Field, Bogura', '2025-08-20', '2025-10-21 19:33:04'),
(21, 'KUET Blood Donation Camp', 1, 'KUET campus', '2025-10-30', '2025-10-22 07:27:14'),
(22, 'TT Para Blood Bank Camp', 22, 'TT Para', '2025-10-26', '2025-10-22 08:13:42'),
(23, 'Boyra tarunno songho', 11, 'Boyra Khulna', '2025-10-29', '2025-10-25 03:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `donation`
--

CREATE TABLE `donation` (
  `donation_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `camp_id` int(11) DEFAULT NULL,
  `donation_time` datetime DEFAULT NULL,
  `volume_ml` smallint(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation`
--

INSERT INTO `donation` (`donation_id`, `donor_id`, `camp_id`, `donation_time`, `volume_ml`, `created_at`) VALUES
(1, 1, 1, '2025-08-05 09:45:00', 450, '2025-10-22 01:33:04'),
(2, 3, 3, '2025-06-25 11:20:00', 500, '2025-10-22 01:33:04'),
(3, 5, 5, '2025-05-12 09:10:00', 480, '2025-10-22 01:33:04'),
(4, 6, 6, '2025-04-20 10:50:00', 470, '2025-10-22 01:33:04'),
(5, 8, 8, '2025-07-01 09:40:00', 460, '2025-10-22 01:33:04'),
(6, 10, 10, '2025-06-30 11:45:00', 490, '2025-10-22 01:33:04'),
(7, 11, 11, '2025-05-24 12:20:00', 480, '2025-10-22 01:33:04'),
(8, 14, 14, '2025-08-10 09:50:00', 500, '2025-10-22 01:33:04'),
(9, 16, 16, '2025-09-25 10:55:00', 470, '2025-10-22 01:33:04'),
(10, 17, 17, '2025-04-18 11:40:00', 480, '2025-10-22 01:33:04'),
(11, 19, 19, '2025-05-01 09:30:00', 450, '2025-10-22 01:33:04'),
(12, 20, 20, '2025-08-20 10:40:00', 460, '2025-10-22 01:33:04'),
(13, 2, 2, '2025-07-18 10:10:00', 480, '2025-10-22 01:33:04'),
(14, 4, 4, '2025-09-02 12:10:00', 490, '2025-10-22 01:33:04'),
(15, 7, 7, '2025-10-12 11:50:00', 470, '2025-10-22 01:33:04'),
(16, 9, 9, '2025-09-14 10:50:00', 500, '2025-10-22 01:33:04'),
(17, 12, 12, '2025-10-15 09:20:00', 460, '2025-10-22 01:33:04'),
(18, 13, 13, '2025-07-22 10:10:00', 490, '2025-10-22 01:33:04'),
(19, 15, 15, '2025-06-15 11:10:00', 480, '2025-10-22 01:33:04'),
(20, 18, 18, '2025-07-09 12:05:00', 470, '2025-10-22 01:33:04'),
(21, 21, 21, '2025-10-24 09:36:00', 150, '2025-10-24 03:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `donor`
--

CREATE TABLE `donor` (
  `donor_id` int(11) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `blood_group` enum('A+','B+','O+','AB+','A-','B-','O-','AB-') DEFAULT NULL,
  `last_donation` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor`
--

INSERT INTO `donor` (`donor_id`, `full_name`, `phone`, `blood_group`, `last_donation`, `created_at`, `location`) VALUES
(1, 'Rakib Hasan', '01711112222', 'B+', '2025-05-12', '2025-10-21 19:33:04', 'Khulna'),
(2, 'Mithila Akter', '01822223333', 'B+', '2025-03-18', '2025-10-21 19:33:04', 'Chittagong'),
(3, 'Samiul Rahman', '01933334444', 'O+', '2025-01-15', '2025-10-21 19:33:04', 'Khulna'),
(4, 'Nusrat Jahan', '01744445555', 'AB+', '2025-02-20', '2025-10-21 19:33:04', 'Rajshahi'),
(5, 'Arafat Hossain', '01855556666', 'A-', '2025-04-25', '2025-10-21 19:33:04', 'Sylhet'),
(6, 'Tamim Iqbal', '01966667777', 'B-', '2025-06-05', '2025-10-21 19:33:04', 'Barishal'),
(7, 'Moumi Khatun', '01777778888', 'O-', '2025-03-12', '2025-10-21 19:33:04', 'Rangpur'),
(8, 'Sajid Islam', '01888889999', 'AB-', '2025-07-09', '2025-10-21 19:33:04', 'Mymensingh'),
(9, 'Ruhul Amin', '01999990000', 'A+', '2025-08-15', '2025-10-21 19:33:04', 'Gazipur'),
(10, 'Farzana Ahmed', '01700001111', 'B+', '2025-05-01', '2025-10-21 19:33:04', 'Narsingdi'),
(11, 'Kamal Uddin', '01812344321', 'O+', '2025-04-02', '2025-10-21 19:33:04', 'Cumilla'),
(12, 'Sadia Haque', '01987654321', 'AB+', '2025-03-17', '2025-10-21 19:33:04', 'Bogura'),
(13, 'Tanvir Rahman', '01715935726', 'A-', '2025-05-28', '2025-10-21 19:33:04', 'Jessore'),
(14, 'Sumi Akter', '01865478932', 'B-', '2025-07-01', '2025-10-21 19:33:04', 'Pabna'),
(15, 'Habib Ullah', '01974125896', 'O-', '2025-06-10', '2025-10-21 19:33:04', 'Tangail'),
(16, 'Shamima Parvin', '01775395162', 'AB-', '2025-01-24', '2025-10-21 19:33:04', 'Kushtia'),
(17, 'Mehedi Hasan', '01896325874', 'A+', '2025-02-11', '2025-10-21 19:33:04', 'Noakhali'),
(18, 'Fahmida Rahman', '01965478912', 'B+', '2025-04-07', '2025-10-21 19:33:04', 'Feni'),
(19, 'Rafiq Hossain', '01785296341', 'O+', '2025-06-21', '2025-10-21 19:33:04', 'Manikganj'),
(20, 'Nasrin Akter', '01895175362', 'AB+', '2025-03-29', '2025-10-21 19:33:04', 'Madaripur'),
(21, 'Abdullah Md. Shahporan', '01759223522', 'A+', '2025-10-06', '2025-10-22 05:07:13', 'Khulna'),
(23, 'Karim', '01923521485', 'B+', '2025-05-05', '2025-10-25 03:15:19', 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `NAME` varchar(120) NOT NULL,
  `address` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`location_id`, `NAME`, `address`, `created_at`) VALUES
(1, 'Khulna Medical College', 'Boyra Khulna', '2025-10-22 01:33:04'),
(2, 'City Blood Bank', 'Gollamari, Khulna', '2025-10-22 01:33:04'),
(3, 'Sadar Hospital', 'Sonadanga, Khulna', '2025-10-22 01:33:04'),
(4, 'Red Cross Unit', 'Shib Bari More, Khulna', '2025-10-22 01:33:04'),
(5, 'Metro Diagnostic Center', 'Boyra Main Road, Khulna', '2025-10-22 01:33:04'),
(6, 'HealthPoint Hospital', 'Bashundhara, Dhaka', '2025-10-22 01:33:04'),
(7, 'GreenLife Clinic', 'Banani, Dhaka', '2025-10-22 01:33:04'),
(8, 'BloodCare Foundation', 'Rajshahi City Center', '2025-10-22 01:33:04'),
(9, 'Delta Hospital', 'Dhanmondi, Dhaka', '2025-10-22 01:33:04'),
(10, 'Medinova Blood Center', 'Kushtia Sadar', '2025-10-22 01:33:04'),
(11, 'Sheikh Abu Naser Hospital', 'Gollamari, Khulna', '2025-10-22 01:33:04'),
(12, 'LifeSave Point', 'Rupsha, Khulna', '2025-10-22 01:33:04'),
(13, 'Hope Medical Center', 'Mirpur, Dhaka', '2025-10-22 01:33:04'),
(14, 'Nova Hospital', 'Motijheel, Dhaka', '2025-10-22 01:33:04'),
(15, 'National Blood Bank', 'Mohakhali, Dhaka', '2025-10-22 01:33:04'),
(16, 'Satkhira Blood Camp', 'Satkhira Sadar', '2025-10-22 01:33:04'),
(17, 'Medicare Diagnostic', 'Jashore Town', '2025-10-22 01:33:04'),
(18, 'Al-Baraka Blood Point', 'Khulna New Market', '2025-10-22 01:33:04'),
(19, 'Apex Medical Center', 'Bagerhat Town', '2025-10-22 01:33:04'),
(20, 'South Bengal Hospital', 'Khulna Bypass Road', '2025-10-22 01:33:04'),
(22, 'Mugda Medical College', 'Mugda, Dhaka', '2025-10-22 03:18:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allocation`
--
ALTER TABLE `allocation`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `fk_allocation_request` (`request_id`),
  ADD KEY `fk_allocation_unit` (`unit_id`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `fk_appt_donor` (`donor_id`),
  ADD KEY `fk_appt_camp` (`camp_id`);

--
-- Indexes for table `blood_request`
--
ALTER TABLE `blood_request`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `blood_unit`
--
ALTER TABLE `blood_unit`
  ADD PRIMARY KEY (`unit_id`),
  ADD KEY `fk_blood_donation` (`donation_id`),
  ADD KEY `fk_blood_location` (`location_id`);

--
-- Indexes for table `camp`
--
ALTER TABLE `camp`
  ADD PRIMARY KEY (`camp_id`),
  ADD KEY `fk_camp_location` (`location_id`);

--
-- Indexes for table `donation`
--
ALTER TABLE `donation`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `fk_donation_donor` (`donor_id`),
  ADD KEY `fk_donation_camp` (`camp_id`);

--
-- Indexes for table `donor`
--
ALTER TABLE `donor`
  ADD PRIMARY KEY (`donor_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allocation`
--
ALTER TABLE `allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `blood_request`
--
ALTER TABLE `blood_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `camp`
--
ALTER TABLE `camp`
  MODIFY `camp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `donation`
--
ALTER TABLE `donation`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `donor`
--
ALTER TABLE `donor`
  MODIFY `donor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allocation`
--
ALTER TABLE `allocation`
  ADD CONSTRAINT `fk_allocation_request` FOREIGN KEY (`request_id`) REFERENCES `blood_request` (`request_id`),
  ADD CONSTRAINT `fk_allocation_unit` FOREIGN KEY (`unit_id`) REFERENCES `blood_unit` (`unit_id`);

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `fk_appt_camp` FOREIGN KEY (`camp_id`) REFERENCES `camp` (`camp_id`),
  ADD CONSTRAINT `fk_appt_donor` FOREIGN KEY (`donor_id`) REFERENCES `donor` (`donor_id`);

--
-- Constraints for table `blood_unit`
--
ALTER TABLE `blood_unit`
  ADD CONSTRAINT `fk_blood_donation` FOREIGN KEY (`donation_id`) REFERENCES `donation` (`donation_id`),
  ADD CONSTRAINT `fk_blood_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`);

--
-- Constraints for table `camp`
--
ALTER TABLE `camp`
  ADD CONSTRAINT `fk_camp_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`);

--
-- Constraints for table `donation`
--
ALTER TABLE `donation`
  ADD CONSTRAINT `fk_donation_camp` FOREIGN KEY (`camp_id`) REFERENCES `camp` (`camp_id`),
  ADD CONSTRAINT `fk_donation_donor` FOREIGN KEY (`donor_id`) REFERENCES `donor` (`donor_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
