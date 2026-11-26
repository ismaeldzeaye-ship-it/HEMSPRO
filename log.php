<?php
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit;
}

// Sanitise — strip tags, trim whitespace, cap length
function clean($value, $max = 100) {
    return mb_substr(trim(strip_tags((string)($value ?? ''))), 0, $max);
}

$row = [
    date('Y-m-d H:i:s'),
    clean($data['name'],     60),
    clean($data['category'], 40),
    clean($data['field1'],   80),
    clean($data['field2'],   80),
];

// Create data directory if it doesn't exist yet
$dir = __DIR__ . '/data';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$logFile = $dir . '/badge-log.csv';
$isNew   = !file_exists($logFile);

// Append row with file locking to handle concurrent saves safely
$fp = fopen($logFile, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    if ($isNew) {
        fputcsv($fp, ['Timestamp', 'Name', 'Category', 'Faculty_or_Department', 'Program_or_Role']);
    }
    fputcsv($fp, $row);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not write log']);
}
