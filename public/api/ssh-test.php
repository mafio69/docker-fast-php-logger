<?php

declare(strict_types=1);

/**
 * SSH Connection Test Endpoint (public alias)
 */

require_once __DIR__ . '/../../app/shared/SshConnectionTester.php';

SshConnectionTester::handleRequest();
