<?php

declare(strict_types=1);

/**
 * SSH Connection Test Endpoint
 */

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false) {
        throw new RuntimeException('Failed to read request body');
    }

    $input = json_decode($raw, true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

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
        $cmd = sprintf(
            'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
            escapeshellarg($pass),
            $sshOpts,
            $target
        );
    } elseif (!empty($keyPath)) {
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
        $safeOutput = $pass !== '' ? str_replace($pass, '***', $outputStr) : $outputStr;
        $safeCmd = $pass !== '' ? str_replace($pass, '***', $cmd) : $cmd;
        echo json_encode([
            'success' => false,
            'error' => 'SSH connection failed',
            'details' => $safeOutput,
            'command_used' => $safeCmd
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'file'    => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
}
