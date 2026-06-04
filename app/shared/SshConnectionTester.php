<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/JsonResponse.php';

final class SshConnectionTester
{
    public static function handleRequest(): void
    {
        try {
            self::doHandle();
        } catch (\Throwable $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    private static function doHandle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            JsonResponse::error('Method not allowed', 405);
            exit;
        }

        // Auth gate: require X-SSH-Token header matching SSH_TEST_TOKEN env var
        $expectedToken = getenv('SSH_TEST_TOKEN') ?: ($_ENV['SSH_TEST_TOKEN'] ?? '');
        if ($expectedToken === '') {
            JsonResponse::error('SSH test endpoint is disabled (no token configured)', 403);
            exit;
        }

        $providedToken = $_SERVER['HTTP_X_SSH_TOKEN'] ?? '';
        if (!hash_equals($expectedToken, $providedToken)) {
            JsonResponse::error('Unauthorized', 401);
            exit;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false) {
            JsonResponse::error('Failed to read request body', 400);
            exit;
        }

        $input = json_decode($raw, true);
        if (!is_array($input)) {
            JsonResponse::error('Invalid JSON input', 400);
            exit;
        }

        $host = $input['host'] ?? '';
        $user = $input['user'] ?? '';
        $pass = $input['pass'] ?? '';
        $port = $input['port'] ?? '22';
        $keyPath = $input['key'] ?? '';
        $logPath = $input['logPath'] ?? '/var/log/syslog';

        // Validate $logPath: must be an absolute path with no shell metacharacters
        if (!preg_match('#^/[a-zA-Z0-9_./ -]+$#', $logPath)) {
            JsonResponse::send(['success' => false, 'error' => 'Invalid log path']);
            exit;
        }

        if (empty($host) || empty($user)) {
            JsonResponse::send(['success' => false, 'error' => 'Host and user are required']);
            exit;
        }

        $cmd = self::buildCommand($host, $user, $pass, $port, $keyPath);

        if ($cmd === null) {
            JsonResponse::send(['success' => false, 'error' => 'Password or key required']);
            exit;
        }

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode === 0 && strpos($outputStr, 'SSH_OK') !== false) {
            JsonResponse::send([
                'success' => true,
                'message' => 'SSH connection successful',
                'command_preview' => 'tail -f ' . escapeshellarg($logPath),
                'note' => 'Ready to stream logs from: ' . $logPath,
            ]);
        } else {
            // Generic error — never expose the raw command or output to the client
            JsonResponse::send([
                'success' => false,
                'error' => 'SSH connection failed',
                'details' => 'Could not establish SSH connection. Check host, credentials and port.',
            ]);
        }
    }

    private static function buildCommand(
        string $host,
        string $user,
        string $pass,
        string $port,
        string $keyPath,
    ): ?string {
        $sshOpts = '-o StrictHostKeyChecking=no -o PreferredAuthentications=password -p ' . escapeshellarg($port);
        $target = escapeshellarg($user . '@' . $host);

        if (! empty($pass)) {
            return sprintf(
                'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
                escapeshellarg($pass),
                $sshOpts,
                $target
            );
        }

        if (! empty($keyPath)) {
            return sprintf(
                'ssh %s -i %s %s echo "SSH_OK" 2>&1',
                $sshOpts,
                escapeshellarg($keyPath),
                $target
            );
        }

        return null;
    }
}
