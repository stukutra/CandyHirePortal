<?php
/**
 * Get Countries List
 * Public endpoint - No authentication required
 *
 * GET /api/public/countries.php
 * Returns list of countries with VAT/tax information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Get all countries, ordered by name
    $query = "SELECT
                code,
                name,
                name_it,
                name_es,
                name_en,
                has_vat,
                vat_label,
                requires_sdi,
                currency,
                phone_prefix,
                is_eu
              FROM countries
              ORDER BY
                CASE code
                    WHEN 'IT' THEN 1
                    WHEN 'ES' THEN 2
                    WHEN 'FR' THEN 3
                    WHEN 'DE' THEN 4
                    WHEN 'GB' THEN 5
                    ELSE 10
                END,
                name ASC";

    $stmt = $db->query($query);
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert boolean values
    foreach ($countries as &$country) {
        $country['has_vat'] = (bool) $country['has_vat'];
        $country['requires_sdi'] = (bool) $country['requires_sdi'];
        $country['is_eu'] = (bool) $country['is_eu'];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'countries' => $countries
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
