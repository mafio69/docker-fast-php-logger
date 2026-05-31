<?php
/**
 * SSH Connection Test Endpoint
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$host = $input['host'] ?? '';
$user = $input['user'] ?? '';
$pass = $input['pass'] ?? '';
$port = $input['port'] ?? '22';
$keyPath = $input['key'] ?? '';
$logPath = $input['logPath'] ?? '/var/log/syslog';

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
        'command_preview' => 'tail -f ' . $logPath,
        'note' => 'Ready to stream logs from: ' . $logPath
    ]);
} else {
    // Hide password from error
    $safeOutput = str_replace($pass, '***', $outputStr);
    echo json_encode([
        'success' => false,
        'error' => 'SSH connection failed',
        'details' => $safeOutput,
        'command_used' => str_replace($pass, '***', $cmd)
    ]);
}
