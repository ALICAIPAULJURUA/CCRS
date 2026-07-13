<?php
// Optional: Log 404 errors for monitoring
// This helps identify broken links or pages users are looking for

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['page'])) {
        $log_file = __DIR__ . '/logs/404_errors.log';
        $log_dir = dirname($log_file);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = date('Y-m-d H:i:s') . ' | ' . 
                     $_SERVER['REMOTE_ADDR'] . ' | ' . 
                     $data['page'] . ' | ' . 
                     ($data['referrer'] ?? 'direct') . "\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

// Return empty response
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
?>