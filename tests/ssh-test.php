<?php
/**
 * SSH Connection Test Script
 * Tests SSH connection to remote server using password authentication
 */

// Load environment variables
$envFile = dirname(__DIR__) . '/.env.dev';
if (!file_exists($envFile)) {
    die("Error: .env.dev file not found\n");
}

$env = parse_ini_file($envFile);
$host = $env['SSH_TEST_HOST'] ?? null;
$user = $env['SSH_TEST_USER'] ?? null;
$port = $env['SSH_TEST_PORT'] ?? 22;
$password = $env['SSH_TEST_PASSWORD'] ?? null;

if (!$host || !$user || !$password) {
    die("Error: SSH_TEST_HOST, SSH_TEST_USER, and SSH_TEST_PASSWORD must be set in .env.dev\n");
}

echo "=== SSH Connection Test ===\n";
echo "Host: $host:$port\n";
echo "User: $user\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

// Test 1: Check SSH binary
echo "1. Checking SSH binary...\n";
$sshBinary = shell_exec('which ssh 2>&1');
if ($sshBinary) {
    echo "   ✓ SSH binary found: " . trim($sshBinary) . "\n";
} else {
    echo "   ✗ SSH binary NOT found\n";
    exit(1);
}

// Test 2: Check sshpass
echo "\n2. Checking sshpass...\n";
$sshpass = shell_exec('which sshpass 2>&1');
if ($sshpass) {
    echo "   ✓ sshpass found: " . trim($sshpass) . "\n";
} else {
    echo "   ✗ sshpass NOT found\n";
    exit(1);
}

// Test 3: Test SSH connection with password
echo "\n3. Testing SSH connection with password...\n";
$command = sprintf(
    'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s echo "CONNECTION_SUCCESS"',
    escapeshellarg($password),
    (int)$port,
    escapeshellarg($user),
    escapeshellarg($host)
);

$output = shell_exec($command . ' 2>&1');

if (strpos($output, 'CONNECTION_SUCCESS') !== false) {
    echo "   ✓ SSH connection successful\n";
    echo "   Connected to $host:$port as $user\n";
} else {
    echo "   ✗ SSH connection failed\n";
    echo "   Output: " . trim($output) . "\n";
    exit(1);
}

// Test 4: Test SSH command execution
echo "\n4. Testing SSH command execution...\n";
$command = sprintf(
    'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s "uname -a"',
    escapeshellarg($password),
    (int)$port,
    escapeshellarg($user),
    escapeshellarg($host)
);

$output = shell_exec($command . ' 2>&1');

if ($output && strpos($output, 'Linux') !== false) {
    echo "   ✓ Command execution successful\n";
    echo "   Remote system info: " . trim($output) . "\n";
} else {
    echo "   ✗ Command execution failed\n";
    echo "   Output: " . trim($output) . "\n";
}

echo "\n=== All tests passed! ===\n";