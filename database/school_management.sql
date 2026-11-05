-- =====================================================================
-- SIRAJGANJ ROAD CHOWRASTA HIGH SCHOOL MANAGEMENT SYSTEM
-- Location: Hatikumrul, Ullapara, Sirajganj, Bangladesh
-- Database Name: school_management
-- Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Storage Engine: InnoDB
-- Compatible with: MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `school_management` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `school_management`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table 1: roles
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 2: users
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 3: password_resets
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_password_resets_email` (`email`),
    INDEX `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 4: academic_sessions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `academic_sessions`;
CREATE TABLE `academic_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_name` VARCHAR(50) NOT NULL UNIQUE,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_current` TINYINT(1) DEFAULT 0,
    `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 5: classes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_name` VARCHAR(50) NOT NULL UNIQUE,
    `numeric_value` INT NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 6: sections
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT UNSIGNED NOT NULL,
    `section_name` VARCHAR(50) NOT NULL,
    `capacity` INT UNSIGNED DEFAULT 60,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_class_section` (`class_id`, `section_name`),
    INDEX `idx_sections_class` (`class_id`),
    CONSTRAINT `fk_sections_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 7: subjects
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject_code` VARCHAR(20) NOT NULL UNIQUE,
    `subject_name` VARCHAR(100) NOT NULL,
    `subject_type` ENUM('theory', 'practical', 'both') DEFAULT 'theory',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 8: class_subjects
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `class_subjects`;
CREATE TABLE `class_subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `full_marks` DECIMAL(5,2) DEFAULT 100.00,
    `pass_marks` DECIMAL(5,2) DEFAULT 33.00,
    `is_optional` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_class_subject` (`class_id`, `subject_id`),
    INDEX `idx_cs_class` (`class_id`),
    INDEX `idx_cs_subject` (`subject_id`),
    CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 9: teachers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `teacher_code` VARCHAR(50) NOT NULL UNIQUE,
    `designation` VARCHAR(100) NOT NULL,
    `qualification` VARCHAR(255) NOT NULL,
    `specialization` VARCHAR(150) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `blood_group` VARCHAR(10) NULL,
    `nid_number` VARCHAR(50) NULL,
    `joining_date` DATE NOT NULL,
    `address` TEXT NULL,
    `bio` TEXT NULL,
    `order_priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_teachers_user` (`user_id`),
    CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 10: guardians
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `guardians`;
CREATE TABLE `guardians` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `father_name` VARCHAR(150) NOT NULL,
    `father_occupation` VARCHAR(100) NULL,
    `father_phone` VARCHAR(30) NULL,
    `mother_name` VARCHAR(150) NOT NULL,
    `mother_occupation` VARCHAR(100) NULL,
    `mother_phone` VARCHAR(30) NULL,
    `guardian_name` VARCHAR(150) NOT NULL,
    `guardian_relation` VARCHAR(50) NOT NULL,
    `guardian_phone` VARCHAR(30) NOT NULL,
    `guardian_email` VARCHAR(150) NULL,
    `present_address` TEXT NOT NULL,
    `permanent_address` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 11: students
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `guardian_id` INT UNSIGNED NULL,
    `admission_no` VARCHAR(50) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `dob` DATE NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `blood_group` VARCHAR(10) NULL,
    `religion` VARCHAR(50) DEFAULT 'Islam',
    `birth_certificate_no` VARCHAR(50) NULL,
    `photo` VARCHAR(255) DEFAULT 'assets/images/default-student.png',
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `admission_date` DATE NOT NULL,
    `status` ENUM('active', 'graduated', 'transferred', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_guardian` (`guardian_id`),
    INDEX `idx_students_admission_no` (`admission_no`),
    CONSTRAINT `fk_students_guardian` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 12: enrollments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `roll_no` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'promoted', 'failed', 'transferred') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_session` (`student_id`, `session_id`),
    UNIQUE KEY `unique_roll_session_class_section` (`session_id`, `class_id`, `section_id`, `roll_no`),
    INDEX `idx_enroll_student` (`student_id`),
    INDEX `idx_enroll_session` (`session_id`),
    INDEX `idx_enroll_class_sec` (`class_id`, `section_id`),
    CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enroll_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 13: teacher_assignments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `teacher_assignments`;
CREATE TABLE `teacher_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT UNSIGNED NOT NULL,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `is_class_teacher` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_teacher_class_section_sub` (`session_id`, `class_id`, `section_id`, `subject_id`),
    INDEX `idx_ta_teacher` (`teacher_id`),
    INDEX `idx_ta_session` (`session_id`),
    INDEX `idx_ta_class_section` (`class_id`, `section_id`),
    CONSTRAINT `fk_ta_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ta_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ta_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ta_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ta_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 14: attendance
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `attendance_date` DATE NOT NULL,
    `status` ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    `remarks` VARCHAR(255) NULL,
    `marked_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_date` (`student_id`, `attendance_date`),
    INDEX `idx_att_class_date` (`class_id`, `section_id`, `attendance_date`),
    INDEX `idx_att_date` (`attendance_date`),
    CONSTRAINT `fk_att_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_att_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_att_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_att_marked_by` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 15: periods
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `periods`;
CREATE TABLE `periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period_name` VARCHAR(50) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_break` TINYINT(1) DEFAULT 0,
    `order_num` INT UNSIGNED DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 16: routines
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `routines`;
CREATE TABLE `routines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `period_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NULL,
    `teacher_id` INT UNSIGNED NULL,
    `day_of_week` ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    `room_no` VARCHAR(30) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_routine_slot` (`session_id`, `class_id`, `section_id`, `day_of_week`, `period_id`),
    INDEX `idx_routine_lookup` (`class_id`, `section_id`, `day_of_week`),
    INDEX `idx_routine_teacher` (`teacher_id`),
    CONSTRAINT `fk_routine_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_routine_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_routine_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_routine_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_routine_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_routine_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 17: grading_scales
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `grading_scales`;
CREATE TABLE `grading_scales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grade_name` VARCHAR(10) NOT NULL,
    `min_mark` DECIMAL(5,2) NOT NULL,
    `max_mark` DECIMAL(5,2) NOT NULL,
    `grade_point` DECIMAL(3,2) NOT NULL,
    `remarks` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 18: exams
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `exam_name` VARCHAR(150) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_published` TINYINT(1) DEFAULT 0,
    `description` TEXT NULL,
    `status` ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_exams_session` (`session_id`),
    CONSTRAINT `fk_exams_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 19: exam_subjects
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `exam_subjects`;
CREATE TABLE `exam_subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `exam_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `full_marks` DECIMAL(5,2) DEFAULT 100.00,
    `pass_marks` DECIMAL(5,2) DEFAULT 33.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_exam_class_subject` (`exam_id`, `class_id`, `subject_id`),
    INDEX `idx_es_exam` (`exam_id`),
    INDEX `idx_es_class` (`class_id`),
    CONSTRAINT `fk_es_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_es_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_es_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 20: marks
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `marks`;
CREATE TABLE `marks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `marks_obtained` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `grade_point` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `grade_letter` VARCHAR(5) NOT NULL DEFAULT 'F',
    `is_absent` TINYINT(1) DEFAULT 0,
    `remarks` VARCHAR(255) NULL,
    `entered_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_exam_student_subject` (`exam_id`, `student_id`, `subject_id`),
    INDEX `idx_marks_student` (`student_id`),
    INDEX `idx_marks_exam_class` (`exam_id`, `class_id`, `section_id`),
    CONSTRAINT `fk_marks_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_marks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_marks_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_marks_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_marks_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 21: results
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `total_marks` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `gpa` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    `final_grade` VARCHAR(10) NOT NULL DEFAULT 'F',
    `class_rank` INT UNSIGNED NULL,
    `result_status` ENUM('passed', 'failed', 'withheld') DEFAULT 'passed',
    `is_published` TINYINT(1) DEFAULT 0,
    `calculated_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_exam_student_result` (`exam_id`, `student_id`),
    INDEX `idx_results_lookup` (`exam_id`, `class_id`, `section_id`),
    INDEX `idx_results_student` (`student_id`),
    CONSTRAINT `fk_res_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_res_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_res_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_res_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_res_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 22: admissions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `admissions`;
CREATE TABLE `admissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_no` VARCHAR(50) NOT NULL UNIQUE,
    `desired_class_id` INT UNSIGNED NOT NULL,
    `session_id` INT UNSIGNED NOT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `dob` DATE NOT NULL,
    `blood_group` VARCHAR(10) NULL,
    `religion` VARCHAR(50) DEFAULT 'Islam',
    `previous_school` VARCHAR(200) NULL,
    `father_name` VARCHAR(150) NOT NULL,
    `father_phone` VARCHAR(30) NOT NULL,
    `mother_name` VARCHAR(150) NOT NULL,
    `guardian_phone` VARCHAR(30) NOT NULL,
    `present_address` TEXT NOT NULL,
    `permanent_address` TEXT NOT NULL,
    `photo` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `reviewed_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_status` (`status`),
    INDEX `idx_adm_class` (`desired_class_id`),
    CONSTRAINT `fk_adm_class` FOREIGN KEY (`desired_class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_adm_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_adm_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 23: fee_types
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `fee_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `frequency` ENUM('monthly', 'termly', 'yearly', 'one_time') DEFAULT 'monthly',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 24: student_fees
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `student_fees`;
CREATE TABLE `student_fees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `fee_type_id` INT UNSIGNED NOT NULL,
    `month` VARCHAR(20) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `status` ENUM('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sf_student` (`student_id`),
    INDEX `idx_sf_status` (`status`),
    CONSTRAINT `fk_sf_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sf_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sf_fee_type` FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 25: payments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
    `student_fee_id` INT UNSIGNED NOT NULL,
    `amount_paid` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash', 'bKash', 'Nagad', 'Rocket', 'Bank') DEFAULT 'Cash',
    `transaction_id` VARCHAR(100) NULL,
    `payment_date` DATE NOT NULL,
    `received_by` INT UNSIGNED NOT NULL,
    `remarks` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pay_invoice` (`invoice_no`),
    INDEX `idx_pay_fee` (`student_fee_id`),
    CONSTRAINT `fk_pay_student_fee` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pay_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 26: assignments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `section_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `teacher_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `assignment_type` ENUM('assignment', 'study_material') DEFAULT 'assignment',
    `due_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assign_lookup` (`class_id`, `section_id`, `subject_id`),
    INDEX `idx_assign_teacher` (`teacher_id`),
    CONSTRAINT `fk_asg_session` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asg_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asg_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asg_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asg_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 27: assignment_submissions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `assignment_submissions`;
CREATE TABLE `assignment_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `submission_text` TEXT NULL,
    `submitted_at` DATETIME NOT NULL,
    `marks` DECIMAL(5,2) NULL,
    `teacher_feedback` TEXT NULL,
    `status` ENUM('submitted', 'reviewed') DEFAULT 'submitted',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_assignment_student` (`assignment_id`, `student_id`),
    INDEX `idx_asub_assignment` (`assignment_id`),
    INDEX `idx_asub_student` (`student_id`),
    CONSTRAINT `fk_asub_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asub_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 28: notices
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `category` ENUM('academic', 'exam', 'admission', 'event', 'general') DEFAULT 'general',
    `target_audience` ENUM('all', 'students', 'teachers', 'public') DEFAULT 'all',
    `attachment` VARCHAR(255) NULL,
    `published_date` DATE NOT NULL,
    `is_pinned` TINYINT(1) DEFAULT 0,
    `status` ENUM('published', 'draft') DEFAULT 'published',
    `author_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_notices_status` (`status`, `published_date`),
    INDEX `idx_notices_category` (`category`),
    CONSTRAINT `fk_notices_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 29: events
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` TIME NULL,
    `location` VARCHAR(200) DEFAULT 'School Premises',
    `image` VARCHAR(255) NULL,
    `status` ENUM('upcoming', 'completed', 'cancelled') DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 30: galleries
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `galleries`;
CREATE TABLE `galleries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `category` ENUM('Campus', 'Sports', 'Cultural', 'Classroom', 'Events', 'Laboratory') DEFAULT 'Campus',
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) NULL,
    `order_num` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 31: settings
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 32: activity_logs
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_user` (`user_id`),
    INDEX `idx_logs_action` (`action`),
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA INSERTION
-- =====================================================================

-- 1. Roles
INSERT INTO `roles` (`id`, `role_name`, `display_name`, `description`) VALUES
(1, 'admin', 'Administrator', 'Full system privileges and configuration management'),
(2, 'teacher', 'Teacher', 'Classroom, attendance, assignments, and examination marks management');

-- 2. Users (Admin and Teachers)
-- Passwords:
-- admin: Admin@2026#Secure ($2y$10$ELw4SGRvd1gTRrAJQpxiIOu9PozkIfKV73MaDNU5U89pn2NEAzB8G)
-- teachers: Teacher@2026#Secure ($2y$10$MrwykH06XE0WZIGFLPiejeOBY42.tznwqgPc7uQSdUxdIMQ6WvMmS)
INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar`, `status`) VALUES
(1, 1, 'admin', 'admin@srchs.edu.bd', '$2y$10$ELw4SGRvd1gTRrAJQpxiIOu9PozkIfKV73MaDNU5U89pn2NEAzB8G', 'System Administrator', '+880 1711-000001', 'assets/images/default-avatar.png', 'active'),
(2, 2, 'headmaster', 'headmaster@srchs.edu.bd', '$2y$10$MrwykH06XE0WZIGFLPiejeOBY42.tznwqgPc7uQSdUxdIMQ6WvMmS', 'Md. Rafiqul Islam', '+880 1712-345671', 'assets/images/teachers/headmaster.jpg', 'active'),
(3, 2, 'rowshan.ara', 'rowshan@srchs.edu.bd', '$2y$10$MrwykH06XE0WZIGFLPiejeOBY42.tznwqgPc7uQSdUxdIMQ6WvMmS', 'Begum Rowshan Ara', '+880 1712-345672', 'assets/images/teachers/teacher-female.jpg', 'active'),
(4, 2, 'aminul.haque', 'aminul@srchs.edu.bd', '$2y$10$MrwykH06XE0WZIGFLPiejeOBY42.tznwqgPc7uQSdUxdIMQ6WvMmS', 'Md. Aminul Haque', '+880 1712-345673', 'assets/images/teachers/teacher-male1.jpg', 'active'),
(5, 2, 'sharmin.sultana', 'sharmin@srchs.edu.bd', '$2y$10$MrwykH06XE0WZIGFLPiejeOBY42.tznwqgPc7uQSdUxdIMQ6WvMmS', 'Sharmin Sultana', '+880 1712-345674', 'assets/images/teachers/teacher-female2.jpg', 'active');

-- 3. Teachers Profile
INSERT INTO `teachers` (`id`, `user_id`, `teacher_code`, `designation`, `qualification`, `specialization`, `date_of_birth`, `gender`, `blood_group`, `nid_number`, `joining_date`, `address`, `bio`, `order_priority`) VALUES
(1, 2, 'TCH-001', 'Headmaster', 'M.A. (English), B.Ed. (1st Class)', 'English Language & Educational Leadership', '1975-03-12', 'Male', 'B+', '19758812345678901', '2005-01-15', 'Hatikumrul, Ullapara, Sirajganj', 'Dedicated educator with 20+ years of administrative leadership fostering academic rigor, digital literacy, and student character.', 1),
(2, 3, 'TCH-002', 'Assistant Headmaster', 'M.Sc. in Mathematics, B.Ed.', 'Higher Mathematics & Statistics', '1980-07-24', 'Female', 'A+', '19808812345678902', '2010-03-01', 'Ullapara Pourashava, Sirajganj', 'Passionate mathematics educator specializing in foundational concept-building and analytical reasoning.', 2),
(3, 4, 'TCH-003', 'Senior Assistant Teacher', 'B.Sc. (Honours), M.Sc. in Physics', 'Physics & General Science', '1984-11-18', 'Male', 'O+', '19848812345678903', '2013-05-10', 'Salanga, Sirajganj', 'Experienced science instructor with strong focus on laboratory experiments, science fairs, and practical demonstrations.', 3),
(4, 5, 'TCH-004', 'Assistant Teacher', 'M.A. in Bengali Literature, B.Ed.', 'Bengali Literature & Cultural Affairs', '1988-09-05', 'Female', 'AB+', '19888812345678904', '2016-08-01', 'Hatikumrul, Ullapara, Sirajganj', 'Devoted to Bengali language, classical and modern literature, elocution, debating, and co-curricular development.', 4);

-- 4. Academic Sessions
INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_current`, `status`) VALUES
(1, '2026', '2026-01-01', '2026-12-31', 1, 'active'),
(2, '2025', '2025-01-01', '2025-12-31', 0, 'archived');

-- 5. Classes (Class 6 to Class 10)
INSERT INTO `classes` (`id`, `class_name`, `numeric_value`, `description`, `status`) VALUES
(1, 'Class 6', 6, 'Lower Secondary Grade 6 - NCTB Curriculum', 'active'),
(2, 'Class 7', 7, 'Lower Secondary Grade 7 - NCTB Curriculum', 'active'),
(3, 'Class 8', 8, 'Junior Secondary Grade 8 - Preparation for JSC/National Competencies', 'active'),
(4, 'Class 9', 9, 'Secondary Grade 9 - Science & Humanities Tracks', 'active'),
(5, 'Class 10', 10, 'Secondary Grade 10 - SSC Candidate Batch', 'active');

-- 6. Sections (Section A and Section B for each class)
INSERT INTO `sections` (`id`, `class_id`, `section_name`, `capacity`, `status`) VALUES
(1, 1, 'Section A', 60, 'active'),
(2, 1, 'Section B', 60, 'active'),
(3, 2, 'Section A', 60, 'active'),
(4, 2, 'Section B', 60, 'active'),
(5, 3, 'Section A', 60, 'active'),
(6, 3, 'Section B', 60, 'active'),
(7, 4, 'Section A', 60, 'active'),
(8, 4, 'Section B', 60, 'active'),
(9, 5, 'Section A', 60, 'active'),
(10, 5, 'Section B', 60, 'active');

-- 7. Subjects
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `subject_type`, `status`) VALUES
(1, '101', 'Bangla 1st Paper', 'theory', 'active'),
(2, '102', 'Bangla 2nd Paper', 'theory', 'active'),
(3, '107', 'English 1st Paper', 'theory', 'active'),
(4, '108', 'English 2nd Paper', 'theory', 'active'),
(5, '109', 'Mathematics', 'theory', 'active'),
(6, '127', 'General Science', 'both', 'active'),
(7, '150', 'Bangladesh & Global Studies', 'theory', 'active'),
(8, '154', 'Information & Communication Technology', 'both', 'active'),
(9, '111', 'Islam & Moral Education', 'theory', 'active'),
(10, '112', 'Hindu Religion & Moral Education', 'theory', 'active');

-- 8. Class Subjects (Mapping Subjects to Classes 6-10)
INSERT INTO `class_subjects` (`class_id`, `subject_id`, `full_marks`, `pass_marks`, `is_optional`) VALUES
-- Class 6
(1, 1, 100.00, 33.00, 0), (1, 2, 50.00, 17.00, 0), (1, 3, 100.00, 33.00, 0), 
(1, 4, 50.00, 17.00, 0), (1, 5, 100.00, 33.00, 0), (1, 6, 100.00, 33.00, 0), 
(1, 7, 100.00, 33.00, 0), (1, 8, 50.00, 17.00, 0), (1, 9, 100.00, 33.00, 0),
-- Class 7
(2, 1, 100.00, 33.00, 0), (2, 2, 50.00, 17.00, 0), (2, 3, 100.00, 33.00, 0), 
(2, 4, 50.00, 17.00, 0), (2, 5, 100.00, 33.00, 0), (2, 6, 100.00, 33.00, 0), 
(2, 7, 100.00, 33.00, 0), (2, 8, 50.00, 17.00, 0), (2, 9, 100.00, 33.00, 0),
-- Class 8
(3, 1, 100.00, 33.00, 0), (3, 2, 50.00, 17.00, 0), (3, 3, 100.00, 33.00, 0), 
(3, 4, 50.00, 17.00, 0), (3, 5, 100.00, 33.00, 0), (3, 6, 100.00, 33.00, 0), 
(3, 7, 100.00, 33.00, 0), (3, 8, 50.00, 17.00, 0), (3, 9, 100.00, 33.00, 0),
-- Class 9
(4, 1, 100.00, 33.00, 0), (4, 2, 100.00, 33.00, 0), (4, 3, 100.00, 33.00, 0), 
(4, 4, 100.00, 33.00, 0), (4, 5, 100.00, 33.00, 0), (4, 6, 100.00, 33.00, 0), 
(4, 7, 100.00, 33.00, 0), (4, 8, 50.00, 17.00, 0), (4, 9, 100.00, 33.00, 0),
-- Class 10
(5, 1, 100.00, 33.00, 0), (5, 2, 100.00, 33.00, 0), (5, 3, 100.00, 33.00, 0), 
(5, 4, 100.00, 33.00, 0), (5, 5, 100.00, 33.00, 0), (5, 6, 100.00, 33.00, 0), 
(5, 7, 100.00, 33.00, 0), (5, 8, 50.00, 17.00, 0), (5, 9, 100.00, 33.00, 0);

-- 9. Teacher Assignments (Session 2026)
INSERT INTO `teacher_assignments` (`teacher_id`, `session_id`, `class_id`, `section_id`, `subject_id`, `is_class_teacher`) VALUES
(1, 1, 5, 9, 3, 1), -- Headmaster Md. Rafiqul Islam: Class 10 Sec A, English 1st, Class Teacher
(1, 1, 4, 7, 3, 0), -- Headmaster: Class 9 Sec A, English 1st
(2, 1, 5, 9, 5, 0), -- Asst Headmaster Rowshan Ara: Class 10 Sec A, Mathematics
(2, 1, 4, 7, 5, 1), -- Asst Headmaster: Class 9 Sec A, Mathematics, Class Teacher
(3, 1, 5, 9, 6, 0), -- Aminul Haque: Class 10 Sec A, Science
(3, 1, 3, 5, 6, 1), -- Aminul Haque: Class 8 Sec A, Science, Class Teacher
(4, 1, 5, 9, 1, 0), -- Sharmin Sultana: Class 10 Sec A, Bangla 1st
(4, 1, 2, 3, 1, 1); -- Sharmin Sultana: Class 7 Sec A, Bangla 1st, Class Teacher

-- 10. Periods
INSERT INTO `periods` (`id`, `period_name`, `start_time`, `end_time`, `is_break`, `order_num`) VALUES
(1, '1st Period', '09:00:00', '09:45:00', 0, 1),
(2, '2nd Period', '09:45:00', '10:30:00', 0, 2),
(3, '3rd Period', '10:30:00', '11:15:00', 0, 3),
(4, '4th Period', '11:15:00', '12:00:00', 0, 4),
(5, 'Tiffin Break', '12:00:00', '12:45:00', 1, 5),
(6, '5th Period', '12:45:00', '13:30:00', 0, 6),
(7, '6th Period', '13:30:00', '14:15:00', 0, 7),
(8, '7th Period', '14:15:00', '15:00:00', 0, 8);

-- 11. Grading Scales (Bangladesh Secondary Education Board Standard)
INSERT INTO `grading_scales` (`id`, `grade_name`, `min_mark`, `max_mark`, `grade_point`, `remarks`) VALUES
(1, 'A+', 80.00, 100.00, 5.00, 'Outstanding'),
(2, 'A',  70.00, 79.99,  4.00, 'Excellent'),
(3, 'A-', 60.00, 69.99,  3.50, 'Very Good'),
(4, 'B',  50.00, 59.99,  3.00, 'Good'),
(5, 'C',  40.00, 49.99,  2.00, 'Satisfactory'),
(6, 'D',  33.00, 39.99,  1.00, 'Pass'),
(7, 'F',  0.00,  32.99,  0.00, 'Fail');

-- 12. Fee Types
INSERT INTO `fee_types` (`id`, `fee_name`, `description`, `amount`, `frequency`, `status`) VALUES
(1, 'Monthly Tuition Fee', 'Regular monthly tuition fee for class students', 500.00, 'monthly', 'active'),
(2, 'Admission & Registration Fee', 'One-time admission charge for new students', 2500.00, 'one_time', 'active'),
(3, 'Examination Fee', 'Term-end examination fee', 600.00, 'termly', 'active'),
(4, 'Session Development Charge', 'Annual session development and sports/library fee', 1500.00, 'yearly', 'active'),
(5, 'ICT Laboratory Fee', 'Computer and digital lab maintenance charge', 300.00, 'termly', 'active');

-- 13. System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('school_name', 'Sirajganj Road Chowrasta High School', 'general', 'Official name of the institution'),
('school_short_name', 'SRCHS', 'general', 'Acronym/short name'),
('school_eiin', '128456', 'general', 'Educational Institute Identification Number (EIIN)'),
('school_estd', '1974', 'general', 'Year of establishment'),
('school_board', 'Rajshahi Education Board', 'general', 'Affiliated Secondary Education Board'),
('school_address', 'Hatikumrul, Ullapara, Sirajganj, Bangladesh', 'contact', 'Full campus postal address'),
('school_phone', '+880 1712-345678', 'contact', 'Primary office telephone/mobile'),
('school_email', 'info@srchs.edu.bd', 'contact', 'Official institutional email address'),
('website_meta_title', 'Sirajganj Road Chowrasta High School | Hatikumrul, Sirajganj', 'seo', 'Meta title for public portal'),
('website_meta_description', 'Sirajganj Road Chowrasta High School is a premier secondary institution located at Hatikumrul, Ullapara, Sirajganj, offering quality education for Class 6 to 10.', 'seo', 'SEO meta description'),
('headmaster_name', 'Md. Rafiqul Islam', 'leadership', 'Name of current Headmaster'),
('headmaster_designation', 'Headmaster, Sirajganj Road Chowrasta High School', 'leadership', 'Official title of Headmaster'),
('headmaster_quote', 'Empowering youth with intellectual rigor, moral integrity, and technological fluency to become enlightened architects of Bangladesh.', 'leadership', 'Headmaster welcome quote'),
('currency_symbol', '৳', 'finance', 'National currency symbol (BDT)'),
('active_session_id', '1', 'academic', 'ID of active academic session'),
('online_admission_status', 'open', 'admission', 'Admission portal status (open/closed)');

-- 14. Sample Exams
INSERT INTO `exams` (`id`, `session_id`, `exam_name`, `start_date`, `end_date`, `is_published`, `description`, `status`) VALUES
(1, 1, 'Half-Yearly Examination 2026', '2026-06-15', '2026-06-30', 0, 'Mid-term assessment for all classes (Class 6 to Class 10)', 'upcoming'),
(2, 1, 'First Term Assessment 2026', '2026-03-20', '2026-03-28', 1, 'First quarterly progress evaluation', 'completed');

-- 15. Sample Notices
INSERT INTO `notices` (`id`, `title`, `content`, `category`, `target_audience`, `published_date`, `is_pinned`, `status`, `author_id`) VALUES
(1, 'Online Admission Open for Academic Session 2026 (Classes 6-9)', 'Applications are cordially invited for admission into Classes 6 through 9 for the Academic Session 2026. Interested parents and guardians may fill out the online admission form through this website or collect physical forms directly from the school office between 9:00 AM and 3:00 PM.', 'admission', 'all', '2026-01-05', 1, 'published', 1),
(2, 'Schedule of First Term Assessment Examination 2026', 'The First Term Assessment Examination for all enrolled students of Class 6 to Class 10 will commence on March 20, 2026. Detailed subject-wise routine and seat plan are available on the notice board and student portal. All students are directed to clear any outstanding tuition fees prior to exam card collection.', 'exam', 'all', '2026-03-01', 1, 'published', 1),
(3, 'Observance of International Mother Language Day', 'Sirajganj Road Chowrasta High School will observe International Mother Language Day on February 21 with utmost solemnity. Programs include floral wreath laying at Shahid Minar at 7:00 AM, prabhat pheri, discussion session, and patriotic cultural competition.', 'event', 'all', '2026-02-15', 0, 'published', 2),
(4, 'Parent-Teacher Conference for Class 10 SSC Candidates', 'An essential Parent-Teacher Conference for guardians of SSC candidate students will be held in the school auditorium on Saturday at 10:30 AM to review preparatory performance and model test outcomes.', 'academic', 'all', '2026-03-10', 0, 'published', 2);

-- 16. Sample Events
INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `location`, `image`, `status`) VALUES
(1, 'Annual Sports Competition & Prize Giving Ceremony', 'The grand annual sports meet featuring sprint, long jump, high jump, gymnastics, and obstacle races, followed by certificate and trophy distribution by honorable local dignitaries.', '2026-02-10', '09:00:00', 'School Main Playground', 'assets/images/events/sports-day.jpg', 'completed'),
(2, 'Inter-Class Science Fair & Digital Exhibition 2026', 'Young innovators showcasing scientific models, robotics prototypes, renewable energy solutions, and environmental science exhibits judged by university faculty.', '2026-04-18', '10:00:00', 'School Auditorium & Science Lab', 'assets/images/events/science-fair.jpg', 'upcoming'),
(3, 'Bangla Nababarsha 1433 Celebration (Pohela Boishakh)', 'Welcoming the Bengali New Year with traditional Mongol Shobhajatra, sweet distribution, folk music, recitation, and cultural presentation by students and staff.', '2026-04-14', '08:30:00', 'Banyan Tree Yard, Campus', 'assets/images/events/pohela-boishakh.jpg', 'upcoming');

-- 17. Sample Photo Galleries
INSERT INTO `galleries` (`id`, `title`, `category`, `image_path`, `caption`, `order_num`) VALUES
(1, 'School Front Facade & Administrative Building', 'Campus', 'assets/images/gallery/campus-main.jpg', 'The two-story administrative building and lush central courtyard of SRCHS', 1),
(2, 'Well-Equipped Modern Computer Lab', 'Laboratory', 'assets/images/gallery/computer-lab.jpg', 'Students engaged in interactive digital learning and coding practice', 2),
(3, 'High School Science Laboratory', 'Laboratory', 'assets/images/gallery/science-lab.jpg', 'Physics and Chemistry practical experiments under senior teacher supervision', 3),
(4, 'Annual Athletic Track Events', 'Sports', 'assets/images/gallery/sports-track.jpg', 'Exciting 100-meter dash during Annual Sports Meet', 4),
(5, 'School Assembly and National Anthem', 'Campus', 'assets/images/gallery/assembly.jpg', 'Morning physical assembly and national flag salute by students', 5),
(6, 'Cultural Dance and Music Presentation', 'Cultural', 'assets/images/gallery/cultural-event.jpg', 'Students performing traditional folk song and drama', 6);

-- 18. Sample Guardians, Students, and Enrollments for Initial Verification
INSERT INTO `guardians` (`id`, `father_name`, `father_occupation`, `father_phone`, `mother_name`, `mother_occupation`, `mother_phone`, `guardian_name`, `guardian_relation`, `guardian_phone`, `guardian_email`, `present_address`, `permanent_address`) VALUES
(1, 'Md. Abul Kalam', 'Businessman', '+880 1715-111222', 'Rasheda Begum', 'Homemaker', '+880 1715-333444', 'Md. Abul Kalam', 'Father', '+880 1715-111222', 'kalam@example.com', 'Hatikumrul Bazar, Ullapara, Sirajganj', 'Hatikumrul Bazar, Ullapara, Sirajganj'),
(2, 'Abdul Mannan', 'Govt Employee', '+880 1716-222333', 'Hosne Ara', 'Teacher', '+880 1716-444555', 'Abdul Mannan', 'Father', '+880 1716-222333', 'mannan@example.com', 'Salanga, Ullapara, Sirajganj', 'Salanga, Ullapara, Sirajganj');

INSERT INTO `students` (`id`, `guardian_id`, `admission_no`, `first_name`, `last_name`, `dob`, `gender`, `blood_group`, `religion`, `birth_certificate_no`, `photo`, `phone`, `email`, `admission_date`, `status`) VALUES
(1, 1, 'SRCHS-2026-001', 'Tanvir', 'Hasan', '2010-04-15', 'Male', 'A+', 'Islam', '2010881234500001', 'assets/images/default-student.png', '+880 1715-111222', 'tanvir.hasan@example.com', '2026-01-02', 'active'),
(2, 2, 'SRCHS-2026-002', 'Nusrat', 'Jahan', '2010-08-20', 'Female', 'O+', 'Islam', '2010881234500002', 'assets/images/default-student.png', '+880 1716-222333', 'nusrat.jahan@example.com', '2026-01-03', 'active');

INSERT INTO `enrollments` (`id`, `student_id`, `session_id`, `class_id`, `section_id`, `roll_no`, `status`) VALUES
(1, 1, 1, 5, 9, 1, 'active'), -- Class 10, Sec A, Roll 1
(2, 2, 1, 5, 9, 2, 'active'); -- Class 10, Sec A, Roll 2
