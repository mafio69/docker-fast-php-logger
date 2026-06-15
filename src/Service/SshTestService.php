<?php

declare(strict_types=1);

namespace App\Service;

/**
 * SSH connection testing service using system ssh/sshpass commands.
 * No external dependencies - uses shell exec.
 */
readonly class SshTestService
{
    /**
     * @return array{success: bool, message?: string, error?: string, details?: string}
     */
    public function testConnection(
        string $host,
        string $user,
        ?string $password = null,
        ?string $keyPath = null,
        int $port = 22,
    ): array {
        if (empty($host) || empty($user)) {
            return ['success' => false, 'error' => 'Host and user are required'];
        }

        if ($port < 1 || $port > 65535) {
            return ['success' => false, 'error' => 'Port must be between 1 and 65535'];
        }

        if (empty($password) && empty($keyPath)) {
            return ['success' => false, 'error' => 'Password or key required'];
        }

        $cmd = $this->buildCommand($host, $user, $password, $keyPath, $port);

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode === 0 && str_contains($outputStr, 'SSH_OK')) {
            return [
                'success' => true,
                'message' => 'SSH connection successful',
            ];
        }

        return [
            'success' => false,
            'error' => 'SSH connection failed',
            'details' => $this->sanitizeOutput($outputStr, $password),
        ];
    }

    private function buildCommand(
        string $host,
        string $user,
        ?string $password,
        ?string $keyPath,
        int $port,
    ): string {
        $baseOpts = sprintf(
            '-o StrictHostKeyChecking=no -p %s',
            escapeshellarg((string) $port)
        );
        $target = escapeshellarg($user.'@'.$host);

        if (! empty($password)) {
            $sshOpts = $baseOpts.' -o PreferredAuthentications=password';

            return sprintf(
                'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
                escapeshellarg($password),
                $sshOpts,
                $target
            );
        }

        $sshOpts = $baseOpts.' -o PreferredAuthentications=publickey';

        return sprintf(
            'ssh %s -i %s %s echo "SSH_OK" 2>&1',
            $sshOpts,
            escapeshellarg($keyPath ?? ''),
            $target
        );
    }

    private function sanitizeOutput(string $output, ?string $password): string
    {
        if (empty($password)) {
            return $output;
        }

        return str_replace($password, '***', $output);
    }
}
