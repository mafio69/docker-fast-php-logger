<?php
/**
 * SSH Connections Management API
 * GET - list connections
 * POST - save new connection (with limit check)
 * DELETE - remove connection
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Service\DatabaseService;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = new DatabaseService();

try {
    switch ($method) {
        case 'GET':
            // List all connections
            $connections = $db->getSshConnections();
            $limit = $db->getConfig('system', 'ssh_connections_limit', 5);
            $count = $db->countSshConnections();

            echo json_encode([
                'success' => true,
                'connections' => $connections,
                'limit' => $limit,
                'count' => $count,
                'can_add' => $count < $limit
            ]);
            break;

        case 'POST':
            // Save new connection
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$db->canAddSshConnection()) {
                $limit = $db->getConfig('system', 'ssh_connections_limit', 5);
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'error' => "Limit połączeń osiągnięty (max: {$limit}). Usuń stare połączenie, aby dodać nowe."
                ]);
                exit;
            }

            $required = ['name', 'host', 'username', 'log_path'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => "Pole wymagane: {$field}"
                    ]);
                    exit;
                }
            }

            $data = [
                'name' => $input['name'],
                'host' => $input['host'],
                'port' => $input['port'] ?? 22,
                'username' => $input['username'],
                'password' => $input['password'] ?? '',
                'key_path' => $input['key_path'] ?? '',
                'log_path' => $input['log_path']
            ];

            $db->saveSshConnection($data);

            echo json_encode([
                'success' => true,
                'message' => 'Połączenie zapisane',
                'connection' => $data
            ]);
            break;

        case 'DELETE':
            // Delete connection
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID połączenia wymagane'
                ]);
                exit;
            }

            if ($db->deleteSshConnection((int)$id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Połączenie usunięte'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Nie znaleziono połączenia'
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd serwera: ' . $e->getMessage()
    ]);
}
