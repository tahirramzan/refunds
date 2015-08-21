-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 22, 2015 at 12:39 AM
-- Server version: 5.5.44-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wpdemocivi_bwwgb`
--

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_refunds`
--

DROP TABLE IF EXISTS `civicrm_refunds`;
CREATE TABLE IF NOT EXISTS `civicrm_refunds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contribution_id` int(10) unsigned NOT NULL,
  `primary_contact_id` int(10) unsigned NOT NULL,
  `secondary_contact_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status_id` int(10) unsigned NOT NULL,
  `refunds_date` datetime NOT NULL,
  `total_amount` decimal(20,2) NOT NULL,
  `refunded_amount` decimal(20,2) NOT NULL,
  `fees_amount` decimal(20,2) NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `adhoc_charges_note` text COLLATE utf8_unicode_ci,
  `participants_estimate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `participants_actual` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deducted_amount` decimal(20,2) DEFAULT NULL,
  `is_full_refunded` tinyint(4) DEFAULT '0',
  `is_partial_refunded` tinyint(4) DEFAULT '0',
  `is_approved` tinyint(4) DEFAULT '0',
  `is_pending` tinyint(4) DEFAULT '0',
  `created_by` int(10) unsigned NOT NULL,
  `created_date` datetime NOT NULL,
  `updated_by` int(10) unsigned NOT NULL,
  `updated_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_refunds_primary_contact_id` (`primary_contact_id`),
  KEY `FK_civicrm_refunds_secondary_contact_id` (`secondary_contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_refunds`
--
ALTER TABLE `civicrm_refunds`
  ADD CONSTRAINT `FK_civicrm_refunds_primary_contact_id` FOREIGN KEY (`primary_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_refunds_secondary_contact_id` FOREIGN KEY (`secondary_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
