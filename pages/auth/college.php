<?php
// File: pages/auth/college.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/database.php';

// Fetch Colleges
try {
    $pdo = Database::getPDO();
    $stmt = $pdo->query("SELECT * FROM colleges ORDER BY name ASC");
    $colleges = $stmt->fetchAll();
} catch (Exception $e) {
    $colleges = []; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select College - Registration</title>
    <link rel="stylesheet" href="../../assets/css/index.css">
    <style>
        /* Force default background initially */
        .left-pane {
            background-image: 
                linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%),
                url('../../assets/images/default-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        .right-pane {
            background-color: #f8f9fa;
            background-image: radial-gradient(#e9ecef 1px, transparent 1px);
            background-size: 20px 20px; 
        }
        .login-box { border: 3px solid #b30000; }
    </style>
</head>
<body>

    <div class="split-container">
        <div class="left-pane" id="bg-pane"></div>

        <div class="right-pane">
            <div class="login-box">
                <img src="../../assets/images/logo1.png" alt="WMSU Logo" class="logo">
                
                <h1>Create Account</h1>
                <div class="title-underline"></div>
                
                <p class="subtitle">To begin registration, please select your College.</p>

                <div class="form-group">
                    <label for="college-selector">Select College</label>
                    <select id="college-selector">
                        <option value="" disabled selected>-- Choose College --</option>
                        
                        <?php foreach ($colleges as $col): ?>
                            <?php 
                                $bgImage = 'default-bg.jpg';
                                $code = strtoupper($col['code']);
                                if ($code === 'CCS') $bgImage = 'ccs-bg.jpg';
                                elseif ($code === 'CN') $bgImage = 'cn-bg.jpg';
                            ?>
                            <option 
                                value="<?= htmlspecialchars($col['id']) ?>" 
                                data-code="<?= htmlspecialchars($code) ?>"
                                data-img="<?= htmlspecialchars($bgImage) ?>"
                            >
                                <?= htmlspecialchars($col['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn-continue" onclick="proceedToRegister()">
                    Next Step &rarr;
                </button>

                <div class="footer-link">
                    <a href="../../index.php">&larr; Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const selector = document.getElementById('college-selector');
        const bgPane = document.getElementById('bg-pane');

        const GRADIENTS = {
            'DEFAULT': 'linear-gradient(to top, rgba(139, 0, 0, 0.9) 0%, rgba(139, 0, 0, 0.1) 100%)',
            'CCS':     'linear-gradient(to top, rgba(0, 80, 0, 0.9) 0%, rgba(0, 80, 0, 0.1) 100%)',
            'CN':      'linear-gradient(to top, rgba(233, 30, 99, 0.9) 0%, rgba(233, 30, 99, 0.1) 100%)'
        };

        selector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const imgName = selectedOption.getAttribute('data-img');
            const code = selectedOption.getAttribute('data-code');

            if(code) {
                let gradient = GRADIENTS[code] || GRADIENTS['DEFAULT'];
                bgPane.style.backgroundImage = `${gradient}, url('../../assets/images/${imgName}')`;
            }
        });

        function proceedToRegister() {
            const collegeId = selector.value;
            if (!collegeId) {
                alert("Please select a college first.");
                return;
            }
            // REDIRECT TO REGISTER WITH ID
            window.location.href = `register.php?college_id=${collegeId}`;
        }
    </script>
</body>
</html>