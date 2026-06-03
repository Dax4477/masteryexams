<?php
session_start();

// --- SECURE PASSWORD ---
$DASHBOARD_PASSWORD = "masteryadmin"; 

// --- DATABASE SETUP (AUTO-BUILD) ---
$db_file = __DIR__ . '/mastery_tracker.sqlite';
$pdo = new PDO("sqlite:" . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Upgraded Table with Name and Phone columns
$pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
    device_id TEXT PRIMARY KEY,
    current_page TEXT,
    total_visits INTEGER,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_name TEXT DEFAULT 'Anonymous',
    user_phone TEXT DEFAULT '-'
)");

// --- BACKGROUND TRACKER ENDPOINT ---
if (isset($_GET['track'])) {
    $device_id = $_COOKIE['mastery_device_id'] ?? bin2hex(random_bytes(16));
    setcookie('mastery_device_id', $device_id, time() + (86400 * 365), "/"); 
    $page = $_GET['page'] ?? 'Unknown';
    
    $stmt = $pdo->prepare("SELECT total_visits FROM visitors WHERE device_id = ?");
    $stmt->execute([$device_id]);
    $existing = $stmt->fetch();
    
    // Check if we already counted them during this current browsing session
    $is_new_session = !isset($_SESSION['visit_counted_today']);
    
    if ($existing) {
        if ($is_new_session) {
            // New visit: Update page, time, AND add +1 to total visits
            $update = $pdo->prepare("UPDATE visitors SET current_page = ?, total_visits = total_visits + 1, last_active = CURRENT_TIMESTAMP WHERE device_id = ?");
            $_SESSION['visit_counted_today'] = true; // Mark them as counted
        } else {
            // Just clicking around: Only update the page and time (NO +1 to visits)
            $update = $pdo->prepare("UPDATE visitors SET current_page = ?, last_active = CURRENT_TIMESTAMP WHERE device_id = ?");
        }
        $update->execute([$page, $device_id]);
    } else {
        // Brand new device: Insert them with 1 visit
        $insert = $pdo->prepare("INSERT INTO visitors (device_id, current_page, total_visits) VALUES (?, ?, 1)");
        $insert->execute([$device_id, $page]);
        $_SESSION['visit_counted_today'] = true; // Mark them as counted
    }
    exit;
}
// --- HANDLE DELETE ACTIONS (AJAX) ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['mastery_admin_logged_in'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    if ($_POST['action'] === 'delete_single' && isset($_POST['device_id'])) {
        $stmt = $pdo->prepare("DELETE FROM visitors WHERE device_id = ?");
        $stmt->execute([$_POST['device_id']]);
        echo json_encode(['success' => true]); exit;
    }
    if ($_POST['action'] === 'clear_all') {
        $pdo->exec("DELETE FROM visitors");
        echo json_encode(['success' => true]); exit;
    }
}

// --- AJAX LIVE DATA ENDPOINT ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['mastery_admin_logged_in'])) { echo json_encode(['error' => 'Unauthorized']); exit; }
    
    $live_stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE strftime('%s', 'now') - strftime('%s', last_active) < 30");
    $live_users = $live_stmt->fetch()['count'];

    $total_stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors");
    $total_users = $total_stmt->fetch()['count'];

    $log_stmt = $pdo->query("SELECT * FROM visitors ORDER BY last_active DESC LIMIT 15");
    $logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $parsed_logs = [];
    foreach($logs as $row) {
        $dt = new DateTime($row['last_active'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $formatted_date = $dt->format('M j, Y \a\t g:i A');
        $seconds_ago = time() - $dt->getTimestamp();
        
        $status = ($seconds_ago < 30) ? '<span class="text-blue-500 flex items-center gap-1"><span class="w-2 h-2 bg-blue-600 rounded-full animate-pulse"></span>Live</span>' : '<span class="text-slate-500">Away</span>';
        
        if ($seconds_ago < 60) { $rel_time = "Just now"; } 
        elseif ($seconds_ago < 3600) { $rel_time = floor($seconds_ago/60) . " mins ago"; } 
        elseif ($seconds_ago < 86400) { $rel_time = floor($seconds_ago/3600) . " hours ago"; } 
        else { $rel_time = floor($seconds_ago/86400) . " days ago"; }

        // Logic to show Name/WhatsApp if available, otherwise show Device ID
        if ($row['user_name'] !== 'Anonymous') {
            $display_identity = '<span class="text-indigo-600 font-bold">' . htmlspecialchars($row['user_name']) . '</span><br><span class="text-[11px] text-emerald-600 font-bold">☎ ' . htmlspecialchars($row['user_phone']) . '</span>';
        } else {
            $display_identity = substr($row['device_id'], 0, 12) . '...';
        }

        $parsed_logs[] = [
            'raw_id' => $row['device_id'], 
            'id' => $display_identity,
            'page' => htmlspecialchars($row['current_page']),
            'visits' => $row['total_visits'],
            'status' => $status,
            'time_ago' => $rel_time . '<br><span class="text-[10px] text-slate-500 not-italic">' . $formatted_date . '</span>'
        ];
    }
    
    echo json_encode(['live' => $live_users, 'total' => $total_users, 'logs' => $parsed_logs]); exit;
}

// --- LOGIN LOGIC ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit; }
if (isset($_POST['password']) && $_POST['password'] === $DASHBOARD_PASSWORD) {
    $_SESSION['mastery_admin_logged_in'] = true;
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// --- SHOW LOGIN SCREEN ---
if (!isset($_SESSION['mastery_admin_logged_in']) || $_SESSION['mastery_admin_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Mastery Radar | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center p-4">
    <div class="bg-white border border-gray-200 p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-blue-600 mb-2">MasteryExams Radar</h1>
        <p class="text-gray-500 text-sm mb-8">Enter password to view live student traffic.</p>
        <form method="POST" class="space-y-4">
            <input type="password" name="password" required autofocus class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-900 text-center focus:ring-2 focus:ring-blue-500 outline-none">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg uppercase shadow-md transition-all">View Radar</button>
        </form>
    </div>
</body>
</html>
<?php exit; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mastery Live Radar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { background-color: #f8fafc; color: #0f172a; font-family: 'Inter', sans-serif; } </style>
</head>
<body class="p-6 md:p-10 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-600 flex items-center gap-3">
                    <span class="w-3 h-3 bg-blue-500 rounded-full animate-ping absolute"></span>
                    <span class="w-3 h-3 bg-blue-500 rounded-full relative"></span>
                    Mastery Radar
                </h1>
                <p class="text-gray-500 text-sm mt-1">Real-time student tracking for masteryexams.online</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="clearAllData()" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2 shadow-sm">
                    Reset DB
                </button>
                <a href="?logout=true" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm transition-all border border-gray-200 shadow-sm font-semibold">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-white border border-gray-200 p-6 rounded-2xl shadow-sm flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-sm font-bold uppercase tracking-widest mb-2">Students Online Now</span>
                <span id="metric-live" class="text-6xl font-black text-blue-600">-</span>
            </div>
            <div class="bg-white border border-gray-200 p-6 rounded-2xl shadow-sm flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-sm font-bold uppercase tracking-widest mb-2">Total Unique Visitors</span>
                <span id="metric-total" class="text-6xl font-black text-indigo-600">-</span>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-gray-800">Recent Activity Log</h2>
                <span class="text-xs text-blue-600 bg-blue-50 font-bold px-3 py-1 rounded-full border border-blue-100">Updates every 3s</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Identity / Device ID</th>
                            <th class="px-6 py-4">Current Page</th>
                            <th class="px-6 py-4 text-center">Total Visits</th>
                            <th class="px-6 py-4">Last Seen</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="log-table" class="divide-y divide-gray-100 bg-white">
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 font-medium">Loading live data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_URL = window.location.pathname;

        async function fetchLiveAnalytics() {
            try {
                const response = await fetch(CURRENT_URL + '?ajax=1');
                const data = await response.json();
                
                if (data.error) return;

                document.getElementById('metric-live').innerText = data.live;
                document.getElementById('metric-total').innerText = data.total;

                let tableHtml = '';
                data.logs.forEach(log => {
                    tableHtml += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-bold">${log.status}</td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-sm leading-tight">${log.id}</td>
                            <td class="px-6 py-4 text-blue-600 font-medium">${log.page}</td>
                            <td class="px-6 py-4 text-gray-700 font-bold text-center">${log.visits}</td>
                            <td class="px-6 py-4 text-gray-500">${log.time_ago}</td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="deleteRecord('${log.raw_id}')" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                if (data.logs.length === 0) {
                    tableHtml = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 font-medium">Waiting for students...</td></tr>';
                }
                document.getElementById('log-table').innerHTML = tableHtml;
            } catch (error) { console.error("Live sync error", error); }
        }

        async function deleteRecord(deviceId) {
            if (!confirm('Remove this student from the logs?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_single');
            fd.append('device_id', deviceId);
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            fetchLiveAnalytics(); 
        }

        async function clearAllData() {
            if (!confirm('?? WARNING: Erase ALL tracking data?')) return;
            const fd = new FormData();
            fd.append('action', 'clear_all');
            await fetch(CURRENT_URL, { method: 'POST', body: fd });
            fetchLiveAnalytics(); 
        }

        fetchLiveAnalytics();
        setInterval(fetchLiveAnalytics, 3000);
    </script>
</body>
</html>