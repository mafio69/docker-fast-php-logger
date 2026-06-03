<?php
/**
 * SSH Connection Test Endpoint
 *
 * Requires X-SSH-Token header matching the SSH_TEST_TOKEN environment variable.
 * If no token is configured the endpoint is disabled entirely.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Auth gate ────────────────────────────────────────────────────────────────
$expectedToken = getenv('SSH_TEST_TOKEN') ?: ($_ENV['SSH_TEST_TOKEN'] ?? '');
if ($expectedToken === '') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'SSH test endpoint is disabled (no token configured)']);
    exit;
}

$providedToken = $_SERVER['HTTP_X_SSH_TOKEN'] ?? '';
if (!hash_equals($expectedToken, $providedToken)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$host = $input['host'] ?? '';
$user = $input['user'] ?? '';
$pass = $input['pass'] ?? '';
$port = $input['port'] ?? '22';
$keyPath = $input['key'] ?? '';
$logPath = $input['logPath'] ?? '/var/log/syslog';

// Validate $logPath: must be an absolute path with no shell metacharacters
if (!preg_match('#^/[a-zA-Z0-9_./ -]+$#', $logPath)) {
    echo json_encode(['success' => false, 'error' => 'Invalid log path']);
    exit;
}

if (empty($host) || empty($user)) {
    echo json_encode(['success' => false, 'error' => 'Host and user are required']);
    exit;
}

// Build SSH command
$sshOpts = '-o StrictHostKeyChecking=no -o PreferredAuthentications=password -p ' . escapeshellarg($port);
$target = escapeshellarg($user . '@' . $host);

// Test connection with sshpass
if (!empty($pass)) {
    // Using password
    $cmd = sprintf(
        'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
        escapeshellarg($pass),
        $sshOpts,
        $target
    );
} elseif (!empty($keyPath)) {
    // Using key
    $cmd = sprintf(
        'ssh %s -i %s %s echo "SSH_OK" 2>&1',
        $sshOpts,
        escapeshellarg($keyPath),
        $target
    );
} else {
    echo json_encode(['success' => false, 'error' => 'Password or key required']);
    exit;
}

// Execute test
$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);

$outputStr = implode("\n", $output);

if ($returnCode === 0 && strpos($outputStr, 'SSH_OK') !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'SSH connection successful',
        'command_preview' => 'tail -f ' . escapeshellarg($logPath),
        'note' => 'Ready to stream logs from: ' . $logPath,
    ]);
} else {
    // Return a generic error — never expose the raw command or output to the client
    echo json_encode([
        'success' => false,
        'error' => 'SSH connection failed',
        'details' => 'Could not establish SSH connection. Check host, credentials and port.',
    ]);
}
