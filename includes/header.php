<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/auth.php';

$user = Auth::currentUser();

// LOCALHOST BASE URL FIX
$BASE = rtrim(BASE_URL, '/');
?>

<header style="
    width:100%;
    background:#b30000;
    padding:12px 18px;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
    position:sticky;
    top:0;
    z-index:99;
">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="<?= $BASE ?>/assets/images/logo1.png"
             style="height:40px; filter:drop-shadow(0 0 1px #000);" />
        <span style="font-size:1.2em; font-weight:700;">WMSU Alumni Directory</span>
    </div>

    <div style="display:flex; align-items:center; gap:18px;">

        <?php if ($user): ?>
            <!-- NOTIFICATION BELL -->
            <div id="notifBellContainer" style="position:relative;">
                <button id="notifBell"
                    style="background:none;border:none;cursor:pointer;font-size:1.3em;color:white;">
                    🔔
                    <span id="notifBadge"
                        style="
                            display:inline-block;
                            min-width:18px;
                            padding:2px 6px;
                            border-radius:12px;
                            background:#fff;
                            color:#b30000;
                            font-size:0.8em;
                            font-weight:700;
                            margin-left:6px;
                        ">
                        0
                    </span>
                </button>

                <div id="notifDropdown"
                     style="
                        display:none;
                        position:absolute;
                        right:0;
                        top:36px;
                        width:320px;
                        background:white;
                        border:1px solid #eee;
                        border-radius:8px;
                        box-shadow:0 6px 20px rgba(0,0,0,0.15);
                        z-index:999;
                     ">
                    <div style="padding:10px; border-bottom:1px solid #f2f2f2; font-weight:700; color:#333;">
                        Notifications
                    </div>
                    <div id="notifList" style="max-height:320px; overflow:auto;"></div>
                    <div style="padding:10px; text-align:center;">
                        <a href="<?= $BASE ?>/pages/notifications.php">View all</a>
                    </div>
                </div>
            </div>

            <span style="color:white; font-weight:600;">
                Hello, <?= htmlspecialchars($user['display_name'] ?? $user['email']) ?>
            </span>

            <?php if (Auth::isAdmin()): ?>
                <a href="<?= $BASE ?>/pages/adminDashboard.php"
                   style="
                        background:white;
                        color:#b30000;
                        padding:6px 12px;
                        border-radius:6px;
                        font-weight:700;
                        text-decoration:none;
                   ">
                   Admin Dashboard
                </a>
            <?php endif; ?>

            <a href="<?= $BASE ?>/pages/auth/logout.php"
               style="color:white; text-decoration:none; font-weight:600;">
               Logout
            </a>

        <?php else: ?>
            <a href="<?= $BASE ?>/pages/auth/login.php"
               style="color:white; text-decoration:none; font-weight:600;">
               Login
            </a>
        <?php endif; ?>
    </div>
</header>

<?php if ($user): ?>
<script>
function escapeHtml(s) {
    return (s + "").replace(/[&<>"']/g, c => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;",
        '"': "&quot;", "'": "&#39;"
    }[c]));
}

async function loadNotifications() {
    try {
        const res = await fetch("<?= $BASE ?>/functions/getNotifications.php");
        if (!res.ok) return;

        const data = await res.json();
        document.getElementById("notifBadge").textContent = data.unread || 0;

        const list = document.getElementById("notifList");
        list.innerHTML = "";

        if (!data.items || data.items.length === 0) {
            list.innerHTML = "<div style='padding:12px;color:#666;'>No notifications</div>";
            return;
        }

        data.items.forEach(item => {
            const div = document.createElement("div");
            div.style = `
                padding:10px;
                border-bottom:1px solid #f6f6f6;
                font-size:0.95em;
                cursor:pointer;
            `;
            div.innerHTML = `
                <div style="font-weight:600;">${escapeHtml(item.type)}</div>
                <div style="color:#444;">${escapeHtml(item.message)}</div>
                <div style="color:#999;font-size:0.85em;margin-top:6px;">
                    ${item.created_at}
                </div>
            `;
            div.onclick = async () => {
                await fetch("<?= $BASE ?>/functions/markNotificationRead.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + encodeURIComponent(item.id)
                });
                loadNotifications();
            };
            list.appendChild(div);
        });

    } catch (err) {
        console.error(err);
    }
}

document.getElementById("notifBell").addEventListener("click", () => {
    const box = document.getElementById("notifDropdown");
    box.style.display = box.style.display === "block" ? "none" : "block";
    if (box.style.display === "block") loadNotifications();
});

window.addEventListener("click", (e) => {
    if (!document.getElementById("notifBellContainer").contains(e.target)) {
        document.getElementById("notifDropdown").style.display = "none";
    }
});

loadNotifications();
</script>
<?php endif; ?>
