<?php
// File: pages/manageAdmins.php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

Auth::restrict('users.manage');

$pdo = Database::getPDO();
$user = Auth::getUser();

// SEARCH LOGIC
$search = trim($_GET['search'] ?? '');
$searchQuery = "";
$params = [];
if ($search) {
    $searchQuery = "AND (a.full_name LIKE :s OR u.email LIKE :s)";
    $params[':s'] = "%$search%";
}

// FETCH ADMINS
$sql = "
    SELECT 
        a.id as admin_id, 
        a.full_name, 
        u.email, 
        u.role as system_role, 
        u.status, 
        u.last_login,
        COUNT(ap.permission_id) as perm_count
    FROM admins a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN admin_permissions ap ON a.id = ap.admin_id
    WHERE u.status <> 'archived' $searchQuery
    GROUP BY a.id
    ORDER BY perm_count DESC, a.full_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll();

// Get total possible permissions for calculation
$totalPerms = $pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();

// Helper to guess 'Tier' Name (Design is now uniform)
function getAdminRoleName($count, $totalPerms) {
    if ($count >= $totalPerms) return 'Super Admin'; 
    if ($count > 3) return 'Alumni Officer';   
    if ($count > 0) return 'Assistant';      
    return 'No Access';
}

// Helper for Initials Avatar
function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initials;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins - WMSU</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { background-color: #f4f6f9; overflow-y: auto; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* HEADER */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 20px 30px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #b30000;
            margin-bottom: 25px;
        }
        .header-title h1 { margin: 0; font-size: 1.5rem; color: #333; }
        .header-title p { margin: 5px 0 0; color: #666; font-size: 0.9em; }

        .btn-add { 
            background: #b30000; color: white; padding: 12px 25px; text-decoration: none; 
            border-radius: 6px; font-weight: bold; box-shadow: 0 4px 6px rgba(179,0,0,0.2); 
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
        }
        .btn-add:hover { background: #8a0000; transform: translateY(-2px); }

        /* SEARCH BAR */
        .search-container { margin-bottom: 30px; display: flex; gap: 10px; }
        .search-input { 
            flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; 
            font-size: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .btn-search {
            padding: 12px 25px; background: #333; color: white; border: none; 
            border-radius: 6px; cursor: pointer; font-weight: bold;
        }

        /* GRID LAYOUT */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        /* UNIFORM RED CARD STYLE */
        .admin-card {
            background: white; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column; 
            
            /* WMSU RED BORDER FOR EVERYONE */
            border-top: 5px solid #b30000; 
        }
        .admin-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        .card-body { padding: 25px; flex: 1; display: flex; align-items: flex-start; gap: 15px; }
        
        /* UNIFORM RED AVATAR */
        .avatar {
            width: 50px; height: 50px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; 
            
            /* WMSU Theme Colors */
            background: #fff5f5; 
            color: #b30000;
        }

        .info h3 { margin: 0 0 5px; font-size: 1.1rem; color: #333; }
        .info p { margin: 0; color: #777; font-size: 0.9em; word-break: break-all; }

        /* UNIFORM RED BADGES */
        .badges { margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap; }
        .role-badge { 
            font-size: 0.75em; font-weight: 700; padding: 4px 10px; border-radius: 12px; 
            text-transform: uppercase; letter-spacing: 0.5px;
            
            /* WMSU Red Styling for Roles */
            background: #b30000; 
            color: white;
        }
        
        /* Special Green Badge only for Alumni Status */
        .badge-alumni { 
            background: #fff; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }

        /* FOOTER ACTIONS */
        .card-footer {
            background: #f8f9fa; padding: 15px 25px; border-top: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .perm-count { font-size: 0.85em; color: #666; font-weight: 600; }
        
        .actions { display: flex; gap: 10px; }
        .btn-icon {
            text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.85em; 
            font-weight: 600; transition: all 0.2s;
        }
        .btn-edit { background: white; border: 1px solid #ddd; color: #333; }
        .btn-edit:hover { border-color: #b30000; color: #b30000; }
        
        .btn-revoke { background: #fff5f5; border: 1px solid #f5c6cb; color: #dc3545; }
        .btn-revoke:hover { background: #dc3545; color: white; border-color: #dc3545; }

        .back-link { display: inline-block; margin-top: 30px; color: #666; text-decoration: none; }
        .back-link:hover { color: #333; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="page-header">
        <div class="header-title">
            <h1>Admin Hierarchy</h1>
            <p>Manage system access levels, promote staff, and configure permissions.</p>
        </div>
        <a href="adminForm.php" class="btn-add">
            <span style="font-size:1.2em;">+</span> Add Administrator
        </a>
    </div>

    <form class="search-container">
        <input type="text" name="search" class="search-input" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email...">
        <button type="submit" class="btn-search">Search</button>
    </form>

    <div class="admin-grid">
        <?php if (count($admins) > 0): ?>
            <?php foreach ($admins as $adm): 
                $roleName = getAdminRoleName($adm['perm_count'], $totalPerms);
            ?>
            <div class="admin-card">
                <div class="card-body">
                    <div class="avatar"><?= getInitials($adm['full_name']) ?></div>
                    <div class="info">
                        <h3><?= htmlspecialchars($adm['full_name']) ?></h3>
                        <p><?= htmlspecialchars($adm['email']) ?></p>
                        
                        <div class="badges">
                            <span class="role-badge"><?= $roleName ?></span>
                            
                            <?php if($adm['system_role'] === 'both'): ?>
                                <span class="role-badge badge-alumni">ALUMNI</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="perm-count">
                        <?= $adm['perm_count'] ?> Active Permissions
                    </div>
                    <div class="actions">
                        <a href="adminForm.php?id=<?= $adm['admin_id'] ?>" class="btn-icon btn-edit">Edit</a>
                        
                        <?php if ($adm['email'] !== $user['email']): ?>
                            <a href="../functions/revokeAdmin.php?id=<?= $adm['admin_id'] ?>" class="btn-icon btn-revoke" onclick="return confirm('Revoke admin access for this user?')">Revoke</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 40px; color: #888;">
                <h3>No administrators found.</h3>
                <p>Click "Add Administrator" to get started.</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="adminDashboard.php" class="back-link">&larr; Back to Dashboard</a>

</div>

</body>
</html>