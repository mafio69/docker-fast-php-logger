<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\StringLoaderExtension;

class LogController
{
    private string $logDir;
    private Environment $twig;

    public function __construct()
    {
        $this->logDir = __DIR__ . '/../../../logs';
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
        $this->twig->addExtension(new StringLoaderExtension());
        $this->twig->addFunction(new \Twig\TwigFunction('file_size', function($path) {
            return filesize($path);
        }));
    }

    public function index(Request $request): Response
    {
        $directories = [
            'App logs' => $this->logDir,
            'PHP errors' => dirname(ini_get('error_log') ?: '/var/www/html/logs/php-errors.log'),
            '/var/www/logs' => '/var/www/logs',
            'Home' => getenv('HOME') ?: '/root',
        ];

        $dirKey = $request->query->get('dir', 'App logs');
        if (!isset($directories[$dirKey])) {
            $dirKey = 'App logs';
        }
        $logsDir = $directories[$dirKey];

        $allFiles = [];
        $patterns = ['*.log', '*.php', '*/*.log', '*/*.php', '*/*/*.log', '*/*/*.php'];
        foreach ($patterns as $pat) {
            foreach (glob($logsDir . '/' . $pat) ?: [] as $file) {
                $rel = substr($file, strlen($logsDir) + 1);
                $allFiles[$rel] = $file;
            }
        }
        krsort($allFiles);

        $today = date('Y-m-d');
        $selectedFile = $request->query->get('file', '');
        $dateFrom = $request->query->get('from', $today);
        $dateTo = $request->query->get('to', $today);
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $lines = [];
        $loadedFiles = [];
        if ($selectedFile !== '' && isset($allFiles[$selectedFile])) {
            $lines = $this->loadLogFile($allFiles[$selectedFile]);
            $loadedFiles[] = $selectedFile;
        } else {
            foreach ($allFiles as $rel => $file) {
                $base = basename($rel, '.log');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $base) && $base >= $dateFrom && $base <= $dateTo) {
                    $lines = array_merge($lines, $this->loadLogFile($file));
                    $loadedFiles[] = $rel;
                }
            }
            if (empty($lines) && !empty($allFiles)) {
                $selectedFile = array_key_first($allFiles);
                $lines = $this->loadLogFile($allFiles[$selectedFile]);
                $loadedFiles[] = $selectedFile;
            }
        }
        usort($lines, fn($a, $b) => strncmp($b, $a, 19));

        $levelColors = [
            'DEBUG' => '#6a9fb5',
            'INFO' => '#90a959',
            'WARNING' => '#f4bf75',
            'ERROR' => '#ac4142',
            'CRITICAL' => '#ff5f5f',
        ];

        $dateFiles = array_filter(array_keys($allFiles), fn($r) => preg_match('/\d{4}-\d{2}-\d{2}/', basename($r, '.log')));
        $dates = array_map(fn($r) => basename($r, '.log'), $dateFiles);
        sort($dates);
        $minDate = reset($dates) ?: $today;
        $maxDate = end($dates) ?: $today;

        $parsedLines = [];
        foreach ($lines as $line) {
            $parsedLines[] = $this->parseLine($line);
        }

        $dateRange = $dateFrom === $dateTo ? $dateFrom : "$dateFrom → $dateTo";

        $html = $this->twig->render('logs/index.html.twig', [
            'directories' => $directories,
            'dir_key' => $dirKey,
            'all_files' => $allFiles,
            'selected_file' => $selectedFile,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'date_range' => $dateRange,
            'lines' => $parsedLines,
            'loaded_files' => $loadedFiles,
            'level_colors' => $levelColors,
        ]);

        return new Response($html);
    }

    private function loadLogFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if ($lines && str_starts_with($lines[0], '<?php')) {
            array_shift($lines);
        }
        return $lines;
    }

    private function parseLine(string $line): array
    {
        if (!preg_match('/^\[([^\]]+)\] \[([A-Z]+)\] \[([^\]]*)\] (.*)$/', $line, $m)) {
            return ['raw' => $line];
        }
        $rest = $m[4];
        $json = null;
        if (preg_match('/(\{.*\}|\[.*\])$/', $rest, $jm)) {
            $json = $jm[1];
            $rest = trim(substr($rest, 0, -strlen($json)));
        }
        return [
            'date' => $m[1],
            'level' => $m[2],
            'location' => $m[3],
            'message' => $rest,
            'json' => $json,
            'ts' => strtotime($m[1]),
        ];
    }
}
