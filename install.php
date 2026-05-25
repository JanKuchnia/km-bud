<?php
/**
 * KM-BUD Installer
 * Creates database tables and imports existing gallery data from image_metadata.json
 * DELETE THIS FILE AFTER SUCCESSFUL INSTALLATION!
 */

// Security: only allow from CLI, localhost or with token
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedHosts) && ($_GET['token'] ?? '') !== 'kmbud-install-2026') {
    http_response_code(403);
    die('Access denied. Use ?token=kmbud-install-2026 or access from localhost.');
}

$config = require __DIR__ . '/config/config.php';
$messages = [];
$errors = [];
$step = $_POST['step'] ?? ($_GET['step'] ?? 'form');

// Handle password setup
if ($step === 'install') {
    $adminPassword = $_POST['admin_password'] ?? '';
    if (strlen($adminPassword) < 6) {
        $errors[] = 'Hasło admina musi mieć minimum 6 znaków.';
        $step = 'form';
    }
}

if ($step === 'install') {
    try {
        // Connect without database first to create it if needed
        $c = $config['db'];
        $pdo = new PDO("mysql:host={$c['host']};charset={$c['charset']}", $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$c['name']}`");
        $messages[] = "✅ Baza danych '{$c['name']}' gotowa.";

        // Create categories table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                icon VARCHAR(50) DEFAULT 'image',
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = '✅ Tabela "categories" utworzona.';

        // Create photos table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS photos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                aspect_class VARCHAR(30) DEFAULT 'aspect-[4/3]',
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = '✅ Tabela "photos" utworzona.';

        // Insert categories
        $categories = [
            ['betonowe',  'Ogrodzenia z bloczków',  'rectangle-horizontal', 1],
            ['panelowe',  'Ogrodzenia panelowe',     'square',               2],
            ['siatka',    'Ogrodzenia z siatki',     'grid-3x3',             3],
            ['bramy',     'Bramy i furtki',          'door-open',            4],
            ['sprzety',   'Zaplecze maszynowe',      'truck',                5],
        ];

        $catStmt = $pdo->prepare("INSERT IGNORE INTO categories (slug, name, icon, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($categories as $cat) {
            $catStmt->execute($cat);
        }
        $messages[] = '✅ Kategorie zaimportowane (' . count($categories) . ').';

        // Build category slug → id map
        $catMap = [];
        foreach ($pdo->query("SELECT id, slug FROM categories")->fetchAll() as $row) {
            $catMap[$row['slug']] = $row['id'];
        }

        // Import photos from image_metadata.json
        $metadataFile = __DIR__ . '/image_metadata.json';
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
            if ($metadata) {
                // Check if photos already imported
                $existingCount = (int) $pdo->query("SELECT COUNT(*) FROM photos")->fetchColumn();
                if ($existingCount > 0) {
                    $messages[] = "⚠️ Tabela photos zawiera już {$existingCount} rekordów. Pomijam import.";
                } else {
                    $photoStmt = $pdo->prepare("
                        INSERT INTO photos (category_id, filename, title, description, aspect_class, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $imported = 0;
                    foreach ($metadata as $index => $photo) {
                        $catSlug = $photo['category'] ?? '';
                        $catId = $catMap[$catSlug] ?? null;
                        if (!$catId) continue;

                        // Extract just filename without 'images/' prefix
                        $filename = $photo['filename'] ?? '';
                        $filename = str_replace('images/', '', $filename);

                        $photoStmt->execute([
                            $catId,
                            $filename,
                            $photo['title'] ?? 'Bez tytułu',
                            $photo['desc'] ?? '',
                            $photo['aspect_class'] ?? 'aspect-[4/3]',
                            $index + 1,
                        ]);
                        $imported++;
                    }
                    $messages[] = "✅ Zaimportowano {$imported} zdjęć z image_metadata.json.";
                }
            } else {
                $errors[] = '❌ Nie udało się odczytać image_metadata.json (błąd JSON).';
            }
        } else {
            $errors[] = '⚠️ Plik image_metadata.json nie znaleziony. Pomijam import zdjęć.';
        }

        // Update config with admin password hash
        $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $configContent = file_get_contents(__DIR__ . '/config/config.php');
        $configContent = preg_replace_callback(
            "/('password_hash'\s*=>\s*')([^']*)(')/",
            function ($matches) use ($hash) {
                return $matches[1] . $hash . $matches[3];
            },
            $configContent
        );
        file_put_contents(__DIR__ . '/config/config.php', $configContent);
        $messages[] = '✅ Hasło admina zapisane w konfiguracji.';

        $messages[] = '';
        $messages[] = '🎉 Instalacja zakończona pomyślnie!';
        $messages[] = '⚠️ USUŃ PLIK install.php z serwera po instalacji!';
        $messages[] = '';
        $messages[] = '🔗 Panel admina: <a href="admin/" style="color:#3b82f6;text-decoration:underline">admin/</a>';

    } catch (PDOException $e) {
        $errors[] = '❌ Błąd bazy danych: ' . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
        $errors[] = '❌ Błąd: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KM-BUD Installer</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; width: 100%; padding: 2rem; }
        .card { background: #1e293b; border-radius: 1rem; padding: 2rem; border: 1px solid #334155; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .subtitle { color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; color: #cbd5e1; }
        input { width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid #475569; border-radius: 0.5rem; color: #e2e8f0; font-size: 1rem; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        button { width: 100%; padding: 0.75rem; background: #dc2626; color: white; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 700; cursor: pointer; }
        button:hover { background: #b91c1c; }
        .msg { padding: 0.5rem 0; font-size: 0.875rem; line-height: 1.6; }
        .msg.error { color: #f87171; }
        .msg.success { color: #34d399; }
        .info { background: #1e3a5f; border: 1px solid #2563eb; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; font-size: 0.8rem; color: #93c5fd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 KM-BUD Installer</h1>
            <p class="subtitle">Instalacja bazy danych i konfiguracja panelu admina</p>

            <?php if ($step === 'form'): ?>
                <div class="info">
                    <strong>MySQL (XAMPP):</strong> host=localhost, user=root, pass=(puste), db=kmbud
                </div>

                <?php foreach ($errors as $err): ?>
                    <p class="msg error"><?= $err ?></p>
                <?php endforeach; ?>

                <form method="post">
                    <input type="hidden" name="step" value="install">
                    <label for="admin_password">Hasło do panelu admina</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="Minimum 6 znaków" required minlength="6">
                    <button type="submit">🚀 Zainstaluj</button>
                </form>

            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <p class="msg success"><?= $msg ?></p>
                <?php endforeach; ?>
                <?php foreach ($errors as $err): ?>
                    <p class="msg error"><?= $err ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
