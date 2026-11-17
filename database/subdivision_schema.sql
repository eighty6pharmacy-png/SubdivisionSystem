-- ====================================================================
-- Subdivision Management System - Complete MySQL Schema
-- Generated for MySQL Workbench ERD Reverse Engineering
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `subdivision_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `subdivision_system`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `last_name` VARCHAR(100) NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_name` VARCHAR(100) NULL,
    `external_id` VARCHAR(100) NULL,
    `email` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(50) NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `password` VARCHAR(255) NULL,
    `plain_password` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
    `joined_at` DATE NULL DEFAULT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: admins (Specialized role from users)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_admins_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: finance_officers (Specialized role from users)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `finance_officers`;
CREATE TABLE `finance_officers` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_finance_officers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: guards (Specialized role from users)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `guards`;
CREATE TABLE `guards` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `schedule_days` JSON NULL,
    `shift_type` VARCHAR(50) NULL DEFAULT 'day',
    `shift_start` TIME NULL DEFAULT '06:00:00',
    `shift_end` TIME NULL DEFAULT '18:00:00',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_guards_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: residents (Specialized role from users)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `payment_behavior` VARCHAR(100) NOT NULL DEFAULT 'Not available yet',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_residents_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: lots
-- -----------------------------------------------------
DROP TABLE IF EXISTS `lots`;
CREATE TABLE `lots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `block` VARCHAR(50) NOT NULL,
    `lot_number` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Available',
    `provider_managed` TINYINT(1) NOT NULL DEFAULT 0,
    `lot_area` DECIMAL(10,2) NULL,
    `floor_area` DECIMAL(10,2) NULL,
    `description` TEXT NULL,
    `tct_no` VARCHAR(100) NULL,
    `pid` VARCHAR(100) NULL,
    `tax_dec_no` VARCHAR(100) NULL,
    `contract_price` DECIMAL(12,2) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `lots_block_lot_unique` (`block`, `lot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: buyers
-- -----------------------------------------------------
DROP TABLE IF EXISTS `buyers`;
CREATE TABLE `buyers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `lot_id` BIGINT UNSIGNED NULL,
    `civil_status` VARCHAR(50) NULL,
    `spouse_name` VARCHAR(150) NULL,
    `present_address` TEXT NULL,
    `postal_address` TEXT NULL,
    `id_type` VARCHAR(100) NULL,
    `proof_of_id` VARCHAR(255) NULL,
    `pagibig_number` VARCHAR(50) NULL,
    `lot_link_warning` TINYINT(1) NOT NULL DEFAULT 0,
    `tct_no` VARCHAR(100) NULL,
    `pid` VARCHAR(100) NULL,
    `tax_dec_no` VARCHAR(100) NULL,
    `lot_area` DECIMAL(10,2) NULL,
    `floor_area` DECIMAL(10,2) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_buyers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_buyers_lot`
        FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: resident_lots (Pivot: Residents and Lots)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `resident_lots`;
CREATE TABLE `resident_lots` (
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `lot_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`resident_id`, `lot_id`),
    CONSTRAINT `fk_resident_lots_resident`
        FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resident_lots_lot`
        FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: reservations
-- -----------------------------------------------------
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `lot_id` BIGINT UNSIGNED NOT NULL,
    `buyer_id` BIGINT UNSIGNED NULL,
    `last_name` VARCHAR(100) NULL,
    `first_name` VARCHAR(100) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `reservation_date` DATE NOT NULL,
    `deadline_date` DATE NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_reservations_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reservations_lot`
        FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_reservations_buyer`
        FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: downpayments
-- -----------------------------------------------------
DROP TABLE IF EXISTS `downpayments`;
CREATE TABLE `downpayments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `due_date` DATE NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `monthly_amortization` DECIMAL(12,2) NULL,
    `months_to_pay` INT NULL,
    `contract_date` DATE NULL,
    `penalty_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `paymongo_checkout_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_downpayments_reservation`
        FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: downpayment_histories
-- -----------------------------------------------------
DROP TABLE IF EXISTS `downpayment_histories`;
CREATE TABLE `downpayment_histories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `downpayment_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `trn` VARCHAR(100) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `paymongo_payment_id` VARCHAR(100) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Completed',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `downpayment_histories_trn_unique` (`trn`),
    CONSTRAINT `fk_downpayment_histories_downpayment`
        FOREIGN KEY (`downpayment_id`) REFERENCES `downpayments` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: financing_records
-- -----------------------------------------------------
DROP TABLE IF EXISTS `financing_records`;
CREATE TABLE `financing_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `buyer_id` BIGINT UNSIGNED NOT NULL,
    `lot_id` BIGINT UNSIGNED NOT NULL,
    `financing_method` VARCHAR(100) NOT NULL,
    `loan_base` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `loan_term` INT NOT NULL DEFAULT 0,
    `monthly_amortization` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `contract_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `mbrdc_add_fees` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `other_exp_annotation` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `improvements_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `inspection_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `retention_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amt_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `mbrdc_turn_over_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_consideration` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_by_vendee_equity` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `mbrdc_amt_due_for_financing` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `additional_bill_of_materials` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `loan_release` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_loan_proceeds` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `receivables` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `rbgi_ap` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `mbrdc_ap_in_house` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `account_receivables` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `remarks` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `loan_release_date` DATE NULL,
    `sri_mri` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pag_ibig_non_life` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `interim_mri` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `retention_on_hold` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paymongo_checkout_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_financing_records_buyer`
        FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_financing_records_lot`
        FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: financing_payment_histories
-- -----------------------------------------------------
DROP TABLE IF EXISTS `financing_payment_histories`;
CREATE TABLE `financing_payment_histories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `financing_record_id` BIGINT UNSIGNED NOT NULL,
    `amount_paid` DECIMAL(12,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `trn` VARCHAR(100) NULL,
    `payment_method` VARCHAR(100) NULL DEFAULT 'Office Payment',
    `paymongo_payment_id` VARCHAR(100) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Completed',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_financing_payment_histories_record`
        FOREIGN KEY (`financing_record_id`) REFERENCES `financing_records` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: utility_bills
-- -----------------------------------------------------
DROP TABLE IF EXISTS `utility_bills`;
CREATE TABLE `utility_bills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `lot_id` BIGINT UNSIGNED NOT NULL,
    `billed_to_name` VARCHAR(150) NULL,
    `type` VARCHAR(50) NOT NULL,
    `billing_period` VARCHAR(50) NULL,
    `previous_reading` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `previous_reading_date` DATE NULL,
    `current_reading` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `current_reading_date` DATE NULL,
    `usage_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL,
    `previous_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `due_date` DATE NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Unpaid',
    `is_at_risk` TINYINT(1) NOT NULL DEFAULT 0,
    `paymongo_checkout_id` VARCHAR(100) NULL,
    `paymongo_checkout_url` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_utility_bills_resident`
        FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_utility_bills_lot`
        FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: payments
-- -----------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utility_bill_id` BIGINT UNSIGNED NOT NULL,
    `amount_paid` DECIMAL(12,2) NOT NULL,
    `method` VARCHAR(50) NULL,
    `trn` VARCHAR(100) NOT NULL,
    `paymongo_payment_id` VARCHAR(100) NULL,
    `payment_date` DATE NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_payments_utility_bill`
        FOREIGN KEY (`utility_bill_id`) REFERENCES `utility_bills` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: incidents
-- -----------------------------------------------------
DROP TABLE IF EXISTS `incidents`;
CREATE TABLE `incidents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `reporter_name` VARCHAR(150) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) NULL,
    `video_url` VARCHAR(255) NULL,
    `admin_notes` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Open',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_incidents_resident`
        FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: visitors
-- -----------------------------------------------------
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resident_id` BIGINT UNSIGNED NULL,
    `guard_id` BIGINT UNSIGNED NULL,
    `host_name` VARCHAR(150) NULL,
    `guard_name` VARCHAR(150) NULL,
    `visitor_name` VARCHAR(150) NOT NULL,
    `visitor_address` TEXT NULL,
    `contact_number` VARCHAR(50) NULL,
    `pin` VARCHAR(20) NULL,
    `access_token` VARCHAR(100) NULL,
    `purpose` VARCHAR(255) NOT NULL,
    `validity` DATETIME NULL,
    `arrival_time` DATETIME NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `plate_number` VARCHAR(50) NULL,
    `type` VARCHAR(50) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_visitors_resident`
        FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_visitors_guard`
        FOREIGN KEY (`guard_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: announcements
-- -----------------------------------------------------
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `content` TEXT NOT NULL,
    `author` VARCHAR(150) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Draft',
    `publish_date` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_announcements_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: announcement_recipients (Pivot: Announcements and Users)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `announcement_recipients`;
CREATE TABLE `announcement_recipients` (
    `announcement_id` BIGINT UNSIGNED NOT NULL,
    `resident_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`announcement_id`, `resident_id`),
    CONSTRAINT `fk_announcement_recipients_announcement`
        FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_announcement_recipients_resident`
        FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: appointments
-- -----------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `last_name` VARCHAR(100) NULL,
    `first_name` VARCHAR(100) NULL,
    `middle_name` VARCHAR(100) NULL,
    `contact_number` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NULL,
    `type` VARCHAR(100) NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `notes` TEXT NULL,
    `report` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `pin` VARCHAR(20) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: notifications
-- -----------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` VARCHAR(36) NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `notifiable_type` VARCHAR(255) NOT NULL,
    `notifiable_id` BIGINT UNSIGNED NOT NULL,
    `data` TEXT NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `notifications_notifiable_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: settings
-- -----------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `key` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
