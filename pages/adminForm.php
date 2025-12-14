<?php
// File: pages/adminForm.php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

Auth::restrict('users.manage');
$pdo = Database::getPDO();

// 1. CHECK IF EDITING OR ADDING
$adminId = $_GET['id'] ?? null;
$isEdit = !empty($adminId);

// Default Empty Data
$formData = [
    'full_name' => '', 
    'email' => '', 
    'role' => 'admin', 
    'permissions' => []
];

// 2. IF EDITING, FETCH EXISTING DATA
if ($isEdit) {
    // Join Admins + Users tables to get all info
    $stmt = $pdo->prepare("
        SELECT a.id, a.full_name, u.email, u.role, u.id as user_id
        FROM admins a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$adminId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $formData['full_name'] = $existing['full_name'];
        $formData['email'] = $existing['email'];
        $formData['role'] = $existing['role'];
        
        // Fetch their current permissions
        $stmtP = $pdo->prepare("SELECT permission_id FROM admin_permissions WHERE admin_id = ?");
        $stmtP->execute([$adminId]);
        $formData['permissions'] = $stmtP->fetchAll(PDO::FETCH_COLUMN);
    } else {
        die("Admin not found.");
    }
}

// Fetch all available permissions for the checkboxes
$allPerms = $pdo->query("SELECT * FROM permissions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. HANDLE FORM SUBMISSION
$msg = ""; $err = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $isAlumni = isset($_POST['is_alumni']);
    $perms = $_POST['perms'] ?? [];
    
    // Determine Role
    $newRole = $isAlumni ? 'both' : 'admin';

    try {
        $pdo->beginTransaction();

        if ($isEdit) {
            // === UPDATE LOGIC ===
            $uid = $existing['user_id'];
            
            // 1. Update User Table (Email & Role)
            $sql = "UPDATE users SET role = ?, email = ? WHERE id = ?";
            $params = [$newRole, $email, $uid];
            
            // 2. Update Password ONLY if typed
            if (!empty($password)) {
                $sql = "UPDATE users SET role = ?, email = ?, password_hash = ? WHERE id = ?";
                $params = [$newRole, $email, password_hash($password, PASSWORD_DEFAULT), $uid];
            }
            $pdo->prepare($sql)->execute($params);
            
            // 3. Update Admin Profile (Name)
            $pdo->prepare("UPDATE admins SET full_name = ? WHERE id = ?")->execute([$fullName, $adminId]);
            $targetAdminId = $adminId;
            
            $msg = "Admin account updated successfully.";

        } else {
            // === CREATE LOGIC ===
            // 1. Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($u) {
                // Upgrade existing user
                $uid = $u['id'];
                $pdo->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?")->execute([$newRole, $hash, $uid]);
            } else {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status, is_verified, display_name) VALUES (?, ?, ?, 'approved', 1, ?)");
                $stmt->execute([$email, $hash, $newRole, $fullName]);
                $uid = $pdo->lastInsertId();
            }

            // 2. Create Admin Profile
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
            $stmt->execute([$uid]);
            if (!($targetAdminId = $stmt->fetchColumn())) {
                $pdo->prepare("INSERT INTO admins (user_id, full_name, created_at) VALUES (?, ?, NOW())")->execute([$uid, $fullName]);
                $targetAdminId = $pdo->lastInsertId();
            }
            $msg = "New Admin created successfully.";
        }

        // 4. UPDATE PERMISSIONS (Delete old -> Insert new)
        $pdo->prepare("DELETE FROM admin_permissions WHERE admin_id = ?")->execute([$targetAdminId]);
        
        if (!empty($perms)) {
            $stmtI = $pdo->prepare("INSERT INTO admin_permissions (admin_id, permission_id) VALUES (?, ?)");
            foreach ($perms as $pid) {
                $stmtI->execute([$targetAdminId, $pid]);
            }
        }

        $pdo->commit();
        
        // Redirect back to list after short delay
        header("Refresh: 1; url=manageAdmins.php");

    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEdit ? 'Edit' : 'Add' ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { background: #f4f6f9; overflow-x: hidden; }
        
        /* FULL WIDTH CONTAINER */
        .dashboard-container { 
            max-width: 95%; 
            margin: 30px auto; 
            padding: 0 20px; 
        }

        /* HEADER */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .back-link { 
            display: inline-flex; align-items: center; gap: 5px;
            color: #666; text-decoration: none; font-weight: 600; font-size: 1.1em;
            padding: 10px 15px; background: white; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .back-link:hover { color: #b30000; transform: translateX(-3px); }

        /* CARD STYLE */
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            border-top: 6px solid #b30000;
        }

        h1 { margin: 0; color: #333; font-size: 2rem; }
        p.subtitle { color: #666; margin-top: 5px; font-size: 1rem; }

        /* ALERTS */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-size: 1rem; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* GRID INPUTS */
        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        label { font-weight: 700; display: block; margin-bottom: 8px; color: #444; text-transform: uppercase; font-size: 0.85em; letter-spacing: 0.5px; }
        
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 14px 18px; border: 2px solid #e9ecef; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.3s;
        }
        input:focus { border-color: #b30000; outline: none; }

        /* SPLIT SECTION */
        .split-section {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .highlight-box {
            background: #fff; border: 1px solid #e9ecef; border-radius: 8px;
            padding: 25px; height: 100%;
        }
        .highlight-box.red-theme { background-color: #fff8f8; border-color: #fadbd8; }
        
        .box-header {
            font-size: 1.1em; color: #b30000; font-weight: 700; margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef; padding-bottom: 10px;
        }

        /* PERMISSIONS GRID */
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .custom-checkbox {
            display: flex; align-items: flex-start; gap: 12px;
            cursor: pointer; padding: 10px; border-radius: 6px; border: 1px solid transparent; transition: all 0.2s;
        }
        .custom-checkbox:hover { background: #f8f9fa; border-color: #ddd; }
        
        .custom-checkbox input[type="checkbox"] {
            width: 20px; height: 20px; margin-top: 2px; accent-color: #b30000; cursor: pointer;
        }

        /* TOGGLE SWITCH */
        .toggle-wrapper { display: flex; align-items: center; gap: 20px; margin-top: 10px; }
        .toggle-switch { position: relative; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #b30000; }
        input:checked + .slider:before { transform: translateX(26px); }

        .btn-submit {
            grid-column: 1 / -1; width: 100%; padding: 18px; background-color: #b30000; color: white;
            font-size: 1.2rem; font-weight: 700; border: none; border-radius: 8px;
            cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(179, 0, 0, 0.2); margin-top: 30px;
        }
        .btn-submit:hover { background-color: #8a0000; transform: translateY(-2px); }

        @media (max-width: 1024px) {
            .input-grid { grid-template-columns: 1fr; gap: 15px; }
            .split-section { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <div class="page-header">
        <div>
            <h1><?= $isEdit ? 'Edit Administrator' : 'Add Administrator' ?></h1>
            <p class="subtitle">
                <?= $isEdit ? 'Update details, roles, and permissions for this account.' : 'Create new admin accounts and configure specific access permissions.' ?>
            </p>
        </div>
        <a href="manageAdmins.php" class="back-link">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to List
        </a>
    </div>

    <div class="form-card">
        <?php if ($msg): ?> <div class="alert alert-success">✅ <?= $msg ?></div> <?php endif; ?>
        <?php if ($err): ?> <div class="alert alert-error">⚠️ <?= $err ?></div> <?php endif; ?>

        <form method="POST">
            
            <div class="input-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required placeholder="admin@wmsu.edu.ph">
                </div>
                <div class="form-group">
                    <label>
                        Password
                        <?php if($isEdit): ?><span style="font-weight:normal; text-transform:none; color:#888;">(Leave blank to keep current)</span><?php endif; ?>
                    </label>
                    <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> placeholder="••••••••">
                </div>
            </div>

            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #bbdefb;">
                <label style="color:#0d47a1; margin-bottom: 5px;">Quick Role Preset</label>
                <select id="rolePreset" onchange="applyPreset()" style="margin-bottom: 5px; border-color: #90caf9;">
                    <option value="custom">-- Select to Auto-Fill Permissions --</option>
                    <option value="super">👑 Super Admin (Full Access)</option>
                    <option value="officer">👤 Alumni Officer (Manage Records)</option>
                    <option value="assistant">📋 Assistant (View Only)</option>
                </select>
                <small style="color:#555;">Automatically checks the correct boxes below.</small>
            </div>

            <div class="split-section">
                
                <div class="highlight-box red-theme">
                    <div class="box-header">Role Configuration</div>
                    <p style="font-size:0.95em; color:#555; line-height:1.6; margin-bottom:20px;">
                        Is this administrator also a registered alumni of WMSU?
                    </p>
                    
                    <div class="toggle-wrapper">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_alumni" value="1" <?= ($formData['role'] === 'both') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                        <div>
                            <strong style="display:block; color:#b30000; font-size:1.1em;">Enable Alumni Access</strong>
                            <span style="font-size:0.9em; color:#666;">If ON, they can access <strong>Alumni Profile</strong> features.</span>
                        </div>
                    </div>
                </div>

                <div class="highlight-box">
                    <div class="box-header">Access Permissions</div>
                    
                    <div class="permissions-grid">
                        <?php if(!empty($allPerms)): ?>
                            <?php foreach ($allPerms as $perm): ?>
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="perms[]" value="<?= $perm['id'] ?>" 
                                           class="perm-chk" data-code="<?= htmlspecialchars($perm['code']) ?>"
                                           <?= in_array($perm['id'], $formData['permissions']) ? 'checked' : '' ?>>
                                    
                                    <div class="checkbox-label">
                                        <strong style="display:block; color:#333;"><?= htmlspecialchars($perm['code']) ?></strong>
                                        <small style="color:#777; font-size:0.85em;"><?= htmlspecialchars($perm['description']) ?></small>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#b30000;">⚠️ No permissions found in database.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn-submit">
                <?= $isEdit ? 'Update Admin Account' : 'Create Admin Account' ?>
            </button>
        </form>
    </div>
</div>

<script>
// PRESET LOGIC MATCHING YOUR DATABASE CODES
const presets = {
    'super': ['ALL'], 
    'officer': ['alumni.manage', 'applications.view', 'applications.approve', 'applications.reject', 'reports.view'],
    'assistant': ['alumni.manage', 'applications.view'] 
};

function applyPreset() {
    const role = document.getElementById('rolePreset').value;
    const checkboxes = document.querySelectorAll('.perm-chk');
    
    if (role === 'custom') return; 

    checkboxes.forEach(chk => {
        const code = chk.getAttribute('data-code');
        if (role === 'super' || (presets[role] && presets[role].includes(code))) {
            chk.checked = true;
        } else {
            chk.checked = false;
        }
    });
}
</script>

</body>
</html>