<?php
declare(strict_types=1);

$logsDir = __DIR__ . '/../logs';

$days = [
    '2026-05-01' => ['08:12:03', '09:45:17', '11:03:55', '12:30:01', '13:15:44', '14:02:28', '15:47:09', '17:22:33', '19:05:51', '22:58:12'],
    '2026-05-02' => ['07:55:10', '09:10:22', '10:44:38', '11:59:05', '13:28:47', '14:51:03', '16:03:29', '18:17:44', '20:42:11', '23:11:59'],
    '2026-05-03' => ['01:05:03', '01:18:27', '01:31:00', '02:44:15', '03:12:09'],
];

$entries = [
    ['DEBUG',    'App booted',              ['php' => '8.3.0', 'env' => 'development']],
    ['INFO',     'Request received',        ['method' => 'GET', 'uri' => '/']],
    ['INFO',     'User logged in',          ['user_id' => 12, 'email' => 'j*n@example.com']],
    ['WARNING',  'Login failed',            ['email' => 'j*n@example.com', 'attempts' => 3]],
    ['INFO',     'Order placed',            ['order_id' => 42, 'total' => 199.99]],
    ['ERROR',    'Payment failed',          ['order_id' => 42, 'reason' => 'insufficient funds']],
    ['DEBUG',    'Cache miss',              ['key' => 'user:12:profile']],
    ['WARNING',  'Slow query detected',     ['query' => 'SELECT * FROM orders', 'time_ms' => 850]],
    ['INFO',     'Email sent',              ['to' => 'j*n@example.com', 'subject' => 'Order confirmation']],
    ['CRITICAL', 'Database connection lost',['host' => 'db', 'port' => 3306]],
];

foreach ($days as $day => $times) {
    [$year, $month] = explode('-', $day);
    $dir  = "$logsDir/$year/$month";
    $file = "$dir/$day.log";

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $fp = fopen($file, 'a');
    foreach ($times as $i => $time) {
        $e = $entries[$i % count($entries)];
        [$level, $msg, $ctx] = $e;
        $line = sprintf("[%s %s] [%s] [seed_logs.php:%d] %s %s\n",
            $day, $time, $level, $i + 1, $msg,
            empty($ctx) ? '' : json_encode($ctx)
        );
        fwrite($fp, $line);
    }
    fclose($fp);

    echo "Written: $file\n";
}

echo "Done.\n";
