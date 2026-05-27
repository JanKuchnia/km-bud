<?php
/**
 * KM-BUD Gallery REST API Backend
 * CRUD operations, file upload, TinyPNG compression and GD library fallback
 */

header('Content-Type: application/json');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication for ALL API endpoints
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nieautoryzowany dostęp. Zaloguj się.']);
    exit;
}

$db = getDB();
$config = require __DIR__ . '/../config/config.php';
$action = $_GET['action'] ?? '';

// Helper to send JSON responses
function sendResponse(bool $success, string $message = '', array $data = [], int $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Global CSRF Check for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON body or POST form data
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken)) {
        // Attempt to read from JSON body
        $json = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $json['csrf_token'] ?? '';
    }
    
    if (!validateCSRFToken($csrfToken)) {
        sendResponse(false, 'Błąd weryfikacji tokenu CSRF. Odśwież stronę i spróbuj ponownie.', [], 403);
    }
}

// ─────────────────────────────────────────────────────────────
// ENDPOINTS ROUTING
// ─────────────────────────────────────────────────────────────

switch ($action) {
    case 'upload':
        handleUpload($db, $config);
        break;

    case 'update_photo':
        handleUpdatePhoto($db);
        break;

    case 'delete_photo':
        handleDeletePhoto($db);
        break;

    case 'reorder_photos':
        handleReorderPhotos($db);
        break;

    case 'add_category':
        handleAddCategory($db);
        break;

    case 'edit_category':
        handleEditCategory($db);
        break;

    case 'delete_category':
        handleDeleteCategory($db);
        break;

    case 'fetch_google_reviews':
        handleFetchGoogleReviews($db, $config);
        break;

    case 'add_review':
        handleAddReview($db);
        break;

    case 'edit_review':
        handleEditReview($db);
        break;

    case 'toggle_review_visibility':
        handleToggleReviewVisibility($db);
        break;

    case 'delete_review':
        handleDeleteReview($db);
        break;

    // Services CRUD
    case 'add_service':
        handleAddService($db);
        break;

    case 'edit_service':
        handleEditService($db);
        break;

    case 'delete_service':
        handleDeleteService($db);
        break;

    case 'reorder_services':
        handleReorderServices($db);
        break;

    // Service Slides CRUD
    case 'add_service_slide':
        handleAddServiceSlide($db, $config);
        break;

    case 'delete_service_slide':
        handleDeleteServiceSlide($db);
        break;

    case 'reorder_service_slides':
        handleReorderServiceSlides($db);
        break;

    // Equipment CRUD
    case 'add_equipment':
        handleAddEquipment($db, $config);
        break;

    case 'edit_equipment':
        handleEditEquipment($db, $config);
        break;

    case 'delete_equipment':
        handleDeleteEquipment($db);
        break;

    case 'reorder_equipment':
        handleReorderEquipment($db);
        break;

    default:
        sendResponse(false, 'Nieprawidłowa akcja.', [], 400);
}

// ─────────────────────────────────────────────────────────────
// ENDPOINT FUNCTIONS
// ─────────────────────────────────────────────────────────────

/**
 * Handle Photo Upload with TinyPNG + GD WebP logic
 */
function handleUpload(PDO $db, array $config) {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, 'Nie wybrano pliku lub wystąpił błąd podczas przesyłania.');
    }

    $file = $_FILES['photo'];
    $title = trim($_POST['title'] ?? 'Bez tytułu');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);

    // Validate category
    $catCheck = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetch()) {
        sendResponse(false, 'Wybrana kategoria nie istnieje.');
    }

    // Validate size
    if ($file['size'] > $config['upload']['max_size']) {
        sendResponse(false, 'Plik jest zbyt duży. Maksymalny rozmiar to 10MB.');
    }

    // Validate mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $config['upload']['allowed_types'])) {
        sendResponse(false, 'Niedozwolony format pliku. Dopuszczalne są: JPEG, PNG, WebP.');
    }

    // Setup filenames
    $uploadDir = __DIR__ . '/../images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate safe slug for filename
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', translatePolishChars($title))));
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'realizacja';
    }

    // Find unique filename in directory
    $filename = $slug . '.webp';
    $counter = 1;
    while (file_exists($uploadDir . $filename)) {
        $filename = $slug . '-' . $counter . '.webp';
        $counter++;
    }

    $targetPath = $uploadDir . $filename;
    $tempPath = $file['tmp_name'];

    // Compress & optimize image
    $optimized = false;
    $apiKey = $config['tinypng']['api_key'] ?? '';

    if (!empty($apiKey)) {
        $optimized = compressWithTinyPNG($tempPath, $targetPath, $apiKey);
    }

    if (!$optimized) {
        // Fallback to local GD library compression and WebP conversion
        $optimized = compressWithGD($tempPath, $targetPath, $config['upload']['quality'], $config['upload']['max_dimension']);
    }

    if (!$optimized) {
        sendResponse(false, 'Błąd przetwarzania obrazu na serwerze.');
    }

    // Calculate dimensions of final image to detect aspect ratio class
    $dimensions = getimagesize($targetPath);
    $aspectClass = 'aspect-[4/3]'; // default
    if ($dimensions) {
        $width = $dimensions[0];
        $height = $dimensions[1];
        $ratio = $width / $height;

        if (abs($ratio - 1.0) < 0.15) {
            $aspectClass = 'aspect-square';
        } elseif (abs($ratio - 0.75) < 0.15) {
            $aspectClass = 'aspect-[3/4]';
        } elseif (abs($ratio - 1.77) < 0.15) {
            $aspectClass = 'aspect-[16/9]';
        } elseif ($ratio < 1.0) {
            $aspectClass = 'aspect-[3/4]';
        } else {
            $aspectClass = 'aspect-[4/3]';
        }
    }

    // Set sort_order to end of category — computed inside the INSERT transaction to avoid race conditions

    // Insert into DB — sort_order is computed inside the transaction to prevent race conditions
    try {
        $db->beginTransaction();

        $sortOrderStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM photos WHERE category_id = ? FOR UPDATE");
        $sortOrderStmt->execute([$categoryId]);
        $nextSortOrder = (int) $sortOrderStmt->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO photos (category_id, filename, title, description, aspect_class, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $categoryId,
            $filename,
            $title,
            $description,
            $aspectClass,
            $nextSortOrder
        ]);
        $newId = $db->lastInsertId();
        $db->commit();
        
        sendResponse(true, 'Zdjęcie zostało pomyślnie dodane.', [
            'photo' => [
                'id' => $newId,
                'filename' => $filename,
                'title' => $title,
                'description' => $description,
                'aspect_class' => $aspectClass
            ]
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        // Cleanup file if DB insert fails
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        sendResponse(false, 'Błąd bazy danych podczas zapisywania rekordu: ' . $e->getMessage());
    }
}

/**
 * Handle Photo Update
 */
function handleUpdatePhoto(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $aspectClass = trim($_POST['aspect_class'] ?? 'aspect-[4/3]');

    if ($id <= 0 || empty($title) || $categoryId <= 0) {
        sendResponse(false, 'Niepełne dane edycji zdjęcia.');
    }

    // Validate category
    $catCheck = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetch()) {
        sendResponse(false, 'Wybrana kategoria nie istnieje.');
    }

    try {
        $stmt = $db->prepare("
            UPDATE photos 
            SET title = ?, description = ?, category_id = ?, aspect_class = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $categoryId, $aspectClass, $id]);
        
        sendResponse(true, 'Dane zdjęcia zostały pomyślnie zaktualizowane.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd zapisu w bazie danych: ' . $e->getMessage());
    }
}

/**
 * Handle Photo Deletion
 */
function handleDeletePhoto(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora zdjęcia.');
    }

    // Fetch filename first to delete it from disk
    $stmt = $db->prepare("SELECT filename FROM photos WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();

    if (!$photo) {
        sendResponse(false, 'Zdjęcie nie zostało znalezione w bazie.');
    }

    $filepath = __DIR__ . '/../images/' . $photo['filename'];

    try {
        $db->beginTransaction();

        $delete = $db->prepare("DELETE FROM photos WHERE id = ?");
        $delete->execute([$id]);

        // Commit first, then delete file
        $db->commit();

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        sendResponse(true, 'Zdjęcie zostało pomyślnie usunięte.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd bazy danych podczas usuwania rekordu: ' . $e->getMessage());
    }
}

/**
 * Handle Drag & Drop reorder
 */
function handleReorderPhotos(PDO $db) {
    // Read JSON body
    $json = json_decode(file_get_contents('php://input'), true);
    $order = $json['order'] ?? []; // Array of photo IDs in new order

    if (empty($order)) {
        sendResponse(false, 'Brak danych kolejności.');
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE photos SET sort_order = ? WHERE id = ?");
        
        foreach ($order as $index => $id) {
            $stmt->execute([$index + 1, (int) $id]);
        }

        $db->commit();
        sendResponse(true, 'Kolejność zdjęć została zapisana.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd zapisu kolejności w bazie danych: ' . $e->getMessage());
    }
}

/**
 * Add Category
 */
function handleAddCategory(PDO $db) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? 'image');

    if (empty($name) || empty($slug)) {
        sendResponse(false, 'Nazwa i unikalny identyfikator (slug) są wymagane.');
    }

    // Safe slug sanitization
    $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', translatePolishChars($slug)));
    $slug = trim($slug, '-');

    // Check slug uniqueness
    $slugCheck = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) {
        sendResponse(false, 'Kategoria z tym identyfikatorem (slug) już istnieje.');
    }

    $sortOrderStmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories");
    $nextSort = (int) $sortOrderStmt->fetchColumn();

    try {
        $stmt = $db->prepare("INSERT INTO categories (name, slug, icon, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $icon, $nextSort]);
        
        sendResponse(true, 'Kategoria została dodana.', [
            'category' => [
                'id' => $db->lastInsertId(),
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd zapisu kategorii: ' . $e->getMessage());
    }
}

/**
 * Edit Category
 */
function handleEditCategory(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? 'image');

    if ($id <= 0 || empty($name) || empty($slug)) {
        sendResponse(false, 'Brak wymaganych danych do edycji kategorii.');
    }

    $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', translatePolishChars($slug)));
    $slug = trim($slug, '-');

    // Check slug uniqueness excluding this category
    $slugCheck = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        sendResponse(false, 'Kategoria z tym identyfikatorem (slug) już istnieje.');
    }

    try {
        $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $icon, $id]);
        
        sendResponse(true, 'Kategoria została zaktualizowana.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd zapisu kategorii w bazie: ' . $e->getMessage());
    }
}

/**
 * Delete Category
 */
function handleDeleteCategory(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora kategorii.');
    }

    // Safety: don't delete if it is the only category
    $countStmt = $db->query("SELECT COUNT(*) FROM categories");
    if ((int) $countStmt->fetchColumn() <= 1) {
        sendResponse(false, 'Nie można usunąć jedynej kategorii. System wymaga co najmniej jednej kategorii w bazie.');
    }

    // Safety: don't delete if there are photos assigned to this category
    $photoCheck = $db->prepare("SELECT COUNT(*) FROM photos WHERE category_id = ?");
    $photoCheck->execute([$id]);
    if ((int) $photoCheck->fetchColumn() > 0) {
        sendResponse(false, 'Nie można usunąć kategorii zawierającej zdjęcia. Przenieś lub usuń wszystkie zdjęcia z tej kategorii przed jej usunięciem.');
    }

    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, 'Kategoria została pomyślnie usunięta.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// IMAGE PROCESSING LIBRARIES
// ─────────────────────────────────────────────────────────────

/**
 * Compress and scale image using local GD library, then export to WebP
 */
function compressWithGD(string $sourcePath, string $targetPath, int $quality = 85, int $maxDimension = 2048): bool {
    // Get image size and type
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $width = $info[0];
    $height = $info[1];
    $mime = $info['mime'];

    // Load original image based on format
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = @imagecreatefromwebp($sourcePath);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // Rescale image if dimensions exceed maximum
    if ($width > $maxDimension || $height > $maxDimension) {
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = (int) round(($height / $width) * $maxDimension);
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int) round(($width / $height) * $maxDimension);
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/WebP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resizedImage;
    }

    // Export format with WebP fallback check
    if (function_exists('imagewebp')) {
        $result = imagewebp($image, $targetPath, $quality);
    } elseif ($mime === 'image/png') {
        // Fall back to PNG if the original was a PNG to preserve transparency
        $result = imagepng($image, $targetPath, (int) round((100 - $quality) / 10));
    } else {
        // Fall back to JPEG for everything else
        $result = imagejpeg($image, $targetPath, $quality);
    }
    imagedestroy($image);

    return $result;
}

/**
 * Compress using TinyPNG API using native PHP curl client
 */
function compressWithTinyPNG(string $sourcePath, string $targetPath, string $apiKey): bool {
    $url = 'https://api.tinify.com/shrink';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $apiKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($sourcePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201 || !$response) {
        return false; // TinyPNG error or key invalid, proceed with GD fallback
    }

    $result = json_decode($response, true);
    $outputUrl = $result['output']['url'] ?? '';

    if (empty($outputUrl)) {
        return false;
    }

    // Download the optimized image from TinyPNG
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $outputUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $compressedData = curl_exec($ch);
    $httpCodeDownload = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCodeDownload !== 200 || !$compressedData) {
        return false;
    }

    // Save compressed file to temporary location
    $tempCompressed = tempnam(sys_get_temp_dir(), 'tiny_');
    file_put_contents($tempCompressed, $compressedData);

    // Convert local JPG/PNG to WebP locally to preserve WebP standard
    $success = compressWithGD($tempCompressed, $targetPath);
    unlink($tempCompressed);

    return $success;
}

// ─────────────────────────────────────────────────────────────
// CHAR MAPPING UTILS
// ─────────────────────────────────────────────────────────────

function translatePolishChars(string $str): string {
    $chars = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
        'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z'
    ];
    return strtr($str, $chars);
}

// ─────────────────────────────────────────────────────────────
// REVIEWS CRUD HANDLERS
// ─────────────────────────────────────────────────────────────

/**
 * Asynchronously fetch Google Reviews and save to database
 */
function handleFetchGoogleReviews(PDO $db, array $config) {
    require_once __DIR__ . '/../includes/google_reviews_helper.php';
    $res = syncGoogleReviews($db, $config);
    sendResponse($res['success'], $res['message'], ['imported' => $res['imported'] ?? 0]);
}

/**
 * Add Review manually
 */
function handleAddReview(PDO $db) {
    $author = trim($_POST['author_name'] ?? '');
    $text = trim($_POST['review_text'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 5);
    $time = trim($_POST['review_time'] ?? 'niedawno');

    if (empty($author) || empty($text)) {
        sendResponse(false, 'Nazwa autora i treść opinii są wymagane.');
    }

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    // Manual reviews added by admin are visible by default
    $isVisible = 1;

    try {
        $stmt = $db->prepare("
            INSERT INTO google_reviews (author_name, rating, review_text, review_time, is_manual, is_visible)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([$author, $rating, $text, $time, $isVisible]);
        sendResponse(true, 'Opinia została pomyślnie dodana.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

/**
 * Edit Review
 */
function handleEditReview(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    $author = trim($_POST['author_name'] ?? '');
    $text = trim($_POST['review_text'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 5);
    $time = trim($_POST['review_time'] ?? 'niedawno');

    if ($id <= 0 || empty($author) || empty($text)) {
        sendResponse(false, 'Niepełne dane do edycji opinii.');
    }

    try {
        $stmt = $db->prepare("
            UPDATE google_reviews 
            SET author_name = ?, rating = ?, review_text = ?, review_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$author, $rating, $text, $time, $id]);
        sendResponse(true, 'Opinia została zaktualizowana.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

/**
 * Toggle visibility of review on public site
 */
function handleToggleReviewVisibility(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    // Clamp to strict boolean 0|1 — reject arbitrary integers from POST
    $visible = ($_POST['is_visible'] ?? '1') === '0' ? 0 : 1;

    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora opinii.');
    }

    try {
        $stmt = $db->prepare("UPDATE google_reviews SET is_visible = ? WHERE id = ?");
        $stmt->execute([$visible, $id]);
        sendResponse(true, 'Pomyślnie zmieniono widoczność opinii.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd zapisu widoczności: ' . $e->getMessage());
    }
}

/**
 * Delete Review
 */
function handleDeleteReview(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora opinii.');
    }

    try {
        $stmt = $db->prepare("DELETE FROM google_reviews WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(true, 'Opinia została trwale usunięta.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd bazy danych podczas usuwania: ' . $e->getMessage());
    }
}

/**
 * Add Service
 */
function handleAddService(PDO $db) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'image');

    if (empty($name) || empty($slug) || empty($description)) {
        sendResponse(false, 'Nazwa, slug i opis są wymagane.');
    }

    $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', translatePolishChars($slug)));
    $slug = trim($slug, '-');

    $slugCheck = $db->prepare("SELECT id FROM services WHERE slug = ?");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) {
        sendResponse(false, 'Usługa z tym identyfikatorem (slug) już istnieje.');
    }

    try {
        $db->beginTransaction();

        // sort_order computed inside transaction to prevent race conditions
        $sortOrderStmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM services FOR UPDATE");
        $nextSort = (int) $sortOrderStmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO services (name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $icon, $nextSort]);
        $newId = $db->lastInsertId();
        $db->commit();
        
        sendResponse(true, 'Usługa została pomyślnie dodana.', [
            'service' => [
                'id' => $newId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
            ]
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd zapisu: ' . $e->getMessage());
    }
}

/**
 * Edit Service
 */
function handleEditService(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'image');

    if ($id <= 0 || empty($name) || empty($slug) || empty($description)) {
        sendResponse(false, 'Brak wymaganych danych do edycji usługi.');
    }

    $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', translatePolishChars($slug)));
    $slug = trim($slug, '-');

    $slugCheck = $db->prepare("SELECT id FROM services WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        sendResponse(false, 'Usługa z tym identyfikatorem (slug) już istnieje.');
    }

    try {
        $stmt = $db->prepare("UPDATE services SET name = ?, slug = ?, description = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $icon, $id]);
        sendResponse(true, 'Usługa została zaktualizowana.');
    } catch (PDOException $e) {
        sendResponse(false, 'Błąd zapisu: ' . $e->getMessage());
    }
}

/**
 * Delete Service
 */
function handleDeleteService(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora usługi.');
    }

    try {
        $db->beginTransaction();

        // Fetch slide images INSIDE the transaction so the read is consistent with the delete
        $stmt = $db->prepare("SELECT image FROM service_slides WHERE service_id = ? AND image IS NOT NULL");
        $stmt->execute([$id]);
        $slides = $stmt->fetchAll();

        $stmtDel = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmtDel->execute([$id]);

        $db->commit();

        // Only delete files from disk after the DB commit is confirmed
        foreach ($slides as $slide) {
            $filepath = __DIR__ . '/../' . $slide['image'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        sendResponse(true, 'Usługa została pomyślnie usunięta.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

/**
 * Reorder Services
 */
function handleReorderServices(PDO $db) {
    $json = json_decode(file_get_contents('php://input'), true);
    $order = $json['order'] ?? [];

    if (empty($order)) {
        sendResponse(false, 'Brak danych kolejności.');
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE services SET sort_order = ? WHERE id = ?");
        foreach ($order as $index => $id) {
            $stmt->execute([$index + 1, (int) $id]);
        }
        $db->commit();
        sendResponse(true, 'Kolejność usług została zapisana.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd zapisu: ' . $e->getMessage());
    }
}

/**
 * Add Service Slide
 */
function handleAddServiceSlide(PDO $db, array $config) {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $slideType = trim($_POST['slide_type'] ?? 'image'); // 'image' or 'gradient'
    $gradient = trim($_POST['gradient'] ?? '');
    $icon = trim($_POST['icon'] ?? '');

    if ($serviceId <= 0) {
        sendResponse(false, 'Nieprawidłowy identyfikator usługi.');
    }

    $imagePath = null;

    if ($slideType === 'image') {
        if (!isset($_FILES['slide_photo']) || $_FILES['slide_photo']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(false, 'Nie wybrano pliku zdjęcia lub wystąpił błąd.');
        }

        $file = $_FILES['slide_photo'];

        if ($file['size'] > $config['upload']['max_size']) {
            sendResponse(false, 'Plik jest zbyt duży. Maksymalny rozmiar to 10MB.');
        }

        // Validate mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $config['upload']['allowed_types'])) {
            sendResponse(false, 'Dopuszczalne formaty to: JPEG, PNG, WebP.');
        }

        $uploadDir = __DIR__ . '/../images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = 'service-slide-' . time() . '-' . uniqid() . '.webp';
        $targetPath = $uploadDir . $filename;
        $tempPath = $file['tmp_name'];

        // Compress
        $optimized = false;
        $apiKey = $config['tinypng']['api_key'] ?? '';
        if (!empty($apiKey)) {
            $optimized = compressWithTinyPNG($tempPath, $targetPath, $apiKey);
        }
        if (!$optimized) {
            $optimized = compressWithGD($tempPath, $targetPath, $config['upload']['quality'], $config['upload']['max_dimension']);
        }

        if (!$optimized) {
            sendResponse(false, 'Błąd przetwarzania obrazu.');
        }

        $imagePath = 'images/' . $filename;
    } else {
        if (empty($gradient) || empty($icon)) {
            sendResponse(false, 'Gradient i ikona są wymagane dla slajdu bezgraficznego.');
        }
    }

    try {
        $db->beginTransaction();

        // sort_order computed inside transaction to prevent race conditions
        $sortStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM service_slides WHERE service_id = ? FOR UPDATE");
        $sortStmt->execute([$serviceId]);
        $nextSort = (int) $sortStmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$serviceId, $imagePath, $gradient ?: null, $icon ?: null, $nextSort]);
        $db->commit();
        sendResponse(true, 'Slajd został dodany.');
    } catch (PDOException $e) {
        $db->rollBack();
        if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
            unlink(__DIR__ . '/../' . $imagePath);
        }
        sendResponse(false, 'Błąd zapisu slajdu: ' . $e->getMessage());
    }
}

/**
 * Delete Service Slide
 */
function handleDeleteServiceSlide(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora slajdu.');
    }

    $stmt = $db->prepare("SELECT image FROM service_slides WHERE id = ?");
    $stmt->execute([$id]);
    $slide = $stmt->fetch();

    if (!$slide) {
        sendResponse(false, 'Slajd nie istnieje.');
    }

    try {
        $db->beginTransaction();

        $stmtDel = $db->prepare("DELETE FROM service_slides WHERE id = ?");
        $stmtDel->execute([$id]);

        $db->commit();

        if ($slide['image']) {
            $filepath = __DIR__ . '/../' . $slide['image'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        sendResponse(true, 'Slajd został usunięty.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

/**
 * Reorder Service Slides
 */
function handleReorderServiceSlides(PDO $db) {
    $json = json_decode(file_get_contents('php://input'), true);
    $order = $json['order'] ?? [];

    if (empty($order)) {
        sendResponse(false, 'Brak danych kolejności.');
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE service_slides SET sort_order = ? WHERE id = ?");
        foreach ($order as $index => $id) {
            $stmt->execute([$index + 1, (int) $id]);
        }
        $db->commit();
        sendResponse(true, 'Kolejność slajdów została zapisana.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd zapisu kolejności: ' . $e->getMessage());
    }
}

/**
 * Add Equipment (Park Maszynowy)
 */
function handleAddEquipment(PDO $db, array $config) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $badge = trim($_POST['badge'] ?? '');
    $spec1 = trim($_POST['spec_1'] ?? '');
    $spec2 = trim($_POST['spec_2'] ?? '');
    $icon = trim($_POST['icon'] ?? 'wrench');

    if (empty($name) || empty($description)) {
        sendResponse(false, 'Nazwa i opis sprzętu są wymagane.');
    }

    if (!isset($_FILES['equipment_photo']) || $_FILES['equipment_photo']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, 'Przesłanie pliku zdjęcia jest wymagane.');
    }

    $file = $_FILES['equipment_photo'];

    if ($file['size'] > $config['upload']['max_size']) {
        sendResponse(false, 'Plik jest zbyt duży. Maksymalny rozmiar to 10MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $config['upload']['allowed_types'])) {
        sendResponse(false, 'Niedozwolony format. Dopuszczalne formaty: JPEG, PNG, WebP.');
    }

    $uploadDir = __DIR__ . '/../images/';
    $filename = 'sprzet-' . time() . '-' . uniqid() . '.webp';
    $targetPath = $uploadDir . $filename;
    $tempPath = $file['tmp_name'];

    $optimized = false;
    $apiKey = $config['tinypng']['api_key'] ?? '';
    if (!empty($apiKey)) {
        $optimized = compressWithTinyPNG($tempPath, $targetPath, $apiKey);
    }
    if (!$optimized) {
        $optimized = compressWithGD($tempPath, $targetPath, $config['upload']['quality'], $config['upload']['max_dimension']);
    }

    if (!$optimized) {
        sendResponse(false, 'Błąd zapisu i optymalizacji zdjęcia.');
    }

    try {
        $db->beginTransaction();

        // sort_order computed inside transaction to prevent race conditions
        $sortStmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM equipment FOR UPDATE");
        $nextSort = (int) $sortStmt->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO equipment (name, image, icon, description, badge, spec_1, spec_2, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $filename, $icon, $description, $badge ?: null, $spec1 ?: null, $spec2 ?: null, $nextSort]);
        $db->commit();
        sendResponse(true, 'Sprzęt został dodany do parku maszynowego.');
    } catch (PDOException $e) {
        $db->rollBack();
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        sendResponse(false, 'Błąd zapisu sprzętu: ' . $e->getMessage());
    }
}

/**
 * Edit Equipment
 */
function handleEditEquipment(PDO $db, array $config) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $badge = trim($_POST['badge'] ?? '');
    $spec1 = trim($_POST['spec_1'] ?? '');
    $spec2 = trim($_POST['spec_2'] ?? '');
    $icon = trim($_POST['icon'] ?? 'wrench');

    if ($id <= 0 || empty($name) || empty($description)) {
        sendResponse(false, 'Brak wymaganych danych do edycji.');
    }

    // Fetch existing image to delete if a new one is uploaded
    $stmtExist = $db->prepare("SELECT image FROM equipment WHERE id = ?");
    $stmtExist->execute([$id]);
    $existing = $stmtExist->fetch();
    if (!$existing) {
        sendResponse(false, 'Sprzęt nie został znaleziony.');
    }

    $filename = $existing['image'];
    $uploadedNewImage = false;

    if (isset($_FILES['equipment_photo']) && $_FILES['equipment_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['equipment_photo'];
        
        if ($file['size'] > $config['upload']['max_size']) {
            sendResponse(false, 'Plik jest zbyt duży. Maksymalny rozmiar to 10MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $config['upload']['allowed_types'])) {
            sendResponse(false, 'Dopuszczalne formaty: JPEG, PNG, WebP.');
        }

        $uploadDir = __DIR__ . '/../images/';
        $newFilename = 'sprzet-' . time() . '-' . uniqid() . '.webp';
        $targetPath = $uploadDir . $newFilename;
        $tempPath = $file['tmp_name'];

        $optimized = false;
        $apiKey = $config['tinypng']['api_key'] ?? '';
        if (!empty($apiKey)) {
            $optimized = compressWithTinyPNG($tempPath, $targetPath, $apiKey);
        }
        if (!$optimized) {
            $optimized = compressWithGD($tempPath, $targetPath, $config['upload']['quality'], $config['upload']['max_dimension']);
        }

        if ($optimized) {
            $filename = $newFilename;
            $uploadedNewImage = true;
        } else {
            sendResponse(false, 'Błąd zapisu nowego zdjęcia.');
        }
    }

    try {
        $stmt = $db->prepare("
            UPDATE equipment 
            SET name = ?, image = ?, icon = ?, description = ?, badge = ?, spec_1 = ?, spec_2 = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $filename, $icon, $description, $badge ?: null, $spec1 ?: null, $spec2 ?: null, $id]);

        // If a new photo was uploaded and successfully saved, delete the old file
        if ($uploadedNewImage) {
            $oldPath = __DIR__ . '/../images/' . $existing['image'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        sendResponse(true, 'Dane sprzętu zostały pomyślnie zaktualizowane.');
    } catch (PDOException $e) {
        if ($uploadedNewImage && file_exists(__DIR__ . '/../images/' . $filename)) {
            unlink(__DIR__ . '/../images/' . $filename);
        }
        sendResponse(false, 'Błąd zapisu w bazie danych: ' . $e->getMessage());
    }
}

/**
 * Delete Equipment
 */
function handleDeleteEquipment(PDO $db) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendResponse(false, 'Brak identyfikatora.');
    }

    $stmt = $db->prepare("SELECT image FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    $equip = $stmt->fetch();

    if (!$equip) {
        sendResponse(false, 'Sprzęt nie istnieje.');
    }

    $filepath = __DIR__ . '/../images/' . $equip['image'];

    try {
        $db->beginTransaction();

        $stmtDel = $db->prepare("DELETE FROM equipment WHERE id = ?");
        $stmtDel->execute([$id]);

        $db->commit();

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        sendResponse(true, 'Sprzęt został usunięty z bazy danych.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd bazy danych: ' . $e->getMessage());
    }
}

/**
 * Reorder Equipment
 */
function handleReorderEquipment(PDO $db) {
    $json = json_decode(file_get_contents('php://input'), true);
    $order = $json['order'] ?? [];

    if (empty($order)) {
        sendResponse(false, 'Brak danych kolejności.');
    }

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE equipment SET sort_order = ? WHERE id = ?");
        foreach ($order as $index => $id) {
            $stmt->execute([$index + 1, (int) $id]);
        }
        $db->commit();
        sendResponse(true, 'Kolejność sprzętu została zapisana.');
    } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(false, 'Błąd zapisu: ' . $e->getMessage());
    }
}
