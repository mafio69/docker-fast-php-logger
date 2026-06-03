<?php

declare(strict_types=1);

final class ConfigStore
{
    public function __construct(private readonly string $path)
    {
    }

    public function load(): array
    {
        if (! file_exists($this->path)) {
            return [];
        }

        return json_decode(file_get_contents($this->path), true) ?? [];
    }

    public function save(array $config): void
    {
        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($this->path, $encoded) === false) {
            throw new RuntimeException("Failed to write config: {$this->path}");
        }
    }
}
