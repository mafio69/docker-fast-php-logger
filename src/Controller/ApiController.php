<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Throwable;

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
                throw new RuntimeException('Invalid JSON input');
            }

            $config = file_exists($this->configFile)
                ? json_decode(file_get_contents($this->configFile), true) ?? []
                : [];

            $config['hosts'] = $input;

            if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new RuntimeException("Failed to write config: $this->configFile");
            }

            return new JsonResponse(['ok' => true]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function saveApp(Request $request): JsonResponse
    {
        try {
            $input = json_decode($request->getContent(), true);
            if ($input === null) {
                throw new RuntimeException('Invalid JSON input');
            }

            $config = file_exists($this->configFile)
                ? json_decode(file_get_contents($this->configFile), true) ?? []
                : [];

            $config['app'] = $input;

            if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new RuntimeException("Failed to write config: $this->configFile");
            }

            return new JsonResponse(['ok' => true]);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
