echo json_encode([
<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'connected' => isset($_SESSION['utilisateur_id'])
]);
