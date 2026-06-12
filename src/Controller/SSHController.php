<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SSHController extends AbstractController
{
    #[Route('/api/ssh-connections', name: 'api_ssh_connections', methods: ['POST'])]
    public function testConnection(Request $request): JsonResponse
    {
        $input = json_decode($request->getContent(), true);

        $host = $input['host'] ?? '';
        $user = $input['user'] ?? '';
        $pass = $input['pass'] ?? '';
        $port = $input['port'] ?? '22';
        $keyPath = $input['key'] ?? '';
        $logPath = $input['logPath'] ?? '/var/log/syslog';

        if (empty($host) || empty($user)) {
            return new JsonResponse(['success' => false, 'error' => 'Host and user are required'], 400);
        }

        // Build SSH command
        $baseOpts = '-o StrictHostKeyChecking=no -p ' . escapeshellarg($port);
        $target = escapeshellarg($user . '@' . $host);

        // Test connection with sshpass
        if (!empty($pass)) {
            $sshOpts = $baseOpts . ' -o PreferredAuthentications=password';
            $cmd = sprintf(
                'SSHPASS=%s sshpass -e ssh %s %s echo "SSH_OK" 2>&1',
                escapeshellarg($pass),
                $sshOpts,
                $target
            );
        } elseif (!empty($keyPath)) {
            $sshOpts = $baseOpts . ' -o PreferredAuthentications=publickey';
            $cmd = sprintf(
                'ssh %s -i %s %s echo "SSH_OK" 2>&1',
                $sshOpts,
                escapeshellarg($keyPath),
                $target
            );
        } else {
            return new JsonResponse(['success' => false, 'error' => 'Password or key required'], 400);
        }

        // Execute test
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode === 0 && strpos($outputStr, 'SSH_OK') !== false) {
            return new JsonResponse([
                'success' => true,
                'message' => 'SSH connection successful',
                'command_preview' => 'tail -f ' . $logPath,
                'note' => 'Ready to stream logs from: ' . $logPath
            ]);
        } else {
            // Hide password from error
            $safeOutput = str_replace($pass, '***', $outputStr);
            return new JsonResponse([
                'success' => false,
                'error' => 'SSH connection failed',
                'details' => $safeOutput,
                'command_used' => str_replace($pass, '***', $cmd)
            ], 500);
        }
    }
}