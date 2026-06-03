<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DatabaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\StringLoaderExtension;

class LogController
{
    private string $logDir;
    private Environment $twig;
    private DatabaseService $db;

    public function __construct()
    {
        $this->logDir = realpath(__DIR__ . '/../../../logs') ?: '/var/www/html/logs';
        $this->db = new DatabaseService();
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
        $sshConnections = $this->db->getSshConnections();
        $sshId = $request->query->get('ssh');
        $sshConnection = null;

        if ($sshId && is_numeric($sshId)) {
            $sshConnection = $this->db->getSshConnectionById((int) $sshId);
        }

        // Local directories (when not using SSH)
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

        // Ensure local log directory exists
        if (!$sshConnection && !is_dir($logsDir)) {
            @mkdir($logsDir, 0777, true);
        }

        $allFiles = [];

        if ($sshConnection) {
            // Fetch remote files via SSH
            $allFiles = $this->getRemoteFiles($sshConnection, []);
        } else {
            // Local files - scan directory recursively
            $allFiles = $this->getLocalFiles($logsDir);
        }
        ksort($allFiles);

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
            if ($sshConnection) {
                $lines = $this->loadRemoteLogFile($sshConnection, $allFiles[$selectedFile]);
            } else {
                $lines = $this->loadLogFile($allFiles[$selectedFile]);
            }
            $loadedFiles[] = $selectedFile;
        } else {
            foreach ($allFiles as $rel => $file) {
                $base = basename($rel, '.log');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $base) && $base >= $dateFrom && $base <= $dateTo) {
                    if ($sshConnection) {
                        $lines = array_merge($lines, $this->loadRemoteLogFile($sshConnection, $file));
                    } else {
                        $lines = array_merge($lines, $this->loadLogFile($file));
                    }
                    $loadedFiles[] = $rel;
                }
            }
            if (empty($lines) && !empty($allFiles)) {
                $selectedFile = array_key_first($allFiles);
                if ($sshConnection) {
                    $lines = $this->loadRemoteLogFile($sshConnection, $allFiles[$selectedFile]);
                } else {
                    $lines = $this->loadLogFile($allFiles[$selectedFile]);
                }
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
            'ssh_connections' => $sshConnections,
            'ssh_connection' => $sshConnection,
            'ssh_id' => $sshId,
        ]);

        return new Response($html);
    }

    public function saveSshConnection(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
            }

            $required = ['name', 'host', 'username', 'log_path'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse(['success' => false, 'error' => "Field '{$field}' is required"], 400);
                }
            }

            // Must have either password or key_path
            if (empty($data['password']) && empty($data['key_path'])) {
                return new JsonResponse(['success' => false, 'error' => 'Either password or key_path is required'], 400);
            }

            $this->db->saveSshConnection([
                'name' => $data['name'],
                'host' => $data['host'],
                'port' => $data['port'] ?? 22,
                'username' => $data['username'],
                'password' => $data['password'] ?? '',
                'key_path' => $data['key_path'] ?? '',
                'log_path' => $data['log_path'],
            ]);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
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

    private function buildSshCommand(array $conn): string
    {
        $cmd = 'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p ' . escapeshellarg((string) ($conn['port'] ?? 22));

        if (!empty($conn['key_path'])) {
            // Replace ~ with the mounted ssh directory
            $keyPath = str_replace('~', '/var/www', $conn['key_path']);
            $cmd .= ' -i ' . escapeshellarg($keyPath);
        }

        $cmd .= ' ' . escapeshellarg($conn['username'] . '@' . $conn['host']);

        return $cmd;
    }

    private function getRemoteFiles(array $conn, array $patterns): array
    {
        $logPath = $conn['log_path'] ?? '/var/log';
        
        // Expand tilde to home directory
        if (str_starts_with($logPath, '~/')) {
            $logPath = '$HOME' . substr($logPath, 1);
        }
        
        // Remove filename from path if provided - we want the directory
        if (substr($logPath, -1) !== '/') {
            // Check if last part looks like a file (has extension)
            $parts = explode('/', $logPath);
            $lastPart = end($parts);
            if (strpos($lastPart, '.') !== false) {
                // Looks like a file, get directory only
                array_pop($parts);
                $logPath = implode('/', $parts);
            }
        }
        
        // Ensure trailing slash for consistency
        $logPath = rtrim($logPath, '/') . '/';
        
        $sshCmd = $this->buildSshCommand($conn);
        $files = [];

        // Get all files recursively, then filter
        $cmd = $sshCmd . ' "find ' . escapeshellarg($logPath) . ' -type f 2>/dev/null | sort"';
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0) {
            foreach ($output as $file) {
                // Skip common non-log directories and files
                if ($this->shouldSkipFile($file)) {
                    continue;
                }
                
                $rel = substr($file, strlen($logPath));
                if ($rel === false || $rel === '') {
                    $rel = basename($file);
                }
                $files[$rel] = $file;
            }
        }

        return $files;
    }

    private function shouldSkipFile(string $file): bool
    {
        $skipPatterns = [
            '/vendor/',
            '/node_modules/',
            '/.git/',
            '/cache/',
            '/storage/framework/',
            '/bootstrap/cache/',
            '/public/build/',
            '/dist/',
            '/build/',
            '.min.js',
            '.min.css',
            '.map',
            '.woff',
            '.woff2',
            '.ttf',
            '.eot',
            '.svg',
            '.png',
            '.jpg',
            '.jpeg',
            '.gif',
            '.ico',
            '.zip',
            '.tar',
            '.gz',
        ];

        foreach ($skipPatterns as $pattern) {
            if (strpos($file, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function getLocalFiles(string $dir): array
    {
        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $path = $file->getPathname();
                    if ($this->shouldSkipFile($path)) {
                        continue;
                    }
                    $rel = substr($path, strlen($dir) + 1);
                    $files[$rel] = $path;
                }
            }
        } catch (\Exception $e) {
            // Return empty array on error
            return [];
        }

        return $files;
    }

    private function loadRemoteLogFile(array $conn, string $path): array
    {
        // Expand tilde to home directory if needed
        if (str_starts_with($path, '~/')) {
            $path = '$HOME' . substr($path, 1);
        }
        
        $sshCmd = $this->buildSshCommand($conn);
        $cmd = $sshCmd . ' "cat ' . escapeshellarg($path) . ' 2>/dev/null"';

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            return [];
        }

        if ($output && str_starts_with($output[0], '<?php')) {
            array_shift($output);
        }

        return $output;
    }
}
