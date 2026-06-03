<?php
// live_view.php - WITH TIMEZONE FIX (India Time)

// --- CONFIGURATION ---
$servername = "localhost";
$username = "mastissx_log_user"; 
$password = "crbY}~zsI9Fy"; 
$dbname = "mastissx_mastery_logs";

// --- TIMEZONE SETTING (Set this to your location) ---
// This tells PHP to treat the output as India Time
date_default_timezone_set('Asia/Kolkata'); 

// 1. CONNECT
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 2. FILTER LOGIC
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$dateCondition = "";
// Note: We use CONVERT_TZ or just PHP logic, but for simple logs, 
// we will fetch data and filter in PHP to be accurate with timezones, 
// OR we rely on server time for the SQL filter for simplicity.
// For now, let's keep the SQL simple:
if ($filter == 'today') { $dateCondition = "WHERE DATE(visit_time) = CURDATE()"; }
elseif ($filter == 'all') { $dateCondition = ""; }

// 3. GET LEADS
$leads_map = [];
try {
    $leads_result = $conn->query("SELECT * FROM leads");
    if ($leads_result) {
        while($l = $leads_result->fetch_assoc()) {
            $leads_map[$l['device_id']] = $l;
        }
    }
} catch (Exception $e) {}

// 4. GET VISITORS
$sql = "SELECT * FROM visitors $dateCondition ORDER BY visit_time DESC";
$result = $conn->query($sql);

$grouped_visitors = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $idKey = isset($row['device_id']) && $row['device_id'] != '' ? $row['device_id'] : $row['ip_address'];
        if (!isset($grouped_visitors[$idKey])) { $grouped_visitors[$idKey] = []; }
        $grouped_visitors[$idKey][] = $row;
    }
}

// --- HELPER FUNCTION TO CONVERT TIME ---
function formatToIndiaTime($serverTime) {
    // 1. Create a date object from the server time (Assuming Server is UTC)
    // Note: If Namecheap is EST, we might need to adjust, but usually UTC is the base.
    $dt = new DateTime($serverTime, new DateTimeZone('UTC')); 
    
    // 2. Convert to India Time
    $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
    
    // 3. Return nice format (e.g. "10:30 PM")
    return $dt->format('d-M h:i A');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>MasteryExams Command Center</title>
    <meta http-equiv="refresh" content="3"> 
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        h2 { margin: 0; font-size: 22px; color: #2c3e50; }
        .visitor-table { width: 100%; border-collapse: collapse; }
        .visitor-table th { background: #34495e; color: white; text-align: left; padding: 15px; font-weight: 500; }
        .main-row { cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.1s; }
        .main-row:hover { background-color: #f8f9fa; }
        .main-row td { padding: 15px; vertical-align: top; }
        .badge { background: #3498db; color: white; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.85em; }
        .device-id { color: #888; font-size: 0.85em; font-family: monospace; }
        .lead-info { margin-top: 5px; padding: 5px 10px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 5px; display: inline-block; }
        .wa-link { color: #2e7d32; text-decoration: none; font-weight: bold; }
        
        /* Details */
        .details-row { display: none; background-color: #fafafa; }
        .sub-table { width: 95%; margin: 10px auto; border: 1px solid #ddd; background: white; font-size: 0.9em; border-collapse: collapse; }
        .sub-table th { background: #eee; padding: 8px; text-align: left; }
        .sub-table td { padding: 8px; border-bottom: 1px solid #eee; color: #666; }
    </style>
    <script>
        function toggleRow(id) {
            var row = document.getElementById('details-' + id);
            row.style.display = (row.style.display === "table-row") ? "none" : "table-row";
        }
    </script>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>👥 Live Monitor (India Time)</h2>
        <form method="GET" id="filterForm">
            <select name="filter" onchange="document.getElementById('filterForm').submit()">
                <option value="today" <?php if($filter=='today') echo 'selected'; ?>>Today</option>
                <option value="all" <?php if($filter=='all') echo 'selected'; ?>>All Time</option>
            </select>
        </form>
    </div>

    <table class="visitor-table">
        <thead>
            <tr>
                <th width="40%">User / Lead Info</th>
                <th width="15%">Visits</th>
                <th width="20%">Last IP</th>
                <th width="25%">Last Seen (IST)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (empty($grouped_visitors)) {
            echo "<tr><td colspan='4' style='padding:30px; text-align:center; color:#999;'>No visitors found yet...</td></tr>";
        } else {
            foreach ($grouped_visitors as $id => $visits) {
                $idSafe = preg_replace('/[^a-zA-Z0-9]/', '', $id);
                $visitCount = count($visits);
                $lastVisit = $visits[0]; 

                // CONVERT TIME HERE
                $indiaTime = formatToIndiaTime($lastVisit['visit_time']);

                // LEAD HTML
                $userInfoHtml = "<div class='device-id'>ID: $id</div>";
                if (isset($leads_map[$id])) {
                    $lead = $leads_map[$id];
                    $wa_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $lead['phone']);
                    $userInfoHtml .= "<div class='lead-info'><a href='$wa_url' target='_blank' class='wa-link'>📞 " . htmlspecialchars($lead['name']) . "</a></div>";
                }

                echo "<tr class='main-row' onclick=\"toggleRow('$idSafe')\">
                        <td>$userInfoHtml</td>
                        <td><span class='badge'>$visitCount</span></td>
                        <td>{$lastVisit['ip_address']}</td>
                        <td><strong>$indiaTime</strong></td>
                      </tr>";

                // DETAILS
                echo "<tr class='details-row' id='details-$idSafe'>
                        <td colspan='4'>
                            <table class='sub-table'>
                                <thead><tr><th>Time (IST)</th><th>Page</th><th>IP</th></tr></thead>
                                <tbody>";
                foreach($visits as $v) {
                    $subTime = formatToIndiaTime($v['visit_time']);
                    echo "<tr><td>$subTime</td><td>{$v['page_url']}</td><td>{$v['ip_address']}</td></tr>";
                }
                echo "</tbody></table></td></tr>";
            }
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>