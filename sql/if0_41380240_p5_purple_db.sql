-- P.5 PURPLE CLASSROOM MANAGEMENT SYSTEM
-- Complete Database for RAYS OF GRACE Junior School
-- Class: P.5 Purple | Teacher: [Your Name] | Year: 2026
-- Slogan: "Purple Hearts, Bright Minds"

-- Drop database if exists (optional - comment out if you don't want to overwrite)
-- DROP DATABASE IF EXISTS p5_purple_classroom;

-- CREATE DATABASE IF NOT EXISTS p5_purple_classroom;
-- USE p5_purple_classroom;
USE if0_41380240_p5_purple_db;

-- ============================================
-- TABLE 1: PUBLIC HOLIDAYS (Uganda 2026)
-- ============================================
CREATE TABLE IF NOT EXISTS public_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(100) NOT NULL,
    holiday_type ENUM('Public Holiday', 'Religious', 'Observance') DEFAULT 'Public Holiday',
    year INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO public_holidays (holiday_date, holiday_name, year) VALUES
('2026-01-01', 'New Year\'s Day', 2026),
('2026-01-26', 'Liberation Day', 2026),
('2026-02-16', 'Archbishop Janani Luwum Day', 2026),
('2026-03-20', 'Eid al-Fitr', 2026),
('2026-04-03', 'Good Friday', 2026),
('2026-04-06', 'Easter Monday', 2026),
('2026-05-01', 'Labour Day', 2026),
('2026-05-27', 'Eid al-Adha', 2026),
('2026-06-03', 'Martyrs\' Day', 2026),
('2026-06-09', 'National Heroes Day', 2026),
('2026-10-09', 'Independence Day', 2026),
('2026-12-25', 'Christmas Day', 2026),
('2026-12-26', 'Boxing Day', 2026);

-- ============================================
-- TABLE 2: ACADEMIC TERMS (2026)
-- ============================================
CREATE TABLE IF NOT EXISTS academic_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    term_number INT NOT NULL,
    term_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    beginning_exam_start DATE,
    beginning_exam_end DATE,
    mid_term_exam_start DATE,
    mid_term_exam_end DATE,
    end_term_exam_start DATE,
    end_term_exam_end DATE,
    visitation_day DATE,
    sports_day DATE,
    speech_day DATE,
    mid_term_break_start DATE,
    mid_term_break_end DATE,
    report_card_date DATE,
    is_active BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_term (year, term_number)
);

-- Insert 2026 Terms
INSERT INTO academic_terms (year, term_number, term_name, start_date, end_date,
    beginning_exam_start, beginning_exam_end,
    mid_term_exam_start, mid_term_exam_end,
    end_term_exam_start, end_term_exam_end,
    visitation_day, sports_day, speech_day,
    mid_term_break_start, mid_term_break_end,
    report_card_date, is_active) VALUES
-- Term 1
(2026, 1, 'First Term', '2026-02-03', '2026-05-02',
 '2026-02-10', '2026-02-14',
 '2026-03-10', '2026-03-14',
 '2026-04-20', '2026-04-30',
 '2026-03-21', NULL, NULL,
 '2026-03-16', '2026-03-20',
 '2026-05-05', TRUE),
-- Term 2
(2026, 2, 'Second Term', '2026-05-20', '2026-08-15',
 '2026-05-25', '2026-05-29',
 '2026-06-25', '2026-06-29',
 '2026-08-05', '2026-08-15',
 '2026-07-04', '2026-07-18', NULL,
 '2026-06-30', '2026-07-03',
 '2026-08-18', FALSE),
-- Term 3
(2026, 3, 'Third Term', '2026-09-10', '2026-12-10',
 '2026-09-15', '2026-09-19',
 '2026-10-20', '2026-10-24',
 '2026-11-25', '2026-12-05',
 '2026-10-31', NULL, '2026-11-30',
 '2026-10-26', '2026-10-30',
 '2026-12-12', FALSE);

-- ============================================
-- TABLE 3: STUDENTS (Your P.5 Purple Class)
-- ============================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_number VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE,
    student_type ENUM('Day Scholar', 'Boarder') NOT NULL,
    
    -- Enrollment by term
    enrolled_term1 BOOLEAN DEFAULT TRUE,
    enrolled_term2 BOOLEAN DEFAULT TRUE,
    enrolled_term3 BOOLEAN DEFAULT TRUE,
    
    -- Parent/Guardian Information
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    parent_relationship VARCHAR(50),
    
    -- Emergency Contact
    emergency_name VARCHAR(100),
    emergency_phone VARCHAR(20),
    emergency_relationship VARCHAR(50),
    
    -- Medical Information
    blood_group VARCHAR(5),
    allergies TEXT,
    medical_conditions TEXT,
    
    -- Address
    home_district VARCHAR(100),
    home_village VARCHAR(100),
    
    -- School Information
    joined_date DATE,
    previous_school VARCHAR(200),
    status ENUM('Active', 'Inactive', 'Transferred', 'Graduated') DEFAULT 'Active',
    
    -- Extracurricular
    soccer_academy BOOLEAN DEFAULT FALSE,
    other_activities TEXT,
    
    -- Boarding Information
    dormitory_number VARCHAR(20),
    bed_number VARCHAR(20),
    
    photo_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_admission (admission_number),
    INDEX idx_status (status),
    INDEX idx_type (student_type)
);

-- ============================================
-- TABLE 4: DAILY SCHEDULE (Normal School Day)
-- ============================================
CREATE TABLE IF NOT EXISTS daily_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    period_number INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject VARCHAR(50) NOT NULL,
    teacher_name VARCHAR(100) DEFAULT 'Class Teacher',
    room VARCHAR(50) DEFAULT 'P.5 Purple Classroom',
    UNIQUE KEY unique_period (day_of_week, period_number)
);

-- Insert Monday Schedule (copy for other days)
INSERT INTO daily_schedule (day_of_week, period_number, start_time, end_time, subject) VALUES
('Monday', 1, '08:00:00', '08:40:00', 'English'),
('Monday', 2, '08:40:00', '09:20:00', 'Mathematics'),
('Monday', 3, '09:20:00', '10:00:00', 'Integrated Science'),
('Monday', 4, '10:00:00', '10:40:00', 'BREAK'),
('Monday', 5, '10:40:00', '11:20:00', 'Social Studies'),
('Monday', 6, '11:20:00', '12:00:00', 'Religious Education'),
('Monday', 7, '12:00:00', '12:40:00', 'Kiswahili'),
('Monday', 8, '12:40:00', '14:00:00', 'LUNCH'),
('Monday', 9, '14:00:00', '14:40:00', 'Reading'),
('Monday', 10, '14:40:00', '15:20:00', 'Writing'),
('Monday', 11, '15:20:00', '16:00:00', 'P.E.');

-- Copy Monday schedule to other days
INSERT INTO daily_schedule (day_of_week, period_number, start_time, end_time, subject)
SELECT 'Tuesday', period_number, start_time, end_time, subject
FROM daily_schedule WHERE day_of_week = 'Monday';

INSERT INTO daily_schedule (day_of_week, period_number, start_time, end_time, subject)
SELECT 'Wednesday', period_number, start_time, end_time, subject
FROM daily_schedule WHERE day_of_week = 'Monday';

INSERT INTO daily_schedule (day_of_week, period_number, start_time, end_time, subject)
SELECT 'Thursday', period_number, start_time, end_time, subject
FROM daily_schedule WHERE day_of_week = 'Monday';

INSERT INTO daily_schedule (day_of_week, period_number, start_time, end_time, subject)
SELECT 'Friday', period_number, start_time, end_time, subject
FROM daily_schedule WHERE day_of_week = 'Monday';

-- ============================================
-- TABLE 5: DAILY ROUTINES (Boarding Schedule)
-- ============================================
CREATE TABLE IF NOT EXISTS daily_routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_date DATE NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    
    -- Morning Prep (6:00 AM - 7:30 AM)
    morning_prep_attendance INT,
    morning_prep_supervisor VARCHAR(100),
    morning_prep_notes TEXT,
    
    -- Evening Prayers (5:30 PM - 6:30 PM)
    evening_prayer_attendance INT,
    prayer_conducted_by VARCHAR(100),
    prayer_notes TEXT,
    
    -- Evening Prep (6:30 PM - 9:00 PM)
    evening_prep_attendance INT,
    prep_supervisor VARCHAR(100),
    prep_notes TEXT,
    
    -- Dormitory
    dormitory_head VARCHAR(100),
    lights_out_check BOOLEAN DEFAULT FALSE,
    lights_out_time TIME,
    dormitory_notes TEXT,
    
    recorded_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_routine (routine_date)
);

-- ============================================
-- TABLE 6: ATTENDANCE (Complete Roll Call System)
-- ============================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    
    -- Student Type (for validation)
    student_type ENUM('Day Scholar', 'Boarder') NOT NULL,
    
    -- Is this a public holiday?
    is_public_holiday BOOLEAN DEFAULT FALSE,
    holiday_name VARCHAR(100),
    
    -- ===== MORNING PREP ROLL CALL (6:00 AM - 7:30 AM) =====
    morning_prep_taken BOOLEAN DEFAULT FALSE,
    morning_prep_attended BOOLEAN DEFAULT NULL,
    morning_prep_time TIME,
    morning_prep_notes TEXT,
    
    -- ===== MORNING ROLL CALL (8:00 AM - All students) =====
    morning_taken BOOLEAN DEFAULT FALSE,
    morning_status ENUM('Present', 'Absent', 'Late', 'Excused') DEFAULT 'Present',
    morning_arrival_time TIME,
    morning_taken_by VARCHAR(100),
    morning_notes TEXT,
    
    -- ===== AFTERNOON ROLL CALL (3:30 PM - Day Scholars Departure) =====
    afternoon_taken BOOLEAN DEFAULT FALSE,
    afternoon_status ENUM('Present', 'Absent', 'Excused', 'Departed') DEFAULT 'Present',
    afternoon_departure_time TIME,
    afternoon_taken_by VARCHAR(100),
    afternoon_notes TEXT,
    
    -- ===== EVENING PRAYERS (5:30 PM - 6:30 PM - Boarders only) =====
    evening_prayer_taken BOOLEAN DEFAULT FALSE,
    evening_prayer_attended BOOLEAN DEFAULT FALSE,
    evening_prayer_time TIME,
    evening_prayer_notes TEXT,
    
    -- ===== EVENING PREP (6:30 PM - 9:00 PM - Boarders only) =====
    evening_prep_taken BOOLEAN DEFAULT FALSE,
    evening_prep_attended BOOLEAN DEFAULT FALSE,
    evening_prep_start_time TIME,
    evening_prep_end_time TIME,
    evening_prep_notes TEXT,
    
    -- ===== FINAL ROLL CALL (9:00 PM - After prep) =====
    final_roll_call_taken BOOLEAN DEFAULT FALSE,
    final_roll_call_status ENUM('Present', 'Absent', 'Excused') DEFAULT 'Present',
    final_roll_call_time TIME,
    final_roll_call_notes TEXT,
    
    -- ===== LIGHTS OUT CHECK (9:30 PM) =====
    lights_out_check BOOLEAN DEFAULT FALSE,
    lights_out_time TIME,
    dormitory_notes TEXT,
    
    -- ===== SOCCER ACADEMY =====
    soccer_training_today BOOLEAN DEFAULT FALSE,
    soccer_attended BOOLEAN DEFAULT NULL,
    soccer_departure_time TIME,
    soccer_return_time TIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, date),
    
    INDEX idx_date (date),
    INDEX idx_term (year, term),
    INDEX idx_morning_status (morning_status)
);

-- ============================================
-- TABLE 7: ASSESSMENTS (3 Exams per Term)
-- ============================================
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    term INT NOT NULL,
    exam_type ENUM('Beginning', 'Mid-term', 'End of Term') NOT NULL,
    subject ENUM('English', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Kiswahili') NOT NULL,
    score DECIMAL(5,2),
    
    -- We'll calculate grades in application code instead of using generated columns
    teacher_comments TEXT,
    exam_date DATE,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assessment (student_id, year, term, exam_type, subject),
    
    INDEX idx_student (student_id),
    INDEX idx_term (year, term)
);

-- ============================================
-- TABLE 8: REPORT CARDS
-- ============================================
CREATE TABLE IF NOT EXISTS report_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    year INT NOT NULL,
    term INT NOT NULL,
    
    -- Subject Scores (Beginning, Mid-term, End)
    english_beginning DECIMAL(5,2),
    english_midterm DECIMAL(5,2),
    english_end DECIMAL(5,2),
    
    mathematics_beginning DECIMAL(5,2),
    mathematics_midterm DECIMAL(5,2),
    mathematics_end DECIMAL(5,2),
    
    science_beginning DECIMAL(5,2),
    science_midterm DECIMAL(5,2),
    science_end DECIMAL(5,2),
    
    social_beginning DECIMAL(5,2),
    social_midterm DECIMAL(5,2),
    social_end DECIMAL(5,2),
    
    religious_beginning DECIMAL(5,2),
    religious_midterm DECIMAL(5,2),
    religious_end DECIMAL(5,2),
    
    kiswahili_beginning DECIMAL(5,2),
    kiswahili_midterm DECIMAL(5,2),
    kiswahili_end DECIMAL(5,2),
    
    class_position INT,
    out_of INT DEFAULT 40,
    
    -- Attendance
    days_present INT,
    days_absent INT,
    days_late INT,
    
    -- Conduct
    conduct ENUM('Excellent', 'Very Good', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
    attitude ENUM('Excellent', 'Very Good', 'Good', 'Fair', 'Poor') DEFAULT 'Good',
    
    class_teacher_remarks TEXT,
    headteacher_remarks TEXT,
    
    visitation_attended BOOLEAN DEFAULT FALSE,
    parent_feedback TEXT,
    
    generated_date DATE,
    published BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_report (student_id, year, term),
    
    INDEX idx_student_term (student_id, year, term)
);

-- ============================================
-- TABLE 9: VISITATION DAY RECORDS
-- ============================================
CREATE TABLE IF NOT EXISTS visitation_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    visitation_date DATE NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    
    parent_attended BOOLEAN DEFAULT FALSE,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    
    meeting_with_teacher BOOLEAN DEFAULT FALSE,
    teacher_notes TEXT,
    
    items_brought TEXT,
    pocket_money_given DECIMAL(10,2),
    
    academic_discussed BOOLEAN DEFAULT FALSE,
    behavior_discussed BOOLEAN DEFAULT FALSE,
    
    follow_up_needed BOOLEAN DEFAULT FALSE,
    follow_up_notes TEXT,
    
    recorded_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_visitation (student_id, visitation_date),
    
    INDEX idx_date (visitation_date)
);

-- ============================================
-- TABLE 10: BEHAVIOR LOG
-- ============================================
CREATE TABLE IF NOT EXISTS behavior_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    log_date DATE NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    
    behavior_type ENUM('Positive', 'Warning', 'Incident', 'Achievement') NOT NULL,
    description TEXT NOT NULL,
    action_taken TEXT,
    
    points_awarded INT DEFAULT 0,
    parent_notified BOOLEAN DEFAULT FALSE,
    parent_notification_date DATE,
    
    recorded_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_date (log_date)
);

-- ============================================
-- TABLE 11: PARENT COMMUNICATION
-- ============================================
CREATE TABLE IF NOT EXISTS parent_communication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    communication_date DATE NOT NULL,
    term INT NOT NULL,
    year INT NOT NULL DEFAULT 2026,
    
    parent_name VARCHAR(100),
    contact_method ENUM('Phone Call', 'Email', 'In Person', 'Note', 'WhatsApp', 'Visitation Day') NOT NULL,
    purpose ENUM('Academic', 'Behavior', 'Attendance', 'Health', 'Fees', 'Visitation', 'General', 'Emergency') NOT NULL,
    notes TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    
    recorded_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_date (communication_date)
);

-- ============================================
-- TABLE 12: EXTRACURRICULAR (Soccer Academy)
-- ============================================
CREATE TABLE IF NOT EXISTS extracurricular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    activity_type ENUM('Soccer Academy', 'Other') NOT NULL,
    training_days VARCHAR(100),
    training_start_time TIME,
    training_end_time TIME,
    coach_name VARCHAR(100),
    coach_phone VARCHAR(20),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    notes TEXT,
    joined_date DATE,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE 13: TEACHER NOTES
-- ============================================
CREATE TABLE IF NOT EXISTS teacher_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_date DATE NOT NULL,
    category ENUM('General', 'Staff Meeting', 'Parent Meeting', 'Visitation Day', 'Exam Planning', 'Holiday', 'Reminder', 'Idea') DEFAULT 'General',
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    reminder_date DATE,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date (note_date),
    INDEX idx_category (category)
);

-- ============================================
-- SAMPLE STUDENT DATA (20 Students)
-- ============================================
INSERT INTO students (admission_number, full_name, gender, student_type, 
    parent_name, parent_phone, soccer_academy, dormitory_number, bed_number) VALUES
('P5-001', 'Akello Mary', 'Female', 'Day Scholar', 'Mr. Akello John', '256701234567', FALSE, NULL, NULL),
('P5-002', 'Okello James', 'Male', 'Boarder', 'Mrs. Okello Sarah', '256702345678', TRUE, 'D-101', 'B-12'),
('P5-003', 'Nambi Grace', 'Female', 'Day Scholar', 'Mr. Nambi Peter', '256703456789', FALSE, NULL, NULL),
('P5-004', 'Kato Samuel', 'Male', 'Boarder', 'Mrs. Kato Ruth', '256704567890', TRUE, 'D-102', 'B-08'),
('P5-005', 'Nakato Esther', 'Female', 'Boarder', 'Mr. Nakato David', '256705678901', FALSE, 'D-101', 'B-15'),
('P5-006', 'Mugisha Brian', 'Male', 'Day Scholar', 'Mrs. Mugisha Grace', '256706789012', TRUE, NULL, NULL),
('P5-007', 'Auma Sarah', 'Female', 'Day Scholar', 'Mr. Auma Michael', '256707890123', FALSE, NULL, NULL),
('P5-008', 'Odongo Peter', 'Male', 'Boarder', 'Mrs. Odongo Janet', '256708901234', TRUE, 'D-102', 'B-10'),
('P5-009', 'Nabatanzi Joyce', 'Female', 'Boarder', 'Mr. Nabatanzi Robert', '256709012345', FALSE, 'D-101', 'B-20'),
('P5-010', 'Ssali Isaac', 'Male', 'Day Scholar', 'Mrs. Ssali Florence', '256700123456', TRUE, NULL, NULL),
('P5-011', 'Achieng Pamela', 'Female', 'Boarder', 'Mr. Achieng John', '256711223344', FALSE, 'D-101', 'B-22'),
('P5-012', 'Wasswa Hassan', 'Male', 'Day Scholar', 'Mrs. Wasswa Mariam', '256722334455', TRUE, NULL, NULL),
('P5-013', 'Nakanwagi Grace', 'Female', 'Boarder', 'Mr. Nakanwagi Paul', '256733445566', FALSE, 'D-101', 'B-18'),
('P5-014', 'Muhammad Ali', 'Male', 'Boarder', 'Mrs. Muhammad Fatima', '256744556677', TRUE, 'D-102', 'B-05'),
('P5-015', 'Kemigisha Annet', 'Female', 'Day Scholar', 'Mr. Kemigisha Tom', '256755667788', FALSE, NULL, NULL),
('P5-016', 'Ochieng David', 'Male', 'Boarder', 'Mrs. Ochieng Rose', '256766778899', TRUE, 'D-102', 'B-14'),
('P5-017', 'Nabukeera Hadijah', 'Female', 'Day Scholar', 'Mr. Nabukeera Yusuf', '256777889900', FALSE, NULL, NULL),
('P5-018', 'Ssenyonga Ivan', 'Male', 'Boarder', 'Mrs. Ssenyonga Sarah', '256788990011', TRUE, 'D-101', 'B-07'),
('P5-019', 'Nantongo Teddy', 'Female', 'Boarder', 'Mr. Nantongo Moses', '256799001122', FALSE, 'D-101', 'B-25'),
('P5-020', 'Lubega Richard', 'Male', 'Day Scholar', 'Mrs. Lubega Jane', '256700112233', TRUE, NULL, NULL);

-- ============================================
-- SAMPLE SOCCER ACADEMY ENTRIES
-- ============================================
INSERT INTO extracurricular (student_id, activity_type, training_days, training_start_time, training_end_time, coach_name, status) VALUES
(2, 'Soccer Academy', 'Tuesday, Thursday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(4, 'Soccer Academy', 'Tuesday, Thursday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(6, 'Soccer Academy', 'Monday, Wednesday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(8, 'Soccer Academy', 'Tuesday, Thursday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(12, 'Soccer Academy', 'Monday, Wednesday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(14, 'Soccer Academy', 'Tuesday, Thursday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(16, 'Soccer Academy', 'Monday, Wednesday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(18, 'Soccer Academy', 'Tuesday, Thursday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active'),
(20, 'Soccer Academy', 'Monday, Wednesday', '15:30:00', '17:00:00', 'Coach Mukasa', 'Active');

-- ============================================
-- SAMPLE ATTENDANCE FOR TODAY
-- ============================================
-- Day Scholars
INSERT INTO attendance (student_id, date, term, year, student_type, 
    morning_taken, morning_status, morning_arrival_time,
    afternoon_taken, afternoon_status, afternoon_departure_time) VALUES
(1, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '07:55:00', TRUE, 'Departed', '15:30:00'),
(3, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '07:50:00', TRUE, 'Departed', '15:30:00'),
(6, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '08:05:00', TRUE, 'Departed', '15:30:00'),
(7, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Absent', NULL, FALSE, NULL, NULL),
(10, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '07:45:00', TRUE, 'Departed', '15:30:00'),
(15, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Late', '08:15:00', TRUE, 'Departed', '15:30:00'),
(17, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '07:50:00', TRUE, 'Departed', '15:30:00'),
(20, CURDATE(), 1, 2026, 'Day Scholar', TRUE, 'Present', '07:55:00', TRUE, 'Departed', '15:30:00');

-- Boarders
INSERT INTO attendance (student_id, date, term, year, student_type,
    morning_taken, morning_status, morning_arrival_time,
    afternoon_taken, afternoon_status,
    evening_prayer_taken, evening_prayer_attended,
    evening_prep_taken, evening_prep_attended,
    final_roll_call_taken, final_roll_call_status,
    lights_out_check) VALUES
(2, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:50:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(4, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:45:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(5, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:55:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(8, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Late', '08:10:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(9, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:50:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(11, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:55:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(13, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:48:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(14, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:52:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(16, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:47:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(18, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:53:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE),
(19, CURDATE(), 1, 2026, 'Boarder',
 TRUE, 'Present', '07:49:00',
 TRUE, 'Present',
 TRUE, TRUE,
 TRUE, TRUE,
 TRUE, 'Present',
 TRUE);

-- ============================================
-- SAMPLE ASSESSMENTS (Term 1 Beginning Exams)
-- ============================================
INSERT INTO assessments (student_id, year, term, exam_type, subject, score, exam_date) VALUES
-- Akello Mary
(1, 2026, 1, 'Beginning', 'English', 75, '2026-02-10'),
(1, 2026, 1, 'Beginning', 'Mathematics', 82, '2026-02-11'),
(1, 2026, 1, 'Beginning', 'Integrated Science', 68, '2026-02-12'),
(1, 2026, 1, 'Beginning', 'Social Studies', 70, '2026-02-13'),
(1, 2026, 1, 'Beginning', 'Religious Education', 85, '2026-02-14'),
(1, 2026, 1, 'Beginning', 'Kiswahili', 72, '2026-02-14'),

-- Okello James
(2, 2026, 1, 'Beginning', 'English', 65, '2026-02-10'),
(2, 2026, 1, 'Beginning', 'Mathematics', 58, '2026-02-11'),
(2, 2026, 1, 'Beginning', 'Integrated Science', 62, '2026-02-12'),
(2, 2026, 1, 'Beginning', 'Social Studies', 55, '2026-02-13'),
(2, 2026, 1, 'Beginning', 'Religious Education', 70, '2026-02-14'),
(2, 2026, 1, 'Beginning', 'Kiswahili', 60, '2026-02-14'),

-- Nambi Grace
(3, 2026, 1, 'Beginning', 'English', 80, '2026-02-10'),
(3, 2026, 1, 'Beginning', 'Mathematics', 85, '2026-02-11'),
(3, 2026, 1, 'Beginning', 'Integrated Science', 78, '2026-02-12'),
(3, 2026, 1, 'Beginning', 'Social Studies', 82, '2026-02-13'),
(3, 2026, 1, 'Beginning', 'Religious Education', 90, '2026-02-14'),
(3, 2026, 1, 'Beginning', 'Kiswahili', 75, '2026-02-14');

-- ============================================
-- SAMPLE VISITATION RECORDS
-- ============================================
INSERT INTO visitation_records (student_id, visitation_date, term, year, 
    parent_attended, parent_name, items_brought, pocket_money_given, 
    meeting_with_teacher, academic_discussed, behavior_discussed) VALUES
(2, '2026-03-21', 1, 2026, TRUE, 'Mrs. Okello Sarah', 'Food, clothes, books', 50000, TRUE, TRUE, TRUE),
(4, '2026-03-21', 1, 2026, TRUE, 'Mrs. Kato Ruth', 'Food, new uniform', 30000, TRUE, TRUE, FALSE),
(5, '2026-03-21', 1, 2026, TRUE, 'Mr. Nakato David', 'Food, pocket money', 25000, FALSE, FALSE, FALSE),
(8, '2026-03-21', 1, 2026, FALSE, NULL, NULL, NULL, FALSE, FALSE, FALSE),
(9, '2026-03-21', 1, 2026, TRUE, 'Mr. Nabatanzi Robert', 'Food, shoes', 40000, TRUE, TRUE, TRUE),
(11, '2026-03-21', 1, 2026, TRUE, 'Mr. Achieng John', 'Food, books', 35000, TRUE, TRUE, FALSE),
(14, '2026-03-21', 1, 2026, TRUE, 'Mrs. Muhammad Fatima', 'Food, clothes', 45000, TRUE, TRUE, TRUE),
(16, '2026-03-21', 1, 2026, FALSE, NULL, NULL, NULL, FALSE, FALSE, FALSE),
(18, '2026-03-21', 1, 2026, TRUE, 'Mrs. Ssenyonga Sarah', 'Food, pocket money', 30000, TRUE, FALSE, TRUE),
(19, '2026-03-21', 1, 2026, TRUE, 'Mr. Nantongo Moses', 'Food, school supplies', 35000, TRUE, TRUE, FALSE);

-- ============================================
-- SAMPLE BEHAVIOR LOG
-- ============================================
INSERT INTO behavior_log (student_id, log_date, term, year, behavior_type, description, action_taken, points_awarded, parent_notified) VALUES
(6, '2026-03-01', 1, 2026, 'Positive', 'Helped a classmate with mathematics homework', 'Commended in class', 10, FALSE),
(12, '2026-03-02', 1, 2026, 'Warning', 'Late to class three times this week', 'Verbal warning', 0, FALSE),
(8, '2026-03-03', 1, 2026, 'Achievement', 'Won class spelling competition', 'Certificate awarded', 20, TRUE),
(4, '2026-03-04', 1, 2026, 'Incident', 'Rough play during break', 'Counseled', 0, TRUE),
(15, '2026-03-05', 1, 2026, 'Positive', 'Excellent performance in Science test', 'Star of the week', 15, TRUE);

-- ============================================
-- SAMPLE PARENT COMMUNICATION
-- ============================================
INSERT INTO parent_communication (student_id, communication_date, term, year, parent_name, contact_method, purpose, notes, follow_up_required) VALUES
(2, '2026-03-01', 1, 2026, 'Mrs. Okello Sarah', 'Phone Call', 'Academic', 'Discussed James''s progress in Mathematics', FALSE),
(4, '2026-03-02', 1, 2026, 'Mrs. Kato Ruth', 'WhatsApp', 'Behavior', 'Informed about incident during break', TRUE),
(7, '2026-03-03', 1, 2026, 'Mr. Auma Michael', 'In Person', 'Attendance', 'Sarah has been late 3 times this week', FALSE),
(9, '2026-03-04', 1, 2026, 'Mr. Nabatanzi Robert', 'Phone Call', 'Health', 'Joyce has a cold - monitoring', FALSE),
(14, '2026-03-05', 1, 2026, 'Mrs. Muhammad Fatima', 'Visitation Day', 'General', 'Pleasant meeting during visitation', FALSE);

-- ============================================
-- SAMPLE DAILY ROUTINES (Boarding)
-- ============================================
INSERT INTO daily_routines (routine_date, term, year, 
    morning_prep_attendance, morning_prep_supervisor,
    evening_prayer_attendance, prayer_conducted_by,
    evening_prep_attendance, prep_supervisor,
    lights_out_check, lights_out_time, dormitory_head) VALUES
(CURDATE(), 1, 2026, 15, 'Mr. Okello', 20, 'Chaplain John', 18, 'Mr. Okello', TRUE, '21:30:00', 'Mr. Okello'),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, 2026, 12, 'Mr. Okello', 19, 'Chaplain John', 17, 'Mr. Okello', TRUE, '21:30:00', 'Mr. Okello'),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 2026, 14, 'Mr. Okello', 20, 'Chaplain John', 18, 'Mr. Okello', TRUE, '21:30:00', 'Mr. Okello');

-- ============================================
-- SAMPLE TEACHER NOTES
-- ============================================
INSERT INTO teacher_notes (note_date, category, title, content, priority, reminder_date) VALUES
(CURDATE(), 'Reminder', 'Staff Meeting', 'Staff meeting on Friday at 2:00 PM in staffroom', 'High', '2026-03-06'),
(CURDATE(), 'Exam Planning', 'Mid-term Exams', 'Prepare exam papers for all subjects by next week', 'High', '2026-03-10'),
(CURDATE(), 'Parent Meeting', 'Meeting with Okello James parents', 'Discuss academic progress and behavior', 'Medium', '2026-03-07'),
(CURDATE(), 'General', 'Soccer tournament', 'Inter-class soccer tournament next month - select team', 'Low', NULL);

-- ============================================
-- IMPORTANT NOTE: VIEWS have been removed
-- You'll need to calculate these in your PHP code:
-- 1. Today's Attendance Summary
-- 2. Term Performance Summary
-- ============================================

-- ============================================
-- END OF DATABASE SCHEMA
-- ============================================