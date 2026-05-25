<?php
/**
 * Shared helper for Google Places API reviews sync
 * Keep all reviews sync logic in one place.
 */

function syncGoogleReviews(PDO $db, array $config): array {
    $apiKey = $config['google_places']['api_key'] ?? '';
    $placeId = $config['google_places']['place_id'] ?? '';

    if (empty($apiKey) || empty($placeId)) {
        return ['success' => false, 'message' => 'Brak klucza API lub Place ID w konfiguracji.'];
    }

    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($placeId) . "&fields=reviews&key=" . urlencode($apiKey) . "&language=pl";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        $curlError = isset($ch) ? curl_error($ch) : 'Unknown connection error';
        error_log("Google Reviews Sync Failure: HTTP Code: {$httpCode}, Curl Error: {$curlError}");
        return ['success' => false, 'message' => 'Błąd połączenia z API Google. Sprawdź logi serwera.'];
    }

    $data = json_decode($response, true);
    if (($data['status'] ?? '') !== 'OK') {
        $apiErr = $data['error_message'] ?? $data['status'] ?? 'Nieznany błąd';
        error_log("Google Reviews Sync Failure: Google API returned status: " . ($data['status'] ?? 'UNKNOWN') . " - Message: {$apiErr}");
        return ['success' => false, 'message' => 'Google API zwróciło błąd: ' . $apiErr];
    }

    $reviews = $data['result']['reviews'] ?? [];
    if (empty($reviews)) {
        return ['success' => true, 'message' => 'Brak nowych opinii w Google Maps.', 'imported' => 0];
    }

    $importedCount = 0;
    try {
        $db->beginTransaction();

        $checkStmt = $db->prepare("SELECT id FROM google_reviews WHERE author_name = ? AND review_text = ?");
        $insertStmt = $db->prepare("
            INSERT INTO google_reviews (author_name, author_photo, rating, review_text, review_time, is_manual, is_visible)
            VALUES (?, ?, ?, ?, ?, 0, ?)
        ");

        foreach ($reviews as $rev) {
            $author = $rev['author_name'] ?? '';
            $text = $rev['text'] ?? '';
            if (empty($author)) continue;

            // Check duplicate
            $checkStmt->execute([$author, $text]);
            if ($checkStmt->fetch()) {
                continue; // Already imported
            }

            $rating = (int) ($rev['rating'] ?? 5);
            $isVisible = ($rating >= 4) ? 1 : 0;

            $insertStmt->execute([
                $author,
                $rev['profile_photo_url'] ?? null,
                $rating,
                $text,
                $rev['relative_time_description'] ?? 'niedawno',
                $isVisible
            ]);
            $importedCount++;
        }

        $db->commit();
        return ['success' => true, 'message' => "Pomyślnie zaimportowano {$importedCount} nowych opinii z Google.", 'imported' => $importedCount];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Google Reviews Database Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Błąd bazy danych podczas importu opinii: ' . $e->getMessage()];
    }
}
