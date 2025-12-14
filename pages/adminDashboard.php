<?php
// ==========================================================
// pages/adminDashboard.php — Main Admin Dashboard (CLEANED)
// ==========================================================
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

// 1. RESTRICT ACCESS (Admins Only)
Auth::restrict(); 

$pdo = Database::getPDO();
$user = Auth::getUser(); 

// 2. PAGINATION & SEARCH CONFIG
$limit = 5; // Max 5 rows per section
$search = trim($_GET['search'] ?? '');
$pendingPage = max(1, intval($_GET['pending_page'] ?? 1));
$generatedPage = max(1, intval($_GET['generated_page'] ?? 1)); 
$activePage = max(1, intval($_GET['active_page'] ?? 1));
$archivedPage = max(1, intval($_GET['archived_page'] ?? 1));

// 3. HELPER: Build Query Conditions
$searchCondition = "";
$params = [];
if ($search) {
    // Enhanced search: looks for Student ID, Name, OR Email 
    $searchCondition = "AND (a.student_id LIKE :s OR a.surname LIKE :s OR a.given_name LIKE :s OR IFNULL(u.email, '') LIKE :s)";
    $params[':s'] = "%$search%";
}


// ==========================================================
// CODE 1: FETCH DATA FUNCTION (The Logic Fix)
// ==========================================================
function fetchAlumniData($pdo, $status, $searchCondition, $params, $limit, $page) {
    $offset = ($page - 1) * $limit;
    
    $whereClause = "";
    $orderBy = "ORDER BY u.created_at DESC";

    // --- MAPPED TO USERS.STATUS ---
    if ($status === 'generated') {
        // Alumni record exists but NO associated user account (Admin added records)
        $whereClause = "WHERE a.user_id IS NULL"; 
        $orderBy = "ORDER BY a.created_at ASC"; // Oldest First
    }
    elseif ($status === 'archived') {
        // Archived/Declined users
        $whereClause = "WHERE u.status IN ('archived', 'declined')";
        $orderBy = "ORDER BY u.archived_at DESC, u.declined_at DESC";
    }
    elseif ($status === 'pending') {
        // New applicants who created an account (pending admin approval)
        $whereClause = "WHERE u.status = 'pending'";
        $orderBy = "ORDER BY u.pending_at ASC"; // Oldest First
    }
    elseif ($status === 'approved') {
        $whereClause = "WHERE u.status = 'approved'";
        $orderBy = "ORDER BY u.approved_at DESC";
    }
    else {
        // Fallback
        $whereClause = "WHERE u.id IS NOT NULL";
    }

    // BASE QUERY - INDENTATION CLEANED
    $sql = "SELECT a.student_id, a.surname, a.given_name, u.email, u.status, u.created_at
        FROM alumni a
        LEFT JOIN users u ON a.user_id = u.id
        $whereClause
        $searchCondition
        $orderBy
        LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $execParams = array_merge([], $params);
    $stmt->execute($execParams);
    return $stmt->fetchAll();
}

// ==========================================================
// CODE 2: COUNT DATA FUNCTION (The Counter Fix)
// ==========================================================
function countAlumniData($pdo, $status, $searchCondition, $params) {
    // Logic matches fetchAlumniData
    if ($status === 'generated') {
        $whereClause = "WHERE a.user_id IS NULL";
    } elseif ($status === 'archived') {
        $whereClause = "WHERE u.status IN ('archived', 'declined')";
    } elseif ($status === 'pending') {
        $whereClause = "WHERE u.status = 'pending'";
    } elseif ($status === 'approved') {
        $whereClause = "WHERE u.status = 'approved'";
    } else {
        $whereClause = "WHERE u.id IS NOT NULL";
    }

    $sql = "SELECT COUNT(*) 
        FROM alumni a 
        LEFT JOIN users u ON a.user_id = u.id 
        $whereClause $searchCondition";
            
    $stmt = $pdo->prepare($sql);
    $execParams = array_merge([], $params);
    $stmt->execute($execParams);
    return $stmt->fetchColumn();
}

// 6. EXECUTE QUERIES
// A. Pending (New user registration awaiting approval)
$pending = fetchAlumniData($pdo, 'pending', $searchCondition, $params, $limit, $pendingPage);
$pendingTotal = countAlumniData($pdo, 'pending', $searchCondition, $params);

// B. Not Registered / Generated (Admin created records with no user account)
$generated = fetchAlumniData($pdo, 'generated', $searchCondition, $params, $limit, $generatedPage);
$generatedTotal = countAlumniData($pdo, 'generated', $searchCondition, $params);

// C. Active (Approved users)
$active = fetchAlumniData($pdo, 'approved', $searchCondition, $params, $limit, $activePage);
$activeTotal = countAlumniData($pdo, 'approved', $searchCondition, $params);

// D. Archived (Archived/Declined users)
$archived = fetchAlumniData($pdo, 'archived', $searchCondition, $params, $limit, $archivedPage);
$archivedTotal = countAlumniData($pdo, 'archived', $searchCondition, $params);

// Pagination Helper
function renderPagination($total, $limit, $currPage, $param) {
    $pages = ceil($total / $limit);
    if ($pages <= 1) return;
    echo '<div class="pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $cls = ($i == $currPage) ? 'active' : '';
        $qs = $_GET; $qs[$param] = $i;
        echo "<a href='?" . http_build_query($qs) . "' class='$cls'>$i</a>";
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - WMSU Alumni</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { background-color: #f4f6f9; overflow-y: auto; }
        .dashboard-container { max-width: 1200px; margin: 30px auto; padding: 20px; }
        
        /* HEADER */
        .admin-header {
            display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 20px 30px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 5px solid #b30000;
            margin-bottom: 25px;
        }
        .admin-nav a {
            text-decoration: none; color: #555; margin-left: 20px; font-weight: 600; font-size: 0.95em;
            padding-bottom: 5px; transition: color 0.3s;
        }
        .admin-nav a:hover { color: #b30000; border-bottom: 2px solid #b30000; }
        .admin-nav a.logout-link { color: #dc3545; }

        /* SECTIONS */
        .data-section {
            background: white; padding: 25px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px;
        }
        .data-section h2 { margin-top: 0; color: #b30000; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        
        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; text-align: left; padding: 12px; color: #555; font-weight: 700; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background-color: #f1f1f1; }
        
        /* ACTIONS */
        .btn-action { text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.85em; font-weight: 600; margin-right: 5px; display: inline-block; }
        .btn-view { background: #17a2b8; color: white; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-archive { background: #6c757d; color: white; }
        .btn-restore { background: #ffc107; color: #333; }
        
        /* PAGINATION */
        .pagination { margin-top: 15px; text-align: center; }
        .pagination a { display: inline-block; padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; color: #333; text-decoration: none; border-radius: 3px; }
        .pagination a.active { background-color: #b30000; color: white; border-color: #b30000; }

        /* BADGES */
        .badge-not-set { color: #999; font-style: italic; background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <header class="admin-header">
        <div>
            <h1 style="margin:0; font-size:1.6rem; color:#333;">Admin Dashboard</h1>
            <p style="margin:5px 0 0 0; color:#777;">Welcome, <?= htmlspecialchars($user['display_name']) ?></p>
        </div>
        <nav class="admin-nav">
            <?php if (Auth::hasPermission('reports.view')): ?>
                <a href="adminAnalytics.php">📊 Analytics</a>
                <a href="reportGenerator.php">🧾 Reports</a>
            <?php endif; ?>

            <?php if (Auth::hasPermission('alumni.manage')): ?>
                <a href="addAlumni.php">➕ Add Alumni</a>
            <?php endif; ?>
            
            <?php if (Auth::hasPermission('users.manage')): ?>
                <a href="manageAdmins.php">👥 Manage Admins</a>
            <?php endif; ?>
            
            <?php if ($user['role'] === 'both'): ?>
                <a href="alumniLanding.php">🔄 Alumni View</a>
            <?php endif; ?>
            
            <a href="auth/logout.php" class="logout-link">🚪 Logout</a>
        </nav>
    </header>
    
    <form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, ID, or email..." style="flex:1; padding:12px; border:1px solid #ddd; border-radius:6px;">
        <button type="submit" style="padding:12px 25px; background:#b30000; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">Search</button>
        <?php if($search): ?>
            <a href="adminDashboard.php" style="padding:12px 20px; background:#eee; color:#333; text-decoration:none; border-radius:6px;">Clear</a>
        <?php endif; ?>
    </form>

    <div class="data-section">
        <h2>⏳ Pending Approvals</h2>
        <?php if(count($pending) > 0): ?>
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Applied On</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($pending as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="viewPending.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-view">View</a>
                            <a href="../functions/approve.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-approve" onclick="return confirm('Approve this user?')">✓</a>
                            <a href="../functions/reject.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-reject" onclick="return confirm('Reject this user?')">✕</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php renderPagination($pendingTotal, $limit, $pendingPage, 'pending_page'); ?>
        <?php else: ?>
            <p style="color:#777; font-style:italic;">No pending applications.</p>
        <?php endif; ?>
    </div>

    <div class="data-section" style="border-top: 4px solid #17a2b8;">
        <h2 style="color: #17a2b8; border-color: #17a2b8;">🛡️ Not Registered (Admin Created)</h2>
        <?php if(count($generated) > 0): ?>
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Created On</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($generated as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?></td>
                        <td>
                            <?php if (empty($row['email'])): ?>
                                <span class="badge-not-set">Not Set</span>
                            <?php else: ?>
                                <?= htmlspecialchars($row['email']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="viewPending.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-view">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php renderPagination($generatedTotal, $limit, $generatedPage, 'generated_page'); ?>
        <?php else: ?>
            <p style="color:#777; font-style:italic;">No admin-created accounts pending email registration.</p>
        <?php endif; ?>
    </div>

    <div class="data-section">
        <h2>✅ Active Alumni</h2>
        <?php if(count($active) > 0): ?>
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($active as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td><span style="background:#d4edda; color:#155724; padding:2px 8px; border-radius:4px; font-size:0.85em;">Active</span></td>
                        <td>
                            <a href="viewPending.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-view">Profile</a>
                            <a href="../functions/archive.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-archive" onclick="return confirm('Archive this record?')">Archive</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php renderPagination($activeTotal, $limit, $activePage, 'active_page'); ?>
        <?php else: ?>
            <p style="color:#777; font-style:italic;">No active alumni found.</p>
        <?php endif; ?>
    </div>

    <div class="data-section">
        <h2>📂 Archived / Rejected</h2>
        <?php if(count($archived) > 0): ?>
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($archived as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_id']) ?></td>
                        <td><?= htmlspecialchars($row['surname'] . ', ' . $row['given_name']) ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td>
                            <span style="background:#f8d7da; color:#721c24; padding:2px 8px; border-radius:4px; font-size:0.85em;">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="../functions/restore.php?id=<?= urlencode($row['student_id']) ?>" class="btn-action btn-restore" onclick="return confirm('Restore to Active?')">Restore</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php renderPagination($archivedTotal, $limit, $archivedPage, 'archived_page'); ?>
        <?php else: ?>
            <p style="color:#777; font-style:italic;">No archived records.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>