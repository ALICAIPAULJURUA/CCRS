<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'counselor') {
    die(json_encode(['error' => 'Unauthorized']));
}

$session_id = filter_input(INPUT_GET, 'session', FILTER_VALIDATE_INT);
$last_count = filter_input(INPUT_GET, 'last', FILTER_VALIDATE_INT);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT COUNT(*) FROM counseling_messages WHERE session_id = ?");
$stmt->execute([$session_id]);
$count = $stmt->fetchColumn();

echo json_encode(['count' => $count]);
?>