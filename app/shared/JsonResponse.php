<?php

declare(strict_types=1);

final class JsonResponse
{
    public static function send(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data);
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): void
    {
        self::send(array_merge(['error' => $message], $extra), $statusCode);
    }
}
