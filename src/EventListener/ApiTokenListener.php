<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Guards the config/SSH API routes with a shared-secret token, since this app
 * has no Symfony security bundle configured. Behavior is intentionally
 * env-driven so the existing zero-friction local dev workflow keeps working:
 * - /api/ssh-test: disabled (404) unless SSH_TEST_TOKEN is set, matching the
 *   convention already documented (but previously unenforced) in .env.example.
 * - /api/config, /api/config/hosts, /api/config/app: open in APP_ENV=dev when
 *   DASHBOARD_API_TOKEN is unset, but required (and fail-closed) otherwise -
 *   these can read/write stored SSH host credentials.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class ApiTokenListener
{
    private const TOKEN_PROTECTED_PATHS = [
        '/api/config',
        '/api/config/hosts',
        '/api/config/app',
    ];

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $providedToken = (string) $request->headers->get('X-Dashboard-Token', '');

        if ($path === '/api/ssh-test') {
            $requiredToken = $_ENV['SSH_TEST_TOKEN'] ?? '';
            if ($requiredToken === '') {
                $event->setResponse(new JsonResponse(['error' => 'endpoint_disabled'], 404));
                return;
            }
            if (!hash_equals($requiredToken, $providedToken)) {
                $event->setResponse(new JsonResponse(['error' => 'unauthorized'], 401));
            }
            return;
        }

        if (in_array($path, self::TOKEN_PROTECTED_PATHS, true)) {
            $requiredToken = $_ENV['DASHBOARD_API_TOKEN'] ?? '';
            $isDev = ($_ENV['APP_ENV'] ?? 'dev') !== 'prod';

            if ($requiredToken === '') {
                if ($isDev) {
                    return;
                }
                $event->setResponse(new JsonResponse(['error' => 'dashboard_api_token_not_configured'], 500));
                return;
            }

            if (!hash_equals($requiredToken, $providedToken)) {
                $event->setResponse(new JsonResponse(['error' => 'unauthorized'], 401));
            }
        }
    }
}
