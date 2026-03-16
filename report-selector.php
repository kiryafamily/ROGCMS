<?php
// report-selector.php - Report Card Type Selector
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_term = $_GET['term'] ?? CURRENT_TERM;
$students = $pdo->query("SELECT id, full_name FROM students WHERE status = 'Active' ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report Card - P.5 Purple</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --purple: #4a1a3a;
            --purple-dark: #2f1224;
            --purple-light: #6a2b52;
            --orange: #ef5b2b;
            --orange-dark: #cf3b0b;
            --orange-light: #ff7b4b;
            --off-white: #f8f8f6;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            --shadow-sm: 0 2px 4px rgba(74, 26, 58, 0.08);
            --shadow-md: 0 4px 8px rgba(74, 26, 58, 0.12);
            --shadow-lg: 0 8px 16px rgba(74, 26, 58, 0.16);
            --shadow-hover: 0 12px 24px rgba(239, 91, 43, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, var(--off-white) 0%, #ffffff 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .selector-container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(74, 26, 58, 0.1);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: var(--purple-dark);
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 i {
            color: var(--orange);
        }

        .subtitle {
            color: var(--gray-600);
            margin-bottom: 30px;
            font-size: 1rem;
            border-left: 3px solid var(--orange);
            padding-left: 15px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--purple-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        label i {
            color: var(--orange);
            margin-right: 8px;
        }

        select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-300);
            border-radius: 14px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: white;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(239, 91, 43, 0.1);
        }

        .radio-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .radio-option {
            background: var(--gray-50);
            border: 2px solid var(--gray-300);
            border-radius: 16px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .radio-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--orange);
            transform: translateY(-100%);
            transition: var(--transition);
        }

        .radio-option:hover {
            border-color: var(--orange);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .radio-option:hover::before {
            transform: translateY(0);
        }

        .radio-option.selected {
            background: var(--purple);
            border-color: var(--purple);
        }

        .radio-option.selected i {
            color: var(--orange);
        }

        .radio-option.selected h3 {
            color: white;
        }

        .radio-option.selected p {
            color: var(--gray-300);
        }

        .radio-option i {
            font-size: 2.5rem;
            color: var(--purple);
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .radio-option h3 {
            margin: 5px 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--purple-dark);
        }

        .radio-option p {
            font-size: 0.8rem;
            color: var(--gray-600);
            line-height: 1.4;
        }

        input[type="radio"] {
            display: none;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-generate {
            flex: 1;
            padding: 16px;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(239, 91, 43, 0.3);
        }

        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 91, 43, 0.4);
        }

        .btn-generate i {
            font-size: 1.2rem;
        }

        .btn-generate-all {
            flex: 1;
            padding: 16px;
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(74, 26, 58, 0.3);
        }

        .btn-generate-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 26, 58, 0.4);
        }

        .btn-generate-all i {
            color: var(--orange);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: var(--purple);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 10px 20px;
            border-radius: 50px;
        }

        .btn-back:hover {
            background: var(--gray-100);
            color: var(--orange);
            transform: translateX(-5px);
        }

        .btn-back i {
            font-size: 0.9rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 25px;
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #0b5e8e;
        }

        .info-box i {
            color: #2196F3;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .selector-container {
                padding: 25px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .radio-group {
                grid-template-columns: 1fr;
            }

            .radio-option {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <h1>
            <i class="fas fa-file-alt"></i> 
            Generate Report Cards
        </h1>
        <p class="subtitle">Choose options to generate individual or bulk report cards</p>
        
        <form action="report-card.php" method="GET" id="reportForm">
            <div class="form-group">
                <label><i class="fas fa-user-graduate"></i> Select Student</label>
                <select name="student_id" id="studentSelect">
                    <option value="" selected>-- All Students (Bulk Generation) --</option>
                    <?php foreach ($students as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--gray-500); display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Select a specific student or leave as "All Students" to generate reports for everyone
                </small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Academic Term</label>
                <select name="term">
                    <option value="1" <?php echo $current_term == '1' ? 'selected' : ''; ?>>Term I (February - May)</option>
                    <option value="2" <?php echo $current_term == '2' ? 'selected' : ''; ?>>Term II (May - August)</option>
                    <option value="3" <?php echo $current_term == '3' ? 'selected' : ''; ?>>Term III (September - December)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-file-signature"></i> Report Type</label>
                <div class="radio-group">
                    <label class="radio-option" id="opt-bot">
                        <input type="radio" name="type" value="BOT" checked>
                        <i class="fas fa-play"></i>
                        <h3>BOT</h3>
                        <p>Beginning of Term<br>Only BOT results</p>
                    </label>
                    
                    <label class="radio-option" id="opt-mid">
                        <input type="radio" name="type" value="MID">
                        <i class="fas fa-pause"></i>
                        <h3>MID</h3>
                        <p>Mid-Term<br>BOT + MID results</p>
                    </label>
                    
                    <label class="radio-option" id="opt-end">
                        <input type="radio" name="type" value="END">
                        <i class="fas fa-flag-checkered"></i>
                        <h3>END</h3>
                        <p>End of Term<br>BOT + MID + END results</p>
                    </label>
                </div>
            </div>
            
            <div class="info-box">
                <i class="fas fa-lightbulb"></i>
                <strong>Bulk Generation:</strong> Select "All Students" to generate a single PDF containing report cards for every student in the class.
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn-generate" id="generateBtn">
                    <i class="fas fa-file-pdf"></i> Generate Report
                </button>
                <button type="button" class="btn-generate-all" id="generateAllBtn" onclick="generateAllReports()">
                    <i class="fas fa-layer-group"></i> Generate All Reports
                </button>
            </div>
        </form>
        
        <div style="text-align: center;">
            <a href="assessments.php?term=<?php echo $current_term; ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Marksheet
            </a>
        </div>
        
        <div class="footer-note">
            <i class="fas fa-info-circle"></i> Report cards are generated in A4 format, ready for printing
        </div>
    </div>

    <script>
        // Highlight selected radio option
        document.querySelectorAll('.radio-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.radio-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Set initial selected state
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[type="radio"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.radio-option').classList.add('selected');
            }
        });

        // Form validation for single report
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const studentSelect = document.getElementById('studentSelect');
            const generateBtn = document.getElementById('generateBtn');
            
            if (!studentSelect.value) {
                e.preventDefault();
                alert('Please select a student or use "Generate All Reports" for bulk generation');
                studentSelect.focus();
            } else {
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                generateBtn.disabled = true;
            }
        });

        // Generate all reports function
        function generateAllReports() {
            const term = document.querySelector('select[name="term"]').value;
            const type = document.querySelector('input[name="type"]:checked').value;
            const generateAllBtn = document.getElementById('generateAllBtn');
            
            // Confirm action
            if (!confirm('Generate report cards for ALL active students? This will create a single PDF with all reports.')) {
                return;
            }
            
            // Show loading state
            generateAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating All Reports...';
            generateAllBtn.disabled = true;
            
            // Redirect to bulk report generator
            window.location.href = `bulk-reports.php?term=${term}&type=${type}`;
        }

        // Handle student selection change
        document.getElementById('studentSelect').addEventListener('change', function() {
            const generateBtn = document.getElementById('generateBtn');
            const generateAllBtn = document.getElementById('generateAllBtn');
            
            if (this.value) {
                // Single student selected
                generateBtn.style.display = 'flex';
                generateAllBtn.style.display = 'none';
            } else {
                // All students selected
                generateBtn.style.display = 'none';
                generateAllBtn.style.display = 'flex';
            }
        });

        // Trigger change on page load
        document.addEventListener('DOMContentLoaded', function() {
            const event = new Event('change');
            document.getElementById('studentSelect').dispatchEvent(event);
        });
    </script>
</body>
</html>