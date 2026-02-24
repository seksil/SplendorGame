<?php
// install.php
session_start();

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = trim($_POST['db_pass']);

    try {
        // Step 1: Connect to MySQL server (without specifying database yet)
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Step 2: Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // Step 3: Create Tables
        $pdo->exec("CREATE TABLE IF NOT EXISTS SpenderGame_games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_code VARCHAR(10) UNIQUE,
            status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
            turn_player_id INT DEFAULT NULL,
            tokens_available JSON,
            board_cards JSON,
            board_nobles JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS SpenderGame_players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            session_id VARCHAR(100),
            is_host BOOLEAN DEFAULT FALSE,
            score INT DEFAULT 0,
            tokens_owned JSON,
            cards_owned JSON,
            cards_reserved JSON,
            nobles_owned JSON,
            turn_order INT,
            FOREIGN KEY (game_id) REFERENCES SpenderGame_games(id) ON DELETE CASCADE
        )");

        // Step 4: Write config.php
        $config_content = "<?php\n// config.php\nsession_start();\n\n\$host = '$db_host';\n\$db_user = '$db_user';\n\$db_pass = '$db_pass';\n\$db_name = '$db_name';\n\ntry {\n    \$pdo = new PDO(\"mysql:host=\$host;charset=utf8mb4\", \$db_user, \$db_pass);\n    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n\n    // Select DB if it exists\n    \$stmt = \$pdo->query(\"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '\$db_name'\");\n    if (\$stmt->fetch()) {\n        \$pdo->query(\"USE \$db_name\");\n    }\n} catch (PDOException \$e) {\n    die(\"Database connection failed: \" . \$e->getMessage());\n}\n\n// Function to send JSON response\nfunction jsonResponse(\$success, \$data = [], \$message = '')\n{\n    header('Content-Type: application/json');\n    echo json_encode([\n        'success' => \$success,\n        'data' => \$data,\n        'message' => \$message\n    ]);\n    exit;\n}\n?>";

        file_put_contents('config.php', $config_content);

        $step = 3;
        $success = "ติดตั้งฐานข้อมูลและบันทึกการตั้งค่าสำเร็จแล้ว!";

    } catch (PDOException $e) {
        $error = "การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splendor Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Prompt:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .install-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .title {
            font-family: 'Cinzel', serif;
            color: #f59e0b;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-control {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: #f59e0b;
            box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
            color: white;
        }

        .btn-primary {
            background: #f59e0b;
            border: none;
            color: #0f172a;
            font-weight: 600;
            padding: 12px;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #d97706;
            color: white;
        }

        .text-muted {
            color: #94a3b8 !important;
        }
    </style>
</head>

<body>
    <div class="install-card">
        <h2 class="title">SPLENDOR INSTALLATION</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1 || ($step === 2 && $error)): ?>
            <p class="text-center text-muted mb-4">กำหนดค่าฐานข้อมูลเพื่อติดตั้งระบบเกมลงบนโฮสต์ของคุณ</p>
            <form action="install.php?step=2" method="POST">
                <div class="mb-3">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                    <small class="text-muted">ปกติจะเป็น localhost ยกเว้นโฮสต์จะกำหนดเป็นอย่างอื่น</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-control" placeholder="splendor_db" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Username</label>
                    <input type="text" name="db_user" class="form-control" placeholder="root" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_pass" class="form-control" placeholder="รหัสผ่านฐานข้อมูล (ถ้ามี)">
                </div>
                <button type="submit" class="btn btn-primary mt-3">ติดตั้งระบบ</button>
            </form>
        <?php elseif ($step === 3): ?>
            <div class="text-center">
                <div class="alert alert-success">
                    <h4>
                        <?php echo htmlspecialchars($success); ?>
                    </h4>
                    <p>ตารางเกมและการตั้งค่าถูกสร้างเรียบร้อยแล้ว</p>
                </div>
                <div class="alert alert-warning">
                    ⚠️ <strong>คำแนะนำเพื่อความปลอดภัย:</strong> กรุณาลบไฟล์ <code>install.php</code> และ
                    <code>setup_db.php</code> ทิ้งก่อนนำไปเปิดใช้งานจริง
                    เพื่อป้องกันไม่ให้คนอื่นมารันติดตั้งทับฐานข้อมูลของคุณ
                </div>
                <a href="index.php" class="btn btn-primary mt-3">เข้าสู่เกม (หน้าแรก)</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>