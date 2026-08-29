<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    echo json_encode(['status' => 'error', 'message' => 'El JSON recibido no es válido']);
    exit;
}

$file = __DIR__ . '/config.json';

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['status' => 'success', 'message' => 'Configuración guardada en el hosting exitosamente']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al escribir config.json. Revisa que tenga permisos de escritura (0644 o 0666) en tu hosting.']);
}
?>