<?php
// admin/visitors.php — Visitor Dashboard
require '../config.php';

// Ensure visitors table exists
try {
    $pdo->query("SELECT 1 FROM SpenderGame_visitors LIMIT 1");
} catch (Exception $e) {
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS SpenderGame_visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(512) DEFAULT '',
        page_visited VARCHAR(100) NOT NULL,
        referrer VARCHAR(512) DEFAULT '',
        session_id VARCHAR(100) DEFAULT '',
        player_name VARCHAR(50) DEFAULT '',
        visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visited_at (visited_at),
        INDEX idx_ip_page (ip_address, page_visited)
    )");
}

// --- Stats Queries ---
// Today
$stmt = $pdo->query("SELECT COUNT(*) FROM SpenderGame_visitors WHERE DATE(visited_at) = CURDATE()");
$today_count = $stmt->fetchColumn();

// Unique IPs today
$stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM SpenderGame_visitors WHERE DATE(visited_at) = CURDATE()");
$today_unique = $stmt->fetchColumn();

// This week
$stmt = $pdo->query("SELECT COUNT(*) FROM SpenderGame_visitors WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$week_count = $stmt->fetchColumn();

// This month
$stmt = $pdo->query("SELECT COUNT(*) FROM SpenderGame_visitors WHERE MONTH(visited_at) = MONTH(CURDATE()) AND YEAR(visited_at) = YEAR(CURDATE())");
$month_count = $stmt->fetchColumn();

// All time
$stmt = $pdo->query("SELECT COUNT(*) FROM SpenderGame_visitors");
$total_count = $stmt->fetchColumn();

// All time unique
$stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM SpenderGame_visitors");
$total_unique = $stmt->fetchColumn();

// Last 7 days chart
$chart_data = [];
$stmt = $pdo->query("
    SELECT DATE(visited_at) as visit_date, COUNT(*) as cnt, COUNT(DISTINCT ip_address) as uniq
    FROM SpenderGame_visitors 
    WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(visited_at) 
    ORDER BY visit_date ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fill in missing days
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($rows as $r) {
        if ($r['visit_date'] === $d) {
            $chart_data[] = ['date' => $d, 'count' => (int) $r['cnt'], 'unique' => (int) $r['uniq']];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chart_data[] = ['date' => $d, 'count' => 0, 'unique' => 0];
    }
}
$max_chart = max(array_column($chart_data, 'count') ?: [1]);
if ($max_chart === 0)
    $max_chart = 1;

// Top pages
$stmt = $pdo->query("
    SELECT page_visited, COUNT(*) as cnt 
    FROM SpenderGame_visitors 
    GROUP BY page_visited 
    ORDER BY cnt DESC 
    LIMIT 10
");
$top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent visits (last 50)
$stmt = $pdo->query("
    SELECT * FROM SpenderGame_visitors 
    ORDER BY visited_at DESC 
    LIMIT 50
");
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page name mapping
$page_labels = [
    'index' => '🏠 หน้าแรก',
    'lobby' => '🚪 ห้องรอ',
    'game' => '🎮 เล่นเกม',
];

function formatUA($ua)
{
    if (empty($ua))
        return '<span class="text-secondary">—</span>';
    // Simple device detection
    if (stripos($ua, 'iPhone') !== false)
        return '📱 iPhone';
    if (stripos($ua, 'iPad') !== false)
        return '📱 iPad';
    if (stripos($ua, 'Android') !== false)
        return '📱 Android';
    if (stripos($ua, 'Mac') !== false)
        return '💻 Mac';
    if (stripos($ua, 'Windows') !== false)
        return '💻 Windows';
    if (stripos($ua, 'Linux') !== false)
        return '🐧 Linux';
    if (stripos($ua, 'bot') !== false || stripos($ua, 'crawl') !== false)
        return '🤖 Bot';
    return '🌐 Other';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splendor — สถิติผู้เข้าชม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(0, 0, 0, 0.2));
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            border-color: rgba(245, 158, 11, 0.3);
            transform: translateY(-2px);
        }

        .stat-number {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-gold);
            text-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .stat-sub {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 2px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--radius-md);
            padding: 20px;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 160px;
            padding-top: 10px;
        }

        .chart-bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            height: 100%;
            justify-content: flex-end;
        }

        .chart-bar {
            width: 100%;
            max-width: 50px;
            border-radius: 6px 6px 2px 2px;
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.8), rgba(245, 158, 11, 0.3));
            min-height: 4px;
            transition: all 0.5s ease;
            position: relative;
        }

        .chart-bar:hover {
            filter: brightness(1.3);
        }

        .chart-bar .tooltip-val {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: var(--text-gold);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
        }

        .chart-bar:hover .tooltip-val {
            opacity: 1;
        }

        .chart-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .chart-count {
            font-size: 0.75rem;
            color: var(--text-gold);
            font-weight: 600;
        }

        .section-title {
            font-family: 'Cinzel', serif;
            color: var(--text-gold);
            font-size: 1.1rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-table {
            width: 100%;
            font-size: 0.8rem;
        }

        .data-table th {
            color: var(--text-gold);
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.2);
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .page-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .page-badge.index {
            background: rgba(59, 130, 246, 0.15);
            color: #93C5FD;
        }

        .page-badge.lobby {
            background: rgba(34, 197, 94, 0.15);
            color: #86EFAC;
        }

        .page-badge.game {
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
        }

        .top-page-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .top-page-bar .bar-fill {
            flex: 1;
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .top-page-bar .bar-fill-inner {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.6), rgba(245, 158, 11, 0.9));
            transition: width 0.6s ease;
        }

        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--text-gold);
        }

        .table-scroll {
            max-height: 500px;
            overflow-y: auto;
        }

        .table-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .table-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="title-font" style="font-size: 1.6rem; margin-bottom: 4px;">
                    <i class="bi bi-bar-chart-fill me-2" style="color: var(--text-gold);"></i>สถิติผู้เข้าชม
                </h1>
                <p class="text-muted-custom small mb-0">Splendor Game — Visitor Analytics</p>
            </div>
            <a href="../index.php" class="back-link">
                <i class="bi bi-arrow-left me-1"></i> กลับหน้าแรก
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo number_format($today_count); ?>
                    </div>
                    <div class="stat-label">วันนี้</div>
                    <div class="stat-sub">
                        <?php echo number_format($today_unique); ?> IP ไม่ซ้ำ
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo number_format($week_count); ?>
                    </div>
                    <div class="stat-label">7 วันล่าสุด</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo number_format($month_count); ?>
                    </div>
                    <div class="stat-label">เดือนนี้</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo number_format($total_count); ?>
                    </div>
                    <div class="stat-label">ทั้งหมด</div>
                    <div class="stat-sub">
                        <?php echo number_format($total_unique); ?> IP ไม่ซ้ำ
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Chart: 7 Days -->
            <div class="col-md-8">
                <div class="chart-container h-100">
                    <div class="section-title">
                        <i class="bi bi-graph-up"></i> ผู้เข้าชม 7 วันล่าสุด
                    </div>
                    <div class="chart-bars">
                        <?php foreach ($chart_data as $cd): ?>
                            <?php
                            $pct = ($cd['count'] / $max_chart) * 100;
                            $day_th = ['Sun' => 'อา', 'Mon' => 'จ', 'Tue' => 'อ', 'Wed' => 'พ', 'Thu' => 'พฤ', 'Fri' => 'ศ', 'Sat' => 'ส'];
                            $day_name = $day_th[date('D', strtotime($cd['date']))] ?? '';
                            $is_today = $cd['date'] === date('Y-m-d');
                            ?>
                            <div class="chart-bar-group">
                                <div class="chart-count">
                                    <?php echo $cd['count']; ?>
                                </div>
                                <div class="chart-bar"
                                    style="height: <?php echo max($pct, 3); ?>%;<?php echo $is_today ? 'background: linear-gradient(180deg, rgba(34,197,94,0.8), rgba(34,197,94,0.3));' : ''; ?>">
                                    <span class="tooltip-val">
                                        <?php echo $cd['count']; ?> visits /
                                        <?php echo $cd['unique']; ?> unique
                                    </span>
                                </div>
                                <div class="chart-label"
                                    style="<?php echo $is_today ? 'color: #86EFAC; font-weight:600;' : ''; ?>">
                                    <?php echo $day_name; ?><br>
                                    <span style="font-size: 0.6rem;">
                                        <?php echo date('d/m', strtotime($cd['date'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Pages -->
            <div class="col-md-4">
                <div class="chart-container h-100">
                    <div class="section-title">
                        <i class="bi bi-trophy"></i> หน้ายอดนิยม
                    </div>
                    <?php
                    $max_page = !empty($top_pages) ? $top_pages[0]['cnt'] : 1;
                    foreach ($top_pages as $tp):
                        $label = $page_labels[$tp['page_visited']] ?? '📄 ' . htmlspecialchars($tp['page_visited']);
                        $bar_pct = ($tp['cnt'] / $max_page) * 100;
                        ?>
                        <div class="top-page-bar">
                            <span style="min-width: 90px; font-size: 0.8rem; color: var(--text-primary);">
                                <?php echo $label; ?>
                            </span>
                            <div class="bar-fill">
                                <div class="bar-fill-inner" style="width: <?php echo $bar_pct; ?>%"></div>
                            </div>
                            <span
                                style="min-width: 35px; text-align: right; font-size: 0.8rem; color: var(--text-gold); font-weight: 600;">
                                <?php echo number_format($tp['cnt']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($top_pages)): ?>
                        <p class="text-muted-custom text-center small mt-4">ยังไม่มีข้อมูล</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Visits Table -->
        <div class="chart-container mb-4">
            <div class="section-title">
                <i class="bi bi-clock-history"></i> การเข้าชมล่าสุด
                <span class="badge"
                    style="background: rgba(245,158,11,0.15); color: var(--text-gold); font-size: 0.7rem; font-weight: 500;">
                    50 รายการล่าสุด
                </span>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>หน้า</th>
                            <th>IP</th>
                            <th>ผู้เล่น</th>
                            <th>อุปกรณ์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 1.5rem; color: var(--text-secondary);"></i>
                                    <p class="text-muted-custom small mt-2 mb-0">ยังไม่มีข้อมูลผู้เข้าชม</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent as $v): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <i class="bi bi-clock" style="color: rgba(255,255,255,0.2); margin-right: 4px;"></i>
                                        <?php echo date('d/m H:i', strtotime($v['visited_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="page-badge <?php echo htmlspecialchars($v['page_visited']); ?>">
                                            <?php echo $page_labels[$v['page_visited']] ?? htmlspecialchars($v['page_visited']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.75rem; color: var(--text-secondary);">
                                                    <?php echo htmlspecialchars($v['ip_address']); ?>
                                                </code>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($v['player_name'])) {
                                            echo '<span style="color: var(--text-primary);">' . htmlspecialchars($v['player_name']) . '</span>';
                                        } else {
                                            echo '<span style="color: rgba(255,255,255,0.2);">—</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo formatUA($v['user_agent']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mb-3" style="color: rgba(255,255,255,0.15); font-size: 0.7rem;">
            Splendor Game v
            <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?> — Visitor Analytics
        </div>
    </div>
</body>

</html>