<?php

declare(strict_types=1);

/**
 * SSH Connection Test Endpoint
 */

require_once __DIR__ . '/../shared/SshConnectionTester.php';

SshConnectionTester::handleRequest();
