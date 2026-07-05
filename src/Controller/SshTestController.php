<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SshTestService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * SSH connection test endpoint - migrated from app_old/api/ssh-test.php
 */
readonly class SshTestController
{
    public function __construct(
        private SshTestService $sshService,
    ) {
    }

    #[Route('/api/ssh-test', methods: ['POST'])]
    public function test(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];

            $result = $this->sshService->testConnection(
                host: $data['host'] ?? '',
                user: $data['user'] ?? '',
                password: $data['pass'] ?? null,
                keyPath: $data['key'] ?? null,
                port: (int) ($data['port'] ?? 22),
            );

            $statusCode = $result['success'] ? 200 : 400;

            return new JsonResponse($result, $statusCode);
        } catch (Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
