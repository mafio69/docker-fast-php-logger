<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController
{
    private string $configFile;

    public function __construct()
    {
        $this->configFile = __DIR__ . '/../../../config.json';
    }

    public function config(): JsonResponse
    {
        $config = file_exists($this->configFile)
            ? json_decode(file_get_contents($this->configFile), true) ?? []
            : [];

        return new JsonResponse($config);
    }

    public function saveHosts(Request $request): JsonResponse
    {
        try {
            $input = json_decode($request->getContent(), true);
            if ($input === null) {
                throw new \RuntimeException('Invalid JSON input');
            }

            $config = file_exists($this->configFile)
                ? json_decode(file_get_contents($this->configFile), true) ?? []
                : [];

            $config['hosts'] = $input;

            if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new \RuntimeException("Failed to write config: {$this->configFile}");
            }

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function saveApp(Request $request): JsonResponse
    {
        try {
            $input = json_decode($request->getContent(), true);
            if ($input === null) {
                throw new \RuntimeException('Invalid JSON input');
            }

            $config = file_exists($this->configFile)
                ? json_decode(file_get_contents($this->configFile), true) ?? []
                : [];

            $config['app'] = $input;

            if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new \RuntimeException("Failed to write config: {$this->configFile}");
            }

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function sshTest(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \RuntimeException('Invalid JSON input');
            }

            $host = $data['host'] ?? '';
            $user = $data['user'] ?? '';
            $pass = $data['pass'] ?? '';
            $port = $data['port'] ?? '22';
            $key = $data['key'] ?? '';
            $logPath = $data['logPath'] ?? '';

            if (empty($host) || empty($user) || empty($logPath)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: host, user, logPath'
                ], 400);
            }

            // Test SSH connection
            $sshCommand = $this->buildSshCommand($host, $user, $port, $pass, $key);
            $testCmd = $sshCommand . ' "echo \"SSH connection successful\"" 2>&1';

            $output = [];
            $returnCode = 0;
            exec($testCmd, $output, $returnCode);

            if ($returnCode !== 0) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'SSH connection failed',
                    'details' => implode("\n", $output)
                ], 500);
            }

            // Check if log file exists
            $checkCmd = $sshCommand . ' "test -f ' . escapeshellarg($logPath) . ' && echo EXISTS || echo NOT_FOUND" 2>&1';
            $checkOutput = [];
            $checkReturn = 0;
            exec($checkCmd, $checkOutput, $checkReturn);

            $fileExists = in_array('EXISTS', $checkOutput);

            return new JsonResponse([
                'success' => true,
                'message' => 'SSH connection successful',
                'note' => $fileExists
                    ? "Log file found: {$logPath}"
                    : "Log file not found: {$logPath} (will be created when logs are written)"
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildSshCommand(string $host, string $user, string $port, string $pass, string $key): string
    {
        $cmd = 'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p ' . escapeshellarg($port);

        if (!empty($key)) {
            $keyPath = str_replace('~', getenv('HOME') ?: '/root', $key);
            $cmd .= ' -i ' . escapeshellarg($keyPath);
        }

        $cmd .= ' ' . escapeshellarg($user . '@' . $host);

        return $cmd;
    }
}
