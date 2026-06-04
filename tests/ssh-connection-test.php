<?php
/**
 * SSH Connection Test using SSH_FROG_PASS from .env.dev
 */

// Load .env.dev file
$envFile = dirname(__DIR__) . '/.env.dev';
if (!file_exists($envFile)) {
    die("Error: .env.dev file not found at: $envFile\n");
}

$env = parse_ini_file($envFile);
$password = $env['SSH_FROG_PASS'] ?? null;

if (!$password) {
    die("Error: SSH_FROG_PASS not found in .env.dev\n");
}

echo "=== SSH Connection Test ===\n";
echo "Host: frog01.mikr.us:10137\n";
echo "User: frog\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

// Test SSH connection using sshpass
$command = sprintf(
    'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p 10137 frog@frog01.mikr.us echo "CONNECTION_SUCCESS"',
    escapeshellarg($password)
);

echo "Executing SSH connection...\n";
$output = shell_exec($command . ' 2>&1');

if (strpos($output, 'CONNECTION_SUCCESS') !== false) {
    echo "✓ SSH connection successful!\n";
    echo "Connected to frog01.mikr.us:10137 as frog\n";
} else {
    echo "✗ SSH connection failed\n";
    echo "Output: " . trim($output) . "\n";
    exit(1);
}