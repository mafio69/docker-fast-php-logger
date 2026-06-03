<?php

declare(strict_types=1);

namespace App\Logger;

/**
 * Builds SSH commands for remote log streaming.
 */
class SshCommandBuilder
{
    /**
     * Validate SSH connection input.
     *
     * @return string|null Error message or null if valid.
     */
    public function validate(string $host, string $user, string $pass, string $keyPath): ?string
    {
        if (empty($host) || empty($user)) {
            return 'Host and user are required';
        }

        if (empty($pass) && empty($keyPath)) {
            return 'Password or key required';
        }

        return null;
    }

    /**
     * Build the SSH test command string.
     */
    public function buildTestCommand(
        string $host,
        string $user,
        string $pass = '',
        string $keyPath = '',
        string $port = '22'
    ): string {
        $sshOpts = '-o StrictHostKeyChecking=no -o PreferredAuthentications=password -p ' . escapeshellarg($port);
        $target = escapeshellarg($user . '@' . $host);

        if (!empty($pass)) {
            return sprintf(
                'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
                escapeshellarg($pass),
                $sshOpts,
                $target
            );
        }

        return sprintf(
            'ssh %s -i %s %s echo "SSH_OK" 2>&1',
            $sshOpts,
            escapeshellarg($keyPath),
            $target
        );
    }

    /**
     * Sanitize output by removing the password from any error messages.
     */
    public function sanitizeOutput(string $output, string $password): string
    {
        if (empty($password)) {
            return $output;
        }

        return str_replace($password, '***', $output);
    }
}
