<?php
session_start();
require_once 'includes/config.php';

// PROTECT THIS PAGE
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current term info
$current_term = getCurrentTerm($pdo);

// Get student statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
$day_scholars = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Day Scholar' AND status = 'Active'")->fetchColumn();
$boarders = $pdo->query("SELECT COUNT(*) FROM students WHERE student_type = 'Boarder' AND status = 'Active'")->fetchColumn();
$soccer_students = $pdo->query("SELECT COUNT(*) FROM students WHERE soccer_academy = TRUE AND status = 'Active'")->fetchColumn();

// Get teacher profile
$stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
$teacher = $stmt->fetch();
if (!$teacher) {
    // Create default if not exists
    $pdo->exec("INSERT INTO teacher_profile (teacher_name, teacher_title) VALUES ('Mr. Kirya Amos', 'Class Teacher P.5 Purple')");
    $stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 1");
    $teacher = $stmt->fetch();
}

// Get today's date
$today = date('Y-m-d');
$day_of_week = date('l');

    // Get today's schedule
    $stmt = $pdo->prepare("SELECT * FROM daily_schedule WHERE day_of_week = ? ORDER BY period_number");
    $stmt->execute([$day_of_week]);
    $schedule = $stmt->fetchAll();

// Get today's attendance summary
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN morning_status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN morning_status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN morning_status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE date = ?
");
$stmt->execute([$today]);
$today_attendance = $stmt->fetch();

// Get recent students
$stmt = $pdo->query("SELECT * FROM students WHERE status = 'Active' ORDER BY id DESC LIMIT 5");
$recent_students = $stmt->fetchAll();

// Get upcoming assessments
$stmt = $pdo->prepare("
    SELECT a.*, s.full_name 
    FROM assessments a
    JOIN students s ON a.student_id = s.id
    WHERE a.exam_date >= ? 
    ORDER BY a.exam_date 
    LIMIT 5
");
$stmt->execute([$today]);
$upcoming = $stmt->fetchAll();

// Get recent behavior
$stmt = $pdo->prepare("
    SELECT b.*, s.full_name 
    FROM behavior_log b
    JOIN students s ON b.student_id = s.id
    ORDER BY b.log_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_behavior = $stmt->fetchAll();

// Check if today is a holiday
$stmt = $pdo->prepare("SELECT * FROM public_holidays WHERE holiday_date = ?");
$stmt->execute([$today]);
$is_holiday = $stmt->fetch();

// Get upcoming events
$upcoming_events = [];

if ($current_term) {
    if ($current_term['visitation_day'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['visitation_day'], 'event' => 'Visitation Day', 'icon' => 'fa-users'];
    }
    if ($current_term['mid_term_exam_start'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['mid_term_exam_start'], 'event' => 'Mid-Term Exams Start', 'icon' => 'fa-pencil-alt'];
    }
    if ($current_term['end_term_exam_start'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['end_term_exam_start'], 'event' => 'End of Term Exams', 'icon' => 'fa-file-alt'];
    }
    if ($current_term['report_card_date'] >= $today) {
        $upcoming_events[] = ['date' => $current_term['report_card_date'], 'event' => 'Report Cards Out', 'icon' => 'fa-certificate'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P.5 Purple - Classroom Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CLEAN SOLID COLORS - NO GRADIENTS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f0f5;
            color: #333;
        }

        /* Premium Container */
        .premium-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* HEADER WITH TEACHER PHOTO */
        .premium-header {
            background-color: #4B1C3C;
            border-radius: 15px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(75, 28, 60, 0.2);
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .class-title h1 {
            color: #ffffff;
            font-size: 2.5rem;
            margin-bottom: 5px;
        }

        .class-title h1 i {
            color: #FFB800;
            margin-right: 15px;
        }

        .class-slogan {
            color: #FFB800;
            font-size: 1.1rem;
        }

        /* Teacher Profile Card */
        .teacher-profile {
            display: flex;
            align-items: center;
            gap: 20px;
            background-color: #2F1224;
            padding: 15px 25px;
            border-radius: 12px;
            border-left: 4px solid #FFB800;
        }

        .teacher-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #4B1C3C;
            border: 3px solid #FFB800;
            overflow: hidden;
            position: relative;
        }

        .teacher-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .teacher-photo-placeholder {
            width: 100%;
            height: 100%;
            background-color: #4B1C3C;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #FFB800;
        }

        .teacher-info {
            text-align: left;
        }

        .teacher-name {
            color: #FFB800;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .teacher-title {
            color: #ffffff;
            font-size: 0.9rem;
        }

        .photo-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #FFB800;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4B1C3C;
            font-size: 0.8rem;
            cursor: pointer;
            border: 2px solid #4B1C3C;
            transition: background-color 0.3s;
        }

        .photo-upload-btn:hover {
            background-color: #ffffff;
        }

        .class-badge {
            background-color: #2F1224;
            padding: 15px 25px;
            border-radius: 10px;
            border-left: 4px solid #FFB800;
            text-align: center;
        }

        .class-badge .teacher {
            color: #FFB800;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 3px;
        }

        .class-badge .year {
            color: #ffffff;
            font-size: 0.9rem;
        }

        .logout-btn {
            background-color: #4B1C3C;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            border: 1px solid #FFB800;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #1a0d14;
        }

        /* Alert Box */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background-color: #e1f5fe;
            border-left: 4px solid #0288d1;
            color: #01579b;
        }

        /* Time Card */
        .time-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-display i {
            font-size: 2rem;
            color: #4B1C3C;
        }

        .date-display .day {
            font-size: 1rem;
            color: #666;
        }

        .date-display .date {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4B1C3C;
        }

        .live-clock .time {
            font-size: 2rem;
            font-weight: 700;
            color: #4B1C3C;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(75, 28, 60, 0.1);
            border-color: #4B1C3C;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: #f0e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: #4B1C3C;
        }

        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4B1C3C;
            line-height: 1.2;
        }

        .stat-content p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0d0e0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0e8f0;
        }

        .card-header h2 {
            color: #4B1C3C;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h2 i {
            color: #FFB800;
        }

        .view-link {
            color: #4B1C3C;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .view-link:hover {
            color: #FFB800;
        }

        /* Schedule List */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background-color: #f8f4f8;
            border-radius: 8px;
            border-left: 3px solid transparent;
        }

        .schedule-item:hover {
            background-color: #f0e8f0;
            border-left-color: #4B1C3C;
        }

        .schedule-time {
            width: 80px;
            font-weight: 600;
            color: #4B1C3C;
        }

        .schedule-subject {
            flex: 1;
            font-weight: 500;
        }

        .schedule-badge {
            background-color: #4B1C3C;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .break-item {
            background-color: #fff3e0;
        }

        .break-item .schedule-subject {
            color: #FFB800;
            font-weight: 600;
        }

        .break-item .schedule-badge {
            background-color: #FFB800;
            color: #4B1C3C;
        }

        /* Action Grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .action-card {
            background-color: #f8f4f8;
            padding: 20px 15px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            border: 1px solid #e0d0e0;
            transition: background-color 0.2s;
        }

        .action-card:hover {
            background-color: #4B1C3C;
        }

        .action-card:hover i,
        .action-card:hover span {
            color: #ffffff;
        }

        .action-card i {
            font-size: 2rem;
            color: #4B1C3C;
            margin-bottom: 8px;
        }

        .action-card span {
            display: block;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Student List */
        .student-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f8f4f8;
            border-radius: 8px;
        }

        .student-item:hover {
            background-color: #f0e8f0;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background-color: #4B1C3C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
            margin-right: 15px;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #4B1C3C;
        }

        .student-meta {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-present {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-departed {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .premium-badge {
            background-color: #FFB800;
            color: #4B1C3C;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Term Progress */
        .progress-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e0d0e0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .progress-bar-bg {
            height: 10px;
            background-color: #f0e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: #4B1C3C;
        }

        /* Footer */
        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 0.9rem;
        }

        .footer-note i {
            color: #FFB800;
        }

        /* Modal for Photo Upload */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #4B1C3C;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .premium-container {
                padding: 10px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .teacher-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .class-title h1 {
                font-size: 2rem;
            }
            
            .time-card {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f0e8f0;
        }

        ::-webkit-scrollbar-thumb {
            background: #4B1C3C;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="premium-container">
        <!-- Premium Header with Teacher Photo -->
        <div class="premium-header">
            <div class="header-content">
                <div class="class-title">
                    <h1>
                        <i class="fas fa-crown"></i> 
                        <?php echo CLASS_NAME; ?>
                    </h1>
                    <div class="class-slogan">
                        <i class="fas fa-quote-left"></i>
                        <?php echo CLASS_SLOGAN; ?>
                        <i class="fas fa-quote-right"></i>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <!-- Teacher Profile with Photo -->
                    <div class="teacher-profile">
                        <div class="teacher-photo">
                            <?php if (!empty($teacher['teacher_photo'])): ?>
                                <img src="uploads/teachers/<?php echo $teacher['teacher_photo']; ?>" alt="Teacher Photo">
                            <?php else: ?>
                                <div class="teacher-photo-placeholder">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            <?php endif; ?>
                            <div class="photo-upload-btn" onclick="openUploadModal()">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <div class="teacher-info">
                            <div class="teacher-name"><?php echo $teacher['teacher_name']; ?></div>
                            <div class="teacher-title"><?php echo $teacher['teacher_title'] ?? 'Class Teacher'; ?></div>
                        </div>
                    </div>
                    
                    <!-- Class Badge with Logout -->
                    <div class="class-badge">
                        <div class="teacher"><i class="fas fa-chalkboard-teacher"></i> P.5 Purple</div>
                        <div class="year">
                            <i class="fas fa-calendar-alt"></i> 
                            Term <?php echo CURRENT_TERM; ?> | <?php echo ACADEMIC_YEAR; ?>
                        </div>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holiday Alert -->
        <?php if ($is_holiday): ?>
        <div class="alert alert-info">
            <i class="fas fa-calendar-day"></i>
            <strong>Today is <?php echo $is_holiday['holiday_name']; ?> - No classes!</strong>
        </div>
        <?php endif; ?>

        <!-- Time Card -->
        <div class="time-card">
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <div class="day"><?php echo $day_of_week; ?></div>
                    <div class="date"><?php echo date('F j, Y'); ?></div>
                </div>
            </div>
            <div class="live-clock">
                <div class="time" id="liveTime"></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sun"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $day_scholars; ?></h3>
                    <p>Day Scholars</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $boarders; ?></h3>
                    <p>Boarders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $soccer_students; ?></h3>
                    <p>Soccer Academy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_attendance['present'] ?? 0; ?></h3>
                    <p>Present Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $today_attendance['late'] ?? 0; ?></h3>
                    <p>Late Today</p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
          <!-- Today's Schedule Card -->
<div class="dashboard-card">
    <div class="card-header">
        <h2><i class="fas fa-clock"></i> Today's Schedule - <?php echo date('l'); ?></h2>
        <a href="timetable.php" class="view-link">
            Full Timetable <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="schedule-list">
        <?php
        $today = date('l'); // Monday, Tuesday, etc.
        
        // Define the complete daily routine (same for all days)
        $daily_routine = [
            ['time' => '6:00 AM', 'activity' => 'Morning Prep', 'type' => 'prep', 'badge' => 'Prep', 'color' => '#FF9800'],
            ['time' => '7:20 AM', 'activity' => 'Morning Tea', 'type' => 'break', 'badge' => 'Tea', 'color' => '#FFB800'],
        ];
        
        // Assembly only on Monday and Wednesday
        if ($today == 'Monday' || $today == 'Wednesday') {
            $daily_routine[] = ['time' => '7:50 AM', 'activity' => 'Assembly', 'type' => 'assembly', 'badge' => 'ASS', 'color' => '#9C27B0'];
        } else {
            $daily_routine[] = ['time' => '7:50 AM', 'activity' => 'Period', 'type' => 'period', 'badge' => 'Period', 'color' => '#4B1C3C'];
        }
        
        // Add all periods based on the day
        $periods = [
            'Monday' => [
                ['time' => '8:30 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'Computer', 'teacher' => 'Mr. Nelson', 'period' => 5],
                ['time' => '2:50 PM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 6]
            ],
            'Tuesday' => [
                ['time' => '7:50 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 0],
                ['time' => '8:30 AM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'Religious Education', 'teacher' => 'Mr. Opio', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 5],
                ['time' => '2:50 PM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 6]
            ],
            'Wednesday' => [
                ['time' => '8:30 AM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 5],
                ['time' => '2:50 PM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 6]
            ],
            'Thursday' => [
                ['time' => '7:50 AM', 'subject' => 'Physical Education', 'teacher' => '', 'period' => 0],
                ['time' => '8:30 AM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'Music', 'teacher' => 'Mr. Dean', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 5],
                ['time' => '2:50 PM', 'subject' => 'Religious Education', 'teacher' => 'Mr. Opio', 'period' => 6]
            ],
            'Friday' => [
                ['time' => '7:50 AM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 0],
                ['time' => '8:30 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'Music', 'teacher' => 'Mr. Dean', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'Debate & Quiz', 'teacher' => '', 'period' => 5]
            ],
            'Saturday' => [
                ['time' => '7:50 AM', 'subject' => 'Cleaning', 'teacher' => '', 'period' => 0],
                ['time' => '8:30 AM', 'subject' => 'Social Studies', 'teacher' => 'Mr. Opio', 'period' => 1],
                ['time' => '9:30 AM', 'subject' => 'Science', 'teacher' => 'Mr. Amos', 'period' => 2],
                ['time' => '11:00 AM', 'subject' => 'Mathematics', 'teacher' => 'Mr. Rogers', 'period' => 3],
                ['time' => '12:00 PM', 'subject' => 'English', 'teacher' => 'Miss Grace', 'period' => 4],
                ['time' => '1:40 PM', 'subject' => 'Free Activity', 'teacher' => '', 'period' => 5]
            ]
        ];
        
        // Break times (same every day)
        $breaks = [
            ['time' => '10:30 AM', 'activity' => 'Break Time', 'badge' => 'Break', 'color' => '#FFB800'],
            ['time' => '1:00 PM', 'activity' => 'Lunch Break', 'badge' => 'Lunch', 'color' => '#4CAF50']
        ];
        
        // Afternoon routine (same every day)
        $afternoon = [
            ['time' => '3:40 PM', 'activity' => 'Homework Period', 'badge' => 'Homework', 'color' => '#FF9800'],
            ['time' => '4:00 PM', 'activity' => 'Cleaning', 'badge' => 'Clean', 'color' => '#03A9F4'],
            ['time' => '4:20 PM', 'activity' => 'Music, Dance & Drama / Games', 'badge' => 'MDD', 'color' => '#E91E63'],
            ['time' => '5:20 PM', 'activity' => 'Personal Administration (Washing)', 'badge' => 'Admin', 'color' => '#3F51B5'],
            ['time' => '6:00 PM', 'activity' => 'Evening Prayers', 'badge' => 'Prayers', 'color' => '#009688'],
        ];
        
        // Evening prep (different subject each day)
        $prep_subjects = [
            'Monday' => 'SST (Mr. Opio)',
            'Tuesday' => 'English (Miss Grace)',
            'Wednesday' => 'Science (Mr. Amos)',
            'Thursday' => 'Social Studies (Mr. Opio)',
            'Friday' => 'Mathematics (Mr. Rogers)',
            'Saturday' => 'General Prep'
        ];
        
        $afternoon[] = ['time' => '6:30 PM', 'activity' => 'Evening Prep - ' . $prep_subjects[$today], 'badge' => 'Prep', 'color' => '#FF9800'];
        
        // Night routine (same every day)
        $night = [
            ['time' => '7:30 PM', 'activity' => 'Supper', 'badge' => 'Supper', 'color' => '#8BC34A'],
            ['time' => '8:00 PM', 'activity' => 'Night Prep', 'badge' => 'Study', 'color' => '#FF9800'],
            ['time' => '9:30 PM', 'activity' => 'Lights Out', 'badge' => '🌙', 'color' => '#212121']
        ];
        
        // Display Morning Prep and Tea
        echo '<div class="schedule-item prep-item" style="background: #fff8e1;">';
        echo '<span class="schedule-time">6:00 AM</span>';
        echo '<span class="schedule-subject">Morning Prep</span>';
        echo '<span class="schedule-badge" style="background-color: #FF9800; color: white;">Prep</span>';
        echo '</div>';
        
        echo '<div class="schedule-item break-item">';
        echo '<span class="schedule-time">7:20 AM</span>';
        echo '<span class="schedule-subject">Morning Tea</span>';
        echo '<span class="schedule-badge" style="background-color: #FFB800; color: #4a1a3a;">Tea</span>';
        echo '</div>';
        
        // Display Assembly or first period based on day
        if ($today == 'Monday' || $today == 'Wednesday') {
            echo '<div class="schedule-item assembly-item" style="background: #f3e5f5;">';
            echo '<span class="schedule-time">7:50 AM</span>';
            echo '<span class="schedule-subject">Assembly</span>';
            echo '<span class="schedule-badge" style="background-color: #9C27B0; color: white;">ASS</span>';
            echo '</div>';
        }
        
        // Display all periods for today
        if (isset($periods[$today])) {
            foreach ($periods[$today] as $period) {
                $bg_color = ($period['period'] == 0) ? '#e0e0e0' : '';
                echo '<div class="schedule-item" style="' . $bg_color . '">';
                echo '<span class="schedule-time">' . $period['time'] . '</span>';
                echo '<span class="schedule-subject">' . $period['subject'];
                if (!empty($period['teacher'])) {
                    echo '<small style="display: block; color: #ef5b2b; font-size: 0.8rem;">' . $period['teacher'] . '</small>';
                }
                echo '</span>';
                if ($period['period'] > 0) {
                    echo '<span class="schedule-badge">Period ' . $period['period'] . '</span>';
                } else {
                    echo '<span class="schedule-badge" style="background-color: #757575;">Extra</span>';
                }
                echo '</div>';
            }
        }
        
        // Display Break
        echo '<div class="schedule-item break-item">';
        echo '<span class="schedule-time">10:30 AM</span>';
        echo '<span class="schedule-subject">Break Time</span>';
        echo '<span class="schedule-badge" style="background-color: #FFB800; color: #4a1a3a;">Break</span>';
        echo '</div>';
        
        // Display Lunch
        echo '<div class="schedule-item lunch-item" style="background: #e8f5e9;">';
        echo '<span class="schedule-time">1:00 PM</span>';
        echo '<span class="schedule-subject">Lunch Break</span>';
        echo '<span class="schedule-badge" style="background-color: #4CAF50; color: white;">Lunch</span>';
        echo '</div>';
        
        // Display Afternoon activities
        echo '<div class="schedule-item homework-item" style="background: #fff8e1;">';
        echo '<span class="schedule-time">3:40 PM</span>';
        echo '<span class="schedule-subject">Homework Period</span>';
        echo '<span class="schedule-badge" style="background-color: #FF9800; color: white;">Homework</span>';
        echo '</div>';
        
        echo '<div class="schedule-item cleaning-item" style="background: #e1f5fe;">';
        echo '<span class="schedule-time">4:00 PM</span>';
        echo '<span class="schedule-subject">Cleaning</span>';
        echo '<span class="schedule-badge" style="background-color: #03A9F4; color: white;">Clean</span>';
        echo '</div>';
        
        echo '<div class="schedule-item mdd-item" style="background: #fce4ec;">';
        echo '<span class="schedule-time">4:20 PM</span>';
        echo '<span class="schedule-subject">Music, Dance & Drama / Games</span>';
        echo '<span class="schedule-badge" style="background-color: #E91E63; color: white;">MDD</span>';
        echo '</div>';
        
        echo '<div class="schedule-item admin-item" style="background: #e8eaf6;">';
        echo '<span class="schedule-time">5:20 PM</span>';
        echo '<span class="schedule-subject">Personal Administration (Washing)</span>';
        echo '<span class="schedule-badge" style="background-color: #3F51B5; color: white;">Admin</span>';
        echo '</div>';
        
        echo '<div class="schedule-item prayer-item" style="background: #e0f2f1;">';
        echo '<span class="schedule-time">6:00 PM</span>';
        echo '<span class="schedule-subject">Evening Prayers</span>';
        echo '<span class="schedule-badge" style="background-color: #009688; color: white;">Prayers</span>';
        echo '</div>';
        
        // Display Evening Prep with correct subject
        echo '<div class="schedule-item prep-item" style="background: #fff8e1;">';
        echo '<span class="schedule-time">6:30 PM</span>';
        echo '<span class="schedule-subject">Evening Prep - ' . $prep_subjects[$today] . '</span>';
        echo '<span class="schedule-badge" style="background-color: #FF9800; color: white;">Prep</span>';
        echo '</div>';
        
        // Display Supper
        echo '<div class="schedule-item supper-item" style="background: #f1f8e9;">';
        echo '<span class="schedule-time">7:30 PM</span>';
        echo '<span class="schedule-subject">Supper</span>';
        echo '<span class="schedule-badge" style="background-color: #8BC34A; color: white;">Supper</span>';
        echo '</div>';
        
        // Display Night Prep
        echo '<div class="schedule-item nightprep-item" style="background: #fff8e1;">';
        echo '<span class="schedule-time">8:00 PM</span>';
        echo '<span class="schedule-subject">Night Prep</span>';
        echo '<span class="schedule-badge" style="background-color: #FF9800; color: white;">Study</span>';
        echo '</div>';
        
        // Display Lights Out
        echo '<div class="schedule-item lights-item" style="background: #212121;">';
        echo '<span class="schedule-time" style="color: white;">9:30 PM</span>';
        echo '<span class="schedule-subject" style="color: white;">Lights Out</span>';
        echo '<span class="schedule-badge" style="background-color: #000; color: white;">🌙</span>';
        echo '</div>';
        ?>
    </div>

    <!-- Boarding Schedule Summary -->
    <?php if ($boarders > 0): ?>
    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px dashed #e0d0e0;">
        <div style="display: flex; gap: 10px; align-items: center; color: #4a1a3a;">
            <i class="fas fa-moon"></i>
            <span style="font-weight: 600;">Boarding Schedule:</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
            <span class="status-badge status-present"><i class="fas fa-pray"></i> 6:00 PM Prayers</span>
            <span class="status-badge status-present"><i class="fas fa-book"></i> 6:30 PM Prep</span>
            <span class="status-badge status-departed"><i class="fas fa-bed"></i> 9:30 PM Lights Out</span>
        </div>
    </div>
    <?php endif; ?>
</div>
            <!-- Quick Actions Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                </div>
                <div class="action-grid">
                    <a href="attendance.php" class="action-card">
                        <i class="fas fa-check-circle"></i>
                        <span>Roll Call</span>
                    </a>
                    <a href="students.php" class="action-card">
                        <i class="fas fa-user-plus"></i>
                        <span>Students</span>
                    </a>
                    <a href="assessments.php" class="action-card">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Marks</span>
                    </a>
                    <a href="behavior.php" class="action-card">
                        <i class="fas fa-smile"></i>
                        <span>Behavior</span>
                    </a>
                    <a href="communication.php" class="action-card">
                        <i class="fas fa-comments"></i>
                        <span>Parents</span>
                    </a>
                    <a href="boarding.php" class="action-card">
                        <i class="fas fa-bed"></i>
                        <span>Boarders</span>
                    </a>
                    <a href="visitation.php" class="action-card">
                        <i class="fas fa-users"></i>
                        <span>Visitation</span>
                    </a>
                    <a href="soccer.php" class="action-card">
                        <i class="fas fa-futbol"></i>
                        <span>Soccer</span>
                    </a>
                    <a href="reports.php" class="action-card">
                        <i class="fas fa-file-pdf"></i>
                        <span>Reports</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="dashboard-grid">
            <!-- Recent Students Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Recent Students</h2>
                    <a href="students.php" class="view-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="student-list">
                    <?php foreach ($recent_students as $student): ?>
                    <div class="student-item">
                        <div class="student-avatar">
                            <?php echo substr($student['full_name'], 0, 1); ?>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $student['full_name']; ?></div>
                            <div class="student-meta">
                                <i class="fas <?php echo $student['student_type'] == 'Boarder' ? 'fa-moon' : 'fa-sun'; ?>" 
                                   style="color: <?php echo $student['student_type'] == 'Boarder' ? '#4B1C3C' : '#FFB800'; ?>"></i>
                                <?php echo $student['student_type']; ?>
                                <?php if ($student['soccer_academy']): ?>
                                    <span class="premium-badge">⚽ Soccer</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Upcoming Events Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-alt"></i> Upcoming Events</h2>
                    <a href="timetable.php" class="view-link">
                        View Calendar <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if (empty($upcoming_events)): ?>
                    <p style="color: #999; text-align: center; padding: 20px;">No upcoming events</p>
                <?php else: ?>
                    <div class="student-list">
                        <?php foreach ($upcoming_events as $event): ?>
                        <div class="student-item">
                            <div class="student-avatar" style="background-color: #FFB800; color: #4B1C3C;">
                                <i class="fas <?php echo $event['icon']; ?>"></i>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo $event['event']; ?></div>
                                <div class="student-meta">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Recent Behavior -->
                <?php if (!empty($recent_behavior)): ?>
                <div style="margin-top: 20px;">
                    <h3 style="color: #4B1C3C; font-size: 1rem; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle" style="color: #FFB800;"></i> Recent Incidents
                    </h3>
                    <?php foreach (array_slice($recent_behavior, 0, 3) as $behavior): ?>
                    <div class="student-item">
                        <div class="student-avatar" style="background-color: <?php 
                            echo $behavior['behavior_type'] == 'Positive' ? '#4CAF50' : 
                                ($behavior['behavior_type'] == 'Warning' ? '#FF9800' : '#f44336'); 
                        ?>">
                            <i class="fas <?php 
                                echo $behavior['behavior_type'] == 'Positive' ? 'fa-star' : 
                                    ($behavior['behavior_type'] == 'Warning' ? 'fa-exclamation' : 'fa-exclamation-triangle'); 
                            ?>"></i>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $behavior['full_name']; ?></div>
                            <div class="student-meta">
                                <?php echo substr($behavior['description'], 0, 40); ?>...
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Term Progress Bar -->
        <?php if ($current_term): 
            $start = strtotime($current_term['start_date']);
            $end = strtotime($current_term['end_date']);
            $now = time();
            $progress = (($now - $start) / ($end - $start)) * 100;
            $progress = min(100, max(0, $progress));
        ?>
        <div class="progress-container">
            <div class="progress-header">
                <span><i class="fas fa-play"></i> <?php echo date('M d', $start); ?></span>
                <span><strong>Term <?php echo $current_term['term_number']; ?> Progress</strong></span>
                <span><i class="fas fa-flag-checkered"></i> <?php echo date('M d', $end); ?></span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer-note">
            <i class="fas fa-heart"></i> 
            <?php echo CLASS_NAME; ?> - Where Great Minds Grow 
            <i class="fas fa-heart"></i>
        </div>
    </div>

    <!-- Photo Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-camera"></i> Update Profile Photo</h3>
                <button class="close-btn" onclick="closeUploadModal()">&times;</button>
            </div>
            <form action="upload-photo.php" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #4B1C3C;">Choose Photo</label>
                    <input type="file" name="teacher_photo" accept="image/*" required style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 5px;">
                </div>
                <button type="submit" class="logout-btn" style="width: 100%; margin-top: 0; justify-content: center;">Upload Photo</button>
            </form>
        </div>
    </div>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('liveTime').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>