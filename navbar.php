<?php
// navbar.php - Reusable navigation component
// Include this file in any page that needs the main menu.
// It expects $recent_students to be defined (for the "View Profile" link).
// If $recent_students is not defined, you can set a fallback.
if (!isset($recent_students) || empty($recent_students)) {
    // Provide a fallback student ID or use 0 (which will be ignored)
    $first_student_id = 0;
} else {
    $first_student_id = $recent_students[0]['id'] ?? 0;
}
?>
<!-- Navigation Bar -->
<nav class="main-nav">
    <button class="nav-toggle" id="navToggle">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Desktop menu -->
    <ul class="nav-menu" id="navMenu">
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-check-circle"></i> Attendance <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 5px;"></i>
            </a>
            <div class="dropdown-content">
                <a href="attendance.php"><i class="fas fa-sun"></i> Morning Roll Call</a>
                <a href="attendance.php?type=afternoon"><i class="fas fa-cloud-sun"></i> Afternoon Roll Call</a>
                <a href="attendance.php?type=evening"><i class="fas fa-moon"></i> Evening Roll Call</a>
                <hr>
                <a href="attendance-reports.php"><i class="fas fa-chart-line"></i> Reports & Export</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-users"></i> Students <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
            </a>
            <div class="dropdown-content">
                <a href="students.php"><i class="fas fa-list"></i> View All Students</a>
                <a href="add-student.php"><i class="fas fa-user-plus"></i> Add New Student</a>
                <a href="upload-student-photo.php"><i class="fas fa-camera"></i> Upload Photos</a>
                <a href="student-profile.php?id=<?php echo $first_student_id; ?>"><i class="fas fa-id-card"></i> View Profile</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-pencil-alt"></i> Marks <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-content">
                <a href="assessments.php"><i class="fas fa-table"></i> Marksheet</a>
                <a href="report-selector.php"><i class="fas fa-file-pdf"></i> Report Cards</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-comments"></i> Communication <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-content">
                <a href="communication.php"><i class="fas fa-comments"></i> Parent Hub</a>
                <a href="sms-broadcast.php"><i class="fas fa-bullhorn"></i> Bulk SMS</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-content">
                <a href="attendance-reports.php"><i class="fas fa-calendar-check"></i> Attendance Reports</a>
                <a href="assessment-reports.php"><i class="fas fa-chart-line"></i> Performance Analysis</a>
                <a href="behavior-reports.php"><i class="fas fa-smile"></i> Behavior Logs</a>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="return false;">
                <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-content">
                <a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a>
                <a href="public-holidays.php"><i class="fas fa-calendar-day"></i> Public Holidays</a>
                <a href="teacher-profile.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Profile</a>
                <a href="school-info.php"><i class="fas fa-school"></i> School Info</a>
            </div>
        </li>
    </ul>
</nav>

<!-- Mobile Overlay and Slide-out Menu -->
<div class="nav-overlay" id="navOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <h3><i class="fas fa-crown" style="color: #FFB800;"></i> Menu</h3>
        <button class="mobile-close" id="mobileClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-menu-content">
        <!-- Mobile menu items with dropdowns -->
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-check-circle"></i> Attendance</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="attendance.php"><i class="fas fa-sun"></i> Morning Roll Call</a>
                <a href="attendance.php?type=afternoon"><i class="fas fa-cloud-sun"></i> Afternoon Roll Call</a>
                <a href="attendance.php?type=evening"><i class="fas fa-moon"></i> Evening Roll Call</a>
                <a href="attendance-reports.php"><i class="fas fa-chart-line"></i> Reports & Export</a>
            </div>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-users"></i> Students</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="students.php"><i class="fas fa-list"></i> View All Students</a>
                <a href="add-student.php"><i class="fas fa-user-plus"></i> Add New Student</a>
                <a href="upload-student-photo.php"><i class="fas fa-camera"></i> Upload Photos</a>
                <a href="student-profile.php?id=<?php echo $first_student_id; ?>"><i class="fas fa-id-card"></i> View Profile</a>
            </div>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-pencil-alt"></i> Marks</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="assessments.php"><i class="fas fa-table"></i> Marksheet</a>
                <a href="report-selector.php"><i class="fas fa-file-pdf"></i> Report Cards</a>
            </div>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-comments"></i> Communication</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="communication.php"><i class="fas fa-comments"></i> Parent Hub</a>
                <a href="sms-broadcast.php"><i class="fas fa-bullhorn"></i> Bulk SMS</a>
            </div>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-chart-bar"></i> Reports</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="attendance-reports.php"><i class="fas fa-calendar-check"></i> Attendance Reports</a>
                <a href="assessment-reports.php"><i class="fas fa-chart-line"></i> Performance Analysis</a>
                <a href="behavior-reports.php"><i class="fas fa-smile"></i> Behavior Logs</a>
            </div>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="toggleMobileDropdown(this); return false;">
                <span><i class="fas fa-cog"></i> Settings</span>
                <i class="fas fa-chevron-down"></i>
            </a>
            <div class="mobile-dropdown">
                <a href="timetable.php"><i class="fas fa-clock"></i> Timetable</a>
                <a href="public-holidays.php"><i class="fas fa-calendar-day"></i> Public Holidays</a>
                <a href="teacher-profile.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Profile</a>
                <a href="school-info.php"><i class="fas fa-school"></i> School Info</a>
            </div>
        </div>
    </div>
</div>

<!-- Mobile menu JavaScript -->
<script>
    // Ensure these functions are available globally
    function toggleMobileDropdown(element) {
        const dropdown = element.nextElementSibling;
        dropdown.classList.toggle('show');
        const icon = element.querySelector('.fa-chevron-down');
        if (icon) {
            icon.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : '';
        }
    }

    // Menu toggle for mobile (will be initialised when DOM is ready)
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('navToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const navOverlay = document.getElementById('navOverlay');
        const mobileClose = document.getElementById('mobileClose');

        if (navToggle) {
            navToggle.addEventListener('click', function() {
                mobileMenu.classList.add('show');
                navOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }

        if (mobileClose) {
            mobileClose.addEventListener('click', function() {
                mobileMenu.classList.remove('show');
                navOverlay.classList.remove('show');
                document.body.style.overflow = '';
            });
        }

        if (navOverlay) {
            navOverlay.addEventListener('click', function() {
                mobileMenu.classList.remove('show');
                navOverlay.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
    });
</script>