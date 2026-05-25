<?php
/**
 * KM-BUD Migration & Seeding Helper
 * Sets up services, service_slides, and equipment tables and seeds them.
 */

// Security check: only allow CLI or localhost access
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedHosts)) {
    http_response_code(403);
    die('Access denied. Access from localhost or CLI only.');
}

require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();
    echo "Connected to database successfully.\n";

    $sqlPath = __DIR__ . '/admin/includes/create_services_and_equipment_tables.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("Migration SQL file not found at: " . $sqlPath);
    }

    $sql = file_get_contents($sqlPath);
    
    // Execute SQL queries
    $db->exec($sql);
    echo "✅ Database tables created and seeded successfully with Services and Equipment data!\n";
    
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
