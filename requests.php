<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Ruta hacia el archivo JSON de almacenamiento local de pedidos
$file = __DIR__ . '/requests.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo json_encode([]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o JSON erróneo']);
        exit;
    }

    // Cargar la lista existente de pedidos
    $requests = [];
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $requests = json_decode($content, true) ?: [];
    }

    if ($data['action'] === 'add') {
        // Limpiar entrada sin aplicar htmlspecialchars redundante que duplique entidades al renderizar en admin.html
        $name = trim(strip_tags($data['name'] ?? ''));
        $song = trim(strip_tags($data['song'] ?? ''));
        $message = trim(strip_tags($data['message'] ?? ''));
        $city = trim(strip_tags($data['city'] ?? ''));
        $timestamp = !empty($data['timestamp']) ? trim(strip_tags($data['timestamp'])) : date('H:i');

        if (empty($name) || empty($song)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'El nombre y la canción son obligatorios']);
            exit;
        }

        $newRequest = [
            'id' => uniqid('req_'),
            'name' => $name,
            'song' => $song,
            'message' => $message,
            'city' => $city,
            'timestamp' => $timestamp
        ];

        // Insertar al inicio de la lista
        array_unshift($requests, $newRequest);
        
        // Limitar el historial a los últimos 100 pedidos
        if (count($requests) > 100) {
            $requests = array_slice($requests, 0, 100);
        }

        if (file_put_contents($file, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false) {
            echo json_encode(['status' => 'success', 'message' => '¡Pedido enviado a cabina con éxito!']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar el pedido en el servidor']);
        }
        exit;

    } elseif ($data['action'] === 'delete') {
        $targetId = $data['id'] ?? '';
        $requests = array_values(array_filter($requests, function($item) use ($targetId) {
            return $item['id'] !== $targetId;
        }));

        if (file_put_contents($file, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false) {
            echo json_encode(['status' => 'success', 'message' => 'Pedido eliminado correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el pedido']);
        }
        exit;
    }
}
?>