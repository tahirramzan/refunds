SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



DROP TABLE IF EXISTS `civicrm_refunds`;
DROP TABLE IF EXISTS `civicrm_refunds_admin`;

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

CREATE TABLE IF NOT EXISTS `civicrm_refunds_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contribution_id` int(10) unsigned NOT NULL,
  `primary_contact_id` int(10) unsigned NOT NULL,
  `secondary_contact_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status_id` int(10) unsigned NOT NULL,
  `refunds_admin_date` datetime NOT NULL,
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
  KEY `FK_civicrm_refunds_admin_primary_contact_id` (`primary_contact_id`),
  KEY `FK_civicrm_refunds_admin_secondary_contact_id` (`secondary_contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_refunds_admin`
--
ALTER TABLE `civicrm_refunds_admin`
  ADD CONSTRAINT `FK_civicrm_refunds_admin_primary_contact_id` FOREIGN KEY (`primary_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_refunds_admin_secondary_contact_id` FOREIGN KEY (`secondary_contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
