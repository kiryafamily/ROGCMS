<?php
// rollcall-selector.php - Roll Call Type Selector
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Roll Call Type - P.5 Purple</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f0f5 0%, #faf5fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .selector-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(75, 28, 60, 0.2);
            border: 1px solid rgba(255, 184, 0, 0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header i {
            font-size: 3rem;
            color: #FFB800;
            background: #4B1C3C;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .header h1 {
            color: #4B1C3C;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 1rem;
        }

        .date-box {
            background: #f8f4f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #FFB800;
        }

        .date-box i {
            color: #FFB800;
            font-size: 1.2rem;
        }

        .date-box span {
            color: #4B1C3C;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #4B1C3C;
            font-weight: 500;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 8px;
        }

        .rollcall-select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .rollcall-select:hover {
            border-color: #FFB800;
        }

        .rollcall-select:focus {
            outline: none;
            border-color: #4B1C3C;
            box-shadow: 0 0 0 3px rgba(75, 28, 60, 0.1);
        }

        .option-item {
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .option-item i {
            width: 24px;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
        }

        .btn-primary:hover {
            background: #2F1224;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 28, 60, 0.3);
        }

        .btn-primary i {
            color: #FFB800;
        }

        .btn-secondary {
            background: #f0e8f0;
            color: #4B1C3C;
        }

        .btn-secondary:hover {
            background: #e0d0e0;
            transform: translateY(-2px);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .feature-item {
            text-align: center;
            padding: 10px;
            background: #f8f4f8;
            border-radius: 8px;
        }

        .feature-item i {
            color: #FFB800;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .feature-item small {
            color: #666;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="selector-card">
        <div class="header">
            <i class="fas fa-clipboard-list"></i>
            <h1>Export Roll Call</h1>
            <p>Choose which session to export</p>
        </div>

        <div class="date-box">
            <i class="far fa-calendar-alt"></i>
            <span><?php echo date('l, F j, Y', strtotime($date)); ?></span>
        </div>

        <form action="" method="GET" id="exportForm">
            <input type="hidden" name="date" value="<?php echo $date; ?>">

            <div class="form-group">
                <label>
                    <i class="fas fa-list-ul"></i>
                    Select Roll Call Type
                </label>
                <select name="type" class="rollcall-select" id="rollcallType" required>
                    <option value="" disabled selected>-- Choose a session --</option>
                    <option value="morning" style="color: #4B1C3C;">
                        🌅 Morning Roll Call (8:00 AM)
                    </option>
                    <option value="afternoon" style="color: #4B1C3C;">
                        ☀️ Afternoon Roll Call (3:30 PM)
                    </option>
                    <option value="evening" style="color: #4B1C3C;">
                        🌙 Evening Prep (9:00 PM)
                    </option>
                    <option value="full" style="color: #4B1C3C;">
                        📋 Complete Day Report
                    </option>
                </select>
            </div>

            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-sun"></i>
                    <small>Morning</small>
                </div>
                <div class="feature-item">
                    <i class="fas fa-cloud-sun"></i>
                    <small>Afternoon</small>
                </div>
                <div class="feature-item">
                    <i class="fas fa-moon"></i>
                    <small>Evening</small>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='attendance.php'">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="exportBtn" disabled>
                    <i class="fas fa-download"></i>
                    Export
                </button>
            </div>
        </form>
    </div>

    <script>
        const select = document.getElementById('rollcallType');
        const exportBtn = document.getElementById('exportBtn');
        const form = document.getElementById('exportForm');

        select.addEventListener('change', function() {
            exportBtn.disabled = false;
            
            // Change button icon based on selection
            const icons = {
                'morning': 'fa-sun',
                'afternoon': 'fa-cloud-sun',
                'evening': 'fa-moon',
                'full': 'fa-file-csv'
            };
            
            const icon = exportBtn.querySelector('i');
            icon.className = 'fas ' + (icons[this.value] || 'fa-download');
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const type = select.value;
            const date = new URLSearchParams(window.location.search).get('date') || '<?php echo date('Y-m-d'); ?>';
            
            let exportUrl = '';
            switch(type) {
                case 'morning':
                    exportUrl = 'rollcall-export-morning.php?date=' + date;
                    break;
                case 'afternoon':
                    exportUrl = 'rollcall-export-afternoon.php?date=' + date;
                    break;
                case 'evening':
                    exportUrl = 'rollcall-export-evening.php?date=' + date;
                    break;
                case 'full':
                    exportUrl = 'rollcall-export-full.php?date=' + date;
                    break;
                default:
                    alert('Please select a roll call type');
                    return;
            }
            
            window.location.href = exportUrl;
        });

        // Enable/disable export button based on selection
        select.addEventListener('change', function() {
            exportBtn.disabled = !this.value;
        });
    </script>
</body>
</html>