<?php

declare(strict_types=1);

require_once __DIR__ . '/JsonResponse.php';

final class SshConnectionTester
{
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            JsonResponse::error('Method not allowed', 405);
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
                'command_preview' => 'tail -f ' . $logPath,
                'note' => 'Ready to stream logs from: ' . $logPath,
            ]);
        } else {
            $safeOutput = str_replace($pass, '***', $outputStr);
            JsonResponse::send([
                'success' => false,
                'error' => 'SSH connection failed',
                'details' => $safeOutput,
                'command_used' => str_replace($pass, '***', $cmd),
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
